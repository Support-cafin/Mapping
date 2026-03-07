<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Balance des comptes</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'Arial', sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 18px;
            margin: 0;
            color: #2C5282;
        }
        .header h2 {
            font-size: 14px;
            margin: 5px 0;
            color: #4A5568;
        }
        .header p {
            font-size: 11px;
            margin: 2px 0;
            color: #718096;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th {
            background-color: #2C5282;
            color: white;
            font-weight: bold;
            padding: 8px 5px;
            text-align: center;
            border: 1px solid #1A365D;
            font-size: 10px;
        }
        td {
            padding: 5px;
            border: 1px solid #E2E8F0;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .font-bold {
            font-weight: bold;
        }
        .bg-gray {
            background-color: #F7FAFC;
        }
        .text-red {
            color: #C53030;
        }
        .text-green {
            color: #276749;
        }
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 9px;
            color: #A0AEC0;
        }
        .totaux {
            background-color: #EDF2F7;
            font-weight: bold;
        }
        .equilibre {
            background-color: #F0FFF4;
            font-weight: bold;
            text-align: center;
            padding: 8px;
        }
        .equilibre.error {
            background-color: #FFF5F5;
            color: #C53030;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>BALANCE DES COMPTES</h1>
        <h2>{{ $entreprise->nom ?? $entreprise->name ?? 'Entreprise' }}</h2>
        <p>Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</p>
        <p>Type : {{ $balanceType === '4colonnes' ? 'Balance à 4 colonnes' : 'Balance à 6 colonnes' }}</p>
        <p>Date d'édition : {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    @if($balanceType === '4colonnes')
        <!-- BALANCE À 4 COLONNES -->
        <table>
            <thead>
                <tr>
                    <th width="10%">Code SYCEBNL</th>
                    <th width="38%">Libellé du compte</th>
                    <th width="15%">Mouvement Débit</th>
                    <th width="15%">Mouvement Crédit</th>
                    <th width="11%">Solde Débiteur</th>
                    <th width="11%">Solde Créditeur</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalMouvDebit = 0;
                    $totalMouvCredit = 0;
                    $totalSoldeDeb = 0;
                    $totalSoldeCred = 0;
                @endphp

                @forelse($balances as $balance)
                    @php
                        $totalMouvDebit += $balance['total_debit'];
                        $totalMouvCredit += $balance['total_credit'];
                        $soldeDeb = $balance['solde'] > 0 ? $balance['solde'] : 0;
                        $soldeCred = $balance['solde'] < 0 ? abs($balance['solde']) : 0;
                        $totalSoldeDeb += $soldeDeb;
                        $totalSoldeCred += $soldeCred;
                    @endphp
                    <tr>
                        <td class="text-center font-medium">{{ $balance['code'] }}</td>
                        <td>{{ $balance['intitule'] }}</td>
                        <td class="text-right">{{ $balance['total_debit'] > 0 ? number_format($balance['total_debit'], 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $balance['total_credit'] > 0 ? number_format($balance['total_credit'], 0, ',', ' ') : '-' }}</td>
                        <td class="text-right {{ $soldeDeb > 0 ? 'font-medium' : '' }}">{{ $soldeDeb > 0 ? number_format($soldeDeb, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right {{ $soldeCred > 0 ? 'font-medium' : '' }}">{{ $soldeCred > 0 ? number_format($soldeCred, 0, ',', ' ') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Aucun compte trouvé</td>
                    </tr>
                @endforelse

                @if(count($balances) > 0)
                    <tr class="totaux">
                        <td colspan="2" class="text-right">TOTAUX</td>
                        <td class="text-right">{{ number_format($totalMouvDebit, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalMouvCredit, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalSoldeDeb, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalSoldeCred, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td colspan="6" class="equilibre {{ abs($totalSoldeDeb - $totalSoldeCred) > 0.01 ? 'error' : '' }}">
                            ÉQUILIBRE : {{ number_format($totalSoldeDeb, 0, ',', ' ') }} = {{ number_format($totalSoldeCred, 0, ',', ' ') }}
                            @if(abs($totalSoldeDeb - $totalSoldeCred) <= 0.01)
                                ✓ Balance équilibrée
                            @else
                                ✗ Déséquilibre de {{ number_format(abs($totalSoldeDeb - $totalSoldeCred), 0, ',', ' ') }}
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @else
        <!-- BALANCE À 6 COLONNES -->
        <table>
            <thead>
                <tr>
                    <th width="10%">Code</th>
                    <th width="30%">Libellé</th>
                    <th colspan="2" width="15%">Solde ouverture</th>
                    <th colspan="2" width="15%">Mouvement</th>
                    <th colspan="2" width="15%">Solde clôture</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th width="7%">Débit</th>
                    <th width="7%">Crédit</th>
                    <th width="7%">Débit</th>
                    <th width="7%">Crédit</th>
                    <th width="7%">Débiteur</th>
                    <th width="7%">Créditeur</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalOuvertureDeb = 0;
                    $totalOuvertureCred = 0;
                    $totalMouvDebit = 0;
                    $totalMouvCredit = 0;
                    $totalClotureDeb = 0;
                    $totalClotureCred = 0;
                @endphp

                @forelse($balances as $balance)
                    @php
                        // Simulation pour 6 colonnes - à adapter selon votre structure
                        $ouvertureDeb = $balance['total_debit'] * 0.2 ?? 0;
                        $ouvertureCred = $balance['total_credit'] * 0.2 ?? 0;
                        $mouvDebit = $balance['total_debit'] - $ouvertureDeb;
                        $mouvCredit = $balance['total_credit'] - $ouvertureCred;
                        $soldeCloture = ($ouvertureDeb + $mouvDebit) - ($ouvertureCred + $mouvCredit);
                        
                        $totalOuvertureDeb += $ouvertureDeb;
                        $totalOuvertureCred += $ouvertureCred;
                        $totalMouvDebit += $mouvDebit;
                        $totalMouvCredit += $mouvCredit;
                        $totalClotureDeb += $soldeCloture > 0 ? $soldeCloture : 0;
                        $totalClotureCred += $soldeCloture < 0 ? abs($soldeCloture) : 0;
                    @endphp
                    <tr>
                        <td class="text-center font-bold">{{ $balance['code'] }}</td>
                        <td>{{ $balance['intitule'] }}</td>
                        <td class="text-right">{{ $ouvertureDeb > 0 ? number_format($ouvertureDeb, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $ouvertureCred > 0 ? number_format($ouvertureCred, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $mouvDebit > 0 ? number_format($mouvDebit, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $mouvCredit > 0 ? number_format($mouvCredit, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $soldeCloture > 0 ? number_format($soldeCloture, 0, ',', ' ') : '-' }}</td>
                        <td class="text-right">{{ $soldeCloture < 0 ? number_format(abs($soldeCloture), 0, ',', ' ') : '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center">Aucun compte trouvé</td>
                    </tr>
                @endforelse

                @if(count($balances) > 0) 
                    <tr class="totaux">
                        <td colspan="2" class="text-right">TOTAUX</td>
                        <td class="text-right">{{ number_format($totalOuvertureDeb, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalOuvertureCred, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalMouvDebit, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalMouvCredit, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalClotureDeb, 0, ',', ' ') }}</td>
                        <td class="text-right">{{ number_format($totalClotureCred, 0, ',', ' ') }}</td>
                    </tr>
                    <tr>
                        <td colspan="8" class="equilibre {{ abs(($totalOuvertureDeb + $totalMouvDebit) - ($totalOuvertureCred + $totalMouvCredit)) > 0.01 ? 'error' : '' }}">
                            ÉQUILIBRE (A+C) = (B+D) : 
                            {{ number_format($totalOuvertureDeb + $totalMouvDebit, 0, ',', ' ') }} = 
                            {{ number_format($totalOuvertureCred + $totalMouvCredit, 0, ',', ' ') }}
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    @endif

    <div class="footer">
        Document généré le {{ now()->format('d/m/Y à H:i:s') }}
    </div>
</body>
</html>