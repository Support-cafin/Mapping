<div class="p-0 mt-12 bg-gray-50 flex flex-col"
     style="height: calc(100vh - 3rem); overflow: hidden;"
     x-data="{
        showImportModal: @entangle('showImportModal'),
        showFilters: false,
        selectedEcritures: [],
        exportFormat: 'excel',
        isLoading: @entangle('isLoading'),
        hasMore: @entangle('hasMore'),
        init() {
            // Infinite scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && this.hasMore && !this.isLoading) {
                        @this.call('loadMore');
                    }
                });
            }, {
                root: null,
                rootMargin: '200px',
                threshold: 0.1
            });
            
            const sentinel = document.getElementById('sentinel');
            if (sentinel) {
                observer.observe(sentinel);
            }
            
            this.$watch('hasMore', (value) => {
                if (!value && sentinel) {
                    observer.unobserve(sentinel);
                }
            });
        }
     }">
    
    <!-- Style pour la scrollbar plus grande et noire -->
    <style>
        .table-scroll-container::-webkit-scrollbar {
            width: 16px !important;
            height: 16px !important;
        }
        
        .table-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1 !important;
            border-radius: 10px !important;
        }
        
        .table-scroll-container::-webkit-scrollbar-thumb {
            background: #1a1a1a !important;
            border-radius: 10px !important;
            border: 3px solid #f1f1f1 !important;
        }
        
        .table-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #000000 !important;
        }
        
        /* Pour Firefox */
        .table-scroll-container {
            scrollbar-width: thick !important;
            scrollbar-color: #1a1a1a #f1f1f1 !important;
        }
    </style>

    @include('livewire.grand-livre.partials.styles')
    
    <!-- Header -->
    <div class="mb-4 flex-shrink-0">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800" style="font-size: 14px;">
                    Grand Livre Comptable : {{ $entreprise->nom }} ({{ $entreprise->code }})
                </h1>
                <p class="text-sm text-gray-600 mt-1" style="font-size: 12px;">
                    Vue détaillée des écritures - Exercice {{ $exercice ?? 'Tous' }}
                </p>
            </div>
            
            <div class="flex items-center space-x-3 mt-3">

                <!-- Bouton Import -->
                <button style="font-size: 12px !important;" 
                        @click="showImportModal = true"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center">
                    <i class="fas fa-file-import mr-2"></i>
                    Importer Excel
                </button>
                <!-- Bouton Ajouter -->
                <button style="font-size: 12px !important;" 
                        wire:click="openAddModal"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter
                </button>
                <!-- Bouton Export -->
                <div class="relative" x-data="{ showExportOptions: false }">
                    <button style="font-size: 12px !important;" 
                            @click="showExportOptions = !showExportOptions"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                        <i class="fas fa-file-export mr-2"></i>
                        Exporter Excel
                    </button>
                
                    <div x-show="showExportOptions" 
                         x-cloak
                         @click.outside="showExportOptions = false"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
                        <button style="font-size: 12px !important;" 
                                wire:click="export('excel')"
                                class="block w-full text-left px-4 py-3 hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-file-excel text-black-500 mr-2"></i>
                            Excel (.xlsx)
                        </button>
                    </div>
                </div>
                <!-- Lien vers Grand Livre Général -->
                <a href="{{ route('grand-livre.general') }}"
                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center" 
                   style="font-size: 12px;">
                    <i class="fas fa-layer-group mr-2"></i>
                    Grand Livre Général 
                </a>
                
                {{-- Remplacer le menu d'export existant par celui-ci --}}

                <!-- Bouton Export -->
                <div class="relative" x-data="{ showExportOptions: false }">
                    <button style="font-size: 12px !important;" 
                            @click="showExportOptions = !showExportOptions"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                    <i class="fas fa-file-export mr-2"></i>
                    Imprimer GLG
                    </button>

                    <div x-show="showExportOptions" 
                         x-cloak
                         @click.outside="showExportOptions = false"
                         class="absolute right-0 mt-2 w-64 bg-white rounded-lg shadow-lg border z-50">
                        <!-- Section Impression -->
                        <div>
                            <div class="px-4 py-2 bg-gray-50 text-xs font-semibold text-gray-600">
                                <i class="fas fa-print text-blue-600 mr-1"></i> Impression
                            </div>
                            <a href="{{ route('grand-livre.print1', $this->getFiltersArray()) }}" 
                               target="_blank"
                               class="block w-full text-left px-4 py-2 hover:bg-gray-50 text-gray-700">
                                <i class="fas fa-print mr-2 text-blue-500"></i>
                                Version imprimable
                            </a>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white p-1 rounded-lg shadow border mb-4 flex-shrink-0" x-data="{ showFilters: true }">
        <div class="flex items-center justify-between mt-1">
            <h3 style="font-size: 12px !important;" class="font-medium text-gray-700 flex items-center">
                <i class="fas fa-filter mr-1"></i> Filtres
            </h3>
    
            <div class="flex space-x-2">
                <button style="font-size: 12px !important;" 
                        @click="showFilters = !showFilters"
                        class="text-blue-600 hover:text-blue-700">
                    <span x-text="showFilters ? 'Masquer' : 'Afficher'"></span>
                </button>
    
                <button style="font-size: 12px !important;" 
                        wire:click="resetFilters"
                        class="text-gray-600 hover:text-gray-700">
                    <i class="fas fa-redo mr-1"></i> Reset
                </button>
            </div>
        </div>
    
        <div x-show="showFilters" class="overflow-x-auto whitespace-nowrap py-0.5 -mx-2 px-2">
            <div class="inline-flex items-end gap-1 flex-nowrap">
                <!-- Stats -->
                <div class="bg-gray-50 px-2 py-0.5 rounded border text-center">
                    <p style="font-size: 12px !important;" class="text-gray-500 leading-none">Total écritures</p>
                    <p style="font-size: 12px !important;" class="font-bold text-[12px] leading-none">
                        {{ number_format($stats['total'], 0, ',', ' ') }}
                    </p>
                </div>
    
                <!-- Période -->
                <div class="min-w-[160px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Période</label>
                    <div class="flex gap-1">
                        <input style="font-size: 12px !important;" 
                               type="date" 
                               wire:model.live="dateDebut" 
                               class="px-1 py-0.5 border rounded w-full">
                        <input style="font-size: 12px !important;" 
                               type="date" 
                               wire:model.live="dateFin" 
                               class="px-1 py-0.5 border rounded w-full">
                    </div>
                </div>
                
                <!-- Source -->
                <div class="min-w-[12px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Source</label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="sourceFilter" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="all">Toutes</option>
                        <option value="manuel">Manuelles</option>
                        <option value="import">Importées</option>
                    </select>
                </div>
    
                <!-- Journal -->
                <div class="min-w-[90px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Journal</label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="journalCode" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="">Tous</option>
                        <option value="RAN">RAN</option>
                        @foreach($journaux as $j)
                            <option value="{{ $j['code'] }}">{{ $j['code'] }}</option>
                        @endforeach
                    </select>
                </div>
    
                <!-- Type compte -->
                <!--<div class="min-w-[12px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Type compte</label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="accountType" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="all">Tous</option>
                        <option value="old">Compte entité</option>
                        <option value="new">Compte SYCEBNL</option>
                    </select>
                </div>-->
                
                <!-- Type compte -->
                <div class="min-w-[12px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Type compte</label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="accountType" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="all">Tous</option>
                        <option value="old">Compte entité</option>
                        <option value="new">Compte SYCEBNL</option>
                    </select>
                </div>
                
                <!-- Sélection multiple de comptes -->
                @if($accountType !== 'all')
                <div class="min-w-[250px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block flex justify-between">
                        <span>Comptes sélectionnés ({{ count($selectedAccounts) }})</span>
                        <span class="flex space-x-2">
                            <button wire:click="selectAllAccounts" class="text-blue-600 hover:text-blue-800 text-[10px]">
                                <i class="fas fa-check-double"></i> Tous
                            </button>
                            <button wire:click="clearSelectedAccounts" class="text-red-600 hover:text-red-800 text-[10px]">
                                <i class="fas fa-times"></i> Effacer
                            </button>
                        </span>
                    </label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="selectedAccounts" 
                            multiple
                            size="5"
                            class="w-full px-1 py-0.5 border rounded h-24">
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}" class="py-0.5">
                                {{ $a->code }} - {{ Str::limit($a->intitule, 30) }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[10px] text-gray-500 mt-1">Maintenez Ctrl pour sélection multiple</p>
                </div>
                @endif
    
                <!-- Compte -->
                @if($accountType !== 'all')
                <div class="min-w-[150px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Compte</label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="accountId" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="">Tous les comptes</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}">
                                {{ $a->code }} - {{ Str::limit($a->intitule, 20) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
    
                <!-- Recherche -->
                <div class="min-w-[180px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Recherche</label>
                    <input style="font-size: 12px !important;" 
                           type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Pièce, libellé, compte…"
                           class="w-full px-2 py-0.5 border rounded">
                </div>
                
                <!-- Statut mapping -->
                <div class="min-w-[12px]">
                    <label style="font-size: 12px !important;" class="text-[9px] font-medium mb-0.5 block">Statut mapping</label>
                    <select style="font-size: 12px !important;" 
                            wire:model.live="mappingFilter" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="all">Toutes</option>
                        <option value="mapped">Mappées</option>
                        <option value="unmapped">Non mappées</option>
                    </select>
                </div>
            </div>
            @if($accountType !== 'all' && count($selectedAccounts) > 0)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-xs">
                    <i class="fas fa-layer-group mr-1"></i> {{ count($selectedAccounts) }} compte(s) sélectionné(s)
                    <button wire:click="clearSelectedAccounts" class="ml-1 text-purple-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            @endif
            
            <!-- Bouton pour activer/désactiver le classement par compte -->
          {{--  @if($accountType !== 'all' && count($selectedAccounts) > 0)
            <button style="font-size: 12px !important;" 
                    wire:click="$toggle('groupByAccount')"
                    class="px-3 py-2 {{ $groupByAccount ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' }} rounded-lg hover:bg-indigo-700 transition flex items-center">
                <i class="fas {{ $groupByAccount ? 'fa-layer-group' : 'fa-list' }} mr-2"></i>
                {{ $groupByAccount ? 'Vue normale' : 'Classer par compte' }}
            </button>
            @endif --}}
        </div>
    
        <!-- Résumé filtres actifs -->
        <div class="mt-2 flex flex-wrap gap-1">
            @if($dateDebut && $dateFin)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs">
                    {{ \Carbon\Carbon::parse($dateDebut)->format('d/m') }} → {{ \Carbon\Carbon::parse($dateFin)->format('d/m') }}
                    <button wire:click="$set('dateDebut','')" class="ml-1 text-blue-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
    
            @if($journalCode)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-black-800 text-xs">
                    Journal: {{ $journalCode }}
                    <button wire:click="$set('journalCode','')" class="ml-1 text-black-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
    
            @if($mappingFilter !== 'all')
                <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $mappingFilter === 'mapped' ? 'bg-green-100 text-black-800' : 'bg-red-100 text-black-800' }} text-xs">
                    @if($mappingFilter === 'mapped')
                        <i class="fas fa-check-circle mr-1"></i> Mappées
                    @else
                        <i class="fas fa-exclamation-triangle mr-1"></i> Non mappées
                    @endif
                    <button wire:click="$set('mappingFilter','all')" class="ml-1">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            @endif
        </div>
    </div>
    
    <!-- Tableau des écritures -->
    <div class="bg-white rounded-lg shadow border overflow-hidden flex flex-col h-full">
        <div class="table-scroll-container">
            <!-- Indicateur de chargement -->
            @if($isLoading)
            <div class="loading-indicator">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    <span class="text-sm text-gray-600">Chargement...</span>
                </div>
            </div>
            @endif
            
            <!-- Barre de sélection -->
            @include('livewire.grand-livre.partials.selection-toolbar')
            
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-800 text-white sticky top-0 z-30">
                    <tr>
                        <!-- Colonne de sélection -->
                        <th class="px-2 py-0.5 text-center text-xs font-medium" style="font-size: 12px; width: 40px;">
                            <input type="checkbox" 
                                   id="select-all" 
                                   class="h-3 w-3 rounded border-gray-300"
                                   onclick="toggleAllCheckboxes(this)">
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 60px;">N° ligne</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 90px;">Date</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 70px;">Journal</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 100px;">Pièce</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 120px;">Compte Entité</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 250px;">Libellé</th>
                        <th class="px-2 py-0.5 text-right text-xs font-medium" style="font-size: 12px; width: 150px;">Débit</th>
                        <th class="px-2 py-0.5 text-right text-xs font-medium" style="font-size: 12px; width: 150px;">Crédit</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 150px;">Compte SYCEBNL</th>
                        <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 12px; width: 80px;">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $ecrituresNormales = $ecritures->where('source', '!=', 'manuel');
                        $ecrituresManuelles = $ecritures->where('source', 'manuel');
                        $manualCount = $ecrituresManuelles->count();
                        $normalCount = $ecrituresNormales->count();
                        $nbr_ligne = 1;
                    @endphp
                    
                    <!-- Écritures normales -->
                    @forelse($ecrituresNormales as $ecriture)
                        @include('livewire.grand-livre.partials.ecriture-row', [
                            'ecriture' => $ecriture,
                            'nbr_ligne' => $nbr_ligne,
                            'isManual' => false
                        ])
                        @php $nbr_ligne++; @endphp
                    @empty
                        @if($manualCount == 0)
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-book text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg">Aucune écriture trouvée</p>
                                </div>
                            </td>
                        </tr>
                        @endif
                    @endforelse
                    
                    <!-- Séparation avant écritures manuelles -->
                    @if($manualCount > 0 && $normalCount > 0)
                    <tr class="bg-yellow-50 border-y-2 border-yellow-300">
                        <td colspan="12" class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center text-yellow-700 font-medium text-sm">
                                ÉCRITURES AJOUTÉES MANUELLEMENT ({{ $manualCount }})
                            </div>
                        </td>
                    </tr>
                    @endif
                    
                    <!-- Écritures manuelles -->
                    @foreach($ecrituresManuelles as $ecriture)
                        @include('livewire.grand-livre.partials.ecriture-row', [
                            'ecriture' => $ecriture,
                            'nbr_ligne' => $nbr_ligne,
                            'isManual' => true
                        ])
                        @php $nbr_ligne++; @endphp
                    @endforeach
                    
                @if($ecritures->count() > 0)
                    <tr class="bg-gray-100 font-bold border-t-2 border-gray-400">
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px; width: 40px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium text-gray-700" style="font-size: 11px;">TOTAUX :</td>
                        <td class="px-2 py-1 text-right text-xs font-bold text-blue-700" style="font-size: 11px;">{{ number_format($totals['total_debit'], 0, ' ', ' ') }}</td>
                        <td class="px-2 py-1 text-right text-xs font-bold text-green-700" style="font-size: 11px;">{{ number_format($totals['total_credit'], 0, ' ', ' ') }}</td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-bold text-purple-700" style="font-size: 11px;"></td>
                     </tr>
                     @if($totals['total_debit'] >= $totals['total_credit'])
                     <tr class="bg-gray-100 font-bold border-t-2 border-gray-400">
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px; width: 40px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium text-gray-700" style="font-size: 11px;"></td>
                        <td class="px-2 py-1 text-right text-xs font-bold text-blue-700" style="font-size: 11px;">Solde = {{ number_format($totals['difference'], 0, ' ', ' ') }}</td>
                        <td class="px-2 py-1 text-right text-xs font-bold text-green-700" style="font-size: 11px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-bold text-purple-700" style="font-size: 11px;"></td>
                     </tr>
                     @else
                     <tr class="bg-gray-100 font-bold border-t-2 border-gray-400">
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px; width: 40px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-medium text-gray-700" style="font-size: 11px;"></td>
                        <td class="px-2 py-1 text-right text-xs font-bold text-blue-700" style="font-size: 11px;"></td>
                        <td class="px-2 py-1 text-right text-xs font-bold text-green-700" style="font-size: 11px;">Solde = {{ number_format($totals['difference'], 0, ' ', ' ') }}</td>
                        <td class="px-2 py-1 text-left text-xs font-medium" style="font-size: 12px;"></td>
                        <td class="px-2 py-1 text-left text-xs font-bold text-purple-700" style="font-size: 11px;"></td>
                    </tr>
                    @endif
                @endif
                </tbody>
            </table>
            <style>
                /* Amélioration de l'affichage des montants */
                .montant-cell {
                    font-family: 'Courier New', monospace;
                    font-weight: 600;
                    white-space: nowrap;
                }
                
                /* Ajustement de la largeur des colonnes */
                .table-scroll-container {
                    overflow-x: auto;
                    overflow-y: auto;
                    max-height: calc(100vh - 200px);
                }
                
                /* Style pour les cellules de montant */
                td.text-right span {
                    display: inline-block;
                    min-width: 130px;
                    text-align: right;
                }
                
                /* Amélioration de la lisibilité */
                .bg-blue-50, .bg-green-50, .bg-red-50 {
                    border-radius: 4px;
                    padding: 4px 8px;
                }
            </style>
            <!-- LIGNE DES TOTAUX CORRIGÉE -->
            

            <!-- Sentinel pour infinite scroll -->
            <div id="sentinel" class="h-10 flex items-center justify-center">
                @if($hasMore && $totalCount > 0)
                    <div class="text-center py-4">
                        <div class="inline-flex items-center space-x-2 text-gray-600">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                            <span class="text-sm">Chargement des écritures suivantes...</span>
                        </div>
                    </div>
                @elseif($totalCount > 0)
                    <div class="text-center py-4 text-gray-500 text-sm">
                        <i class="fas fa-check-circle text-black-500 mr-2"></i>
                        Toutes les écritures sont chargées ({{ number_format($totalCount, 0, ',', ' ') }} au total)
                    </div>
                @endif
            </div>
             
            <!-- Statistiques par classe de comptes -->
            <div class="mt-6 bg-white rounded-lg shadow border">
                <div class="px-6 py-4 border-b">
                    <h3 class="font-semibold text-gray-900 flex items-center" style="font-size: 12px;">
                        <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
                        Statistiques par classe de comptes
                    </h3>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 12px;">Classe de comptes SYCEBNL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 12px;">Total Débit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 12px;">Total Crédit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 12px;">Solde</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded mr-2">1-5</span>
                                        <div class="font-medium text-gray-900" style="font-size: 12px;">Comptes SYCEBNL 1 à 5</div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['classe_1_5']['debit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['classe_1_5']['credit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 font-bold {{ $classStats['classe_1_5']['solde'] > 0 ? 'text-black-600' : 'text-black-600' }}" style="font-size: 12px;">
                                    {{ number_format(abs($classStats['classe_1_5']['solde']), 0, ' ', ' ') }} FCFA
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs bg-green-100 text-black-800 px-1.5 py-0.5 rounded mr-2">6 à 8</span>
                                        <div class="font-medium text-gray-900" style="font-size: 12px;">Comptes SYCEBNL 6 à 8</div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['classe_6_7']['debit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['classe_6_7']['credit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 font-bold {{ $classStats['classe_6_7']['solde'] > 0 ? 'text-black-600' : 'text-black-600' }}" style="font-size: 12px;">
                                    {{ number_format(abs($classStats['classe_6_7']['solde']), 0, ' ', ' ') }} FCFA
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs bg-yellow-100 text-black-800 px-1.5 py-0.5 rounded mr-2">9</span>
                                        <div class="font-medium text-gray-900" style="font-size: 12px;">Comptes SYCEBNL 9</div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['classe_9']['debit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['classe_9']['credit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 font-bold {{ $classStats['classe_9']['solde'] > 0 ? 'text-black-600' : 'text-black-600' }}" style="font-size: 12px;">
                                    {{ number_format(abs($classStats['classe_9']['solde']), 0, ' ', ' ') }} FCFA
                                </td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3">
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs bg-red-100 text-black-800 px-1.5 py-0.5 rounded mr-2">NM</span>
                                        <div class="font-medium text-gray-900" style="font-size: 12px;">Écritures non mappées</div>
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['non_mappes']['debit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 text-black-600 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['non_mappes']['credit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 font-bold {{ $classStats['non_mappes']['solde'] > 0 ? 'text-black-600' : 'text-black-600' }}" style="font-size: 12px;">
                                    {{ number_format(abs($classStats['non_mappes']['solde']), 0, ' ', ' ') }} FCFA
                                </td>
                            </tr>
                            <tr class="bg-gray-50 font-bold border-t-2">
                                <td class="px-6 py-3 font-bold text-gray-900" style="font-size: 12px;">TOTAL GLOBAL</td>
                                <td class="px-6 py-3 text-black-700 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['global']['debit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 text-black-700 font-bold" style="font-size: 12px;">
                                    {{ number_format($classStats['global']['credit'], 0, ' ', ' ') }} FCFA
                                </td>
                                <td class="px-6 py-3 text-blue-700 font-bold" style="font-size: 12px;">
                                    {{ number_format(abs($classStats['global']['solde']), 0, ' ', ' ') }} FCFA
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Messages flash -->
    @if(session()->has('success'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 5000)"
             class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 z-50">
            <i class="fas fa-check-circle"></i>
            <span style="font-size: 12px;">{{ session('success') }}</span>
        </div>
    @endif
    
    @if(session()->has('error'))
        <div x-data="{ show: true }" 
             x-show="show" 
             x-init="setTimeout(() => show = false, 8000)"
             class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3 z-50">
            <i class="fas fa-exclamation-circle"></i>
            <span style="font-size: 12px;">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Inclure les modals (import, édition, ajout) si nécessaire --}}
    @include('livewire.grand-livre.partials.import-modal')
    @include('livewire.grand-livre.partials.edit-modal')
    @include('livewire.grand-livre.partials.delete-modal')
    @include('livewire.grand-livre.partials.add-modal')
    
    <script>
document.addEventListener('livewire:load', function () {
    let select = new TomSelect("#accountSelect", {
        create: false,
        sortField: {
            field: "text",
            direction: "asc"
        }
    });

    // Sync avec Livewire
    select.on('change', function (value) {
        @this.set('addOldAccountId', value);
    });
});
</script>
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('export-start', () => {
            Swal.fire({
                title: 'Préparation de l\'export...',
                html: 'Veuillez patienter pendant la génération du PDF',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
        });
        
        Livewire.on('export-complete', () => {
            Swal.close();
        });
    });
</script>
    @include('livewire.grand-livre.partials.scripts')
</div>