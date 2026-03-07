<div x-data
     x-cloak
     x-show="$wire.showAddModal"
     x-transition
     class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
         @click.outside="$wire.closeAddModal()">
        
        <!-- En-tête -->
        <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
            <h2 class="text-lg font-bold text-gray-800 flex items-center">
                <i class="fas fa-plus-circle text-indigo-500 mr-2"></i>
                Ajouter une écriture
            </h2>
            <button @click="$wire.closeAddModal()" 
                    class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>
        
        <!-- Formulaire -->
        <div class="p-6">
            <form wire:submit.prevent="addEcriture">
                
                <!-- Informations de base -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Informations de l'écriture
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Date d'écriture *
                            </label>
                            <input type="date" 
                                   wire:model="addDate"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('addDate') 
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <!-- Exercice -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Exercice *
                            </label>
                            <input type="number" 
                                   wire:model="addExercice"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            @error('addExercice') 
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <!-- Journal -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Code journal
                            </label>
                            <select wire:model="addJournal"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Sélectionner un journal</option>
                                @foreach($journaux as $journal)
                                    <option value="{{ $journal['code'] }}">
                                        {{ $journal['code'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <!-- Pièce -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Numéro de pièce
                            </label>
                            <input type="text" 
                                   wire:model="addPiece"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                   placeholder="Ex: F-2024-001">
                        </div>
                    </div>
                </div>
                
                <!-- Compte et montants -->
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-calculator text-green-500 mr-2"></i>
                        Compte et montants
                    </h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Compte -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Compte *
                            </label>
                            
                            <select wire:model="addOldAccountId"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Sélectionner un compte</option>
                                @if(isset($allOldAccounts) && $allOldAccounts->count() > 0)
                                    @foreach($allOldAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->code }} - {{ Str::limit($account->intitule, 30) }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                            
                            <!-- Recherche de compte -->
                            <div class="mt-2" x-data="{ showSearch: false }">
                                <button type="button" 
                                        @click="showSearch = !showSearch"
                                        class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-search mr-1"></i>
                                    Rechercher un compte
                                </button>
                                
                                <div x-show="showSearch" x-cloak class="mt-2">
                                    <input type="text" 
                                           wire:model.debounce.300ms="searchAccount"
                                           placeholder="Rechercher par code ou libellé..."
                                           class="w-full px-2 py-1 text-xs border rounded">
                                    
                                    <!-- Liste filtrée -->
                                    @php
                                        $searchAccount = $searchAccount ?? '';
                                        $filteredAccounts = isset($allOldAccounts) ? $allOldAccounts->filter(function($account) use ($searchAccount) {
                                            return stripos($account->code, $searchAccount) !== false || 
                                                   stripos($account->intitule, $searchAccount) !== false;
                                        }) : collect();
                                    @endphp
                                    
                                    @if($searchAccount && $filteredAccounts->isNotEmpty())
                                        <div class="mt-2 max-h-40 overflow-y-auto border rounded">
                                            @foreach($filteredAccounts as $account)
                                                <button type="button"
                                                        wire:click="$set('addOldAccountId', {{ $account->id }})"
                                                        @click="showSearch = false"
                                                        class="w-full text-left px-2 py-1 text-xs hover:bg-blue-50 border-b last:border-b-0">
                                                    <strong>{{ $account->code }}</strong> - {{ $account->intitule }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            @error('addOldAccountId') 
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <!-- Débit -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Montant Débit (FCFA)
                            </label>
                            <input type="number" 
                                   wire:model="addDebit"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                                   placeholder="0">
                            @error('addDebit') 
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <!-- Crédit -->
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                Montant Crédit (FCFA)
                            </label>
                            <input type="number" 
                                   wire:model="addCredit"
                                   min="0"
                                   step="0.01"
                                   class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                   placeholder="0">
                            @error('addCredit') 
                                <span class="text-xs text-red-600">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    
                    <!-- Avertissement débit/crédit -->
                    @if($addDebit && $addCredit)
                        <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-xs text-yellow-800 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Attention : Les deux montants (débit et crédit) sont renseignés. 
                                Normalement, une écriture ne devrait avoir qu'un seul montant.
                            </p>
                        </div>
                    @endif
                </div>
                
                <!-- Libellé et lettre -->
                <div class="mb-6">
                    <label class="block text-xs font-medium text-gray-700 mb-1">
                        Libellé de l'écriture
                    </label>
                    <textarea wire:model="addLibelle"
                              rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                              placeholder="Description de l'opération..."></textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        Maximum 255 caractères. Actuellement : {{ strlen($addLibelle ?? '') }}/255
                    </p>
                    @error('addLibelle') 
                        <span class="text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </div>
                
                <!-- Lettre -->
                <!--<div class="mb-6">-->
                <!--    <label class="block text-xs font-medium text-gray-700 mb-1">-->
                <!--        Lettre (optionnel)-->
                <!--    </label>-->
                <!--    <input type="text" -->
                <!--           wire:model="addLettre"-->
                <!--           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"-->
                <!--           placeholder="Ex: A, B, C...">-->
                <!--</div>-->
                
                <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <!-- Annuler -->
                    <button type="button" 
                            wire:click="closeAddModal"
                            class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition text-sm">
                        <i class="fas fa-times mr-2"></i>
                        Annuler
                    </button>
                    
                    <!-- Ajouter -->
                    <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-green-600 text-white hover:bg-green-700 rounded-lg transition shadow-sm flex items-center disabled:opacity-50 text-sm">
                        <span wire:loading.remove wire:target="addEcriture">
                            <i class="fas fa-plus mr-2"></i>
                            Ajouter l'écriture
                        </span>
                        <span wire:loading wire:target="addEcriture">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Ajout en cours...
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>