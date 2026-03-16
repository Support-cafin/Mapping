<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Donateur;
use App\Models\NewAccount;
use App\Models\Entreprise;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class DonateurController extends Controller
{
    use ApiResponseTrait;

    private function getEntreprise()
    {
        return Auth::user()->entreprise;
    }

    private function entrepriseId(): int
    {
        return Auth::user()->entreprise_id;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // LISTE + FILTRES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/donateurs
     * Paramètres : search, year, month, statut, sort_field, sort_direction, page, per_page
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $entrepriseId = $this->entrepriseId();

            $query = Donateur::where('entreprise_id', $entrepriseId)
                ->with(['newAccount:id,code,intitule', 'createur:id,name', 'modificateur:id,name']);

            // Recherche
            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('denomination', 'like', "%{$s}%")
                        ->orWhere('nom_prenoms', 'like', "%{$s}%")
                        ->orWhere('numero_enregistrement', 'like', "%{$s}%")
                        ->orWhere('registre_commerce', 'like', "%{$s}%")
                        ->orWhere('numero_identification_fiscal', 'like', "%{$s}%");
                });
            }

            // Filtres date
            if ($request->filled('year'))  $query->whereYear('date', $request->year);
            if ($request->filled('month')) $query->whereMonth('date', $request->month);

            // Filtre statut
            if ($request->filled('statut')) $query->where('statut', $request->statut);

            // Tri
            $sortField     = in_array($request->get('sort_field'), ['date', 'denomination', 'montant_don', 'statut', 'created_at'])
                ? $request->get('sort_field') : 'date';
            $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);

            // Pagination
            $perPage = (int) $request->get('per_page', 15);
            $paginator = $query->paginate($perPage);

            // Stats globales
            $statsQuery = Donateur::where('entreprise_id', $entrepriseId);
            $stats = [
                'total'           => $statsQuery->count(),
                'total_montant'   => $statsQuery->sum('montant_don'),
                'enregistré'      => $statsQuery->where('statut', 'enregistré')->count(),
                'validé'          => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'validé')->count(),
                'comptabilisé'    => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'comptabilisé')->count(),
                'annulé'          => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'annulé')->count(),
            ];

            return $this->success('OK', [
                'donateurs'  => $paginator->items(),
                'stats'      => $stats,
                'pagination' => [
                    'total'        => $paginator->total(),
                    'per_page'     => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'from'         => $paginator->firstItem(),
                    'to'           => $paginator->lastItem(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/donateurs/filtres
     * Retourne les valeurs disponibles pour les filtres (années, comptes)
     */
    public function filtres(): JsonResponse
    {
        try {
            $entrepriseId = $this->entrepriseId();

            $years = Donateur::where('entreprise_id', $entrepriseId)
                ->selectRaw('YEAR(date) as year')
                ->distinct()
                ->orderBy('year', 'desc')
                ->pluck('year')
                ->filter()
                ->values();

            $comptes = NewAccount::where('entreprise_id', $entrepriseId)
                ->orderBy('code')
                ->get(['id', 'code', 'intitule']);

            return $this->success('OK', [
                'years'   => $years,
                'comptes' => $comptes,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/donateurs/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $entrepriseId = $this->entrepriseId();

            return $this->success('OK', [
                'total'         => Donateur::where('entreprise_id', $entrepriseId)->count(),
                'total_montant' => Donateur::where('entreprise_id', $entrepriseId)->sum('montant_don'),
                'enregistré'    => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'enregistré')->count(),
                'validé'        => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'validé')->count(),
                'comptabilisé'  => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'comptabilisé')->count(),
                'annulé'        => Donateur::where('entreprise_id', $entrepriseId)->where('statut', 'annulé')->count(),
                'par_devise'    => Donateur::where('entreprise_id', $entrepriseId)
                    ->selectRaw('devise, COUNT(*) as nb, SUM(montant_don) as total')
                    ->groupBy('devise')
                    ->get(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // CRUD
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/donateurs/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())
                ->with(['newAccount:id,code,intitule', 'createur:id,name', 'modificateur:id,name'])
                ->find($id);

            if (!$donateur) return $this->notFound('Donateur introuvable.');

            return $this->success('OK', $donateur);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/donateurs
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'date'         => 'required|date',
                'montant_don'  => 'required|numeric|min:0',
                'devise'       => 'nullable|string|max:10',
                'denomination' => 'nullable|string|max:255',
                'nom_prenoms'  => 'nullable|string|max:255',
                'statut'       => 'nullable|in:enregistré,validé,comptabilisé,annulé',
                'mode_liberation' => 'nullable|in:espèces,chèque,virement,nature',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $entrepriseId = $this->entrepriseId();

            // Générer le numéro d'enregistrement
            $lastNum = Donateur::where('entreprise_id', $entrepriseId)->max('numero_enregistrement');
            $numero  = 'DON-' . str_pad((int)substr($lastNum ?? 'DON-0', 4) + 1, 5, '0', STR_PAD_LEFT);

            $donateur = Donateur::create([
                'entreprise_id'               => $entrepriseId,
                'numero_enregistrement'        => $numero,
                'date'                         => $request->date,
                'denomination'                 => $request->denomination,
                'nom_prenoms'                  => $request->nom_prenoms,
                'adresse_siege_social'         => $request->adresse_siege_social,
                'registre_commerce'            => $request->registre_commerce,
                'numero_identification_fiscal' => $request->numero_identification_fiscal,
                'email'                        => $request->email,
                'montant_don'                  => $request->montant_don,
                'devise'                       => $request->devise ?? 'XOF',
                'mode_liberation'              => $request->mode_liberation,
                'new_account_id'               => $request->new_account_id,
                'statut'                       => $request->statut ?? 'enregistré',
                'notes'                        => $request->notes,
                'created_by'                   => Auth::id(),
            ]);

            return $this->success('Donateur créé avec succès.', $donateur->load('newAccount'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/donateurs/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())->find($id);
            if (!$donateur) return $this->notFound('Donateur introuvable.');

            $validator = Validator::make($request->all(), [
                'date'        => 'sometimes|required|date',
                'montant_don' => 'sometimes|required|numeric|min:0',
                'statut'      => 'nullable|in:enregistré,validé,comptabilisé,annulé',
                'mode_liberation' => 'nullable|in:espèces,chèque,virement,nature',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $donateur->update(array_merge(
                $request->only([
                    'date', 'denomination', 'nom_prenoms', 'adresse_siege_social',
                    'registre_commerce', 'numero_identification_fiscal', 'email',
                    'montant_don', 'devise', 'mode_liberation', 'new_account_id',
                    'statut', 'notes',
                ]),
                ['updated_by' => Auth::id()]
            ));

            return $this->success('Donateur mis à jour.', $donateur->fresh()->load('newAccount'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/donateurs/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())->find($id);
            if (!$donateur) return $this->notFound('Donateur introuvable.');

            $donateur->delete();
            return $this->success('Donateur supprimé.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PATCH /api/donateurs/{id}/statut
     * Identique à changerStatut() du Livewire FicheDonateur
     */
    public function updateStatut(Request $request, int $id): JsonResponse
    {
        try {
            $donateur = Donateur::where('entreprise_id', $this->entrepriseId())->find($id);
            if (!$donateur) return $this->notFound('Donateur introuvable.');

            $validator = Validator::make($request->all(), [
                'statut' => 'required|in:enregistré,validé,comptabilisé,annulé',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $donateur->update([
                'statut'     => $request->statut,
                'updated_by' => Auth::id(),
            ]);

            return $this->success('Statut mis à jour.', $donateur->fresh()->load('newAccount'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXPORTS PDF (identiques au DonateurPdfController Livewire)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/donateurs/export/pdf
     * Registre PDF de tous les donateurs (identique à DonateurPdfController::export)
     */
    public function exportPdf(Request $request)
    {
        try {
            $entrepriseId = $this->entrepriseId();
            $entreprise   = $this->getEntreprise();

            $query = Donateur::where('entreprise_id', $entrepriseId);

            if ($request->filled('search')) {
                $s = $request->search;
                $query->where(function ($q) use ($s) {
                    $q->where('denomination', 'like', "%{$s}%")
                        ->orWhere('nom_prenoms', 'like', "%{$s}%")
                        ->orWhere('numero_enregistrement', 'like', "%{$s}%")
                        ->orWhere('registre_commerce', 'like', "%{$s}%")
                        ->orWhere('numero_identification_fiscal', 'like', "%{$s}%");
                });
            }

            if ($request->filled('year'))   $query->whereYear('date', $request->year);
            if ($request->filled('month'))  $query->whereMonth('date', $request->month);
            if ($request->filled('statut')) $query->where('statut', $request->statut);

            $sortField     = in_array($request->get('sort_field'), ['date', 'denomination', 'montant_don', 'statut'])
                ? $request->get('sort_field') : 'date';
            $sortDirection = $request->get('sort_direction') === 'asc' ? 'asc' : 'desc';
            $query->orderBy($sortField, $sortDirection);

            $donateurs = $query->get();

            // Grouper par 2 (identique au Livewire)
            $donateursArray   = $donateurs->toArray();
            $donateursGrouped = array_chunk($donateursArray, 2);

            $pdf = Pdf::loadView('pdf.donateurs-liste', [
                'donateursGrouped' => $donateursGrouped,
                'totalDonateurs'   => $donateurs->count(),
                'totalMontant'     => $donateurs->sum('montant_don'),
                'dateGeneration'   => now()->format('d/m/Y H:i'),
                'entreprise'       => $entreprise,
                'filters'          => $request->only(['search', 'year', 'month', 'statut']),
            ])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'helvetica',
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => true,
                ]);

            $filename = 'registre-donateurs-' . ($entreprise->code ?? 'export') . '-' . now()->format('Y-m-d') . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage(), 'statut' => false], 500);
        }
    }

    /**
     * GET /api/donateurs/{id}/fiche/pdf
     * Fiche individuelle PDF (identique à DonateurPdfController::ficheIndividuelle)
     */
    public function fichePdf(int $id)
    {
        try {
            $entreprise = $this->getEntreprise();

            $donateur = Donateur::with(['newAccount:id,code,intitule', 'createur:id,name', 'modificateur:id,name'])
                ->where('entreprise_id', $this->entrepriseId())
                ->find($id);

            if (!$donateur) {
                return response()->json(['message' => 'Donateur introuvable.', 'statut' => false], 404);
            }

            $pdf = Pdf::loadView('pdf.donateur-fiche', [
                'donateur'       => $donateur,
                'entreprise'     => $entreprise,
                'dateGeneration' => now()->format('d/m/Y H:i'),
            ])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'helvetica',
                    'isHtml5ParserEnabled' => true,
                ]);

            $filename = 'fiche-donateur-' . ($donateur->numero_enregistrement ?? $id) . '.pdf';

            return $pdf->download($filename);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage(), 'statut' => false], 500);
        }
    }
}
