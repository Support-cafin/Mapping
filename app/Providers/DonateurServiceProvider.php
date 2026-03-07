<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\DonateurService;

class DonateurServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(DonateurService::class, function ($app) {
            return new DonateurService();
        });
    }

    public function boot()
    {
        // Observer pour la synchronisation comptable
        \App\Models\Donateur::observe(\App\Observers\DonateurObserver::class);
    }
}