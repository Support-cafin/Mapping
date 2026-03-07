<?php
// database/seeders/ExerciceSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Exercice;
use Carbon\Carbon;

class ExerciceSeeder extends Seeder
{
    public function run()
    {
        // Créer l'exercice 2024 comme actif
        Exercice::create([
            'libelle' => 'Exercice 2024',
            'date_debut' => '2024-01-01',
            'date_fin' => '2024-12-31',
            'statut' => 1, // Actif
        ]);

        // Créer l'exercice 2023 comme fermé
        Exercice::create([
            'libelle' => 'Exercice 2025',
            'date_debut' => '2025-01-01',
            'date_fin' => '2025-12-31',
            'statut' => 0, // Fermé
        ]);
    }
}