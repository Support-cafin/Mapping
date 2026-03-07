<div class="p-6 mt-12" x-data="{
    showGenerateModal: @entangle('showGenerateModal'),
    viewMode: @entangle('viewMode'),
    selectedBalance: @entangle('selectedBalance'),
    stats: @entangle('stats')
}">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 style="font-size: 14px;" class="font-bold text-gray-800">
                    <i class="fas fa-scale-balanced mr-2 text-blue-600"></i>
                    Balance Générale des Comptes SYCEBNL
                </h1>
                <p style="font-size: 12px;" class="text-gray-600 mt-1">
                    Balance complète avec soldes N-1 (Bilan d'ouverture) et mouvements N
                </p>
            </div>
            
            <div class="flex items-center space-x-3">
                <span style="font-size: 12px;" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                    {{ $entreprise->nom }}
                </span>
                
                @if($viewMode === 'details' && $selectedBalance)
                    <button style="font-size: 12px;" @click="viewMode = 'table'; selectedBalance = null"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center gap-2 text-sm font-medium">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la liste
                    </button>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Filtres et Actions -->
    @if($viewMode === 'table')
    <div class="bg-white rounded-lg shadow border p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Exercice -->
            <div>
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Exercice
                </label>
                <input style="font-size: 12px;" type="number" 
                       wire:model.live="exercice"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                       min="2000" max="{{ date('Y') + 5 }}">
            </div>
            
            <!-- Période N -->
            <div>
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-calendar mr-1"></i>
                    Période (Exercice N)
                </label>
                <div class="flex gap-2">
                    <input style="font-size: 12px;" type="date" 
                           wire:model.live="dateDebut"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <input style="font-size: 12px;" type="date" 
                           wire:model.live="dateFin"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
            
            <!-- Compte spécifique -->
            <div>
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-filter mr-1"></i>
                    Compte spécifique
                </label>
                <select style="font-size: 12px;" wire:model.live="accountId"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">Tous les comptes</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}">
                            {{ $account->code }} - {{ Str::limit($account->intitule, 20) }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <!-- Actions -->
            <div class="flex items-end gap-2">
                <div class="flex-1 space-y-2">
                    <button style="font-size: 12px;" wire:click="resetFilters"
                            class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-redo"></i>
                        Réinitialiser
                    </button>
                    
                    <button style="font-size: 12px;" @click="showGenerateModal = true"
                            class="w-full px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 
                                   flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-calculator"></i>
                        Générer
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Résumé filtres actifs -->
        <div class="mt-4 flex flex-wrap gap-2">
            <span style="font-size: 12px;" class="inline-flex items-center px-3 py-1 rounded-full bg-purple-100 text-purple-800 text-xs">
                Exercice: {{ $exercice }} (N-1: {{ $exercice - 1 }})
            </span>
            
            @if($dateDebut && $dateFin)
                <span style="font-size: 12px;" class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs">
                    {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
                </span>
            @endif
            
            @if($accountId && $selectedAccount = ($accounts->firstWhere('id', $accountId)))
                <span style="font-size: 12px;" class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs">
                    {{ $selectedAccount->code }}
                    <button wire:click="$set('accountId', '')" class="ml-2">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            @endif
        </div>
    </div>
    
    <!-- Stats rapides -->
    <div class="mb-6 grid grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-sm text-gray-500">Total Comptes</p>
                    <p style="font-size: 11px;" class="font-bold text-gray-900 mt-1">
                        {{ $stats['total_comptes'] }}
                    </p>
                </div>
                <div style="font-size: 11px;" class="p-2 bg-blue-100 rounded-lg">
                    <i class="fas fa-hashtag text-xl text-blue-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-sm text-gray-500">Écritures N</p>
                    <p style="font-size: 11px;" class="font-bold text-gray-900 mt-1">
                        {{ $stats['total_ecritures'] }}
                    </p>
                </div>
                <div style="font-size: 11px;" class="p-2 bg-green-100 rounded-lg">
                    <i class="fas fa-file-alt text-xl text-green-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-sm text-gray-500">Solde Global</p>
                    <p style="font-size: 11px;" class="font-bold {{ $stats['total_solde'] > 0 ? 'text-red-600' : 'text-green-600' }} mt-1">
                        {{ number_format(abs($stats['total_solde']), 0, ',', ' ') }} FCFA
                    </p>
                </div>
                <div style="font-size: 11px;" class="p-2 bg-yellow-100 rounded-lg">
                    <i class="fas fa-scale-balanced text-xl text-yellow-600"></i>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow border p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-sm text-gray-500">Statut</p>
                    <p style="font-size: 11px;" class="font-bold {{ $stats['total_solde'] > 0 ? 'text-red-600' : 'text-green-600' }} mt-1">
                        {{ $stats['total_solde'] > 0 ? 'Débiteur' : 'Créditeur' }}
                    </p>
                </div>
                <div style="font-size: 11px;" class="p-2 {{ $stats['total_solde'] > 0 ? 'bg-red-100' : 'bg-green-100' }} rounded-lg">
                    <i class="fas fa-{{ $stats['total_solde'] > 0 ? 'arrow-down' : 'arrow-up' }} text-xl {{ $stats['total_solde'] > 0 ? 'text-red-600' : 'text-green-600' }}"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Table des balances -->
    <div class="bg-white rounded-lg shadow border overflow-hidden">
        <div class="px-6 py-2 border-b bg-gray-50">
            <div class="flex justify-between items-center">
                <h3 style="font-size: 12px;" class="text-lg font-semibold text-gray-900">
                    Balance Générale SYCEBNL
                </h3>
                <div class="flex items-center gap-2">
                    <span style="font-size: 12px;" class="text-sm text-gray-600">Trier par :</span>
                    <select style="font-size: 12px;" wire:model="sortField" 
                            wire:change="sortBy($event.target.value)"
                            class="text-sm border rounded-lg p-2">
                        <option value="code">Code</option>
                        <option value="solde">Solde</option>
                        <option value="total_debit">Débit</option>
                        <option value="total_credit">Crédit</option>
                    </select>
                    <button style="font-size: 12px;" wire:click="sortDirection = '{{ $sortDirection === 'asc' ? 'desc' : 'asc' }}'; sortBy('{{ $sortField }}')"
                            class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-arrow-{{ $sortDirection === 'asc' ? 'down' : 'up' }}"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Compte
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Intitulé
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde Débit N-1
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde Crédit N-1
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Débit N
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Crédit N
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde Total
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Écritures
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($balances as $balance)
                        @php
                            $isDebiteur = $balance['solde'] > 0;
                            $isCrediteur = $balance['solde'] < 0;
                        @endphp
                        
                        <tr class="hover:bg-gray-50 {{ !$balance['has_mappings'] ? 'bg-gray-50' : '' }}">
                            <!-- Code compte -->
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div style="font-size: 11px;" class="font-mono font-bold text-green-700">
                                    {{ $balance['code'] }}
                                </div>
                                @if(!$balance['has_mappings'])
                                <span style="font-size: 9px;" class="px-1 py-0.5 bg-gray-200 text-gray-600 rounded text-xs">
                                    Sans mapping
                                </span>
                                @endif
                            </td>
                            
                            <!-- Intitulé -->
                            <td class="px-6 py-1.5">
                                <div style="font-size: 11px;" class="text-sm text-gray-900">
                                    {{ Str::limit($balance['intitule'], 25) }}
                                </div>
                            </td>
                            
                            <!-- Soldes N-1 -->
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-sm {{ $balance['solde_debit_n1'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                    @if($balance['solde_debit_n1'] > 0)
                                        {{ number_format($balance['solde_debit_n1'], 0, ',', ' ') }} FCFA
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-sm {{ $balance['solde_credit_n1'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    @if($balance['solde_credit_n1'] > 0)
                                        {{ number_format($balance['solde_credit_n1'], 0, ',', ' ') }} FCFA
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Total débit N -->
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-sm {{ $balance['total_debit_n'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                    @if($balance['total_debit_n'] > 0)
                                        {{ number_format($balance['total_debit_n'], 0, ',', ' ') }} FCFA
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Total crédit N -->
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-sm {{ $balance['total_credit_n'] > 0 ? 'text-green-600' : 'text-gray-400' }}">
                                    @if($balance['total_credit_n'] > 0)
                                        {{ number_format($balance['total_credit_n'], 0, ',', ' ') }} FCFA
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Solde total -->
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div style="font-size: 11px;" class="text-sm font-bold 
                                        {{ $isDebiteur ? 'text-red-600' : ($isCrediteur ? 'text-green-600' : 'text-gray-600') }}">
                                        {{ number_format(abs($balance['solde']), 0, ',', ' ') }} FCFA
                                    </div>
                                    @if($balance['solde'] != 0)
                                    <div style="font-size: 11px;" class="px-2 py-1 rounded text-xs font-medium
                                        {{ $isDebiteur ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $isDebiteur ? 'Débiteur' : 'Créditeur' }}
                                    </div>
                                    @endif
                                </div>
                            </td>
                            
                            <!-- Écritures -->
                            <td class="px-6 py-1.5 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-sm text-gray-900">
                                    {{ $balance['ecritures_count'] }}
                                </div>
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-1.5 whitespace-nowrap text-sm font-medium">
                                @if($balance['has_mappings'] && $balance['ecritures_count'] > 0)
                                <button style="font-size: 11px;" wire:click="showDetails({{ $balance['id'] }})"
                                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <i class="fas fa-eye"></i>
                                    Détails
                                </button>
                                @else
                                <span style="font-size: 11px;" class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-scale-balanced text-4xl mb-4"></i>
                                    <p class="text-lg font-medium mb-2">Aucun compte SYCEBNL trouvé</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                <!-- Totaux -->
                @if(count($balances) > 0)
                <tfoot class="bg-gray-50 font-bold border-t-2">
                    <tr>
                        <td style="font-size: 11px;" colspan="2" class="px-6 py-1.5 text-right text-sm text-gray-700">
                            TOTAUX GÉNÉRAUX :
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-red-600">
                            {{ number_format(collect($balances)->sum('solde_debit_n1'), 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-green-600">
                            {{ number_format(collect($balances)->sum('solde_credit_n1'), 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-red-600">
                            {{ number_format($stats['total_debit'] - collect($balances)->sum('solde_debit_n1'), 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-green-600">
                            {{ number_format($stats['total_credit'] - collect($balances)->sum('solde_credit_n1'), 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm 
                            {{ $stats['total_solde'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format(abs($stats['total_solde']), 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-gray-600">
                            {{ $stats['total_ecritures'] }}
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 text-sm text-gray-600">
                            {{ $stats['total_solde'] > 0 ? 'Débiteur' : 'Créditeur' }}
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
    
    <!-- Pagination -->
    <div class="mt-6">
        @if(count($balances) > 50)
            <!-- Implémenter la pagination si nécessaire -->
        @endif
    </div>
    @endif
    
    <!-- Vue détaillée -->
    @if($viewMode === 'details' && $selectedBalance)
    <div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden mb-6">
        <div class="px-6 py-4 border-b bg-gray-50">
            <div class="flex justify-between items-center">
                <div>
                    <h2 style="font-size: 11px;" class="text-xl font-bold text-gray-900">
                        <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                        Détails du compte {{ $selectedBalance['code'] }}
                    </h2>
                    <p style="font-size: 11px;" class="text-gray-600 mt-1">
                        {{ $selectedBalance['intitule'] }}
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 rounded-lg 
                        {{ $selectedBalance['solde'] > 0 ? 'bg-red-100 text-red-800' : 
                           ($selectedBalance['solde'] < 0 ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                        <div style="font-size: 11px;" class="text-sm font-medium">
                            Solde : {{ number_format(abs($selectedBalance['solde']), 0, ',', ' ') }} FCFA
                        </div>
                        <div style="font-size: 11px;" class="text-xs">
                            {{ $selectedBalance['solde'] > 0 ? 'Débiteur' : 
                               ($selectedBalance['solde'] < 0 ? 'Créditeur' : 'Équilibré') }}
                        </div>
                    </div>
                    
                    <button style="font-size: 11px;" wire:click="backToList"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </button>
                </div>
            </div>
        </div>
        
        <div class="p-6">
            <!-- Résumé -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Solde Débit N-1</p>
                            <p style="font-size: 11px;" class="font-bold text-red-600 mt-1">
                                {{ number_format($selectedBalance['solde_debit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-red-50 rounded-lg">
                            <i class="fas fa-history text-xl text-red-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Solde Crédit N-1</p>
                            <p style="font-size: 11px;" class="font-bold text-green-600 mt-1">
                                {{ number_format($selectedBalance['solde_credit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-green-50 rounded-lg">
                            <i class="fas fa-history text-xl text-green-400"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Total Débit N</p>
                            <p style="font-size: 11px;" class="font-bold text-red-600 mt-1">
                                {{ number_format($selectedBalance['total_debit_n'] ?? 0, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-arrow-down text-xl text-red-600"></i>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Total Crédit N</p>
                            <p style="font-size: 11px;" class="font-bold text-green-600 mt-1">
                                {{ number_format($selectedBalance['total_credit_n'] ?? 0, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-arrow-up text-xl text-green-600"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Anciens comptes -->
            @if($selectedBalance['old_accounts_count'] > 0)
            <div class="mb-8">
                <h3 style="font-size: 11px;" class="text-lg font-semibold text-gray-900 mb-4">
                    Anciens comptes mappés
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Code
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Intitulé
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Débit
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Crédit
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Solde
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($selectedBalance['old_accounts'] as $oldAccount)
                                <tr class="hover:bg-gray-50">
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-mono font-bold text-orange-700">
                                            {{ $oldAccount['code'] }}
                                        </div>
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ Str::limit($oldAccount['intitule'], 25) }}
                                        </div>
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-red-600">
                                            {{ number_format($oldAccount['debit'], 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-green-600">
                                            {{ number_format($oldAccount['credit'], 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $solde = $oldAccount['debit'] - $oldAccount['credit'];
                                        @endphp
                                        <div class="text-sm font-bold 
                                            {{ $solde > 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ number_format(abs($solde), 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            
            <!-- Écritures -->
            @if($selectedBalance['ecritures_count'] > 0)
            <div>
                <h3 style="font-size: 11px;" class="text-lg font-semibold text-gray-900 mb-4">
                    Écritures ({{ $selectedBalance['ecritures_count'] }})
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Libellé
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Débit
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Crédit
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($selectedBalance['ecritures'] as $ecriture)
                                <tr class="hover:bg-gray-50">
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $ecriture->date_ecriture->format('d/m/Y') }}
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 text-sm text-gray-900">
                                        {{ $ecriture->libelle ?? '-' }}
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        @if($ecriture->debit > 0)
                                            {{ number_format($ecriture->debit, 0, ',', ' ') }} FCFA
                                        @endif
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        @if($ecriture->credit > 0)
                                            {{ number_format($ecriture->credit, 0, ',', ' ') }} FCFA
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <!-- Modal de génération -->
    <!-- ... Le modal reste inchangé ... -->

</div>