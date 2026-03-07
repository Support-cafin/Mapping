<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre - {{ $entreprise->nom }}</title>
    <style>
        /* STYLE ULTRA COMPACT - NIVEAUX DE GRIS */
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 6px; 
            margin: 0;
            padding: 2px;
            line-height: 1.1;
            color: #333;
        }
        
        /* En-tête - Minimal */
        .header { 
            text-align: center; 
            margin-bottom: 4px; 
            padding-bottom: 2px;
            border-bottom: 1px solid #ccc;
        }
        
        .title { 
            font-size: 9px; 
            font-weight: bold; 
            margin-bottom: 1px;
            color: #000;
        }
        
        .entreprise { 
            font-size: 7px; 
            font-weight: bold; 
            margin-bottom: 1px;
            color: #333;
        }
        
        .subtitle {
            font-size: 5px;
            color: #666;
        }
        
        /* Compte - Ultra compact */
        .account-section {
            margin-bottom: 5px;
            page-break-inside: avoid;
        }
        
        .account-header {
            background-color: #ddd;
            padding: 2px 3px;
            margin-bottom: 1px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #bbb;
        }
        
        .account-title {
            font-weight: bold;
            font-size: 7px;
            color: #000;
        }
        
        .account-count {
            font-size: 5px;
            background: #f0f0f0;
            padding: 0px 3px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
        
        /* Tableau - TRÈS COMPACT */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 5.5px;
            margin-bottom: 2px;
        }
        
        th {
            background-color: #e0e0e0;
            border: 1px solid #aaa;
            padding: 1px 1px;
            text-align: left;
            font-weight: bold;
            color: #000;
            white-space: nowrap;
        }
        
        td {
            border: 1px solid #ccc;
            padding: 0px 1px;
            vertical-align: top;
            white-space: nowrap;
        }
        
        /* COLONNES - Ultra réduites pour le portrait */
        .col-date { width: 32px; }      /* JJ/MM/AA */
        .col-piece { width: 25px; }      /* 6 caractères */
        .col-journal { width: 20px; }     /* 3 caractères */
        .col-ancien { width: 32px; }      /* 6-7 caractères */
        .col-compte { width: 30px; }      /* Code compte */
        .col-libelle { width: 100px; }     /* Réduit au maximum */
        .col-debit { width: 30px; }        /* 8 chiffres max */
        .col-credit { width: 30px; }       /* 8 chiffres max */
        
        .libelle-cell {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }
        
        /* Alignement */
        .text-right { text-align: right; padding-right: 2px; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }
        
        /* Totaux */
        .account-total {
            background-color: #e8e8e8 !important;
            font-weight: bold;
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
        }
        
        .account-solde {
            background-color: #f0f0f0 !important;
            font-weight: bold;
        }
        
        /* Pied de page - Minimal */
        .footer {
            margin-top: 5px;
            padding-top: 2px;
            border-top: 1px solid #ccc;
            font-size: 4px;
            color: #666;
            text-align: center;
        }
        
        /* Page break */
        .page-break {
            page-break-before: always;
        }
        
        /* Lignes alternées */
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        /* Montants */
        .montant-debit, .montant-credit {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="title">GRAND LIVRE</div>
        <div class="entreprise">{{ $entreprise->nom }}</div>
        <div class="subtitle">
            {{ now()->format('d/m/y H:i') }}
            @if(!empty($filters['date_debut']))
            | {{ $filters['date_debut'] }} au {{ $filters['date_fin'] }}
            @endif
        </div>
    </div>
    
    <!-- Comptes avec écritures -->
    @foreach($accounts as $account)
    <div class="account-section">
        <!-- En-tête du compte -->
        <div class="account-header">
            <div class="account-title">
                {{ $account->code }} - {{ Str::limit($account->intitule, 25) }}
            </div>
            <div class="account-count">
                {{ $account->ecritures_count }} écrit.
            </div>
        </div>
        
        <!-- Tableau des écritures -->
        <table>
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-piece">Pièce</th>
                    <th class="col-journal">Journal</th>
                    <th class="col-ancien">Compte</th>
                    <th class="col-compte">Compte SYCEBNL</th>
                    <th class="col-libelle">Libellé</th>
                    <th class="col-debit text-right">Débit</th>
                    <th class="col-credit text-right">Crédit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($account->ecritures as $ecriture)
                <tr>
                    <td>{{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/y') : '' }}</td>
                    <td>{{ Str::limit($ecriture->piece, 5) }}</td>
                    <td>{{ Str::limit($ecriture->journal_code, 3) }}</td>
                    <td>{{ Str::limit($ecriture->old_account_code, 6) }}</td>
                    <td class="font-bold">{{ $account->code }}</td>
                    <td class="libelle-cell" title="{{ $ecriture->libelle }}">
                        {{ Str::limit($ecriture->libelle, 20) }}
                    </td>
                    <td class="text-right montant-debit">
                        @if($ecriture->debit > 0)
                            {{ number_format($ecriture->debit, 0, ',', ' ') }}
                        @endif
                    </td>
                    <td class="text-right montant-credit">
                        @if($ecriture->credit > 0)
                            {{ number_format($ecriture->credit, 0, ',', ' ') }}
                        @endif
                    </td>
                </tr>
                @endforeach
                
                <!-- Total du compte -->
                <tr class="account-total">
                    <td colspan="5" class="text-right font-bold">TOTAL</td>
                    <td class="font-bold">{{ $account->ecritures_count }}</td>
                    <td class="text-right font-bold">{{ number_format($account->total_debit, 0, ',', ' ') }}</td>
                    <td class="text-right font-bold">{{ number_format($account->total_credit, 0, ',', ' ') }}</td>
                </tr>
                
                <!-- Solde du compte -->
                <tr class="account-solde">
                    <td colspan="6" class="text-right font-bold">SOLDE</td>
                    @if($account->solde > 0)
                        <td class="text-right font-bold">
                            {{ number_format($account->solde, 0, ',', ' ') }}
                        </td>
                        <td></td>
                    @elseif($account->solde < 0)
                        <td></td>
                        <td class="text-right font-bold">
                            {{ number_format(abs($account->solde), 0, ',', ' ') }}
                        </td>
                    @else
                        <td colspan="2" class="text-center font-bold">0</td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Saut de page après 6 comptes (plus de comptes par page) -->
    @if($loop->iteration % 6 == 0 && !$loop->last)
    <div class="page-break"></div>
    @endif
    @endforeach
    
    <!-- Pied de page -->
    <div class="footer">
        {{ config('app.name') }} | {{ now()->format('d/m/Y') }} | Page {PAGE_NUM}/{PAGE_COUNT}
    </div>
    
    <!-- Script pour la numérotation des pages -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "{PAGE_NUM}/{PAGE_COUNT}";
            $size = 4;
            $font = $fontMetrics->get_font("helvetica");
            $width = $fontMetrics->get_text_width($text, $font, $size);
            $x = $pdf->get_width() - $width - 5;
            $y = $pdf->get_height() - 5;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>