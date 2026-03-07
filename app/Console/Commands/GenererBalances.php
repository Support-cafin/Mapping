<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BalanceService;
use App\Models\Entreprise;

class GenererBalances extends Command
{
    protected $signature = 'balances:generer {entreprise_id} {date_debut} {date_fin}';
    protected $description = 'Générer les balances à 4 colonnes';
    
    public function handle()
    {
        $entreprise = Entreprise::find($this->argument('entreprise_id'));
        
        if (!$entreprise) {
            $this->error('Entreprise non trouvée');
            return;
        }
        
        $balanceService = new BalanceService();
        $balances = $balanceService->genererBalance(
            $entreprise->id,
            $this->argument('date_debut'),
            $this->argument('date_fin')
        );
        
        $this->info('Balances générées avec succès!');
        $this->table(
            ['Compte', 'Libellé', 'Solde Débiteur', 'Solde Créditeur', 'Mvt Débit', 'Mvt Crédit'],
            $balances->map(function($balance) {
                return [
                    $balance->compte_code,
                    $balance->compte_libelle,
                    number_format($balance->solde_debiteur, 0, ',', ' '),
                    number_format($balance->solde_crediteur, 0, ',', ' '),
                    number_format($balance->mouvement_debit, 0, ',', ' '),
                    number_format($balance->mouvement_credit, 0, ',', ' ')
                ];
            })->toArray()
        );
    }
}