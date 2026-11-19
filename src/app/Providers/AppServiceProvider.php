<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\ServiceProvider;

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
        ResetPassword::createUrlUsing(function ($user, string $token): string {
            $resetToken = Crypt::encryptString(json_encode([
                'email' => $user->getEmailForPasswordReset(),
                'token' => $token,
            ], JSON_THROW_ON_ERROR));

            return route('password.reset', ['token' => $resetToken]);
        });
    }
}
