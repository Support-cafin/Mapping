<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;
use App\Models\OldAccount;

class OldAccountSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = Entreprise::all();

        foreach ($entreprises as $entreprise) {
            // Structure hiérarchique des classes
            $classes = [
                [
                    'code' => '1',
                    'intitule' => 'Comptes de capitaux',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '10', 'intitule' => 'Capital et réserves', 'niveau' => 2],
                        ['code' => '11', 'intitule' => 'Report à nouveau', 'niveau' => 2],
                        ['code' => '12', 'intitule' => 'Résultat de l\'exercice', 'niveau' => 2],
                        ['code' => '13', 'intitule' => 'Subventions d\'investissement', 'niveau' => 2],
                        ['code' => '14', 'intitule' => 'Provisions réglementées', 'niveau' => 2],
                        ['code' => '15', 'intitule' => 'Emprunts et dettes assimilées', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '2',
                    'intitule' => 'Comptes d\'immobilisations',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '20', 'intitule' => 'Immobilisations incorporelles', 'niveau' => 2],
                        ['code' => '21', 'intitule' => 'Immobilisations corporelles', 'niveau' => 2],
                        ['code' => '22', 'intitule' => 'Terrains', 'niveau' => 2],
                        ['code' => '23', 'intitule' => 'Constructions', 'niveau' => 2],
                        ['code' => '24', 'intitule' => 'Matériel industriel', 'niveau' => 2],
                        ['code' => '25', 'intitule' => 'Matériel de transport', 'niveau' => 2],
                        ['code' => '26', 'intitule' => 'Mobilier et matériel de bureau', 'niveau' => 2],
                        ['code' => '27', 'intitule' => 'Immobilisations en cours', 'niveau' => 2],
                        ['code' => '28', 'intitule' => 'Amortissements', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '3',
                    'intitule' => 'Comptes de stocks et en-cours',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '30', 'intitule' => 'Marchandises', 'niveau' => 2],
                        ['code' => '31', 'intitule' => 'Matières premières', 'niveau' => 2],
                        ['code' => '32', 'intitule' => 'Autres approvisionnements', 'niveau' => 2],
                        ['code' => '33', 'intitule' => 'En-cours de production', 'niveau' => 2],
                        ['code' => '34', 'intitule' => 'Produits intermédiaires', 'niveau' => 2],
                        ['code' => '35', 'intitule' => 'Produits finis', 'niveau' => 2],
                        ['code' => '37', 'intitule' => 'Stocks de marchandises', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '4',
                    'intitule' => 'Comptes de tiers',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '40', 'intitule' => 'Fournisseurs', 'niveau' => 2],
                        ['code' => '41', 'intitule' => 'Clients', 'niveau' => 2],
                        ['code' => '42', 'intitule' => 'Personnel', 'niveau' => 2],
                        ['code' => '43', 'intitule' => 'Sécurité sociale', 'niveau' => 2],
                        ['code' => '44', 'intitule' => 'État', 'niveau' => 2],
                        ['code' => '45', 'intitule' => 'Groupe et associés', 'niveau' => 2],
                        ['code' => '46', 'intitule' => 'Débiteurs et créditeurs divers', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '5',
                    'intitule' => 'Comptes financiers',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '50', 'intitule' => 'Valeurs mobilières de placement', 'niveau' => 2],
                        ['code' => '51', 'intitule' => 'Banques', 'niveau' => 2],
                        ['code' => '52', 'intitule' => 'Instruments de trésorerie', 'niveau' => 2],
                        ['code' => '53', 'intitule' => 'Caisse', 'niveau' => 2],
                        ['code' => '54', 'intitule' => 'Règlements en cours', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '6',
                    'intitule' => 'Comptes de charges',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '60', 'intitule' => 'Achats', 'niveau' => 2],
                        ['code' => '61', 'intitule' => 'Services extérieurs', 'niveau' => 2],
                        ['code' => '62', 'intitule' => 'Autres services extérieurs', 'niveau' => 2],
                        ['code' => '63', 'intitule' => 'Impôts, taxes et versements assimilés', 'niveau' => 2],
                        ['code' => '64', 'intitule' => 'Charges de personnel', 'niveau' => 2],
                        ['code' => '65', 'intitule' => 'Autres charges de gestion courante', 'niveau' => 2],
                        ['code' => '66', 'intitule' => 'Charges financières', 'niveau' => 2],
                        ['code' => '67', 'intitule' => 'Charges exceptionnelles', 'niveau' => 2],
                        ['code' => '68', 'intitule' => 'Dotations aux amortissements', 'niveau' => 2],
                        ['code' => '69', 'intitule' => 'Impôts sur les bénéfices', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '7',
                    'intitule' => 'Comptes de produits',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '70', 'intitule' => 'Ventes', 'niveau' => 2],
                        ['code' => '71', 'intitule' => 'Production stockée', 'niveau' => 2],
                        ['code' => '72', 'intitule' => 'Production immobilisée', 'niveau' => 2],
                        ['code' => '73', 'intitule' => 'Subventions d\'exploitation', 'niveau' => 2],
                        ['code' => '74', 'intitule' => 'Produits accessoires', 'niveau' => 2],
                        ['code' => '75', 'intitule' => 'Autres produits de gestion courante', 'niveau' => 2],
                        ['code' => '76', 'intitule' => 'Produits financiers', 'niveau' => 2],
                        ['code' => '77', 'intitule' => 'Produits exceptionnels', 'niveau' => 2],
                        ['code' => '78', 'intitule' => 'Reprises sur amortissements', 'niveau' => 2],
                    ]
                ],
            ];

            foreach ($classes as $classe) {
                // Créer la classe parent
                $parent = OldAccount::create([
                    'entreprise_id' => $entreprise->id,
                    'code' => $classe['code'],
                    'intitule' => $classe['intitule'],
                    'classe' => $classe['code'],
                    'groupe' => null,
                    'niveau' => $classe['niveau'],
                    'parent_id' => null,
                ]);

                // Créer les sous-comptes
                foreach ($classe['children'] as $child) {
                    OldAccount::create([
                        'entreprise_id' => $entreprise->id,
                        'code' => $child['code'],
                        'intitule' => $child['intitule'],
                        'classe' => $classe['code'],
                        'groupe' => $child['code'],
                        'niveau' => $child['niveau'],
                        'parent_id' => $parent->id,
                    ]);
                }
            }

            // Ajouter quelques comptes détaillés pour tester
            $detailedAccounts = [
                // Sous 101 Capital
                ['code' => '1011', 'intitule' => 'Capital souscrit - non appelé', 'classe' => '1', 'groupe' => '10', 'parent_code' => '10'],
                ['code' => '1012', 'intitule' => 'Capital souscrit - appelé, non versé', 'classe' => '1', 'groupe' => '10', 'parent_code' => '10'],
                
                // Sous 221 Terrains
                ['code' => '2211', 'intitule' => 'Terrains nus', 'classe' => '2', 'groupe' => '22', 'parent_code' => '22'],
                ['code' => '2212', 'intitule' => 'Terrains aménagés', 'classe' => '2', 'groupe' => '22', 'parent_code' => '22'],
                
                // Sous 222 Constructions
                ['code' => '2221', 'intitule' => 'Bâtiments administratifs', 'classe' => '2', 'groupe' => '22', 'parent_code' => '22'],
                ['code' => '2222', 'intitule' => 'Bâtiments industriels', 'classe' => '2', 'groupe' => '22', 'parent_code' => '22'],
                
                // Sous 401 Fournisseurs
                ['code' => '4011', 'intitule' => 'Fournisseurs - achats de biens', 'classe' => '4', 'groupe' => '40', 'parent_code' => '40'],
                ['code' => '4012', 'intitule' => 'Fournisseurs - achats de services', 'classe' => '4', 'groupe' => '40', 'parent_code' => '40'],
                
                // Sous 411 Clients
                ['code' => '4111', 'intitule' => 'Clients - ventes de biens', 'classe' => '4', 'groupe' => '41', 'parent_code' => '41'],
                ['code' => '4112', 'intitule' => 'Clients - ventes de services', 'classe' => '4', 'groupe' => '41', 'parent_code' => '41'],
                
                // Sous 511 Banque
                ['code' => '5111', 'intitule' => 'Banque X - compte courant', 'classe' => '5', 'groupe' => '51', 'parent_code' => '51'],
                ['code' => '5112', 'intitule' => 'Banque Y - compte épargne', 'classe' => '5', 'groupe' => '51', 'parent_code' => '51'],
                
                // Sous 601 Achats
                ['code' => '6011', 'intitule' => 'Achats de marchandises', 'classe' => '6', 'groupe' => '60', 'parent_code' => '60'],
                ['code' => '6012', 'intitule' => 'Achats de matières premières', 'classe' => '6', 'groupe' => '60', 'parent_code' => '60'],
                ['code' => '6021', 'intitule' => 'Achats non stockés', 'classe' => '6', 'groupe' => '60', 'parent_code' => '60'],
            ];

            foreach ($detailedAccounts as $detail) {
                $parent = OldAccount::where('entreprise_id', $entreprise->id)
                    ->where('code', $detail['parent_code'])
                    ->first();
                    
                if ($parent) {
                    OldAccount::create([
                        'entreprise_id' => $entreprise->id,
                        'code' => $detail['code'],
                        'intitule' => $detail['intitule'],
                        'classe' => $detail['classe'],
                        'groupe' => $detail['groupe'],
                        'niveau' => 3,
                        'parent_id' => $parent->id,
                    ]);
                }
            }
        }
    }
}