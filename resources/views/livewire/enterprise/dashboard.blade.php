<div class="p-6 mt-10 mx-auto max-w-7xl">

    <!-- Header Entreprise -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            Tableau de bord — {{ $entreprise->nom }}
        </h1>

        <p class="text-gray-600 mt-1">
            Code : <span class="font-semibold">{{ $entreprise->code }}</span> —
            Utilisateur : <span class="font-semibold text-blue-600">{{ auth()->user()->name }}</span>
        </p>
    </div>

    <!-- GRID WIDGETS -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">

        <!-- Anciens comptes -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <p class="text-gray-500">Anciens comptes</p>
            <p class="text-3xl font-bold">{{ $old_count }}</p>

            <button wire:click="goToOldAccounts"
                class="mt-3 text-sm font-semibold text-blue-600 hover:text-blue-700">
                Voir les anciens comptes →
            </button>
        </div>

        <!-- Nouveaux comptes -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <p class="text-gray-500">Nouveaux comptes</p>
            <p class="text-3xl font-bold">{{ $new_count }}</p>

            <button wire:click="goToNewAccounts"
                class="mt-3 text-sm font-semibold text-blue-600 hover:text-blue-700">
                Voir les nouveaux comptes →
            </button>
        </div>

        <!-- Mappings -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <p class="text-gray-500">Mappings effectués</p>
            <p class="text-3xl font-bold">{{ $mapped_count }}</p>

            <button wire:click="goToMapping"
                    wire:loading.attr="disabled"
                    class="relative mt-3 text-sm font-semibold text-blue-600 hover:text-blue-700">

                <span wire:loading.remove>Gérer les mappings →</span>
                <span wire:loading class="flex items-center gap-1">
                    <span class="animate-spin h-4 w-4 border-2 border-blue-500 border-t-transparent rounded-full"></span>
                    Chargement...
                </span>
            </button>
        </div>

        <!-- Avancement -->
        <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
            <p class="text-gray-500">Progression</p>
            <p class="text-3xl font-bold">{{ $progress }}%</p>

            <!-- Progress bar -->
            <div class="w-full bg-gray-200 rounded-full h-2.5 mt-3">
                <div class="bg-blue-600 h-2.5 rounded-full" style="width: {{ $progress }}%"></div>
            </div>
        </div>

    </div>

    <!--  MAPPING RAPIDE  -->
    <div class="bg-white p-6 rounded-xl shadow mb-10">
        <h2 class="text-xl font-bold mb-4">Comptes non mappés</h2>

        @if($recent_unmapped->count() == 0)
            <p class="text-gray-500">Tous les comptes sont mappés 🎉</p>
        @else
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="py-2 text-left">Code</th>
                        <th class="py-2 text-left">Intitulé</th>
                        <th class="py-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recent_unmapped as $account)
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-2">{{ $account->code }}</td>
                            <td class="py-2">{{ $account->intitule }}</td>
                            <!-- Dans la section MAPPING RAPIDE -->
                            <td class="py-2">
                                <button wire:click="$dispatch('openMapping', { oldAccountId: {{ $account->id }} })"
                                        class="text-blue-600 hover:text-blue-700 font-semibold text-sm">
                                    Mapper →
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!--  DERNIERS MAPPINGS -->
    <div class="bg-white p-6 rounded-xl shadow mb-10">
        <h2 class="text-xl font-bold mb-4">Derniers mappings effectués</h2>

        <table class="w-full border-collapse">
            <thead>
                <tr class="border-b">
                    <th class="py-2 text-left">Ancien compte</th>
                    <th class="py-2 text-left">Nouveau compte</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recent_mappings as $map)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-2">
                            {{ $map->oldAccount->code }} — 
                            {{ $map->oldAccount->intitule }}
                        </td>
                        <td class="py-2">
                            {{ $map->newAccount->code }} — 
                            {{ $map->newAccount->intitule }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

</div>
