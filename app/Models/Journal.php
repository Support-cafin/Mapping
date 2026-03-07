<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Journal extends Model
{
    protected $table = 'journaux';

    protected $fillable = [
        'entreprise_id',
        'code',
        'intitule',
        'type',
        'actif',
        'description',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function entreprise(): BelongsTo
    {
        return $this->belongsTo(Entreprise::class);
    }

    public function ecritures(): HasMany
    {
        return $this->hasMany(GrandLivre::class);
    }

    // Journaux actifs
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    // Filtre entreprise
    public function scopeForEntreprise($query, $entrepriseId)
    {
        return $query->where('entreprise_id', $entrepriseId);
    }

    // Tri par code (ASC)
    public function scopeOrderByCode($query)
    {
        return $query->orderBy('code', 'ASC');
    }

    // Total optimisé
    public function getTotalAttribute()
    {
        return $this->ecritures()
            ->selectRaw('SUM(debit + credit) as total')
            ->value('total') ?? 0;
    }
}
