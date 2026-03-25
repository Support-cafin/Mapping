@extends('layouts.admin')

@section('content')
<div class="py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8" style="margin-top: 30px;">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-900">Modifier l'ONG</h1>
            <p class="mt-1 text-sm text-gray-600">Modifiez les informations de l'ONG {{ $entreprise->nom }}</p>
        </div>

        <!-- Formulaire -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <form action="{{ route('admin.entreprises.update', $entreprise) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="p-6 space-y-6">
                    <!-- Informations de base -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations générales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="nom" class="block text-sm font-medium text-gray-700 mb-1">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="nom" 
                                       name="nom" 
                                       value="{{ old('nom', $entreprise->nom) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('nom') border-red-500 @enderror"
                                       required>
                                @error('nom')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="code" class="block text-sm font-medium text-gray-700 mb-1">
                                    Code <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="code" 
                                       name="code" 
                                       value="{{ old('code', $entreprise->code) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('code') border-red-500 @enderror"
                                       required>
                                @error('code')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="sigle_usuel" class="block text-sm font-medium text-gray-700 mb-1">Sigle usuel</label>
                                <input type="text" 
                                       id="sigle_usuel" 
                                       name="sigle_usuel" 
                                       value="{{ old('sigle_usuel', $entreprise->sigle_usuel) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="type_compte" class="block text-sm font-medium text-gray-700 mb-1">Type de compte</label>
                                <select id="type_compte" 
                                        name="type_compte"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Sélectionnez un type</option>
                                    <option value="standard" {{ old('type_compte', $entreprise->type_compte) == 'standard' ? 'selected' : '' }}>Standard</option>
                                    <option value="premium" {{ old('type_compte', $entreprise->type_compte) == 'premium' ? 'selected' : '' }}>Premium</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Coordonnées -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Coordonnées</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="telephone" class="block text-sm font-medium text-gray-700 mb-1">Téléphone</label>
                                <input type="tel" 
                                       id="telephone" 
                                       name="telephone" 
                                       value="{{ old('telephone', $entreprise->telephone) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $entreprise->email) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('email') border-red-500 @enderror">
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="md:col-span-2">
                                <label for="adresse" class="block text-sm font-medium text-gray-700 mb-1">Adresse</label>
                                <textarea id="adresse" 
                                          name="adresse" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">{{ old('adresse', $entreprise->adresse) }}</textarea>
                            </div>
                            <div class="form-group mb-3">
                            <label for="pays_id">Pays</label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('pays_id') border-red-500 @enderror" id="pays_id" name="pays_id">
                                <option value="">Sélectionnez un pays</option>
                                @foreach($pays as $p)
                                    <option value="{{ $p->libelle_fr }}" {{ old('pays_id', $entreprise->pays_id) == $p->libelle_fr ? 'selected' : '' }}>
                                        {{ $p->libelle_fr }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        </div>
                    </div>

                    <!-- Informations fiscales -->
                    <div class="border-t border-gray-200 pt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Informations fiscales</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="numero_fiscal" class="block text-sm font-medium text-gray-700 mb-1">Numéro fiscal</label>
                                <input type="text" 
                                       id="numero_fiscal" 
                                       name="numero_fiscal" 
                                       value="{{ old('numero_fiscal', $entreprise->numero_fiscal) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="numero_agrement" class="block text-sm font-medium text-gray-700 mb-1">Numéro d'agrément</label>
                                <input type="text" 
                                       id="numero_agrement" 
                                       name="numero_agrement" 
                                       value="{{ old('numero_agrement', $entreprise->numero_agrement) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="registre_commerce" class="block text-sm font-medium text-gray-700 mb-1">Registre de commerce</label>
                                <input type="text" 
                                       id="registre_commerce" 
                                       name="registre_commerce" 
                                       value="{{ old('registre_commerce', $entreprise->registre_commerce) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>

                            <div>
                                <label for="numero_ninea" class="block text-sm font-medium text-gray-700 mb-1">Numéro NINEA</label>
                                <input type="text" 
                                       id="numero_ninea" 
                                       name="numero_ninea" 
                                       value="{{ old('numero_ninea', $entreprise->numero_ninea) }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 bg-gray-50 rounded-b-lg border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('admin.entreprises.show', $entreprise) }}" 
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Annuler
                    </a>
                    <button type="submit" style="background-color : blue;" 
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection