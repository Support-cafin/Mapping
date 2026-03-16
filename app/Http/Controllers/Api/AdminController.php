<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Entreprise;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    use ApiResponseTrait;

    private function currentUser() { return Auth::user(); }
    private function isSuperAdmin(): bool {
        $u = $this->currentUser();
        return $u->is_admin && ($u->is_Super_admin ?? false);
    }

    // ─── GET /api/admin/users ─────────────────────────────────────────────────
    public function users(Request $request): JsonResponse
    {
        try {
            $user  = $this->currentUser();
            $query = User::with('entreprise:id,nom,code')->orderBy('created_at', 'desc');

            // Admin normal → seulement son entreprise
            // Super admin → tous les utilisateurs
            if (!$this->isSuperAdmin()) {
                $query->where('entreprise_id', $user->entreprise_id);
            }

            // Filtres
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(fn($q) => $q
                    ->where('name', 'like', "%{$s}%")
                    ->orWhere('email', 'like', "%{$s}%")
                );
            }
            if ($request->filled('role')) {
                $query->where('is_admin', $request->role === 'admin');
            }
            if ($request->filled('entreprise_id') && $this->isSuperAdmin()) {
                $query->where('entreprise_id', $request->entreprise_id);
            }

            $users = $query->get()->map(fn($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'email'        => $u->email,
                'is_admin'     => (bool) $u->is_admin,
                'entreprise_id'=> $u->entreprise_id,
                'entreprise'   => $u->entreprise ? ['id' => $u->entreprise->id, 'nom' => $u->entreprise->nom] : null,
                'created_at'   => $u->created_at?->format('d/m/Y'),
                'created_at_time' => $u->created_at?->format('H:i'),
                'initiale'     => strtoupper(substr($u->name, 0, 1)),
            ]);

            // Stats
            $statsBase = User::query();
            if (!$this->isSuperAdmin()) $statsBase->where('entreprise_id', $user->entreprise_id);

            $stats = [
                'total'      => (clone $statsBase)->count(),
                'admins'     => (clone $statsBase)->where('is_admin', true)->count(),
                'ce_mois'    => (clone $statsBase)->where('created_at', '>=', now()->subMonth())->count(),
                'entreprises'=> $this->isSuperAdmin() ? Entreprise::count() : 1,
            ];

            // Entreprises pour le filtre (super admin seulement)
            $entreprises = $this->isSuperAdmin()
                ? Entreprise::orderBy('nom')->get(['id', 'nom'])
                : [];

            return $this->success('OK', [
                'users'       => $users,
                'stats'       => $stats,
                'entreprises' => $entreprises,
                'is_super_admin' => $this->isSuperAdmin(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── POST /api/admin/users ────────────────────────────────────────────────
    public function storeUser(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'name'          => 'required|string|max:255',
                'email'         => 'required|email|max:255|unique:users,email',
                'password'      => 'required|string|min:8|confirmed',
                'entreprise_id' => 'required|exists:entreprises,id',
                'is_admin'      => 'boolean',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $user = $this->currentUser();

            // Admin normal → peut uniquement créer pour son entreprise, pas admin
            $entrepriseId = $this->isSuperAdmin()
                ? $request->entreprise_id
                : $user->entreprise_id;

            $isAdmin = $this->isSuperAdmin() ? (bool) $request->is_admin : false;

            $newUser = User::create([
                'name'          => $request->name,
                'email'         => $request->email,
                'password'      => Hash::make($request->password),
                'entreprise_id' => $entrepriseId,
                'is_admin'      => $isAdmin,
            ]);

            return $this->success('Utilisateur créé avec succès.', [
                'id'    => $newUser->id,
                'name'  => $newUser->name,
                'email' => $newUser->email,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── PUT /api/admin/users/{id} ────────────────────────────────────────────
    public function updateUser(Request $request, int $id): JsonResponse
    {
        try {
            $current = $this->currentUser();
            $target  = User::find($id);

            if (!$target) return $this->notFound('Utilisateur introuvable.');

            // Admin normal → seulement son entreprise
            if (!$this->isSuperAdmin() && $target->entreprise_id !== $current->entreprise_id) {
                return $this->forbidden('Accès refusé.');
            }

            $validator = Validator::make($request->all(), [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email|max:255|unique:users,email,' . $id,
                'is_admin' => 'boolean',
                'password' => 'nullable|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $data = [
                'name'  => $request->name,
                'email' => $request->email,
            ];
            if ($this->isSuperAdmin()) {
                $data['is_admin'] = (bool) $request->is_admin;
            }
            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            $target->update($data);
            return $this->success('Utilisateur mis à jour.', $target->fresh()->load('entreprise'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── DELETE /api/admin/users/{id} ─────────────────────────────────────────
    public function destroyUser(int $id): JsonResponse
    {
        try {
            $current = $this->currentUser();
            $target  = User::find($id);

            if (!$target) return $this->notFound('Utilisateur introuvable.');
            if ($target->id === $current->id) {
                return $this->validationError(['id' => ['Impossible de supprimer votre propre compte.']]);
            }
            if (!$this->isSuperAdmin() && $target->entreprise_id !== $current->entreprise_id) {
                return $this->forbidden('Accès refusé.');
            }

            $target->delete();
            return $this->success('Utilisateur supprimé.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
