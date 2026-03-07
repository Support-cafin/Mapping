<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FluxTresorerie extends Component
{
    public $entreprise;
    public $dateDebut;
    public $dateFin;
    public $exercice;
    
    public $flux = [];
    public $tresorerie = [];
    
    public function mount(
        $entreprise = null, 
        $dateDebut = null, 
        $dateFin = null, 
        $exercice = null
    ) {
        $this->entreprise = $entreprise ?? auth()->user()->entreprise;
        
        $this->dateDebut = $dateDebut ?? '2024-01-01';
        $this->dateFin = $dateFin ?? '2024-12-31';
        $this->exercice = $exercice ?? 2024;
        
        $this->calculerFlux();
    }
    
    public function updated($property)
    {
        if (in_array($property, ['dateDebut', 'dateFin', 'exercice'])) {
            $this->calculerFlux();
        }
    }
    
    public function calculerFlux()
    {
        // Initialiser les tableaux
        $this->flux = [
            'operationnel' => [
                'encaissements' => 0,
                'decaissements' => 0,
                'net' => 0
            ],
            'investissement' => [
                'acquisitions' => 0,
                'cessions' => 0,
                'net' => 0
            ],
            'financement' => [
                'entrees' => 0,
                'sorties' => 0,
                'net' => 0
            ]
        ];
        
        // Récupérer les données
        $soldes = $this->getSoldesParCompte();
        $mouvements = $this->getMouvementsTresorerie();
        
        // Flux opérationnel (à partir du compte de résultat ajusté)
        // Simplification: utiliser les mouvements des comptes clients/fournisseurs
        $this->flux['operationnel']['encaissements'] = 
            ($soldes['70'] ?? 0) + ($soldes['71'] ?? 0) + ($soldes['74'] ?? 0);
        
        $this->flux['operationnel']['decaissements'] = 
            ($soldes['60'] ?? 0) + ($soldes['61'] ?? 0) + ($soldes['64'] ?? 0);
        
        $this->flux['operationnel']['net'] = 
            $this->flux['operationnel']['encaissements'] - 
            $this->flux['operationnel']['decaissements'];
        
        // Flux d'investissement (acquisitions d'immobilisations)
        $this->flux['investissement']['acquisitions'] = 
            ($soldes['20'] ?? 0) + ($soldes['21'] ?? 0) + ($soldes['22'] ?? 0);
        
        $this->flux['investissement']['net'] = 
            -$this->flux['investissement']['acquisitions']; // Négatif car décaissement
        
        // Flux de financement (capitaux propres et dettes)
        $this->flux['financement']['entrees'] = 
            ($soldes['10'] ?? 0) + ($soldes['16'] ?? 0);
        
        $this->flux['financement']['net'] = 
            $this->flux['financement']['entrees'] - 
            $this->flux['financement']['sorties'];
        
        // Calculer la trésorerie
        $variationTresorerie = 
            $this->flux['operationnel']['net'] +
            $this->flux['investissement']['net'] +
            $this->flux['financement']['net'];
        
        // Trésorerie de début (à partir du solde d'ouverture)
        $tresorerieDebut = $this->getTresorerieDebut();
        
        $this->tresorerie = [
            'debut' => $tresorerieDebut,
            'variation' => $variationTresorerie,
            'fin' => $tresorerieDebut + $variationTresorerie
        ];
    }
    
    private function getSoldesParCompte()
    {
        $soldes = [];
        
        $comptes = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereHas('mappings')
            ->get();
        
        foreach ($comptes as $compte) {
            $oldAccountIds = AccountMapping::where('new_account_id', $compte->id)
                ->where('entreprise_id', $this->entreprise->id)
                ->pluck('old_account_id')
                ->toArray();
            
            if (empty($oldAccountIds)) continue;
            
            $ecritures = GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
                ->where('validated', true)
                ->get();
            
            $debit = $ecritures->sum('debit');
            $credit = $ecritures->sum('credit');
            $solde = abs($debit - $credit);
            
            // Regrouper par classe
            $classe = substr($compte->code, 0, 2);
            $soldes[$classe] = ($soldes[$classe] ?? 0) + $solde;
        }
        
        return $soldes;
    }
    
    private function getMouvementsTresorerie()
    {
        // Méthode simplifiée - à adapter selon vos besoins
        return [
            'encaissements_clients' => 0,
            'paiements_fournisseurs' => 0,
            'salaires' => 0,
            'impots' => 0,
            'investissements' => 0,
            'emprunts' => 0,
            'remboursements' => 0
        ];
    }
    
    private function getTresorerieDebut()
    {
        // Récupérer le solde de trésorerie au début de la période
        // Pour simplifier, on prend 30% du total actif circulant
        return 0; // À implémenter selon vos données
    }
    
    public function render()
    {
        return view('livewire.financial.flux-tresorerie', [
            'flux' => $this->flux,
            'tresorerie' => $this->tresorerie,
            'periode' => [
                'debut' => $this->dateDebut,
                'fin' => $this->dateFin,
                'exercice' => $this->exercice
            ]
        ])->layout('layouts.app');
    }
}