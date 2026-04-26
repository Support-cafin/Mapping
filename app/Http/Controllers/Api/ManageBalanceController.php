<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\OldBalance;
use App\Models\NewBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ManageBalanceController extends Controller
{
    use ApiResponseTrait;

    private function entrepriseId(): int
    {
        return auth()->user()->entreprise_id;
    }

    private function model(string $type): string
    {
        return $type === 'old' ? OldBalance::class : NewBalance::class;
    }

    private function accountRelation(string $type): string
    {
        return $type === 'old' ? 'oldAccount' : 'newAccount';
    }

    private function accountFk(string $type): string
    {
        return $type === 'old' ? 'old_account_id' : 'new_account_id';
    }

    private function accountTable(string $type): string
    {
        return $type === 'old' ? 'old_accounts' : 'new_accounts';
    }

    // ─── Liste avec filtres ───────────────────────────────────────────────────

    public function index(Request $request, string $type): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide. Utilisez "old" ou "new".');
        }

        try {
            $relation = $this->accountRelation($type);

            $query = $this->model($type)::where('entreprise_id', $this->entrepriseId())
                ->with($relation)
                ->when($request->filled('exercice'), fn($q) => $q->where('exercice', $request->exercice))
                ->when($request->filled('periode'),  fn($q) => $q->where('periode', $request->periode))
                ->when($request->filled('search'),   fn($q) => $q->whereHas($relation, fn($r) =>
                    $r->where('code', 'like', "%{$request->search}%")
                      ->orWhere('intitule', 'like', "%{$request->search}%")
                ))
                ->orderBy('exercice', 'desc')
                ->orderBy('periode', 'desc');

            $perPage  = (int) $request->get('per_page', 20);
            $balances = $query->paginate($perPage);

            return $this->success("Balances {$type}.", $balances);
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
            $fk = $this->accountFk($type);

            $validated = $request->validate([
                $fk       => "required|exists:{$this->accountTable($type)},id",
                'debit'   => 'required|numeric|min:0',
                'credit'  => 'required|numeric|min:0',
                'solde'   => 'required|numeric',
                'periode' => 'required|string|max:20',
                'exercice'=> 'required|integer|min:2000|max:2100',
            ]);

            $balance = $this->model($type)::create([
                'entreprise_id' => $this->entrepriseId(),
                ...$validated,
            ]);

            return $this->success('Balance ajoutée.', $balance->load($this->accountRelation($type)), 201);
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
            $balance = $this->model($type)::where('entreprise_id', $this->entrepriseId())->find($id);

            if (!$balance) {
                return $this->notFound('Balance introuvable.');
            }

            $fk = $this->accountFk($type);

            $validated = $request->validate([
                $fk       => "required|exists:{$this->accountTable($type)},id",
                'debit'   => 'required|numeric|min:0',
                'credit'  => 'required|numeric|min:0',
                'solde'   => 'required|numeric',
                'periode' => 'required|string|max:20',
                'exercice'=> 'required|integer|min:2000|max:2100',
            ]);

            $balance->update($validated);

            return $this->success('Balance mise à jour.', $balance->load($this->accountRelation($type)));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Supprimer un enregistrement ─────────────────────────────────────────

    public function destroy(string $type, int $id): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $balance = $this->model($type)::where('entreprise_id', $this->entrepriseId())->find($id);

            if (!$balance) {
                return $this->notFound('Balance introuvable.');
            }

            $balance->delete();

            return $this->success('Balance supprimée.');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Supprimer toutes les balances d'un type ──────────────────────────────

    public function destroyAll(string $type): JsonResponse
    {
        if (!in_array($type, ['old', 'new'])) {
            return $this->error('Type invalide.');
        }

        try {
            $deleted = $this->model($type)::where('entreprise_id', $this->entrepriseId())->delete();

            return $this->success("{$deleted} balance(s) supprimée(s).");
        } catch (\Throwable $e) {
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
                ? new \App\Imports\OldBalancesImport($this->entrepriseId())
                : new \App\Imports\NewBalancesImport($this->entrepriseId());

            Excel::import($importClass, $request->file('file')->getRealPath());

            return $this->success('Import terminé.', [
                'imported' => $importClass->getImportedCount(),
                'errors'   => $importClass->getErrors(),
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

        $filename = "template_{$type}_balances.xlsx";
        $filepath = storage_path('app/templates/' . $filename);

        if (!file_exists($filepath)) {
            $dir = storage_path('app/templates');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setCellValue('A1', 'numero_compte');
            $sheet->setCellValue('B1', 'debit');
            $sheet->setCellValue('C1', 'credit');
            $sheet->setCellValue('D1', 'solde');
            $sheet->setCellValue('E1', 'periode');
            $sheet->setCellValue('F1', 'exercice');
            $sheet->setCellValue('A2', $type === 'old' ? '411100' : '4111');
            $sheet->setCellValue('B2', '1000.00');
            $sheet->setCellValue('C2', '0.00');
            $sheet->setCellValue('D2', '1000.00');
            $sheet->setCellValue('E2', '01');
            $sheet->setCellValue('F2', date('Y'));

            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($filepath);
        }

        return response()->download($filepath);
    }
}
