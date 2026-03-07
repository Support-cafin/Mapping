<?php

namespace App\Http\Controllers;

use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    // Dashboard admin
    public function dashboard()
    {
        $user = auth()->user();
        
        if ($user->isSuperAdmin()) {
            $entrepriseCount = Entreprise::count();
            $userCount = User::count();
        } else {
            // Pour les admin d'entreprise, seulement leur entreprise
            $entrepriseCount = Entreprise::count(); // MAINTENANT TOUTES LES ENTREPRISES
            $userCount = User::where('entreprise_id', $user->entreprise_id)->count();
        }
        
        return view('admin.dashboard', compact('entrepriseCount', 'userCount'));
    }

    // Gestion des entreprises (MAINTENANT POUR TOUS LES ADMINS)
    public function entreprises()
    {
        // TOUS les admins peuvent voir les entreprises
        $user = auth()->user();
        
        $entreprises = Entreprise::withCount(['users', 'users as admins_count' => function($query) {
            $query->where('is_admin', true);
        }])->orderBy('created_at', 'desc')->get();
        
        // Statistiques globales
        $totalUsers = User::count();
        $adminUsers = User::where('is_admin', true)->count();
        $regularUsers = $totalUsers - $adminUsers;
        
        return view('admin.entreprises.index', compact('entreprises', 'totalUsers', 'adminUsers', 'regularUsers'));
    }
    
    public function createEntreprise()
    {
        // TOUS les admins peuvent créer des entreprises
        return view('admin.entreprises.create');
    }
    
    public function storeEntreprise(Request $request)
    {
        // TOUS les admins peuvent créer des entreprises
        
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:entreprises',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
            'admin_name' => 'nullable|string|max:255',
            'admin_email' => 'nullable|email|unique:users,email',
            'admin_password' => 'nullable|string|min:6',
        ]);
    
        // Créer l'entreprise
        $entreprise = Entreprise::create([
            'nom' => $request->nom,
            'code' => $request->code,
            'adresse' => $request->adresse,
            'telephone' => $request->telephone,
            'email' => $request->email,
        ]);
    
        // Créer un admin pour l'entreprise si demandé
        if ($request->has('create_admin') && $request->admin_name && $request->admin_email && $request->admin_password) {
            User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'is_admin' => true,
                'entreprise_id' => $entreprise->id,
            ]);
        }
    
        return redirect()->route('admin.entreprises.index')
            ->with('success', 'Entreprise créée avec succès' . 
                   ($request->has('create_admin') ? ' et administrateur associé créé' : ''));
    }
    
    // Méthodes supplémentaires pour edit/show/destroy
    public function showEntreprise(Entreprise $entreprise)
    {
        // TOUS les admins peuvent voir les détails d'une entreprise
        $entreprise->load(['users' => function($query) {
            $query->orderBy('is_admin', 'desc')->orderBy('name');
        }]);
        
        return view('admin.entreprises.show', compact('entreprise'));
    }
    
    public function editEntreprise(Entreprise $entreprise)
    {
        // TOUS les admins peuvent modifier les entreprises
        return view('admin.entreprises.edit', compact('entreprise'));
    }
    
    public function updateEntreprise(Request $request, Entreprise $entreprise)
    {
        // TOUS les admins peuvent modifier les entreprises
    
        $request->validate([
            'nom' => 'required|string|max:255',
            'code' => 'required|string|unique:entreprises,code,' . $entreprise->id,
            'email' => 'nullable|email',
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string',
        ]);
    
        $entreprise->update($request->only(['nom', 'code', 'adresse', 'telephone', 'email']));
    
        return redirect()->route('admin.entreprises.index')
            ->with('success', 'Entreprise mise à jour avec succès');
    }
    
    public function destroyEntreprise(Entreprise $entreprise)
    {
        // TOUS les admins peuvent supprimer les entreprises
    
        // Supprimer d'abord tous les utilisateurs de l'entreprise
        $entreprise->users()->delete();
        
        // Puis supprimer l'entreprise
        $entreprise->delete();
    
        return redirect()->route('admin.entreprises.index')
            ->with('success', 'Entreprise et tous ses utilisateurs supprimés avec succès');
    }
    
        // Gestion des utilisateurs (MAINTENANT POUR TOUS LES ADMINS)
    public function users()
    {
        $user = auth()->user();
        // TOUS les admins voient tous les utilisateurs  where('entreprise_id', $user->entreprise_id)
        $users = User::with('entreprise')->orderBy('created_at', 'desc')->get();
        $entreprises = Entreprise::all();
        
        return view('admin.users.index', compact('users', 'entreprises'));
    }

    public function createUser()
    {
        // TOUS les admins peuvent créer des utilisateurs pour n'importe quelle entreprise
        $entreprises = Entreprise::all();
        
        return view('admin.users.create', compact('entreprises'));
    }

    public function storeUser(Request $request)
    {
        // TOUS les admins peuvent créer des utilisateurs pour n'importe quelle entreprise
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'entreprise_id' => 'required|exists:entreprises,id',
            'is_admin' => 'boolean',
        ]);
        
        // Préparer les données
        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'entreprise_id' => $request->entreprise_id,
            'is_admin' => $request->has('is_admin') ? true : false,
        ];
        
        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Utilisateur créé avec succès');
    }
    

    // // Gestion des utilisateurs (DIFFÉRENT selon le type d'admin)
    // public function users()
    // {
    //     $user = auth()->user();
        
    //     if ($user->isSuperAdmin()) {
    //         // Super admin voit tous les utilisateurs
    //         $users = User::with('entreprise')->orderBy('created_at', 'desc')->get();
    //         $entreprises = Entreprise::all();
    //     } else {
    //         // Admin d'entreprise voit seulement ses utilisateurs
    //         $users = User::where('entreprise_id', $user->entreprise_id)
    //                     ->orderBy('created_at', 'desc')
    //                     ->get();
    //         $entreprises = Entreprise::where('id', $user->entreprise_id)->get();
    //     }
        
    //     return view('admin.users.index', compact('users', 'entreprises'));
    // }

    // public function createUser()
    // {
    //     $user = auth()->user();
        
    //     if ($user->isSuperAdmin()) {
    //         // Super admin peut choisir n'importe quelle entreprise
    //         $entreprises = Entreprise::all();
    //     } else {
    //         // Admin d'entreprise ne peut créer que pour son entreprise
    //         $entreprises = Entreprise::where('id', $user->entreprise_id)->get();
    //     }
        
    //     return view('admin.users.create', compact('entreprises'));
    // }

    // public function storeUser(Request $request)
    // {
    //     $currentUser = auth()->user();
        
    //     // Règles de validation
    //     $rules = [
    //         'name' => 'required|string|max:255',
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8|confirmed',
    //     ];
        
    //     // Pour super admin, on peut choisir l'entreprise
    //     if ($currentUser->isSuperAdmin()) {
    //         $rules['entreprise_id'] = 'required|exists:entreprises,id';
    //     }
        
    //     $request->validate($rules);
        
    //     // Préparer les données
    //     $data = [
    //         'name' => $request->name,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),
    //     ];
        
    //     // Gérer l'entreprise_id selon le type d'admin
    //     if ($currentUser->isSuperAdmin()) {
    //         $data['entreprise_id'] = $request->entreprise_id;
    //     } else {
    //         // Admin d'entreprise ne peut créer que pour son entreprise
    //         $data['entreprise_id'] = $currentUser->entreprise_id;
    //     }
        
    //     // Gérer le rôle admin
    //     if ($currentUser->isSuperAdmin()) {
    //         // Super admin peut créer d'autres admins
    //         $data['is_admin'] = $request->has('is_admin');
    //     } else {
    //         // Admin d'entreprise NE PEUT PAS créer d'autres admins
    //         $data['is_admin'] = false;
    //     }
        
    //     User::create($data);

    //     return redirect()->route('admin.users.index')
    //         ->with('success', 'Utilisateur créé avec succès');
    // }
}