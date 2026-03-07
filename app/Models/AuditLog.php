<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    protected $table = 'audit_logs';
    
    protected $fillable = [
        'entreprise_id',
        'user_id',
        'action',
        'model',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation avec l'entreprise
     */
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation polymorphique avec le modèle concerné
     */
    public function related(): MorphTo
    {
        return $this->morphTo('related', 'model', 'model_id');
    }

    /**
     * Relation avec AccountMapping (si c'est le modèle concerné)
     */
    public function accountMapping(): BelongsTo
    {
        return $this->belongsTo(AccountMapping::class, 'model_id')
            ->where('model', 'AccountMapping');
    }

    /**
     * Relation avec OldAccount (si c'est le modèle concerné)
     */
    public function oldAccount(): BelongsTo
    {
        return $this->belongsTo(OldAccount::class, 'model_id')
            ->where('model', 'OldAccount');
    }

    /**
     * Relation avec NewAccount (si c'est le modèle concerné)
     */
    public function newAccount(): BelongsTo
    {
        return $this->belongsTo(NewAccount::class, 'model_id')
            ->where('model', 'NewAccount');
    }

    /**
     * Relation avec GrandLivre (si c'est le modèle concerné)
     */
    public function grandLivre(): BelongsTo
    {
        return $this->belongsTo(GrandLivre::class, 'model_id')
            ->where('model', 'GrandLivre');
    }

    /**
     * Obtenir le modèle réel concerné
     */
    public function getRelatedModelAttribute()
    {
        if (!$this->model || !$this->model_id) {
            return null;
        }

        $modelClass = match($this->model) {
            'AccountMapping' => AccountMapping::class,
            'OldAccount' => OldAccount::class,
            'NewAccount' => NewAccount::class,
            'GrandLivre' => GrandLivre::class,
            default => null,
        };

        if (!$modelClass) {
            return null;
        }

        return $modelClass::find($this->model_id);
    }

    /**
     * Obtenir les informations du compte source pour les mappings
     */
    public function getOldAccountInfoAttribute(): ?string
    {
        if ($this->model === 'AccountMapping') {
            // Si on a le mapping chargé
            if ($this->relationLoaded('accountMapping') && $this->accountMapping) {
                return $this->accountMapping->oldAccount 
                    ? "{$this->accountMapping->oldAccount->code} - {$this->accountMapping->oldAccount->intitule}"
                    : "ID: {$this->accountMapping->old_account_id}";
            }
            
            // Sinon, essayer de charger via les valeurs
            if (isset($this->new_values['old_account_id'])) {
                $oldAccount = OldAccount::find($this->new_values['old_account_id']);
                return $oldAccount 
                    ? "{$oldAccount->code} - {$oldAccount->intitule}"
                    : "ID: {$this->new_values['old_account_id']}";
            }
            
            if (isset($this->old_values['old_account_id'])) {
                $oldAccount = OldAccount::find($this->old_values['old_account_id']);
                return $oldAccount 
                    ? "{$oldAccount->code} - {$oldAccount->intitule}"
                    : "ID: {$this->old_values['old_account_id']}";
            }
        }
        
        return null;
    }

    /**
     * Obtenir les informations du compte cible pour les mappings
     */
    public function getNewAccountInfoAttribute(): ?string
    {
        if ($this->model === 'AccountMapping') {
            // Si on a le mapping chargé
            if ($this->relationLoaded('accountMapping') && $this->accountMapping) {
                return $this->accountMapping->newAccount 
                    ? "{$this->accountMapping->newAccount->code} - {$this->accountMapping->newAccount->intitule}"
                    : "ID: {$this->accountMapping->new_account_id}";
            }
            
            // Sinon, essayer de charger via les valeurs
            if (isset($this->new_values['new_account_id'])) {
                $newAccount = NewAccount::find($this->new_values['new_account_id']);
                return $newAccount 
                    ? "{$newAccount->code} - {$newAccount->intitule}"
                    : "ID: {$this->new_values['new_account_id']}";
            }
            
            if (isset($this->old_values['new_account_id'])) {
                $newAccount = NewAccount::find($this->old_values['new_account_id']);
                return $newAccount 
                    ? "{$newAccount->code} - {$newAccount->intitule}"
                    : "ID: {$this->old_values['new_account_id']}";
            }
        }
        
        return null;
    }

    /**
     * Obtenir les informations du compte (pour OldAccount/NewAccount)
     */
    public function getAccountInfoAttribute(): ?string
    {
        if ($this->model === 'OldAccount' || $this->model === 'NewAccount') {
            // Si on a le compte chargé
            if ($this->relationLoaded('related') && $this->related) {
                return "{$this->related->code} - {$this->related->intitule}";
            }
            
            // Sinon, essayer de charger via les valeurs
            $values = $this->new_values ?? $this->old_values ?? [];
            if (isset($values['code']) && isset($values['intitule'])) {
                return "{$values['code']} - {$values['intitule']}";
            }
            
            // En dernier recours, chercher par ID
            $modelClass = $this->model === 'OldAccount' ? OldAccount::class : NewAccount::class;
            $account = $modelClass::find($this->model_id);
            return $account ? "{$account->code} - {$account->intitule}" : "ID: {$this->model_id}";
        }
        
        return null;
    }

    /**
     * Accès rapide aux changements
     */
    public function getChangesAttribute(): array
    {
        $changes = [];
        
        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue != $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue
                    ];
                }
            }
        }
        
        return $changes;
    }

    /**
     * Formater l'action pour l'affichage
     */
    public function getFormattedActionAttribute(): string
    {
        $actions = [
            'create' => 'Création',
            'update' => 'Modification',
            'delete' => 'Suppression',
            'validate' => 'Validation',
            'mapping_sync' => 'Synchronisation de mapping',
            'mapping_create' => 'Création de mapping',
            'mapping_update' => 'Mise à jour de mapping',
            'mapping_dragdrop' => 'Mapping par glisser-déposer',
        ];

        return $actions[$this->action] ?? $this->action;
    }

    /**
     * Obtenir une description détaillée de l'action
     */
    public function getDetailedDescriptionAttribute(): string
    {
        $userName = $this->user->name ?? 'Utilisateur inconnu';
        $timeAgo = $this->created_at->diffForHumans();
        
        // Pour les mappings
        if ($this->model === 'AccountMapping') {
            return $this->getMappingDescription($userName, $timeAgo);
        }
        
        // Pour les comptes
        if ($this->model === 'OldAccount' || $this->model === 'NewAccount') {
            return $this->getAccountDescription($userName, $timeAgo);
        }
        
        // Pour le Grand Livre
        if ($this->model === 'GrandLivre') {
            return $this->getGrandLivreDescription($userName, $timeAgo);
        }
        
        // Description générique
        return match($this->action) {
            'create' => "{$userName} a créé un nouvel enregistrement dans {$this->model} ({$timeAgo})",
            'update' => "{$userName} a modifié un enregistrement dans {$this->model} ({$timeAgo})",
            'delete' => "{$userName} a supprimé un enregistrement de {$this->model} ({$timeAgo})",
            default => "{$userName} a effectué l'action '{$this->formatted_action}' sur {$this->model} ({$timeAgo})",
        };
    }

    /**
     * Description spécifique pour les mappings
     */
    private function getMappingDescription(string $userName, string $timeAgo): string
    {
        $oldInfo = $this->old_account_info ?? 'compte source inconnu';
        $newInfo = $this->new_account_info ?? 'compte cible inconnu';
        
        return match($this->action) {
            'mapping_create' => "{$userName} a créé un mapping entre le compte {$oldInfo} et le compte {$newInfo} ({$timeAgo})",
            'mapping_update' => "{$userName} a modifié le mapping du compte {$oldInfo} vers le compte {$newInfo} ({$timeAgo})",
            'delete' => "{$userName} a supprimé le mapping du compte {$oldInfo} ({$timeAgo})",
            'mapping_dragdrop' => "{$userName} a mappé le compte {$oldInfo} vers le compte {$newInfo} par glisser-déposer ({$timeAgo})",
            default => "{$userName} a effectué une action de mapping ({$timeAgo})",
        };
    }

    /**
     * Description spécifique pour les comptes
     */
    private function getAccountDescription(string $userName, string $timeAgo): string
    {
        $accountInfo = $this->account_info ?? 'compte inconnu';
        
        return match($this->action) {
            'create' => "{$userName} a créé le compte {$accountInfo} ({$timeAgo})",
            'update' => "{$userName} a modifié le compte {$accountInfo} ({$timeAgo})",
            'delete' => "{$userName} a supprimé le compte {$accountInfo} ({$timeAgo})",
            default => "{$userName} a effectué une action sur le compte {$accountInfo} ({$timeAgo})",
        };
    }

    /**
     * Description spécifique pour le Grand Livre
     */
    private function getGrandLivreDescription(string $userName, string $timeAgo): string
    {
        $entryInfo = $this->getGrandLivreInfo();
        
        return match($this->action) {
            'create' => "{$userName} a créé une écriture {$entryInfo} ({$timeAgo})",
            'update' => "{$userName} a modifié l'écriture {$entryInfo} ({$timeAgo})",
            'validate' => "{$userName} a validé l'écriture {$entryInfo} ({$timeAgo})",
            default => "{$userName} a effectué une action sur l'écriture {$entryInfo} ({$timeAgo})",
        };
    }

    /**
     * Obtenir les informations du Grand Livre
     */
    private function getGrandLivreInfo(): string
    {
        $values = $this->new_values ?? $this->old_values ?? [];
        
        if (isset($values['piece'])) {
            return "n°{$values['piece']}" . (isset($values['libelle']) ? " ({$values['libelle']})" : '');
        }
        
        if ($this->model_id) {
            return "ID: {$this->model_id}";
        }
        
        return "dans le Grand Livre";
    }

    /**
     * Obtenir un résumé des changements pour l'affichage
     */
    public function getChangesSummaryAttribute(): ?string
    {
        if (!$this->changes) {
            return null;
        }
        
        $summary = [];
        foreach ($this->changes as $field => $change) {
            $fieldName = $this->getFieldLabel($field);
            
            if ($change['old'] === null) {
                $summary[] = "a ajouté {$fieldName}: " . $this->formatValue($change['new']);
            } elseif ($change['new'] === null) {
                $summary[] = "a supprimé {$fieldName}";
            } else {
                $summary[] = "a changé {$fieldName}: " . 
                            $this->formatValue($change['old']) . " → " . 
                            $this->formatValue($change['new']);
            }
        }
        
        return implode(', ', $summary);
    }

    /**
     * Traduire les noms de champs
     */
    private function getFieldLabel(string $field): string
    {
        $labels = [
            'old_account_id' => 'compte source',
            'new_account_id' => 'compte cible',
            'coefficient' => 'coefficient',
            'commentaire' => 'commentaire',
            'code' => 'code',
            'intitule' => 'intitulé',
            'debit' => 'débit',
            'credit' => 'crédit',
            'validated' => 'statut de validation',
            'date_ecriture' => 'date',
            'libelle' => 'libellé',
        ];
        
        return $labels[$field] ?? $field;
    }

    /**
     * Formater une valeur pour l'affichage
     */
    private function formatValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        
        if (is_numeric($value) && (str_contains($value, '.') || str_contains($value, ','))) {
            return number_format(floatval($value), 2, ',', ' ') . ' €';
        }
        
        return (string) $value;
    }

    /**
     * Scope pour filtrer par entreprise
     */
    public function scopeForEntreprise($query, int $entrepriseId)
    {
        return $query->where('entreprise_id', $entrepriseId);
    }

    /**
     * Scope pour filtrer par modèle
     */
    public function scopeForModel($query, string $model, ?int $modelId = null)
    {
        $query = $query->where('model', $model);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Scope pour les actions récentes
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Charger automatiquement les relations selon le modèle
     */
    public function scopeWithRelated($query)
    {
        return $query->with(['user', 'entreprise']);
    }

    /**
     * Charger les relations spécifiques selon le type de modèle
     */
    public function loadRelated()
    {
        switch ($this->model) {
            case 'AccountMapping':
                $this->load(['accountMapping' => function($q) {
                    $q->with(['oldAccount', 'newAccount']);
                }]);
                break;
            case 'OldAccount':
                $this->load('oldAccount');
                break;
            case 'NewAccount':
                $this->load('newAccount');
                break;
            case 'GrandLivre':
                $this->load('grandLivre');
                break;
        }
        
        return $this;
    }
}