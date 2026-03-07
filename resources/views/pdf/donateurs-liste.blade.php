<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Registre des Donateurs</title>

    <style>
        @page {
            margin: 30px; /* ✅ marge plus grande pour éviter bordure coupée */
        }

        body {
            font-family: "Times New Roman", serif; /* ✅ police officielle */
            font-size: 14px;
            color: #000;
        }

        .page-break {
            page-break-after: always;
        }

        /* Cadre principal */
        .donateur-container {
            border: 2px solid #2e7d32;
            padding: 20px; /* ✅ padding plus large */
            margin-bottom: 25px;
            width: 95%;
            box-sizing: border-box;
        }

        /* Titre unique */
        .main-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        /* Ligne formulaire */
        .form-row {
            margin-bottom: 12px;
        }

        .label-text {
            font-weight: bold;
            display: inline-block;
            width: 220px;
        }

        .label-value {
            display: inline-block;
        }

        /* Montant */
        .montant-row {
            margin-top: 15px;
            font-weight: bold;
        }

        /* Mode libération en une seule ligne */
        .mode-liberation {
            margin-top: 20px;
            font-weight: bold;
        }

        .mode-inline {
            display: inline-block;
            margin-left: 15px;
            font-weight: normal;
        }

        .checkbox {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 1px solid black;
            text-align: center;
            line-height: 14px;
            font-weight: bold;
            margin-right: 4px;
        }

        /* Signature */
        .signature {
            margin-top: 40px;
            font-weight: bold;
            margin-bottom: 25px; /* ✅ espace en bas */
        }


    </style>
</head>

<body>

{{-- ✅ Numéro de page en haut --}}
<script type="text/php">
    if (isset($pdf)) {
        $pdf->page_text(520, 20, "Page {PAGE_NUM} / {PAGE_COUNT}", null, 10, array(0,0,0));
    }
</script>

@php
    if (!is_array($donateursGrouped) && method_exists($donateursGrouped, 'toArray')) {
        $donateursGrouped = $donateursGrouped->toArray();
    }

    $totalPages = count($donateursGrouped);
    $currentPage = 0;
@endphp

@if($totalDonateurs > 0)

    @foreach($donateursGrouped as $pageIndex => $donateursPair)

        @php $currentPage++; @endphp

        {{-- ✅ TITRE UNE SEULE FOIS PAR PAGE --}}
        <div class="main-title">
            REGISTRE DES DONATEURS {{ $entreprise->nom ?? 'ALIMA SENEGAL' }} 
        </div>

        {{-- Boucle donateurs (2 max par page) --}}
        @foreach($donateursPair as $donateurData)

            @php
                $donateur = is_array($donateurData) ? (object)$donateurData : $donateurData;
            @endphp

            <div class="donateur-container">

                {{-- DATE --}}
                <div class="form-row">
                    <span class="label-text">Date :</span>
                    <span class="label-value">
                        {{ \Carbon\Carbon::parse($donateur->date)->format('d/m/Y') }}
                    </span>
                </div>

                {{-- NOM --}}
                <div class="form-row">
                    <span class="label-text">Nom et prénoms :</span>
                    <span class="label-value">{{ $donateur->nom_prenoms ?? '' }}</span>
                </div>

                {{-- DENOMINATION --}}
                <div class="form-row">
                    <span class="label-text">Dénomination :</span>
                    <span class="label-value">{{ $donateur->denomination ?? '' }}</span>
                </div>

                {{-- REGISTRE COMMERCE --}}
                <div class="form-row">
                    <span class="label-text">Registre de Commerce :</span>
                    <span class="label-value">{{ $donateur->registre_commerce ?? '' }}</span>
                </div>

                {{-- NIF --}}
                <div class="form-row">
                    <span class="label-text">Numéro identification fiscal :</span>
                    <span class="label-value">{{ $donateur->numero_identification_fiscal ?? '' }}</span>
                </div>

                {{-- ADRESSE --}}
                <div class="form-row">
                    <span class="label-text">Adresse siège social :</span>
                    <span class="label-value">{{ $donateur->adresse_siege_social ?? '' }}</span>
                </div>

                {{-- EMAIL --}}
                @if($donateur->email && is_array($donateur->email) && count($donateur->email) > 0)
                <div class="form-row">
                    <span class="label-text">Email :</span>
                    @foreach($donateur->email as $email)
                    <span class="label-value">{{ $email ?? '' }}</span>
                    @endforeach
                </div>
                @endif

                {{-- MONTANT --}}
                <div class="montant-row">
                    Montant don/legs :
                    {{ number_format($donateur->montant_don ?? 0, 0, ',', ' ') }}
                    {{ $donateur->devise ?? 'XOF' }}
                </div>

                {{-- ✅ MODE LIBERATION SUR UNE SEULE LIGNE --}}
                <div class="mode-liberation">
                    Mode de libération :

                    <span class="mode-inline">
                        <span class="checkbox">
                            {{ ($donateur->mode_liberation == "espèces") ? "X" : "" }}
                        </span>
                        Espèces
                    </span>

                    <span class="mode-inline">
                        <span class="checkbox">
                            {{ ($donateur->mode_liberation == "chèque") ? "X" : "" }}
                        </span>
                        Chèque
                    </span>

                    <span class="mode-inline">
                        <span class="checkbox">
                            {{ ($donateur->mode_liberation == "virement") ? "X" : "" }}
                        </span>
                        Virement
                    </span>

                    <span class="mode-inline">
                        <span class="checkbox">
                            {{ ($donateur->mode_liberation == "nature") ? "X" : "" }}
                        </span>
                        Nature
                    </span>
                </div>

                {{-- SIGNATURE --}}
                <div class="signature">
                    Signature du représentant :
                </div>

            </div>

        @endforeach

        {{-- Saut de page --}}
        @if($currentPage < $totalPages)
            <div class="page-break"></div>
        @endif

    @endforeach

@else

    <p style="text-align:center; font-size:16px;">
        Aucun donateur trouvé.
    </p>

@endif

</body>
</html>
