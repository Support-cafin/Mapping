<?php

namespace App\Imports;

use App\Models\OldAccount;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class OldAccountsImport implements 
    ToCollection, 
    WithHeadingRow, 
    WithStartRow, 
    WithBatchInserts, 
    WithChunkReading, 
    SkipsOnError, 
    SkipsOnFailure
{
    use SkipsErrors, SkipsFailures;

    private $entrepriseId;
    private $importedCount = 0;
    private $importErrors = [];
    private $importWarnings = []; // ✅ AJOUTÉ : pour les avertissements
    private $processedCodes = [];
    private $existingCodesCache = []; // ✅ AJOUTÉ : cache des codes existants
    private $parentsCache = []; // ✅ AJOUTÉ : cache des parents
    private $maxRows = 10000; // ✅ AJOUTÉ : limite de sécurité
    private $exerciceId = null; // ✅ AJOUTÉ : cache exercice_id

    // Mapping des colonnes (pour faciliter la configuration)
    protected $columnMapping = [
        'code' => ['code', 'codecompte', 'cod', 'account_code', 'compte'],
        'intitule' => ['intitule', 'intitulé', 'libelle', 'libellé', 'label', 'nom', 'name', 'description', 'designation'],
        'parent_code' => ['parentcode', 'parent', 'codeparent', 'parent_code', 'parent_code', 'parentaccount'],
    ];

    public function __construct($entrepriseId)
    {
        $this->entrepriseId = $entrepriseId;
        $this->loadExistingCodes();
    }

    /**
     * ✅ AJOUTÉ : Charge les codes existants en cache
     */
    private function loadExistingCodes()
    {
        $exoId = $this->getExerciceId();
        if (!$exoId) return;

        $this->existingCodesCache = Cache::remember(
            "old_accounts_codes_{$this->entrepriseId}_{$exoId}",
            3600, // 1 heure
            function () use ($exoId) {
                return OldAccount::where('entreprise_id', $this->entrepriseId)
                    ->where('exercice_id', $exoId)
                    ->pluck('code')
                    ->mapWithKeys(function ($code) {
                        return [strtoupper(trim($code)) => true];
                    })
                    ->toArray();
            }
        );

        Log::info('Cache des codes existants chargé', [
            'count' => count($this->existingCodesCache),
            'type' => 'old_accounts'
        ]);
    }

    /**
     * ✅ AJOUTÉ : Récupère l'exercice_id avec cache
     */
    private function getExerciceId()
    {
        if ($this->exerciceId === null) {
            $exo = DB::table('exercices')->where('statut', 1)->first();
            $this->exerciceId = $exo->id ?? null;
            
            if (!$this->exerciceId) {
                Log::error('Aucun exercice actif trouvé');
                $this->importErrors[] = "❌ Aucun exercice actif trouvé dans le système";
            }
        }
        return $this->exerciceId;
    }

    public function startRow(): int
    {
        return 2;
    }

    public function collection(Collection $rows)
    {
        Log::info('=== DÉBUT IMPORT ANCIENS COMPTES ===', [
            'rows_count' => $rows->count(),
            'entreprise_id' => $this->entrepriseId,
        ]);

        // ✅ AJOUTÉ : Vérification de la limite
        if ($rows->count() > $this->maxRows) {
            $this->importErrors[] = "❌ Trop de lignes : maximum {$this->maxRows} comptes autorisés";
            Log::warning('Import arrêté - trop de lignes', ['count' => $rows->count()]);
            return;
        }

        if ($rows->isEmpty()) {
            $this->addFormatInstructions();
            Log::warning('Aucune ligne trouvée après les en-têtes');
            return;
        }

        // Vérifier l'exercice avant de commencer
        if (!$this->getExerciceId()) {
            return; // Arrêt si pas d'exercice
        }

        // Log de la structure pour debug
        $this->logFirstRowStructure($rows->first());

        // Traitement en deux passes
        $accountsData = $this->collectAccountsData($rows);
        
        if (empty($accountsData)) {
            $this->importErrors[] = "❌ Aucune donnée valide trouvée dans le fichier";
            return;
        }

        $this->insertAccounts($accountsData);

        Log::info('=== FIN IMPORT ===', [
            'imported' => $this->importedCount,
            'errors' => count($this->importErrors),
            'warnings' => count($this->importWarnings)
        ]);
    }

    /**
     * ✅ AJOUTÉ : Instructions de formatage
     */
    private function addFormatInstructions()
    {
        $this->importErrors[] = "⚠️ Fichier vide ou mal formaté. Assurez-vous que :";
        $this->importErrors[] = "  • La ligne 1 contient les en-têtes: code, intitule, parent_code";
        $this->importErrors[] = "  • Les lignes suivantes (2, 3, etc.) contiennent les données";
        $this->importErrors[] = "  • Il n'y a pas de lignes vides entre les en-têtes et les données";
        $this->importErrors[] = "  • Les colonnes sont dans l'ordre: code, intitule, parent_code";
    }

    /**
     * ✅ AJOUTÉ : Log de la première ligne
     */
    private function logFirstRowStructure($firstRow)
    {
        if ($firstRow) {
            Log::info('Structure première ligne', [
                'keys' => array_keys($firstRow->toArray()),
                'sample' => array_slice($firstRow->toArray(), 0, 3)
            ]);
        }
    }

    /**
     * ✅ AMÉLIORÉ : Collecte des données avec meilleure validation
     */
    private function collectAccountsData(Collection $rows)
    {
        $accountsData = [];
        $this->processedCodes = [];

        foreach ($rows as $index => $row) {
            $lineNumber = $index + 2;
            
            try {
                $rowData = $this->normalizeRow($row);
                
                Log::debug("Ligne $lineNumber", ['data' => $rowData]);

                $code = $this->sanitizeCode($rowData['code'] ?? '');
                $intitule = $this->sanitizeIntitule($rowData['intitule'] ?? '');
                $parentCode = $this->sanitizeCode($rowData['parent_code'] ?? '');

                // Validations
                if (!$this->validateCode($code, $lineNumber)) {
                    continue;
                }

                if (!$this->validateIntitule($intitule, $code, $lineNumber)) {
                    continue;
                }

                // Vérification doublon dans le fichier
                if (isset($this->processedCodes[$code])) {
                    $this->importWarnings[] = "⚠️ Ligne $lineNumber : Code '$code' en double dans le fichier (ignoré)";
                    continue;
                }

                // Vérification existence en base (via cache)
                $codeUpper = strtoupper($code);
                if (isset($this->existingCodesCache[$codeUpper])) {
                    $this->importWarnings[] = "⚠️ Ligne $lineNumber : Code '$code' existe déjà en base (ignoré)";
                    continue;
                }

                $this->processedCodes[$code] = true;

                $accountsData[] = [
                    'line' => $lineNumber,
                    'code' => $code,
                    'intitule' => $intitule,
                    'parent_code' => $parentCode,
                ];

            } catch (\Exception $e) {
                $this->importErrors[] = "❌ Ligne $lineNumber : " . $e->getMessage();
                Log::error("Erreur ligne $lineNumber", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        return $accountsData;
    }

    /**
     * ✅ AJOUTÉ : Validation du code
     */
    private function validateCode($code, $lineNumber)
    {
        if (empty($code)) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Le code est obligatoire";
            return false;
        }

        if (strlen($code) > 20) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Code '$code' trop long (max 20 caractères)";
            return false;
        }

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $code)) {
            $this->importErrors[] = "❌ Ligne $lineNumber : Code '$code' contient des caractères non autorisés (uniquement lettres, chiffres, . _ -)";
            return false;
        }

        return true;
    }

    /**
     * ✅ AJOUTÉ : Validation de l'intitulé
     */
    private function validateIntitule($intitule, $code, $lineNumber)
    {
        if (empty($intitule)) {
            $this->importErrors[] = "❌ Ligne $lineNumber (Code: $code) : L'intitulé est obligatoire";
            return false;
        }

        if (strlen($intitule) > 255) {
            $this->importWarnings[] = "⚠️ Ligne $lineNumber : Intitulé trop long pour le code '$code' (sera tronqué)";
        }

        return true;
    }

    /**
     * ✅ AJOUTÉ : Nettoyage du code
     */
    private function sanitizeCode($code)
    {
        return trim(preg_replace('/\s+/', '', $code));
    }

    /**
     * ✅ AJOUTÉ : Nettoyage de l'intitulé
     */
    private function sanitizeIntitule($intitule)
    {
        $intitule = trim($intitule);
        $intitule = preg_replace('/\s+/', ' ', $intitule); // Normaliser les espaces
        return substr($intitule, 0, 255); // Tronquer si trop long
    }

    /**
     * ✅ AMÉLIORÉ : Normalisation avec mapping flexible
     */
    private function normalizeRow($row)
    {
        $normalized = [
            'code' => null,
            'intitule' => null,
            'parent_code' => null
        ];
        
        foreach ($row as $key => $value) {
            if ($value === null) continue;
            
            $cleanKey = strtolower(trim($key));
            $cleanKey = preg_replace('/[^a-z]/', '', $cleanKey); // Garder seulement les lettres
            
            foreach ($this->columnMapping as $field => $patterns) {
                foreach ($patterns as $pattern) {
                    if (strpos($cleanKey, $pattern) !== false) {
                        $normalized[$field] = $value;
                        break 2;
                    }
                }
            }
        }
        
        return $normalized;
    }

    /**
     * ✅ AMÉLIORÉ : Insertion avec gestion des parents
     */
    private function insertAccounts($accountsData)
    {
        $exoId = $this->getExerciceId();
        
        if (!$exoId) {
            return; // Déjà géré dans collection
        }

        DB::beginTransaction();
        
        try {
            // Préparer les données avec résolution des parents
            $accountsToCreate = [];
            
            foreach ($accountsData as $data) {
                $account = $this->prepareAccountData($data, $exoId);
                if ($account) {
                    $accountsToCreate[] = $account;
                }
            }

            // Insertion par lots
            foreach (array_chunk($accountsToCreate, 50) as $chunk) {
                OldAccount::insert($chunk);
                $this->importedCount += count($chunk);
                
                Log::info("Lot inséré", ['count' => count($chunk)]);
            }
            
            DB::commit();
            
            // Invalider le cache
            $this->invalidateCache();
            
            Log::info("Import terminé avec succès", [
                'imported' => $this->importedCount,
                'total_traite' => count($accountsData)
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->importErrors[] = "❌ Erreur critique lors de l'importation : " . $e->getMessage();
            Log::error('Transaction rollback', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * ✅ AJOUTÉ : Préparation des données d'un compte
     */
    private function prepareAccountData($data, $exoId)
    {
        $code = $data['code'];
        $intitule = $data['intitule'];
        $parentCode = $data['parent_code'];
        $line = $data['line'];

        // Calcul des métadonnées
        $classe = strlen($code) > 0 ? substr($code, 0, 1) : null;
        $groupe = strlen($code) > 1 ? substr($code, 0, 2) : $classe;
        $niveau = $this->determineAccountLevel($code);

        // Résolution du parent
        $parentId = $this->resolveParentId($parentCode, $line, $code);

        return [
            'entreprise_id' => $this->entrepriseId,
            'exercice_id' => $exoId,
            'code' => $code,
            'intitule' => substr($intitule, 0, 255),
            'classe' => $classe,
            'groupe' => $groupe,
            'niveau' => $niveau,
            'parent_id' => $parentId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * ✅ AJOUTÉ : Résolution de l'ID du parent
     */
    private function resolveParentId($parentCode, $line, $childCode)
    {
        if (empty($parentCode)) {
            return null;
        }

        $parentCode = $this->sanitizeCode($parentCode);

        // Vérifier le cache
        if (isset($this->parentsCache[$parentCode])) {
            return $this->parentsCache[$parentCode];
        }

        // Vérifier dans les comptes à créer (pour les parents qui seront créés dans le même import)
        if (isset($this->processedCodes[$parentCode])) {
            // Le parent sera créé plus tard, on le traitera dans une deuxième passe
            // Pour l'instant, on le met en cache pour une résolution ultérieure
            $this->parentsCache[$parentCode] = null; // Marqué comme à créer
            $this->importWarnings[] = "⚠️ Ligne $line : Parent '$parentCode' pour '$childCode' sera créé ultérieurement";
            return null;
        }

        $exoId = $this->getExerciceId();
        
        // Rechercher en base
        $parent = OldAccount::where('entreprise_id', $this->entrepriseId)
            ->where('exercice_id', $exoId)
            ->where('code', $parentCode)
            ->first();
        
        if ($parent) {
            $this->parentsCache[$parentCode] = $parent->id;
            return $parent->id;
        }

        $this->importWarnings[] = "⚠️ Ligne $line : Parent '$parentCode' introuvable pour '$childCode' (compte créé sans parent)";
        return null;
    }

    /**
     * ✅ AJOUTÉ : Détermination du niveau du compte
     */
    private function determineAccountLevel($code)
    {
        $length = strlen($code);
        if ($length <= 2) return 1;
        if ($length <= 4) return 2;
        if ($length <= 6) return 3;
        return 4;
    }

    /**
     * ✅ AJOUTÉ : Invalidation du cache
     */
    private function invalidateCache()
    {
        try {
            Cache::forget("old_accounts_codes_{$this->entrepriseId}_{$this->getExerciceId()}");
            
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['accounts', 'old_accounts', 'entreprise_' . $this->entrepriseId])->flush();
            }
        } catch (\Exception $e) {
            Log::warning('Erreur lors de l\'invalidation du cache', ['error' => $e->getMessage()]);
        }
    }

    /**
     * ✅ AJOUTÉ : Deuxième passe pour résoudre les parents
     * (Optionnel - à appeler après l'insertion si nécessaire)
     */
    public function resolveParentsAfterImport()
    {
        $exoId = $this->getExerciceId();
        
        // Mettre à jour les comptes dont le parent était dans le même import
        foreach ($this->parentsCache as $parentCode => $parentId) {
            if ($parentId === null && isset($this->processedCodes[$parentCode])) {
                // Le parent a été créé, on peut mettre à jour les enfants
                $parent = OldAccount::where('entreprise_id', $this->entrepriseId)
                    ->where('exercice_id', $exoId)
                    ->where('code', $parentCode)
                    ->first();
                
                if ($parent) {
                    OldAccount::where('entreprise_id', $this->entrepriseId)
                        ->where('exercice_id', $exoId)
                        ->where('parent_id', null)
                        ->whereIn('code', array_keys($this->processedCodes))
                        ->update(['parent_id' => $parent->id]);
                    
                    Log::info("Parents résolus après import", ['parent_code' => $parentCode]);
                }
            }
        }
    }

    // Configuration
    public function rules(): array
    {
        return [];
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function chunkSize(): int
    {
        return 50;
    }

    // Getters
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