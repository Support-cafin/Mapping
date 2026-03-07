<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fiche Donateur - {{ $donateur->numero_enregistrement }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; }
        .title { font-size: 18px; font-weight: bold; }
        .subtitle { font-size: 14px; color: #666; }
        .section { margin-bottom: 15px; }
        .section-title { font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 5px; }
        .label { font-weight: bold; color: #555; }
        .value { color: #333; }
        .montant { font-size: 16px; font-weight: bold; color: #2ecc71; }
        .signature { margin-top: 50px; border-top: 1px solid #000; width: 300px; padding-top: 10px; }
        .footer { margin-top: 50px; font-size: 10px; color: #999; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $entreprise->nom ?? 'Entreprise' }}</h1>
        <div class="entreprise-info">
            Code: {{ $entreprise->code ?? 'N/A' }} | 
            Fiche générée le: {{ $dateGeneration }}
        </div>
        <h2>FICHE DONATEUR</h2>
        <h3>{{ $donateur->numero_enregistrement }}</h3>
    </div>

    <div class="section">
        <div class="section-title">Informations Générales</div>
        <div class="info-grid">
            <div class="label">N° Enregistrement:</div>
            <div class="value">{{ $donateur->numero_enregistrement }}</div>
            
            <div class="label">Date:</div>
            <div class="value">{{ $donateur->date->format('d/m/Y') }}</div>
            
            <div class="label">Statut:</div>
            <div class="value">{{ ucfirst($donateur->statut) }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Donateur</div>
        <div class="info-grid">
            <div class="label">Dénomination:</div>
            <div class="value">{{ $donateur->denomination }}</div>
            
            @if($donateur->nom_prenoms)
            <div class="label">Nom et prénoms:</div>
            <div class="value">{{ $donateur->nom_prenoms }}</div>
            @endif
            
            @if($donateur->numero_identification_fiscal)
            <div class="label">NIF:</div>
            <div class="value">{{ $donateur->numero_identification_fiscal }}</div>
            @endif
            
            @if($donateur->registre_commerce)
            <div class="label">Registre Commerce:</div>
            <div class="value">{{ $donateur->registre_commerce }}</div>
            @endif
            
            @if($donateur->adresse_siege_social)
            <div class="label">Adresse:</div>
            <div class="value">{{ $donateur->adresse_siege_social }}</div>
            @endif
        </div>
    </div>

    <div class="section">
        <div class="section-title">Détails du Don</div>
        <div class="info-grid">
            <div class="label">Montant:</div>
            <div class="montant">{{ number_format($donateur->montant_don, 0, ',', ' ') }} {{ $donateur->devise }}</div>
            
            <div class="label">Mode de libération:</div>
            <div class="value">{{ ucfirst($donateur->mode_liberation) }}</div>
            
            @if($donateur->compte)
            <div class="label">Compte comptable:</div>
            <div class="value">{{ $donateur->compte->numero }} - {{ $donateur->compte->libelle }}</div>
            @endif
            
            <div class="label">Signature représentant:</div>
            <div class="value">{{ $donateur->signature_representant ? 'OUI' : 'NON' }}</div>
        </div>
    </div>

    @if($donateur->notes)
    <div class="section">
        <div class="section-title">Notes</div>
        <div style="padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
            {{ $donateur->notes }}
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-title">Informations de Suivi</div>
        <div class="info-grid">
            <div class="label">Créé le:</div>
            <div class="value">{{ $donateur->created_at->format('d/m/Y H:i') }}</div>
            
            <div class="label">Par:</div>
            <div class="value">{{ $donateur->createur->name ?? 'N/A' }}</div>
            
            <div class="label">Dernière modification:</div>
            <div class="value">{{ $donateur->updated_at->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    <div class="footer" style="text-align: center; margin-top: 30px; font-size: 10px; color: #666;">
        Document généré le {{ now()->format('d/m/Y H:i') }} | 
        Document confidentiel - {{ $entreprise->nom ?? 'Entreprise' }}
    </div>
</body>
</html>