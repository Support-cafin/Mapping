<?php

namespace App\Livewire\Enterprise;

use Livewire\Component;
use App\Models\Entreprise;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;

class Dashboard extends Component
{
    public $entreprise;
    public $old_count;
    public $new_count;
    public $mapped_count;
    public $unmapped_count;
    public $progress;

    public $recent_unmapped = [];
    public $recent_mappings = [];

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;

        // Stats
        $this->old_count = $this->entreprise->oldAccounts()->count();
        $this->new_count = $this->entreprise->newAccounts()->count();
        $this->mapped_count = $this->entreprise->mappings()->count();
        $this->unmapped_count = $this->old_count - $this->mapped_count;

        $this->progress = $this->old_count > 0
            ? round(($this->mapped_count / $this->old_count) * 100, 2)
            : 0;

        // Comptes non mappés (max 5)
        $this->recent_unmapped = $this->entreprise->oldAccounts()
            ->doesntHave('mappings')
            ->orderBy('code')
            ->take(5)
            ->get();

        // Derniers mappings
        $this->recent_mappings = $this->entreprise->mappings()
            ->with(['oldAccount', 'newAccount'])
            ->latest()
            ->take(5)
            ->get();
    }

    public function goToMapping()
    {
        return redirect()->route('mapping.table');
        // Plus besoin de passer l'ID, car récupéré automatiquement
    }

    public function goToOldAccounts()
    {
        return redirect()->route('old.index');
        // Créer cette route plus tard
    }

    public function goToNewAccounts()
    {
        return redirect()->route('new.index');
        // Créer cette route plus tard
    }

    public function render()
    {
        return view('livewire.enterprise.dashboard')
            ->layout('layouts.app');
    }
}
