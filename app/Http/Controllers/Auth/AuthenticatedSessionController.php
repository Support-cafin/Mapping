<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
         try {
        $request->authenticate();
            } catch (ValidationException $e) {
                // Ajouter le nombre de tentatives restantes dans la session flash
                $attempts = RateLimiter::attempts($request->throttleKey());
                $remaining = 3 - $attempts;
                
                if ($remaining > 0) {
                    session()->flash('attempts_remaining', $remaining);
                }
                
                throw $e;
            }

    $request->session()->regenerate();

    // Réinitialiser le compteur côté client en cas de succès
    session()->flash('clear_login_attempts', true);
        $request->authenticate();

        $request->session()->regenerate();

        // Récupérer l'utilisateur authentifié
        $user = Auth::user();
        
        if (!$user) {
            return redirect('/');
        }
        
        // Vérifier si l'utilisateur est admin
        if ($user->isAdmin()) {
            return redirect()->route('parametres.exercices');
        }

        // Pour les utilisateurs non-admin, vérifier s'ils ont une entreprise
        if (!$user->entreprise_id) {
            // Optionnel : déconnecter ou rediriger vers une page d'erreur
            Auth::logout();
            return redirect('/')->withErrors(['email' => 'Votre compte n\'est pas associé à une entreprise.']);
        }

        return redirect()->intended(route('mapping.dual_panel', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}