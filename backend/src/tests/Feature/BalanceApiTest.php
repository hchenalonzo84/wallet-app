<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BalanceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_balances(): void
    {
        $response = $this->getJson('/api/balances');

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'No autenticado.'
            );
    }

    public function test_balance_is_calculated_from_all_movement_types(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $movements = [
            [
                'type' => 'opening_balance',
                'amount' => 1000.00,
            ],
            [
                'type' => 'deposit',
                'amount' => 500.00,
            ],
            [
                'type' => 'withdrawal',
                'amount' => 100.00,
            ],
            [
                'type' => 'adjustment_in',
                'amount' => 50.00,
            ],
            [
                'type' => 'adjustment_out',
                'amount' => 25.00,
            ],
            [
                'type' => 'transfer_in',
                'amount' => 200.00,
            ],
            [
                'type' => 'transfer_out',
                'amount' => 75.00,
            ],
        ];

        foreach ($movements as $movement) {
            Movement::create([
                'user_id' => $user->id,
                'pocket_id' => $pocket->id,
                'type' => $movement['type'],
                'amount' => $movement['amount'],
                'description' => null,
                'occurred_at' => '2026-08-24T10:00:00-06:00',
            ]);
        }

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/balances');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $pocket->id
            )
            ->assertJsonPath(
                'data.0.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.0.balance',
                '1550.00'
            )
            ->assertJsonPath(
                'meta.total_balance',
                '1550.00'
            );
    }

    public function test_pocket_without_movements_has_zero_balance(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Sin movimientos',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/balances');

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.0.id',
                $pocket->id
            )
            ->assertJsonPath(
                'data.0.balance',
                '0.00'
            )
            ->assertJsonPath(
                'meta.total_balance',
                '0.00'
            );
    }

    public function test_total_balance_is_sum_of_all_user_pockets(): void
    {
        $user = User::factory()->create();

        $savings = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        $daily = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $savings->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => null,
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $daily->id,
            'type' => 'opening_balance',
            'amount' => 300.00,
            'description' => null,
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $daily->id,
            'type' => 'withdrawal',
            'amount' => 100.00,
            'description' => null,
            'occurred_at' => '2026-08-24T11:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/balances');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath(
                'data.0.balance',
                '500.00'
            )
            ->assertJsonPath(
                'data.1.balance',
                '200.00'
            )
            ->assertJsonPath(
                'meta.total_balance',
                '700.00'
            );
    }

    public function test_inactive_pocket_remains_visible_in_balances(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => false,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'type' => 'opening_balance',
            'amount' => 250.00,
            'description' => null,
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/balances');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.id',
                $pocket->id
            )
            ->assertJsonPath(
                'data.0.is_active',
                false
            )
            ->assertJsonPath(
                'data.0.balance',
                '250.00'
            )
            ->assertJsonPath(
                'meta.total_balance',
                '250.00'
            );
    }

    public function test_user_only_sees_balances_for_own_pockets(): void
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
            'name' => 'Privado',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $userPocket->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'description' => null,
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Movement::create([
            'user_id' => $otherUser->id,
            'pocket_id' => $otherPocket->id,
            'type' => 'deposit',
            'amount' => 9999.00,
            'description' => null,
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/balances');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath(
                'data.0.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.0.balance',
                '500.00'
            )
            ->assertJsonPath(
                'meta.total_balance',
                '500.00'
            )
            ->assertJsonMissing([
                'name' => 'Privado',
            ]);
    }

    public function test_user_can_view_balance_of_own_pocket(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'type' => 'deposit',
            'amount' => 750.00,
            'description' => null,
            'occurred_at' => '2026-08-24T10:00:00-06:00',
        ]);

        Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'type' => 'withdrawal',
            'amount' => 125.00,
            'description' => null,
            'occurred_at' => '2026-08-24T11:00:00-06:00',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/pockets/{$pocket->id}/balance"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $pocket->id
            )
            ->assertJsonPath(
                'data.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.balance',
                '625.00'
            );
    }

    public function test_user_cannot_view_balance_of_another_users_pocket(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPocket = Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Privado',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/pockets/{$otherPocket->id}/balance"
        );

        $response->assertNotFound();
    }
}