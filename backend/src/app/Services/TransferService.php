<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransferService
{
    public function transfer(
        User $user,
        array $data
    ): array {
        return DB::transaction(function () use ($user, $data) {
            $fromPocketId = (int) $data['from_pocket_id'];
            $toPocketId = (int) $data['to_pocket_id'];

            /*
             * Bloqueamos ambos bolsillos en orden estable para reducir
             * el riesgo de condiciones de carrera y deadlocks.
             */
            $pockets = Pocket::query()
                ->where('user_id', $user->id)
                ->whereIn('id', [
                    $fromPocketId,
                    $toPocketId,
                ])
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $fromPocket = $pockets->get($fromPocketId);
            $toPocket = $pockets->get($toPocketId);

            if (
                $fromPocket === null
                || $toPocket === null
                || ! $fromPocket->is_active
                || ! $toPocket->is_active
            ) {
                throw ValidationException::withMessages([
                    'from_pocket_id' => [
                        'Los bolsillos de la transferencia no son válidos.',
                    ],
                ]);
            }

            if ($fromPocket->id === $toPocket->id) {
                throw ValidationException::withMessages([
                    'to_pocket_id' => [
                        'El bolsillo de destino debe ser diferente al bolsillo de origen.',
                    ],
                ]);
            }

            $amount = bcadd(
                (string) $data['amount'],
                '0',
                2
            );

            $balance = $this->calculatePocketBalance(
                $user,
                $fromPocket
            );

            if (bccomp($balance, $amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Saldo insuficiente en el bolsillo de origen.',
                    ],
                ]);
            }

            $transferGroupId = (string) Str::uuid();

            $outMovement = Movement::create([
                'user_id' => $user->id,
                'pocket_id' => $fromPocket->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'occurred_at' => $data['occurred_at'],
                'transfer_group_id' => $transferGroupId,
            ]);

            $inMovement = Movement::create([
                'user_id' => $user->id,
                'pocket_id' => $toPocket->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'occurred_at' => $data['occurred_at'],
                'transfer_group_id' => $transferGroupId,
            ]);

            return [
                'transfer_group_id' => $transferGroupId,
                'amount' => $amount,
                'from_movement' => $outMovement,
                'to_movement' => $inMovement,
            ];
        });
    }

    private function calculatePocketBalance(
        User $user,
        Pocket $pocket
    ): string {
        $balance = Movement::query()
            ->where('user_id', $user->id)
            ->where('pocket_id', $pocket->id)
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

        return bcadd(
            (string) ($balance ?? '0.00'),
            '0',
            2
        );
    }
}