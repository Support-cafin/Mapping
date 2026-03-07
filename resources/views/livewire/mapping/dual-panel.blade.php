{{-- resources/views/livewire/mapping/dual-panel.blade.php --}}
<div class="p-6 space-y-6 mt-12 bg-gray-50" style="height: calc(100vh - 3rem);"> {{-- Hauteur fixe --}}
 
 <style>
    /* Style pour le scroll fluide et indépendant */
    .table-scroll-container {
        flex: 1;
        min-height: 0; /* Crucial pour le scroll dans flexbox */
        overflow-y: auto;
        position: relative;
    }
    
    /* Style pour l'en-tête fixe dans chaque tableau */
    .table-header-fixed {
        position: sticky;
        top: 0;
        z-index: 20;
        background-color: #dbeafe; /* bg-blue-200 */
    }
    
    /* Pour éviter que le scroll remonte toute la page */
    html, body {
        overflow: hidden;
        height: 100%;
    }
    
    /* Amélioration du scroll */
    .overflow-y-auto {
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 #f1f5f9;
    }
    
    .overflow-y-auto::-webkit-scrollbar {
        width: 6px;
    }
    
    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
</style>
        @php $entr = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->first(); @endphp
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-semibold">{{ $entr->nom ?? 'non renseigné' }}</h1>
        </div>
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-semibold">Plan comptable</h1>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-600">Total: {{ $total }}</span>
                <span class="text-sm text-green-600">Mappés: {{ $mapped }}</span>
                <span class="text-sm text-blue-600">{{ $percentage }}%</span>
            </div>
        </div>
 
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 h-full"> {{-- Hauteur 100% --}}

        <!-- === PANEL GAUCHE : ANCIENS COMPTES === -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col h-full">
            <div class="p-4 border-b bg-gray-50 flex items-center justify-between flex-shrink-0"> {{-- Pas de rétrécissement --}}
                <h2 class="font-semibold text-gray-700" style="font-size: 9px;">
                    ENTITE
                    <span class="text-sm text-gray-500" style="font-size: 9px;">({{ $oldRoots->count() }})</span>
                </h2>
                
                <div class="flex items-center gap-3">
                    <input type="text" 
                       wire:model.live.debounce.300ms="searchOld"
                       class="px-3 py-1.5 text-sm border rounded-md w-64"
                       placeholder="Rechercher..." style="font-size: 9px; height: 30px;">
                    
                    <!-- Pour les anciens comptes -->
                    <div class="flex gap-2 ml-2">
                        <button wire:click="$set('showAddAccountModal', true)" 
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <!--+ -->
                            Ajouter
                        </button>
                        <button wire:click="$set('showImportModal', true)" 
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                            <!--📁 -->
                            Importer
                        </button>
                    </div>
                </div>
            </div>

            {{-- Barre de statut sélection --}}
            @if($selectedOldAccount && $quickMapping)
            <div class="px-4 py-2 bg-blue-50 border-b text-xs flex-shrink-0">
                Sélectionné :
                <strong style="font-size: 9px;">
                    {{ $selectedOldAccount->code }}
                </strong>
                — {{ $selectedOldAccount->intitule }}
            </div>
            @endif

            <div class="flex-1 overflow-y-auto min-h-0"> {{-- Important: min-h-0 pour permettre le scroll --}}
                <div class="relative h-full"> {{-- Conteneur pour le scroll --}}
                    <table class="w-full">
                        <thead class="bg-blue-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 text-left py-2 text-xs font-medium text-gray-600 w-1/4" style="font-size: 9px;">
                                    N° Compte
                                </th>
                        
                                <th class="px-4 text-left py-2 text-xs font-medium text-gray-600" style="font-size: 9px;">
                                    <div class="flex items-center justify-between">
                        
                                        <span>Intitulé</span>
                        
                                        @if($oldRoots->count() > 0)
                                            <button
                                                wire:click="confirmDeleteAllOld"
                                                wire:loading.attr="disabled"
                                                class="flex items-center gap-1 px-2 py-0.5
                                                       text-[8px] text-red-600 border border-red-200
                                                       rounded-md bg-red-50
                                                       disabled:opacity-50">
                        
                                                <span wire:loading.remove wire:target="confirmDeleteAllOld">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="sm:inline">Supprimer tous</span>
                                                </span>
                        
                                                <span wire:loading wire:target="confirmDeleteAllOld">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </span>
                                            </button>
                                        @endif
                        
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @include('livewire.mapping.table.tree-old', ['nodes' => $oldRoots, 'level' => 0])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- === PANEL DROIT : NOUVEAUX COMPTES === -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col h-full">
            <div class="p-4 border-b bg-gray-50 flex items-center justify-between flex-shrink-0">
                <h2 class="font-semibold text-gray-700" style="font-size: 9px;">
                    SYCEBNL
                    <span class="text-sm text-gray-500" style="font-size: 9px;">({{ $newRoots->count() }})</span>
                </h2>
                
                <div class="flex items-center gap-3">
                    <input type="text" 
                           wire:model.live.debounce.300ms="searchNew"
                           class="px-3 py-1.5 text-sm border rounded-md w-64"
                           placeholder="Rechercher..." style="font-size: 9px; height: 30px;">
                                        
                    <!-- Pour les nouveaux comptes -->
                    <div class="flex gap-2 ml-2">
                        <button wire:click.prevent="openAddModal('new')" 
                                type="button"
                                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            <!--+ -->
                            Ajouter
                        </button>
                        <button wire:click.prevent="openImportModal('new')" 
                                type="button"
                                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                            <!--📁 -->
                            Importer
                        </button>                               
                    </div>
                </div>
            </div>

            {{-- Barre de statut sélection --}}
            @if($selectedOldAccount)
                <div class="px-4 py-2 bg-blue-50 border-b text-xs flex-shrink-0">
                    Ancien compte sélectionné :
                    <strong style="font-size: 9px;">
                        {{ $selectedOldAccount->code }}
                    </strong>
                    — {{ $selectedOldAccount->intitule }}
                </div>
            @endif

            <div class="flex-1 overflow-y-auto min-h-0">
                <div class="relative h-full">
                    <table class="w-full">
                        <thead class="bg-blue-200 sticky top-0 z-10">
                            <tr>
                                <th class="px-4 text-left py-2 text-xs font-medium text-gray-600 w-1/4" style="font-size: 9px;">
                                    N° Compte
                                </th>
                        
                                <th class="px-4 py-2 text-xs font-medium text-gray-600" style="font-size: 9px;">
                                    <div class="flex items-center justify-between">
                        
                                        <span>Intitulé</span>
                        
                                        @if($newRoots->count() > 0)
                                            <button
                                                wire:click="confirmDeleteAllNew"
                                                wire:loading.attr="disabled"
                                                class="flex items-center gap-1 px-2 py-0.5
                                                       text-[8px] text-red-600 border border-red-200
                                                       rounded-md bg-red-50
                                                       disabled:opacity-50">
                        
                                                <span wire:loading.remove wire:target="confirmDeleteAllNew">
                                                    <i class="fas fa-trash"></i>
                                                    <span class="sm:inline">Supprimer tous</span>
                                                </span>
                        
                                                <span wire:loading wire:target="confirmDeleteAllNew">
                                                    <i class="fas fa-spinner fa-spin"></i>Supprimer tous
                                                </span>
                                            </button>
                                        @endif
                        
                                    </div>
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100">
                            @include('livewire.mapping.table.tree-new', ['nodes' => $newRoots, 'level' => 0])
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>


    <!-- MODAL DE MODIFICATION DE COMPTE -->
@if($showEditAccountModal && $accountToEdit)
<div wire:key="modal-edit-account">
    <div class="fixed inset-0 z-[9999] overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         style="display: block;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal Container -->
        <div class="fixed inset-0 z-[10000] overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
                <!-- Modal Panel -->
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    
                    <!-- Close button (X) -->
                    <button wire:click="closeEditModal" 
                            type="button"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full {{ $accountToEdit->isMapped() ? 'bg-yellow-100' : 'bg-blue-100' }}">
                            <svg class="h-6 w-6 {{ $accountToEdit->isMapped() ? 'text-yellow-600' : 'text-blue-600' }}" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                            </svg>
                        </div>
                        
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                {{ $accountToEdit->isMapped() ? 'Modifier le libellé' : 'Modifier le compte' }}
                            </h3>
                            
                            @if($accountToEdit->isMapped())
                                <div class="mt-2 p-3 bg-yellow-50 rounded-md text-sm text-yellow-700">
                                    ⚠️ Ce compte est déjà mappé. Vous pouvez seulement modifier son libellé.
                                </div>
                            @endif
                            
                            <div class="mt-6 space-y-4 text-left">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Code du compte</label>
                                    <input type="text" 
                                        wire:model="editAccountCode" 
                                        {{ $accountToEdit->isMapped() ? 'disabled' : '' }}
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 {{ $accountToEdit->isMapped() ? 'bg-gray-100' : '' }}"
                                        placeholder="Ex: 411100">
                                    @error('editAccountCode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé</label>
                                    <input type="text" 
                                        wire:model="editAccountIntitule" 
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Ex: Clients - Vente de marchandises">
                                    @error('editAccountIntitule') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>
                                
                                @if(!$accountToEdit->isMapped())
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Compte parent</label>
                                        <select wire:model="editAccountParentId" 
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Aucun (compte racine)</option>
                                            @foreach($oldParents as $parent)
                                                @if($parent['id'] != $accountToEdit->id)
                                                    <option value="{{ $parent['id'] }}">{{ $parent['text'] }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        <button type="button" 
                                wire:click.prevent="updateAccount" 
                                wire:loading.attr="disabled"
                                class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 sm:col-start-2">
                            <span wire:loading.remove wire:target="updateAccount">Mettre à jour</span>
                            <span wire:loading wire:target="updateAccount">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                        <button type="button" 
                                wire:click.prevent="closeEditModal" 
                                class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<!-- MODAL DE SUPPRESSION DE COMPTE -->
@if($showDeleteAccountModal && $deleteAccountInfo)
<div wire:key="modal-delete-account">
    <div class="fixed inset-0 z-[9999] overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true"
         style="display: block;">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal Container -->
        <div class="fixed inset-0 z-[10000] overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                
                <!-- Modal Panel -->
                <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                    
                    <!-- Close button (X) -->
                    <button wire:click="closeDeleteModal" 
                            type="button"
                            class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Fermer</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    <div>
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                        
                        <div class="mt-3 text-center sm:mt-5">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                Confirmer la suppression
                            </h3>
                            
                            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                <div class="text-center">
                                    <p class="text-sm font-medium text-gray-900">
                                        Compte : <span class="text-blue-600">{{ $deleteAccountInfo['code'] }}</span>
                                    </p>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ $deleteAccountInfo['intitule'] }}
                                    </p>
                                </div>
                                
                                <!-- Affichage des vérifications -->
                                <div class="mt-4 space-y-3 text-sm">
                                    @if($deleteAccountInfo['isMapped'])
                                        <div class="flex items-center text-red-600">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Ce compte est mappé ({{ $deleteAccountInfo['mappingsCount'] }} mapping(s))</span>
                                        </div>
                                    @endif
                                    
                                    @if($deleteAccountInfo['hasData'])
                                        <div class="flex items-center text-red-600">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Ce compte contient des données</span>
                                        </div>
                                    @endif
                                    
                                    @if($deleteAccountInfo['hasChildren'])
                                        <div class="flex items-center text-red-600">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                            <span>Ce compte a {{ $deleteAccountInfo['childrenCount'] }} sous-compte(s)</span>
                                        </div>
                                    @endif
                                    
                                    @if($deleteAccountInfo['canDelete'])
                                        <div class="flex items-center text-green-600">
                                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                            <span>Ce compte peut être supprimé</span>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Message d'avertissement -->
                                <div class="mt-4 text-sm text-gray-600">
                                    @if(!$deleteAccountInfo['canDelete'])
                                        <p class="font-medium text-red-600">
                                            ⚠️ Ce compte ne peut pas être supprimé car :
                                        </p>
                                        <ul class="mt-2 list-disc pl-5 text-left">
                                            @if($deleteAccountInfo['isMapped'])
                                                <li>Il est déjà mappé</li>
                                            @endif
                                            @if($deleteAccountInfo['hasData'])
                                                <li>Il contient des données</li>
                                            @endif
                                            @if($deleteAccountInfo['hasChildren'])
                                                <li>Il a des sous-comptes</li>
                                            @endif
                                        </ul>
                                    @else
                                        <p class="font-medium text-yellow-600">
                                            ⚠️ Cette action est irréversible
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                        @if($deleteAccountInfo['canDelete'])
                            <button type="button" 
                                    wire:click.prevent="deleteAccount" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50 sm:col-start-2">
                                <span wire:loading.remove wire:target="deleteAccount">Confirmer la suppression</span>
                                <span wire:loading wire:target="deleteAccount">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                        @endif
                        <button type="button" 
                                wire:click.prevent="closeDeleteModal" 
                                class="{{ $deleteAccountInfo['canDelete'] ? 'sm:col-start-1 sm:mt-0' : 'col-span-2' }} mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            {{ $deleteAccountInfo['canDelete'] ? 'Annuler' : 'Fermer' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif


    <!-- MODAL D'AJOUT DE COMPTE -->
    @if($showAddAccountModal)
    <div wire:key="modal-add-account">
        <div class="fixed inset-0 z-[9999] overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            style="display: block;">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal Container -->
            <div class="fixed inset-0 z-[10000] overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    
                    <!-- Modal Panel -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <!-- Close button (X) en haut à droite -->
                        <button wire:click="closeAddModal" 
                                type="button"
                                class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Fermer</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                    Ajouter un {{ $accountType === 'old' ? 'ancien' : 'nouveau' }} compte
                                </h3>
                                
                                <div class="mt-6 space-y-4 text-left">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Code du compte</label>
                                        <input type="text" 
                                            wire:model="accountCode" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Ex: 411100">
                                        @error('accountCode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé</label>
                                        <input type="text" 
                                            wire:model="accountIntitule" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Ex: Clients - Vente de marchandises">
                                        @error('accountIntitule') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Compte parent</label>
                                        <select wire:model="accountParentId" 
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Aucun (compte racine)</option>
                                            @foreach(($accountType === 'old' ? $oldParents : $newParents) as $parent)
                                                <option value="{{ $parent['id'] }}">{{ $parent['text'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="button" 
                                    wire:click.prevent="addAccount" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 sm:col-start-2">
                                <span wire:loading.remove wire:target="addAccount">Ajouter</span>
                                <span wire:loading wire:target="addAccount">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                            <button type="button" 
                                    wire:click.prevent="closeAddModal" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL D'IMPORT EXCEL -->
    @if($showImportModal)
    <div wire:key="modal-import-account">
        <div class="fixed inset-0 z-[9999] overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            style="display: block;">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal Container -->
            <div class="fixed inset-0 z-[10000] overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    
                    <!-- Modal Panel -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <!-- Close button (X) -->
                        <button wire:click="closeImportModal" 
                                type="button"
                                class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                            <span class="sr-only" style="font-size: 9px;">Fermer</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                            
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900" style="font-size: 9px;">
                                    Importer des {{ $importType === 'old' ? 'anciens' : 'nouveaux' }} comptes
                                </h3>
                                
                                <div class="mt-6 space-y-4 text-left">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 9px;">Fichier Excel</label>
                                        <input type="file" 
                                            wire:model="excelFile" 
                                            accept=".xlsx,.xls,.csv"
                                            class="w-full text-sm text-gray-500
                                                    file:mr-4 file:py-2 file:px-4
                                                    file:rounded-full file:border-0
                                                    file:text-sm file:font-semibold
                                                    file:bg-green-50 file:text-green-700
                                                    hover:file:bg-green-100">
                                        <p class="mt-2 text-xs text-gray-500" style="font-size: 9px;">
                                            Formats supportés: .xlsx, .xls, .csv
                                        </p>
                                        @error('excelFile') <span class="text-red-500 text-xs" style="font-size: 9px;">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <button wire:click.prevent="downloadTemplate('{{ $importType }}')"
                                                type="button"
                                                class="text-sm px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200" style="font-size: 9px;">
                                            📥 Télécharger le template
                                        </button>
                                    </div>
                                    
                                    @if($importing)
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span style="font-size: 9px;">Importation en cours...</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-green-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($importSuccess)
                                        <div class="mt-4 p-3 bg-green-100 text-green-800 rounded-md" style="font-size: 9px;">
                                            ✓ Importation réussie !
                                        </div>
                                    @endif
                                    
                                    @if(count($importErrors) > 0)
                                        <div class="mt-4 p-3 bg-red-100 text-red-800 rounded-md">
                                            <h4 class="font-semibold mb-2" style="font-size: 9px;">Erreurs d'importation :</h4>
                                            <ul class="text-sm space-y-1 max-h-32 overflow-y-auto" style="font-size: 9px;">
                                                @foreach($importErrors as $error)
                                                    <li>• {{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="button" 
                                    wire:click.prevent="importAccounts" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 sm:col-start-2">
                                <span wire:loading.remove wire:target="importAccounts" style="font-size: 9px;">Importer</span>
                                <span wire:loading wire:target="importAccounts">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                            <button type="button" 
                                    wire:click.prevent="closeImportModal" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0" style="font-size: 9px;">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    
    <!-- MODAL DE CONFIRMATION POUR SUPPRIMER TOUS -->
    @if($showDeleteAllModal)
    <div wire:key="modal-delete-all">
        <div class="fixed inset-0 z-[10001] overflow-y-auto" 
             style="display: block;">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
    
            <!-- Modal Container -->
            <div class="fixed inset-0 z-[10002] overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    
                    <!-- Modal Panel -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <!-- Close button -->
                        <button wire:click="closeDeleteAllModal" 
                                type="button"
                                class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
    
                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100">
                                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                </svg>
                            </div>
                            
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900">
                                    Supprimer tous les comptes {{ $deleteAllType === 'old' ? 'ENTITÉ' : 'SYCEBNL' }}
                                </h3>
                                
                                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                    <!-- Statistiques -->
                                    <div class="space-y-3 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-700">Total des comptes :</span>
                                            <span class="font-semibold">{{ $deleteAllCount }}</span>
                                        </div>
                                        
                                        @if($deleteAllWithMappings > 0)
                                        <div class="flex justify-between text-yellow-600">
                                            <span>Avec des mappings :</span>
                                            <span class="font-semibold">{{ $deleteAllWithMappings }}</span>
                                        </div>
                                        @endif
                                        
                                        @if($deleteAllWithData > 0)
                                        <div class="flex justify-between text-red-600">
                                            <span>Avec des données :</span>
                                            <span class="font-semibold">{{ $deleteAllWithData }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Comptes supprimables -->
                                        @php
                                            $deletableCount = $deleteAllCount - $deleteAllWithMappings - $deleteAllWithData;
                                        @endphp
                                        <div class="flex justify-between {{ $deletableCount > 0 ? 'text-green-600' : 'text-gray-600' }}">
                                            <span>Peuvent être supprimés :</span>
                                            <span class="font-semibold">{{ $deletableCount }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Message d'avertissement -->
                                    <div class="mt-4 text-sm text-gray-600">
                                        <p class="font-medium text-red-600 mb-2">
                                            ⚠️ ATTENTION : Cette action est irréversible !
                                        </p>
                                        
                                        @if($deleteAllWithMappings > 0 || $deleteAllWithData > 0)
                                            <p class="mb-2">
                                                Certains comptes ne peuvent pas être supprimés normalement :
                                            </p>
                                            <ul class="list-disc pl-5 space-y-1">
                                                @if($deleteAllWithMappings > 0)
                                                    <li>{{ $deleteAllWithMappings }} compte(s) ont des mappings</li>
                                                @endif
                                                @if($deleteAllWithData > 0)
                                                    <li>{{ $deleteAllWithData }} compte(s) contiennent des données</li>
                                                @endif
                                            </ul>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Boutons d'action -->
                        <div class="mt-5 sm:mt-6 space-y-3">
                            @if($deleteAllCount > 0)
                                <!-- Bouton suppression normale -->
                                <button type="button" 
                                        wire:click.prevent="deleteAllAccounts" 
                                        wire:loading.attr="disabled"
                                        class="w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50">
                                    <span wire:loading.remove wire:target="deleteAllAccounts">
                                        Supprimer uniquement les comptes supprimables
                                    </span>
                                    <span wire:loading wire:target="deleteAllAccounts">
                                        <svg class="animate-spin h-5 w-5 text-white inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Suppression...
                                    </span>
                                </button>
                                
                                @if($deleteAllWithMappings > 0 || $deleteAllWithData > 0)
                                    <!-- Bouton suppression forcée -->
                                    <button type="button" 
                                            wire:click.prevent="forceDeleteAllAccounts" 
                                            wire:loading.attr="disabled"
                                            class="w-full justify-center rounded-md bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="forceDeleteAllAccounts">
                                            ⚠️ Supprimer TOUS FORCÉMENT (y compris avec données/mappings)
                                        </span>
                                        <span wire:loading wire:target="forceDeleteAllAccounts">
                                            <svg class="animate-spin h-5 w-5 text-white inline mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            Suppression FORCÉE...
                                        </span>
                                    </button>
                                @endif
                            @endif
                            
                            <!-- Bouton Annuler -->
                            <button type="button" 
                                    wire:click.prevent="closeDeleteAllModal" 
                                    class="w-full justify-center rounded-md bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-200">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

{{-- Confirmation remap - 100% Tailwind, sans SweetAlert2 --}}
<div x-data="{ showConfirm: false, pendingOldId: null, currentNew: '' }"
     x-show="showConfirm"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95"
     class="fixed inset-0 z-[99999] flex items-center justify-center"
     style="display: none;"
     @confirm-remap.window="
         showConfirm = true;
         pendingOldId = $event.detail.oldId;
         currentNew = $event.detail.newCode + ' - ' + $event.detail.newIntitule;
     "
     @keydown.escape.window="showConfirm = false">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black bg-opacity-50" @click="showConfirm = false"></div>

    <!-- Modal -->
    <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
        <div class="flex items-center gap-4">
            <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3.01L12.732 4.01c-.77-1.333-2.694-1.333-3.464 0L3.34 16.99c-.77 1.333.192 3.01 1.732 3.01z"/>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-lg font-semibold text-gray-900">Compte déjà mappé</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Ce compte ancien est déjà mappé
                </p>
                <!--<p class="mt-2 text-sm font-bold text-blue-700 bg-blue-50 px-3 py-2 rounded-lg inline-block">-->
                <!--    <span x-text="currentNew"></span>-->
                <!--</p>-->
                <p class="mt-3 text-sm text-gray-700">
                    Voulez-vous <strong>remplacer</strong> ce mapping ?
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button @click="showConfirm = false; $wire.set('selectedOld', null)"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                Non, annuler
            </button>
            <button @click="
                showConfirm = false;
                $wire.call('confirmRemap', pendingOldId)
            "
                    class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-md">
                Oui, changer
            </button>
        </div>
    </div>
</div>
{{-- Scroll fluide + effet visuel quand on confirme le remap --}}
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('scroll-to-old', (accountId) => {
            setTimeout(() => {
                const row = document.querySelector(`tr[wire\\:click="selectOld(${accountId})"]`);
                if (row) {
                    row.scrollIntoView({ behavior: 'smooth', block: 'center' });

                    // Effet flash bleu
                    row.classList.add('bg-blue-200', 'ring-4', 'ring-blue-600');
                    setTimeout(() => {
                        row.classList.remove('bg-blue-200', 'ring-4', 'ring-blue-600');
                    }, 1500);
                }
            }, 150);
        });
    });
</script>
</div>