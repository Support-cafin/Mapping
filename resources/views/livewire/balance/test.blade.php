<div>
    <!-- TEST - Affiche les valeurs -->
    <div class="bg-blue-100 p-4 mb-4">
        <h3>Debug des variables :</h3>
        <p>viewMode = {{ var_dump($viewMode) ?? 'non défini' }}</p>
        <p>exerciceActif = {{ var_dump($exerciceActif) ?? 'non défini' }}</p>
        <p>dateDebut = {{ $dateDebut ?? 'non défini' }}</p>
        <p>dateFin = {{ $dateFin ?? 'non défini' }}</p>
    </div>

    <!-- Filtres -->
    <div class="mb-4 bg-white rounded-lg shadow border p-4">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date début</label>
                <input type="date" wire:model.live="dateDebut" 
                       class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Date fin</label>
                <input type="date" wire:model.live="dateFin" 
                       class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Exercice</label>
                <input type="number" wire:model.live="exercice" 
                       class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label class="block text-xs font-medium text-gray-700 mb-1">Recherche</label>
                <input type="text" wire:model.live.debounce="search" placeholder="Code ou intitulé..."
                       class="w-full text-sm border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="flex items-end gap-2">
                <button wire:click="resetFilters" 
                        class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm flex items-center gap-2">
                    <i class="fas fa-undo"></i>
                    Réinitialiser
                </button>
            </div>
        </div>
        
        <div wire:loading class="mt-2 text-sm text-blue-600">
            <i class="fas fa-spinner fa-spin mr-2"></i>
            Chargement en cours...
        </div>
        
        <!-- Utilisation des variables avec isset pour éviter les erreurs -->
        @if(isset($exerciceActif) && !$exerciceActif)
            <div class="mt-2 p-2 bg-yellow-100 text-yellow-800 rounded-lg text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Aucun exercice actif trouvé. Veuillez activer un exercice comptable.
            </div>
        @endif
    </div>
    
    <!-- Mode tableau - avec isset -->
    @if(isset($viewMode) && $viewMode === 'table')
        @include('livewire.balance.partials.tiers-balance-table', [
            'balances' => $balances,
            'stats' => $stats,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'search' => $search,
            'exercice' => $exercice,
            'exerciceActif' => $exerciceActif ?? null,
            'viewMode' => $viewMode,
            'selectedTiers' => $selectedTiers ?? null
        ])
    @endif
    
    <!-- Mode détails - avec isset -->
    @if(isset($viewMode) && $viewMode === 'details' && isset($selectedTiers) && $selectedTiers)
        @include('livewire.balance.partials.tiers-balance-details', [
            'selectedTiers' => $selectedTiers,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
            'search' => $search,
            'exercice' => $exercice,
            'exerciceActif' => $exerciceActif ?? null,
            'viewMode' => $viewMode,
            'balances' => $balances,
            'stats' => $stats
        ])
    @endif
    
    <!-- Débogage -->
    @if(config('app.debug') && isset($debug))
    <div class="mt-4 p-4 bg-gray-100 rounded-lg text-xs">
        <details>
            <summary class="cursor-pointer font-medium text-gray-700">Informations de débogage</summary>
            <div class="mt-2 grid grid-cols-2 gap-2">
                @foreach($debug as $key => $value)
                    <div class="flex">
                        <span class="font-mono w-40">{{ $key }}:</span>
                        <span class="font-mono text-gray-600">{{ $value }}</span>
                    </div>
                @endforeach
            </div>
        </details>
    </div>
    @endif
</div>