<x-app-layout>
    <div class="min-h-screen bg-gray-50 mt-12">
        <!-- Header -->
        <div class="mb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="py-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">
                                <i class="fas fa-history mr-2 text-blue-600"></i>
                                Journal d'activité
                            </h1>
                            <p class="text-gray-600 mt-1">
                                Historique complet des modifications et actions dans votre entreprise
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                                {{ $entreprise->nom }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Section statistiques -->
            <div class="mb-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Total actions -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-500">Actions totales</p>
                                <p class="text-3xl font-bold text-gray-900 mt-2">{{ $stats['total'] }}</p>
                            </div>
                            <div class="p-3 bg-blue-100 rounded-lg">
                                <i class="fas fa-chart-line text-2xl text-blue-600"></i>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-green-600">
                            <i class="fas fa-arrow-up mr-1"></i>
                            {{ $stats['today'] }} aujourd'hui
                        </div>
                    </div>
                    
                    <!-- Par modèle -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-medium text-gray-700">Par type</p>
                            <i class="fas fa-layer-group text-gray-400"></i>
                        </div>
                        <div class="space-y-2">
                            @foreach($stats['by_model'] as $model => $count)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ $model }}</span>
                                    <span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs font-medium">
                                        {{ $count }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Par action -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-medium text-gray-700">Par action</p>
                            <i class="fas fa-bolt text-gray-400"></i>
                        </div>
                        <div class="space-y-2">
                            @foreach($stats['by_action'] as $action => $count)
                                @php
                                    $colors = [
                                        'create' => 'bg-green-100 text-green-800',
                                        'update' => 'bg-blue-100 text-blue-800',
                                        'delete' => 'bg-red-100 text-red-800',
                                        'mapping' => 'bg-purple-100 text-purple-800',
                                    ];
                                    $color = $colors[$action] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">{{ $action }}</span>
                                    <span class="px-2 py-1 {{ $color }} rounded text-xs font-medium">
                                        {{ $count }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    
                    <!-- Top utilisateurs -->
                    <div class="bg-white rounded-xl shadow-sm border p-6">
                        <div class="flex items-center justify-between mb-4">
                            <p class="text-sm font-medium text-gray-700">Top utilisateurs</p>
                            <i class="fas fa-users text-gray-400"></i>
                        </div>
                        <div class="space-y-2">
                            @foreach($stats['by_user'] as $user => $count)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600 truncate">{{ $user }}</span>
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 rounded text-xs font-medium">
                                        {{ $count }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filtres avancés -->
            <div class="bg-white rounded-xl shadow-sm border p-6 mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">
                        <i class="fas fa-filter mr-2 text-gray-400"></i>
                        Filtres avancés
                    </h2>
                    <button id="resetFilters"
                            class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
                        <i class="fas fa-redo"></i>
                        Réinitialiser
                    </button>
                </div>
                
                <form id="filterForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                
                    <!-- Modèle -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-cube mr-1"></i>
                            Type d'objet
                        </label>
                        <select name="model" 
                                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Tous les types</option>
                            @foreach($models as $model)
                                <option value="{{ $model }}" {{ $filters['model'] == $model ? 'selected' : '' }}>
                                    {{ $model }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Action -->
                    <!--<div>-->
                    <!--    <label class="block text-sm font-medium text-gray-700 mb-2">-->
                    <!--        <i class="fas fa-bolt mr-1"></i>-->
                    <!--        Type d'action-->
                    <!--    </label>-->
                    <!--    <select name="action" -->
                    <!--            class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">-->
                    <!--        <option value="">Toutes les actions</option>-->
                    <!--        @foreach($actions as $action)-->
                    <!--            <option value="{{ $action }}" {{ $filters['action'] == $action ? 'selected' : '' }}>-->
                    <!--                {{ $action }}-->
                    <!--            </option>-->
                    <!--        @endforeach-->
                    <!--    </select>-->
                    <!--</div>-->
                    
                    <!-- Utilisateur -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-1"></i>
                            Utilisateur
                        </label>
                        <select name="user_id" 
                                class="w-full p-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option value="">Tous les utilisateurs</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" {{ $filters['user_id'] == $user->id ? 'selected' : '' }}>
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Recherche -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-search mr-1"></i>
                            Recherche
                        </label>
                        <div class="relative">
                            <input type="text" 
                                   name="search" 
                                   value="{{ $filters['search'] ?? '' }}"
                                   placeholder="Code, intitulé, libellé..."
                                   class="w-full p-2 pl-10 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>
                </form>
                
                <!-- Boutons d'action -->
                <div class="mt-6 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <span id="resultsCount">{{ $logs->total() }}</span> actions trouvées
                    </div>
                    <div class="flex gap-3">
                        <!--<button type="button" -->
                        <!--        onclick="exportLogs()"-->
                        <!--        class="px-4 py-2 border border-green-600 text-green-600 rounded-lg hover:bg-green-50 -->
                        <!--               flex items-center gap-2 text-sm font-medium">-->
                        <!--    <i class="fas fa-file-excel"></i>-->
                        <!--    Exporter Excel-->
                        <!--</button>-->
                        <button type="submit" 
                                form="filterForm"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 
                                       flex items-center gap-2 text-sm font-medium">
                            <i class="fas fa-filter"></i>
                            Appliquer les filtres
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Table des logs -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
                <!-- En-tête de table -->
                <div class="px-6 py-4 border-b bg-gray-50">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900">
                            Liste des actions
                        </h3>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-600">Trier par :</span>
                            <select class="text-sm border rounded-lg p-1">
                                <option>Date (plus récent)</option>
                                <option>Date (plus ancien)</option>
                                <option>Utilisateur (A-Z)</option>
                                <option>Type d'action</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Table -->
                <div id="logsTable">
                    @include('audit-logs.partials.logs-table', ['logs' => $logs])
                </div>
            </div>
            
            <!-- Pagination -->
            <div class="mt-6">
                {{ $logs->links() }}
            </div>
            
            <!-- Légende -->
            <div class="mt-8 bg-gray-50 rounded-lg p-6 border">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">
                    <i class="fas fa-key mr-1"></i>
                    Légende des icônes
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-green-100 text-green-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-plus"></i>
                        </div>
                        <span class="text-sm text-gray-700">Création</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-edit"></i>
                        </div>
                        <span class="text-sm text-gray-700">Modification</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-red-100 text-red-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-trash"></i>
                        </div>
                        <span class="text-sm text-gray-700">Suppression</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-purple-100 text-purple-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-link"></i>
                        </div>
                        <span class="text-sm text-gray-700">Mapping</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Export form (caché) -->
    <form id="exportForm" action="{{ route('audit-logs.export') }}" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="date_start" value="{{ $filters['date_start'] }}">
        <input type="hidden" name="date_end" value="{{ $filters['date_end'] }}">
        <input type="hidden" name="model" value="{{ $filters['model'] }}">
        <input type="hidden" name="action" value="{{ $filters['action'] }}">
        <input type="hidden" name="user_id" value="{{ $filters['user_id'] }}">
        <input type="hidden" name="search" value="{{ $filters['search'] }}">
    </form>
    
    @push('scripts')
    <script>
    // Variables globales
    let isLoading = false;
    
    // Filtrer avec AJAX
    document.getElementById('filterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        if (isLoading) return;
        
        isLoading = true;
        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        
        // Afficher le loader
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Chargement...';
        submitBtn.disabled = true;
        
        // Faire la requête AJAX
        fetch('{{ route("audit-logs.filter") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        })
        .then(response => response.json())
        .then(data => {
            // Mettre à jour le tableau
            document.getElementById('logsTable').innerHTML = data.html;
            
            // Mettre à jour le compteur
            document.getElementById('resultsCount').textContent = data.count;
            
            // Mettre à jour les statistiques (optionnel)
            updateStats(data.stats);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Une erreur est survenue lors du filtrage.');
        })
        .finally(() => {
            // Réinitialiser le bouton
            submitBtn.innerHTML = '<i class="fas fa-filter"></i> Appliquer les filtres';
            submitBtn.disabled = false;
            isLoading = false;
        });
    });
    
    // Réinitialiser les filtres
    document.getElementById('resetFilters').addEventListener('click', function() {
        const form = document.getElementById('filterForm');
        form.reset();
        form.querySelector('input[name="date_start"]').value = '{{ now()->subDays(7)->format("Y-m-d") }}';
        form.querySelector('input[name="date_end"]').value = '{{ now()->format("Y-m-d") }}';
        form.dispatchEvent(new Event('submit'));
    });
    
    // Exporter les logs
    function exportLogs() {
        // Remplir le formulaire caché avec les filtres actuels
        const formData = new FormData(document.getElementById('filterForm'));
        const exportForm = document.getElementById('exportForm');
        
        for (const [key, value] of formData.entries()) {
            exportForm.querySelector(`input[name="${key}"]`).value = value;
        }
        
        // Soumettre le formulaire
        exportForm.submit();
    }
    
    // Imprimer la page
    function printPage() {
        window.print();
    }
    
    // Mettre à jour les statistiques (exemple)
    function updateStats(stats) {
        // Implémentez la mise à jour des cartes de statistiques si besoin
        console.log('Stats mises à jour:', stats);
    }
    
    // Auto-refresh toutes les 60 secondes (optionnel)
    setInterval(() => {
        if (!isLoading && document.visibilityState === 'visible') {
            document.getElementById('filterForm').dispatchEvent(new Event('submit'));
        }
    }, 60000);
    
    // Fonction pour afficher plus de détails
    function showDetails(logId) {
        window.open(`/audit-logs/${logId}`, '_blank');
    }
    
    // Initialiser les tooltips
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser les tooltips Bootstrap si utilisés
        if (typeof bootstrap !== 'undefined') {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        // Auto-submit au chargement si des filtres sont présents dans l'URL
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.toString()) {
            setTimeout(() => {
                document.getElementById('filterForm').dispatchEvent(new Event('submit'));
            }, 500);
        }
    });
    </script>
    
    <style>
    /* Styles d'impression */
    @media print {
        .no-print { display: none !important; }
        body { background: white; }
        .max-w-7xl { max-width: none !important; }
        .shadow-sm, .shadow, .shadow-lg { box-shadow: none !important; }
        .border, .border-b { border: 1px solid #e5e7eb !important; }
    }
    
    /* Animation pour les nouvelles entrées */
    @keyframes highlightNew {
        0% { background-color: rgba(59, 130, 246, 0.1); }
        100% { background-color: transparent; }
    }
    
    .highlight-new {
        animation: highlightNew 2s ease-out;
    }
    
    /* Styles pour la table */
    .table-row-hover:hover {
        background-color: #f9fafb;
        cursor: pointer;
    }
    
    /* Scrollbar personnalisée */
    .scrollbar-thin {
        scrollbar-width: thin;
    }
    
    .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 3px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
        background: #a1a1a1;
    }
    </style>
    @endpush
</x-app-layout>