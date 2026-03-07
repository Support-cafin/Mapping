@foreach($group['old_accounts_data'] as $oldAccountIndex => $oldAccountData)
    
    <!-- Écritures individuelles pour cet ancien compte -->
    @foreach($oldAccountData['ecritures'] as $ecritureIndex => $ecriture)
    <tr class="hover:bg-gray-50 border-b border-gray-100">
        <!-- Date -->
        <td class="px-2 py-0.5 text-xs text-gray-700" style="font-size: 11px;">
            {{ $ecriture->date_ecriture->format('d/m/Y') }}
        </td>
        
        <!-- N° de pièce -->
        <td class="px-2 py-0.5 text-xs text-gray-700 font-mono" style="font-size: 11px;">
            {{ $ecriture->piece ?? '-' }}
        </td>
        
        <!-- Code journal -->
        <td class="px-2 py-0.5 text-xs text-gray-700" style="font-size: 11px;">
            @if($ecriture->journal_code)
                <span class="bg-gray-100 px-1 py-0.5 rounded">{{ $ecriture->journal_code }}</span>
            @else
                -
            @endif
        </td>
        
        <!-- N° de compte (ancien) -->
        <td class="px-2 py-0.5 text-xs font-mono text-gray-800" style="font-size: 11px;">
            {{ $ecriture->oldAccount->code }}
        </td>
        
        <!-- Compte SYCEBNL -->
        <td class="px-2 py-0.5 text-xs font-mono text-blue-600 font-medium" style="font-size: 11px;">
            {{ $group['new_account_code'] }}
        </td>
        
        <!-- Libellé -->
        <td class="px-2 py-0.5 text-xs text-gray-600" style="font-size: 11px; max-width: 300px;">
            <span class="block truncate">{{ $ecriture->libelle ?? '-' }}</span>
        </td>
        
        <!-- Montant débit -->
        <td class="px-2 py-0.5 text-right text-xs whitespace-nowrap tabular-nums min-w-[120px]
            {{ $ecriture->debit > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">
            {{ $ecriture->debit > 0 ? number_format($ecriture->debit, 0, '', ' ') : '-' }}
        </td>
        
        <!-- Montant crédit -->
        <td class="px-2 py-0.5 text-right text-xs whitespace-nowrap tabular-nums min-w-[120px]
            {{ $ecriture->credit > 0 ? 'text-green-600 font-medium' : 'text-gray-400' }}">
            {{ $ecriture->credit > 0 ? number_format($ecriture->credit, 0, '', ' ') : '-' }}
        </td>
    </tr>
    @endforeach
    
    <!-- Sous-total par ancien compte (si plusieurs) -->
    @if(count($group['old_accounts_data']) > 1 && !$loop->last)
    <tr class="bg-gray-200">
        <td colspan="6" class="px-2 py-0.5 text-right text-xs font-medium text-gray-700" style="font-size: 10px;">
            Sous-total {{ $oldAccountData['account']->code }}
        </td>
        <td class="px-2 py-0.5 text-right text-xs font-bold text-red-700 whitespace-nowrap min-w-[120px]" style="font-size: 11px;">
            {{ number_format($oldAccountData['total_debit'], 0, '', ' ') }}
        </td>
        <td class="px-2 py-0.5 text-right text-xs font-bold text-green-700 whitespace-nowrap min-w-[120px]" style="font-size: 11px;">
            {{ number_format($oldAccountData['total_credit'], 0, '', ' ') }}
        </td>
    </tr>
    @endif
@endforeach

<!-- Total pour ce compte SYCEBNL -->
<tr class="bg-gray-100 border-t border-gray-300 font-bold">
    <td colspan="6" class="px-2 py-0.5 text-left text-xs text-gray-800" style="font-size: 11px;">
        <i class="fas fa-calculator mr-1"></i> Total Mouvements - {{ $group['new_account_code'] }} {{ $group['new_account_intitule'] }}
    </td>
    <td class="px-2 py-0.5 text-right text-xs text-red-800 whitespace-nowrap tabular-nums min-w-[120px]">
        {{ number_format($group['total_debit'], 0, '', ' ') }}
    </td>
    <td class="px-2 py-0.5 text-right text-xs text-green-800 whitespace-nowrap tabular-nums min-w-[120px]">
        {{ number_format($group['total_credit'], 0, '', ' ') }}
    </td>
</tr>

<!-- Ligne de solde -->
@php
    $solde = $group['solde'];
    $isDebit = $solde > 0;
    $soldeAbs = abs($solde);
    $soldeText = number_format($soldeAbs, 0, '', ' ');
    $dateSolde = $dateFin
        ? \Carbon\Carbon::parse($dateFin)->format('d-m-Y')
        : now()->format('d-m-Y');
@endphp

<tr class="bg-blue-50/50 border-b-2 border-blue-200">
    <td colspan="6" class="px-2 py-0.5 text-right text-xs font-bold text-blue-800" style="font-size: 11px;">
        <i class="fas fa-balance-scale mr-1"></i> Solde au {{ $dateSolde }}
    </td>
    
    @if($solde > 0)
        <!-- Solde débiteur -->
        <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100
            whitespace-nowrap tabular-nums min-w-[120px]">
            {{ $soldeText }}
        </td>
        <td class="px-2 py-0.5 text-right text-xs text-gray-400 whitespace-nowrap min-w-[120px]">
            -
        </td>
    @elseif($solde < 0)
        <!-- Solde créditeur -->
        <td class="px-2 py-0.5 text-right text-xs text-gray-400 whitespace-nowrap min-w-[120px]">
            -
        </td>
        <td class="px-2 py-0.5 text-right text-xs font-bold text-blue-800 bg-blue-100
            whitespace-nowrap tabular-nums min-w-[120px]">
            {{ $soldeText }}
        </td>
    @else
        <!-- Solde nul -->
        <td class="px-2 py-0.5 text-right text-xs text-gray-400 whitespace-nowrap min-w-[120px]">-</td>
        <td class="px-2 py-0.5 text-right text-xs text-gray-400 whitespace-nowrap min-w-[120px]">-</td>
    @endif
</tr>

<!-- Saut de ligne entre groupes -->
@if(!$loop->last)
<tr>
    <td colspan="8" class="py-4">
        <div class="h-px bg-gray-200"></div>
    </td>
</tr>
@endif