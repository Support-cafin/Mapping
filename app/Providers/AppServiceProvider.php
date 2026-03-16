<?php

namespace App\Providers;

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
        // Ignorer les warnings swagger-php traités comme erreurs
        set_error_handler(function($errno, $errstr) {
            if (str_contains($errstr, 'swagger') || str_contains($errstr, '@OA')) {
                return true; // ignorer
            }
            return false;
        }, E_USER_WARNING | E_USER_NOTICE);
    }
}
