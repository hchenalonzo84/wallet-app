<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class WebRegistrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Un usuario puede registrarse e iniciar sesión web.
     */
    public function test_user_can_register_with_web_session(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Usuario Web',
            'email' => 'usuario.web@wallet.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Cuenta creada correctamente.')
            ->assertJsonPath('user.name', 'Usuario Web')
            ->assertJsonPath('user.email', 'usuario.web@wallet.local');

        $this->assertAuthenticated();

        $user = User::query()
            ->where('email', 'usuario.web@wallet.local')
            ->firstOrFail();

        $this->assertTrue(
            Hash::check('Password123!', $user->password),
        );
    }

    /**
     * No permite registrar dos cuentas con el mismo correo.
     */
    public function test_duplicate_email_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'existente@wallet.local',
        ]);

        $response = $this->postJson('/register', [
            'name' => 'Segundo Usuario',
            'email' => 'existente@wallet.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    /**
     * La confirmación de contraseña debe coincidir.
     */
    public function test_password_confirmation_must_match(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Usuario Web',
            'email' => 'usuario.web@wallet.local',
            'password' => 'Password123!',
            'password_confirmation' => 'OtraPassword123!',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertGuest();
    }

    /**
     * La sesión creada durante el registro funciona con Sanctum SPA.
     */
    public function test_registered_user_can_access_api_me(): void
    {
        $this->postJson('/register', [
            'name' => 'Usuario Web',
            'email' => 'usuario.web@wallet.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertCreated();

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', 'usuario.web@wallet.local');
    }
}