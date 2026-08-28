<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class ReportService
{
    public function generate(
        User $user,
        array $filters
    ): array {
        [$from, $to] = $this->resolvePeriod($filters);

        $pocketsQuery = Pocket::query()
            ->where('user_id', $user->id)
            ->orderBy('id');

        if (! empty($filters['pocket_id'])) {
            $pocketsQuery->where(
                'id',
                $filters['pocket_id']
            );
        }

        $pockets = $pocketsQuery->get();

        $pocketReports = $pockets
            ->map(
                fn (Pocket $pocket) => $this->reportForPocket(
                    $user,
                    $pocket,
                    $from,
                    $to
                )
            )
            ->values();

        $summary = $this->buildSummary(
            $pocketReports
        );

        return [
            'period' => [
                'type' => $filters['type'],
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],

            'summary' => $summary,

            'pockets' => $pocketReports,
        ];
    }

    private function reportForPocket(
        User $user,
        Pocket $pocket,
        CarbonImmutable $from,
        CarbonImmutable $to
    ): array {
        $openingBalance = $this->openingBalance(
            $user,
            $pocket,
            $from
        );

        $stats = Movement::query()
            ->where('user_id', $user->id)
            ->where('pocket_id', $pocket->id)
            ->whereBetween(
                'occurred_at',
                [$from, $to]
            )
            ->selectRaw(
                "
                COUNT(*) AS movement_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'opening_balance'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS opening_entries,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'deposit'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS deposits,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'withdrawal'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS withdrawals,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'adjustment_in'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS adjustments_in,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'adjustment_out'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS adjustments_out,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'transfer_in'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS transfers_in,

                COALESCE(
                    SUM(
                        CASE
                            WHEN type = 'transfer_out'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS transfers_out
                "
            )
            ->first();

        $openingEntries = $this->money(
            $stats->opening_entries
        );

        $deposits = $this->money(
            $stats->deposits
        );

        $withdrawals = $this->money(
            $stats->withdrawals
        );

        $adjustmentsIn = $this->money(
            $stats->adjustments_in
        );

        $adjustmentsOut = $this->money(
            $stats->adjustments_out
        );

        $transfersIn = $this->money(
            $stats->transfers_in
        );

        $transfersOut = $this->money(
            $stats->transfers_out
        );

        $entries = '0.00';

        foreach ([
            $openingEntries,
            $deposits,
            $adjustmentsIn,
            $transfersIn,
        ] as $value) {
            $entries = bcadd(
                $entries,
                $value,
                2
            );
        }

        $exits = '0.00';

        foreach ([
            $withdrawals,
            $adjustmentsOut,
            $transfersOut,
        ] as $value) {
            $exits = bcadd(
                $exits,
                $value,
                2
            );
        }

        $netMovement = bcsub(
            $entries,
            $exits,
            2
        );

        $closingBalance = bcadd(
            $openingBalance,
            $netMovement,
            2
        );

        return [
            'pocket' => [
                'id' => $pocket->id,
                'name' => $pocket->name,
                'is_active' => $pocket->is_active,
            ],

            'opening_balance' => $openingBalance,

            'opening_entries' => $openingEntries,

            'deposits' => $deposits,

            'withdrawals' => $withdrawals,

            'adjustments_in' => $adjustmentsIn,

            'adjustments_out' => $adjustmentsOut,

            'transfers_in' => $transfersIn,

            'transfers_out' => $transfersOut,

            'entries' => $entries,

            'exits' => $exits,

            'net_movement' => $netMovement,

            'closing_balance' => $closingBalance,

            'movement_count' => (int) $stats->movement_count,
        ];
    }

    private function openingBalance(
        User $user,
        Pocket $pocket,
        CarbonImmutable $from
    ): string {
        $balance = Movement::query()
            ->where('user_id', $user->id)
            ->where('pocket_id', $pocket->id)
            ->where(
                'occurred_at',
                '<',
                $from
            )
            ->selectRaw(
                "
                CAST(
                    COALESCE(
                        SUM(
                            CASE
                                WHEN type IN (
                                    'opening_balance',
                                    'deposit',
                                    'transfer_in',
                                    'adjustment_in'
                                )
                                THEN amount

                                WHEN type IN (
                                    'withdrawal',
                                    'transfer_out',
                                    'adjustment_out'
                                )
                                THEN -amount

                                ELSE 0
                            END
                        ),
                        0
                    )
                    AS NUMERIC(18, 2)
                ) AS balance
                "
            )
            ->value('balance');

        return $this->money($balance);
    }

    private function buildSummary(
        Collection $reports
    ): array {
        $summary = [
            'opening_balance' => '0.00',
            'opening_entries' => '0.00',
            'deposits' => '0.00',
            'withdrawals' => '0.00',
            'adjustments_in' => '0.00',
            'adjustments_out' => '0.00',
            'transfers_in' => '0.00',
            'transfers_out' => '0.00',
            'entries' => '0.00',
            'exits' => '0.00',
            'net_movement' => '0.00',
            'closing_balance' => '0.00',
            'movement_count' => 0,
        ];

        foreach ($reports as $report) {
            foreach ([
                'opening_balance',
                'opening_entries',
                'deposits',
                'withdrawals',
                'adjustments_in',
                'adjustments_out',
                'transfers_in',
                'transfers_out',
                'entries',
                'exits',
                'net_movement',
                'closing_balance',
            ] as $field) {
                $summary[$field] = bcadd(
                    $summary[$field],
                    $report[$field],
                    2
                );
            }

            $summary['movement_count'] +=
                $report['movement_count'];
        }

        return $summary;
    }

    private function resolvePeriod(
        array $filters
    ): array {
        $timezone = config(
            'app.timezone',
            'UTC'
        );

        return match ($filters['type']) {
            'monthly' => $this->monthlyPeriod(
                (int) $filters['year'],
                (int) $filters['month'],
                $timezone
            ),

            'quarterly' => $this->quarterlyPeriod(
                (int) $filters['year'],
                (int) $filters['quarter'],
                $timezone
            ),

            'semiannual' => $this->semiannualPeriod(
                (int) $filters['year'],
                (int) $filters['semester'],
                $timezone
            ),

            'annual' => $this->annualPeriod(
                (int) $filters['year'],
                $timezone
            ),

            'custom' => $this->customPeriod(
                $filters['from'],
                $filters['to'],
                $timezone
            ),
        };
    }

    private function monthlyPeriod(
        int $year,
        int $month,
        string $timezone
    ): array {
        $from = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        return [
            $from,
            $from->endOfMonth()->endOfDay(),
        ];
    }

    private function quarterlyPeriod(
        int $year,
        int $quarter,
        string $timezone
    ): array {
        $month = (($quarter - 1) * 3) + 1;

        $from = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        $to = $from
            ->addMonths(2)
            ->endOfMonth()
            ->endOfDay();

        return [$from, $to];
    }

    private function semiannualPeriod(
        int $year,
        int $semester,
        string $timezone
    ): array {
        $month = $semester === 1
            ? 1
            : 7;

        $from = CarbonImmutable::create(
            $year,
            $month,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        $to = $from
            ->addMonths(5)
            ->endOfMonth()
            ->endOfDay();

        return [$from, $to];
    }

    private function annualPeriod(
        int $year,
        string $timezone
    ): array {
        $from = CarbonImmutable::create(
            $year,
            1,
            1,
            0,
            0,
            0,
            $timezone
        )->startOfDay();

        return [
            $from,
            $from->endOfYear()->endOfDay(),
        ];
    }

    private function customPeriod(
        string $from,
        string $to,
        string $timezone
    ): array {
        return [
            CarbonImmutable::parse(
                $from,
                $timezone
            )->startOfDay(),

            CarbonImmutable::parse(
                $to,
                $timezone
            )->endOfDay(),
        ];
    }

    private function money(
        mixed $value
    ): string {
        return bcadd(
            (string) ($value ?? '0'),
            '0',
            2
        );
    }
}