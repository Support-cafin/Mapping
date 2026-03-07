<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Message d'état -->
        @if(session('error'))
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                    <span class="font-medium text-red-800">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- En-tête -->
            <div class="px-6 py-4 {{ $isEditMode ? 'bg-green-50 border-l-4 border-green-400' : 'bg-blue-50 border-l-4 border-blue-400' }}">
                <h2 class="text-xl font-semibold text-gray-800">
                    {{ $isEditMode ? 'Modifier le donateur' : 'Nouveau donateur' }}
                </h2>
                @if($isEditMode)
                    <p class="text-sm text-gray-600 mt-1">
                        Modification du donateur #{{ $donateurId ?? 'N/A' }}
                    </p>
                @endif
            </div>

            <!-- Formulaire -->
            <form wire:submit.prevent="save" class="p-6">
                <!-- Debug info -->
                <div class="mb-4 p-3 bg-gray-50 rounded border text-xs">
                    <strong>Debug:</strong> Mode: {{ $isEditMode ? 'Édition' : 'Création' }} | 
                    ID: {{ $donateurId ?? 'Nouveau' }} | 
                    Entreprise: {{ $entreprise_id ?? 'N/A' }}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Colonne gauche -->
                    <div class="space-y-4">
                        <!-- Numéro d'enregistrement -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Numéro d'enregistrement *
                            </label>
                            <input type="text" 
                                   wire:model="numero_enregistrement"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   {{ $isEditMode ? 'readonly' : '' }}>
                            @error('numero_enregistrement')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Date *
                            </label>
                            <input type="date" 
                                   wire:model="date"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('date')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Dénomination -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Dénomination *
                            </label>
                            <input type="text" 
                                   wire:model="denomination"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Ex: Société XYZ SARL">
                            @error('denomination')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nom et prénoms -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Nom et prénoms
                            </label>
                            <input type="text" 
                                   wire:model="nom_prenoms"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Ex: Jean Dupont">
                        </div>
                    </div>

                    <!-- Colonne droite -->
                    <div class="space-y-4">
                        <!-- Montant -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Montant du don *
                            </label>
                            <div class="relative">
                                <input type="number" 
                                       step="0.01"
                                       wire:model="montant_don"
                                       class="w-full pl-10 pr-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                       placeholder="0.00">
                                <div class="absolute left-3 top-2 text-gray-600">{{ $devise }}</div>
                            </div>
                            @error('montant_don')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Mode de libération -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Mode de libération *
                            </label>
                            <select wire:model="mode_liberation"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="virement" {{ $mode_liberation == 'virement' ? 'selected' : '' }}>Virement</option>
                                <option value="chèque" {{ $mode_liberation == 'chèque' ? 'selected' : '' }}>Chèque</option>
                                <option value="espèces" {{ $mode_liberation == 'espèces' ? 'selected' : '' }}>Espèces</option>
                                <option value="nature" {{ $mode_liberation == 'nature' ? 'selected' : '' }}>Nature</option>
                            </select>
                        </div>

                        <!-- Registre de commerce -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Registre de Commerce
                            </label>
                            <input type="text" 
                                   wire:model="registre_commerce"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Ex: RC 123456">
                        </div>

                        <!-- Numéro d'identification fiscal -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Numéro d'identification fiscal
                            </label>
                            <input type="text" 
                                   wire:model="numero_identification_fiscal"
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                   placeholder="Ex: NIF 987654321">
                        </div>
                    </div>
                </div>

                <!-- Adresse siège social -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Adresse siège social
                    </label>
                    <textarea wire:model="adresse_siege_social" 
                              rows="2"
                              class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Adresse complète..."></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-4">
                    <!-- Emails -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Emails
                            </label>
                            @foreach($email as $index => $emailItem)
                                <div class="flex gap-2 mb-2">
                                    <input type="email" 
                                           wire:model="email.{{ $index }}"
                                           class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                           placeholder="email@exemple.com">
                                    @if($index > 0)
                                        <button type="button" 
                                                wire:click="removeEmail({{ $index }})"
                                                class="px-3 py-2 bg-red-50 text-red-600 rounded hover:bg-red-100">
                                            ×
                                        </button>
                                    @endif
                                </div>
                                @error('email.' . $index)
                                    <p class="mt-1 text-xs text-red-600 mb-2">{{ $message }}</p>
                                @enderror
                            @endforeach
                            <button type="button" 
                                    wire:click="addEmail"
                                    class="mt-2 px-3 py-2 text-sm bg-gray-50 text-gray-700 rounded hover:bg-gray-100">
                                + Ajouter un email
                            </button>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Compte comptable Sycbnl
                            </label>
                            <select wire:model="compte_id"
                                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-dark">
                                <option value="">Sélectionner un compte</option>
                                @foreach($comptes as $compte)
                                    <option value="{{ $compte->new_account_id }}">{{ $compte->newAccount->code }} - {{ $compte->newAccount->intitule }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <!-- Notes -->
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Notes
                    </label>
                    <textarea wire:model="notes" 
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Notes supplémentaires..."></textarea>
                </div>

                <!-- Boutons -->
                <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('donateurs.index') }}" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 {{ $isEditMode ? 'bg-green-600 hover:bg-green-700' : 'bg-blue-600 hover:bg-blue-700' }} text-white rounded">
                        {{ $isEditMode ? 'Mettre à jour' : 'Enregistrer' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>