<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportPeriodRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

/**
 * Controlador encargado de generar reportes financieros por período.
 *
 * La lógica de cálculo no se realiza aquí;
 * se delega completamente a ReportService.
 */
class ReportController extends Controller
{
    /**
     * Genera un reporte financiero según el período solicitado.
     *
     * El período puede ser:
     * - mensual,
     * - trimestral,
     * - semestral,
     * - anual,
     * - personalizado.
     */
    public function period(
        ReportPeriodRequest $request,
        ReportService $reportService
    ): JsonResponse {
        /*
         * ReportPeriodRequest ya validó los filtros recibidos.
         *
         * ReportService recibe:
         * - el usuario autenticado,
         * - los filtros del período.
         */
        $report = $reportService->generate(
            $request->user(),
            $request->validated()
        );

        /*
         * Devuelve el reporte completo en formato JSON.
         */
        return response()->json([
            'data' => $report,
        ]);
    }
}