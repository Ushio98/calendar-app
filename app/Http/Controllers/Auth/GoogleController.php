<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    /**
     * Send the user to Google's sign-in screen.
     */
    public function redirect()
    {
        if (! $this->configured()) {
            return redirect('/login?error=google_unavailable');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle the response from Google and log the user in.
     */
    public function callback(Request $request)
    {
        if (! $this->configured()) {
            return redirect('/login?error=google_unavailable');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            // キャンセルされた場合や state の不一致など
            report($e);

            return redirect('/login?error=google_failed');
        }

        $email = $googleUser->getEmail();
        $emailVerified = (bool) ($googleUser->user['email_verified'] ?? false);

        if (! $email) {
            return redirect('/login?error=google_failed');
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                // 既存アカウントとの連携は、Google側でメールアドレスが確認済みの場合に限る
                if (! $emailVerified) {
                    return redirect('/login?error=google_unverified');
                }

                $attributes = [
                    'google_id' => $googleUser->getId(),
                    'avatar' => $user->avatar ?: $googleUser->getAvatar(),
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ];

                // メール未確認のアカウントは第三者が先にそのアドレスで登録した可能性があるため、
                // 本人であることが確認できたこの時点でパスワードを無効にする
                if (! $user->email_verified_at) {
                    $attributes['password'] = null;
                    $attributes['remember_token'] = Str::random(60);
                }

                $user->forceFill($attributes)->save();
            } else {
                $user = User::create([
                    'name' => $googleUser->getName() ?: Str::before($email, '@'),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => $emailVerified ? now() : null,
                ]);
            }
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect('/');
    }

    private function configured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
