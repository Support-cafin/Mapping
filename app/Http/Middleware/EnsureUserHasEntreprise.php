<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasEntreprise
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (!auth()->user()->entreprise_id) {
            // Rediriger vers une page pour choisir/ajouter une entreprise
            return redirect()->route('entreprise.select');
        }

        return $next($request);
    }
}