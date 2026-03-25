@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" style="margin-top: 30px;">
        <!-- Header -->
        <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Détails de l'ONG</h1>
                <p class="mt-1 text-sm text-gray-600">Consultez les informations de l'ONG</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <a href="{{ route('admin.entreprises.edit', $entreprise) }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Modifier
                </a>
                <form action="{{ route('admin.entreprises.destroy', $entreprise) }}" method="POST" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" 
                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ? Cette action est irréversible.')"
                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Supprimer
                    </button>
                </form>
                <a href="{{ route('admin.entreprises.index') }}" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Retour
                </a>
            </div>
        </div>

        <!-- Information Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Informations générales -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Informations générales</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Nom</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->nom }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Code</label>
                            <p class="mt-1 text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    {{ $entreprise->code }}
                                </span>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Sigle usuel</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->sigle_usuel ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Type de compte</label>
                            <p class="mt-1">
                                @php
                                    $typeColors = [
                                        'standard' => 'bg-blue-100 text-blue-800',
                                        'premium' => 'bg-purple-100 text-purple-800',
                                        'enterprise' => 'bg-indigo-100 text-indigo-800'
                                    ];
                                    $typeColor = $typeColors[$entreprise->type_compte] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $typeColor }}">
                                    {{ ucfirst($entreprise->type_compte ?? 'Non défini') }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistiques rapides -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Statistiques</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Utilisateurs</span>
                        <span class="text-2xl font-semibold text-gray-900">{{ $adminUsers ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Date de création</span>
                        <span class="text-sm text-gray-900">{{ $entreprise->created_at ? $entreprise->created_at->format('d/m/Y') : '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Dernière modification</span>
                        <span class="text-sm text-gray-900">{{ $entreprise->updated_at ? $entreprise->updated_at->format('d/m/Y H:i') : '—' }}</span>
                    </div>
                </div>
            </div>

            <!-- Coordonnées -->
            <div class="lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Coordonnées</h2>
                </div>
                <div class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Téléphone</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($entreprise->telephone)
                                    <a href="tel:{{ $entreprise->telephone }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $entreprise->telephone }}
                                    </a>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email</label>
                            <p class="mt-1 text-sm text-gray-900">
                                @if($entreprise->email)
                                    <a href="mailto:{{ $entreprise->email }}" class="text-indigo-600 hover:text-indigo-900">
                                        {{ $entreprise->email }}
                                    </a>
                                @else
                                    —
                                @endif
                            </p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-500">Adresse</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->adresse ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Pays</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->pays_id ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informations fiscales -->
            <div class="lg:col-span-3 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Informations fiscales</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Numéro fiscal</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->numero_fiscal ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Numéro d'agrément</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->numero_agrement ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Registre de commerce</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->registre_commerce ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Numéro NINEA</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $entreprise->numero_ninea ?? '—' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mes users -->
            <div class="lg:col-span-3 bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-medium text-gray-900">Liste Utilisateurs</h2>
                </div>
                @foreach($adminEntreprisesUsers as $adminEntreprisesUser)
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-6 lg:grid-cols-6 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Prénnom & Nom</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $adminEntreprisesUser->name ?? '—' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email :</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $adminEntreprisesUser->email ?? '—' }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection