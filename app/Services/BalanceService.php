<?php

namespace App\Services;

use App\Models\GrandLivre;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use App\Models\Balance;
use Carbon\Carbon;

class BalanceService
{
    public function genererBalance($entrepriseId, $dateDebut, $dateFin)
    {
        // Vérifier si la balance existe déjà pour cette période
        $balanceExistante = Balance::where('entreprise_id', $entrepriseId)
            ->where('periode_debut', $dateDebut)
            ->where('periode_fin', $dateFin)
            ->first();
        
        if ($balanceExistante) {
            return $balanceExistante;
        }
        
        // Récupérer tous les comptes de l'entreprise
        $comptes = NewAccount::where('entreprise_id', $entrepriseId)
            ->whereHas('mappings')
            ->get();
        
        $balances = [];
        
        foreach ($comptes as $compte) {
            // Récupérer les anciens comptes mappés
            $oldAccountIds = AccountMapping::where('new_account_id', $compte->id)
                ->where('entreprise_id', $entrepriseId)
                ->pluck('old_account_id')
                ->toArray();
            
            if (empty($oldAccountIds)) {
                continue;
            }
            
            // Calculer les mouvements
            $ecritures = GrandLivre::where('entreprise_id', $entrepriseId)
                ->whereIn('old_account_id', $oldAccountIds)
                ->whereBetween('date_ecriture', [$dateDebut, $dateFin])
                ->where('validated', true)
                ->get();
            
            $mouvementDebit = $ecritures->sum('debit');
            $mouvementCredit = $ecritures->sum('credit');
            
            // Calculer le solde
            $premierChiffre = substr($compte->code, 0, 1);
            
            if (in_array($premierChiffre, ['2', '3', '6'])) {
                // Comptes de débit (actif, charges)
                $soldeDebiteur = max(0, $mouvementDebit - $mouvementCredit);
                $soldeCrediteur = max(0, $mouvementCredit - $mouvementDebit);
            } else {
                // Comptes de crédit (passif, produits)
                $soldeCrediteur = max(0, $mouvementCredit - $mouvementDebit);
                $soldeDebiteur = max(0, $mouvementDebit - $mouvementCredit);
            }
            
            // Créer la balance
            $balance = Balance::create([
                'entreprise_id' => $entrepriseId,
                'compte_code' => $compte->code,
                'compte_libelle' => $compte->libelle,
                'periode_debut' => $dateDebut,
                'periode_fin' => $dateFin,
                'solde_debiteur' => $soldeDebiteur,
                'solde_crediteur' => $soldeCrediteur,
                'mouvement_debit' => $mouvementDebit,
                'mouvement_credit' => $mouvementCredit
            ]);
            
            $balances[] = $balance;
        }
        
        return $balances;
    }
    
    public function getSoldesBilan($entrepriseId, $dateDebut, $dateFin)
    {
        // Générer ou récupérer la balance
        $this->genererBalance($entrepriseId, $dateDebut, $dateFin);
        
        // Récupérer tous les soldes des comptes de bilan (1-5)
        $soldes = Balance::where('entreprise_id', $entrepriseId)
            ->where('periode_debut', $dateDebut)
            ->where('periode_fin', $dateFin)
            ->where(function($query) {
                $query->where('compte_code', 'like', '1%')
                      ->orWhere('compte_code', 'like', '2%')
                      ->orWhere('compte_code', 'like', '3%')
                      ->orWhere('compte_code', 'like', '4%')
                      ->orWhere('compte_code', 'like', '5%');
            })
            ->get()
            ->mapWithKeys(function($balance) {
                // Déterminer le solde net selon la nature du compte
                $premierChiffre = substr($balance->compte_code, 0, 1);
                
                if (in_array($premierChiffre, ['2', '3', '5'])) {
                    // Comptes d'actif : solde débiteur positif
                    $solde = $balance->solde_debiteur - $balance->solde_crediteur;
                } elseif ($premierChiffre === '1') {
                    // Comptes de passif : solde créditeur positif
                    $solde = $balance->solde_crediteur - $balance->solde_debiteur;
                } elseif ($premierChiffre === '4') {
                    // Comptes de tiers : besoin de savoir si c'est créance ou dette
                    // Par défaut, solde débiteur (créance)
                    $solde = $balance->solde_debiteur - $balance->solde_crediteur;
                } else {
                    $solde = $balance->solde_debiteur - $balance->solde_crediteur;
                }
                
                return [$balance->compte_code => $solde];
            })
            ->toArray();
        
        return $soldes;
    }
    
        // Dans BalanceService.php
    public function getSoldesPourBilan($entrepriseId, $dateDebut, $dateFin)
    {
        $balances = Balance::where('entreprise_id', $entrepriseId)
            ->where('periode_debut', '>=', $dateDebut)
            ->where('periode_fin', '<=', $dateFin)
            ->get();
        
        $soldes = [];
        
        foreach ($balances as $balance) {
            $code = $balance->compte_code;
            
            // Pour le bilan, on veut le solde net (débit - crédit)
            $soldeNet = $balance->solde_debiteur - $balance->solde_crediteur;
            
            // On garde le signe tel quel, le composant Bilan gérera la logique
            $soldes[$code] = $soldeNet;
        }
        
        return $soldes;
    }
}