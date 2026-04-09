<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Tests\TestCase;

class PasswordResetControllerTest extends TestCase
{
  use RefreshDatabase;

  public function test_guest_can_view_forgot_password_page()
  {
    $response = $this->get(route('password.request'));
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Auth/ForgotPassword'));
  }

  public function test_guest_can_request_password_reset_link()
  {
    $user = User::factory()->create(['email' => 'reset@example.com']);
    $response = $this->post(route('password.email'), [
      'email' => 'reset@example.com',
    ]);
    $response->assertRedirect(route('password.request'));
    $response->assertSessionHas('success');
  }

  public function test_guest_request_password_reset_link_failure_shows_error()
  {
    // Forçar retorno de status de falha
    \Illuminate\Support\Facades\Password::shouldReceive('sendResetLink')
      ->once()
      ->andReturn(\Illuminate\Support\Facades\Password::INVALID_USER);

    $response = $this->post(route('password.email'), [
      'email' => 'doesnotexist@example.com',
    ]);

    $response->assertSessionHasErrors('email');
  }

  public function test_guest_can_view_reset_password_form()
  {
    $token = Password::createToken(User::factory()->create(['email' => 'reset2@example.com']));
    $response = $this->get(route('password.reset', ['token' => $token, 'email' => 'reset2@example.com']));
    $response->assertStatus(200);
    $response->assertInertia(fn($page) => $page->component('Auth/ResetPassword'));
  }

  public function test_guest_can_reset_password()
  {
    $user = User::factory()->create(['email' => 'reset3@example.com']);
    $token = Password::createToken($user);
    $response = $this->post(route('password.update'), [
      'email' => 'reset3@example.com',
      'token' => $token,
      'password' => 'newpassword',
      'password_confirmation' => 'newpassword',
    ]);
    $response->assertRedirect(route('dashboard'));
    $this->assertTrue(
      Hash::check('newpassword', $user->fresh()->password)
    );
  }

  public function test_guest_reset_password_failure_shows_error()
  {
    $user = User::factory()->create(['email' => 'reset4@example.com']);

    // Simular falha no Password::reset
    \Illuminate\Support\Facades\Password::shouldReceive('reset')
      ->once()
      ->andReturn(\Illuminate\Support\Facades\Password::INVALID_TOKEN);

    $response = $this->from(route('password.reset', ['token' => 'token']))->post(route('password.update'), [
      'email' => 'reset4@example.com',
      'token' => 'invalid-token',
      'password' => 'newpassword',
      'password_confirmation' => 'newpassword',
    ]);

    $response->assertSessionHasErrors('email');
  }
}
