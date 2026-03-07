<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Résultats d'importation</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-box {
            background: #f9fafb;
            border-left: 4px solid;
            padding: 15px;
            border-radius: 8px;
        }
        .summary-box.total { border-color: #3b82f6; }
        .summary-box.success { border-color: #22c55e; }
        .summary-box.warning { border-color: #eab308; }
        .summary-box.error { border-color: #ef4444; }
        
        .summary-box .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .summary-box .value {
            font-size: 28px;
            font-weight: bold;
            margin-top: 5px;
        }
        .summary-box.total .value { color: #3b82f6; }
        .summary-box.success .value { color: #22c55e; }
        .summary-box.warning .value { color: #eab308; }
        .summary-box.error .value { color: #ef4444; }
        
        .info-row {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 11px;
        }
        .info-row table {
            width: 100%;
        }
        .info-row td {
            padding: 5px;
            color: #4b5563;
        }
        .info-row td:first-child {
            font-weight: 600;
            width: 150px;
            color: #1f2937;
        }
        
        .messages-section {
            margin-top: 30px;
        }
        .messages-section h2 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .message {
            padding: 12px;
            margin-bottom: 8px;
            border-radius: 6px;
            border-left-width: 4px;
            border-left-style: solid;
        }
        .message-error {
            background: #fef2f2;
            border-left-color: #ef4444;
        }
        .message-warning {
            background: #fefce8;
            border-left-color: #eab308;
        }
        .message-success {
            background: #f0fdf4;
            border-left-color: #22c55e;
        }
        .message-info {
            background: #f3f4f6;
            border-left-color: #9ca3af;
        }
        .message .type {
            font-weight: 600;
            margin-bottom: 3px;
        }
        .message-error .type { color: #b91c1c; }
        .message-warning .type { color: #854d0e; }
        .message-success .type { color: #166534; }
        .message-info .type { color: #374151; }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Résultats d'importation</h1>
        <p>{{ $importType === 'old' ? 'Anciens comptes (ENTITÉ)' : 'Nouveaux comptes (SYCEBNL)' }}</p>
    </div>
    
    <div class="summary">
        <div class="summary-box total">
            <div class="label">Total traités</div>
            <div class="value">{{ $summary['total'] }}</div>
        </div>
        <div class="summary-box success">
            <div class="label">Importés</div>
            <div class="value">{{ $importedCount }}</div>
        </div>
        <div class="summary-box warning">
            <div class="label">Avertissements</div>
            <div class="value">{{ $summary['warnings'] }}</div>
        </div>
        <div class="summary-box error">
            <div class="label">Erreurs</div>
            <div class="value">{{ $summary['errors'] }}</div>
        </div>
    </div>
    
    <div class="info-row">
        <table>
            <tr>
                <td>Entreprise :</td>
                <td>{{ $entreprise }}</td>
            </tr>
            <tr>
                <td>Utilisateur :</td>
                <td>{{ $user }}</td>
            </tr>
            <tr>
                <td>Date d'import :</td>
                <td>{{ $generated_at }}</td>
            </tr>
            <tr>
                <td>Fichier source :</td>
                <td>{{ $summary['filename'] }}</td>
            </tr>
            <tr>
                <td>Temps d'exécution :</td>
                <td>{{ number_format($summary['duration'], 2) }} secondes</td>
            </tr>
        </table>
    </div>
    
    <div class="messages-section">
        <h2>Détail des opérations</h2>
        
        @forelse($allMessages as $message)
            @php
                $isError = str_contains($message, '❌') || str_contains($message, 'Erreur');
                $isWarning = str_contains($message, '⚠️') || str_contains($message, 'Attention');
                $isSuccess = str_contains($message, '✓') || str_contains($message, 'succès');
                $type = $isError ? 'error' : ($isWarning ? 'warning' : ($isSuccess ? 'success' : 'info'));
            @endphp
            
            <div class="message message-{{ $type }}">
                <div class="type">
                    @if($type === 'error') ❌ ERREUR
                    @elseif($type === 'warning') ⚠️ AVERTISSEMENT
                    @elseif($type === 'success') ✓ SUCCÈS
                    @else ℹ️ INFORMATION
                    @endif
                </div>
                <div>{!! nl2br(e($message)) !!}</div>
            </div>
        @empty
            <p>Aucun message à afficher</p>
        @endforelse
    </div>
    
    <div class="footer">
        <p>Document généré le {{ $generated_at }} - {{ $entreprise }}</p>
    </div>
</body>
</html>