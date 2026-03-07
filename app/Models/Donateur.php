<?php
// app/Models/Donateur.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Donateur extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero_enregistrement',
        'date',
        'nom_prenoms',
        'denomination',
        'registre_commerce',
        'numero_identification_fiscal',
        'adresse_siege_social',
        'email',
        'montant_don',
        'mode_liberation',
        'devise',
        'signature_representant',
        'notes',
        'statut',
        'created_by',
        'entreprise_id',
        'updated_by',
        'compte_id'
    ];

    protected $casts = [
        'date' => 'date',
        'montant_don' => 'decimal:2',
        'email' => 'array', // Pour stocker plusieurs emails
    ];
    
    public function entreprise()
    {
        return $this->belongsTo(Entreprise::class);
    }
    

    // Relation avec le plan comptable
    public function oldAccount()
    {
        return $this->belongsTo(OldAccount::class, 'compte_id');
    }

    // Relation avec le plan comptable
    public function newAccount()
    {
        return $this->belongsTo(NewAccount::class, 'compte_id');
    }

    // Relation avec l'utilisateur créateur
    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation avec l'utilisateur modificateur
    public function modificateur()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes utiles
    public function scopeThisYear($query)
    {
        return $query->whereYear('date', date('Y'));
    }

    public function scopeByMonth($query, $month)
    {
        return $query->whereMonth('date', $month);
    }

    public function scopeByDenomination($query, $denomination)
    {
        return $query->where('denomination', 'like', "%{$denomination}%");
    }
}