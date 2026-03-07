<div class="mt-6 bg-white rounded-lg shadow border">
    <div class="px-6 py-4 border-b">
        <h3 class="font-semibold text-gray-900 flex items-center" style="font-size: 11px;">
            <i class="fas fa-chart-bar mr-2 text-blue-600"></i>
            Statistiques par classe de comptes
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 11px;">Classe de comptes SYCEBNL</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 11px;">Total Débit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 11px;">Total Crédit</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase" style="font-size: 11px;">Solde</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                    <td class="px-6 py-3">
                        <div class="flex items-center">
                            <span class="font-mono text-xs bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded mr-2">1-5</span>
                            <div class="font-medium text-gray-900" style="font-size: 11px;">Comptes SYCEBNL 1 à 5</div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-red-600 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['classe_1_5']['debit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 text-green-600 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['classe_1_5']['credit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 font-bold {{ $classStats['classe_1_5']['solde'] > 0 ? 'text-red-600' : 'text-green-600' }}" style="font-size: 11px;">
                        {{ number_format(abs($classStats['classe_1_5']['solde']), 0, ' ', ' ') }} FCFA
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-3">
                        <div class="flex items-center">
                            <span class="font-mono text-xs bg-green-100 text-green-800 px-1.5 py-0.5 rounded mr-2">6-7</span>
                            <div class="font-medium text-gray-900" style="font-size: 11px;">Comptes SYCEBNL 6 à 7</div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-red-600 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['classe_6_7']['debit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 text-green-600 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['classe_6_7']['credit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 font-bold {{ $classStats['classe_6_7']['solde'] > 0 ? 'text-red-600' : 'text-green-600' }}" style="font-size: 11px;">
                        {{ number_format(abs($classStats['classe_6_7']['solde']), 0, ' ', ' ') }} FCFA
                    </td>
                </tr>
                <tr>
                    <td class="px-6 py-3">
                        <div class="flex items-center">
                            <span class="font-mono text-xs bg-red-100 text-red-800 px-1.5 py-0.5 rounded mr-2">NM</span>
                            <div class="font-medium text-gray-900" style="font-size: 11px;">Écritures non mappées</div>
                        </div>
                    </td>
                    <td class="px-6 py-3 text-red-600 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['non_mappes']['debit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 text-green-600 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['non_mappes']['credit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 font-bold {{ $classStats['non_mappes']['solde'] > 0 ? 'text-red-600' : 'text-green-600' }}" style="font-size: 11px;">
                        {{ number_format(abs($classStats['non_mappes']['solde']), 0, ' ', ' ') }} FCFA
                    </td>
                </tr>
                <tr class="bg-gray-50 font-bold border-t-2">
                    <td class="px-6 py-3 font-bold text-gray-900" style="font-size: 11px;">TOTAL GLOBAL</td>
                    <td class="px-6 py-3 text-red-700 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['global']['debit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 text-green-700 font-bold" style="font-size: 11px;">
                        {{ number_format($classStats['global']['credit'], 0, ' ', ' ') }} FCFA
                    </td>
                    <td class="px-6 py-3 text-blue-700 font-bold" style="font-size: 11px;">
                        {{ number_format(abs($classStats['global']['solde']), 0, ' ', ' ') }} FCFA
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>