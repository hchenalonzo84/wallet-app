<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        try {
            DB::select('SELECT 1');

            return response()->json([
                'status' => 'ok',
                'application' => config('app.name'),
                'database' => 'ok',
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'application' => config('app.name'),
                'database' => 'unavailable',
                'timestamp' => now()->toIso8601String(),
            ], 503);
        }
    }
}