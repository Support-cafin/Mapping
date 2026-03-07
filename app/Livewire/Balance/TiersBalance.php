<?php

namespace App\Livewire\Balance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class TiersBalance extends Component
{
    use WithPagination;
    
    public $entreprise;
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $search = '';
    public $selectedTiers = null;
    public $viewMode = 'table';
    
    public $stats = [
        'total_debit' => 0,
        'total_credit' => 0,
        'total_solde_debiteur' => 0,
        'total_solde_crediteur' => 0,
        'total_comptes' => 0,
    ];
    
    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = 2024;
    }
    
    public function updated($property)
    {
        if (in_array($property, ['dateDebut', 'dateFin', 'exercice', 'search'])) {
            $this->resetPage();
        }
    }
    
    /**
     * Récupère tous les comptes de la classe 4 (comptes de tiers)
     */
    /**
 * Récupère tous les comptes enfants de la classe 4 (comptes de tiers)
 * Un compte enfant est un compte qui a un parent (parent_id n'est pas null)
 */
public function getTiersAccounts()
{
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    if (!$exo) {
        return collect([]);
    }
    
    // Récupérer tous les comptes SYCEBNL qui commencent par 4 ET qui sont des enfants (ont un parent)
    $query = NewAccount::where('entreprise_id', $this->entreprise->id)
        ->where('code', 'like', '4%') // Comptes de la classe 4
        ->whereNotNull('parent_id'); // Seulement les comptes enfants (ceux qui ont un parent)
    
    // Appliquer la recherche si présente
    if ($this->search) {
        $query->where(function ($q) {
            $q->where('code', 'like', '%' . $this->search . '%')
              ->orWhere('intitule', 'like', '%' . $this->search . '%');
        });
    }
    
    $accounts = $query->orderBy('code')->get();
    
    return $accounts;
}
    
    /**
     * Récupère tous les enfants d'un compte parent (récursivement)
     */
    public function getAllChildrenIds($parentId)
    {
        $childrenIds = [];
        
        $directChildren = NewAccount::where('parent_id', $parentId)
            ->where('entreprise_id', $this->entreprise->id)
            ->pluck('id')
            ->toArray();
        
        foreach ($directChildren as $childId) {
            $childrenIds[] = $childId;
            $grandChildren = $this->getAllChildrenIds($childId);
            $childrenIds = array_merge($childrenIds, $grandChildren);
        }
        
        return $childrenIds;
    }
    
    /**
     * Calcule les mouvements et soldes pour un compte tiers
     */
    public function calculateTiersBalance($account)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        if (!$exo) {
            return null;
        }
        
        // Récupérer tous les IDs des comptes enfants (sous-comptes)
        $allAccountIds = $this->getAllChildrenIds($account->id);
        array_unshift($allAccountIds, $account->id);
        
        // Récupérer tous les anciens comptes mappés à ces comptes SYCEBNL
        $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->pluck('old_account_id')
            ->unique()
            ->toArray();
        
        if (empty($oldAccountIds)) {
            return null;
        }
        
        // Récupérer les écritures pour ces anciens comptes
        $query = GrandLivre::with(['oldAccount'])
            ->where('entreprise_id', $this->entreprise->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('validated', true);
        
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
        
        $ecritures = $query->get();
        
        // Calculer les totaux
        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde = $totalDebit - $totalCredit;
        
        // Ne retourner que s'il y a des écritures
        if ($ecritures->isEmpty()) {
            return null;
        }
        
        // Organiser les écritures par ancien compte pour les détails
        $oldAccountsData = [];
        foreach ($oldAccountIds as $oldId) {
            $oldEcritures = $ecritures->where('old_account_id', $oldId);
            
            if ($oldEcritures->isNotEmpty()) {
                $oldAccount = OldAccount::find($oldId);
                
                $oldAccountsData[] = [
                    'id' => $oldId,
                    'code' => $oldAccount->code ?? 'N/A',
                    'intitule' => $oldAccount->intitule ?? 'N/A',
                    'debit' => $oldEcritures->sum('debit'),
                    'credit' => $oldEcritures->sum('credit'),
                    'solde' => $oldEcritures->sum('debit') - $oldEcritures->sum('credit'),
                    'ecritures' => $oldEcritures,
                ];
            }
        }
        
        return [
            'id' => $account->id,
            'code' => $account->code,
            'intitule' => $account->intitule,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'solde_absolu' => abs($solde),
            'est_debiteur' => $solde > 0,
            'ecritures_count' => $ecritures->count(),
            'old_accounts_data' => $oldAccountsData,
            'ecritures' => $ecritures,
        ];
    }
    
    /**
     * Récupère toutes les balances des tiers
     */
    public function getBalances()
    {
        $balances = [];
        $accounts = $this->getTiersAccounts();
        
        foreach ($accounts as $account) {
            $balanceData = $this->calculateTiersBalance($account);
            
            if ($balanceData) {
                $balances[] = $balanceData;
            }
        }
        
        // Trier par code
        usort($balances, function($a, $b) {
            return strcmp($a['code'], $b['code']);
        });
        
        return $balances;
    }
    
    /**
     * Calcule les statistiques
     */
    public function calculateStats()
    {
        $balances = $this->getBalances();
        
        $totalSoldeDebiteur = 0;
        $totalSoldeCrediteur = 0;
        
        foreach ($balances as $balance) {
            if ($balance['solde'] > 0) {
                $totalSoldeDebiteur += $balance['solde'];
            } else {
                $totalSoldeCrediteur += abs($balance['solde']);
            }
        }
        
        return [
            'total_debit' => collect($balances)->sum('total_debit'),
            'total_credit' => collect($balances)->sum('total_credit'),
            'total_solde_debiteur' => $totalSoldeDebiteur,
            'total_solde_crediteur' => $totalSoldeCrediteur,
            'total_comptes' => count($balances),
        ];
    }
    
    /**
     * Afficher les détails d'un compte tiers
     */
    public function showDetails($balanceId)
    {
        $balances = $this->getBalances();
        $this->selectedTiers = collect($balances)->firstWhere('id', $balanceId);
        
        if ($this->selectedTiers) {
            $this->viewMode = 'details';
            $this->dispatch('scroll-to-details');
        }
    }
    
    /**
     * Retour à la liste
     */
    public function backToList()
    {
        $this->viewMode = 'table';
        $this->selectedTiers = null;
    }
    
    /**
     * Réinitialiser les filtres
     */
    public function resetFilters()
    {
        $this->reset(['dateDebut', 'dateFin', 'exercice', 'search']);
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = 2024;
        $this->resetPage();
    }
    
    /**
     * Imprimer la balance
     */
    public function print()
    {
        $this->dispatch('print-balance');
    }
    
    /**
     * Fonction de débogage pour vérifier les données
     */
    public function debug()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        $totalComptes4 = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('code', 'like', '4%')
            ->count();
        
        $comptesAvecMappings = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('code', 'like', '4%')
            ->whereHas('mappings', function($q) use ($exo) {
                $q->where('exercice_id', $exo->id);
            })
            ->count();
        
        $totalEcritures = GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
            ->where('validated', true)
            ->count();
        
        return [
            'total_comptes_classe4' => $totalComptes4,
            'comptes_avec_mappings' => $comptesAvecMappings,
            'total_ecritures_periode' => $totalEcritures,
            'date_debut' => $this->dateDebut,
            'date_fin' => $this->dateFin,
        ];
    }
    
    
    
    public function render()
    {
        // Récupérer les balances
        $balances = $this->getBalances();
        
        // Calculer les stats
        $this->stats = $this->calculateStats();
        
        // Données de débogage
        $debug = $this->debug();
        
        return view('livewire.balance.tiers-balance', [
            'balances' => $balances,
            'stats' => $this->stats,
            'viewMode' => $this->viewMode,
            'selectedTiers' => $this->selectedTiers,
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'debug' => $debug,
        ])->layout('layouts.app');
    }
}