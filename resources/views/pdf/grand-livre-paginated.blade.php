{{-- resources/views/pdf/grand-livre-paginated.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre Général</title>
    <style>
        @page { margin: 15px; }
        body { font-family: Arial, sans-serif; font-size: 8pt; margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 10px; padding-bottom: 5px; border-bottom: 2px solid #000; }
        h1 { font-size: 12pt; margin: 0; color: #000; }
        .subtitle { font-size: 8pt; color: #666; margin: 2px 0; }
        .filters { background: #f5f5f5; padding: 5px; margin: 5px 0; font-size: 7pt; }
        .account-section { margin: 10px 0; page-break-inside: avoid; }
        .account-header { background: #4a6fa5; color: white; padding: 4px; font-weight: bold; }
        .ecritures-table { width: 100%; border-collapse: collapse; margin: 3px 0; font-size: 7pt; }
        .ecritures-table th { background: #e0e0e0; padding: 3px; border: 1px solid #ccc; }
        .ecritures-table td { padding: 3px; border: 1px solid #ccc; }
        .debit { color: #c00; text-align: right; }
        .credit { color: #009900; text-align: right; }
        .account-total { background: #f0f0f0; font-weight: bold; }
        .summary-section { margin-top: 15px; page-break-before: always; }
        .summary-table { width: 100%; border-collapse: collapse; margin: 5px 0; font-size: 7pt; }
        .global-totals { background: #333; color: white; padding: 8px; margin-top: 10px; border-radius: 3px; }
        .footer { text-align: center; margin-top: 10px; padding-top: 5px; border-top: 1px solid #ddd; font-size: 6pt; color: #777; }
        .page-number:after { content: "Page " counter(page); }
    </style>
</head>
<body>
    <div class="header">
        <h1>GRAND LIVRE GÉNÉRAL</h1>
        <div class="subtitle">{{ $entreprise->nom }} ({{ $entreprise->code }})</div>
        <div class="subtitle">Généré le: {{ $date_generation }}</div>
    </div>
    
    @if($filters['date_debut'])
    <div class="filters">
        <strong>Période:</strong> {{ $filters['date_debut'] }} au {{ $filters['date_fin'] }}
    </div>
    @endif
    
    <!-- Comptes détaillés -->
    @foreach($detailed_accounts as $account)
    <div class="account-section">
        <div class="account-header">
            {{ $account->code }} - {{ Str::limit($account->intitule, 40) }}
            <span style="float: right;">
                {{ $account->total_ecritures }} écritures
                @if($account->has_more_ecritures)
                (affichage limité)
                @endif
            </span>
        </div>
        
        @if($account->ecritures->isNotEmpty())
        <table class="ecritures-table">
            <thead>
                <tr>
                    <th width="10%">Date</th>
                    <th width="8%">Pièce</th>
                    <th width="8%">Journal</th>
                    <th width="12%">Compte Ancien</th>
                    <th width="42%">Libellé</th>
                    <th width="10%">Débit</th>
                    <th width="10%">Crédit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($account->ecritures as $ecriture)
                <tr>
                    <td>{{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '' }}</td>
                    <td>{{ $ecriture->piece }}</td>
                    <td>{{ $ecriture->journal_code }}</td>
                    <td>{{ $ecriture->old_account_code }}</td>
                    <td>{{ Str::limit($ecriture->libelle, 40) }}</td>
                    <td class="debit">
                        @if($ecriture->debit > 0)
                        {{ number_format($ecriture->debit, 0, ',', ' ') }}
                        @endif
                    </td>
                    <td class="credit">
                        @if($ecriture->credit > 0)
                        {{ number_format($ecriture->credit, 0, ',', ' ') }}
                        @endif
                    </td>
                </tr>
                @endforeach
                
                <tr class="account-total">
                    <td colspan="5"><strong>TOTAL {{ $account->code }}</strong></td>
                    <td class="debit"><strong>{{ number_format($account->total_debit, 0, ',', ' ') }}</strong></td>
                    <td class="credit"><strong>{{ number_format($account->total_credit, 0, ',', ' ') }}</strong></td>
                </tr>
                
                @php
                    $solde = $account->total_debit - $account->total_credit;
                @endphp
                <tr class="account-total">
                    <td colspan="5"><strong>SOLDE {{ $account->code }}</strong></td>
                    @if($solde > 0)
                        <td class="debit"><strong>{{ number_format($solde, 0, ',', ' ') }}</strong></td>
                        <td></td>
                    @else
                        <td></td>
                        <td class="credit"><strong>{{ number_format(abs($solde), 0, ',', ' ') }}</strong></td>
                    @endif
                </tr>
            </tbody>
        </table>
        @endif
    </div>
    @endforeach
    
    <!-- Synthèse des comptes restants -->
    @if($has_more_accounts)
    <div class="summary-section">
        <div class="account-header" style="background: #666;">
            SYNTHÈSE DES AUTRES COMPTES ({{ $remaining_accounts->count() }} comptes)
        </div>
        
        <table class="summary-table">
            <thead>
                <tr>
                    <th width="15%">Compte</th>
                    <th width="45%">Intitulé</th>
                    <th width="15%">Débit</th>
                    <th width="15%">Crédit</th>
                    <th width="10%">Solde</th>
                </tr>
            </thead>
            <tbody>
                @foreach($remaining_accounts as $account)
                @php
                    $solde = $account->total_debit - $account->total_credit;
                @endphp
                <tr>
                    <td>{{ $account->new_account_code }}</td>
                    <td>{{ Str::limit($account->new_account_intitule, 40) }}</td>
                    <td class="debit">{{ number_format($account->total_debit, 0, ',', ' ') }}</td>
                    <td class="credit">{{ number_format($account->total_credit, 0, ',', ' ') }}</td>
                    <td class="{{ $solde > 0 ? 'debit' : 'credit' }}">
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
        <div style="text-align: center;">
            <div style="font-size: 9pt; margin-bottom: 3px;">TOTAUX GÉNÉRAUX</div>
            <div style="font-size: 11pt; margin-bottom: 5px;">
                {{ $totals['total_comptes'] }} comptes | 
                {{ number_format($totals['total_ecritures'], 0, ',', ' ') }} écritures
            </div>
            <div style="font-size: 14pt; font-weight: bold; color: {{ $totals['is_debiteur'] ? '#ff9999' : '#99ff99' }};">
                Solde: {{ number_format($totals['solde_global'], 2, ',', ' ') }}
                ({{ $totals['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT' }})
            </div>
        </div>
    </div>
    
    <div class="footer">
        <div class="page-number"></div>
        Document généré par {{ config('app.name') }}
    </div>
</body>
</html>