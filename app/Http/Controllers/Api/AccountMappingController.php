<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\GrandLivre;
use App\Traits\AutoUpdatesGrandLivre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AccountMappingController extends Controller
{
    use ApiResponseTrait, AutoUpdatesGrandLivre;

    // ─── Helpers privés ───────────────────────────────────────────────────────

    private function exerciceActif(): ?object
    {
        return DB::table('exercices')->where('statut', 1)->first();
    }

    private function entrepriseId(): int
    {
        return auth()->user()->entreprise_id;
    }

    // ─── Plan comptable (arbres) ───────────────────────────────────────────────

    /**
     * Arbre des anciens comptes (SYSCOA)
     */
    public function oldAccountsTree(Request $request): JsonResponse
    {
        try {
            $exo    = $this->exerciceActif();
            $search = $request->get('search', '');

            $query = OldAccount::where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->whereNull('parent_id')
                ->with('childrenRecursive')
                ->orderBy('code');

            $roots = $query->get();

            if ($search) {
                $roots = $this->filterTree($roots, $search);
            }

            return $this->success('Arbre des anciens comptes.', $roots);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Arbre des nouveaux comptes (SYCEBNL)
     */
    public function newAccountsTree(Request $request): JsonResponse
    {
        try {
            $exo    = $this->exerciceActif();
            $search = $request->get('search', '');

            $roots = NewAccount::where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->whereNull('parent_id')
                ->with('childrenRecursive')
                ->orderBy('code')
                ->get();

            if ($search) {
                $roots = $this->filterTree($roots, $search);
            }

            return $this->success('Arbre des nouveaux comptes.', $roots);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Mappings ─────────────────────────────────────────────────────────────

    /**
     * Liste des mappings avec recherche
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $exo = $this->exerciceActif();

            $mappings = AccountMapping::with(['oldAccount', 'newAccount'])
                ->where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->when($request->filled('search_old'), function ($q) use ($request) {
                    $q->whereHas('oldAccount', function ($q2) use ($request) {
                        $q2->where('code', 'like', "%{$request->search_old}%")
                            ->orWhere('intitule', 'like', "%{$request->search_old}%");
                    });
                })
                ->when($request->filled('search_new'), function ($q) use ($request) {
                    $q->whereHas('newAccount', function ($q2) use ($request) {
                        $q2->where('code', 'like', "%{$request->search_new}%")
                            ->orWhere('intitule', 'like', "%{$request->search_new}%");
                    });
                })
                ->orderBy('old_account_id')
                ->get();

            return $this->success('Liste des mappings.', [
                'mappings' => $mappings,
                'total'    => $mappings->count(),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Détail d'un mapping
     */
    public function show(int $id): JsonResponse
    {
        try {
            $mapping = AccountMapping::with(['oldAccount', 'newAccount'])
                ->where('entreprise_id', $this->entrepriseId())
                ->find($id);

            if (!$mapping) {
                return $this->notFound('Mapping introuvable.');
            }

            return $this->success('Détail du mapping.', $mapping);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Créer un mapping
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'old_account_id' => 'required|exists:old_accounts,id',
                'new_account_id' => 'required|exists:new_accounts,id',
                'commentaire'    => 'nullable|string|max:500',
            ]);

            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            // Vérifier doublon
            $exists = AccountMapping::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->where('old_account_id', $validated['old_account_id'])
                ->exists();

            if ($exists) {
                return $this->error('Un mapping existe déjà pour cet ancien compte.');
            }

            DB::beginTransaction();

            $mapping = AccountMapping::create([
                'entreprise_id'  => $entrepriseId,
                'exercice_id'    => $exo->id ?? '',
                'old_account_id' => $validated['old_account_id'],
                'new_account_id' => $validated['new_account_id'],
                'commentaire'    => $validated['commentaire'] ?? null,
            ]);

            // Mettre à jour le Grand Livre
            $updatedCount = $this->updateGrandLivreForOldAccount(
                $validated['old_account_id'],
                $entrepriseId,
                $exo->id ?? '',
                $validated['new_account_id']
            );

            DB::commit();

            return $this->success(
                "Mapping créé. {$updatedCount} écriture(s) mise(s) à jour dans le Grand Livre.",
                $mapping->load(['oldAccount', 'newAccount']),
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
     * Modifier un mapping
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $mapping = AccountMapping::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$mapping) {
                return $this->notFound('Mapping introuvable.');
            }

            $validated = $request->validate([
                'old_account_id' => 'required|exists:old_accounts,id',
                'new_account_id' => 'required|exists:new_accounts,id',
                'commentaire'    => 'nullable|string|max:500',
            ]);

            $oldOldAccountId = $mapping->old_account_id;

            DB::beginTransaction();

            $mapping->update($validated);

            // Si l'ancien compte a changé, réinitialiser les anciennes écritures
            if ($oldOldAccountId != $validated['old_account_id']) {
                $this->updateGrandLivreForOldAccount(
                    $oldOldAccountId,
                    $entrepriseId,
                    $exo->id ?? '',
                    null
                );
            }

            // Mettre à jour les nouvelles écritures
            $updatedCount = $this->updateGrandLivreForOldAccount(
                $validated['old_account_id'],
                $entrepriseId,
                $exo->id ?? '',
                $validated['new_account_id']
            );

            DB::commit();

            return $this->success(
                "Mapping mis à jour. {$updatedCount} écriture(s) mise(s) à jour.",
                $mapping->load(['oldAccount', 'newAccount'])
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer un mapping
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $mapping = AccountMapping::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$mapping) {
                return $this->notFound('Mapping introuvable.');
            }

            DB::beginTransaction();

            // Réinitialiser le grand livre
            $this->updateGrandLivreForOldAccount(
                $mapping->old_account_id,
                $entrepriseId,
                $exo->id ?? '',
                null
            );

            $mapping->delete();

            DB::commit();

            Log::info('Mapping supprimé', ['id' => $id, 'entreprise_id' => $entrepriseId]);

            return $this->success('Mapping supprimé. Grand livre réinitialisé.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Erreur suppression mapping', ['id' => $id, 'error' => $e->getMessage()]);
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Supprimer TOUS les mappings de l'entreprise (exercice actif)
     */
    public function destroyAll(): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $mappings = AccountMapping::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->get();

            $count = $mappings->count();

            if ($count === 0) {
                return $this->error('Aucun mapping à supprimer.');
            }

            DB::beginTransaction();

            foreach ($mappings as $mapping) {
                $this->updateGrandLivreForOldAccount(
                    $mapping->old_account_id,
                    $entrepriseId,
                    $exo->id ?? '',
                    null
                );
                $mapping->delete();
            }

            DB::commit();

            return $this->success("Tous les mappings ({$count}) ont été supprimés. Le Grand Livre a été réinitialisé.");
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Synchroniser tous les mappings avec le Grand Livre
     */
    public function syncAll(): JsonResponse
    {
        try {
            $updatedCount = $this->syncAllGrandLivreMappings($this->entrepriseId());

            return $this->success("Synchronisation terminée. {$updatedCount} écriture(s) mise(s) à jour.");
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * Télécharger le template XLSX d'import
     */
    public function downloadTemplate()
    {
        $filename = 'template_mappings1.xlsx';
        $path     = storage_path('app/templates/' . $filename);

        if (!file_exists($path)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'old_code');
            $sheet->setCellValue('B1', 'new_code');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($path);
        }

        return response()->download($path);
    }

    /**
     * Importer des mappings depuis un fichier XLSX/CSV
     */
    public function import(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);

            $import = new \App\Imports\MappingImport($this->entrepriseId());
            Excel::import($import, $request->file('file')->getRealPath());

            return $this->success('Import terminé.', [
                'imported'     => $import->getCount(),
                'errors'       => $import->getErrors(),
                'success'      => $import->getSuccess(),
                'all_messages' => $import->getAllMessages(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            Log::error('Import mapping failed', ['error' => $e->getMessage()]);
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Helper privé ─────────────────────────────────────────────────────────

    private function filterTree($nodes, string $search)
    {
        return $nodes->filter(function ($node) use ($search) {
            $match = str_contains(strtolower($node->code), strtolower($search))
                || str_contains(strtolower($node->intitule), strtolower($search));

            $filteredChildren = $this->filterTree($node->children ?? collect(), $search);

            if ($filteredChildren->isNotEmpty()) {
                $node->setRelation('children', $filteredChildren);
                return true;
            }

            return $match;
        });
    }
}
