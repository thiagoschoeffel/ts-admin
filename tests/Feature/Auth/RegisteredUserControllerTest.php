<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use App\Http\Controllers\Auth\RegisteredUserController;
use Tests\TestCase;

class RegisteredUserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_register_page()
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
        $response->assertSee('Register');
    }

    public function test_authenticated_user_redirected_from_register()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('register'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_register_and_is_redirected_to_verification_notice()
    {
        $response = $this->post(route('register'), [
            'name' => 'Test User',
            'email' => 'testuser@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertRedirect(route('verification.notice'));
        $this->assertDatabaseHas('users', [
            'email' => 'testuser@example.com',
        ]);
    }

    public function test_create_redirects_to_dashboard_when_authenticated_direct_call()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $controller = app(RegisteredUserController::class);
        $response = $controller->create();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('dashboard'), $response->getTargetUrl());
    }
}
