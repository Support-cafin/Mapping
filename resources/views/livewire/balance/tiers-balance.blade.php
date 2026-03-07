<div>
    <!-- Filtres (ne seront pas imprimés car en dehors de printable-area) -->
    <div class="mb-4 bg-white p-4 rounded-lg shadow">
        <!-- Vos filtres existants -->
    </div>

    <!-- Zone imprimable -->
    <div id="printable-area">
        <!-- En-tête d'impression -->
        <div class="print-title" style="text-align: center; margin-bottom: 20px; display: none;">
            <h2 style="font-size: 18px; margin: 0;">Balance des Tiers</h2>  <!-- Bouton d'impression -->
    
            <p style="font-size: 12px; margin: 5px 0 0; color: #666;">
                Période du {{ \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') }} 
                au {{ \Carbon\Carbon::parse($dateFin)->format('d/m/Y') }}
            </p>
        </div>

        {{-- Affichage conditionnel --}}
        @if(isset($viewMode) && $viewMode === 'details' && isset($selectedTiers) && $selectedTiers)
            @include('livewire.balance.partials.tiers-details')
        @else
            @if(isset($balances) && count($balances) > 0)
                @include('livewire.balance.partials.tiers-table')
            @else
                <div class="bg-white rounded-lg shadow border p-8 text-center">
                    <!-- Message quand aucun compte -->
                </div>
            @endif
        @endif
    </div>

   

    <!-- Script amélioré -->
    <script>
    function imprimerBalance() {
        // Afficher l'en-tête d'impression
        const printTitle = document.querySelector('.print-title');
        if (printTitle) {
            printTitle.style.display = 'block';
        }
        
        // Créer une nouvelle fenêtre d'impression
        const printWindow = window.open('', '_blank');
        
        // Récupérer le contenu à imprimer
        const content = document.getElementById('printable-area').innerHTML;
        
        // Écrire le contenu dans la nouvelle fenêtre
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Balance des Tiers</title>
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        margin: 20px;
                        padding: 0;
                    }
                    table {
                        width: 100%;
                        border-collapse: collapse;
                        margin-top: 20px;
                    }
                    th {
                        background-color: #f3f4f6 !important;
                        border: 1px solid #ddd;
                        padding: 8px;
                        text-align: left;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                    }
                    td {
                        border: 1px solid #ddd;
                        padding: 8px;
                    }
                    .text-right { text-align: right; }
                    .font-bold { font-weight: bold; }
                    .text-red-600 { color: #dc2626; }
                    .text-green-600 { color: #059669; }
                    .text-blue-600 { color: #2563eb; }
                    .print-title {
                        text-align: center;
                        margin-bottom: 20px;
                    }
                    .print-title h2 {
                        font-size: 18px;
                        margin: 0;
                    }
                    .print-title p {
                        font-size: 12px;
                        margin: 5px 0 0;
                        color: #666;
                    }
                    
                    /* Cacher la colonne Actions et les boutons */
                    th:last-child,
                    td:last-child,
                    button,
                    .btn,
                    [class*="btn-"],
                    [class*="button"] {
                        display: none !important;
                    }
                    
                    /* Ajuster le colspan si nécessaire */
                    td[colspan] {
                        display: table-cell !important;
                    }
                    
                    @media print {
                        body { margin: 0; padding: 15px; }
                        
                        /* Forcer l'affichage des couleurs */
                        * {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>
                ${content}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Attendre le chargement puis imprimer
        printWindow.onload = function() {
            printWindow.print();
            
            // Revenir à la fenêtre principale
            setTimeout(function() {
                printWindow.close();
            }, 1000);
        };
        
        // Cacher l'en-tête après l'impression
        setTimeout(function() {
            if (printTitle) {
                printTitle.style.display = 'none';
            }
        }, 2000);
    }
    </script>

    <!-- Style pour l'impression -->
    <style>
        @media print {
            /* Cache tout sauf la zone imprimable */
            body * {
                visibility: hidden;
            }
            
            #printable-area, 
            #printable-area * {
                visibility: visible;
            }
            
            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                margin: 0;
                padding: 15px;
            }
            
            /* Affiche l'en-tête d'impression */
            .print-title {
                display: block !important;
            }
            
            /* Cache la colonne Actions et les boutons */
            th:last-child,
            td:last-child,
            button,
            .btn {
                display: none !important;
            }
            
            /* Ajuste les tableaux */
            table {
                width: 100% !important;
            }
            
            /* Évite les sauts de page inutiles */
            tr {
                page-break-inside: avoid;
            }
        }
        
        /* Cache l'en-tête à l'écran */
        .print-title {
            display: none;
        }
    </style>
</div>