<div class="p-0 mt-12 bg-gray-50 flex flex-col h-screen"
     x-data="{
        isLoading: @entangle('isLoading'),
        hasMore: @entangle('hasMore'),
        exporting: @entangle('exporting'),
        showExportOptions: false,
        init() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting && this.hasMore && !this.isLoading) {
                        @this.call('loadMore');
                    }
                });
            }, {
                root: null,
                rootMargin: '100px',
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
    
    
    <style>
        .table-container {
            height: calc(100vh - 140px);
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .compact-filters {
            max-height: 70px;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .compact-filters.expanded {
            max-height: 120px;
        }
        
        .table-row {
            font-size: 11px;
            line-height: 1.1;
        }
        
        .account-header {
            background-color: #f1f5f9;
            font-weight: 600;
        }
        
        .amount-column {
            min-width: 130px !important;
            text-align: right;
            font-family: 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', monospace;
            font-weight: 500;
            font-size : 13px;
            color : black;
        }
        
        .solde-row td {
            padding-top: 3px !important;
            padding-bottom: 3px !important;
        }
        
        .stats-footer {
            position: sticky;
            bottom: 0;
            background: #ffffff;
            border-top: 2px solid #e5e7eb;
            z-index: 20;
            height: 42px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 10px;
            border-right: 1px solid #f1f5f9;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-label {
            font-size: 9px;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1px;
        }
        
        .stat-value {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.1;
        }
        
        .debit-stat {
            color: #dc2626;
        }
        
        .credit-stat {
            color: #059669;
        }
        
        .solde-stat-debit {
            color: black;
            background-color: white;
            padding: 0 6px;
            border-radius: 3px;
        }
        
        .solde-stat-credit {
            color: black;
            background-color: white;
            padding: 0 6px;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 3px;
        }
    </style>
    
    <!-- Header très compact -->
    <div class="flex-shrink-0 px-3 py-1 bg-white border-b shadow-sm">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <h1 class="font-medium text-gray-800 text-sm">Grand Livre Général</h1>
                <span class="text-gray-500 text-[10px] bg-gray-100 px-2 py-0.5 rounded">
                    {{ $entreprise->code }}
                </span>
            </div>
            
             <div class="flex items-center space-x-1">
                    <a href="{{ route('grand-livre.detail') }}"
                       class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-[11px] hover:bg-purple-200 border border-purple-200">
                        <i class="fas fa-list mr-1"></i>Grand livre
                    </a>
                    
                    <!-- Bouton Excel -->
                     <!--<button wire:click="exportSimple"
                            class="px-2 py-1 bg-green-100 text-black-700 rounded text-[11px] hover:bg-green-200 border border-green-200"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed">
                        <span wire:loading.remove>
                            <i class="fas fa-file-excel mr-1"></i>Excel
                        </span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-1"></i>Génération...
                        </span>
                    </button>-->
                    
                    

                   {{-- <button wire:click="exportPdfFinal"
                            class="px-3 py-1.5 bg-red-600 text-white rounded text-[12px] hover:bg-red-700 border border-red-700 flex items-center"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed">
                        <span wire:loading.remove>
                            <i class="fas fa-file-pdf mr-2"></i>PDF (Version finale)
                        </span>
                        <span wire:loading>
                            <i class="fas fa-spinner fa-spin mr-2"></i>Génération...
                        </span> 
                    </button> --}}
                    
                    {{-- Solution la plus fiable --}}
                   {{-- <button onclick="exportPdfImproved(this)" 
                            data-url="{{ route('grand-livre-general.pdf.export', $entreprise->id) }}?dateDebut={{ $dateDebut }}&dateFin={{ $dateFin }}&journalCode={{ $journalCode }}&search={{ $search }}"
                            class="px-3 py-1.5 bg-red-600 text-white rounded text-[12px] hover:bg-red-700 border border-red-700 flex items-center pdf-export-btn">
                        <i class="fas fa-file-pdf mr-2"></i>
                        <span class="pdf-text">PDF</span>
                        <span class="pdf-loading hidden ml-2">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                    </button> --}}
                    
                    <style>
                        .hidden { display: none !important; }
                        .pdf-export-btn:disabled {
                            opacity: 0.6;
                            cursor: not-allowed;
                        }
                     </style>

                </div>
        </div>
    </div>
    
    <!-- Filtres très compacts -->
    <div class="bg-white border-b px-3 py-1" x-data="{ filtersExpanded: false }">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <button @click="filtersExpanded = !filtersExpanded"
                        class="px-2 py-0.5 bg-gray-50 rounded text-[11px] hover:bg-gray-100 border">
                    <i class="fas fa-filter mr-1 text-gray-600"></i>
                    <span class="text-gray-700" x-text="filtersExpanded ? 'Cacher' : 'Filtres'"></span>
                </button>
                
                <button wire:click="resetFilters"
                        class="px-2 py-0.5 bg-gray-50 rounded text-[11px] hover:bg-gray-100 border">
                    <i class="fas fa-redo mr-1 text-gray-600"></i>
                    <span class="text-gray-700">Reset</span>
                </button>
                
                @if($search || $journalCode)
                <div class="flex items-center space-x-1">
                    @if($search)
                    <span class="inline-flex items-center px-1.5 py-0.5 bg-amber-50 text-amber-700 rounded text-[10px] border border-amber-200">
                        {{ Str::limit($search, 8) }}
                        <button wire:click="$set('search', '')" class="ml-1 text-amber-600 hover:text-amber-800">
                            <i class="fas fa-times text-[9px]"></i>
                        </button>
                    </span>
                    @endif
                    @if($journalCode)
                    <span class="inline-flex items-center px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded text-[10px] border border-emerald-200">
                        J:{{ $journalCode }}
                        <button wire:click="$set('journalCode', '')" class="ml-1 text-emerald-600 hover:text-emerald-800">
                            <i class="fas fa-times text-[9px]"></i>
                        </button>
                    </span>
                    @endif
                </div>
                @endif
            </div>
            
            <div class="text-[11px] text-gray-600">
                <span class="font-semibold">{{ $loadedCount }}</span> / 
                <span class="font-medium text-blue-600">{{ $totalCount }}</span> comptes
            </div>
        </div>
        
        <!-- Filtres dépliables -->
        <div class="mt-1 pt-1 border-t border-gray-100" x-show="filtersExpanded" x-collapse>
            <div class="grid grid-cols-4 gap-2">
                <div>
                    <label class="text-[10px] text-gray-500 block mb-0.5">Période</label>
                    <div class="flex space-x-1">
                        <input type="date" wire:model.live="dateDebut" 
                               class="w-full px-1.5 py-0.5 border rounded text-[11px] bg-gray-50">
                        <input type="date" wire:model.live="dateFin" 
                               class="w-full px-1.5 py-0.5 border rounded text-[11px] bg-gray-50">
                    </div>
                </div>
                
                <div>
                    <label class="text-[10px] text-gray-500 block mb-0.5">Journal</label>
                    <select wire:model.live="journalCode" 
                            class="w-full px-1.5 py-0.5 border rounded text-[11px] bg-gray-50">
                        <option value="">Tous</option>
                        @foreach($journaux as $journal)
                        <option value="{{ $journal }}">{{ $journal }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="text-[10px] text-gray-500 block mb-0.5">Exercice</label>
                    <input type="number" wire:model.live="exercice" 
                           placeholder="Année"
                           class="w-full px-1.5 py-0.5 border rounded text-[11px] bg-gray-50">
                </div>
                
                <div>
                    <label class="text-[10px] text-gray-500 block mb-0.5">Recherche</label>
                    <div class="relative">
                        <input type="text" wire:model.live.debounce.300ms="search" 
                               placeholder="Compte, libellé..." 
                               class="w-full pl-7 pr-1.5 py-0.5 border rounded text-[11px] bg-gray-50">
                        <i class="fas fa-search absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400 text-[10px]"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Tableau principal -->
    <div class="flex-1 min-h-0 bg-white">
        <div class="table-container scrollbar-thin">
            @if($totalCount === 0)
            <div class="flex flex-col items-center justify-center h-full">
                <i class="fas fa-layer-group text-3xl text-gray-300 mb-2"></i>
                <p class="text-sm text-gray-500">Aucune écriture trouvée</p>
                <p class="text-gray-400 text-xs mt-1">Modifiez vos critères de recherche</p>
            </div>
            @else
            <table class="min-w-full border-collapse">
                <thead class="sticky top-0 bg-gray-800 text-white">
                    <tr>
                        <th class="px-2 py-1 text-left text-[11px] font-medium w-20">Date</th>
                        <th class="px-2 py-1 text-left text-[11px] font-medium w-16">Pièce</th>
                        <th class="px-2 py-1 text-left text-[11px] font-medium w-20">Journal</th>
                        <th class="px-2 py-1 text-left text-[11px] font-medium w-24">Compte</th>
                        <th class="px-2 py-1 text-left text-[11px] font-medium w-32">Compte SYCEBNL</th>
                        <th class="px-2 py-1 text-left text-[11px] font-medium">Libellé</th>
                        <th class="px-2 py-1 text-right text-[11px] font-medium w-36">Débit</th>
                        <th class="px-2 py-1 text-right text-[11px] font-medium w-36">Crédit</th>
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($recapData as $group)
                        <!-- En-tête compte -->
                        <tr class="account-header border-t border-gray-300">
                            <td colspan="8" class="px-2 py-1">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center">
                                        <span class="font-medium text-gray-800 text-[12px]">
                                            {{ $group['new_account_code'] }}
                                        </span>
                                        <span class="text-gray-600 text-[11px] ml-2">
                                            {{ Str::limit($group['new_account_intitule'], 35) }}
                                        </span>
                                    </div>
                                    <span class="text-gray-500 text-[10px] bg-gray-100 px-1.5 py-0.5 rounded">
                                        {{ $group['nombre_ecritures'] }} écritures
                                    </span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Écritures -->
                        @foreach($group['ecritures'] as $ecriture)
                        <tr class="table-row hover:bg-gray-50 border-b border-gray-100">
                            <td class="px-2 py-0.5 font-medium text-gray-800" style="font-size: 13px;">
                                {{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '' }}
                            </td>
                            <td class="px-2 py-0.5 font-medium text-gray-800" style="font-size: 13px;">
                                {{ $ecriture->piece ?? '' }}
                            </td>
                            <td class="px-2 py-0.5 font-medium text-gray-800" style="font-size: 13px;">
                                {{ $ecriture->journal_code ?? '' }}
                            </td>
                            <td class="px-2 py-0.5 font-medium text-gray-800" style="font-size: 13px;">
                                {{ $ecriture->old_account_code ?? '' }}
                            </td>
                            <td class="px-2 py-0.5 font-medium text-gray-800" style="font-size: 13px;">
                                {{ $group['new_account_code'] }}
                            </td>
                            <td class="px-2 py-0.5 font-medium text-gray-800" style="font-size: 13px;">
                                {{ Str::limit($ecriture->libelle, 45) }}
                            </td>
                            <td class="amount-column font-medium text-gray-800" style="font-size: 14px;">
                                @if($ecriture->debit > 0)
                                   {{ number_format($ecriture->debit, 0, ',', ' ') }}
                                @endif
                            </td>
                            <td class="amount-column font-medium text-gray-800" style="font-size: 14px;">
                                @if($ecriture->credit > 0)
                                    {{ number_format($ecriture->credit, 0, ',', ' ') }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        
                        <!-- Totaux du compte -->
                        @php
                            $soldeCompte = $group['total_debit'] - $group['total_credit'];
                            $isDebiteur = $soldeCompte > 0;
                        @endphp
                        <tr class="bg-gray-50 border-b border-gray-300">
                            <td colspan="5" class="px-2 py-0.5 text-right font-semibold text-gray-800 text-[10px]">
                                TOTAL {{ $group['new_account_code'] }}
                            </td>
                            <td class="px-2 py-0.5 text-gray-600 text-[10px] font-medium">
                                {{ $group['nombre_ecritures'] }} écritures
                            </td>
                            <td class="amount-column text-black-700 font-medium">
                                <b>{{ number_format($group['total_debit'], 0, ',', ' ') }}</b>
                            </td>
                            <td class="amount-column text-black-700 font-medium">
                                <b>{{ number_format($group['total_credit'], 0, ',', ' ') }}</b>
                            </td>
                        </tr>
                        
                        <!-- Solde du compte -->
                        <tr class="solde-row bg-blue-50 border-b-2 border-blue-300">
                            <td colspan="6" class="px-2 py-0.5 text-right font-medium text-blue-800 text-[11px]">
                                SOLDE {{ $group['new_account_code'] }}
                            </td>
                            @if($isDebiteur)
                                <td class="amount-column text-black-700 font-medium border-b-2 border-red-400">
                                    <b>{{ number_format(abs($soldeCompte), 0, ',', ' ') }}</b>
                                </td>
                                <td class="amount-column"></td>
                            @else
                                <td class="amount-column"></td>
                                <td class="amount-column text-black-700 font-medium border-b-2 border-green-400">
                                    <b>{{ number_format(abs($soldeCompte), 0, ',', ' ') }}</b>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
                
                <!-- Sentinel pour infinite scroll -->
                <tfoot>
                    <tr id="sentinel-general">
                        <td colspan="8" class="px-2 py-2 text-center bg-gray-50">
                            @if($hasMore && $totalCount > 0)
                            <div class="inline-flex items-center space-x-2 text-gray-600">
                                <div class="animate-spin rounded-full h-3 w-3 border-b-2 border-blue-600"></div>
                                <span class="text-[11px]">Chargement des comptes suivants...</span>
                            </div>
                            @elseif($totalCount > 0)
                            <div class="text-[11px] text-gray-500">
                                <i class="fas fa-check-circle text-black-500 mr-1"></i>
                                Tous les comptes sont chargés
                            </div>
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>
            @endif
        </div>
        
        <!-- Statistiques horizontales avec fond clair -->
        @if($totalCount > 0)
        <div class="stats-footer flex items-center justify-between px-4">
            <!-- Partie gauche : Comptes et Écritures -->
            <div class="flex items-center space-x-6">
                <div class="stat-item">
                    <div class="stat-label">Comptes</div>
                    <div class="stat-value text-gray-900" >{{ $recapStats['total_comptes'] }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Écritures</div>
                    <div class="stat-value text-gray-900">{{ number_format($recapStats['total_ecritures'], 0, ',', ' ') }}</div>
                </div>
            </div>
            
            <!-- Partie centre : Totaux Débit/Crédit -->
            <div class="flex items-center space-x-6">
                <div class="stat-item">
                    <div class="stat-label">Total Débit</div>
                    <div class="stat-value text-gray-900">{{ number_format($recapStats['total_debit'], 0, ',', ' ') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Total Crédit</div>
                    <div class="stat-value text-gray-900">{{ number_format($recapStats['total_credit'], 0, ',', ' ') }}</div>
                </div>
            </div>
            
            <!-- Partie droite : Solde Global -->
            <div class="stat-item">
                <div class="stat-value text-gray-900">Solde Global</div>
                <div class="flex items-center space-x-2">
                    <div class="stat-value text-gray-900 {{ $recapStats['is_debiteur'] ? 'solde-stat-debit' : 'solde-stat-credit' }}">
                        {{ number_format($recapStats['solde_global_absolu'], 0, ',', ' ') }}
                    </div>
                    <div class="stat-value text-gray-900 {{ $recapStats['is_debiteur'] ? 'text-black-600' : 'text-black-600' }}">
                        ({{ $recapStats['is_debiteur'] ? 'Débit' : 'Crédit' }})
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Version alternative avec une seule ligne horizontale -->
        {{-- <div class="stats-footer flex items-center justify-around px-4">
            <div class="stat-item">
                <div class="stat-label">Comptes SYCEBNL</div>
                <div class="stat-value text-gray-900">{{ $recapStats['total_comptes'] }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Total Écritures</div>
                <div class="stat-value text-gray-900">{{ number_format($recapStats['total_ecritures'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Total Débit</div>
                <div class="stat-value debit-stat">{{ number_format($recapStats['total_debit'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Total Crédit</div>
                <div class="stat-value credit-stat">{{ number_format($recapStats['total_credit'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Solde Global</div>
                <div class="flex flex-col">
                    <div class="stat-value {{ $recapStats['is_debiteur'] ? 'solde-stat-debit' : 'solde-stat-credit' }}">
                        {{ number_format($recapStats['solde_global_absolu'], 0, ',', ' ') }}
                    </div>
                    <div class="text-[9px] font-medium {{ $recapStats['is_debiteur'] ? 'text-black-600' : 'text-black-600' }} text-center">
                        {{ $recapStats['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT' }}
                    </div>
                </div>
            </div>
        </div> --}}
        @endif
    </div>
    @include('livewire.grand-livre.partials.general-scripts')
</div>