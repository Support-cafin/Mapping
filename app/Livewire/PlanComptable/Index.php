<?php

namespace App\Livewire\PlanComptable;

use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        return view('livewire.plan-comptable.index')
            ->layout('layouts.app');
    }
}
