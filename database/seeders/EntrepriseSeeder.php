<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;

class EntrepriseSeeder extends Seeder
{
    public function run(): void
    {
        Entreprise::create([
            'nom' => 'CAFIN ONG',
            'code' => 'CAFIN',
            'adresse' => 'Ouagadougou',
            'telephone' => '+22670101010',
            'email' => 'contact@cafin.org',
        ]);

        Entreprise::create([
            'nom' => 'AIDE INTERNATIONALE',
            'code' => 'AIDE-INT',
            'adresse' => 'Bobo-Dioulasso',
            'telephone' => '+22660112233',
            'email' => 'contact@aideint.org',
        ]);

        Entreprise::create([
            'nom' => 'GULMU DEV',
            'code' => 'GULMU',
            'adresse' => 'Fada N’Gourma',
            'telephone' => '+22676118877',
            'email' => 'contact@gulmudev.bf',
        ]);
    }
}
