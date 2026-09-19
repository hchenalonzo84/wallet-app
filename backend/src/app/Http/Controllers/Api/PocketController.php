<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePocketRequest;
use App\Http\Requests\UpdatePocketRequest;
use App\Models\Pocket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador encargado de administrar los bolsillos virtuales.
 *
 * Permite:
 * - listar,
 * - crear,
 * - consultar,
 * - actualizar,
 * - desactivar bolsillos.
 *
 * Los bolsillos financieros no se eliminan físicamente
 * para conservar la integridad del historial.
 */
class PocketController extends Controller
{
    /**
     * Devuelve todos los bolsillos del usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        /*
         * Filtra por user_id para garantizar que cada usuario
         * solo pueda ver sus propios bolsillos.
         */
        $pockets = Pocket::query()
            ->where('user_id', $request->user()->id)
            ->orderBy('id')
            ->get();

        /*
         * Devuelve la colección de bolsillos.
         */
        return response()->json([
            'data' => $pockets,
        ]);
    }

    /**
     * Crea un nuevo bolsillo para el usuario autenticado.
     */
    public function store(StorePocketRequest $request): JsonResponse
    {
        /*
         * validated() devuelve únicamente los datos
         * que superaron las reglas de validación.
         *
         * user_id se agrega desde la sesión autenticada
         * para impedir que el cliente lo manipule.
         */
        $pocket = Pocket::create([
            ...$request->validated(),
            'user_id' => $request->user()->id,
        ]);

        /*
         * HTTP 201 indica que el recurso fue creado correctamente.
         */
        return response()->json([
            'message' => 'Bolsillo creado correctamente.',
            'data' => $pocket,
        ], 201);
    }

    /**
     * Devuelve un bolsillo específico del usuario autenticado.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        /*
         * Busca el bolsillo verificando que pertenezca
         * al usuario autenticado.
         */
        $pocket = $this->findUserPocket(
            $request,
            $id
        );

        return response()->json([
            'data' => $pocket,
        ]);
    }

    /**
     * Actualiza los datos de un bolsillo existente.
     */
    public function update(
        UpdatePocketRequest $request,
        string $id
    ): JsonResponse {
        /*
         * Localiza únicamente un bolsillo propiedad
         * del usuario autenticado.
         */
        $pocket = $this->findUserPocket(
            $request,
            $id
        );

        /*
         * Aplica únicamente los campos que fueron validados
         * por UpdatePocketRequest.
         */
        $pocket->update(
            $request->validated()
        );

        /*
         * fresh() vuelve a leer el modelo desde la base de datos
         * para devolver los valores realmente persistidos.
         */
        return response()->json([
            'message' => 'Bolsillo actualizado correctamente.',
            'data' => $pocket->fresh(),
        ]);
    }

    /**
     * Desactiva un bolsillo sin eliminarlo físicamente.
     *
     * Esto conserva:
     * - movimientos históricos,
     * - transferencias,
     * - reportes,
     * - relaciones financieras.
     */
    public function destroy(
        Request $request,
        string $id
    ): JsonResponse {
        /*
         * Busca el bolsillo dentro de los recursos
         * pertenecientes al usuario autenticado.
         */
        $pocket = $this->findUserPocket(
            $request,
            $id
        );

        /*
         * Realizamos una eliminación lógica.
         *
         * is_active = false evita nuevos usos del bolsillo,
         * pero mantiene intacto todo su historial financiero.
         */
        $pocket->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Bolsillo desactivado correctamente.',
            'data' => $pocket->fresh(),
        ]);
    }

    /**
     * Busca un bolsillo perteneciente al usuario autenticado.
     *
     * Si el bolsillo no existe o pertenece a otro usuario,
     * findOrFail() genera una respuesta 404.
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