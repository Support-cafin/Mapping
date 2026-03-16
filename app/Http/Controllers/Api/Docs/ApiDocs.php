<?php

namespace App\Http\Controllers\Api\Docs;

/**
 * @OA\Info(
 *     title="Mapping API",
 *     version="1.0.0",
 *     description="API REST du projet Mapping — comptabilité, grand livre, donateurs, audit."
 * )
 *
 * @OA\Server(url="http://localhost:8000")
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(name="Auth",        description="Authentification")
 * @OA\Tag(name="Admin",       description="Gestion entreprises et utilisateurs")
 * @OA\Tag(name="Audit Logs",  description="Journal d'audit")
 * @OA\Tag(name="Donateurs",   description="Export PDF donateurs")
 * @OA\Tag(name="Balance",     description="Export balance comptable")
 * @OA\Tag(name="Grand Livre", description="Export grand livre")
 * @OA\Tag(name="Profile",     description="Profil utilisateur")
 */
class ApiDocs {}
