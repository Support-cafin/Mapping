
<div>
    <div class="container mx-auto px-4 py-8">
        <!-- En-tête -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Registre des Donateurs</h1>
            <p class="text-gray-600">Gestion des dons et legs</p>
        </div>
        @php $entr = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->first(); @endphp
        <div class="flex items-center gap-4">
            <h1 class="text-lg font-semibold">{{ $entr->nom ?? 'non renseigné' }}</h1>
        </div>
        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Total Annuel</div>
                <div class="text-2xl font-bold text-green-600">
                    {{ number_format($totalAnnuel, 0, ',', ' ') }} XOF
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Enregistrés</div>
                <div class="text-2xl font-bold text-blue-600">{{ $stats['enregistre'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Validés</div>
                <div class="text-2xl font-bold text-yellow-600">{{ $stats['valide'] }}</div>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500">Comptabilisés</div>
                <div class="text-2xl font-bold text-green-600">{{ $stats['comptabilise'] }}</div>
            </div>
        </div>
         <div class="mt-4 flex justify-end">
           <!-- <button onclick="window.print()" 
                    class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition mr-2">
                <i class="fas fa-print mr-2"></i> Imprimer la page
            </button> -->
            
            <button wire:click="exportPdf" 
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-file-pdf mr-2"></i> Exporter PDF
            </button>
        </div>
        <!-- Filtres -->
        <div class="bg-white rounded-lg shadow mb-6 p-4">
            <div class="flex flex-col md:flex-row gap-4">
                <!-- Recherche -->
                <div class="flex-1">
                    <input type="text" 
                           wire:model.live.debounce.300ms="search"
                           placeholder="Rechercher par nom, dénomination..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Filtres supplémentaires -->
                <div class="flex gap-2">
                    <select wire:model.live="selectedYear" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Toutes années</option>
                        @for($i = 2024; $i <= date('Y'); $i++)
                            <option value="{{ $i }}">{{ $i }}</option> 
                        @endfor
                    </select>

                    <select wire:model.live="selectedMonth" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Tous mois</option>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ $month }}">{{ DateTime::createFromFormat('!m', $month)->format('F') }}</option>
                        @endforeach
                    </select>
 
                    <select wire:model.live="selectedMode" class="px-4 py-2 border border-gray-300 rounded-lg">
                        <option value="">Tous les modes</option>
                        <option value="espèces">Espèces</option>
                        <option value="chèque">Chèque</option>
                        <option value="virement">Virement</option>
                        <option value="nature">Nature</option>
                    </select>
                    <div class="flex items-end">
                        <button wire:click="resetFilters" 
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 w-full">
                            Réinitialiser
                        </button>
                    </div>
                </div>

                <!-- Bouton Ajouter -->
                <a href="{{ route('donateurs.create') }}" 
                   class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nouveau Donateur
                </a>
            </div>
        </div>

        <!-- Tableau -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('numero_enregistrement')">
                                N°
                                @if($sortField === 'numero_enregistrement')
                                    @if($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('date')">
                                Date
                                @if($sortField === 'date')
                                    @if($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('denomination')">
                                Donateur
                                @if($sortField === 'denomination')
                                    @if($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                                wire:click="sortBy('montant_don')">
                                Montant
                                @if($sortField === 'montant_don')
                                    @if($sortDirection === 'asc')
                                        ↑
                                    @else
                                        ↓
                                    @endif
                                @endif
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Mode
                            </th>
                            <!--<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Statut
                            </th>-->
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($donateurs as $donateur)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $donateur->numero_enregistrement }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $donateur->date->format('d/m/Y') }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $donateur->denomination }}</div>
                                @if($donateur->nom_prenoms)
                                    <div class="text-sm text-gray-500">{{ $donateur->nom_prenoms }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ number_format($donateur->montant_don, 0, ',', ' ') }} XOF
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $donateur->mode_liberation === 'virement' ? 'bg-blue-100 text-blue-800' : 
                                       ($donateur->mode_liberation === 'chèque' ? 'bg-green-100 text-green-800' : 
                                       ($donateur->mode_liberation === 'espèces' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-gray-100 text-gray-800')) }}">
                                    {{ ucfirst($donateur->mode_liberation) }}
                                </span>
                            </td>
                            <!---<td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $donateur->statut === 'comptabilisé' ? 'bg-green-100 text-green-800' : 
                                       ($donateur->statut === 'validé' ? 'bg-yellow-100 text-yellow-800' : 
                                       ($donateur->statut === 'annulé' ? 'bg-red-100 text-red-800' : 
                                       'bg-blue-100 text-blue-800')) }}">
                                    {{ ucfirst($donateur->statut) }}
                                </span>
                            </td>-->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('donateurs.show', $donateur) }}" 
                                       class="text-blue-600 hover:text-blue-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    <a href="{{ route('donateurs.edit', $donateur) }}" 
                                       class="text-yellow-600 hover:text-yellow-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button wire:click="delete({{ $donateur->id }})" 
                                            onclick="return confirm('Êtes-vous sûr ?')"
                                            class="text-red-600 hover:text-red-900">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $donateurs->links() }}
            </div>
        </div>

        <!-- Export -->
        <!--<div class="mt-4 flex justify-end">
            <button onclick="window.print()" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition mr-2">
                Imprimer
            </button>
            <button wire:click="exportExcel" 
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                Exporter Excel
            </button>
        </div>-->
    </div>

    <!-- Message de session -->
    @if(session()->has('message'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
         class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        {{ session('message') }}
    </div>
    @endif
</div>