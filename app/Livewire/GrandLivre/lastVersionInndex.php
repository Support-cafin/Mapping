<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\Journal;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Illuminate\Support\Facades\Cache;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Index extends Component
{
    use WithPagination, WithFileUploads;

    // Entreprise
    public $entreprise;
    
    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
        'mappingDeleted' => 'handleMappingDeleted',
        'close-edit-modal' => 'closeModalAndReset',
        'close-add-modal' => 'closeAddModal',
        'loadMore' => 'loadMore',
    ];

    // Filtres
    public $sourceFilter = 'all';
    public $allOldAccounts = [];
    public $searchAccount = '';
    public $mappingFilter = 'all';
    public $search = '';
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $journalCode = '';
    public $accountType = 'all';
    public $accountId = '';
    public $lettre = '';

    // Import
    public $importFile;
    public $showImportModal = false;
    public $importExercice;
    public $autoValidate = true;
    public $importResult = null;
    public $importing = false;
    public $importErrors = [];
    public $importSuccess = false;
    public $importWarnings = [];

    // Édition
    public $editingEcriture = null;
    public $editDate;
    public $editJournal;
    public $editPiece;
    public $editLibelle;
    public $editDebit;
    public $editCredit;
    public $editOldAccountId;
    public $editExercice;
    public $editLettre;
    public $showEditModal = false;
    
    // Ajout
    public $showAddModal = false;
    public $addDate;
    public $addJournal = '';
    public $addPiece = '';
    public $addLibelle = '';
    public $addDebit = '';
    public $addCredit = '';
    public $addOldAccountId = '';
    public $addExercice;
    public $addLettre = '';

    // Sélection
    public $selectedIds = [];
    
    public $importCompleted = false;
    public $importResultFile = null;
    
    // Par ceci
    public $selectedAccounts = [];
    
    // Ajoutez aussi cette propriété pour le mode de sélection
    public $accountSelectionMode = 'multiple'; // 'single' ou 'multiple'
    public $expandedAccounts = [];
    
    public $groupByAccount = false;

    // Stats
    public $stats = [
        'total' => 0,
        'total_debit' => 0,
        'total_credit' => 0,
        'solde' => 0,
        'count_journaux' => 0,
    ];

    // Pagination et infinite scroll
    public $perPage = 1000;
    public $loadedCount = 0;
    public $totalCount = 0;
    public $hasMore = true;
    public $isLoading = false;
    public $sortField = 'date_ecriture';
    public $sortDirection = 'desc';
    public $importedCount = 0;

    // QueryString pour persistance
    /*protected $queryString = [
        'search' => ['except' => ''],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'journalCode' => ['except' => ''],
        'accountType' => ['except' => 'all'],
        'accountId' => ['except' => ''],
        'lettre' => ['except' => ''],
        'sourceFilter' => ['except' => 'all'],
        'mappingFilter' => ['except' => 'all'],
    ];*/
    
    protected $queryString = [
    'search' => ['except' => ''],
    'dateDebut' => ['except' => ''],
    'dateFin' => ['except' => ''],
    'exercice' => ['except' => ''],
    'journalCode' => ['except' => ''],
    'accountType' => ['except' => 'all'],
    'selectedAccounts' => ['except' => ''],  // Changé
    'lettre' => ['except' => ''],
    'sourceFilter' => ['except' => 'all'],
    'mappingFilter' => ['except' => 'all'],
    ];

    // Constante de limite
    const MAX_RESULTS = 25000;

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        
        // Filtres par défaut
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = null;
        $this->journalCode = '';
        $this->accountType = 'all';
        $this->accountId = '';
        $this->lettre = '';
        $this->sourceFilter = 'all';
        $this->mappingFilter = 'all';

        $this->importExercice = date('Y');
        $this->loadOldAccounts();
        $this->updateStats();
    }
    
    /*public function loadOldAccounts()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->allOldAccounts = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->orderByRaw('LENGTH(code) ASC, code ASC')
            ->get(['id', 'code', 'intitule']);
    }*/
    
    public function loadOldAccounts()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        $this->allOldAccounts = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->orderByRaw('LEFT(code, 1) ASC, LENGTH(code) ASC, code ASC')
            ->get(['id', 'code', 'intitule']);
        
        // Optionnel : Grouper par catégorie pour l'affichage
        $this->groupedAccounts = $this->allOldAccounts->groupBy(function($account) {
            return substr($account->code, 0, 1); // Premier chiffre du code
        })->sortKeys();
    }
    
    public function getFilteredAccountsProperty()
    {
        return \App\Models\OldAccount::query()
            ->when($this->searchAccount, function ($query) {
                $query->where('code', 'like', $this->searchAccount . '%') // commence par
                      ->orWhere('intitule', 'like', '%' . $this->searchAccount . '%');
            })
            ->orderBy('code')
            ->limit(20)
            ->get();
}

    /*public function updated($property)
    {
        $filterProperties = [
            'search', 'dateDebut', 'dateFin', 'exercice', 'journalCode',
            'accountType', 'accountId', 'lettre', 'sourceFilter', 'mappingFilter'
        ];

        if (in_array($property, $filterProperties)) {
            $this->resetPagination();
            $this->updateStats();
        }
    }*/
    
    public function updated($property)
    {
        $filterProperties = [
            'search', 'dateDebut', 'dateFin', 'exercice', 'journalCode',
            'accountType', 'selectedAccounts', 'lettre', 'sourceFilter', 
            'mappingFilter', 'groupByAccount'  // Ajouté
        ];
    
        if (in_array($property, $filterProperties)) {
            $this->resetPagination();
            $this->updateStats();
        }
        
        // Si on change le type de compte ou la sélection, on réinitialise l'expansion
        if ($property === 'accountType' || $property === 'selectedAccounts') {
            $this->expandedAccounts = [];
        }
    }

    public function resetPagination()
    {
        $this->loadedCount = 0;
        $this->hasMore = true;
        $this->isLoading = false;
    }

    public function updateStats()
    {
        $cacheKey = "grand_livre_stats_{$this->entreprise->id}_{$this->dateDebut}_{$this->dateFin}_{$this->exercice}_{$this->journalCode}_{$this->accountType}_{$this->accountId}_{$this->lettre}_{$this->mappingFilter}";

        $this->stats = Cache::remember($cacheKey, 300, function () {
            $query = $this->getBaseQuery();
            
            return [
                'total' => $this->getTotalCount(),
                'total_debit' => $query->sum('debit'),
                'total_credit' => $query->sum('credit'),
                'solde' => abs($query->sum('debit') - $query->sum('credit')),
                'count_journaux' => $query->distinct('journal_code')->count('journal_code'),
                'mapped_count' => $this->getBaseQuery()->whereNotNull('new_account_id')->count(),
                'unmapped_count' => $this->getBaseQuery()->whereNull('new_account_id')->count(),
            ];
        });
    }

    public function loadMore()
    {
        if ($this->isLoading || !$this->hasMore) {
            return;
        }

        $this->isLoading = true;
        
        usleep(300000); // 0.3 seconde
        
        $this->loadedCount += $this->perPage;
        
        if ($this->loadedCount >= $this->getTotalCount()) {
            $this->hasMore = false;
        }
        
        $this->isLoading = false;
    }

    protected function getTotalCount()
    {
        return $this->getBaseQuery()->count();
    }
    
    public function selectAccount($accountId)
    {
        $this->addOldAccountId = $accountId;
        $this->searchAccount = ''; // Effacer la recherche après sélection
        
        // Optionnel : réinitialiser le champ de recherche
        $this->dispatch('accountSelected');
    }
    
    public function updatedSearchAccount()
    {
        // La recherche est automatiquement gérée par le debounce dans le HTML
        // Vous pouvez ajouter une logique supplémentaire ici si nécessaire
    }
    /*protected function getBaseQuery()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = GrandLivre::select([
            'id', 'date_ecriture', 'journal_code', 'piece',
            'libelle', 'debit', 'credit', 'old_account_id',
            'new_account_id', 'exercice','exercice_id', 'lettre', 'source'
        ])->where('exercice_id', $exo->id ?? '')
        ->with(['oldAccount:id,code,intitule', 'newAccount:id,code,intitule'])
        ->forEntreprise($this->entreprise->id)
        ->valides();

        // Appliquer les filtres
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }

        if ($this->exercice) {
            $query->where('exercice', $this->exercice);
        }

        if ($this->journalCode) {
            $query->where('journal_code', $this->journalCode);
        }

        if ($this->lettre) {
            if ($this->lettre === 'non') {
                $query->whereNull('lettre');
            } else {
                $query->where('lettre', $this->lettre);
            }
        }

        if ($this->accountType !== 'all' && $this->accountId) {
            if ($this->accountType === 'old') {
                $query->where('old_account_id', $this->accountId);
            } elseif ($this->accountType === 'new') {
                $query->where('new_account_id', $this->accountId);
            }
        }

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('libelle', 'like', '%' . $this->search . '%')
                  ->orWhere('piece', 'like', '%' . $this->search . '%')
                  ->orWhereHas('oldAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('newAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->sourceFilter !== 'all') {
            $query->where('source', $this->sourceFilter);
        }

        if ($this->mappingFilter !== 'all') {
            if ($this->mappingFilter === 'mapped') {
                $query->whereNotNull('new_account_id');
            } elseif ($this->mappingFilter === 'unmapped') {
                $query->whereNull('new_account_id');
            }
        }

        // Appliquer le tri
        if ($this->sortField === 'source') {
            $query->orderByRaw("CASE WHEN source = 'manuel' THEN 1 ELSE 0 END")
                  ->orderBy($this->sortField, $this->sortDirection);
        } elseif ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return $query;
    }*/
    
    /*protected function getBaseQuery()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = GrandLivre::select([
            'id', 'date_ecriture', 'journal_code', 'piece',
            'libelle', 'debit', 'credit', 'old_account_id',
            'new_account_id', 'exercice','exercice_id', 'lettre', 'source'
        ])->where('exercice_id', $exo->id ?? '')
        ->with(['oldAccount:id,code,intitule', 'newAccount:id,code,intitule'])
        ->forEntreprise($this->entreprise->id)
        ->valides();
    
        // Appliquer les filtres
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
    
        if ($this->exercice) {
            $query->where('exercice', $this->exercice);
        }
    
        if ($this->journalCode) {
            $query->where('journal_code', $this->journalCode);
        }
    
        if ($this->lettre) {
            if ($this->lettre === 'non') {
                $query->whereNull('lettre');
            } else {
                $query->where('lettre', $this->lettre);
            }
        }
    
        // MODIFICATION ICI - FILTRE PAR PLUSIEURS COMPTES
        if ($this->accountType !== 'all' && !empty($this->selectedAccounts)) {
            if ($this->accountType === 'old') {
                $query->whereIn('old_account_id', $this->selectedAccounts);
            } elseif ($this->accountType === 'new') {
                $query->whereIn('new_account_id', $this->selectedAccounts);
            }
        }
    
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('libelle', 'like', '%' . $this->search . '%')
                  ->orWhere('piece', 'like', '%' . $this->search . '%')
                  ->orWhereHas('oldAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('newAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  });
            });
        }
    
        if ($this->sourceFilter !== 'all') {
            $query->where('source', $this->sourceFilter);
        }
    
        if ($this->mappingFilter !== 'all') {
            if ($this->mappingFilter === 'mapped') {
                $query->whereNotNull('new_account_id');
            } elseif ($this->mappingFilter === 'unmapped') {
                $query->whereNull('new_account_id');
            }
        }
    
        // Appliquer le tri
        if ($this->sortField === 'source') {
            $query->orderByRaw("CASE WHEN source = 'manuel' THEN 1 ELSE 0 END")
                  ->orderBy($this->sortField, $this->sortDirection);
        } elseif ($this->sortField) {
            $query->orderBy($this->sortField, $this->sortDirection);
        }
    
        return $query;
    }*/
    
    protected function getBaseQuery($forGrouping = false)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        $query = GrandLivre::select([
            'id', 'date_ecriture', 'journal_code', 'piece',
            'libelle', 'debit', 'credit', 'old_account_id',
            'new_account_id', 'exercice','exercice_id', 'lettre', 'source'
        ])->where('exercice_id', $exo->id ?? '')
        ->with(['oldAccount:id,code,intitule', 'newAccount:id,code,intitule'])
        ->forEntreprise($this->entreprise->id)
        ->valides();
    
        // Appliquer les filtres (identique à avant)
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
    
        if ($this->exercice) {
            $query->where('exercice', $this->exercice);
        }
    
        if ($this->journalCode) {
            $query->where('journal_code', $this->journalCode);
        }
    
        if ($this->lettre) {
            if ($this->lettre === 'non') {
                $query->whereNull('lettre');
            } else {
                $query->where('lettre', $this->lettre);
            }
        }
    
        // Filtre par comptes sélectionnés
        if ($this->accountType !== 'all' && !empty($this->selectedAccounts)) {
            if ($this->accountType === 'old') {
                $query->whereIn('old_account_id', $this->selectedAccounts);
            } elseif ($this->accountType === 'new') {
                $query->whereIn('new_account_id', $this->selectedAccounts);
            }
        }
    
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('libelle', 'like', '%' . $this->search . '%')
                  ->orWhere('piece', 'like', '%' . $this->search . '%')
                  ->orWhereHas('oldAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('newAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  });
            });
        }
    
        if ($this->sourceFilter !== 'all') {
            $query->where('source', $this->sourceFilter);
        }
    
        if ($this->mappingFilter !== 'all') {
            if ($this->mappingFilter === 'mapped') {
                $query->whereNotNull('new_account_id');
            } elseif ($this->mappingFilter === 'unmapped') {
                $query->whereNull('new_account_id');
            }
        }
    
        // Si on veut grouper, on ajoute un order by par compte
        if ($this->groupByAccount && !$forGrouping) {
            if ($this->accountType === 'old') {
                $query->orderBy('old_account_id');
            } elseif ($this->accountType === 'new') {
                $query->orderBy('new_account_id');
            }
        }
    
        // Appliquer le tri standard si pas de groupement
        if (!$this->groupByAccount && $this->sortField) {
            if ($this->sortField === 'source') {
                $query->orderByRaw("CASE WHEN source = 'manuel' THEN 1 ELSE 0 END")
                      ->orderBy($this->sortField, $this->sortDirection);
            } else {
                $query->orderBy($this->sortField, $this->sortDirection);
            }
        }
    
        return $query;
    }

    public function toggleAccount($accountId)
    {
        if (in_array($accountId, $this->selectedAccounts)) {
            $this->selectedAccounts = array_diff($this->selectedAccounts, [$accountId]);
        } else {
            $this->selectedAccounts[] = $accountId;
        }
        
        // Réinitialiser la pagination et mettre à jour les stats
        $this->resetPagination();
        $this->updateStats();
    }
    
    public function selectAllAccounts()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        if ($this->accountType === 'old') {
            $this->selectedAccounts = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->pluck('id')
                ->toArray();
        } elseif ($this->accountType === 'new') {
            $this->selectedAccounts = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->pluck('id')
                ->toArray();
        }
        
        $this->resetPagination();
        $this->updateStats();
    }
    
    public function clearSelectedAccounts()
    {
        $this->selectedAccounts = [];
        $this->resetPagination();
        $this->updateStats();
    }
    
    public function updatedAccountType()
    {
        $this->selectedAccounts = [];
        $this->resetPagination();
        $this->updateStats();
    }
    
    public function getEcrituresProperty()
    {
        if ($this->loadedCount === 0) {
            $this->loadedCount = $this->perPage;
        }

        $query = $this->getBaseQuery();
        $ecritures = $query->limit($this->loadedCount)->get();

        $this->totalCount = $this->getTotalCount();
        
        if ($this->loadedCount >= $this->totalCount) {
            $this->hasMore = false;
        }

        return $ecritures;
    }

    public function calculateClassStats()
{
    $query = $this->getBaseQuery();
    $ecritures = $query->get();

    $classStats = [
        'classe_1_5' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        'classe_6_7' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        //'classe_8' => ['debit' => 0, 'credit' => 0, 'solde' => 0], // ✅ AJOUTER classe 8
        'classe_9' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        'autres_comptes' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        'non_mappes' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
        'global' => ['debit' => 0, 'credit' => 0, 'solde' => 0],
    ];

    foreach ($ecritures as $ecriture) {
        $debit = $ecriture->debit ?? 0;
        $credit = $ecriture->credit ?? 0;

        $classStats['global']['debit'] += $debit;
        $classStats['global']['credit'] += $credit;
        $classStats['global']['solde'] += ($debit - $credit);

        if ($ecriture->newAccount && $ecriture->newAccount->code) {
            $firstDigit = substr($ecriture->newAccount->code, 0, 1);

            if (in_array($firstDigit, ['1', '2', '3', '4', '5'])) {
                $classStats['classe_1_5']['debit'] += $debit;
                $classStats['classe_1_5']['credit'] += $credit;
                $classStats['classe_1_5']['solde'] += ($debit - $credit);
            } elseif (in_array($firstDigit, ['6', '7', '8'])) {
                $classStats['classe_6_7']['debit'] += $debit;
                $classStats['classe_6_7']['credit'] += $credit;
                $classStats['classe_6_7']['solde'] += ($debit - $credit);
                //$classStats['classe_8']['debit'] += $debit;
                //$classStats['classe_8']['credit'] += $credit;
                //$classStats['classe_8']['solde'] += ($debit - $credit);
            } elseif ($firstDigit === '9') {
                // ✅ CORRECTION : '10' n'est pas un premier chiffre
                $classStats['classe_9']['debit'] += $debit;
                $classStats['classe_9']['credit'] += $credit;
                $classStats['classe_9']['solde'] += ($debit - $credit);
            } else {
                $classStats['autres_comptes']['debit'] += $debit;
                $classStats['autres_comptes']['credit'] += $credit;
                $classStats['autres_comptes']['solde'] += ($debit - $credit);
            }
        } else {
            $classStats['non_mappes']['debit'] += $debit;
            $classStats['non_mappes']['credit'] += $credit;
            $classStats['non_mappes']['solde'] += ($debit - $credit);
        }
    }

    return $classStats;
}

    public function calculateTotals()
    {
        $query = $this->getBaseQuery();
        $ecritures = $query->get();

        return [
            'total_debit' => $ecritures->sum('debit'),
            'total_credit' => $ecritures->sum('credit'),
            'difference' => abs($ecritures->sum('debit') - $ecritures->sum('credit')),
        ];
    }

    public function getJournauxProperty()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        return GrandLivre::forEntreprise($this->entreprise->id)
            ->select('journal_code')
            ->distinct()
            ->where('exercice_id', $exo->id ?? '')
            ->whereNotNull('journal_code')
            ->orderBy('journal_code')
            ->pluck('journal_code')
            ->map(function($code) {
                return ['code' => $code, 'intitule' => $code];
            });
    }

    public function getAccountsProperty()
    {
        $query = null;
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        if ($this->accountType === 'old') {
            $query = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '');
        } elseif ($this->accountType === 'new') {
            // ✅ CORRECTION : remplacer >where par ->where
            $query = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '');
        }
    
        if ($query && $this->search) {
            $query->where(function ($q) {
                $q->where('code', 'like', '%' . $this->search . '%')
                  ->orWhere('intitule', 'like', '%' . $this->search . '%');
            });
        }
    
        return $query ? $query->orderByRaw('LENGTH(code) ASC, code ASC')->get() : collect();
    }

    /*public function resetFilters()
    {
        $this->reset([
            'search',
            'dateDebut',
            'dateFin',
            'exercice',
            'journalCode',
            'accountType',
            'accountId',
            'lettre',
            'sourceFilter',
            'mappingFilter'
        ]);

        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->accountType = 'all';
        $this->sourceFilter = 'all';
        $this->mappingFilter = 'all';

        $this->resetPagination();
        $this->updateStats();
    }*/
    
    public function resetFilters()
    {
        $this->reset([
            'search',
            'dateDebut',
            'dateFin',
            'exercice',
            'journalCode',
            'accountType',
            'selectedAccounts',  // Changé de 'accountId'
            'lettre',
            'sourceFilter',
            'mappingFilter'
        ]);
    
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->accountType = 'all';
        $this->sourceFilter = 'all';
        $this->mappingFilter = 'all';
        $this->selectedAccounts = [];  // Ajouté
    
        $this->resetPagination();
        $this->updateStats();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPagination();
    }

    // Méthodes d'édition, suppression, import, etc. (identiques à votre code original)
    public function editEcriture($id)
    {   
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->editingEcriture = GrandLivre::with(['oldAccount', 'newAccount'])
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->findOrFail($id);
       
        $this->editDate = $this->editingEcriture->date_ecriture->format('Y-m-d');
        $this->editJournal = $this->editingEcriture->journal_code;
        $this->editPiece = $this->editingEcriture->piece;
        $this->editLibelle = $this->editingEcriture->libelle;
        $this->editDebit = $this->editingEcriture->debit;
        $this->editCredit = $this->editingEcriture->credit;
        $this->editOldAccountId = $this->editingEcriture->old_account_id;
        $this->editExercice = $this->editingEcriture->exercice;
        $this->editLettre = $this->editingEcriture->lettre;
       
        $this->showEditModal = true;
        $this->dispatch('show-edit-modal');
    }
    
    
    public function updateEcriture()
{
    $this->validate([
        'editDate' => 'required|date',
        'editOldAccountId' => 'required|exists:old_accounts,id',
        'editDebit' => 'nullable|numeric|min:0',
        'editCredit' => 'nullable|numeric|min:0',
        'editLibelle' => 'nullable|string|max:255',
        'editPiece' => 'nullable|string|max:100',
        'editJournal' => 'nullable|string|max:20',
        'editExercice' => 'nullable|integer',
        'editLettre' => 'nullable|string|max:10',
    ], [
        'editOldAccountId.required' => 'Le compte est requis',
    ]);
    
    // ✅ AJOUT : Vérification personnalisée qu'au moins un montant est présent
    if ((!$this->editDebit || $this->editDebit <= 0) && (!$this->editCredit || $this->editCredit <= 0)) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Le débit ou le crédit doit être renseigné avec un montant positif'
        ]);
        return;
    }
    
    try {
        if ($this->editingEcriture->entreprise_id !== $this->entreprise->id) {
            throw new \Exception('Cette écriture ne vous appartient pas');
        }
        
        if ($this->editingEcriture->newAccount && $this->editingEcriture->old_account_id != $this->editOldAccountId) {
            throw new \Exception('Impossible de modifier le compte d\'une écriture déjà mappée');
        }
        
        $this->editingEcriture->update([
            'date_ecriture' => $this->editDate,
            'journal_code' => $this->editJournal,
            'piece' => $this->editPiece,
            'libelle' => $this->editLibelle,
            'debit' => $this->editDebit ?: 0,  // ✅ Sécuriser les valeurs nulles
            'credit' => $this->editCredit ?: 0,
            'old_account_id' => $this->editOldAccountId,
            'exercice' => $this->editExercice,
            'lettre' => $this->editLettre,
            'synced' => false,
        ]);
        
        $this->editingEcriture->syncMapping();
        
        $this->showEditModal = false;
        $this->invalidateCaches();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Écriture modifiée avec succès'
        ]);
        
    } catch (\Exception $e) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
}
    
    public function deleteEcriture($id = null)
    {
        try {
            if (!$id && $this->editingEcriture) {
                $id = $this->editingEcriture->id;
            }
           
            if (!$id) {
                session()->flash('error', 'Aucune écriture à supprimer.');
                return;
            }
           
            $ecriture = GrandLivre::findOrFail($id);
           
            if ($ecriture->entreprise_id !== $this->entreprise->id) {
                session()->flash('error', 'Action non autorisée.');
                return;
            }
           
            if ($ecriture->newAccount) {
                session()->flash('error', 'Impossible de supprimer une écriture déjà mappée.');
                return;
            }
           
            $ecriture->delete();
           
            // Réinitialiser et fermer
            $this->showEditModal = false;
            $this->resetEditFields();
           
            // Rafraîchir
            $this->invalidateCaches();
            $this->updateStats();
           
            session()->flash('success', 'Écriture supprimée avec succès.');
           
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }
    
    public function closeModalAndReset()
    {
        $this->showEditModal = false;
        $this->resetEditFields();
    }
   
    public function lettrerEcriture($id, $lettre)
    {
        $ecriture = GrandLivre::find($id);
       
        if ($ecriture && $ecriture->entreprise_id === $this->entreprise->id) {
            $ecriture->update(['lettre' => $lettre]);
            $this->invalidateCaches();
            session()->flash('success', 'Écriture lettrée: ' . $lettre);
        }
    }
    
    // Ajout
        public function openAddModal()
    {
        $this->resetAddFields();
        $this->addDate = date('Y-m-d');
        $this->addExercice = date('Y');
        $this->showAddModal = true;
    }

    public function resetAddFields()
    {
        $this->reset([
            'addDate',
            'addJournal',
            'addPiece',
            'addLibelle',
            'addDebit',
            'addCredit',
            'addOldAccountId',
            'addExercice',
            'addLettre',
        ]);
    }

    public function addEcriture()
    {
        $this->validate([
            'addDate' => 'required|date',
            'addOldAccountId' => 'required|exists:old_accounts,id',
            'addDebit' => 'required_without:addCredit|numeric|min:0',
            'addCredit' => 'required_without:addDebit|numeric|min:0',
            'addLibelle' => 'nullable|string|max:255',
            'addPiece' => 'nullable|string|max:100',
            'addJournal' => 'nullable|string|max:20',
            'addExercice' => 'nullable|integer',
            'addLettre' => 'nullable|string|max:10',
        ], [
            'addDebit.required_without' => 'Le débit ou le crédit doit être renseigné',
            'addCredit.required_without' => 'Le crédit ou le débit doit être renseigné',
            'addOldAccountId.required' => 'Le compte est requis',
        ]);

        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            // Vérifier que le compte appartient bien à l'entreprise
            $oldAccount = OldAccount::find($this->addOldAccountId);
            if (!$oldAccount || $oldAccount->entreprise_id !== $this->entreprise->id) {
                throw new \Exception('Compte invalide ou non autorisé');
            }

            // Créer l'écriture
            $ecriture = GrandLivre::create([
                'date_ecriture' => $this->addDate,
                'journal_code' => $this->addJournal,
                'piece' => $this->addPiece,
                'libelle' => $this->addLibelle,
                'debit' => $this->addDebit ?? 0,
                'credit' => $this->addCredit ?? 0,
                'old_account_id' => $this->addOldAccountId,
                'exercice' => $this->addExercice,
                'exercice_id' => $exo->id ?? '',
                'lettre' => $this->addLettre,
                'entreprise_id' => $this->entreprise->id,
                'source' => 'manuel',
                'valide' => true,
                'synced' => false,
            ]);

            // Appliquer le mapping si disponible
            $ecriture->syncMapping();

            $this->showAddModal = false;
            $this->resetAddFields();
            $this->invalidateCaches();
            $this->updateStats();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Écriture ajoutée avec succès'
            ]);

            // Rafraîchir les données
            $this->dispatch('refreshGrandLivre');

        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->resetAddFields();
    }
    
    protected function resetEditFields()
    {
        $this->reset([
            'editDate',
            'editJournal',
            'editPiece',
            'editLibelle',
            'editDebit',
            'editCredit',
            'editOldAccountId',
            'editExercice',
            'editLettre',
            'editingEcriture',
        ]);
    }
    
   public function import()
{
    $this->validate([
        'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        'importExercice' => 'required|integer|min:2000|max:' . (date('Y') + 5),
    ]);

    $this->importing = true;
    $this->importErrors = [];
    $this->importSuccess = false;
    $this->importWarnings = [];
    $this->importCompleted = false; // Initialisé à false
    
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    try {
        // Récupérer le nom de l'entreprise
        $entrepriseName = $this->entreprise->nom ?? $this->entreprise->raison_sociale ?? 'Entreprise';
        
        \Illuminate\Support\Facades\Log::info('Début import Grand Livre', [
            'file' => $this->importFile->getClientOriginalName(),
            'entreprise_id' => $this->entreprise->id,
            'exercice' => $this->importExercice,
            'exercice_id' => $exo->id ?? '',
            'auto_validate' => $this->autoValidate
        ]);

        $importClass = new \App\Imports\GrandLivreImport(
            $this->entreprise->id,
            $this->importExercice,
            $this->autoValidate,
            $entrepriseName
        );

        \Maatwebsite\Excel\Facades\Excel::import($importClass, $this->importFile->getRealPath());

        $importedCount = $importClass->getImportedCount();
        $errors = $importClass->getErrors();
        $warnings = $importClass->getWarnings();
        
        // Récupérer le chemin du fichier de résultat
        //$this->importResultFile = $importClass->getResultFilePath();

        \Illuminate\Support\Facades\Log::info('Import terminé', [
            'imported' => $importedCount,
            'errors_count' => count($errors),
            'warnings_count' => count($warnings)
        ]);

        if ($importedCount === 0) {
            if (empty($errors)) {
                $this->importErrors[] = "❌ Aucune écriture importée. Vérifiez le format du fichier :";
                $this->importErrors[] = "• La ligne 1 doit contenir : date, journal, compte, libelle, piece, debit, credit";
                $this->importErrors[] = "• Les lignes suivantes doivent contenir les données";
                $this->importErrors[] = "• Au moins une colonne débit ou crédit doit être renseignée";
               
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Aucune écriture importée. Vérifiez le format du fichier.'
                ]);
            } else {
                $this->importErrors = $errors;
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Erreurs d\'importation détectées.'
                ]);
            }
            $this->importing = false;
            return;
        }

        $this->importSuccess = true;
        $this->importCompleted = true; // IMPORTANT : Définir à true après import réussi
        $this->importErrors = array_merge($errors, $warnings);
        $this->importedCount = $importedCount; // Assurez-vous d'avoir cette propriété

        $this->invalidateCaches();
        $this->resetPage();
        $this->updateStats();

        $message = "✓ $importedCount écriture(s) importée(s) avec succès !";
       
        if (!empty($warnings)) {
            $message .= " ⚠️ " . count($warnings) . " avertissement(s).";
        }

        if (!empty($errors)) {
            $message .= " ❌ " . count($errors) . " erreur(s).";
        }

        $this->dispatch('notify', [
            'type' => count($errors) > 0 ? 'warning' : 'success',
            'message' => $message
        ]);

        if (empty($errors)) {
            $this->dispatch('close-modal-after-delay');
        }

        session()->flash('success', $message);

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
       
        foreach ($failures as $failure) {
            $this->importErrors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
        }
       
        \Illuminate\Support\Facades\Log::error('Import validation failed', [
            'failures' => $failures
        ]);
       
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreurs de validation. Vérifiez les détails.'
        ]);
       
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        $this->importErrors[] = "❌ Erreur d'importation : " . $errorMessage;
       
        \Illuminate\Support\Facades\Log::error('Import failed', [
            'error' => $errorMessage,
            'trace' => $e->getTraceAsString()
        ]);
       
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur lors de l\'importation : ' . $errorMessage
        ]);
    }

    $this->importing = false;
}
    
    /*public function downloadImportResult()
    {
        if ($this->importResultFile && file_exists($this->importResultFile)) {
            return response()->download(
                $this->importResultFile,
                'resultat_import_' . date('Y-m-d_H-i-s') . '.pdf' // Changé en .pdf
            );
        }
        
        session()->flash('error', 'Fichier de résultat non trouvé.');
    }*/
    
    public function resetImport()
    {
        $this->importFile = null;
        $this->importCompleted = false;
        //$this->importResultFile = null;
        $this->importErrors = [];
        $this->importWarnings = [];
        $this->importSuccess = false;
    }

    private function createGrandLivreTemplate()
    {
        $dir = storage_path('app/templates');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
   
        $filepath = storage_path('app/templates/template_grand_livre.xlsx');
   
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
   
        $headers = ['date', 'journal', 'compte', 'libelle', 'piece', 'debit', 'credit'];
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . '1', $header);
        }
   
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
   
        $examples = [
            ['15/01/2024', 'ACH', '401', 'Achat marchandises', 'F-2024-001', '', '50000'],
            ['15/01/2024', 'ACH', '607', 'Achat marchandises', 'F-2024-001', '50000', ''],
            ['20/01/2024', 'VTE', '411', 'Vente client ABC', 'V-2024-015', '120000', ''],
            ['20/01/2024', 'VTE', '701', 'Vente client ABC', 'V-2024-015', '', '100000'],
            ['20/01/2024', 'VTE', '445', 'TVA collectée', 'V-2024-015', '', '20000'],
            ['25/01/2024', 'BQ', '512', 'Virement reçu', 'VIR-001', '120000', ''],
            ['25/01/2024', 'BQ', '411', 'Virement reçu', 'VIR-001', '', '120000'],
        ];
        $row = 2;
        foreach ($examples as $example) {
            $sheet->setCellValue('A' . $row, $example[0]);
            $sheet->setCellValue('B' . $row, $example[1]);
            $sheet->setCellValue('C' . $row, $example[2]);
            $sheet->setCellValue('D' . $row, $example[3]);
            $sheet->setCellValue('E' . $row, $example[4]);
            $sheet->setCellValue('F' . $row, $example[5]);
            $sheet->setCellValue('G' . $row, $example[6]);
           
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');
            }
           
            $row++;
        }
        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(35);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(15);
        $sheet->getColumnDimension('G')->setWidth(15);
   
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle('A1:G' . ($row - 1))->applyFromArray($styleArray);
   
        $sheet->getStyle('F2:G' . ($row - 1))->getNumberFormat()
            ->setFormatCode('#,##0.00');
   
        $noteRow = $row + 2;
        $sheet->setCellValue('A' . $noteRow, 'INSTRUCTIONS D\'IMPORT :');
        $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $noteRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FEF3C7');
       
        $instructions = [
            '1. La première ligne DOIT contenir les en-têtes : date, journal, compte, libelle, piece, debit, credit',
            '2. DATE : Format JJ/MM/AAAA (ex: 15/01/2024) ou format Excel',
            '3. JOURNAL : Code journal existant (ex: ACH, VTE, BQ) - optionnel',
            '4. COMPTE : Code du compte existant (ancien ou nouveau plan) - OBLIGATOIRE',
            '5. LIBELLE : Description de l\'écriture - recommandé',
            '6. PIECE : Numéro de pièce justificative - optionnel',
            '7. DEBIT : Montant au débit (laisser vide si crédit)',
            '8. CREDIT : Montant au crédit (laisser vide si débit)',
            '9. Une seule des colonnes DEBIT ou CREDIT doit être renseignée par ligne',
            '10. Les montants peuvent utiliser la virgule ou le point comme séparateur décimal',
        ];
       
        $instructionRow = $noteRow + 1;
        foreach ($instructions as $instruction) {
            $sheet->setCellValue('A' . $instructionRow, $instruction);
            $sheet->mergeCells('A' . $instructionRow . ':G' . $instructionRow);
            $sheet->getStyle('A' . $instructionRow)->getAlignment()->setWrapText(true);
            $instructionRow++;
        }
   
        $planRow = $instructionRow + 2;
        $sheet->setCellValue('A' . $planRow, 'EXEMPLES DE CODES COMPTES COURANTS :');
        $sheet->getStyle('A' . $planRow)->getFont()->setBold(true)->setSize(11);
       
        $plansExamples = [
            '• 101 : Capital',
            '• 401 : Fournisseurs',
            '• 411 : Clients',
            '• 445 : État - TVA',
            '• 512 : Banque',
            '• 607 : Achats de marchandises',
            '• 701 : Ventes de marchandises',
        ];
       
        $planExRow = $planRow + 1;
        foreach ($plansExamples as $planEx) {
            $sheet->setCellValue('A' . $planExRow, $planEx);
            $sheet->mergeCells('A' . $planExRow . ':G' . $planExRow);
            $planExRow++;
        }
   
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
    }
    
    public function deleteSelected(array $ids)
{
    $user = auth()->user();
    
    Log::info('Suppression sélectionnée avec param', [
        'ids_reçus' => $ids,
        'user_admin' => $user->is_admin ?? false
    ]);
    
    if (!$user || !$user->is_admin) {
        $this->dispatch('alert', [
            'type' => 'error',
            'message' => 'Action non autorisée.'
        ]);
        return;
    }
    
    if (empty($ids)) {
        $this->dispatch('alert', [
            'type' => 'warning',
            'message' => 'Aucun ID reçu.'
        ]);
        return;
    }
    
    try {
        DB::beginTransaction();
        
        // ✅ CORRECTION : Filtrer et convertir les IDs valides uniquement
        $intIds = array_filter(array_map(function($id) {
            return is_numeric($id) ? (int)$id : null;
        }, $ids));
        
        if (empty($intIds)) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'IDs invalides.'
            ]);
            DB::rollBack();
            return;
        }
        
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $deleted = GrandLivre::whereIn('id', $intIds)
            ->where('entreprise_id', $user->entreprise_id)
            ->where('exercice_id', $exo->id ?? '')
            ->delete();
            
        DB::commit();
        
        $this->dispatch('alert', [
            'type' => 'success',
            'message' => "$deleted écriture(s) supprimée(s)."
        ]);
        
        $this->invalidateCaches();
        $this->updateStats();
        $this->selectedIds = []; // ✅ AJOUT : Réinitialiser la sélection
        
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Erreur suppression avec param', ['error' => $e->getMessage()]);
        
        $this->dispatch('alert', [
            'type' => 'error',
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
}
   
    public function deleteAllVisible()
    {
        $user = auth()->user();
       
        if (!$user || !$user->is_admin) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Action non autorisée.'
            ]);
            return;
        }
       
        try {
            $count = $this->getVisibleCount();
           
            if ($count === 0) {
                $this->dispatch('alert', [
                    'type' => 'warning',
                    'message' => 'Aucune écriture visible à supprimer.'
                ]);
                return;
            }
           
            DB::beginTransaction();
           
            $query = GrandLivre::where('entreprise_id', $user->entreprise_id);
            $exo = DB::table('exercices')->where('statut', 1)->first();
            $query->where('exercice_id', $exo->id ?? '');
           
            if ($this->dateDebut) {
                $query->where('date_ecriture', '>=', $this->dateDebut);
            }
           
            if ($this->dateFin) {
                $query->where('date_ecriture', '<=', $this->dateFin);
            }
           
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('libelle', 'LIKE', "%{$this->search}%")
                      ->orWhere('piece', 'LIKE', "%{$this->search}%")
                      ->orWhere('journal_code', 'LIKE', "%{$this->search}%")
                      ->orWhereHas('oldAccount', function($q2) {
                          $q2->where('code', 'LIKE', "%{$this->search}%")
                             ->orWhere('intitule', 'LIKE', "%{$this->search}%");
                      });
                });
            }
           
            $deleted = $query->delete();
           
            DB::commit();
           
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => "$deleted écriture(s) supprimée(s)."
            ]);
           
            $this->invalidateCaches();
            $this->updateStats();
           
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
    
    public function deleteSelectedWireModel()
    {
        $user = auth()->user();
       
        // Log pour débogage
        Log::info('Suppression sélectionnée wire:model', [
            'user_id' => $user->id ?? null,
            'is_admin' => $user->is_admin ?? false,
            'selected_ids' => $this->selectedIds,
            'count' => count($this->selectedIds)
        ]);
       
        if (!$user || !$user->is_admin) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Action non autorisée. Administrateur requis.'
            ]);
            return;
        }
       
        if (empty($this->selectedIds)) {
            $this->dispatch('alert', [
                'type' => 'warning',
                'message' => 'Aucune écriture sélectionnée.'
            ]);
            return;
        }
       
        try {
            DB::beginTransaction();
           
            // Convertir les IDs en entiers
            $ids = array_map('intval', $this->selectedIds);
           
            Log::info('IDs convertis', ['ids' => $ids]);
           
            // Vérifier d'abord combien d'écritures existent
            $existingCount = GrandLivre::whereIn('id', $ids)
                ->where('entreprise_id', $user->entreprise_id)
                ->count();
               
            Log::info('Écritures existantes', ['count' => $existingCount]);
           
            if ($existingCount === 0) {
                $this->dispatch('alert', [
                    'type' => 'warning',
                    'message' => 'Aucune écriture correspondante trouvée.'
                ]);
                DB::rollBack();
                return;
            }
           
           $exo = DB::table('exercices')->where('statut', 1)->first();
            // Supprimer
            $deleted = GrandLivre::whereIn('id', $ids)
                ->where('entreprise_id', $user->entreprise_id)
                ->where('exercice_id', $exo->id ?? '')
                ->delete();
               
            DB::commit();
           
            Log::info('Suppression réussie', ['deleted' => $deleted]);
           
            // Réinitialiser la sélection
            $this->selectedIds = [];
           
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => "$deleted écriture(s) supprimée(s) avec succès."
            ]);
           
            // Rafraîchir les données
            $this->invalidateCaches();
            $this->updateStats();
           
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erreur suppression wire:model', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
           
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
    
    protected function invalidateCaches()
    {
        // ✅ CORRECTION : Utiliser forget() ou tags si disponible
        try {
            // Supprimer les caches spécifiques avec des clés précises
            $cacheKey = "grand_livre_stats_{$this->entreprise->id}_{$this->dateDebut}_{$this->dateFin}_{$this->exercice}_{$this->journalCode}_{$this->accountType}_{$this->accountId}_{$this->lettre}_{$this->mappingFilter}";
            Cache::forget($cacheKey);
            
            // Alternative: utiliser les tags si votre cache le supporte
            if (method_exists(Cache::getStore(), 'tags')) {
                Cache::tags(['grand_livre', 'entreprise_' . $this->entreprise->id])->flush();
            }
            
        } catch (\Exception $e) {
            Log::warning('Erreur lors de l\'invalidation du cache', ['error' => $e->getMessage()]);
        }
    }
    
    public function deleteAll(string $confirmationCode)
    {
        $user = auth()->user();
       
        if (!$user || !$user->is_admin) {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Action non autorisée.'
            ]);
            return;
        }
       
        if ($confirmationCode !== 'SUPPRIMER-TOUT') {
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Code de confirmation incorrect.'
            ]);
            return;
        }
       
        try {
            $exo = DB::table('exercices')->where('statut', 1)->first();
            $count = GrandLivre::where('entreprise_id', $user->entreprise_id)->where('exercice_id', $exo->id ?? '')->count();
           
            DB::beginTransaction();
           
            $deleted = GrandLivre::where('entreprise_id', $user->entreprise_id)->where('exercice_id', $exo->id ?? '')->delete();
           
            DB::commit();
           
            Log::critical('Suppression totale', [
                'user_id' => $user->id,
                'entreprise_id' => $user->entreprise_id,
                'count' => $deleted
            ]);
           
            $this->dispatch('alert', [
                'type' => 'success',
                'message' => "$deleted écriture(s) supprimée(s)."
            ]);
           
            $this->reset(['dateDebut', 'dateFin', 'search']);
            $this->invalidateCaches();
            $this->updateStats();
           
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('alert', [
                'type' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }

    public function syncAllMappings()
    {   
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            $updated = 0;
           
            GrandLivre::forEntreprise($this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNotNull('old_account_id')
                ->chunk(100, function ($ecritures) use (&$updated) {
                    foreach ($ecritures as $ecriture) {
                        if ($ecriture->syncMapping()) {
                            $updated++;
                        }
                    }
                });
           
            $this->invalidateCaches();
            session()->flash('success', "$updated écriture(s) synchronisée(s) avec les mappings.");
            $this->updateStats();
           
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }
    
    public function handleMappingDeleted($oldAccountId)
    {
        $this->invalidateCaches();
        $this->updateStats();
    }
    
    
   
    public function downloadTemplate()
    {
        $filepath = storage_path('app/templates/template_grand_livre.xlsx');
       
        if (!file_exists($filepath)) {
            $this->createGrandLivreTemplate();
        }
       
        return response()->download($filepath, 'template_grand_livre.xlsx');
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetEditFields();
        $this->dispatch('close-edit-modal');
    }
    
    public function export($format = 'excel')
    {
        // Vérifier le nombre d'écritures avant l'export
        $countQuery = $this->getBaseQuery();
        $totalCount = $countQuery->count();
       
        if ($totalCount > self::MAX_RESULTS) {
            session()->flash('warning', "⚠️ L'export est limité à " . self::MAX_RESULTS . " écritures pour des raisons de performance. Affinez vos filtres.");
        }
       
        if ($format !== 'excel') {
            return redirect()->route('grand-livre.export-pdf', [
                'filters' => $this->getFiltersArray(),
            ]);
        }
       
        return redirect()->route('grand-livre.export-detail', [
            'filters' => $this->getFiltersArray(),
        ]);
    }
    
    /*public function getFiltersArray(): array
    {
        return [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'journalCode' => $this->journalCode,
            'accountType' => $this->accountType,
            'accountId' => $this->accountId,
            'lettre' => $this->lettre,
            'search' => $this->search,
            'entreprise_id' => $this->entreprise->id,
        ];
    }*/
    
    public function getFiltersArray(): array
    {
        return [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'journalCode' => $this->journalCode,
            'accountType' => $this->accountType,
            'selectedAccounts' => $this->selectedAccounts,  // Changé de 'accountId'
            'lettre' => $this->lettre,
            'search' => $this->search,
            'sourceFilter' => $this->sourceFilter,
            'mappingFilter' => $this->mappingFilter,
            'entreprise_id' => $this->entreprise->id,
        ];
    }
    public function toggleAccountGroup($accountId)
    {
        if (in_array($accountId, $this->expandedAccounts)) {
            $this->expandedAccounts = array_diff($this->expandedAccounts, [$accountId]);
        } else {
            $this->expandedAccounts[] = $accountId;
        }
    }
    
    public function expandAllAccounts()
    {
        if ($this->accountType === 'old') {
            $this->expandedAccounts = $this->selectedAccounts;
        } elseif ($this->accountType === 'new') {
            $this->expandedAccounts = $this->selectedAccounts;
        }
    }
    
    public function collapseAllAccounts()
    {
        $this->expandedAccounts = [];
    }
    
    public function getGroupedEcrituresProperty()
{
    if (!$this->groupByAccount || empty($this->selectedAccounts)) {
        return collect();
    }

    // Utiliser la même requête de base que pour les écritures normales
    $query = $this->getBaseQuery();
    
    // Limiter le nombre d'écritures pour éviter les problèmes de performance
    $ecritures = $query->limit(5000)->get(); // Limite raisonnable
    
    if ($ecritures->isEmpty()) {
        return collect();
    }
    
    // Grouper par compte
    $grouped = [];
    
    if ($this->accountType === 'old') {
        $grouped = $ecritures->groupBy('old_account_id');
    } elseif ($this->accountType === 'new') {
        $grouped = $ecritures->groupBy('new_account_id');
    } else {
        return collect();
    }
    
    // Ajouter les totaux par compte
    $result = [];
    foreach ($grouped as $accountId => $items) {
        // Récupérer les infos du compte
        $account = null;
        if ($this->accountType === 'old' && $items->first()->oldAccount) {
            $account = $items->first()->oldAccount;
        } elseif ($this->accountType === 'new' && $items->first()->newAccount) {
            $account = $items->first()->newAccount;
        }
        
        // Si pas de compte, on utilise un placeholder
        $accountCode = $account ? $account->code : 'N/A';
        $accountIntitule = $account ? $account->intitule : 'Compte inconnu';
        
        $totalDebit = $items->sum('debit');
        $totalCredit = $items->sum('credit');
        $solde = $totalDebit - $totalCredit;
        
        $result[] = [
            'account_id' => $accountId,
            'account_code' => $accountCode,
            'account_intitule' => $accountIntitule,
            'ecritures' => $items,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'is_expanded' => in_array($accountId, $this->expandedAccounts),
            'count' => $items->count()
        ];
    }
    
    // Trier par code de compte
    usort($result, function($a, $b) {
        return strcmp($a['account_code'], $b['account_code']);
    });
    
    return collect($result);
}

public function debugGroupedView()
{
    $grouped = $this->groupedEcritures;
    Log::info('Debug grouped view', [
        'groupByAccount' => $this->groupByAccount,
        'selectedAccounts' => $this->selectedAccounts,
        'accountType' => $this->accountType,
        'grouped_count' => $grouped->count(),
        'first_group' => $grouped->first(),
    ]);
    
    return $grouped;
}
    
    
    // ... (Copier toutes les autres méthodes de votre composant original : updateEcriture, deleteEcriture, import, etc.)

    public function render()
    {
        $ecritures = $this->ecritures;
        $totals = $this->calculateTotals();
        $classStats = $this->calculateClassStats();
    
        return view('livewire.grand-livre.index', [
            'ecritures' => $ecritures,
            'journaux' => $this->journaux,
            'accounts' => $this->accounts,
            'allOldAccounts' => $this->allOldAccounts, // AJOUTEZ CETTE LIGNE
            'stats' => $this->stats,
            'totals' => $totals,
            'classStats' => $classStats,
            'loadedCount' => $this->loadedCount,
            'totalCount' => $this->totalCount,
            'hasMore' => $this->hasMore,
            'isLoading' => $this->isLoading,
        ])->layout('layouts.app');
    }
}