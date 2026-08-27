<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransferRequest;
use App\Services\TransferService;
use Illuminate\Http\JsonResponse;

class TransferController extends Controller
{
    public function store(
        StoreTransferRequest $request,
        TransferService $transferService
    ): JsonResponse {
        $transfer = $transferService->transfer(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Transferencia realizada correctamente.',
            'data' => $transfer,
        ], 201);
    }
}