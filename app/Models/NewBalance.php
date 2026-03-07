<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class NewBalance extends Model
{
    use Auditable;
    protected $fillable = [
        'entreprise_id',
        'new_account_id', 
        'debit', 
        'credit', 
        'solde',
        'periode', 
        'exercice'
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'solde' => 'decimal:2',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function newAccount(): BelongsTo
    {
        return $this->belongsTo(NewAccount::class);
    }
}
