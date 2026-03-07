<div>
    <!-- Styles CSS -->
    <style>
        .resultat-container {
            font-family: Arial, sans-serif;
            font-size: 10px;
            width: 80%;
            margin: 0 auto;
            background-color: white;
        }
        
        .header-info {
            background-color: white;
            padding: 15px;
            border: 1px solid #ccc;
            margin-bottom: 10px;
            text-align: center;
            color: black;
        }
        
        .entreprise-info {
            background-color: green;
            font-weight: bold;
            font-size: 12px;
            margin-bottom: 5px;
            text-transform: uppercase;
            color: white;
        }
        
        .entreprise-details {
            display: flex;
            justify-content: space-between; /* pousse left à gauche et right à droite */
            align-items: flex-start; /* aligne en haut */
            width: 100%;
            font-size: 13px;
            line-height: 1.4;
        }
        
        .resultat-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            font-size: 9px;
        }
        
        .resultat-table th {
            background-color: #f2f2f2;
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }
        
        .resultat-table td {
            border: 1px solid #000;
            padding: 2px 3px;
            vertical-align: top;
        }
        
        .ref-col {
            width: 35px;
            text-align: center;
            font-weight: bold;
        }
        
        .libelle-col {
            text-align: left;
            width: 400px;
            padding-left: 5px !important;
        }
        
        .note-col {
            width: 35px;
            text-align: center;
        }
        
        .montant-col {
            width: 120px;
            text-align: right;
            font-family: 'Courier New', monospace;
            padding-right: 8px !important;
           
        }
        
        .controls-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* COULEURS EXACTES */
        .couleur-produit {
            background-color: white !important; /* Vert clair */
        }
        
        .couleur-charge {
            background-color: white !important; /* Rouge clair */
        }
        
        .couleur-total {
            background-color: green !important; /* Jaune clair */
            color : white;
        }
        
        .couleur-resultat {
            background-color: lightgreen !important; /* Bleu clair */
        }
       
        .couleur-resultat-net {
            background-color: darkblue !important; /* Orange clair */
            color : white;
        }
        
        .couleur-head {
            background-color: lightblue !important; /* Orange clair */
        }
        
        .text-produit {
            color: black !important;
        }
        
        .text-charge {
            color: black !important;
        }
        
        .text-total {
            color: white !important;
            font-weight: bold;
        }
        
        .pull-left {
            text-align: left;
        }
        
        .pull-right {
            text-align: right;
        }
        
        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
    
    <!-- Contrôles d'action -->
    <div class="no-print controls-container">
        <div class="row">
            <div class="col-md-6">
                <h5 class="mb-3">
                    <i class="fas fa-chart-line me-2"></i> Compte de Résultat
                </h5>
                <div class="row g-2">
                    <p class="mb-0"><strong>Désignation entité :</strong> {{ $entreprise->nom }}</p>
                <p class="mb-0"><strong>Exercice :</strong> {{ $exercice }}</p>
                <p class="mb-0"><strong>Période :</strong> 01/01/{{ $exercice }} au 31/12/{{ $exercice }}</p>
                </div>
            </div>
            <!--<div class="col-md-6 d-flex align-items-end justify-content-end">
                <div>
                    <button wire:click="refreshResultat" class="btn btn-primary btn-sm me-2">
                        <i class="fas fa-sync-alt me-1"></i> Recalculer
                    </button>
                    <button onclick="window.print()" class="btn btn-success btn-sm">
                        <i class="fas fa-print me-1"></i> Imprimer
                    </button>
                </div>
            </div>-->
        </div>
    </div>
    <!--<button wire:click="corrigerMappingTG" class="btn btn-warning btn-sm">
    <i class="fas fa-fix"></i> Corriger mapping TG (631800)
</button>-->
    <!-- Compte de Résultat -->
    <div class="resultat-container">
        <!-- En-tête -->
        <div class="header-info">
            <div class="entreprise-info">
                COMPTE DE RESULTAT
            </div>
            <div class="entreprise-details">
                <div class="pull-left">
                Désignation entité : {{ $entreprise->nom ?? 'The Alliance for International Medical Action - AMCP/SP' }}<br>
                Adresse : Rue 420 - Porte 32 Niérabia Commune II<br>
                N° d'identification fiscale : 081119261P<br>
                Numéro d'agrément : Arrêté N°00000045 du 07 Mars 2016<br>
                </div>
                <div class="pull-right">
                Sigle usuel : ALIMA-MALI<br>
                Exercice clos le : 31/12/{{ $exercice }}<br>
                Durée (en mois) : 12
                </div>
            </div>
        </div>
        
        <!-- Tableau du compte de résultat -->
        <table class="resultat-table">
            <!-- En-tête du tableau -->
            <thead>
                <tr class="couleur-head">
                    <th rowspan="2" class="ref-col">REF</th>
                    <th rowspan="2" class="libelle-col">LIBELLES</th>
                    <th rowspan="2" class="note-col">NOTE</th>
                    <th class="text-center">EXERCICE au 31/12/N</th>
                    <th class="text-center">EXERCICE AU 31/12/N-1</th>
                </tr>
                <tr class="couleur-head">
                    <th class="montant-col">MONTANT</th>
                    <th class="montant-col">MONTANT</th>
                </tr>
            </thead>
            
            <tbody>
                <!-- Section PRODUITS -->
                <!--<tr>
                    <td colspan="5" style="background-color: #e6e6e6; font-weight: bold; text-align: center; padding: 4px;">
                        PRODUITS D'EXPLOITATION
                    </td>
                </tr>-->
                
                <!-- RA - Cotisations -->
                <tr class="couleur-produit">
                    <td class="ref-col">RA</td>
                    <td class="libelle-col">{{ $resultatData['RA']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RA']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RA']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RA']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RB - Prestations et ventes -->
                <tr class="couleur-produit">
                    <td class="ref-col">RB</td>
                    <td class="libelle-col">{{ $resultatData['RB']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RB']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RB']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RB']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RC - Subventions et dons -->
                <tr class="couleur-produit">
                    <td class="ref-col">RC</td>
                    <td class="libelle-col">{{ $resultatData['RC']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RC']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RC']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RC']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RD - Ventes de marchandises -->
                <tr class="couleur-produit">
                    <td class="ref-col">RD</td>
                    <td class="libelle-col">{{ $resultatData['RD']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RD']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RD']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RD']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RE - Ventes de services -->
                <tr class="couleur-produit">
                    <td class="ref-col">RE</td>
                    <td class="libelle-col">{{ $resultatData['RE']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RE']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RE']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RE']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RF - Subventions d'exploitation -->
                <tr class="couleur-produit">
                    <td class="ref-col">RF</td>
                    <td class="libelle-col">{{ $resultatData['RF']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RF']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RF']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RF']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RG - Autres produits -->
                <tr class="couleur-produit">
                    <td class="ref-col">RG</td>
                    <td class="libelle-col">{{ $resultatData['RG']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RG']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RG']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RG']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RH - Quote-part subventions -->
                <tr class="couleur-produit">
                    <td class="ref-col">RH</td>
                    <td class="libelle-col">{{ $resultatData['RH']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RH']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RH']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RH']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- RJ - Reprises sur amortissements -->
                <!--<tr class="couleur-produit">
                    <td class="ref-col">RJ</td>
                    <td class="libelle-col">{{ $resultatData['RJ']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RJ']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RJ']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RJ']['montant_n1'] ?? 0) }}</td>
                </tr>-->
                
                <!-- RK - Produits financiers -->
                <!--<tr class="couleur-produit">
                    <td class="ref-col">RK</td>
                    <td class="libelle-col">{{ $resultatData['RK']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RK']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RK']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RK']['montant_n1'] ?? 0) }}</td>
                </tr>-->
                
                <!-- RL - Produits exceptionnels -->
                <!--<tr class="couleur-produit">
                    <td class="ref-col">RL</td>
                    <td class="libelle-col">{{ $resultatData['RL']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['RL']['note'] ?? '' }}</td>
                    <td class="montant-col text-produit">{{ formatMontant($resultatData['RL']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['RL']['montant_n1'] ?? 0) }}</td>
                </tr>-->
                
                <!-- TOTAL PRODUITS - XA -->
                <tr class="couleur-total">
                    <td class="ref-col">XA</td>
                    <td class="libelle-col"><strong>{{ $resultatData['XA']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XA']['montant'] ?? 0) }}</strong></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XA']['montant_n1'] ?? 0) }}</strong></td>
                </tr>
                
                
                
                <!-- Section CHARGES -->
                <!--<tr>
                    <td colspan="5" style="background-color: #e6e6e6; font-weight: bold; text-align: center; padding: 4px;">
                        CHARGES D'EXPLOITATION
                    </td>
                </tr>-->
                
                <!-- TA - Achats -->
                <tr class="couleur-charge">
                    <td class="ref-col">TA</td>
                    <td class="libelle-col">{{ $resultatData['TA']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TA']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TA']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TA']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TB - Achats stockés -->
                <tr class="couleur-charge">
                    <td class="ref-col">TB</td>
                    <td class="libelle-col">{{ $resultatData['TB']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TB']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TB']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TB']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TC - Variations stocks -->
                <tr class="couleur-charge">
                    <td class="ref-col">TC</td>
                    <td class="libelle-col">{{ $resultatData['TC']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TC']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TC']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TC']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TD - Transports -->
                <tr class="couleur-charge">
                    <td class="ref-col">TD</td>
                    <td class="libelle-col">{{ $resultatData['TD']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TD']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TD']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TD']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TE - Services extérieurs -->
                <tr class="couleur-charge">
                    <td class="ref-col">TE</td>
                    <td class="libelle-col">{{ $resultatData['TE']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TE']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TE']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TE']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TF - Autres services -->
                <tr class="couleur-charge">
                    <td class="ref-col">TF</td>
                    <td class="libelle-col">{{ $resultatData['TF']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TF']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TF']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TF']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TG - Services extérieurs -->
                <tr class="couleur-charge">
                    <td class="ref-col">TG</td>
                    <td class="libelle-col">{{ $resultatData['TG']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TG']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TG']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TG']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TH - Impôts et taxes -->
                <tr class="couleur-charge">
                    <td class="ref-col">TH</td>
                    <td class="libelle-col">{{ $resultatData['TH']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TH']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TH']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TH']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                 <!-- TH - Impôts et taxes -->
                <tr class="couleur-charge">
                    <td class="ref-col">TI</td>
                    <td class="libelle-col">{{ $resultatData['TI']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TI']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TI']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TI']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TJ - Charges de personnel -->
                <tr class="couleur-charge">
                    <td class="ref-col">TJ</td>
                    <td class="libelle-col">{{ $resultatData['TJ']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TJ']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TJ']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TJ']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TK - Frais financiers -->
                <tr class="couleur-charge">
                    <td class="ref-col">TK</td>
                    <td class="libelle-col">{{ $resultatData['TK']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TK']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TK']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TK']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TL - Dotations amortissements -->
                <tr class="couleur-charge">
                    <td class="ref-col">TL</td>
                    <td class="libelle-col">{{ $resultatData['TL']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TL']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TL']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TL']['montant_n1'] ?? 0) }}</td>
                </tr>
                
               
                
                
                
                
                <!-- Section RÉSULTATS -->
                <!--<tr>
                    <td colspan="5" style="background-color: #e6e6e6; font-weight: bold; text-align: center; padding: 4px;">
                        RÉSULTATS
                    </td>
                </tr> -->
                <tr class="couleur-total">
                    <td class="ref-col">XB</td>
                    <td class="libelle-col"><strong>{{ $resultatData['XB']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XB']['montant'] ?? 0) }}</strong></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XB']['montant_n1'] ?? 0) }}</strong></td>
                </tr>
                <!-- XC - Résultat des activités ordinaires -->
                <tr class="couleur-resultat">
                    <td class="ref-col">XC</td>
                    <td class="libelle-col"><strong>{{ $resultatData['XC']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XC']['montant'] ?? 0) }}</strong></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XC']['montant_n1'] ?? 0) }}</strong></td>
                </tr>
                
                <!-- TM - Produits H.A.O. -->
                <tr class="couleur-charge">
                    <td class="ref-col">TM</td>
                    <td class="libelle-col">{{ $resultatData['TM']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TM']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TM']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TM']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TN - Charges H.A.O. -->
                <tr class="couleur-charge">
                    <td class="ref-col">TN</td>
                    <td class="libelle-col">{{ $resultatData['TN']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $resultatData['TN']['note'] ?? '' }}</td>
                    <td class="montant-col text-charge">{{ formatMontant($resultatData['TN']['montant'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($resultatData['TN']['montant_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- XD - Résultat H.A.O. -->
                <tr class="couleur-resultat">
                    <td class="ref-col">XD</td>
                    <td class="libelle-col"><strong>{{ $resultatData['XD']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XD']['montant'] ?? 0) }}</strong></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XD']['montant_n1'] ?? 0) }}</strong></td>
                </tr>
                
               
                <!-- XE - Résultat net -->
                <tr class="couleur-resultat-net">
                    <td class="ref-col">XE</td>
                    <td class="libelle-col"><strong>{{ $resultatData['XE']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XE']['montant'] ?? 0) }}</strong></td>
                    <td class="montant-col text-total"><strong>{{ formatMontant($resultatData['XE']['montant_n1'] ?? 0) }}</strong></td>
                </tr>
            </tbody>
        </table>
        
        
    </div>
</div>

@php
function formatMontant($montant) {
    if ($montant === null || abs($montant) < 1) {
        return '0';
    }
    return number_format($montant, 0, ',', ' ');
}
@endphp