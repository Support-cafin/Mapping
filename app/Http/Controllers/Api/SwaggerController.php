<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Info(
 *     title="Mapping API",
 *     version="1.0.0",
 *     description="API REST du projet Mapping — comptabilité, grand livre, donateurs, audit.",
 *     @OA\Contact(email="admin@mapping.sn")
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Serveur local"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token Sanctum — obtenu via POST /api/auth/login"
 * )
 *
 * @OA\Tag(name="Auth",         description="Authentification")
 * @OA\Tag(name="Admin",        description="Gestion entreprises & utilisateurs")
 * @OA\Tag(name="Audit Logs",   description="Journal d'audit")
 * @OA\Tag(name="Donateurs",    description="Export PDF donateurs")
 * @OA\Tag(name="Balance",      description="Export balance comptable")
 * @OA\Tag(name="Grand Livre",  description="Export grand livre")
 * @OA\Tag(name="Profile",      description="Profil utilisateur connecté")
 *
 * ─── Schémas réutilisables ───────────────────────────────────────────────────
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     @OA\Property(property="message", type="string",  example="Opération réussie."),
 *     @OA\Property(property="statut",  type="boolean", example=true),
 *     @OA\Property(property="data",    type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ApiError",
 *     @OA\Property(property="message", type="string",  example="Une erreur est survenue."),
 *     @OA\Property(property="statut",  type="boolean", example=false),
 *     @OA\Property(property="data",    type="object")
 * )
 *
 * @OA\Schema(
 *     schema="Entreprise",
 *     @OA\Property(property="id",        type="integer", example=1),
 *     @OA\Property(property="nom",       type="string",  example="ACME Sénégal"),
 *     @OA\Property(property="code",      type="string",  example="ACME"),
 *     @OA\Property(property="email",     type="string",  example="contact@acme.sn"),
 *     @OA\Property(property="telephone", type="string",  example="+221 77 000 00 00"),
 *     @OA\Property(property="adresse",   type="string",  example="Dakar, Sénégal")
 * )
 *
 * @OA\Schema(
 *     schema="User",
 *     @OA\Property(property="id",            type="integer", example=1),
 *     @OA\Property(property="name",          type="string",  example="Mamadou Diallo"),
 *     @OA\Property(property="email",         type="string",  example="m.diallo@acme.sn"),
 *     @OA\Property(property="is_admin",      type="boolean", example=false),
 *     @OA\Property(property="entreprise_id", type="integer", example=1)
 * )
 */
class SwaggerController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/auth/login",
     *     tags={"Auth"},
     *     summary="Connexion et obtention du token Sanctum",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email",    type="string", example="admin@mapping.sn"),
     *             @OA\Property(property="password", type="string", example="secret123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string",  example="Connexion réussie."),
     *             @OA\Property(property="statut",  type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="token", type="string", example="1|abc123..."),
     *                 @OA\Property(property="user",  ref="#/components/schemas/User")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Identifiants invalides", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $credentials = $request->validate([
                'email'    => 'required|email',
                'password' => 'required|string',
            ]);

            if (!Auth::attempt($credentials)) {
                return $this->error('Identifiants invalides.', null, 401);
            }

            $user  = Auth::user();
            $token = $user->createToken('api-token')->plainTextToken;

            return $this->success('Connexion réussie.', ['token' => $token, 'user' => $user]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/auth/logout",
     *     tags={"Auth"},
     *     summary="Déconnexion (révocation du token courant)",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(ref="#/components/schemas/ApiResponse")
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $request->user()->currentAccessToken()->delete();
            return $this->success('Déconnexion réussie.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
