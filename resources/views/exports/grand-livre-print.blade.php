<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Grand Livre — Impression</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            color: #111;
            padding: 20px;
            background: #fff;
        }

        /* ── Bouton impression ── */
        .btn-print {
            position: fixed;
            top: 15px;
            right: 15px;
            padding: 8px 18px;
            background: #555;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
        }
        .btn-print:hover { background: #333; }

        /* ── En-tête ── */
        .header {
            border-bottom: 2px solid #aaa;
            padding-bottom: 8px;
            margin-bottom: 14px;
        }
        .header h1 { font-size: 15px; letter-spacing: 1px; text-transform: uppercase; }
        .header h2 { font-size: 12px; font-weight: normal; color: #444; margin-top: 3px; }
        .header p  { font-size: 9px; color: #666; margin-top: 2px; }

        /* ── Meta barre ── */
        .meta {
            background: #f2f2f2;
            border: 1px solid #ccc;
            padding: 5px 8px;
            margin-bottom: 18px;
            font-size: 9px;
        }

        /* ══════════════════════════════════════
           BLOC PAR COMPTE
        ══════════════════════════════════════ */

        /* Ligne titre du compte */
        .compte-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            background: #f5f5f5;
            border-top: 2px solid #bbb;
            border-bottom: 1px solid #ccc;
            padding: 6px 8px;
            margin-top: 16px;
        }
        .compte-header:first-of-type { margin-top: 0; }

        .compte-header .compte-title {
            font-size: 11px;
            font-weight: bold;
            color: #111;
        }
        .compte-header .compte-title span {
            color: #555;
            font-size: 10px;
            font-weight: normal;
            margin-left: 6px;
        }
        .compte-header .nb-ecritures {
            font-size: 9px;
            color: #777;
            font-style: italic;
        }

        /* Tableau des écritures du compte */
        .compte-table {
            width: 100%;
            border-collapse: collapse;
        }
        .compte-table thead tr th {
            background: #ebebeb;
            color: #333;
            font-weight: bold;
            font-size: 8.5px;
            padding: 4px 5px;
            border-bottom: 1px solid #ccc;
            text-align: left;
            white-space: nowrap;
        }
        .compte-table tbody tr td {
            padding: 3px 5px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 9.5px;
            color: #222;
        }
        .compte-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        /* Ligne TOTAL du compte */
        .compte-total {
            background: #ebebeb;
            border-top: 1px solid #bbb;
        }
        .compte-total td {
            padding: 4px 5px;
            font-size: 9.5px;
            font-weight: bold;
            color: #111;
        }

        /* Ligne SOLDE */
        .compte-solde {
            background: #fff;
        }
        .compte-solde td {
            padding: 5px 5px 8px;
            font-size: 9.5px;
            color: #2255aa;
            font-weight: bold;
            text-align: right;
            border-bottom: 2px solid #ddd;
        }
        .compte-solde .solde-label {
            color: #2255aa;
            letter-spacing: 0.5px;
        }
        .compte-solde .solde-underline {
            display: inline-block;
            border-bottom: 2px solid #e05555;
            padding-bottom: 1px;
        }

        /* ── Utilitaires ── */
        .text-right  { text-align: right !important; }
        .text-center { text-align: center !important; }
        .text-bold   { font-weight: bold; }

        /* ── Totaux généraux ── */
        .totaux-generaux {
            margin-top: 20px;
            border-top: 2px solid #888;
        }
        .totaux-generaux table {
            width: 100%;
            border-collapse: collapse;
        }
        .totaux-generaux table td {
            padding: 5px 5px;
            font-size: 10px;
            font-weight: bold;
            background: #e0e0e0;
        }

        /* ── Pied de page ── */
        .footer {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }

        /* ── Print ── */
        @media print {
            .btn-print { display: none; }
            body { padding: 0; }
            .compte-header,
            .compte-table thead tr th,
            .compte-total,
            .totaux-generaux table td {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .compte-header { page-break-inside: avoid; }
            .compte-table  { page-break-inside: auto; }
            .compte-table tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body>

    <button class="btn-print" onclick="window.print()">&#128424; Imprimer</button>

    {{-- ── En-tête ── --}}
    <div class="header">
        <h1>Grand Livre Comptable</h1>
        <h2>{{ $entreprise->nom ?? '' }}{{ $entreprise->code ? ' (' . $entreprise->code . ')' : '' }}</h2>
        <p>
            Période :
            {{ !empty($filters['dateDebut']) ? \Carbon\Carbon::parse($filters['dateDebut'])->format('d/m/Y') : '—' }}
            au
            {{ !empty($filters['dateFin'])   ? \Carbon\Carbon::parse($filters['dateFin'])->format('d/m/Y')   : '—' }}
        </p>
    </div>

    {{-- ── Meta ── --}}
    <div class="meta">
        <strong>Impression :</strong> {{ $dateGeneration }} &nbsp;&nbsp;
        <strong>Comptes :</strong> {{ count($comptes) }} &nbsp;&nbsp;
        <strong>Total écritures :</strong> {{ collect($comptes)->sum(fn($c) => $c['ecritures']->count()) }}
    </div>

    {{-- ══════════════════════════════════════
         BOUCLE PAR COMPTE
    ══════════════════════════════════════ --}}
    @foreach($comptes as $compte)
        @php
            $nbEcritures = $compte['ecritures']->count();
        @endphp

        {{-- ── Titre du bloc : code SYCEBNL (new_account) ── --}}
        <div class="compte-header">
            <div class="compte-title">
                {{ $compte['code'] }}          {{-- code SYCEBNL --}}
                <span>{{ $compte['intitule'] }}</span>  {{-- intitulé SYCEBNL --}}
            </div>
            <div class="nb-ecritures">{{ $nbEcritures }} écriture{{ $nbEcritures > 1 ? 's' : '' }}</div>
        </div>

        {{-- ── Tableau des écritures ── --}}
        <table class="compte-table">
            <thead>
                <tr>
                    <th width="9%">Date</th>
                    <th width="10%">N° Pièce</th>
                    <th width="7%">Journal</th>
                    <th width="10%" class="text-right">Compte entité</th>  {{-- old_account --}}
                    <th width="9%" class="text-right">Compte Sycebnl</th>        {{-- new_account (répété pour rappel) --}}
                    <th>Libellé</th>
                    <th width="11%" class="text-right">Débit</th>
                    <th width="11%" class="text-right">Crédit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($compte['ecritures'] as $e)
                    <tr>
                        <td>{{ $e->date_ecriture instanceof \Carbon\Carbon ? $e->date_ecriture->format('d/m/Y') : \Carbon\Carbon::parse($e->date_ecriture)->format('d/m/Y') }}</td>
                        <td>{{ $e->piece ?? '-' }}</td>
                        <td>{{ $e->journal_code ?? '-' }}</td>
                        {{-- Compte entité (old_account) --}}
                        <td class="text-right">{{ $e->oldAccount ? $e->oldAccount->code : '-' }}</td>
                        {{-- Code SYCEBNL (new_account) = même pour tout le bloc --}}
                        <td class="text-right">{{ $compte['code'] }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($e->libelle, 50) }}</td>
                        <td class="text-right">
                            {{ $e->debit  > 0 ? number_format($e->debit,  0, ',', ' ') : '' }}
                        </td>
                        <td class="text-right">
                            {{ $e->credit > 0 ? number_format($e->credit, 0, ',', ' ') : '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>

            {{-- Total du compte SYCEBNL --}}
            <tr class="compte-total">
                <td colspan="5" class="text-right text-bold">
                    TOTAL {{ $compte['code'] }} &nbsp; {{ $nbEcritures }} écriture{{ $nbEcritures > 1 ? 's' : '' }}
                </td>
                <td></td>
                <td class="text-right">{{ number_format($compte['total_debit'],  0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($compte['total_credit'], 0, ',', ' ') }}</td>
            </tr>
        </table>

        {{-- Ligne SOLDE --}}
        <table class="compte-table">
            <tr class="compte-solde">
                <td colspan="6" class="text-right solde-label">
                    SOLDE {{ $compte['code'] }}
                </td>
                <td width="11%" class="text-right">
                    @if($compte['solde'] >= 0)
                        <span class="solde">{{ number_format($compte['solde_absolu'], 0, ',', ' ') }}</span>
                    @endif
                </td>
                <td width="11%" class="text-right">
                    @if($compte['solde'] < 0)
                        <span class="solde">{{ number_format($compte['solde_absolu'], 0, ',', ' ') }}</span>
                    @endif
                </td>
            </tr>
        </table>

    @endforeach

    {{-- ── Totaux généraux ── --}}
    @if(!empty($comptes))
    <div class="totaux-generaux">
        <table>
            <tr>
                <td colspan="6" class="text-right">TOTAL GÉNÉRAL</td>
                <td width="11%" class="text-right">{{ number_format($total_general_debit,  0, ',', ' ') }}</td>
                <td width="11%" class="text-right">{{ number_format($total_general_credit, 0, ',', ' ') }}</td>
            </tr>
        </table>
    </div>
    @endif

    <div class="footer">Document imprimé le {{ $dateGeneration }}</div>

</body>
</html>
