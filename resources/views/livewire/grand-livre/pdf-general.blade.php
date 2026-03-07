<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Grand Livre Général</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 5px;
            font-size: 8pt;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        h1 {
            font-size: 14pt;
            margin: 0;
        }
        .account-header {
            background: #eee;
            padding: 3px;
            margin: 10px 0 3px;
            font-weight: bold;
            font-size: 9pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 2px 0;
            font-size: 7pt;
        }
        th {
            background: #ddd;
            padding: 2px;
            border: 1px solid #aaa;
            text-align: left;
        }
        td {
            padding: 2px;
            border: 1px solid #aaa;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
        .total-row {
            background: #f0f0f0;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 6pt;
            color: #666;
            border-top: 1px dotted #ccc;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAND LIVRE GÉNÉRAL</h1>
        <div>{{ $entreprise->nom }} ({{ $entreprise->code }})</div>
        <div>Période: {{ $dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : '' }} au {{ $dateFin ? \Carbon\Carbon::parse($dateFin)->format('d/m/Y') : '' }}</div>
        <div>Généré le: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    @foreach($accounts as $account)
    <div>
        <div class="account-header">
            COMPTE {{ $account->code }} - {{ $account->intitule }} ({{ $account->total_ecritures }} écrit.)
        </div>
        
        <table>
            <thead>
                <tr>
                    <th width="10%">Date</th>
                    <th width="8%">Pièce</th>
                    <th width="8%">Journal</th>
                    <th width="8%">Cpt</th>
                    <th width="38%">Libellé</th>
                    <th width="14%">Débit</th>
                    <th width="14%">Crédit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($account->ecritures as $e)
                <tr>
                    <td>{{ $e->date_ecriture ? \Carbon\Carbon::parse($e->date_ecriture)->format('d/m') : '' }}</td>
                    <td>{{ $e->piece ?? '' }}</td>
                    <td>{{ $e->journal_code ?? '' }}</td>
                    <td>{{ $e->old_account_code ?? '' }}</td>
                    <td>{{ Str::limit($e->libelle ?? '', 40) }}</td>
                    <td class="amount">{{ $e->debit > 0 ? number_format($e->debit, 0, ',', ' ') : '' }}</td>
                    <td class="amount">{{ $e->credit > 0 ? number_format($e->credit, 0, ',', ' ') : '' }}</td>
                </tr>
                @endforeach
                
                @php
                    $solde = $account->total_debit - $account->total_credit;
                @endphp
                
                <tr class="total-row">
                    <td colspan="5"><strong>TOTAL</strong></td>
                    <td class="amount"><strong>{{ number_format($account->total_debit, 0, ',', ' ') }}</strong></td>
                    <td class="amount"><strong>{{ number_format($account->total_credit, 0, ',', ' ') }}</strong></td>
                </tr>
                
                <tr>
                    <td colspan="5"><strong>SOLDE</strong></td>
                    @if($solde > 0)
                        <td class="amount"><strong>{{ number_format($solde, 0, ',', ' ') }}</strong></td>
                        <td></td>
                    @else
                        <td></td>
                        <td class="amount"><strong>{{ number_format(abs($solde), 0, ',', ' ') }}</strong></td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>
    
    @if(!$loop->last)
    <div style="height: 10px;"></div>
    @endif
    @endforeach

    <div style="margin-top: 15px; border-top: 1px solid #aaa; padding: 5px;">
        <table style="width: 70%; margin: 0 auto;">
            <tr>
                <td><strong>Total Comptes:</strong> {{ $stats['total_comptes'] }}</td>
                <td><strong>Total Écritures:</strong> {{ number_format($stats['total_ecritures'], 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td><strong>Total Débit:</strong> {{ number_format($stats['total_debit'], 0, ',', ' ') }}</td>
                <td><strong>Total Crédit:</strong> {{ number_format($stats['total_credit'], 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td colspan="2" style="text-align: center; padding-top: 8px;">
                    <strong>SOLDE GLOBAL: {{ number_format($stats['solde_global_absolu'], 0, ',', ' ') }} 
                    ({{ $stats['is_debiteur'] ? 'Débit' : 'Crédit' }})</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Document généré automatiquement
    </div>
</body>
</html>