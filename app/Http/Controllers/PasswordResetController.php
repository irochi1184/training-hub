<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetController extends Controller
{
    public function showForgotForm(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'パスワードリセットリンクを送信しました。メールをご確認ください。');
        }

        return back()->withErrors(['email' => $this->translateStatus($status)]);
    }

    public function showResetForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'パスワードをリセットしました。新しいパスワードでログインしてください。');
        }

        return back()->withErrors(['email' => $this->translateStatus($status)]);
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            Password::RESET_THROTTLED => 'しばらく待ってから再度お試しください。',
            Password::INVALID_USER => '指定されたメールアドレスのユーザーが見つかりません。',
            Password::INVALID_TOKEN => 'リセットトークンが無効または期限切れです。',
            default => 'パスワードリセットに失敗しました。',
        };
    }
}
