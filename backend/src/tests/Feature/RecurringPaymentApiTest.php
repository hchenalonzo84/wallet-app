<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\RecurringPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Pruebas de integración para la API de pagos automáticos.
 *
 * Verifica principalmente:
 * - autenticación,
 * - creación,
 * - aislamiento entre usuarios,
 * - validaciones,
 * - edición,
 * - cálculo de fechas,
 * - desactivación lógica.
 */
class RecurringPaymentApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un usuario autenticado puede crear un pago automático
     * utilizando uno de sus bolsillos activos.
     */
    public function test_user_can_create_recurring_payment(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Uso diario'
        );

        /*
         * Simula una sesión autenticada mediante Sanctum.
         */
        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/recurring-payments',
            [
                'pocket_id' => $pocket->id,
                'name' => 'Internet',
                'description' => 'Servicio mensual',
                'amount' => '250.50',
                'starts_on' => '2026-09-15',
                'ends_on' => null,
            ]
        );

        /*
         * HTTP 201 confirma que el recurso fue creado.
         */
        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Pago automático creado correctamente.'
            )
            ->assertJsonPath(
                'data.name',
                'Internet'
            )
            ->assertJsonPath(
                'data.billing_day',
                15
            )
            ->assertJsonPath(
                'data.frequency',
                'monthly'
            )
            ->assertJsonPath(
                'data.is_active',
                true
            );

        /*
         * También verificamos directamente lo almacenado
         * en PostgreSQL.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'user_id' => $user->id,
                'pocket_id' => $pocket->id,
                'name' => 'Internet',
                'amount' => '250.50',
                'frequency' => 'monthly',
                'billing_day' => 15,
                'starts_on' => '2026-09-15',
                'next_due_on' => '2026-09-15',
                'is_active' => true,
            ]
        );
    }

    /**
     * No se permite crear un pago automático
     * utilizando un bolsillo perteneciente a otro usuario.
     */
    public function test_user_cannot_use_another_users_pocket(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherPocket = $this->createPocket(
            $otherUser,
            'Bolsillo ajeno'
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/recurring-payments',
            [
                'pocket_id' => $otherPocket->id,
                'name' => 'Pago inválido',
                'amount' => '100.00',
                'starts_on' => '2026-09-10',
            ]
        );

        /*
         * pocket_id debe fallar porque el bolsillo
         * no pertenece al usuario autenticado.
         */
        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'pocket_id',
            ]);

        $this->assertDatabaseCount(
            'recurring_payments',
            0
        );
    }

    /**
     * No se pueden asociar nuevos pagos automáticos
     * a bolsillos que estén desactivados.
     */
    public function test_user_cannot_use_inactive_pocket(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Bolsillo inactivo',
            false
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/recurring-payments',
            [
                'pocket_id' => $pocket->id,
                'name' => 'Suscripción',
                'amount' => '75.00',
                'starts_on' => '2026-09-20',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'pocket_id',
            ]);

        $this->assertDatabaseCount(
            'recurring_payments',
            0
        );
    }

    /**
     * La fecha final no puede ser anterior
     * a la fecha inicial al crear el pago.
     */
    public function test_end_date_cannot_be_before_start_date(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Servicios'
        );

        Sanctum::actingAs($user);

        $response = $this->postJson(
            '/api/recurring-payments',
            [
                'pocket_id' => $pocket->id,
                'name' => 'Servicio temporal',
                'amount' => '80.00',
                'starts_on' => '2026-10-15',
                'ends_on' => '2026-10-10',
            ]
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'ends_on',
            ]);
    }

    /**
     * El listado solamente debe mostrar
     * pagos pertenecientes al usuario autenticado.
     */
    public function test_index_returns_only_authenticated_users_payments(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userPocket = $this->createPocket(
            $user,
            'Principal'
        );

        $otherPocket = $this->createPocket(
            $otherUser,
            'Otro usuario'
        );

        /*
         * Creamos un pago para cada usuario directamente
         * en la base de datos para preparar el escenario.
         */
        $ownPayment = $this->createRecurringPayment(
            $user,
            $userPocket,
            'Internet'
        );

        $this->createRecurringPayment(
            $otherUser,
            $otherPocket,
            'Pago ajeno'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/recurring-payments'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $ownPayment->id
            )
            ->assertJsonPath(
                'data.0.name',
                'Internet'
            );
    }

    /**
     * El usuario puede consultar uno de sus propios
     * pagos automáticos.
     */
    public function test_user_can_show_own_recurring_payment(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Principal'
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Seguro'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/recurring-payments/{$payment->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $payment->id
            )
            ->assertJsonPath(
                'data.name',
                'Seguro'
            );
    }

    /**
     * Un usuario no puede consultar
     * un pago automático perteneciente a otro usuario.
     */
    public function test_user_cannot_show_another_users_recurring_payment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherPocket = $this->createPocket(
            $otherUser,
            'Privado'
        );

        $payment = $this->createRecurringPayment(
            $otherUser,
            $otherPocket,
            'Pago privado'
        );

        Sanctum::actingAs($user);

        /*
         * Se devuelve 404 para no revelar
         * recursos pertenecientes a otros usuarios.
         */
        $this->getJson(
            "/api/recurring-payments/{$payment->id}"
        )->assertNotFound();
    }

    /**
     * Permite modificar únicamente algunos campos
     * utilizando una petición PATCH.
     */
    public function test_user_can_update_recurring_payment(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Principal'
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Internet'
        );

        Sanctum::actingAs($user);

        /*
         * Solamente modificamos nombre y monto.
         * Los demás datos deben conservarse.
         */
        $response = $this->patchJson(
            "/api/recurring-payments/{$payment->id}",
            [
                'name' => 'Internet hogar',
                'amount' => '325.75',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Pago automático actualizado correctamente.'
            )
            ->assertJsonPath(
                'data.name',
                'Internet hogar'
            )
            ->assertJsonPath(
                'data.amount',
                '325.75'
            );

        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'name' => 'Internet hogar',
                'amount' => '325.75',
            ]
        );
    }

    /**
     * Cambiar la fecha inicial debe recalcular
     * billing_day y next_due_on.
     */
    public function test_changing_start_date_recalculates_schedule(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Servicios'
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Suscripción'
        );

        Sanctum::actingAs($user);

        $response = $this->patchJson(
            "/api/recurring-payments/{$payment->id}",
            [
                'starts_on' => '2026-10-31',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.billing_day',
                31
            );

        /*
         * La nueva fecha inicial pasa a ser
         * el próximo vencimiento del pago.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'billing_day' => 31,
                'starts_on' => '2026-10-31',
                'next_due_on' => '2026-10-31',
            ]
        );
    }

    /**
     * DELETE realiza una desactivación lógica.
     *
     * El registro permanece en la base de datos
     * para conservar su historial.
     */
    public function test_user_can_deactivate_recurring_payment(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Principal'
        );

        $payment = $this->createRecurringPayment(
            $user,
            $pocket,
            'Streaming'
        );

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/recurring-payments/{$payment->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Pago automático desactivado correctamente.'
            )
            ->assertJsonPath(
                'data.is_active',
                false
            );

        /*
         * Comprobamos que el registro no fue eliminado.
         */
        $this->assertDatabaseHas(
            'recurring_payments',
            [
                'id' => $payment->id,
                'is_active' => false,
            ]
        );

        $this->assertDatabaseCount(
            'recurring_payments',
            1
        );
    }

    /**
     * Los endpoints financieros requieren autenticación.
     */
    public function test_recurring_payments_require_authentication(): void
    {
        $this->getJson(
            '/api/recurring-payments'
        )->assertUnauthorized();
    }

    /**
     * Crea un bolsillo de prueba.
     *
     * Este método evita repetir el mismo código
     * de preparación en cada prueba.
     */
    private function createPocket(
        User $user,
        string $name,
        bool $isActive = true
    ): Pocket {
        return Pocket::create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => null,
            'is_active' => $isActive,
        ]);
    }

    /**
     * Crea directamente un pago automático de prueba.
     *
     * Se utiliza cuando la prueba necesita un registro
     * existente antes de llamar al endpoint correspondiente.
     */
    private function createRecurringPayment(
        User $user,
        Pocket $pocket,
        string $name
    ): RecurringPayment {
        return RecurringPayment::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'name' => $name,
            'description' => null,
            'amount' => '100.00',
            'frequency' => 'monthly',
            'billing_day' => 15,
            'starts_on' => '2026-09-15',
            'next_due_on' => '2026-09-15',
            'ends_on' => null,
            'is_active' => true,
        ]);
    }
}