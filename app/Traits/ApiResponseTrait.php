<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponseTrait
{
    protected function success(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'statut'  => true,
            'data'    => $data ?? [],
        ], $status);
    }

    protected function error(string $message, mixed $data = null, int $status = 400): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'statut'  => false,
            'data'    => $data ?? [],
        ], $status);
    }

    protected function serverError(string $message = 'Une erreur interne est survenue.', mixed $data = null): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'statut'  => false,
            'data'    => $data ?? [],
        ], 500);
    }

    protected function notFound(string $message = 'Ressource introuvable.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'statut'  => false,
            'data'    => [],
        ], 404);
    }

    protected function forbidden(string $message = 'Accès refusé.'): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'statut'  => false,
            'data'    => [],
        ], 403);
    }

    protected function validationError(mixed $errors): JsonResponse
    {
        return response()->json([
            'message' => 'Erreur de validation.',
            'statut'  => false,
            'data'    => $errors,
        ], 422);
    }
}
