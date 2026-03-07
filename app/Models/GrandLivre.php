<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\Auditable;

class GrandLivre extends Model
{
    use Auditable;
    
    protected $fillable = [
        'entreprise_id',
        'exercice_id',
        'journal_code',  
        'old_account_id',
        'new_account_id',
        'date_ecriture',
        'piece',
        'libelle',
        'debit',
        'credit',
        'solde',
        'exercice',
        'mois',
        'trimestre',
        'source',
        'validated',
        'notes',
        'lettre',
    ];

    protected $casts = [
        'date_ecriture' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'solde' => 'decimal:2',
        'validated' => 'boolean',
    ];

    // ======= RELATIONS =======
    
    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function oldAccount(): BelongsTo
    {
        return $this->belongsTo(OldAccount::class);
    }

    public function newAccount(): BelongsTo
    {
        return $this->belongsTo(NewAccount::class);
    }

    // ======= SCOPES =======
    
    public function scopeForEntreprise($query, $entrepriseId)
    {
        return $query->where('entreprise_id', $entrepriseId);
    }

    public function scopeForExercice($query, $exercice)
    {
        return $query->where('exercice', $exercice);
    }

    public function scopeForPeriode($query, $dateDebut, $dateFin)
    {
        return $query->whereBetween('date_ecriture', [$dateDebut, $dateFin]);
    }

    public function scopeForAccount($query, $accountType, $accountId)
    {
        return match ($accountType) {
            'old' => $query->where('old_account_id', $accountId),
            'new' => $query->where('new_account_id', $accountId),
            default => $query
        };
    }

    public function scopeValides($query)
    {
        return $query->where('validated', true);
    }

    public function scopeOrderByDate($query)
    {
        return $query->orderBy('date_ecriture', 'ASC');
    }

    public function scopeLettres($query, $lettre)
    {
        return $query->where('lettre', $lettre);
    }

    public function scopeNonLettres($query)
    {
        return $query->whereNull('lettre');
    }

    // Scope pour filtrer par journal
    public function scopeForJournal($query, $journalCode)
    {
        return $query->where('journal_code', $journalCode);
    }

    // ======= ACCESSEURS =======
    
    // App\Models\GrandLivre.php - MODIFIER
    public function getSoldeAttribute($value)
    {
        // Si $value existe (stocké en base), le retourner
        if ($value !== null) {
            return $value;
        }
        
        // Sinon calculer avec abs()
        return abs($this->debit - $this->credit);
    }

    public function getMontantAttribute(): float
    {
        return max($this->debit, $this->credit);
    }

    public function getSensAttribute(): string
    {
        return $this->debit > 0 ? 'Débit' : 'Crédit';
    }

    public function getMappingStatusAttribute()
    {
        if ($this->oldAccount && $this->newAccount) {
            return 'mapped';
        } elseif ($this->oldAccount && !$this->newAccount) {
            return 'not_mapped';
        } else {
            return 'no_old_account';
        }
    }

    // ======= ÉVÉNEMENTS =======
    
    /**
     * Boot du modèle - détection automatique du mapping
     */
    protected static function boot()
    {
        parent::boot();

        // Avant la création, détecter automatiquement le nouveau compte
        static::creating(function ($grandLivre) {
            if ($grandLivre->old_account_id && !$grandLivre->new_account_id) {
                $grandLivre->new_account_id = static::detectNewAccountFromMapping($grandLivre->old_account_id);
            }
        });

        // Avant la mise à jour, détecter automatiquement le nouveau compte
        static::updating(function ($grandLivre) {
            if ($grandLivre->isDirty('old_account_id') && $grandLivre->old_account_id) {
                $grandLivre->new_account_id = static::detectNewAccountFromMapping($grandLivre->old_account_id);
            }
        });
    }

    /**
     * Détecte le nouveau compte à partir du mapping
     */
    private static function detectNewAccountFromMapping($oldAccountId)
    {
        if (!$oldAccountId) {
            return null;
        }

        $mapping = AccountMapping::where('old_account_id', $oldAccountId)->first();
        
        return $mapping ? $mapping->new_account_id : null;
    }

    /**
     * Met à jour le mapping manuellement (si besoin)
     */
    public function syncMapping()
    {
        if ($this->old_account_id) {
            $newAccountId = static::detectNewAccountFromMapping($this->old_account_id);
            
            if ($newAccountId !== $this->new_account_id) {
                $this->update(['new_account_id' => $newAccountId]);
                return true;
            }
        }
        
        return false;
    }
}