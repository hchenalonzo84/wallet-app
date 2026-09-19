<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador encargado de realizar transferencias
 * entre bolsillos del usuario autenticado.
 *
 * La lógica financiera de la transferencia
 * se delega a TransferService.
 */
class TransferController extends Controller
{
    /**
     * Registra una nueva transferencia entre bolsillos.
     */
    public function store(
        StoreTransferRequest $request,
        TransferService $transferService
    ): JsonResponse {
        /*
         * StoreTransferRequest valida los datos recibidos.
         *
         * TransferService se encarga de:
         * - validar los bolsillos,
         * - comprobar saldo disponible,
         * - bloquear los registros,
         * - crear transfer_out,
         * - crear transfer_in.
         */
        $transfer = $transferService->transfer(
            $request->user(),
            $request->validated()
        );

        /*
         * Devuelve la transferencia creada.
         *
         * HTTP 201 indica que la operación
         * creó correctamente los movimientos asociados.
         */
        return response()->json([
            'message' => 'Transferencia realizada correctamente.',
            'data' => $transfer,
        ], 201);
    }
}