<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\RecurringPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pruebas del comando Artisan que procesa
 * los pagos automáticos.
 *
 * Verifica que:
 * - el comando pueda ejecutarse,
 * - genere el withdrawal correspondiente,
 * - avance next_due_on,
 * - no duplique movimientos.
 */
class ProcessRecurringPaymentsCommandTest extends TestCase
{
    use RefreshDatabase;

    /**
     * El comando debe procesar un pago
     * cuya fecha de vencimiento ya llegó.
     */
    public function test_command_processes_due_recurring_payment(): void
    {
        /*
         * Fijamos temporalmente la fecha actual
         * para que la prueba sea completamente determinista.
         */
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-09-15 12:00:00')
        );

        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Internet',
            '2026-09-15'
        );

        /*
         * Ejecutamos el comando igual que lo hará
         * posteriormente Laravel Scheduler.
         */
        $this->artisan(
            'recurring-payments:process'
        )
            ->expectsOutput(
                'Pagos automáticos procesados: 1.'
            )
            ->assertSuccessful();

        /*
         * Debe haberse generado el retiro automático.
         */
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
         * El próximo vencimiento debe avanzar
         * al mismo día del mes siguiente.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'next_due_on' => '2026-10-15',
                'is_active' => true,
            ]
        );

        /*
         * Restauramos el reloj normal de Carbon
         * al finalizar la prueba.
         */
        CarbonImmutable::setTestNow();
    }

    /**
     * Ejecutar el comando nuevamente el mismo día
     * no debe generar un retiro duplicado.
     */
    public function test_command_does_not_duplicate_processed_payment(): void
    {
        CarbonImmutable::setTestNow(
            CarbonImmutable::parse('2026-09-15 12:00:00')
        );

        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Streaming',
            '2026-09-15'
        );

        /*
         * Primera ejecución:
         * procesa el vencimiento.
         */
        $this->artisan(
            'recurring-payments:process'
        )
            ->expectsOutput(
                'Pagos automáticos procesados: 1.'
            )
            ->assertSuccessful();

        /*
         * Segunda ejecución el mismo día:
         * no debe crear nada nuevo.
         */
        $this->artisan(
            'recurring-payments:process'
        )
            ->expectsOutput(
                'Pagos automáticos procesados: 0.'
            )
            ->assertSuccessful();

        /*
         * Debe existir solamente un movimiento
         * para ese pago y fecha programada.
         */
        $movementCount = Movement::query()
            ->where(
                'recurring_payment_id',
                $payment->id
            )
            ->whereDate(
                'scheduled_for',
                '2026-09-15'
            )
            ->count();

        $this->assertSame(
            1,
            $movementCount
        );

        CarbonImmutable::setTestNow();
    }

    /**
     * Crea un bolsillo activo para las pruebas.
     */
    private function createPocket(
        User $user
    ): Pocket {
        return Pocket::create([
            'user_id' => $user->id,
            'name' => 'Bolsillo de prueba',
            'description' => null,
            'is_active' => true,
        ]);
    }

    /**
     * Crea un pago automático listo
     * para ser procesado por el comando.
     */
    private function createRecurringPayment(
        User $user,
        Pocket $pocket,
        string $name,
        string $nextDueOn
    ): RecurringPayment {
        /*
         * El día mensual se obtiene de la fecha
         * utilizada para preparar la prueba.
         */
        $billingDay = CarbonImmutable::parse(
            $nextDueOn
        )->day;

        return RecurringPayment::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'name' => $name,
            'description' => 'Pago generado para pruebas.',
            'amount' => '100.00',
            'frequency' => 'monthly',
            'billing_day' => $billingDay,
            'starts_on' => $nextDueOn,
            'next_due_on' => $nextDueOn,
            'ends_on' => null,
            'is_active' => true,
        ]);
    }
}