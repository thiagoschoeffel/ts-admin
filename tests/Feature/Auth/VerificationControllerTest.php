<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Tests\TestCase;

class VerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_register_if_no_session()
    {
        $response = $this->get(route('verification.notice'));
        $response->assertRedirect(route('register'));
    }

    public function test_authenticated_verified_user_redirected_to_dashboard()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_authenticated_unverified_user_sees_notice()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $response = $this->actingAs($user)->get(route('verification.notice'));
        $response->assertStatus(200);
        $response->assertSee('Verify');
    }

    public function test_guest_with_registered_user_id_sees_notice()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $response = $this->withSession(['registered_user_id' => $user->id])->get(route('verification.notice'));
        $response->assertStatus(200);
        $response->assertSee('Verify');
    }

    public function test_verification_link_verifies_user()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
        $response = $this->actingAs($user)->get($url);
        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_resend_verification_notification_for_valid_user()
    {
        $user = User::factory()->create(['email_verified_at' => null]);
        $response = $this->withSession(['registered_user_id' => $user->id])
            ->post(route('verification.send'));
        $response->assertRedirect(route('verification.notice'));
        $response->assertSessionHas('success');
    }

    public function test_resend_verification_notification_for_invalid_user_redirects_to_login()
    {
        $response = $this->withSession(['registered_user_id' => 9999])
            ->post(route('verification.send'));
        $response->assertRedirect(route('login'));
    }

    public function test_verify_with_valid_email_verification_request()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->actingAs($user)->get($url);

        $response->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $response->assertSessionHas('success');
    }

    public function test_verify_with_invalid_hash_returns_403()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // URL assinada com hash inválido (assinatura válida, hash errado para o usuário)
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong@example.com')]
        );

        $response = $this->get($url);

        // Deve passar pelo middleware 'signed' e falhar no check de hash (linha 80)
        $response->assertStatus(403);
    }

    public function test_verify_with_invalid_user_returns_404()
    {
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 9999, 'hash' => sha1('fake@example.com')]
        );
        $response = $this->get($url);
        $response->assertStatus(404);
    }

    public function test_guest_with_registered_user_id_and_verified_user_redirects_to_dashboard()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $response = $this->withSession(['registered_user_id' => $user->id])->get(route('verification.notice'));
        $response->assertRedirect(route('dashboard'));
    }

    public function test_verify_already_verified_user_redirects_to_dashboard()
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
        $response = $this->actingAs($user)->get($url);
        $response->assertRedirect(route('dashboard'));
    }

    public function test_verify_with_invalid_signed_url_returns_403()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        $url = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(60), // URL expirou
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $response = $this->get($url);

        $response->assertStatus(403);
    }

    public function test_verify_branch_with_EmailVerificationRequest_authenticated_user()
    {
        $user = User::factory()->create(['email_verified_at' => null]);

        // Autenticar para que Auth::user() funcione dentro do controller
        $this->actingAs($user);

        // Usar uma classe anônima que estende EmailVerificationRequest e sobrescreve fulfill()
        $request = new class extends EmailVerificationRequest {
            public function fulfill(): void {}
        };

        $response = app(\App\Http\Controllers\Auth\VerificationController::class)->verify($request);

        // Cobre linhas 62-67 (fulfill + flashes + redirect)
        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertEquals(route('dashboard'), $response->getTargetUrl());
        $this->assertTrue(session()->has('success'));
    }
}
