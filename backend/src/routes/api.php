<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\PocketController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('auth')->group(function () {
    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    Route::post('/login', [
        AuthController::class,
        'login',
    ]);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource(
        'pockets',
        PocketController::class
    );

    Route::get('/movements', [
        MovementController::class,
        'index',
    ]);

    Route::post('/movements', [
        MovementController::class,
        'store',
    ]);

    Route::get('/movements/{movement}', [
        MovementController::class,
        'show',
    ]);

    Route::get('/balances', [
        BalanceController::class,
        'index',
    ]);

    Route::get('/pockets/{pocket}/balance', [
        BalanceController::class,
        'show',
    ]);

    Route::post('/transfers', [
        TransferController::class,
        'store',
    ]);
});