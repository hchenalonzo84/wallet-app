<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MovementApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_movements(): void
    {
        $response = $this->getJson('/api/movements');

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'No autenticado.'
            );
    }

    public function test_authenticated_user_can_create_movement(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/movements', [
            'pocket_id' => $pocket->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Movimiento de prueba',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Movimiento registrado correctamente.'
            )
            ->assertJsonPath(
                'data.user_id',
                $user->id
            )
            ->assertJsonPath(
                'data.pocket_id',
                $pocket->id
            )
            ->assertJsonPath(
                'data.type',
                'deposit'
            )
            ->assertJsonPath(
                'data.amount',
                '500.00'
            );

        $this->assertDatabaseHas('movements', [
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Movimiento de prueba',
        ]);
    }

    public function test_user_only_lists_own_movements(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $userPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $otherPocket = Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $userPocket->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Movimiento propio',
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Movement::create([
            'user_id' => $otherUser->id,
            'pocket_id' => $otherPocket->id,
            'type' => 'deposit',
            'amount' => 900.00,
            'description' => 'Movimiento de otro usuario',
            'occurred_at' => '2026-08-24T11:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/movements');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'description' => 'Movimiento propio',
            ])
            ->assertJsonMissing([
                'description' => 'Movimiento de otro usuario',
            ]);
    }

    public function test_user_can_view_own_movement(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $movement = Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Movimiento propio',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/movements/{$movement->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $movement->id
            )
            ->assertJsonPath(
                'data.user_id',
                $user->id
            )
            ->assertJsonPath(
                'data.pocket_id',
                $pocket->id
            );
    }

    public function test_user_cannot_view_another_users_movement(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPocket = Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $otherMovement = Movement::create([
            'user_id' => $otherUser->id,
            'pocket_id' => $otherPocket->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => 'Movimiento privado',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/movements/{$otherMovement->id}"
        );

        $response->assertNotFound();
    }

    public function test_user_cannot_create_movement_in_inactive_pocket(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/movements', [
            'pocket_id' => $pocket->id,
            'type' => 'deposit',
            'amount' => 100.00,
            'description' => 'Movimiento inválido',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'pocket_id',
            ]);

        $this->assertDatabaseCount(
            'movements',
            0
        );
    }

    public function test_user_cannot_create_movement_in_another_users_pocket(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPocket = Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Ahorro privado',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/movements', [
            'pocket_id' => $otherPocket->id,
            'type' => 'deposit',
            'amount' => 100.00,
            'description' => 'Intento no autorizado',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'pocket_id',
            ]);

        $this->assertDatabaseCount(
            'movements',
            0
        );
    }

    public function test_transfer_types_cannot_be_created_through_movements_endpoint(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/movements', [
            'pocket_id' => $pocket->id,
            'type' => 'transfer_out',
            'amount' => 100.00,
            'description' => 'Transferencia inválida',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'type',
            ]);

        $this->assertDatabaseCount(
            'movements',
            0
        );
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/movements', [
            'pocket_id' => $pocket->id,
            'type' => 'deposit',
            'amount' => 0,
            'description' => 'Monto inválido',
            'occurred_at' => '2026-08-24T10:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'amount',
            ]);

        $this->assertDatabaseCount(
            'movements',
            0
        );
    }
}