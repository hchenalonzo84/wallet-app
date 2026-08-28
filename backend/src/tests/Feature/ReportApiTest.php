<?php

namespace Tests\Feature;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_reports(): void
    {
        $response = $this->getJson(
            '/api/reports/period?type=monthly&year=2026&month=8'
        );

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'No autenticado.'
            );
    }

    public function test_monthly_report_calculates_balances_and_movements(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $this->createMovement(
            $user,
            $pocket,
            'opening_balance',
            '1000.00',
            '2026-07-15T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '500.00',
            '2026-08-05T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'withdrawal',
            '100.00',
            '2026-08-10T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'adjustment_in',
            '25.00',
            '2026-08-15T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'adjustment_out',
            '10.00',
            '2026-08-16T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'transfer_in',
            '50.00',
            '2026-08-17T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'transfer_out',
            '20.00',
            '2026-08-18T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=monthly&year=2026&month=8'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.period.type',
                'monthly'
            )
            ->assertJsonPath(
                'data.period.from',
                '2026-08-01'
            )
            ->assertJsonPath(
                'data.period.to',
                '2026-08-31'
            )
            ->assertJsonPath(
                'data.summary.opening_balance',
                '1000.00'
            )
            ->assertJsonPath(
                'data.summary.deposits',
                '500.00'
            )
            ->assertJsonPath(
                'data.summary.withdrawals',
                '100.00'
            )
            ->assertJsonPath(
                'data.summary.adjustments_in',
                '25.00'
            )
            ->assertJsonPath(
                'data.summary.adjustments_out',
                '10.00'
            )
            ->assertJsonPath(
                'data.summary.transfers_in',
                '50.00'
            )
            ->assertJsonPath(
                'data.summary.transfers_out',
                '20.00'
            )
            ->assertJsonPath(
                'data.summary.entries',
                '575.00'
            )
            ->assertJsonPath(
                'data.summary.exits',
                '130.00'
            )
            ->assertJsonPath(
                'data.summary.net_movement',
                '445.00'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '1445.00'
            )
            ->assertJsonPath(
                'data.summary.movement_count',
                6
            );
    }

    public function test_opening_balance_created_inside_period_is_counted_as_entry(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $this->createMovement(
            $user,
            $pocket,
            'opening_balance',
            '300.00',
            '2026-08-01T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '50.00',
            '2026-08-02T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=monthly&year=2026&month=8'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.summary.opening_balance',
                '0.00'
            )
            ->assertJsonPath(
                'data.summary.opening_entries',
                '300.00'
            )
            ->assertJsonPath(
                'data.summary.entries',
                '350.00'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '350.00'
            )
            ->assertJsonPath(
                'data.summary.movement_count',
                2
            );
    }

    public function test_quarterly_report_uses_correct_period(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $this->createMovement(
            $user,
            $pocket,
            'opening_balance',
            '10.00',
            '2026-06-30T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '100.00',
            '2026-07-10T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'withdrawal',
            '20.00',
            '2026-08-10T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '50.00',
            '2026-09-30T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '1000.00',
            '2026-10-01T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=quarterly&year=2026&quarter=3'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.period.from',
                '2026-07-01'
            )
            ->assertJsonPath(
                'data.period.to',
                '2026-09-30'
            )
            ->assertJsonPath(
                'data.summary.opening_balance',
                '10.00'
            )
            ->assertJsonPath(
                'data.summary.deposits',
                '150.00'
            )
            ->assertJsonPath(
                'data.summary.withdrawals',
                '20.00'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '140.00'
            )
            ->assertJsonPath(
                'data.summary.movement_count',
                3
            );
    }

    public function test_semiannual_report_uses_correct_period(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $this->createMovement(
            $user,
            $pocket,
            'opening_balance',
            '75.00',
            '2026-06-30T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '200.00',
            '2026-11-10T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=semiannual&year=2026&semester=2'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.period.from',
                '2026-07-01'
            )
            ->assertJsonPath(
                'data.period.to',
                '2026-12-31'
            )
            ->assertJsonPath(
                'data.summary.opening_balance',
                '75.00'
            )
            ->assertJsonPath(
                'data.summary.deposits',
                '200.00'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '275.00'
            );
    }

    public function test_annual_report_uses_correct_period(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $this->createMovement(
            $user,
            $pocket,
            'opening_balance',
            '100.00',
            '2025-12-31T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '200.00',
            '2026-01-10T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'withdrawal',
            '50.00',
            '2026-12-20T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=annual&year=2026'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.period.from',
                '2026-01-01'
            )
            ->assertJsonPath(
                'data.period.to',
                '2026-12-31'
            )
            ->assertJsonPath(
                'data.summary.opening_balance',
                '100.00'
            )
            ->assertJsonPath(
                'data.summary.net_movement',
                '150.00'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '250.00'
            )
            ->assertJsonPath(
                'data.summary.movement_count',
                2
            );
    }

    public function test_custom_report_respects_date_range(): void
    {
        $user = User::factory()->create();

        $pocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '100.00',
            '2026-08-05T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '50.00',
            '2026-08-10T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'withdrawal',
            '20.00',
            '2026-08-20T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $pocket,
            'deposit',
            '1000.00',
            '2026-08-21T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=custom'
            . '&from=2026-08-10'
            . '&to=2026-08-20'
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.period.from',
                '2026-08-10'
            )
            ->assertJsonPath(
                'data.period.to',
                '2026-08-20'
            )
            ->assertJsonPath(
                'data.summary.opening_balance',
                '100.00'
            )
            ->assertJsonPath(
                'data.summary.deposits',
                '50.00'
            )
            ->assertJsonPath(
                'data.summary.withdrawals',
                '20.00'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '130.00'
            )
            ->assertJsonPath(
                'data.summary.movement_count',
                2
            );
    }

    public function test_report_can_be_filtered_by_pocket(): void
    {
        $user = User::factory()->create();

        $savings = $this->createPocket(
            $user,
            'Ahorro'
        );

        $daily = $this->createPocket(
            $user,
            'Uso diario'
        );

        $this->createMovement(
            $user,
            $savings,
            'deposit',
            '500.00',
            '2026-08-10T12:00:00Z'
        );

        $this->createMovement(
            $user,
            $daily,
            'deposit',
            '200.00',
            '2026-08-10T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=monthly'
            . '&year=2026'
            . '&month=8'
            . "&pocket_id={$savings->id}"
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.pockets'
            )
            ->assertJsonPath(
                'data.pockets.0.pocket.id',
                $savings->id
            )
            ->assertJsonPath(
                'data.pockets.0.pocket.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '500.00'
            )
            ->assertJsonMissing([
                'name' => 'Uso diario',
            ]);
    }

    public function test_user_only_sees_own_financial_report(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $ownPocket = $this->createPocket(
            $user,
            'Ahorro'
        );

        $otherPocket = $this->createPocket(
            $otherUser,
            'Privado'
        );

        $this->createMovement(
            $user,
            $ownPocket,
            'deposit',
            '100.00',
            '2026-08-10T12:00:00Z'
        );

        $this->createMovement(
            $otherUser,
            $otherPocket,
            'deposit',
            '9999.00',
            '2026-08-10T12:00:00Z'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=monthly&year=2026&month=8'
        );

        $response
            ->assertOk()
            ->assertJsonCount(
                1,
                'data.pockets'
            )
            ->assertJsonPath(
                'data.summary.closing_balance',
                '100.00'
            )
            ->assertJsonMissing([
                'name' => 'Privado',
            ]);
    }

    public function test_user_cannot_filter_report_by_another_users_pocket(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPocket = $this->createPocket(
            $otherUser,
            'Privado'
        );

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=monthly'
            . '&year=2026'
            . '&month=8'
            . "&pocket_id={$otherPocket->id}"
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'pocket_id',
            ]);
    }

    public function test_custom_report_rejects_invalid_date_range(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->getJson(
            '/api/reports/period?type=custom'
            . '&from=2026-08-20'
            . '&to=2026-08-10'
        );

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'to',
            ]);
    }

    private function createPocket(
        User $user,
        string $name
    ): Pocket {
        return Pocket::create([
            'user_id' => $user->id,
            'name' => $name,
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function createMovement(
        User $user,
        Pocket $pocket,
        string $type,
        string $amount,
        string $occurredAt
    ): Movement {
        return Movement::create([
            'user_id' => $user->id,
            'pocket_id' => $pocket->id,
            'type' => $type,
            'amount' => $amount,
            'description' => null,
            'occurred_at' => $occurredAt,
        ]);
    }
}