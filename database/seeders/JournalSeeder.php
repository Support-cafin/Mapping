<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;
use App\Models\Journal;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = Entreprise::all();
        
        $journauxParDefaut = [
            ['code' => 'ACH', 'intitule' => 'Achats', 'type' => 'Achat'],
            ['code' => 'VTE', 'intitule' => 'Ventes', 'type' => 'Vente'],
            ['code' => 'BNQ', 'intitule' => 'Banque', 'type' => 'Banque'],
            ['code' => 'CAI', 'intitule' => 'Caisse', 'type' => 'Caisse'],
            ['code' => 'OD', 'intitule' => 'Opérations diverses', 'type' => 'Divers'],
            ['code' => 'ANX', 'intitule' => 'Annexes', 'type' => 'Annexe'],
        ];
        
        foreach ($entreprises as $entreprise) {
            foreach ($journauxParDefaut as $journal) {
                Journal::create([
                    'entreprise_id' => $entreprise->id,
                    'code' => $journal['code'],
                    'intitule' => $journal['intitule'],
                    'type' => $journal['type'],
                    'actif' => true,
                    'description' => 'Journal ' . $journal['intitule'] . ' - ' . $entreprise->nom,
                ]);
            }
        }
    }
}