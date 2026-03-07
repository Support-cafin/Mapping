<?php

namespace App\Livewire\PlanComptable;

use Livewire\Component;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use App\Models\GrandLivre;
use App\Traits\AutoUpdatesGrandLivre;
use Illuminate\Support\Facades\Auth;
use Livewire\WithFileUploads;

use App\Imports\MappingImport;
use Maatwebsite\Excel\Facades\Excel;
use DB;

class DualPanel extends Component
{
    use AutoUpdatesGrandLivre, WithFileUploads;
    
    public $searchOld = '';
    public $searchNew = '';

    public bool $confirmDelete = false;

    public $expandedOld = [];
    public $expandedNew = [];
    
    // Propriétés pour la modal de confirmation
    public $showDeleteAllMappingsModal = false;
    public $deleteAllMappingsCount = 0;
    
    // Ajoutez ces propriétés
    public $deletingAll = false;
    public $deletedCount = 0;
    public $totalToDelete = 0;

    public $showModal = false;
    public $editingMappingId = null;

    public $editOldAccount = null;
    public $editNewAccount = null;
    public $editNotes = '';

    // NEWLY ADDED
    public $showAddMappingModal = false;
    public $addOldAccount;
    public $addNewAccount;
    public $addNotes;

    public $showImportMappingModal = false;
    public $mappingFile;
    public $importErrors = [];


    public function mount()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Déployer les racines par défaut
        $this->expandedOld = OldAccount::whereNull('parent_id')->where('exercice_id', $exo->id ?? '')
            ->pluck('id')->toArray();

        $this->expandedNew = NewAccount::whereNull('parent_id')->where('exercice_id', $exo->id ?? '')
            ->pluck('id')->toArray();
    }

    // Méthode pour confirmer la suppression de tous les mappings
    public function confirmDeleteAllMappings()
    {
        $this->deleteAllMappingsCount = $this->mappingList->count();
        $this->showDeleteAllMappingsModal = true;
    }
    
    // Méthode pour supprimer tous les mappings
    public function deleteAllMappings()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            // Compter les mappings avant suppression
            $count = $this->deleteAllMappingsCount;
            $entrepriseId = auth()->user()->entreprise_id;
            
            // Récupérer tous les mappings pour réinitialiser le grand livre
            $mappings = AccountMapping::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id ?? '')->get();
            
            // Réinitialiser le grand livre pour chaque mapping
            foreach ($mappings as $mapping) {
                $this->updateGrandLivreForOldAccount(
                    $mapping->old_account_id,
                    $entrepriseId,
                    null
                );
            }
            
            // Supprimer tous les mappings
            AccountMapping::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id ?? '')->delete();
            
            // Fermer la modal
            $this->closeDeleteAllMappingsModal();
            
            // Notification
            session()->flash('success', "Tous les mappings ($count) ont été supprimés. Le grand livre a été réinitialisé.");
            
            // Rafraîchir
            $this->dispatch('$refresh');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }
    }
    
    // Méthode pour fermer la modal
    public function closeDeleteAllMappingsModal()
    {
        $this->showDeleteAllMappingsModal = false;
        $this->deleteAllMappingsCount = 0;
    }
    
    // Option alternative : Suppression avec confirmation simple
    public function deleteAllMappingsSimple()
    {
        // Utiliser la confirmation native de Livewire
        $this->js("
            if (confirm('⚠️ Êtes-vous sûr de vouloir supprimer TOUS les mappings ?\\n\\nCette action supprimera ' + $this->mappingList->count + ' correspondance(s) et réinitialisera le grand livre.')) {
                \$wire.call('deleteAllMappingsConfirmed');
            }
        ");
    }
    
    // Méthode appelée après confirmation
    public function deleteAllMappingsConfirmed()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            $count = AccountMapping::where('entreprise_id', auth()->user()->entreprise_id)->where('exercice_id', $exo->id ?? '')->count();
            
            // Réinitialiser le grand livre avant suppression
            $mappings = AccountMapping::where('entreprise_id', auth()->user()->entreprise_id)->where('exercice_id', $exo->id ?? '')->get();
            foreach ($mappings as $mapping) {
                $this->updateGrandLivreForOldAccount(
                    $mapping->old_account_id,
                    auth()->user()->entreprise_id,
                    null
                );
            }
            
            // Supprimer tous les mappings
            AccountMapping::where('entreprise_id', auth()->user()->entreprise_id)->where('exercice_id', $exo->id ?? '')->delete();
            
            session()->flash('success', "$count mapping(s) supprimé(s) avec succès.");
            
            // Rafraîchir
            $this->dispatch('$refresh');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur : ' . $e->getMessage());
        }
    }
    
    // Méthode avec progression
    public function deleteAllMappingsWithProgress()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->deletingAll = true;
        $this->totalToDelete = AccountMapping::where('entreprise_id', auth()->user()->entreprise_id)->where('exercice_id', $exo->id ?? '')->count();
        $this->deletedCount = 0;
        
        // Lancer la suppression en arrière-plan
        $this->dispatch('start-bulk-delete');
    }
    
    // Événement pour la suppression en masse
    protected $listeners = ['performBulkDelete' => 'performBulkDelete'];
    
    public function performBulkDelete()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $entrepriseId = auth()->user()->entreprise_id;
        $mappings = AccountMapping::where('entreprise_id', $entrepriseId)->where('exercice_id', $exo->id ?? '')->get();
        
        foreach ($mappings as $mapping) {
            // Réinitialiser le grand livre
            $this->updateGrandLivreForOldAccount(
                $mapping->old_account_id,
                $entrepriseId,
                null
            );
            
            // Supprimer le mapping
            $mapping->delete();
            
            $this->deletedCount++;
            
            // Mettre à jour la progression (toutes les 10 suppressions)
            if ($this->deletedCount % 10 === 0) {
                $this->dispatch('update-progress', [
                    'current' => $this->deletedCount,
                    'total' => $this->totalToDelete
                ]);
            }
        }
        
        // Terminer
        $this->deletingAll = false;
        session()->flash('success', "$this->deletedCount mapping(s) supprimé(s).");
        $this->dispatch('$refresh');
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

    public function getOldTreeProperty()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $roots = OldAccount::where('entreprise_id', auth()->user()->entreprise_id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('code')
            ->get();
    
        return $this->filterTree($roots, $this->searchOld);
    }
    
    
    public function confirmDeleteFromList($id)
    {
        $this->editingMappingId = $id;
        $this->confirmDelete = true;
        $this->showModal = true;
        
         \Illuminate\Support\Facades\Log::info('Confirmation suppression mapping', ['id' => $id]);
    }



    public function getNewTreeProperty()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $roots = NewAccount::where('entreprise_id', auth()->user()->entreprise_id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('code')
            ->get();
    
        return $this->filterTree($roots, $this->searchNew);
    }


    public function openEditMapping($id)
    {
        $this->editingMappingId = $id;
        $map = AccountMapping::findOrFail($id);

        $this->editOldAccount = $map->old_account_id;
        $this->editNewAccount = $map->new_account_id;
        $this->editNotes = $map->commentaire;

        $this->showModal = true;
    }
    
    private function resetGrandLivreMappings($oldAccountId)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        GrandLivre::where('old_account_id', $oldAccountId)
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereNotNull('new_account_id') // Uniquement celles qui avaient un mapping
            ->update([
                'new_account_id' => null,
                'updated_at' => now()
            ]);
    }

    private function filterTree($nodes, $search)
    {
        if (!$search) return $nodes;
    
        return $nodes->filter(function ($node) use ($search) {
    
            $match = str_contains(strtolower($node->code), strtolower($search))
                  || str_contains(strtolower($node->intitule), strtolower($search));
    
            $filteredChildren = $this->filterTree($node->children, $search);
    
            if ($filteredChildren->isNotEmpty()) {
                $node->setRelation('children', $filteredChildren);
                $this->expandedOld[] = $node->id;
                $this->expandedNew[] = $node->id;
                return true;
            }
    
            return $match;
        });
    }


    public function saveMapping($oldAccountId, $newAccountId, $notes = '')
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        AccountMapping::create([
            'entreprise_id' => auth()->user()->entreprise_id,
            'exercice_id' => $exo->id ?? '',
            'old_account_id' => $oldAccountId,
            'new_account_id' => $newAccountId,
        ]);

        
        // Mettre à jour automatiquement le Grand Livre
        $updatedCount = $this->updateGrandLivreForOldAccount(
            $oldAccountId, 
            auth()->user()->entreprise_id, 
            $exo->id ?? '',
            $newAccountId
        );
        
        session()->flash('success', "Mapping créé. $updatedCount écriture(s) mise(s) à jour dans le Grand Livre.");
    }
    
    public function updateMapping()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $mapping = AccountMapping::findOrFail($this->editingMappingId);
        $oldOldAccountId = $mapping->old_account_id; // Ancien ancien compte
        
        $mapping->update([
            'old_account_id' => $this->editOldAccount,
            'new_account_id' => $this->editNewAccount,
        ]);
        
        // Si l'ancien compte a changé, réinitialiser les anciennes écritures
        if ($oldOldAccountId != $this->editOldAccount) {
            $this->updateGrandLivreForOldAccount(
                $oldOldAccountId, 
                auth()->user()->entreprise_id, 
                null
            );
        }
        
        // Mettre à jour les nouvelles écritures
        $updatedCount = $this->updateGrandLivreForOldAccount(
            $this->editOldAccount, 
            auth()->user()->entreprise_id, 
            $exo->id ?? '',
            $this->editNewAccount
        );
        
        $this->showModal = false;
        session()->flash('success', "Correspondance mise à jour.");
        // $updatedCount écriture(s) mise(s) à jour.");
    }
    
    private function closeEditModal()
    {
        $this->showModal = false;
        $this->confirmDelete = false;
        $this->editingMappingId = null;
    
        $this->reset([
            'editOldAccount',
            'editNewAccount',
            'editNotes',
        ]);
    }

    
    // composant.php - Modifiez la méthode deleteMapping
    /*public function deleteMapping($id)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $mapping = AccountMapping::where('exercice_id', $exo->id ?? '')->findOrFail($id);
        $oldAccountId = $mapping->old_account_id;
        $entrepriseId = $mapping->entreprise_id;
    
        // Réinitialiser le grand livre
        $updatedCount = $this->updateGrandLivreForOldAccount(
            $oldAccountId,
            $entrepriseId,
            $exo->id ?? '',
            null
        );
    
        $mapping->delete();
    
        // 🔥 FERMETURE PROPRE DU MODAL
        $this->closeEditModal();
    
        // 🔔 Notification
        session()->flash(
            'success',
            "Correspondance supprimée. "
            // $updatedCount écriture(s) réinitialisée(s)."
        );
    
        // ⚡ Forcer le rafraîchissement visuel
        $this->dispatch('$refresh');
    }*/
    
    public function deleteMapping($id)
{
    $exo = DB::table('exercices')->where('statut', 1)->first();
    try {
        \Illuminate\Support\Facades\Log::info('Tentative de suppression mapping', ['id' => $id]);
        
        // 🔥 CORRECTION : Ajouter les filtres par exercice_id ET entreprise_id
        $mapping = AccountMapping::where('exercice_id', $exo->id ?? '')
                    ->where('entreprise_id', auth()->user()->entreprise_id)
                    ->findOrFail($id);
                    
        $oldAccountId = $mapping->old_account_id;
        $entrepriseId = $mapping->entreprise_id;
    
        // Réinitialiser le grand livre
        $updatedCount = $this->updateGrandLivreForOldAccount(
            $oldAccountId,
            $entrepriseId,
            $exo->id ?? '',
            null
        );
    
        $mapping->delete();
    
        $this->closeEditModal();
    
        session()->flash('success', "Correspondance supprimée.");
        $this->dispatch('$refresh');
        
    } catch (\Exception $e) {
        \Illuminate\Support\Facades\Log::error('Erreur suppression mapping', [
            'id' => $id,
            'error' => $e->getMessage()
        ]);
        session()->flash('error', 'Erreur : ' . $e->getMessage());
    }
}

public function openDeleteModal($id)
{
    $this->editingMappingId = $id;
    $map = AccountMapping::findOrFail($id);
    
    // Initialiser les propriétés avec les bonnes valeurs
    $this->editOldAccount = $map->old_account_id;
    $this->editNewAccount = $map->new_account_id;
    
    // Activer le mode confirmation directement
    $this->confirmDelete = true;
    $this->showModal = true;
    
    \Illuminate\Support\Facades\Log::info('Ouverture modal suppression', [
        'mapping_id' => $id,
        'old_account_id' => $map->old_account_id,
        'new_account_id' => $map->new_account_id
    ]);
}

public function closeModal()
{
    $this->showModal = false;
    $this->confirmDelete = false;
    $this->editingMappingId = null;
    $this->editOldAccount = null;
    $this->editNewAccount = null;
    $this->editNotes = '';
}
    
    // Ajouter une méthode pour synchroniser tout
    public function syncAllMappings()
    {
        $updatedCount = $this->syncAllGrandLivreMappings(auth()->user()->entreprise_id);
        session()->flash('success', "Synchronisation terminée. $updatedCount écriture(s) mise(s) à jour.");
    }

    public function openAddMappingModal()
    {
        // $this->resetAddForm();
        $this->showAddMappingModal = true;
    }

    public function openImportMappingModal()
    {
        $this->importErrors = [];
        $this->mappingFile = null;
        $this->showImportMappingModal = true;
    }

    public function createMapping()
    {
        $this->validate([
            'addOldAccount' => 'required|exists:old_accounts,id',
            'addNewAccount' => 'required|exists:new_accounts,id',
        ]);

        $this->saveMapping($this->addOldAccount, $this->addNewAccount, $this->addNotes);

        $this->showAddMappingModal = false;
        session()->flash('success', 'Correspondance ajoutée avec succès.');
    }

    public function downloadMappingTemplate()
    {
        $filename = 'template_mappings1.xlsx';
        $path = storage_path('app/templates/' . $filename);

        if (!file_exists($path)) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // En-têtes
            $sheet->setCellValue('A1', 'old_code');
            $sheet->setCellValue('B1', 'new_code');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($path);
        }

        return response()->download($path);
    }

    public function importMappings()
{
    $startTime = microtime(true);
    $filename = $this->mappingFile->getClientOriginalName();
    
    $this->validate([
        'mappingFile' => 'required|file|mimes:xlsx,xls,csv|max:10240'
    ]);

    $this->importErrors = [];

    try {
        $import = new \App\Imports\MappingImport(auth()->user()->entreprise_id);

        Excel::import($import, $this->mappingFile->getRealPath());

        $importedCount = $import->getCount();
        $errors = $import->getErrors();
        $success = $import->getSuccess();
        $allMessages = $import->getAllMessages();

        $duration = microtime(true) - $startTime;

        // ✅ AFFICHER LES RÉSULTATS
        $this->dispatch('show-mapping-results', [
            'imported' => $importedCount,
            'errors' => $errors,
            'success' => $success,
            'all_messages' => $allMessages,
            'filename' => $filename,
            'duration' => $duration
        ]);

        if ($importedCount > 0) {
            session()->flash('success', $importedCount . " correspondance(s) importée(s).");
        }

        $this->showImportMappingModal = false;
        $this->mappingFile = null;

    } catch (\Exception $e) {
        $this->importErrors[] = "❌ Erreur: " . $e->getMessage();
        Log::error('Import mapping failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}


    public function getMappingListProperty()
    {   
        $exo = DB::table('exercices')->where('statut', 1)->first();
        return AccountMapping::with(['oldAccount', 'newAccount'])
            ->where('entreprise_id', auth()->user()->entreprise_id)
            ->where('exercice_id', $exo->id ?? '')
            ->when($this->searchOld, function ($q) {
                $q->whereHas('oldAccount', function ($q2) {
                    $q2->where('code', 'like', "%{$this->searchOld}%")
                       ->orWhere('intitule', 'like', "%{$this->searchOld}%");
                });
            })
            ->when($this->searchNew, function ($q) {
                $q->whereHas('newAccount', function ($q2) {
                    $q2->where('code', 'like', "%{$this->searchNew}%")
                       ->orWhere('intitule', 'like', "%{$this->searchNew}%");
                });
            })
            ->orderBy('old_account_id')
            ->get();
    }



    public function render()
    {
        return view('livewire.plan-comptable.dual-panel')
            ->layout('layouts.app');
    }
}
