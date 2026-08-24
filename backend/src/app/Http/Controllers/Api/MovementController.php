<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovementRequest;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movements = Movement::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'data' => $movements,
        ]);
    }

    public function store(
        StoreMovementRequest $request
    ): JsonResponse {
        $movement = Movement::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Movimiento registrado correctamente.',
            'data' => $movement,
        ], 201);
    }

    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        $movement = $this->findUserMovement(
            $request,
            $id
        );

        return response()->json([
            'data' => $movement,
        ]);
    }

    private function findUserMovement(
        Request $request,
        string $id
    ): Movement {
        return Movement::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }
}