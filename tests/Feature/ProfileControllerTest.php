<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($this->user);
    }

    public function test_edit_displays_profile_form()
    {
        $response = $this->get(route('profile.edit'));

        $response->assertStatus(200);
        $response->assertInertia(
            fn($page) => $page
                ->has('user')
                ->where('user.id', $this->user->id)
                ->where('user.name', $this->user->name)
                ->where('user.email', $this->user->email)
        );
    }

    public function test_update_updates_profile_without_password()
    {
        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
        ];

        $response = $this->patch(route('profile.update'), $data);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'Perfil atualizado com sucesso.');

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('updated@example.com', $this->user->email);
    }

    public function test_update_updates_profile_with_password()
    {
        $data = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ];

        $response = $this->patch(route('profile.update'), $data);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'Perfil atualizado com sucesso.');

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('updated@example.com', $this->user->email);
        $this->assertTrue(password_verify('newpassword123', $this->user->password));
    }

    public function test_destroy_deletes_account_when_no_clients()
    {
        $response = $this->delete(route('profile.destroy'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status', 'Conta removida com sucesso.');

        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    public function test_destroy_fails_when_user_has_clients()
    {
        Client::factory()->create(['created_by_id' => $this->user->id]);

        $response = $this->delete(route('profile.destroy'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHasErrors('profile', 'Não é possível remover a conta enquanto houver clientes associados ao seu usuário.');

        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    }
}
