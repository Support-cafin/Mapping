<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileApiController extends Controller
{
    use ApiResponseTrait;

    // ─── GET /api/profile ─────────────────────────────────────────────────────
    public function show(): JsonResponse
    {
        try {
            $user = Auth::user()->load('entreprise');

            return $this->success('OK', [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'is_admin'   => $user->is_admin,
                'entreprise' => $user->entreprise ? [
                    'id'  => $user->entreprise->id,
                    'nom' => $user->entreprise->nom,
                    'code'=> $user->entreprise->code,
                ] : null,
                'created_at' => $user->created_at?->format('d/m/Y'),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── PUT /api/profile ─────────────────────────────────────────────────────
    // Met à jour nom + email (identique à ProfileController::update du Livewire)
    public function update(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'name'  => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            // Si l'email change → réinitialiser la vérification (identique au Livewire)
            if ($user->email !== $request->email) {
                $user->email_verified_at = null;
            }

            $user->name  = $request->name;
            $user->email = $request->email;
            $user->save();

            return $this->success('Profil mis à jour.', [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── PUT /api/profile/password ────────────────────────────────────────────
    // Changer le mot de passe
    public function updatePassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password'      => 'required|string',
                'password'              => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $user = Auth::user();

            if (!Hash::check($request->current_password, $user->password)) {
                return $this->validationError([
                    'current_password' => ['Le mot de passe actuel est incorrect.'],
                ]);
            }

            $user->password = Hash::make($request->password);
            $user->save();

            return $this->success('Mot de passe mis à jour.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── DELETE /api/profile ──────────────────────────────────────────────────
    // Supprimer le compte (identique à ProfileController::destroy)
    public function destroy(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'password' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $user = Auth::user();

            if (!Hash::check($request->password, $user->password)) {
                return $this->validationError([
                    'password' => ['Mot de passe incorrect.'],
                ]);
            }

            Auth::logout();
            $user->delete();

            return $this->success('Compte supprimé.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
