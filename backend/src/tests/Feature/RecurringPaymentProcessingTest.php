<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\RecurringPayment;
use App\Models\User;
use App\Services\RecurringPaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del procesamiento automático
 * de pagos recurrentes.
 *
 * Verifica:
 * - creación de retiros,
 * - avance de fechas,
 * - días 29/30/31,
 * - recuperación de meses pendientes,
 * - protección contra duplicados,
 * - fecha final,
 * - bolsillos inactivos.
 */
class RecurringPaymentProcessingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un pago vencido debe generar un withdrawal
     * y avanzar al siguiente mes.
     */
    public function test_due_payment_creates_withdrawal(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Internet',
            15,
            '2026-09-15'
        );

        $service = app(
            RecurringPaymentService::class
        );

        $created = $service->processDuePayments(
            CarbonImmutable::parse('2026-09-15')
        );

        /*
         * Debe haberse creado exactamente
         * un movimiento automático.
         */
        $this->assertSame(
            1,
            $created
        );

        $this->assertDatabaseHas(
            'movements',
            [
                'user_id' => $user->id,
                'pocket_id' => $pocket->id,
                'type' => 'withdrawal',
                'amount' => '100.00',
                'recurring_payment_id' => $payment->id,
                'scheduled_for' => '2026-09-15',
            ]
        );

        /*
         * El siguiente vencimiento debe ser
         * el mismo día del siguiente mes.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'billing_day' => 15,
                'next_due_on' => '2026-10-15',
                'is_active' => true,
            ]
        );
    }

    /**
     * billing_day = 31 debe adaptarse a febrero
     * y posteriormente regresar al día 31.
     */
    public function test_day_31_falls_back_and_returns_to_31(): void
    {
        $user = User::factory()->create();
        $pocket = $this->createPocket($user);

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Seguro',
            31,
            '2026-01-31'
        );

        $service = app(
            RecurringPaymentService::class
        );

        /*
         * Procesamos hasta febrero.
         *
         * Esto debe generar enero y febrero.
         */
        $created = $service->processDuePayments(
            CarbonImmutable::parse('2026-02-28')
        );

        $this->assertSame(
            2,
            $created
        );

        $this->assertDatabaseHas(
            'movements',
            [
                'recurring_payment_id' => $payment->id,
                'scheduled_for' => '2026-01-31',
            ]
        );

        $this->assertDatabaseHas(
            'movements',
            [
                'recurring_payment_id' => $payment->id,
                'scheduled_for' => '2026-02-28',
            ]
        );

        /*
         * El día configurado sigue siendo 31,
         * por lo que marzo vuelve al día 31.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'billing_day' => 31,
                'next_due_on' => '2026-03-31',
            ]
        );
    }

    /**
     * Ejecutar el procesador más de una vez
     * no debe duplicar un mismo vencimiento.
     */
    public function test_same_due_date_is_not_processed_twice(): void
    {
        $user = User::factory()->create();
        $pocket = $this->createPocket($user);

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Streaming',
            10,
            '2026-09-10'
        );

        $service = app(
            RecurringPaymentService::class
        );

        $date = CarbonImmutable::parse(
            '2026-09-10'
        );

        /*
         * Primera ejecución:
         * crea el movimiento.
         */
        $firstRun = $service->processDuePayments(
            $date
        );

        /*
         * Segunda ejecución:
         * ya no debe crear otro.
         */
        $secondRun = $service->processDuePayments(
            $date
        );

        $this->assertSame(
            1,
            $firstRun
        );

        $this->assertSame(
            0,
            $secondRun
        );

        $this->assertSame(
            1,
            Movement::query()
                ->where(
                    'recurring_payment_id',
                    $payment->id
                )
                ->whereDate(
                    'scheduled_for',
                    '2026-09-10'
                )
                ->count()
        );
    }

    /**
     * Si el scheduler estuvo detenido,
     * debe recuperar todos los meses vencidos.
     */
    public function test_missed_months_are_caught_up(): void
    {
        $user = User::factory()->create();
        $pocket = $this->createPocket($user);

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Servicio mensual',
            31,
            '2026-01-31'
        );

        $service = app(
            RecurringPaymentService::class
        );

        /*
         * Simulamos que el sistema no procesó
         * pagos hasta el 30 de abril.
         */
        $created = $service->processDuePayments(
            CarbonImmutable::parse('2026-04-30')
        );

        /*
         * Deben recuperarse:
         * enero, febrero, marzo y abril.
         */
        $this->assertSame(
            4,
            $created
        );

        foreach ([
            '2026-01-31',
            '2026-02-28',
            '2026-03-31',
            '2026-04-30',
        ] as $scheduledFor) {
            $this->assertDatabaseHas(
                'movements',
                [
                    'recurring_payment_id' => $payment->id,
                    'scheduled_for' => $scheduledFor,
                ]
            );
        }

        /*
         * El próximo cobro vuelve al día 31.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'next_due_on' => '2026-05-31',
            ]
        );
    }

    /**
     * La fecha final impide generar pagos
     * posteriores al período configurado.
     */
    public function test_payment_stops_after_end_date(): void
    {
        $user = User::factory()->create();
        $pocket = $this->createPocket($user);

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Servicio temporal',
            31,
            '2026-01-31',
            '2026-03-15'
        );

        $service = app(
            RecurringPaymentService::class
        );

        /*
         * Enero y febrero son válidos.
         *
         * Marzo 31 quedaría después de ends_on,
         * por lo que no debe generarse.
         */
        $created = $service->processDuePayments(
            CarbonImmutable::parse('2026-04-30')
        );

        $this->assertSame(
            2,
            $created
        );

        $this->assertDatabaseMissing(
            'movements',
            [
                'recurring_payment_id' => $payment->id,
                'scheduled_for' => '2026-03-31',
            ]
        );

        /*
         * Al no existir otro vencimiento válido,
         * la recurrencia termina automáticamente.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'is_active' => false,
                'next_due_on' => null,
            ]
        );
    }

    /**
     * Un pago desactivado manualmente
     * nunca debe ser ejecutado.
     */
    public function test_inactive_recurring_payment_is_not_processed(): void
    {
        $user = User::factory()->create();
        $pocket = $this->createPocket($user);

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Pago desactivado',
            15,
            '2026-09-15',
            null,
            false
        );

        $service = app(
            RecurringPaymentService::class
        );

        $created = $service->processDuePayments(
            CarbonImmutable::parse('2026-09-15')
        );

        $this->assertSame(
            0,
            $created
        );

        $this->assertDatabaseMissing(
            'movements',
            [
                'recurring_payment_id' => $payment->id,
            ]
        );
    }

    /**
     * Si el bolsillo fue desactivado,
     * no se genera el retiro automático.
     *
     * También se desactiva la recurrencia
     * para evitar intentos repetidos cada día.
     */
    public function test_inactive_pocket_stops_recurring_payment(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            false
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Pago bloqueado',
            15,
            '2026-09-15'
        );

        $service = app(
            RecurringPaymentService::class
        );

        $created = $service->processDuePayments(
            CarbonImmutable::parse('2026-09-15')
        );

        $this->assertSame(
            0,
            $created
        );

        $this->assertDatabaseMissing(
            'movements',
            [
                'recurring_payment_id' => $payment->id,
            ]
        );

        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'is_active' => false,
            ]
        );
    }

    /**
     * Crea un bolsillo para los escenarios de prueba.
     */
    private function createPocket(
        User $user,
        bool $isActive = true
    ): Pocket {
        return Pocket::create([
            'user_id' => $user->id,
            'name' => 'Bolsillo de prueba',
            'description' => null,
            'is_active' => $isActive,
        ]);
    }

    /**
     * Crea un pago automático directamente
     * en la base de datos para preparar cada prueba.
     */
    private function createRecurringPayment(
        User $user,
        Pocket $pocket,
        string $name,
        int $billingDay,
        string $nextDueOn,
        ?string $endsOn = null,
        bool $isActive = true
    ): RecurringPayment {
        return RecurringPayment::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'name' => $name,
            'description' => 'Descripción de prueba',
            'amount' => '100.00',
            'frequency' => 'monthly',
            'billing_day' => $billingDay,
            'starts_on' => $nextDueOn,
            'next_due_on' => $nextDueOn,
            'ends_on' => $endsOn,
            'is_active' => $isActive,
        ]);
    }
}