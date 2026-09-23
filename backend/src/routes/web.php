<?php

use App\Http\Controllers\Web\AuthController;
use Illuminate\Support\Facades\Route;

/*
 * Registra un usuario e inicia su sesión web.
 */
Route::post('/register', [
    AuthController::class,
    'register',
]);

/*
 * Inicia una sesión web mediante cookies de Laravel.
 */
Route::post('/login', [
    AuthController::class,
    'login',
]);

/*
 * Cierra únicamente la sesión web actual.
 */
Route::post('/logout', [
    AuthController::class,
    'logout',
])->middleware('auth');

/*
 * React controla todas las rutas visuales de la aplicación.
 * Las rutas /api quedan reservadas para Laravel.
 */
Route::view('/{path?}', 'app')
    ->where('path', '^(?!api(?:/|$)).*');