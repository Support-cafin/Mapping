<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OldGeneralLedger extends Model
{
    protected $table = 'old_general_ledger';

    protected $fillable = [
        'entreprise_id',
        'old_account_id', 
        'new_account_id',
        'journal',
        'date_operation', 
        'piece',
        'libelle', 
        'debit', 
        'credit', 
        'solde', 
        'exercice'
    ];

    public function entreprise() { return $this->belongsTo(Entreprise::class); }
    public function oldAccount() { return $this->belongsTo(OldAccount::class); }
    public function newAccount() { return $this->belongsTo(NewAccount::class); }
}
