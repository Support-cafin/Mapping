<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
    use ApiResponseTrait;

    private function exerciceActif(): ?object
    {
        return DB::table('exercices')->where('statut', 1)->first();
    }

    private function entrepriseId(): int
    {
        return auth()->user()->entreprise_id;
    }

    private function model(string $type): string
    {
        return $type === 'old' ? OldAccount::class : NewAccount::class;
    }

    private function table(string $type): string
    {
        return $type === 'old' ? 'old_accounts' : 'new_accounts';
    }

    // ─── Liste flat avec search ───────────────────────────────────────────────

    public function index(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide. Utilisez "old" ou "new".');
        }

        try {
            $exo    = $this->exerciceActif();
            $search = $request->get('search', '');

            $query = $this->model($type)::where('entreprise_id', $this->entrepriseId())
                ->where('exercice_id', $exo->id ?? '')
                ->when($search, fn($q) => $q->where(
                    fn($i) => $i->where('code', 'like', "%{$search}%")
                        ->orWhere('intitule', 'like', "%{$search}%")
                ))
                ->orderByRaw('LENGTH(code), code');

            $perPage = $request->get('per_page', 50);
            $accounts = $perPage === 'all' ? $query->get() : $query->paginate((int) $perPage);

            return $this->success("Comptes {$type}.", $accounts);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Détail ───────────────────────────────────────────────────────────────

    public function show(string $type, int $id): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $account = $this->model($type)::where('entreprise_id', $this->entrepriseId())
                ->with(['parent', 'children'])
                ->find($id);

            if (!$account) {
                return $this->notFound('Compte introuvable.');
            }

            return $this->success('Détail du compte.', $account);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Créer ────────────────────────────────────────────────────────────────

    public function store(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $table        = $this->table($type);

            $validated = $request->validate([
                'code'      => ['required', 'string', 'max:20',
                    Rule::unique($table, 'code')
                        ->where('entreprise_id', $entrepriseId)
                        ->where('exercice_id', $exo->id ?? ''),
                ],
                'intitule'  => 'required|string|max:255',
                'classe'    => 'nullable|string|max:10',
                'groupe'    => 'nullable|string|max:10',
                'parent_id' => ['nullable', Rule::exists($table, 'id')->where('entreprise_id', $entrepriseId)],
            ]);

            $account = $this->model($type)::create([
                'entreprise_id' => $entrepriseId,
                'exercice_id'   => $exo->id ?? '',
                ...$validated,
            ]);

            return $this->success('Compte créé.', $account, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Modifier ─────────────────────────────────────────────────────────────

    public function update(Request $request, string $type, int $id): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $table        = $this->table($type);

            $account = $this->model($type)::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->find($id);

            if (!$account) {
                return $this->notFound('Compte introuvable.');
            }

            $isMapped = $account->mappings()->exists();

            if ($isMapped) {
                // Compte mappé : seulement l'intitulé est modifiable
                $validated = $request->validate(['intitule' => 'required|string|max:255']);
                $account->update(['intitule' => $validated['intitule']]);
                return $this->success('Libellé mis à jour (compte mappé).', $account);
            }

            $validated = $request->validate([
                'code'      => ['required', 'string', 'max:20',
                    Rule::unique($table, 'code')
                        ->where('entreprise_id', $entrepriseId)
                        ->where('exercice_id', $exo->id ?? '')
                        ->ignore($id),
                ],
                'intitule'  => 'required|string|max:255',
                'classe'    => 'nullable|string|max:10',
                'groupe'    => 'nullable|string|max:10',
                'parent_id' => ['nullable', Rule::exists($table, 'id')->where('entreprise_id', $entrepriseId)],
            ]);

            $account->update($validated);

            return $this->success('Compte mis à jour.', $account);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Supprimer un compte ──────────────────────────────────────────────────

    public function destroy(string $type, int $id): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $account = $this->model($type)::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->with(['children', 'mappings'])
                ->find($id);

            if (!$account) {
                return $this->notFound('Compte introuvable.');
            }

            if ($account->mappings->isNotEmpty()) {
                return $this->error('Impossible de supprimer un compte mappé.');
            }

            if ($account->children->isNotEmpty()) {
                return $this->error('Impossible de supprimer un compte ayant des sous-comptes.');
            }

            if ($type === 'old' && method_exists($account, 'hasData') && $account->hasData()) {
                return $this->error('Impossible de supprimer un compte contenant des données dans le Grand Livre.');
            }

            $account->delete();

            return $this->success('Compte supprimé.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Supprimer tous (non mappés, sans enfants, sans données) ─────────────

    public function destroyAll(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $force        = $request->boolean('force', false);

            DB::beginTransaction();

            if ($force) {
                // Suppression forcée : supprimer les mappings liés, puis tous les comptes
                AccountMapping::where('entreprise_id', $entrepriseId)
                    ->where('exercice_id', $exo->id ?? '')
                    ->whereIn(
                        $type === 'old' ? 'old_account_id' : 'new_account_id',
                        fn($q) => $q->select('id')->from($this->table($type))
                            ->where('entreprise_id', $entrepriseId)
                            ->where('exercice_id', $exo->id ?? '')
                    )
                    ->delete();

                $deleted = $this->model($type)::where('entreprise_id', $entrepriseId)
                    ->where('exercice_id', $exo->id ?? '')
                    ->delete();

                DB::commit();
                return $this->success("{$deleted} compte(s) supprimé(s) de force.");
            }

            // Suppression douce : seulement les comptes sans mappings, enfants ni données
            $accounts = $this->model($type)::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->whereDoesntHave('mappings')
                ->whereDoesntHave('children')
                ->when($type === 'old', fn($q) => $q->whereDoesntHave('grandLivre'))
                ->get();

            $deleted = 0;
            foreach ($accounts as $account) {
                $account->delete();
                $deleted++;
            }

            DB::commit();

            return $this->success("{$deleted} compte(s) supprimé(s).");
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Import Excel ─────────────────────────────────────────────────────────

    public function import(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            ]);

            $importClass = $type === 'old'
                ? new \App\Imports\OldAccountsImport($this->entrepriseId())
                : new \App\Imports\NewAccountsImport($this->entrepriseId());

            Excel::import($importClass, $request->file('file')->getRealPath());

            return $this->success('Import terminé.', [
                'imported'     => $importClass->getImportedCount(),
                'errors'       => $importClass->getErrors(),
                'all_messages' => $importClass->getAllMessages(),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Télécharger le template ──────────────────────────────────────────────

    public function template(string $type)
    {
        if (!in_array($type, ['old', 'new'])) {
            return response()->json(['message' => 'Type invalide.'], 400);
        }

        $filename = "template_{$type}_accounts.xlsx";
        $filepath = storage_path('app/templates/' . $filename);

        if (!file_exists($filepath)) {
            $dir = storage_path('app/templates');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'code');
            $sheet->setCellValue('B1', 'intitule');
            $sheet->setCellValue('C1', 'parent_code');
            $sheet->setCellValue('A2', '1');
            $sheet->setCellValue('B2', 'CAPITAUX');
            $sheet->setCellValue('A3', '10');
            $sheet->setCellValue('B3', 'Capital');
            $sheet->setCellValue('C3', '1');

            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($filepath);
        }

        return response()->download($filepath);
    }

    // ─── Stats (progression du mapping) ──────────────────────────────────────

    public function stats(): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $total  = OldAccount::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id ?? '')->count();
            $mapped = AccountMapping::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id ?? '')->count();

            return $this->success('Statistiques des comptes.', [
                'total_old'  => $total,
                'total_new'  => NewAccount::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id ?? '')->count(),
                'mapped'     => $mapped,
                'unmapped'   => max(0, $total - $mapped),
                'percentage' => $total > 0 ? round(($mapped / $total) * 100, 2) : 0,
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
