<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use App\Models\User;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_login_page()
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $response->assertInertia(fn($page) => $page->component('Auth/Login'));
    }
    public function test_login_with_invalid_credentials_fails()
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_with_inactive_user_shows_custom_error()
    {
        $user = User::factory()->create([
            'email' => 'inactive2@example.com',
            'password' => bcrypt('password'),
            'status' => 'inactive',
        ]);
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'inactive2@example.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_redirected_from_login()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('login'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_user_redirected_to_dashboard()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_can_login_and_redirected_to_dashboard()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
        $response = $this->post(route('login'), [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_inactive_user_cannot_login()
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'status' => 'inactive',
        ]);
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_logout()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);
        $response = $this->post(route('logout'));
        $response->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_create_redirects_to_dashboard_when_authenticated_direct_call()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($user);

        $controller = app(AuthenticatedSessionController::class);
        $response = $controller->create();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(route('dashboard'), $response->getTargetUrl());
    }
}
