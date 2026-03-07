<x-app-layout>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">
                            <i class="fas fa-file-alt mr-2 text-blue-600"></i>
                            Détails de l'action
                        </h1>
                        <p class="text-gray-600 mt-1">
                            Consultez les informations complètes de cette action
                        </p>
                    </div>
                    <a href="{{ route('audit-logs.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 
                              flex items-center gap-2 text-gray-700">
                        <i class="fas fa-arrow-left"></i>
                        Retour au journal
                    </a>
                </div>
            </div>
            
            <!-- Carte principale -->
            <div class="bg-white rounded-xl shadow-sm border overflow-hidden mb-8">
                <!-- En-tête -->
                <div class="px-6 py-4 border-b bg-gray-50">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="flex items-center gap-3">
                                @php
                                    $colors = [
                                        'create' => 'bg-green-100 text-green-800',
                                        'update' => 'bg-blue-100 text-blue-800',
                                        'delete' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $colors[$auditLog->action] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-3 py-1 {{ $color }} rounded-full text-sm font-medium">
                                    {{ $auditLog->formatted_action }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    sur {{ $auditLog->model }}
                                </span>
                            </div>
                        </div>
                        <div class="text-sm text-gray-500">
                            #{{ $auditLog->id }}
                        </div>
                    </div>
                </div>
                
                <!-- Corps -->
                <div class="p-6">
                    <!-- Description principale -->
                    <div class="mb-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">
                            <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                            Description
                        </h2>
                        <div class="bg-blue-50 border border-blue-100 rounded-lg p-4">
                            <p class="text-gray-800">
                                <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                                <strong>{{ $auditLog->user->name ?? 'Utilisateur inconnu' }}</strong>
                                {{ strtolower($auditLog->detailed_description) }}
                            </p>
                        </div>
                    </div>
                    
                    <!-- Informations spécifiques selon le modèle -->
                    @if($auditLog->model === 'AccountMapping')
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-exchange-alt mr-2 text-purple-600"></i>
                                Détails du mapping
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Compte source -->
                                <div class="border rounded-lg overflow-hidden">
                                    <div class="bg-red-50 px-4 py-3 border-b">
                                        <h3 class="text-sm font-medium text-red-700">
                                            <i class="fas fa-database mr-2"></i>
                                            Compte source (ONG)
                                        </h3>
                                    </div>
                                    <div class="p-4">
                                        @if($auditLog->accountMapping && $auditLog->accountMapping->oldAccount)
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Code :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $auditLog->accountMapping->oldAccount->code }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Intitulé :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $auditLog->accountMapping->oldAccount->intitule }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">ID :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $auditLog->accountMapping->old_account_id }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">Informations non disponibles</p>
                                        @endif
                                    </div>
                                </div>
                                
                                <!-- Compte cible -->
                                <div class="border rounded-lg overflow-hidden">
                                    <div class="bg-green-50 px-4 py-3 border-b">
                                        <h3 class="text-sm font-medium text-green-700">
                                            <i class="fas fa-database mr-2"></i>
                                            Compte cible (SYCEBNL)
                                        </h3>
                                    </div>
                                    <div class="p-4">
                                        @if($auditLog->accountMapping && $auditLog->accountMapping->newAccount)
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Code :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $auditLog->accountMapping->newAccount->code }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Intitulé :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $auditLog->accountMapping->newAccount->intitule }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">ID :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $auditLog->accountMapping->new_account_id }}
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-500">Informations non disponibles</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @elseif(in_array($auditLog->model, ['OldAccount', 'NewAccount']))
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-database mr-2 text-blue-600"></i>
                                Détails du compte
                            </h2>
                            <div class="border rounded-lg overflow-hidden">
                                <div class="bg-blue-50 px-4 py-3 border-b">
                                    <h3 class="text-sm font-medium text-blue-700">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Informations
                                    </h3>
                                </div>
                                <div class="p-4">
                                    @php
                                        $account = $auditLog->model === 'OldAccount' 
                                            ? $auditLog->oldAccount 
                                            : $auditLog->newAccount;
                                    @endphp
                                    @if($account)
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Code :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $account->code }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Intitulé :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $account->intitule }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Classe :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $account->classe ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Niveau :</span>
                                                    <span class="text-sm font-medium text-gray-900">
                                                        {{ $account->niveau ?? 'N/A' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <p class="text-sm text-gray-500">Informations non disponibles</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Informations de base -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">
                                <i class="far fa-clock mr-2"></i>
                                Horodatage
                            </h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Date et heure :</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $auditLog->created_at->format('d/m/Y H:i:s') }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Il y a :</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $auditLog->created_at->diffForHumans() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">
                                <i class="fas fa-laptop mr-2"></i>
                                Contexte technique
                            </h3>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Adresse IP :</span>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $auditLog->ip_address }}
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-sm text-gray-600">Navigateur :</span>
                                    <span class="text-sm font-medium text-gray-900 truncate" 
                                          title="{{ $auditLog->user_agent }}">
                                        {{ Str::limit($auditLog->user_agent, 40) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Valeurs avant/après -->
                    @if($auditLog->old_values || $auditLog->new_values)
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-exchange-alt mr-2 text-purple-600"></i>
                                Modifications
                            </h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Anciennes valeurs -->
                                @if($auditLog->old_values)
                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="bg-red-50 px-4 py-3 border-b">
                                            <h3 class="text-sm font-medium text-red-700">
                                                <i class="fas fa-arrow-left mr-2"></i>
                                                Valeurs avant
                                            </h3>
                                        </div>
                                        <div class="p-4">
                                            <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- Nouvelles valeurs -->
                                @if($auditLog->new_values)
                                    <div class="border rounded-lg overflow-hidden">
                                        <div class="bg-green-50 px-4 py-3 border-b">
                                            <h3 class="text-sm font-medium text-green-700">
                                                <i class="fas fa-arrow-right mr-2"></i>
                                                Valeurs après
                                            </h3>
                                        </div>
                                        <div class="p-4">
                                            <pre class="text-sm text-gray-700 whitespace-pre-wrap">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                    
                    <!-- Résumé des changements -->
                    @if($auditLog->changes_summary)
                        <div class="mb-8">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                                <i class="fas fa-list mr-2 text-orange-600"></i>
                                Résumé des changements
                            </h2>
                            <div class="bg-orange-50 border border-orange-100 rounded-lg p-4">
                                <p class="text-gray-800">
                                    {{ ucfirst($auditLog->changes_summary) }}
                                </p>
                            </div>
                        </div>
                    @endif
                    
                    <!-- Métadonnées -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-database mr-2"></i>
                            Métadonnées
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <span class="text-xs text-gray-500">ID du modèle :</span>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $auditLog->model_id ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">Entreprise :</span>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $auditLog->entreprise->nom ?? 'N/A' }}
                                </p>
                            </div>
                            <div>
                                <span class="text-xs text-gray-500">ID utilisateur :</span>
                                <p class="text-sm font-medium text-gray-900">
                                    {{ $auditLog->user_id ?? 'Système' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <div class="px-6 py-4 border-t bg-gray-50 flex justify-between items-center">
                    <div class="text-sm text-gray-600">
                        <i class="far fa-eye mr-1"></i>
                        Consultation unique
                    </div>
                    <div class="flex gap-2">
                        <button onclick="printPage()"
                                class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50 
                                       flex items-center gap-1 text-gray-700">
                            <i class="fas fa-print"></i>
                            Imprimer
                        </button>
                        <button onclick="copyToClipboard()"
                                class="px-3 py-1 border border-blue-300 rounded text-sm hover:bg-blue-50 
                                       flex items-center gap-1 text-blue-700">
                            <i class="fas fa-copy"></i>
                            Copier les infos
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <div class="flex justify-between">
                @if($previous = \App\Models\AuditLog::where('id', '<', $auditLog->id)
                    ->where('entreprise_id', $auditLog->entreprise_id)
                    ->orderBy('id', 'desc')
                    ->first())
                    <a href="{{ route('audit-logs.show', $previous) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 
                              flex items-center gap-2 text-gray-700">
                        <i class="fas fa-chevron-left"></i>
                        Action précédente
                    </a>
                @else
                    <div></div>
                @endif
                
                @if($next = \App\Models\AuditLog::where('id', '>', $auditLog->id)
                    ->where('entreprise_id', $auditLog->entreprise_id)
                    ->orderBy('id', 'asc')
                    ->first())
                    <a href="{{ route('audit-logs.show', $next) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 
                              flex items-center gap-2 text-gray-700">
                        Action suivante
                        <i class="fas fa-chevron-right"></i>
                    </a>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
    function printPage() {
        window.print();
    }
    
    function copyToClipboard() {
        const text = `Action: {{ $auditLog->formatted_action }}
    Modèle: {{ $auditLog->model }}
    ID: {{ $auditLog->id }}
    Utilisateur: {{ $auditLog->user->name ?? 'Système' }}
    Date: {{ $auditLog->created_at->format('d/m/Y H:i:s') }}
    Description: {{ $auditLog->detailed_description }}
    {{ $auditLog->changes_summary ? "Changements: " . $auditLog->changes_summary : '' }}`;
        
        navigator.clipboard.writeText(text).then(() => {
            // Afficher une notification
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 bg-green-500 text-white p-4 rounded-lg shadow-xl z-50 animate-fade-in';
            toast.innerHTML = `
                <div class="flex items-center gap-2">
                    <i class="fas fa-check-circle"></i>
                    <span>Informations copiées !</span>
                </div>
            `;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.remove();
            }, 3000);
        });
    }
    </script>
    
    <style>
    @media print {
        .no-print { display: none !important; }
        nav, footer { display: none !important; }
        .max-w-4xl { max-width: none !important; }
        .shadow-sm { box-shadow: none !important; }
    }
    
    pre {
        font-family: 'Courier New', monospace;
        font-size: 12px;
        line-height: 1.4;
        max-height: 300px;
        overflow-y: auto;
        padding: 10px;
        background: #f8f9fa;
        border-radius: 4px;
        border: 1px solid #e9ecef;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    </style>
    @endpush
</x-app-layout>