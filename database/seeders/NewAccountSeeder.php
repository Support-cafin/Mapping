<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;
use App\Models\NewAccount;

class NewAccountSeeder extends Seeder
{
    public function run(): void
    {
        $entreprises = Entreprise::all();

        foreach ($entreprises as $entreprise) {
            // Structure hiérarchique SYSCOHADA/IFRS
            $classes = [
                [
                    'code' => '1',
                    'intitule' => 'CAPITAL ET RESERVES',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '10', 'intitule' => 'CAPITAL', 'niveau' => 2],
                        ['code' => '11', 'intitule' => 'RESERVES', 'niveau' => 2],
                        ['code' => '12', 'intitule' => 'RESULTAT', 'niveau' => 2],
                        ['code' => '13', 'intitule' => 'SUBVENTIONS D\'INVESTISSEMENT', 'niveau' => 2],
                        ['code' => '14', 'intitule' => 'PROVISIONS REGLEMENTEES', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '2',
                    'intitule' => 'IMMOBILISATIONS',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '20', 'intitule' => 'IMMOBILISATIONS INCORPORELLES', 'niveau' => 2],
                        ['code' => '21', 'intitule' => 'IMMOBILISATIONS CORPORELLES', 'niveau' => 2],
                        ['code' => '22', 'intitule' => 'IMMOBILISATIONS FINANCIERES', 'niveau' => 2],
                        ['code' => '28', 'intitule' => 'AMORTISSEMENTS DES IMMOBILISATIONS', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '3',
                    'intitule' => 'STOCKS',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '30', 'intitule' => 'MARCHANDISES', 'niveau' => 2],
                        ['code' => '31', 'intitule' => 'MATIERES PREMIERES', 'niveau' => 2],
                        ['code' => '32', 'intitule' => 'PRODUITS INTERMEDIAIRES', 'niveau' => 2],
                        ['code' => '33', 'intitule' => 'PRODUITS FINIS', 'niveau' => 2],
                        ['code' => '34', 'intitule' => 'EN-COURS DE PRODUCTION', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '4',
                    'intitule' => 'TIERS',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '40', 'intitule' => 'FOURNISSEURS', 'niveau' => 2],
                        ['code' => '41', 'intitule' => 'CLIENTS', 'niveau' => 2],
                        ['code' => '42', 'intitule' => 'PERSONNEL', 'niveau' => 2],
                        ['code' => '43', 'intitule' => 'ORGANISMES SOCIAUX', 'niveau' => 2],
                        ['code' => '44', 'intitule' => 'ETAT', 'niveau' => 2],
                        ['code' => '45', 'intitule' => 'GROUPES ET ASSOCIES', 'niveau' => 2],
                        ['code' => '46', 'intitule' => 'DEBITEURS ET CREDITEURS DIVERS', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '5',
                    'intitule' => 'FINANCES',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '50', 'intitule' => 'VALEURS MOBILIERES DE PLACEMENT', 'niveau' => 2],
                        ['code' => '51', 'intitule' => 'BANQUES', 'niveau' => 2],
                        ['code' => '52', 'intitule' => 'CAISSE', 'niveau' => 2],
                        ['code' => '53', 'intitule' => 'TRESORERIE', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '6',
                    'intitule' => 'CHARGES',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '60', 'intitule' => 'ACHATS', 'niveau' => 2],
                        ['code' => '61', 'intitule' => 'SERVICES EXTERIEURS', 'niveau' => 2],
                        ['code' => '62', 'intitule' => 'AUTRES SERVICES EXTERIEURS', 'niveau' => 2],
                        ['code' => '63', 'intitule' => 'IMPOTS ET TAXES', 'niveau' => 2],
                        ['code' => '64', 'intitule' => 'CHARGES DE PERSONNEL', 'niveau' => 2],
                        ['code' => '65', 'intitule' => 'CHARGES FINANCIERES', 'niveau' => 2],
                        ['code' => '66', 'intitule' => 'CHARGES EXCEPTIONNELLES', 'niveau' => 2],
                        ['code' => '67', 'intitule' => 'DOTATIONS AUX AMORTISSEMENTS', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '7',
                    'intitule' => 'PRODUITS',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '70', 'intitule' => 'VENTES', 'niveau' => 2],
                        ['code' => '71', 'intitule' => 'PRODUCTION STOCKEE', 'niveau' => 2],
                        ['code' => '72', 'intitule' => 'PRODUCTION IMMOBILISEE', 'niveau' => 2],
                        ['code' => '73', 'intitule' => 'SUBVENTIONS D\'EXPLOITATION', 'niveau' => 2],
                        ['code' => '74', 'intitule' => 'PRODUITS DIVERS', 'niveau' => 2],
                        ['code' => '75', 'intitule' => 'PRODUITS FINANCIERS', 'niveau' => 2],
                        ['code' => '76', 'intitule' => 'PRODUITS EXCEPTIONNELS', 'niveau' => 2],
                    ]
                ],
                [
                    'code' => '8',
                    'intitule' => 'COMPTES SPECIAUX',
                    'niveau' => 1,
                    'children' => [
                        ['code' => '80', 'intitule' => 'ENGAGEMENTS HORS BILAN', 'niveau' => 2],
                        ['code' => '81', 'intitule' => 'COMPTES DE REGULARISATION', 'niveau' => 2],
                    ]
                ],
            ];

            foreach ($classes as $classe) {
                // Créer la classe parent
                $parent = NewAccount::create([
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
                    NewAccount::create([
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

            // Ajouter des comptes détaillés spécifiques pour le mapping
            $detailedAccounts = [
                // Sous 21 Immobilisations corporelles
                ['code' => '211', 'intitule' => 'Terrains', 'classe' => '2', 'groupe' => '21', 'parent_code' => '21'],
                ['code' => '212', 'intitule' => 'Constructions', 'classe' => '2', 'groupe' => '21', 'parent_code' => '21'],
                ['code' => '213', 'intitule' => 'Matériel de transport', 'classe' => '2', 'groupe' => '21', 'parent_code' => '21'],
                ['code' => '214', 'intitule' => 'Matériel industriel', 'classe' => '2', 'groupe' => '21', 'parent_code' => '21'],
                ['code' => '215', 'intitule' => 'Mobilier et matériel de bureau', 'classe' => '2', 'groupe' => '21', 'parent_code' => '21'],
                
                // Sous 28 Amortissements
                ['code' => '281', 'intitule' => 'Amortissements terrains', 'classe' => '2', 'groupe' => '28', 'parent_code' => '28'],
                ['code' => '282', 'intitule' => 'Amortissements constructions', 'classe' => '2', 'groupe' => '28', 'parent_code' => '28'],
                ['code' => '283', 'intitule' => 'Amortissements matériel de transport', 'classe' => '2', 'groupe' => '28', 'parent_code' => '28'],
                
                // Sous 40 Fournisseurs
                ['code' => '401', 'intitule' => 'Fournisseurs - achats de biens', 'classe' => '4', 'groupe' => '40', 'parent_code' => '40'],
                ['code' => '402', 'intitule' => 'Fournisseurs - achats de services', 'classe' => '4', 'groupe' => '40', 'parent_code' => '40'],
                
                // Sous 41 Clients
                ['code' => '411', 'intitule' => 'Clients - ventes de biens', 'classe' => '4', 'groupe' => '41', 'parent_code' => '41'],
                ['code' => '412', 'intitule' => 'Clients - ventes de services', 'classe' => '4', 'groupe' => '41', 'parent_code' => '41'],
                
                // Sous 51 Banques
                ['code' => '511', 'intitule' => 'Banque', 'classe' => '5', 'groupe' => '51', 'parent_code' => '51'],
                ['code' => '512', 'intitule' => 'Compte courant', 'classe' => '5', 'groupe' => '51', 'parent_code' => '51'],
                ['code' => '513', 'intitule' => 'Compte épargne', 'classe' => '5', 'groupe' => '51', 'parent_code' => '51'],
                
                // Sous 60 Achats
                ['code' => '601', 'intitule' => 'Achats de marchandises', 'classe' => '6', 'groupe' => '60', 'parent_code' => '60'],
                ['code' => '602', 'intitule' => 'Achats de matières premières', 'classe' => '6', 'groupe' => '60', 'parent_code' => '60'],
                ['code' => '603', 'intitule' => 'Achats non stockés', 'classe' => '6', 'groupe' => '60', 'parent_code' => '60'],
                
                // Sous 70 Ventes
                ['code' => '701', 'intitule' => 'Ventes de marchandises', 'classe' => '7', 'groupe' => '70', 'parent_code' => '70'],
                ['code' => '702', 'intitule' => 'Ventes de produits finis', 'classe' => '7', 'groupe' => '70', 'parent_code' => '70'],
                ['code' => '703', 'intitule' => 'Ventes de services', 'classe' => '7', 'groupe' => '70', 'parent_code' => '70'],
            ];

            foreach ($detailedAccounts as $detail) {
                $parent = NewAccount::where('entreprise_id', $entreprise->id)
                    ->where('code', $detail['parent_code'])
                    ->first();
                    
                if ($parent) {
                    NewAccount::create([
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