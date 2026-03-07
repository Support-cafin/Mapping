{{-- resources/views/pdf/grand-livre-general.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Général - {{ $entreprise->nom }}</title>
    <style>
        /* Styles optimisés */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 9pt; line-height: 1.3; color: #333; }
        
        /* En-tête */
        .header { margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #3b82f6; }
        .header-title { font-size: 16pt; font-weight: bold; color: #1e40af; margin-bottom: 5px; text-align: center; }
        .entreprise-info { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 9pt; }
        .entreprise-name { font-weight: bold; color: #374151; }
        .entreprise-code { color: #6b7280; }
        
        /* Filtres */
        .filters { background-color: #f3f4f6; padding: 8px; border-radius: 4px; margin-bottom: 15px; font-size: 8pt; }
        .filter-item { display: inline-block; margin-right: 15px; }
        .filter-label { font-weight: 600; color: #4b5563; }
        
        /* Compte avec détails */
        .account-with-details { margin-bottom: 20px; page-break-inside: avoid; }
        .account-header { background-color: #1e40af; color: white; padding: 6px; font-weight: bold; border-radius: 3px; margin-bottom: 5px; }
        .account-code { font-size: 10pt; }
        
        /* Compte sans détails (résumé) */
        .account-summary { margin-bottom: 10px; page-break-inside: avoid; }
        .summary-header { background-color: #6b7280; color: white; padding: 4px; font-size: 9pt; }
        
        /* Tableaux */
        .table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 8pt; }
        .table th { background-color: #4f46e5; color: white; padding: 6px 4px; text-align: left; border: 1px solid #3730a3; }
        .table td { padding: 4px; border: 1px solid #d1d5db; vertical-align: top; }
        .table tr:nth-child(even) { background-color: #f9fafb; }
        
        /* Montants */
        .amount { text-align: right; font-family: 'DejaVu Sans Mono', monospace; white-space: nowrap; }
        .debit { color: #dc2626; font-weight: 600; }
        .credit { color: #059669; font-weight: 600; }
        
        /* Totaux */
        .account-total { background-color: #fef3c7; font-weight: bold; border-top: 2px solid #f59e0b; }
        .account-solde { background-color: #dbeafe; font-weight: bold; border-top: 2px solid #3b82f6; }
        
        /* Totaux globaux */
        .global-totals { margin-top: 20px; padding: 10px; background-color: #1f2937; color: white; border-radius: 5px; }
        .global-totals-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px; }
        .global-stat { text-align: center; padding: 8px; border-radius: 4px; }
        .stat-debit { background-color: #991b1b; }
        .stat-credit { background-color: #065f46; }
        .stat-solde-debit { background-color: #7c2d12; }
        .stat-solde-credit { background-color: #1e3a8a; }
        .stat-label { font-size: 8pt; opacity: 0.8; margin-bottom: 3px; }
        .stat-value { font-size: 12pt; font-weight: bold; }
        
        /* Avertissement */
        .warning { background-color: #fef3c7; border: 1px solid #f59e0b; padding: 8px; border-radius: 4px; margin: 10px 0; font-size: 8pt; }
        
        /* Pied de page */
        .footer { position: fixed; bottom: 0; left: 0; right: 0; text-align: center; font-size: 8pt; color: #6b7280; padding: 5px; border-top: 1px solid #e5e7eb; background-color: white; }
        .page-number:before { content: "Page " counter(page); }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="header-title">GRAND LIVRE GÉNÉRAL</div>
        <div class="entreprise-info">
            <div>
                <span class="entreprise-name">{{ $entreprise->nom }}</span>
                <span class="entreprise-code">({{ $entreprise->code }})</span>
            </div>
            <div>Généré le: {{ now()->format('d/m/Y H:i') }}</div>
        </div>
        
        <!-- Filtres appliqués -->
        @if($filters['date_debut'] || $filters['journal'] || $filters['exercice'])
        <div class="filters">
            <strong>Filtres appliqués:</strong>
            @if($filters['date_debut'])
                <span class="filter-item">
                    <span class="filter-label">Période:</span>
                    {{ $filters['date_debut'] }} au {{ $filters['date_fin'] }}
                </span>
            @endif
            @if($filters['journal'])
                <span class="filter-item">
                    <span class="filter-label">Journal:</span>
                    {{ $filters['journal'] }}
                </span>
            @endif
            @if($filters['exercice'])
                <span class="filter-item">
                    <span class="filter-label">Exercice:</span>
                    {{ $filters['exercice'] }}
                </span>
            @endif
        </div>
        @endif
        
        <!-- Avertissement si certains comptes n'ont pas de détails -->
        @if($has_detailed_view && count($summary_accounts) > 0)
        <div class="warning">
            <strong>Note:</strong> Pour des raisons de performance, seuls les {{ count($accounts) - count($summary_accounts) }} premiers comptes sont affichés avec le détail des écritures. 
            Les {{ count($summary_accounts) }} autres comptes sont résumés dans le tableau de synthèse en fin de document.
            Les totaux globaux incluent tous les comptes.
        </div>
        @endif
    </div>
    
    <!-- Comptes avec détails -->
    @foreach($accounts as $account)
        @if($account['has_details'] && $account['ecritures']->isNotEmpty())
        <div class="account-with-details">
            <!-- En-tête du compte -->
            <div class="account-header">
                <span class="account-code">{{ $account['code'] }}</span> - 
                {{ $account['intitule'] }}
                <span style="float: right;">{{ $account['nombre_ecritures'] }} écritures</span>
            </div>
            
            <!-- Tableau des écritures -->
            <table class="table">
                <thead>
                    <tr>
                        <th width="8%">Date</th>
                        <th width="7%">Pièce</th>
                        <th width="8%">Journal</th>
                        <th width="10%">Compte Ancien</th>
                        <th width="12%">Compte SYCEBNL</th>
                        <th width="30%">Libellé</th>
                        <th width="12%" class="text-right">Débit</th>
                        <th width="13%" class="text-right">Crédit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($account['ecritures'] as $ecriture)
                    <tr>
                        <td>{{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '' }}</td>
                        <td>{{ $ecriture->piece ?? '' }}</td>
                        <td>{{ $ecriture->journal_code ?? '' }}</td>
                        <td>{{ $ecriture->old_account_code ?? '' }}</td>
                        <td class="bold">{{ $account['code'] }}</td>
                        <td>{{ Str::limit($ecriture->libelle ?? '', 50) }}</td>
                        <td class="amount debit">
                            @if($ecriture->debit > 0)
                                {{ number_format($ecriture->debit, 0, ',', ' ') }}
                            @endif
                        </td>
                        <td class="amount credit">
                            @if($ecriture->credit > 0)
                                {{ number_format($ecriture->credit, 0, ',', ' ') }}
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    
                    <!-- Total du compte -->
                    <tr class="account-total">
                        <td colspan="5" class="bold text-right">TOTAL {{ $account['code'] }}</td>
                        <td class="bold">{{ $account['nombre_ecritures'] }} écritures</td>
                        <td class="amount debit bold">{{ number_format($account['total_debit'], 0, ',', ' ') }}</td>
                        <td class="amount credit bold">{{ number_format($account['total_credit'], 0, ',', ' ') }}</td>
                    </tr>
                    
                    <!-- Solde du compte -->
                    <tr class="account-solde">
                        <td colspan="6" class="bold text-right">SOLDE {{ $account['code'] }}</td>
                        @if($account['is_debiteur'])
                            <td class="amount debit bold">{{ number_format($account['solde'], 0, ',', ' ') }}</td>
                            <td></td>
                        @else
                            <td></td>
                            <td class="amount credit bold">{{ number_format($account['solde'], 0, ',', ' ') }}</td>
                        @endif
                    </tr>
                </tbody>
            </table>
        </div>
        @elseif(!$account['has_details'])
        <!-- Compte résumé (sans détails) -->
        <div class="account-summary">
            <div class="summary-header">
                {{ $account['code'] }} - {{ Str::limit($account['intitule'], 40) }}
                <span style="float: right;">
                    {{ number_format($account['total_debit'], 0, ',', ' ') }} / 
                    {{ number_format($account['total_credit'], 0, ',', ' ') }} 
                    ({{ $account['is_debiteur'] ? 'Débit' : 'Crédit' }}: {{ number_format($account['solde'], 0, ',', ' ') }})
                </span>
            </div>
        </div>
        @endif
    @endforeach
    
    <!-- Tableau récapitulatif pour les comptes sans détails -->
    @if(count($summary_accounts) > 0)
    <div style="page-break-before: always; margin-top: 20px;">
        <div class="account-header" style="background-color: #6b7280;">
            SYNTHÈSE DES COMPTES SANS DÉTAILS ({{ count($summary_accounts) }} comptes)
        </div>
        
        <table class="table">
            <thead>
                <tr>
                    <th width="15%">Compte SYCEBNL</th>
                    <th width="35%">Intitulé</th>
                    <th width="10%">Écritures</th>
                    <th width="15%" class="text-right">Total Débit</th>
                    <th width="15%" class="text-right">Total Crédit</th>
                    <th width="10%" class="text-right">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($summary_accounts as $account)
                @php
                    $solde = $account->total_debit - $account->total_credit;
                @endphp
                <tr>
                    <td>{{ $account->new_account_code }}</td>
                    <td>{{ Str::limit($account->new_account_intitule, 50) }}</td>
                    <td>{{ $account->nombre_ecritures }}</td>
                    <td class="amount debit">{{ number_format($account->total_debit, 0, ',', ' ') }}</td>
                    <td class="amount credit">{{ number_format($account->total_credit, 0, ',', ' ') }}</td>
                    <td class="amount {{ $solde > 0 ? 'debit' : 'credit' }}">
                        {{ number_format(abs($solde), 0, ',', ' ') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Totaux globaux -->
    <div class="global-totals">
        <div class="global-totals-grid">
            <div class="global-stat">
                <div class="stat-label">Comptes SYCEBNL</div>
                <div class="stat-value">{{ $totals['total_comptes'] }}</div>
            </div>
            
            <div class="global-stat">
                <div class="stat-label">Total Écritures</div>
                <div class="stat-value">{{ number_format($totals['total_ecritures'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="global-stat stat-debit">
                <div class="stat-label">Total Débit</div>
                <div class="stat-value">{{ number_format($totals['total_debit'], 0, ',', ' ') }}</div>
            </div>
            
            <div class="global-stat stat-credit">
                <div class="stat-label">Total Crédit</div>
                <div class="stat-value">{{ number_format($totals['total_credit'], 0, ',', ' ') }}</div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.2);">
            <div style="font-size: 10pt; margin-bottom: 5px;">SOLDE GLOBAL</div>
            <div style="font-size: 16pt; font-weight: bold; color: {{ $totals['is_debiteur'] ? '#fbbf24' : '#60a5fa' }};">
                {{ number_format($totals['solde_global'], 0, ',', ' ') }}
                ({{ $totals['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT' }})
            </div>
        </div>
    </div>
    
    <!-- Pied de page -->
    <div class="footer">
        <div class="page-number"></div>
        <div style="margin-top: 2px;">
            Document généré automatiquement - {{ config('app.name') }}
        </div>
    </div>
</body>
</html>