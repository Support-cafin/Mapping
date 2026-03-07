<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewGeneralLedger extends Model
{
    protected $table = 'new_general_ledger';

    protected $fillable = [
        'new_account_id', 
        'entreprise_id',
        'journal',
        'date_operation', 
        'piece',
        'libelle', 
        'debit', 
        'credit', 
        'solde', 
        'exercice'
    ];

    public function entreprise() 
    { 
        return $this->belongsTo(Entreprise::class); 
    }
    public function newAccount() 
    { 
        return $this->belongsTo(NewAccount::class); 
    }
}
