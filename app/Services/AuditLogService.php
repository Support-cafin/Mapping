<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogService
{
    /**
     * Créer un log d'audit
     */
    public static function log(
        string $action,
        string $model,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $entrepriseId = null,
        ?int $userId = null
    ): ?AuditLog {
        
        // Filtrer les valeurs sensibles
        $oldValues = $oldValues ? self::filterSensitiveData($oldValues) : null;
        $newValues = $newValues ? self::filterSensitiveData($newValues) : null;

        try {
            return AuditLog::create([
                'entreprise_id' => $entrepriseId ?? Auth::user()->entreprise_id ?? null,
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'model' => $model,
                'model_id' => $modelId,
                'old_values' => $oldValues ? json_encode($oldValues) : null,
                'new_values' => $newValues ? json_encode($newValues) : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (\Exception $e) {
            // Log l'erreur mais ne pas bloquer l'application
            \Log::error('Failed to create audit log: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Log de création
     */
    public static function logCreate(
        string $model,
        array $data,
        ?int $modelId = null,
        ?int $entrepriseId = null
    ): ?AuditLog {
        return self::log(
            action: 'create',
            model: $model,
            modelId: $modelId,
            newValues: $data,
            entrepriseId: $entrepriseId
        );
    }

    /**
     * Log de mise à jour
     */
    public static function logUpdate(
        string $model,
        int $modelId,
        array $oldValues,
        array $newValues,
        ?int $entrepriseId = null
    ): ?AuditLog {
        // Ne loguer que les champs modifiés
        $changes = [];
        foreach ($newValues as $key => $value) {
            if (!array_key_exists($key, $oldValues) || $oldValues[$key] != $value) {
                $changes[$key] = [
                    'old' => $oldValues[$key] ?? null,
                    'new' => $value
                ];
            }
        }

        if (empty($changes)) {
            // Aucun changement, pas de log
            return null;
        }

        return self::log(
            action: 'update',
            model: $model,
            modelId: $modelId,
            oldValues: $oldValues,
            newValues: $newValues,
            entrepriseId: $entrepriseId
        );
    }

    /**
     * Log de suppression
     */
    public static function logDelete(
        string $model,
        int $modelId,
        array $oldValues,
        ?int $entrepriseId = null
    ): ?AuditLog {
        return self::log(
            action: 'delete',
            model: $model,
            modelId: $modelId,
            oldValues: $oldValues,
            entrepriseId: $entrepriseId
        );
    }

    /**
     * Log de validation
     */
    public static function logValidation(
        string $model,
        int $modelId,
        string $validationType,
        ?int $entrepriseId = null
    ): ?AuditLog {
        return self::log(
            action: 'validate_' . $validationType,
            model: $model,
            modelId: $modelId,
            entrepriseId: $entrepriseId
        );
    }

    /**
     * Log de synchronisation de mapping
     */
    // Dans app/Services/AuditLogService.php, ajoutez cette méthode :
    
    /**
     * Log de création de mapping avec détails complets
     */
    public static function logMappingCreate(
        AccountMapping $mapping,
        ?int $entrepriseId = null
    ): ?AuditLog {
        $oldAccount = $mapping->oldAccount;
        $newAccount = $mapping->newAccount;
        
        return self::log(
            action: 'mapping_create',
            model: 'AccountMapping',
            modelId: $mapping->id,
            oldValues: null,
            newValues: [
                'old_account_id' => $mapping->old_account_id,
                'new_account_id' => $mapping->new_account_id,
                'coefficient' => $mapping->coefficient,
                'commentaire' => $mapping->commentaire,
                'old_account_info' => $oldAccount ? "{$oldAccount->code} - {$oldAccount->intitule}" : null,
                'new_account_info' => $newAccount ? "{$newAccount->code} - {$newAccount->intitule}" : null,
            ],
            entrepriseId: $entrepriseId ?? $mapping->entreprise_id
        );
    }
    
    /**
     * Log de suppression de mapping avec détails complets
     */
    public static function logMappingDelete(
        AccountMapping $mapping,
        ?int $entrepriseId = null
    ): ?AuditLog {
        $oldAccount = $mapping->oldAccount;
        $newAccount = $mapping->newAccount;
        
        return self::log(
            action: 'delete',
            model: 'AccountMapping',
            modelId: $mapping->id,
            oldValues: [
                'old_account_id' => $mapping->old_account_id,
                'new_account_id' => $mapping->new_account_id,
                'coefficient' => $mapping->coefficient,
                'commentaire' => $mapping->commentaire,
                'old_account_info' => $oldAccount ? "{$oldAccount->code} - {$oldAccount->intitule}" : null,
                'new_account_info' => $newAccount ? "{$newAccount->code} - {$newAccount->intitule}" : null,
            ],
            newValues: null,
            entrepriseId: $entrepriseId ?? $mapping->entreprise_id
        );
    }

    /**
     * Log d'import CSV
     */
    public static function logImportCsv(
        string $model,
        array $stats,
        ?string $fileName = null,
        ?int $entrepriseId = null
    ): ?AuditLog {
        return self::log(
            action: 'import_csv',
            model: $model,
            newValues: array_merge(
                ['file' => $fileName],
                $stats
            ),
            entrepriseId: $entrepriseId
        );
    }

    /**
     * Filtrer les données sensibles
     */
    private static function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_key',
            'secret',
            'credit_card',
            'cvv',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***FILTERED***';
            }
        }

        return $data;
    }

    /**
     * Récupérer les logs d'un modèle
     */
    public static function getModelLogs(string $model, ?int $modelId = null, ?int $entrepriseId = null)
    {
        $query = AuditLog::query();
        
        if ($entrepriseId) {
            $query->where('entreprise_id', $entrepriseId);
        }
        
        if ($modelId) {
            $query->where('model', $model)
                  ->where('model_id', $modelId);
        } else {
            $query->where('model', $model);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Obtenir les statistiques de logs
     */
    public static function getStats(?int $entrepriseId = null): array
    {
        $query = AuditLog::query();
        
        if ($entrepriseId) {
            $query->where('entreprise_id', $entrepriseId);
        }

        $total = $query->count();
        $today = $query->whereDate('created_at', today())->count();
        $last7Days = $query->where('created_at', '>=', now()->subDays(7))->count();
        
        $byAction = $query->groupBy('action')
            ->selectRaw('action, COUNT(*) as count')
            ->pluck('count', 'action')
            ->toArray();
            
        $byModel = $query->groupBy('model')
            ->selectRaw('model, COUNT(*) as count')
            ->pluck('count', 'model')
            ->toArray();

        return [
            'total' => $total,
            'today' => $today,
            'last_7_days' => $last7Days,
            'by_action' => $byAction,
            'by_model' => $byModel,
        ];
    }
    
    // Ajoutez ces méthodes dans votre AuditLogService
    
    /**
     * Log de création de mapping avec toutes les informations
     */
    public static function logMappingCreateWithDetails(AccountMapping $mapping, ?int $entrepriseId = null): ?AuditLog
    {
        $oldAccount = $mapping->oldAccount;
        $newAccount = $mapping->newAccount;
        
        return self::log(
            action: 'mapping_create',
            model: 'AccountMapping',
            modelId: $mapping->id,
            oldValues: null,
            newValues: [
                'old_account_id' => $mapping->old_account_id,
                'new_account_id' => $mapping->new_account_id,
                'coefficient' => $mapping->coefficient,
                'commentaire' => $mapping->commentaire,
                'old_account_code' => $oldAccount?->code,
                'old_account_intitule' => $oldAccount?->intitule,
                'new_account_code' => $newAccount?->code,
                'new_account_intitule' => $newAccount?->intitule,
            ],
            entrepriseId: $entrepriseId ?? $mapping->entreprise_id
        );
    }
    
    /**
     * Log de mise à jour de mapping avec toutes les informations
     */
    public static function logMappingUpdateWithDetails(AccountMapping $mapping, array $oldData, ?int $entrepriseId = null): ?AuditLog
    {
        $oldAccount = $mapping->oldAccount;
        $newAccount = $mapping->newAccount;
        
        return self::log(
            action: 'mapping_update',
            model: 'AccountMapping',
            modelId: $mapping->id,
            oldValues: $oldData,
            newValues: [
                'old_account_id' => $mapping->old_account_id,
                'new_account_id' => $mapping->new_account_id,
                'coefficient' => $mapping->coefficient,
                'commentaire' => $mapping->commentaire,
                'old_account_code' => $oldAccount?->code,
                'old_account_intitule' => $oldAccount?->intitule,
                'new_account_code' => $newAccount?->code,
                'new_account_intitule' => $newAccount?->intitule,
            ],
            entrepriseId: $entrepriseId ?? $mapping->entreprise_id
        );
    }
}