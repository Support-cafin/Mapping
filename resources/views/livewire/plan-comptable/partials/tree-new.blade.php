<div class="p-4">

    <div class="overflow-y-auto max-h-[500px] border rounded">

        <table class="w-full text-sm">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-3 py-2 text-left">Code</th>
                    <th class="px-3 py-1">Intitulé</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($this->newTree as $root)
                    @include('livewire.plan-comptable.partials.items.tree-new-item', [
                        'account' => $root,
                        'level' => 0
                    ])
                @empty
                    <tr>
                        <td class="px-3 py-2 text-gray-500">Aucun compte trouvé</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    </div>

</div>
