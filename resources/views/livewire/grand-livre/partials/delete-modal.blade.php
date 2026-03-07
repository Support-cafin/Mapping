<!-- Modal pour les options de suppression -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 overflow-y-auto">
    <!-- Overlay -->
    <div id="modal-overlay" class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" 
         onclick="closeDeleteModal(event)"></div>    
    <!-- Modal content -->
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            
            <!-- Header -->
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-trash-alt text-red-600"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Options de suppression
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500">
                                Choisissez l'action de suppression à effectuer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Options -->
            <div class="bg-gray-50 px-4 py-3 sm:px-6">
                <div class="space-y-3">
                    <!-- Option 1: Supprimer la sélection -->
                    <button onclick="deleteSelected()" 
                            class="w-full flex items-center justify-between px-4 py-3 bg-red-50 hover:bg-red-100 rounded-lg border border-red-200 transition-colors">
                        <div class="text-left">
                            <div class="font-medium text-red-700">Supprimer la sélection</div>
                            <div class="text-sm text-red-600 mt-1" id="selected-text">
                                <span id="selected-count-modal">0</span> écriture(s) sélectionnée(s)
                            </div>
                        </div>
                        <i class="fas fa-check-circle text-red-500"></i>
                    </button>
                    
                    <!-- Option 2: Tout effacer visible -->
                    <button onclick="deleteAllVisible()" 
                            class="w-full flex items-center justify-between px-4 py-3 bg-yellow-50 hover:bg-yellow-100 rounded-lg border border-yellow-200 transition-colors">
                        <div class="text-left">
                            <div class="font-medium text-yellow-700">Tout effacer (visible)</div>
                            <div class="text-sm text-yellow-600 mt-1">
                                Supprime toutes les écritures actuellement affichées
                            </div>
                        </div>
                        <i class="fas fa-eye text-yellow-500"></i>
                    </button>
                    
                    <!-- Option 3: Tout supprimer -->
                    <button onclick="deleteAll()" 
                            class="w-full flex items-center justify-between px-4 py-3 bg-red-100 hover:bg-red-200 rounded-lg border border-red-300 transition-colors">
                        <div class="text-left">
                            <div class="font-bold text-red-800">⚠️ Tout supprimer</div>
                            <div class="text-sm text-red-700 mt-1">
                                Supprime TOUTES les écritures sans exception
                            </div>
                        </div>
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </button>
                    
                    
                    <p class="text-irreversible">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Cette action est irréversible
                    </p>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="bg-gray-100 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        onclick="closeDeleteModal()"
                        class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Annuler
                </button>
            </div>
        </div>
    </div>
</div>