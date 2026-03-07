<div class="p-2 mt-12" x-data="{
    viewMode: @entangle('viewMode'),
    selectedBalance: @entangle('selectedBalance'),
    stats: @entangle('stats')
}">
    <!-- Header -->
    <div class="mb-2">
        <div class="flex justify-between items-start">
            <div>
                <h3 style="font-size: 12px;" class="text-lg font-semibold text-gray-900">
                    Balance des comptes SYCEBNL
                </h3>
                <p style="font-size: 12px;" class="text-gray-600 mt-1">
                    Solde des comptes mappés avec les anciens comptes
                </p>
            </div>
            
            <div class="flex items-center space-x-3">
                <span style="font-size: 12px;" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                    {{ $entreprise->nom }}
                </span>
                
                @if($viewMode === 'details' && $selectedBalance)
                    <button style="font-size: 12px;" @click="$wire.backToList()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center gap-2 text-sm font-medium">
                        <i class="fas fa-arrow-left"></i>
                        Retour à la liste
                    </button>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    @if($viewMode === 'table')
    <div class="bg-white rounded-lg shadow border p-6 mb-0">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Période -->
            <div class="col-span-2">
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
            
            <!-- Recherche -->
            <div>
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-search mr-1"></i>
                    Recherche
                </label>
                <input style="font-size: 12px;" type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Code ou libellé..."
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            
            <!-- Actions -->
            <div class="flex items-end">
                <button style="font-size: 12px;" wire:click="resetFilters"
                        class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                               flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </button>
            </div>
        </div>
    </div>
    
    <!-- SECTION DEBUG - À AJOUTER APRÈS LES FILTRES -->
    {{-- <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <h4 class="text-sm font-bold text-yellow-800 mb-3 flex items-center">
            <i class="fas fa-bug mr-2"></i>
            Informations de débogage
        </h4>
        
        @php
            $debugInfo = $this->debugInfo();
        @endphp
        
        <!-- Informations de base -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
            <div class="bg-white p-2 rounded border">
                <p class="text-xs text-gray-600">Comptes SYCEBNL</p>
                <p class="text-sm font-bold">{{ $debugInfo['total_comptes_sybcel'] }}</p>
            </div>
            <div class="bg-white p-2 rounded border">
                <p class="text-xs text-gray-600">Comptes mappés</p>
                <p class="text-sm font-bold">{{ $debugInfo['comptes_mappes'] }}</p>
            </div>
            <div class="bg-white p-2 rounded border">
                <p class="text-xs text-gray-600">Mappings</p>
                <p class="text-sm font-bold">{{ $debugInfo['total_mappings'] }}</p>
            </div>
            <div class="bg-white p-2 rounded border">
                <p class="text-xs text-gray-600">Balances</p>
                <p class="text-sm font-bold">{{ $debugInfo['balances_calculees'] }}</p>
            </div>
        </div>
        
        <!-- Test d'un compte spécifique -->
        @if(!empty($debugInfo['test_compte']))
        <div class="mb-4 p-3 bg-yellow-100 rounded border border-yellow-300">
            <p class="text-xs font-bold text-yellow-800 mb-2">
                Test du compte: {{ $debugInfo['test_compte']['compte_sybcel'] }}
            </p>
            
            <p class="text-xs text-yellow-700 mb-1">
                Anciens comptes mappés: {{ implode(', ', $debugInfo['test_compte']['anciens_comptes_details'] ?? []) }}
            </p>
            
            <div class="grid grid-cols-3 gap-2 mt-2">
                <div class="bg-white p-2 rounded">
                    <p class="text-xs text-gray-600">Méthode 1 (directe)</p>
                    <p class="text-xs font-bold">{{ $debugInfo['test_compte']['method1_direct']['count'] }} écritures</p>
                </div>
                <div class="bg-white p-2 rounded">
                    <p class="text-xs text-gray-600">Méthode 2 (anciens validés)</p>
                    <p class="text-xs font-bold">{{ $debugInfo['test_compte']['method2_anciens_valide']['count'] }} écritures</p>
                </div>
                <div class="bg-white p-2 rounded">
                    <p class="text-xs text-gray-600">Méthode 3 (anciens tous)</p>
                    <p class="text-xs font-bold">{{ $debugInfo['test_compte']['method3_anciens_tout']['count'] }} écritures</p>
                </div>
            </div>
            
            @if($debugInfo['test_compte']['method3_anciens_tout']['count'] > 0)
            <div class="mt-2 p-2 bg-green-50 rounded border border-green-200">
                <p class="text-xs text-green-700 font-bold">✓ Données trouvées !</p>
                <p class="text-xs text-green-600">
                    Débit: {{ number_format($debugInfo['test_compte']['method3_anciens_tout']['debit'], 0, ',', ' ') }} 
                    | Crédit: {{ number_format($debugInfo['test_compte']['method3_anciens_tout']['credit'], 0, ',', ' ') }}
                </p>
            </div>
            @else
            <div class="mt-2 p-2 bg-red-50 rounded border border-red-200">
                <p class="text-xs text-red-700 font-bold">✗ Aucune écriture trouvée</p>
                <p class="text-xs text-red-600">Vérifiez les mappings et la période</p>
            </div>
            @endif
        </div>
        @endif
        
        <!-- Statistiques générales -->
        <div class="p-3 bg-blue-50 rounded border border-blue-200">
            <p class="text-xs font-bold text-blue-800 mb-2">Statistiques générales</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                <div>
                    <p class="text-xs text-blue-700">Écritures période</p>
                    <p class="text-xs font-bold">{{ $debugInfo['total_ecritures_periode'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-blue-700">Écritures validées</p>
                    <p class="text-xs font-bold">{{ $debugInfo['ecritures_valides'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-blue-700">Avec new_account_id</p>
                    <p class="text-xs font-bold">{{ $debugInfo['ecritures_avec_new_account'] }}</p>
                </div>
                <div>
                    <p class="text-xs text-blue-700">Période</p>
                    <p class="text-xs font-bold">{{ $debugInfo['periode'] }}</p>
                </div>
            </div>
        </div>
        
        <!-- Actions de débogage -->
        <div class="mt-3 flex gap-2">
            <button wire:click="$refresh" 
                    class="text-xs px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                <i class="fas fa-sync-alt mr-1"></i> Actualiser
            </button>
            
            <button onclick="console.log({{ json_encode($debugInfo) }})" 
                    class="text-xs px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600">
                <i class="fas fa-code mr-1"></i> Voir en console
            </button>
        </div>
    </div> --}}
    
    <!-- Après le bouton "Réinitialiser", ajoutez : -->
    <div class="mt-0">
        <button wire:click="syncAllMappings" 
                wire:confirm="Êtes-vous sûr de vouloir synchroniser tous les mappings ?"
                class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 
                       flex items-center gap-2 text-sm">
            <i class="fas fa-sync-alt"></i>
            Synchroniser tous les mappings
        </button>
        <p class="text-xs text-gray-500 mt-1">
            Met à jour les écritures avec les mappings actuels
        </p>
    </div>
    
    <!-- Table des balances -->
    <div class="bg-white rounded-lg shadow border overflow-hidden">
        <div class="px-6 py-3 border-b bg-gray-50">
            <div class="flex justify-between items-center">
                <h3 style="font-size: 12px;" class="font-semibold text-gray-900">
                    Liste des comptes SYCEBNL mappés
                </h3>
                <div class="text-sm text-gray-600">
                    {{ $stats['total_ecritures'] }} écritures au total
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Code SYCEBNL
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Libellé
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Débit
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Total Crédit
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Solde
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mouvements
                        </th>
                        <th style="font-size: 11px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($balances as $balance)
                        @php
                            $isDebiteur = $balance['solde'] > 0;
                            $soldeAbsolu = abs($balance['solde']);
                        @endphp
                        
                        <tr class="hover:bg-gray-50">
                            <!-- Code SYCEBNL -->
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div style="font-size: 11px;" class="font-mono font-bold text-blue-700">
                                    {{ $balance['code'] }}
                                </div>
                            </td>
                            
                            <!-- Libellé -->
                            <td class="px-6 py-2">
                                <div style="font-size: 11px;" class="font-medium text-gray-900">
                                    {{ Str::limit($balance['intitule'], 30) }}
                                </div>
                                @if($balance['old_accounts_count'] > 0)
                                    <div style="font-size: 10px;" class="text-gray-500 mt-1">
                                        {{ $balance['old_accounts_count'] }} ancien(s) compte(s)
                                    </div>
                                @endif
                            </td>
                            
                            <!-- Total Débit -->
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-red-600 font-medium">
                                    {{ number_format($balance['total_debit'], 0, ',', ' ') }} FCFA
                                </div>
                            </td>
                            
                            <!-- Total Crédit -->
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-green-600 font-medium">
                                    {{ number_format($balance['total_credit'], 0, ',', ' ') }} FCFA
                                </div>
                            </td>
                            
                            <!-- Solde (toujours positif) -->
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <div style="font-size: 11px;" class="font-bold text-blue-600">
                                        {{ number_format($soldeAbsolu, 0, ',', ' ') }} FCFA
                                    </div>
                                    <span style="font-size: 10px;" class="px-2 py-1 rounded {{ $isDebiteur ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                        {{ $isDebiteur ? 'Débiteur' : 'Créditeur' }}
                                    </span>
                                </div>
                            </td>
                            
                            <!-- Mouvements -->
                            <td class="px-6 py-2 whitespace-nowrap">
                                <div style="font-size: 11px;" class="text-gray-700">
                                    {{ $balance['ecritures_count'] }} écriture(s)
                                </div>
                            </td>
                            
                            <!-- Actions -->
                            <td class="px-6 py-2 whitespace-nowrap">
                                @if($balance['ecritures_count'] > 0)
                                    <button style="font-size: 11px;" wire:click="showDetails({{ $balance['id'] }})"
                                            class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                        <i class="fas fa-eye"></i>
                                        Détails
                                    </button>
                                @else
                                    <span style="font-size: 10px;" class="text-gray-400">Aucun mouvement</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-scale-balanced text-3xl mb-3"></i>
                                    <p style="font-size: 12px;" class="font-medium mb-1">Aucun compte trouvé</p>
                                    <p style="font-size: 11px;" class="text-gray-500">
                                        Vérifiez vos filtres ou créez des mappings de comptes
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                <!-- Totaux -->
                @if(count($balances) > 0)
                <tfoot class="bg-gray-50 font-bold border-t">
                    <tr>
                        <td colspan="2" style="font-size: 11px;" class="px-6 py-2 text-right text-gray-700">
                            TOTAUX GÉNÉRAUX
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-2 text-red-600">
                            {{ number_format($stats['total_debit'], 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-2 text-green-600">
                            {{ number_format($stats['total_credit'], 0, ',', ' ') }} FCFA
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-2 text-blue-600">
                            {{ number_format(abs($stats['total_solde']), 0, ',', ' ') }} FCFA
                            <span class="text-xs font-normal ml-2">
                                ({{ $stats['total_solde'] > 0 ? 'Débiteur' : 'Créditeur' }})
                            </span>
                        </td>
                        <td style="font-size: 11px;" class="px-6 py-2 text-gray-700">
                            {{ $stats['total_ecritures'] }} écritures
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        
    </div>
        
    <!-- Résumé -->
    <div class="mt-4 grid grid-cols-3 gap-4">
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-blue-600 font-medium">Comptes</p>
                    <p style="font-size: 11px;" class="text-blue-800 font-bold mt-1">
                        {{ $stats['total_comptes'] }}
                    </p>
                </div>
                <i class="fas fa-list-ol text-blue-500"></i>
            </div>
        </div>
        
        <div class="bg-red-50 border border-red-200 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-red-600 font-medium">Total Débit</p>
                    <p style="font-size: 11px;" class="text-red-800 font-bold mt-1">
                        {{ number_format($stats['total_debit'], 0, ',', ' ') }} FCFA
                    </p>
                </div>
                <i class="fas fa-arrow-down text-red-500"></i>
            </div>
        </div>
        
        <div class="bg-green-50 border border-green-200 rounded-lg p-3">
            <div class="flex items-center justify-between">
                <div>
                    <p style="font-size: 11px;" class="text-green-600 font-medium">Total Crédit</p>
                    <p style="font-size: 11px;" class="text-green-800 font-bold mt-1">
                        {{ number_format($stats['total_credit'], 0, ',', ' ') }} FCFA
                    </p>
                </div>
                <i class="fas fa-arrow-up text-green-500"></i>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Vue détaillée -->
    @if($viewMode === 'details' && $selectedBalance)
    <div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden">
        <!-- En-tête -->
        <div class="px-6 py-4 border-b bg-gray-50">
            <div class="flex justify-between items-center">
                <div>
                    <h2 style="font-size: 12px;" class="font-bold text-gray-900">
                        <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                        Détails du compte {{ $selectedBalance['code'] }}
                    </h2>
                    <p style="font-size: 11px;" class="text-gray-600 mt-1">
                        {{ $selectedBalance['intitule'] }}
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <!-- Statut solde -->
                    <div class="px-4 py-2 rounded-lg bg-blue-100">
                        <div style="font-size: 11px;" class="text-blue-800 font-medium">
                            Solde : {{ number_format(abs($selectedBalance['solde']), 0, ',', ' ') }} FCFA
                        </div>
                        <div style="font-size: 10px;" class="text-blue-700">
                            {{ $selectedBalance['solde'] > 0 ? 'Débiteur' : 'Créditeur' }}
                        </div>
                    </div>
                    
                    <button style="font-size: 11px;" @click="$wire.backToList()"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center gap-2">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Résumé -->
        <div class="p-6 border-b">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <!-- Total débit -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-gray-500">Total Débit</p>
                            <p style="font-size: 11px;" class="text-red-600 font-bold mt-1">
                                {{ number_format($selectedBalance['total_debit'], 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div class="p-2 bg-red-100 rounded-lg">
                            <i class="fas fa-arrow-down text-red-600"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Total crédit -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-gray-500">Total Crédit</p>
                            <p style="font-size: 11px;" class="text-green-600 font-bold mt-1">
                                {{ number_format($selectedBalance['total_credit'], 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        <div class="p-2 bg-green-100 rounded-lg">
                            <i class="fas fa-arrow-up text-green-600"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Solde -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-gray-500">Solde</p>
                            <p style="font-size: 11px;" class="text-blue-600 font-bold mt-1">
                                {{ number_format(abs($selectedBalance['solde']), 0, ',', ' ') }} FCFA
                            </p>
                            <p style="font-size: 10px;" class="text-gray-500">
                                {{ $selectedBalance['solde'] > 0 ? 'Débiteur' : 'Créditeur' }}
                            </p>
                        </div>
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <i class="fas fa-balance-scale text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Mouvements -->
                <div class="bg-white border rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p style="font-size: 11px;" class="text-gray-500">Mouvements</p>
                            <p style="font-size: 11px;" class="text-purple-600 font-bold mt-1">
                                {{ $selectedBalance['ecritures_count'] }}
                            </p>
                            <p style="font-size: 10px;" class="text-gray-500">écritures</p>
                        </div>
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <i class="fas fa-exchange-alt text-purple-600"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tableau des écritures -->
        <div class="p-6">
            <h3 style="font-size: 12px;" class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-list-alt mr-2 text-blue-600"></i>
                Écritures détaillées
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
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
                        @php
                            $currentOldAccount = null;
                        @endphp
                        
                        @foreach($selectedBalance['old_accounts_data'] as $oldAccountData)
                            <!-- Écritures pour cet ancien compte -->
                            @foreach($oldAccountData['ecritures'] as $ecriture)
                            <tr class="hover:bg-gray-50 border-b border-gray-100">
                                <!-- Date -->
                                <td class="px-2 py-0.5 text-xs text-gray-700" style="font-size: 10px;">
                                    {{ $ecriture->date_ecriture->format('d/m/Y') }}
                                </td>
                                
                                <!-- N° de pièce -->
                                <td class="px-2 py-0.5 text-xs text-gray-700 font-mono" style="font-size: 10px;">
                                    {{ $ecriture->piece ?? '-' }}
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
                                    {{ $ecriture->oldAccount->code ?? 'N/A' }}
                                </td>
                                
                                <!-- Compte SYCEBNL -->
                                <td class="px-2 py-0.5 text-xs font-mono text-blue-600 font-medium" style="font-size: 10px;">
                                    {{ $selectedBalance['code'] }}
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
                            @if($selectedBalance['old_accounts_count'] > 1)
                            <tr class="bg-gray-50">
                                <td colspan="6" class="px-2 py-0.5 text-right text-xs font-medium text-gray-700" style="font-size: 10px;">
                                    Sous-total {{ $oldAccountData['code'] }}
                                </td>
                                <td class="px-2 py-0.5 text-right text-xs font-medium text-red-700" style="font-size: 10px;">
                                    {{ number_format($oldAccountData['debit'], 0, '', ' ') }}
                                </td>
                                <td class="px-2 py-0.5 text-right text-xs font-medium text-green-700" style="font-size: 10px;">
                                    {{ number_format($oldAccountData['credit'], 0, '', ' ') }}
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
                                {{ number_format($selectedBalance['total_debit'], 0, '', ' ') }}
                            </td>
                            <td class="px-2 py-0.5 text-right text-xs text-green-800" style="font-size: 11px;">
                                {{ number_format($selectedBalance['total_credit'], 0, '', ' ') }}
                            </td>
                        </tr>
                        
                        <!-- Ligne de solde -->
                        @php
                            $solde = $selectedBalance['solde'];
                            $soldeAbs = abs($solde);
                        @endphp
                        
                        <tr class="bg-blue-50/50 border-b-2 border-blue-200">
                            <td colspan="6" class="px-2 py-0.5 text-right text-xs font-bold text-blue-800" style="font-size: 11px;">
                                Solde au {{ \Carbon\Carbon::parse($dateFin)->format('d-m-Y') }}
                            </td>
                            
                            @if($solde > 0)
                                <!-- Solde débiteur -> colonne débit -->
                                <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100" style="font-size: 11px;">
                                    {{ number_format($soldeAbs, 0, '', ' ') }}
                                </td>
                                <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                    -
                                </td>
                            @elseif($solde < 0)
                                <!-- Solde créditeur -> colonne crédit -->
                                <td class="px-2 py-0.5 text-right text-xs text-gray-400" style="font-size: 11px;">
                                    -
                                </td>
                                <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100" style="font-size: 11px;">
                                    {{ number_format($soldeAbs, 0, '', ' ') }}
                                </td>
                            @endif
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    
    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('scroll-to-details', () => {
                const element = document.getElementById('balance-details');
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>
    @endpush
</div>