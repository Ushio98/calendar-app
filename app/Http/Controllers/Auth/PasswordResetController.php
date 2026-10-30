<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    /**
     * Email a password reset link.
     */
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }

        // 登録されているメールアドレスかどうかを第三者に知られないよう、
        // 該当ユーザーがいない場合も送信済みと同じ応答を返す
        return response()->json(['message' => __(Password::RESET_LINK_SENT)]);
    }

    /**
     * Set a new password using the token from the reset email.
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            // 「ユーザーが見つからない」とは返さず、無効なリンクとして扱う（登録の有無を知られないため）
            $message = $status === Password::INVALID_USER ? __(Password::INVALID_TOKEN) : __($status);

            throw ValidationException::withMessages(['email' => $message]);
        }

        return response()->json(['message' => __($status)]);
    }
}
