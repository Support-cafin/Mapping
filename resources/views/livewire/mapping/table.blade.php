<div class="p-6 mt-12" x-data="{
    showSuccess: false,
    successMessage: '',
    showError: false,
    errorMessage: ''
}" 
x-on:mapping-saved.window="
    showSuccess = true;
    successMessage = 'Mapping enregistré avec succès';
    setTimeout(() => showSuccess = false, 3000);
"
x-on:mapping-removed.window="
    showSuccess = true;
    successMessage = 'Mapping supprimé';
    setTimeout(() => showSuccess = false, 3000);
"
x-on:mapping-updated.window="$wire.refreshStats()">

    <!-- Header avec stats -->
    <div class="mb-6">
        <div class="flex justify-between items-start">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Table de mapping</h1>
                <p class="text-gray-600">{{ $entreprise->nom }} ({{ $entreprise->code }})</p>
                <p class="text-sm text-gray-500">Utilisateur : {{ auth()->user()->name }}</p>
            </div>
            
            <!-- Bouton retour -->
            <a href="{{ route('entreprise.dashboard') }}"
               class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                ← Retour au tableau de bord
            </a>
        </div>
        
        <!-- Stats cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <div class="bg-white p-4 rounded-lg shadow border border-gray-100">
                <p class="text-sm text-gray-500">Total comptes</p>
                <p class="text-2xl font-bold">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-100">
                <p class="text-sm text-gray-500">Mappés</p>
                <p class="text-2xl font-bold text-green-600">{{ $stats['mapped'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-100">
                <p class="text-sm text-gray-500">Non mappés</p>
                <p class="text-2xl font-bold text-orange-600">{{ $stats['unmapped'] }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-100">
                <p class="text-sm text-gray-500">Progression</p>
                <div class="flex items-center">
                    <p class="text-2xl font-bold mr-2">{{ $stats['percentage'] }}%</p>
                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" 
                             :style="`width: {{ $stats['percentage'] }}%`"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres et recherche -->
    <div class="bg-white p-4 rounded-lg shadow border border-gray-100 mb-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <!-- Recherche -->
            <div class="flex-1">
                <div class="relative">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Rechercher par code ou intitulé..."
                           class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <div class="absolute left-3 top-2.5 text-gray-400">
                        <i class="fas fa-search"></i>
                    </div>
                </div>
            </div>
            
            <!-- Filtres -->
            <div class="flex items-center space-x-4">
                <select wire:model.live="statusFilter" 
                        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">Tous les comptes</option>
                    <option value="mapped">Mappés seulement</option>
                    <option value="unmapped">Non mappés</option>
                </select>
                
                <select wire:model.live="perPage" 
                        class="px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="15">15 par page</option>
                    <option value="30">30 par page</option>
                    <option value="50">50 par page</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                            <input type="checkbox" 
                                   wire:model.live="selectAll"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            wire:click="sortBy('code')">
                            <div class="flex items-center">
                                Code ancien
                                @if($sortField === 'code')
                                    <i class="ml-2 fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                                @else
                                    <i class="ml-2 fas fa-sort text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition"
                            wire:click="sortBy('intitule')">
                            <div class="flex items-center">
                                Intitulé ancien
                                @if($sortField === 'intitule')
                                    <i class="ml-2 fas fa-sort-{{ $sortDirection === 'asc' ? 'up' : 'down' }} text-blue-500"></i>
                                @else
                                    <i class="ml-2 fas fa-sort text-gray-300"></i>
                                @endif
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Code nouveau
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Intitulé nouveau
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Coefficient
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($accounts as $account)
                        <tr class="hover:bg-gray-50 transition-colors" 
                            x-data="{ showQuickMap: false }"
                            x-on:mouseenter="showQuickMap = true"
                            x-on:mouseleave="showQuickMap = false">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" 
                                       value="{{ $account->id }}"
                                       wire:model.live="selectedIds"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-mono text-sm bg-gray-100 px-2 py-1 rounded border">
                                    {{ $account->code }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $account->intitule }}</div>
                                @if($account->classe)
                                    <div class="text-xs text-gray-500">Classe: {{ $account->classe }}</div>
                                @endif
                            </td>
                            
                            <!-- Mapping existant -->
                            @if($account->mappings->isNotEmpty())
                                @php $mapping = $account->mappings->first(); @endphp
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="font-mono text-sm bg-green-100 text-green-800 px-2 py-1 rounded border border-green-200">
                                        {{ $mapping->newAccount->code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ $mapping->newAccount->intitule }}</div>
                                    @if($mapping->commentaire)
                                        <div class="text-xs text-gray-500 italic">"{{ Str::limit($mapping->commentaire, 30) }}"</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        × {{ $mapping->coefficient }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="openEditor({{ $account->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg transition">
                                            <i class="fas fa-edit mr-1 text-sm"></i>
                                            <span>Éditer</span>
                                        </button>
                                        <button wire:click="removeMapping({{ $account->id }})"
                                                x-on:click="if(!confirm('Supprimer ce mapping ?')) return false"
                                                class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-700 hover:bg-red-100 rounded-lg transition">
                                            <i class="fas fa-trash mr-1 text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            @else
                                <!-- Non mappé -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-400 italic">Non mappé</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <button wire:click="openEditor({{ $account->id }})"
                                                class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition shadow-sm">
                                            <i class="fas fa-link mr-1"></i>
                                            <span>Mapper</span>
                                        </button>
                                        
                                        <!-- Suggestions au survol -->
                                        <div x-show="showQuickMap && $wire.quickMapSuggestions.length > 0" 
                                             x-transition
                                             class="relative">
                                            <div class="absolute left-0 mt-2 w-64 bg-white rounded-lg shadow-lg border z-10">
                                                <div class="p-2 text-xs text-gray-500 border-b">Suggestions rapides :</div>
                                                <div class="max-h-48 overflow-y-auto">
                                                    @foreach($quickMapSuggestions as $suggested)
                                                        <button wire:click="quickMap({{ $account->id }}, {{ $suggested->id }})"
                                                                class="block w-full text-left px-3 py-2 text-sm hover:bg-blue-50 hover:text-blue-700 transition">
                                                            <div class="font-medium">{{ $suggested->code }}</div>
                                                            <div class="text-xs text-gray-600 truncate">{{ $suggested->intitule }}</div>
                                                        </button>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="text-gray-400">
                                    <i class="fas fa-inbox text-4xl mb-3"></i>
                                    <p class="text-lg">
                                        @if($search)
                                            Aucun compte trouvé pour "{{ $search }}"
                                        @else
                                            Aucun compte ancien disponible.
                                        @endif
                                    </p>
                                    @if($search)
                                        <button wire:click="$set('search', '')" 
                                                class="mt-2 text-blue-600 hover:text-blue-700 text-sm">
                                            Réinitialiser la recherche
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $accounts->links() }}
        </div>
    </div>

    <!-- Modal Editor -->
    @if($showEditor)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50"
             x-transition>
            <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
                <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-link mr-2 text-blue-500"></i>
                        Éditer le mapping
                    </h3>
                    <button wire:click="closeEditor" 
                            class="text-gray-400 hover:text-gray-500">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <livewire:mapping.editor :oldAccountId="$editingOldAccountId" />
            </div>
        </div>
    @endif

    <!-- Messages toast -->
    <div x-show="showSuccess" 
         x-transition
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center space-x-3">
        <i class="fas fa-check-circle"></i>
        <span x-text="successMessage"></span>
    </div>

</div>