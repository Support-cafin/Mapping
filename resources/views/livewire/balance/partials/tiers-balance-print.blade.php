<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance des Tiers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th {
            background-color: #f0f0f0;
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0 0;
            color: #666;
        }
        .footer {
            margin-top: 30px;
            text-align: right;
            font-size: 12px;
            color: #666;
        }
        .text-right {
            text-align: right;
        }
        .font-bold {
            font-weight: bold;
        }
        .text-red-600 { color: #dc2626; }
        .text-green-600 { color: #059669; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Balance des Tiers</h1>
        <p>{{ $entreprise->name }} - Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}</p>
    </div>
    
    @include('livewire.balance.partials.tiers-table')
    
    <div class="footer">
        Éditée le {{ now()->format('d/m/Y à H:i') }}
    </div>
</body>
</html>