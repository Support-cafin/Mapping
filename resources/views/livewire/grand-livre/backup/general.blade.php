<div class="p-0 mt-12 bg-gray-50 flex flex-col h-screen"
     x-data="{
        isLoading: @entangle('isLoading'),
        hasMore: @entangle('hasMore'),
        exporting: @entangle('exporting'),
        showAccountSelector: false,
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
            background-color: #f8f9fa;
        }
        
        .table-row {
            font-size: 9px !important;
            line-height: 1.1;
        }
        
        .table-row td {
            font-size: 9px !important;
            padding: 3px 5px !important;
            color: #212529;
        }
        
        .account-header {
            background-color: #e9ecef !important;
            font-weight: 600;
            border-top: 2px solid #adb5bd;
            border-bottom: 1px solid #adb5bd;
        }
        
        .account-header td {
            font-size: 10px !important;
            padding: 4px 6px !important;
            color: #212529;
        }
        
        .amount-column {
            min-width: 90px !important;
            text-align: right;
            font-family: 'SFMono-Regular', 'Menlo', 'Monaco', 'Consolas', monospace;
            font-weight: 500;
            font-size: 9px !important;
            color: #212529;
        }
        
        .solde-row td {
            padding-top: 2px !important;
            padding-bottom: 2px !important;
            background-color: #f1f3f5 !important;
            font-size: 9px !important;
        }
        
        .stats-footer {
            position: sticky;
            bottom: 0;
            background: #e9ecef;
            border-top: 2px solid #ced4da;
            z-index: 20;
            height: 38px;
            font-size: 9px;
        }
        
        .stat-item {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 8px;
            border-right: 1px solid #ced4da;
        }
        
        .stat-item:last-child {
            border-right: none;
        }
        
        .stat-label {
            font-size: 8px;
            color: #495057;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1px;
        }
        
        .stat-value {
            font-size: 10px;
            font-weight: 700;
            line-height: 1.1;
            color: #212529;
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #e9ecef;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #adb5bd;
            border-radius: 3px;
        }
        
        .filter-card {
            background-color: #f1f3f5;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 6px;
            margin-bottom: 4px;
        }
        
        .btn-filter {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #212529;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .btn-filter:hover {
            background-color: #dee2e6;
        }
        
        .btn-primary {
            background-color: #6c757d;
            border: 1px solid #495057;
            color: white;
            font-size: 9px;
            padding: 2px 6px;
            border-radius: 3px;
        }
        
        .btn-primary:hover {
            background-color: #5a6268;
        }
        
        .print-btn {
            background-color: #6c757d;
            border: 1px solid #495057;
            color: white;
            font-size: 10px;
            padding: 4px 10px;
            border-radius: 3px;
        }
        
        .print-btn:hover {
            background-color: #5a6268;
        }
        
        th {
            font-size: 9px !important;
            padding: 4px 6px !important;
            background-color: #495057 !important;
            color: #f8f9fa !important;
            font-weight: 600;
        }
        
        .total-row {
            background-color: #e9ecef !important;
            font-weight: 600;
        }
        
        .total-row td {
            font-size: 9px !important;
            padding: 4px 6px !important;
        }
        
        .solde-cell-debit {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }
        
        .solde-cell-credit {
            background-color: #d4edda;
            color: #155724;
            font-weight: bold;
        }
        
        .checkbox-column {
            width: 25px;
            text-align: center;
        }
        
        /* Modal de sélection des comptes */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        
        .modal-content {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 15px;
            border-radius: 5px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 1001;
        }
        
        .account-item {
            padding: 4px 6px;
            border-bottom: 1px solid #e9ecef;
            font-size: 9px;
        }
        
        .account-item:hover {
            background-color: #f8f9fa;
        }
        
        .account-checkbox {
            margin-right: 8px;
        }
    </style>
    
    <!-- Header très compact -->
    <div class="flex-shrink-0 px-3 py-1 bg-white border-b shadow-sm">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-3">
                <h1 class="font-medium text-gray-800 text-sm">Grand Livre Général</h1>
                <span class="text-gray-500 text-[9px] bg-gray-100 px-2 py-0.5 rounded">
                    {{ $entreprise->code }}
                </span>
            </div>
            
            <div class="flex items-center space-x-2">
                <a href="{{ route('grand-livre.detail') }}"
                   class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-[10px] hover:bg-purple-200 border border-purple-200">
                    <i class="fas fa-list mr-1"></i>Détail
                </a>
                
                <!-- Bouton Imprimer (remplace Excel) -->
                <button @click="window.print()"
                        class="px-2 py-1 print-btn text-[10px]">
                    <i class="fas fa-print mr-1"></i>Imprimer
                </button>
                
                <!-- Bouton PDF -->
                <button wire:click="exportPdf" 
                        wire:loading.attr="disabled"
                        class="px-2 py-1 bg-red-600 text-white rounded text-[10px] hover:bg-red-700 border border-red-700">
                    <i class="fas fa-file-pdf mr-1"></i>PDF
                </button>
                
                <!-- Indicateur d'export -->
                <div wire:loading wire:target="exportPdf" 
                     class="text-[9px] text-gray-600 bg-gray-100 px-2 py-1 rounded">
                    <i class="fas fa-spinner fa-spin mr-1"></i>Génération...
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres très compacts - Gris clair -->
    <div class="filter-card mx-3 mt-1">
        <div class="grid grid-cols-4 gap-2">
            <div>
                <label class="text-[8px] text-gray-600 block mb-0.5 font-medium">Date début</label>
                <input type="date" wire:model.live="dateDebut" 
                       class="w-full px-1 py-0.5 border border-gray-300 rounded text-[9px] bg-white">
            </div>
            
            <div>
                <label class="text-[8px] text-gray-600 block mb-0.5 font-medium">Date fin</label>
                <input type="date" wire:model.live="dateFin" 
                       class="w-full px-1 py-0.5 border border-gray-300 rounded text-[9px] bg-white">
            </div>
            
            <div>
                <label class="text-[8px] text-gray-600 block mb-0.5 font-medium">Exercice</label>
                <input type="number" wire:model.live="exercice" 
                       placeholder="Année"
                       class="w-full px-1 py-0.5 border border-gray-300 rounded text-[9px] bg-white">
            </div>
            
            <div class="flex items-end">
                <button wire:click="resetFilters"
                        class="px-2 py-0.5 bg-gray-300 text-gray-700 rounded text-[9px] hover:bg-gray-400 border border-gray-400">
                    <i class="fas fa-redo mr-1"></i>Reset
                </button>
            </div>
        </div>
        
        <!-- Sélecteur de comptes -->
        <div class="mt-2 pt-1 border-t border-gray-300">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <span class="text-[9px] font-medium text-gray-700">Comptes sélectionnés :</span>
                    <span class="text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded">
                        {{ count($selectedAccounts) }}
                    </span>
                </div>
                
                <button @click="showAccountSelector = true"
                        class="px-2 py-0.5 bg-gray-200 text-gray-700 rounded text-[9px] hover:bg-gray-300">
                    <i class="fas fa-list mr-1"></i>Choisir les comptes
                </button>
                
                @if(!empty($selectedAccounts))
                <button wire:click="$set('selectedAccounts', [])"
                        class="text-[8px] text-gray-500 hover:text-gray-700 underline">
                    Effacer tout
                </button>
                @endif
            </div>
            
            <!-- Aperçu des comptes sélectionnés -->
            @if(!empty($selectedAccounts) && count($selectedAccounts) <= 5)
            <div class="mt-1 flex flex-wrap gap-1">
                @foreach($selectedAccounts as $code)
                <span class="inline-flex items-center px-1.5 py-0.5 bg-gray-100 text-gray-700 rounded text-[8px]">
                    {{ $code }}
                    <button wire:click="selectedAccounts = {{ json_encode(array_diff($selectedAccounts, [$code])) }}" 
                            class="ml-1 text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
                @endforeach
            </div>
            @elseif(count($selectedAccounts) > 5)
            <div class="mt-1 text-[8px] text-gray-500">
                {{ count($selectedAccounts) }} comptes sélectionnés
            </div>
            @endif
        </div>
    </div>
    
    <!-- Modal de sélection des comptes -->
    <div x-show="showAccountSelector" 
         x-cloak
         @keydown.escape.window="showAccountSelector = false">
        <div class="modal-overlay" @click="showAccountSelector = false"></div>
        <div class="modal-content">
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-medium text-gray-800 text-[11px]">Sélectionner les comptes</h3>
                <button @click="showAccountSelector = false" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="mb-2 flex items-center space-x-2">
                <button wire:click="$set('selectedAccounts', @js($accountsList->pluck('code')->toArray()))"
                        @click="showAccountSelector = false"
                        class="px-2 py-0.5 bg-blue-600 text-white rounded text-[9px] hover:bg-blue-700">
                    Sélectionner tout
                </button>
                <button wire:click="$set('selectedAccounts', [])"
                        @click="showAccountSelector = false"
                        class="px-2 py-0.5 bg-gray-200 text-gray-700 rounded text-[9px] hover:bg-gray-300">
                    Désélectionner tout
                </button>
            </div>
            
            <div class="max-h-96 overflow-y-auto border border-gray-200 rounded">
                @foreach($accountsList as $account)
                <div class="account-item flex items-center">
                    <input type="checkbox" 
                           wire:model.live="selectedAccounts"
                           value="{{ $account->code }}"
                           id="account_{{ $loop->index }}"
                           class="account-checkbox h-3 w-3 rounded border-gray-400">
                    <label for="account_{{ $loop->index }}" class="flex-1 cursor-pointer text-[9px]">
                        <span class="font-medium">{{ $account->code }}</span>
                        <span class="text-gray-600 ml-2">{{ Str::limit($account->intitule, 40) }}</span>
                        <span class="text-gray-400 ml-1">({{ $account->total_ecritures }} écrit.)</span>
                    </label>
                </div>
                @endforeach
            </div>
            
            <div class="mt-3 text-right">
                <button @click="showAccountSelector = false"
                        class="px-3 py-1 bg-gray-200 text-gray-700 rounded text-[9px] hover:bg-gray-300">
                    Fermer
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tableau principal -->
    <div class="flex-1 min-h-0 bg-white mx-3 mb-1 border border-gray-300 rounded">
        <div class="table-container scrollbar-thin">
            @if($totalCount === 0)
            <div class="flex flex-col items-center justify-center h-full">
                <i class="fas fa-layer-group text-3xl text-gray-400 mb-2"></i>
                <p class="text-sm text-gray-500">Aucune écriture trouvée</p>
                <p class="text-gray-400 text-xs mt-1">Modifiez vos critères de recherche</p>
            </div>
            @else
            <table class="min-w-full border-collapse">
                <thead class="sticky top-0">
                    <tr>
                        <th class="px-1 py-0.5 text-center text-[9px] font-medium w-20">Date</th>
                        <th class="px-1 py-0.5 text-left text-[9px] font-medium w-14">Pièce</th>
                        <th class="px-1 py-0.5 text-left text-[9px] font-medium w-16">Journal</th>
                        <th class="px-1 py-0.5 text-left text-[9px] font-medium w-20">Compte</th>
                        <th class="px-1 py-0.5 text-left text-[9px] font-medium">Libellé</th>
                        <th class="px-1 py-0.5 text-right text-[9px] font-medium w-28">Débit</th>
                        <th class="px-1 py-0.5 text-right text-[9px] font-medium w-28">Crédit</th>
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($recapData as $group)
                        <!-- En-tête compte -->
                        <tr class="account-header">
                            <td colspan="7" class="px-2 py-0.5">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-bold text-gray-800 text-[10px]">
                                            {{ $group['new_account_code'] }}
                                        </span>
                                        <span class="text-gray-700 text-[9px] ml-2">
                                            {{ Str::limit($group['new_account_intitule'], 50) }}
                                        </span>
                                    </div>
                                    <span class="text-gray-600 text-[8px] bg-gray-200 px-1.5 py-0.5 rounded">
                                        {{ $group['nombre_ecritures'] }} écrit.
                                    </span>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Écritures -->
                        @foreach($group['ecritures'] as $ecriture)
                        <tr class="table-row hover:bg-gray-100 border-b border-gray-200">
                            <td class="px-1 py-0.5 text-gray-800 text-[9px]">
                                {{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '' }}
                            </td>
                            <td class="px-1 py-0.5 text-gray-800 text-[9px]">
                                {{ $ecriture->piece ?? '' }}
                            </td>
                            <td class="px-1 py-0.5 text-gray-800 text-[9px]">
                                {{ $ecriture->journal_code ?? '' }}
                            </td>
                            <td class="px-1 py-0.5 text-gray-800 text-[9px]">
                                {{ $ecriture->old_account_code ?? '' }}
                            </td>
                            <td class="px-1 py-0.5 text-gray-800 text-[9px]">
                                {{ Str::limit($ecriture->libelle, 45) }}
                            </td>
                            <td class="amount-column font-medium text-gray-800 text-[9px]">
                                @if($ecriture->debit > 0)
                                   {{ number_format($ecriture->debit, 0, ',', ' ') }}
                                @endif
                            </td>
                            <td class="amount-column font-medium text-gray-800 text-[9px]">
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
                        <tr class="total-row border-b border-gray-300">
                            <td colspan="4" class="px-1 py-0.5 text-right font-semibold text-gray-800 text-[9px]">
                                TOTAL {{ $group['new_account_code'] }}
                            </td>
                            <td class="px-1 py-0.5 text-gray-700 text-[8px]">
                                {{ $group['nombre_ecritures'] }} écrit.
                            </td>
                            <td class="amount-column text-gray-800 font-bold text-[9px]">
                                {{ number_format($group['total_debit'], 0, ',', ' ') }}
                            </td>
                            <td class="amount-column text-gray-800 font-bold text-[9px]">
                                {{ number_format($group['total_credit'], 0, ',', ' ') }}
                            </td>
                        </tr>
                        
                        <!-- Solde du compte -->
                        <tr class="solde-row border-b border-gray-300">
                            <td colspan="5" class="px-1 py-0.5 text-right font-medium text-gray-800 text-[9px]">
                                SOLDE {{ $group['new_account_code'] }}
                            </td>
                            @if($isDebiteur)
                                <td class="amount-column solde-cell-debit font-bold text-[9px]">
                                    {{ number_format(abs($soldeCompte), 0, ',', ' ') }}
                                </td>
                                <td class="amount-column"></td>
                            @else
                                <td class="amount-column"></td>
                                <td class="amount-column solde-cell-credit font-bold text-[9px]">
                                    {{ number_format(abs($soldeCompte), 0, ',', ' ') }}
                                </td>
                            @endif
                        </tr>
                        
                        <!-- Ligne vide -->
                        <tr><td colspan="7" style="height: 3px;"></td></tr>
                    @endforeach
                </tbody>
                
                <!-- Sentinel pour infinite scroll -->
                <tfoot>
                    <tr id="sentinel-general">
                        <td colspan="7" class="px-1 py-1 text-center bg-gray-50">
                            @if($hasMore && $totalCount > 0)
                            <div class="inline-flex items-center space-x-2 text-gray-600">
                                <div class="animate-spin rounded-full h-2 w-2 border-b-2 border-blue-600"></div>
                                <span class="text-[9px]">Chargement...</span>
                            </div>
                            @elseif($totalCount > 0)
                            <div class="text-[9px] text-gray-500">
                                <i class="fas fa-check-circle text-gray-500 mr-1"></i>
                                Tous les comptes chargés
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
        <div class="stats-footer flex items-center justify-around px-1">
            <div class="stat-item">
                <div class="stat-label">Comptes</div>
                <div class="stat-value">{{ $recapStats['total_comptes'] }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Écritures</div>
                <div class="stat-value">{{ number_format($recapStats['total_ecritures'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Débit</div>
                <div class="stat-value">{{ number_format($recapStats['total_debit'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Crédit</div>
                <div class="stat-value">{{ number_format($recapStats['total_credit'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-label">Solde</div>
                <div class="flex items-center space-x-1">
                    <div class="stat-value">{{ number_format($recapStats['solde_global_absolu'], 0, ',', ' ') }}</div>
                    <div class="text-[7px] font-medium {{ $recapStats['is_debiteur'] ? 'text-red-600' : 'text-green-600' }}">
                        ({{ $recapStats['is_debiteur'] ? 'D' : 'C' }})
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
    <style media="print">
    @page {
        size: landscape;
        margin: 0.8cm;
    }
    
    body {
        background: white;
        font-size: 8pt;
    }
    
    .btn-filter, .btn-primary, .print-btn, a, button, .modal-overlay, .modal-content {
        display: none !important;
    }
    
    .filter-card, .stats-footer {
        background: #f0f0f0 !important;
        border: 1px solid #ccc !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    th {
        background-color: #333 !important;
        color: white !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    .account-header {
        background-color: #e0e0e0 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    .solde-cell-debit {
        background-color: #ffe6e6 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    .solde-cell-credit {
        background-color: #e6ffe6 !important;
        print-color-adjust: exact;
        -webkit-print-color-adjust: exact;
    }
    
    .table-container {
        height: auto !important;
        overflow: visible !important;
    }
</style>
    <!-- Script pour l'impression -->
    @push('scripts')
    <script>
        window.onbeforeprint = function() {
            // Optionnel : Ajouter des classes spéciales pour l'impression
            document.body.classList.add('printing');
        };
        
        window.onafterprint = function() {
            document.body.classList.remove('printing');
        };
    </script>
    @endpush
</div>