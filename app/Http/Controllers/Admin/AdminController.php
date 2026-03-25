<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

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
        return view('admin.entreprise.create');
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
            'numero_fiscal' => $request->numero_fiscal,
            'numero_agrement' => $request->numero_agrement,
            'sigle_usuel' => $request->sigle_usuel,
            'registre_commerce' => $request->registre_commerce,
            'numero_ninea' => $request->numero_ninea,
            'pays_id' => $request->pays_id,
            'type_compte' => $request->type_compte,
        ]);

        return redirect()->route('admin.entreprises.index')
            ->with('success', 'Entreprise créée avec succès');
    }
    
    // Afficher une entreprise spécifique
    public function showEntreprise(Entreprise $entreprise)
    {
        $pays = DB::table('pays')->orderBy('libelle_fr', 'asc')->get();
        return view('admin.entreprise.show', compact('entreprise', 'pays'));
    }

    // Formulaire d'édition d'une entreprise
    public function editEntreprise(Entreprise $entreprise)
    {
        $pays = DB::table('pays')->orderBy('libelle_fr', 'asc')->get();
        return view('admin.entreprise.edit', compact('entreprise', 'pays'));
    }

    // Mettre à jour une entreprise
    public function updateEntreprise(Request $request, Entreprise $entreprise)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:entreprises,code,' . $entreprise->id,
            'email' => 'nullable|email',
        ]);

        $entreprise->update([
            'nom' => $request->nom,
            'code' => $request->code,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
            'numero_fiscal' => $request->numero_fiscal,
            'numero_agrement' => $request->numero_agrement,
            'sigle_usuel' => $request->sigle_usuel,
            'registre_commerce' => $request->registre_commerce,
            'numero_ninea' => $request->numero_ninea,
            'pays_id' => $request->pays_id,
            'type_compte' => $request->type_compte,
        ]);

        return redirect()->route('admin.entreprises.show', $entreprise)
            ->with('success', 'Entreprise mise à jour avec succès');
    }

    // Supprimer une entreprise
    public function destroyEntreprise(Entreprise $entreprise)
    {
        // Vérifier si l'entreprise a des utilisateurs associés
        if ($entreprise->users()->count() > 0) {
            return redirect()->route('admin.entreprises')
                ->with('error', 'Impossible de supprimer cette entreprise car elle a des utilisateurs associés.');
        }

        $entreprise->delete();

        return redirect()->route('admin.entreprises')
            ->with('success', 'Entreprise supprimée avec succès');
    }

    // Gestion des comptes (si nécessaire)
    public function comptes()
    {
        return view('admin.comptes');
    }
}