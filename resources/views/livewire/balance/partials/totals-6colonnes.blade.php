@php
    $totalOuvertureDebit = 0;
    $totalOuvertureCredit = 0;
    $totalMouvementDebit = 0;
    $totalMouvementCredit = 0;
    $totalClotureDebit = 0;
    $totalClotureCredit = 0;
    
    foreach($balances as $balance) {
        $soldeOuvertureDebit = $balance['solde_ouverture_debit'] ?? 0;
        $soldeOuvertureCredit = $balance['solde_ouverture_credit'] ?? 0;
        $mouvementDebit = $balance['total_debit'] ?? 0;
        $mouvementCredit = $balance['total_credit'] ?? 0;
        
        $totalOuvertureDebit += $soldeOuvertureDebit;
        $totalOuvertureCredit += $soldeOuvertureCredit;
        $totalMouvementDebit += $mouvementDebit;
        $totalMouvementCredit += $mouvementCredit;
        
        $soldeCloture = ($soldeOuvertureDebit + $mouvementDebit) - ($soldeOuvertureCredit + $mouvementCredit);
        if($soldeCloture > 0) {
            $totalClotureDebit += $soldeCloture;
        } else {
            $totalClotureCredit += abs($soldeCloture);
        }
    }
    
    $totalDebitGeneral = $totalOuvertureDebit + $totalMouvementDebit;
    $totalCreditGeneral = $totalOuvertureCredit + $totalMouvementCredit;
    $equilibre = abs($totalDebitGeneral - $totalCreditGeneral) < 0.01;
@endphp

<tfoot class="bg-gray-50 font-medium border-t">
    <tr>
        <td colspan="2" style="font-size: 12px;" class="px-6 py-2 text-right text-gray-700 border-x">
            TOTAUX
        </td>
        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
            {{ number_format($totalOuvertureDebit, 0, ',', ' ') }}
        </td>
        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
            {{ number_format($totalOuvertureCredit, 0, ',', ' ') }}
        </td>
        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
            {{ number_format($totalMouvementDebit, 0, ',', ' ') }}
        </td>
        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
            {{ number_format($totalMouvementCredit, 0, ',', ' ') }}
        </td>
        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
            {{ number_format($totalClotureDebit, 0, ',', ' ') }}
        </td>
        <td style="font-size: 12px;" class="px-6 py-2 text-right text-black-600 border-x">
            {{ number_format($totalClotureCredit, 0, ',', ' ') }}
        </td>
        <td class="border-x no-print"></td>
    </tr>
    <tr class="bg-blue-50">
        <td colspan="9" class="px-6 py-2 text-center border-x">
            <span style="font-size: 12px;" class="font-medium text-blue-800">
                ÉQUILIBRE (A+C) = (B+D) : 
                {{ number_format($totalDebitGeneral, 0, ',', ' ') }} = {{ number_format($totalCreditGeneral, 0, ',', ' ') }}
                @if($equilibre)
                    <i class="fas fa-check-circle text-green-600 ml-2"></i>
                @else
                    <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                @endif
            </span>
        </td>
    </tr>
</tfoot>