<?php

namespace App\Providers;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // doctrine/dbal を使わずにマイグレーションで列を変更（->change()）できるようにする
        Schema::useNativeSchemaOperationsIfPossible();

        // パスワードは8文字以上で、英字と数字を両方含める
        Password::defaults(fn () => Password::min(8)->letters()->numbers());
    }
}
