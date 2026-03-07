<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\GrandLivre;
use App\Policies\GrandLivrePolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        GrandLivre::class => GrandLivrePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}