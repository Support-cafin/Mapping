<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class AccountMapping extends Model
{
    use Auditable;
    
    protected $fillable = [
        'entreprise_id',
        'exercice_id',
        'old_account_id', 
        'new_account_id', 
        'coefficient',
        'commentaire',
    ];

    public function oldAccount(): BelongsTo
    {
        return $this->belongsTo(OldAccount::class, 'old_account_id');
    }
    public function newAccount(): BelongsTo
    {
        return $this->belongsTo(NewAccount::class, 'new_account_id');
    }
    public function grandLivreEntries(): HasMany
    {
        return $this->hasMany(GrandLivre::class, 'account_mapping_id');
    }
    
     public function dons()
    {
        return $this->hasMany(Donateur::class, 'compte_id');
    }
    
    public static function getComptesDons()
    {
        return self::where('numero', 'like', '75%') // Comptes 75XXX pour dons
                   ->orWhere('libelle', 'like', '%don%')
                   ->orWhere('libelle', 'like', '%mécénat%')
                   ->orderBy('numero')
                   ->get();
    }
    

}
