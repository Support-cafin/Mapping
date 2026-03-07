<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre - {{ $entreprise->nom ?? 'Entreprise' }}</title>
    <style>
        /* Styles minimalistes pour la vitesse */
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; line-height: 1.2; margin: 0.5cm; }
        .header { text-align: center; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px; }
        .header h1 { font-size: 14px; margin: 0; }
        .header h3 { font-size: 11px; margin: 2px 0; color: #666; }
        .info { margin-bottom: 10px; padding: 5px; background-color: #f5f5f5; font-size: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #333; color: white; padding: 3px; text-align: left; font-size: 8px; }
        td { padding: 2px 3px; border-bottom: 1px solid #ddd; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-row { background-color: #e0e0e0; font-weight: bold; }
        .footer { margin-top: 10px; text-align: center; font-size: 7px; color: #999; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAND LIVRE</h1>
        <h3>{{ $entreprise->nom ?? '' }}</h3>
        <p>
            @if(!empty($filters['dateDebut']))
                {{ \Carbon\Carbon::parse($filters['dateDebut'])->format('d/m/Y') }} - 
                {{ \Carbon\Carbon::parse($filters['dateFin'])->format('d/m/Y') }}
            @endif
        </p>
    </div>
    
    <div class="info">
        <strong>{{ $count }} écritures</strong> - Édition: {{ $dateGeneration }}
    </div>
    
    <table>
        <thead>
            <tr>
                <th width="3%">N°</th>
                <th width="6%">Date</th>
                <th width="4%">J</th>
                <th width="6%">Pièce</th>
                <th width="8%">Code</th>
                <th width="20%">Libellé Compte</th>
                <th width="25%">Libellé Écriture</th>
                <th width="10%">Débit</th>
                <th width="10%">Crédit</th>
            </tr>
        </thead>
        <tbody>
            @php $num = 1; @endphp
            @foreach($ecritures as $ecriture)
                <tr>
                    <td class="text-center">{{ $num++ }}</td>
                    <td>{{ isset($ecriture->date_ecriture) ? date('d/m/Y', strtotime($ecriture->date_ecriture)) : '-' }}</td>
                    <td>{{ $ecriture->journal_code ?? '-' }}</td>
                    <td>{{ $ecriture->piece ?? '-' }}</td>
                    <td><strong>{{ $ecriture->old_code ?? '-' }}</strong></td>
                    <td>{{ isset($ecriture->old_intitule) ? substr($ecriture->old_intitule, 0, 25) : '-' }}</td>
                    <td>{{ isset($ecriture->libelle) ? substr($ecriture->libelle, 0, 30) : '-' }}</td>
                    <td class="text-right">{{ $ecriture->debit > 0 ? number_format($ecriture->debit, 0, ',', ' ') : '-' }}</td>
                    <td class="text-right">{{ $ecriture->credit > 0 ? number_format($ecriture->credit, 0, ',', ' ') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" class="text-right">TOTAUX</td>
                <td class="text-right">{{ number_format($totaux['debit'], 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($totaux['credit'], 0, ',', ' ') }}</td>
            </tr>
            <tr class="total-row" style="background-color: #c0c0c0;">
                <td colspan="7" class="text-right">SOLDE</td>
                <td colspan="2" class="text-center">{{ number_format($totaux['solde'], 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        Document généré le {{ $dateGeneration }}
    </div>
</body>
</html>