<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // パスワード再設定メール。リンク先は SPA の再設定ページにする
        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = url('/reset-password/'.$token).'?email='.urlencode($notifiable->getEmailForPasswordReset());
            $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('【Tsumugu】パスワード再設定のご案内')
                ->greeting($notifiable->name.' 様')
                ->line('パスワード再設定のリクエストを受け付けました。下のボタンから新しいパスワードを設定してください。')
                ->action('パスワードを再設定する', $url)
                ->line("このリンクの有効期限は{$expire}分です。")
                ->line('お心当たりがない場合は、このメールを破棄してください。パスワードは変更されません。')
                ->salutation('Tsumugu（つむぐ）');
        });
    }
}
