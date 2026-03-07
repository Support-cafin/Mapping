<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Résultat d'importation</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4CAF50;
        }
        .header h1 {
            color: #4CAF50;
            margin: 0;
            font-size: 20px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .summary {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
        .summary-grid {
            display: table;
            width: 100%;
        }
        .summary-row {
            display: table-row;
        }
        .summary-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px;
            width: 30%;
        }
        .summary-value {
            display: table-cell;
            padding: 5px 10px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
        }
        .message-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .message-item {
            padding: 8px 12px;
            margin-bottom: 5px;
            border-radius: 4px;
            background-color: #f8f9fa;
            border-left: 4px solid;
        }
        .message-item.error {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }
        .message-item.warning {
            border-left-color: #ffc107;
            background-color: #fff3cd;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            margin-right: 5px;
        }
        .badge.success {
            background-color: #28a745;
            color: white;
        }
        .badge.warning {
            background-color: #ffc107;
            color: #333;
        }
        .badge.error {
            background-color: #dc3545;
            color: white;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 10px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>RAPPORT D'IMPORTATION</h1>
        <p>Généré le {{ $date }}</p>
    </div>
    
    <div class="summary">
        <div class="summary-grid">
            <div class="summary-row">
                <div class="summary-label">Entreprise :</div>
                <div class="summary-value">{{ $entreprise_nom }} (ID: {{ $entreprise_id }})</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Exercice :</div>
                <div class="summary-value">{{ $exercice }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Écritures importées :</div>
                <div class="summary-value"><span class="success">{{ $importedCount }}</span></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Erreurs :</div>
                <div class="summary-value"><span class="error">{{ $errorsCount }}</span></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Avertissements :</div>
                <div class="summary-value"><span class="warning">{{ $warningsCount }}</span></div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Statut global :</div>
                <div class="summary-value">
                    @if($success)
                        <span class="badge success">RÉUSSI</span>
                    @else
                        <span class="badge error">ÉCHEC PARTIEL</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    @if(!empty($errors))
    <div class="section">
        <div class="section-title">
            <span class="error">❌ ERREURS ({{ count($errors) }})</span>
        </div>
        <ul class="message-list">
            @foreach($errors as $error)
                <li class="message-item error">{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    
    @if(!empty($warnings))
    <div class="section">
        <div class="section-title">
            <span class="warning">⚠️ AVERTISSEMENTS ({{ count($warnings) }})</span>
        </div>
        <ul class="message-list">
            @foreach($warnings as $warning)
                <li class="message-item warning">{{ $warning }}</li>
            @endforeach
        </ul>
    </div>
    @endif
    
    @if(empty($errors) && empty($warnings))
    <div class="section">
        <div class="section-title">
            <span class="success">✓ AUCUNE ERREUR</span>
        </div>
        <p style="text-align: center; padding: 20px;">L'importation s'est déroulée sans erreur ni avertissement.</p>
    </div>
    @endif
    
    <div class="footer">
        <p>Document généré automatiquement - {{ config('app.name') }}</p>
    </div>
</body>
</html>