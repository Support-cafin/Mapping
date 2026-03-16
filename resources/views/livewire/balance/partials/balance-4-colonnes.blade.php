<div class="bg-white rounded-lg shadow border overflow-hidden">
    <div class="px-6 py-3 border-b bg-gray-50">
        <div class="flex justify-between items-center">
            <h3 style="font-size: 13px;" class="font-semibold text-gray-900">
                Balance à 4 colonnes  <!-- Boutons d'export -->
            <button wire:click="exportExcel"
                    class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700 flex items-center gap-2">
                <i class="fas fa-file-excel"></i>
                Export Excel
            </button>
            </h3>
            <div class="text-sm text-gray-600">
                {{ $stats['total_ecritures'] }} écritures au total
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Compte SYCEBNL
                    </th>
                    <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Intitulé
                    </th>
                    <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Mouvement
                    </th>
                    <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Solde
                    </th>
                    <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                <tr class="bg-gray-50">
                    <th></th>
                    <th></th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Débit
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Crédit
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Débiteur
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Créditeur
                    </th>
                    <th></th>
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($balances as $balance)
                    @php
                        $solde = $balance['solde'];
                        $isDebiteur = $solde > 0;
                        $soldeAbsolu = abs($solde);
                    @endphp

                    @if($balance['total_debit'] !== 0 || $balance['total_credit'] !== 0 || $soldeAbsolu !== 0)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-2 whitespace-nowrap border-x">
                            <div style="font-size: 13px;" class="font-mono font-bold text-blue-700">
                                {{ $balance['code'] }}
                            </div>
                        </td>

                        <td class="px-6 py-2 border-x">
                            <div style="font-size: 13px;" class="font-medium text-gray-900">
                                {{ Str::limit($balance['intitule'], 30) }}
                            </div>
                            @if($balance['old_accounts_count'] > 0)
                                <div style="font-size: 10px;" class="text-gray-500 mt-1">
                                    {{ $balance['old_accounts_count'] }} compte entité
                                </div>
                            @endif
                        </td>

                        <!-- Mouvement Débit -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($balance['total_debit'] > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($balance['total_debit'], 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>

                        <!-- Mouvement Crédit -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($balance['total_credit'] > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($balance['total_credit'], 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>

                        <!-- Solde Débiteur -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($isDebiteur)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($soldeAbsolu, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>

                        <!-- Solde Créditeur -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if(!$isDebiteur && $soldeAbsolu > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($soldeAbsolu, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-2 whitespace-nowrap border-x">
                            @if($balance['ecritures_count'] > 0)
                                <button style="font-size: 13px;" wire:click="showDetails({{ $balance['id'] }})"
                                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <i class="fas fa-eye"></i>
                                    Détails
                                </button>
                            @else
                                <span style="font-size: 10px;" class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center border-x">
                            <div class="text-gray-400">
                                <i class="fas fa-scale-balanced text-3xl mb-3"></i>
                                <p style="font-size: 13px;" class="font-medium mb-1">Aucun compte trouvé</p>
                                <p style="font-size: 13px;" class="text-gray-500">
                                    Vérifiez vos filtres ou créez des mappings de comptes
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>

            <!-- Totaux -->
            @if(count($balances) > 0)
                @php
                    $totalSoldeDebiteur = 0;
                    $totalSoldeCrediteur = 0;

                    foreach($balances as $balance) {
                        $solde = $balance['solde'];
                        if($solde > 0) {
                            $totalSoldeDebiteur += $solde;
                        } else {
                            $totalSoldeCrediteur += abs($solde);
                        }
                    }
                @endphp

                <tfoot class="bg-gray-50 font-bold border-t">
                    <tr>
                        <td colspan="2" style="font-size: 13px;" class="px-6 py-2 text-right text-gray-700 border-x">
                            TOTAUX
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($stats['total_debit'], 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($stats['total_credit'], 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalSoldeDebiteur, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalSoldeCrediteur, 0, ',', ' ') }}
                        </td>
                        <td class="border-x"></td>
                    </tr>
                    <tr class="bg-blue-50">
                        <td colspan="7" class="px-6 py-2 text-center border-x">
                            <span style="font-size: 13px;" class="font-medium text-blue-800">
                                ÉQUILIBRE : {{ number_format($totalSoldeDebiteur, 0, ',', ' ') }} = {{ number_format($totalSoldeCrediteur, 0, ',', ' ') }}
                                @if($totalSoldeDebiteur === $totalSoldeCrediteur)
                                    <i class="fas fa-check-circle text-green-600 ml-2"></i>
                                @else
                                    <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                                @endif
                            </span>
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
