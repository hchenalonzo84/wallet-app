<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Confía en los proxies que anteceden a Laravel.
         * Permite detectar HTTPS cuando TLS termina en Tailscale Serve.
         */
        $middleware->trustProxies(at: '*');

        /*
         * Permite que Sanctum autentique la SPA web mediante
         * cookies de sesión sin afectar los tokens Bearer.
         */
        $middleware->statefulApi();

        /*
         * Wallet App utiliza Laravel como backend API.
         * Una petición no autenticada no debe redirigir a "login".
         */
        $middleware->redirectGuestsTo(null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * Todas las rutas API devuelven errores en JSON.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*')
                || $request->expectsJson(),
        );

        /*
         * Respuesta uniforme para peticiones API sin autenticación.
         */
        $exceptions->render(
            function (AuthenticationException $exception, Request $request) {
                if ($request->is('api/*')) {
                    return response()->json([
                        'message' => 'No autenticado.',
                    ], 401);
                }
            },
        );
    })
    ->create();