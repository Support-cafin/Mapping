<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Sommaire - {{ $entreprise->nom }}</title>
    <style>
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 9px; 
            margin: 0;
            padding: 10px;
        }
        
        .header { 
            text-align: center; 
            margin-bottom: 15px; 
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .title { 
            font-size: 16px; 
            font-weight: bold; 
            margin-bottom: 5px;
        }
        
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 9px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }
        
        th {
            background-color: #f2f2f2;
            border: 1px solid #ddd;
            padding: 6px 4px;
            text-align: left;
            font-weight: bold;
        }
        
        td {
            border: 1px solid #ddd;
            padding: 5px 4px;
            vertical-align: top;
        }
        
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .totals {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 7px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="title">GRAND LIVRE GÉNÉRAL - SYNTHÈSE</div>
        <div style="font-size: 11px; font-weight: bold;">{{ $entreprise->nom }} ({{ $entreprise->code }})</div>
        <div style="font-size: 9px; color: #666;">
            Généré le {{ now()->format('d/m/Y à H:i:s') }}
            @if(!empty($filters['date_debut']))
            | Période : {{ $filters['date_debut'] }} au {{ $filters['date_fin'] }}
            @endif
        </div>
    </div>
    
    @if(!$has_details)
    <div class="warning">
        <strong>⚠️ RAPPORT SYNTHÉTIQUE</strong><br>
        Ce document présente uniquement les totaux par compte. 
        Pour obtenir le détail des écritures, veuillez exporter chaque compte individuellement 
        ou filtrer sur une période plus courte.
    </div>
    @endif
    
    <table>
        <thead>
            <tr>
                <th width="10%">Code</th>
                <th width="40%">Intitulé du compte</th>
                <th width="10%" class="text-right">Nb. écritures</th>
                <th width="15%" class="text-right">Total Débit</th>
                <th width="15%" class="text-right">Total Crédit</th>
                <th width="10%" class="text-right">Solde</th>
            </tr>
        </thead>
        <tbody>
            @foreach($accounts as $account)
            @php
                $solde = $account->total_debit - $account->total_credit;
            @endphp
            <tr>
                <td><strong>{{ $account->code }}</strong></td>
                <td>{{ $account->intitule }}</td>
                <td class="text-right">{{ number_format($account->nombre_ecritures, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($account->total_debit, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($account->total_credit, 0, ',', ' ') }}</td>
                <td class="text-right">
                    @if($solde > 0)
                        <span style="color: #c00;">{{ number_format($solde, 0, ',', ' ') }} D</span>
                    @elseif($solde < 0)
                        <span style="color: #090;">{{ number_format(abs($solde), 0, ',', ' ') }} C</span>
                    @else
                        0
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="totals">
        <table style="background: none; border: none;">
            <tr>
                <td width="40%"><strong>TOTAL COMPTES :</strong> {{ $totals['total_comptes'] }}</td>
                <td width="20%" class="text-right"><strong>TOTAL ÉCRITURES :</strong></td>
                <td width="15%" class="text-right">{{ number_format($totals['total_ecritures'], 0, ',', ' ') }}</td>
                <td width="25%"></td>
            </tr>
            <tr>
                <td></td>
                <td class="text-right"><strong>TOTAL DÉBIT :</strong></td>
                <td class="text-right">{{ number_format($totals['total_debit'], 0, ',', ' ') }}</td>
                <td></td>
            </tr>
            <tr>
                <td></td>
                <td class="text-right"><strong>TOTAL CRÉDIT :</strong></td>
                <td class="text-right">{{ number_format($totals['total_credit'], 0, ',', ' ') }}</td>
                <td></td>
            </tr>
            <tr style="border-top: 2px solid #333;">
                <td></td>
                <td class="text-right"><strong>SOLDE GLOBAL :</strong></td>
                <td class="text-right" style="font-size: 10px; font-weight: bold;">
                    {{ number_format($totals['solde_global'], 0, ',', ' ') }}
                    ({{ $totals['is_debiteur'] ? 'DÉBITEUR' : 'CRÉDITEUR' }})
                </td>
                <td></td>
            </tr>
        </table>
    </div>
    
    <div class="footer">
        Document généré automatiquement - {{ config('app.name') }} - Page 1/1
    </div>
</body>
</html>