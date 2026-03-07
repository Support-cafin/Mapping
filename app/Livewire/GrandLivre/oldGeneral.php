<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class General extends Component
{
    public $entreprise;
    
    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
        'loadMore' => 'loadMore',
    ];

    // Filtres
    public $search = '';
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $journalCode = '';
    public $lettre = '';

    // Pagination et infinite scroll
    public $perPage = 20;
    public $loadedCount = 0;
    public $totalCount = 0;
    public $hasMore = true;
    public $isLoading = false;

    // QueryString pour persistance
    protected $queryString = [
        'search' => ['except' => ''],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'journalCode' => ['except' => ''],
        'lettre' => ['except' => ''],
    ];

    // Cache TTL en secondes
    const CACHE_TTL = 300; // 5 minutes

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        
        // Filtres par défaut
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = null;
        $this->journalCode = '';
        $this->lettre = '';
    }

    public function updated($property)
    {
        $filterProperties = [
            'search', 'dateDebut', 'dateFin', 'exercice', 'journalCode', 'lettre'
        ];

        if (in_array($property, $filterProperties)) {
            $this->resetPagination();
            $this->invalidateCache();
        }
    }

    public function resetPagination()
    {
        $this->loadedCount = 0;
        $this->hasMore = true;
        $this->isLoading = false;
    }

    public function loadMore()
    {
        if ($this->isLoading || !$this->hasMore) {
            return;
        }

        $this->isLoading = true;
        
        usleep(300000);
        
        $this->loadedCount += $this->perPage;
        
        $allData = $this->getAllRecapData();
        if ($this->loadedCount >= count($allData)) {
            $this->hasMore = false;
        }
        
        $this->isLoading = false;
    }

    /**
     * Génère une clé de cache unique basée sur les filtres
     */
    private function getCacheKey(string $prefix): string
    {
        return sprintf(
            "%s_%s_%s_%s_%s_%s_%s_%s",
            $prefix,
            $this->entreprise->id,
            $this->dateDebut,
            $this->dateFin,
            $this->exercice ?? 'null',
            $this->journalCode,
            $this->lettre,
            md5($this->search)
        );
    }

    /**
     * Récupérer TOUTES les données (sans limite)
     */
    private function getAllRecapData(): array
    {
        $cacheKey = $this->getCacheKey('grand_livre_recap_all');
       
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            try {
                // Récupérer les écritures avec filtres appliqués
                $query = GrandLivre::with(['oldAccount:id,code,intitule'])
                    ->where('entreprise_id', $this->entreprise->id)
                    ->whereNotNull('new_account_id')
                    ->valides();
               
                // Appliquer les filtres
                $this->applyFilters($query);
               
                $ecritures = $query->orderBy('date_ecriture')->get();
               
                if ($ecritures->isEmpty()) {
                    return [];
                }
               
                // Grouper par compte SYCEBNL
                return $this->groupByNewAccount($ecritures);
                
            } catch (\Exception $e) {
                Log::error('Erreur lors de la récupération des données du grand livre général', [
                    'entreprise_id' => $this->entreprise->id,
                    'error' => $e->getMessage()
                ]);
                return [];
            }
        });
    }

    /**
     * Applique les filtres à la requête
     */
    private function applyFilters($query): void
    {
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
            $this->lettre === 'non'
                ? $query->whereNull('lettre')
                : $query->where('lettre', $this->lettre);
        }
       
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('libelle', 'like', '%' . $this->search . '%')
                  ->orWhere('piece', 'like', '%' . $this->search . '%')
                  ->orWhereHas('oldAccount', function ($subQ) {
                      $subQ->where('code', 'like', '%' . $this->search . '%')
                           ->orWhere('intitule', 'like', '%' . $this->search . '%');
                  });
            });
        }
    }

    /**
     * Groupe les écritures par compte SYCEBNL
     */
    private function groupByNewAccount($ecritures): array
    {
        $newAccountIds = $ecritures->pluck('new_account_id')->unique()->filter();
       
        if ($newAccountIds->isEmpty()) {
            return [];
        }
       
        // Récupérer les comptes SYCEBNL
        $newAccounts = NewAccount::whereIn('id', $newAccountIds)
            ->orderBy('code')
            ->get();
           
        $recapData = [];
           
        foreach ($newAccounts as $newAccount) {
            // Filtrer les écritures pour ce compte SYCEBNL
            $accountEcritures = $ecritures->filter(function($ecriture) use ($newAccount) {
                return $ecriture->new_account_id == $newAccount->id;
            });
           
            if ($accountEcritures->isEmpty()) {
                continue;
            }
           
            $recapData[] = $this->formatAccountGroup($newAccount, $accountEcritures);
        }
       
        return $recapData;
    }

    /**
     * Formate les données d'un groupe de compte
     */
    private function formatAccountGroup($newAccount, $accountEcritures): array
    {
        // Grouper par ancien compte
        $oldAccountsData = [];
        $groupedByOldAccount = $accountEcritures->groupBy('old_account_id');
       
        foreach ($groupedByOldAccount as $oldAccountId => $oldEcritures) {
            $oldAccount = $oldEcritures->first()->oldAccount;
            $oldAccountsData[] = [
                'account' => $oldAccount,
                'ecritures' => $oldEcritures,
                'total_debit' => $oldEcritures->sum('debit'),
                'total_credit' => $oldEcritures->sum('credit'),
            ];
        }
       
        $totalDebit = $accountEcritures->sum('debit');
        $totalCredit = $accountEcritures->sum('credit');
       
        return [
            'new_account' => $newAccount,
            'new_account_code' => $newAccount->code,
            'new_account_intitule' => $newAccount->intitule,
            'ecritures' => $accountEcritures,
            'old_accounts_data' => $oldAccountsData,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $totalDebit - $totalCredit,
            'nombre_ecritures' => $accountEcritures->count(),
        ];
    }

    /**
     * Récupérer les données paginées (pour affichage progressif)
     */
    public function getRecapDataProperty(): array
    {
        if ($this->loadedCount === 0) {
            $this->loadedCount = $this->perPage;
        }

        $allData = $this->getAllRecapData();
        $this->totalCount = count($allData);
        
        if ($this->loadedCount >= $this->totalCount) {
            $this->hasMore = false;
        }

        // Retourner seulement les N premiers éléments
        return array_slice($allData, 0, $this->loadedCount);
    }
    
    public function getRecapStatsProperty(): array
    {
        $cacheKey = $this->getCacheKey('grand_livre_recap_stats');
       
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            $recapData = $this->getAllRecapData();
           
            $stats = [
                'total_comptes' => 0,
                'total_ecritures' => 0,
                'total_debit' => 0,
                'total_credit' => 0,
            ];
           
            foreach ($recapData as $item) {
                $stats['total_comptes']++;
                $stats['total_ecritures'] += $item['nombre_ecritures'];
                $stats['total_debit'] += $item['total_debit'];
                $stats['total_credit'] += $item['total_credit'];
            }
           
            $soldeGlobal = $stats['total_debit'] - $stats['total_credit'];
           
            return array_merge($stats, [
                'solde_global' => $soldeGlobal,
                'solde_global_absolu' => abs($soldeGlobal),
                'is_debiteur' => $soldeGlobal > 0,
            ]);
        });
    }

    public function getJournauxProperty()
    {
        return GrandLivre::forEntreprise($this->entreprise->id)
            ->select('journal_code')
            ->distinct()
            ->whereNotNull('journal_code')
            ->orderBy('journal_code')
            ->pluck('journal_code')
            ->map(function($code) {
                return ['code' => $code, 'intitule' => $code];
            });
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'dateDebut',
            'dateFin',
            'exercice',
            'journalCode',
            'lettre',
        ]);

        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';

        $this->resetPagination();
        $this->invalidateCache();
    }

    /**
     * Invalide le cache pour les données actuelles
     */
    private function invalidateCache(): void
    {
        $keys = [
            $this->getCacheKey('grand_livre_recap_all'),
            $this->getCacheKey('grand_livre_recap_stats'),
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    public function export($format = 'excel')
    {
        if ($format !== 'excel') {
            return redirect()->route('grand-livre.export-pdf', [
                'filters' => $this->getFiltersArray(),
            ]);
        }
        
        return redirect()->route('grand-livre.export-general', [
            'filters' => $this->getFiltersArray(),
        ]);
    }

    public function getFiltersArray(): array
    {
        return [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'journalCode' => $this->journalCode,
            'lettre' => $this->lettre,
            'search' => $this->search,
        ];
    }

    public function render()
    {
        return view('livewire.grand-livre.general', [
            'recapData' => $this->recapData,
            'recapStats' => $this->recapStats,
            'journaux' => $this->journaux,
            'loadedCount' => $this->loadedCount,
            'totalCount' => $this->totalCount,
            'hasMore' => $this->hasMore,
            'isLoading' => $this->isLoading,
        ])->layout('layouts.app');
    }
}