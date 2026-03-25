<?php
namespace App\Livewire\Mapping;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DualPanel extends Component
{
    use WithFileUploads;
    
    public array $mappedToSelectedOld = [];
    public $quickMapping = false;
    public $mappedNewIds = [];
    public $entreprise;
    public $exercice;
    public $searchOld = '';
    public $searchNew = '';
    public $selectedOld = null;
    public $selectedOldAccount = null;
    public $expandedOld = [];
    public $expandedNew = [];
    public $oldRoots = [];
    public $newRoots = [];
    public $mappings = [];
    public $mappedOldIds = [];
    public $total = 0;
    public $mapped = 0;
    public $percentage = 0;
    
    // Nouveaux états pour modification/suppression
    public $showEditAccountModal = false;
    public $showDeleteAccountModal = false;
    public $accountToEdit = null;
    public $accountToDelete = null;
    public $editAccountCode = '';
    public $editAccountIntitule = '';
    public $editAccountClasse = '';
    public $editAccountGroupe = '';
    public $editAccountParentId = null;
    
    // Propriétés pour le compte à supprimer
    public $deleteAccountInfo = null;

    // Pour l'ajout manuel de comptes
    public $showAddAccountModal = false;
    public $accountType = 'old';
    public $accountCode = '';
    public $accountIntitule = '';
    public $accountClasse = '';
    public $accountGroupe = '';
    public $accountParentId = null;
    
    // Pour l'import Excel
    public $showImportModal = false;
    public $importType = 'old';
    public $excelFile;
    public $importing = false;
    public $importErrors = [];
    public $importSuccess = false;

    // Filtres
    public $filterClass = '';
    public $filterGroup = '';
    public $filterLevel = '';

    // Liste des parents pour le select
    public $oldParents = [];
    public $newParents = [];
    
    // Propriétés pour les modales de confirmation
    public $showDeleteAllModal = false;
    public $deleteAllType = null; // 'old' ou 'new'
    public $deleteAllCount = 0;
    public $deleteAllWithData = 0;
    public $deleteAllWithMappings = 0;

    public function mount()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        $this->entreprise = auth()->user()->entreprise;
        $this->exercice = $exo->id ?? '';
        $this->loadData();
        $this->loadParentLists();
        
        // Initialiser les nouveaux états
        $this->resetEditForm();
        $this->resetDeleteForm();
    }
    
    
    
    // Confirmation pour supprimer tous les anciens comptes
public function confirmDeleteAllOld()
{
    $this->deleteAllType = 'old';
    $this->calculateDeleteAllStats();
    $this->showDeleteAllModal = true;
}

// Confirmation pour supprimer tous les nouveaux comptes
public function confirmDeleteAllNew()
{
    $this->deleteAllType = 'new';
    $this->calculateDeleteAllStats();
    $this->showDeleteAllModal = true;
}

// Calculer les statistiques de suppression
private function calculateDeleteAllStats()
{
    $exo = DB::table('exercices')->where('statut', 1)->first();
    if ($this->deleteAllType === 'old') {
        // Comptes anciens
        $allAccounts = OldAccount::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id ?? '')->get();
        
        $this->deleteAllCount = $allAccounts->count();
        
        // Compter ceux qui ont des données dans le grand livre
        $this->deleteAllWithData = $allAccounts->filter(function($account) {
            return $account->hasData();
        })->count();
        
        // Compter ceux qui sont mappés
        $this->deleteAllWithMappings = $allAccounts->filter(function($account) {
            return $account->isMapped();
        })->count();
        
    } else {
        // Comptes nouveaux
        $allAccounts = NewAccount::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id ?? '')->get();
        
        $this->deleteAllCount = $allAccounts->count();
        
        // Pour les nouveaux comptes, vérifier s'ils ont des mappings
        $this->deleteAllWithMappings = $allAccounts->filter(function($account) {
            return $account->mappings()->exists();
        })->count();
        
        // Les nouveaux comptes n'ont généralement pas de données directes
        $this->deleteAllWithData = 0;
    }
}

// Supprimer tous les comptes
public function deleteAllAccounts()
{
    if (!$this->deleteAllType) {
        return;
    }
    $exo = DB::table('exercices')->where('statut', 1)->first();
    DB::beginTransaction();
    
    try {
        if ($this->deleteAllType === 'old') {
            // Supprimer tous les anciens comptes non mappés et sans enfants
            $accountsToDelete = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->whereDoesntHave('mappings')
                ->whereDoesntHave('children')
                ->whereDoesntHave('grandLivre')
                ->get();
            
            $deletedCount = 0;
            foreach ($accountsToDelete as $account) {
                $account->delete();
                $deletedCount++;
            }
            
            session()->flash('success', "$deletedCount ancien(s) compte(s) supprimé(s)");
            
        } else {
            // Supprimer tous les nouveaux comptes non mappés et sans enfants
            $accountsToDelete = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->whereDoesntHave('mappings')
                ->whereDoesntHave('children')
                ->get();
            
            $deletedCount = 0;
            foreach ($accountsToDelete as $account) {
                $account->delete();
                $deletedCount++;
            }
            
            session()->flash('success', "$deletedCount nouveau(x) compte(s) supprimé(s)");
        }
        
        DB::commit();
        
        // Recharger les données
        $this->loadData();
        $this->loadParentLists();
        
    } catch (\Exception $e) {
        DB::rollBack();
        session()->flash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
    }
    
    $this->closeDeleteAllModal();
}

// Fermer la modal
public function closeDeleteAllModal()
{
    $this->showDeleteAllModal = false;
    $this->deleteAllType = null;
    $this->deleteAllCount = 0;
    $this->deleteAllWithData = 0;
    $this->deleteAllWithMappings = 0;
}

// Méthode pour supprimer FORCÉMENT tous les comptes (y compris avec données)
public function forceDeleteAllAccounts()
{
    if (!$this->deleteAllType) {
        return;
    }
    
    $exo = DB::table('exercices')->where('statut', 1)->first();
    DB::beginTransaction();
    
    try {
        if ($this->deleteAllType === 'old') {
            // ✅ OPTIMISATION : Supprimer en masse
            AccountMapping::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->whereIn('old_account_id', function($query) use ($exo) {
                    $query->select('id')
                        ->from('old_accounts')
                        ->where('entreprise_id', $this->entreprise->id)
                        ->where('exercice_id', $exo->id ?? '');
                })
                ->delete();
            
            $deleted = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->delete(); // Une seule requête !
            
            session()->flash('warning', "⚠️ $deleted ancien(s) compte(s) supprimé(s) FORCÉMENT");
            
        } else {
            // Même optimisation pour les nouveaux comptes
            AccountMapping::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->whereIn('new_account_id', function($query) use ($exo) {
                    $query->select('id')
                        ->from('new_accounts')
                        ->where('entreprise_id', $this->entreprise->id)
                        ->where('exercice_id', $exo->id ?? '');
                })
                ->delete();
            
            $deleted = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->delete();
            
            session()->flash('warning', "⚠️ $deleted nouveau(x) compte(s) supprimé(s) FORCÉMENT");
        }
        
        DB::commit();
        $this->loadData();
        $this->loadParentLists();
        
    } catch (\Exception $e) {
        DB::rollBack();
        session()->flash('error', 'Erreur : ' . $e->getMessage());
    }
    
    $this->closeDeleteAllModal();
}
    
    
    
    // === NOUVELLES MÉTHODES POUR MODIFICATION ===
    
    public function openEditModal($accountId)
    {
        $account = OldAccount::find($accountId);
        
        if (!$account) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Compte introuvable'
            ]);
            return;
        }
        
        // Vérifier si le compte peut être modifié
        if (!$account->canBeModified()) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Ce compte est mappé. Vous pouvez seulement modifier son libellé.'
            ]);
            
            // Si mappé, on ne permet que la modification du libellé
            $this->accountToEdit = $account;
            $this->editAccountIntitule = $account->intitule;
            $this->editAccountCode = $account->code; // Non modifiable
            $this->showEditAccountModal = true;
            return;
        }
        
        // Si non mappé, on peut tout modifier
        $this->accountToEdit = $account;
        $this->editAccountCode = $account->code;
        $this->editAccountIntitule = $account->intitule;
        $this->editAccountClasse = $account->classe;
        $this->editAccountGroupe = $account->groupe;
        $this->editAccountParentId = $account->parent_id;
        $this->showEditAccountModal = true;
    }
    
    public function updateAccount()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->validate([
            'editAccountCode' => [
                'required',
                'string',
                'max:20',
                Rule::unique('old_accounts', 'code')
                    ->where('entreprise_id', $this->entreprise->id)
                    ->where('exercice_id', $exo->id ?? '')
                    ->ignore($this->accountToEdit->id)
            ],
            'editAccountIntitule' => 'required|string|max:255',
            'editAccountClasse' => 'nullable|string|max:10',
            'editAccountGroupe' => 'nullable|string|max:10',
            'editAccountParentId' => [
                'nullable',
                Rule::exists('old_accounts', 'id')
                    ->where('entreprise_id', $this->entreprise->id)
                    ->where('exercice_id', $exo->id ?? '')
            ],
        ]);
        
        // Vérification supplémentaire si le compte est mappé
        if ($this->accountToEdit->isMapped()) {
            // Si mappé, on ne permet que la modification du libellé
            $this->accountToEdit->update([
                'intitule' => $this->editAccountIntitule
            ]);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Libellé du compte mappé mis à jour avec succès'
            ]);
        } else {
            // Si non mappé, on peut tout modifier
            $this->accountToEdit->update([
                'code' => $this->editAccountCode,
                'intitule' => $this->editAccountIntitule,
                'classe' => $this->editAccountClasse,
                'groupe' => $this->editAccountGroupe,
                'parent_id' => $this->editAccountParentId,
            ]);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Compte mis à jour avec succès'
            ]);
        }
        
        $this->closeEditModal();
        $this->loadData();
        $this->loadParentLists();
    }
    
    public function closeEditModal()
    {
        $this->showEditAccountModal = false;
        $this->resetEditForm();
    }
    
    private function resetEditForm()
    {
        $this->accountToEdit = null;
        $this->editAccountCode = '';
        $this->editAccountIntitule = '';
        $this->editAccountClasse = '';
        $this->editAccountGroupe = '';
        $this->editAccountParentId = null;
    }
    
    // === NOUVELLES MÉTHODES POUR SUPPRESSION ===
    
    public function openDeleteModal($accountId, $type = 'old')
{
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    if ($type === 'old') {
        $account = OldAccount::with(['mappings', 'children'])->where('exercice_id', $exo->id ?? '')->find($accountId);
    } else {
        $account = NewAccount::with(['mappings', 'children'])->where('exercice_id', $exo->id ?? '')->find($accountId);
    }
    
    if (!$account) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Compte introuvable'
        ]);
        return;
    }
    
    // Adapter la vérification selon le type
    $canDelete = $type === 'old' ? $account->canBeDeleted() : !$account->isMapped();
    $isMapped = $type === 'old' ? $account->isMapped() : $account->mappings()->exists();
    $hasChildren = $account->children->isNotEmpty();
    
    $this->accountToDelete = $account;
    $this->deleteAccountInfo = [
        'code' => $account->code,
        'intitule' => $account->intitule,
        'type' => $type, // Ajouter le type
        'canDelete' => $canDelete,
        'isMapped' => $isMapped,
        'hasChildren' => $hasChildren,
        'hasData' => $type === 'old' ? $account->hasData() : false,
        'childrenCount' => $account->children->count(),
        'mappingsCount' => $type === 'old' ? $account->mappings->count() : $account->mappings()->count(),
    ];
    
    $this->showDeleteAccountModal = true;
}
    
    public function deleteAccount()
{
    if (!$this->accountToDelete) {
        return;
    }
    
    $type = $this->deleteAccountInfo['type'] ?? 'old';
    
    if ($type === 'old') {
        // Vérifications pour ancien compte
        if ($this->accountToDelete->isMapped()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Impossible de supprimer un compte mappé'
            ]);
            $this->closeDeleteModal();
            return;
        }
        
        if (!$this->accountToDelete->canBeDeleted()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Impossible de supprimer un compte contenant des données'
            ]);
            $this->closeDeleteModal();
            return;
        }
    } else {
        // Vérifications pour nouveau compte
        if ($this->accountToDelete->mappings()->exists()) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Impossible de supprimer un compte utilisé dans des mappings'
            ]);
            $this->closeDeleteModal();
            return;
        }
    }
    
    // Vérifier les enfants (valable pour les deux types)
    if ($this->accountToDelete->children->isNotEmpty()) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Impossible de supprimer un compte ayant des sous-comptes'
        ]);
        $this->closeDeleteModal();
        return;
    }
    
    try {
        $this->accountToDelete->delete();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Compte supprimé avec succès'
        ]);
        
        $this->closeDeleteModal();
        $this->loadData();
        $this->loadParentLists();
        
    } catch (\Exception $e) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
        ]);
    }
}
    
    public function closeDeleteModal()
    {
        $this->showDeleteAccountModal = false;
        $this->resetDeleteForm();
    }
    
    private function resetDeleteForm()
    {
        $this->accountToDelete = null;
        $this->deleteAccountInfo = null;
    }
    
    // Mettre à jour la méthode selectOld pour inclure la vérification
    public function selectOld($id)
{
    $account = OldAccount::find($id);
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    if (!$account) {
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Compte introuvable'
        ]);
        return;
    }
    
    // ✅ Vérifier si le compte old est déjà mappé
    $isMapped = in_array($id, $this->mappedOldIds);
    
    if ($isMapped) {
        // 🔥 ICI - Le modal DOIT s'afficher
        // Trouver le mapping existant
        $mapping = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->where('old_account_id', $id)
            ->with('newAccount')
            ->first();
        
        if ($mapping && $mapping->newAccount) {
            // Déclencher le modal Alpine
            $this->dispatch('open-replace-modal', [
                'oldId' => $id,
                'oldCode' => $account->code,
                'currentNewId' => $mapping->new_account_id,
                'currentNewCode' => $mapping->newAccount->code,
                'currentNewIntitule' => $mapping->newAccount->intitule
            ]);
        }
        return;
    }
    
    // Si on clique sur le compte déjà sélectionné → DÉSÉLECTIONNER
    if ($this->selectedOld === $id) {
        $this->cancelSelection();
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Sélection annulée'
        ]);
        return;
    }
    
    // Sinon, sélectionner ce nouveau compte
    $this->selectedOld = $id;
    $this->selectedOldAccount = $account;
    $this->quickMapping = true;
    
    $this->dispatch('notify', [
        'type' => 'info',
        'message' => "Compte {$account->code} sélectionné - Cliquez sur un nouveau compte pour mapper"
    ]);
}
    

    // Ajouter une méthode pour réorganiser les comptes enfants après suppression/modification
    private function reorganizeAccountsAfterDelete($deletedAccount)
    {
        // Si le compte supprimé avait un parent, on pourrait vouloir réorganiser
        // les niveaux des comptes restants (optionnel)
    }
    
    // Méthode pour obtenir les actions disponibles pour un compte
    public function getAccountActions($accountId)
    {
        $account = OldAccount::find($accountId);
        if (!$account) return [];
        
        return [
            'canEdit' => true, // Toujours possible d'éditer (au moins le libellé)
            'canDelete' => $account->canBeDeleted() && !$account->isMapped(),
            'isMapped' => $account->isMapped(),
            'hasData' => $account->hasData(),
        ];
    }
    

    public function loadData()
{
    $this->loadOldAccounts();
    $this->loadNewAccounts();
    $this->loadMappings(); // <- Important: charger les mappings APRÈS les comptes
    $this->refreshStats();
}

    public function loadOldAccounts()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->with('children')
            ->orderByRaw('LENGTH(code) ASC, code ASC');
    
        if ($this->searchOld) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->searchOld}%")
                  ->orWhere('intitule', 'like', "%{$this->searchOld}%");
            });
        }
    
        if ($this->filterClass) {
            $query->where('classe', $this->filterClass);
        }
    
        if ($this->filterGroup) {
            $query->where('groupe', $this->filterGroup);
        }
    
        if ($this->filterLevel) {
            $query->where('niveau', $this->filterLevel);
        }
    
        $this->oldRoots = $query->whereNull('parent_id')->get();
        
        $this->oldRoots->each(function ($account) {
            $this->sortAccountChildren($account);
        });
    }

    public function loadNewAccounts()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->with('children')
            ->orderByRaw('LENGTH(code) ASC, code ASC');
    
        if ($this->searchNew) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->searchNew}%")
                  ->orWhere('intitule', 'like', "%{$this->searchNew}%");
            });
        }
    
        if ($this->filterClass) {
            $query->where('classe', $this->filterClass);
        }
    
        if ($this->filterGroup) {
            $query->where('groupe', $this->filterGroup);
        }
    
        if ($this->filterLevel) {
            $query->where('niveau', $this->filterLevel);
        }
    
        $this->newRoots = $query->whereNull('parent_id')->get();
        
        $this->newRoots->each(function ($account) {
            $this->sortAccountChildren($account);
        });
    }
    
    private function sortAccountChildren($account)
    {
        if ($account->children && $account->children->isNotEmpty()) {
            $sortedChildren = $account->children->sortBy(function($child) {
                return [strlen($child->code), $child->code];
            });
            
            $account->setRelation('children', $sortedChildren->values());
            
            foreach ($account->children as $child) {
                $this->sortAccountChildren($child);
            }
        }
    }

    public function loadMappings()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        // Charger tous les mappings
        $this->mappings = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->with(['oldAccount', 'newAccount'])
            ->get();
        
        // IMPORTANT: mappedOldIds doit contenir les IDs des anciens comptes qui sont mappés
        $this->mappedOldIds = $this->mappings->pluck('old_account_id')->toArray();
        
        // mappedNewIds contient les IDs des nouveaux comptes mappés (clé = new_account_id, valeur = old_account_id)
        $this->mappedNewIds = $this->mappings->pluck('old_account_id', 'new_account_id')->toArray();
        
        // Pour le mapping rapide
        if ($this->selectedOld) {
            $this->mappedToSelectedOld = $this->mappings
                ->where('old_account_id', $this->selectedOld)
                ->pluck('new_account_id')
                ->toArray();
        }
    }
    
    public function debugMappings()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        
        $count = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->count();
        
        $mappings = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->with(['oldAccount', 'newAccount'])
            ->get();
        
        Log::info('=== DEBUG MAPPINGS ===');
        Log::info('Nombre de mappings: ' . $count);
        
        foreach ($mappings as $m) {
            Log::info("Mapping: old_id={$m->old_account_id} (code={$m->oldAccount?->code}) -> new_id={$m->new_account_id} (code={$m->newAccount?->code})");
        }
        
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => "{$count} mappings trouvés (voir logs)"
        ]);
    }

    public function loadParentLists()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->oldParents = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereNull('parent_id')
            ->orderByRaw('LENGTH(code) ASC, code ASC')
            ->get(['id', 'code', 'intitule'])
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'text' => "{$account->code} - {$account->intitule}"
                ];
            })->toArray();
    
        $this->newParents = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereNull('parent_id')
            ->orderByRaw('LENGTH(code) ASC, code ASC')
            ->get(['id', 'code', 'intitule'])
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'text' => "{$account->code} - {$account->intitule}"
                ];
            })->toArray();
    }

    
    // ========== MAPPING RAPIDE ET FLUIDE ==========
   // ========== MAPPING RAPIDE ET FLUIDE ==========
public function mapToNew($newId)
{
    if (!$this->selectedOld) {
        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'Veuillez d\'abord sélectionner un ancien compte'
        ]);
        return;
    }
    
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    // ✅ Vérifier si l'ancien compte est déjà mappé (un seul mapping possible)
    $oldIsMapped = in_array($this->selectedOld, $this->mappedOldIds);
    
    if ($oldIsMapped) {
        // Trouver le mapping existant
        $mapping = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exercice)
            ->where('old_account_id', $this->selectedOld)
            ->with('newAccount')
            ->first();
        
        if ($mapping && $mapping->newAccount) {
            // Ouvrir le modal de confirmation
            $this->dispatch('open-replace-modal', [
                'oldId' => $this->selectedOld,
                'oldCode' => $this->selectedOldAccount->code,
                'currentNewId' => $mapping->new_account_id,
                'currentNewCode' => $mapping->newAccount->code,
                'currentNewIntitule' => $mapping->newAccount->intitule
            ]);
        }
        return;
    }
    
    // ✅ Vérifier si ce mapping exact existe déjà (ne devrait pas arriver car old non mappé)
    $existing = AccountMapping::where('entreprise_id', $this->entreprise->id)
        ->where('exercice_id', $exo->id ?? '')
        ->where('old_account_id', $this->selectedOld)
        ->where('new_account_id', $newId)
        ->first();
        
    if ($existing) {
        $this->dispatch('notify', [
            'type' => 'warning',
            'message' => 'Ce mapping existe déjà'
        ]);
        return;
    }
    
    DB::beginTransaction();
    try {
        // ✅ CRÉATION du mapping (old → new)
        AccountMapping::create([
            'entreprise_id' => $this->entreprise->id,
            'exercice_id' => $exo->id ?? '',
            'old_account_id' => $this->selectedOld,
            'new_account_id' => $newId,
            'coefficient' => 1,
        ]);
        
        // ✅ MISE À JOUR IMMÉDIATE DES TABLEAUX
        $this->mappedOldIds[] = $this->selectedOld;
        
        // Important: un new compte peut être mappé à plusieurs old, donc on ajoute pas on écrase pas
        $this->mappedNewIds[$newId] = $this->selectedOld; 
        
        $this->mappedToSelectedOld = [$newId];
        
        // Mise à jour du grand livre
        $this->updateGrandLivre($this->selectedOld, $newId, null);
        
        DB::commit();
        
        // Rafraîchir les statistiques
        $this->refreshStats();
        
        // ✅ DISPATCHER UN ÉVÉNEMENT POUR METTRE À JOUR L'INTERFACE
        $this->dispatch('mapping-created', [
            'oldId' => $this->selectedOld,
            'newId' => $newId,
            'oldCode' => $this->selectedOldAccount->code,
            'newCode' => NewAccount::find($newId)->code
        ]);
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "✓ Mapping créé : {$this->selectedOldAccount->code} → " . NewAccount::find($newId)->code
        ]);
        
        // ✅ DÉSÉLECTIONNER AUTOMATIQUEMENT APRÈS MAPPAGE
        $this->cancelSelection();
        
    } catch (\Exception $e) {
        DB::rollBack();
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
}

public function replaceMapping($oldId, $newId)
{
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    DB::beginTransaction();
    try {
        // Trouver le mapping existant pour ce old compte
        $existingMapping = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->where('old_account_id', $oldId)
            ->first();
        
        if ($existingMapping) {
            $oldNewId = $existingMapping->new_account_id;
            
            // Mettre à jour le mapping
            $existingMapping->update([
                'new_account_id' => $newId,
                'coefficient' => 1,
            ]);
            
            // Mise à jour du grand livre
            $this->updateGrandLivre($oldId, $newId, $oldNewId);
            
            DB::commit();
            
            // ✅ MISE À JOUR DES TABLEAUX
            // Important: Un nouveau compte peut être mappé à plusieurs anciens
            // Donc on met à jour mappedNewIds pour ce nouveau compte
            $this->mappedNewIds[$newId] = $oldId;
            
            // Retirer l'ancien nouveau compte des mappedNewIds si c'était le seul mapping
            // Mais attention: un nouveau compte peut avoir plusieurs mappings
            // Il faudrait vérifier s'il y a d'autres mappings pour l'ancien nouveau compte
            // Pour simplifier, on recharge tous les mappings
            $this->loadMappings();
            
            // Mettre à jour mappedToSelectedOld
            if ($this->selectedOld == $oldId) {
                $this->mappedToSelectedOld = [$newId];
            }
            
            // Rafraîchir les statistiques
            $this->refreshStats();
            
            $this->dispatch('mapping-updated', [
                'oldId' => $oldId,
                'newId' => $newId
            ]);
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => "✓ Mapping remplacé avec succès"
            ]);
        }
        
        // Désélectionner après remplacement
        $this->cancelSelection();
        
    } catch (\Exception $e) {
        DB::rollBack();
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur: ' . $e->getMessage()
        ]);
    }
}
    // ========== MISE À JOUR DU GRAND LIVRE ==========
    private function updateGrandLivre($oldAccountId, $newAccountId, $previousNewId = null)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            // Récupérer toutes les entrées du grand livre pour cette entreprise avec cet old_account_id
            $entries = DB::table('grand_livres')
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->where('old_account_id', $oldAccountId)
                ->get();
            
            // Si on a un previousNewId, d'abord nettoyer les anciennes entrées
            if ($previousNewId) {
                DB::table('grand_livres')
                    ->where('entreprise_id', $this->entreprise->id)
                    ->where('exercice_id', $exo->id ?? '')
                    ->where('new_account_id', $previousNewId)
                    ->where('old_account_id', $oldAccountId)
                    ->update(['new_account_id' => null]);
            }
            
            // Mettre à jour avec le nouveau new_account_id
            $updated = DB::table('grand_livres')
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->where('old_account_id', $oldAccountId)
                ->update([
                    'new_account_id' => $newAccountId,
                    'updated_at' => now()
                ]);
            
            // Log pour débogage (optionnel)
            Log::info("Grand livre mis à jour", [
                'entreprise_id' => $this->entreprise->id,
                'exercice_id' => $exo->id ?? '',
                'old_account_id' => $oldAccountId,
                'new_account_id' => $newAccountId,
                'entries_updated' => $updated,
                'previous_new_id' => $previousNewId
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du grand livre", [
                'error' => $e->getMessage(),
                'entreprise_id' => $this->entreprise->id,
                'exercice_id' => $exo->id ?? '',
                'old_account_id' => $oldAccountId,
                'new_account_id' => $newAccountId
            ]);
        }
    }
    
    // ========== SUPPRIMER UN MAPPING ==========
    public function deleteMappingG($mappingId)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            $mapping = AccountMapping::findOrFail($mappingId);
            
            // Vérifier que le mapping appartient à cette entreprise
            if ($mapping->entreprise_id != $this->entreprise->id || $mapping->exercice_id != $exo->id ?? '') {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Action non autorisée'
                ]);
                return;
            }
            
            $oldAccountId = $mapping->old_account_id;
            $newAccountId = $mapping->new_account_id;
            
            // Supprimer le mapping
            $mapping->delete();
            
            // Nettoyer le grand livre (retirer le new_account_id)
            $this->cleanGrandLivre($oldAccountId, $newAccountId);
            
            // Recharger les données
            $this->loadMappings();
            $this->refreshStats();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Mapping supprimé avec succès. Grand livre nettoyé.'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur lors de la suppression : ' . $e->getMessage()
            ]);
        }
    }

    // ========== NETTOYAGE DU GRAND LIVRE APRÈS SUPPRESSION ==========
    private function cleanGrandLivre($oldAccountId, $newAccountId)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            $cleaned = DB::table('grand_livres')
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->where('old_account_id', $oldAccountId)
                ->where('new_account_id', $newAccountId)
                ->update([
                    'new_account_id' => null,
                    'updated_at' => now()
                ]);
            
            Log::info("Grand livre nettoyé après suppression de mapping", [
                'entreprise_id' => $this->entreprise->id,
                'exercice_id' => $exo->id ?? '',
                'old_account_id' => $oldAccountId,
                'new_account_id' => $newAccountId,
                'entries_cleaned' => $cleaned
            ]);
            
        } catch (\Exception $e) {
            Log::error("Erreur lors du nettoyage du grand livre", [
                'error' => $e->getMessage(),
                'entreprise_id' => $this->entreprise->id,
                'exercice_id' => $exo->id ?? '',
                'old_account_id' => $oldAccountId,
                'new_account_id' => $newAccountId
            ]);
        }
    }
    
    // ========== VÉRIFIER L'IMPACT DANS LE GRAND LIVRE ==========
    public function checkGrandLivreImpact($oldAccountId)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $impact = DB::table('grand_livres')
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->where('old_account_id', $oldAccountId)
            ->select(
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('SUM(debit) as total_debit'),
                DB::raw('SUM(credit) as total_credit'),
                DB::raw('MIN(date_ecriture) as first_date'),
                DB::raw('MAX(date_ecriture) as last_date')
            )
            ->first();
        
        return $impact;
    }
    
    // Utilisation dans le template Livewire :
    public function showImpact($oldAccountId)
    {
        $impact = $this->checkGrandLivreImpact($oldAccountId);
        
        if ($impact->total_entries > 0) {
            $this->dispatch('show-impact-modal', [
                'total_entries' => $impact->total_entries,
                'total_debit' => $impact->total_debit,
                'total_credit' => $impact->total_credit,
                'first_date' => $impact->first_date,
                'last_date' => $impact->last_date
            ]);
        }
    }

    public function cancelSelection()
    {
        $this->selectedOld = null;
        $this->selectedOldAccount = null;
        $this->quickMapping = false;
        $this->mappedToSelectedOld = [];
    }

    public function toggleOld($id)
    {
        if (in_array($id, $this->expandedOld)) {
            $this->expandedOld = array_diff($this->expandedOld, [$id]);
        } else {
            $this->expandedOld[] = $id;
        }
    }

    public function toggleNew($id)
    {
        if (in_array($id, $this->expandedNew)) {
            $this->expandedNew = array_diff($this->expandedNew, [$id]);
        } else {
            $this->expandedNew[] = $id;
        }
    }

    public function confirmRemap($oldId)
    {
        $this->selectedOld = $oldId;
    
        $account = OldAccount::find($oldId);
        if ($account) {
            $parentIds = [];
            $current = $account;
    
            while ($current->parent_id) {
                $current = $current->parent;
                if ($current) {
                    $parentIds[] = $current->id;
                }
            }
    
            $this->expandedOld = array_unique(array_merge($this->expandedOld, $parentIds));
        }
    
        $this->loadOldAccounts();
    
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Compte sélectionné - Choisissez le nouveau compte cible'
        ]);
    
        $this->dispatch('scroll-to-old', $oldId);
    }

    public function deleteMapping($mappingId)
    {
        AccountMapping::find($mappingId)->delete();
        $this->loadData();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Mapping supprimé'
        ]);
    }

    public function resetAll()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        AccountMapping::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id ?? '')->delete();
        $this->loadData();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Tous les mappings ont été réinitialisés'
        ]);
    }

    public function refreshStats()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->total = OldAccount::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id ?? '')->count();
        $this->mapped = AccountMapping::where('entreprise_id', $this->entreprise->id)->where('exercice_id', $exo->id ?? '')->count();
        $this->percentage = $this->total > 0 ? round(($this->mapped / $this->total) * 100) : 0;
    }

    public function addAccount()
{
    $exo = DB::table('exercices')->where('statut', 1)->first();
    
    // ✅ AJOUTER la validation d'unicité
    $this->validate([
        'accountType' => 'required|in:old,new',
        'accountCode' => [
            'required',
            'string',
            'max:20',
            // Validation d'unicité directement ici
            Rule::unique($this->accountType === 'old' ? 'old_accounts' : 'new_accounts', 'code')
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
        ],
        'accountIntitule' => 'required|string|max:255',
        'accountClasse' => 'nullable|string|max:10',
        'accountGroupe' => 'nullable|string|max:10',
        'accountParentId' => 'nullable|exists:' . ($this->accountType === 'old' ? 'old_accounts' : 'new_accounts') . ',id',
    ]);

    // Plus besoin de vérification manuelle
    $data = [
        'entreprise_id' => $this->entreprise->id,
        'exercice_id' => $exo->id ?? '',
        'code' => $this->accountCode,
        'intitule' => $this->accountIntitule,
        'classe' => $this->accountClasse,
        'groupe' => $this->accountGroupe,
        'parent_id' => $this->accountParentId,
    ];

    if ($this->accountType === 'old') {
        OldAccount::create($data);
    } else {
        NewAccount::create($data);
    }

    $this->resetAccountForm();
    $this->loadData();
    $this->loadParentLists();
    $this->showAddAccountModal = false;
    
    $this->dispatch('notify', [
        'type' => 'success',
        'message' => 'Compte ajouté avec succès'
    ]);
}

    /*public function importAccounts()
    {
        $this->validate([
            'importType' => 'required|in:old,new',
            'excelFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importing = true;
        $this->importErrors = [];
        $this->importSuccess = false;

        try {
            \Illuminate\Support\Facades\Log::info('Starting import', [
                'type' => $this->importType,
                'file' => $this->excelFile->getClientOriginalName(),
                'entreprise_id' => $this->entreprise->id
            ]);

            $importClass = $this->importType === 'old' 
                ? new \App\Imports\OldAccountsImport($this->entreprise->id)
                : new \App\Imports\NewAccountsImport($this->entreprise->id);

            Excel::import($importClass, $this->excelFile->getRealPath());

            $importedCount = $importClass->getImportedCount();
            $errors = $importClass->getErrors();

            \Illuminate\Support\Facades\Log::info('Import finished', [
                'imported' => $importedCount,
                'errors_count' => count($errors)
            ]);

            if ($importedCount === 0) {
                if (empty($errors)) {
                    $this->importErrors[] = "❌ Aucun compte importé. Vérifiez le format de votre fichier";
                    
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Aucun compte importé'
                    ]);
                } else {
                    $this->importErrors = $errors;
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Erreurs d\'importation détectées'
                    ]);
                }
                $this->importing = false;
                return;
            }

            $this->importSuccess = true;
            $this->importErrors = $errors;

            $this->loadData();
            $this->loadParentLists();

            $message = "✓ $importedCount compte(s) importé(s) avec succès";
            if (!empty($errors)) {
                $message .= " ⚠️ " . count($errors) . " avertissement(s)";
            }

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $message
            ]);

            if (empty($errors)) {
                $this->dispatch('close-modal-after-delay');
            }

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->importErrors[] = "❌ Erreur: " . $errorMessage;
            
            \Illuminate\Support\Facades\Log::error('Import failed', [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur lors de l\'importation'
            ]);
        }

        $this->importing = false;
    }*/
    
    public function importAccounts()
{
    $startTime = microtime(true);
    $filename = $this->excelFile->getClientOriginalName();
    
    $this->validate([
        'importType' => 'required|in:old,new',
        'excelFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
    ]);

    $this->importing = true;
    $this->importErrors = [];
    $this->importSuccess = false;

    try {
        \Illuminate\Support\Facades\Log::info('Starting import', [
            'type' => $this->importType,
            'file' => $filename,
            'entreprise_id' => $this->entreprise->id
        ]);

        $importClass = $this->importType === 'old' 
            ? new \App\Imports\OldAccountsImport($this->entreprise->id)
            : new \App\Imports\NewAccountsImport($this->entreprise->id);

        Excel::import($importClass, $this->excelFile->getRealPath());

        $importedCount = $importClass->getImportedCount();
        $errors = $importClass->getErrors();
        $warnings = method_exists($importClass, 'getWarnings') ? $importClass->getWarnings() : [];
        $allMessages = $importClass->getAllMessages();

        $duration = microtime(true) - $startTime;

        \Illuminate\Support\Facades\Log::info('Import finished', [
            'imported' => $importedCount,
            'errors_count' => count($errors),
            'warnings_count' => count($warnings),
            'duration' => $duration
        ]);

        // ✅ AFFICHER LES RÉSULTATS
        $this->dispatch('show-import-results', [
            'type' => $this->importType,
            'imported' => $importedCount,
            'errors' => $errors,
            'warnings' => $warnings,
            'all_messages' => $allMessages,
            'filename' => $filename,
            'duration' => $duration
        ]);

        if ($importedCount === 0 && empty($errors) && empty($warnings)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'Aucun compte importé'
            ]);
        }

        $this->importSuccess = true;
        $this->loadData();
        $this->loadParentLists();

    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        $this->importErrors[] = "❌ Erreur: " . $errorMessage;
        
        Log::error('Import failed', [
            'error' => $errorMessage,
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur lors de l\'importation'
        ]);
    }

    $this->importing = false;
    $this->closeImportModal();
}

    public function downloadTemplate($type)
    {
        $filename = $type === 'old' ? 'template_old_accounts.xlsx' : 'template_new_accounts.xlsx';
        $filepath = storage_path('app/templates/' . $filename);
        
        if (!file_exists($filepath)) {
            $this->createAccountsTemplateFile($type);
        }
        
        return response()->download($filepath);
    }

    private function createAccountsTemplateFile($type)
    {
        $dir = storage_path('app/templates');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $type === 'old' 
            ? 'template_old_accounts.xlsx' 
            : 'template_new_accounts.xlsx';
        
        $filepath = storage_path('app/templates/' . $filename);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'code');
        $sheet->setCellValue('B1', 'intitule');
        $sheet->setCellValue('C1', 'parent_code');

        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F46E5']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $examples = [
            ['1', 'CAPITAUX', ''],
            ['10', 'Capital', '1'],
            ['101', 'Capital social', '10'],
        ];

        $row = 2;
        foreach ($examples as $example) {
            $sheet->setCellValue('A' . $row, $example[0]);
            $sheet->setCellValue('B' . $row, $example[1]);
            $sheet->setCellValue('C' . $row, $example[2]);
            
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');
            }
            
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->getColumnDimension('C')->setWidth(15);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
    }

    public function resetAccountForm()
    {
        $this->accountCode = '';
        $this->accountIntitule = '';
        $this->accountClasse = '';
        $this->accountGroupe = '';
        $this->accountParentId = null;
    }

    public function openAddModal($type)
    {
        $this->accountType = $type;
        $this->showAddAccountModal = true;
        $this->loadParentLists();
    }

    public function openImportModal($type)
    {
        $this->importType = $type;
        $this->showImportModal = true;
        $this->importErrors = [];
        $this->importSuccess = false;
    }

    public function closeAddModal()
    {
        $this->showAddAccountModal = false;
        $this->resetAccountForm();
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->excelFile = null;
        $this->importErrors = [];
        $this->importSuccess = false;
    }

    public function updatedSearchOld()
    {
        $this->loadOldAccounts();
    }

    public function updatedSearchNew()
    {
        $this->loadNewAccounts();
    }

    public function updatedFilterClass()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
    }

    public function updatedFilterGroup()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
    }

    public function updatedFilterLevel()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
    }

    public function updatedAccountType()
    {
        $this->accountParentId = null;
    }

    public function render()
    {
        return view('livewire.mapping.dual-panel')->layout('layouts.app');
    }
}