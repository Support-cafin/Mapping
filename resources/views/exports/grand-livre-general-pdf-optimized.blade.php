<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Général - {{ $entreprise->nom ?? 'Entreprise' }}</title>
    <style>
        /* Styles minimalistes pour performance */
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.2;
            margin: 1cm;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0 0 3px;
            color: #333;
        }
        .header h3 {
            font-size: 12px;
            margin: 2px 0;
            color: #666;
        }
        .info {
            margin-bottom: 10px;
            padding: 5px;
            background-color: #f5f5f5;
            border-radius: 3px;
            font-size: 8px;
        }
        .compte-header {
            margin-top: 15px;
            margin-bottom: 5px;
            padding: 3px 5px;
            background-color: #e3f2fd;
            border-left: 3px solid #2196f3;
            font-weight: bold;
        }
        .compte-header h3 {
            margin: 0;
            font-size: 10px;
            color: #1565c0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        th {
            background-color: #333;
            color: white;
            padding: 3px 2px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
        }
        td {
            padding: 2px;
            border-bottom: 1px solid #ddd;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-row {
            background-color: #e0e0e0;
            font-weight: bold;
        }
        .total-general {
            margin-top: 15px;
            padding: 5px;
            background-color: #ffecb3;
            border-left: 3px solid #ff9800;
            font-weight: bold;
        }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 7px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        .page-break {
            page-break-after: always;
        }
        .solde-debiteur {
            color: #d32f2f;
        }
        .solde-crediteur {
            color: #388e3c;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAND LIVRE GÉNÉRAL</h1>
        <h3>{{ $entreprise->nom ?? '' }} ({{ $entreprise->code ?? '' }})</h3>
        <p>
            @if(!empty($filters['dateDebut']))
                Du {{ \Carbon\Carbon::parse($filters['dateDebut'])->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($filters['dateFin'])->format('d/m/Y') }}
            @endif
        </p>
    </div>

    <div class="info">
        <strong>{{ $count }} comptes</strong> - Édition: {{ $dateGeneration }}
        @if(!empty($filters['journalCode']))
            - Journal: {{ $filters['journalCode'] }}
        @endif
    </div>

    @forelse($comptes as $compte)
        <div class="compte-header">
            <h3>Compte {{ $compte['code'] }} - {{ $compte['intitule'] }}</h3>
        </div>

        <table>
            <thead>
                <tr>
                    <th width="60%">Libellé</th>
                    <th width="15%">Débit</th>
                    <th width="15%">Crédit</th>
                    <th width="10%">Nb</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Totaux du compte</strong></td>
                    <td class="text-right"><strong>{{ number_format($compte['total_debit'], 0, ',', ' ') }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($compte['total_credit'], 0, ',', ' ') }}</strong></td>
                    <td class="text-center">{{ $compte['nb_ecritures'] }}</td>
                </tr>
                <tr>
                    <td><strong>Solde</strong></td>
                    <td colspan="2" class="text-center {{ $compte['solde'] > 0 ? 'solde-debiteur' : 'solde-crediteur' }}">
                        <strong>{{ number_format($compte['solde_absolu'], 0, ',', ' ') }} FCFA</strong>
                        ({{ $compte['type_solde'] }})
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        @if(!$loop->last)
            <div style="margin-bottom: 10px;"></div>
        @endif
    @empty
        <div style="text-align: center; padding: 30px;">
            Aucun compte trouvé
        </div>
    @endforelse

    <!-- Totaux généraux -->
    <div class="total-general">
        <table style="width: 100%; background-color: transparent;">
            <tr>
                <td width="60%"><strong>TOTAL GÉNÉRAL DÉBIT</strong></td>
                <td width="40%" class="text-right"><strong>{{ number_format($total_general_debit, 0, ',', ' ') }} FCFA</strong></td>
            </tr>
            <tr>
                <td><strong>TOTAL GÉNÉRAL CRÉDIT</strong></td>
                <td class="text-right"><strong>{{ number_format($total_general_credit, 0, ',', ' ') }} FCFA</strong></td>
            </tr>
            <tr>
                <td><strong>SOLDE GÉNÉRAL</strong></td>
                <td class="text-right"><strong>{{ number_format($total_general_solde, 0, ',', ' ') }} FCFA</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Document généré le {{ $dateGeneration }} - Grand Livre Général
    </div>
</body>
</html>
