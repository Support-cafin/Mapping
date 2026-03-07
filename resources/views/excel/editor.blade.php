<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur Excel des États Financiers</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- x-spreadsheet CSS -->
    <link href="https://unpkg.com/x-data-spreadsheet@1.1.9/dist/xspreadsheet.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
        }
        
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 15px 20px;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
        }
        
        .btn {
            border-radius: 5px;
            padding: 8px 15px;
            font-weight: 500;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background-color: #27ae60;
            border-color: #27ae60;
        }
        
        .btn-success:hover {
            background-color: #219653;
            border-color: #219653;
            transform: translateY(-2px);
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
        }
        
        .btn-info:hover {
            background-color: #138496;
            border-color: #138496;
            transform: translateY(-2px);
        }
        
        .btn-warning {
            background-color: #f39c12;
            border-color: #f39c12;
        }
        
        .btn-warning:hover {
            background-color: #e67e22;
            border-color: #e67e22;
            transform: translateY(-2px);
        }
        
        h2 {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 3px solid #3498db;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-control {
            border-radius: 5px;
            border: 1px solid #ddd;
            padding: 10px;
            transition: border 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        
        label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #2c3e50;
        }
        
        hr {
            margin: 25px 0;
            border-color: #eee;
        }
        
        #excelEditor {
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: white;
        }
        
        /* Animation pour les alertes */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .custom-alert {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
            min-width: 300px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .col-md-3, .col-md-9 {
                width: 100%;
                margin-bottom: 20px;
            }
            
            #excelEditor {
                height: 400px !important;
            }
        }
        
        /* Style pour la date */
        input[type="date"] {
            cursor: pointer;
        }
        
        /* Style pour les icônes */
        .fa-download, .fa-save {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid mt-4">
        <h2>Éditeur Excel des États Financiers</h2>
        
        <div class="row">
            <!-- Sidebar gauche - Modèles et contrôles -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <h5>Modèles</h5>
                    </div>
                    <div class="card-body">
                        <button class="btn btn-primary mb-2" id="btnBilan" onclick="loadTemplate('bilan')">
                            <i class="fas fa-balance-scale"></i> Bilan
                        </button>
                        <button class="btn btn-primary mb-2" id="btnCompteResultat" onclick="loadTemplate('compte_resultat')">
                            <i class="fas fa-chart-line"></i> Compte de résultat
                        </button>
                        <button class="btn btn-primary mb-2" id="btnFluxTresorerie" onclick="loadTemplate('flux_tresorerie')">
                            <i class="fas fa-money-bill-wave"></i> Flux de trésorerie
                        </button>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label for="dateCloture">
                                <i class="fas fa-calendar-alt"></i> Date de clôture
                            </label>
                            <input type="date" id="dateCloture" class="form-control" 
                                   value="2024-12-31">
                        </div>
                        
                        <button class="btn btn-success" id="btnFillData" onclick="fillFromDatabase()">
                            <i class="fas fa-database"></i> Remplir depuis la base
                        </button>
                        <button class="btn btn-info" id="btnDownload" onclick="downloadExcel()">
                            <i class="fas fa-download"></i> Télécharger
                        </button>
                        <button class="btn btn-warning" id="btnSave" onclick="saveToDatabase()">
                            <i class="fas fa-save"></i> Sauvegarder
                        </button>
                        
                        <hr>
                        
                        <div class="form-group">
                            <label for="fileName">
                                <i class="fas fa-file"></i> Nom du fichier
                            </label>
                            <input type="text" id="fileName" class="form-control" 
                                   value="etat_financier" placeholder="Nom du fichier">
                        </div>
                        
                        <div class="form-group">
                            <label for="exportFormat">
                                <i class="fas fa-file-export"></i> Format d'export
                            </label>
                            <select id="exportFormat" class="form-control">
                                <option value="json">JSON (x-spreadsheet)</option>
                                <option value="csv">CSV</option>
                                <option value="xlsx">Excel (XLSX)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Zone principale - Éditeur Excel -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Éditeur</h5>
                        <div>
                            <span id="cellInfo" class="badge bg-secondary">Cellule: A1</span>
                            <span id="sheetInfo" class="badge bg-info ms-2">Feuille 1</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="excelEditor" style="height: 600px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Zone pour les alertes -->
    <div id="alertContainer"></div>

    <!-- x-spreadsheet JS -->
    <script src="https://unpkg.com/x-data-spreadsheet@1.1.9/dist/xspreadsheet.js"></script>
    
    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        let spreadsheet = null;
        let currentTemplate = 'bilan';
        
        // Données simulées pour les templates
        const templates = {
            'bilan': {
                name: 'Bilan',
                data: [
                    { name: 'Feuille1', rows: {}, cols: { len: 6 } }
                ]
            },
            'compte_resultat': {
                name: 'Compte de résultat',
                data: [
                    { name: 'Feuille1', rows: {}, cols: { len: 6 } }
                ]
            },
            'flux_tresorerie': {
                name: 'Flux de trésorerie',
                data: [
                    { name: 'Feuille1', rows: {}, cols: { len: 6 } }
                ]
            }
        };
        
        // Données simulées pour la base
        const mockData = {
            '2024-12-31': {
                actif_immobilise: 150000,
                stocks: 25000,
                clients: 35000,
                banque: 45000,
                capitaux_propres: 120000,
                dettes: 80000,
                resultat: 25000
            },
            '2024-06-30': {
                actif_immobilise: 145000,
                stocks: 22000,
                clients: 30000,
                banque: 38000,
                capitaux_propres: 115000,
                dettes: 75000,
                resultat: 20000
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser l'éditeur Excel
            spreadsheet = x_spreadsheet('#excelEditor', {
                showToolbar: true,
                showGrid: true,
                showContextmenu: true,
                view: {
                    height: () => document.getElementById('excelEditor').clientHeight,
                    width: () => document.getElementById('excelEditor').clientWidth,
                },
                row: {
                    len: 50,
                    height: 25
                },
                col: {
                    len: 20,
                    width: 100
                }
            });
            
            // Charger un template par défaut
            loadTemplate('bilan');
            
            // Mettre à jour la date par défaut
            document.getElementById('dateCloture').value = new Date().toISOString().split('T')[0];
            
            // Écouter les changements de cellule
            spreadsheet.change(function(data) {
                updateCellInfo();
            });
            
            // Initialiser les infos de cellule
            updateCellInfo();
            
            // Événements pour les boutons de template
            highlightActiveButton('bilan');
        });
        
        function loadTemplate(template) {
            currentTemplate = template;
            
            // Mettre en évidence le bouton actif
            highlightActiveButton(template);
            
            // Afficher une alerte
            showAlert(`Chargement du modèle : ${templates[template].name}`, 'info');
            
            // Simuler un chargement de données
            setTimeout(() => {
                // Construire un template de base
                const data = getTemplateData(template);
                spreadsheet.loadData(data);
                showAlert(`Modèle "${templates[template].name}" chargé avec succès`, 'success');
            }, 500);
        }
        
        function getTemplateData(template) {
            let rows = {};
            
            if (template === 'bilan') {
                // Template Bilan
                rows = {
                    0: { cells: { 0: { text: 'BILAN', style: 0 } } },
                    1: { cells: { 0: { text: 'ACTIF', style: 1 }, 3: { text: 'PASSIF', style: 1 } } },
                    2: { cells: { 0: { text: 'Immobilisations', style: 2 }, 3: { text: 'Capitaux propres', style: 2 } } },
                    3: { cells: { 1: { text: 'Terrains' }, 4: { text: 'Capital social' } } },
                    4: { cells: { 1: { text: 'Bâtiments' }, 4: { text: 'Réserves' } } },
                    5: { cells: { 0: { text: 'Total Actif Immobilisé', style: 3 }, 2: { text: '=SUM(B4:B5)', style: 4 }, 
                                  3: { text: 'Total Capitaux propres', style: 3 }, 5: { text: '=SUM(E4:E5)', style: 4 } } },
                    7: { cells: { 0: { text: 'Actif Circulant', style: 2 }, 3: { text: 'Dettes', style: 2 } } },
                    8: { cells: { 1: { text: 'Stocks' }, 4: { text: 'Emprunts LT' } } },
                    9: { cells: { 1: { text: 'Clients' }, 4: { text: 'Fournisseurs' } } },
                    10: { cells: { 0: { text: 'Total Actif Circulant', style: 3 }, 2: { text: '=SUM(B8:B9)', style: 4 }, 
                                  3: { text: 'Total Dettes', style: 3 }, 5: { text: '=SUM(E8:E9)', style: 4 } } },
                    12: { cells: { 0: { text: 'TOTAL ACTIF', style: 5 }, 2: { text: '=B6+B11', style: 6 }, 
                                  3: { text: 'TOTAL PASSIF', style: 5 }, 5: { text: '=E6+E11', style: 6 } } }
                };
            } else if (template === 'compte_resultat') {
                // Template Compte de résultat
                rows = {
                    0: { cells: { 0: { text: 'COMPTE DE RÉSULTAT', style: 0 } } },
                    1: { cells: { 0: { text: 'Produits', style: 1 }, 3: { text: 'Charges', style: 1 } } },
                    2: { cells: { 1: { text: 'Ventes' }, 4: { text: 'Achats' } } },
                    3: { cells: { 1: { text: 'Produits financiers' }, 4: { text: 'Salaires' } } },
                    4: { cells: { 1: { text: 'Subventions' }, 4: { text: 'Loyers' } } },
                    6: { cells: { 0: { text: 'Total Produits', style: 3 }, 2: { text: '=SUM(B3:B5)', style: 4 }, 
                                  3: { text: 'Total Charges', style: 3 }, 5: { text: '=SUM(E3:E5)', style: 4 } } },
                    8: { cells: { 0: { text: 'RÉSULTAT', style: 5 }, 2: { text: '=C7-F7', style: 6 } } }
                };
            }
            
            return [{
                name: templates[template].name,
                rows: rows,
                cols: { len: 6 },
                styles: [
                    { align: 'center', bold: true, bgcolor: '#2c3e50', color: 'white', fontSize: 16 },
                    { align: 'left', bold: true, bgcolor: '#3498db', color: 'white' },
                    { align: 'left', bold: true, bgcolor: '#ecf0f1' },
                    { align: 'left', bold: true, bgcolor: '#f8f9fa' },
                    { align: 'right', bgcolor: '#f8f9fa' },
                    { align: 'left', bold: true, bgcolor: '#27ae60', color: 'white' },
                    { align: 'right', bold: true, bgcolor: '#27ae60', color: 'white' }
                ]
            }];
        }
        
        function fillFromDatabase() {
            const date = document.getElementById('dateCloture').value;
            
            showAlert(`Récupération des données pour le ${date}...`, 'info');
            
            // Simuler un appel API
            setTimeout(() => {
                const data = mockData[date] || mockData['2024-12-31'];
                
                if (currentTemplate === 'bilan') {
                    // Remplir le bilan
                    spreadsheet.cell(3, 2).value = data.actif_immobilise; // Terrains + Bâtiments
                    spreadsheet.cell(4, 2).value = 0; // Pour démo
                    spreadsheet.cell(8, 2).value = data.stocks;
                    spreadsheet.cell(9, 2).value = data.clients;
                    spreadsheet.cell(3, 5).value = data.capitaux_propres;
                    spreadsheet.cell(8, 5).value = data.dettes;
                    
                    spreadsheet.change();
                    
                    showAlert('Données du bilan chargées avec succès !', 'success');
                } else if (currentTemplate === 'compte_resultat') {
                    // Remplir le compte de résultat
                    spreadsheet.cell(2, 2).value = data.resultat * 1.5; // Ventes
                    spreadsheet.cell(4, 5).value = data.resultat * 0.8; // Charges
                    
                    spreadsheet.change();
                    
                    showAlert('Données du compte de résultat chargées avec succès !', 'success');
                }
            }, 1000);
        }
        
        function downloadExcel() {
            const data = spreadsheet.getData();
            const format = document.getElementById('exportFormat').value;
            const fileName = document.getElementById('fileName').value || 'etat_financier';
            const date = new Date().toISOString().split('T')[0];
            
            if (format === 'json') {
                // Télécharger en JSON
                const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${fileName}_${date}.json`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                showAlert('Fichier JSON téléchargé !', 'success');
            } else if (format === 'csv') {
                // Convertir en CSV (simplifié)
                let csv = '';
                const sheet = data[0];
                
                for (let i = 0; i < 30; i++) {
                    const row = sheet.rows[i];
                    let rowData = [];
                    
                    for (let j = 0; j < 6; j++) {
                        const cell = row?.cells?.[j];
                        rowData.push(cell?.text || '');
                    }
                    
                    csv += rowData.join(';') + '\n';
                }
                
                const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${fileName}_${date}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                showAlert('Fichier CSV téléchargé !', 'success');
            } else {
                // Pour XLSX, on utiliserait une bibliothèque comme SheetJS
                showAlert('Format XLSX nécessite SheetJS. Exporté en JSON à la place.', 'warning');
                downloadExcel(); // Retour en JSON
            }
        }
        
        function saveToDatabase() {
            const data = spreadsheet.getData();
            const date = document.getElementById('dateCloture').value;
            
            showAlert('Sauvegarde en cours...', 'info');
            
            // Simuler une sauvegarde
            setTimeout(() => {
                // Ici, vous feriez un appel fetch vers votre API
                // fetch('/api/save-etat-financier', {
                //     method: 'POST',
                //     headers: { 'Content-Type': 'application/json' },
                //     body: JSON.stringify({ date: date, data: data, type: currentTemplate })
                // })
                
                // Pour la démo, on simule juste
                console.log('Données à sauvegarder:', { date, data, type: currentTemplate });
                
                showAlert(`État financier sauvegardé pour le ${date} !`, 'success');
            }, 1500);
        }
        
        function highlightActiveButton(template) {
            // Réinitialiser tous les boutons
            document.querySelectorAll('.btn-primary').forEach(btn => {
                btn.classList.remove('active');
                btn.style.opacity = '0.8';
            });
            
            // Activer le bouton sélectionné
            let activeBtn;
            switch(template) {
                case 'bilan':
                    activeBtn = document.getElementById('btnBilan');
                    break;
                case 'compte_resultat':
                    activeBtn = document.getElementById('btnCompteResultat');
                    break;
                case 'flux_tresorerie':
                    activeBtn = document.getElementById('btnFluxTresorerie');
                    break;
            }
            
            if (activeBtn) {
                activeBtn.classList.add('active');
                activeBtn.style.opacity = '1';
                activeBtn.style.boxShadow = '0 0 0 0.2rem rgba(52, 152, 219, 0.5)';
            }
        }
        
        function updateCellInfo() {
            // Cette fonction mettrait à jour les infos de cellule
            // Pour la démo, on montre un texte statique
            document.getElementById('cellInfo').textContent = 'Cellule: Sélectionnée';
        }
        
        function showAlert(message, type = 'info') {
            const alertContainer = document.getElementById('alertContainer');
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show custom-alert`;
            alertDiv.role = 'alert';
            
            let icon = 'info-circle';
            if (type === 'success') icon = 'check-circle';
            if (type === 'warning') icon = 'exclamation-triangle';
            if (type === 'danger') icon = 'times-circle';
            
            alertDiv.innerHTML = `
                <i class="fas fa-${icon} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            alertContainer.appendChild(alertDiv);
            
            // Supprimer automatiquement après 5 secondes
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 300);
                }
            }, 5000);
        }
        
        // Fonctions supplémentaires pour la démo
        function demoAutoFill() {
            document.getElementById('dateCloture').value = '2024-12-31';
            loadTemplate('bilan');
            setTimeout(fillFromDatabase, 1000);
        }
        
        // Exposer les fonctions globalement
        window.loadTemplate = loadTemplate;
        window.fillFromDatabase = fillFromDatabase;
        window.downloadExcel = downloadExcel;
        window.saveToDatabase = saveToDatabase;
        window.demoAutoFill = demoAutoFill;
    </script>
</body>
</html>