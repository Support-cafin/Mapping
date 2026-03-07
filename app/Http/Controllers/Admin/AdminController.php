<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'Accès réservé aux administrateurs');
            }
            return $next($request);
        });
    }

    // Dashboard admin
    public function dashboard()
    {
        $entrepriseCount = Entreprise::count();
        $userCount = User::count();
        
        return view('admin.dashboard', compact('entrepriseCount', 'userCount'));
    }

    // Gestion des entreprises
    public function entreprises()
    {
        $entreprises = Entreprise::all();
        return view('admin.entreprises', compact('entreprises'));
    }

    public function createEntreprise()
    {
        return view('admin.entreprise-create');
    }

    public function storeEntreprise(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:entreprises',
            'email' => 'nullable|email',
        ]);

        Entreprise::create([
            'nom' => $request->nom,
            'code' => $request->code,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.entreprises')
            ->with('success', 'Entreprise créée avec succès');
    }

    // Gestion des comptes (si nécessaire)
    public function comptes()
    {
        return view('admin.comptes');
    }
}