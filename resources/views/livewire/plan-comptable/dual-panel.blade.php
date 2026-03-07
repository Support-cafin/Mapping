<div class="flex flex-col space-y-4 bg-gray-50"
     style="margin-top:80px; height: calc(100vh - 80px); overflow: hidden;">
    
    <style>
        
        html, body {
            height: 100%;
            overflow: hidden;
        }
    
        .table-scroll-container {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            padding-bottom: 12px;
        }
    
        .table-header-fixed {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #f3f4f6;
        }
    
        .table-scroll-container::-webkit-scrollbar {
            width: 6px;
        }
    
        .table-scroll-container::-webkit-scrollbar-thumb {
            background-color: #cbd5e1;
            border-radius: 3px;
        }
    
        .animate-fade-in-down {
            animation: fadeInDown 0.5s ease-out;
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .notification-exit {
            transition: all 0.3s ease;
        }
    </style>
    
    <!-- Notification de succès -->
    @if (session()->has('success'))
        <div 
            wire:ignore
            id="success-notification"
            class="fixed top-20 right-5 z-[9999] animate-fade-in-down"
        >
            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-r-lg shadow-lg max-w-sm">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
    
                    <p class="ml-3 text-sm text-green-800 font-medium">
                        {{ session('success') }}
                    </p>
    
                    <button 
                        onclick="this.closest('#success-notification').remove()"
                        class="ml-auto text-green-400 hover:text-green-600"
                    >
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        </div>
    
        <script>
            (function () {
                const notif = document.getElementById('success-notification');
                if (!notif) return;
    
                setTimeout(() => {
                    notif.style.transition = 'all 0.3s ease';
                    notif.style.opacity = '0';
                    notif.style.transform = 'translateX(20px)';
    
                    setTimeout(() => notif.remove(), 300);
                }, 3000);
            })();
        </script>
    @endif



    <div class="bg-white rounded-xl shadow border border-gray-200
                flex flex-col h-full">
        @php $entr = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->first(); @endphp
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-semibold">{{ $entr->nom ?? 'non renseigné' }}</h1>
        </div>
        <div class="flex items-center justify-between px-5 py-4 border-b
            bg-gradient-to-r from-gray-50 to-gray-100 flex-shrink-0">
        
            <div>
                <h3 class="font-semibold text-gray-800 text-sm">
                    Mapping : Correspondances comptables
                </h3>
                <p class="text-xs text-gray-500">
                    Liaison entre le plan ONG et le plan SYCEBNL
                </p>
            </div>
        
            <div class="flex items-center gap-2">
                <!-- Bouton Supprimer tous -->
                @if($this->mappingList->count() > 0)
                    <button 
                        wire:click="confirmDeleteAllMappings"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 shadow-sm">
                        <i class="fas fa-trash-alt mr-1"></i>
                        Supprimer tous
                    </button>
                @endif
                
                <button 
                    wire:click="openAddMappingModal"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 shadow-sm">
                    <i class="fas fa-plus"></i>
                    Ajouter
                </button>
        
                <button 
                    wire:click="openImportMappingModal"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 shadow-sm">
                    <i class="fas fa-file-excel"></i>
                    Importer
                </button>
            </div>
        </div>


        <div class="table-scroll-container">
            <table class="w-full text-sm">
                <thead class="table-header-fixed">
                    @php $nbr = 1; @endphp
                    <tr>
                        <!-- Colonne Plan Comptable ONG -->
                        <th class="px-4 py-3 text-left align-top w-1/3">
                            
                            <!-- Input recherche -->
                            <div class="mb-2">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="searchOld"
                                    placeholder="🔍 Rechercher code ou intitulé"
                                    class="w-full px-3 py-1.5 text-xs rounded-md border border-gray-300 
                                           focus:ring-1 focus:ring-blue-500 focus:border-blue-500
                                           placeholder-gray-400"
                                />
                            </div>
                
                            <!-- Titre -->
                            <div class="font-semibold text-gray-800 mb-1 flex items-center gap-1 text-xs">
                                <i class="fas fa-building text-gray-500"></i>
                                Plan Comptable ONG :  {{ count($this->mappingList) ?? 0 }}
                            </div>  
                
                            <!-- Sous-entêtes -->
                            <div class="grid grid-cols-12 gap-2 text-gray-600 border-t pt-1">
                                <div class="col-span-2 font-medium" style="font-size: 11px;">N°</div>
                                <div class="col-span-2 font-medium" style="font-size: 11px;">N° compte</div>
                                <div class="col-span-8 font-medium text-center" style="font-size: 11px;">Intitulé</div>
                            </div>
                        </th>
                
                        <!-- Flèche -->
                        <th class="px-2 py-3 text-center align-top w-10">
                            <div class="h-6"></div>
                        </th>
                
                        <!-- Colonne Affectation au SYCEBNL -->
                        <th class="px-4 py-3 text-left align-top w-1/3">
                            
                            <!-- Input recherche -->
                            <div class="mb-2">
                                <input
                                    type="text"
                                    wire:model.live.debounce.300ms="searchNew"
                                    placeholder="🔍 Rechercher code ou intitulé"
                                    class="w-full px-3 py-1.5 text-xs rounded-md border border-gray-300 
                                           focus:ring-1 focus:ring-green-500 focus:border-green-500
                                           placeholder-gray-400"
                                />
                            </div>
                
                            <!-- Titre -->
                            <div class="font-semibold text-gray-800 mb-1 flex items-center gap-1 text-xs">
                                <i class="fas fa-link text-green-600"></i>
                                Plan SYCEBNL
                            </div>
                
                            <!-- Sous-entêtes -->
                            <div class="grid grid-cols-12 gap-2 text-gray-600 border-t pt-1">
                                <div class="col-span-4 font-medium" style="font-size: 11px;">N° compte</div>
                                <div class="col-span-8 font-medium" style="font-size: 11px;">Intitulé</div>
                            </div>
                        </th>
                
                        <!-- Actions -->
                        <th class="px-4 py-3 text-left align-top w-28">
                            <div class="font-medium text-gray-800" style="font-size: 11px;">
                                Actions
                            </div>
                        </th>
                    </tr>
                </thead>


                <tbody class="divide-y">
                    @foreach ($this->mappingList as $map)
                    <tr class="border-b hover:bg-gray-50 transition-colors">

                        <!-- Ancien compte -->
                        <td class="px-4 py-0.5">
                            <div class="grid grid-cols-12 gap-x-4 border-b border-gray-200 py-1">
                                <div class="col-span-2">
                                    <span class="font-semibold text-gray-900 text-[11px]">{{ $nbr }}</span>
                                </div>
                            
                                <div class="col-span-2 text-right pr-3">
                                <span class="font-semibold text-gray-900 text-[11px]">
                                    {{ $map->oldAccount->code ?? 0 }}
                                </span>
                            </div>
                            
                            <div class="col-span-8 pl-10">
                                <span class="text-gray-700 text-[11px]">
                                    {{ $map->oldAccount->intitule ?? 0 }}
                                </span>
                            </div>
                            </div>
                        </td>

                        <!-- Flèche -->
                        <td class="px-2 py-0.5 text-center">
                            <div class="text-blue-500 font-bold text-lg" style="font-size: 11px;">→</div>
                        </td>

                        <!-- Nouveau compte -->
                        <td class="px-4 py-0.5">
                            <div class="grid grid-cols-12 gap-x-4 border-b border-gray-200 py-1">
                                <div class="col-span-2 text-right pr-3">
                                    <span class="font-semibold text-gray-900 text-[11px]">
                                        {{ $map->newAccount->code ?? 0 }}
                                    </span>
                                </div>
                                <div class="col-span-8 pl-10">
                                    <span class="text-gray-700 text-[11px]">
                                        {{ $map->newAccount->intitule ?? 0 }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Actions -->
                            <td class="px-4 py-0.5">
                                <div class="flex gap-2">
                            
                                    <!-- Modifier -->
                                    <button 
                                        wire:click="openEditMapping({{ $map->id }})"
                                        class="px-3 py-0.5 bg-blue-50 text-blue-700 rounded-lg
                                               hover:bg-blue-100 border border-blue-100"
                                        style="font-size: 11px;">
                                        Modifier
                                    </button>
                            
                                    <!-- Supprimer - UTILISE LA NOUVELLE MÉTHODE -->
                                    <button
                                        wire:click="openDeleteModal({{ $map->id }})"
                                        class="px-3 py-0.5 bg-red-50 text-red-600 rounded-lg
                                               hover:bg-red-100 border border-red-100"
                                        style="font-size: 11px;">
                                        Supprimer
                                    </button>
                            
                                </div>
                            </td>


                    </tr>
                    @php $nbr++; @endphp
                    @endforeach
                </tbody>
            </table>
        </div>

    </div>

    <!-- Modal (inchangé) -->
    <!-- Modal -->
    @if ($showModal && $editingMappingId)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
    
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50 rounded-t-2xl">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">
                        Modifier une correspondance
                    </h2>
                    <p class="text-xs text-gray-500">
                        Liaison entre plan ONG et plan SYCEBNL
                    </p>
                </div>
    
                <button 
                    wire:click="$set('showModal', false)"
                    class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
    
            {{-- Body --}}
            <div class="px-6 py-5 space-y-4 text-xs">
    
               {{-- Ancien compte --}}
<div>
    <label class="flex items-center gap-1 font-medium text-gray-700 mb-1">
        <i class="fas fa-building text-gray-400"></i>
        Compte entité
    </label>
    @php 
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $oldAccounts = \App\Models\OldAccount::where('entreprise_id', auth()->user()->entreprise_id)
                        ->where('exercice_id', $exo->id)
                        ->orderBy('code')
                        ->get();
        
        // Récupérer le mapping en cours d'édition pour afficher ses valeurs
        $currentMapping = $editingMappingId ? \App\Models\AccountMapping::find($editingMappingId) : null;
    @endphp
    <select 
        wire:model="editOldAccount"
        class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
        @foreach($oldAccounts as $old)
            <option value="{{ $old->id }}" 
                {{ $currentMapping && $currentMapping->old_account_id == $old->id ? 'selected' : '' }}>
                {{ $old->code }} — {{ $old->intitule }}
            </option>
        @endforeach
    </select>
    
    {{-- Afficher l'ID du compte sélectionné pour déboguer --}}
    @if($currentMapping)
        <p class="text-[10px] text-gray-400 mt-1">
            Compte sélectionné ID: {{ $currentMapping->old_account_id }}
        </p>
    @endif
</div>

{{-- Flèche visuelle --}}
<div class="flex justify-center text-blue-500">
    <i class="fas fa-arrow-down"></i>
</div>

{{-- Nouveau compte --}}
<div>
    <label class="flex items-center gap-1 font-medium text-gray-700 mb-1">
        <i class="fas fa-link text-green-600"></i>
        Compte SYCEBNL
    </label>
    <select 
        wire:model="editNewAccount"
        class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-green-500 focus:border-green-500">
        @php
            $newAccounts = \App\Models\NewAccount::where('entreprise_id', auth()->user()->entreprise_id)
                            ->where('exercice_id', $exo->id)
                            ->orderBy('code')
                            ->get();
        @endphp
        @foreach($newAccounts as $new)
            <option value="{{ $new->id }}"
                {{ $currentMapping && $currentMapping->new_account_id == $new->id ? 'selected' : '' }}>
                {{ $new->code }} — {{ $new->intitule }}
            </option>
        @endforeach
    </select>
    
    {{-- Afficher l'ID du compte sélectionné pour déboguer --}}
    @if($currentMapping)
        <p class="text-[10px] text-gray-400 mt-1">
            Compte sélectionné ID: {{ $currentMapping->new_account_id }}
        </p>
    @endif
</div>
    
            </div>
    
            {{-- Footer --}}
<div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl">

    <div class="flex justify-between items-center">
        @if(!$confirmDelete)
            {{-- Mode Édition --}}
            <button 
                wire:click="$set('confirmDelete', true)"
                class="text-xs text-red-600 hover:text-red-800 flex items-center gap-1">
                <i class="fas fa-trash"></i>
                Supprimer
            </button>

            <div class="flex gap-2">
                <button 
                    wire:click="closeModal"
                    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200 transition">
                    Annuler
                </button>

                <button 
                    wire:click="updateMapping"
                    class="px-4 py-1.5 bg-blue-600 text-white rounded-lg text-xs hover:bg-blue-700 shadow-sm transition">
                    Enregistrer
                </button>
            </div>
        @else
            {{-- Mode Suppression --}}
            <div></div> {{-- Pour l'alignement --}}
            
            <div class="flex gap-2">
                <button 
    wire:click="closeModal"
    class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200 transition">
    Annuler
</button>

                <button 
                    wire:click="deleteMapping({{ $editingMappingId }})"
                    class="px-4 py-1.5 bg-red-600 text-white rounded-lg text-xs hover:bg-red-700 shadow-sm transition">
                    Confirmer suppression
                </button>
            </div>
        @endif
    </div>

    {{-- Message de confirmation (uniquement en mode suppression) --}}
    @if($confirmDelete)
        <div class="mt-4 bg-red-50 border border-red-200 rounded-lg p-3 text-xs">
            <p class="text-red-700 font-medium flex items-center gap-1">
                <i class="fas fa-exclamation-triangle"></i>
                Cette action est irréversible
            </p>
            <p class="text-gray-600 mt-1">
                Voulez-vous vraiment supprimer cette correspondance ?
            </p>
        </div>
    @endif

</div>
    
        </div>
    </div>     
    @endif
                    

    @if ($showAddMappingModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">
    
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b bg-gray-50 rounded-t-2xl">
                <div>
                    <h2 class="text-sm font-semibold text-gray-800">
                        Nouvelle correspondance
                    </h2>
                    <p class="text-xs text-gray-500">
                        Créer une liaison entre plan ONG et plan SYCEBNL
                    </p>
                </div>
    
                <button 
                    wire:click="$set('showAddMappingModal', false)"
                    class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
    
            {{-- Body --}}
            <div class="px-6 py-5 space-y-4 text-xs">
    
                {{-- Ancien compte --}}
<div>
    <label class="flex items-center gap-1 font-medium text-gray-700 mb-1">
        <i class="fas fa-building text-gray-400"></i>
        Compte entité
    </label>
    @php 
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $oldAccounts = \App\Models\OldAccount::where('entreprise_id', auth()->user()->entreprise_id)
                        ->where('exercice_id', $exo->id)  // 🔥 AJOUT de cette condition
                        ->orderBy('code')
                        ->get();
    @endphp
    <select 
        wire:model="addOldAccount"
        class="w-full border-gray-300 rounded-lg px-3 py-2
               focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
        <option value="">— Sélectionner un compte —</option>
        @foreach($oldAccounts as $old)
            <option value="{{ $old->id }}">
                {{ $old->code }} — {{ $old->intitule }}
            </option>
        @endforeach
    </select>
</div>

{{-- Nouveau compte --}}
<div>
    <label class="flex items-center gap-1 font-medium text-gray-700 mb-1">
        <i class="fas fa-link text-green-600"></i>
        Compte SYCEBNL
    </label>
    <select 
        wire:model="addNewAccount"
        class="w-full border-gray-300 rounded-lg px-3 py-2
               focus:ring-1 focus:ring-green-500 focus:border-green-500">
        <option value="">— Sélectionner un compte —</option>
        @php
            $newAccounts = \App\Models\NewAccount::where('entreprise_id', auth()->user()->entreprise_id)
                            ->where('exercice_id', $exo->id)  // 🔥 AJOUT de cette condition
                            ->orderBy('code')
                            ->get();
        @endphp
        @foreach($newAccounts as $new)
            <option value="{{ $new->id }}">
                {{ $new->code }} — {{ $new->intitule }}
            </option>
        @endforeach
    </select>
</div>
    
            </div>
    
            {{-- Footer --}}
            <div class="px-6 py-4 border-t bg-gray-50 rounded-b-2xl">
                <div class="flex justify-end gap-2">
    
                    <button 
                        wire:click="$set('showAddMappingModal', false)"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-xs hover:bg-gray-200 transition">
                        Annuler
                    </button>
    
                    <button 
                        wire:click="createMapping"
                        class="px-4 py-1.5 bg-blue-600 text-white rounded-lg text-xs
                               hover:bg-blue-700 shadow-sm transition">
                        Enregistrer
                    </button>
    
                </div>
            </div>
    
        </div>
    </div>
    @endif
    
    <!-- MODAL DE CONFIRMATION POUR SUPPRIMER TOUS LES MAPPINGS -->
    @if($showDeleteAllMappingsModal)
    <div wire:key="modal-delete-all-mappings">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[9999]">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                
                <!-- Icône d'avertissement -->
                <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                </div>
                
                <!-- Titre -->
                <h3 class="text-lg font-semibold text-center text-gray-900 mb-2">
                    Supprimer tous les mappings
                </h3>
                
                <!-- Message -->
                <div class="text-center mb-6">
                    <p class="text-sm text-gray-600 mb-3">
                        Vous êtes sur le point de supprimer <strong>{{ $deleteAllMappingsCount }} correspondance(s)</strong>.
                    </p>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 text-xs text-yellow-800">
                        <p class="font-medium mb-1">⚠️ Impact sur le grand livre :</p>
                        <ul class="list-disc pl-5 text-left">
                            <li>Les écritures du grand livre seront dé-mappées</li>
                            <li>Les correspondances seront définitivement supprimées</li>
                            <li>Cette action est irréversible</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Boutons -->
                <div class="flex justify-center gap-3">
                    <!-- Annuler -->
                    <button 
                        wire:click="closeDeleteAllMappingsModal"
                        type="button"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Annuler
                    </button>
                    
                    <!-- Confirmer -->
                    <button 
                        wire:click="deleteAllMappings"
                        type="button"
                        class="px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition shadow-md">
                        <span wire:loading.remove wire:target="deleteAllMappings">
                            <i class="fas fa-trash-alt mr-2"></i> Supprimer tous
                        </span>
                        <span wire:loading wire:target="deleteAllMappings">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Suppression...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if ($showImportMappingModal)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white w-full max-w-lg rounded-xl p-6 shadow-xl">

            <h2 class="text-xl font-semibold mb-4" style="font-size: 11px;">Importer des correspondances</h2>

            <div class="mb-4">
                <a href="#" wire:click.prevent="downloadMappingTemplate"
                class="text-blue-600 underline text-sm" style="font-size: 11px;">
                📥 Télécharger le template Excel
                </a>
            </div>

            <input type="file" wire:model="mappingFile" class="mb-4 w-full border rounded px-3 py-2">

            @if ($importErrors)
                <div class="bg-red-50 text-red-700 p-2 mb-3 rounded text-sm">
                    <ul>
                        @foreach ($importErrors as $err)
                            <li style="font-size: 11px;">• {{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <button wire:click="$set('showImportMappingModal', false)" class="px-4 py-2 bg-gray-100 rounded-lg" style="font-size: 11px;">Annuler</button>

                <button wire:click="importMappings"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg" style="font-size: 11px;">
                    Importer
                </button>
            </div>

        </div>
    </div>
    @endif


</div>