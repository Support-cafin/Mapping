{{-- resources/views/livewire/mapping/ultra-simple-cards.blade.php --}}
<div class="p-4 space-y-4 bg-gray-50 min-h-screen">
    
    <!-- En-tête minimaliste -->
    <div class="bg-white rounded-xl shadow p-4">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">
                <i class="fas fa-exchange-alt text-blue-500 mr-2"></i>
                Mapping Express
            </h1>
            <p class="text-gray-600">
                Sélectionnez un compte de gauche, puis un compte de droite
            </p>
            
            <!-- Barre de progression simple -->
            <div class="mt-4">
                <div class="flex justify-between text-sm text-gray-600 mb-1">
                    <span>{{ $mappedCount }} mappés</span>
                    <span>{{ $totalCount }} total</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-500"
                         style="width: {{ $totalCount > 0 ? ($mappedCount / $totalCount) * 100 : 0 }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface cartes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- Carte ONG -->
        <div class="bg-white rounded-xl shadow-lg border-2 {{ $selectedOldId ? 'border-blue-500' : 'border-gray-200' }}">
            <div class="p-4 bg-blue-50 rounded-t-xl">
                <h2 class="font-bold text-lg text-gray-800 flex items-center">
                    <i class="fas fa-database text-blue-600 mr-2"></i>
                    Comptes ONG
                </h2>
                <p class="text-sm text-gray-600 mt-1">Cliquez pour sélectionner</p>
                
                <!-- Recherche rapide -->
                <div class="mt-3">
                    <input type="text"
                           wire:model.live.debounce.300ms="searchOld"
                           placeholder="Filtrer par code ou libellé..."
                           class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            
            <!-- Grille de cartes -->
            <div class="p-4 overflow-y-auto max-h-[500px]">
                <div class="grid grid-cols-1 gap-2">
                    @foreach($oldAccounts as $account)
                        <div class="p-3 rounded-lg border cursor-pointer transition-all duration-200
                                    {{ $selectedOldId == $account->id 
                                        ? 'bg-blue-100 border-blue-500 ring-2 ring-blue-300' 
                                        : 'hover:bg-gray-50 border-gray-200' }}
                                    {{ $account->mapping ? 'bg-green-50 border-green-200' : '' }}"
                             wire:click="selectOldAccount({{ $account->id }})">
                            
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-gray-800 text-lg">
                                            {{ $account->code }}
                                        </span>
                                        @if($account->mapping)
                                            <span class="px-2 py-0.5 text-xs rounded-full bg-green-600 text-white">
                                                <i class="fas fa-check"></i>
                                            </span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        {{ Str::limit($account->intitule, 50) }}
                                    </div>
                                </div>
                                
                                <!-- Indicateur -->
                                <div class="w-6 h-6 rounded-full 
                                            {{ $selectedOldId == $account->id 
                                                ? 'bg-blue-500' 
                                                : 'bg-gray-200' }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Carte SYCEBNL -->
        <div class="bg-white rounded-xl shadow-lg border-2 {{ $selectedOldId ? 'border-green-500' : 'border-gray-200 opacity-75' }}">
            <div class="p-4 bg-green-50 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-bold text-lg text-gray-800 flex items-center">
                            <i class="fas fa-chart-bar text-green-600 mr-2"></i>
                            Plan SYCEBNL
                        </h2>
                        <p class="text-sm text-gray-600 mt-1">
                            @if($selectedOldId)
                                Sélectionnez le compte cible
                            @else
                                Sélectionnez d'abord un compte ONG
                            @endif
                        </p>
                    </div>
                    
                    @if($selectedOldAccount)
                        <button wire:click="resetSelection"
                                class="px-3 py-1 bg-red-100 text-red-600 rounded-lg text-sm">
                            <i class="fas fa-times mr-1"></i> Annuler
                        </button>
                    @endif
                </div>
                
                <!-- Recherche -->
                <div class="mt-3">
                    <input type="text"
                           wire:model.live.debounce.300ms="searchNew"
                           placeholder="Rechercher un compte SYCEBNL..."
                           class="w-full px-4 py-2 rounded-lg border focus:ring-2 focus:ring-green-500"
                           {{ !$selectedOldId ? 'disabled' : '' }}>
                </div>
            </div>
            
            <!-- Grille SYCEBNL -->
            <div class="p-4 overflow-y-auto max-h-[500px]">
                @if($selectedOldAccount)
                    <!-- Compte sélectionné -->
                    <div class="mb-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="font-bold text-blue-700">
                                    {{ $selectedOldAccount->code }}
                                </span>
                                <span class="text-sm text-blue-600 ml-2">
                                    {{ Str::limit($selectedOldAccount->intitule, 40) }}
                                </span>
                            </div>
                            <i class="fas fa-arrow-right text-blue-500 text-xl"></i>
                        </div>
                    </div>
                    
                    <!-- Liste des comptes SYCEBNL -->
                    <div class="grid grid-cols-1 gap-2">
                        @foreach($newAccounts as $account)
                            <div class="p-3 rounded-lg border cursor-pointer transition-all duration-200
                                        {{ $selectedNewId == $account->id 
                                            ? 'bg-green-100 border-green-500 ring-2 ring-green-300' 
                                            : 'hover:bg-gray-50 border-gray-200' }}"
                                 wire:click="mapToNew({{ $account->id }})">
                                
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-gray-800 text-lg">
                                                {{ $account->code }}
                                            </span>
                                            @if($selectedNewId == $account->id)
                                                <span class="px-2 py-0.5 text-xs rounded-full bg-green-600 text-white">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-600 mt-1">
                                            {{ Str::limit($account->intitule, 50) }}
                                        </div>
                                    </div>
                                    
                                    <!-- Bouton mapper -->
                                    <button class="w-8 h-8 rounded-full bg-green-100 text-green-600 
                                                   hover:bg-green-200 flex items-center justify-center">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- État inactif -->
                    <div class="h-full flex items-center justify-center text-gray-400 p-8">
                        <div class="text-center">
                            <i class="fas fa-hand-point-left text-4xl mb-4"></i>
                            <p class="text-lg">Sélectionnez un compte ONG</p>
                            <p class="text-sm mt-2">puis choisissez le compte SYCEBNL correspondant</p>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- Instructions -->
            @if($selectedOldId)
                <div class="p-3 border-t bg-gray-50 rounded-b-xl">
                    <div class="text-center text-sm text-gray-600">
                        <div class="flex items-center justify-center gap-3">
                            <div class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full">
                                {{ $selectedOldAccount->code }}
                            </div>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                            <div class="px-3 py-1 bg-green-100 text-green-700 rounded-full">
                                Cliquez sur un compte
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Feedback visuel flottant -->
    @if($selectedOldId && !$selectedNewId)
        <div class="fixed bottom-4 left-1/2 transform -translate-x-1/2 z-40">
            <div class="bg-gradient-to-r from-blue-500 to-green-500 text-white px-6 py-3 rounded-full shadow-xl
                        flex items-center gap-3 animate-pulse">
                <i class="fas fa-lightbulb"></i>
                <span>Sélectionnez maintenant un compte SYCEBNL pour finaliser le mapping</span>
            </div>
        </div>
    @endif

    <!-- Animation de succès -->
    <div wire:ignore id="success-animation" class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none" style="display: none;">
        <div class="w-64 h-64">
            <svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
            </svg>
        </div>
    </div>

    <style>
    .checkmark__circle {
        stroke-dasharray: 166;
        stroke-dashoffset: 166;
        stroke-width: 2;
        stroke-miterlimit: 10;
        stroke: #4CAF50;
        fill: none;
        animation: stroke 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
    }
    
    .checkmark {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: block;
        stroke-width: 2;
        stroke: #fff;
        stroke-miterlimit: 10;
        margin: 10% auto;
        box-shadow: inset 0px 0px 0px #4CAF50;
        animation: fill .4s ease-in-out .4s forwards, scale .3s ease-in-out .9s both;
    }
    
    .checkmark__check {
        transform-origin: 50% 50%;
        stroke-dasharray: 48;
        stroke-dashoffset: 48;
        animation: stroke 0.3s cubic-bezier(0.65, 0, 0.45, 1) 0.8s forwards;
    }
    
    @keyframes stroke {
        100% { stroke-dashoffset: 0; }
    }
    
    @keyframes scale {
        0%, 100% { transform: none; }
        50% { transform: scale3d(1.1, 1.1, 1); }
    }
    
    @keyframes fill {
        100% { box-shadow: inset 0px 0px 0px 30px #4CAF50; }
    }
    </style>
    
    <script>
    // Animation de succès
    Livewire.on('mapping-done', () => {
        const animation = document.getElementById('success-animation');
        animation.style.display = 'flex';
        
        setTimeout(() => {
            animation.style.display = 'none';
        }, 2000);
    });
    
    // Navigation clavier
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            @this.call('resetSelection');
        }
    });
    </script>

</div>