<div class="p-0 mt-12 bg-gray-50 flex flex-col"
     style="height: calc(100vh - 3rem); overflow: hidden;"
     x-data="{
        showImportModal: @entangle('showImportModal'),
        showFilters: false,
        selectedEcritures: [],
        exportFormat: 'excel'
     }">
    
    <style>
    .table-scroll-container {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        position: relative;
    }

    .table-header-sticky {
        position: sticky;
        top: 0;
        z-index: 30;
        background-color: #f9fafb;
    }

    .table-scroll-container::-webkit-scrollbar {
        width: 6px;
    }

    .table-scroll-container::-webkit-scrollbar-thumb {
        background-color: #cbd5e1;
        border-radius: 3px;
    }
</style>
    
    <!-- Header -->
    <div class="mb-4 flex-shrink-0">
        <div class="flex justify-between items-start">
            <h1 class="text-2xl font-bold text-gray-800" style="font-size: 12px;">
                Grand Livre Comptable : {{ $entreprise->nom }} ({{ $entreprise->code }}) : Exercice {{ $exercice }}
            </h1>
            
            <div class="flex space-x-1 mt-3 border-b">
                <button wire:click="switchTab('detail')"
                        class="px-4 py-2 text-sm font-medium rounded-t-lg transition-all {{ $activeTab === 'detail' ? 'bg-white border border-b-0 border-gray-300 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
                        style="font-size: 11px;">
                    <i class="fas fa-list mr-1"></i> GRAND LIVRE
                </button>
                
                <button wire:click="switchTab('recap')"
                        class="px-4 py-2 text-sm font-medium rounded-t-lg transition-all {{ $activeTab === 'recap' ? 'bg-white border border-b-0 border-gray-300 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
                        style="font-size: 11px;">
                    <i class="fas fa-layer-group mr-1"></i> GRAND LIVRE GENERAL
                </button>
            </div>
        </div>
            
        <!-- Boutons dans la section Header -->
        <div class="flex items-center space-x-3">
            <!-- Bouton Import - caché dans l'onglet "recap" -->
            @if($activeTab !== 'recap')
                <button style="font-size: 11px !important;" @click="showImportModal = true"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center" style="font-size: 12px;">
                    <i class="fas fa-file-import mr-2"></i>
                    Importer Excel
                </button>
            @endif
            
            <!-- Bouton Export -->
            <div class="relative" x-data="{ showExportOptions: false }">
                <button style="font-size: 11px !important;" 
                        @click="showExportOptions = !showExportOptions"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center" 
                        style="font-size: 12px;">
                    <i class="fas fa-file-export mr-2"></i>
                    Exporter {{ $activeTab === 'recap' ? 'Général' : 'Détail' }}
                </button>
                
                <!-- Menu déroulant Export -->
                <div x-show="showExportOptions" 
                     x-cloak
                     @click.outside="showExportOptions = false"
                     class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
                    <button style="font-size: 11px !important;" 
                            wire:click="export('excel')"
                            class="block w-full text-left px-4 py-3 hover:bg-gray-50 text-gray-700 border-b" 
                            style="font-size: 12px;">
                        <i class="fas fa-file-excel text-green-500 mr-2"></i>
                        Excel (.xlsx) - {{ $activeTab === 'recap' ? 'Général' : 'Détail' }}
                    </button>
                </div>
            </div>
        
            <!-- Bouton Ajouter - caché dans l'onglet "recap" -->
            @if($activeTab !== 'recap')
                <button style="font-size: 11px !important;" wire:click="$dispatch('open-add-modal')"
                        class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center" style="font-size: 12px;">
                    <i class="fas fa-plus mr-2"></i>
                    Ajouter
                </button>
            @endif
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white p-1 rounded-lg shadow border mb-4 flex-shrink-0" x-data="{ showFilters: true }">
    
        <!-- Header -->
        <div class="flex items-center justify-between mt-1">
            <h3 style="font-size: 11px !important;" class="font-medium text-gray-700 flex items-center">
                <i class="fas fa-filter mr-1"></i> Filtres
            </h3>
    
            <div class="flex space-x-2">
                <button style="font-size: 11px !important;" @click="showFilters = !showFilters"
                    class="text-blue-600 hover:text-blue-700">
                    <span x-text="showFilters ? 'Masquer' : 'Afficher'"></span>
                </button>
    
                <button style="font-size: 11px !important;" wire:click="resetFilters"
                    class="text-gray-600 hover:text-gray-700">
                    <i class="fas fa-redo mr-1"></i> Reset
                </button>
            </div>
        </div>
    
        <!-- Filtres -->
        <div x-show="showFilters" class="overflow-x-auto whitespace-nowrap py-0.5 -mx-2 px-2">
            <div class="inline-flex items-end gap-1 flex-nowrap">
    
                <!-- Stats -->
                <div class="bg-gray-50 px-2 py-0.5 rounded border text-center">
                    <p style="font-size: 11px !important;" class="text-gray-500 leading-none">Total écritures</p>
                    <p style="font-size: 11px !important;" class="font-bold text-[10px] leading-none">
                        {{ number_format($stats['total'], 0, ',', ' ') }}
                    </p>
                </div>
    
                <!-- Période -->
                <div class="min-w-[160px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Période</label>
                    <div class="flex gap-1">
                        <input style="font-size: 11px !important;" type="date" wire:model.live="dateDebut" class="px-1 py-0.5 border rounded w-full">
                        <input style="font-size: 11px !important;" type="date" wire:model.live="dateFin" class="px-1 py-0.5 border rounded w-full">
                    </div>
                </div>
                
                <div class="min-w-[110px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Source</label>
                    <select style="font-size: 11px !important;" wire:model.live="sourceFilter" class="w-full px-1 py-0.5 border rounded">
                        <option value="all">Toutes</option>
                        <option value="manuel">Manuelles</option>
                        <option value="import">Importées</option>
                    </select>
                </div>
    
                <!-- Journal -->
                <div class="min-w-[90px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Journal</label>
                    <select style="font-size: 11px !important;" wire:model.live="journalCode" class="w-full px-1 py-0.5 border rounded">
                        <option style="font-size: 11px !important;" value="">Tous</option>
                        @foreach($journaux as $j)
                            <option style="font-size: 11px !important;" value="{{ $j['code'] }}">{{ $j['code'] }}</option>
                        @endforeach
                    </select>
                </div>
    
                <!-- Type compte -->
                <div class="min-w-[110px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Type compte</label>
                    <select style="font-size: 11px !important;" wire:model.live="accountType" class="w-full px-1 py-0.5 border rounded">
                        <option value="all">Tous</option>
                        <option value="old">Compte entité</option>
                        <option value="new">Compte SYCEBNL</option>
                    </select>
                </div>
    
                <!-- Compte -->
                @if($accountType !== 'all')
                <div class="min-w-[150px]" x-data="{ open: false }">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Compte</label>
                    <select style="font-size: 11px !important;" 
                            wire:model.live="accountId" 
                            class="w-full px-1 py-0.5 border rounded"
                            wire:loading.attr="disabled">
                        <option value="">Tous les comptes</option>
                        @foreach($accounts as $a)
                            <option value="{{ $a->id }}">
                                {{ $a->code }} - {{ Str::limit($a->intitule, 20) }}
                            </option>
                        @endforeach
                    </select>
                    @if($accounts->isEmpty() && $search)
                        <p class="text-xs text-gray-500 mt-1">Aucun compte trouvé pour "{{ $search }}"</p>
                    @endif
                </div>
                @endif
    
                <!-- Recherche -->
                <div class="min-w-[180px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Recherche</label>
                    <input style="font-size: 11px !important;" type="text" wire:model.live.debounce.300ms="search"
                           placeholder="Pièce, libellé, compte…"
                           class="w-full px-2 py-0.5 border rounded">
                </div>
    
            </div>
        </div>
    
        <!-- Résumé filtres -->
        <div class="mt-2 flex flex-wrap gap-1">
            @if($dateDebut && $dateFin)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-800">
                    {{ \Carbon\Carbon::parse($dateDebut)->format('d/m') }} → {{ \Carbon\Carbon::parse($dateFin)->format('d/m') }}
                    <button wire:click="$set('dateDebut','')" class="ml-1 text-blue-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
    
            @if($journalCode)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800">
                    Journal: {{ $journalCode }}
                    <button wire:click="$set('journalCode','')" class="ml-1 text-green-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
    
            @if($accountType !== 'all' && $accountId)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">
                    Compte: {{ $selectedAccount->code ?? '' }}
                    <button wire:click="$set('accountId','')" class="ml-1 text-purple-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
    
            @if($lettre)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-yellow-100 text-yellow-800">
                    Lettre: {{ $lettre === 'non' ? 'Non lettré' : $lettre }}
                    <button wire:click="$set('lettre','')" class="ml-1 text-yellow-600"><i class="fas fa-times"></i></button>
                </span>
            @endif
            
            @php
                use App\Models\OldAccount; 
                use App\Models\NewAccount; 
            @endphp
            
            <!-- Dans la section "Résumé filtres" -->
            @if($accountType !== 'all' && $accountId)
                @php
                    $selectedAccount = $accountType === 'old' 
                        ? OldAccount::find($accountId) 
                        : NewAccount::find($accountId);
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-purple-100 text-purple-800">
                    {{ $accountType === 'old' ? 'Entité' : 'SYCEBNL' }}: {{ $selectedAccount->code ?? '' }}
                    <button wire:click="$set('accountId','')" class="ml-1 text-purple-600">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            @endif
        </div>
    </div>
    
    <!-- Tableau des écritures -->
    @if($activeTab === 'detail')
    <div class="bg-white rounded-lg shadow border overflow-hidden flex flex-col h-full">
        <div class="table-scroll-container">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="table-header-sticky">
                    <tr>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-24" style="font-size: 10px;">
                            Date
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-16" style="font-size: 10px;">
                            Journal
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-20" style="font-size: 10px;">
                            Pièce
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32" style="font-size: 10px;">
                            Compte Entité
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-64" style="font-size: 10px;">
                            Libellé
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-28" style="font-size: 10px;">
                            Montant Débit
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-28" style="font-size: 10px;">
                            Montant Crédit
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32" style="font-size: 10px;">
                            Compte SYCEBNL
                        </th>
                        <th class="px-2 py-0.5 text-left text-xs font-bold text-gray-500 uppercase tracking-wider w-32" style="font-size: 10px;">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        // Séparer les écritures normales et manuelles
                        $ecrituresNormales = $ecritures->where('source', '!=', 'manuel');
                        $ecrituresManuelles = $ecritures->where('source', 'manuel');
                        $manualCount = $ecrituresManuelles->count();
                        $normalCount = $ecrituresNormales->count();
                    @endphp
                    
                    <!-- Écritures normales d'abord -->
                    @forelse($ecrituresNormales as $ecriture)
                        @php
                            $isDebit = $ecriture->debit > 0;
                            $isCredit = $ecriture->credit > 0;
                        @endphp
                        
                        <!-- Ligne d'écriture normale -->
                        <tr class="hover:bg-gray-50 transition-colors 
                                   {{ $isDebit ? 'bg-red-50/30' : 'bg-green-50/30' }}"
                            title="Écriture importée">
                            
                            <!-- Date -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-24">
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-900"style="font-size: 12px;">
                                        {{ $ecriture->date_ecriture->format('d/m/Y') }}
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Journal -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-16">
                                <div class="flex items-center">
                                    @if($ecriture->journal_code)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800"style="font-size: 12px;">
                                            {{ $ecriture->journal_code }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Pièce -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-20">
                                @if($ecriture->piece)
                                    <span class="font-mono text-sm"style="font-size: 12px;">{{ $ecriture->piece }}</span>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            
                            <!-- Compte Entité -->
                            <td class="px-2 py-0.5 w-32">
                                <div class="text-sm font-medium text-gray-900">
                                    @if($ecriture->oldAccount)
                                        <div class="flex items-center">
                                            <span class="font-mono text-xs bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded"style="font-size: 12px;">
                                                {{ $ecriture->oldAccount->code }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Libellé -->
                            <td class="px-2 py-0.5 w-64">
                                <div class="text-sm text-gray-900 flex items-center">
                                    <span class="truncate max-w-xs"style="font-size: 12px;">
                                        {{ Str::limit($ecriture->libelle ?? 'non renseigné', 25) }}
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Montant Débit -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-28">
                                @if($ecriture->debit > 0)
                                    <span class="text-red-600 font-medium text-sm"style="font-size: 12px;">
                                        {{ number_format($ecriture->debit, 0, '', ' ') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm"style="font-size: 12px;">00</span>
                                @endif
                            </td>
                            
                            <!-- Montant Crédit -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-28">
                                @if($ecriture->credit > 0)
                                    <span class="text-green-600 font-medium text-sm"style="font-size: 12px;">
                                        {{ number_format($ecriture->credit, 0, '', ' ') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm"style="font-size: 12px;">00</span>
                                @endif
                            </td>
                            
                            <!-- Compte SYCEBNL -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-32">
                                @if($ecriture->newAccount)
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded"style="font-size: 12px;">
                                            {{ $ecriture->newAccount->code }}
                                        </span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800"style="font-size: 12px;">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Non mappé
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-32">
                                <div class="flex items-center">
                                    <button wire:click="editEcriture({{ $ecriture->id }})"
                                            class="text-blue-600 hover:text-blue-800 text-sm"
                                            title="Modifier"style="font-size: 12px;">
                                        Modifier
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <!-- Si aucune écriture normale, mais il y a des manuelles -->
                        @if($manualCount == 0)
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-book text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg">Aucune écriture trouvée</p>
                                    @if($search || $dateDebut || $journalCode || $accountId)
                                        <button wire:click="resetFilters"
                                                class="mt-2 text-blue-600 hover:text-blue-700 text-sm">
                                            Réinitialiser les filtres
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endif
                    @endforelse
                    
                    <!-- Séparation avant les écritures manuelles -->
                    @if($manualCount > 0 && $normalCount > 0)
                    <tr class="bg-yellow-50 border-y-2 border-yellow-300">
                        <td colspan="9" class="px-4 py-2 text-center">
                            <div class="flex items-center justify-center text-yellow-700 font-medium text-sm">
                                <!--<i class="fas fa-pencil-alt mr-2"></i>-->
                                ÉCRITURES AJOUTÉES MANUELLEMENT ({{ $manualCount }})
                                <!--<i class="fas fa-pencil-alt ml-2"></i>-->
                            </div>
                        </td>
                    </tr>
                    @endif
                    
                    <!-- Écritures manuelles en bas -->
                    @forelse($ecrituresManuelles as $ecriture)
                        @php
                            $isDebit = $ecriture->debit > 0;
                            $isCredit = $ecriture->credit > 0;
                        @endphp
                        
                        <!-- Ligne d'écriture manuelle -->
                        <tr class="hover:bg-gray-50 transition-colors 
                                   {{ $isDebit ? 'bg-red-50/30' : 'bg-green-50/30' }}
                                   bg-yellow-100 manual-row"
                            title="Écriture ajoutée manuellement">
                            
                            <!-- Date avec indicateur manuel -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-24">
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-900" style="font-size: 12px;">
                                        {{ $ecriture->date_ecriture->format('d/m/Y') }}
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Journal avec indicateur manuel -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-16">
                                <div class="flex items-center">
                                    @if($ecriture->journal_code)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800" style="font-size: 12px;">
                                            {{ $ecriture->journal_code }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 text-xs">-</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Pièce -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-20">
                                @if($ecriture->piece)
                                    <span class="font-mono text-sm" style="font-size: 12px;">{{ $ecriture->piece }}</span>
                                @else
                                    <span class="text-gray-400 text-sm">-</span>
                                @endif
                            </td>
                            
                            <!-- Compte Entité -->
                            <td class="px-2 py-0.5 w-32">
                                <div class="text-sm font-medium text-gray-900">
                                    @if($ecriture->oldAccount)
                                        <div class="flex items-center">
                                            <span class="font-mono text-xs bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded" style="font-size: 12px;">
                                                {{ $ecriture->oldAccount->code }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Libellé -->
                            <td class="px-2 py-0.5 w-64">
                                <div class="text-sm text-gray-900 flex items-center">
                                    <span class="truncate max-w-xs" style="font-size: 12px;">
                                        {{ $ecriture->libelle ?? 'non renseigné'}}
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Montant Débit -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-28">
                                @if($ecriture->debit > 0)
                                    <span class="text-red-600 font-medium text-sm" style="font-size: 12px;">
                                        {{ number_format($ecriture->debit, 0, '', ' ') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm" style="font-size: 12px;">00</span>
                                @endif
                            </td>
                            
                            <!-- Montant Crédit -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-28">
                                @if($ecriture->credit > 0)
                                    <span class="text-green-600 font-medium text-sm" style="font-size: 12px;">
                                        {{ number_format($ecriture->credit, 0, '', ' ') }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-sm" style="font-size: 12px;">00</span>
                                @endif
                            </td>
                            
                            <!-- Compte SYCEBNL -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-32">
                                @if($ecriture->newAccount)
                                    <div class="flex items-center">
                                        <span class="font-mono text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded" style="font-size: 12px;">
                                            {{ $ecriture->newAccount->code }}
                                        </span>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800" style="font-size: 12px;">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> Non mappé
                                    </span>
                                @endif
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-2 py-0.5 whitespace-nowrap w-32">
                                <div class="flex items-center">
                                    <button wire:click="editEcriture({{ $ecriture->id }})"
                                            class="text-blue-600 hover:text-blue-800 text-sm mr-2"
                                            title="Modifier" style="font-size: 12px;">
                                        Modifier
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <!-- Pas d'écritures manuelles -->
                    @endforelse
                    
                    <!-- Si seulement des écritures manuelles -->
                    @if($normalCount == 0 && $manualCount > 0)
                        <tr class="bg-yellow-50 border-y-2 border-yellow-300">
                            <td colspan="9" class="px-4 py-2 text-center">
                                <div class="flex items-center justify-center text-yellow-700 font-medium text-sm">
                                    <!--<i class="fas fa-pencil-alt mr-2"></i>-->
                                    ÉCRITURES AJOUTÉES MANUELLEMENT ({{ $manualCount }})
                                    <!--<i class="fas fa-pencil-alt ml-2"></i>-->
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
                
                <!-- Totaux -->
                @if($ecritures->count() > 0)
                <tfoot class="bg-gray-50 font-medium">
                    <tr>
                        <td colspan="5" class="px-6 py-3 text-right text-sm text-gray-700">
                            Totaux :
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-red-600 font-bold">
                            {{ number_format($totals['total_debit'], 0, ' ', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-green-600 font-bold">
                            {{ number_format($totals['total_credit'], 0, ' ', ' ') }} FCFA
                        </td>
                        <td class="px-6 py-3 whitespace-nowrap text-sm text-red-600 font-bold">
                           Différence = {{ number_format($totals['difference'], 0, ' ', ' ') }} FCFA
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
            
            <style>
                .manual-row {
                    background: linear-gradient(90deg, #fffbeb 0%, #fef3c7 100%) !important;
                    position: relative;
                    box-shadow: inset 4px 0 0 #f59e0b; /* ✅ PAS DE DECALAGE */
                }
                
                .manual-row:hover {
                    background: linear-gradient(90deg, #fef3c7 0%, #fde68a 100%) !important;
                }
                
                .badge-manuel {
                    background: #fbbf24;
                    color: #92400e;
                    border: 1px solid #f59e0b;
                    box-shadow: 0 1px 3px rgba(245, 158, 11, 0.2);
                }
                
                .separator-manuel {
                    background: linear-gradient(90deg, #fef3c7 0%, #fdba74 100%);
                    border-top: 2px dashed #f59e0b;
                    border-bottom: 2px dashed #f59e0b;
                }
                
                /* Assure que toutes les cellules ont la même hauteur */
                table td, table th {
                    height: 32px;
                    vertical-align: middle;
                }
            </style>
            
            <!-- Tableau statistique des classes de comptes -->
<div class="mt-6 bg-white rounded-lg shadow border">
    <div class="px-6 py-4 border-b">
        <h3 style="font-size: 11px;" class="font-semibold text-gray-900 flex items-center">
            <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
            Statistiques par classe de comptes
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Classe de comptes
                    </th>
                    <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Total Débit
                    </th>
                    <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Total Crédit
                    </th>
                    <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                        Solde
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <!-- Classe 1-5 (Actif, Passif, Capitaux, Résultats) -->
                <tr>
                    <td class="px-6 py-3">
                        <div class="flex items-center">
                            <div style="font-size: 11px;" class="font-medium text-gray-900">
                                Comptes 1 à 5 (Actif/Passif/Capitaux/Résultats)
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <div style="font-size: 11px;" class="text-red-600 font-bold">
                            {{ number_format($classStats['classe_1_5']['debit'], 0, ' ', ' ') }} FCFA
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <div style="font-size: 11px;" class="text-green-600 font-bold">
                            {{ number_format($classStats['classe_1_5']['credit'], 0, ' ', ' ') }} FCFA
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        @php
                            $solde_1_5 = $classStats['classe_1_5']['solde'];
                            $isDebiteur_1_5 = $solde_1_5 > 0;
                        @endphp
                        <div style="font-size: 11px;" class="font-bold {{ $isDebiteur_1_5 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format(abs($solde_1_5), 0, ' ', ' ') }} FCFA
                            <span style="font-size: 10px;" class="font-normal ml-2">
                                ({{ $isDebiteur_1_5 ? 'Débiteur' : 'Créditeur' }})
                            </span>
                        </div>
                    </td>
                </tr>
                
                <!-- Classe 6-7 (Charges, Produits) -->
                <tr>
                    <td class="px-6 py-3">
                        <div class="flex items-center">
                            <div style="font-size: 11px;" class="font-medium text-gray-900">
                                Comptes 6 et 7 (Charges/Produits)
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <div style="font-size: 11px;" class="text-red-600 font-bold">
                            {{ number_format($classStats['classe_6_7']['debit'], 0, ' ', ' ') }} FCFA
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <div style="font-size: 11px;" class="text-green-600 font-bold">
                            {{ number_format($classStats['classe_6_7']['credit'], 0, ' ', ' ') }} FCFA
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        @php
                            $solde_6_7 = $classStats['classe_6_7']['solde'];
                            $isDebiteur_6_7 = $solde_6_7 > 0;
                        @endphp
                        <div style="font-size: 11px;" class="font-bold {{ $isDebiteur_6_7 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format(abs($solde_6_7), 0, ' ', ' ') }} FCFA
                            <span style="font-size: 10px;" class="font-normal ml-2">
                                ({{ $isDebiteur_6_7 ? 'Débiteur' : 'Créditeur' }})
                            </span>
                        </div>
                    </td>
                </tr>
                
                <!-- Total Global -->
                <tr class="bg-gray-50 font-bold">
                    <td class="px-6 py-3">
                        <div class="flex items-center">
                            <div style="font-size: 11px;" class="font-bold text-gray-900">
                                TOTAL GLOBAL
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <div style="font-size: 11px;" class="text-red-700 font-bold">
                            {{ number_format($classStats['global']['debit'], 0, ' ', ' ') }} FCFA
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        <div style="font-size: 11px;" class="text-green-700 font-bold">
                            {{ number_format($classStats['global']['credit'], 0, ' ', ' ') }} FCFA
                        </div>
                    </td>
                    <td class="px-6 py-3 whitespace-nowrap">
                        @php
                            $solde_global = $classStats['global']['solde'];
                            $isDebiteur_global = $solde_global > 0;
                        @endphp
                        <div style="font-size: 11px;" class="font-bold text-blue-700">
                            {{ number_format(abs($solde_global), 0, ' ', ' ') }} FCFA
                            <span style="font-size: 10px;" class="font-normal ml-2">
                                ({{ $isDebiteur_global ? 'Débiteur' : 'Créditeur' }})
                            </span>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
        </div>
        
    </div>
    
    <!-- VUE RÉCAPITULÉE (nouvelle vue) -->
    <!-- VUE RÉCAPITULÉE (avec toutes les écritures détaillées) -->
    @elseif($activeTab === 'recap')
        @php
            $recapData = $this->getRecapData();
            $recapStats = $this->getRecapStats();
        @endphp
        
        <!-- Tableau Récapitulatif (comme votre capture Excel) -->
        <div class="bg-white rounded-lg shadow border overflow-hidden flex flex-col h-full">
            <div class="table-scroll-container">
                <table class="min-w-full border-collapse">
                    <!-- En-tête exact comme votre capture -->
                    <thead class="bg-gray-800 text-white sticky top-0 z-30">
                        <tr>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">Date</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">N° de pièce</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">Code journal</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">N° de compte</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">Compte SYCEBNL</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">Libellé</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">Montant débit</th>
                            <th class="px-2 py-0.5 text-left text-xs font-medium" style="font-size: 10px;">Montant crédit</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        @forelse($recapData as $groupIndex => $group)
                            
                            <!-- Écritures détaillées -->
                            @foreach($group['old_accounts_data'] as $oldAccountIndex => $oldAccountData)
                                
                                <!-- Écritures individuelles pour cet ancien compte -->
                                @foreach($oldAccountData['ecritures'] as $ecritureIndex => $ecriture)
                                <tr class="hover:bg-gray-50 border-b border-gray-100">
                                    <!-- Date -->
                                    <td class="px-2 py-0.5 text-xs text-gray-700" style="font-size: 10px;">
                                        {{ $ecriture->date_ecriture->format('d/m/Y') }}
                                    </td>
                                    
                                    <!-- N° de pièce -->
                                    <td class="px-2 py-0.5 text-xs text-gray-700 font-mono" style="font-size: 10px;">
                                        @if($ecriture->piece)
                                            {{ $ecriture->piece }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    
                                    <!-- Code journal -->
                                    <td class="px-2 py-0.5 text-xs text-gray-700" style="font-size: 10px;">
                                        @if($ecriture->journal_code)
                                            <span class="bg-gray-100 px-1 py-0.5 rounded">{{ $ecriture->journal_code }}</span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    
                                    <!-- N° de compte (ancien) -->
                                    <td class="px-2 py-0.5 text-xs font-mono text-gray-800" style="font-size: 10px;">
                                        {{ $ecriture->oldAccount->code }}
                                    </td>
                                    
                                    <!-- Compte SYCEBNL -->
                                    <td class="px-2 py-0.5 text-xs font-mono text-blue-600 font-medium" style="font-size: 10px;">
                                        {{ $group['new_account_code'] }}
                                    </td>
                                    
                                    <!-- Libellé -->
                                    <td class="px-2 py-0.5 text-xs text-gray-600 max-w-xs truncate" style="font-size: 10px; max-width: 300px;">
                                        {{ $ecriture->libelle ?? '-' }}
                                    </td>
                                    
                                    <!-- Montant débit -->
                                    <td class="px-2 py-0.5 text-right text-xs {{ $ecriture->debit > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}" style="font-size: 10px;">
                                        @if($ecriture->debit > 0)
                                            {{ number_format($ecriture->debit, 0, '', ' ') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    
                                    <!-- Montant crédit -->
                                    <td class="px-2 py-0.5 text-right text-xs {{ $ecriture->credit > 0 ? 'text-green-600 font-medium' : 'text-gray-400' }}" style="font-size: 10px;">
                                        @if($ecriture->credit > 0)
                                            {{ number_format($ecriture->credit, 0, '', ' ') }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                                
                                <!-- Sous-total par ancien compte -->
                                @if(count($group['old_accounts_data']) > 1 && !$loop->last)
                                <tr class="bg-gray-50">
                                    <td colspan="6" class="px-2 py-0.5 text-right text-xs font-medium text-gray-700" style="font-size: 10px;">
                                        Sous-total {{ $oldAccountData['account']->code }}
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs font-medium text-red-700" style="font-size: 10px;">
                                        {{ number_format($oldAccountData['total_debit'], 0, '', ' ') }}
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs font-medium text-green-700" style="font-size: 10px;">
                                        {{ number_format($oldAccountData['total_credit'], 0, '', ' ') }}
                                    </td>
                                </tr>
                                @endif
                            @endforeach
                            
                            <!-- Total pour ce compte SYCEBNL -->
                            <tr class="bg-gray-100 border-t border-gray-300 font-bold">
                                <td colspan="6" class="px-2 py-0.5 text-right text-xs text-gray-800" style="font-size: 11px;">
                                    Total Mouvements
                                </td>
                                <td class="px-2 py-0.5 text-right text-xs text-red-800" style="font-size: 11px;">
                                    {{ number_format($group['total_debit'], 0, '', ' ') }}
                                </td>
                                <td class="px-2 py-0.5 text-right text-xs text-green-800" style="font-size: 11px;">
                                    {{ number_format($group['total_credit'], 0, '', ' ') }}
                                </td>
                            </tr>
                            
                            <!-- Ligne de solde - VERSION AVEC POSITION DYNAMIQUE -->
                            @php
                                $solde = $group['solde'];
                                $isDebit = $solde > 0; // Si solde > 0, c'est un solde débiteur (à placer en débit)
                                $isCredit = $solde < 0; // Si solde < 0, c'est un solde créditeur (à placer en crédit)
                                $soldeAbs = abs($solde);
                                $soldeText = number_format($soldeAbs, 0, '', ' ');
                                
                                // Pour équilibre comptable, déterminer quelle colonne est la plus grande
                                $debitBigger = $group['total_debit'] > $group['total_credit'];
                                $creditBigger = $group['total_credit'] > $group['total_debit'];
                            @endphp
                            
                            <tr class="bg-blue-50/50 border-b-2 border-blue-200">
                                <td colspan="6" class="px-2 py-0.5 text-right text-xs font-bold text-blue-800" style="font-size: 11px;">
                                    @php
                                        $dateSolde = $dateFin
                                            ? \Carbon\Carbon::parse($dateFin)->format('d-m-Y')
                                            : now()->format('d-m-Y');
                                    @endphp
                                    
                                    Solde au {{ $dateSolde }}
                                </td>
                                
                                @if($solde > 0)
                                    <!-- Solde débiteur (positif) -> colonne débit -->
                                    <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100" style="font-size: 11px;">
                                        {{ $soldeText }}
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                @elseif($solde < 0)
                                    <!-- Solde créditeur (négatif) -> colonne crédit -->
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100" style="font-size: 11px;">
                                        {{ $soldeText }}
                                    </td>
                                @else
                                    <!-- Solde nul -->
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                @endif
                            </tr>
                            
                            <!-- Alternative : Basé sur le total le plus grand (selon votre demande originale) -->
                            {{-- 
                            <tr class="bg-blue-50/50 border-b-2 border-blue-200">
                                <td colspan="6" class="px-2 py-0.5 text-right text-xs font-bold text-blue-800" style="font-size: 11px;">
                                    Solde au {{ $dateSolde }}
                                </td>
                                
                                @if($debitBigger)
                                    <!-- Total débit plus grand -> solde en débit -->
                                    <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100" style="font-size: 11px;">
                                        {{ $soldeText }}
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                @elseif($creditBigger)
                                    <!-- Total crédit plus grand -> solde en crédit -->
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100" style="font-size: 11px;">
                                        {{ $soldeText }}
                                    </td>
                                @else
                                    <!-- Totaux égaux -->
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                    <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                        -
                                    </td>
                                @endif
                            </tr>
                            --}}
                            
                            <!-- Saut de ligne entre groupes -->
                            @if(!$loop->last)
                            <tr>
                                <td colspan="8" class="py-4">
                                    <div class="h-px bg-gray-200"></div>
                                </td>
                            </tr>
                            @endif
                            
                        @empty
                            <!-- Aucune donnée -->
                            <tr>
                                <td colspan="8" class="px-6 py-0.52 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-layer-group text-4xl text-gray-300 mb-3"></i>
                                        <p class="text-lg" style="font-size: 12px;">Aucune écriture regroupée trouvée</p>
                                        @if($search || $dateDebut)
                                            <p class="text-sm text-gray-400" style="font-size: 10px;">Essayez de modifier vos filtres</p>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    
                    <!-- Totaux généraux en bas -->
                    @if(count($recapData) > 0)
                    <tfoot class="bg-blue-100 border-t-4 border-blue-300 font-bold">
                        <tr>
                            <td colspan="5" class="px-2 py-0.5 text-right text-xs text-blue-800" style="font-size: 11px;">
                                TOTAUX GÉNÉRAUX
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
                                SOLDE GLOBAL
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
                    
                    <!-- Alerte de correspondance -->
@php
    $comparison = $this->compareWithBalance();
@endphp

@if(!$comparison['correspondance']['debit_match'] || !$comparison['correspondance']['credit_match'])
<div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
    <div class="flex items-center">
        <i class="fas fa-exclamation-triangle text-yellow-500 mr-3 text-xl"></i>
        <div>
            <h4 class="text-sm font-medium text-yellow-800">Attention : Disparité détectée</h4>
            <p class="text-xs text-yellow-700 mt-1">
                Les totaux du Grand Livre Général ne correspondent pas exactement à ceux de la Balance.
            </p>
            
            <div class="mt-2 grid grid-cols-2 gap-4 text-xs">
                <div class="bg-white p-2 rounded border">
                    <p class="font-medium text-gray-700">Grand Livre Général</p>
                    <p class="text-red-600">Débit: {{ number_format($comparison['grand_livre_general']['debit'], 0, ' ', ' ') }}</p>
                    <p class="text-green-600">Crédit: {{ number_format($comparison['grand_livre_general']['credit'], 0, ' ', ' ') }}</p>
                </div>
                <div class="bg-white p-2 rounded border">
                    <p class="font-medium text-gray-700">Balance</p>
                    <p class="text-red-600">Débit: {{ number_format($comparison['balance']['debit'], 0, ' ', ' ') }}</p>
                    <p class="text-green-600">Crédit: {{ number_format($comparison['balance']['credit'], 0, ' ', ' ') }}</p>
                </div>
            </div>
            
            <p class="text-xs text-yellow-600 mt-2">
                <i class="fas fa-lightbulb mr-1"></i>
                Conseils : Vérifiez que toutes les écritures sont correctement mappées et synchronisées.
            </p>
        </div>
    </div>
</div>
@endif
                </table>
            </div>
        </div>
    @endif


    <!-- Modal d'import -->
    <div x-show="showImportModal" 
         x-transition.opacity
         x-cloak
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
                <h3 class="text-lg font-semibold text-gray-900" style="font-size: 12px;">
                    <i class="fas fa-file-import mr-2 text-green-500"></i>
                    Importer des écritures comptables
                </h3>
                <button @click="showImportModal = false" 
                        class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form wire:submit.prevent="import">
                    <!-- Instructions -->
                    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="font-medium text-blue-800 mb-2 flex items-center" style="font-size: 12px;">
                            <i class="fas fa-info-circle mr-2"></i>
                            Format Excel requis
                        </h4>
                        <ul class="text-sm text-blue-700 list-disc pl-5 space-y-1">
                            <li style="font-size: 12px;"><strong>En-têtes (ligne 1)</strong> : date, journal, compte, libelle, piece, debit, credit</li>
                            <li style="font-size: 12px;"><strong>Date</strong> : JJ/MM/AAAA (ex: 15/01/2024)</li>
                            <li style="font-size: 12px;"><strong>Journal</strong> : Code journal existant (optionnel)</li>
                            <li style="font-size: 12px;"><strong>Compte</strong> : Code du plan comptable (OBLIGATOIRE)</li>
                            <li style="font-size: 12px;"><strong>Libellé</strong> : Description de l'écriture</li>
                            <li style="font-size: 12px;"><strong>Pièce</strong> : Numéro de justificatif (optionnel)</li>
                            <li style="font-size: 12px;"><strong>Débit/Crédit</strong> : Renseigner un seul par ligne</li>
                        </ul>
                        <div class="mt-3 flex items-center gap-3">
                            <a href="{{ route('grand-livre.template') }}"
                               class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                               style="font-size: 12px;">
                                <i class="fas fa-download"></i>
                                Télécharger le template Excel
                            </a>
                        </div>
                    
                        <!-- Fichier -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 12px;">
                                Fichier Excel (.xlsx, .xls, .csv)
                            </label>
                            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition">
                                <div class="space-y-1 text-center">
                                    <i class="fas fa-file-excel text-5xl text-green-500 mb-2"></i>
                                    <div class="flex text-sm text-gray-600">
                                        <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                            <span style="font-size: 12px;">Choisir un fichier</span>
                                            <input type="file" wire:model="importFile" class="sr-only" accept=".xlsx,.xls,.csv">
                                        </label>
                                        <p class="pl-1" style="font-size: 12px;">ou glissez-déposez</p>
                                    </div>
                                    <p class="text-xs text-gray-500" style="font-size: 12px;">
                                        XLSX, XLS, CSV jusqu'à 10MB
                                    </p>
                                    
                                    @if($importFile)
                                    <div class="mt-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                                <div class="text-left">
                                                    <p class="text-sm font-medium text-green-700" style="font-size: 12px;">
                                                        {{ $importFile->getClientOriginalName() }}
                                                    </p>
                                                    <p class="text-xs text-gray-500" style="font-size: 12px;">
                                                        {{ number_format($importFile->getSize() / 1024, 2) }} KB
                                                    </p>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="$set('importFile', null)" 
                                                    class="text-red-500 hover:text-red-700">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @error('importFile') 
                                <p class="mt-2 text-sm text-red-600 flex items-center" style="font-size: 12px;">
                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                    {{ $message }}
                                </p> 
                            @enderror
                        </div>
                        
                        <!-- Options -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 12px;">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Exercice comptable
                                </label>
                                <input type="number" wire:model="importExercice"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                       min="2000" max="{{ date('Y') + 5 }}">
                                @error('importExercice') 
                                    <p class="mt-1 text-xs text-red-600" style="font-size: 12px;">{{ $message }}</p> 
                                @enderror
                            </div>
                            
                            <div class="flex items-center pt-6">
                                <input type="checkbox" wire:model="autoValidate" 
                                       id="autoValidate"
                                       class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                <label for="autoValidate" class="ml-2 block text-sm text-gray-700" style="font-size: 12px;">
                                    <i class="fas fa-check-double mr-1 text-green-500"></i>
                                    Valider automatiquement les écritures
                                </label>
                            </div>
                        </div>
                        
                        <!-- Barre de progression -->
                        @if($importing)
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm font-medium text-blue-700" style="font-size: 12px;">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Importation en cours...
                                </span>
                            </div>
                            <div class="w-full bg-blue-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                            </div>
                            <p class="mt-2 text-xs text-blue-600" style="font-size: 12px;">
                                Veuillez patienter, cette opération peut prendre quelques instants...
                            </p>
                        </div>
                        @endif
                        
                        <!-- Messages de succès -->
                        @if($importSuccess && !$importing)
                        <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                                <div>
                                    <p class="font-medium text-green-800" style="font-size: 12px;">
                                        Importation réussie !
                                    </p>
                                    <p class="text-sm text-green-700 mt-1" style="font-size: 12px;">
                                        Les écritures ont été importées dans le grand livre.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Erreurs et avertissements -->
                        @if(count($importErrors) > 0 && !$importing)
                        <div class="mb-6 max-h-60 overflow-y-auto">
                            <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                                <h4 class="font-semibold text-yellow-800 mb-3 flex items-center" style="font-size: 12px;">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>
                                    Messages d'importation ({{ count($importErrors) }})
                                </h4>
                                <ul class="space-y-2">
                                    @foreach($importErrors as $error)
                                        <li class="text-sm flex items-start" style="font-size: 12px;">
                                            @if(str_starts_with($error, '❌'))
                                                <i class="fas fa-times-circle text-red-500 mr-2 mt-0.5"></i>
                                                <span class="text-red-700">{{ $error }}</span>
                                            @elseif(str_starts_with($error, '⚠️'))
                                                <i class="fas fa-exclamation-circle text-yellow-500 mr-2 mt-0.5"></i>
                                                <span class="text-yellow-700">{{ $error }}</span>
                                            @else
                                                <span class="text-gray-700">• {{ $error }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Actions -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                            <button type="button"
                                    @click="showImportModal = false"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition" 
                                    style="font-size: 12px;">
                                <i class="fas fa-times mr-2"></i>
                                Fermer
                            </button>
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    :disabled="!$wire.importFile"
                                    class="px-4 py-2 bg-green-600 text-white hover:bg-green-700 rounded-lg transition shadow-sm flex items-center disabled:opacity-50 disabled:cursor-not-allowed" 
                                    style="font-size: 12px;">
                                <span wire:loading.remove wire:target="import">
                                    <i class="fas fa-upload mr-2"></i>
                                    Importer
                                </span>
                                <span wire:loading wire:target="import">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Importation...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
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


    <!-- Modal Ajouter -->
    <div x-data="{ open: false }"
        x-cloak
        x-on:open-add-modal.window="open = true">

        <div x-show="open"
            class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center"
            x-transition>
            
            <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6 relative z-50"
                x-transition>

                <!-- Close -->
                <button @click="open = false" class="absolute right-3 top-3 text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>

                <h2 class="text-xl font-bold mb-4" style="font-size: 12px;">Ajouter une écriture</h2>

                @livewire('grand-livre.add') <!-- Composant Livewire -->

            </div>
        </div>
    </div>
    
    
    <!-- Modal Éditer -->
    <div x-data="{ open: false }"
         x-cloak
         x-on:show-edit-modal.window="open = true"
         x-on:close-edit-modal.window="open = false">
    
        <div x-show="open"
             class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
             x-transition>
            
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
                 @click.outside="open = false">
                
                <!-- En-tête -->
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
                    <h2 class="text-lg font-bold text-gray-800 flex items-center">
                        <i class="fas fa-edit text-blue-500 mr-2"></i>
                        Modifier l'écriture
                    </h2>
                    <button @click="open = false" 
                            class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
                
                <!-- Formulaire -->
                <div class="p-6">
                    <form wire:submit.prevent="updateEcriture">
                        
                        <!-- Informations de base -->
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                Informations de l'écriture
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Date -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Date d'écriture *
                                    </label>
                                    <input type="date" 
                                           wire:model="editDate"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    @error('editDate') 
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                
                                <!-- Journal -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Code journal
                                    </label>
                                    <select wire:model="editJournal"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        <option value="">Sélectionner un journal</option>
                                        @foreach($journaux as $journal)
                                            <option value="{{ $journal['code'] }}">
                                                {{ $journal['code'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                
                                <!-- Pièce -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Numéro de pièce
                                    </label>
                                    <input type="text" 
                                           wire:model="editPiece"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                           placeholder="Ex: F-2024-001">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Compte et montants -->
                        <div class="mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-calculator text-green-500 mr-2"></i>
                                Compte et montants
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Compte -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Compte *
                                        @if($editingEcriture && $editingEcriture->newAccount)
                                            <span class="text-xs text-yellow-600 ml-1">
                                                (Mappé à {{ $editingEcriture->newAccount->code }})
                                            </span>
                                        @endif
                                    </label>
                                    
                                    <!-- D'abord, charger la liste complète des anciens comptes -->
                                    @php
                                        // Utiliser la propriété allOldAccounts qu'on a chargée dans mount()
                                        $oldAccounts = $this->allOldAccounts;
                                    @endphp
                                    
                                    <select wire:model="editOldAccountId"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            {{ $editingEcriture && $editingEcriture->newAccount ? 'disabled' : '' }}>
                                        <option value="">Sélectionner un compte</option>
                                        @foreach($oldAccounts as $account)
                                            <option value="{{ $account->id }}">
                                                {{ $account->code }} - {{ Str::limit($account->intitule, 30) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    
                                    <!-- Ajouter un champ de recherche pour filtrer les comptes -->
                                    <div class="mt-2" x-data="{ showSearch: false }">
                                        <button type="button" 
                                                @click="showSearch = !showSearch"
                                                class="text-xs text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-search mr-1"></i>
                                            Rechercher un compte
                                        </button>
                                        
                                        <div x-show="showSearch" x-cloak class="mt-2">
                                            <input type="text" 
                                                   wire:model.debounce.300ms="searchAccount"
                                                   placeholder="Rechercher par code ou libellé..."
                                                   class="w-full px-2 py-1 text-xs border rounded">
                                            
                                            <!-- Liste filtrée des comptes -->
                                            @php
                                                $searchAccount = $searchAccount ?? '';
                                                $filteredAccounts = $oldAccounts->filter(function($account) use ($searchAccount) {
                                                    return stripos($account->code, $searchAccount) !== false || 
                                                           stripos($account->intitule, $searchAccount) !== false;
                                                });
                                            @endphp
                                            
                                            @if($searchAccount && $filteredAccounts->isNotEmpty())
                                                <div class="mt-2 max-h-40 overflow-y-auto border rounded">
                                                    @foreach($filteredAccounts as $account)
                                                        <button type="button"
                                                                wire:click="$set('editOldAccountId', {{ $account->id }})"
                                                                class="w-full text-left px-2 py-1 text-xs hover:bg-blue-50 border-b last:border-b-0">
                                                            <strong>{{ $account->code }}</strong> - {{ $account->intitule }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    @error('editOldAccountId') 
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                    
                                    @if($editingEcriture && $editingEcriture->newAccount)
                                        <p class="text-xs text-yellow-600 mt-1">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            Le compte ne peut être modifié car l'écriture est déjà mappée. 
                                            Seul le libellé peut être modifié.
                                        </p>
                                    @endif
                                </div>
                                
                                <!-- Débit -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Montant Débit (FCFA)
                                    </label>
                                    <input type="number" 
                                           wire:model="editDebit"
                                           min="0"
                                           step="1"
                                           class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                                           placeholder="0">
                                    @error('editDebit') 
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                                
                                <!-- Crédit -->
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">
                                        Montant Crédit (FCFA)
                                    </label>
                                    <input type="number" 
                                           wire:model="editCredit"
                                           min="0"
                                           step="1"
                                           class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                           placeholder="0">
                                    @error('editCredit') 
                                        <span class="text-xs text-red-600">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                            
                            <!-- Avertissement débit/crédit -->
                            @if($editDebit && $editCredit)
                                <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                    <p class="text-xs text-yellow-800 flex items-center">
                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                        Attention : Les deux montants (débit et crédit) sont renseignés. 
                                        Normalement, une écriture ne devrait avoir qu'un seul montant.
                                    </p>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Libellé -->
                        <div class="mb-6">
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Libellé de l'écriture
                            </label>
                            <textarea wire:model="editLibelle"
                                      rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                      placeholder="Description de l'opération..."></textarea>
                            <p class="text-xs text-gray-500 mt-1">
                                Maximum 255 caractères. Actuellement : {{ strlen($editLibelle ?? '') }}/255
                            </p>
                        </div>
                        
                        <!-- Informations de mapping -->
                        @if($editingEcriture && $editingEcriture->newAccount)
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h4 class="text-sm font-medium text-blue-800 mb-2 flex items-center">
                                <i class="fas fa-link mr-2"></i>
                                Information de mapping
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div>
                                    <p class="text-xs text-blue-700 font-medium">Ancien compte :</p>
                                    <p class="text-sm font-bold">{{ $editingEcriture->oldAccount->code ?? 'N/A' }} - {{ $editingEcriture->oldAccount->intitule ?? '' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-blue-700 font-medium">Nouveau compte SYCEBNL :</p>
                                    <p class="text-sm font-bold text-green-600">{{ $editingEcriture->newAccount->code }} - {{ $editingEcriture->newAccount->intitule }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Actions -->
                        <div class="flex justify-end space-x-3 pt-4 border-t">
                        
                            <!-- Bouton Supprimer -->
                            <button type="button"
                                wire:click="deleteEcriture"
                                @if($editingEcriture && $editingEcriture->newAccount)
                                    disabled
                                @endif
                                class="px-4 py-2 rounded-lg transition text-sm flex items-center
                                    {{ $editingEcriture && $editingEcriture->newAccount
                                        ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                        : 'bg-red-600 text-white hover:bg-red-700' }}"
                            >
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer
                            </button>
                        
                            <!-- Annuler -->
                            <button type="button"
                                    wire:click="closeEditModal"
                                    class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition text-sm">
                                <i class="fas fa-times mr-2"></i>
                                Annuler
                            </button>
                        
                            <!-- Enregistrer -->
                            <button type="submit"
                                    wire:loading.attr="disabled"
                                    class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition shadow-sm flex items-center disabled:opacity-50 text-sm">
                                <span wire:loading.remove wire:target="updateEcriture">
                                    <i class="fas fa-save mr-2"></i>
                                    Enregistrer les modifications
                                </span>
                                <span wire:loading wire:target="updateEcriture">
                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                    Enregistrement...
                                </span>
                            </button>
                        
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    

</div>