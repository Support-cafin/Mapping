<?php
// app/Models/Exercice.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Exercice extends Model
{
    use HasFactory;

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'statut'
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'statut' => 'boolean'
    ];

    // Constantes pour les statuts
    const STATUT_FERME = 0;
    const STATUT_ACTIF = 1;

    // Méthodes pour vérifier le statut
    public function estActif()
    {
        return $this->statut == self::STATUT_ACTIF;
    }

    public function estFerme()
    {
        return $this->statut == self::STATUT_FERME;
    }

    // Méthode pour formater la période
    public function getPeriodeAttribute()
    {
        return Carbon::parse($this->date_debut)->format('d/m/Y') . ' - ' . 
               Carbon::parse($this->date_fin)->format('d/m/Y');
    }

    // Méthode pour obtenir le libellé du statut
    public function getStatutLibelleAttribute()
    {
        return $this->statut ? 'Actif' : 'Fermé';
    }

    public function getStatutBadgeAttribute()
    {
        return $this->statut 
            ? '<span class="badge bg-success">Actif</span>' 
            : '<span class="badge bg-secondary">Fermé</span>';
    }

    // Scope pour les exercices actifs
    public function scopeActif($query)
    {
        return $query->where('statut', self::STATUT_ACTIF);
    }

    // Scope pour les exercices fermés
    public function scopeFerme($query)
    {
        return $query->where('statut', self::STATUT_FERME);
    }

    // Vérifier si un autre exercice est actif
    public static function autreExerciceActif($id = null)
    {
        $query = self::where('statut', self::STATUT_ACTIF);
        
        if ($id) {
            $query->where('id', '!=', $id);
        }
        
        return $query->exists();
    }
}