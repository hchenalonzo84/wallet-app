<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador encargado de consultar los saldos.
 *
 * Delega el cálculo financiero a BalanceService
 * y se limita a preparar la respuesta HTTP.
 */
class BalanceController extends Controller
{
    /**
     * Devuelve todos los bolsillos del usuario autenticado
     * junto con su saldo y el saldo total general.
     */
    public function index(
        Request $request,
        BalanceService $balanceService
    ): JsonResponse {
        /*
         * BalanceService calcula el saldo de cada bolsillo
         * a partir de los movimientos registrados.
         */
        $pockets = $balanceService->forUser(
            $request->user()
        );

        /*
         * Inicializamos el saldo total en cero.
         */
        $totalBalance = '0.00';

        /*
         * Sumamos el saldo de cada bolsillo.
         *
         * Se utiliza BCMath para mantener precisión
         * en operaciones con valores monetarios.
         */
        foreach ($pockets as $pocket) {
            $totalBalance = bcadd(
                $totalBalance,
                (string) $pocket->balance,
                2
            );
        }

        /*
         * Devuelve:
         * - data: lista de bolsillos con sus saldos.
         * - meta.total_balance: saldo combinado del usuario.
         */
        return response()->json([
            'data' => $pockets,
            'meta' => [
                'total_balance' => $totalBalance,
            ],
        ]);
    }

    /**
     * Devuelve el saldo de un bolsillo específico.
     */
    public function show(
        Request $request,
        string $pocket,
        BalanceService $balanceService
    ): JsonResponse {
        /*
         * Busca el bolsillo dentro de los bolsillos
         * pertenecientes al usuario autenticado.
         *
         * Si no existe o no le pertenece,
         * BalanceService terminará en un 404.
         */
        $pocketBalance = $balanceService->forPocket(
            $request->user(),
            $pocket
        );

        /*
         * Devuelve el bolsillo junto con su saldo calculado.
         */
        return response()->json([
            'data' => $pocketBalance,
        ]);
    }
}