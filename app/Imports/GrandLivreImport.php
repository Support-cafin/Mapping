<?php

namespace App\Imports;

use App\Models\GrandLivre;
use App\Models\OldAccount;
use App\Models\AccountMapping;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class GrandLivreImport implements ToCollection, WithHeadingRow, WithStartRow, WithBatchInserts, WithChunkReading, SkipsOnError, SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private $entrepriseId;
    private $exercice;
    private $autoValidate;
    private $importedCount = 0;
    private $importErrors = [];
    private $importWarnings = [];
    private $oldAccountsCache = [];
    private $mappingsCache = [];
    private $resultFilePath = null;
    private $entrepriseName = null; 
    
    private $exerciceId = null; // ✅ AJOUTÉ
    private $maxRows = 50000;    // ✅ AJOUTÉ
    
    public function __construct($entrepriseId, $exercice, $autoValidate = true, $entrepriseName = null)
    {
        $this->entrepriseId = $entrepriseId;
        $this->exercice = $exercice;
        $this->autoValidate = $autoValidate;
        $this->entrepriseName = $entrepriseName;
        
        $this->loadCaches();
    }
    
    
    private function getExerciceId()
    {
        if ($this->exerciceId === null) {
            $exo = DB::table('exercices')->where('statut', 1)->first();
            $this->exerciceId = $exo->id ?? null;
        }
        return $this->exerciceId;
    }
    

    public function startRow(): int
    {
        return 2;
    }

    private function loadCaches()
    {
        $exoId = $this->getExerciceId();
        $exerc = DB::table('exercices')->where('statut', 1)->first();
        
        if (!$exerc) {
            Log::error('Aucun exercice actif trouvé');
            return;
        }

        // Charger tous les comptes anciens
        $this->oldAccountsCache = OldAccount::where('entreprise_id', $this->entrepriseId)
            ->where('exercice_id', $exerc->id)  // ✅ AJOUTÉ
            ->get()
            ->keyBy('code')
            ->mapWithKeys(function ($account) {
                return [$account->code => ['id' => $account->id]];
            })
            ->toArray();

        // Charger tous les mappings
        $mappings = AccountMapping::where('entreprise_id', $this->entrepriseId)
            ->where('exercice_id', $exerc->id)
            ->with('newAccount')
            ->get();
        
        foreach ($mappings as $mapping) {
            $this->mappingsCache[$mapping->old_account_id] = $mapping->new_account_id;
        }

        Log::info('Caches chargés pour import', [
            'old_accounts' => count($this->oldAccountsCache),
            'mappings' => count($this->mappingsCache)
        ]);
    }

    
    /*public function getResultFilePath()
    {
        return $this->resultFilePath;
    }*/

    public function collection(Collection $rows)
    {
        Log::info('=== DÉBUT IMPORT GRAND LIVRE ===', [
            'rows_count' => $rows->count(),
            'entreprise_id' => $this->entrepriseId,
            'exercice' => $this->exercice
        ]);

        if ($rows->isEmpty()) {
            $this->importErrors[] = "⚠️ Fichier vide. Assurez-vous que :";
            $this->importErrors[] = "  • La ligne 1 contient : date, journal, compte, libelle, piece, debit, credit";
            $this->importErrors[] = "  • Les lignes suivantes contiennent les données";
            Log::warning('Aucune ligne trouvée');
            return;
        }

        // Log structure première ligne
        $firstRow = $rows->first();
        if ($firstRow) {
            Log::info('Structure première ligne', [
                'keys' => array_keys($firstRow->toArray()),
                'sample' => $firstRow->toArray()
            ]);
        }

        // OPTIMISATION : Insertion par lots au lieu de transaction globale
        $batch = [];
        $batchSize = 1000; // Ajustable selon votre serveur
        
        try {
            foreach ($rows as $index => $row) {
                try {
                    $data = $this->prepareRowData($row, $index);
                    
                    if ($data) {
                        $batch[] = $data;
                    }
                    
                    // Insertion par lots
                    if (count($batch) >= $batchSize) {
                        $this->insertBatch($batch);
                        $batch = [];
                    }
                    
                } catch (\Exception $e) {
                    $this->importErrors[] = "❌ Ligne " . ($index + 2) . " : " . $e->getMessage();
                    Log::error("Erreur ligne " . ($index + 2), [
                        'error' => $e->getMessage(),
                        'row' => $row->toArray()
                    ]);
                }
            }
            
            // Insérer le dernier lot
            if (!empty($batch)) {
                $this->insertBatch($batch);
            }
            
            Log::info('=== FIN IMPORT GRAND LIVRE ===', [
                'imported' => $this->importedCount,
                'errors' => count($this->importErrors),
                'warnings' => count($this->importWarnings)
            ]);
        //$this->generateResultFile();
        } catch (\Exception $e) {
            $this->importErrors[] = "❌ Erreur critique : " . $e->getMessage();
            Log::error('Erreur critique import', ['error' => $e->getMessage()]);
        }
    }
    
     /**
     * Génère un fichier PDF avec le résultat de l'import
     */
    /*private function generateResultFile()
    {
        try {
            // Créer le dossier storage/app/imports s'il n'existe pas
            $importDir = storage_path('app/imports');
            if (!file_exists($importDir)) {
                mkdir($importDir, 0755, true);
            }
            
            // Nom du fichier avec timestamp
            $filename = 'import_result_' . date('Y-m-d_His') . '_' . uniqid() . '.pdf';
            $this->resultFilePath = $importDir . '/' . $filename;
            
            // Préparer les données pour le PDF
            $data = [
                'date' => date('d/m/Y H:i:s'),
                'entreprise_id' => $this->entrepriseId,
                'entreprise_nom' => $this->entrepriseName ?? 'N/A',
                'exercice' => $this->exercice,
                'importedCount' => $this->importedCount,
                'errorsCount' => count($this->importErrors),
                'warningsCount' => count($this->importWarnings),
                'errors' => $this->importErrors,
                'warnings' => $this->importWarnings,
                'success' => empty($this->importErrors),
                'total_messages' => count($this->importErrors) + count($this->importWarnings)
            ];
            
            // Générer le PDF
            $pdf = Pdf::loadView('pdf.import-result', $data);
            
            // Sauvegarder le PDF
            $pdf->save($this->resultFilePath);
            
            Log::info('Fichier PDF de résultat généré', ['path' => $this->resultFilePath]);
            
        } catch (\Exception $e) {
            Log::error('Erreur génération fichier PDF résultat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback: créer un fichier texte en cas d'erreur PDF
            $this->generateTextFileFallback();
        }
    }*/
    
    /**
     * Fallback: génère un fichier texte si le PDF échoue
     */
    /*private function generateTextFileFallback()
    {
        try {
            $importDir = storage_path('app/imports');
            $filename = 'import_result_' . date('Y-m-d_His') . '_' . uniqid() . '.txt';
            $this->resultFilePath = $importDir . '/' . $filename;
            
            $content = "=== RÉSULTAT DE L'IMPORTATION ===\n";
            $content .= "Date : " . date('d/m/Y H:i:s') . "\n";
            $content .= "Entreprise ID : " . $this->entrepriseId . "\n";
            $content .= "Exercice : " . $this->exercice . "\n";
            $content .= "Écritures importées : " . $this->importedCount . "\n";
            $content .= "Erreurs : " . count($this->importErrors) . "\n";
            $content .= "Avertissements : " . count($this->importWarnings) . "\n\n";
            
            file_put_contents($this->resultFilePath, $content);
            
        } catch (\Exception $e) {
            Log::error('Erreur fallback texte', ['error' => $e->getMessage()]);
        }
    }*/

    /**
     * Prépare les données d'une ligne sans l'insérer
     */
    private function prepareRowData($row, $index)
    {
        $lineNumber = $index + 2;
        $rowData = $this->normalizeRow($row);
        
        Log::info("Traitement ligne $lineNumber", ['data' => $rowData]);

        // 1. Extraire et valider les données
        $date = $this->parseDate($rowData['date'] ?? null, $lineNumber);
        $journalCode = strtoupper(trim($rowData['journal'] ?? ''));
        $compteCode = trim($rowData['compte'] ?? '');
        $libelle = trim($rowData['libelle'] ?? '');
        $piece = trim($rowData['piece'] ?? '');
        $debit = $this->parseAmount($rowData['debit'] ?? 0);
        $credit = $this->parseAmount($rowData['credit'] ?? 0);

        // Validations
        if (!$date) {
            $this->importErrors[] = "❌ Ligne $lineNumber :  $compteCode  : Date invalide ou manquante";
            return null;
        }

        if (empty($compteCode)) {
            $this->importErrors[] = "❌ Ligne $lineNumber : $compteCode  : Code compte obligatoire";
            return null;
        }

        if ($debit == 0 && $credit == 0) {
            $this->importErrors[] = "❌ Ligne $lineNumber : $compteCode  : Débit ou crédit requis";
            return null;
        }

        if ($debit > 0 && $credit > 0) {
            $this->importErrors[] = "❌ Ligne $lineNumber : $compteCode  : Un seul montant (débit OU crédit)";
            return null;
        }

        // 2. Résoudre le compte ancien
        $oldAccountId = $this->resolveOldAccount($compteCode, $lineNumber);
        
        if (!$oldAccountId) {
            return null;
        }

        // 3. Détecter automatiquement le nouveau compte via mapping
        $newAccountId = $this->mappingsCache[$oldAccountId] ?? null;

        // Warning si pas mappé
        if (!$newAccountId) {
            $this->importWarnings[] = "⚠️ Ligne $lineNumber : Compte '$compteCode' non mappé → nouveau plan introuvable";
        }

        // 4. Calculer les métadonnées
        $mois = $date->month;
        $trimestre = ceil($mois / 3);
        $solde = abs($debit - $credit);

        $exo = DB::table('exercices')->where('statut', 1)->first();
        // 5. Retourner les données préparées (sans insertion)
        return [
            'entreprise_id' => $this->entrepriseId,
            'exercice_id' => $exo->id,
            'journal_code' => $journalCode ?: null,
            'old_account_id' => $oldAccountId,
            'new_account_id' => $newAccountId,
            'date_ecriture' => $date,
            'piece' => $piece ?: null,
            'libelle' => $libelle ?: 'Importation Excel',
            'debit' => $debit,
            'credit' => $credit,
            'solde' => $solde,
            'exercice' => $this->exercice,
            'mois' => $mois,
            'trimestre' => $trimestre,
            'source' => 'import_excel',
            'validated' => $this->autoValidate,
            'lettre' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Insère un lot de données en une seule requête
     */
    private function insertBatch(array $batch)
    {
        if (empty($batch)) {
            return;
        }

        try {
            GrandLivre::insert($batch);
            $this->importedCount += count($batch);
            
            Log::info("Lot inséré", [
                'count' => count($batch),
                'total' => $this->importedCount
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur insertion lot", [
                'error' => $e->getMessage(),
                'batch_size' => count($batch)
            ]);
            
            // Fallback : insertion ligne par ligne pour identifier les problèmes
            foreach ($batch as $data) {
                try {
                    GrandLivre::create($data);
                    $this->importedCount++;
                } catch (\Exception $ex) {
                    $this->importErrors[] = "❌ Erreur insertion : " . $ex->getMessage();
                }
            }
        }
    }

    private function normalizeRow($row)
    {
        $normalized = [];
        
        foreach ($row as $key => $value) {
            $cleanKey = strtolower(trim($key));
            $cleanKey = str_replace([' ', '_', '-', 'é', 'è', 'ê'], ['', '', '', 'e', 'e', 'e'], $cleanKey);
            
            if (in_array($cleanKey, ['date', 'dateecriture', 'dateecrit'])) {
                $normalized['date'] = $value;
            } elseif (in_array($cleanKey, ['journal', 'codejournal', 'jrn'])) {
                $normalized['journal'] = $value;
            } elseif (in_array($cleanKey, ['compte', 'codecompte', 'cpt', 'account'])) {
                $normalized['compte'] = $value;
            } elseif (in_array($cleanKey, ['libelle', 'libele', 'label', 'description', 'lib'])) {
                $normalized['libelle'] = $value;
            } elseif (in_array($cleanKey, ['piece', 'npiece', 'npice', 'numero', 'ref'])) {
                $normalized['piece'] = $value;
            } elseif (in_array($cleanKey, ['debit', 'deb', 'dt'])) {
                $normalized['debit'] = $value;
            } elseif (in_array($cleanKey, ['credit', 'cred', 'cr', 'ct'])) {
                $normalized['credit'] = $value;
            }
        }
        
        return $normalized;
    }

    private function parseDate($value, $lineNumber)
    {
        if (empty($value)) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $unixTimestamp = ($value - 25569) * 86400;
                return Carbon::createFromTimestamp($unixTimestamp);
            }

            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'd.m.Y'];
            
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    if ($date) return $date;
                } catch (\Exception $e) {
                    continue;
                }
            }

            return Carbon::parse($value);

        } catch (\Exception $e) {
            Log::warning("Date invalide ligne $lineNumber", ['value' => $value]);
            return null;
        }
    }

    private function parseAmount($value)
    {
        if (empty($value) || trim($value) === '') {
            return 0;
        }
        
        // Convertir en string
        $value = trim(strval($value));
        
        // Cas spécial : si c'est déjà un nombre (ex: 123.45)
        if (is_numeric($value)) {
            return (float)$value;
        }
        
        // Retirer les espaces, apostrophes, et caractères spéciaux
        $value = str_replace([' ', "'", '`', chr(160)], '', $value);
        
        // Compter les virgules et points
        $commaCount = substr_count($value, ',');
        $dotCount = substr_count($value, '.');
        
        // Cas 1: Un seul séparateur décimal
        if ($commaCount == 1 && $dotCount == 0) {
            // Format français : 1234,56 → virgule comme décimal
            $cleaned = str_replace(',', '.', $value);
            return (float)$cleaned;
        }
        
        if ($dotCount == 1 && $commaCount == 0) {
            // Format simple avec point décimal : 1234.56
            return (float)$value;
        }
        
        // Cas 2: Plusieurs séparateurs (milliers + décimal)
        if ($commaCount > 0 && $dotCount > 0) {
            $lastCommaPos = strrpos($value, ',');
            $lastDotPos = strrpos($value, '.');
            
            if ($lastDotPos > $lastCommaPos) {
                // Point est décimal : 1,234.56
                $cleaned = str_replace(',', '', $value);
                return (float)$cleaned;
            } else {
                // Virgule est décimal : 1.234,56
                $cleaned = str_replace('.', '', $value);
                $cleaned = str_replace(',', '.', $cleaned);
                return (float)$cleaned;
            }
        }
        
        // Cas 3: Aucun séparateur décimal mais des séparateurs de milliers
        if ($commaCount > 1 || $dotCount > 1) {
            $cleaned = str_replace([',', '.'], '', $value);
            return (float)$cleaned;
        }
        
        // Cas 4: Tentative de nettoyage final
        $cleaned = str_replace(',', '.', $value);
        
        if (is_numeric($cleaned)) {
            return (float)$cleaned;
        }
        
        // Dernier recours : extraire les chiffres
        preg_match_all('/[\d.,]+/', $value, $matches);
        if (!empty($matches[0])) {
            $number = $matches[0][0];
            $number = str_replace(',', '.', $number);
            return (float)$number;
        }
        
        Log::warning("Impossible de parser le montant", ['value' => $value]);
        return 0;
    }

    /**
     * OPTIMISÉ : Résolution du compte ancien sans transaction imbriquée
     */
    private function resolveOldAccount($code, $lineNumber)
    {
        $exerc = DB::table('exercices')->where('statut', 1)->first();
        $code = trim($code);

        // Vérifier le cache
        if (isset($this->oldAccountsCache[$code])) {
            return $this->oldAccountsCache[$code]['id'];
        }

        // Vérifier si le compte existe en DB
        $existingAccount = OldAccount::where('code', $code)
            ->where('entreprise_id', $this->entrepriseId)
             ->where('exercice_id', $exerc->id)
            ->first();
            
        if ($existingAccount) {
            $this->oldAccountsCache[$code] = ['id' => $existingAccount->id];
            return $existingAccount->id;
        }

        // Créer le compte automatiquement (SANS transaction imbriquée)
        try {
            $oldAccount = OldAccount::create([
                'code' => $code,
                'entreprise_id' => $this->entrepriseId,
                'exercice_id' => $exerc->id,
                'intitule' => "Compte importé automatiquement - $code",
                'niveau' => 1,
                // Ajoutez d'autres champs obligatoires si nécessaire
            ]);
            
            $this->oldAccountsCache[$code] = ['id' => $oldAccount->id];
            $this->importWarnings[] = "⚠️ Ligne $lineNumber : Compte '$code' créé automatiquement";
            
            Log::info("Compte ancien créé", [
                'code' => $code, 
                'id' => $oldAccount->id
            ]);
            
            return $oldAccount->id;
            
        } catch (\Exception $e) {
            // Gérer les doublons potentiels (race condition)
            $account = OldAccount::where('code', $code)
                ->where('entreprise_id', $this->entrepriseId)
                ->where('exercice_id', $exerc->id)
                ->first();
                
            if ($account) {
                $this->oldAccountsCache[$code] = ['id' => $account->id];
                return $account->id;
            }
            
            $this->importErrors[] = "❌ Ligne $lineNumber : Erreur création compte '$code' - " . $e->getMessage();
            Log::error("Échec création compte", [
                'code' => $code, 
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    public function batchSize(): int
    {
        return 1000; // Augmenté pour meilleures performances
    }

    public function chunkSize(): int
    {
        return 1000; // Augmenté pour meilleures performances
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getErrors(): array
    {
        return $this->importErrors;
    }

    public function getWarnings(): array
    {
        return $this->importWarnings;
    }

    public function getAllMessages(): array
    {
        return array_merge($this->importErrors, $this->importWarnings);
    }
}