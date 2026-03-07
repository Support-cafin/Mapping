<div class="flex justify-between items-center mb-4 bg-gray-50 p-3 rounded-lg">
    <div class="flex items-center space-x-3">
        <span class="text-sm text-gray-600" style="font-size: 11px;">
            Affichage de <strong>{{ $ecritures->count() }}</strong> sur <strong>{{ number_format($totalCount, 0, ',', ' ') }}</strong> écritures
        </span>
        
        <!-- Bouton Effacer -->
        <button type="button" 
                onclick="openDeleteModal(event)"
                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium flex items-center">
            <i class="fas fa-trash-alt mr-2"></i>
            Effacer
        </button>
        
        <!-- Boutons de sélection -->
        <button type="button" 
                onclick="selectAllCheckboxes()"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-md text-sm">
            Tout sélectionner
        </button>
        
        <button type="button" 
                onclick="deselectAllCheckboxes()"
                class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-md text-sm">
            Désélectionner
        </button>
        
        <!-- Compteur de sélection -->
        <span class="text-sm text-gray-600 ml-2">
            <span id="selected-count">0</span> sélectionné(s)
        </span>
    </div>
</div>