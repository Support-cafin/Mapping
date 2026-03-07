<?php

namespace App\Livewire\Balance;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\OldBalance;
use App\Models\NewBalance;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination;
    
    // Entreprise
    public $entreprise;
    
    // Filtres
    public $type = 'old'; // 'old' ou 'new'
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $accountId = '';
    public $viewMode = 'table'; // 'table' ou 'details'
    public $sortField = 'code';
    public $sortDirection = 'asc';
    
    // Génération
    public $showGenerateModal = false;
    public $generateType = 'old'; // 'old', 'new', 'both'
    public $generateFromDate;
    public $generateToDate;
    public $generateExercice;
    
    // Données
    public $balances = [];
    public $selectedBalance = null;
    public $balanceDetails = [];
    public $stats = [
        'total_comptes' => 0,
        'total_debit' => 0,
        'total_credit' => 0,
        'total_solde' => 0,
        'comptes_crediteurs' => 0,
        'comptes_debiteurs' => 0,
    ];
    
    // Export
    public $exportFormat = 'excel';
    public $showExportOptions = false;
    
    protected $queryString = [
        'type' => ['except' => 'old'],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'accountId' => ['except' => ''],
        'viewMode' => ['except' => 'table'],
    ];
    
    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        
        // Dates par défaut (mois en cours)
        //$this->dateDebut = now()->startOfMonth()->format('Y-m-d');
        //$this->dateFin = now()->endOfMonth()->format('Y-m-d');
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = now()->year;
        
        // Génération
        $this->generateFromDate = $this->dateDebut;
        $this->generateToDate = $this->dateFin;
        $this->generateExercice = $this->exercice;
        
        $this->loadBalances();
    }
    
    public function updated($property)
    {
        if (in_array($property, ['type', 'dateDebut', 'dateFin', 'exercice', 'accountId'])) {
            $this->resetPage();
            $this->loadBalances();
        }
    }
    
    public function loadBalances()
    {
        if ($this->type === 'old') {
            $this->loadOldBalances();
        } else {
            $this->loadNewBalances();
        }
        
        $this->calculateStats();
    }
    
    
    
    private function loadOldBalances()
    {
        // Récupérer tous les comptes anciens qui ont des écritures
        $accounts = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->orderBy('code')
            ->get();
        
        $balances = [];
        
        foreach ($accounts as $account) {
            // Récupérer les écritures de ce compte dans la période
            $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
                ->where('old_account_id', $account->id)
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->where('exercice', $this->exercice);
                })
                ->valides()
                ->get();
            
            if ($ecritures->count() > 0) {
                $totalDebit = $ecritures->sum('debit');
                $totalCredit = $ecritures->sum('credit');
                $solde = abs($totalDebit - $totalCredit);
                
                // Solde précédent (si disponible)
                $soldePrecedent = $this->getOldSoldePrecedent($account->id);
                
                $balances[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'intitule' => $account->intitule,
                    'type' => 'old',
                    'solde_precedent' => $soldePrecedent,
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'solde' => $solde,
                    'ecritures_count' => $ecritures->count(),
                    'mapped' => $account->mappings()->exists(),
                    'ecritures' => $ecritures,
                ];
            }
        }
        
        // Trier
        $this->sortBalances($balances);
        $this->balances = $balances;
    }
    
    /*private function loadNewBalances()
    {
        // Récupérer tous les comptes SYCEBNL qui ont des mappings
        $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereHas('mappings')
            ->orderBy('code')
            ->get();
        
        $balances = [];
        
        foreach ($accounts as $account) {
            // Récupérer les anciens comptes mappés
            $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
                ->pluck('old_account_id')
                ->toArray();
            
            if (empty($oldAccountIds)) {
                continue;
            }
            
            // Récupérer toutes les écritures des anciens comptes mappés
            $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->where('exercice', $this->exercice);
                })
                ->valides()
                ->get();
            
            if ($ecritures->count() > 0) {
                $totalDebit = $ecritures->sum('debit');
                $totalCredit = $ecritures->sum('credit');
                $solde = abs($totalDebit - $totalCredit);
                
                // Calculer le solde N-1 pour ce compte SYCEBNL
                $soldeN1 = $this->getNewAccountSoldeN1($account->id);
                
                // Détails par ancien compte
                $oldAccountsDetails = [];
                foreach ($oldAccountIds as $oldId) {
                    $oldEcritures = $ecritures->where('old_account_id', $oldId);
                    if ($oldEcritures->count() > 0) {
                        $oldAccount = OldAccount::find($oldId);
                        $oldAccountsDetails[] = [
                            'id' => $oldId,
                            'code' => $oldAccount->code ?? 'N/A',
                            'intitule' => $oldAccount->intitule ?? 'N/A',
                            'debit' => $oldEcritures->sum('debit'),
                            'credit' => $oldEcritures->sum('credit'),
                            'count' => $oldEcritures->count(),
                        ];
                    }
                }
                
                $balances[] = [
                    'id' => $account->id,
                    'code' => $account->code,
                    'intitule' => $account->intitule,
                    'type' => 'new',
                    'solde_debit_n1' => $soldeN1['debit'],  // Ajouté
                    'solde_credit_n1' => $soldeN1['credit'], // Ajouté
                    'total_debit' => $totalDebit,
                    'total_credit' => $totalCredit,
                    'solde' => $solde,
                    'ecritures_count' => $ecritures->count(),
                    'old_accounts' => $oldAccountsDetails,
                    'old_accounts_count' => count($oldAccountsDetails),
                    'ecritures' => $ecritures,
                ];
            }
        }
        
        // Trier
        $this->sortBalances($balances);
        $this->balances = $balances;
    }*/
    
    /*private function loadNewBalances()
    {
    // Récupérer tous les comptes SYCEBNL (pas seulement ceux qui ont des mappings)
    $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
        ->orderBy('code')
        ->get();
    
    $balances = [];
    
    foreach ($accounts as $account) {
        // Récupérer les anciens comptes mappés à ce compte SYCEBNL
        $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
            ->pluck('old_account_id')
            ->toArray();
        
        // Initialiser les totaux
        $totalDebit = 0;
        $totalCredit = 0;
        $ecrituresCount = 0;
        $oldAccountsDetails = [];
        
        // Si le compte a des mappings, calculer à partir des anciens comptes
        if (!empty($oldAccountIds)) {
            // Récupérer toutes les écritures des anciens comptes mappés
            $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->where('exercice', $this->exercice);
                })
                ->valides()
                ->get();
            
            $totalDebit = $ecritures->sum('debit');
            $totalCredit = $ecritures->sum('credit');
            $ecrituresCount = $ecritures->count();
            
            // Détails par ancien compte
            foreach ($oldAccountIds as $oldId) {
                $oldEcritures = $ecritures->where('old_account_id', $oldId);
                if ($oldEcritures->count() > 0) {
                    $oldAccount = OldAccount::find($oldId);
                    if ($oldAccount) {
                        $oldAccountsDetails[] = [
                            'id' => $oldId,
                            'code' => $oldAccount->code ?? 'N/A',
                            'intitule' => $oldAccount->intitule ?? 'N/A',
                            'debit' => $oldEcritures->sum('debit'),
                            'credit' => $oldEcritures->sum('credit'),
                            'count' => $oldEcritures->count(),
                        ];
                    }
                }
            }
        }
        
        // AJOUTEZ CE CODE : Aussi inclure les écritures directes sur le nouveau compte
        // (si vous avez des écritures directement liées à new_account_id)
        $directEcritures = GrandLivre::forEntreprise($this->entreprise->id)
            ->where('new_account_id', $account->id)
            ->when($this->dateDebut && $this->dateFin, function ($q) {
                $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            })
            ->when($this->exercice, function ($q) {
                $q->where('exercice', $this->exercice);
            })
            ->valides()
            ->get();
        
        $totalDebit += $directEcritures->sum('debit');
        $totalCredit += $directEcritures->sum('credit');
        $ecrituresCount += $directEcritures->count();
        
        // Si pas d'écritures, on ne montre pas le compte
        if ($totalDebit == 0 && $totalCredit == 0) {
            continue;
        }
        
        $solde = abs($totalDebit - $totalCredit);
        
        // Calculer le solde N-1
        $soldeN1 = $this->getNewAccountSoldeN1($account->id);
        
        $balances[] = [
            'id' => $account->id,
            'code' => $account->code,
            'intitule' => $account->intitule,
            'type' => 'new',
            'solde_debit_n1' => $soldeN1['debit'],
            'solde_credit_n1' => $soldeN1['credit'],
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'ecritures_count' => $ecrituresCount,
            'old_accounts' => $oldAccountsDetails,
            'old_accounts_count' => count($oldAccountsDetails),
            'ecritures' => $directEcritures->merge($ecritures ?? collect())->unique(),
        ];
    }
    
    // Trier
    $this->sortBalances($balances);
    $this->balances = $balances;
}*/
    private function loadNewBalances()
    {
    // Récupérer tous les comptes SYCEBNL avec leurs relations
    $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
        ->with(['oldAccounts' => function($query) {
            // Charger les anciens comptes mappés
        }])
        ->orderBy('code')
        ->get();
    
    $balances = [];
    
    foreach ($accounts as $account) {
        // Récupérer les anciens comptes mappés à ce compte SYCEBNL
        $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
            ->pluck('old_account_id')
            ->toArray();
        
        // Initialiser les totaux
        $totalDebit = 0;
        $totalCredit = 0;
        $ecrituresCount = 0;
        $oldAccountsDetails = [];
        
        // Si le compte a des mappings, calculer à partir des anciens comptes
        if (!empty($oldAccountIds)) {
            // Récupérer toutes les écritures des anciens comptes mappés
            $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->where('exercice', $this->exercice);
                })
                ->valides()
                ->get();
            
            $totalDebit = $ecritures->sum('debit');
            $totalCredit = $ecritures->sum('credit');
            $ecrituresCount = $ecritures->count();
            
            // Détails par ancien compte
            foreach ($oldAccountIds as $oldId) {
                $oldEcritures = $ecritures->where('old_account_id', $oldId);
                if ($oldEcritures->count() > 0) {
                    $oldAccount = OldAccount::find($oldId);
                    if ($oldAccount) {
                        $oldAccountsDetails[] = [
                            'id' => $oldId,
                            'code' => $oldAccount->code ?? 'N/A',
                            'intitule' => $oldAccount->intitule ?? 'N/A',
                            'debit' => $oldEcritures->sum('debit'),
                            'credit' => $oldEcritures->sum('credit'),
                            'count' => $oldEcritures->count(),
                        ];
                    }
                }
            }
        }
        
        // AJOUTEZ CE CODE : Aussi inclure les écritures directes sur le nouveau compte
        // (si vous avez des écritures directement liées à new_account_id)
        $directEcritures = GrandLivre::forEntreprise($this->entreprise->id)
            ->where('new_account_id', $account->id)
            ->when($this->dateDebut && $this->dateFin, function ($q) {
                $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            })
            ->when($this->exercice, function ($q) {
                $q->where('exercice', $this->exercice);
            })
            ->valides()
            ->get();
        
        $totalDebit += $directEcritures->sum('debit');
        $totalCredit += $directEcritures->sum('credit');
        $ecrituresCount += $directEcritures->count();
        
        // Si pas d'écritures, on ne montre pas le compte
        if ($totalDebit == 0 && $totalCredit == 0) {
            continue;
        }
        
        $solde = abs($totalDebit - $totalCredit);
        
        // Calculer le solde N-1
        $soldeN1 = $this->getNewAccountSoldeN1($account->id);
        
        // ICI, utilisez les données du compte SYCEBNL (NewAccount)
        $balances[] = [
            'id' => $account->id,
            'code' => $account->code,
            'intitule' => $account->intitule,
            'classe' => $account->classe,  // AJOUTEZ CE CI
            'groupe' => $account->groupe,  // AJOUTEZ CE CI
            'niveau' => $account->niveau,  // Optionnel
            'type' => 'new',
            'solde_debit_n1' => $soldeN1['debit'],
            'solde_credit_n1' => $soldeN1['credit'],
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'ecritures_count' => $ecrituresCount,
            'old_accounts' => $oldAccountsDetails,
             'old_accounts_count' => $oldAccountsCount,
            'ecritures' => $directEcritures->merge($ecritures ?? collect())->unique(),
            'account' => $account,  // Pour accéder à toutes les propriétés
        ];
    }
    
    // Trier
    $this->sortBalances($balances);
    $this->balances = $balances;
}
   
   private function getNewAccountSoldeN1($newAccountId)
{
    $anneeN1 = $this->exercice - 1;
    $soldeN1 = ['debit' => 0, 'credit' => 0];
    
    // Récupérer le compte SYCEBNL
    $account = NewAccount::find($newAccountId);
    
    // 1. Vérifier d'abord si des soldes N-1 sont stockés
    if ($account && ($account->solde_debit_n1 > 0 || $account->solde_credit_n1 > 0)) {
        return [
            'debit' => $account->solde_debit_n1 ?? 0,
            'credit' => $account->solde_credit_n1 ?? 0
        ];
    }
    
    // 2. Sinon, calculer à partir des écritures
    // Récupérer les anciens comptes mappés
    $oldAccountIds = AccountMapping::where('new_account_id', $newAccountId)
        ->pluck('old_account_id')
        ->toArray();
    
    if (!empty($oldAccountIds)) {
        // Calculer les écritures pour l'année N-1
        $ecrituresN1 = GrandLivre::forEntreprise($this->entreprise->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('exercice', $anneeN1)
            ->valides()
            ->get();
        
        $soldeN1['debit'] = $ecrituresN1->sum('debit');
        $soldeN1['credit'] = $ecrituresN1->sum('credit');
    }
    
    // 3. Vérifier aussi les écritures directes sur new_account_id
    $directEcrituresN1 = GrandLivre::forEntreprise($this->entreprise->id)
        ->where('new_account_id', $newAccountId)
        ->where('exercice', $anneeN1)
        ->valides()
        ->get();
    
    $soldeN1['debit'] += $directEcrituresN1->sum('debit');
    $soldeN1['credit'] += $directEcrituresN1->sum('credit');
    
    return $soldeN1;
}
   
   /*private function getNewAccountSoldeN1($newAccountId)
    {
        $anneeN1 = $this->exercice - 1;
        
        // Récupérer les anciens comptes mappés
        $oldAccountIds = AccountMapping::where('new_account_id', $newAccountId)
            ->pluck('old_account_id')
            ->toArray();
        
        if (empty($oldAccountIds)) {
            return ['debit' => 0, 'credit' => 0];
        }
        
        // Calculer les écritures pour l'année N-1
        $ecrituresN1 = GrandLivre::forEntreprise($this->entreprise->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('exercice', $anneeN1)
            ->valides()
            ->get();
        
        // Si aucune écriture en N-1, essayer de prendre les valeurs stockées dans la table new_accounts
        if ($ecrituresN1->count() === 0) {
            $account = NewAccount::find($newAccountId);
            return [
                'debit' => $account->solde_debit_n1 ?? 0,
                'credit' => $account->solde_credit_n1 ?? 0
            ];
        }
        
        return [
            'debit' => abs($ecrituresN1->sum('debit')),
            'credit' => abs($ecrituresN1->sum('credit'))
        ];
    }*/
   
    
    private function sortBalances(&$balances)
    {
        usort($balances, function ($a, $b) {
            $field = $this->sortField;
            $direction = $this->sortDirection === 'asc' ? 1 : -1;
            
            if ($field === 'solde') {
                return $direction * ($a['solde'] <=> $b['solde']);
            } elseif ($field === 'total_debit') {
                return $direction * ($a['total_debit'] <=> $b['total_debit']);
            } elseif ($field === 'total_credit') {
                return $direction * ($a['total_credit'] <=> $b['total_credit']);
            } else {
                return $direction * strcmp($a['code'], $b['code']);
            }
        });
    }
    
    private function getOldSoldePrecedent($accountId)
    {
        // Récupérer le solde au début de la période
        $debut = Carbon::parse($this->dateDebut)->subDay();
        
        $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
            ->where('old_account_id', $accountId)
            ->where('date_ecriture', '<', $this->dateDebut)
            ->valides()
            ->get();
        
        $debit = $ecritures->sum('debit');
        $credit = $ecritures->sum('credit');
        
        return $debit - $credit;
    }
    
    /*private function calculateStats()
    {
        $stats = [
            'total_comptes' => count($this->balances),
            'total_debit' => 0,
            'total_credit' => 0,
            'total_solde' => 0,
            'comptes_crediteurs' => 0,
            'comptes_debiteurs' => 0,
        ];
        
        foreach ($this->balances as $balance) {
            $stats['total_debit'] += $balance['total_debit'];
            $stats['total_credit'] += $balance['total_credit'];
            $stats['total_solde'] += $balance['solde'];
            
            if ($balance['solde'] > 0) {
                $stats['comptes_debiteurs']++;
            } elseif ($balance['solde'] < 0) {
                $stats['comptes_crediteurs']++;
            }
        }
        
        $this->stats = $stats;
    }*/
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->sortBalances($this->balances);
    }
    
    public function resetFilters()
    {
        $this->reset([
            'dateDebut',
            'dateFin',
            'exercice',
            'accountId',
        ]);
        
        // Réinitialiser aux valeurs par défaut
        $this->dateDebut = now()->startOfMonth()->format('Y-m-d');
        $this->dateFin = now()->endOfMonth()->format('Y-m-d');
        $this->exercice = now()->year;
        
        $this->resetPage();
        $this->loadBalances();
    }
    
    public function showDetails($balanceId)
    {
        $this->selectedBalance = collect($this->balances)->firstWhere('id', $balanceId);
        
        if ($this->selectedBalance) {
            $this->viewMode = 'details';
            $this->dispatch('scroll-to-details');
        }
    }
    
    public function backToList()
    {
        $this->viewMode = 'table';
        $this->selectedBalance = null;
        $this->balanceDetails = [];
    }
    
    public function openGenerateModal()
    {
        $this->generateFromDate = $this->dateDebut;
        $this->generateToDate = $this->dateFin;
        $this->generateExercice = $this->exercice;
        $this->showGenerateModal = true;
        $this->dispatch('open-generate-modal');
    }
    
    public function generateBalances()
    {
        $this->validate([
            'generateType' => 'required|in:old,new,both',
            'generateFromDate' => 'required|date',
            'generateToDate' => 'required|date|after_or_equal:generateFromDate',
            'generateExercice' => 'required|integer',
        ]);
        
        try {
            // Nettoyer les anciennes balances de la période
            $this->cleanOldBalances();
            
            // Générer les nouvelles balances
            if ($this->generateType === 'old' || $this->generateType === 'both') {
                $this->generateOldBalances();
            }
            
            if ($this->generateType === 'new' || $this->generateType === 'both') {
                $this->generateNewBalances();
            }
            
            $this->showGenerateModal = false;
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Balances générées avec succès !'
            ]);
            
            // Recharger les données
            $this->loadBalances();
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur lors de la génération : ' . $e->getMessage()
            ]);
        }
    }
    
    private function cleanOldBalances()
    {
        // Supprimer les anciennes balances pour la période
        OldBalance::where('entreprise_id', $this->entreprise->id)
            ->where('periode', $this->getPeriodeString())
            ->where('exercice', $this->generateExercice)
            ->delete();
            
        NewBalance::where('entreprise_id', $this->entreprise->id)
            ->where('periode', $this->getPeriodeString())
            ->where('exercice', $this->generateExercice)
            ->delete();
    }
    
    private function generateOldBalances()
    {
        $accounts = OldAccount::where('entreprise_id', $this->entreprise->id)->get();
        
        foreach ($accounts as $account) {
            $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
                ->where('old_account_id', $account->id)
                ->whereBetween('date_ecriture', [$this->generateFromDate, $this->generateToDate])
                ->valides()
                ->get();
            
            if ($ecritures->count() > 0) {
                OldBalance::create([
                    'entreprise_id' => $this->entreprise->id,
                    'old_account_id' => $account->id,
                    'debit' => $ecritures->sum('debit'),
                    'credit' => $ecritures->sum('credit'),
                    'solde' => $ecritures->sum('debit') - $ecritures->sum('credit'),
                    'periode' => $this->getPeriodeString(),
                    'exercice' => $this->generateExercice,
                ]);
            }
        }
    }
    
    private function generateNewBalances()
    {
        $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereHas('mappings')
            ->get();
        
        foreach ($accounts as $account) {
            $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
                ->pluck('old_account_id')
                ->toArray();
            
            if (!empty($oldAccountIds)) {
                $ecritures = GrandLivre::forEntreprise($this->entreprise->id)
                    ->whereIn('old_account_id', $oldAccountIds)
                    ->whereBetween('date_ecriture', [$this->generateFromDate, $this->generateToDate])
                    ->valides()
                    ->get();
                
                if ($ecritures->count() > 0) {
                    NewBalance::create([
                        'entreprise_id' => $this->entreprise->id,
                        'new_account_id' => $account->id,
                        'debit' => $ecritures->sum('debit'),
                        'credit' => $ecritures->sum('credit'),
                        'solde' => $ecritures->sum('debit') - $ecritures->sum('credit'),
                        'periode' => $this->getPeriodeString(),
                        'exercice' => $this->generateExercice,
                    ]);
                }
            }
        }
    }
    
    private function getPeriodeString(): string
    {
        $start = Carbon::parse($this->generateFromDate);
        $end = Carbon::parse($this->generateToDate);
        
        if ($start->format('Y-m-d') === $start->startOfMonth()->format('Y-m-d') && 
            $end->format('Y-m-d') === $end->endOfMonth()->format('Y-m-d')) {
            return $start->format('Y-m');
        }
        
        return $start->format('Y-m-d') . '_' . $end->format('Y-m-d');
    }
    
    public function export($format = 'excel')
    {
        return redirect()->route('balance.export', [
            'type' => $this->type,
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'format' => $format,
        ]);
    }
    
    public function getAccountsProperty()
    {
        if ($this->type === 'old') {
            return OldAccount::where('entreprise_id', $this->entreprise->id)
                ->orderBy('code')
                ->get();
        } else {
            return NewAccount::where('entreprise_id', $this->entreprise->id)
                ->whereHas('mappings')
                ->orderBy('code')
                ->get();
        }
    }
    
    private function calculateStats()
{
    $stats = [
        'total_comptes' => count($this->balances),
        'total_debit' => 0,
        'total_credit' => 0,
        'total_solde' => 0,
        'total_debit_n1' => 0,
        'total_credit_n1' => 0,
        'comptes_crediteurs' => 0,
        'comptes_debiteurs' => 0,
    ];
    
    foreach ($this->balances as $balance) {
        $stats['total_debit'] += $balance['total_debit'];
        $stats['total_credit'] += $balance['total_credit'];
        $stats['total_solde'] += $balance['solde'];
        $stats['total_debit_n1'] += $balance['solde_debit_n1'] ?? 0;
        $stats['total_credit_n1'] += $balance['solde_credit_n1'] ?? 0;
        
        if ($balance['solde'] > 0) {
            $stats['comptes_debiteurs']++;
        } elseif ($balance['solde'] < 0) {
            $stats['comptes_crediteurs']++;
        }
    }
    
    $this->stats = $stats;
}
    
    public function render()
    {
        return view('livewire.balance.index', [
            'accounts' => $this->accounts,
        ]);
    }
}