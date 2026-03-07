<?php

namespace App\Livewire\Donateurs;

use App\Models\Donateur;
use Livewire\Component;
use Livewire\WithPagination;
use App\Http\Controllers\DonateurPdfController;
use Illuminate\Support\Facades\Auth;
use DB;

class ListeDonateurs extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $selectedYear;
    public $selectedMonth;
    public $selectedStatut;
    public $selectedMode;
    public $entreprise_id;

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'date'],
        'sortDirection' => ['except' => 'desc'],
        'selectedYear' => ['except' => ''],
        'selectedMonth' => ['except' => ''],
        'selectedStatut' => ['except' => ''],
        'selectedMode' => ['except' => ''],
    ];

    public function mount()
    {
        // Récupérer l'ID de l'entreprise de l'utilisateur connecté
        $this->entreprise_id = Auth::user()->entreprise_id;
        
        if (!$this->entreprise_id) {
            session()->flash('error', 'Vous devez être associé à une entreprise pour accéder aux donateurs.');
        }
        
        //$this->selectedYear = date('Y');
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function exportPdf()
    {
        // Récupérer les paramètres actuels
        $params = [
            'search' => $this->search,
            'selectedYear' => $this->selectedYear,
            'selectedMonth' => $this->selectedMonth,
            'selectedStatut' => $this->selectedStatut,
            'selectedMode' => $this->selectedMode,
            'sortField' => $this->sortField,
            'sortDirection' => $this->sortDirection,
            'entreprise_id' => $this->entreprise_id,
        ];
        
        // Rediriger vers la route d'export PDF avec les paramètres
        return redirect()->route('donateurs.export.pdf', $params);
    }

    public function delete($id)
    {
        // Vérifier que le donateur appartient à l'entreprise de l'utilisateur
        $donateur = Donateur::where('id', $id)
            ->where('entreprise_id', $this->entreprise_id)
            ->firstOrFail();
            
        $donateur->delete();
        
        session()->flash('message', 'Donateur supprimé avec succès.');
    }

    public function render()
    {
        // Vérifier si l'utilisateur a une entreprise
        if (!$this->entreprise_id) {
            return view('livewire.donateurs.liste-donateurs', [
                'donateurs' => collect([]),
                'totalAnnuel' => 0,
                'stats' => [
                    'enregistre' => 0,
                    'valide' => 0,
                    'comptabilise' => 0,
                ]
            ])->layout('layouts.app');
        }

        $query = Donateur::where('entreprise_id', $this->entreprise_id);

        // Appliquer les filtres
        $query->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('denomination', 'like', '%' . $this->search . '%')
                  ->orWhere('nom_prenoms', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_enregistrement', 'like', '%' . $this->search . '%')
                  ->orWhere('registre_commerce', 'like', '%' . $this->search . '%')
                  ->orWhere('numero_identification_fiscal', 'like', '%' . $this->search . '%');
            });
        })
        ->when($this->selectedYear, function ($query) {
            $query->whereYear('date', $this->selectedYear);
        })
        ->when($this->selectedMonth, function ($query) {
            $query->whereMonth('date', $this->selectedMonth);
        })
        ->when($this->selectedStatut, function ($query) {
            $query->where('statut', $this->selectedStatut);
        })
         ->when($this->selectedMode, function ($query) {
            $query->where('mode_liberation', $this->selectedMode);
        });

        // Compter les statistiques AVANT la pagination
        $statsQuery = clone $query;
        $stats = [
            'enregistre' => $statsQuery->where('statut', 'enregistré')->count(),
            'valide' => $statsQuery->where('statut', 'validé')->count(),
            'comptabilise' => $statsQuery->where('statut', 'comptabilisé')->count(),
        ];

        // Calculer le total annuel
        $totalAnnuelQuery = clone $query;
        $totalAnnuel = $totalAnnuelQuery->whereYear('date', $this->selectedYear)->sum('montant_don');

        // Appliquer le tri et la pagination
        $donateurs = $query->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.donateurs.liste-donateurs', [
            'donateurs' => $donateurs,
            'totalAnnuel' => $totalAnnuel,
            'stats' => $stats,
            'years' => $this->getYears(),
            'months' => $this->getMonths(),
            'statuts' => $this->getStatuts(),
        ])->layout('layouts.app');
    }

    /**
     * Obtenir la liste des années disponibles pour les donateurs de l'entreprise
     */
    protected function getYears()
    {
        return Donateur::where('entreprise_id', $this->entreprise_id)
            ->selectRaw('YEAR(date) as year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    /**
     * Obtenir la liste des mois disponibles
     */
    protected function getMonths()
    {
        return [
            '01' => 'Janvier',
            '02' => 'Février',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Août',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Décembre',
        ];
    }

    /**
     * Obtenir la liste des statuts disponibles
     */
    protected function getStatuts()
    {
        return [
            'enregistré' => 'Enregistré',
            'validé' => 'Validé',
            'comptabilisé' => 'Comptabilisé',
            'annulé' => 'Annulé',
        ];
    }

    /**
     * Réinitialiser tous les filtres
     */
    public function resetFilters()
    {
        $this->reset([
            'search',
            'selectedYear',
            'selectedMonth',
            'selectedStatut',
            'selectedMode',
        ]);
        $this->selectedYear = date('Y');
        $this->resetPage();
    }

    /**
     * Obtenir le total des donateurs pour l'entreprise
     */
    public function getTotalDonateursProperty()
    {
        return Donateur::where('entreprise_id', $this->entreprise_id)->count();
    }

    /**
     * Obtenir le montant total des dons pour l'entreprise
     */
    public function getMontantTotalProperty()
    {
        return Donateur::where('entreprise_id', $this->entreprise_id)->sum('montant_don');
    }

    /**
     * Obtenir les statistiques par mode de libération
     */
    public function getStatsModeLiberationProperty()
    {
        return Donateur::where('entreprise_id', $this->entreprise_id)
            ->select('mode_liberation', DB::raw('COUNT(*) as count'), DB::raw('SUM(montant_don) as total'))
            ->groupBy('mode_liberation')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->mode_liberation => [
                    'count' => $item->count,
                    'total' => $item->total,
                ]];
            });
    }
}