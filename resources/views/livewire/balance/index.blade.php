<div>
    @if(session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    @if(session()->has('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <!-- Bouton de débogage (optionnel) -->
    @if($debugMode)
        <div class="mb-4 p-4 bg-gray-800 text-white rounded-lg overflow-auto max-h-96">
            <h4 class="font-bold mb-2">🔍 Informations de débogage</h4>
            <pre class="text-xs">{{ json_encode($debug, JSON_PRETTY_PRINT) }}</pre>
        </div>
    @endif

    @if($viewMode === 'details' && $selectedBalance)
        <!-- Vue des détails -->
        <div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden" style="margin-top: 50px;">
            <!-- En-tête -->
            <div class="px-6 py-4 border-b bg-gray-50">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 style="font-size: 14px;" class="font-bold text-gray-900">
                            <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                            Détail du compte {{ $selectedBalance['code'] }}
                        </h2>
                        <p style="font-size: 13px;" class="text-gray-600 mt-1">
                            {{ $selectedBalance['intitule'] }}
                        </p>
                        @if(isset($selectedBalance['has_children']) && $selectedBalance['has_children'])
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 mt-2">
                                <i class="fas fa-sitemap mr-1"></i>
                                Compte principal avec {{ $selectedBalance['children_count'] ?? 0 }} compte auxiliaire
                            </span>
                        @endif
                    </div>
                    
                    <button wire:click="backToList"
                            class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                                   flex items-center gap-2 text-sm">
                        <i class="fas fa-arrow-left"></i>
                        Retour
                    </button>
                </div>
            </div>
            
            <!-- Détail par ancien compte -->
            <div class="p-6">
                <h3 style="font-size: 13px;" class="font-semibold text-gray-900 mb-4">
                    <i class="fas fa-list-alt mr-2 text-blue-600"></i>
                    Détail par compte entité
                </h3>
                
                <!-- Dans la vue détail, afficher les écritures comme dans l'image -->
                <!-- Dans la vue détail, afficher les écritures comme dans l'image -->
            <div class="overflow-x-auto">
                <table class="min-w-full border-collapse">
                    <thead class="bg-gray-800 text-white">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs">Date</th>
                            <th class="px-4 py-2 text-left text-xs">Journal</th>
                            <th class="px-4 py-2 text-left text-xs">Compte</th>
                            <th class="px-4 py-2 text-left text-xs">Libellé</th>
                            <th class="px-4 py-2 text-right text-xs">Débit</th>
                            <th class="px-4 py-2 text-right text-xs">Crédit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $totalDebit = 0;
                            $totalCredit = 0;
                            $nombreEcritures = 0;
                        @endphp
                        
                        @foreach($selectedBalance['ecritures'] as $ecriture)
                            @php
                                $totalDebit += $ecriture->debit;
                                $totalCredit += $ecriture->credit;
                                $nombreEcritures++;
                            @endphp
                            <tr class="hover:bg-gray-50 border-b border-gray-100">
                                <td class="px-4 py-2 text-xs">
                                    {{ \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    {{ $ecriture->journal_code ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-xs font-mono">
                                    {{ $ecriture->oldAccount->code ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    {{ $ecriture->libelle ?? '-' }}
                                </td>
                                <td class="px-4 py-2 text-right text-xs text-red-600">
                                    {{ $ecriture->debit > 0 ? number_format($ecriture->debit, 0, ',', ' ') : '-' }}
                                </td>
                                <td class="px-4 py-2 text-right text-xs text-green-600">
                                    {{ $ecriture->credit > 0 ? number_format($ecriture->credit, 0, ',', ' ') : '-' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    
                    <!-- Pied de tableau style "comme dans l'image" -->
                    <tfoot>
                        <!-- Ligne TOTAL avec le nombre d'écritures -->
                        <tr class="bg-gray-100 border-t-2 border-gray-300 font-bold">
                            <td colspan="4" class="px-4 py-2 text-xs text-gray-600">
                                TOTAL ({{ $nombreEcritures }} écrit.)
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-bold text-red-600">
                                {{ number_format($totalDebit, 0, ',', ' ') }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-bold text-green-600">
                                {{ number_format($totalCredit, 0, ',', ' ') }}
                            </td>
                        </tr>
                        
                        @php
                            $solde = $totalDebit - $totalCredit;
                        @endphp
                        
                        <!-- Ligne SOLDE -->
                        @if($solde != 0)
                            <tr class="bg-blue-50 border-t border-blue-200 font-bold">
                                <td colspan="4" class="px-4 py-2 text-xs text-gray-700">
                                    @if($solde > 0)
                                        SOLDE DÉBITEUR
                                    @else
                                        SOLDE CRÉDITEUR
                                    @endif
                                </td>
                                
                                @if($solde > 0)
                                    <!-- Solde débiteur dans la colonne DÉBIT -->
                                    <td class="px-4 py-2 text-right text-xs font-bold text-red-600">
                                        {{ number_format(abs($solde), 0, ',', ' ') }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs text-gray-400">
                                        -
                                    </td>
                                @else
                                    <!-- Solde créditeur dans la colonne CRÉDIT -->
                                    <td class="px-4 py-2 text-right text-xs text-gray-400">
                                        -
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs font-bold text-green-600">
                                        {{ number_format(abs($solde), 0, ',', ' ') }}
                                    </td>
                                @endif
                            </tr>
                        @endif
                        
                        <!-- Optionnel : ligne d'équilibre -->
                        <tr class="text-[10px]">
                            <td colspan="6" class="px-4 py-1 text-right text-gray-400">
                                @if($totalDebit == $totalCredit)
                                    <span class="text-green-600">✓ Équilibre</span>
                                @else
                                    <span class="text-orange-500">⚠ Différence : {{ number_format(abs($solde), 0, ',', ' ') }}</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Légende -->
            <div class="mt-4 flex justify-end gap-4 text-[10px] text-gray-500">
                <span><span class="text-red-600 font-bold">D</span> = Débiteur</span>
                <span><span class="text-green-600 font-bold">C</span> = Créditeur</span>
                <span><span class="text-gray-600">{{ $nombreEcritures ?? 0 }}</span> écriture(s)</span>
            </div>
            </div>
            
            <!-- Section des sous-comptes si existants -->
            @if(isset($selectedBalance['children_data']) && count($selectedBalance['children_data']) > 0)
            <div class="p-6 border-t">
                <h3 style="font-size: 13px;" class="font-semibold text-gray-900 mb-4">
                    <i class="fas fa-sitemap mr-2 text-indigo-600"></i>
                    Détail par compte auxiliaire
                </h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead class="bg-indigo-800 text-white">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs">Compte auxiliaire</th>
                                <th class="px-4 py-2 text-right text-xs">Débit</th>
                                <th class="px-4 py-2 text-right text-xs">Crédit</th>
                                <th class="px-4 py-2 text-right text-xs">Solde</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @foreach($selectedBalance['children_data'] as $child)
                                <tr class="hover:bg-gray-50 border-b border-gray-100">
                                    <td class="px-4 py-2 text-xs font-mono">
                                        <span class="font-medium text-indigo-700">{{ $child['code'] }}</span>
                                        <span class="text-gray-500 text-[10px] block">{{ Str::limit($child['intitule'], 40) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs {{ $child['debit'] > 0 ? 'font-medium text-red-600' : 'text-gray-400' }}">
                                        {{ $child['debit'] > 0 ? number_format($child['debit'], 0, ',', ' ') : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs {{ $child['credit'] > 0 ? 'font-medium text-green-600' : 'text-gray-400' }}">
                                        {{ $child['credit'] > 0 ? number_format($child['credit'], 0, ',', ' ') : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs font-medium {{ $child['solde'] > 0 ? 'text-red-600' : ($child['solde'] < 0 ? 'text-green-600' : 'text-gray-500') }}">
                                        {{ $child['solde'] != 0 ? number_format(abs($child['solde']), 0, ',', ' ') : '-' }}
                                        @if($child['solde'] != 0)
                                            <span class="text-[8px] ml-1">{{ $child['solde'] > 0 ? 'D' : 'C' }}</span>
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
    @else
        <!-- Balance à 4 colonnes (vue normale) -->
        <div>
            <!-- Bouton de contrôle du mode debug -->
            <div class="mb-4 flex justify-between items-center">
                <div class="flex gap-2">
                    <button wire:click="toggleDebug" 
                            class="px-3 py-1 text-xs {{ $debugMode ? 'bg-red-600' : 'bg-gray-600' }} text-white rounded">
                        <i class="fas fa-bug mr-1"></i>
                        {{ $debugMode ? 'Désactiver' : 'Activer' }} mode debug
                    </button>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border overflow-hidden">
                <div class="px-6 py-3 border-b bg-gray-50">
                    <div class="flex justify-between items-center">
                        <h3 style="font-size: 13px;" class="font-semibold text-gray-900">
                            Balance à 4 colonnes
                        </h3>
                        <div class="text-sm text-gray-600">
                            {{ $stats['total_ecritures'] }} écritures au total
                        </div>
                    </div>
                </div>
                
                <!-- Barre d'outils -->
            {{--    <div class="px-6 py-3 border-b bg-gray-100 flex flex-wrap gap-2 items-center">
                    <a href="{{ route('balance.tiers') }}" 
                       class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        Balance des Tiers
                    </a>
                    
                    <!-- Bouton Export Excel -->
                    <button wire:click="exportExcel" 
                            wire:loading.attr="disabled"
                            wire:target="exportExcel"
                            class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 flex items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="exportExcel">
                            <i class="fas fa-file-excel"></i>
                        </span>
                        <span wire:loading wire:target="exportExcel">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                        <span>Export Excel</span>
                    </button>
                    
                    <!-- Bouton Imprimer -->
                    <button onclick="imprimerBalance()" 
                            class="px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Imprimer
                    </button>
                </div> --}}
                <!-- Barre d'outils -->
                <div class="px-6 py-3 border-b bg-gray-100 flex flex-wrap gap-2 items-center">
                    <!-- Sélecteur de type de balance -->
                    <div class="flex items-center gap-2 mr-4">
                        <span class="text-sm text-gray-700">Type:</span>
                        <select wire:model.live="balanceType" class="text-sm border-gray-300 rounded-md">
                            <option value="4colonnes">Balance à 4 colonnes</option>
                            <option value="6colonnes">Balance à 6 colonnes</option>
                        </select>
                    </div>
                    
                    <a href="{{ route('balance.tiers') }}" 
                       class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        Balance des Tiers
                    </a>
                    
                    <button wire:click="exportExcel" 
                            wire:loading.attr="disabled"
                            wire:target="exportExcel"
                            class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 flex items-center gap-2 disabled:opacity-50">
                        <span wire:loading.remove wire:target="exportExcel">
                            <i class="fas fa-file-excel"></i>
                        </span>
                        <span wire:loading wire:target="exportExcel">
                            <i class="fas fa-spinner fa-spin"></i>
                        </span>
                        <span>Export Excel</span>
                    </button>
                    
                    <button onclick="imprimerBalance()" 
                            class="px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 flex items-center gap-2">
                        <i class="fas fa-print"></i>
                        Imprimer
                    </button>
                </div>
                 
                <div class="overflow-x-auto" id="balance-table">
                    <table class="min-w-full divide-y divide-gray-200">
    <thead class="bg-gray-50">
        @if($balanceType === '4colonnes')
            <!-- En-tête pour 4 colonnes -->
            <tr>
                <div class="print-only" style="padding:8px 0 6px; border-bottom:2px solid #2c3e50; margin-bottom:6px;">
                    <div style="font-size:12pt; font-weight:bold; color:#2c3e50;">Balance</div>
                    <div style="font-size:8pt; color:#555; margin-top:3px;">
                        <strong>{{ $entreprise->nom }}</strong>
                        — {{ $entreprise->code }}
                        &nbsp;|&nbsp; Période : {{ $dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : '—' }}
                        au {{ $dateFin ? \Carbon\Carbon::parse($dateFin)->format('d/m/Y') : '—' }}
                        &nbsp;|&nbsp; Imprimé le {{ now()->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </tr>
            <tr>
                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Code SYCEBNL
                </th>
                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Intitulé
                </th>
                <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Mouvement
                </th>
                <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Solde
                </th>
                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider no-print">
                    Actions
                </th>
            </tr>
            <tr class="bg-gray-50">
                <th></th>
                <th></th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Débit
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Crédit
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Débiteur
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Créditeur
                </th>
                <th class="no-print"></th>
            </tr>
        @else
            <!-- En-tête pour 6 colonnes -->
            <tr>
                <div class="print-only" style="padding:8px 0 6px; border-bottom:2px solid #2c3e50; margin-bottom:6px;">
                    <div style="font-size:12pt; font-weight:bold; color:#2c3e50;">Balance</div>
                    <div style="font-size:8pt; color:#555; margin-top:3px;">
                        <strong>{{ $entreprise->nom }}</strong>
                        — {{ $entreprise->code }}
                        &nbsp;|&nbsp; Période : {{ $dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : '—' }}
                        au {{ $dateFin ? \Carbon\Carbon::parse($dateFin)->format('d/m/Y') : '—' }}
                        &nbsp;|&nbsp; Imprimé le {{ now()->format('d/m/Y à H:i') }}
                    </div>
                </div>
            </tr>
            <tr>
                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Compte SYCEBNL
                </th>
                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Intitulé
                </th>
                <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Solde d'ouverture
                </th>
                <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Mouvement
                </th>
                <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Solde clôture
                </th>
                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider no-print">
                    Actions
                </th>
            </tr>
            <tr class="bg-gray-50">
                <th></th>
                <th></th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Débit (A)
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Crédit (B)
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Débit (C)
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Crédit (D)
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Débiteur
                </th>
                <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                    Créditeur
                </th>
                <th class="no-print"></th>
            </tr>
        @endif
    </thead>
    
    <tbody class="bg-white divide-y divide-gray-200">
        @forelse($balances as $balance)
            @if($balanceType === '4colonnes')
                {{-- Code pour 4 colonnes --}}
                @php
                    $solde = $balance['solde'];
                    $isDebiteur = $solde > 0;
                    $soldeAbsolu = abs($solde);
                    
                    $hasChildren = isset($balance['has_children']) && $balance['has_children'];
                    $childrenCount = $balance['children_count'] ?? 0;
                    $oldAccountsCount = isset($balance['old_accounts_data']) ? count($balance['old_accounts_data']) : 0;
                @endphp
                
                @if($balance['total_debit'] !== 0 || $balance['total_credit'] !== 0 || $soldeAbsolu !== 0)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-2 whitespace-nowrap border-x">
                        <div style="font-size: 13px;" class="font-mono font-bold text-blue-700">
                            {{ $balance['code'] }}
                        </div>
                        @if($hasChildren)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1 print:hidden">
                                <i class="fas fa-sitemap mr-1"></i> Principal
                            </span>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 border-x">
                        <div style="font-size: 13px;" class="font-medium text-gray-900">
                            {{ Str::limit($balance['intitule'], 30) }}
                        </div>
                        
                        @if($hasChildren && $childrenCount > 0)
                            <div style="font-size: 10px;" class="text-blue-600 mt-1 print:hidden">
                                <i class="fas fa-sitemap mr-1"></i>
                                {{ $childrenCount }} compte auxiliaire
                            </div>
                        @endif
                        
                        @if($oldAccountsCount > 0 && !$hasChildren)
                            <div style="font-size: 10px;" class="text-gray-500 mt-1 print:hidden">
                                -
                            </div>
                        @endif
                        
                        @if($hasChildren && $oldAccountsCount > 0)
                            <div style="font-size: 10px;" class="text-gray-500 mt-1 print:hidden">
                                
                                -
                            </div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($balance['total_debit'] > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($balance['total_debit'], 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($balance['total_credit'] > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($balance['total_credit'], 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($isDebiteur)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($soldeAbsolu, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if(!$isDebiteur && $soldeAbsolu > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($soldeAbsolu, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 whitespace-nowrap border-x no-print">
                        @if($balance['ecritures_count'] > 0)
                            <button style="font-size: 13px;" 
                                    wire:click="showDetails({{ $balance['id'] }})"
                                    type="button"
                                    class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <i class="fas fa-eye"></i>
                                Détails
                                @if($hasChildren)
                                    <span class="ml-1 text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded-full">
                                        {{ $childrenCount }}
                                    </span>
                                @endif
                            </button>
                        @else
                            <span style="font-size: 10px;" class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @endif
            @else
                {{-- Code pour 6 colonnes --}}
                @php
                    // DÉFINITION DES FONCTIONS UNE SEULE FOIS
                    if (!function_exists('arrondirBalance')) {
                        function arrondirBalance($montant, $precision = 2) {
                            return round(floatval($montant), $precision);
                        }
                    }
                    
                    if (!function_exists('sontEgauxBalance')) {
                        function sontEgauxBalance($a, $b, $tolerance = 0.01) {
                            return abs($a - $b) < $tolerance;
                        }
                    }
                    
                    // Initialiser les totaux pour 6 colonnes
                    static $totalOuvertureDebit6 = 0;
                    static $totalOuvertureCredit6 = 0;
                    static $totalMouvementDebit6 = 0;
                    static $totalMouvementCredit6 = 0;
                    static $totalClotureDebit6 = 0;
                    static $totalClotureCredit6 = 0;
                    static $comptesAffiches6 = 0;
                    
                    // Récupérer l'exercice en cours
                    $exo = DB::table('exercices')->where('statut', 1)->first();
                    
                    // Déterminer si c'est un compte parent
                    $hasChildren = isset($balance['has_children']) && $balance['has_children'];
                    $childrenCount = $balance['children_count'] ?? 0;
                    $oldAccountsCount = isset($balance['old_accounts_data']) ? count($balance['old_accounts_data']) : 0;
                    
                    // Récupérer les soldes d'ouverture (RAN) pour ce compte parent (incluant ses enfants)
                    $allAccountIds = [$balance['id']];
                    
                    if ($hasChildren) {
                        // Pour les parents, on doit aussi chercher les RAN de tous les enfants
                        $childrenIds = isset($balance['children_data']) 
                            ? collect($balance['children_data'])->pluck('id')->toArray() 
                            : [];
                        $allAccountIds = array_merge($allAccountIds, $childrenIds);
                    }
                    
                    // Récupérer les écritures RAN pour tous les comptes (parent + enfants)
                    $soldesRAN = DB::table('grand_livres')
                        ->join('new_accounts', 'new_accounts.id', 'grand_livres.new_account_id')
                        ->whereIn('grand_livres.new_account_id', $allAccountIds)
                        ->where('grand_livres.journal_code', 'RAN')
                        ->where('grand_livres.entreprise_id', $entreprise->id)
                        ->where('grand_livres.exercice_id', $exo?->id)
                        ->get();
                    
                    $ouvertureDebit = arrondirBalance($soldesRAN->sum('debit'));
                    $ouvertureCredit = arrondirBalance($soldesRAN->sum('credit'));
                    
                    // Mouvements (exclure RAN)
                    $mouvementDebit = 0;
                    $mouvementCredit = 0;
                    
                    if (isset($balance['ecritures']) && $balance['ecritures']->count() > 0) {
                        $ecrituresSansRAN = $balance['ecritures']->filter(function($ecriture) {
                            return $ecriture->journal_code !== 'RAN';
                        });
                        
                        $mouvementDebit = arrondirBalance($ecrituresSansRAN->sum('debit'));
                        $mouvementCredit = arrondirBalance($ecrituresSansRAN->sum('credit'));
                    }
                    
                    $totalDebitCalc = arrondirBalance($ouvertureDebit + $mouvementDebit);
                    $totalCreditCalc = arrondirBalance($ouvertureCredit + $mouvementCredit);
                    
                    $soldeCloture = arrondirBalance($totalDebitCalc - $totalCreditCalc);
                    $isDebiteurCloture = $soldeCloture > 0;
                    $soldeClotureAbsolu = abs($soldeCloture);
                    
                    // Déterminer si on affiche
                    $hasOuverture = (abs($ouvertureDebit) > 0.001) || (abs($ouvertureCredit) > 0.001);
                    $hasMouvements = (abs($mouvementDebit) > 0.001) || (abs($mouvementCredit) > 0.001);
                    $hasCloture = (abs($soldeCloture) > 0.001);
                    
                    $doitAfficher = $hasOuverture || $hasMouvements || $hasCloture;
                    
                    if ($doitAfficher) {
                        $comptesAffiches6++;
                        $totalOuvertureDebit6 = arrondirBalance($totalOuvertureDebit6 + $ouvertureDebit);
                        $totalOuvertureCredit6 = arrondirBalance($totalOuvertureCredit6 + $ouvertureCredit);
                        $totalMouvementDebit6 = arrondirBalance($totalMouvementDebit6 + $mouvementDebit);
                        $totalMouvementCredit6 = arrondirBalance($totalMouvementCredit6 + $mouvementCredit);
                        
                        if ($soldeCloture > 0) {
                            $totalClotureDebit6 = arrondirBalance($totalClotureDebit6 + $soldeCloture);
                        } else {
                            $totalClotureCredit6 = arrondirBalance($totalClotureCredit6 + abs($soldeCloture));
                        }
                    }
                @endphp
                
                @if($doitAfficher)
                <tr class="hover:bg-gray-50 {{ $hasChildren ? 'bg-blue-50/30' : '' }}">
                    <td class="px-6 py-2 whitespace-nowrap border-x">
                        <div style="font-size: 13px;" class="font-mono font-bold {{ $hasChildren ? 'text-indigo-700' : 'text-blue-700' }}">
                            {{ $balance['code'] }}
                        </div>
                        @if($hasChildren)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 mt-1 print:hidden">
                                <i class="fas fa-sitemap mr-1"></i> Parent ({{ $childrenCount }})
                            </span>
                        @endif
                    </td>
                    
                    <td class="px-6 py-2 border-x">
                        <div style="font-size: 13px;" class="font-medium text-gray-900">
                            {{ Str::limit($balance['intitule'], 30) }}
                        </div>
                        
                        @if($hasChildren && $childrenCount > 0)
                            <div style="font-size: 10px;" class="text-indigo-600 mt-1 print:hidden">
                                <i class="fas fa-sitemap mr-1"></i>
                                {{ $childrenCount }} compte(s) auxiliaire(s) agrégé(s)
                            </div>
                        @endif
                        
                        {{-- @if($oldAccountsCount > 0)
                            <div style="font-size: 10px;" class="text-gray-500 mt-1 print:hidden">
                                <i class="fas fa-history mr-1"></i>
                                {{ $oldAccountsCount }} compte entité 
                            </div>
                        @endif --}}
                    </td>
                    
                    <!-- Solde d'ouverture Débit -->
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($ouvertureDebit > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($ouvertureDebit, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <!-- Solde d'ouverture Crédit -->
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($ouvertureCredit > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($ouvertureCredit, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <!-- Mouvement Débit -->
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($mouvementDebit > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($mouvementDebit, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <!-- Mouvement Crédit -->
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($mouvementCredit > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($mouvementCredit, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <!-- Solde clôture Débiteur -->
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if($isDebiteurCloture)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($soldeClotureAbsolu, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <!-- Solde clôture Créditeur -->
                    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                        @if(!$isDebiteurCloture && $soldeClotureAbsolu > 0)
                            <div style="font-size: 13px;" class="text-black-600 font-medium">
                                {{ number_format($soldeClotureAbsolu, 0, ',', ' ') }}
                            </div>
                        @else
                            <div style="font-size: 13px;" class="text-gray-400">-</div>
                        @endif
                    </td>
                    
                    <!-- Actions -->
                    <td class="px-6 py-2 whitespace-nowrap border-x no-print">
                        @if($balance['ecritures_count'] > 0)
                            <button style="font-size: 13px;" wire:click="showDetails({{ $balance['id'] }})"
                                    class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                <i class="fas fa-eye"></i>
                                Détails
                                @if($hasChildren)
                                    <span class="ml-1 text-xs bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded-full">
                                        {{ $childrenCount }}
                                    </span>
                                @endif
                            </button>
                        @else
                            <span style="font-size: 10px;" class="text-gray-400">-</span>
                        @endif
                    </td>
                </tr>
                @endif
            @endif
        @empty
            <tr>
                <td colspan="{{ $balanceType === '4colonnes' ? 7 : 9 }}" class="px-6 py-8 text-center border-x">
                    <div class="text-gray-400">
                        <i class="fas fa-scale-balanced text-3xl mb-3"></i>
                        <p style="font-size: 13px;" class="font-medium mb-1">Aucun compte trouvé</p>
                        <p style="font-size: 13px;" class="text-gray-500">
                            Vérifiez vos filtres ou créez des mappings de comptes
                        </p>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
    
    @if(count($balances) > 0)
        @if($balanceType === '4colonnes')
            @php
                $totalSoldeDebiteur = 0;
                $totalSoldeCrediteur = 0;
                
                foreach($balances as $balance) {
                    $solde = $balance['solde'];
                    if($solde > 0) {
                        $totalSoldeDebiteur += $solde;
                    } else {
                        $totalSoldeCrediteur += abs($solde);
                    }
                }
            @endphp
            
            <tfoot class="bg-gray-50 font-medium border-t">
                <tr>
                    <td colspan="2" style="font-size: 12px;" class="px-6 py-2 text-right text-gray-700 border-x">
                        TOTAUX
                    </td>
                    <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                        {{ number_format($stats['total_debit'], 0, ',', ' ') }}
                    </td>
                    <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                        {{ number_format($stats['total_credit'], 0, ',', ' ') }}
                    </td>
                    <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                        {{ number_format($totalSoldeDebiteur, 0, ',', ' ') }}
                    </td>
                    <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                        {{ number_format($totalSoldeCrediteur, 0, ',', ' ') }}
                    </td>
                    <td class="border-x no-print"></td>
                </tr>
                <tr class="bg-blue-50">
                    <td colspan="7" class="px-6 py-2 text-center border-x">
                        <span style="font-size: 12px;" class="font-medium text-blue-800">
                            ÉQUILIBRE : {{ number_format($totalSoldeDebiteur, 0, ',', ' ') }} = {{ number_format($totalSoldeCrediteur, 0, ',', ' ') }}
                            @if($totalSoldeDebiteur == $totalSoldeCrediteur)
                                <i class="fas fa-check-circle text-green-600 ml-2"></i>
                            @else
                                <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                            @endif
                        </span>
                    </td>
                </tr>
            </tfoot>
        @else
            {{-- Totaux pour 6 colonnes --}}
            @if(isset($comptesAffiches6) && $comptesAffiches6 > 0)
                @php
                    $totalDebitGeneral = arrondirBalance($totalOuvertureDebit6 + $totalMouvementDebit6);
                    $totalCreditGeneral = arrondirBalance($totalOuvertureCredit6 + $totalMouvementCredit6);
                    $equilibreGeneral = abs($totalDebitGeneral - $totalCreditGeneral) < 0.01;
                @endphp
                
                <tfoot class="bg-gray-50 font-bold border-t">
                    <tr>
                        <td colspan="2" style="font-size: 13px;" class="px-6 py-2 text-right text-gray-700 border-x">
                            TOTAUX ({{ $comptesAffiches6 }} comptes)
                        </td>
                        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalOuvertureDebit6, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalOuvertureCredit6, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalMouvementDebit6, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalMouvementCredit6, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalClotureDebit6, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalClotureCredit6, 0, ',', ' ') }}
                        </td>
                        <td class="border-x no-print"></td>
                    </tr>
                    <tr class="bg-blue-50">
                        <td colspan="9" class="px-6 py-2 text-center border-x">
                            <span style="font-size: 12px;" class="font-medium text-blue-800">
                                ÉQUILIBRE: (A + C) = (B + D) → 
                                {{ number_format($totalDebitGeneral, 0, ',', ' ') }} = {{ number_format($totalCreditGeneral, 0, ',', ' ') }}
                                @if($equilibreGeneral)
                                    <i class="fas fa-check-circle text-green-600 ml-2"></i>
                                @else
                                    <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                                @endif
                            </span>
                        </td>
                    </tr>
                </tfoot>
            @endif
        @endif
    @endif
</table>
                </div>
            </div>
        </div>
    @endif

    <style>
    @media print {
        /* Cache tous les éléments sauf le tableau */
        body * {
            visibility: hidden;
        }
        
        #balance-table, 
        #balance-table *,
        .print-header,
        .print-footer {
            visibility: visible;
        }
        
        #balance-table {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            margin: 0;
            padding: 15px 10px;
            box-sizing: border-box;
        }
        
        /* Style pour l'en-tête d'impression */
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            border-bottom: 2px solid #ccc;
            font-family: Arial, sans-serif;
            width: 100%;
            box-sizing: border-box;
        }
        
        .print-header h1 {
            font-size: 18px;
            margin: 0 0 8px 0;
            font-weight: bold;
            color: #000;
        }
        
        .print-header .entreprise {
            font-size: 15px;
            margin: 5px 0;
            font-weight: bold;
            color: #333;
        }
        
        .print-header .periode {
            font-size: 13px;
            margin: 3px 0;
            color: #555;
        }
        
        .print-header .total {
            font-size: 12px;
            margin: 8px 0 0 0;
            font-style: italic;
            color: #666;
            border-top: 1px dashed #ccc;
            padding-top: 5px;
        }
        
        /* Style du tableau */
        table {
            width: 100%;
            border-collapse: collapse;
            font-family: Arial, sans-serif;
            font-size: 10px;
            page-break-inside: auto;
            margin-bottom: 15px;
        }
        
        th {
            background-color: #e6e6e6 !important;
            color: #000 !important;
            font-weight: bold;
            padding: 6px 4px;
            border: 1px solid #888;
            text-align: center;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        td {
            padding: 5px 4px;
            border: 1px solid #888;
            vertical-align: middle;
        }
        
        /* Alignement des colonnes */
        td:nth-child(1), th:nth-child(1) { 
            text-align: left; 
            font-weight: 500;
        }
        td:nth-child(2), th:nth-child(2) { 
            text-align: left; 
        }
        td:nth-child(3), th:nth-child(3),
        td:nth-child(4), th:nth-child(4),
        td:nth-child(5), th:nth-child(5),
        td:nth-child(6), th:nth-child(6) { 
            text-align: right;
            font-family: 'Courier New', monospace;
        }
        
        /* Couleur alternée des lignes */
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        
        /* Style des totaux */
        tfoot tr:first-child {
            background-color: #d9d9d9;
            font-weight: bold;
        }
        
        tfoot tr:first-child td {
            border-top: 2px solid #666;
        }
        
        tfoot tr:last-child {
            background-color: #cce5ff;
            font-weight: bold;
        }
        
        tfoot tr:last-child td {
            border-top: 2px solid #666;
            border-bottom: 2px solid #666;
        }
        
        /* Cacher la colonne Actions */
        th:last-child,
        td:last-child,
        .no-print {
            display: none !important;
        }
        
        /* Largeurs de colonnes optimisées */
        th:nth-child(1) { width: 12%; }
        th:nth-child(2) { width: 38%; }
        th:nth-child(3), th:nth-child(4),
        th:nth-child(5), th:nth-child(6) { width: 12.5%; }
        
        /* Pied de page */
        .print-footer {
            display: block !important;
            text-align: right;
            margin-top: 15px;
            font-size: 9px;
            color: #555;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            width: 100%;
            box-sizing: border-box;
        }
        
        /* Éviter les coupures de ligne */
        tr {
            page-break-inside: avoid;
        }
        
        /* Forcer les sauts de page si nécessaire */
        tfoot {
            display: table-row-group;
            page-break-after: avoid;
        }
    }
    
    /* Masqué à l'écran */
    .print-header, .print-footer {
        display: none;
    }
    
    /* Dans ton fichier CSS principal */
.balance-table {
    width: 100%;
    border-collapse: collapse;
    font-family: 'Inter', sans-serif;
}

.balance-table th {
    background-color: #1f2937;
    color: white;
    font-weight: 600;
    padding: 12px 16px;
    text-align: left;
    font-size: 13px;
}

.balance-table td {
    padding: 10px 16px;
    border-bottom: 1px solid #e5e7eb;
}

.balance-table tr:hover {
    background-color: #f9fafb;
}

.balance-table .child-row td:first-child {
    padding-left: 32px;
}

.balance-table .subtotal-row {
    background-color: #f3f4f6;
    font-weight: 600;
    border-top: 1px solid #d1d5db;
    border-bottom: 1px solid #d1d5db;
}

.balance-table .total-row {
    background-color: #1f2937;
    color: white;
    font-weight: bold;
}
</style>
@push('scripts')
<!-- Script pour l'impression -->
<script>
    function imprimerBalance() {
        // Récupérer les informations avec échappement JSON pour éviter les problèmes de caractères
        const entreprise = @json($entreprise->nom ?? $entreprise->name ?? 'Entreprise');
        const dateDebut = @json(\Carbon\Carbon::parse($dateDebut)->format('d/m/Y'));
        const dateFin = @json(\Carbon\Carbon::parse($dateFin)->format('d/m/Y'));
        const totalEcritures = @json($stats['total_ecritures'] ?? 0);
        
        // Calculer les totaux depuis le tableau
        const totalDebitElement = document.querySelector('tfoot tr:first-child td:nth-child(3)');
        const totalCreditElement = document.querySelector('tfoot tr:first-child td:nth-child(4)');
        const totalSoldeDebElement = document.querySelector('tfoot tr:first-child td:nth-child(5)');
        const totalSoldeCredElement = document.querySelector('tfoot tr:first-child td:nth-child(6)');
        
        const totalDebit = totalDebitElement ? totalDebitElement.innerText : '';
        const totalCredit = totalCreditElement ? totalCreditElement.innerText : '';
        const totalSoldeDeb = totalSoldeDebElement ? totalSoldeDebElement.innerText : '';
        const totalSoldeCred = totalSoldeCredElement ? totalSoldeCredElement.innerText : '';
        
        // Récupérer la ligne d'équilibre
        const equilibreElement = document.querySelector('tfoot tr:last-child td span');
        const equilibre = equilibreElement ? equilibreElement.innerText : '';
        
        // Créer un en-tête d'impression complet
        const printHeader = document.createElement('div');
        printHeader.className = 'print-header';
        printHeader.innerHTML = `
            <h1>BALANCE DES COMPTES</h1>
            <div class="entreprise">${entreprise}</div>
            <div class="periode">Période du ${dateDebut} au ${dateFin}</div>
            <div class="periode">Balance à 4 colonnes</div>
            <div class="total">Total écritures : ${totalEcritures}</div>
        `;
        
        // Créer un résumé des totaux
        const printSummary = document.createElement('div');
        printSummary.className = 'print-summary';
        printSummary.style.cssText = 'margin: 10px 0; padding: 8px; background-color: #f5f5f5; border: 1px solid #ccc; font-size: 11px; display: none;';
        printSummary.innerHTML = `
            <div style="display: flex; justify-content: space-around; font-weight: bold;">
                <span>Total Débit: ${totalDebit}</span>
                <span>Total Crédit: ${totalCredit}</span>
                <span>Solde Débiteur: ${totalSoldeDeb}</span>
                <span>Solde Créditeur: ${totalSoldeCred}</span>
            </div>
        `;
        
        // Créer un pied de page
        const printFooter = document.createElement('div');
        printFooter.className = 'print-footer';
        printFooter.innerHTML = `Document imprimé le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')} | ${equilibre}`;
        
        // Ajouter l'en-tête avant le tableau
        const tableContainer = document.getElementById('balance-table');
        const parent = tableContainer.parentNode;
        parent.insertBefore(printHeader, tableContainer);
        parent.insertBefore(printSummary, tableContainer);
        
        // Ajouter le pied de page après le tableau
        parent.insertBefore(printFooter, tableContainer.nextSibling);
        
        // Imprimer
        window.print();
        
        // Retirer l'en-tête, le résumé et le pied de page après impression
        setTimeout(() => {
            printHeader.remove();
            printSummary.remove();
            printFooter.remove();
        }, 1000);
    }
</script>
@endpush
    <!-- Script pour le scroll -->
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('scroll-to-details', () => {
                setTimeout(() => {
                    const detailsElement = document.getElementById('balance-details');
                    if (detailsElement) {
                        detailsElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }, 100);
            });
        });
    </script>
</div>