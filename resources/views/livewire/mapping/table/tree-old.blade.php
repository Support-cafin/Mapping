@foreach($nodes as $node)
@php
    $hasChildren = $node->children->isNotEmpty();
    $isExpanded  = in_array($node->id, $expandedOld);
    $isSelected  = $selectedOld === $node->id;
    // Vérification si le compte old est mappé (un seul mapping possible)
    $isMapped    = in_array($node->id, $mappedOldIds ?? []);
@endphp

<tr wire:key="old-{{ $node->id }}"
    wire:click="selectOld({{ $node->id }})"
    class="transition-all duration-100 cursor-pointer
           {{ $isSelected ? 'bg-blue-200 ring-2 ring-blue-500 shadow-lg' : '' }}
           {{ $isMapped && !$isSelected ? 'bg-green-100 border-l-4 border-green-500' : '' }}
           {{ !$isSelected && !$isMapped ? 'hover:bg-gray-100' : '' }}">
    
    <!-- CODE -->
    <td class="px-4 py-1 font-mono text-xs"
        style="padding-left: {{ $level * 20 + 16 }}px;">
        <div class="flex items-center gap-2">
            
            {{-- Bouton expand/collapse --}}
            @if($hasChildren)
                <button type="button"
                        wire:click.stop="toggleOld({{ $node->id }})"
                        class="w-4 h-4 flex items-center justify-center
                               text-gray-500 hover:text-gray-800 transition-all duration-150
                               hover:bg-gray-200 rounded">
                    <svg class="w-3 h-3 transition-transform duration-150 {{ $isExpanded ? 'rotate-90' : '' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="w-4"></span>
            @endif

            {{-- Code du compte --}}
            <span class="font-semibold {{ $isSelected ? 'text-blue-900' : ($isMapped ? 'text-green-700' : 'text-gray-700') }}">
                {{ $node->code }}
            </span>
            
            {{-- Badge de statut --}}
            @if($isSelected)
                <span class="ml-2 px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-600 text-white animate-pulse">
                    ✓ Sélectionné
                </span>
            @elseif($isMapped)
                <span class="ml-2 px-2 py-0.5 text-[10px] font-medium rounded-full bg-green-600 text-white flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Mappé
                </span>
            @else
                <span class="ml-2 px-2 py-0.5 text-[10px] font-medium rounded-full bg-red-500 text-white">
                    ✗ Non mappé
                </span>
            @endif
        </div>
    </td>
    
    <!-- INTITULÉ avec boutons d'action -->
    <td class="py-1 pr-4 text-xs {{ $isSelected ? 'text-blue-900 font-medium' : 'text-gray-700' }}">
    <div class="flex items-center justify-between">
        <span class="flex-1">{{ $node->intitule }}</span>
        
        {{-- Boutons d'action toujours visibles --}}
        <div class="flex items-center gap-1 ml-2" wire:click.stop>
                
                 {{-- Bouton modifier --}}
            <button wire:click.stop="openEditModal({{ $node->id }})"
                    class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-100 rounded transition"
                    title="Modifier le compte">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
            </button>
            
            {{-- Bouton supprimer (seulement si non mappé) --}}
            @if(!$isMapped)
                <button wire:click.stop="openDeleteModal({{ $node->id }})"
                        class="p-1 text-red-600 hover:text-red-800 hover:bg-red-100 rounded transition"
                        title="Supprimer le compte">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>
            @endif
            </div>
        </div>
    </td>
</tr>

{{-- CHILDREN (sous-comptes) --}}
@if($isExpanded && $hasChildren)
    @include('livewire.mapping.table.tree-old', [
        'nodes' => $node->children,
        'level' => $level + 1
    ])
@endif
@endforeach