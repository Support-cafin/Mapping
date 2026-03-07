<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $table = 'balances';
    
    protected $fillable = [
        'entreprise_id',
        'compte_code',
        'compte_libelle',
        'periode_debut',
        'periode_fin',
        'solde_debiteur',
        'solde_crediteur',
        'mouvement_debit',
        'mouvement_credit'
    ];
    
    public $timestamps = true;
    
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
}