<div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden">
    <!-- En-tête simplifié -->
    <div class="px-6 py-4 border-b bg-gray-50">
        <div class="flex justify-between items-center">
            <div>
                <h2 style="font-size: 14px;" class="font-bold text-gray-900">
                    <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                    Détail du compte {{ $selectedTiers['code'] }}
                </h2>
                <p style="font-size: 13px;" class="text-gray-600 mt-1">
                    {{ $selectedTiers['intitule'] }}
                </p>
            </div>
            
            <button wire:click="backToList"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 
                           flex items-center gap-2 text-sm">
                <i class="fas fa-arrow-left"></i>
                Retour
            </button>
        </div>
    </div>
    
   
    <!-- Tableau simplifié : seulement compte, mouvement, solde -->
    <div class="p-6">
        <h3 style="font-size: 13px;" class="font-semibold text-gray-900 mb-4 flex items-center">
            <i class="fas fa-list-alt mr-2 text-blue-600"></i>
            Synthèse du compte
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs">Compte Sycebnl</th>
                        <th class="px-4 py-2 text-right text-xs">Mouvement Débit</th>
                        <th class="px-4 py-2 text-right text-xs">Mouvement Crédit</th>
                        <th class="px-4 py-2 text-right text-xs">Solde</th>
                    </tr>
                </thead>
                
                <tbody>
                    @php
                        $totalDebit = 0;
                        $totalCredit = 0;
                        $soldeTotal = 0;
                    @endphp
                    
                    @foreach($selectedTiers['old_accounts_data'] as $oldData)
                        @php
                            $debit = $oldData['debit'] ?? 0;
                            $credit = $oldData['credit'] ?? 0;
                            $solde = $debit - $credit;
                            
                            $totalDebit += $debit;
                            $totalCredit += $credit;
                            $soldeTotal += $solde;
                        @endphp
                        
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="px-4 py-2 text-xs font-mono">
                                <span class="font-medium">{{ $oldData['code'] }}</span>
                                <span class="text-gray-500 text-[10px] block">{{ Str::limit($oldData['intitule'], 40) }}</span>
                            </td>
                            <td class="px-4 py-2 text-right text-xs {{ $debit > 0 ? 'font-medium text-red-600' : 'text-gray-400' }}">
                                {{ $debit > 0 ? number_format($debit, 0, ',', ' ') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs {{ $credit > 0 ? 'font-medium text-green-600' : 'text-gray-400' }}">
                                {{ $credit > 0 ? number_format($credit, 0, ',', ' ') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-medium {{ $solde > 0 ? 'text-red-600' : ($solde < 0 ? 'text-green-600' : 'text-gray-500') }}">
                                {{ $solde != 0 ? number_format(abs($solde), 0, ',', ' ') : '-' }}
                                @if($solde != 0)
                                    <span class="text-[8px] ml-1">{{ $solde > 0 ? 'D' : 'C' }}</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    
                    <!-- Ligne de total -->
                    <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                        <td class="px-4 py-3 text-right text-sm">
                            TOTAL
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-red-600">
                            {{ number_format($totalDebit, 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-green-600">
                            {{ number_format($totalCredit, 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm {{ $soldeTotal > 0 ? 'text-red-600' : ($soldeTotal < 0 ? 'text-green-600' : 'text-gray-500') }}">
                            {{ $soldeTotal != 0 ? number_format(abs($soldeTotal), 0, ',', ' ') : '-' }}
                            @if($soldeTotal != 0)
                                <span class="ml-1">{{ $soldeTotal > 0 ? 'D' : 'C' }}</span>
                            @endif
                        </td>
                    </tr>
                    
                    <!-- Ligne de solde final -->
                    <tr class="bg-blue-50">
                        <td colspan="3" class="px-4 py-3 text-right text-sm font-bold text-blue-800">
                            SOLDE DU COMPTE {{ $selectedTiers['code'] }} AU {{ \Carbon\Carbon::parse($dateFin)->locale('fr')->isoFormat('D MMMM YYYY') }}
                        </td>
                        <td class="px-4 py-3 text-center text-sm font-bold {{ $selectedTiers['solde'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format(abs($selectedTiers['solde']), 0, ',', ' ') }}
                            ({{ $selectedTiers['solde'] > 0 ? 'D' : 'C' }})
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Légende simplifiée -->
        <div class="mt-4 flex justify-end gap-4 text-[10px] text-gray-500">
            <span><span class="text-red-600 font-bold">D</span> = Débiteur</span>
            <span><span class="text-green-600 font-bold">C</span> = Créditeur</span>
        </div>
    </div>
</div>