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
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\BalanceExport;
use Barryvdh\DomPDF\Facade\Pdf; // <-- AJOUTEZ CETTE LIGNE

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
    public $balanceType = '4colonnes';
    public $debugMode = false; // Pour activer le débogage
    public $loadingExcel = false;
    public $loadingPrint = false;
    public $stats = [
        'total_comptes' => 0,
        'total_debit' => 0,
        'total_credit' => 0,
        'total_solde' => 0,
        'total_ecritures' => 0,
    ];
    
    public function mount()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->entreprise = auth()->user()->entreprise;
        //$this->dateDebut = '2024-01-01';
        //$this->dateFin = '2024-12-31';
        $this->dateDebut = $exo->date_debut ?? '2024-12-31';
        $this->dateFin = $exo->date_fin ?? '2024-12-31';
        $this->exercice = date('Y', strtotime($exo->date_debut));
        $this->balanceType = '4colonnes';
    }
    
    public function updatedBalanceType($value)
    {
        $this->resetPage();
    }
    
    public function updated($property)
    {
        if (in_array($property, ['dateDebut', 'dateFin', 'exercice', 'search'])) {
            $this->resetPage();
        }
    }
    
    /**
     * Récupère tous les comptes parents (ceux qui ont des enfants)
     */
    public function getParentAccounts()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        // MÉTHODE 1: Récupérer les comptes qui sont parents (qui ont des enfants)
        $parentIds = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereNotNull('parent_id') // Ceux qui ont un parent (les enfants)
            ->distinct()
            ->pluck('parent_id')
            ->filter() // Enlever les null
            ->unique()
            ->values()
            ->toArray();
        
        // MÉTHODE 2: Ajouter aussi les comptes racines qui pourraient avoir des mappings
        $rootAccountIds = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereNull('parent_id')
            ->whereHas('mappings', function($query) use ($exo) {
                $query->where('exercice_id', $exo->id ?? '');
            })
            ->pluck('id')
            ->toArray();
        
        // Fusionner les deux listes
        $allParentIds = array_unique(array_merge($parentIds, $rootAccountIds));
        
        if ($this->debugMode) {
            \Log::info('Parents IDs trouvés:', [
                'parents_avec_enfants' => $parentIds,
                'comptes_racines_avec_mappings' => $rootAccountIds,
                'total_parents' => count($allParentIds)
            ]);
        }
        
        // Récupérer les détails des comptes parents
        $parentAccounts = NewAccount::whereIn('id', $allParentIds)
            ->where('entreprise_id', $this->entreprise->id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('intitule', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('code')
            ->get();
        
        return $parentAccounts;
    }
    
    /**
     * Récupère tous les enfants d'un compte parent (récursivement)
     */
    public function getAllChildrenIds($parentId)
    {
        $childrenIds = [];
        
        // Récupérer les enfants directs
        $directChildren = NewAccount::where('parent_id', $parentId)
            ->where('entreprise_id', $this->entreprise->id)
            ->pluck('id')
            ->toArray();
        
        foreach ($directChildren as $childId) {
            $childrenIds[] = $childId;
            // Récursivement, récupérer les petits-enfants
            $grandChildren = $this->getAllChildrenIds($childId);
            $childrenIds = array_merge($childrenIds, $grandChildren);
        }
        
        return $childrenIds;
    }
    
    /**
     * Récupère tous les IDs d'anciens comptes pour une liste de comptes SYCEBNL
     */
    public function getOldAccountIdsForNewAccounts($newAccountIds, $exo)
    {
        return AccountMapping::whereIn('new_account_id', $newAccountIds)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->pluck('old_account_id')
            ->unique()
            ->toArray();
    }
    
    /**
     * Calcule le solde pour un compte parent en agrégeant tous ses enfants
     */
    public function calculateParentBalance($parentAccount)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        // Récupérer tous les IDs des comptes enfants (récursivement)
        $allAccountIds = [$parentAccount->id];
        
        // Vérifier si ce compte a des enfants
        $hasChildren = NewAccount::where('parent_id', $parentAccount->id)
            ->where('entreprise_id', $this->entreprise->id)
            ->exists();
        
        if ($hasChildren) {
            $childrenIds = $this->getAllChildrenIds($parentAccount->id);
            $allAccountIds = array_merge($allAccountIds, $childrenIds);
        }
        
        if ($this->debugMode) {
            \Log::info('Calcul pour parent: ' . $parentAccount->code, [
                'parent_id' => $parentAccount->id,
                'has_children' => $hasChildren,
                'all_account_ids' => $allAccountIds
            ]);
        }
        
        // Récupérer tous les anciens comptes mappés à ces comptes SYCEBNL
        $oldAccountIds = $this->getOldAccountIdsForNewAccounts($allAccountIds, $exo);
        
        if (empty($oldAccountIds)) {
            if ($this->debugMode) {
                \Log::info('Aucun mapping trouvé pour le parent: ' . $parentAccount->code);
            }
            return null;
        }
        
        // Récupérer les mappings avec les détails des anciens comptes
        $mappings = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->with('oldAccount')
            ->get();
        
        // Récupérer toutes les écritures pour ces anciens comptes
        $query = GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('validated', true);
        
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
        
        $ecritures = $query->get();
        
        if ($ecritures->isEmpty() && $this->debugMode) {
            \Log::info('Aucune écriture pour le parent: ' . $parentAccount->code);
        }
        
        // Calcul des totaux
        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde = $totalDebit - $totalCredit;
        
        // Organiser les données par ancien compte
        $oldAccountsData = [];
        foreach ($mappings->groupBy('old_account_id') as $oldAccountId => $accountMappings) {
            $oldEcritures = $ecritures->where('old_account_id', $oldAccountId);
            
            if ($oldEcritures->isNotEmpty()) {
                $oldAccount = $mappings->firstWhere('old_account_id', $oldAccountId)->oldAccount;
                
                $oldTotalDebit = $oldEcritures->sum('debit');
                $oldTotalCredit = $oldEcritures->sum('credit');
                
                $oldAccountsData[] = [
                    'id' => $oldAccountId,
                    'code' => $oldAccount->code,
                    'intitule' => $oldAccount->intitule,
                    'debit' => $oldTotalDebit,
                    'credit' => $oldTotalCredit,
                    'solde' => $oldTotalDebit - $oldTotalCredit,
                    'count' => $oldEcritures->count(),
                    'ecritures' => $oldEcritures,
                    'mapped_to' => $accountMappings->pluck('newAccount.code')->unique()->implode(', '),
                ];
            }
        }
        
        // Organiser les données par compte enfant SYCEBNL
        $childrenData = [];
        foreach ($allAccountIds as $accountId) {
            if ($accountId == $parentAccount->id) continue; // Skip le parent lui-même
            
            $childAccount = NewAccount::find($accountId);
            if (!$childAccount) continue;
            
            $childMappings = $mappings->where('new_account_id', $accountId);
            $childOldAccountIds = $childMappings->pluck('old_account_id')->toArray();
            
            $childEcritures = $ecritures->filter(function($ecriture) use ($childOldAccountIds) {
                return in_array($ecriture->old_account_id, $childOldAccountIds);
            });
            
            if ($childEcritures->isNotEmpty()) {
                $childDebit = $childEcritures->sum('debit');
                $childCredit = $childEcritures->sum('credit');
                
                $childrenData[] = [
                    'id' => $childAccount->id,
                    'code' => $childAccount->code,
                    'intitule' => $childAccount->intitule,
                    'debit' => $childDebit,
                    'credit' => $childCredit,
                    'solde' => $childDebit - $childCredit,
                    'count' => $childEcritures->count(),
                    'old_accounts' => $childMappings->pluck('oldAccount.code')->implode(', '),
                ];
            }
        }
        
        return [
            'id' => $parentAccount->id,
            'code' => $parentAccount->code,
            'intitule' => $parentAccount->intitule,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'solde_absolu' => abs($solde),
            'ecritures_count' => $ecritures->count(),
            'old_accounts_data' => $oldAccountsData,
            'old_accounts_count' => count($oldAccountsData),
            'children_data' => $childrenData,
            'children_count' => count($childrenData),
            'has_children' => $hasChildren,
            'is_parent' => $hasChildren || count($childrenData) > 0,
            'ecritures' => $ecritures,
            'account' => $parentAccount,
        ];
    }
    
    public function getBalances()
    {
        $balances = [];
        
        // Récupérer les comptes parents
        $parentAccounts = $this->getParentAccounts();
        
        if ($this->debugMode) {
            \Log::info('Nombre de comptes parents trouvés: ' . $parentAccounts->count());
            \Log::info('Liste des parents:', $parentAccounts->pluck('code')->toArray());
        }
        
        foreach ($parentAccounts as $parentAccount) {
            $balanceData = $this->calculateParentBalance($parentAccount);
            
            // N'ajouter que s'il y a des écritures
            if ($balanceData && $balanceData['ecritures_count'] > 0) {
                $balances[] = $balanceData;
                
                if ($this->debugMode) {
                    \Log::info('Balance ajoutée pour: ' . $parentAccount->code, [
                        'debit' => $balanceData['total_debit'],
                        'credit' => $balanceData['total_credit'],
                        'enfants' => $balanceData['children_count']
                    ]);
                }
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
    
    /**
     * Méthode de débogage pour voir la hiérarchie des comptes
     */
    public function debugHierarchy()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        // Voir tous les comptes avec leurs parents
        $allAccounts = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->orderBy('code')
            ->get();
        
        $hierarchy = [];
        foreach ($allAccounts as $account) {
            $parent = $account->parent_id ? NewAccount::find($account->parent_id) : null;
            
            // Compter les enfants
            $childrenCount = NewAccount::where('parent_id', $account->id)
                ->where('entreprise_id', $this->entreprise->id)
                ->count();
            
            // Vérifier les mappings
            $mappingsCount = AccountMapping::where('new_account_id', $account->id)
                ->where('exercice_id', $exo->id ?? '')
                ->count();
            
            $hierarchy[] = [
                'id' => $account->id,
                'code' => $account->code,
                'intitule' => $account->intitule,
                'parent_id' => $account->parent_id,
                'parent_code' => $parent ? $parent->code : null,
                'est_parent' => $childrenCount > 0,
                'nb_enfants' => $childrenCount,
                'a_mappings' => $mappingsCount > 0,
                'nb_mappings' => $mappingsCount,
            ];
        }
        
        return $hierarchy;
    }
    
    public function syncAllMappings()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            $updated = 0;
            
            GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNotNull('old_account_id')
                ->chunk(100, function ($ecritures) use (&$updated) {
                    foreach ($ecritures as $ecriture) {
                        if ($ecriture->syncMapping()) {
                            $updated++;
                        }
                    }
                });
            
            session()->flash('success', "$updated écriture(s) synchronisée(s) avec les mappings.");
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "Synchronisation terminée : $updated écriture(s) mises à jour."
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }
    
    public function debugInfo()
    {
        $this->debugMode = true;
        $balances = $this->getBalances();
        
        return [
            'hierarchie' => $this->debugHierarchy(),
            'parents_trouves' => $this->getParentAccounts()->pluck('code')->toArray(),
            'balances_calculees' => count($balances),
            'balances_details' => collect($balances)->map(function($b) {
                return [
                    'code' => $b['code'],
                    'enfants' => $b['children_count'],
                    'ecritures' => $b['ecritures_count'],
                    'debit' => $b['total_debit'],
                    'credit' => $b['total_credit']
                ];
            })->toArray()
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
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->reset(['dateDebut', 'dateFin', 'exercice', 'search']);
        //$this->dateDebut = '2024-01-01';
        //$this->dateFin = '2024-12-31';
        //$this->exercice = 2024;
        $this->dateDebut = $exo->date_debut ?? '2024-12-31';
        $this->dateFin = $exo->date_fin ?? '2024-12-31';
        $this->exercice = date('Y', strtotime($exo->date_debut));
        $this->resetPage();
    }
    
    public function toggleDebug()
    {
        $this->debugMode = !$this->debugMode;
    }
    
   /**
 * Exporter en Excel
 */
public function exportExcel()
{
    $this->loadingExcel = true;
    
    try {
        // Simuler un délai pour voir le loader (à retirer en production)
        // sleep(1);
        
        return Excel::download(
            new BalanceExport(
                $this->entreprise,
                $this->dateDebut,
                $this->dateFin,
                $this->exercice,
                $this->search,
                $this->balanceType
            ),
            'balance_' . $this->balanceType . '_' . date('Y-m-d') . '.xlsx'
        );
    } catch (\Exception $e) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur lors de l\'export : ' . $e->getMessage()
        ]);
    } finally {
        $this->loadingExcel = false;
    }
}

/**
 * Imprimer
 */
public function print()
{
    $this->loadingPrint = true;
    
    try {
        $this->dispatch('print-balance');
    } catch (\Exception $e) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur lors de l\'impression'
        ]);
    } finally {
        // On remet à false après un délai pour que le loader soit visible
        // Le script d'impression va remettre à false via un événement
        $this->loadingPrint = false;
    }
}
/**
 * Exporter en PDF - Version simplifiée
 */
public function exportPdf()
{
    $balances = $this->getBalances();
    $stats = $this->calculateStats();
    
    // Convertir toutes les données en JSON puis les re-décoder pour nettoyer
    $balances = json_decode(json_encode($balances, JSON_UNESCAPED_UNICODE), true);
    
    $pdf = Pdf::loadView('exports.balance-pdf', [
        'balances' => $balances,
        'stats' => $stats,
        'entreprise' => $this->cleanString($this->entreprise->nom ?? $this->entreprise->name ?? 'Entreprise'),
        'dateDebut' => $this->dateDebut,
        'dateFin' => $this->dateFin,
        'balanceType' => $this->balanceType
    ]);
    
    return $pdf->download('balance_' . $this->balanceType . '_' . date('Y-m-d') . '.pdf');
}

private function cleanString($string)
{
    if (!is_string($string)) {
        return $string;
    }
    
    // Supprimer tous les caractères non imprimables
    return preg_replace('/[[:^print:]]/', '', $string);
}
    
    public function render()
    {
        $balances = $this->getBalances();
        $this->stats = $this->calculateStats();
        
        $debug = $this->debugMode ? $this->debugInfo() : null;
        
        return view('livewire.balance.index', [
            'balances' => $balances,
            'stats' => $this->stats,
            'debug' => $debug,
            'debugMode' => $this->debugMode,
        ]);
    }
}