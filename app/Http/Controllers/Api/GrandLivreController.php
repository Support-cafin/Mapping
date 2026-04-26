<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\GrandLivre;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class GrandLivreController extends Controller
{
    use ApiResponseTrait;

    const MAX_RESULTS = 25000;

    // ─── Helpers privés ───────────────────────────────────────────────────────

    private ?object $_exerciceActif = null;

    private function exerciceActif(): ?object
    {
        if ($this->_exerciceActif === null) {
            $this->_exerciceActif = DB::table('exercices')->where('statut', 1)->first() ?? false;
        }
        return $this->_exerciceActif ?: null;
    }

    private function entrepriseId(): int
    {
        return auth()->user()->entreprise_id;
    }

    private function buildQuery(Request $request, int $entrepriseId, ?object $exo)
    {
        $t = 'grand_livres';

        $query = GrandLivre::select([
            "{$t}.id", "{$t}.date_ecriture", "{$t}.journal_code", "{$t}.piece",
            "{$t}.libelle", "{$t}.debit", "{$t}.credit", "{$t}.old_account_id",
            "{$t}.new_account_id", "{$t}.exercice", "{$t}.exercice_id", "{$t}.lettre", "{$t}.source"
        ])
            ->where("{$t}.exercice_id", $exo->id ?? '')
            ->where("{$t}.entreprise_id", $entrepriseId)
            ->where("{$t}.validated", true)
            ->with(['oldAccount:id,code,intitule', 'newAccount:id,code,intitule']);

        // Filtre dates
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween("{$t}.date_ecriture", [$request->date_debut, $request->date_fin]);
        }

        // Filtre exercice
        if ($request->filled('exercice')) {
            $query->where("{$t}.exercice", $request->exercice);
        }

        // Filtre journal
        if ($request->filled('journal_code')) {
            $query->where("{$t}.journal_code", $request->journal_code);
        }

        // Filtre lettre
        if ($request->filled('lettre')) {
            if ($request->lettre === 'non') {
                $query->whereNull("{$t}.lettre");
            } else {
                $query->where("{$t}.lettre", $request->lettre);
            }
        }

        // Filtre comptes (old ou new)
        $accountType      = $request->get('account_type', 'all');
        $selectedAccounts = $request->get('selected_accounts', []);

        if ($accountType !== 'all' && !empty($selectedAccounts)) {
            if ($accountType === 'old') {
                $query->whereIn("{$t}.old_account_id", $selectedAccounts);
            } elseif ($accountType === 'new') {
                $query->whereIn("{$t}.new_account_id", $selectedAccounts);
            }
        }

        // Recherche texte
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $t) {
                $q->where("{$t}.libelle", 'like', "%{$search}%")
                    ->orWhere("{$t}.piece", 'like', "%{$search}%")
                    ->orWhereHas('oldAccount', fn($s) => $s->where('code', 'like', "%{$search}%")->orWhere('intitule', 'like', "%{$search}%"))
                    ->orWhereHas('newAccount', fn($s) => $s->where('code', 'like', "%{$search}%")->orWhere('intitule', 'like', "%{$search}%"));
            });
        }

        // Filtre source
        if ($request->filled('source_filter') && $request->source_filter !== 'all') {
            $query->where("{$t}.source", $request->source_filter);
        }

        // Filtre mapping
        if ($request->filled('mapping_filter') && $request->mapping_filter !== 'all') {
            if ($request->mapping_filter === 'mapped') {
                $query->whereNotNull("{$t}.new_account_id");
            } elseif ($request->mapping_filter === 'unmapped') {
                $query->whereNull("{$t}.new_account_id");
            }
        }

        // Tri
        $sortField     = $request->get('sort_field', 'date_ecriture');
        $sortDirection = $request->get('sort_direction', 'desc');
        $allowed       = ['date_ecriture', 'journal_code', 'piece', 'libelle', 'debit', 'credit', 'source'];

        if (in_array($sortField, $allowed)) {
            if ($sortField === 'source') {
                $query->orderByRaw("CASE WHEN {$t}.source = 'manuel' THEN 1 ELSE 0 END")
                    ->orderBy("{$t}.{$sortField}", $sortDirection);
            } else {
                $query->orderBy("{$t}.{$sortField}", $sortDirection);
            }
        }

        return $query;
    }

    // ─── Liste des écritures ──────────────────────────────────────────────────

    /**
     * GET /api/grand-livre/ecritures
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            // ── 1. Pagination (2 requêtes internes : count + select) ──────────
            $perPage   = min((int) $request->get('per_page', 100), 1000);
            $ecritures = $this->buildQuery($request, $entrepriseId, $exo)
                ->paginate($perPage);

            // ── 2. Stats globales en UNE seule requête avec SUM(CASE…) ────────
            $aggRow = $this->buildQuery($request, $entrepriseId, $exo)
                ->reorder()
                ->select(DB::raw('
                    COUNT(*)                                                      AS total,
                    COALESCE(SUM(debit), 0)                                      AS total_debit,
                    COALESCE(SUM(credit), 0)                                     AS total_credit,
                    COUNT(DISTINCT journal_code)                                 AS count_journaux,
                    SUM(CASE WHEN new_account_id IS NOT NULL THEN 1 ELSE 0 END) AS mapped_count,
                    SUM(CASE WHEN new_account_id IS NULL     THEN 1 ELSE 0 END) AS unmapped_count
                '))
                ->first();

            $stats = [
                'total'          => (int)   $aggRow->total,
                'total_debit'    => (float) $aggRow->total_debit,
                'total_credit'   => (float) $aggRow->total_credit,
                'solde'          => abs((float) $aggRow->total_debit - (float) $aggRow->total_credit),
                'count_journaux' => (int)   $aggRow->count_journaux,
                'mapped_count'   => (int)   $aggRow->mapped_count,
                'unmapped_count' => (int)   $aggRow->unmapped_count,
            ];

            // ── 3. Stats par classe SYCEBNL via JOIN SQL (pas de chargement PHP) ──
            $classRows = $this->buildQuery($request, $entrepriseId, $exo)
                ->reorder()
                ->leftJoin('new_accounts as na_cs', 'grand_livres.new_account_id', '=', 'na_cs.id')
                ->select(DB::raw('
                    CASE
                        WHEN na_cs.code IS NULL                                   THEN \'non_mappes\'
                        WHEN LEFT(na_cs.code, 1) IN (\'1\',\'2\',\'3\',\'4\',\'5\') THEN \'classe_1_5\'
                        WHEN LEFT(na_cs.code, 1) IN (\'6\',\'7\',\'8\')            THEN \'classe_6_7\'
                        WHEN LEFT(na_cs.code, 1) = \'9\'                           THEN \'classe_9\'
                        ELSE \'non_mappes\'
                    END AS classe,
                    COALESCE(SUM(grand_livres.debit),  0) AS debit,
                    COALESCE(SUM(grand_livres.credit), 0) AS credit
                '))
                ->groupByRaw('
                    CASE
                        WHEN na_cs.code IS NULL                                   THEN \'non_mappes\'
                        WHEN LEFT(na_cs.code, 1) IN (\'1\',\'2\',\'3\',\'4\',\'5\') THEN \'classe_1_5\'
                        WHEN LEFT(na_cs.code, 1) IN (\'6\',\'7\',\'8\')            THEN \'classe_6_7\'
                        WHEN LEFT(na_cs.code, 1) = \'9\'                           THEN \'classe_9\'
                        ELSE \'non_mappes\'
                    END
                ')
                ->get();

            $classStats = [
                'classe_1_5' => ['debit' => 0.0, 'credit' => 0.0, 'solde' => 0.0],
                'classe_6_7' => ['debit' => 0.0, 'credit' => 0.0, 'solde' => 0.0],
                'classe_9'   => ['debit' => 0.0, 'credit' => 0.0, 'solde' => 0.0],
                'non_mappes' => ['debit' => 0.0, 'credit' => 0.0, 'solde' => 0.0],
                'global'     => ['debit' => 0.0, 'credit' => 0.0, 'solde' => 0.0],
            ];

            foreach ($classRows as $row) {
                $d = (float) $row->debit;
                $c = (float) $row->credit;
                $key = $row->classe;
                if (isset($classStats[$key])) {
                    $classStats[$key]['debit']  = $d;
                    $classStats[$key]['credit'] = $c;
                    $classStats[$key]['solde']  = $d - $c;
                }
                $classStats['global']['debit']  += $d;
                $classStats['global']['credit'] += $c;
                $classStats['global']['solde']  += ($d - $c);
            }

            return $this->success('Liste des écritures.', [
                'ecritures'    => $ecritures->items(),
                'total'        => $ecritures->total(),
                'per_page'     => $ecritures->perPage(),
                'current_page' => $ecritures->currentPage(),
                'last_page'    => $ecritures->lastPage(),
                'stats'        => $stats,
                'class_stats'  => $classStats,
                'truncated'    => $ecritures->total() > self::MAX_RESULTS,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/grand-livre/ecritures/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $exo = $this->exerciceActif();

            $ecriture = GrandLivre::with(['oldAccount', 'newAccount'])
                ->where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$ecriture) {
                return $this->notFound('Écriture introuvable.');
            }

            return $this->success('Détail de l\'écriture.', $ecriture);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/grand-livre/ecritures
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'date_ecriture'  => 'required|date',
                'old_account_id' => 'required|exists:old_accounts,id',
                'debit'          => 'nullable|numeric|min:0',
                'credit'         => 'nullable|numeric|min:0',
                'libelle'        => 'nullable|string|max:255',
                'piece'          => 'nullable|string|max:100',
                'journal_code'   => 'nullable|string|max:20',
                'exercice'       => 'nullable|integer',
                'lettre'         => 'nullable|string|max:10',
            ]);

            if ((($validated['debit'] ?? 0) <= 0) && (($validated['credit'] ?? 0) <= 0)) {
                return $this->error('Le débit ou le crédit doit être renseigné avec un montant positif.');
            }

            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            // Vérifier que le compte appartient à l'entreprise
            $oldAccount = OldAccount::find($validated['old_account_id']);
            if (!$oldAccount || $oldAccount->entreprise_id !== $entrepriseId) {
                return $this->error('Compte invalide ou non autorisé.');
            }

            DB::beginTransaction();

            $ecriture = GrandLivre::create([
                'date_ecriture'  => $validated['date_ecriture'],
                'journal_code'   => $validated['journal_code'] ?? null,
                'piece'          => $validated['piece'] ?? null,
                'libelle'        => $validated['libelle'] ?? null,
                'debit'          => $validated['debit'] ?? 0,
                'credit'         => $validated['credit'] ?? 0,
                'old_account_id' => $validated['old_account_id'],
                'exercice'       => $validated['exercice'] ?? null,
                'exercice_id'    => $exo->id ?? '',
                'lettre'         => $validated['lettre'] ?? null,
                'entreprise_id'  => $entrepriseId,
                'source'         => 'manuel',
                'valide'         => true,
                'synced'         => false,
            ]);

            $ecriture->syncMapping();

            DB::commit();

            return $this->success(
                'Écriture ajoutée avec succès.',
                $ecriture->load(['oldAccount', 'newAccount']),
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * PUT /api/grand-livre/ecritures/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $exo = $this->exerciceActif();

            $ecriture = GrandLivre::with(['oldAccount', 'newAccount'])
                ->where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$ecriture) {
                return $this->notFound('Écriture introuvable.');
            }

            if ($ecriture->newAccount && $ecriture->old_account_id != $request->old_account_id) {
                return $this->error('Impossible de modifier le compte d\'une écriture déjà mappée.');
            }

            $validated = $request->validate([
                'date_ecriture'  => 'required|date',
                'old_account_id' => 'required|exists:old_accounts,id',
                'debit'          => 'nullable|numeric|min:0',
                'credit'         => 'nullable|numeric|min:0',
                'libelle'        => 'nullable|string|max:255',
                'piece'          => 'nullable|string|max:100',
                'journal_code'   => 'nullable|string|max:20',
                'exercice'       => 'nullable|integer',
                'lettre'         => 'nullable|string|max:10',
            ]);

            if ((($validated['debit'] ?? 0) <= 0) && (($validated['credit'] ?? 0) <= 0)) {
                return $this->error('Le débit ou le crédit doit être renseigné avec un montant positif.');
            }

            DB::beginTransaction();

            $ecriture->update([
                'date_ecriture'  => $validated['date_ecriture'],
                'journal_code'   => $validated['journal_code'] ?? null,
                'piece'          => $validated['piece'] ?? null,
                'libelle'        => $validated['libelle'] ?? null,
                'debit'          => $validated['debit'] ?? 0,
                'credit'         => $validated['credit'] ?? 0,
                'old_account_id' => $validated['old_account_id'],
                'exercice'       => $validated['exercice'] ?? null,
                'lettre'         => $validated['lettre'] ?? null,
                'synced'         => false,
            ]);

            $ecriture->syncMapping();

            DB::commit();

            return $this->success('Écriture modifiée avec succès.', $ecriture->load(['oldAccount', 'newAccount']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/grand-livre/ecritures/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $exo = $this->exerciceActif();

            $ecriture = GrandLivre::where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$ecriture) {
                return $this->notFound('Écriture introuvable.');
            }

            if ($ecriture->newAccount) {
                return $this->error('Impossible de supprimer une écriture déjà mappée.');
            }

            $ecriture->delete();

            return $this->success('Écriture supprimée avec succès.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/grand-livre/ecritures (suppression multiple par IDs)
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        try {
            if (!auth()->user()->is_admin) {
                return $this->forbidden('Action réservée aux administrateurs.');
            }

            $validated = $request->validate([
                'ids'   => 'required|array|min:1',
                'ids.*' => 'integer',
            ]);

            $exo  = $this->exerciceActif();
            $user = auth()->user();

            DB::beginTransaction();

            $deleted = GrandLivre::whereIn('id', $validated['ids'])
                ->where('entreprise_id', $user->entreprise_id)
                ->where('exercice_id', $exo->id ?? '')
                ->delete();

            DB::commit();

            return $this->success("{$deleted} écriture(s) supprimée(s).");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * DELETE /api/grand-livre/ecritures/all (suppression totale — admin requis)
     */
    public function destroyAll(Request $request): JsonResponse
    {
        try {
            if (!auth()->user()->is_admin) {
                return $this->forbidden('Action réservée aux administrateurs.');
            }

            $validated = $request->validate([
                'confirmation' => 'required|in:SUPPRIMER-TOUT',
            ]);

            $exo  = $this->exerciceActif();
            $user = auth()->user();

            DB::beginTransaction();

            $deleted = GrandLivre::where('entreprise_id', $user->entreprise_id)
                ->where('exercice_id', $exo->id ?? '')
                ->delete();

            DB::commit();

            Log::critical('Suppression totale Grand Livre via API', [
                'user_id'       => $user->id,
                'entreprise_id' => $user->entreprise_id,
                'deleted'       => $deleted,
            ]);

            return $this->success("{$deleted} écriture(s) supprimée(s).");
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Actions spéciales ────────────────────────────────────────────────────

    /**
     * POST /api/grand-livre/ecritures/{id}/lettre
     */
    public function lettre(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'lettre' => 'required|string|max:10',
            ]);

            $exo = $this->exerciceActif();

            $ecriture = GrandLivre::where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$ecriture) {
                return $this->notFound('Écriture introuvable.');
            }

            $ecriture->update(['lettre' => $validated['lettre']]);

            return $this->success('Écriture lettrée : ' . $validated['lettre'], $ecriture);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/grand-livre/sync
     */
    public function syncMappings(): JsonResponse
    {
        try {
            $exo     = $this->exerciceActif();
            $updated = 0;

            GrandLivre::forEntreprise($this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->whereNotNull('old_account_id')
                ->chunk(100, function ($ecritures) use (&$updated) {
                    foreach ($ecritures as $ecriture) {
                        if ($ecriture->syncMapping()) {
                            $updated++;
                        }
                    }
                });

            return $this->success("{$updated} écriture(s) synchronisée(s) avec les mappings.");
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/grand-livre/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $query        = $this->buildQuery($request, $entrepriseId, $exo);

            $ecritures = $query->get();

            // Stats par classe de compte SYCEBNL
            $classStats = [
                'classe_1_5'     => ['debit' => 0, 'credit' => 0, 'solde' => 0],
                'classe_6_7'     => ['debit' => 0, 'credit' => 0, 'solde' => 0],
                'classe_9'       => ['debit' => 0, 'credit' => 0, 'solde' => 0],
                'autres_comptes' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
                'non_mappes'     => ['debit' => 0, 'credit' => 0, 'solde' => 0],
                'global'         => ['debit' => 0, 'credit' => 0, 'solde' => 0],
            ];

            foreach ($ecritures as $e) {
                $d = $e->debit ?? 0;
                $c = $e->credit ?? 0;

                $classStats['global']['debit']  += $d;
                $classStats['global']['credit'] += $c;
                $classStats['global']['solde']  += ($d - $c);

                if ($e->newAccount && $e->newAccount->code) {
                    $first = substr($e->newAccount->code, 0, 1);

                    if (in_array($first, ['1', '2', '3', '4', '5'])) {
                        $key = 'classe_1_5';
                    } elseif (in_array($first, ['6', '7', '8'])) {
                        $key = 'classe_6_7';
                    } elseif ($first === '9') {
                        $key = 'classe_9';
                    } else {
                        $key = 'autres_comptes';
                    }
                } else {
                    $key = 'non_mappes';
                }

                $classStats[$key]['debit']  += $d;
                $classStats[$key]['credit'] += $c;
                $classStats[$key]['solde']  += ($d - $c);
            }

            return $this->success('Statistiques du Grand Livre.', [
                'class_stats'   => $classStats,
                'total_debit'   => $ecritures->sum('debit'),
                'total_credit'  => $ecritures->sum('credit'),
                'difference'    => abs($ecritures->sum('debit') - $ecritures->sum('credit')),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/grand-livre/journaux
     */
    public function journaux(): JsonResponse
    {
        try {
            $exo = $this->exerciceActif();

            $journaux = GrandLivre::forEntreprise($this->entrepriseId())
                ->select('journal_code')
                ->distinct()
                ->where('exercice_id', $exo->id ?? '')
                ->whereNotNull('journal_code')
                ->orderBy('journal_code')
                ->pluck('journal_code');

            return $this->success('Liste des journaux.', $journaux);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/grand-livre/comptes
     */
    public function comptes(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $type         = $request->get('type', 'old');
            $search       = $request->get('search', '');

            if ($type === 'old') {
                $query = OldAccount::where('entreprise_id', $entrepriseId)
                    ->where('exercice_id', $exo->id ?? '');
            } else {
                $query = NewAccount::where('entreprise_id', $entrepriseId)
                    ->where('exercice_id', $exo->id ?? '');
            }

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', $search . '%')
                        ->orWhere('intitule', 'like', '%' . $search . '%');
                });
            }

            $comptes = $query->orderByRaw('LEFT(code, 1) ASC, LENGTH(code) ASC, code ASC')
                ->limit(100)
                ->get(['id', 'code', 'intitule']);

            return $this->success('Liste des comptes.', $comptes);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * POST /api/grand-livre/import
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'file'          => 'required|file|mimes:xlsx,xls,csv|max:10240',
                'exercice'      => 'required|integer|min:2000|max:' . (date('Y') + 5),
                'auto_validate' => 'boolean',
            ]);

            $entrepriseId  = $this->entrepriseId();
            $entreprise    = auth()->user()->entreprise;
            $entrepriseName = $entreprise->nom ?? $entreprise->raison_sociale ?? 'Entreprise';

            $importClass = new \App\Imports\GrandLivreImport(
                $entrepriseId,
                $validated['exercice'],
                $validated['auto_validate'] ?? true,
                $entrepriseName
            );

            Excel::import($importClass, $request->file('file')->getRealPath());

            $importedCount = $importClass->getImportedCount();
            $errors        = $importClass->getErrors();
            $warnings      = $importClass->getWarnings();

            if ($importedCount === 0 && empty($errors)) {
                return $this->error('Aucune écriture importée. Vérifiez le format du fichier.');
            }

            return $this->success("Import terminé. {$importedCount} écriture(s) importée(s).", [
                'imported'  => $importedCount,
                'errors'    => $errors,
                'warnings'  => $warnings,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            Log::error('Import Grand Livre failed', ['error' => $e->getMessage()]);
            return $this->serverError($e->getMessage());
        }
    }

    public function general(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            if (!$exo) {
                return $this->error('Aucun exercice actif trouvé.');
            }

            // ── 1. Requête de base — identique à Livewire::baseAccountQuery() ──
            // Jointure : grand_livres → account_mappings → new_accounts
            $baseQuery = DB::table('grand_livres as gl')
                ->leftJoin('account_mappings as am', function ($join) use ($entrepriseId) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                        ->where('am.entreprise_id', $entrepriseId);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
                ->where('gl.entreprise_id', $entrepriseId)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id');  // ← uniquement les écritures MAPPÉES (comme Livewire)

            // ── 2. Filtres (même logique que Livewire::applyQueryFilters) ──
            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $baseQuery->whereBetween('gl.date_ecriture', [
                    $request->date_debut,
                    $request->date_fin,
                ]);
            }

            if ($request->filled('journal_code')) {
                $baseQuery->where('gl.journal_code', $request->journal_code);
            }

            if ($request->filled('search')) {
                $s = $request->search;
                $baseQuery->where(function ($q) use ($s) {
                    $q->where('gl.libelle',    'like', "%{$s}%")
                        ->orWhere('gl.piece',    'like', "%{$s}%")
                        ->orWhere('oa.code',     'like', "%{$s}%")
                        ->orWhere('oa.intitule', 'like', "%{$s}%")
                        ->orWhere('na.code',     'like', "%{$s}%")
                        ->orWhere('na.intitule', 'like', "%{$s}%");
                });
            }

            // Filtre par comptes SYCEBNL sélectionnés (codes new_account)
            $selectedAccounts = $request->get('selected_accounts', []);
            if (!empty($selectedAccounts)) {
                $baseQuery->whereIn('na.code', $selectedAccounts);
            }

            // ── 3. Total des comptes distincts (pour la pagination) ──
            $totalComptes = (clone $baseQuery)
                ->distinct('na.code')
                ->count('na.code');

            // ── 4. Pagination des COMPTES (comme Livewire avec loadedCount) ──
            $perPage     = (int) $request->get('per_page', 50);
            $page        = (int) $request->get('page', 1);
            $offset      = ($page - 1) * $perPage;
            $lastPage    = max(1, (int) ceil($totalComptes / $perPage));
            $hasMore     = $page < $lastPage;

            // Récupérer les comptes de cette page (groupés, triés par code)
            $accountsPage = (clone $baseQuery)
                ->select([
                    'na.code as new_account_code',
                    'na.intitule as new_account_intitule',
                    DB::raw('SUM(gl.debit)    as total_debit'),
                    DB::raw('SUM(gl.credit)   as total_credit'),
                    DB::raw('COUNT(gl.id)     as nombre_ecritures'),
                ])
                ->groupBy('na.code', 'na.intitule')
                ->orderBy('na.code')
                ->offset($offset)
                ->limit($perPage)
                ->get();

            if ($accountsPage->isEmpty()) {
                return $this->success('Grand Livre Général.', [
                    'comptes'      => [],
                    'stats'        => $this->buildGeneralStats([], 0),
                    'total_comptes'=> 0,
                    'current_page' => $page,
                    'last_page'    => 1,
                    'per_page'     => $perPage,
                    'has_more'     => false,
                ]);
            }

            // ── 5. Récupérer les écritures pour ces comptes ──
            // Même logique que Livewire::getEcrituresForAccounts()
            $accountCodes = $accountsPage->pluck('new_account_code')->toArray();

            $ecritures = (clone $baseQuery)
                ->whereIn('na.code', $accountCodes)
                ->select([
                    'gl.id',
                    'gl.date_ecriture',
                    'gl.piece',
                    'gl.journal_code',
                    'gl.libelle',
                    'gl.debit',
                    'gl.credit',
                    'oa.code     as old_account_code',
                    'oa.intitule as old_account_intitule',
                    'na.code     as new_account_code',
                    'na.intitule as new_account_intitule',
                ])
                ->orderBy('na.code')
                ->orderBy('gl.date_ecriture')
                ->orderBy('gl.id')
                ->get();

            // Grouper les écritures par code SYCEBNL
            $ecrituresGrouped = $ecritures->groupBy('new_account_code');

            // ── 6. Construire la réponse par compte ──
            $comptes = $accountsPage->map(function ($account) use ($ecrituresGrouped) {
                $totalDebit  = (float) $account->total_debit;
                $totalCredit = (float) $account->total_credit;
                $solde       = $totalDebit - $totalCredit;

                return [
                    'code'             => $account->new_account_code,
                    'intitule'         => $account->new_account_intitule,
                    'nombre_ecritures' => (int) $account->nombre_ecritures,
                    'total_debit'      => $totalDebit,
                    'total_credit'     => $totalCredit,
                    'solde'            => $solde,
                    'solde_absolu'     => abs($solde),
                    'is_debiteur'      => $solde > 0,
                    'is_crediteur'     => $solde < 0,
                    'ecritures'        => $ecrituresGrouped
                        ->get($account->new_account_code, collect())
                        ->map(fn ($e) => [
                            'id'                   => $e->id,
                            'date_ecriture'        => $e->date_ecriture,
                            'piece'                => $e->piece,
                            'journal_code'         => $e->journal_code,
                            'libelle'              => $e->libelle,
                            'debit'                => (float) $e->debit,
                            'credit'               => (float) $e->credit,
                            'old_account_code'     => $e->old_account_code,
                            'old_account_intitule' => $e->old_account_intitule,
                        ])
                        ->values()
                        ->toArray(),
                ];
            })->toArray();

            // ── 7. Stats globales (sur TOUS les comptes filtrés, pas seulement la page) ──
            $globalStats = (clone $baseQuery)
                ->select([
                    DB::raw('COUNT(DISTINCT na.code) as total_comptes'),
                    DB::raw('COUNT(gl.id)            as total_ecritures'),
                    DB::raw('SUM(gl.debit)           as total_debit'),
                    DB::raw('SUM(gl.credit)          as total_credit'),
                ])
                ->first();

            $soldeGlobal = (float) $globalStats->total_debit - (float) $globalStats->total_credit;

            $stats = [
                'total_comptes'       => (int)   $globalStats->total_comptes,
                'total_ecritures'     => (int)   $globalStats->total_ecritures,
                'total_debit'         => (float) $globalStats->total_debit,
                'total_credit'        => (float) $globalStats->total_credit,
                'solde_global'        => $soldeGlobal,
                'solde_global_absolu' => abs($soldeGlobal),
                'is_debiteur'         => $soldeGlobal > 0,
                'is_crediteur'        => $soldeGlobal < 0,
                'is_equilibre'        => $soldeGlobal == 0,
            ];

            return $this->success('Grand Livre Général.', [
                'comptes'       => $comptes,
                'stats'         => $stats,
                'total_comptes' => $totalComptes,
                'current_page'  => $page,
                'last_page'     => $lastPage,
                'per_page'      => $perPage,
                'has_more'      => $hasMore,
            ]);

        } catch (\Throwable $e) {
            Log::error('GrandLivreController::general', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return $this->serverError($e->getMessage());
        }
    }

// ─── Méthode privée utilitaire (peut être supprimée si non utilisée ailleurs) ──
    private function buildGeneralStats(array $data, int $totalComptes): array
    {
        $soldeGlobal = 0;
        return [
            'total_comptes'       => $totalComptes,
            'total_ecritures'     => 0,
            'total_debit'         => 0,
            'total_credit'        => 0,
            'solde_global'        => 0,
            'solde_global_absolu' => 0,
            'is_debiteur'         => false,
            'is_crediteur'        => false,
            'is_equilibre'        => true,
        ];
    }


    public function generalExportExcel(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $entreprise   = auth()->user()->entreprise;

            if (!$exo) {
                return $this->error('Aucun exercice actif trouvé.');
            }

            // ── 1. Requête de base (identique à general()) ────────────────────
            $baseQuery = DB::table('grand_livres as gl')
                ->leftJoin('account_mappings as am', function ($join) use ($entrepriseId) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                        ->where('am.entreprise_id', $entrepriseId);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
                ->where('gl.entreprise_id', $entrepriseId)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id');

            // ── 2. Filtres ─────────────────────────────────────────────────────
            if ($request->filled('date_debut') && $request->filled('date_fin')) {
                $baseQuery->whereBetween('gl.date_ecriture', [$request->date_debut, $request->date_fin]);
            }
            if ($request->filled('journal_code')) {
                $baseQuery->where('gl.journal_code', $request->journal_code);
            }
            if ($request->filled('search')) {
                $s = $request->search;
                $baseQuery->where(function ($q) use ($s) {
                    $q->where('gl.libelle',    'like', "%{$s}%")
                        ->orWhere('gl.piece',    'like', "%{$s}%")
                        ->orWhere('oa.code',     'like', "%{$s}%")
                        ->orWhere('na.code',     'like', "%{$s}%");
                });
            }
            $selectedAccounts = $request->get('selected_accounts', []);
            if (!empty($selectedAccounts)) {
                $baseQuery->whereIn('na.code', $selectedAccounts);
            }

            // ── 3. Récupérer tous les comptes (sans pagination pour l'export) ──
            $accounts = (clone $baseQuery)
                ->select([
                    'na.code as new_account_code',
                    'na.intitule as new_account_intitule',
                    DB::raw('SUM(gl.debit)  as total_debit'),
                    DB::raw('SUM(gl.credit) as total_credit'),
                    DB::raw('COUNT(gl.id)   as nombre_ecritures'),
                ])
                ->groupBy('na.code', 'na.intitule')
                ->orderBy('na.code')
                ->get();

            if ($accounts->isEmpty()) {
                return $this->error('Aucune donnée à exporter.');
            }

            // ── 4. Récupérer toutes les écritures groupées par compte ──────────
            $accountCodes = $accounts->pluck('new_account_code')->toArray();

            $allEcritures = (clone $baseQuery)
                ->whereIn('na.code', $accountCodes)
                ->select([
                    'gl.date_ecriture',
                    'gl.piece',
                    'gl.journal_code',
                    'gl.libelle',
                    'gl.debit',
                    'gl.credit',
                    'na.code as new_account_code',
                ])
                ->orderBy('na.code')
                ->orderBy('gl.date_ecriture')
                ->orderBy('gl.id')
                ->get()
                ->groupBy('new_account_code');

            // ── 5. Construire les données (même format que Livewire) ───────────
            $data = $accounts->map(function ($account) use ($allEcritures) {
                $ecritures = $allEcritures->get($account->new_account_code, collect());
                return [
                    'code'             => $account->new_account_code,
                    'intitule'         => $account->new_account_intitule,
                    'total_debit'      => (float) $account->total_debit,
                    'total_credit'     => (float) $account->total_credit,
                    'nombre_ecritures' => (int)   $account->nombre_ecritures,
                    'ecritures'        => $ecritures->map(fn ($e) => [
                        'date'    => $e->date_ecriture
                            ? \Carbon\Carbon::parse($e->date_ecriture)->format('d/m/Y')
                            : '',
                        'piece'   => $e->piece        ?? '',
                        'journal' => $e->journal_code  ?? '',
                        'libelle' => $e->libelle       ?? '',
                        'debit'   => (float) $e->debit,
                        'credit'  => (float) $e->credit,
                    ])->toArray(),
                ];
            })->toArray();

            // ── 6. Génération Excel (identique au Livewire) ────────────────────
            $fileName    = 'grand_livre_general_' . $entreprise->code . '_' . date('Ymd_His') . '.xlsx';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Grand Livre Général');

            // En-tête document
            $sheet->setCellValue('A1', 'GRAND LIVRE GÉNÉRAL');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

            $sheet->setCellValue('A2', $entreprise->nom . ' (' . $entreprise->code . ')');
            $sheet->mergeCells('A2:F2');
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

            $debut = $request->filled('date_debut')
                ? \Carbon\Carbon::parse($request->date_debut)->format('d/m/Y') : '';
            $fin   = $request->filled('date_fin')
                ? \Carbon\Carbon::parse($request->date_fin)->format('d/m/Y')   : '';
            $sheet->setCellValue('A3', "Période : $debut au $fin");
            $sheet->mergeCells('A3:F3');
            $sheet->getStyle('A3')->getAlignment()->setHorizontal(
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            );

            $row = 5;
            foreach ($data as $account) {
                // ── Titre du compte ──
                $sheet->setCellValue('A' . $row, 'COMPTE ' . $account['code'] . ' — ' . $account['intitule']);
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D0D8E4');
                $row++;

                // ── En-tête colonnes ──
                foreach (['A' => 'Date', 'B' => 'Pièce', 'C' => 'Journal', 'D' => 'Libellé', 'E' => 'Débit', 'F' => 'Crédit'] as $col => $label) {
                    $sheet->setCellValue($col . $row, $label);
                }
                $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8ECF0');
                $sheet->getStyle("E{$row}:F{$row}")->getAlignment()->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT
                );
                $row++;

                // ── Écritures ──
                foreach ($account['ecritures'] as $e) {
                    $sheet->setCellValue('A' . $row, $e['date']);
                    $sheet->setCellValue('B' . $row, $e['piece']);
                    $sheet->setCellValue('C' . $row, $e['journal']);
                    $sheet->setCellValue('D' . $row, $e['libelle']);
                    if ($e['debit']  > 0) $sheet->setCellValue('E' . $row, $e['debit']);
                    if ($e['credit'] > 0) $sheet->setCellValue('F' . $row, $e['credit']);
                    $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    $row++;
                }

                // ── Total compte ──
                $sheet->setCellValue("D{$row}", 'TOTAL ' . $account['code']);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->setCellValue("E{$row}", $account['total_debit']);
                $sheet->setCellValue("F{$row}", $account['total_credit']);
                $sheet->getStyle("E{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("D{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8ECF0');
                $row++;

                // ── Solde compte ──
                $solde = $account['total_debit'] - $account['total_credit'];
                $sheet->setCellValue("D{$row}", 'SOLDE ' . $account['code']);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                if ($solde > 0) {
                    $sheet->setCellValue("E{$row}", abs($solde));
                    $sheet->getStyle("E{$row}")->getFont()->setBold(true)->getColor()->setRGB('CC0000');
                } else {
                    $sheet->setCellValue("F{$row}", abs($solde));
                    $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('006600');
                }
                $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $row += 2; // Ligne vide entre comptes
            }

            // ── Totaux généraux ──
            $totalDebit  = array_sum(array_column($data, 'total_debit'));
            $totalCredit = array_sum(array_column($data, 'total_credit'));
            $soldeGlobal = $totalDebit - $totalCredit;

            $sheet->setCellValue("D{$row}", 'TOTAL GÉNÉRAL');
            $sheet->setCellValue("E{$row}", $totalDebit);
            $sheet->setCellValue("F{$row}", $totalCredit);
            $sheet->getStyle("D{$row}:F{$row}")->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle("D{$row}:F{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('2C3E50');
            $sheet->getStyle("D{$row}:F{$row}")->getFont()->getColor()->setRGB('FFFFFF');
            $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
            $row++;

            $sheet->setCellValue("D{$row}", 'SOLDE GLOBAL');
            $isDebiteur = $soldeGlobal > 0;
            if ($isDebiteur) {
                $sheet->setCellValue("E{$row}", abs($soldeGlobal));
                $sheet->getStyle("E{$row}")->getFont()->setBold(true)->getColor()->setRGB('CC0000');
            } else {
                $sheet->setCellValue("F{$row}", abs($soldeGlobal));
                $sheet->getStyle("F{$row}")->getFont()->setBold(true)->getColor()->setRGB('006600');
            }
            $sheet->getStyle("D{$row}")->getFont()->setBold(true);
            $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');

            // ── Mise en forme finale ──
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Figer la première ligne de données
            $sheet->freezePane('A5');

            // ── Sauvegarde et téléchargement ──────────────────────────────────
            $tempFile = tempnam(sys_get_temp_dir(), 'gl_general_');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempFile);

            return response()->download($tempFile, $fileName, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])->deleteFileAfterSend(true);

        } catch (\Throwable $e) {
            Log::error('GrandLivreController::generalExportExcel', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->serverError($e->getMessage());
        }
    }

}
