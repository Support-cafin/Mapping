<div class="p-6 mt-12">
    <!-- Entête -->
    <div class="mb-4">
        <div class="flex justify-between items-start">
            <h1 class="text-2xl font-bold text-gray-800" style="font-size: 13px;">
                Récapitulatif Grand Livre - Regroupement par compte SYCEBNL
            </h1>
        </div>
    </div>

    <!-- Filtres (similaires à votre Grand Livre existant) -->
    <div class="bg-white p-1 rounded-lg shadow border mb-4">
        <div class="flex items-center justify-between mt-1">
            <h3 style="font-size: 11px !important;" class="font-medium text-gray-700 flex items-center">
                <i class="fas fa-filter mr-1"></i> Filtres
            </h3>
            <div class="flex space-x-2">
                <button style="font-size: 11px !important;" wire:click="resetFilters" class="text-gray-600 hover:text-gray-700">
                    <i class="fas fa-redo mr-1"></i> Reset
                </button>
            </div>
        </div>

        <div class="overflow-x-auto whitespace-nowrap py-0.5 -mx-2 px-2">
            <div class="inline-flex items-end gap-1 flex-nowrap">
                <!-- Période -->
                <div class="min-w-[160px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Période</label>
                    <div class="flex gap-1">
                        <input style="font-size: 11px !important;" type="date" wire:model.live="dateDebut" class="px-1 py-0.5 border rounded w-full">
                        <input style="font-size: 11px !important;" type="date" wire:model.live="dateFin" class="px-1 py-0.5 border rounded w-full">
                    </div>
                </div>

                <!-- Exercice -->
                <div class="min-w-[80px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Exercice</label>
                    <input style="font-size: 11px !important;" type="number" wire:model.live="exercice" class="w-full px-1 py-0.5 border rounded">
                </div>

                <!-- Recherche -->
                <div class="min-w-[180px]">
                    <label style="font-size: 11px !important;" class="text-[9px] font-medium mb-0.5 block">Recherche</label>
                    <input style="font-size: 11px !important;" type="text" wire:model.live.debounce.300ms="search" placeholder="Code ou intitulé compte SYCEBNL..." class="w-full px-2 py-0.5 border rounded">
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Globales -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-3 rounded-lg shadow border">
            <p class="text-xs text-gray-500 mb-1">Total Débit (SYCEBNL)</p>
            <p class="text-lg font-bold text-black-600" style="font-size: 13px;">{{ number_format($stats['total_debit'], 0, '', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow border">
            <p class="text-xs text-gray-500 mb-1">Total Crédit (SYCEBNL)</p>
            <p class="text-lg font-bold text-black-600" style="font-size: 13px;">{{ number_format($stats['total_credit'], 0, '', ' ') }} FCFA</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow border">
            <p class="text-xs text-gray-500 mb-1">Solde Global</p>
            <p class="text-lg font-bold text-blue-600" style="font-size: 13px;">{{ number_format($stats['solde'], 0, '', ' ') }} FCFA</p>
        </div>
    </div>

    @if($viewMode === 'table')
        <!-- TABLEAU RECAP (vue principale) -->
        <div class="bg-white rounded-lg shadow border overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Code Compte SYCEBNL</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Intitulé</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Nbre Écritures</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Total Débit</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Total Crédit</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Solde</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recapData as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-0.5">
                                <span class="font-mono text-xs bg-green-100 text-black-800 px-1.5 py-0.5 rounded" style="font-size: 13px;">
                                    {{ $item['new_account_code'] }}
                                </span>
                            </td>
                            <td class="px-2 py-0.5 text-sm text-gray-900" style="font-size: 13px;">{{ $item['new_account_intitule'] }}</td>
                            <td class="px-2 py-0.5 text-center text-sm text-gray-900" style="font-size: 13px;">{{ $item['nombre_ecritures'] }}</td>
                            <td class="px-2 py-0.5 text-sm text-black-600" style="font-size: 13px;">{{ number_format($item['total_debit'], 0, '', ' ') }}</td>
                            <td class="px-2 py-0.5 text-sm text-black-600" style="font-size: 13px;">{{ number_format($item['total_credit'], 0, '', ' ') }}</td>
                            <td class="px-2 py-0.5 text-sm font-medium">
                                @php
                                    $solde = $item['solde'];
                                    $color = $solde > 0 ? 'text-black-600' : ($solde < 0 ? 'text-black-600' : 'text-gray-600');
                                @endphp
                                <span class="{{ $color }}" style="font-size: 13px;">{{ number_format(abs($solde), 0, '', ' ') }}</span>
                            </td>
                            <td class="px-2 py-0.5 text-sm font-medium">
                                <button wire:click="viewDetail({{ $item['new_account_id'] }})"
                                        class="text-blue-600 hover:text-blue-800"
                                        title="Voir le détail des anciens comptes">
                                    <i class="fas fa-eye mr-1"></i> Détail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-0.52 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-layer-group text-4xl text-gray-300 mb-3"></i>
                                    <p class="text-lg" style="font-size: 13px;">Aucun compte SYCEBNL avec données trouvé</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>

    @elseif($viewMode === 'detail')
        <!-- VUE DÉTAIL (Anciens comptes d'un nouveau compte) -->
        <div class="bg-white rounded-lg shadow border overflow-hidden">
            <!-- En-tête du détail -->
            <div class="px-4 py-3 bg-blue-50 border-b flex justify-between items-center">
                <div>
                    <button wire:click="backToRecap" class="text-blue-600 hover:text-blue-800 flex items-center" style="font-size: 13px;">
                        <i class="fas fa-arrow-left mr-2"></i> Retour au récap
                    </button>
                    <h3 class="font-bold text-gray-800 mt-1" style="font-size: 13px;">
                        Détail pour le compte SYCEBNL : 
                        <span class="font-mono bg-green-100 text-black-800 px-2 py-1 rounded">{{ $selectedNewAccount->code }}</span> - {{ $selectedNewAccount->intitule }}
                    </h3>
                </div>
            </div>

            <!-- Tableau des anciens comptes mappés -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Code Compte Entité</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Intitulé</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Nbre Écritures</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Total Débit</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Total Crédit</th>
                            <th class="px-2 font-bold py-0.5 text-left text-xs text-gray-500 uppercase tracking-wider" style="font-size: 13px;">Solde</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($detailData as $detail)
                        <tr class="hover:bg-gray-50">
                            <td class="px-2 py-0.5">
                                <span class="font-mono text-xs bg-orange-100 text-orange-800 px-1.5 py-0.5 rounded" style="font-size: 13px;">
                                    {{ $detail['old_account_code'] }}
                                </span>
                            </td>
                            <td class="px-2 py-0.5 text-sm text-gray-900" style="font-size: 13px;">{{ $detail['old_account_intitule'] }}</td>
                            <td class="px-2 py-0.5 text-center text-sm text-gray-900" style="font-size: 13px;">{{ $detail['nombre_ecritures'] }}</td>
                            <td class="px-2 py-0.5 text-sm text-black-600" style="font-size: 13px;">{{ number_format($detail['total_debit'], 0, '', ' ') }}</td>
                            <td class="px-2 py-0.5 text-sm text-black-600" style="font-size: 13px;">{{ number_format($detail['total_credit'], 0, '', ' ') }}</td>
                            <td class="px-2 py-0.5 text-sm font-medium">
                                @php
                                    $solde = $detail['solde'];
                                    $color = $solde > 0 ? 'text-black-600' : ($solde < 0 ? 'text-black-600' : 'text-gray-600');
                                @endphp
                                <span class="{{ $color }}" style="font-size: 13px;">{{ number_format(abs($solde), 0, '', ' ') }}</span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-0.52 text-center text-gray-500">
                                <p style="font-size: 13px;">Aucune écriture trouvée pour ce compte SYCEBNL sur la période sélectionnée.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <!-- Totaux pour ce nouveau compte -->
                    @if($detailData->count() > 0)
                    <tfoot class="bg-gray-50 font-medium">
                        <tr>
                            <td colspan="2" class="px-2 py-0.5 text-right text-sm text-gray-700" style="font-size: 13px;">Totaux pour ce compte :</td>
                            <td class="px-2 py-0.5 text-center text-sm text-gray-700" style="font-size: 13px;">{{ $detailData->sum('nombre_ecritures') }}</td>
                            <td class="px-2 py-0.5 text-sm text-black-600" style="font-size: 13px;">{{ number_format($detailData->sum('total_debit'), 0, '', ' ') }}</td>
                            <td class="px-2 py-0.5 text-sm text-black-600" style="font-size: 13px;">{{ number_format($detailData->sum('total_credit'), 0, '', ' ') }}</td>
                            <td class="px-2 py-0.5 text-sm font-medium">
                                @php
                                    $totalSolde = $detailData->sum('solde');
                                    $totalColor = $totalSolde > 0 ? 'text-black-600' : ($totalSolde < 0 ? 'text-black-600' : 'text-gray-600');
                                @endphp
                                <span class="{{ $totalColor }}" style="font-size: 13px;">{{ number_format(abs($totalSolde), 0, '', ' ') }}</span>
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif
</div>