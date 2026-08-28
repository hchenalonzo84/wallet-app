<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePocketRequest;
use App\Http\Requests\UpdatePocketRequest;
use App\Models\Pocket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PocketController extends Controller
{
    /**
     * Display the authenticated user's pockets.
     */
    public function index(Request $request): JsonResponse
    {
        $pockets = Pocket::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $pockets,
        ]);
    }

    /**
     * Store a newly created pocket.
     */
    public function store(StorePocketRequest $request): JsonResponse
    {
        $pocket = Pocket::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Bolsillo creado correctamente.',
            'data' => $pocket,
        ], 201);
    }

    /**
     * Display the specified pocket.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $pocket = $this->findUserPocket(
            $request,
            $id
        );

        return response()->json([
            'data' => $pocket,
        ]);
    }

    /**
     * Update the specified pocket.
     */
    public function update(
        UpdatePocketRequest $request,
        string $id
    ): JsonResponse {
        $pocket = $this->findUserPocket(
            $request,
            $id
        );

        $pocket->update(
            $request->validated()
        );

        return response()->json([
            'message' => 'Bolsillo actualizado correctamente.',
            'data' => $pocket->fresh(),
        ]);
    }

    /**
     * Deactivate the specified pocket.
     *
     * Financial pockets are not physically deleted.
     */
    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        $pocket = $this->findUserPocket(
            $request,
            $id
        );

        $pocket->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Bolsillo desactivado correctamente.',
            'data' => $pocket->fresh(),
        ]);
    }

    /**
     * Find a pocket that belongs to the authenticated user.
     */
    private function findUserPocket(
        Request $request,
        string $id
    ): Pocket {
        return Pocket::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }
}