<!-- Modal d'import -->
<div x-show="showImportModal" 
     x-transition.opacity
     x-cloak
     class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center p-4 z-50">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b flex justify-between items-center sticky top-0 bg-white z-10">
            <h3 class="text-lg font-semibold text-gray-900" style="font-size: 12px;">
                <i class="fas fa-file-import mr-2 text-green-500"></i>
                Importer des écritures comptables
            </h3>
            <button @click="showImportModal = false" 
                    class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="p-6">
            <form wire:submit.prevent="import">
                <!-- Instructions -->
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <h4 class="font-medium text-blue-800 mb-2 flex items-center" style="font-size: 12px;">
                        <i class="fas fa-info-circle mr-2"></i>
                        Format Excel requis
                    </h4>
                    <ul class="text-sm text-blue-700 list-disc pl-5 space-y-1">
                        <li style="font-size: 12px;"><strong>En-têtes (ligne 1)</strong> : date, journal, compte, libelle, piece, debit, credit</li>
                        <li style="font-size: 12px;"><strong>Date</strong> : JJ/MM/AAAA (ex: 15/01/2024)</li>
                        <li style="font-size: 12px;"><strong>Journal</strong> : Code journal existant (optionnel)</li>
                        <li style="font-size: 12px;"><strong>Compte</strong> : Code du plan comptable (OBLIGATOIRE)</li>
                        <li style="font-size: 12px;"><strong>Libellé</strong> : Description de l'écriture</li>
                        <li style="font-size: 12px;"><strong>Pièce</strong> : Numéro de justificatif (optionnel)</li>
                        <li style="font-size: 12px;"><strong>Débit/Crédit</strong> : Renseigner un seul par ligne</li>
                    </ul>
                    <div class="mt-3 flex items-center gap-3">
                        <button type="button"
                                wire:click="downloadTemplate"
                                class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                                style="font-size: 12px;">
                            <i class="fas fa-download"></i>
                            Télécharger le template Excel
                        </button>
                    </div>
                </div>
                
                <!-- Fichier -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 12px;">
                        Fichier Excel (.xlsx, .xls, .csv)
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg hover:border-blue-400 transition">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-file-excel text-5xl text-green-500 mb-2"></i>
                            <div class="flex text-sm text-gray-600">
                                <label class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500">
                                    <span class="inline-flex items-center gap-2 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm"
                           style="font-size: 12px;">Choisir un fichier</span>
                                    <input type="file" wire:model="importFile" class="sr-only" accept=".xlsx,.xls,.csv">
                                </label>
                            </div>
                            <p class="text-xs text-gray-500" style="font-size: 12px;">
                                ou glissez-déposez XLSX, XLS, CSV jusqu'à 10MB
                            </p>
                            
                            @if($importFile)
                            <div class="mt-3 p-3 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                        <div class="text-left">
                                            <p class="text-sm font-medium text-green-700" style="font-size: 12px;">
                                                {{ $importFile->getClientOriginalName() }}
                                            </p>
                                            <p class="text-xs text-gray-500" style="font-size: 12px;">
                                                {{ number_format($importFile->getSize() / 1024, 2) }} KB
                                            </p>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="$set('importFile', null)" 
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @error('importFile') 
                        <p class="mt-2 text-sm text-red-600 flex items-center" style="font-size: 12px;">
                            <i class="fas fa-exclamation-circle mr-1"></i>
                            {{ $message }}
                        </p> 
                    @enderror
                </div>
                
                <!-- Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                  
                    <div class="flex items-center pt-6">
                        <input type="checkbox" wire:model="autoValidate" 
                               id="autoValidate"
                               class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <label for="autoValidate" class="ml-2 block text-sm text-gray-700" style="font-size: 12px;">
                            <i class="fas fa-check-double mr-1 text-green-500"></i>
                            Valider automatiquement les écritures
                        </label>
                    </div>
                </div>
                
                <!-- Barre de progression -->
                @if($importing)
                <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-blue-700" style="font-size: 12px;">
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Importation en cours...
                        </span>
                    </div>
                    <div class="w-full bg-blue-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                    </div>
                    <p class="mt-2 text-xs text-blue-600" style="font-size: 12px;">
                        Veuillez patienter, cette opération peut prendre quelques instants...
                    </p>
                </div>
                @endif
                
                <!-- Messages de succès -->
                @if($importSuccess && !$importing)
                <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium text-green-800" style="font-size: 12px;">
                                Importation réussie !
                            </p>
                            <p class="text-sm text-green-700 mt-1" style="font-size: 12px;">
                                Les écritures ont été importées dans le grand livre.
                            </p>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Erreurs et avertissements -->
                @if(count($importErrors) > 0 && !$importing)
                <div class="mb-6 max-h-60 overflow-y-auto">
                    <div class="p-4 bg-yellow-50 rounded-lg border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-3 flex items-center" style="font-size: 12px;">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            Messages d'importation ({{ count($importErrors) }})
                        </h4>
                        <ul class="space-y-2">
                            @foreach($importErrors as $error)
                                <li class="text-sm flex items-start" style="font-size: 12px;">
                                    @if(str_starts_with($error, '❌'))
                                        <i class="fas fa-times-circle text-red-500 mr-2 mt-0.5"></i>
                                        <span class="text-red-700">{{ $error }}</span>
                                    @elseif(str_starts_with($error, '⚠️'))
                                        <i class="fas fa-exclamation-circle text-yellow-500 mr-2 mt-0.5"></i>
                                        <span class="text-yellow-700">{{ $error }}</span>
                                    @else
                                        <span class="text-gray-700">• {{ $error }}</span>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                <!-- Message après import réussi avec bouton de téléchargement -->
                <!-- Message après import réussi -->
                @if($importSuccess && $importCompleted && !$importing)
                <div class="mb-6 p-4 bg-green-50 rounded-lg border border-green-200">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                        <div>
                            <p class="font-medium text-green-800" style="font-size: 12px;">
                                Importation terminée avec succès !
                            </p>
                            <p class="text-sm text-green-700 mt-1" style="font-size: 12px;">
                                {{ $importedCount ?? 0 }} écritures importées.
                                
                                @php
                                    $errorsCount = is_array($importErrors) ? count($importErrors) : 0;
                                    $warningsCount = is_array($importWarnings) ? count($importWarnings) : 0;
                                @endphp
                                
                                @if($errorsCount > 0 || $warningsCount > 0)
                                    <br>{{ $errorsCount }} erreurs, {{ $warningsCount }} avertissements.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
                @endif
               <!-- Actions -->
                <div class="flex justify-end space-x-3 pt-4 border-t">
                    @if($importCompleted)
                        <!--<button type="button"
                                wire:click="downloadImportResult"
                                class="px-4 py-2 bg-blue-600 text-white hover:bg-blue-700 rounded-lg transition shadow-sm flex items-center"
                                style="font-size: 12px;">
                            <i class="fas fa-download mr-2"></i>
                            Télécharger le résultat
                        </button>-->
                        <button type="button"
                                wire:click="resetImport"
                                @click="showImportModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition"
                                style="font-size: 12px;">
                            <i class="fas fa-times mr-2"></i>
                            Fermer
                        </button>
                    @else
                        <button type="button"
                                @click="showImportModal = false"
                                class="px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg transition" 
                                style="font-size: 12px;">
                            <i class="fas fa-times mr-2"></i>
                            Annuler
                        </button>
                        <button type="submit" 
                                wire:loading.attr="disabled"
                                :disabled="!$wire.importFile || $wire.importing"
                                class="px-4 py-2 bg-green-600 text-white hover:bg-green-700 rounded-lg transition shadow-sm flex items-center disabled:opacity-50 disabled:cursor-not-allowed" 
                                style="font-size: 12px;">
                            <span wire:loading.remove wire:target="import">
                                <i class="fas fa-upload mr-2"></i>
                                Importer
                            </span>
                            <span wire:loading wire:target="import">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Importation...
                            </span>
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>