<?php

namespace App\Livewire\Dashboard;

use App\Models\Donateur;
use Livewire\Component;

class StatistiquesDons extends Component
{
    public $annee = 2024;

    public function render()
    {
        $donsParMois = Donateur::whereYear('date', $this->annee)
            ->selectRaw('MONTH(date) as mois, SUM(montant_don) as total')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->mois => $item->total];
            });

        $topDonateurs = Donateur::whereYear('date', $this->annee)
            ->selectRaw('denomination, SUM(montant_don) as total')
            ->groupBy('denomination')
            ->orderBy('total', 'desc')
            ->take(5)
            ->get();

        return view('livewire.dashboard.statistiques-dons', [
            'donsParMois' => $donsParMois,
            'topDonateurs' => $topDonateurs,
            'totalAnnee' => Donateur::whereYear('date', $this->annee)->sum('montant_don'),
            'nombreDons' => Donateur::whereYear('date', $this->annee)->count(),
            'moyenneDon' => Donateur::whereYear('date', $this->annee)->avg('montant_don'),
        ]);
    }
}