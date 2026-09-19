<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMovementRequest;
use App\Models\Movement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador encargado de consultar y registrar movimientos financieros.
 *
 * Los movimientos son inmutables:
 * - se pueden listar,
 * - se pueden crear,
 * - se pueden consultar,
 * - pero no se editan ni eliminan mediante la API.
 */
class MovementController extends Controller
{
    /**
     * Devuelve todos los movimientos pertenecientes
     * al usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        /*
         * Filtra por user_id para garantizar
         * que cada usuario solo vea sus propios movimientos.
         */
        $movements = Movement::query()
            ->where('user_id', $request->user()->id)

            /*
             * Ordena primero por fecha financiera
             * y luego por ID para mantener un orden estable.
             */
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        /*
         * Devuelve la colección de movimientos.
         */
        return response()->json([
            'data' => $movements,
        ]);
    }

    /**
     * Registra un nuevo movimiento financiero.
     */
    public function store(
        StoreMovementRequest $request
    ): JsonResponse {
        /*
         * validated() devuelve únicamente los datos
         * que pasaron las reglas de validación.
         *
         * user_id se agrega desde el usuario autenticado
         * para evitar que el cliente pueda falsificarlo.
         */
        $movement = Movement::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        /*
         * Devuelve el movimiento recién creado.
         *
         * HTTP 201 indica que el recurso fue creado correctamente.
         */
        return response()->json([
            'message' => 'Movimiento registrado correctamente.',
            'data' => $movement,
        ], 201);
    }

    /**
     * Devuelve un movimiento específico del usuario autenticado.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        /*
         * Busca el movimiento verificando al mismo tiempo
         * que pertenezca al usuario autenticado.
         */
        $movement = $this->findUserMovement(
            $request,
            $id
        );

        return response()->json([
            'data' => $movement,
        ]);
    }

    /**
     * Busca un movimiento perteneciente al usuario autenticado.
     *
     * Si no existe o pertenece a otro usuario,
     * findOrFail() provoca una respuesta 404.
     */
    private function findUserMovement(
        Request $request,
        string $id
    ): Movement {
        return Movement::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }
}