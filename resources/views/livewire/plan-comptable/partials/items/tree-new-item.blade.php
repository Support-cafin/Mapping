@php
    $children = $account->children()->orderBy('code')->get();
@endphp

<tr class="border-b">

    {{-- COLONNE 1 : Code avec indentation + flèche --}}
    <td class="px-3 py-1 whitespace-nowrap">

        {{-- Indentation --}}
        <span style="padding-left: {{ $level * 18 }}px"></span>

        {{-- Flèche si parent --}}
        @if ($children->count() > 0)
            <span 
                wire:click="toggleNew({{ $account->id }})"
                class="cursor-pointer text-gray-600 select-none"
            >
                @if (in_array($account->id, $this->expandedNew))
                    ▾
                @else
                    ▸
                @endif
            </span>
        @else
            <span class="text-gray-400">•</span>
        @endif

        {{-- Code --}}
        <span class="ml-1 font-semibold">{{ $account->code }}</span>

    </td>

    {{-- COLONNE 2 : Intitulé --}}
    <td class="px-3 py-1">
        {{ $account->intitule }}
    </td>

</tr>

{{-- Enfants --}}
@if (in_array($account->id, $this->expandedNew))
    @foreach ($children as $child)
        @include('livewire.plan-comptable.partials.items.tree-new-item', [
            'account' => $child,
            'level' => $level + 1
        ])
    @endforeach
@endif
