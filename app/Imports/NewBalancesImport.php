<?php

namespace App\Imports;

use App\Models\NewBalance;
use App\Models\NewAccount;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;

class NewBalancesImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private $entrepriseId;
    private $importedCount = 0;
    private $errors = [];

    public function __construct($entrepriseId)
    {
        $this->entrepriseId = $entrepriseId;
    }

    public function model(array $row)
    {
        $row = array_change_key_case($row, CASE_LOWER);
        
        $account = NewAccount::where('entreprise_id', $this->entrepriseId)
            ->where('code', $row['numero_compte'] ?? $row['numéro_compte'] ?? $row['compte'] ?? null)
            ->first();

        if (!$account) {
            $this->errors[] = "Compte non trouvé: " . ($row['numero_compte'] ?? 'N/A');
            return null;
        }

        $this->importedCount++;

        return new NewBalance([
            'entreprise_id' => $this->entrepriseId,
            'new_account_id' => $account->id,
            'debit' => $row['debit'] ?? $row['débit'] ?? 0,
            'credit' => $row['credit'] ?? $row['crédit'] ?? 0,
            'solde' => $row['solde'] ?? 0,
            'periode' => $row['periode'] ?? $row['période'] ?? date('m'),
            'exercice' => $row['exercice'] ?? date('Y'),
        ]);
    }

    public function rules(): array
    {
        return [
            'numero_compte' => 'required|string',
            'debit' => 'required|numeric|min:0',
            'credit' => 'required|numeric|min:0',
            'solde' => 'required|numeric',
            'periode' => 'required|string|max:20',
            'exercice' => 'required|integer|min:2000|max:2100',
        ];
    }

    public function batchSize(): int
    {
        return 1000;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}