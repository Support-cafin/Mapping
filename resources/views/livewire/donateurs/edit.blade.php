<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        
        <!-- Message d'erreur -->
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
            <div class="px-6 py-4 bg-green-50 border-l-4 border-green-400">
                <h2 class="text-xl font-semibold text-gray-800">
                    Modifier le donateur #{{ $donateurId }}
                </h2>
                <p class="text-sm text-gray-600 mt-1">
                    Dénomination: {{ $denomination }}
                </p>
            </div>

            <!-- Formulaire -->
            <form wire:submit.prevent="update" class="p-6">
                @include('livewire.donateurs.form-fields', ['isEditMode' => true])

                <!-- Boutons -->
                <div class="mt-6 pt-4 border-t border-gray-200 flex justify-end space-x-3">
                    <a href="{{ route('donateurs.index') }}" 
                       class="px-4 py-2 border border-gray-300 text-gray-700 rounded hover:bg-gray-50">
                        Annuler
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded">
                        Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>