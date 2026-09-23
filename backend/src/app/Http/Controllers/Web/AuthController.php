<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\WebLoginRequest;
use App\Http\Requests\WebRegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gestiona la autenticación por sesión utilizada por la aplicación web.
 */
class AuthController extends Controller
{
    /**
     * Registra al usuario e inicia inmediatamente su sesión web.
     */
    public function register(WebRegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        // Inicia la sesión sin crear ningún token Bearer.
        Auth::guard('web')->login($user);

        // Regenera el identificador para proteger la nueva sesión.
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Cuenta creada correctamente.',
            'user' => $user,
        ], 201);
    }

    /**
     * Inicia una sesión web mediante correo electrónico y contraseña.
     */
    public function login(WebLoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $remember = (bool) ($credentials['remember'] ?? false);

        if (! Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 422);
        }

        // Regenera la sesión para evitar ataques de fijación de sesión.
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'user' => $request->user(),
        ]);
    }

    /**
     * Cierra la sesión web actual e invalida sus datos.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}