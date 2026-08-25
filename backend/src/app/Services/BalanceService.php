<?php

namespace App\Services;

use App\Models\Pocket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BalanceService
{
    public function forUser(User $user): Collection
    {
        return $this->queryForUser($user)
            ->orderBy('pockets.id')
            ->get();
    }

    public function forPocket(
        User $user,
        string $pocketId
    ): Pocket {
        return $this->queryForUser($user)
            ->where('pockets.id', $pocketId)
            ->firstOrFail();
    }

    private function queryForUser(User $user): Builder
    {
        return Pocket::query()
            ->leftJoin(
                'movements',
                function ($join) {
                    $join
                        ->on(
                            'movements.pocket_id',
                            '=',
                            'pockets.id'
                        )
                        ->on(
                            'movements.user_id',
                            '=',
                            'pockets.user_id'
                        );
                }
            )
            ->where(
                'pockets.user_id',
                $user->id
            )
            ->select([
                'pockets.id',
                'pockets.user_id',
                'pockets.name',
                'pockets.description',
                'pockets.is_active',
                'pockets.created_at',
                'pockets.updated_at',
            ])
            ->selectRaw(
                "
                CAST(
                    COALESCE(
                        SUM(
                            CASE
                                WHEN movements.type IN (
                                    'opening_balance',
                                    'deposit',
                                    'transfer_in',
                                    'adjustment_in'
                                )
                                THEN movements.amount

                                WHEN movements.type IN (
                                    'withdrawal',
                                    'transfer_out',
                                    'adjustment_out'
                                )
                                THEN -movements.amount

                                ELSE 0
                            END
                        ),
                        0
                    )
                    AS NUMERIC(18, 2)
                ) AS balance
                "
            )
            ->groupBy([
                'pockets.id',
                'pockets.user_id',
                'pockets.name',
                'pockets.description',
                'pockets.is_active',
                'pockets.created_at',
                'pockets.updated_at',
            ]);
    }
}