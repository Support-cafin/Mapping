<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Index extends Component
{
    public $entreprise;
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $activeTab = 'bilan';
    public $chargement = false;
    
    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->dateDebut = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateFin = Carbon::now()->endOfYear()->format('Y-m-d');
        $this->exercice = Carbon::now()->year;
    }
    
    public function updatedDateDebut()
    {
        if ($this->dateDebut) {
            $this->exercice = Carbon::parse($this->dateDebut)->year;
        }
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function resetFilters()
    {
        $this->dateDebut = Carbon::now()->startOfYear()->format('Y-m-d');
        $this->dateFin = Carbon::now()->endOfYear()->format('Y-m-d');
        $this->exercice = Carbon::now()->year;
    }
    
    public function getIndicateurs()
    {
        return [
            'bilan_equilibre' => $this->checkBilanEquilibre(),
            'rentabilite' => $this->calculerRentabilite(),
            'liquidite' => $this->calculerLiquidite(),
            'solvabilite' => $this->calculerSolvabilite(),
        ];
    }
    
    private function checkBilanEquilibre()
    {
        // Vérifier si le bilan est équilibré
        // Cette méthode devrait interroger vos données
        return true; // Temporaire
    }
    
    public function render()
    {
        return view('livewire.financial.index', [
        'periode' => [
            'debut' => $this->dateDebut,
            'fin' => $this->dateFin,
            'exercice' => $this->exercice
        ],
        'chargement' => $this->chargement,
        'indicateurs' => $this->getIndicateurs()
        ])->layout('layouts.app');
    }
}