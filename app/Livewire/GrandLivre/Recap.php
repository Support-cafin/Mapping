<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use App\Models\Entreprise;
use Illuminate\Support\Facades\DB;

class Recap extends Component
{
    use WithPagination;

    public $entreprise;
    public $search = '';
    public $dateDebut = null;
    public $dateFin = null;
    public $exercice = null;
    public $journalCode = '';
    public $lettre = '';

    // Mode d'affichage : 'table' pour le récap, 'detail' pour voir les anciens comptes d'un nouveau compte
    public $viewMode = 'table';
    public $selectedNewAccountId = null;
    public $selectedNewAccount = null;

    // Stats pour l'entête
    public $stats = [
        'total_debit' => 0,
        'total_credit' => 0,
        'solde' => 0,
    ];

    // Pagination
    public $perPage = 20;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'journalCode' => ['except' => ''],
        'lettre' => ['except' => ''],
        'perPage' => ['except' => 20],
        'viewMode' => ['except' => 'table'],
        'selectedNewAccountId' => ['except' => null],
    ];

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->exercice = null; // Exercice courant par défaut
        $this->loadStats();
    }

    public function updated($property)
    {
        // Recharger les données et les stats quand un filtre change
        if (in_array($property, ['search', 'dateDebut', 'dateFin', 'exercice', 'journalCode', 'lettre'])) {
            $this->resetPage();
            $this->loadStats();
        }
    }

    public function loadStats()
    {
        // Calcul des totaux globaux pour la période filtrée
        $query = GrandLivre::query()
            ->select(
                DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit'),
                DB::raw('COALESCE(SUM(debit) - SUM(credit), 0) as solde')
            )
            ->where('entreprise_id', $this->entreprise->id)
            ->valides()
            ->when($this->dateDebut && $this->dateFin, function ($q) {
                $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            })
            ->when($this->exercice, function ($q) {
                $q->whereYear('date_ecriture', $this->exercice);
            });

        $stats = $query->first();

        $this->stats['total_debit'] = $stats->total_debit ?? 0;
        $this->stats['total_credit'] = $stats->total_credit ?? 0;
        $this->stats['solde'] = $stats->solde ?? 0;
    }

    // Méthode principale : Récupère les données regroupées par nouveau compte
    public function getRecapData()
    {
        // Sous-requête pour obtenir les IDs des anciens comptes mappés pour chaque nouveau compte
        $mappedOldAccountIds = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->select('old_account_id', 'new_account_id')
            ->get()
            ->groupBy('new_account_id')
            ->map(function ($item) {
                return $item->pluck('old_account_id')->toArray();
            });

        // Récupère tous les nouveaux comptes qui ont au moins un mapping
        $newAccounts = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereIn('id', $mappedOldAccountIds->keys())
            ->orderBy('code')
            ->get();

        $recapData = [];

        foreach ($newAccounts as $newAccount) {
            // IDs des anciens comptes mappés à ce nouveau compte
            $oldAccountIds = $mappedOldAccountIds->get($newAccount->id, []);

            if (empty($oldAccountIds)) {
                continue;
            }

            // Requête pour agréger les écritures de ces anciens comptes
            $aggregate = GrandLivre::query()
                ->select(
                    DB::raw('COUNT(*) as nombre_ecritures'),
                    DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                    DB::raw('COALESCE(SUM(credit), 0) as total_credit'),
                    DB::raw('COALESCE(SUM(debit) - SUM(credit), 0) as solde')
                )
                ->where('entreprise_id', $this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->valides()
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->whereYear('date_ecriture', $this->exercice);
                })
                ->when($this->journalCode, function ($q) {
                    $q->where('journal_code', $this->journalCode);
                })
                ->when($this->lettre, function ($q) {
                    if ($this->lettre === 'non') {
                        $q->whereNull('lettre');
                    } else {
                        $q->where('lettre', $this->lettre);
                    }
                })
                ->first();

            // Si ce nouveau compte a des écritures pour la période
            if ($aggregate && $aggregate->nombre_ecritures > 0) {
                $recapData[] = [
                    'new_account_id' => $newAccount->id,
                    'new_account_code' => $newAccount->code,
                    'new_account_intitule' => $newAccount->intitule,
                    'nombre_ecritures' => $aggregate->nombre_ecritures,
                    'total_debit' => $aggregate->total_debit,
                    'total_credit' => $aggregate->total_credit,
                    'solde' => $aggregate->solde,
                    'old_account_ids' => $oldAccountIds, // Pour le détail
                ];
            }
        }

        // Appliquer la recherche si besoin (sur le code ou l'intitulé du nouveau compte)
        if (!empty($this->search)) {
            $recapData = collect($recapData)->filter(function ($item) {
                return stripos($item['new_account_code'], $this->search) !== false ||
                       stripos($item['new_account_intitule'], $this->search) !== false;
            })->values()->all();
        }

        // Pagination manuelle (ou vous pouvez utiliser un Collection Paginator)
        return collect($recapData);
    }

    // Méthode pour voir le détail d'un nouveau compte (les anciens comptes associés)
    public function viewDetail($newAccountId)
    {
        $this->selectedNewAccountId = $newAccountId;
        $this->selectedNewAccount = NewAccount::find($newAccountId);
        $this->viewMode = 'detail';
    }

    public function backToRecap()
    {
        $this->viewMode = 'table';
        $this->selectedNewAccountId = null;
        $this->selectedNewAccount = null;
    }

    // Méthode pour récupérer les anciens comptes et leurs totaux pour un nouveau compte sélectionné
    public function getDetailData()
    {
        if (!$this->selectedNewAccountId) {
            return collect();
        }

        // Récupérer les mappings pour ce nouveau compte
        $mappings = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('new_account_id', $this->selectedNewAccountId)
            ->with('oldAccount')
            ->get();

        $detailData = [];

        foreach ($mappings as $mapping) {
            $oldAccount = $mapping->oldAccount;

            if (!$oldAccount) continue;

            $aggregate = GrandLivre::query()
                ->select(
                    DB::raw('COUNT(*) as nombre_ecritures'),
                    DB::raw('COALESCE(SUM(debit), 0) as total_debit'),
                    DB::raw('COALESCE(SUM(credit), 0) as total_credit'),
                    DB::raw('COALESCE(SUM(debit) - SUM(credit), 0) as solde')
                )
                ->where('entreprise_id', $this->entreprise->id)
                ->where('old_account_id', $oldAccount->id)
                ->valides()
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->whereYear('date_ecriture', $this->exercice);
                })
                ->when($this->journalCode, function ($q) {
                    $q->where('journal_code', $this->journalCode);
                })
                ->when($this->lettre, function ($q) {
                    if ($this->lettre === 'non') {
                        $q->whereNull('lettre');
                    } else {
                        $q->where('lettre', $this->lettre);
                    }
                })
                ->first();

            // Inclure même si pas d'écritures pour cette période, pour voir tous les comptes mappés
            $detailData[] = [
                'old_account_id' => $oldAccount->id,
                'old_account_code' => $oldAccount->code,
                'old_account_intitule' => $oldAccount->intitule,
                'nombre_ecritures' => $aggregate->nombre_ecritures ?? 0,
                'total_debit' => $aggregate->total_debit ?? 0,
                'total_credit' => $aggregate->total_credit ?? 0,
                'solde' => $aggregate->solde ?? 0,
            ];
        }

        return collect($detailData);
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'dateDebut',
            'dateFin',
            'exercice',
            'journalCode',
            'lettre'
        ]);
        $this->resetPage();
        $this->loadStats();
    }

    public function render()
    {
        $recapData = ($this->viewMode === 'table') ? $this->getRecapData() : collect();
        $detailData = ($this->viewMode === 'detail') ? $this->getDetailData() : collect();

        return view('livewire.grand-livre.recap', [
            'recapData' => $recapData,
            'detailData' => $detailData,
            'stats' => $this->stats,
        ])->layout('layouts.app');
    }
}