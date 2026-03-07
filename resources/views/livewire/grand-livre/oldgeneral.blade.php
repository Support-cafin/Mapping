<div class="p-0 mt-12 bg-gray-50 flex flex-col"
     style="height: calc(100vh - 3rem); overflow: hidden;"
     x-data="{
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
            
            const sentinel = document.getElementById('sentinel-general');
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
    
    <!-- Inclure le CSS -->
    @include('livewire.grand-livre.partials.styles')
    
    <!-- Header -->
    <div class="mb-4 flex-shrink-0">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800" style="font-size: 14px;">
                    Grand Livre Général : {{ $entreprise->nom }} ({{ $entreprise->code }})
                </h1>
                <p class="text-sm text-gray-600 mt-1" style="font-size: 11px;">
                    Vue récapitulative par compte SYCEBNL - Exercice {{ $exercice ?? 'Tous' }}
                </p>
            </div>
            
            <div class="flex items-center space-x-3 mt-3">
                <!-- Lien vers Grand Livre Détail -->
                <a href="{{ route('grand-livre.detail') }}"
                   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center" 
                   style="font-size: 11px;">
                    <i class="fas fa-list mr-2"></i>
                    Grand Livre Détail
                </a>

                <!-- Bouton Export -->
                <div class="relative" x-data="{ showExportOptions: false }">
                    <button style="font-size: 11px !important;" 
                            @click="showExportOptions = !showExportOptions"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                        <i class="fas fa-file-export mr-2"></i>
                        Exporter Général
                    </button>
                
                    <div x-show="showExportOptions" 
                         x-cloak
                         @click.outside="showExportOptions = false"
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
                        <button style="font-size: 11px !important;" 
                                wire:click="export('excel')"
                                class="block w-full text-left px-4 py-3 hover:bg-gray-50 text-gray-700">
                            <i class="fas fa-file-excel text-green-500 mr-2"></i>
                            Excel (.xlsx)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white p-1 rounded-lg shadow border mb-4 flex-shrink-0" x-data="{ showFilters: true }">
        <div class="flex items-center justify-between mt-1">
            <h3 style="font-size: 11px !important;" class="font-medium text-gray-700 flex items-center">
                <i class="fas fa-filter mr-1"></i> Filtres
            </h3>
    
            <div class="flex space-x-2">
                <button style="font-size: 11px !important;" 
                        @click="showFilters = !showFilters"
                        class="text-blue-600 hover:text-blue-700">
                    <span x-text="showFilters ? 'Masquer' : 'Afficher'"></span>
                </button>
    
                <button style="font-size: 11px !important;" 
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
                    <p style="font-size: 11px !important;" class="text-gray-500 leading-none">Comptes SYCEBNL</p>
                    <p style="font-size: 11px !important;" class="font-bold text-[10px] leading-none">
                        {{ number_format($recapStats['total_comptes'], 0, ',', ' ') }}
                    </p>
                </div>

                <div class="bg-gray-50 px-2 py-0.5 rounded border text-center">
                    <p style="font-size: 11px !important;" class="text-gray-500 leading-none">Total écritures</p>
                    <p style="font-size: 11px !important;" class="font-bold text-[10px] leading-none">
                        {{ number_format($recapStats['total_ecritures'], 0, ',', ' ') }}
                    </p>
                </div>
    
                <!-- Période -->
                <div class="min-w-[160px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Période</label>
                    <div class="flex gap-1">
                        <input style="font-size: 11px !important;" 
                               type="date" 
                               wire:model.live="dateDebut" 
                               class="px-1 py-0.5 border rounded w-full">
                        <input style="font-size: 11px !important;" 
                               type="date" 
                               wire:model.live="dateFin" 
                               class="px-1 py-0.5 border rounded w-full">
                    </div>
                </div>
    
                <!-- Journal -->
                <div class="min-w-[90px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Journal</label>
                    <select style="font-size: 11px !important;" 
                            wire:model.live="journalCode" 
                            class="w-full px-1 py-0.5 border rounded">
                        <option value="">Tous</option>
                        @foreach($journaux as $j)
                            <option value="{{ $j['code'] }}">{{ $j['code'] }}</option>
                        @endforeach
                    </select>
                </div>
    
                <!-- Recherche -->
                <div class="min-w-[180px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Recherche</label>
                    <input style="font-size: 11px !important;" 
                           type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Compte, libellé…"
                           class="w-full px-2 py-0.5 border rounded">
                </div>
            </div>
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
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">
                    Journal: {{ $journalCode }}
                    <button wire:click="$set('journalCode','')" class="ml-1 text-green-600"><i class="fas fa-times"></i></button>
                </span>
            @endif

            @if($search)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-100 text-purple-800 text-xs">
                    Recherche: {{ Str::limit($search, 20) }}
                    <button wire:click="$set('search','')" class="ml-1 text-purple-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
        </div>
    </div>
    
    <!-- Tableau Récapitulatif -->
    <div class="bg-white rounded-lg shadow border overflow-hidden flex flex-col h-full">
        <div class="table-scroll-container">
            <!-- Indicateur de chargement -->
            @if($isLoading)
            <div class="loading-indicator">
                <div class="flex items-center space-x-2">
                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600"></div>
                    <span class="text-sm text-gray-600">Chargement des comptes suivants...</span>
                </div>
            </div>
            @endif
            
            <!-- Compteur de résultats -->
            <div class="flex justify-between items-center mb-4 bg-gray-50 p-3 rounded-lg">
                <span class="text-sm text-gray-600" style="font-size: 11px;">
                    Affichage de <strong>{{ $loadedCount }}</strong> sur <strong>{{ $totalCount }}</strong> comptes SYCEBNL
                </span>
            </div>
            
            <table class="min-w-full border-collapse">
                <!-- En-tête -->
                <thead class="bg-gray-800 text-white sticky top-0 z-30">
                    <tr>
                        <th class="px-2 py-0.5 text-left text-xs font-bold" style="font-size: 11px;">Date</th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold" style="font-size: 11px;">N° de pièce</th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold" style="font-size: 11px;">Code journal</th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold" style="font-size: 11px;">N° de compte</th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold" style="font-size: 11px;">Compte SYCEBNL</th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold" style="font-size: 11px;">Libellé</th>
                        <th class="px-2 py-0.5 text-right text-xs font-bold whitespace-nowrap min-w-[120px]" style="font-size: 11px;">Montant débit</th>
                        <th class="px-2 py-0.5 text-right text-xs font-bold whitespace-nowrap min-w-[120px]" style="font-size: 11px;">Montant crédit</th>
                    </tr>
                </thead>
                
                <tbody>
                    @forelse($recapData as $groupIndex => $group)
                        @include('livewire.grand-livre.partials.general.general-account-group', [
                            'group' => $group,
                            'groupIndex' => $groupIndex,
                            'dateFin' => $dateFin
                        ])
                    @empty
                        <!-- Aucune donnée -->
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-layer-group text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg" style="font-size: 12px;">Aucune écriture regroupée trouvée</p>
                                    @if($search || $dateDebut)
                                        <p class="text-sm text-gray-400 mt-2" style="font-size: 10px;">Essayez de modifier vos filtres</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                <!-- Totaux généraux en bas -->
                @if(count($recapData) > 0)
                <tfoot class="bg-blue-100 border-t-4 border-blue-300 font-bold sticky bottom-0">
                    <tr>
                        <td colspan="5" class="px-2 py-0.5 text-right text-xs text-blue-800" style="font-size: 11px;">
                            <i class="fas fa-chart-line mr-1"></i> TOTAUX GÉNÉRAUX
                        </td>
                        <td class="px-2 py-0.5 text-xs text-blue-800" style="font-size: 11px;">
                            {{ $recapStats['total_ecritures'] }} écritures
                        </td>
                        <td class="px-2 py-0.5 text-right text-xs text-red-800" style="font-size: 11px;">
                            {{ number_format($recapStats['total_debit'], 0, '', ' ') }}
                        </td>
                        <td class="px-2 py-0.5 text-right text-xs text-green-800" style="font-size: 11px;">
                            {{ number_format($recapStats['total_credit'], 0, '', ' ') }}
                        </td>
                    </tr>
                    <tr class="bg-blue-200">
                        <td colspan="6" class="px-2 py-0.5 text-right text-xs font-extrabold text-blue-900" style="font-size: 11px;">
                            <i class="fas fa-equals mr-1"></i> SOLDE GLOBAL
                        </td>
                        <td colspan="2" class="px-2 py-0.5 text-center text-xs font-extrabold text-blue-900" style="font-size: 11px;">
                            @php
                                $soldeGlobal = $recapStats['solde_global'];
                                $soldeGlobalText = number_format(abs($soldeGlobal), 0, '', ' ') . ' (' . ($soldeGlobal > 0 ? 'Débit' : 'Crédit') . ')';
                            @endphp
                            {{ $soldeGlobalText }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
            
            <!-- Sentinel pour infinite scroll -->
            <div id="sentinel-general" class="h-10 flex items-center justify-center">
                @if($hasMore && $totalCount > 0)
                    <div class="text-center py-4">
                        <div class="inline-flex items-center space-x-2 text-gray-600">
                            <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600"></div>
                            <span class="text-sm">Chargement des comptes suivants...</span>
                        </div>
                    </div>
                @elseif($totalCount > 0)
                    <div class="text-center py-4 text-gray-500 text-sm">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Tous les comptes sont chargés ({{ number_format($totalCount, 0, ',', ' ') }} au total)
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Messages flash -->
    @include('livewire.grand-livre.partials.flash-messages')
</div>