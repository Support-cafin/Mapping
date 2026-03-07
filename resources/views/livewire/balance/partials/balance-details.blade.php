<div id="balance-details" class="bg-white rounded-lg shadow border overflow-hidden">
    <!-- En-tête -->
    <div class="px-6 py-4 border-b bg-gray-50">
        <div class="flex justify-between items-center">
            <div>
                <h2 style="font-size: 14px;" class="font-bold text-gray-900">
                    <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                    Détail du compte {{ $selectedBalance['code'] }}
                </h2>
                <p style="font-size: 13px;" class="text-gray-600 mt-1">
                    {{ $selectedBalance['intitule'] }}
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

    
    <!-- Tableau des écritures simplifié -->
    <div class="p-6">
        <h3 style="font-size: 13px;" class="font-semibold text-gray-900 mb-4">
            <i class="fas fa-list-alt mr-2 text-blue-600"></i>
            Détail par compte entité
        </h3>
        
        <div class="overflow-x-auto">
            <table class="min-w-full border-collapse">
                <thead class="bg-gray-800 text-white">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs">Compte entité</th>
                        <th class="px-4 py-2 text-right text-xs">Débit</th>
                        <th class="px-4 py-2 text-right text-xs">Crédit</th>
                        <th class="px-4 py-2 text-right text-xs">Solde</th>
                    </tr>
                </thead>
                
                <tbody>
                    @foreach($selectedBalance['old_accounts_data'] as $oldData)
                        <tr class="hover:bg-gray-50 border-b border-gray-100">
                            <td class="px-4 py-2 text-xs font-mono">
                                {{ $oldData['code'] }} - {{ $oldData['intitule'] }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs {{ $oldData['debit'] > 0 ? 'font-medium text-red-600' : 'text-gray-400' }}">
                                {{ $oldData['debit'] > 0 ? number_format($oldData['debit'], 0, ',', ' ') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs {{ $oldData['credit'] > 0 ? 'font-medium text-green-600' : 'text-gray-400' }}">
                                {{ $oldData['credit'] > 0 ? number_format($oldData['credit'], 0, ',', ' ') : '-' }}
                            </td>
                            <td class="px-4 py-2 text-right text-xs font-medium {{ $oldData['solde'] > 0 ? 'text-red-600' : ($oldData['solde'] < 0 ? 'text-green-600' : 'text-gray-500') }}">
                                {{ $oldData['solde'] != 0 ? number_format(abs($oldData['solde']), 0, ',', ' ') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                    
                    <!-- Ligne de total -->
                    <tr class="bg-gray-100 font-bold">
                        <td class="px-4 py-3 text-right text-sm">TOTAL</td>
                        <td class="px-4 py-3 text-right text-sm text-red-600">
                            {{ number_format($selectedBalance['total_debit'], 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm text-green-600">
                            {{ number_format($selectedBalance['total_credit'], 0, ',', ' ') }}
                        </td>
                        <td class="px-4 py-3 text-right text-sm {{ $selectedBalance['solde'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                            {{ number_format(abs($selectedBalance['solde']), 0, ',', ' ') }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>