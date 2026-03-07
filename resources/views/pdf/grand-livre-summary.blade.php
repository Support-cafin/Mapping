{{-- resources/views/pdf/grand-livre-summary.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Général - Synthèse</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 15px; }
        .header { text-align: center; margin-bottom: 15px; }
        h1 { font-size: 14px; margin: 0; color: #000; }
        .info { font-size: 8px; color: #666; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #f0f0f0; padding: 4px; border: 1px solid #ddd; text-align: left; }
        td { padding: 4px; border: 1px solid #ddd; }
        .total { font-weight: bold; background: #e8e8e8; }
        .debit { color: #c00; text-align: right; }
        .credit { color: #090; text-align: right; }
        .solde-debit { background: #ffe6e6; }
        .solde-credit { background: #e6ffe6; }
        .global-totals { background: #333; color: white; padding: 8px; margin-top: 10px; border-radius: 3px; }
        .footer { text-align: center; margin-top: 10px; padding-top: 5px; border-top: 1px solid #ddd; font-size: 6pt; color: #777; }
    </style>
</head>
<body>
    <div class="header">
        <h1>SYNTHÈSE DU GRAND LIVRE GÉNÉRAL</h1>
        <div class="info">{{ $entreprise->nom }} ({{ $entreprise->code }})</div>
        <div class="info">Généré le: {{ $date_generation }}</div>
    </div>
    
    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 8px; margin-bottom: 10px; font-size: 8px;">
        <strong>Document de synthèse</strong> - {{ number_format($totals['total_ecritures'], 0, ',', ' ') }} écritures regroupées en {{ $totals['total_comptes'] }} comptes.
        Les totaux présentés sont complets et exacts.
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="12%">Compte</th>
                <th width="38%">Intitulé</th>
                <th width="10%">Écritures</th>
                <th width="15%">Débit</th>
                <th width="15%">Crédit</th>
                <th width="10%">Solde</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $account)
            @php
                $solde = $account->total_debit - $account->total_credit;
                $soldeClass = $solde > 0 ? 'solde-debit' : 'solde-credit';
                $soldeText = number_format(abs($solde), 0, ',', ' ');
            @endphp
            <tr>
                <td><strong>{{ $account->new_account_code }}</strong></td>
                <td>{{ Str::limit($account->new_account_intitule, 50) }}</td>
                <td>{{ number_format($account->nombre_ecritures, 0, ',', ' ') }}</td>
                <td class="debit">{{ number_format($account->total_debit, 0, ',', ' ') }}</td>
                <td class="credit">{{ number_format($account->total_credit, 0, ',', ' ') }}</td>
                <td class="{{ $soldeClass }}">{{ $soldeText }}</td>
            </tr>
            @endforeach
            
            <tr class="total">
                <td colspan="2"><strong>TOTAUX GÉNÉRAUX</strong></td>
                <td><strong>{{ number_format($totals['total_ecritures'], 0, ',', ' ') }}</strong></td>
                <td class="debit"><strong>{{ number_format($totals['total_debit'], 0, ',', ' ') }}</strong></td>
                <td class="credit"><strong>{{ number_format($totals['total_credit'], 0, ',', ' ') }}</strong></td>
                <td class="{{ $totals['is_debiteur'] ? 'solde-debit' : 'solde-credit' }}">
                    <strong>{{ number_format($totals['solde_global'], 2, ',', ' ') }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
    
    <div class="global-totals">
        <div style="text-align: center;">
            <div style="font-size: 9px; margin-bottom: 3px;">SOLDE GLOBAL</div>
            <div style="font-size: 14px; font-weight: bold; color: {{ $totals['is_debiteur'] ? '#ff9999' : '#99ff99' }};">
                {{ number_format($totals['solde_global'], 2, ',', ' ') }}
                ({{ $totals['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT' }})
            </div>
        </div>
    </div>
    
    <div class="footer">
        Document généré automatiquement - {{ config('app.name') }} - Page 1/1
    </div>
</body>
</html>