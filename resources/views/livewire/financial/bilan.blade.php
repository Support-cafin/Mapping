<div>
    <!-- Styles CSS -->
    <style>
        .bilan-container {
            font-family: Arial, sans-serif;
            font-size: 10px;
            width: 100%;
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
        
        .bilan-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            font-size: 9px;
        }
        
        .bilan-table th {
            background-color: #f2f2f2;
            border: 1px solid #000;
            padding: 3px 2px;
            text-align: center;
            font-weight: bold;
            vertical-align: middle;
        }
        
        .bilan-table td {
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
            width: 300px;
            padding-left: 5px !important;
        }
        
        .note-col {
            width: 25px;
            text-align: center;
        }
        
        .montant-col {
            width: 100px;
            text-align: right;
            font-family: 'Courier New', monospace;
            padding-right: 6px !important;
        }
        
        .controls-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        
        /* COULEURS EXACTES DU FICHIER EXCEL */
        .couleur-1 {
            background-color: #ffffff !important; /* Blanc */
        }
        
        .couleur-2 {
            background-color: #ffffff !important; /* Jaune clair */
        }
        
        .couleur-3 {
            background-color: #ffffff !important; /* Vert clair */
        }
        
        .couleur-4 {
            background-color: #ffffff !important; /* Bleu clair */
        }
        
        .couleur-5 {
            background-color: #033080 !important; /* Rouge clair */
            color : white;
        }
        
        .couleur-6 {
            background-color: #e6ccff !important; /* Violet clair */
        }
        
        .couleur-7 {
            background-color: green !important; /* Orange clair */
            color : white;
        }
        
        .couleur-8 {
            background-color: #cccccc !important; /* Gris clair */
        }
        
        .couleur-9 {
            background-color: blue; !important; /* Gris très clair */
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
       <!-- En-tête -->
    <div class="bg-white rounded-lg shadow border p-6 mb-4">
        <div class="row">
            <div class="col-md-6">
                <h3 class="h5 mb-0 fw-bold">
                    <i class="fas fa-chart-line me-2"></i> Bilan
                </h3>
                <p class="mb-0"><strong>Désignation entité :</strong> {{ $entreprise->nom }}</p>
                <p class="mb-0"><strong>Exercice :</strong> {{ $exercice }}</p>
                <p class="mb-0"><strong>Période :</strong> 01/01/{{ $exercice }} au 31/12/{{ $exercice }}</p>
            </div>
            <!--<div class="col-md-6 text-end">
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-light btn-sm" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimer
                    </button>
                    <a href="{{ route('bilan.export', ['dateDebut' => 01/01/$exercice, 'dateFin' => 31/12/$exercice, 'exercice' => $exercice]) }}" 
                       class="btn btn-success btn-sm">
                        <i class="fas fa-file-excel me-1"></i> Excel
                    </a>
                </div>
            </div>-->
        </div>
    </div>
    </div>
    
    <!-- Bilan Comptable -->
    <div class="bilan-container">
        <!-- En-tête -->
        <div class="header-info">
            <div class="entreprise-info">
                BILAN
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
        
        <!-- Tableau du bilan -->
        <table class="bilan-table">
            <!-- En-tête du tableau -->
            <thead>
                <tr class="couleur-9 text-green">
                    <th rowspan="2" class="ref-col">REF</th>
                    <th rowspan="2" class="libelle-col">ACTIF</th>
                    <th rowspan="2" class="note-col">Note</th>
                    <th colspan="3" class="text-center">EXERCICE au 31/12/N</th>
                    <th class="text-center">EXERCICE AU 31/12/N-1</th>
                    <th style="width: 15px;"></th>
                    <th rowspan="2" class="ref-col">REF</th>
                    <th rowspan="2" class="libelle-col">PASSIF</th>
                    <th rowspan="2" class="note-col">Note</th>
                    <th class="text-center">EXERCICE AU 31/12/N</th>
                    <th class="text-center">EXERCICE AU 31/12/N-1</th>
                </tr>
                <tr class="couleur-9">
                    <th class="montant-col">BRUT</th>
                    <th class="montant-col">AMORT et DEPREC.</th>
                    <th class="montant-col">NET</th>
                    <th class="montant-col">NET</th>
                    <th></th>
                    <th class="montant-col">NET</th>
                    <th class="montant-col">NET</th>
                </tr>
            </thead>
            
            <tbody>
                <!-- Section 1: Immobilisations en attente -->
                <tr class="couleur-1">
                    <td class="ref-col">AA</td>
                    <td class="libelle-col"><b>{{ $actifData['AA']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $actifData['AA']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AA']['brut'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AA']['amortissement'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AA']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AA']['net_n1'] ?? 0) }}</b></td>
                    <td></td>
                    <td class="ref-col">CA</td>
                    <td class="libelle-col">{{ $passifData['CA']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CA']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CA']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CA']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Ligne AB - CB (blanc) -->
                <tr class="couleur-1">
                    <td class="ref-col" style"background-color : gris;">AB</td>
                    <td class="libelle-col">{{ $actifData['AB']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AB']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AB']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AB']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AB']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AB']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CB</td>
                    <td class="libelle-col">{{ $passifData['CB']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CB']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CB']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CB']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Ligne AC - CC (blanc) -->
                <tr class="couleur-1">
                    <td class="ref-col">AC</td>
                    <td class="libelle-col">{{ $actifData['AC']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AC']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AC']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AC']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AC']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AC']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CC</td>
                    <td class="libelle-col">{{ $passifData['CC']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CC']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CC']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CC']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Section 2: Immobilisations incorporelles (JAUNE) -->
                <tr class="couleur-2">
                    <td class="ref-col">AD</td>
                    <td class="libelle-col"><b>{{ $actifData['AD']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $actifData['AD']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AD']['brut'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AD']['amortissement'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AD']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AD']['net_n1'] ?? 0) }}</b></td>
                    <td></td>
                    <td class="ref-col">CD</td>
                    <td class="libelle-col">{{ $passifData['CD']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CD']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CD']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CD']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Détails immob incorporelles (jaune) -->
                <tr class="couleur-2">
                    <td class="ref-col">AE</td>
                    <td class="libelle-col">{{ $actifData['AE']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AE']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AE']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AE']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AE']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AE']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CE</td>
                    <td class="libelle-col">{{ $passifData['CE']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CE']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CE']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CE']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AF</td>
                    <td class="libelle-col">{{ $actifData['AF']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AF']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AF']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AF']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AF']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AF']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CF</td>
                    <td class="libelle-col">{{ $passifData['CF']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CF']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CF']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CF']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AG</td>
                    <td class="libelle-col">{{ $actifData['AG']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AG']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AG']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AG']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AG']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AG']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CG</td>
                    <td class="libelle-col">{{ $passifData['CG']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CG']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CG']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CG']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Section 3: Immobilisations corporelles (JAUNE) -->
                <tr class="couleur-2">
                    <td class="ref-col">AH</td>
                    <td class="libelle-col"><b>{{ $actifData['AH']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $actifData['AH']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AH']['brut'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AH']['amortissement'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AH']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AH']['net_n1'] ?? 0) }}</b></td>
                    <td></td>
                    <td class="ref-col">CH</td>
                    <td class="libelle-col">{{ $passifData['CH']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CH']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CH']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CH']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Détails immob corporelles (jaune) -->
                <tr class="couleur-2">
                    <td class="ref-col">AI</td>
                    <td class="libelle-col">{{ $actifData['AI']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AI']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AI']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AI']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AI']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AI']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CI</td>
                    <td class="libelle-col">{{ $passifData['CI']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CI']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CI']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CI']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AJ</td>
                    <td class="libelle-col">{{ $actifData['AJ']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AJ']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AJ']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AJ']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AJ']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AJ']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CJ</td>
                    <td class="libelle-col">{{ $passifData['CJ']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CJ']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CJ']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CJ']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AK</td>
                    <td class="libelle-col">{{ $actifData['AK']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AK']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AK']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AK']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AK']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AK']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CK</td>
                    <td class="libelle-col"><strong>{{ $passifData['CK']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['CK']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['CK']['net_n1'] ?? 0) }}</strong></td>
                    
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AL</td>
                    <td class="libelle-col">{{ $actifData['AL']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AL']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AL']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AL']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AL']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AL']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CW</td>
                    <td class="libelle-col">{{ $passifData['CW']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CW']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CW']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CW']['net_n1'] ?? 0) }}</td>
                    
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AM</td>
                    <td class="libelle-col">{{ $actifData['AM']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AM']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AM']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AM']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AM']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AM']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">CX</td>
                    <td class="libelle-col">{{ $passifData['CX']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['CX']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CX']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['CX']['net_n1'] ?? 0) }}</td>
                   
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col">AN</td>
                    <td class="libelle-col">{{ $actifData['AN']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AN']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AN']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AN']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AN']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AN']['net_n1'] ?? 0) }}</td>
                    <td></td>
                     <td class="ref-col">CY</td>
                    <td class="libelle-col"><b>{{ $passifData['CY']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $passifData['CY']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($passifData['CY']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($passifData['CY']['net_n1'] ?? 0) }}</b></td>
                    
                </tr>
                
                <!-- Section 4: Immobilisations financières (JAUNE) -->
                <tr class="couleur-2">
                    <td class="ref-col">AO</td>
                    <td class="libelle-col"><b>{{ $actifData['AO']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $actifData['AO']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AO']['brut'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AO']['amortissement'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AO']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AO']['net_n1'] ?? 0) }}</b></td>
                    <td></td>
                    <td class="ref-col">CZ</td>
                    <td class="libelle-col"><b>{{ $passifData['CZ']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $passifData['CZ']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($passifData['CZ']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($passifData['CZ']['net_n1'] ?? 0) }}</b></td>
                    
                </tr>
                
                <!-- Détails immob financières (jaune) -->
                <tr class="couleur-2">
                    <td class="ref-col">AX</td>
                    <td class="libelle-col">{{ $actifData['AX']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AX']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AX']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AX']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AX']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AX']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DA</td>
                    <td class="libelle-col">{{ $passifData['DA']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DA']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DA']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DA']['net_n1'] ?? 0) }}</td>
                    
                </tr>
                
               
                
                <tr class="couleur-2">
                    <td class="ref-col">AY</td>
                    <td class="libelle-col">{{ $actifData['AY']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['AY']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AY']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AY']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AY']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['AY']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DB</td>
                    <td class="libelle-col">{{ $passifData['DB']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DB']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DB']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DB']['net_n1'] ?? 0) }}</td>
                    
                </tr>
                
                 <tr class="couleur-2">
                    <td class="ref-col"></td>
                    <td class="libelle-col"></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td></td>
                   <td class="ref-col">DC</td>
                    <td class="libelle-col">{{ $passifData['DC']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DC']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DC']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DC']['net_n1'] ?? 0) }}</td>
                    
                </tr>
                
                <tr class="couleur-2">
                    <td class="ref-col"></td>
                    <td class="libelle-col"></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td></td>
                   <td class="ref-col">DD</td>
                    <td class="libelle-col"><b>{{ $passifData['DD']['libelle'] ?? '' }}</b></td>
                    <td class="note-col"><b>{{ $passifData['DD']['note'] ?? '' }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($passifData['DD']['net'] ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($passifData['DD']['net_n1'] ?? 0) }}</b></td>
                    
                </tr>
                <!-- Ligne vide -->
                <tr>
                    <td colspan="7"></td>
                    <td></td>
                    <td colspan="5"></td>
                </tr>
                
                <!-- TOTAL ACTIF IMMOBILISE - TOTAL FONDS PROPRES (ORANGE) -->
                <tr class="couleur-7">
                    <td class="ref-col" style="background-color: white; color: black;">AZ</td>
                    <td class="libelle-col"><strong>{{ $actifData['AZ']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AA']['brut'] + $actifData['AD']['brut'] + $actifData['AH']['brut'] + $actifData['AO']['brut']  ?? 0) }}</b></td>
                    <td class="montant-col"><b>{{ formatMontant($actifData['AA']['amortissement'] + $actifData['AD']['amortissement'] + $actifData['AH']['amortissement'] + $actifData['AO']['amortissement']  ?? 0) }}</b></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['AZ']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['AZ']['net_n1'] ?? 0) }}</strong></td>
                    <td></td>
                    <td class="ref-col" style="background-color: white; color: black;">DE</td>
                    <td class="libelle-col"><strong>{{ $passifData['DE']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['DE']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['DE']['net_n1'] ?? 0) }}</strong></td>
                </tr>
                
                <!-- Ligne vide -->
                <tr>
                    <td colspan="7"></td>
                    <td></td>
                    <td colspan="5"></td>
                </tr>
                
                <!-- Section 5: Actif circulant HAO (VERT) -->
                <tr class="couleur-3">
                    <td class="ref-col">BA</td>
                    <td class="libelle-col">{{ $actifData['BA']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BA']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BA']['brut'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BA']['amortissement'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BA']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BA']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DF</td>
                    <td class="libelle-col">{{ $passifData['DF']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DF']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DF']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DF']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- Section 6: Actif circulant détaillé (VERT) -->
                <tr class="couleur-3">
                    <td class="ref-col">BB</td>
                    <td class="libelle-col">{{ $actifData['BB']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BB']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BB']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BB']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BB']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DG</td>
                    <td class="libelle-col">{{ $passifData['DG']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DG']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DG']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DG']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-3">
                    <td class="ref-col">BC</td>
                    <td class="libelle-col">{{ $actifData['BC']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BC']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BC']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BC']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BC']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DH</td>
                    <td class="libelle-col">{{ $passifData['DH']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DH']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DH']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DH']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-3">
                    <td class="ref-col">BD</td>
                    <td class="libelle-col">{{ $actifData['BD']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BD']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BD']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BD']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BD']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DI</td>
                    <td class="libelle-col">{{ $passifData['DI']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DI']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DI']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DI']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-3">
                    <td class="ref-col">BE</td>
                    <td class="libelle-col">{{ $actifData['BE']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BE']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BE']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BE']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BE']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col"></td>
                    <td class="libelle-col"></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                </tr>
                 
                <!-- Ligne vide -->
                <tr>
                    <td colspan="7"></td>
                    <td></td>
                    <td colspan="5"></td>
                </tr>
                
                <!-- TOTAL ACTIF CIRCULANT - TOTAL PASSIF CIRCULANT (ORANGE) -->
                <tr class="couleur-7">
                    <td class="ref-col" style="background-color: white; color: black;">BT</td>
                    <td class="libelle-col"><strong>{{ $actifData['BT']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['BT']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['BT']['net_n1'] ?? 0) }}</strong></td>
                    <td></td>
                    <td class="ref-col" style="background-color: white; color: black;">DV</td>
                    <td class="libelle-col"><strong>{{ $passifData['DV']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['DV']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['DV']['net_n1'] ?? 0) }}</strong></td>
                </tr>
                
                <!-- Ligne vide -->
                <tr>
                    <td colspan="7"></td>
                    <td></td>
                    <td colspan="5"></td>
                </tr>
                
                <!-- Section 7: Trésorerie actif (BLEU) -->
                <tr class="couleur-4">
                    <td class="ref-col">BU</td>
                    <td class="libelle-col">{{ $actifData['BU']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BU']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BU']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BU']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BU']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DW</td>
                    <td class="libelle-col">{{ $passifData['DW']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DW']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DW']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DW']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <tr class="couleur-4">
                    <td class="ref-col">BV</td>
                    <td class="libelle-col">{{ $actifData['BV']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BV']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BV']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BV']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BV']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col"></td>
                    <td class="libelle-col"><strong></strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"><strong></strong></td>
                    <td class="montant-col"><strong></strong></td>
                </tr>
                
                <tr class="couleur-4">
                    <td class="ref-col">BW</td>
                    <td class="libelle-col">{{ $actifData['BW']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BW']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BW']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BW']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BW']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col"></td>
                    <td class="libelle-col"></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                </tr>
                
                <!-- Ligne vide -->
                <tr>
                    <td colspan="7"></td>
                    <td></td>
                    <td colspan="5"></td>
                </tr>
                
                <!-- TOTAL TRÉSORERIE ACTIF - TOTAL RESSOURCES STABLES (ORANGE) -->
                <tr class="couleur-7">
                    <td class="ref-col" style="background-color: white; color: black;">BX</td>
                    <td class="libelle-col"><strong>{{ $actifData['BX']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['BX']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['BX']['net_n1'] ?? 0) }}</strong></td>
                    <td></td>
                    <td class="ref-col" style="background-color: white; color: black;">DX</td>
                    <td class="libelle-col">{{ $passifData['DX']['libelle'] ?? '' }}</td>
                    <td class="note-col"></td>
                    <td class="montant-col">{{ formatMontant($passifData['DX']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DX']['net_n1'] ?? 0) }}</td>
                    
                </tr>
                
                <!-- Section 8: Écarts de conversion (BLEU) -->
                <tr class="couleur-4">
                    <td class="ref-col">BY</td>
                    <td class="libelle-col">{{ $actifData['BY']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $actifData['BY']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BY']['brut'] ?? 0) }}</td>
                    <td class="montant-col"></td>
                    <td class="montant-col">{{ formatMontant($actifData['BY']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($actifData['BY']['net_n1'] ?? 0) }}</td>
                    <td></td>
                    <td class="ref-col">DY</td>
                    <td class="libelle-col">{{ $passifData['DY']['libelle'] ?? '' }}</td>
                    <td class="note-col">{{ $passifData['DY']['note'] ?? '' }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DY']['net'] ?? 0) }}</td>
                    <td class="montant-col">{{ formatMontant($passifData['DY']['net_n1'] ?? 0) }}</td>
                </tr>
                
                <!-- TOTAL GÉNÉRAL ACTIF - TOTAL GÉNÉRAL PASSIF (ROUGE) -->
                <tr class="couleur-5">
                    <td class="ref-col" style="background-color: white; color: black;">BZ</td>
                    <td class="libelle-col"><strong>{{ $actifData['BZ']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['BZ']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($actifData['BZ']['net_n1'] ?? 0) }}</strong></td>
                    <td></td>
                    <td class="ref-col" style="background-color: white; color: black;">DZ</td>
                    <td class="libelle-col"><strong>{{ $passifData['DZ']['libelle'] ?? '' }}</strong></td>
                    <td class="note-col"></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['DZ']['net'] ?? 0) }}</strong></td>
                    <td class="montant-col"><strong>{{ formatMontant($passifData['DZ']['net_n1'] ?? 0) }}</strong></td>
                </tr>
            </tbody>
        </table>
        
        <!-- Équilibre du bilan -->
        <div class="mt-3">
            @if(isset($totaux['equilibre']))
                @if($totaux['equilibre'])
                    <div class="alert alert-success text-center py-2">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>Bilan équilibré :</strong> 
                        Actif ({{ formatMontant($totaux['actif'] ?? 0) }}) = 
                        Passif ({{ formatMontant($totaux['passif'] ?? 0) }})
                    </div>
                @else
                    <div class="alert alert-warning text-center py-2">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Bilan déséquilibré :</strong> 
                        Différence de {{ formatMontant($totaux['difference'] ?? 0) }}
                    </div>
                @endif
            @endif 
        </div>
        
        <!-- Informations supplémentaires -->
        <div class="mt-2 text-center" style="font-size: 8px;">
            Document généré le {{ date('d/m/Y H:i') }} | 
            Exercice {{ $exercice }} | 
            Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
        </div>
    </div>
</div>

@php
function formatMontant($montant) {
    if ($montant === null || abs($montant) < 1) {
        return '';
    }
    return number_format($montant, 0, ',', ' ');
}
@endphp