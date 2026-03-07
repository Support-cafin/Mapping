@php
    $isSelected = $selectedId == $account->id;
    $isMapped = in_array($account->id, $mappedIds);
@endphp

<div>
    <div
        class="flex items-center justify-between px-3 py-2 text-sm cursor-pointer 
            border-b hover:bg-gray-50
            {{ $isSelected ? 'bg-green-100' : '' }}">

        <div class="flex items-center space-x-2">
            <span class="text-gray-700 font-medium">{{ $account->code }}</span>
            <span class="text-gray-600">{{ $account->intitule }}</span>
        </div>

        @if($type === 'old')
            <button wire:click="selectOld({{ $account->id }})"
                class="text-xs px-2 py-1 border rounded hover:bg-gray-100">
                Choisir
            </button>
        @else
            <button wire:click="mapToNew({{ $account->id }})"
                class="text-xs px-2 py-1 border border-blue-300 text-blue-600 rounded hover:bg-blue-50">
                Mapper
            </button>
        @endif
    </div>

    @if($account->children && $account->children->count())
        <div class="ml-4 border-l pl-3">
            @foreach($account->children as $child)
                @include('livewire.mapping.partials.account-item', [
                    'account' => $child,
                    'type' => $type,
                    'level' => $level + 1,
                    'selectedId' => $selectedId,
                    'mappedIds' => $mappedIds
                ])
            @endforeach
        </div>
    @endif
</div>
