<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Usuario Prueba',
            'email' => 'usuario.prueba@wallet.local',
            'password' => 'WalletTest2026!',
            'password_confirmation' => 'WalletTest2026!',
            'device_name' => 'PHPUnit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Usuario registrado correctamente.'
            )
            ->assertJsonPath(
                'user.email',
                'usuario.prueba@wallet.local'
            )
            ->assertJsonPath(
                'token_type',
                'Bearer'
            )
            ->assertJsonStructure([
                'message',
                'user',
                'token',
                'token_type',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'usuario.prueba@wallet.local',
        ]);
    }

    public function test_user_can_login(): void
    {
        User::factory()->create([
            'name' => 'Usuario Prueba',
            'email' => 'usuario.prueba@wallet.local',
            'password' => 'WalletTest2026!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'usuario.prueba@wallet.local',
            'password' => 'WalletTest2026!',
            'device_name' => 'PHPUnit',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Inicio de sesión correcto.'
            )
            ->assertJsonPath(
                'user.email',
                'usuario.prueba@wallet.local'
            )
            ->assertJsonPath(
                'token_type',
                'Bearer'
            )
            ->assertJsonStructure([
                'message',
                'user',
                'token',
                'token_type',
            ]);
    }

    public function test_authenticated_user_can_get_profile(): void
    {
        $user = User::factory()->create([
            'email' => 'usuario.prueba@wallet.local',
        ]);

        $token = $user
            ->createToken('PHPUnit')
            ->plainTextToken;

        $response = $this
            ->withToken($token)
            ->getJson('/api/auth/me');

        $response
            ->assertOk()
            ->assertJsonPath(
                'user.email',
                'usuario.prueba@wallet.local'
            );
    }

    public function test_user_can_logout_and_current_token_is_deleted(): void
    {
        $user = User::factory()->create();

        $newToken = $user->createToken('PHPUnit');

        $tokenId = $newToken->accessToken->id;

        $response = $this
            ->withToken($newToken->plainTextToken)
            ->postJson('/api/auth/logout');

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Sesión cerrada correctamente.'
            );

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_revoked_token_cannot_access_protected_route(): void
    {
        $user = User::factory()->create();

        $newToken = $user->createToken('PHPUnit');

        $plainTextToken = $newToken->plainTextToken;

        /*
         * Simulamos que el token ya fue revocado.
         */
        $newToken->accessToken->delete();

        $response = $this
            ->withToken($plainTextToken)
            ->getJson('/api/auth/me');

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'No autenticado.'
            );
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'usuario.prueba@wallet.local',
            'password' => 'WalletTest2026!',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'usuario.prueba@wallet.local',
            'password' => 'PasswordIncorrecto2026!',
            'device_name' => 'PHPUnit',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'Credenciales incorrectas.'
            );
    }
}