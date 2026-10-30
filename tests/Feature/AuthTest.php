<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_is_logged_in(): void
    {
        $response = $this->postJson('/auth/register', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response->assertCreated()->assertJsonPath('user.email', 'taro@example.com');
        $this->assertAuthenticated();
    }

    public function test_register_rejects_weak_password_and_duplicate_email_in_japanese(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $this->postJson('/auth/register', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'このメールアドレスはすでに使われています。')
            ->assertJsonPath('errors.password.0', 'パスワードは8文字以上で入力してください。');
    }

    public function test_first_user_takes_over_schedules_created_before_login_existed(): void
    {
        $schedule = Schedule::create(['title' => '既存の予定', 'start_date' => '2026-10-30', 'end_date' => '2026-10-30']);

        $first = User::factory()->create();
        $second = User::factory()->create();

        $this->assertSame($first->id, $schedule->fresh()->user_id);
        $this->assertCount(0, $second->schedules);
    }

    public function test_user_can_log_in_and_out(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/auth/login', ['email' => $user->email, 'password' => 'secret123'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
        $this->assertAuthenticatedAs($user);

        $this->postJson('/auth/logout')->assertNoContent();
        $this->assertGuest('web');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->postJson('/auth/login', ['email' => $user->email, 'password' => 'wrong-pass1'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'メールアドレスまたはパスワードが正しくありません。');
        $this->assertGuest();
    }

    public function test_google_only_user_cannot_log_in_with_a_password(): void
    {
        $user = User::factory()->create(['password' => null, 'google_id' => 'google-123']);

        $this->postJson('/auth/login', ['email' => $user->email, 'password' => 'anything1'])
            ->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_current_user_endpoint_returns_null_for_guests(): void
    {
        $this->getJson('/auth/user')->assertOk()->assertJson(['user' => null]);
    }

    public function test_password_can_be_reset_with_emailed_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => 'old-pass1']);

        $this->postJson('/auth/forgot-password', ['email' => $user->email])->assertOk();

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, &$token) {
            $token = $notification->token;
            $mail = $notification->toMail($user);

            // メールのリンクが SPA の再設定ページを指していること
            return str_contains($mail->actionUrl, '/reset-password/'.$token.'?email=');
        });

        $this->postJson('/auth/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-pass1',
            'password_confirmation' => 'new-pass1',
        ])->assertOk();

        $this->assertTrue(Hash::check('new-pass1', $user->fresh()->password));
    }

    public function test_forgot_password_does_not_reveal_unknown_emails(): void
    {
        Notification::fake();

        $this->postJson('/auth/forgot-password', ['email' => 'nobody@example.com'])
            ->assertOk()
            ->assertJsonPath('message', 'パスワード再設定用のリンクをメールで送信しました。');

        Notification::assertNothingSent();
    }

    public function test_reset_does_not_reveal_unknown_emails(): void
    {
        $this->postJson('/auth/reset-password', [
            'token' => 'invalid',
            'email' => 'nobody@example.com',
            'password' => 'new-pass1',
            'password_confirmation' => 'new-pass1',
        ])
            ->assertUnprocessable()
            ->assertJsonPath('errors.email.0', 'この再設定リンクは無効か、有効期限が切れています。もう一度お手続きください。');
    }

    public function test_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();

        $this->postJson('/auth/reset-password', [
            'token' => 'invalid',
            'email' => $user->email,
            'password' => 'new-pass1',
            'password_confirmation' => 'new-pass1',
        ])->assertUnprocessable();
    }

    public function test_google_login_is_unavailable_without_credentials(): void
    {
        config(['services.google.client_id' => null, 'services.google.client_secret' => null]);

        $this->get('/auth/google/redirect')->assertRedirect('/login?error=google_unavailable');
    }
}
