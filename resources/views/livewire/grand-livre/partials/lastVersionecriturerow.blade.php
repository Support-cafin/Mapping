@php
    $isDebit = $ecriture->debit > 0;
    $rowClass = $isDebit ? 'bg-red-50/30' : 'bg-green-50/30';
    if ($isManual) {
        $rowClass .= ' manual-row';
    }
@endphp

<tr class="hover:bg-gray-50 transition-colors {{ $rowClass }}">
    <!-- Checkbox de sélection -->
    <td class="px-2 py-1 text-center align-middle" style="font-size: 10px;">
        <input type="checkbox" 
               wire:model="selectedIds"
               value="{{ $ecriture->id }}"
               class="ecriture-checkbox h-4 w-4 rounded border-gray-300 cursor-pointer">
    </td>
    
    <td class="px-2 py-1 whitespace-nowrap align-middle">
        <span class="font-mono text-xs" style="font-size: 11px;">{{ $nbr_ligne }}</span>
    </td>
    
    <td class="px-2 py-1 whitespace-nowrap align-middle">
        <span class="text-xs" style="font-size: 12px;">{{ $ecriture->date_ecriture->format('d/m/Y') }}</span>
    </td>
    
    <td class="px-2 py-1 align-middle">
        @if($ecriture->journal_code)
            <span class="font-mono text-xs" style="font-size: 11px;">{{ $ecriture->journal_code }}</span>
        @else
            <span class="text-gray-400 text-xs">-</span>
        @endif
    </td>
    
    <td class="px-2 py-1 align-middle">
        @if($ecriture->piece)
            <span class="font-mono text-xs" style="font-size: 11px;">{{ $ecriture->piece }}</span>
        @else
            <span class="text-gray-400 text-xs">-</span>
        @endif
    </td>
    
    <td class="px-2 py-1 align-middle">
        @if($ecriture->oldAccount)
            <span class="font-mono text-xs bg-light-50 text-dark-800 px-2 py-1 rounded" style="font-size: 11px; white-space: nowrap;">
                {{ $ecriture->oldAccount->code }}
            </span>
        @endif
    </td>
    
    <td class="px-2 py-1 align-middle max-w-xs">
        <div class="truncate text-xs" style="font-size: 12px; max-width: 240px;" title="{{ $ecriture->libelle ?? 'non renseigné' }}">
            {{ $ecriture->libelle ?? 'non renseigné' }}
        </div>
    </td>
    
    <td class="px-2 py-1 text-right align-middle">
        @if($ecriture->debit > 0)
            <span class="font-mono text-sm font-medium text-dark-700 bg-light-50 px-3 py-1 rounded inline-block" style="font-size: 13px; min-width: 120px; text-align: right;">
                {{ number_format($ecriture->debit, 0, ' ', ' ') }}
            </span>
        @else
            <span class="text-gray-400 text-sm" style="font-size: 12px;">-</span>
        @endif
    </td>
    
    <td class="px-2 py-1 text-right align-middle">
        @if($ecriture->credit > 0)
            <span class="font-mono text-sm font-medium text-dark-700 bg-light-50 px-3 py-1 rounded inline-block" style="font-size: 13px; min-width: 120px; text-align: right;">
                {{ number_format($ecriture->credit, 0, ' ', ' ') }}
            </span>
        @else
            <span class="text-gray-400 text-sm" style="font-size: 12px;">-</span>
        @endif
    </td>
    
    <td class="px-2 py-1 align-middle">
        @if($ecriture->newAccount)
            <span class="font-mono text-xs bg-light-50 text-dark-800 px-2 py-1 rounded" style="font-size: 11px; white-space: nowrap;">
                {{ $ecriture->newAccount->code }}
            </span>
        @else
            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800 whitespace-nowrap">
                <i class="fas fa-exclamation-triangle mr-1 text-xs"></i> Non mappé
            </span>
        @endif
    </td>
    
    <td class="px-2 py-1 align-middle">
        <button wire:click="editEcriture({{ $ecriture->id }})"
                class="text-blue-600 hover:text-blue-800 text-xs font-medium px-2 py-1 rounded hover:bg-blue-50 transition"
                style="font-size: 11px;">
            <i class="fas fa-edit mr-1"></i> Modifier
        </button>
    </td>
</tr>