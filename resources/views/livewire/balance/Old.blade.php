<div class="p-6 mt-12" x-data="{
    showGenerateModal: @entangle('showGenerateModal'),
    showExportOptions: false,
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
                    Balance des Comptes
                </h1>
                <p style="font-size: 12px;" class="text-gray-600 mt-1">
                    Analyse des soldes et mouvements comptables
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
    
    <!-- Onglets Type -->
    <div class="mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex -mb-px">
                <!--<button style="font-size: 12px;" wire:click="$set('type', 'old')"-->
                <!--        :class="{'border-blue-500 text-blue-600': '{{ $type }}' === 'old',-->
                <!--                 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': '{{ $type }}' !== 'old'}"-->
                <!--        class="py-3 px-4 font-medium text-sm border-b-2 flex items-center gap-2">-->
                <!--    <i class="fas fa-database"></i>-->
                <!--    Balance des comptes entité-->
                <!--</button>-->
                
                <!--<button style="font-size: 12px;" wire:click="$set('type', 'new')"-->
                <!--        :class="{'border-green-500 text-green-600': '{{ $type }}' === 'new',-->
                <!--                 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': '{{ $type }}' !== 'new'}"-->
                <!--        class="py-3 px-4 font-medium text-sm border-b-2 flex items-center gap-2">-->
                <!--    <i class="fas fa-database"></i>-->
                <!--    Balance des comptes SYCEBNL-->
                <!--</button>-->
                <!-- Carte pour le total des écritures -->
                <div class="bg-white border rounded-lg p-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Total Écritures</p>
                            @php
                                $totalEcritures = collect($balances)->sum('ecritures_count');
                            @endphp
                            <p style="font-size: 11px;" class="font-bold text-purple-600 mt-1">
                                {{ number_format($totalEcritures, 0, ',', ' ') }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">mouvements</p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-file-invoice text-xl text-purple-600"></i>
                        </div>
                    </div>
                </div>
                <button style="font-size: 12px;" wire:click="$set('type', 'old')"
                        :class="{'border-green-500 text-green-600': '{{ $type }}' === 'old',
                                 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': '{{ $type }}' !== 'old'}"
                        class="py-3 px-4 font-medium text-sm border-b-2 flex items-center gap-2">
                    <i class="fas fa-database"></i>
                    Balance des comptes de SYCEBNL
                </button>
            </nav>
        </div>
    </div>
    
    <!-- Filtres et Actions -->
    @if($viewMode === 'table')
    <div class="bg-white rounded-lg shadow border p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Période -->
            <div>
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-calendar mr-1"></i>
                    Période
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
            @if($dateDebut && $dateFin)
                <span style="font-size: 12px;" class="inline-flex items-center px-3 py-1 rounded-full bg-blue-100 text-blue-800 text-xs">
                    {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} → {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
                </span>
            @endif
            
            @if($accountId && $selectedAccount = ($accounts->firstWhere('id', $accountId)))
                <span style="font-size: 12px;" class="inline-flex items-center px-3 py-1 rounded-full bg-green-100 text-green-800 text-xs">
                    Compte: {{ $selectedAccount->code }}
                    <button wire:click="$set('accountId', '')" class="ml-2">
                        <i class="fas fa-times"></i>
                    </button>
                </span>
            @endif
        </div>
    </div>
    
    <!-- Table des balances -->
    <div class="bg-white rounded-lg shadow border overflow-hidden">
        <div class="px-6 py-2 border-b bg-gray-50">
            <div class="flex justify-between items-center">
                <h3 style="font-size: 12px;" class="text-lg font-semibold text-gray-900">
                    Balance {{ $type === 'old' ? 'des comptes entité' : 'des comptes SYCEBNL' }}
                </h3>
                <div class="flex items-center gap-2">
                    <span style="font-size: 12px;" class="text-sm text-gray-600">Trier par :</span>
                    <select style="font-size: 12px;" wire:model="sortField" 
                            wire:change="sortBy($event.target.value)"
                            class="text-sm border rounded-lg p-2">
                        <option value="code">Code compte</option>
                        <option value="solde">Solde</option>
                        <option value="total_debit">Total débit</option>
                        <option value="total_credit">Total crédit</option>
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
                            Code SYCEBNL
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Libellé SYCEBNL
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde Débit N-1
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde Crédit N-1
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Débit
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Crédit
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mouvements
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Comptes entité mappés
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
            $isEquilibre = $balance['solde'] == 0;
            
            // Utilisez des variables sécurisées
            $oldAccounts = $balance['old_accounts'] ?? [];
            $oldAccountsCount = count($oldAccounts);
            $ecrituresCount = $balance['ecritures_count'] ?? 0;
        @endphp
        
        <tr class="hover:bg-gray-50 transition-colors duration-150">
            <!-- Code compte SYCEBNL -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div style="font-size: 11px;" class="font-mono font-bold text-green-700">
                    {{ $balance['code'] ?? '' }}
                </div>
                @if(($balance['classe'] ?? '') || ($balance['groupe'] ?? ''))
                    <div style="font-size: 9px;" class="text-gray-500 mt-1">
                        Classe: {{ $balance['classe'] ?? '-' }} | Groupe: {{ $balance['groupe'] ?? '-' }}
                    </div>
                @endif
            </td>
            
            <!-- Libellé compte SYCEBNL -->
            <td class="px-6 py-1.5">
                <div style="font-size: 11px;" class="text-sm text-gray-900 font-medium">
                    {{ Str::limit($balance['intitule'] ?? '', 30) }}
                </div>
                @if(($balance['account'] ?? null) && ($balance['account']->parent ?? null))
                    <div style="font-size: 9px;" class="text-blue-600 mt-1">
                        <i class="fas fa-level-up-alt"></i> 
                        Parent: {{ $balance['account']->parent->code ?? '' }}
                    </div>
                @endif
            </td>
            
            <!-- Solde Débit N-1 -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div style="font-size: 11px;" class="text-sm text-gray-700">
                    {{ number_format($balance['solde_debit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
            </td>
            
            <!-- Solde Crédit N-1 -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div style="font-size: 11px;" class="text-sm text-gray-700">
                    {{ number_format($balance['solde_credit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
            </td>
            
            <!-- Total débit -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div style="font-size: 11px;" class="text-sm text-red-600 font-medium">
                    {{ number_format($balance['total_debit'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
            </td>
            
            <!-- Total crédit -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div style="font-size: 11px;" class="text-sm text-green-600 font-medium">
                    {{ number_format($balance['total_credit'] ?? 0, 0, ',', ' ') }} FCFA
                </div>
            </td>
            
            <!-- Solde -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div class="flex items-center gap-2">
                    <div style="font-size: 11px;" class="text-sm font-bold 
                        {{ $isDebiteur ? 'text-red-600' : ($isCrediteur ? 'text-green-600' : 'text-gray-600') }}">
                        {{ number_format(abs($balance['solde'] ?? 0), 0, ',', ' ') }} FCFA
                    </div>
                    <div style="font-size: 11px;" class="px-2 py-1 rounded text-xs font-medium
                        {{ $isDebiteur ? 'bg-red-100 text-red-800' : 
                           ($isCrediteur ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') }}">
                        {{ $isDebiteur ? 'Débiteur' : ($isCrediteur ? 'Créditeur' : 'Équilibré') }}
                    </div>
                </div>
            </td>
            
            <!-- Mouvements -->
            <td class="px-6 py-1.5 whitespace-nowrap">
                <div style="font-size: 11px;" class="text-sm text-gray-900">
                    {{ $ecrituresCount }} écriture(s)
                    @if($oldAccountsCount > 0)
                        <div style="font-size: 9px;" class="text-orange-600">
                            ({{ $oldAccountsCount }} ancien(s) compte(s))
                        </div>
                    @endif
                </div>
            </td>
            
            <!-- Anciens comptes mappés -->
            <td class="px-6 py-1.5">
                <div style="font-size: 11px;" class="text-sm text-gray-700">
                    @if($oldAccountsCount > 0)
                        <div class="flex flex-wrap gap-1">
                            @foreach(array_slice($oldAccounts, 0, 3) as $oldAccount)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-orange-100 text-orange-800"
                                      title="{{ $oldAccount['intitule'] ?? '' }}">
                                    {{ $oldAccount['code'] ?? '' }}
                                    @if(($oldAccount['count'] ?? 0) > 0)
                                        <span class="ml-1 text-xs">({{ $oldAccount['count'] ?? 0 }})</span>
                                    @endif
                                </span>
                            @endforeach
                            @if($oldAccountsCount > 3)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-600"
                                      title="Cliquez pour voir tous les comptes">
                                    +{{ $oldAccountsCount - 3 }}
                                </span>
                            @endif
                        </div>
                    @else
                        <span style="font-size: 11px;" class="text-gray-400">Aucun mapping</span>
                    @endif
                </div>
            </td>
            
            <!-- Actions -->
            <td class="px-6 py-1.5 whitespace-nowrap text-sm font-medium">
                <button style="font-size: 11px;" wire:click="showDetails({{ $balance['id'] ?? 0 }})"
                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                    <i class="fas fa-eye"></i>
                    Détails
                </button>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="10" class="px-6 py-12 text-center">
                <div class="text-gray-400">
                    <i class="fas fa-scale-balanced text-4xl mb-4"></i>
                    <p class="text-lg font-medium mb-2">Aucune balance trouvée</p>
                    <p class="text-sm">Aucune écriture comptable dans la période sélectionnée</p>
                    <button wire:click="resetFilters"
                            class="mt-4 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Réinitialiser les filtres
                    </button>
                </div>
            </td>
        </tr>
    @endforelse
</tbody>
                
                <!-- Totaux -->
                @if(count($balances) > 0)
                <!-- Dans les totaux (~ligne 450) -->
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td style="font-size: 11px;" colspan="2" class="px-6 py-1.5 text-right text-sm text-gray-700">
                            TOTAUX :
                        </td>
                        <!-- Totaux N-1 -->
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-gray-600">
                            @php
                                $totalDebitN1 = collect($balances)->sum('solde_debit_n1');
                                $totalCreditN1 = collect($balances)->sum('solde_credit_n1');
                            @endphp
                            {{ number_format($totalDebitN1, 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-gray-600">
                            {{ number_format($totalCreditN1, 0, ',', ' ') }} FCFA
                        </td>
                        <!-- Totaux actuels -->
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-red-600">
                            {{ number_format($stats['total_debit'], 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-green-600">
                            {{ number_format($stats['total_credit'], 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm 
                            {{ $stats['total_solde'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format(abs($stats['total_solde']), 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" colspan="4" class="px-6 py-1.5 text-sm text-gray-600">
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
    
    <!-- Vue détaillée d'une balance -->
    @if($viewMode === 'details' && $selectedBalance)
    <div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden mb-6">
        <!-- En-tête -->
        <div class="px-6 py-4 border-b bg-gray-50">
            <div class="flex justify-between items-center">
                <div>
                    <h2 style="font-size: 11px;" class="text-xl font-bold text-gray-900">
                        <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                        Détails du compte {{ $selectedBalance['code'] }}
                    </h2>
                    <p style="font-size: 11px;" class="text-gray-600 mt-1">
                        {{ $selectedBalance['intitule'] }}
                        @if($type === 'new')
                            <span class="text-sm text-gray-500 ml-2">
                                ({{ $selectedBalance['old_accounts_count'] ?? 0 }} ancien(s) compte(s))
                            </span>
                        @endif
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Statut solde -->
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
                    
                    <!-- Bouton retour -->
                    <button style="font-size: 11px;" wire:click="backToList"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Corps -->
        <div class="p-6">
            <!-- Résumé -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Total débit -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Total Débit</p>
                            <p style="font-size: 11px;" class="font-bold text-red-600 mt-1">
                                {{ number_format($selectedBalance['total_debit'], 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-arrow-down text-xl text-red-600"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total crédit -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Total Crédit</p>
                            <p style="font-size: 11px;" class="font-bold text-green-600 mt-1">
                                {{ number_format($selectedBalance['total_credit'], 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-arrow-up text-xl text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Nombre écritures -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Mouvements</p>
                            <p style="font-size: 11px;" class="font-bold text-blue-600 mt-1">
                                {{ $selectedBalance['ecritures_count'] }}
                            </p>
                            <p class="text-xs text-gray-500 mt-1">écritures</p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-exchange-alt text-xl text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                
                
                <!-- Période -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-sm text-gray-500">Période analysée</p>
                            <p style="font-size: 11px;" class="text-sm font-bold text-gray-900 mt-1">
                                {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }}
                            </p>
                            <p style="font-size: 11px;" class="text-sm font-bold text-gray-900">
                                {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
                            </p>
                        </div>
                        <div style="font-size: 11px;" class="p-2 bg-yellow-100 rounded-lg">
                            <i class="far fa-calendar text-xl text-yellow-600"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Dans les totaux (~ligne 450) -->
                <tfoot class="bg-gray-50 font-bold">
    <tr>
        <td style="font-size: 11px;" colspan="2" class="px-6 py-1.5 text-right text-sm text-gray-700">
            TOTAUX :
        </td>
        <!-- Totaux N-1 -->
        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-gray-600">
            @php
                $totalDebitN1 = collect($balances)->sum('solde_debit_n1');
                $totalCreditN1 = collect($balances)->sum('solde_credit_n1');
            @endphp
            {{ number_format($totalDebitN1, 0, ',', ' ') }} FCFA
        </td>
        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-gray-600">
            {{ number_format($totalCreditN1, 0, ',', ' ') }} FCFA
        </td>
        <!-- Totaux actuels -->
        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-red-600">
            {{ number_format($stats['total_debit'], 0, ',', ' ') }} FCFA
        </td>
        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm text-green-600">
            {{ number_format($stats['total_credit'], 0, ',', ' ') }} FCFA
        </td>
        <td style="font-size: 11px;" class="px-6 py-1.5 whitespace-nowrap text-sm 
            {{ $stats['total_solde'] > 0 ? 'text-red-600' : 'text-green-600' }}">
            {{ number_format(abs($stats['total_solde']), 0, ',', ' ') }} FCFA
        </td>
        <td style="font-size: 11px;" colspan="{{ $type === 'old' ? '3' : '4' }}" class="px-6 py-1.5 text-sm text-gray-600">
            {{ $stats['total_solde'] > 0 ? 'Débiteur' : 'Créditeur' }}
        </td>
    </tr>
</tfoot>
            </div>
            
            <!-- Anciens comptes (pour SYCEBNL) -->
            @if($type === 'new' && isset($selectedBalance['old_accounts']))
            <div class="mb-8">
                <h3 style="font-size: 11px;" class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-sitemap mr-2 text-orange-600"></i>
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
                                <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Solde Débit N-1
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Solde Crédit N-1
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
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Mouvements
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
                                            {{ Str::limit($oldAccount['intitule'], 30) }}
                                        </div>
                                    </td>
                                    <!-- Solde Débit N-1 -->
                                    <td class="px-6 py-1.5 whitespace-nowrap">
                                        <div style="font-size: 11px;" class="text-sm text-gray-700">
                                            {{ number_format($balance['solde_debit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                    
                                    <!-- Solde Crédit N-1 -->
                                    <td class="px-6 py-1.5 whitespace-nowrap">
                                        <div style="font-size: 11px;" class="text-sm text-gray-700">
                                            {{ number_format($balance['solde_credit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                    
                                    <!-- Solde Débit N-1 -->
                                    <td class="px-6 py-1.5 whitespace-nowrap">
                                        <div style="font-size: 11px;" class="text-sm text-gray-700">
                                            {{ number_format($oldAccount['solde_debit_n1'] ?? 0, 0, ',', ' ') }} FCFA
                                        </div>
                                    </td>
                                    
                                    <!-- Solde Crédit N-1 -->
                                    <td class="px-6 py-1.5 whitespace-nowrap">
                                        <div style="font-size: 11px;" class="text-sm text-gray-700">
                                            {{ number_format($oldAccount['solde_credit_n1'] ?? 0, 0, ',', ' ') }} FCFA
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
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $oldAccount['count'] }} écriture(s)
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
            
            <!-- Tableau des écritures -->
            <div>
                <h3 style="font-size: 11px;" class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-list-alt mr-2 text-blue-600"></i>
                    Écritures détaillées
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Journal
                                </th>
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Pièce
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
                                @if($type === 'new')
                                <th style="font-size: 11px;" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ancien compte
                                </th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($selectedBalance['ecritures'] as $ecriture)
                                <tr class="hover:bg-gray-50">
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $ecriture->date_ecriture->format('d/m/Y') }}
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        @if($ecriture->journal_code)
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $ecriture->journal_code }}
                                            </span>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($ecriture->piece)
                                            <span class="font-mono">{{ $ecriture->piece }}</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 text-sm text-gray-900">
                                        {{ $ecriture->libelle ?? '-' }}
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                        @if($ecriture->debit > 0)
                                            {{ number_format($ecriture->debit, 0, ',', ' ') }} FCFA
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap text-sm text-green-600">
                                        @if($ecriture->credit > 0)
                                            {{ number_format($ecriture->credit, 0, ',', ' ') }} FCFA
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    @if($type === 'new')
                                    <td style="font-size: 11px;" class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <span class="font-mono text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded">
                                                {{ $ecriture->oldAccount->code ?? 'N/A' }}
                                            </span>
                                        </div>
                                    </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de génération -->
    <div x-show="showGenerateModal" 
         x-cloak
         x-transition.opacity
         class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="px-6 py-4 border-b flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-calculator mr-2 text-purple-600"></i>
                    Générer des balances persistantes
                </h3>
                <button @click="showGenerateModal = false" 
                        class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="p-6">
                <form wire:submit.prevent="generateBalances">
                    <!-- Type de balances -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-cog mr-2"></i>
                            Type de balances à générer
                        </label>
                        <div class="grid grid-cols-3 gap-4">
                            <label class="relative">
                                <input type="radio" 
                                       wire:model="generateType"
                                       value="old"
                                       class="sr-only peer">
                                <div class="flex flex-col items-center justify-center p-4 border-2 rounded-lg cursor-pointer
                                           peer-checked:border-blue-500 peer-checked:bg-blue-50 hover:bg-gray-50">
                                    <i class="fas fa-database text-3xl text-orange-500 mb-2"></i>
                                    <span class="font-medium">Anciens comptes</span>
                                    <span class="text-xs text-gray-500 mt-1">Comptes entité</span>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" 
                                       wire:model="generateType"
                                       value="new"
                                       class="sr-only peer">
                                <div class="flex flex-col items-center justify-center p-4 border-2 rounded-lg cursor-pointer
                                           peer-checked:border-green-500 peer-checked:bg-green-50 hover:bg-gray-50">
                                    <i class="fas fa-database text-3xl text-green-500 mb-2"></i>
                                    <span class="font-medium">Nouveaux comptes</span>
                                    <span class="text-xs text-gray-500 mt-1">Comptes SYCEBNL</span>
                                </div>
                            </label>
                            
                            <label class="relative">
                                <input type="radio" 
                                       wire:model="generateType"
                                       value="both"
                                       class="sr-only peer">
                                <div class="flex flex-col items-center justify-center p-4 border-2 rounded-lg cursor-pointer
                                           peer-checked:border-purple-500 peer-checked:bg-purple-50 hover:bg-gray-50">
                                    <i class="fas fa-layer-group text-3xl text-purple-500 mb-2"></i>
                                    <span class="font-medium">Les deux</span>
                                    <span class="text-xs text-gray-500 mt-1">Tous les comptes</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Période -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="far fa-calendar mr-1"></i>
                                Date de début
                            </label>
                            <input type="date" 
                                   wire:model="generateFromDate"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="far fa-calendar mr-1"></i>
                                Date de fin
                            </label>
                            <input type="date" 
                                   wire:model="generateToDate"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-calendar-alt mr-1"></i>
                                Exercice
                            </label>
                            <input type="number" 
                                   wire:model="generateExercice"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   min="2000" max="{{ date('Y') + 5 }}">
                        </div>
                    </div>
                    
                    <!-- Informations -->
                    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h4 class="text-sm font-medium text-yellow-800 mb-2 flex items-center">
                            <i class="fas fa-info-circle mr-2"></i>
                            Information importante
                        </h4>
                        <p class="text-sm text-yellow-700">
                            La génération créera des enregistrements persistants dans la base de données.
                            Ces balances pourront être consultées et exportées ultérieurement sans recalcul.
                        </p>
                    </div>
                    
                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                        <button type="button"
                                @click="showGenerateModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition">
                            Annuler
                        </button>
                        
                        <button type="submit"
                                class="px-4 py-2 bg-purple-600 text-white hover:bg-purple-700 rounded-lg transition shadow-sm flex items-center">
                            <i class="fas fa-calculator mr-2"></i>
                            Générer les balances
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Scroll vers les détails
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('scroll-to-details', () => {
                const element = document.getElementById('balance-details');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
            
            Livewire.on('open-generate-modal', () => {
                document.querySelector('[x-show="showGenerateModal"]').style.display = 'flex';
            });
        });
        
        // Fermer le modal avec ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                Alpine.store('showGenerateModal', false);
            }
        });
    </script>
    
    <style>
        /* Styles pour les montants */
        .amount-debit { color: #dc2626; }
        .amount-credit { color: #16a34a; }
        
        /* Animation pour la génération */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .generating {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
    @endpush
</div>