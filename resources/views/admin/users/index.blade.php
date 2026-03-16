<x-app-layout>
    <div class="container mx-auto px-4 py-12">
        <!-- En-tête -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    Gestion des utilisateurs
                </h1>
                <p class="text-gray-600 mt-1">
                    Liste de tous les utilisateurs du système
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}"
               class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                <ion-icon name="person-add" class="mr-2"></ion-icon>
                Ajouter un utilisateur
            </a>
        </div>
         @if(Auth::user()->is_admin == 1 AND Auth::user()->is_Super_admin == 1)
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <ion-icon name="people" class="text-2xl text-blue-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Total utilisateurs</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <ion-icon name="shield-checkmark" class="text-2xl text-purple-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Administrateurs</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->where('is_admin', true)->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <ion-icon name="business" class="text-2xl text-green-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Entreprises</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $entreprises->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <ion-icon name="calendar" class="text-2xl text-yellow-600"></ion-icon>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">Ajoutés ce mois</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $users->where('created_at', '>=', now()->subMonth())->count() }}</p>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Tableau des utilisateurs -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- Filtres -->
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                    <input type="text"
                           id="searchInput"
                           placeholder="Rechercher un utilisateur..."
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <select id="filterRole"
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous les rôles</option>
                        <option value="admin">Administrateurs</option>
                        <option value="user">Utilisateurs normaux</option>
                    </select>
                    @if(Auth::user()->is_admin == 1 AND Auth::user()->is_Super_admin == 1)
                    <select id="filterEntreprise"
                            class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Toutes les entreprises</option>
                        @foreach($entreprises as $entreprise)
                            <option value="{{ $entreprise->id }}">{{ $entreprise->nom }}</option>
                        @endforeach
                    </select>
                    @endif
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Utilisateur
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Entreprise
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rôle
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date création
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                        @php $entre = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->first();  @endphp
                            @forelse($users as $user)
                                @if($entre->id === $user->entreprise_id)
                                    <tr class="hover:bg-gray-50 transition-colors user-row"
                                    data-name="{{ strtolower($user->name) }}"
                                    data-email="{{ strtolower($user->email) }}"
                                    data-role="{{ $user->is_admin ? 'admin' : 'user' }}"
                                    data-entreprise="{{ $user->entreprise_id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-800 font-bold">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $user->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $user->email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $user->entreprise->nom ?? 'Non attribué' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->is_admin)
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                <ion-icon name="shield-checkmark" class="mr-1"></ion-icon>
                                                Administrateur
                                            </span>
                                        @else
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <ion-icon name="person" class="mr-1"></ion-icon>
                                                Utilisateur
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('d/m/Y') }}
                                        <div class="text-xs text-gray-400">
                                            {{ $user->created_at->format('H:i') }}
                                        </div>
                                    </td>
                                </tr>
                                @else
                                    @if(Auth::user()->is_admin == 1 AND Auth::user()->is_Super_admin == 1)
                                      <tr class="hover:bg-gray-50 transition-colors user-row"
                                    data-name="{{ strtolower($user->name) }}"
                                    data-email="{{ strtolower($user->email) }}"
                                    data-role="{{ $user->is_admin ? 'admin' : 'user' }}"
                                    data-entreprise="{{ $user->entreprise_id }}">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-blue-800 font-bold">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    {{ $user->name }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-500">
                                            {{ $user->email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ $user->entreprise->nom ?? 'Non attribué' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->is_admin)
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                <ion-icon name="shield-checkmark" class="mr-1"></ion-icon>
                                                Administrateur
                                            </span>
                                        @else
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <ion-icon name="person" class="mr-1"></ion-icon>
                                                Utilisateur
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $user->created_at->format('d/m/Y') }}
                                        <div class="text-xs text-gray-400">
                                            {{ $user->created_at->format('H:i') }}
                                        </div>
                                    </td>
                                </tr>
                                    @endif
                                @endif
                            @empty
                                <tr id="noResultsRow" class="{{ $users->count() > 0 ? 'hidden' : '' }}">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <div class="text-gray-400">
                                            <ion-icon name="people" class="text-4xl mb-2"></ion-icon>
                                            <p class="text-lg">Aucun utilisateur trouvé</p>
                                            <p class="text-sm mt-1">Commencez par créer votre premier utilisateur</p>
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
            const filterRole = document.getElementById('filterRole');
            const filterEntreprise = document.getElementById('filterEntreprise');
            const userRows = document.querySelectorAll('.user-row');
            const noResultsRow = document.getElementById('noResultsRow');

            function filterUsers() {
                const searchTerm = searchInput.value.toLowerCase();
                const roleFilter = filterRole.value;
                const entrepriseFilter = filterEntreprise.value;

                let visibleCount = 0;

                userRows.forEach(row => {
                    const name = row.dataset.name;
                    const email = row.dataset.email;
                    const role = row.dataset.role;
                    const entreprise = row.dataset.entreprise;

                    let show = true;

                    // Filtre par recherche (nom ou email)
                    if (searchTerm && !name.includes(searchTerm) && !email.includes(searchTerm)) {
                        show = false;
                    }

                    // Filtre par rôle
                    if (roleFilter && role !== roleFilter) {
                        show = false;
                    }

                    // Filtre par entreprise
                    if (entrepriseFilter && entreprise !== entrepriseFilter) {
                        show = false;
                    }

                    if (show) {
                        row.style.display = '';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Afficher/masquer le message "aucun résultat"
                if (noResultsRow) {
                    if (visibleCount === 0) {
                        noResultsRow.style.display = '';
                    } else {
                        noResultsRow.style.display = 'none';
                    }
                }
            }

            // Écouteurs d'événements
            searchInput.addEventListener('input', filterUsers);
            filterRole.addEventListener('change', filterUsers);
            filterEntreprise.addEventListener('change', filterUsers);

            // Initialiser la recherche
            filterUsers();
        });
    </script>
    @endpush
</x-app-layout>
