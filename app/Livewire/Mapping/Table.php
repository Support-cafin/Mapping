<?php

namespace App\Livewire\Mapping;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;

class Table extends Component
{
    use WithPagination;
    
    public $entreprise;
    public $search = '';
    public $statusFilter = 'all';
    public $perPage = 30;
    public $sortField = 'code';
    public $sortDirection = 'asc';
    
    public $selectedIds = [];
    public $selectAll = false;
    
    // Modal state
    public $showEditor = false;
    public $editingOldAccountId = null;
    public $quickMapSuggestions = [];
    
    // Stats
    public $stats = [
        'total' => 0,
        'mapped' => 0,
        'unmapped' => 0,
        'percentage' => 0
    ];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all'],
        'perPage' => ['except' => 30],
        'sortField' => ['except' => 'code'],
        'sortDirection' => ['except' => 'asc']
    ];
    
    public function mount()
    {
        // Récupérer l'entreprise de l'utilisateur connecté
        $this->entreprise = auth()->user()->entreprise;
        
        if (!$this->entreprise) {
            abort(403, 'Vous n\'êtes associé à aucune entreprise.');
        }
        
        $this->refreshStats();
    }
    
    public function refreshStats()
    {
        $total = $this->entreprise->oldAccounts()->count();
        $mapped = $this->entreprise->mappings()->distinct('old_account_id')->count();
        
        $this->stats = [
            'total' => $total,
            'mapped' => $mapped,
            'unmapped' => $total - $mapped,
            'percentage' => $total > 0 ? round(($mapped / $total) * 100, 2) : 0
        ];
    }
    
    public function updatedSearch($value)
    {
        $this->resetPage();
        
        // Mettre à jour les suggestions pour le mapping rapide
        if (strlen($value) >= 2) {
            $this->quickMapSuggestions = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where(function ($query) use ($value) {
                    $query->where('code', 'like', '%' . $value . '%')
                          ->orWhere('intitule', 'like', '%' . $value . '%');
                })
                ->limit(5)
                ->get();
        } else {
            $this->quickMapSuggestions = [];
        }
    }
    
    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedIds = $this->accounts->pluck('id')->toArray();
        } else {
            $this->selectedIds = [];
        }
    }
    
    public function updatedSelectedIds()
    {
        $this->selectAll = count($this->selectedIds) === $this->accounts->count();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }
    
    public function openEditor($oldAccountId)
    {
        $this->editingOldAccountId = $oldAccountId;
        $this->showEditor = true;
    }
    
    public function closeEditor()
    {
        $this->showEditor = false;
        $this->editingOldAccountId = null;
        $this->refreshStats();
        $this->dispatch('mapping-updated');
    }
    
    public function quickMap($oldAccountId, $newAccountId)
    {
        $oldAccount = OldAccount::find($oldAccountId);
        
        if ($oldAccount && $oldAccount->entreprise_id === $this->entreprise->id) {
            AccountMapping::updateOrCreate(
                [
                    'entreprise_id' => $this->entreprise->id,
                    'old_account_id' => $oldAccountId
                ],
                [
                    'new_account_id' => $newAccountId,
                    'coefficient' => 1,
                    'commentaire' => 'Mapping rapide'
                ]
            );
            
            $this->refreshStats();
            $this->dispatch('mapping-saved');
            
            // Message flash
            session()->flash('success', 'Mapping enregistré avec succès.');
        }
    }
    
    public function removeMapping($oldAccountId)
    {
        $oldAccount = OldAccount::find($oldAccountId);
        
        if ($oldAccount && $oldAccount->entreprise_id === $this->entreprise->id) {
            AccountMapping::where('entreprise_id', $this->entreprise->id)
                ->where('old_account_id', $oldAccountId)
                ->delete();
            
            $this->refreshStats();
            $this->dispatch('mapping-removed');
            
            session()->flash('success', 'Mapping supprimé.');
        }
    }
    
    public function getAccountsProperty()
    {
        $query = $this->entreprise->oldAccounts()
            ->with(['mappings.newAccount'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('intitule', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->statusFilter === 'mapped', function ($query) {
                $query->has('mappings');
            })
            ->when($this->statusFilter === 'unmapped', function ($query) {
                $query->doesntHave('mappings');
            })
            ->orderBy($this->sortField, $this->sortDirection);
        
        return $query->paginate($this->perPage);
    }
    
    public function render()
    {
        return view('livewire.mapping.table', [
            'accounts' => $this->accounts,
        ])->layout('layouts.app');
    }
}