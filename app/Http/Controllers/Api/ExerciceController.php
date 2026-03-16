<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Exercice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciceController extends Controller
{
    use ApiResponseTrait;

    /**
     * Liste des exercices avec recherche et tri
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Exercice::query();

            // Recherche
            if ($request->filled('search')) {
                $query->where('libelle', 'like', '%' . $request->search . '%');
            }

            // Tri
            $sortField     = $request->get('sort_field', 'created_at');
            $sortDirection = $request->get('sort_direction', 'desc');
            $allowedSorts  = ['libelle', 'date_debut', 'date_fin', 'statut', 'created_at'];

            if (in_array($sortField, $allowedSorts)) {
                $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
            }

            // Pagination
            $perPage  = $request->get('per_page', 10);
            $exercices = $query->paginate($perPage);

            return $this->success('Liste des exercices.', [
                'exercices'      => $exercices->items(),
                'total'          => $exercices->total(),
                'per_page'       => $exercices->perPage(),
                'current_page'   => $exercices->currentPage(),
                'last_page'      => $exercices->lastPage(),
                'stats' => [
                    'total'  => Exercice::count(),
                    'actifs' => Exercice::actif()->count(),
                    'fermes' => Exercice::ferme()->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Détail d'un exercice
     */
    public function show(int $id): JsonResponse
    {
        try {
            $exercice = Exercice::find($id);

            if (!$exercice) {
                return $this->notFound('Exercice introuvable.');
            }

            return $this->success('Détail de l\'exercice.', $exercice);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer un exercice
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'libelle'    => 'required|string|max:100',
                'date_debut' => 'required|date',
                'date_fin'   => 'required|date|after:date_debut',
                'statut'     => 'boolean',
            ], [
                'libelle.required'    => 'Le libellé est obligatoire.',
                'libelle.max'         => 'Le libellé ne doit pas dépasser 100 caractères.',
                'date_debut.required' => 'La date de début est obligatoire.',
                'date_debut.date'     => 'La date de début n\'est pas valide.',
                'date_fin.required'   => 'La date de fin est obligatoire.',
                'date_fin.date'       => 'La date de fin n\'est pas valide.',
                'date_fin.after'      => 'La date de fin doit être postérieure à la date de début.',
            ]);

            // Vérifier si on veut activer et qu'un exercice actif existe déjà
            if (!empty($validated['statut']) && $validated['statut'] == Exercice::STATUT_ACTIF) {
                if (Exercice::autreExerciceActif(null)) {
                    return $this->error('Un exercice est déjà actif. Veuillez fermer l\'exercice actif avant d\'en ouvrir un nouveau.');
                }
            }

            DB::beginTransaction();
            $exercice = Exercice::create($validated);
            DB::commit();

            return $this->success('Exercice créé avec succès.', $exercice, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Modifier un exercice
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $exercice = Exercice::find($id);

            if (!$exercice) {
                return $this->notFound('Exercice introuvable.');
            }

            $validated = $request->validate([
                'libelle'    => 'required|string|max:100',
                'date_debut' => 'required|date',
                'date_fin'   => 'required|date|after:date_debut',
                'statut'     => 'boolean',
            ], [
                'libelle.required'    => 'Le libellé est obligatoire.',
                'libelle.max'         => 'Le libellé ne doit pas dépasser 100 caractères.',
                'date_debut.required' => 'La date de début est obligatoire.',
                'date_debut.date'     => 'La date de début n\'est pas valide.',
                'date_fin.required'   => 'La date de fin est obligatoire.',
                'date_fin.date'       => 'La date de fin n\'est pas valide.',
                'date_fin.after'      => 'La date de fin doit être postérieure à la date de début.',
            ]);

            // Vérifier conflit d'exercice actif
            if (!empty($validated['statut']) && $validated['statut'] == Exercice::STATUT_ACTIF) {
                if (Exercice::autreExerciceActif($id)) {
                    return $this->error('Un exercice est déjà actif. Veuillez fermer l\'exercice actif avant d\'en ouvrir un nouveau.');
                }
            }

            DB::beginTransaction();
            $exercice->update($validated);
            DB::commit();

            return $this->success('Exercice modifié avec succès.', $exercice);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer un exercice (interdit si actif)
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $exercice = Exercice::find($id);

            if (!$exercice) {
                return $this->notFound('Exercice introuvable.');
            }

            if ($exercice->estActif()) {
                return $this->error('Impossible de supprimer un exercice actif.');
            }

            DB::beginTransaction();
            $exercice->delete();
            DB::commit();

            return $this->success('Exercice supprimé avec succès.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Activer / Fermer un exercice (toggle statut)
     */
    public function toggleStatut(int $id): JsonResponse
    {
        try {
            $exercice = Exercice::find($id);

            if (!$exercice) {
                return $this->notFound('Exercice introuvable.');
            }

            $nouveauStatut = !$exercice->statut;

            // Si on veut activer, vérifier qu'aucun autre exercice n'est actif
            if ($nouveauStatut == Exercice::STATUT_ACTIF) {
                if (Exercice::autreExerciceActif($id)) {
                    return $this->error('Un exercice est déjà actif. Veuillez le fermer d\'abord.');
                }
            }

            DB::beginTransaction();
            $exercice->update(['statut' => $nouveauStatut]);
            DB::commit();

            $message = $nouveauStatut ? 'Exercice activé avec succès.' : 'Exercice fermé avec succès.';
            return $this->success($message, $exercice);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }
}
