<!-- Lien vers Grand Livre Général -->
<a href="{{ route('grand-livre.general') }}"
   class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center" 
   style="font-size: 11px;">
    <i class="fas fa-layer-group mr-2"></i>
    Grand Livre Général
</a>

<!-- Bouton Import -->
<button style="font-size: 11px !important;" 
        @click="showImportModal = true"
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition flex items-center">
    <i class="fas fa-file-import mr-2"></i>
    Importer Excel
</button>

<!-- Bouton Export -->
<div class="relative" x-data="{ showExportOptions: false }">
    <button style="font-size: 11px !important;" 
            @click="showExportOptions = !showExportOptions"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
        <i class="fas fa-file-export mr-2"></i>
        Exporter Détail
    </button>

    <div x-show="showExportOptions" 
         x-cloak
         @click.outside="showExportOptions = false"
         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border z-10">
        <button style="font-size: 11px !important;" 
                wire:click="export('excel')"
                class="block w-full text-left px-4 py-3 hover:bg-gray-50 text-gray-700">
            <i class="fas fa-file-excel text-green-500 mr-2"></i>
            Excel (.xlsx)
        </button>
    </div>
</div>

<!-- Bouton Ajouter -->
<button style="font-size: 11px !important;" 
        @click="$dispatch('open-add-modal')"
        class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition flex items-center">
    <i class="fas fa-plus mr-2"></i>
    Ajouter
</button>