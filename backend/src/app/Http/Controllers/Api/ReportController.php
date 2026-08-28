<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReportPeriodRequest;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function period(
        ReportPeriodRequest $request,
        ReportService $reportService
    ): JsonResponse {
        $report = $reportService->generate(
            $request->user(),
            $request->validated()
        );

        return response()->json([
            'data' => $report,
        ]);
    }
}