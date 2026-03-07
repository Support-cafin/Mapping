<div class="bg-white p-1 rounded-lg shadow border mb-4 flex-shrink-0" x-data="{ showFilters: true }">
    <div class="flex items-center justify-between mt-1">
        <h3 style="font-size: 11px !important;" class="font-medium text-gray-700 flex items-center">
            <i class="fas fa-filter mr-1"></i> Filtres
        </h3>

        <div class="flex space-x-2">
            <button style="font-size: 11px !important;" 
                    @click="showFilters = !showFilters"
                    class="text-blue-600 hover:text-blue-700">
                <span x-text="showFilters ? 'Masquer' : 'Afficher'"></span>
            </button>

            <button style="font-size: 11px !important;" 
                    wire:click="resetFilters"
                    class="text-gray-600 hover:text-gray-700">
                <i class="fas fa-redo mr-1"></i> Reset
            </button>
        </div>
    </div>

    <div x-show="showFilters" class="overflow-x-auto whitespace-nowrap py-0.5 -mx-2 px-2">
        <div class="inline-flex items-end gap-1 flex-nowrap">
            <!-- Stats -->
            <div class="bg-gray-50 px-2 py-0.5 rounded border text-center">
                <p style="font-size: 11px !important;" class="text-gray-500 leading-none">Total écritures</p>
                <p style="font-size: 11px !important;" class="font-bold text-[10px] leading-none">
                    {{ number_format($stats['total'], 0, ',', ' ') }}
                </p>
            </div>

            <!-- Période -->
            <div class="min-w-[160px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Période</label>
                <div class="flex gap-1">
                    <input style="font-size: 11px !important;" 
                           type="date" 
                           wire:model.live="dateDebut" 
                           class="px-1 py-0.5 border rounded w-full">
                    <input style="font-size: 11px !important;" 
                           type="date" 
                           wire:model.live="dateFin" 
                           class="px-1 py-0.5 border rounded w-full">
                </div>
            </div>
            
            <!-- Source -->
            <div class="min-w-[110px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Source</label>
                <select style="font-size: 11px !important;" 
                        wire:model.live="sourceFilter" 
                        class="w-full px-1 py-0.5 border rounded">
                    <option value="all">Toutes</option>
                    <option value="manuel">Manuelles</option>
                    <option value="import">Importées</option>
                </select>
            </div>

            <!-- Journal -->
            <div class="min-w-[90px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Journal</label>
                <select style="font-size: 11px !important;" 
                        wire:model.live="journalCode" 
                        class="w-full px-1 py-0.5 border rounded">
                    <option value="">Tous</option>
                    @foreach($journaux as $j)
                        <option value="{{ $j['code'] }}">{{ $j['code'] }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Type compte -->
            <div class="min-w-[110px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Type compte</label>
                <select style="font-size: 11px !important;" 
                        wire:model.live="accountType" 
                        class="w-full px-1 py-0.5 border rounded">
                    <option value="all">Tous</option>
                    <option value="old">Compte entité</option>
                    <option value="new">Compte SYCEBNL</option>
                </select>
            </div>

            <!-- Compte -->
            @if($accountType !== 'all')
            <div class="min-w-[150px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Compte</label>
                <select style="font-size: 11px !important;" 
                        wire:model.live="accountId" 
                        class="w-full px-1 py-0.5 border rounded">
                    <option value="">Tous les comptes</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}">
                            {{ $a->code }} - {{ Str::limit($a->intitule, 20) }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Recherche -->
            <div class="min-w-[180px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Recherche</label>
                <input style="font-size: 11px !important;" 
                       type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="Pièce, libellé, compte…"
                       class="w-full px-2 py-0.5 border rounded">
            </div>
            
            <!-- Statut mapping -->
            <div class="min-w-[110px]">
                <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Statut mapping</label>
                <select style="font-size: 11px !important;" 
                        wire:model.live="mappingFilter" 
                        class="w-full px-1 py-0.5 border rounded">
                    <option value="all">Toutes</option>
                    <option value="mapped">Mappées</option>
                    <option value="unmapped">Non mappées</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Résumé filtres actifs -->
    <div class="mt-2 flex flex-wrap gap-1">
        @if($dateDebut && $dateFin)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs">
                {{ \Carbon\Carbon::parse($dateDebut)->format('d/m') }} → {{ \Carbon\Carbon::parse($dateFin)->format('d/m') }}
                <button wire:click="$set('dateDebut','')" class="ml-1 text-blue-600"><i class="fas fa-times"></i></button>
            </span>
        @endif

        @if($journalCode)
            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-green-100 text-green-800 text-xs">
                Journal: {{ $journalCode }}
                <button wire:click="$set('journalCode','')" class="ml-1 text-green-600"><i class="fas fa-times"></i></button>
            </span>
        @endif

        @if($mappingFilter !== 'all')
            <span class="inline-flex items-center px-2 py-0.5 rounded-full {{ $mappingFilter === 'mapped' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} text-xs">
                @if($mappingFilter === 'mapped')
                    <i class="fas fa-check-circle mr-1"></i> Mappées
                @else
                    <i class="fas fa-exclamation-triangle mr-1"></i> Non mappées
                @endif
                <button wire:click="$set('mappingFilter','all')" class="ml-1">
                    <i class="fas fa-times"></i>
                </button>
            </span>
        @endif
    </div>
</div>