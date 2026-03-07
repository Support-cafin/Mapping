<?php

namespace App\Livewire\Balance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;

class Index extends Component
{
    use WithPagination;
    
    public $entreprise;
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $search = '';
    public $viewMode = 'table';
    public $selectedBalance = null;
    public $balanceType = '4colonnes'; // Par défaut
    
    public $stats = [
        'total_comptes' => 0,
        'total_debit' => 0,
        'total_credit' => 0,
        'total_solde' => 0,
        'total_ecritures' => 0,
    ];
    
    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = 2024;
        
        $this->balanceType = '4colonnes'; // Balance à 4 colonnes par défaut
    }
    
    public function updatedBalanceType($value)
{
    // Recalculer les données si nécessaire
    $this->resetPage(); // Si vous avez de la pagination
}
    
    public function updated($property)
    {
        if (in_array($property, ['dateDebut', 'dateFin', 'exercice', 'search'])) {
            $this->resetPage();
        }
    }
    
// Ajoutez cette méthode au début de votre composant Balance
public function debugInfo()
{
    $entrepriseId = $this->entreprise->id;
    $exo = DB::table('exercices')->where('statut', 1)->first();
    // 1. Vérifier les comptes SYCEBNL
    $totalAccounts = NewAccount::where('entreprise_id', $entrepriseId)->count();
    $mappedAccounts = NewAccount::where('entreprise_id', $entrepriseId)
        ->whereHas('mappings')->count();
    
    // 2. Vérifier les mappings
    $mappings = AccountMapping::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id)->get();
    $mappingsCount = $mappings->count();
    
    // 3. Prendre un compte SYCEBNL au hasard pour tester
    $testAccount = NewAccount::where('entreprise_id', $entrepriseId)
        ->whereHas('mappings')
        ->first();
    
    $testData = [];
    if ($testAccount) {
        // Récupérer les anciens comptes mappés
        $oldAccountIds = AccountMapping::where('new_account_id', $testAccount->id)
            ->where('entreprise_id', $entrepriseId)
            ->where('exercice_id', $exo->id)
            ->pluck('old_account_id')
            ->toArray();
        
        // Méthode 1: Par new_account_id
        $ecrituresDirectes = GrandLivre::where('entreprise_id', $entrepriseId)
            ->where('new_account_id', $testAccount->id)
            ->where('exercice_id', $exo->id)
            ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
            ->where('validated', true)
             
            ->get();
        //->where('journal_code', '!=', 'RAN')
        // Méthode 2: Par anciens comptes
        $ecrituresParAnciens = GrandLivre::where('entreprise_id', $entrepriseId)
            ->whereIn('old_account_id', $oldAccountIds)
            ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
            ->where('validated', true)
             
            ->get();
        
        // Méthode 3: Sans validated
        $ecrituresSansValidated = GrandLivre::where('entreprise_id', $entrepriseId)
            ->where('exercice_id', $exo->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
             
            ->get();
        
        $testData = [
            'compte_sybcel' => $testAccount->code . ' - ' . $testAccount->intitule,
            'anciens_comptes' => $oldAccountIds,
            'anciens_comptes_details' => OldAccount::whereIn('id', $oldAccountIds)->pluck('code')->toArray(),
            'method1_direct' => [
                'count' => $ecrituresDirectes->count(),
                'debit' => $ecrituresDirectes->sum('debit'),
                'credit' => $ecrituresDirectes->sum('credit'),
            ],
            'method2_anciens_valide' => [
                'count' => $ecrituresParAnciens->count(),
                'debit' => $ecrituresParAnciens->sum('debit'),
                'credit' => $ecrituresParAnciens->sum('credit'),
            ],
            'method3_anciens_tout' => [
                'count' => $ecrituresSansValidated->count(),
                'debit' => $ecrituresSansValidated->sum('debit'),
                'credit' => $ecrituresSansValidated->sum('credit'),
            ],
        ];
    }
    
    // 4. Vérifier toutes les écritures dans la période
    $totalEcrituresPeriode = GrandLivre::where('entreprise_id', $entrepriseId)
       ->where('exercice_id', $exo->id)
        ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
         
        ->count();
    
    $totalEcrituresValides = GrandLivre::where('entreprise_id', $entrepriseId)
       ->where('exercice_id', $exo->id)
        ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
        ->where('validated', true)
         
        ->count();
    
    // 5. Vérifier si les écritures ont new_account_id
    $ecrituresAvecNewAccount = GrandLivre::where('entreprise_id', $entrepriseId)
        ->where('exercice_id', $exo->id)
        ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
        ->whereNotNull('new_account_id')
         
        ->count();
    
    return [
        'entreprise_id' => $entrepriseId,
        'periode' => $this->dateDebut . ' - ' . $this->dateFin,
        'total_comptes_sybcel' => $totalAccounts,
        'comptes_mappes' => $mappedAccounts,
        'total_mappings' => $mappingsCount,
        'test_compte' => $testData,
        'total_ecritures_periode' => $totalEcrituresPeriode,
        'ecritures_valides' => $totalEcrituresValides,
        'ecritures_avec_new_account' => $ecrituresAvecNewAccount,
        'balances_calculees' => count($this->getBalances()),
    ];
}

// Modifiez votre méthode getBalances() pour qu'elle soit plus simple
public function getBalances()
{
    $balances = [];
    $exo = DB::table('exercices')->where('statut', 1)->first();
    // Version SIMPLIFIÉE - une seule méthode
    $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
        ->whereHas('mappings')
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('intitule', 'like', '%' . $this->search . '%');
            });
        })
        ->orderBy('code')
        ->get();
    
    foreach ($accounts as $account) {
        // MÉTHODE LA PLUS SIMPLE: Chercher par anciens comptes SANS validated d'abord
        $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
            ->where('exercice_id', $exo->id)
            ->where('entreprise_id', $this->entreprise->id)
            ->pluck('old_account_id')
            ->toArray();
        
        if (empty($oldAccountIds)) {
            continue;
        }
        
        // Test 1: Sans validated
        $query = GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->whereIn('old_account_id', $oldAccountIds) ;
        
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
        
        $ecritures = $query->get();
        
        // Si toujours 0, vérifiez aussi avec validated
        if ($ecritures->count() === 0) {
            $query2 = GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->where('validated', true) ;
            
            if ($this->dateDebut && $this->dateFin) {
                $query2->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            }
            
            $ecritures = $query2->get();
        }
        
        // Si toujours 0, vérifiez par new_account_id
        if ($ecritures->count() === 0) {
            $query3 = GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id)
                ->where('new_account_id', $account->id) ;
            
            if ($this->dateDebut && $this->dateFin) {
                $query3->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            }
            
            $ecritures = $query3->get();
        }
        
        // Calcul des totaux
        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde = $totalDebit - $totalCredit;
        
        // Organisation par ancien compte
        $oldAccountsData = [];
        foreach ($oldAccountIds as $oldId) {
            $oldEcritures = $ecritures->filter(function ($ecriture) use ($oldId) {
                return $ecriture->old_account_id == $oldId;
            });
            
            if ($oldEcritures->count() > 0) {
                $oldAccount = OldAccount::find($oldId);
                if ($oldAccount) {
                    $oldTotalDebit = $oldEcritures->sum('debit');
                    $oldTotalCredit = $oldEcritures->sum('credit');
                    
                    $oldAccountsData[] = [
                        'id' => $oldId,
                        'code' => $oldAccount->code,
                        'intitule' => $oldAccount->intitule,
                        'debit' => $oldTotalDebit,
                        'credit' => $oldTotalCredit,
                        'solde' => $oldTotalDebit - $oldTotalCredit,
                        'count' => $oldEcritures->count(),
                        'ecritures' => $oldEcritures,
                    ];
                }
            }
        }
        
        $balances[] = [
            'id' => $account->id,
            'code' => $account->code,
            'intitule' => $account->intitule,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'ecritures_count' => $ecritures->count(),
            'old_accounts_data' => $oldAccountsData,
            'old_accounts_count' => count($oldAccountsData),
            'ecritures' => $ecritures,
            'account' => $account,
        ];
    }
    
    return $balances;
}
    
    // MÉTHODE ALTERNATIVE: Utiliser la même logique que le Grand Livre
    public function getBalancesAlternative()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Cette méthode utilise exactement la même logique que votre Grand Livre
        $newAccounts = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereHas('mappings')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('intitule', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('code')
            ->get();

        $balances = [];

        foreach ($newAccounts as $newAccount) {
            // Récupérer les anciens comptes mappés
            $oldAccountIds = AccountMapping::where('entreprise_id', $this->entreprise->id)
                ->where('new_account_id', $newAccount->id)
                ->where('exercice_id', $exo->id)
                ->pluck('old_account_id')
                ->toArray();

            if (empty($oldAccountIds)) {
                continue;
            }

            // Récupérer les écritures (EXACTEMENT comme dans Grand Livre)
            $ecrituresQuery = GrandLivre::with(['oldAccount'])
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->where('validated', true) ; // Utiliser validated au lieu de valides()

            // Appliquer les filtres
            if ($this->dateDebut && $this->dateFin) {
                $ecrituresQuery->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            }
            
            if ($this->exercice) {
                $ecrituresQuery->where('exercice', $this->exercice);
            }

            $ecritures = $ecrituresQuery->get();

            // Calculer les totaux
            $totalDebit = $ecritures->sum('debit');
            $totalCredit = $ecritures->sum('credit');
            $solde = $totalDebit - $totalCredit;

            // Organiser par ancien compte
            $oldAccountsData = [];
            $oldAccounts = OldAccount::whereIn('id', $oldAccountIds)->get()->keyBy('id');
            
            foreach ($oldAccountIds as $oldId) {
                if (!isset($oldAccounts[$oldId])) continue;
                
                $oldEcritures = $ecritures->where('old_account_id', $oldId);
                
                if ($oldEcritures->count() > 0) {
                    $oldTotalDebit = $oldEcritures->sum('debit');
                    $oldTotalCredit = $oldEcritures->sum('credit');
                    
                    $oldAccountsData[] = [
                        'account' => $oldAccounts[$oldId],
                        'ecritures' => $oldEcritures,
                        'total_debit' => $oldTotalDebit,
                        'total_credit' => $oldTotalCredit,
                        'solde' => $oldTotalDebit - $oldTotalCredit,
                        'count' => $oldEcritures->count(),
                    ];
                }
            }

            // Ajouter la balance seulement si il y a des écritures
            if ($ecritures->count() > 0) {
                $balances[] = [
                    'id' => $newAccount->id,
                    'code' => $newAccount->code,
                    'intitule' => $newAccount->intitule,
                    'classe' => $newAccount->classe,
                    'groupe' => $newAccount->groupe,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'solde' => $solde,
                    'solde_absolu' => abs($solde),
                    'ecritures_count' => $ecritures->count(),
                    'old_accounts_data' => $oldAccountsData,
                    'old_accounts_count' => count($oldAccountsData),
                    'ecritures' => $ecritures,
                    'account' => $newAccount,
                ];
            }
        }

        return $balances;
    }
    
    public function calculateStats()
    {
        $balances = $this->getBalances();
        
        $stats = [
            'total_comptes' => count($balances),
            'total_debit' => 0,
            'total_credit' => 0,
            'total_solde' => 0,
            'total_ecritures' => 0,
        ];
        
        foreach ($balances as $balance) {
            $stats['total_debit'] += $balance['total_debit'];
            $stats['total_credit'] += $balance['total_credit'];
            $stats['total_solde'] += $balance['solde'];
            $stats['total_ecritures'] += $balance['ecritures_count'];
        }
        
        return $stats;
    }
    
    // MÉTHODE IMPORTANTE: Synchroniser les mappings avant d'afficher
    public function syncAllMappings()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            $updated = 0;
            
            GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
                ->whereNotNull('old_account_id')
                 
                ->chunk(100, function ($ecritures) use (&$updated) {
                    foreach ($ecritures as $ecriture) {
                        if ($ecriture->syncMapping()) {
                            $updated++;
                        }
                    }
                });
            
            session()->flash('success', "$updated écriture(s) synchronisée(s) avec les mappings.");
            
            // Recharger les données
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Synchronisation terminée : $updated écriture(s) mises à jour."
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }
    
    // MÉTHODE DEBUG: Vérifions les écritures
    public function debugEcritures()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Vérifier combien d'écritures ont un new_account_id
        $totalEcritures = GrandLivre::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id)->count();
        $ecrituresAvecNewAccount = GrandLivre::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id)
            ->whereNotNull('new_account_id')
             
            ->count();
        
        $ecrituresValides = GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->where('validated', true)
             
            ->count();
        
        // Vérifier un compte spécifique
        $testAccount = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereHas('mappings')
            ->first();
        
        $testInfo = [];
        if ($testAccount) {
            // Par new_account_id
            $ecrituresDirectes = GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id)
                ->where('new_account_id', $testAccount->id)
                ->where('validated', true)
                ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
                 
                ->get();
            
            // Par anciens comptes
            $oldIds = AccountMapping::where('new_account_id', $testAccount->id)
                 ->where('exercice_id', $exo->id)
                ->pluck('old_account_id')
                ->toArray();
            
            $ecrituresParAnciens = GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id)
                ->whereIn('old_account_id', $oldIds)
                ->where('validated', true)
                ->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin])
                 
                ->get();
            
            $testInfo = [
                'compte' => $testAccount->code,
                'ecritures_directes' => $ecrituresDirectes->count(),
                'ecritures_par_anciens' => $ecrituresParAnciens->count(),
                'anciens_comptes' => $oldIds,
            ];
        }
        
        return [
            'total_ecritures' => $totalEcritures,
            'avec_new_account' => $ecrituresAvecNewAccount,
            'valides' => $ecrituresValides,
            'test_compte' => $testInfo,
        ];
    }
    
    public function showDetails($balanceId)
    {
        $balances = $this->getBalances();
        $this->selectedBalance = collect($balances)->firstWhere('id', $balanceId);
        
        if ($this->selectedBalance) {
            $this->viewMode = 'details';
            $this->dispatch('scroll-to-details');
        }
    }
    
    public function backToList()
    {
        $this->viewMode = 'table';
        $this->selectedBalance = null; 
    }
    
    public function resetFilters()
    {
        $this->reset(['dateDebut', 'dateFin', 'exercice', 'search']);
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = 2024;
        $this->resetPage();
    }
    
    public function render()
    {
        // TEST: Synchroniser d'abord les mappings
        // $this->syncAllMappings();
        
        $balances = $this->getBalances();
        $this->stats = $this->calculateStats();
        
        // Debug info
        // $debug = $this->debugEcritures();
        // \Log::info('Balance Debug', $debug);
        
        return view('livewire.balance.index', [
            'balances' => $balances,
            'stats' => $this->stats,
        ]);
    }
}