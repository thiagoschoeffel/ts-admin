<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class PasswordResetController extends Controller
{
    public function request()
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function email(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status == Password::RESET_LINK_SENT) {
            session()->flash('success', __($status));
            session()->flash('flash_id', (string) Str::uuid());
            return redirect()->route('password.request');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function resetForm(Request $request, $token)
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function reset(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                Auth::login($user);
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            session()->flash('success', __($status));
            session()->flash('flash_id', (string) Str::uuid());
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => [__($status)]]);
    }
}
