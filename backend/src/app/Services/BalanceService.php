<?php

namespace App\Services;

use App\Models\Pocket;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio encargado de calcular los saldos de los bolsillos.
 *
 * El saldo no se guarda físicamente en la tabla pockets.
 * Se calcula dinámicamente a partir de los movimientos registrados.
 */
class BalanceService
{
    /**
     * Devuelve todos los bolsillos de un usuario
     * junto con su saldo calculado.
     */
    public function forUser(User $user): Collection
    {
        return $this->queryForUser($user)
            ->orderBy('pockets.id')
            ->get();
    }

    /**
     * Devuelve un bolsillo específico del usuario
     * junto con su saldo calculado.
     *
     * Si el bolsillo no pertenece al usuario o no existe,
     * Laravel devolverá un error 404 mediante firstOrFail().
     */
    public function forPocket(
        User $user,
        string $pocketId
    ): Pocket {
        return $this->queryForUser($user)
            ->where('pockets.id', $pocketId)
            ->firstOrFail();
    }

    /**
     * Construye la consulta base utilizada para calcular saldos.
     *
     * Une pockets con movements y suma o resta los importes
     * según el tipo de movimiento.
     */
    private function queryForUser(User $user): Builder
    {
        return Pocket::query()

            /*
             * leftJoin permite obtener también bolsillos
             * que todavía no tienen movimientos.
             */
            ->leftJoin(
                'movements',
                function ($join) {
                    $join
                        /*
                         * Relaciona cada movimiento
                         * con el bolsillo correspondiente.
                         */
                        ->on(
                            'movements.pocket_id',
                            '=',
                            'pockets.id'
                        )

                        /*
                         * Refuerza que el movimiento y el bolsillo
                         * pertenezcan al mismo usuario.
                         */
                        ->on(
                            'movements.user_id',
                            '=',
                            'pockets.user_id'
                        );
                }
            )

            /*
             * Limita la consulta únicamente a los bolsillos
             * del usuario autenticado.
             */
            ->where(
                'pockets.user_id',
                $user->id
            )

            /*
             * Selecciona los datos principales del bolsillo.
             */
            ->select([
                'pockets.id',
                'pockets.user_id',
                'pockets.name',
                'pockets.description',
                'pockets.is_active',
                'pockets.created_at',
                'pockets.updated_at',
            ])

            /*
             * Calcula el saldo usando los movimientos.
             *
             * Movimientos que SUMAN:
             * - opening_balance
             * - deposit
             * - transfer_in
             * - adjustment_in
             *
             * Movimientos que RESTAN:
             * - withdrawal
             * - transfer_out
             * - adjustment_out
             *
             * COALESCE(..., 0) garantiza saldo 0.00
             * si el bolsillo todavía no tiene movimientos.
             */
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

            /*
             * PostgreSQL requiere agrupar las columnas
             * del bolsillo porque estamos usando SUM().
             */
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