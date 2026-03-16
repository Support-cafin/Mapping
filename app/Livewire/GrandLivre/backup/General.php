<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\GrandLivreGeneralExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class General extends Component
{
    public $entreprise;

    public $exportProgress = 0;
    public $exportMessage = '';

    // AJOUT : Sélection multiple
    public $selectedAccounts = [];
    public $selectAll = false;

    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
        'loadMore' => 'loadMore',
    ];

    // Filtres - SUPPRESSION de journalCode et search
    public $dateDebut;
    public $dateFin;
    public $exercice;

    // Pagination et infinite scroll
    public $perPage = 50;
    public $loadedCount = 0;
    public $totalCount = 0;
    public $hasMore = true;
    public $isLoading = false;
    public $exporting = false;

    // Données paginées
    private $paginatedData = [];

    // QueryString pour persistance
    protected $queryString = [
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'selectedAccounts' => ['except' => ''],
    ];

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;

        // Filtres par défaut
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = null;
    }

    public function updated($property)
    {
        $filterProperties = [
            'dateDebut', 'dateFin', 'exercice', 'selectedAccounts'
        ];

        if (in_array($property, $filterProperties)) {
            $this->resetPagination();
        }

        // Gérer la sélection/déselection de tous les comptes
        if ($property === 'selectAll') {
            if ($this->selectAll) {
                $this->selectedAccounts = $this->getAllAccountCodes();
            } else {
                $this->selectedAccounts = [];
            }
            $this->resetPagination();
        }
    }

    /**
     * Récupère tous les codes comptes disponibles
     */
    private function getAllAccountCodes(): array
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        return DB::table('grand_livres as gl')
            ->select('na.code')
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id')
            ->distinct()
            ->orderBy('na.code')
            ->pluck('code')
            ->toArray();
    }

    public function resetPagination()
    {
        $this->loadedCount = 0;
        $this->hasMore = true;
        $this->isLoading = false;
        $this->paginatedData = [];
    }

    public function loadMore()
    {
        if ($this->isLoading || !$this->hasMore) {
            return;
        }

        $this->isLoading = true;
        $this->loadedCount += $this->perPage;
        $this->isLoading = false;

        $totalAccounts = $this->getTotalAccountsCount();
        if ($this->loadedCount >= $totalAccounts) {
            $this->hasMore = false;
        }
    }

    /**
     * Compte le nombre total de comptes SYCEBNL avec filtres
     */
    private function getTotalAccountsCount(): int
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = DB::table('grand_livres as gl')
            ->select(DB::raw('COUNT(DISTINCT na.code) as total'))
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id');

        $this->applyQueryFilters($query);

        // Filtrer par comptes sélectionnés si nécessaire
        if (!empty($this->selectedAccounts)) {
            $query->whereIn('na.code', $this->selectedAccounts);
        }

        return $query->value('total') ?? 0;
    }

    /**
     * Récupère les données paginées
     */
    private function getPaginatedData(): array
    {
        if (empty($this->paginatedData)) {
            $this->paginatedData = $this->fetchPaginatedData();
        }

        return $this->paginatedData;
    }

    /**
     * Récupère les données paginées depuis la base
     */
    private function fetchPaginatedData(): array
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            // D'abord, récupérer les comptes SYCEBNL paginés
            $accountsQuery = DB::table('grand_livres as gl')
                ->select([
                    'na.code as new_account_code',
                    'na.intitule as new_account_intitule',
                    DB::raw('SUM(gl.debit) as total_debit'),
                    DB::raw('SUM(gl.credit) as total_credit'),
                    DB::raw('COUNT(gl.id) as nombre_ecritures')
                ])
                ->leftJoin('account_mappings as am', function($join) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                         ->where('am.entreprise_id', $this->entreprise->id);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->where('gl.entreprise_id', $this->entreprise->id)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id')
                ->groupBy('na.code', 'na.intitule');

            $this->applyQueryFilters($accountsQuery);

            // Filtrer par comptes sélectionnés si nécessaire
            if (!empty($this->selectedAccounts)) {
                $accountsQuery->whereIn('na.code', $this->selectedAccounts);
            }

            $accountsQuery->orderBy('na.code');

            // Pagination manuelle
            $accounts = $accountsQuery->limit($this->loadedCount)->get();

            if ($accounts->isEmpty()) {
                return [];
            }

            // Pour chaque compte, récupérer les écritures
            $result = [];
            $accountCodes = $accounts->pluck('new_account_code')->toArray();

            // Récupérer toutes les écritures pour ces comptes en une seule requête
            $ecritures = $this->getEcrituresForAccounts($accountCodes);

            foreach ($accounts as $account) {
                $accountEcritures = $ecritures->where('new_account_code', $account->new_account_code);

                $result[] = [
                    'new_account_code' => $account->new_account_code,
                    'new_account_intitule' => $account->new_account_intitule,
                    'ecritures' => $accountEcritures,
                    'total_debit' => (float) $account->total_debit,
                    'total_credit' => (float) $account->total_credit,
                    'nombre_ecritures' => (int) $account->nombre_ecritures,
                ];
            }

            return $result;

        } catch (\Exception $e) {
            \Log::error('Erreur fetchPaginatedData', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Récupère les écritures pour une liste de comptes
     */
    private function getEcrituresForAccounts(array $accountCodes): Collection
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = DB::table('grand_livres as gl')
            ->select([
                'gl.id',
                'gl.date_ecriture',
                'gl.piece',
                'gl.journal_code',
                'gl.libelle',
                'gl.debit',
                'gl.credit',
                'oa.code as old_account_code',
                'oa.intitule as old_account_intitule',
                'na.code as new_account_code',
                'na.intitule as new_account_intitule'
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id');

        $this->applyQueryFilters($query);
        $query->orderBy('na.code')
              ->orderBy('gl.date_ecriture')
              ->orderBy('gl.id');

        return $query->get();
    }

    /**
     * Applique les filtres à une requête DB
     */
    private function applyQueryFilters($query): void
    {
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('gl.date_ecriture', [$this->dateDebut, $this->dateFin]);
        }

        if ($this->exercice) {
            $query->where('gl.exercice', $this->exercice);
        }
    }

    /**
     * Propriété pour les données affichées
     */
    public function getRecapDataProperty(): array
    {
        if ($this->loadedCount === 0) {
            $this->loadedCount = $this->perPage;
        }

        $this->totalCount = $this->getTotalAccountsCount();

        if ($this->loadedCount >= $this->totalCount) {
            $this->hasMore = false;
        }

        return $this->getPaginatedData();
    }

    /**
     * Statistiques légères (calculées à la volée)
     */
    public function getRecapStatsProperty(): array
    {
        $data = $this->getPaginatedData();

        $stats = [
            'total_comptes' => count($data),
            'total_ecritures' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
        ];

        foreach ($data as $item) {
            $stats['total_ecritures'] += $item['nombre_ecritures'];
            $stats['total_debit'] += $item['total_debit'];
            $stats['total_credit'] += $item['total_credit'];
        }

        $soldeGlobal = $stats['total_debit'] - $stats['total_credit'];

        return array_merge($stats, [
            'solde_global' => $soldeGlobal,
            'solde_global_absolu' => abs($soldeGlobal),
            'is_debiteur' => $soldeGlobal > 0,
            'is_crediteur' => $soldeGlobal < 0,
            'is_equilibre' => $soldeGlobal == 0,
        ]);
    }

    public function resetFilters()
    {
        $this->reset([
            'dateDebut',
            'dateFin',
            'exercice',
            'selectedAccounts',
            'selectAll',
        ]);

        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';

        $this->resetPagination();
    }

    /**
     * Export Excel
     */
    public function exportExcel()
    {
        if ($this->exporting) {
            return;
        }

        $this->exporting = true;
        $this->exportProgress = 5;
        $this->exportMessage = 'Préparation de l\'export...';

        try {
            $filters = $this->getFiltersArray();
            $fileName = 'grand_livre_general_' . $this->entreprise->code . '_' . date('Ymd_His') . '.xlsx';

            // Récupérer les données
            $data = $this->getExportData();

            // Générer le HTML pour l'export
            $html = $this->generateExportHtml($data);

            // Utiliser PhpSpreadsheet pour générer l'Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Titre
            $sheet->setCellValue('A1', 'GRAND LIVRE GÉNÉRAL');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            // Informations
            $sheet->setCellValue('A2', $this->entreprise->nom . ' (' . $this->entreprise->code . ')');
            $sheet->mergeCells('A2:F2');

            $periode = 'Période: ' . ($this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '') .
                       ' au ' . ($this->dateFin ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y') : '');
            $sheet->setCellValue('A3', $periode);
            $sheet->mergeCells('A3:F3');

            $row = 5;

            foreach ($data as $account) {
                // En-tête compte
                $sheet->setCellValue('A' . $row, 'COMPTE: ' . $account['code'] . ' - ' . $account['intitule']);
                $sheet->mergeCells('A' . $row . ':F' . $row);
                $sheet->getStyle('A' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F0F0F0');
                $row++;

                // En-têtes colonnes
                $sheet->setCellValue('A' . $row, 'Date');
                $sheet->setCellValue('B' . $row, 'Pièce');
                $sheet->setCellValue('C' . $row, 'Journal');
                $sheet->setCellValue('D' . $row, 'Libellé');
                $sheet->setCellValue('E' . $row, 'Débit');
                $sheet->setCellValue('F' . $row, 'Crédit');
                $sheet->getStyle('A' . $row . ':F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('A' . $row . ':F' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E0E0E0');
                $row++;

                // Écritures
                foreach ($account['ecritures'] as $ecriture) {
                    $sheet->setCellValue('A' . $row, $ecriture['date']);
                    $sheet->setCellValue('B' . $row, $ecriture['piece']);
                    $sheet->setCellValue('C' . $row, $ecriture['journal']);
                    $sheet->setCellValue('D' . $row, $ecriture['libelle']);
                    $sheet->setCellValue('E' . $row, $ecriture['debit'] > 0 ? $ecriture['debit'] : '');
                    $sheet->setCellValue('F' . $row, $ecriture['credit'] > 0 ? $ecriture['credit'] : '');
                    $row++;
                }

                // Total compte
                $sheet->setCellValue('D' . $row, 'TOTAL ' . $account['code']);
                $sheet->getStyle('D' . $row)->getFont()->setBold(true);
                $sheet->setCellValue('E' . $row, $account['total_debit']);
                $sheet->setCellValue('F' . $row, $account['total_credit']);
                $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('E' . $row . ':F' . $row)->getNumberFormat()
                    ->setFormatCode('#,##0');
                $row++;

                // Solde compte
                $solde = $account['total_debit'] - $account['total_credit'];
                $sheet->setCellValue('D' . $row, 'SOLDE ' . $account['code']);
                $sheet->getStyle('D' . $row)->getFont()->setBold(true);
                if ($solde > 0) {
                    $sheet->setCellValue('E' . $row, abs($solde));
                } else {
                    $sheet->setCellValue('F' . $row, abs($solde));
                }
                $sheet->getStyle('E' . $row . ':F' . $row)->getFont()->setBold(true);
                $row += 2;
            }

            // Totaux généraux
            $totals = $this->recapStats;
            $row += 2;

            $sheet->setCellValue('C' . $row, 'TOTAUX GÉNÉRAUX');
            $sheet->mergeCells('C' . $row . ':D' . $row);
            $sheet->getStyle('C' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('C' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('FFFF00');
            $row++;

            $sheet->setCellValue('C' . $row, 'Total Débit:');
            $sheet->setCellValue('D' . $row, number_format($totals['total_debit'], 0, ',', ' '));
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;

            $sheet->setCellValue('C' . $row, 'Total Crédit:');
            $sheet->setCellValue('D' . $row, number_format($totals['total_credit'], 0, ',', ' '));
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $row++;

            $sheet->setCellValue('C' . $row, 'Solde Global:');
            $sheet->setCellValue('D' . $row, number_format($totals['solde_global_absolu'], 0, ',', ' ') .
                                 ' (' . ($totals['is_debiteur'] ? 'Débit' : 'Crédit') . ')');
            $sheet->getStyle('C' . $row . ':D' . $row)->getFont()->setBold(true);

            // Ajuster les largeurs
            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $this->exportProgress = 100;
            $this->exportMessage = 'Export terminé';

            // Sauvegarder dans un fichier temporaire
            $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFile);

            $this->exporting = false;

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Erreur export Excel', ['error' => $e->getMessage()]);
            $this->exporting = false;
            $this->addError('export', 'Erreur: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Export PDF simple
     */
    public function exportPdf()
    {

        if ($this->exporting) {
            return;
        }

        $this->exporting = true;

        try {
            // Nettoyer les buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Récupérer les données
            $data = $this->getExportData();
            $totals = $this->recapStats;
            $fileName = 'grand_livre_general_' . $this->entreprise->code . '_' . date('Ymd_His') . '.pdf';

            // Générer le HTML pour le PDF
            $html = $this->generatePdfHtml($data, $totals);

            // Générer le PDF
            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'helvetica',
                'isRemoteEnabled' => false,
                'dpi' => 72,
                'isHtml5ParserEnabled' => true,
            ]);

            // Sauvegarder dans un fichier temporaire
            $tempDir = storage_path('app/temp_pdf/');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $tempFile = $tempDir . uniqid('gl_', true) . '.pdf';
            $pdf->save($tempFile);

            $this->exporting = false;

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF', ['error' => $e->getMessage()]);
            $this->exporting = false;
            $this->addError('export', 'Erreur: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Récupère les données pour l'export
     */
    private function getExportData(): array
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();

        // Récupérer les comptes
        $accountsQuery = DB::table('grand_livres as gl')
            ->select([
                'na.code as new_account_code',
                'na.intitule as new_account_intitule',
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit'),
                DB::raw('COUNT(gl.id) as nombre_ecritures')
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id')
            ->groupBy('na.code', 'na.intitule');

        $this->applyQueryFilters($accountsQuery);

        if (!empty($this->selectedAccounts)) {
            $accountsQuery->whereIn('na.code', $this->selectedAccounts);
        }

        $accountsQuery->orderBy('na.code');
        $accounts = $accountsQuery->get();

        if ($accounts->isEmpty()) {
            return [];
        }

        $result = [];
        $accountCodes = $accounts->pluck('new_account_code')->toArray();

        // Récupérer les écritures pour tous les comptes
        $ecrituresQuery = DB::table('grand_livres as gl')
            ->select([
                'gl.date_ecriture',
                'gl.piece',
                'gl.journal_code',
                'gl.libelle',
                'gl.debit',
                'gl.credit',
                'na.code as new_account_code'
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id');

        $this->applyQueryFilters($ecrituresQuery);
        $ecrituresQuery->orderBy('na.code')
                       ->orderBy('gl.date_ecriture');

        $allEcritures = $ecrituresQuery->get();
        $groupedEcritures = $allEcritures->groupBy('new_account_code');

        foreach ($accounts as $account) {
            $accountEcritures = $groupedEcritures->get($account->new_account_code, collect());

            $ecrituresFormatted = [];
            foreach ($accountEcritures as $ecriture) {
                $ecrituresFormatted[] = [
                    'date' => $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '',
                    'piece' => $ecriture->piece ?? '',
                    'journal' => $ecriture->journal_code ?? '',
                    'libelle' => $ecriture->libelle ?? '',
                    'debit' => (float) $ecriture->debit,
                    'credit' => (float) $ecriture->credit,
                ];
            }

            $result[] = [
                'code' => $account->new_account_code,
                'intitule' => $account->new_account_intitule,
                'ecritures' => $ecrituresFormatted,
                'total_debit' => (float) $account->total_debit,
                'total_credit' => (float) $account->total_credit,
                'nombre_ecritures' => (int) $account->nombre_ecritures,
            ];
        }

        return $result;
    }

    /**
     * Génère le HTML pour l'export PDF
     */
    private function generatePdfHtml(array $data, array $totals): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Grand Livre Général</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 10px; background-color: #f8f9fa; }
                .header { text-align: center; margin-bottom: 15px; padding: 10px; background-color: #e9ecef; border-radius: 5px; }
                h1 { font-size: 16px; margin: 0 0 5px 0; color: #2c3e50; }
                .info { font-size: 11px; color: #495057; margin: 2px 0; }
                .account-header { background-color: #dee2e6; padding: 8px; margin: 15px 0 5px 0; font-weight: bold; font-size: 12px; border-radius: 3px; }
                table { width: 100%; border-collapse: collapse; margin: 5px 0; font-size: 10px; }
                th { background-color: #ced4da; padding: 6px; border: 1px solid #adb5bd; font-weight: bold; text-align: left; }
                td { padding: 4px; border: 1px solid #adb5bd; }
                .total-row { background-color: #e9ecef; font-weight: bold; }
                .solde-row { background-color: #f8f9fa; font-weight: bold; }
                .debit { text-align: right; }
                .credit { text-align: right; }
                .footer { text-align: center; margin-top: 20px; font-size: 9px; color: #6c757d; }
                .global-stats { background-color: #f8f9fa; padding: 10px; margin-top: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>GRAND LIVRE GÉNÉRAL</h1>
                <div class="info">' . htmlspecialchars($this->entreprise->nom) . ' (' . htmlspecialchars($this->entreprise->code) . ')</div>
                <div class="info">Période: ' . ($this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '') .
                                       ' au ' . ($this->dateFin ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y') : '') . '</div>
                <div class="info">Généré le: ' . date('d/m/Y H:i') . '</div>
            </div>';

        foreach ($data as $account) {
            $html .= '<div class="account-header">COMPTE ' . htmlspecialchars($account['code']) . ' - ' .
                     htmlspecialchars($account['intitule']) . ' (' . $account['nombre_ecritures'] . ' écritures)</div>';

            $html .= '<table>
                <thead>
                    <tr>
                        <th width="10%">Date</th>
                        <th width="8%">Pièce</th>
                        <th width="8%">Journal</th>
                        <th width="44%">Libellé</th>
                        <th width="15%">Débit</th>
                        <th width="15%">Crédit</th>
                    </tr>
                </thead>
                <tbody>';

            foreach ($account['ecritures'] as $ecriture) {
                $html .= '<tr>
                    <td>' . $ecriture['date'] . '</td>
                    <td>' . $ecriture['piece'] . '</td>
                    <td>' . $ecriture['journal'] . '</td>
                    <td>' . htmlspecialchars($ecriture['libelle']) . '</td>
                    <td class="debit">' . ($ecriture['debit'] > 0 ? number_format($ecriture['debit'], 0, ',', ' ') : '') . '</td>
                    <td class="credit">' . ($ecriture['credit'] > 0 ? number_format($ecriture['credit'], 0, ',', ' ') : '') . '</td>
                </tr>';
            }

            $solde = $account['total_debit'] - $account['total_credit'];

            $html .= '<tr class="total-row">
                <td colspan="4"><strong>TOTAL ' . $account['code'] . '</strong></td>
                <td class="debit"><strong>' . number_format($account['total_debit'], 0, ',', ' ') . '</strong></td>
                <td class="credit"><strong>' . number_format($account['total_credit'], 0, ',', ' ') . '</strong></td>
            </tr>';

            $html .= '<tr class="solde-row">
                <td colspan="4"><strong>SOLDE ' . $account['code'] . '</strong></td>';

            if ($solde > 0) {
                $html .= '<td class="debit"><strong>' . number_format($solde, 0, ',', ' ') . '</strong></td><td></td>';
            } else {
                $html .= '<td></td><td class="credit"><strong>' . number_format(abs($solde), 0, ',', ' ') . '</strong></td>';
            }

            $html .= '</tr></tbody></table>';
        }

        $html .= '<div class="global-stats">
            <table style="width: 60%; margin: 0 auto;">
                <tr>
                    <td><strong>Total Comptes:</strong></td>
                    <td>' . $totals['total_comptes'] . '</td>
                    <td><strong>Total Écritures:</strong></td>
                    <td>' . number_format($totals['total_ecritures'], 0, ',', ' ') . '</td>
                </tr>
                <tr>
                    <td><strong>Total Débit:</strong></td>
                    <td>' . number_format($totals['total_debit'], 0, ',', ' ') . '</td>
                    <td><strong>Total Crédit:</strong></td>
                    <td>' . number_format($totals['total_credit'], 0, ',', ' ') . '</td>
                </tr>
                <tr>
                    <td colspan="2"><strong>Solde Global:</strong></td>
                    <td colspan="2"><strong>' . number_format($totals['solde_global_absolu'], 0, ',', ' ') .
                                      ' (' . ($totals['is_debiteur'] ? 'Débit' : 'Crédit') . ')</strong></td>
                </tr>
            </table>
        </div>

        <div class="footer">
            Document généré automatiquement - Page 1/1
        </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Génère le HTML pour l'export Excel
     */
    private function generateExportHtml(array $data): string
    {
        $html = '<table>';
        $html .= '<tr><th>Date</th><th>Pièce</th><th>Journal</th><th>Compte</th><th>Libellé</th><th>Débit</th><th>Crédit</th></tr>';

        foreach ($data as $account) {
            $html .= '<tr><td colspan="7"><strong>COMPTE ' . $account['code'] . ' - ' . $account['intitule'] . '</strong></td></tr>';

            foreach ($account['ecritures'] as $ecriture) {
                $html .= '<tr>
                    <td>' . $ecriture['date'] . '</td>
                    <td>' . $ecriture['piece'] . '</td>
                    <td>' . $ecriture['journal'] . '</td>
                    <td>' . $account['code'] . '</td>
                    <td>' . htmlspecialchars($ecriture['libelle']) . '</td>
                    <td>' . ($ecriture['debit'] > 0 ? number_format($ecriture['debit'], 0, ',', ' ') : '') . '</td>
                    <td>' . ($ecriture['credit'] > 0 ? number_format($ecriture['credit'], 0, ',', ' ') : '') . '</td>
                </tr>';
            }

            $html .= '<tr>
                <td colspan="5"><strong>TOTAL ' . $account['code'] . '</strong></td>
                <td><strong>' . number_format($account['total_debit'], 0, ',', ' ') . '</strong></td>
                <td><strong>' . number_format($account['total_credit'], 0, ',', ' ') . '</strong></td>
            </tr>';

            $solde = $account['total_debit'] - $account['total_credit'];
            $html .= '<tr>
                <td colspan="5"><strong>SOLDE ' . $account['code'] . '</strong></td>';

            if ($solde > 0) {
                $html .= '<td><strong>' . number_format($solde, 0, ',', ' ') . '</strong></td><td></td>';
            } else {
                $html .= '<td></td><td><strong>' . number_format(abs($solde), 0, ',', ' ') . '</strong></td>';
            }

            $html .= '</tr><tr><td colspan="7">&nbsp;</td></tr>';
        }

        $html .= '</table>';
        return $html;
    }

    public function getFiltersArray(): array
    {
        return [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'selectedAccounts' => $this->selectedAccounts,
            'entreprise_id' => $this->entreprise->id,
        ];
    }

    /**
 * Récupère la liste de tous les comptes pour la sélection
 */
/**
 * Récupère la liste de tous les comptes pour la sélection
 */
public function getAccountsListProperty(): Collection
{
    $exo = DB::table('exercices')->where('statut', 1)->first();

    return DB::table('grand_livres as gl')
        ->select([
            'na.code',
            'na.intitule',
            DB::raw('COUNT(gl.id) as total_ecritures'),
            DB::raw('SUM(gl.debit) as total_debit'),
            DB::raw('SUM(gl.credit) as total_credit')
        ])
        ->leftJoin('account_mappings as am', function($join) {
            $join->on('gl.old_account_id', '=', 'am.old_account_id')
                 ->where('am.entreprise_id', $this->entreprise->id);
        })
        ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
        ->where('gl.entreprise_id', $this->entreprise->id)
        ->where('gl.exercice_id', $exo->id)
        ->whereNotNull('na.id')
        ->groupBy('na.code', 'na.intitule')
        ->orderBy('na.code')
        ->get()
        ->map(function($item) {
            $item->total_ecritures = (int) $item->total_ecritures;
            $item->total_debit = (float) $item->total_debit;
            $item->total_credit = (float) $item->total_credit;
            $item->solde = $item->total_debit - $item->total_credit;
            return $item;
        });
}

    public function render()
{
    return view('livewire.grand-livre.general', [
        'recapData' => $this->recapData,
        'recapStats' => $this->recapStats,
        'accountsList' => $this->accountsList, // AJOUTEZ CETTE LIGNE
        'loadedCount' => $this->loadedCount,
        'totalCount' => $this->totalCount,
        'hasMore' => $this->hasMore,
        'isLoading' => $this->isLoading,
        'exporting' => $this->exporting,
    ])->layout('layouts.app');
}
}
