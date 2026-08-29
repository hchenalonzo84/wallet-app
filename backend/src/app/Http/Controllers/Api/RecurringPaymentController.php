<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRecurringPaymentRequest;
use App\Http\Requests\UpdateRecurringPaymentRequest;
use App\Models\RecurringPayment;
use App\Services\RecurringPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador encargado de administrar los pagos automáticos.
 *
 * Permite:
 * - listar,
 * - crear,
 * - consultar,
 * - actualizar,
 * - desactivar pagos automáticos.
 *
 * La lógica de fechas y recurrencia se delega
 * a RecurringPaymentService.
 */
class RecurringPaymentController extends Controller
{
    /**
     * Devuelve todos los pagos automáticos
     * pertenecientes al usuario autenticado.
     */
    public function index(Request $request): JsonResponse
    {
        /*
         * Filtramos siempre por user_id para impedir
         * que un usuario consulte pagos de otro usuario.
         *
         * También cargamos el bolsillo asociado para que
         * la API pueda mostrar su información básica.
         */
        $recurringPayments = RecurringPayment::query()
            ->with('pocket')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_active')
            ->orderBy('next_due_on')
            ->orderBy('id')
            ->get();

        return response()->json([
            'data' => $recurringPayments,
        ]);
    }

    /**
     * Crea un nuevo pago automático.
     */
    public function store(
        StoreRecurringPaymentRequest $request,
        RecurringPaymentService $recurringPaymentService
    ): JsonResponse {
        /*
         * El Form Request ya validó los datos recibidos.
         *
         * El servicio se encarga de calcular:
         * - billing_day,
         * - frequency,
         * - next_due_on,
         * - estado inicial.
         */
        $recurringPayment = $recurringPaymentService->create(
            $request->user(),
            $request->validated()
        );

        /*
         * Cargamos el bolsillo relacionado antes
         * de devolver el nuevo pago automático.
         */
        $recurringPayment->load('pocket');

        return response()->json([
            'message' => 'Pago automático creado correctamente.',
            'data' => $recurringPayment,
        ], 201);
    }

    /**
     * Devuelve un pago automático específico
     * perteneciente al usuario autenticado.
     */
    public function show(
        Request $request,
        string $id
    ): JsonResponse {
        /*
         * findUserRecurringPayment() garantiza que el recurso
         * realmente pertenezca al usuario autenticado.
         */
        $recurringPayment = $this->findUserRecurringPayment(
            $request,
            $id
        );

        /*
         * Incluimos información del bolsillo asociado.
         */
        $recurringPayment->load('pocket');

        return response()->json([
            'data' => $recurringPayment,
        ]);
    }

    /**
     * Actualiza un pago automático existente.
     *
     * Los cambios solamente afectan ejecuciones futuras.
     * Los movimientos históricos no se modifican.
     */
    public function update(
        UpdateRecurringPaymentRequest $request,
        string $id,
        RecurringPaymentService $recurringPaymentService
    ): JsonResponse {
        /*
         * Primero comprobamos que el pago automático
         * pertenezca al usuario autenticado.
         */
        $recurringPayment = $this->findUserRecurringPayment(
            $request,
            $id
        );

        /*
         * RecurringPaymentService aplica los cambios
         * y recalcula fechas cuando sea necesario.
         */
        $recurringPayment = $recurringPaymentService->update(
            $recurringPayment,
            $request->validated()
        );

        $recurringPayment->load('pocket');

        return response()->json([
            'message' => 'Pago automático actualizado correctamente.',
            'data' => $recurringPayment,
        ]);
    }

    /**
     * Desactiva un pago automático.
     *
     * No se elimina físicamente para mantener
     * la relación con su historial financiero.
     */
    public function destroy(
        Request $request,
        string $id,
        RecurringPaymentService $recurringPaymentService
    ): JsonResponse {
        /*
         * Localizamos únicamente un pago perteneciente
         * al usuario autenticado.
         */
        $recurringPayment = $this->findUserRecurringPayment(
            $request,
            $id
        );

        /*
         * Realizamos una desactivación lógica:
         * is_active = false.
         */
        $recurringPayment = $recurringPaymentService->deactivate(
            $recurringPayment
        );

        $recurringPayment->load('pocket');

        return response()->json([
            'message' => 'Pago automático desactivado correctamente.',
            'data' => $recurringPayment,
        ]);
    }

    /**
     * Busca un pago automático perteneciente
     * exclusivamente al usuario autenticado.
     *
     * Si no existe o pertenece a otro usuario,
     * findOrFail() genera una respuesta HTTP 404.
     */
    private function findUserRecurringPayment(
        Request $request,
        string $id
    ): RecurringPayment {
        return RecurringPayment::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);
    }
}