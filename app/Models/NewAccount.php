<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class NewAccount extends Model
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
        'solde_debit_n1',  // Ajouté
        'solde_credit_n1', // Ajouté
        'parent_id'
    ];
    
    public function entreprise() { return $this->belongsTo(Entreprise::class); }

    public function parent() { return $this->belongsTo(self::class, 'parent_id'); }
    public function children() { return $this->hasMany(self::class, 'parent_id'); }

    public function mappings() { return $this->hasMany(AccountMapping::class); }

    public function oldAccounts() {
        return $this->belongsToMany(OldAccount::class, 'account_mappings')
            ->withPivot(['coefficient','commentaire'])
            ->withTimestamps();
    }

    public function ledger() { return $this->hasMany(NewGeneralLedger::class); }
    public function balances() { return $this->hasMany(NewBalance::class); }
    
    public function grandLivre()
    {
        return $this->hasMany(GrandLivre::class, 'old_account_id');
    }
    
    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }

    
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
}
