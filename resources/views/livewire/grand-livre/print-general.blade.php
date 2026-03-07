<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Grand Livre Général - Impression</title>
    <style>
        /* RESET COMPLET POUR L'IMPRESSION */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: white;
            padding: 10px;
            font-size: 8pt;
            line-height: 1.1;
        }
        
        /* Masquer tous les éléments de l'interface */
        nav, header, footer, aside, .sidebar, .navbar, 
        .btn, button, .modal, .filter-card, .stats-footer,
        [x-cloak], .hidden, .print\\:hidden {
            display: none !important;
        }
        
        /* Style du document imprimé */
        .print-header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #000;
        }
        
        .print-header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0 0 3px 0;
        }
        
        .print-header .info {
            font-size: 8pt;
            color: #333;
            margin: 2px 0;
        }
        
        .account-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .account-title {
            background-color: #eee;
            padding: 4px 6px;
            margin: 10px 0 5px 0;
            font-weight: bold;
            font-size: 9pt;
            border-left: 3px solid #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 3px 0;
            font-size: 7.5pt;
        }
        
        th {
            background-color: #ddd;
            padding: 4px 3px;
            border: 1px solid #999;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            padding: 3px;
            border: 1px solid #ccc;
        }
        
        .amount {
            text-align: right;
            font-family: 'Courier New', monospace;
            padding-right: 8px;
        }
        
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        
        .solde-row {
            background-color: #f9f9f9;
            border-top: 2px solid #999;
        }
        
        .debit-cell {
            background-color: #ffe6e6;
        }
        
        .credit-cell {
            background-color: #e6ffe6;
        }
        
        .global-stats {
            margin-top: 20px;
            padding: 8px;
            border-top: 2px solid #333;
            font-size: 8pt;
        }
        
        .stats-table {
            width: 70%;
            margin: 0 auto;
            border: none;
        }
        
        .stats-table td {
            border: none;
            padding: 3px 8px;
        }
        
        .print-footer {
            text-align: center;
            margin-top: 15px;
            font-size: 6pt;
            color: #666;
            border-top: 1px dotted #ccc;
            padding-top: 5px;
        }
        
        /* Forcer les sauts de page */
        .page-break {
            page-break-before: always;
        }
        
        /* Optimisation pour l'impression */
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .account-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="print-header">
        <h1>GRAND LIVRE GÉNÉRAL</h1>
        <div class="info">{{ $entreprise->nom }} ({{ $entreprise->code }})</div>
        <div class="info">Période: {{ $dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : '' }} au {{ $dateFin ? \Carbon\Carbon::parse($dateFin)->format('d/m/Y') : '' }}</div>
        <div class="info">Généré le: {{ now()->format('d/m/Y H:i') }}</div>
        @if(!empty($selectedAccounts))
        <div class="info">{{ count($selectedAccounts) }} compte(s) sélectionné(s)</div>
        @endif
    </div>

    @foreach($accounts as $account)
    <div class="account-section">
        <div class="account-title">
            COMPTE {{ $account['code'] }} - {{ $account['intitule'] }} 
            ({{ $account['total_ecritures'] }} écritures)
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
                @foreach($account['ecritures'] as $e)
                <tr>
                    <td>{{ $e->date_ecriture ? \Carbon\Carbon::parse($e->date_ecriture)->format('d/m') : '' }}</td>
                    <td>{{ $e->piece ?? '' }}</td>
                    <td>{{ $e->journal_code ?? '' }}</td>
                    <td>{{ $e->old_account_code ?? '' }}</td>
                    <td>{{ Str::limit($e->libelle ?? '', 35) }}</td>
                    <td class="amount">{{ $e->debit > 0 ? number_format($e->debit, 0, ',', ' ') : '' }}</td>
                    <td class="amount">{{ $e->credit > 0 ? number_format($e->credit, 0, ',', ' ') : '' }}</td>
                </tr>
                @endforeach
                
                @php
                    $solde = $account['total_debit'] - $account['total_credit'];
                @endphp
                
                <tr class="total-row">
                    <td colspan="5"><strong>TOTAL {{ $account['code'] }}</strong></td>
                    <td class="amount"><strong>{{ number_format($account['total_debit'], 0, ',', ' ') }}</strong></td>
                    <td class="amount"><strong>{{ number_format($account['total_credit'], 0, ',', ' ') }}</strong></td>
                </tr>
                
                <tr class="solde-row">
                    <td colspan="5"><strong>SOLDE {{ $account['code'] }}</strong></td>
                    @if($solde > 0)
                        <td class="amount debit-cell"><strong>{{ number_format($solde, 0, ',', ' ') }}</strong></td>
                        <td></td>
                    @else
                        <td></td>
                        <td class="amount credit-cell"><strong>{{ number_format(abs($solde), 0, ',', ' ') }}</strong></td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>
    
    @if($loop->iteration % 3 == 0 && !$loop->last)
        <div class="page-break"></div>
    @endif
    @endforeach

    <div class="global-stats">
        <table class="stats-table">
            <tr>
                <td><strong>Total Comptes:</strong></td>
                <td>{{ $stats['total_comptes'] }}</td>
                <td><strong>Total Écritures:</strong></td>
                <td>{{ number_format($stats['total_ecritures'], 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td><strong>Total Débit:</strong></td>
                <td>{{ number_format($stats['total_debit'], 0, ',', ' ') }}</td>
                <td><strong>Total Crédit:</strong></td>
                <td>{{ number_format($stats['total_credit'], 0, ',', ' ') }}</td>
            </tr>
            <tr>
                <td colspan="4" style="text-align: center; padding-top: 8px;">
                    <strong>SOLDE GLOBAL: {{ number_format($stats['solde_global_absolu'], 0, ',', ' ') }} 
                    ({{ $stats['is_debiteur'] ? 'Débit' : 'Crédit' }})</strong>
                </td>
            </tr>
        </table>
    </div>

    <div class="print-footer">
        Document généré automatiquement - Page 1/1
    </div>
</body>
</html>