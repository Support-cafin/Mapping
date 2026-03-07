<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre - {{ $entreprise->nom }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .header .subtitle { margin: 5px 0; color: #666; }
        .filters { margin-bottom: 15px; padding: 10px; background: #f5f5f5; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f0f0f0; text-align: left; padding: 8px; border: 1px solid #ddd; }
        td { padding: 6px; border: 1px solid #ddd; }
        .debit { color: #d00; }
        .credit { color: #090; }
        .total { font-weight: bold; background: #f9f9f9; }
        .footer { margin-top: 30px; text-align: center; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAND LIVRE COMPTABLE</h1>
        <div class="subtitle">{{ $entreprise->nom }} ({{ $entreprise->code }})</div>
        <div>Période: {{ isset($filters['dateDebut']) ? date('d/m/Y', strtotime($filters['dateDebut'])) : date('01/01/Y') }} 
             au {{ isset($filters['dateFin']) ? date('d/m/Y', strtotime($filters['dateFin'])) : date('31/12/Y') }}</div>
        <div>Exercice: {{ $filters['exercice'] ?? date('Y') }}</div>
        <div>Édité le: {{ date('d/m/Y H:i') }}</div>
    </div>
    
    @if(!empty($filters))
    <div class="filters">
        <strong>Filtres appliqués:</strong>
        @if(isset($filters['journalId']))
            | Journal: {{ $filters['journalId'] }}
        @endif
        @if(isset($filters['accountType']) && isset($filters['accountId']))
            | Compte: {{ $filters['accountType'] }} #{{ $filters['accountId'] }}
        @endif
    </div>
    @endif
    
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Journal</th>
                <th>Pièce</th>
                <th>Compte</th>
                <th>Libellé</th>
                <th>Débit</th>
                <th>Crédit</th>
                <th>Solde</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ecritures as $ecriture)
            <tr>
                <td>{{ $ecriture->date_ecriture->format('d/m/Y') }}</td>
                <td>{{ $ecriture->journal?->code }}</td>
                <td>{{ $ecriture->piece }}</td>
                <td>
                    @if($ecriture->oldAccount)
                        {{ $ecriture->oldAccount->code }}
                    @elseif($ecriture->newAccount)
                        {{ $ecriture->newAccount->code }}
                    @endif
                </td>
                <td>{{ $ecriture->libelle }}</td>
                <td class="debit">{{ number_format($ecriture->debit, 2, ',', ' ') }}</td>
                <td class="credit">{{ number_format($ecriture->credit, 2, ',', ' ') }}</td>
                <td>{{ number_format($ecriture->solde, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="total">
            <tr>
                <td colspan="5" style="text-align: right;">TOTAUX :</td>
                <td class="debit">{{ number_format($totals['debit'], 2, ',', ' ') }}</td>
                <td class="credit">{{ number_format($totals['credit'], 2, ',', ' ') }}</td>
                <td>{{ number_format($totals['solde'], 2, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        Page 1/1 • Système de Mapping Comptable • {{ config('app.name') }}
    </div>
</body>
</html>