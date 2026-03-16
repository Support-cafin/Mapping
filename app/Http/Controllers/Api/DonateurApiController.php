<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Donateur;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DonateurApiController extends Controller
{
    use ApiResponseTrait;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function entrepriseId(): int
    {
        return auth()->user()->entreprise_id;
    }

    private function buildQuery(Request $request, int $entrepriseId)
    {
        $query = Donateur::where('entreprise_id', $entrepriseId);

        // Recherche texte
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('denomination', 'like', "%{$search}%")
                    ->orWhere('nom_prenoms', 'like', "%{$search}%")
                    ->orWhere('numero_enregistrement', 'like', "%{$search}%")
                    ->orWhere('registre_commerce', 'like', "%{$search}%")
                    ->orWhere('numero_identification_fiscal', 'like', "%{$search}%");
            });
        }

        // Filtres
        if ($request->filled('year')) {
            $query->whereYear('date', $request->year);
        }

        if ($request->filled('month')) {
            $query->whereMonth('date', $request->month);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('mode_liberation')) {
            $query->where('mode_liberation', $request->mode_liberation);
        }

        return $query;
    }

    // ─── Liste ────────────────────────────────────────────────────────────────

    /**
     * GET /api/donateurs
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $entrepriseId = $this->entrepriseId();
            $query        = $this->buildQuery($request, $entrepriseId);

            // Tri
            $sortField     = in_array($request->get('sort_field', 'date'), [
                'date', 'denomination', 'nom_prenoms',
                'montant_don', 'statut', 'mode_liberation',
            ]) ? $request->get('sort_field', 'date') : 'date';
            $sortDirection = $request->get('sort_direction', 'desc') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);

            // Stats (avant pagination)
            $statsQuery = $this->buildQuery($request, $entrepriseId);
            $stats = [
                'enregistre'   => (clone $statsQuery)->where('statut', 'enregistré')->count(),
                'valide'       => (clone $statsQuery)->where('statut', 'validé')->count(),
                'comptabilise' => (clone $statsQuery)->where('statut', 'comptabilisé')->count(),
                'annule'       => (clone $statsQuery)->where('statut', 'annulé')->count(),
                'total'        => (clone $statsQuery)->count(),
                'montant_total'=> (clone $statsQuery)->sum('montant_don'),
            ];

            // Total annuel
            $totalAnnuel = null;
            if ($request->filled('year')) {
                $totalAnnuel = (clone $statsQuery)->whereYear('date', $request->year)->sum('montant_don');
            }

            // Pagination
            $perPage   = (int) $request->get('per_page', 10);
            $donateurs = $query->paginate(min($perPage, 100));

            return $this->success('Liste des donateurs.', [
                'donateurs'    => $donateurs->items(),
                'total'        => $donateurs->total(),
                'per_page'     => $donateurs->perPage(),
                'current_page' => $donateurs->currentPage(),
                'last_page'    => $donateurs->lastPage(),
                'stats'        => $stats,
                'total_annuel' => $totalAnnuel,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Détail ───────────────────────────────────────────────────────────────

    /**
     * GET /api/donateurs/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())
                ->find($id);

            if (!$donateur) {
                return $this->notFound('Donateur introuvable.');
            }

            return $this->success('Détail du donateur.', $donateur);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Créer ────────────────────────────────────────────────────────────────

    /**
     * POST /api/donateurs
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date'                        => 'required|date',
                'denomination'                => 'nullable|string|max:255',
                'nom_prenoms'                 => 'nullable|string|max:255',
                'numero_enregistrement'       => 'nullable|string|max:100',
                'registre_commerce'           => 'nullable|string|max:100',
                'numero_identification_fiscal'=> 'nullable|string|max:100',
                'montant_don'                 => 'required|numeric|min:0',
                'mode_liberation'             => 'nullable|string|max:100',
                'statut'                      => 'nullable|in:enregistré,validé,comptabilisé,annulé',
            ]);

            if (empty($validated['denomination']) && empty($validated['nom_prenoms'])) {
                return $this->error('La dénomination ou le nom/prénoms est requis.');
            }

            $donateur = Donateur::create(array_merge($validated, [
                'entreprise_id' => $this->entrepriseId(),
                'statut'        => $validated['statut'] ?? 'enregistré',
            ]));

            return $this->success('Donateur créé avec succès.', $donateur, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Modifier ─────────────────────────────────────────────────────────────

    /**
     * PUT /api/donateurs/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())->find($id);

            if (!$donateur) {
                return $this->notFound('Donateur introuvable.');
            }

            $validated = $request->validate([
                'date'                        => 'sometimes|required|date',
                'denomination'                => 'nullable|string|max:255',
                'nom_prenoms'                 => 'nullable|string|max:255',
                'numero_enregistrement'       => 'nullable|string|max:100',
                'registre_commerce'           => 'nullable|string|max:100',
                'numero_identification_fiscal'=> 'nullable|string|max:100',
                'montant_don'                 => 'sometimes|required|numeric|min:0',
                'mode_liberation'             => 'nullable|string|max:100',
                'statut'                      => 'nullable|in:enregistré,validé,comptabilisé,annulé',
            ]);

            $donateur->update($validated);

            return $this->success('Donateur modifié avec succès.', $donateur);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Supprimer ────────────────────────────────────────────────────────────

    /**
     * DELETE /api/donateurs/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())->find($id);

            if (!$donateur) {
                return $this->notFound('Donateur introuvable.');
            }

            $donateur->delete();

            return $this->success('Donateur supprimé avec succès.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Changer statut ───────────────────────────────────────────────────────

    /**
     * PATCH /api/donateurs/{id}/statut
     */
    public function updateStatut(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'statut' => 'required|in:enregistré,validé,comptabilisé,annulé',
            ]);

            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())->find($id);

            if (!$donateur) {
                return $this->notFound('Donateur introuvable.');
            }

            $donateur->update(['statut' => $validated['statut']]);

            return $this->success('Statut mis à jour.', $donateur);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Filtres disponibles ──────────────────────────────────────────────────

    /**
     * GET /api/donateurs/filtres
     * Retourne les années dispo, mois, statuts, et modes de libération
     */
    public function filtres(): JsonResponse
    {
        try {
            $entrepriseId = $this->entrepriseId();

            $years = Donateur::where('entreprise_id', $entrepriseId)
                ->selectRaw('YEAR(date) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year');

            $modes = Donateur::where('entreprise_id', $entrepriseId)
                ->whereNotNull('mode_liberation')
                ->distinct()
                ->orderBy('mode_liberation')
                ->pluck('mode_liberation');

            return $this->success('Filtres disponibles.', [
                'years'   => $years,
                'months'  => [
                    '01' => 'Janvier',  '02' => 'Février',  '03' => 'Mars',
                    '04' => 'Avril',    '05' => 'Mai',      '06' => 'Juin',
                    '07' => 'Juillet',  '08' => 'Août',     '09' => 'Septembre',
                    '10' => 'Octobre',  '11' => 'Novembre', '12' => 'Décembre',
                ],
                'statuts' => [
                    'enregistré'   => 'Enregistré',
                    'validé'       => 'Validé',
                    'comptabilisé' => 'Comptabilisé',
                    'annulé'       => 'Annulé',
                ],
                'modes_liberation' => $modes,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Stats globales ───────────────────────────────────────────────────────

    /**
     * GET /api/donateurs/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $entrepriseId = $this->entrepriseId();

            $total  = Donateur::where('entreprise_id', $entrepriseId)->count();
            $montant = Donateur::where('entreprise_id', $entrepriseId)->sum('montant_don');

            // Stats par statut
            $parStatut = Donateur::where('entreprise_id', $entrepriseId)
                ->select('statut', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant_don) as total'))
                ->groupBy('statut')
                ->get();

            // Stats par mode de libération
            $parMode = Donateur::where('entreprise_id', $entrepriseId)
                ->select('mode_liberation', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant_don) as total'))
                ->groupBy('mode_liberation')
                ->get();

            // Stats par année
            $parAnnee = Donateur::where('entreprise_id', $entrepriseId)
                ->selectRaw('YEAR(date) as year, COUNT(*) as count, SUM(montant_don) as total')
                ->groupBy('year')
                ->orderBy('year', 'desc')
                ->get();

            return $this->success('Statistiques des donateurs.', [
                'total_donateurs'  => $total,
                'montant_total'    => $montant,
                'par_statut'       => $parStatut,
                'par_mode'         => $parMode,
                'par_annee'        => $parAnnee,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
