<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PocketController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health
|--------------------------------------------------------------------------
|
| Endpoint público para comprobar el estado de la aplicación
| y la conexión con PostgreSQL.
|
*/

Route::get('/health', HealthController::class);

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
|
| Register y login son públicos.
| Me y logout requieren autenticación mediante Sanctum.
|
*/

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Pockets
|--------------------------------------------------------------------------
|
| Todos los endpoints de bolsillos requieren un usuario autenticado.
|
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('pockets', PocketController::class);
});