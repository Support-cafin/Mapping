{{-- resources/views/admin/entreprises/index.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-12">
        <!-- En-tête -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Gestion des entreprises</h1>
                <p class="text-gray-600 mt-1">Liste de toutes les entreprises du système</p>
            </div>
            <a href="{{ route('admin.entreprises.create') }}" 
               class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <ion-icon name="business" class="mr-2"></ion-icon>
                Ajouter une entreprise
            </a>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <ion-icon name="business" class="text-2xl text-blue-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total entreprises</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $entreprises->count() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <ion-icon name="people" class="text-2xl text-purple-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total utilisateurs</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $totalUsers }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <ion-icon name="shield-checkmark" class="text-2xl text-green-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Admins entreprises</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $adminUsers }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <ion-icon name="person" class="text-2xl text-yellow-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Utilisateurs normaux</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $regularUsers }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tableau des entreprises -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Filtres et recherche -->
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                    <input type="text" 
                           id="searchInput" 
                           placeholder="Rechercher une entreprise..." 
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    
                    <select id="filterSort" 
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="name_asc">Nom (A-Z)</option>
                        <option value="name_desc">Nom (Z-A)</option>
                        <option value="users_desc">Plus d'utilisateurs</option>
                        <option value="users_asc">Moins d'utilisateurs</option>
                        <option value="newest">Plus récentes</option>
                        <option value="oldest">Plus anciennes</option>
                    </select>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Entreprise
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Code
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Utilisateurs
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date création
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="entreprisesTableBody">
                        @forelse($entreprises as $entreprise)
                            <tr class="hover:bg-gray-50 transition-colors entreprise-row"
                                data-name="{{ strtolower($entreprise->nom) }}"
                                data-code="{{ strtolower($entreprise->code) }}"
                                data-users="{{ $entreprise->users_count }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <ion-icon name="business" class="text-blue-600 text-xl"></ion-icon>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ $entreprise->nom }}
                                            </div>
                                            @if($entreprise->adresse)
                                                <div class="text-xs text-gray-500 truncate max-w-xs">
                                                    {{ $entreprise->adresse }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        {{ $entreprise->code }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        @if($entreprise->email)
                                            <div class="flex items-center">
                                                <ion-icon name="mail" class="text-gray-400 mr-1"></ion-icon>
                                                <span>{{ $entreprise->email }}</span>
                                            </div>
                                        @endif
                                        @if($entreprise->telephone)
                                            <div class="flex items-center mt-1">
                                                <ion-icon name="call" class="text-gray-400 mr-1"></ion-icon>
                                                <span>{{ $entreprise->telephone }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                            <span class="text-xs font-bold text-blue-800">{{ $entreprise->users_count }}</span>
                                        </div>
                                        <div class="text-sm text-gray-900">
                                            {{ $entreprise->users_count }} utilisateur{{ $entreprise->users_count > 1 ? 's' : '' }}
                                            @if($entreprise->admins_count > 0)
                                                <div class="text-xs text-purple-600">
                                                    {{ $entreprise->admins_count }} admin{{ $entreprise->admins_count > 1 ? 's' : '' }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $entreprise->created_at->format('d/m/Y') }}
                                    <div class="text-xs text-gray-400">
                                        {{ $entreprise->created_at->diffForHumans() }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <!--<a href="{{ route('admin.entreprises.show', $entreprise->id) }}" -->
                                        <!--   class="text-blue-600 hover:text-blue-900"-->
                                        <!--   title="Voir détails">-->
                                        <!--    <ion-icon name="eye" class="text-lg"></ion-icon>-->
                                        <!--</a>-->
                                        <!--<a href="{{ route('admin.entreprises.edit', $entreprise->id) }}" -->
                                        <!--   class="text-yellow-600 hover:text-yellow-900"-->
                                        <!--   title="Modifier">-->
                                        <!--    <ion-icon name="create" class="text-lg"></ion-icon>-->
                                        <!--</a>-->
                                        <!--<a href="{{ route('admin.users.index', ['entreprise' => $entreprise->id]) }}" -->
                                        <!--   class="text-green-600 hover:text-green-900"-->
                                        <!--   title="Voir utilisateurs">-->
                                        <!--    <ion-icon name="people" class="text-lg"></ion-icon>-->
                                        <!--</a>-->
                                        <form action="{{ route('admin.entreprises.destroy', $entreprise->id) }}" 
                                              method="POST" 
                                              class="inline"
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ? Tous les utilisateurs associés seront également supprimés.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900"
                                                    title="Supprimer">
                                                <ion-icon name="trash" class="text-lg"></ion-icon>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr id="noResultsRow" class="{{ $entreprises->count() > 0 ? 'hidden' : '' }}">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="text-gray-400">
                                        <ion-icon name="business" class="text-4xl mb-2"></ion-icon>
                                        <p class="text-lg">Aucune entreprise trouvée</p>
                                        <p class="text-sm mt-1">Commencez par créer votre première entreprise</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchInput');
        const filterSort = document.getElementById('filterSort');
        const entrepriseRows = document.querySelectorAll('.entreprise-row');
        const noResultsRow = document.getElementById('noResultsRow');

        function filterEntreprises() {
            const searchTerm = searchInput.value.toLowerCase();
            const sortValue = filterSort.value;

            let visibleRows = [];

            // Filtrer par recherche
            entrepriseRows.forEach(row => {
                const name = row.dataset.name;
                const code = row.dataset.code;
                
                let show = true;
                
                // Filtre par recherche
                if (searchTerm && !name.includes(searchTerm) && !code.includes(searchTerm)) {
                    show = false;
                }
                
                if (show) {
                    row.style.display = '';
                    visibleRows.push(row);
                } else {
                    row.style.display = 'none';
                }
            });

            // Trier les résultats visibles
            sortEntreprises(visibleRows, sortValue);

            // Réorganiser le tableau
            const tbody = document.getElementById('entreprisesTableBody');
            visibleRows.forEach(row => {
                tbody.appendChild(row);
            });

            // Afficher/masquer le message "aucun résultat"
            if (noResultsRow) {
                if (visibleRows.length === 0) {
                    noResultsRow.style.display = '';
                } else {
                    noResultsRow.style.display = 'none';
                }
            }
        }

        function sortEntreprises(rows, sortBy) {
            rows.sort((a, b) => {
                switch(sortBy) {
                    case 'name_asc':
                        return a.dataset.name.localeCompare(b.dataset.name);
                    case 'name_desc':
                        return b.dataset.name.localeCompare(a.dataset.name);
                    case 'users_desc':
                        return parseInt(b.dataset.users) - parseInt(a.dataset.users);
                    case 'users_asc':
                        return parseInt(a.dataset.users) - parseInt(b.dataset.users);
                    case 'newest':
                        return 0; // À implémenter avec data-date si nécessaire
                    case 'oldest':
                        return 0; // À implémenter avec data-date si nécessaire
                    default:
                        return 0;
                }
            });
        }

        // Écouteurs d'événements
        searchInput.addEventListener('input', filterEntreprises);
        filterSort.addEventListener('change', filterEntreprises);
        
        // Initialiser le filtrage
        filterEntreprises();
    });
    </script>
    @endpush
</x-app-layout>