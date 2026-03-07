<!-- Modal Éditer -->
<div x-data="{ open: false }"
     x-cloak
     x-on:show-edit-modal.window="open = true"
     x-on:close-edit-modal.window="open = false">

    <div x-show="open"
         class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4"
         x-transition>
        
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"
             @click.outside="open = false">
            
            <!-- En-tête -->
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
                <h2 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-edit text-blue-500 mr-2"></i>
                    Modifier l'écriture
                </h2>
                <button @click="open = false" 
                        class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>
            
            <!-- Formulaire -->
            <div class="p-6">
                <form wire:submit.prevent="updateEcriture">
                    
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
                                       wire:model="editDate"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                @error('editDate') 
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <!-- Journal -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Code journal
                                </label>
                                <select wire:model="editJournal"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                    <option value="">Sélectionner un journal</option>
                                    <option value="RAN">RAN</option>
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
                                       wire:model="editPiece"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                       placeholder="Ex: F-2024-001">
                            </div>
                            
                            <!-- Exercice -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Exercice *
                                </label>
                                <input type="number" 
                                       wire:model="editExercice"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                       min="2000" max="2100">
                                @error('editExercice') 
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
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
                            <div class="md:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Compte *
                                    @if($editingEcriture && $editingEcriture->newAccount)
                                        <span class="text-xs text-yellow-600 ml-1">
                                            (Mappé à {{ $editingEcriture->newAccount->code }})
                                        </span>
                                    @endif
                                </label>
                                
                                <select wire:model="editOldAccountId"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                        {{ $editingEcriture && $editingEcriture->newAccount ? 'disabled' : '' }}>
                                    <option value="">Sélectionner un compte</option>
                                    @foreach($allOldAccounts as $account)
                                        <option value="{{ $account->id }}">
                                            {{ $account->code }} - {{ Str::limit($account->intitule, 30) }}
                                        </option>
                                    @endforeach
                                </select>
                                
                                @error('editOldAccountId') 
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                                
                                @if($editingEcriture && $editingEcriture->newAccount)
                                    <p class="text-xs text-yellow-600 mt-1">
                                        <i class="fas fa-exclamation-triangle"></i> 
                                        Le compte ne peut être modifié car l'écriture est déjà mappée.
                                    </p>
                                @endif
                            </div>
                            
                            <!-- Débit -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Montant Débit (FCFA)
                                </label>
                                @if($editingEcriture && $editingEcriture->newAccount)
                                <input type="number" 
                                       wire:model="editDebit"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                                       placeholder="0" disabled>
                                @else
                                <input type="number" 
                                       wire:model="editDebit"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-red-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm"
                                       placeholder="0">
                                @endif
                                @error('editDebit') 
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                            
                            <!-- Crédit -->
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                    Montant Crédit (FCFA)
                                </label>
                                @if($editingEcriture && $editingEcriture->newAccount)
                                <input type="number" 
                                       wire:model="editCredit"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                       placeholder="0" disabled>
                                @else
                                <input type="number" 
                                       wire:model="editCredit"
                                       min="0"
                                       step="0.01"
                                       class="w-full px-3 py-2 border border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 text-sm"
                                       placeholder="0">
                                @endif
                                @error('editCredit') 
                                    <span class="text-xs text-red-600">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        
                        <!-- Avertissement débit/crédit -->
                        @if($editDebit && $editCredit)
                            <div class="mt-3 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                <p class="text-xs text-yellow-800 flex items-center">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Attention : Les deux montants (débit et crédit) sont renseignés. 
                                    Normalement, une écriture ne devrait avoir qu'un seul montant.
                                </p>
                            </div>
                        @endif
                    </div>
                    
                    <!-- Libellé -->
                    <div class="mb-6">
                        <label class="block text-xs font-medium text-gray-700 mb-1">
                            Libellé de l'écriture
                        </label>
                        <textarea wire:model="editLibelle"
                                  rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                  placeholder="Description de l'opération..."></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            Maximum 255 caractères. Actuellement : {{ strlen($editLibelle ?? '') }}/255
                        </p>
                    </div>
                    
                    <!-- Informations de mapping -->
                    @if($editingEcriture && $editingEcriture->newAccount)
                    <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                        <h4 class="text-sm font-medium text-blue-800 mb-2 flex items-center">
                            <i class="fas fa-link mr-2"></i>
                            Information de mapping
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <p class="text-xs text-blue-700 font-medium">Ancien compte :</p>
                                <p class="text-sm font-bold">{{ $editingEcriture->oldAccount->code ?? 'N/A' }} - {{ $editingEcriture->oldAccount->intitule ?? '' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-blue-700 font-medium">Nouveau compte SYCEBNL :</p>
                                <p class="text-sm font-bold text-green-600">{{ $editingEcriture->newAccount->code }} - {{ $editingEcriture->newAccount->intitule }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Actions -->
                    <div class="flex justify-end space-x-3 pt-4 border-t">
                    
                        <!-- Bouton Supprimer -->
                        <button type="button"
                            onclick="confirmDeleteEcriture({{ $editingEcriture->id ?? 0 }})"
                            @if($editingEcriture && $editingEcriture->newAccount)
                                disabled
                            @endif
                            class="px-4 py-2 rounded-lg transition text-sm flex items-center
                                {{ $editingEcriture && $editingEcriture->newAccount
                                    ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                    : 'bg-red-600 text-white hover:bg-red-700' }}">
                            <i class="fas fa-trash mr-2"></i>
                            Supprimer
                        </button>
                    
                        <!-- Annuler -->
                        <button type="button" 
                                wire:click="closeModalAndReset"
                                class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition text-sm">
                            <i class="fas fa-times mr-2"></i>
                            Annuler
                        </button>
                    
                        <!-- Enregistrer -->
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition shadow-sm flex items-center disabled:opacity-50 text-sm">
                            <span wire:loading.remove wire:target="updateEcriture">
                                <i class="fas fa-save mr-2"></i>
                                Enregistrer
                            </span>
                            <span wire:loading wire:target="updateEcriture">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Enregistrement...
                            </span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteEcriture(ecritureId) {
    if (!ecritureId || ecritureId === 0) {
        alert('Erreur : aucune écriture sélectionnée.');
        return;
    }
    
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette écriture ?\nCette action est irréversible.')) {
        return;
    }
    
    // Appeler la méthode Livewire
    @this.call('deleteEcriture', ecritureId)
        .then(() => {
            // Livewire gère déjà la fermeture
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur : ' + (error.message || 'Une erreur est survenue'));
        });
}
</script>