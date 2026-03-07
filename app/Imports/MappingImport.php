<?php
// app/Imports/MappingImport.php

namespace App\Imports;

use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MappingImport implements ToModel, WithHeadingRow, WithStartRow, SkipsOnError
{
    use SkipsErrors;

    protected $entrepriseId;
    protected $importErrors = [];
    protected $importSuccess = [];
    protected $allMessages = [];
    protected $count = 0;
    protected $processed = [];
    protected $exerciceId = null;

    public function __construct($entrepriseId)
    {
        $this->entrepriseId = $entrepriseId;
        $this->getExerciceId();
    }

    public function startRow(): int
    {
        return 2;
    }

    private function getExerciceId()
    {
        if ($this->exerciceId === null) {
            $exo = DB::table('exercices')->where('statut', 1)->first();
            $this->exerciceId = $exo->id ?? null;
        }
        return $this->exerciceId;
    }

    public function model(array $row)
    {
        $lineNumber = $this->getCurrentLine();
        $exoId = $this->getExerciceId();
        
        if (!$exoId) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Aucun exercice actif trouvé";
            return null;
        }

        $oldCode = trim($row['old_code'] ?? '');
        $newCode = trim($row['new_code'] ?? '');

        // Validation des champs
        if (empty($oldCode) || empty($newCode)) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Les codes old et new sont obligatoires";
            return null;
        }

        // Vérifier les doublons dans le fichier
        $key = $oldCode . '|' . $newCode;
        if (isset($this->processed[$key])) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Mapping en double dans le fichier (old: $oldCode, new: $newCode)";
            return null;
        }
        $this->processed[$key] = true;

        // Rechercher les comptes
        $old = OldAccount::where('entreprise_id', $this->entrepriseId)
            ->where('exercice_id', $exoId)
            ->where('code', $oldCode)
            ->first();

        $new = NewAccount::where('entreprise_id', $this->entrepriseId)
            ->where('exercice_id', $exoId)
            ->where('code', $newCode)
            ->first();

        if (!$old) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Ancien compte introuvable : $oldCode";
            return null;
        }

        if (!$new) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Nouveau compte introuvable : $newCode";
            return null;
        }

        // Créer ou mettre à jour le mapping
        try {
            AccountMapping::updateOrCreate(
                [
                    'entreprise_id' => $this->entrepriseId,
                    'exercice_id' => $exoId,
                    'old_account_id' => $old->id,
                ],
                [
                    'new_account_id' => $new->id,
                ]
            );

            $this->count++;
            $this->importSuccess[] = "✓ Ligne $lineNumber : Mapping créé : $oldCode → $newCode";
            
            Log::info("Mapping créé", [
                'old' => $oldCode,
                'new' => $newCode,
                'line' => $lineNumber
            ]);

        } catch (\Exception $e) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Erreur lors de la création : " . $e->getMessage();
        }

        return null;
    }

    private function getCurrentLine()
    {
        static $line = 1;
        return ++$line;
    }

    public function getErrors()
    {
        return $this->importErrors;
    }

    public function getSuccess()
    {
        return $this->importSuccess;
    }

    public function getAllMessages()
    {
        return array_merge($this->importSuccess, $this->importErrors);
    }

    public function getCount()
    {
        return $this->count;
    }
}