<?php

namespace App\Livewire\Mapping;

use Livewire\Component;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Illuminate\Support\Str;

class Editor extends Component
{
    public $oldAccountId;
    public $oldAccount;
    
    public $newAccountId;
    public $searchNewAccount = '';
    public $coefficient = 1;
    public $commentaire = '';
    
    public $suggestedAccounts = [];
    
    protected $listeners = [
        'openEditor' => 'loadOldAccount',
        'closeEditor' => 'resetForm'
    ];
    
    public function mount($oldAccountId = null)
    {
        if ($oldAccountId) {
            $this->loadOldAccount($oldAccountId);
        }
    }
    
    public function loadOldAccount($oldAccountId)
    {
        $this->resetForm();
        
        $this->oldAccountId = $oldAccountId;
        $this->oldAccount = OldAccount::with('mappings.newAccount')->find($oldAccountId);
        
        // Vérifier que le compte appartient à l'entreprise de l'utilisateur
        if ($this->oldAccount && $this->oldAccount->entreprise_id !== auth()->user()->entreprise_id) {
            abort(403, 'Accès non autorisé à ce compte.');
        }
        
        if ($this->oldAccount && $this->oldAccount->mappings->isNotEmpty()) {
            $mapping = $this->oldAccount->mappings->first();
            $this->newAccountId = $mapping->new_account_id;
            $this->coefficient = $mapping->coefficient;
            $this->commentaire = $mapping->commentaire;
            
            // Pré-remplir la recherche
            if ($mapping->newAccount) {
                $this->searchNewAccount = $mapping->newAccount->code . ' - ' . $mapping->newAccount->intitule;
            }
        }
    }
    
    public function resetForm()
    {
        $this->reset([
            'oldAccountId',
            'oldAccount',
            'newAccountId',
            'searchNewAccount',
            'coefficient',
            'commentaire',
            'suggestedAccounts'
        ]);
    }
    
    public function updatedSearchNewAccount($value)
    {
        if (strlen($value) >= 2) {
            $this->suggestedAccounts = NewAccount::where('entreprise_id', auth()->user()->entreprise_id)
                ->where(function ($query) use ($value) {
                    $query->where('code', 'like', '%' . $value . '%')
                          ->orWhere('intitule', 'like', '%' . $value . '%');
                })
                ->orderBy('code')
                ->limit(8)
                ->get();
        } else {
            $this->suggestedAccounts = [];
        }
    }
    
    public function selectAccount($accountId)
    {
        $account = NewAccount::find($accountId);
        
        if ($account && $account->entreprise_id === auth()->user()->entreprise_id) {
            $this->newAccountId = $account->id;
            $this->searchNewAccount = $account->code . ' - ' . $account->intitule;
            $this->suggestedAccounts = [];
        }
    }
    
    public function save()
    {
        $this->validate([
            'oldAccountId' => 'required|exists:old_accounts,id',
            'newAccountId' => 'required|exists:new_accounts,id',
            'coefficient' => 'required|numeric|min:0.001',
            'commentaire' => 'nullable|string|max:500',
        ]);
        
        // Vérifications de sécurité
        $oldAccount = OldAccount::find($this->oldAccountId);
        $newAccount = NewAccount::find($this->newAccountId);
        
        if (!$oldAccount || $oldAccount->entreprise_id !== auth()->user()->entreprise_id) {
            session()->flash('error', 'Compte ancien non valide.');
            return;
        }
        
        if (!$newAccount || $newAccount->entreprise_id !== auth()->user()->entreprise_id) {
            session()->flash('error', 'Compte nouveau non valide.');
            return;
        }
        
        $mapping = AccountMapping::updateOrCreate(
            [
                'entreprise_id' => auth()->user()->entreprise_id,
                'old_account_id' => $this->oldAccountId,
            ],
            [
                'new_account_id' => $this->newAccountId,
                'coefficient' => $this->coefficient,
                'commentaire' => $this->commentaire,
            ]
        );
        
        $this->dispatch('mapping-saved', mappingId: $mapping->id);
        $this->dispatch('closeEditor');
        
        session()->flash('success', 'Mapping enregistré avec succès.');
    }
    
    public function delete()
    {
        if ($this->oldAccountId) {
            AccountMapping::where('old_account_id', $this->oldAccountId)
                ->where('entreprise_id', auth()->user()->entreprise_id)
                ->delete();
            
            $this->dispatch('mapping-deleted');
            $this->dispatch('closeEditor');
            
            session()->flash('success', 'Mapping supprimé.');
        }
    }
    
    public function close()
    {
        $this->dispatch('closeEditor');
    }
    
    public function render()
    {
        return view('livewire.mapping.editor');
    }
}