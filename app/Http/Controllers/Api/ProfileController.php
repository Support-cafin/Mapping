<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Récupérer le profil de l'utilisateur connecté",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="OK", @OA\JsonContent(ref="#/components/schemas/ApiResponse"))
     * )
     */
    public function show(Request $request): JsonResponse
    {
        try {
            return $this->success('Profil récupéré.', ['user' => $request->user()]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Mettre à jour le profil",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name",  type="string", example="Mamadou Diallo"),
     *             @OA\Property(property="email", type="string", example="m.diallo@acme.sn")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Mis à jour",  @OA\JsonContent(ref="#/components/schemas/ApiResponse")),
     *     @OA\Response(response=422, description="Validation",  @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function update(ProfileUpdateRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $user->fill($request->validated());
            if ($user->isDirty('email')) $user->email_verified_at = null;
            $user->save();
            return $this->success('Profil mis à jour.', ['user' => $user]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/profile",
     *     tags={"Profile"},
     *     summary="Supprimer son compte",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"password"},
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Compte supprimé", @OA\JsonContent(ref="#/components/schemas/ApiResponse")),
     *     @OA\Response(response=422, description="Validation",      @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function destroy(Request $request): JsonResponse
    {
        try {
            $request->validate(['password' => ['required', 'current_password']]);
            $user = $request->user();
            Auth::logout();
            $user->delete();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return $this->success('Compte supprimé avec succès.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
