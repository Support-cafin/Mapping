<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <!-- En-tête -->
            <div class="px-6 py-4 bg-blue-50 border-l-4 border-blue-400">
                <h2 class="text-xl font-semibold text-gray-800">
                    Nouveau donateur
                </h2>
            </div>

            <!-- Formulaire -->
            <form wire:submit.prevent="save" class="p-6">
                @include('livewire.donateurs.form-fields', ['isEditMode' => false])

                <!-- Boutons -->
                <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('donateurs.index') }}" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>