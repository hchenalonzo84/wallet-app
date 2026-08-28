<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Register a new user and create an API token.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
        ]);

        $deviceName = $request->string('device_name')->toString()
            ?: 'api-client';

        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        return response()->json([
            'message' => 'Usuario registrado correctamente.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Authenticate a user and create a new API token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where(
            'email',
            $request->string('email')->toString()
        )->first();

        if (
            ! $user ||
            ! Hash::check(
                $request->string('password')->toString(),
                $user->password
            )
        ) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $deviceName = $request->string('device_name')->toString()
            ?: 'api-client';

        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Return the currently authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $request->user(),
        ]);
    }

    /**
     * Revoke the current API token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}