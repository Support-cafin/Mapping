<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Général - {{ $entreprise->nom ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; background: #fff; }

        .header { border-bottom: 2px solid #999; padding-bottom: 8px; margin-bottom: 10px; }
        .header h1 { font-size: 14px; letter-spacing: 1px; }
        .header h2 { font-size: 11px; font-weight: normal; color: #444; margin-top: 3px; }
        .header p  { font-size: 9px; color: #555; margin-top: 2px; }

        .meta { background: #f2f2f2; border: 1px solid #ccc; padding: 5px 8px; margin-bottom: 10px; }

        .compte-header {
            background: #e8e8e8;
            border-left: 3px solid #888;
            padding: 5px 8px;
            margin-top: 18px;
            margin-bottom: 4px;
            font-size: 10px;
            font-weight: bold;
        }

        table { width: 100%; border-collapse: collapse; }
        thead tr th {
            background: #e0e0e0;
            color: #111;
            font-weight: bold;
            font-size: 8px;
            padding: 4px;
            border: 1px solid #bbb;
            text-align: left;
        }
        tbody tr td {
            padding: 3px 4px;
            border-bottom: 1px solid #ddd;
        }
        tbody tr:nth-child(even) td { background: #fafafa; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        .total-row td {
            background: #e0e0e0 !important;
            border-top: 1px solid #999;
            font-weight: bold;
            padding: 4px;
        }
        .solde-row td {
            background: #ececec !important;
            font-weight: bold;
            padding: 4px;
        }

        .totaux-generaux { margin-top: 25px; }
        .totaux-generaux .section-title {
            background: #d0d0d0;
            border-left: 3px solid #555;
            padding: 5px 8px;
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 4px;
        }
        .totaux-generaux table td {
            background: #ececec;
            font-weight: bold;
            padding: 5px 8px;
            border-bottom: 1px solid #ccc;
        }

        .page-break { page-break-after: always; }
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ccc;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAND LIVRE GÉNÉRAL</h1>
        <h2>{{ $entreprise->nom ?? '' }} {{ $entreprise->code ? '(' . $entreprise->code . ')' : '' }}</h2>
        <p>Période : {{ \Carbon\Carbon::parse($filters['dateDebut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($filters['dateFin'])->format('d/m/Y') }}</p>
    </div>

    <div class="meta">
        <strong>Édition :</strong> {{ $dateGeneration }} &nbsp;&nbsp;
        <strong>Comptes :</strong> {{ count($comptes) }}
    </div>

    @foreach($comptes as $compte)
        <div class="compte-header">
            Compte {{ $compte['code'] }} — {{ $compte['intitule'] }}
        </div>

        <table>
            <thead>
                <tr>
                    <th width="4%">N°</th>
                    <th width="10%">Date</th>
                    <th width="8%">Journal</th>
                    <th width="10%">Pièce</th>
                    <th width="38%">Libellé</th>
                    <th width="12%" class="text-right">Débit</th>
                    <th width="12%" class="text-right">Crédit</th>
                    <th width="6%">SYCEBNL</th>
                </tr>
            </thead>
            <tbody>
                @php $num = 1; @endphp
                @foreach($compte['ecritures'] as $e)
                    <tr>
                        <td class="text-center">{{ $num++ }}</td>
                        <td>{{ $e->date_ecriture->format('d/m/Y') }}</td>
                        <td>{{ $e->journal_code ?? '-' }}</td>
                        <td>{{ $e->piece ?? '-' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($e->libelle, 45) }}</td>
                        <td class="text-right">{{ $e->debit > 0 ? number_format($e->debit, 0, ',', ' ') : '' }}</td>
                        <td class="text-right">{{ $e->credit > 0 ? number_format($e->credit, 0, ',', ' ') : '' }}</td>
                        <td>{{ $e->newAccount ? $e->newAccount->code : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5" class="text-right">Totaux du compte</td>
                    <td class="text-right">{{ number_format($compte['total_debit'], 0, ',', ' ') }}</td>
                    <td class="text-right">{{ number_format($compte['total_credit'], 0, ',', ' ') }}</td>
                    <td></td>
                </tr>
                <tr class="solde-row">
                    <td colspan="5" class="text-right">Solde</td>
                    <td colspan="2" class="text-center">
                        {{ number_format($compte['solde_absolu'], 0, ',', ' ') }} FCFA ({{ $compte['type_solde'] }})
                    </td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        @if(!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach

    <div class="totaux-generaux">
        <div class="section-title">TOTAUX GÉNÉRAUX</div>
        <table>
            <tr><td class="text-right" width="70%">Total Général Débit</td><td class="text-right">{{ number_format($total_general_debit, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td class="text-right">Total Général Crédit</td><td class="text-right">{{ number_format($total_general_credit, 0, ',', ' ') }} FCFA</td></tr>
            <tr><td class="text-right">Solde Général</td><td class="text-right">{{ number_format($total_general_solde, 0, ',', ' ') }} FCFA</td></tr>
        </table>
    </div>

    <div class="footer">Document généré le {{ $dateGeneration }} — Grand Livre Général</div>
</body>
</html>
