<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Controlador encargado de la autenticación de usuarios.
 *
 * Gestiona:
 * - registro,
 * - inicio de sesión,
 * - consulta del usuario autenticado,
 * - cierre de sesión.
 *
 * Para la API móvil utiliza tokens de Laravel Sanctum.
 */
class AuthController extends Controller
{
    /**
     * Registra un nuevo usuario y genera su primer token de acceso.
     */
    public function register(
        RegisterRequest $request
    ): JsonResponse {
        /*
         * Crea el usuario con los datos ya validados
         * por RegisterRequest.
         *
         * El modelo User se encarga de aplicar hash
         * automáticamente a la contraseña.
         */
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        /*
         * Identifica el dispositivo o cliente que solicita el token.
         *
         * Si no se envía device_name, se utiliza un nombre genérico.
         */
        $deviceName = $request
            ->string('device_name')
            ->toString()
            ?: 'api-client';

        /*
         * Laravel Sanctum crea un token personal
         * asociado al usuario y al dispositivo.
         */
        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        /*
         * Devuelve el usuario registrado
         * junto con el token Bearer recién creado.
         */
        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Autentica a un usuario y genera un nuevo token de acceso.
     */
    public function login(
        LoginRequest $request
    ): JsonResponse {
        /*
         * Busca al usuario por correo electrónico.
         */
        $user = User::where(
            'email',
            $request->string('email')->toString()
        )->first();

        /*
         * Verifica que:
         * - el usuario exista,
         * - la contraseña ingresada coincida con el hash almacenado.
         */
        if (
            ! $user ||
            ! Hash::check(
                $request->string('password')->toString(),
                $user->password
            )
        ) {
            /*
             * No revelamos si falló el correo o la contraseña.
             */
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        /*
         * Obtiene el nombre del dispositivo o cliente.
         */
        $deviceName = $request
            ->string('device_name')
            ->toString()
            ?: 'api-client';

        /*
         * Genera un nuevo token Sanctum
         * para esta sesión/dispositivo.
         */
        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        /*
         * Devuelve el usuario autenticado
         * junto con el token Bearer.
         */
        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Devuelve los datos del usuario actualmente autenticado.
     */
    public function me(
        Request $request
    ): JsonResponse {
        /*
         * request()->user() obtiene al usuario
         * resuelto por el middleware auth:sanctum.
         */
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Cierra la sesión revocando el token actual.
     */
    public function logout(
        Request $request
    ): JsonResponse {
        /*
         * Elimina únicamente el token usado
         * en la petición actual.
         *
         * Otros dispositivos pueden conservar
         * sus propios tokens activos.
         */
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}