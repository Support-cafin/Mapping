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
    
    public $entreprise;
    public $type = 'new';
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $accountId = '';
    public $viewMode = 'table';
    public $sortField = 'code';
    public $sortDirection = 'asc';
    
    public $showGenerateModal = false;
    public $generateType = 'new';
    public $generateFromDate;
    public $generateToDate;
    public $generateExercice;
    
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
        'total_ecritures' => 0,
    ];
    
    public $exportFormat = 'excel';
    public $showExportOptions = false;
    
    protected $queryString = [
        'type' => ['except' => 'new'],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'accountId' => ['except' => ''],
        'viewMode' => ['except' => 'table'],
    ];
    
    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        
        // Dates par défaut (année complète)
        $this->exercice = now()->year;
        $this->dateDebut = $this->exercice . '-01-01';
        $this->dateFin = $this->exercice . '-12-31';
        
        $this->generateExercice = $this->exercice;
        $this->generateFromDate = $this->dateDebut;
        $this->generateToDate = $this->dateFin;
        
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
        $this->loadNewBalances();
        $this->calculateStats();
    }
    
    private function loadNewBalances()
    {
        // Récupérer TOUS les comptes SYCEBNL
        $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->orderBy('code')
            ->get();
        
        $balances = [];
        
        foreach ($accounts as $account) {
            // Récupérer les anciens comptes mappés (s'ils existent)
            $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
                ->pluck('old_account_id')
                ->toArray();
            
            // Initialiser les variables
            $totalDebitN = 0;
            $totalCreditN = 0;
            $ecrituresCount = 0;
            $oldAccountsDetails = [];
            $ecrituresN = collect();
            
            // Si le compte SYCEBNL a des anciens comptes mappés
            if (!empty($oldAccountIds)) {
                // Écritures pour l'exercice N (période sélectionnée)
                $ecrituresN = GrandLivre::forEntreprise($this->entreprise->id)
                    ->whereIn('old_account_id', $oldAccountIds)
                    ->where('exercice', $this->exercice)
                    ->when($this->dateDebut && $this->dateFin, function ($q) {
                        $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                    })
                    ->valides()
                    ->get();
                
                $totalDebitN = $ecrituresN->sum('debit');
                $totalCreditN = $ecrituresN->sum('credit');
                $ecrituresCount = $ecrituresN->count();
                
                // Détails par ancien compte pour l'exercice N
                foreach ($oldAccountIds as $oldId) {
                    $oldEcritures = $ecrituresN->where('old_account_id', $oldId);
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
            }
            
            // Récupérer les soldes N-1 pour TOUS les comptes SYCEBNL
            $soldeN1 = $this->getSoldeN1($account->id);
            $soldeDebitN1 = $soldeN1['debit'];
            $soldeCreditN1 = $soldeN1['credit'];
            
            // Calculer le solde total (N-1 + N)
            $totalDebit = $totalDebitN + $soldeDebitN1;
            $totalCredit = $totalCreditN + $soldeCreditN1;
            $solde = $totalDebit - $totalCredit;
            
            // Ajouter TOUS les comptes SYCEBNL à la balance
            $balances[] = [
                'id' => $account->id,
                'code' => $account->code,
                'intitule' => $account->intitule,
                'type' => 'new',
                'solde_debit_n1' => $soldeDebitN1,
                'solde_credit_n1' => $soldeCreditN1,
                'total_debit_n' => $totalDebitN,
                'total_credit_n' => $totalCreditN,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'solde' => $solde,
                'ecritures_count' => $ecrituresCount,
                'has_mappings' => !empty($oldAccountIds),
                'old_accounts' => $oldAccountsDetails,
                'old_accounts_count' => count($oldAccountsDetails),
                'ecritures' => $ecrituresN,
                'old_account_ids' => $oldAccountIds,
            ];
        }
        
        // Trier
        $this->sortBalances($balances);
        $this->balances = $balances;
    }
    
    private function getSoldeN1($newAccountId)
    {
        $exercicePrecedent = $this->exercice - 1;
        
        // Récupérer les anciens comptes mappés à ce compte SYCEBNL
        $oldAccountIds = AccountMapping::where('new_account_id', $newAccountId)
            ->pluck('old_account_id')
            ->toArray();
        
        if (empty($oldAccountIds)) {
            return ['debit' => 0, 'credit' => 0];
        }
        
        // Chercher les écritures "Bilan d'ouverture" dans l'exercice précédent
        // Pour CHAQUE ancien compte mappé
        $totalDebitN1 = 0;
        $totalCreditN1 = 0;
        
        foreach ($oldAccountIds as $oldAccountId) {
            // Chercher les écritures avec libellé exact "Bilan d'ouverture" pour cet ancien compte
            $ecrituresBilan = GrandLivre::forEntreprise($this->entreprise->id)
                ->where('old_account_id', $oldAccountId)
                ->where('exercice', $exercicePrecedent)
                ->where('libelle', 'Bilan d\'ouverture')
                ->valides()
                ->get();
            
            // Si pas trouvé avec exact, chercher avec like
            if ($ecrituresBilan->isEmpty()) {
                $ecrituresBilan = GrandLivre::forEntreprise($this->entreprise->id)
                    ->where('old_account_id', $oldAccountId)
                    ->where('exercice', $exercicePrecedent)
                    ->where(function($query) {
                        $query->where('libelle', 'like', '%ouverture%')
                            ->orWhere('libelle', 'like', '%OUVERTURE%');
                    })
                    ->valides()
                    ->get();
            }
            
            // Ajouter les montants trouvés
            $totalDebitN1 += $ecrituresBilan->sum('debit');
            $totalCreditN1 += $ecrituresBilan->sum('credit');
        }
        
        return [
            'debit' => $totalDebitN1,
            'credit' => $totalCreditN1
        ];
    }
    
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
    
    private function calculateStats()
    {
        $stats = [
            'total_comptes' => count($this->balances),
            'total_debit' => 0,
            'total_credit' => 0,
            'total_solde' => 0,
            'comptes_crediteurs' => 0,
            'comptes_debiteurs' => 0,
            'total_ecritures' => 0,
        ];
        
        foreach ($this->balances as $balance) {
            $stats['total_debit'] += $balance['total_debit'];
            $stats['total_credit'] += $balance['total_credit'];
            $stats['total_solde'] += $balance['solde'];
            $stats['total_ecritures'] += $balance['ecritures_count'];
            
            if ($balance['solde'] > 0) {
                $stats['comptes_debiteurs']++;
            } elseif ($balance['solde'] < 0) {
                $stats['comptes_crediteurs']++;
            }
        }
        
        $this->stats = $stats;
    }
    
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
        
        $this->exercice = now()->year;
        $this->dateDebut = $this->exercice . '-01-01';
        $this->dateFin = $this->exercice . '-12-31';
        
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
            $this->cleanOldBalances();
            
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
        $accounts = NewAccount::where('entreprise_id', $this->entreprise->id)->get();
        
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
        return NewAccount::where('entreprise_id', $this->entreprise->id)
            ->orderBy('code')
            ->get();
    }
    
    public function render()
    {
        return view('livewire.balance.index', [
            'accounts' => $this->accounts,
        ]);
    }
}