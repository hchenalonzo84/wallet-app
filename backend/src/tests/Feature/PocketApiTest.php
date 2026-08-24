<?php

namespace Tests\Feature;

use App\Models\Pocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PocketApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_access_pockets(): void
    {
        $response = $this->getJson('/api/pockets');

        $response
            ->assertUnauthorized()
            ->assertJsonPath(
                'message',
                'No autenticado.'
            );
    }

    public function test_authenticated_user_can_create_pocket(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pockets', [
            'name' => 'Ahorro',
            'description' => 'Bolsillo destinado al ahorro',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'message',
                'Bolsillo creado correctamente.'
            )
            ->assertJsonPath(
                'data.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.user_id',
                $user->id
            );

        $this->assertDatabaseHas('pockets', [
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => 'Bolsillo destinado al ahorro',
            'is_active' => true,
        ]);
    }

    public function test_user_only_lists_own_pockets(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => null,
            'is_active' => true,
        ]);

        Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Privado de otro usuario',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/pockets');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => 'Ahorro',
            ])
            ->assertJsonFragment([
                'name' => 'Uso diario',
            ])
            ->assertJsonMissing([
                'name' => 'Privado de otro usuario',
            ]);
    }

    public function test_user_can_view_own_pocket(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => 'Ahorro principal',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/pockets/{$pocket->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'data.id',
                $pocket->id
            )
            ->assertJsonPath(
                'data.name',
                'Ahorro'
            );
    }

    public function test_user_cannot_view_another_users_pocket(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherPocket = Pocket::create([
            'user_id' => $otherUser->id,
            'name' => 'Ahorro privado',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson(
            "/api/pockets/{$otherPocket->id}"
        );

        $response->assertNotFound();
    }

    public function test_user_can_update_own_pocket(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => 'Descripción inicial',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->patchJson(
            "/api/pockets/{$pocket->id}",
            [
                'description' => 'Ahorro principal',
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Bolsillo actualizado correctamente.'
            )
            ->assertJsonPath(
                'data.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.description',
                'Ahorro principal'
            );

        $this->assertDatabaseHas('pockets', [
            'id' => $pocket->id,
            'name' => 'Ahorro',
            'description' => 'Ahorro principal',
        ]);
    }

    public function test_same_user_cannot_create_duplicate_pocket_name(): void
    {
        $user = User::factory()->create();

        Pocket::create([
            'user_id' => $user->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/pockets', [
            'name' => 'Ahorro',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
            ]);
    }

    public function test_different_users_can_use_same_pocket_name(): void
    {
        $firstUser = User::factory()->create();

        $secondUser = User::factory()->create();

        Pocket::create([
            'user_id' => $firstUser->id,
            'name' => 'Ahorro',
            'description' => null,
            'is_active' => true,
        ]);

        Sanctum::actingAs($secondUser);

        $response = $this->postJson('/api/pockets', [
            'name' => 'Ahorro',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath(
                'data.name',
                'Ahorro'
            )
            ->assertJsonPath(
                'data.user_id',
                $secondUser->id
            );
    }

    public function test_deleting_pocket_deactivates_it_without_removing_it(): void
    {
        $user = User::factory()->create();

        $pocket = Pocket::create([
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'description' => 'Gastos cotidianos',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson(
            "/api/pockets/{$pocket->id}"
        );

        $response
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Bolsillo desactivado correctamente.'
            )
            ->assertJsonPath(
                'data.is_active',
                false
            );

        $this->assertDatabaseHas('pockets', [
            'id' => $pocket->id,
            'user_id' => $user->id,
            'name' => 'Uso diario',
            'is_active' => false,
        ]);
    }
}