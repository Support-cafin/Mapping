<div class="space-y-5">
    <div class="grid grid-cols-2 gap-4">
        {{-- DATE --}}
        <div>
            <label class="font-semibold text-gray-700" style="font-size: 9px;">Date</label> 
            <input type="date" wire:model="date_ecriture"
                class="w-full px-3 py-2 border rounded-lg @error('date_ecriture') border-red-500 @enderror" style="font-size: 9px;">
            @error('date_ecriture') 
                <p class="text-sm text-red-600" style="font-size: 9px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- JOURNAL --}}
           <!-- Journal (Input avec suggestions) -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1" style="font-size: 9px;">
                    <i class="fas fa-book mr-1"></i>
                    Code Journal (optionnel)
                </label>
                
                <div class="relative">
                    <input type="text" 
                           wire:model="journal_code"
                           list="journaux-list"
                           placeholder="Ex: ACH, VTE, BQ..."
                           maxlength="20"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 uppercase"
                           style="text-transform: uppercase;">
                    
                    <!-- Datalist pour suggestions -->
                    <datalist id="journaux-list">
                        <option value="RAN">RAN</option>
                        @foreach($journaux_codes as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </datalist>
                    
                    <div class="absolute right-3 top-2.5 text-gray-400">
                        <i class="fas fa-search text-xs"></i>
                    </div>
                </div>
                
                <p class="mt-1 text-xs text-gray-500" style="font-size: 9px;">
                    💡 Tapez le code journal ou laissez vide
                </p> 
                
                @if(count($journaux_codes) > 0)
                    <div class="mt-2 flex flex-wrap gap-1">
                        <span class="text-xs text-gray-500" style="font-size: 9px;">Suggestions :</span>
                        @foreach(array_slice($journaux_codes, 0, 5) as $code)
                            <button type="button"
                                    wire:click="$set('journal_code', '{{ $code }}')"
                                    class="inline-flex items-center px-2 py-1 rounded text-xs bg-gray-100 text-gray-700 hover:bg-gray-200">
                                {{ $code }}
                            </button>
                        @endforeach
                    </div>
                @endif
                
                @error('journal_code') 
                    <p class="mt-1 text-xs text-red-600" style="font-size: 9px;">{{ $message }}</p> 
                @enderror
            </div>
    </div>

    {{-- LIBELLE --}}
    <div>
        <label class="font-semibold text-gray-700" style="font-size: 9px;">Libellé</label>
        <input type="text" wire:model="libelle"
            class="w-full px-3 py-2 border rounded-lg @error('libelle') border-red-500 @enderror">
        @error('libelle') 
            <p class="text-sm text-red-600" style="font-size: 9px;">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-2 gap-4">
        {{-- DEBIT --}}
        <div>
            <label class="font-semibold text-gray-700" style="font-size: 9px;">Débit</label>
            <input type="number" step="0.01" wire:model="debit"
                class="w-full px-3 py-2 border rounded-lg @error('debit') border-red-500 @enderror" style="font-size: 9px;">
            @error('debit') 
                <p class="text-sm text-red-600" style="font-size: 9px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- CREDIT --}}
        <div>
            <label class="font-semibold text-gray-700" style="font-size: 9px;">Crédit</label>
            <input type="number" step="0.01"
               wire:model.live="credit"
               class="w-full px-3 py-2 border rounded-lg @error('credit') border-red-500 @enderror"
               style="font-size: 9px;">
            @error('credit') 
                <p class="text-sm text-red-600" style="font-size: 9px;">{{ $message }}</p>
            @enderror
        </div>

        {{-- PIECE --}}
        <div>
            <label class="font-semibold text-gray-700" style="font-size: 9px;">Pièce</label>
            <input type="text" wire:model="piece"
                class="w-full px-3 py-2 border rounded-lg @error('piece') border-red-500 @enderror" style="font-size: 9px;">
            @error('piece') 
                <p class="text-sm text-red-600" style="font-size: 9px;">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- ANCIEN COMPTE --}}
    <div>
        <label class="font-semibold text-gray-700" style="font-size: 9px;">Compte Entité</label>
        <select wire:model="old_account_id"
            class="w-full px-3 py-2 border rounded-lg @error('old_account_id') border-red-500 @enderror">
            <option value="" style="font-size: 9px;">Choisir un compte entité...</option>
            @foreach($old_accounts as $account)
                <option value="{{ $account->id }}" style="font-size: 9px;">
                    {{ $account->code }} — {{ $account->intitule }}
                </option>
            @endforeach
        </select>
        @error('old_account_id') 
            <p class="text-sm text-red-600" style="font-size: 9px;">{{ $message }}</p>
        @enderror
    </div>

    {{-- AFFICHAGE DU MAPPING --}}
    @if($old_account_id)
        <div class="border rounded-lg p-4 bg-gray-50">
            <h3 class="font-semibold text-gray-700 mb-2" style="font-size: 9px;">État du mapping :</h3>
            
            @if($mapping_exists && $mapped_new_account)
                <div class="flex items-center space-x-4 p-3 bg-green-50 rounded border border-green-200">
                    <div class="text-green-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center space-x-2">
                            <div class="font-mono bg-orange-100 text-orange-800 px-2 py-1 rounded text-sm" style="font-size: 9px;">
                                {{-- Afficher le code de l'ancien compte --}}
                                @php
                                    $oldAccount = \App\Models\OldAccount::find($old_account_id);
                                @endphp
                                {{ $oldAccount->code ?? '' }}
                            </div>
                            <div class="text-gray-400">
                                <i class="fas fa-arrow-right"></i>
                            </div>
                            <div class="font-mono bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm" style="font-size: 9px;">
                                {{ $mapped_new_account->code }}
                            </div>
                        </div>
                        <div class="mt-1 text-sm text-gray-600" style="font-size: 9px;">
                            <span class="font-medium" style="font-size: 9px;">Nouveau compte :</span> 
                            {{ $mapped_new_account->intitule }}
                        </div>
                        @if($mapping_details && $mapping_details->commentaire)
                            <div class="mt-1 text-sm text-gray-500" style="font-size: 9px;">
                                <span class="font-medium" style="font-size: 9px;">Note :</span> 
                                {{ $mapping_details->commentaire }}
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex items-center space-x-4 p-3 bg-yellow-50 rounded border border-yellow-200">
                    <div class="text-yellow-600">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <div class="font-medium text-yellow-800" style="font-size: 9px;">Non mappé</div>
                        <div class="text-sm text-yellow-600" style="font-size: 9px;">
                            Cet compte entité n'a pas encore de correspondance dans le nouveau plan comptable.
                        </div>
                        @php
                            $oldAccount = \App\Models\OldAccount::find($old_account_id);
                        @endphp
                        @if($oldAccount)
                            <div class="mt-1 text-sm" style="font-size: 9px;">
                                <span class="font-medium" style="font-size: 9px;">Compte sélectionné :</span>
                                <span class="font-mono bg-orange-100 text-orange-800 px-2 py-0.5 rounded ml-1" style="font-size: 9px;">
                                    {{ $oldAccount->code }}
                                </span> — {{ $oldAccount->intitule }}
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- BUTTON --}}
    <div class="flex justify-end pt-4">
        <button wire:click="save"
            class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700" style="font-size: 9px;">
            Enregistrer l'écriture
        </button>
    </div>
</div>