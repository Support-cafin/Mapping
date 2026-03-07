<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Entreprise;
use App\Models\Journal;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\GrandLivre;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GrandLivreSeeder extends Seeder
{
    private $faker;
    private $exercice;
    
    public function __construct()
    {
        $this->faker = \Faker\Factory::create('fr_FR');
        $this->exercice = date('Y');
    }
    
    public function run(): void
    {
        $entreprises = Entreprise::all();
        
        foreach ($entreprises as $entreprise) {
            $this->createGrandLivreForEntreprise($entreprise);
        }
        
        $this->command->info('Grand Livre seedé avec succès !');
        $this->command->info('Totaux générés :');
        $this->command->info('- ' . GrandLivre::count() . ' écritures');
        $this->command->info('- ' . DB::table('grand_livres')->sum('debit') . ' total débit');
        $this->command->info('- ' . DB::table('grand_livres')->sum('credit') . ' total crédit');
    }
    
    private function createGrandLivreForEntreprise($entreprise): void
    {
        $this->command->info("Création du Grand Livre pour {$entreprise->nom}...");
        
        // Récupérer les journaux et comptes de l'entreprise
        $journaux = Journal::where('entreprise_id', $entreprise->id)->actif()->get();
        $oldAccounts = OldAccount::where('entreprise_id', $entreprise->id)->get();
        $newAccounts = NewAccount::where('entreprise_id', $entreprise->id)->get();
        
        if ($journaux->isEmpty() || $oldAccounts->isEmpty()) {
            $this->command->warn("Pas de journaux ou comptes pour {$entreprise->nom}, skipping...");
            return;
        }
        
        // Créer les écritures
        $ecritures = [];
        
        // 1. Écritures d'ouverture (janvier)
        $ecritures = array_merge($ecritures, $this->createEcrituresOuverture($entreprise, $journaux, $oldAccounts, $newAccounts));
        
        // 2. Écritures régulières (toute l'année)
        $ecritures = array_merge($ecritures, $this->createEcrituresRegulieres($entreprise, $journaux, $oldAccounts, $newAccounts));
        
        // 3. Écritures de clôture (décembre)
        $ecritures = array_merge($ecritures, $this->createEcrituresCloture($entreprise, $journaux, $oldAccounts, $newAccounts));
        
        // Insérer en batch
        $chunks = array_chunk($ecritures, 100);
        foreach ($chunks as $chunk) {
            GrandLivre::insert($chunk);
        }
        
        // Calculer les soldes cumulés
        $this->calculateSoldes($entreprise);
    }
    
    private function createEcrituresOuverture($entreprise, $journaux, $oldAccounts, $newAccounts): array
    {
        $ecritures = [];
        $dateBase = Carbon::create($this->exercice, 1, 1);
        
        // Écritures d'ouverture typiques
        $ouvertures = [
            ['compte' => '1000', 'libelle' => 'Apport des associés', 'debit' => 5000000, 'credit' => 0],
            ['compte' => '2000', 'libelle' => 'Acquisition matériel informatique', 'debit' => 1500000, 'credit' => 0],
            ['compte' => '3000', 'libelle' => 'Emprunt bancaire', 'debit' => 0, 'credit' => 2000000],
            ['compte' => '512', 'libelle' => 'Banque - compte courant', 'debit' => 4500000, 'credit' => 0],
        ];
        
        foreach ($ouvertures as $ouverture) {
            $oldAccount = $oldAccounts->firstWhere('code', $ouverture['compte']);
            if (!$oldAccount) continue;
            
            $newAccount = $oldAccount->mappings()->first()?->newAccount;
            
            $ecritures[] = [
                'entreprise_id' => $entreprise->id,
                'journal_id' => $journaux->where('code', 'OD')->first()->id,
                'old_account_id' => $oldAccount->id,
                'new_account_id' => $newAccount?->id,
                'date_ecriture' => $dateBase->copy()->addDays(rand(1, 5)),
                'piece' => 'OUV-' . str_pad(rand(1, 50), 3, '0', STR_PAD_LEFT),
                'libelle' => $ouverture['libelle'] . ' - Ouverture ' . $this->exercice,
                'debit' => $ouverture['debit'],
                'credit' => $ouverture['credit'],
                'exercice' => $this->exercice,
                'mois' => 1,
                'trimestre' => 1,
                'source' => 'seeder',
                'validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return $ecritures;
    }
    
    private function createEcrituresRegulieres($entreprise, $journaux, $oldAccounts, $newAccounts): array
    {
        $ecritures = [];
        $nombreEcritures = 200; // Nombre d'écritures à générer
        
        // Types d'écritures avec probabilités
        $typesEcritures = [
            'achat' => ['journal' => 'ACH', 'frequence' => 0.25],
            'vente' => ['journal' => 'VTE', 'frequence' => 0.20],
            'banque' => ['journal' => 'BNQ', 'frequence' => 0.15],
            'caisse' => ['journal' => 'CAI', 'frequence' => 0.10],
            'divers' => ['journal' => 'OD', 'frequence' => 0.30],
        ];
        
        // Comptes par type
        $comptesParType = [
            'achat' => ['6000', '6010', '6020', '6070', '6080'],
            'vente' => ['7000', '7010', '7020', '7030'],
            'banque' => ['512', '513', '514'],
            'caisse' => ['530', '531'],
            'divers' => ['4000', '4010', '4280', '4400', '4500', '4600', '4800'],
        ];
        
        // Libellés par type
        $libellesAchats = [
            'Achat fournitures bureau',
            'Facture téléphone',
            'Abonnement logiciel',
            'Frais de déplacement',
            'Honoraires expert-comptable',
            'Assurance véhicule',
            'Entretien matériel',
            'Publicité et communication',
        ];
        
        $libellesVentes = [
            'Vente produit A',
            'Facture client B',
            'Prestation de service',
            'Contrat maintenance',
            'Location équipement',
            'Formation professionnelle',
        ];
        
        $libellesBanque = [
            'Virement reçu',
            'Prélèvement EDF',
            'Frais bancaires',
            'Remboursement emprunt',
            'Dépôt espèces',
            'Chèque émis',
        ];
        
        for ($i = 0; $i < $nombreEcritures; $i++) {
            // Choisir un type aléatoire
            $type = $this->getRandomType($typesEcritures);
            $journal = $journaux->firstWhere('code', $typesEcritures[$type]['journal']);
            
            if (!$journal) continue;
            
            // Choisir un compte aléatoire pour ce type
            $compteCode = $this->faker->randomElement($comptesParType[$type]);
            $oldAccount = $oldAccounts->firstWhere('code', $compteCode);
            
            if (!$oldAccount) {
                // Essayer un compte aléatoire si le compte spécifique n'existe pas
                $oldAccount = $oldAccounts->random();
            }
            
            $newAccount = $oldAccount->mappings()->first()?->newAccount;
            
            // Déterminer débit/crédit selon le type
            $isDebit = in_array($type, ['achat', 'divers']);
            $montant = $this->generateMontant($type);
            
            // Générer une date dans l'année
            $date = Carbon::create($this->exercice, rand(1, 12), rand(1, 28));
            
            // Générer le libellé
            $libelle = $this->generateLibelle($type, $libellesAchats, $libellesVentes, $libellesBanque);
            
            // Numéro de pièce
            $piece = $this->generatePieceNumero($type, $i);
            
            $ecritures[] = [
                'entreprise_id' => $entreprise->id,
                'journal_id' => $journal->id,
                'old_account_id' => $oldAccount->id,
                'new_account_id' => $newAccount?->id,
                'date_ecriture' => $date,
                'piece' => $piece,
                'libelle' => $libelle,
                'debit' => $isDebit ? $montant : 0,
                'credit' => !$isDebit ? $montant : 0,
                'exercice' => $this->exercice,
                'mois' => $date->month,
                'trimestre' => ceil($date->month / 3),
                'source' => 'seeder',
                'validated' => true,
                'lettre' => $this->generateLettre($i),
                'notes' => $this->faker->optional(0.3)->sentence(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Créer l'écriture de contrepartie (pour équilibrer)
            if ($i % 2 == 0) {
                $contrepartie = $this->createContrepartie(
                    $entreprise, $journal, $oldAccounts, $newAccounts, 
                    $date, $montant, $isDebit, $libelle, $piece
                );
                if ($contrepartie) {
                    $ecritures[] = $contrepartie;
                }
            }
        }
        
        return $ecritures;
    }
    
    private function createEcrituresCloture($entreprise, $journaux, $oldAccounts, $newAccounts): array
    {
        $ecritures = [];
        $dateCloture = Carbon::create($this->exercice, 12, 31);
        
        // Écritures de clôture typiques
        $clotures = [
            ['compte' => '1200', 'libelle' => 'Report bénéfice', 'debit' => 0, 'credit' => 750000],
            ['compte' => '1290', 'libelle' => 'Report bénéfice', 'debit' => 750000, 'credit' => 0],
            ['compte' => '2800', 'libelle' => 'Dotation amortissement', 'debit' => 300000, 'credit' => 0],
            ['compte' => '6810', 'libelle' => 'Dotation amortissement', 'debit' => 0, 'credit' => 300000],
        ];
        
        foreach ($clotures as $cloture) {
            $oldAccount = $oldAccounts->firstWhere('code', $cloture['compte']);
            if (!$oldAccount) continue;
            
            $newAccount = $oldAccount->mappings()->first()?->newAccount;
            
            $ecritures[] = [
                'entreprise_id' => $entreprise->id,
                'journal_id' => $journaux->where('code', 'OD')->first()->id,
                'old_account_id' => $oldAccount->id,
                'new_account_id' => $newAccount?->id,
                'date_ecriture' => $dateCloture,
                'piece' => 'CLO-' . str_pad(rand(1, 20), 3, '0', STR_PAD_LEFT),
                'libelle' => $cloture['libelle'] . ' - Clôture ' . $this->exercice,
                'debit' => $cloture['debit'],
                'credit' => $cloture['credit'],
                'exercice' => $this->exercice,
                'mois' => 12,
                'trimestre' => 4,
                'source' => 'seeder',
                'validated' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        
        return $ecritures;
    }
    
    private function createContrepartie($entreprise, $journal, $oldAccounts, $newAccounts, $date, $montant, $isDebit, $libelle, $piece): ?array
    {
        // Pour les achats, contrepartie en banque ou fournisseurs
        if ($journal->code === 'ACH') {
            $compteContrepartie = $this->faker->randomElement(['4010', '512']);
            $oldAccountContre = $oldAccounts->firstWhere('code', $compteContrepartie);
            
            if ($oldAccountContre) {
                $newAccountContre = $oldAccountContre->mappings()->first()?->newAccount;
                
                return [
                    'entreprise_id' => $entreprise->id,
                    'journal_id' => $journal->id,
                    'old_account_id' => $oldAccountContre->id,
                    'new_account_id' => $newAccountContre?->id,
                    'date_ecriture' => $date,
                    'piece' => $piece,
                    'libelle' => $libelle . ' (contrepartie)',
                    'debit' => !$isDebit ? $montant : 0,
                    'credit' => $isDebit ? $montant : 0,
                    'exercice' => $this->exercice,
                    'mois' => $date->month,
                    'trimestre' => ceil($date->month / 3),
                    'source' => 'seeder',
                    'validated' => true,
                    'lettre' => $this->generateLettre(rand(1000, 9999)),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }
        
        return null;
    }
    
    private function calculateSoldes($entreprise): void
    {
        $this->command->info("Calcul des soldes pour {$entreprise->nom}...");
        
        // Récupérer les écritures triées par date
        $ecritures = GrandLivre::where('entreprise_id', $entreprise->id)
            ->orderBy('date_ecriture')
            ->orderBy('id')
            ->get();
        
        $soldesCumules = [];
        
        foreach ($ecritures as $ecriture) {
            $accountKey = $ecriture->old_account_id ?? 'new_' . $ecriture->new_account_id;
            
            if (!isset($soldesCumules[$accountKey])) {
                $soldesCumules[$accountKey] = 0;
            }
            
            $soldesCumules[$accountKey] += ($ecriture->debit - $ecriture->credit);
            
            // Mettre à jour le solde dans la base
            $ecriture->update(['solde' => $soldesCumules[$accountKey]]);
        }
    }
    
    private function getRandomType($typesEcritures): string
    {
        $rand = mt_rand() / mt_getrandmax();
        $cumulative = 0;
        
        foreach ($typesEcritures as $type => $details) {
            $cumulative += $details['frequence'];
            if ($rand <= $cumulative) {
                return $type;
            }
        }
        
        return 'divers';
    }
    
    private function generateMontant($type): float
    {
        $min = 10000;
        $max = 500000;
        
        switch ($type) {
            case 'achat':
                $min = 5000;
                $max = 200000;
                break;
            case 'vente':
                $min = 10000;
                $max = 500000;
                break;
            case 'banque':
                $min = 50000;
                $max = 1000000;
                break;
            case 'caisse':
                $min = 1000;
                $max = 50000;
                break;
        }
        
        return round(mt_rand($min, $max) / 100) * 100; // Arrondi à la centaine
    }
    
    private function generateLibelle($type, $libellesAchats, $libellesVentes, $libellesBanque): string
    {
        switch ($type) {
            case 'achat':
                $libelle = $this->faker->randomElement($libellesAchats);
                break;
            case 'vente':
                $libelle = $this->faker->randomElement($libellesVentes);
                $client = $this->faker->company();
                $libelle .= ' - ' . $client;
                break;
            case 'banque':
                $libelle = $this->faker->randomElement($libellesBanque);
                break;
            case 'caisse':
                $libelle = 'Règlement espèces - ' . $this->faker->word();
                break;
            default:
                $libelle = $this->faker->sentence(4);
        }
        
        // Ajouter parfois une référence
        if (mt_rand(1, 3) === 1) {
            $libelle .= ' - Ref: ' . strtoupper($this->faker->bothify('??###'));
        }
        
        return $libelle;
    }
    
    private function generatePieceNumero($type, $index): string
    {
        $prefixes = [
            'achat' => 'FA',
            'vente' => 'FV',
            'banque' => 'VIR',
            'caisse' => 'RC',
            'divers' => 'OD',
        ];
        
        $prefix = $prefixes[$type] ?? 'ECR';
        $numero = str_pad($index + 1, 4, '0', STR_PAD_LEFT);
        
        return $prefix . $this->exercice . $numero;
    }
    
    private function generateLettre($index): ?string
    {
        // 30% des écritures sont lettrées
        if (mt_rand(1, 10) <= 3) {
            $lettres = ['A', 'B', 'C', 'D', 'E'];
            return $lettres[array_rand($lettres)];
        }
        
        return null;
    }
}