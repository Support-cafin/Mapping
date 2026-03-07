<div class="p-2 mt-12">
    <!-- Header -->
    <div class="mb-4">
        <div class="flex justify-between items-start">
            <div>
                <h3 style="font-size: 12px;" class="text-lg font-semibold text-gray-900">
                    États Financiers
                </h3>
                <p style="font-size: 12px;" class="text-gray-600 mt-1">
                    Bilan, Compte de Résultat et Tableau de Flux de Trésorerie
                </p>
            </div>
            
            <div class="flex items-center space-x-3">
                <span style="font-size: 12px;" class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                    {{ $entreprise->nom }}
                </span>
            </div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div class="bg-white rounded-lg shadow border p-6 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Période -->
            <div class="col-span-2">
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="far fa-calendar mr-1"></i>
                    Période
                </label>
                <div class="flex gap-2">
                    <input style="font-size: 12px;" type="date" 
                           wire:model.live="dateDebut"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <input style="font-size: 12px;" type="date" 
                           wire:model.live="dateFin"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                </div>
            </div>
            
            <!-- Exercice -->
            <div>
                <label style="font-size: 12px;" class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-calendar-alt mr-1"></i>
                    Exercice
                </label>
                <input style="font-size: 12px;" type="number" 
                       wire:model.live="exercice"
                       class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            
            <!-- Actions -->
            <div class="flex items-end gap-2">
                <button style="font-size: 12px;" wire:click="resetFilters"
                        class="w-full px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                               flex items-center justify-center gap-2 text-sm">
                    <i class="fas fa-redo"></i>
                    Réinitialiser
                </button>
                
                @if($activeTab == 'bilan')
                <a href="{{ route('bilan.export', ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'exercice' => $exercice]) }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
                    Exporter
                </a>
                @elseif($activeTab == 'compte_resultat')
                <a href="{{ route('compte.resultat.export', ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'exercice' => $exercice]) }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
                    Exporter
                </a>
                @elseif($activeTab == 'flux_tresorerie')
                <a href="{{ route('flux.tresorerie.export', ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'exercice' => $exercice]) }}" 
                   class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm flex items-center gap-2">
                    <i class="fas fa-file-excel"></i>
                    Exporter
                </a>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Onglets -->
    <div class="mb-4">
        <div class="flex border-b border-gray-200">
            <button 
                wire:click="setActiveTab('bilan')"
                class="px-4 py-2 text-sm font-medium rounded-t-lg transition-all {{ $activeTab === 'bilan' ? 'bg-white border border-b-0 border-gray-300 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
                style="font-size: 11px;">
                <i class="fas fa-balance-scale mr-1"></i> Bilan
            </button>
            
            <button 
                wire:click="setActiveTab('compte_resultat')"
                class="px-4 py-2 text-sm font-medium rounded-t-lg transition-all {{ $activeTab === 'compte_resultat' ? 'bg-white border border-b-0 border-gray-300 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
                style="font-size: 11px;">
                <i class="fas fa-chart-line mr-1"></i> Compte de Résultat
            </button>
            
            <button 
                wire:click="setActiveTab('flux_tresorerie')"
                class="px-4 py-2 text-sm font-medium rounded-t-lg transition-all {{ $activeTab === 'flux_tresorerie' ? 'bg-white border border-b-0 border-gray-300 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}"
                style="font-size: 11px;">
                <i class="fas fa-money-bill-wave mr-1"></i> Flux de Trésorerie
            </button>
        </div>
    </div>
    
    <!-- Contenu des onglets -->
    <div>
        @if($activeTab === 'bilan')
            @php
                // Passer les propriétés comme un tableau
                $params = [
                    'entreprise' => $entreprise,
                    'dateDebut' => $dateDebut,
                    'dateFin' => $dateFin,
                    'exercice' => $exercice
                ];
            @endphp
            @livewire('financial.bilan', $params)
        @elseif($activeTab === 'compte_resultat')
            @php
                $params = [
                    'entreprise' => $entreprise,
                    'dateDebut' => $dateDebut,
                    'dateFin' => $dateFin,
                    'exercice' => $exercice
                ];
            @endphp
            @livewire('financial.compte-de-resultat', $params)
        @elseif($activeTab === 'flux_tresorerie')
            @php
                $params = [
                    'entreprise' => $entreprise,
                    'dateDebut' => $dateDebut,
                    'dateFin' => $dateFin,
                    'exercice' => $exercice
                ];
            @endphp
            @livewire('financial.flux-tresorerie', $params)
        @endif
    </div>
</div>