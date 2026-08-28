<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    public function index(
        Request $request,
        BalanceService $balanceService
    ): JsonResponse {
        $pockets = $balanceService->forUser(
            $request->user()
        );

        $totalBalance = '0.00';

        foreach ($pockets as $pocket) {
            $totalBalance = bcadd(
                $totalBalance,
                (string) $pocket->balance,
                2
            );
        }

        return response()->json([
            'data' => $pockets,
            'meta' => [
                'total_balance' => $totalBalance,
            ],
        ]);
    }

    public function show(
        Request $request,
        string $pocket,
        BalanceService $balanceService
    ): JsonResponse {
        $pocketBalance = $balanceService->forPocket(
            $request->user(),
            $pocket
        );

        return response()->json([
            'data' => $pocketBalance,
        ]);
    }
}