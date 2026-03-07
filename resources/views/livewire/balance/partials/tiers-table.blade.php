<div class="bg-white rounded-lg shadow border overflow-hidden">
    <div class="px-6 py-3 border-b bg-gray-50">
        <div class="flex justify-between items-center">
            <h3 style="font-size: 13px;" class="font-semibold text-gray-900">
                <i class="fas fa-users mr-2 text-blue-600"></i>
                Balance des Tiers
            </h3>
             <button onclick="imprimerBalance()" 
            class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
        <i class="fas fa-print mr-1"></i> Imprimer
    </button>
            <div class="text-sm text-gray-600">
                Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} 
                au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-800 text-white">
                <tr>
                    <th style="font-size: 10px;" class="px-4 py-2 text-left">Compte Sycebnl</th>
                    <th style="font-size: 10px;" class="px-4 py-2 text-left">Intitulé</th>
                    <th style="font-size: 10px;" class="px-4 py-2 text-right border-x border-gray-600">Mouvements Débit</th>
                    <th style="font-size: 10px;" class="px-4 py-2 text-right border-x border-gray-600">Mouvements Crédit</th>
                    <th style="font-size: 10px;" class="px-4 py-2 text-right border-x border-gray-600">Solde Débiteur</th>
                    <th style="font-size: 10px;" class="px-4 py-2 text-right border-x border-gray-600">Solde Créditeur</th>
                    <th style="font-size: 10px;" class="px-4 py-2 text-center">Actions</th>
                </tr>
            </thead>
            
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $totalMouvementDebit = 0;
                    $totalMouvementCredit = 0;
                    $totalSoldeDebiteur = 0;
                    $totalSoldeCrediteur = 0;
                @endphp
                
                @forelse($balances as $balance)
                    @php
                        $totalMouvementDebit += $balance['total_debit'];
                        $totalMouvementCredit += $balance['total_credit'];
                        
                        if ($balance['solde'] > 0) {
                            $totalSoldeDebiteur += $balance['solde'];
                        } else {
                            $totalSoldeCrediteur += abs($balance['solde']);
                        }
                    @endphp
                    
                    <tr class="hover:bg-gray-50 {{ $loop->index % 2 === 0 ? 'bg-white' : 'bg-gray-50/30' }}">
                        <td class="px-4 py-2 whitespace-nowrap border-x">
                            <span style="font-size: 10px;" class="font-mono font-bold text-blue-700">
                                {{ $balance['code'] }}
                            </span>
                        </td>
                        
                        <td class="px-4 py-2 border-x">
                            <div style="font-size: 10px;" class="font-medium text-gray-900">
                                {{ $balance['intitule'] }}
                            </div>
                            @if(isset($balance['old_accounts_data']) && count($balance['old_accounts_data']) > 0)
                                <div style="font-size: 9px;" class="text-gray-500 mt-1">
                                    {{ count($balance['old_accounts_data']) }} compte entité
                                </div>
                            @endif
                        </td>
                        
                        <td class="px-4 py-2 whitespace-nowrap text-right border-x">
                            @if($balance['total_debit'] > 0)
                                <span style="font-size: 10px;" class="font-mono">
                                    {{ number_format($balance['total_debit'], 0, ',', ' ') }}
                                </span>
                            @else
                                <span style="font-size: 10px;" class="text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-2 whitespace-nowrap text-right border-x">
                            @if($balance['total_credit'] > 0)
                                <span style="font-size: 10px;" class="font-mono">
                                    {{ number_format($balance['total_credit'], 0, ',', ' ') }}
                                </span>
                            @else
                                <span style="font-size: 10px;" class="text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-2 whitespace-nowrap text-right border-x">
                            @if($balance['solde'] > 0)
                                <span style="font-size: 10px;" class="font-mono font-medium text-black">
                                    {{ number_format($balance['solde'], 0, ',', ' ') }}
                                </span>
                            @else
                                <span style="font-size: 10px;" class="text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-2 whitespace-nowrap text-right border-x">
                            @if($balance['solde'] < 0)
                                <span style="font-size: 10px;" class="font-mono font-medium text-black">
                                    {{ number_format(abs($balance['solde']), 0, ',', ' ') }}
                                </span>
                            @else
                                <span style="font-size: 10px;" class="text-gray-400">-</span>
                            @endif
                        </td>
                        
                        <td class="px-4 py-2 whitespace-nowrap text-center border-x">
                            @if($balance['ecritures_count'] > 0)
                                <button wire:click="showDetails({{ $balance['id'] }})"
                                        class="text-blue-600 hover:text-blue-800 text-xs flex items-center justify-center gap-1">
                                    <i class="fas fa-eye"></i>
                                    Détails
                                </button>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center">
                            <div class="text-gray-400">
                                <i class="fas fa-users text-3xl mb-3"></i>
                                <p style="font-size: 13px;" class="font-medium">Aucun compte tiers trouvé</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            
            @if(count($balances) > 0)
                <tfoot class="bg-gray-100 border-t-2 border-gray-300">
                    <tr>
                        <td colspan="2" class="px-4 py-2 text-right text-medium text-gray-700 border-x">
                            TOTAUX
                        </td>
                        <td class="px-4 py-2 text-right text-medium border-x" style="font-size: 10px;">
                            {{ number_format($totalMouvementDebit, 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-2 text-right text-medium border-x" style="font-size: 10px;">
                            {{ number_format($totalMouvementCredit, 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-2 text-right text-medium border-x" style="font-size: 10px;">
                            {{ number_format($totalSoldeDebiteur, 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-2 text-right text-medium border-x" style="font-size: 10px;">
                            {{ number_format($totalSoldeCrediteur, 0, ',', ' ') }}
                        </td>
                        <td class="border-x"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>