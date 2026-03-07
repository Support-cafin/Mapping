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
        <div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden">
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
                
                <div class="overflow-x-auto">
                    <table class="min-w-full border-collapse">
                        <thead class="bg-gray-800 text-white">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs">Compte Entité</th>
                                <th class="px-4 py-2 text-right text-xs">Débit</th>
                                <th class="px-4 py-2 text-right text-xs">Crédit</th>
                                <th class="px-4 py-2 text-right text-xs">Solde</th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            @php
                                $totalDebit = 0;
                                $totalCredit = 0;
                            @endphp
                            
                            @forelse($selectedBalance['old_accounts_data'] as $oldData)
                                @php
                                    $totalDebit += $oldData['debit'];
                                    $totalCredit += $oldData['credit'];
                                    $soldeOld = $oldData['debit'] - $oldData['credit'];
                                @endphp
                                <tr class="hover:bg-gray-50 border-b border-gray-100">
                                    <td class="px-4 py-2 text-xs font-mono">
                                        <span class="font-medium">{{ $oldData['code'] }}</span>
                                        <span class="text-gray-500 text-[10px] block">{{ Str::limit($oldData['intitule'], 40) }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs {{ $oldData['debit'] > 0 ? 'font-medium text-red-600' : 'text-gray-400' }}">
                                        {{ $oldData['debit'] > 0 ? number_format($oldData['debit'], 0, ',', ' ') : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs {{ $oldData['credit'] > 0 ? 'font-medium text-green-600' : 'text-gray-400' }}">
                                        {{ $oldData['credit'] > 0 ? number_format($oldData['credit'], 0, ',', ' ') : '-' }}
                                    </td>
                                    <td class="px-4 py-2 text-right text-xs font-medium {{ $soldeOld > 0 ? 'text-red-600' : ($soldeOld < 0 ? 'text-green-600' : 'text-gray-500') }}">
                                        {{ $soldeOld != 0 ? number_format(abs($soldeOld), 0, ',', ' ') : '-' }}
                                        @if($soldeOld != 0)
                                            <span class="text-[8px] ml-1">{{ $soldeOld > 0 ? 'D' : 'C' }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">
                                        Aucun détail disponible
                                    </td>
                                </tr>
                            @endforelse
                            
                            <!-- Ligne de total -->
                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                <td class="px-4 py-3 text-right text-sm">TOTAL</td>
                                <td class="px-4 py-3 text-right text-sm text-red-600">
                                    {{ number_format($totalDebit, 0, ',', ' ') }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm text-green-600">
                                    {{ number_format($totalCredit, 0, ',', ' ') }}
                                </td>
                                <td class="px-4 py-3 text-right text-sm {{ $selectedBalance['solde'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ number_format(abs($selectedBalance['solde']), 0, ',', ' ') }}
                                    ({{ $selectedBalance['solde'] > 0 ? 'D' : 'C' }})
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Légende -->
                <div class="mt-4 flex justify-end gap-4 text-[10px] text-gray-500">
                    <span><span class="text-red-600 font-bold">D</span> = Débiteur</span>
                    <span><span class="text-green-600 font-bold">C</span> = Créditeur</span>
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
                    
                    <!--<a href="{{ route('balance.tiers') }}" 
                       class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 flex items-center gap-2">
                        <i class="fas fa-users"></i>
                        Balance des Tiers
                    </a>-->
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border overflow-hidden">
                <div class="px-6 py-3 border-b bg-gray-50">
                    <div class="flex justify-between items-center">
    <h3 style="font-size: 13px;" class="font-semibold text-gray-900">
        Balance à 4 colonnes : 
        <br><br>
        <a href="{{ route('balance.tiers') }}" 
           class="px-4 py-2 bg-purple-600 text-white rounded-md text-sm hover:bg-purple-700 flex items-center gap-2">
            <i class="fas fa-users"></i>
            Balance des Tiers
        </a>
        <br><br>
        
        <!-- Boutons d'export avec loaders -->
        <div class="flex gap-2">
            <!-- Bouton Export Excel -->
            <button wire:click="exportExcel" 
                    wire:loading.attr="disabled"
                    wire:target="exportExcel"
                    class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="exportExcel">
                    <i class="fas fa-file-excel"></i>
                </span>
                <span wire:loading wire:target="exportExcel">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <span wire:loading.remove wire:target="exportExcel">Export Excel</span>
                <span wire:loading wire:target="exportExcel">Préparation...</span>
            </button>
            
            <!-- Bouton Imprimer -->
            <button wire:click="print" 
                    wire:loading.attr="disabled"
                    wire:target="print"
                    class="px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700 flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="print">
                    <i class="fas fa-print"></i>
                </span>
                <span wire:loading wire:target="print">
                    <i class="fas fa-spinner fa-spin"></i>
                </span>
                <span wire:loading.remove wire:target="print">Imprimer</span>
                <span wire:loading wire:target="print">Préparation...</span>
            </button>
        </div>
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
                                <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
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
                                <th></th>
                            </tr>
                        </thead>
                        
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($balances as $balance)
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
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 mt-1">
                                                <i class="fas fa-sitemap mr-1"></i> Principal
                                            </span>
                                        @endif
                                    </td>
                                    
                                    <td class="px-6 py-2 border-x">
                                        <div style="font-size: 13px;" class="font-medium text-gray-900">
                                            {{ Str::limit($balance['intitule'], 30) }}
                                        </div>
                                        
                                        @if($hasChildren && $childrenCount > 0)
                                            <div style="font-size: 10px;" class="text-blue-600 mt-1">
                                                <i class="fas fa-sitemap mr-1"></i>
                                                {{ $childrenCount }} compte @if($childrenCount === 1) auxiliaire @else auxiliaire @endif 
                                            </div>
                                        @endif
                                        
                                        @if($oldAccountsCount > 0 && !$hasChildren)
                                            <div style="font-size: 10px;" class="text-gray-500 mt-1">
                                                {{ $oldAccountsCount }}  compte entité
                                            </div>
                                        @endif
                                        
                                        @if($hasChildren && $oldAccountsCount > 0)
                                            <div style="font-size: 10px;" class="text-gray-500 mt-1">
                                                <i class="fas fa-history mr-1"></i>
                                                {{ $oldAccountsCount }} compte entité agrégé
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
                                    
                                    <td class="px-6 py-2 whitespace-nowrap border-x">
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
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center border-x">
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
                                    <td class="border-x"></td>
                                </tr>
                                <tr class="bg-blue-50">
                                    <td colspan="7" class="px-6 py-2 text-center border-x">
                                        <span style="font-size: 12px;" class="font-medium text-blue-800">
                                            ÉQUILIBRE : {{ number_format($totalSoldeDebiteur, 0, ',', ' ') }} = {{ number_format($totalSoldeCrediteur, 0, ',', ' ') }}
                                            @if($totalSoldeDebiteur === $totalSoldeCrediteur)
                                                <i class="fas fa-check-circle text-green-600 ml-2"></i>
                                            @else
                                                <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    @endif

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-balance', () => {
            // Récupérer uniquement le contenu du tableau
            const tableContent = document.querySelector('.overflow-x-auto').cloneNode(true);
            
            // Supprimer la colonne Actions du tableau pour l'impression
            tableContent.querySelectorAll('th:last-child, td:last-child').forEach(el => el.remove());
            
            // Créer une nouvelle fenêtre d'impression
            const printWindow = window.open('', '_blank');
            
            // Récupérer les informations d'en-tête
            const periodeElement = document.querySelector('.text-sm.text-gray-600');
            const periode = periodeElement ? periodeElement.textContent.trim() : '';
            
            // Compter le nombre de colonnes après suppression
            const colSpan = 6; // Maintenant 6 colonnes au lieu de 7
            
            // Écrire le contenu dans la nouvelle fenêtre
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Balance des comptes</title>
                    <meta charset="UTF-8">
                    <style>
                        body {
                            font-family: Arial, sans-serif;
                            margin: 0;
                            padding: 20px;
                            background: white;
                        }
                        .print-header {
                            text-align: center;
                            margin-bottom: 20px;
                            padding-bottom: 10px;
                            border-bottom: 2px solid #333;
                        }
                        .print-header h1 {
                            font-size: 18px;
                            margin: 0;
                            color: #333;
                        }
                        .print-header h2 {
                            font-size: 14px;
                            margin: 5px 0;
                            color: #666;
                        }
                        .print-header p {
                            font-size: 12px;
                            margin: 2px 0;
                            color: #777;
                        }
                        table {
                            width: 100%;
                            border-collapse: collapse;
                            margin-top: 15px;
                            font-size: 11px;
                        }
                        th {
                            background-color: #2C5282 !important;
                            color: white !important;
                            font-weight: bold;
                            padding: 8px 5px;
                            border: 1px solid #1A365D;
                            text-align: center;
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                        td {
                            padding: 6px 5px;
                            border: 1px solid #E2E8F0;
                        }
                        .text-right {
                            text-align: right;
                        }
                        .font-bold {
                            font-weight: bold;
                        }
                        .totaux {
                            background-color: #EDF2F7;
                            font-weight: bold;
                        }
                        .equilibre {
                            background-color: #EBF8FF;
                            text-align: center;
                            padding: 8px;
                            font-weight: bold;
                        }
                        .footer {
                            margin-top: 20px;
                            text-align: right;
                            font-size: 10px;
                            color: #999;
                            border-top: 1px solid #ddd;
                            padding-top: 10px;
                        }
                        /* Cacher les boutons et éléments interactifs */
                        button, .btn, [wire\\:click], .actions {
                            display: none !important;
                        }
                        @media print {
                            body { margin: 0; padding: 15px; }
                            th { background-color: #2C5282 !important; }
                        }
                    </style>
                </head>
                <body>
                    <div class="print-header">
                        <h1>BALANCE DES COMPTES</h1>
                        <h2>{{ $entreprise->nom ?? $entreprise->name ?? 'Entreprise' }}</h2>
                        <p>Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</p>
                        <p>Type : Balance à 4 colonnes</p>
                        <p>${periode}</p>
                    </div>
                    
                    ${tableContent.outerHTML}
                    
                    <div class="footer">
                        Document imprimé le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}
                    </div>
                    
                    <script>
                        // Attendre le chargement puis imprimer
                        window.onload = function() {
                            setTimeout(() => {
                                window.print();
                                setTimeout(() => window.close(), 1000);
                            }, 500);
                        }
                    <\/script>
                </body>
                </html>
            `);
            
            printWindow.document.close();
            
            // Remettre le loader à false après un délai
            setTimeout(() => {
                @this.loadingPrint = false;
            }, 2000);
        });
    });
</script>
@endpush
@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-balance', () => {
            // Simuler un délai d'impression
            setTimeout(() => {
                window.print();
                // Optionnel : remettre le loader à false après impression
                // Livewire.dispatch('print-completed');
            }, 100);
        });
    });
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