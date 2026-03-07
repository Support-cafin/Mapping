<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Général - {{ $entreprise->nom }}</title>
    <style>
        /* Reset et base */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            margin: 10px;
            padding: 0;
        }
        
        /* En-tête */
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #2c3e50;
        }
        
        .title {
            font-size: 16px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .subtitle {
            font-size: 10px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }
        
        .entreprise-info {
            font-size: 11px;
            font-weight: bold;
            color: #34495e;
            margin-bottom: 2px;
        }
        
        /* Filtres */
        .filters {
            background-color: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            border-left: 3px solid #3498db;
            font-size: 8px;
        }
        
        /* Section compte */
        .account-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .account-header {
            background-color: #2c3e50;
            color: white;
            padding: 6px 8px;
            font-weight: bold;
            border-radius: 3px 3px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .account-code {
            font-family: 'DejaVu Sans Mono', monospace;
            font-size: 11px;
        }
        
        .account-name {
            font-size: 10px;
            opacity: 0.9;
            margin-left: 10px;
        }
        
        .account-count {
            font-size: 9px;
            background: rgba(255, 255, 255, 0.2);
            padding: 2px 6px;
            border-radius: 10px;
        }
        
        /* Tableau des écritures */
        .ecritures-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            margin-bottom: 5px;
        }
        
        .ecritures-table th {
            background-color: #34495e;
            color: white;
            padding: 5px 4px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #2c3e50;
        }
        
        .ecritures-table td {
            padding: 4px 3px;
            border: 1px solid #ddd;
            vertical-align: top;
        }
        
        .ecritures-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        
        /* Colonnes spécifiques */
        .col-date { width: 8%; }
        .col-piece { width: 7%; }
        .col-journal { width: 7%; }
        .col-ancien-compte { width: 10%; }
        .col-sybcebnl { width: 12%; }
        .col-libelle { width: 38%; }
        .col-debit { width: 9%; }
        .col-credit { width: 9%; }
        
        .numeric {
            text-align: right;
            font-family: 'DejaVu Sans Mono', monospace;
        }
        
        .debit {
            color: #c0392b;
            font-weight: 500;
        }
        
        .credit {
            color: #27ae60;
            font-weight: 500;
        }
        
        /* Totaux du compte */
        .account-total {
            background-color: #ecf0f1 !important;
            font-weight: bold;
            border-top: 2px solid #bdc3c7;
        }
        
        .account-solde {
            background-color: #d6eaf8 !important;
            font-weight: bold;
            border-top: 2px solid #3498db;
        }
        
        /* Totaux globaux */
        .global-totals {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            border-radius: 5px;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        
        .global-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .global-stat {
            text-align: center;
            padding: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .global-stat-debit {
            background-color: #c0392b;
        }
        
        .global-stat-credit {
            background-color: #27ae60;
        }
        
        .global-stat-value {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 2px;
        }
        
        .global-stat-label {
            font-size: 8px;
            opacity: 0.9;
        }
        
        .solde-global {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .solde-value {
            font-size: 18px;
            font-weight: bold;
            color: #f1c40f;
            margin-bottom: 3px;
        }
        
        .solde-label {
            font-size: 10px;
            opacity: 0.8;
        }
        
        /* Pied de page */
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #ddd;
            font-size: 7px;
            color: #7f8c8d;
        }
        
        /* Utilitaires */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        
        /* Pagination */
        .page-number:after {
            content: "Page " counter(page);
        }
        
        /* Pour éviter les coupures */
        tr { page-break-inside: avoid; }
        
        /* Style pour les libellés longs */
        .libelle-cell {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="title">GRAND LIVRE GÉNÉRAL</div>
        
        <div class="entreprise-info">
            {{ $entreprise->nom }}
            <span style="color: #7f8c8d;">({{ $entreprise->code }})</span>
        </div>
        
        <div class="subtitle">
            Généré le {{ now()->format('d/m/Y à H:i') }}
        </div>
        
        <!-- Filtres appliqués -->
        @if(!empty($filters['date_debut']) || !empty($filters['date_fin']))
        <div class="filters">
            <strong>Période :</strong> {{ $filters['date_debut'] }} au {{ $filters['date_fin'] }}
            @if(!empty($filters['journal']))
            | <strong>Journal :</strong> {{ $filters['journal'] }}
            @endif
            @if(!empty($filters['exercice']))
            | <strong>Exercice :</strong> {{ $filters['exercice'] }}
            @endif
        </div>
        @endif
    </div>
    
    <!-- Comptes avec leurs écritures -->
    @foreach($accounts as $account)
    <div class="account-section">
        <!-- En-tête du compte -->
        <div class="account-header">
            <div>
                <span class="account-code">{{ $account->code }}</span>
                <span class="account-name">{{ $account->intitule }}</span>
            </div>
            <span class="account-count">{{ $account->nombre_ecritures }} écritures</span>
        </div>
        
        <!-- Tableau des écritures -->
        <table class="ecritures-table">
            <thead>
                <tr>
                    <th class="col-date">Date</th>
                    <th class="col-piece">Pièce</th>
                    <th class="col-journal">Journal</th>
                    <th class="col-ancien-compte">Compte Ancien</th>
                    <th class="col-sybcebnl">Compte SYCEBNL</th>
                    <th class="col-libelle">Libellé</th>
                    <th class="col-debit">Débit</th>
                    <th class="col-credit">Crédit</th>
                </tr>
            </thead>
            <tbody>
                <!-- Écritures -->
                @foreach($account->ecritures as $ecriture)
                <tr>
                    <td>{{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '' }}</td>
                    <td>{{ $ecriture->piece }}</td>
                    <td>{{ $ecriture->journal_code }}</td>
                    <td>{{ $ecriture->old_account_code }}</td>
                    <td class="bold">{{ $account->code }}</td>
                    <td class="libelle-cell" title="{{ $ecriture->libelle }}">
                        {{ Str::limit($ecriture->libelle, 50) }}
                    </td>
                    <td class="numeric debit">
                        @if($ecriture->debit > 0)
                            {{ number_format($ecriture->debit, 0, ',', ' ') }}
                        @endif
                    </td>
                    <td class="numeric credit">
                        @if($ecriture->credit > 0)
                            {{ number_format($ecriture->credit, 0, ',', ' ') }}
                        @endif
                    </td>
                </tr>
                @endforeach
                
                <!-- Total du compte -->
                <tr class="account-total">
                    <td colspan="5" class="text-right bold">TOTAL {{ $account->code }}</td>
                    <td class="bold">{{ $account->nombre_ecritures }} écritures</td>
                    <td class="numeric debit bold">{{ number_format($account->total_debit, 0, ',', ' ') }}</td>
                    <td class="numeric credit bold">{{ number_format($account->total_credit, 0, ',', ' ') }}</td>
                </tr>
                
                <!-- Solde du compte -->
                <tr class="account-solde">
                    <td colspan="6" class="text-right bold">SOLDE {{ $account->code }}</td>
                    @if($account->solde > 0)
                        <td class="numeric debit bold">{{ number_format($account->solde, 0, ',', ' ') }}</td>
                        <td></td>
                    @else
                        <td></td>
                        <td class="numeric credit bold">{{ number_format(abs($account->solde), 0, ',', ' ') }}</td>
                    @endif
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach
    
    <!-- Totaux globaux -->
    <div class="global-totals">
        <div class="global-stats">
            <div class="global-stat">
                <div class="global-stat-value">{{ $totals['total_comptes'] }}</div>
                <div class="global-stat-label">Comptes SYCEBNL</div>
            </div>
            
            <div class="global-stat">
                <div class="global-stat-value">{{ number_format($totals['total_ecritures'], 0, ',', ' ') }}</div>
                <div class="global-stat-label">Total Écritures</div>
            </div>
            
            <div class="global-stat global-stat-debit">
                <div class="global-stat-value">{{ number_format($totals['total_debit'], 0, ',', ' ') }}</div>
                <div class="global-stat-label">Total Débit</div>
            </div>
            
            <div class="global-stat global-stat-credit">
                <div class="global-stat-value">{{ number_format($totals['total_credit'], 0, ',', ' ') }}</div>
                <div class="global-stat-label">Total Crédit</div>
            </div>
        </div>
        
        <div class="solde-global">
            <div class="solde-label">SOLDE GLOBAL</div>
            <div class="solde-value">
                {{ number_format($totals['solde_global'], 2, ',', ' ') }}
                ({{ $totals['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT' }})
            </div>
        </div>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <div style="display: inline-block;">
            Document généré automatiquement par {{ config('app.name') }}<br>
            <span style="font-size: 6px; color: #bdc3c7;">{{ date('d/m/Y H:i:s') }}</span>
        </div>
        <div class="page-number" style="float: right; font-size: 8px;"></div>
        <div style="clear: both;"></div>
    </div>
    
    <!-- Script pour la numérotation des pages -->
    <script type="text/php">
        if (isset($pdf)) {
            $text = "Page {PAGE_NUM} / {PAGE_COUNT}";
            $size = 8;
            $font = $fontMetrics->get_font("DejaVu Sans");
            $width = $fontMetrics->get_text_width($text, $font, $size) / 2;
            $x = ($pdf->get_width() - $width) / 2;
            $y = $pdf->get_height() - 15;
            $pdf->page_text($x, $y, $text, $font, $size);
        }
    </script>
</body>
</html>