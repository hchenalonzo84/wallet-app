<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransferApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_create_transfer(): void
    {
        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => 1,
            'to_pocket_id' => 2,
            'amount' => 100.00,
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'No autenticado.'
            );
    }

    public function test_user_can_transfer_between_own_active_pockets(): void
    {
        $user = User::factory()->create();

        $fromPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $toPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $fromPocket->id,
            'type' => 'opening_balance',
            'amount' => 500.00,
            'description' => null,
            'occurred_at' => '2026-08-27T10:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => $fromPocket->id,
            'to_pocket_id' => $toPocket->id,
            'amount' => 100.00,
            'description' => 'Transferencia de prueba',
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Transferencia realizada correctamente.'
            )
            ->assertJsonPath(
                'data.amount',
                '100.00'
            )
            ->assertJsonPath(
                'data.from_movement.type',
                'transfer_out'
            )
            ->assertJsonPath(
                'data.from_movement.pocket_id',
                $fromPocket->id
            )
            ->assertJsonPath(
                'data.to_movement.type',
                'transfer_in'
            )
            ->assertJsonPath(
                'data.to_movement.pocket_id',
                $toPocket->id
            );

        $groupId = $response->json(
            'data.transfer_group_id'
        );

        $this->assertNotEmpty($groupId);

        $this->assertDatabaseHas('movements', [
            'user_id' => $user->id,
            'pocket_id' => $fromPocket->id,
            'type' => 'transfer_out',
            'amount' => 100.00,
            'transfer_group_id' => $groupId,
        ]);

        $this->assertDatabaseHas('movements', [
            'user_id' => $user->id,
            'pocket_id' => $toPocket->id,
            'type' => 'transfer_in',
            'amount' => 100.00,
            'transfer_group_id' => $groupId,
        ]);

        $this->assertSame(
            $response->json(
                'data.from_movement.transfer_group_id'
            ),
            $response->json(
                'data.to_movement.transfer_group_id'
            )
        );
    }

    public function test_transfer_updates_both_pocket_balances(): void
    {
        $user = User::factory()->create();

        $fromPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $toPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $fromPocket->id,
            'type' => 'opening_balance',
            'amount' => 500.00,
            'description' => null,
            'occurred_at' => '2026-08-27T10:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/transfers', [
            'from_pocket_id' => $fromPocket->id,
            'to_pocket_id' => $toPocket->id,
            'amount' => 100.00,
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ])->assertCreated();

        $response = $this->getJson('/api/balances');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.balance',
                '400.00'
            )
            ->assertJsonPath(
                'data.1.balance',
                '100.00'
            )
            ->assertJsonPath(
                'meta.total_balance',
                '500.00'
            );
    }

    public function test_user_cannot_transfer_more_than_available_balance(): void
    {
        $user = User::factory()->create();

        $fromPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $toPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $fromPocket->id,
            'type' => 'opening_balance',
            'amount' => 400.00,
            'description' => null,
            'occurred_at' => '2026-08-27T10:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => $fromPocket->id,
            'to_pocket_id' => $toPocket->id,
            'amount' => 500.00,
            'description' => 'Saldo insuficiente',
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'amount',
            ]);

        $this->assertDatabaseMissing('movements', [
            'type' => 'transfer_out',
        ]);

        $this->assertDatabaseMissing('movements', [
            'type' => 'transfer_in',
        ]);
    }

    public function test_user_cannot_transfer_to_same_pocket(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => $pocket->id,
            'to_pocket_id' => $pocket->id,
            'amount' => 100.00,
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to_pocket_id',
            ]);
    }

    public function test_user_cannot_transfer_using_inactive_pocket(): void
    {
        $user = User::factory()->create();

        $fromPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $toPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => false,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => $fromPocket->id,
            'to_pocket_id' => $toPocket->id,
            'amount' => 100.00,
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to_pocket_id',
            ]);
    }

    public function test_user_cannot_transfer_using_another_users_pocket(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $ownPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $otherPocket = Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Privado',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => $ownPocket->id,
            'to_pocket_id' => $otherPocket->id,
            'amount' => 100.00,
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to_pocket_id',
            ]);
    }

    public function test_transfer_amount_must_be_greater_than_zero(): void
    {
        $user = User::factory()->create();

        $fromPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $toPocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/transfers', [
            'from_pocket_id' => $fromPocket->id,
            'to_pocket_id' => $toPocket->id,
            'amount' => 0,
            'occurred_at' => '2026-08-27T15:30:00-06:00',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'amount',
            ]);
    }
}