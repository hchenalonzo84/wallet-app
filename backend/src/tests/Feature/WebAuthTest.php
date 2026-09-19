<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Comprueba la autenticación web mediante sesión de Laravel.
 */
class WebAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Comprueba que un usuario puede iniciar sesión desde la web.
     */
    public function test_user_can_login_with_web_session(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'password123',
            'remember' => false,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Inicio de sesión correcto.')
            ->assertJsonPath('user.id', $user->id);

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Comprueba que unas credenciales incorrectas son rechazadas.
     */
    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
        ]);

        $response = $this->postJson('/login', [
            'email' => $user->email,
            'password' => 'incorrecta',
            'remember' => false,
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Credenciales incorrectas.');

        $this->assertGuest();
    }

    /**
     * Comprueba que Sanctum reconoce la sesión web autenticada.
     */
    public function test_authenticated_web_session_can_access_api_me(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email);
    }

    /**
     * Comprueba que el usuario puede cerrar su sesión web.
     */
    public function test_user_can_logout_from_web_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/logout');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');

        $this->assertGuest();
    }
}