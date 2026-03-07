<?php

namespace App\Livewire\Mapping;

use Livewire\Component;
use App\Models\NewAccount;

class SearchNewAccount extends Component
{
    public $search = '';
    public $results = [];
    public $entrepriseId;
    
    public $listeners = ['performSearch'];
    
    public function mount($entrepriseId)
    {
        $this->entrepriseId = $entrepriseId;
    }
    
    public function updatedSearch($value)
    {
        $this->performSearch($value);
    }
    
    public function performSearch($term)
    {
        $this->search = $term;
        
        if (strlen($term) >= 2) {
            $this->results = NewAccount::where('entreprise_id', $this->entrepriseId)
                ->where(function ($query) use ($term) {
                    $query->where('code', 'like', '%' . $term . '%')
                          ->orWhere('intitule', 'like', '%' . $term . '%');
                })
                ->orderBy('code')
                ->limit(15)
                ->get();
        } else {
            $this->results = [];
        }
    }
    
    public function selectAccount($accountId)
    {
        $account = NewAccount::find($accountId);
        if ($account) {
            $this->dispatch('account-selected', 
                accountId: $accountId,
                accountCode: $account->code,
                accountIntitule: $account->intitule
            );
            $this->reset(['search', 'results']);
        }
    }
    
    public function render()
    {
        return view('livewire.mapping.search-new-account');
    }
}