@php
    $soldeOuvertureDebit = $balance['solde_ouverture_debit'] ?? 0;
    $soldeOuvertureCredit = $balance['solde_ouverture_credit'] ?? 0;
    $mouvementDebit = $balance['total_debit'] ?? 0;
    $mouvementCredit = $balance['total_credit'] ?? 0;
    $totalDebit = $soldeOuvertureDebit + $mouvementDebit;
    $totalCredit = $soldeOuvertureCredit + $mouvementCredit;
    $soldeCloture = $totalDebit - $totalCredit;
    $isDebiteur = $soldeCloture > 0;
    $soldeClotureAbsolu = abs($soldeCloture);
    
    $hasChildren = isset($balance['has_children']) && $balance['has_children'];
    $childrenCount = $balance['children_count'] ?? 0;
    $oldAccountsCount = isset($balance['old_accounts_data']) ? count($balance['old_accounts_data']) : 0;
@endphp

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
                {{ $oldAccountsCount }} compte entité
            </div>
        @endif
        
        @if($hasChildren && $oldAccountsCount > 0)
            <div style="font-size: 10px;" class="text-gray-500 mt-1 print:hidden">
                <i class="fas fa-history mr-1"></i>
                {{ $oldAccountsCount }} compte entité agrégé
            </div>
        @endif
    </td>
    
    <!-- Solde d'ouverture -->
    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
        @if($soldeOuvertureDebit > 0)
            <div style="font-size: 13px;" class="text-black-600 font-medium">
                {{ number_format($soldeOuvertureDebit, 0, ',', ' ') }}
            </div>
        @else
            <div style="font-size: 13px;" class="text-gray-400">-</div>
        @endif
    </td>
    
    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
        @if($soldeOuvertureCredit > 0)
            <div style="font-size: 13px;" class="text-black-600 font-medium">
                {{ number_format($soldeOuvertureCredit, 0, ',', ' ') }}
            </div>
        @else
            <div style="font-size: 13px;" class="text-gray-400">-</div>
        @endif
    </td>
    
    <!-- Mouvement -->
    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
        @if($mouvementDebit > 0)
            <div style="font-size: 13px;" class="text-black-600 font-medium">
                {{ number_format($mouvementDebit, 0, ',', ' ') }}
            </div>
        @else
            <div style="font-size: 13px;" class="text-gray-400">-</div>
        @endif
    </td>
    
    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
        @if($mouvementCredit > 0)
            <div style="font-size: 13px;" class="text-black-600 font-medium">
                {{ number_format($mouvementCredit, 0, ',', ' ') }}
            </div>
        @else
            <div style="font-size: 13px;" class="text-gray-400">-</div>
        @endif
    </td>
    
    <!-- Solde clôture -->
    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
        @if($isDebiteur)
            <div style="font-size: 13px;" class="text-black-600 font-medium">
                {{ number_format($soldeClotureAbsolu, 0, ',', ' ') }}
            </div>
        @else
            <div style="font-size: 13px;" class="text-gray-400">-</div>
        @endif
    </td>
    
    <td class="px-6 py-2 whitespace-nowrap text-right border-x">
        @if(!$isDebiteur && $soldeClotureAbsolu > 0)
            <div style="font-size: 13px;" class="text-black-600 font-medium">
                {{ number_format($soldeClotureAbsolu, 0, ',', ' ') }}
            </div>
        @else
            <div style="font-size: 13px;" class="text-gray-400">-</div>
        @endif
    </td>
    
    <td class="px-6 py-2 whitespace-nowrap border-x no-print">
        @if(($balance['ecritures_count'] ?? 0) > 0)
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