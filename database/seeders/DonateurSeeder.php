<?php

namespace Database\Seeders;

use App\Models\Donateur;
use Illuminate\Database\Seeder;

class DonateurSeeder extends Seeder
{
    public function run()
    {
        $donateurs = [
            [
                'numero_enregistrement' => 'DON-2024-001',
                'date' => '2024-01-18',
                'denomination' => 'Comunità Impegno Servizio Volontariato (CISV)',
                'numero_identification_fiscal' => '10132',
                'adresse_siege_social' => 'Corso Chieri 121/6 | 10132 | Torino (Italy)',
                'email' => json_encode(['s.fischetti@cisvto.org', 'contaprog@cisvto.org']),
                'montant_don' => 4687469,
                'mode_liberation' => 'virement',
                'signature_representant' => true,
            ],
            // Ajoutez les autres enregistrements...
        ];

        foreach ($donateurs as $donateur) {
            Donateur::create($donateur);
        }
    }
}