<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class OldAccount extends Model
{
    use Auditable;
    protected $fillable = [
        'entreprise_id',
        'exercice_id',
        'code', 
        'intitule', 
        'classe', 
        'groupe', 
        'niveau', 
        'parent_id'
    ];

    public function entreprise() { return $this->belongsTo(Entreprise::class); }

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }

    public function mappings() { return $this->hasMany(AccountMapping::class); }

    public function newAccounts() {
        return $this->belongsToMany(NewAccount::class, 'account_mappings')
            ->withPivot(['coefficient','commentaire'])
            ->withTimestamps();
    }

    public function ledger() { return $this->hasMany(OldGeneralLedger::class); }
    public function balances() { return $this->hasMany(OldBalance::class); }
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            // Si pas de niveau défini, le calculer
            if (!$account->niveau) {
                $account->niveau = $account->parent_id ? 3 : 1;
            }
            
            // Si pas de classe/groupe et qu'il y a un parent, hériter
            if ($account->parent_id && (!$account->classe || !$account->groupe)) {
                $parent = self::find($account->parent_id);
                if ($parent) {
                    $account->classe = $account->classe ?: $parent->classe;
                    $account->groupe = $account->groupe ?: $parent->groupe;
                }
            }
        });
        
        
    }
    
    
    
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    
    // Dans OldAccount.php et NewAccount.php
    public function grandLivre()
    {
        return $this->hasMany(GrandLivre::class, 'old_account_id');
    }
    
    // Nouveaux scopes utiles
    public function scopeMapped($query)
    {
        return $query->whereHas('mappings');
    }
    
    public function scopeUnmapped($query)
    {
        return $query->whereDoesntHave('mappings');
    }
    
    public function scopeWithData($query)
    {
        return $query->whereHas('ledger')
            ->orWhereHas('balances')
            ->orWhereHas('grandLivre');
    }
    
    public function scopeWithoutData($query)
    {
        return $query->whereDoesntHave('ledger')
            ->whereDoesntHave('balances')
            ->whereDoesntHave('grandLivre');
    }
    
    public function canBeDeleted()
    {
        // Vérifie si le compte contient des données
        return $this->ledger()->doesntExist() 
            && $this->balances()->doesntExist()
            && $this->grandLivre()->doesntExist();
    }
    
    public function canBeModified()
    {
        // Peut être modifié s'il n'est pas mappé OU s'il est mappé mais on ne change que le libellé
        return !$this->mappings()->exists();
    }
    
    // Vérifie si le compte est mappé
    public function isMapped()
    {
        return $this->mappings()->exists();
    }
    
    // Vérifie si le compte a des données
    public function hasData()
    {
        return $this->ledger()->exists() 
            || $this->balances()->exists()
            || $this->grandLivre()->exists();
    }
    
    
    // Méthode pour déterminer la classe du compte basée sur le premier chiffre
    public function getClasseCodeAttribute()
    {
        $firstDigit = substr($this->code, 0, 1);
        
        if (in_array($firstDigit, ['1', '2', '3', '4', '5'])) {
            return 'classe_1_5'; // Comptes de bilan et capitaux
        } elseif (in_array($firstDigit, ['6', '7'])) {
            return 'classe_6_7'; // Comptes de charges et produits
        } else {
            return 'classe_autre'; // Autres comptes
        }
    }
    
    // Méthode pour obtenir le libellé de la classe
    public function getClasseLibelleAttribute()
    {
        $firstDigit = substr($this->code, 0, 1);
        
        $classes = [
            '1' => 'Comptes de capitaux',
            '2' => 'Comptes d\'immobilisations',
            '3' => 'Comptes de stocks',
            '4' => 'Comptes de tiers',
            '5' => 'Comptes financiers',
            '6' => 'Comptes de charges',
            '7' => 'Comptes de produits',
            '8' => 'Comptes spéciaux',
            '9' => 'Comptes analytiques'
        ];
        
        return $classes[$firstDigit] ?? "Classe {$firstDigit}";
    }
    
    
    
    
}
