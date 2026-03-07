<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;

class MappingSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = Entreprise::all();

        foreach ($entreprises as $entreprise) {
            // Mapping automatique basé sur les codes/intitulés similaires
            $mappings = [
                ['old_code' => '2211', 'new_code' => '211', 'notes' => 'Correspondance exacte'],
                ['old_code' => '2221', 'new_code' => '212', 'notes' => 'Correspondance exacte'],
                ['old_code' => '4011', 'new_code' => '401', 'notes' => 'Fournisseurs'],
                ['old_code' => '4111', 'new_code' => '411', 'notes' => 'Clients'],
                ['old_code' => '5111', 'new_code' => '511', 'notes' => 'Banque'],
                ['old_code' => '6011', 'new_code' => '601', 'notes' => 'Achats marchandises'],
                ['old_code' => '1011', 'new_code' => '10', 'notes' => 'Capital'],
                ['old_code' => '28', 'new_code' => '28', 'notes' => 'Amortissements'],
            ];

            foreach ($mappings as $mapping) {
                $oldAccount = OldAccount::where('entreprise_id', $entreprise->id)
                    ->where('code', $mapping['old_code'])
                    ->first();
                    
                $newAccount = NewAccount::where('entreprise_id', $entreprise->id)
                    ->where('code', $mapping['new_code'])
                    ->first();
                    
                if ($oldAccount && $newAccount) {
                    AccountMapping::create([
                        'entreprise_id' => $entreprise->id,
                        'old_account_id' => $oldAccount->id,
                        'new_account_id' => $newAccount->id,
                        'coefficient' => 1,
                        'commentaire' => $mapping['notes'],
                    ]);
                }
            }
        }
    }
}
