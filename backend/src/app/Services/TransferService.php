<?php

namespace App\Services;

use App\Models\Movement;
use App\Models\Pocket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Servicio encargado de realizar transferencias entre bolsillos.
 *
 * Una transferencia genera dos movimientos:
 * - transfer_out en el bolsillo de origen.
 * - transfer_in en el bolsillo de destino.
 *
 * Ambos movimientos comparten el mismo transfer_group_id.
 */
class TransferService
{
    /**
     * Realiza una transferencia entre dos bolsillos del mismo usuario.
     *
     * Toda la operación se ejecuta dentro de una transacción
     * para que ambos movimientos se creen juntos o ninguno.
     */
    public function transfer(
        User $user,
        array $data
    ): array {
        return DB::transaction(function () use ($user, $data) {
            /*
             * Obtenemos los identificadores de los bolsillos
             * de origen y destino.
             */
            $fromPocketId = (int) $data['from_pocket_id'];
            $toPocketId = (int) $data['to_pocket_id'];

            /*
             * Bloqueamos ambos bolsillos en un orden estable.
             *
             * lockForUpdate() evita que otra operación modifique
             * simultáneamente estos mismos registros mientras
             * se realiza la transferencia.
             *
             * orderBy('id') ayuda a reducir el riesgo de deadlocks.
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

            /*
             * Recuperamos cada bolsillo desde la colección
             * utilizando su ID como clave.
             */
            $fromPocket = $pockets->get($fromPocketId);
            $toPocket = $pockets->get($toPocketId);

            /*
             * Validamos que ambos bolsillos:
             * - existan,
             * - pertenezcan al usuario,
             * - estén activos.
             */
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

            /*
             * No permitimos transferencias hacia el mismo bolsillo.
             */
            if ($fromPocket->id === $toPocket->id) {
                throw ValidationException::withMessages([
                    'to_pocket_id' => [
                        'El bolsillo de destino debe ser diferente al bolsillo de origen.',
                    ],
                ]);
            }

            /*
             * Normalizamos el monto con exactamente dos decimales.
             *
             * BCMath evita problemas de precisión propios
             * de los números de punto flotante.
             */
            $amount = bcadd(
                (string) $data['amount'],
                '0',
                2
            );

            /*
             * Calculamos el saldo disponible del bolsillo de origen.
             */
            $balance = $this->calculatePocketBalance(
                $user,
                $fromPocket
            );

            /*
             * bccomp() compara ambos importes con dos decimales.
             *
             * Si devuelve un valor menor que 0 significa que:
             * saldo < monto solicitado.
             */
            if (bccomp($balance, $amount, 2) < 0) {
                throw ValidationException::withMessages([
                    'amount' => [
                        'Saldo insuficiente en el bolsillo de origen.',
                    ],
                ]);
            }

            /*
             * Generamos un UUID único que permitirá identificar
             * ambos movimientos como parte de la misma transferencia.
             */
            $transferGroupId = (string) Str::uuid();

            /*
             * Movimiento de salida del bolsillo de origen.
             */
            $outMovement = Movement::create([
                'user_id' => $user->id,
                'pocket_id' => $fromPocket->id,
                'type' => 'transfer_out',
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'occurred_at' => $data['occurred_at'],
                'transfer_group_id' => $transferGroupId,
            ]);

            /*
             * Movimiento de entrada en el bolsillo de destino.
             */
            $inMovement = Movement::create([
                'user_id' => $user->id,
                'pocket_id' => $toPocket->id,
                'type' => 'transfer_in',
                'amount' => $amount,
                'description' => $data['description'] ?? null,
                'occurred_at' => $data['occurred_at'],
                'transfer_group_id' => $transferGroupId,
            ]);

            /*
             * Devolvemos los datos principales de la transferencia
             * junto con los dos movimientos creados.
             */
            return [
                'transfer_group_id' => $transferGroupId,
                'amount' => $amount,
                'from_movement' => $outMovement,
                'to_movement' => $inMovement,
            ];
        });
    }

    /**
     * Calcula el saldo actual de un bolsillo.
     *
     * El saldo se deriva de los movimientos registrados;
     * no se guarda físicamente en la tabla pockets.
     */
    private function calculatePocketBalance(
        User $user,
        Pocket $pocket
    ): string {
        /*
         * Filtramos únicamente los movimientos del usuario
         * y del bolsillo recibido.
         */
        $balance = Movement::query()
            ->where('user_id', $user->id)
            ->where('pocket_id', $pocket->id)

            /*
             * SUM() calcula el saldo aplicando el signo
             * correspondiente a cada tipo de movimiento.
             *
             * Suman:
             * - opening_balance
             * - deposit
             * - transfer_in
             * - adjustment_in
             *
             * Restan:
             * - withdrawal
             * - transfer_out
             * - adjustment_out
             */
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

        /*
         * Normalizamos el saldo para devolver siempre
         * una cadena con dos decimales.
         */
        return bcadd(
            (string) ($balance ?? '0.00'),
            '0',
            2
        );
    }
}