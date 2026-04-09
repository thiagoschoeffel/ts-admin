<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function notice(Request $request)
    {
        // If the user is authenticated
        if (Auth::check()) {
            $user = Auth::user();
            // If already verified, send to dashboard
            if ($user->hasVerifiedEmail()) {
                return redirect()->route('dashboard');
            }

            // Authenticated but not verified -> show notice
            return Inertia::render('Auth/VerifyNotice');
        }

        // Guest: if there's a recently registered user id in the session, show notice
        if ($request->session()->has('registered_user_id')) {
            $id = $request->session()->get('registered_user_id');
            $user = User::find($id);
            if ($user && $user->hasVerifiedEmail()) {
                return redirect()->route('dashboard');
            }
            return Inertia::render('Auth/VerifyNotice');
        }

        // Unauthenticated and no registration in progress -> redirect to register
        return redirect()->route('register');
    }

    public function resend(Request $request)
    {
        $id = $request->session()->pull('registered_user_id');
        $user = User::find($id);
        if (!$user) {
            return redirect()->route('login');
        }

        $user->sendEmailVerificationNotification();
        session()->flash('status', __('validation.custom_messages.login_required'));
        session()->flash('success', __('validation.custom_messages.verification_resent'));
        session()->flash('flash_id', (string) \Illuminate\Support\Str::uuid());
        return redirect()->route('verification.notice');
    }

    public function verify(Request $request, $id = null, $hash = null)
    {
        // If the framework provided EmailVerificationRequest (signed + authenticated), use it
        if ($request instanceof EmailVerificationRequest) {
            $request->fulfill();
            $user = Auth::user();
            // Flash success so layouts will show a toast
            session()->flash('success', __('validation.custom_messages.email_verified'));
            session()->flash('flash_id', (string) \Illuminate\Support\Str::uuid());
            return redirect()->route('dashboard');
        }

        // Otherwise, handle unauthenticated link clicks: validate id/hash manually
        $id = $id ?? $request->route('id');
        $hash = $hash ?? $request->route('hash');

        $user = User::find($id);
        if (! $user) {
            abort(404);
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        Auth::login($user);
        session()->flash('success', __('validation.custom_messages.email_verified'));
        session()->flash('flash_id', (string) \Illuminate\Support\Str::uuid());
        return redirect()->route('dashboard');
    }
}
