<?php

namespace App\Services;

use App\Models\Entreprise;
use App\Models\Journal;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\GrandLivre;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\IOFactory;

class GrandLivreImportService
{
    protected $entreprise;
    protected $errors = [];
    protected $successCount = 0;
    protected $exercice;
    
    public function __construct(Entreprise $entreprise, int $exercice = null)
    {
        $this->entreprise = $entreprise;
        $this->exercice = $exercice ?? date('Y');
    }
    
    public function import(UploadedFile $file, array $options = []): array
    {
        DB::beginTransaction();
        
        try {
            $spreadsheet = IOFactory::load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();
            
            // En-têtes attendues (flexible)
            $headers = $rows[0] ?? [];
            
            $imported = $this->processRows(array_slice($rows, 1), $headers, $options);
            
            DB::commit();
            
            return [
                'success' => true,
                'imported' => $this->successCount,
                'errors' => $this->errors,
                'total' => count($rows) - 1,
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Grand Livre Import Error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'errors' => $this->errors,
                'imported' => $this->successCount,
            ];
        }
    }
    
    protected function processRows(array $rows, array $headers, array $options): void
    {
        $journauxCache = [];
        $oldAccountsCache = [];
        $newAccountsCache = [];
        
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 pour l'en-tête Excel
            
            try {
                // Normaliser la ligne (même nombre de colonnes que les headers)
                $data = array_pad($row, count($headers), null);
                
                // Associer les valeurs aux headers
                $rowData = array_combine($headers, $data);
                
                // Traiter la ligne
                $processed = $this->processRow($rowData, $rowNumber, [
                    'journauxCache' => $journauxCache,
                    'oldAccountsCache' => $oldAccountsCache,
                    'newAccountsCache' => $newAccountsCache,
                ], $options);
                
                if ($processed) {
                    $this->successCount++;
                }
                
            } catch (\Exception $e) {
                $this->errors[] = [
                    'row' => $rowNumber,
                    'error' => $e->getMessage(),
                    'data' => $row,
                ];
            }
        }
    }
    
    protected function processRow(array $rowData, int $rowNumber, array $caches, array $options): bool
    {
        // Valider les données minimales
        if (empty($rowData['Date']) || empty($rowData['Journal']) || empty($rowData['Compte'])) {
            throw new \Exception('Données manquantes (Date, Journal ou Compte)');
        }
        
        // Convertir la date
        $dateEcriture = $this->parseDate($rowData['Date']);
        if (!$dateEcriture) {
            throw new \Exception('Date invalide: ' . $rowData['Date']);
        }
        
        // Trouver ou créer le journal
        $journalCode = trim($rowData['Journal']);
        if (!isset($caches['journauxCache'][$journalCode])) {
            $journal = Journal::where('entreprise_id', $this->entreprise->id)
                ->where('code', $journalCode)
                ->first();
            
            if (!$journal) {
                // Créer le journal automatiquement
                $journal = Journal::create([
                    'entreprise_id' => $this->entreprise->id,
                    'code' => $journalCode,
                    'intitule' => 'Journal ' . $journalCode,
                    'type' => 'Divers',
                    'actif' => true,
                ]);
            }
            
            $caches['journauxCache'][$journalCode] = $journal->id;
        }
        
        // Trouver le compte (ancien ou nouveau)
        $compteCode = trim($rowData['Compte']);
        $oldAccountId = null;
        $newAccountId = null;
        
        // Chercher d'abord dans les anciens comptes
        if (!isset($caches['oldAccountsCache'][$compteCode])) {
            $oldAccount = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->where('code', $compteCode)
                ->first();
            
            if ($oldAccount) {
                $caches['oldAccountsCache'][$compteCode] = $oldAccount->id;
                $oldAccountId = $oldAccount->id;
                
                // Trouver le compte mappé correspondant
                $mapping = $oldAccount->mappings()->first();
                if ($mapping && $mapping->newAccount) {
                    $newAccountId = $mapping->newAccount->id;
                    $caches['newAccountsCache'][$compteCode] = $newAccountId;
                }
            }
        } else {
            $oldAccountId = $caches['oldAccountsCache'][$compteCode];
            $newAccountId = $caches['newAccountsCache'][$compteCode] ?? null;
        }
        
        // Si pas trouvé dans anciens, chercher dans nouveaux
        if (!$oldAccountId && !isset($caches['newAccountsCache'][$compteCode])) {
            $newAccount = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where('code', $compteCode)
                ->first();
            
            if ($newAccount) {
                $caches['newAccountsCache'][$compteCode] = $newAccount->id;
                $newAccountId = $newAccount->id;
            }
        } elseif (!$oldAccountId && isset($caches['newAccountsCache'][$compteCode])) {
            $newAccountId = $caches['newAccountsCache'][$compteCode];
        }
        
        // Convertir débit et crédit
        $debit = $this->parseMontant($rowData['Débit'] ?? 0);
        $credit = $this->parseMontant($rowData['Crédit'] ?? 0);
        
        // S'assurer qu'on a soit débit, soit crédit, mais pas les deux
        if ($debit > 0 && $credit > 0) {
            throw new \Exception('Débit et crédit simultanés non autorisés');
        }
        
        // Créer l'écriture
        GrandLivre::create([
            'entreprise_id' => $this->entreprise->id,
            'journal_id' => $caches['journauxCache'][$journalCode],
            'old_account_id' => $oldAccountId,
            'new_account_id' => $newAccountId,
            'date_ecriture' => $dateEcriture,
            'piece' => $rowData['Pièce'] ?? null,
            'libelle' => $rowData['Libellé'] ?? '',
            'debit' => $debit,
            'credit' => $credit,
            'exercice' => $this->exercice,
            'mois' => $dateEcriture->month,
            'trimestre' => ceil($dateEcriture->month / 3),
            'source' => 'import',
            'validated' => $options['validate'] ?? true,
            'notes' => 'Importé depuis Excel - Ligne ' . $rowNumber,
        ]);
        
        return true;
    }
    
    protected function parseDate($date)
    {
        if (is_numeric($date)) {
            // Date Excel (nombre de jours depuis 1900)
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
        }
        
        try {
            return new \DateTime($date);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    protected function parseMontant($value): float
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        
        // Nettoyer le format
        $value = str_replace([' ', ',', '€', 'F', 'CFA'], '', $value);
        $value = str_replace(',', '.', $value);
        
        return (float) $value;
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    public function getSuccessCount(): int
    {
        return $this->successCount;
    }
}