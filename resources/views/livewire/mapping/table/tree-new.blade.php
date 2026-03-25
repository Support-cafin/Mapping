@foreach($nodes as $node)
@php
    $hasChildren = $node->children->isNotEmpty();
    $isExpanded  = in_array($node->id, $expandedNew);
    $isMappedToSelected = in_array($node->id, $mappedToSelectedOld ?? []);
    $isMappedToAny = isset($mappedNewIds[$node->id]);
    $mappedOldId = $isMappedToAny ? $mappedNewIds[$node->id] : null;
@endphp

<tr wire:key="new-{{ $node->id }}"
    wire:click="mapToNew({{ $node->id }})"
    class="group transition-all duration-150 cursor-pointer
           hover:bg-green-50
           {{ $quickMapping && $selectedOld ? 'hover:ring-2 hover:ring-green-500' : '' }}
           {{ $isMappedToSelected ? 'bg-green-200 font-medium ring-2 ring-green-500' : '' }}
           {{ $isMappedToAny && !$isMappedToSelected ? 'bg-yellow-50' : '' }}">

    <!-- CODE -->
    <td class="px-4 py-1 font-mono text-xs"
        style="padding-left: {{ $level * 20 + 16 }}px;">
        <div class="flex items-center gap-2">

            {{-- Expand --}}
            @if($hasChildren)
                <button type="button"
                        wire:click.stop="toggleNew({{ $node->id }})"
                        class="w-4 h-4 flex items-center justify-center
                               text-gray-500 hover:text-gray-800 transition">
                    <svg class="w-3 h-3 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="w-4"></span>
            @endif

            <span class="{{ $isMappedToSelected ? 'text-green-800 font-bold' : ($isMappedToAny ? 'text-yellow-700' : 'text-gray-700') }}">
                {{ $node->code }}
            </span>
            
            @if($isMappedToSelected)
                <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-green-600 text-white font-medium">
                    ✓ Cible actuelle
                </span>
            @elseif($isMappedToAny)
                <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-yellow-500 text-white">
                    Déjà mappé
                </span>
            @elseif($selectedOld)
                <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-blue-500 text-white opacity-0 group-hover:opacity-100 transition-opacity">
                    Cliquer pour mapper
                </span>
            @endif
        </div>
    </td>

    <!-- INTITULÉ avec boutons d'action -->
    <td class="py-1 pr-4 text-xs text-gray-700">
        <div class="flex items-center justify-between">
            <span>{{ $node->intitule }}</span>
            
            <div class="flex items-center gap-1 ml-2" wire:click.stop>
                {{-- Bouton modifier --}}
                <button wire:click.stop="openEditNewModal({{ $node->id }})"
                        class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition"
                        title="Modifier le compte">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                    </svg>
                </button>
                
                {{-- Bouton supprimer (seulement si non mappé) --}}
                @if(!$isMappedToAny)
                    <button wire:click.stop="openDeleteNewModal({{ $node->id }})"
                            class="p-1 text-red-600 hover:text-red-800 hover:bg-red-100 rounded transition"
                            title="Supprimer le compte">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                @endif
                
                @if($isMappedToAny && !$isMappedToSelected && $mappedOldId)
                    @php
                        $oldAccount = \App\Models\OldAccount::find($mappedOldId);
                    @endphp
                    @if($oldAccount)
                        <span class="text-[9px] text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                            → {{ $oldAccount->code }}
                        </span>
                    @endif
                @endif
            </div>
        </div>
    </td>
</tr>

{{-- CHILDREN --}}
@if($isExpanded && $hasChildren)
    @include('livewire.mapping.table.tree-new', [
        'nodes' => $node->children,
        'level' => $level + 1
    ])
@endif
@endforeach