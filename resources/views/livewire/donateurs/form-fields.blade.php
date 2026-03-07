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
                   @if($isEditMode) readonly @endif>
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
                <option value="virement">Virement</option>
                <option value="chèque">Chèque</option>
                <option value="espèces">Espèces</option>
                <option value="nature">Nature</option>
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
        
        <!-- Compte comptable -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">
                Compte comptable Sycbnl
            </label>
            <select wire:model="compte_id"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-dark">
                <option value="">Sélectionner un compte</option>
                @foreach($comptes as $compte)
                    @if($compte->newAccount)
                        <option value="{{ $compte->newAccount->id }}">
                            {{ $compte->newAccount->code }} - {{ $compte->newAccount->intitule }} 
                        </option>
                    @endif
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