<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BalanceController;
use App\Http\Controllers\Api\MovementController;
use App\Http\Controllers\Api\PocketController;
use App\Http\Controllers\Api\RecurringPaymentController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TransferController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
 * Endpoint público utilizado para comprobar
 * que la API se encuentra disponible.
 */
Route::get('/health', HealthController::class);

/*
 * Endpoints relacionados con autenticación.
 */
Route::prefix('auth')->group(function () {
    /*
     * Registro de nuevos usuarios.
     */
    Route::post('/register', [
        AuthController::class,
        'register',
    ]);

    /*
     * Inicio de sesión y generación de token.
     */
    Route::post('/login', [
        AuthController::class,
        'login',
    ]);

    /*
     * Estas rutas requieren autenticación Sanctum.
     */
    Route::middleware('auth:sanctum')->group(function () {
        /*
         * Obtiene el usuario actualmente autenticado.
         */
        Route::get('/me', [
            AuthController::class,
            'me',
        ]);

        /*
         * Revoca el token utilizado en la sesión actual.
         */
        Route::post('/logout', [
            AuthController::class,
            'logout',
        ]);
    });
});

/*
 * Todos los endpoints financieros requieren
 * un usuario autenticado mediante Sanctum.
 */
Route::middleware('auth:sanctum')->group(function () {
    /*
     * CRUD de bolsillos virtuales.
     *
     * El método DELETE realiza desactivación lógica.
     */
    Route::apiResource(
        'pockets',
        PocketController::class
    );

    /*
     * Consulta todos los movimientos financieros.
     */
    Route::get('/movements', [
        MovementController::class,
        'index',
    ]);

    /*
     * Registra un nuevo movimiento.
     */
    Route::post('/movements', [
        MovementController::class,
        'store',
    ]);

    /*
     * Consulta un movimiento específico.
     *
     * Los movimientos no tienen rutas de edición
     * ni eliminación porque son inmutables.
     */
    Route::get('/movements/{movement}', [
        MovementController::class,
        'show',
    ]);

    /*
     * Devuelve los saldos de todos los bolsillos.
     */
    Route::get('/balances', [
        BalanceController::class,
        'index',
    ]);

    /*
     * Devuelve el saldo de un bolsillo específico.
     */
    Route::get('/pockets/{pocket}/balance', [
        BalanceController::class,
        'show',
    ]);

    /*
     * Realiza una transferencia interna
     * entre dos bolsillos.
     */
    Route::post('/transfers', [
        TransferController::class,
        'store',
    ]);

    /*
     * Genera reportes financieros por período.
     */
    Route::get('/reports/period', [
        ReportController::class,
        'period',
    ]);

    /*
     * Lista todos los pagos automáticos del usuario.
     */
    Route::get('/recurring-payments', [
        RecurringPaymentController::class,
        'index',
    ]);

    /*
     * Crea un nuevo pago automático.
     */
    Route::post('/recurring-payments', [
        RecurringPaymentController::class,
        'store',
    ]);

    /*
     * Consulta un pago automático específico.
     */
    Route::get('/recurring-payments/{recurringPayment}', [
        RecurringPaymentController::class,
        'show',
    ]);

    /*
     * Modifica la configuración de un pago automático.
     *
     * PATCH permite enviar únicamente los campos
     * que realmente se desean modificar.
     */
    Route::patch('/recurring-payments/{recurringPayment}', [
        RecurringPaymentController::class,
        'update',
    ]);

    /*
     * Desactiva lógicamente un pago automático.
     */
    Route::delete('/recurring-payments/{recurringPayment}', [
        RecurringPaymentController::class,
        'destroy',
    ]);
});