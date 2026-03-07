<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre - {{ $entreprise->nom ?? '' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #111; background: #fff; }

        .header { border-bottom: 2px solid #999; padding-bottom: 8px; margin-bottom: 10px; }
        .header h1 { font-size: 14px; letter-spacing: 1px; }
        .header h2 { font-size: 11px; font-weight: normal; color: #444; margin-top: 3px; }
        .header p  { font-size: 9px; color: #555; margin-top: 2px; }

        .meta { background: #f2f2f2; border: 1px solid #ccc; padding: 5px 8px; margin-bottom: 10px; }
        .meta span { margin-right: 20px; }

        table { width: 100%; border-collapse: collapse; }
        thead tr th {
            background: #e0e0e0;
            color: #111;
            font-weight: bold;
            font-size: 8px;
            padding: 5px 4px;
            border: 1px solid #bbb;
            text-align: left;
        }
        tbody tr td {
            padding: 4px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }
        tbody tr:nth-child(even) td { background: #fafafa; }

        .text-right  { text-align: right; }
        .text-center { text-align: center; }

        .total-row td {
            background: #e0e0e0 !important;
            border-top: 2px solid #999;
            font-weight: bold;
            padding: 5px 4px;
        }
        .solde-row td {
            background: #ececec !important;
            font-weight: bold;
            padding: 5px 4px;
        }
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
        <h1>GRAND LIVRE COMPTABLE</h1>
        <h2>{{ $entreprise->nom ?? '' }} {{ $entreprise->code ? '(' . $entreprise->code . ')' : '' }}</h2>
        <p>Période : {{ \Carbon\Carbon::parse($filters['dateDebut'])->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($filters['dateFin'])->format('d/m/Y') }}</p>
    </div>

    <div class="meta">
        <span><strong>Édition :</strong> {{ $dateGeneration }}</span>
        <span><strong>Écritures :</strong> {{ number_format($ecritures->count(), 0, ',', ' ') }}</span>
        @if(!empty($filters['journalCode']))
            <span><strong>Journal :</strong> {{ $filters['journalCode'] }}</span>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th width="4%">N°</th>
                <th width="8%">Date</th>
                <th width="7%">Journal</th>
                <th width="9%">Pièce</th>
                <th width="13%">Compte</th>
                <th width="27%">Libellé</th>
                <th width="11%" class="text-right">Débit</th>
                <th width="11%" class="text-right">Crédit</th>
                <th width="10%">Source</th>
            </tr>
        </thead>
        <tbody>
            @php $num = 1; @endphp
            @foreach($ecritures as $e)
                <tr>
                    <td class="text-center">{{ $num++ }}</td>
                    <td>{{ $e->date_ecriture->format('d/m/Y') }}</td>
                    <td>{{ $e->journal_code ?? '-' }}</td>
                    <td>{{ $e->piece ?? '-' }}</td>
                    <td>
                        @if($e->oldAccount)
                            <strong>{{ $e->oldAccount->code }}</strong><br>
                            <span style="font-size:8px;color:#555;">{{ \Illuminate\Support\Str::limit($e->oldAccount->intitule, 18) }}</span>
                        @else -
                        @endif
                    </td>
                    <td>{{ \Illuminate\Support\Str::limit($e->libelle, 40) }}</td>
                    <td class="text-right">{{ $e->debit > 0 ? number_format($e->debit, 0, ',', ' ') : '-' }}</td>
                    <td class="text-right">{{ $e->credit > 0 ? number_format($e->credit, 0, ',', ' ') : '-' }}</td>
                    <td class="text-center">{{ $e->source === 'manuel' ? 'Manuel' : 'Import' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="6" class="text-right">TOTAUX</td>
                <td class="text-right">{{ number_format($totaux['debit'], 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($totaux['credit'], 0, ',', ' ') }}</td>
                <td></td>
            </tr>
            <tr class="solde-row">
                <td colspan="6" class="text-right">SOLDE</td>
                <td colspan="2" class="text-center">{{ number_format($totaux['solde'], 0, ',', ' ') }} FCFA</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">Document généré le {{ $dateGeneration }} — Grand Livre Comptable</div>
</body>
</html>
