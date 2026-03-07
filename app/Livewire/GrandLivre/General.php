<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class General extends Component
{
    public $entreprise;

    public $selectedAccounts = [];
    public $selectAll = false;
    public $tempSelectedAccounts = [];
    public $showModal = false;

    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
        'loadMore'          => 'loadMore',
    ];

    public $dateDebut;
    public $dateFin;
    public $exercice;

    public $perPage     = 50;
    public $loadedCount = 0;
    public $totalCount  = 0;
    public $hasMore     = true;
    public $isLoading   = false;
    public $exporting   = false;

    private ?object $cachedExo    = null;
    private array   $paginatedData = [];

    protected $queryString = [
        'dateDebut'        => ['except' => ''],
        'dateFin'          => ['except' => ''],
        'exercice'         => ['except' => ''],
        'selectedAccounts' => ['except' => []],
    ];

    // ─────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────

    public function mount(): void
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->dateDebut  = '2024-01-01';
        $this->dateFin    = '2024-12-31';
    }

    // ─────────────────────────────────────────────
    // Exercice actif
    // ─────────────────────────────────────────────

    private function getExo(): object
    {
        if ($this->cachedExo === null) {
            $this->cachedExo = DB::table('exercices')->where('statut', 1)->first();
        }
        return $this->cachedExo;
    }

    // ─────────────────────────────────────────────
    // Réactivité
    // ─────────────────────────────────────────────

    public function updated(string $property): void
    {
        if (in_array($property, ['dateDebut', 'dateFin', 'exercice'])) {
            $this->resetPagination();
        }
    }

    public function updatedSelectedAccounts(): void
    {
        $this->resetPagination();
    }

    public function updatedSelectAll(bool $value): void
    {
        $this->selectedAccounts = $value ? $this->getAllAccountCodes() : [];
        $this->resetPagination();
    }

    // ─────────────────────────────────────────────
    // Actions sélection (appelées depuis la vue via wire:click)
    // ─────────────────────────────────────────────

    //public function openModal(): void
    //{
     //   $this->showModal = true;
    //}
    
    public function openModal(): void
    {
        $this->tempSelectedAccounts = $this->selectedAccounts; // Initialiser avec la sélection actuelle
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function toggleAccount(string $code): void
    {
        /*if (in_array($code, $this->selectedAccounts)) {
            $this->selectedAccounts = array_values(
                array_filter($this->selectedAccounts, fn ($c) => $c !== $code)
            );
        } else {
            $this->selectedAccounts[] = $code;
        }
        $this->resetPagination();*/
         if (in_array($code, $this->tempSelectedAccounts)) {
        $this->tempSelectedAccounts = array_values(
            array_filter($this->tempSelectedAccounts, fn ($c) => $c !== $code)
        );
        } else {
            $this->tempSelectedAccounts[] = $code;
        }
    }

    public function selectAllAccounts(): void
    {
        $this->selectedAccounts = $this->getAllAccountCodes();
        //$this->resetPagination();
    }
    
    public function validateAccountSelection(): void
    {
        $this->selectedAccounts = $this->tempSelectedAccounts;
        $this->showModal = false;
        $this->resetPagination(); // Recharger les données avec la nouvelle sélection
    }

    public function clearSelectedAccounts(): void
    {
        $this->selectedAccounts = [];
        //$this->resetPagination();
    }

    private function getAllAccountCodes(): array
    {
        return $this->baseAccountQuery()
            ->select('na.code')
            ->distinct()
            ->orderBy('na.code')
            ->pluck('code')
            ->toArray();
    }

    // ─────────────────────────────────────────────
    // Pagination
    // ─────────────────────────────────────────────

    public function resetPagination(): void
    {
        $this->loadedCount   = 0;
        $this->hasMore       = true;
        $this->isLoading     = false;
        $this->paginatedData = [];
        $this->cachedExo     = null;
    }

    public function loadMore(): void
    {
        if ($this->isLoading || !$this->hasMore) return;

        $this->isLoading = true;
        $this->loadedCount += $this->perPage;
        $this->isLoading = false;

        if ($this->loadedCount >= $this->getTotalAccountsCount()) {
            $this->hasMore = false;
        }
    }

    // ─────────────────────────────────────────────
    // Requête de base partagée
    // ─────────────────────────────────────────────

    private function baseAccountQuery()
    {
        $exo = $this->getExo();

        $query = DB::table('grand_livres as gl')
            ->leftJoin('account_mappings as am', function ($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id ?? '')
            ->whereNotNull('na.id');

        $this->applyQueryFilters($query);

        if (!empty($this->selectedAccounts)) {
            $query->whereIn('na.code', $this->selectedAccounts);
        }

        return $query;
    }

    private function applyQueryFilters($query): void
    {
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('gl.date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
        if ($this->exercice) {
            $query->where('gl.exercice', $this->exercice);
        }
    }

    private function getTotalAccountsCount(): int
    {
        return (int) $this->baseAccountQuery()
            ->selectRaw('COUNT(DISTINCT na.code) as total')
            ->value('total');
    }

    // ─────────────────────────────────────────────
    // Données paginées
    // ─────────────────────────────────────────────

    private function getPaginatedData(): array
    {
        if (empty($this->paginatedData)) {
            $this->paginatedData = $this->fetchPaginatedData();
        }
        return $this->paginatedData;
    }

    private function fetchPaginatedData(): array
    {
        try {
            $accounts = $this->baseAccountQuery()
                ->select([
                    'na.code as new_account_code',
                    'na.intitule as new_account_intitule',
                    DB::raw('SUM(gl.debit) as total_debit'),
                    DB::raw('SUM(gl.credit) as total_credit'),
                    DB::raw('COUNT(gl.id) as nombre_ecritures'),
                ])
                ->groupBy('na.code', 'na.intitule')
                ->orderBy('na.code')
                ->limit($this->loadedCount)
                ->get();

            if ($accounts->isEmpty()) return [];

            $accountCodes = $accounts->pluck('new_account_code')->toArray();
            $ecritures    = $this->getEcrituresForAccounts($accountCodes);

            return $accounts->map(function ($account) use ($ecritures) {
                return [
                    'new_account_code'     => $account->new_account_code,
                    'new_account_intitule' => $account->new_account_intitule,
                    'ecritures'            => $ecritures->where('new_account_code', $account->new_account_code),
                    'total_debit'          => (float) $account->total_debit,
                    'total_credit'         => (float) $account->total_credit,
                    'nombre_ecritures'     => (int) $account->nombre_ecritures,
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Erreur fetchPaginatedData', ['error' => $e->getMessage()]);
            return [];
        }
    }

    private function getEcrituresForAccounts(array $accountCodes): Collection
    {
        $exo = $this->getExo();

        return DB::table('grand_livres as gl')
            ->select([
                'gl.id', 'gl.date_ecriture', 'gl.piece', 'gl.journal_code',
                'gl.libelle', 'gl.debit', 'gl.credit',
                'oa.code as old_account_code', 'oa.intitule as old_account_intitule',
                'na.code as new_account_code', 'na.intitule as new_account_intitule',
            ])
            ->leftJoin('account_mappings as am', function ($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id ?? '')
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id')
            ->tap(fn ($q) => $this->applyQueryFilters($q))
            ->orderBy('na.code')
            ->orderBy('gl.date_ecriture')
            ->orderBy('gl.id')
            ->get();
    }

    // ─────────────────────────────────────────────
    // Computed properties
    // ─────────────────────────────────────────────

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

    public function getRecapStatsProperty(): array
    {
        $data = $this->getPaginatedData();

        $stats = [
            'total_comptes'   => count($data),
            'total_ecritures' => 0,
            'total_debit'     => 0.0,
            'total_credit'    => 0.0,
        ];

        foreach ($data as $item) {
            $stats['total_ecritures'] += $item['nombre_ecritures'];
            $stats['total_debit']     += $item['total_debit'];
            $stats['total_credit']    += $item['total_credit'];
        }

        $solde = $stats['total_debit'] - $stats['total_credit'];

        return array_merge($stats, [
            'solde_global'        => $solde,
            'solde_global_absolu' => abs($solde),
            'is_debiteur'         => $solde > 0,
            'is_crediteur'        => $solde < 0,
            'is_equilibre'        => $solde == 0,
        ]);
    }

    public function getAccountsListProperty(): Collection
    {
        return $this->baseAccountQuery()
            ->select([
                'na.code', 'na.intitule',
                DB::raw('COUNT(gl.id) as total_ecritures'),
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit'),
            ])
            ->groupBy('na.code', 'na.intitule')
            ->orderBy('na.code')
            ->get()
            ->map(function ($item) {
                $item->total_ecritures = (int)  $item->total_ecritures;
                $item->total_debit     = (float) $item->total_debit;
                $item->total_credit    = (float) $item->total_credit;
                $item->solde           = $item->total_debit - $item->total_credit;
                return $item;
            });
    }

    // ─────────────────────────────────────────────
    // Reset filtres
    // ─────────────────────────────────────────────

    public function resetFilters(): void
    {
        $this->reset(['dateDebut', 'dateFin', 'exercice', 'selectedAccounts', 'selectAll']);
        $this->dateDebut = '2024-01-01';
        $this->dateFin   = '2024-12-31';
        $this->resetPagination();
    }

    // ─────────────────────────────────────────────
    // Export : données communes
    // ─────────────────────────────────────────────

    private function getExportData(): array
    {
        $exo = $this->getExo();

        $accounts = $this->baseAccountQuery()
            ->select([
                'na.code as new_account_code',
                'na.intitule as new_account_intitule',
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit'),
                DB::raw('COUNT(gl.id) as nombre_ecritures'),
            ])
            ->groupBy('na.code', 'na.intitule')
            ->orderBy('na.code')
            ->get();

        if ($accounts->isEmpty()) return [];

        $accountCodes = $accounts->pluck('new_account_code')->toArray();

        $allEcritures = DB::table('grand_livres as gl')
            ->select([
                'gl.date_ecriture', 'gl.piece', 'gl.journal_code',
                'gl.libelle', 'gl.debit', 'gl.credit',
                'na.code as new_account_code',
            ])
            ->leftJoin('account_mappings as am', function ($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id ?? '')
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id')
            ->tap(fn ($q) => $this->applyQueryFilters($q))
            ->orderBy('na.code')
            ->orderBy('gl.date_ecriture')
            ->get()
            ->groupBy('new_account_code');

        return $accounts->map(function ($account) use ($allEcritures) {
            $ecritures = $allEcritures->get($account->new_account_code, collect());

            return [
                'code'             => $account->new_account_code,
                'intitule'         => $account->new_account_intitule,
                'ecritures'        => $ecritures->map(fn ($e) => [
                    'date'    => $e->date_ecriture
                                    ? \Carbon\Carbon::parse($e->date_ecriture)->format('d/m/Y')
                                    : '',
                    'piece'   => $e->piece ?? '',
                    'journal' => $e->journal_code ?? '',
                    'libelle' => $e->libelle ?? '',
                    'debit'   => (float) $e->debit,
                    'credit'  => (float) $e->credit,
                ])->toArray(),
                'total_debit'      => (float) $account->total_debit,
                'total_credit'     => (float) $account->total_credit,
                'nombre_ecritures' => (int)   $account->nombre_ecritures,
            ];
        })->toArray();
    }

    // ─────────────────────────────────────────────
    // Export Excel
    // ─────────────────────────────────────────────

    public function exportExcel()
    {
        if ($this->exporting) return;
        $this->exporting = true;

        try {
            $data     = $this->getExportData();
            $fileName = 'grand_livre_' . $this->entreprise->code . '_' . date('Ymd_His') . '.xlsx';

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', 'GRAND LIVRE GÉNÉRAL');
            $sheet->mergeCells('A1:F1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheet->setCellValue('A2', $this->entreprise->nom . ' (' . $this->entreprise->code . ')');
            $sheet->mergeCells('A2:F2');

            $debut = $this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '';
            $fin   = $this->dateFin   ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y')   : '';
            $sheet->setCellValue('A3', "Période : $debut au $fin");
            $sheet->mergeCells('A3:F3');

            $row = 5;
            foreach ($data as $account) {
                $sheet->setCellValue('A' . $row, 'COMPTE ' . $account['code'] . ' — ' . $account['intitule']);
                $sheet->mergeCells("A{$row}:F{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D0D8E4');
                $row++;

                foreach (['A' => 'Date', 'B' => 'Pièce', 'C' => 'Journal', 'D' => 'Libellé', 'E' => 'Débit', 'F' => 'Crédit'] as $col => $label) {
                    $sheet->setCellValue($col . $row, $label);
                }
                $sheet->getStyle("A{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:F{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8ECF0');
                $row++;

                foreach ($account['ecritures'] as $e) {
                    $sheet->setCellValue('A' . $row, $e['date']);
                    $sheet->setCellValue('B' . $row, $e['piece']);
                    $sheet->setCellValue('C' . $row, $e['journal']);
                    $sheet->setCellValue('D' . $row, $e['libelle']);
                    if ($e['debit']  > 0) $sheet->setCellValue('E' . $row, $e['debit']);
                    if ($e['credit'] > 0) $sheet->setCellValue('F' . $row, $e['credit']);
                    $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
                    $row++;
                }

                $sheet->setCellValue("D{$row}", 'TOTAL ' . $account['code']);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->setCellValue("E{$row}", $account['total_debit']);
                $sheet->setCellValue("F{$row}", $account['total_credit']);
                $sheet->getStyle("E{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $row++;

                $solde = $account['total_debit'] - $account['total_credit'];
                $sheet->setCellValue("D{$row}", 'SOLDE ' . $account['code']);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                if ($solde > 0) { $sheet->setCellValue("E{$row}", abs($solde)); }
                else            { $sheet->setCellValue("F{$row}", abs($solde)); }
                $sheet->getStyle("E{$row}:F{$row}")->getFont()->setBold(true);
                $sheet->getStyle("E{$row}:F{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $row += 2;
            }

            foreach (range('A', 'F') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $tempFile = tempnam(sys_get_temp_dir(), 'gl_excel_');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tempFile);
            $this->exporting = false;

            return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Erreur export Excel', ['error' => $e->getMessage()]);
            $this->exporting = false;
            $this->addError('export', 'Erreur : ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────
    // Export PDF — corrigé page noire
    // ─────────────────────────────────────────────

    public function exportPdf()
    {
        if ($this->exporting) return;
        $this->exporting = true;

        try {
            // Vider tous les output buffers actifs
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            $data     = $this->getExportData();
            $totals   = $this->recapStats;
            $fileName = 'grand_livre_' . $this->entreprise->code . '_' . date('Ymd_His') . '.pdf';
            $html     = $this->generatePdfHtml($data, $totals);

            // Créer le dossier temp si nécessaire
            $tempDir = storage_path('app/temp_pdf/');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            $tempFile = $tempDir . uniqid('gl_', true) . '.pdf';

            // Instancier DomPDF via la façade
            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');

            // Options compatibles DomPDF — pas de DejaVu pour éviter la page noire
            $dompdf = $pdf->getDomPDF();
            $dompdf->set_option('defaultFont', 'helvetica');
            $dompdf->set_option('isRemoteEnabled', false);
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->set_option('isFontSubsettingEnabled', false);
            $dompdf->set_option('dpi', 96);
            $dompdf->set_option('debugKeepTemp', false);

            $pdf->save($tempFile);
            $this->exporting = false;

            return response()->download($tempFile, $fileName, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->exporting = false;
            $this->addError('export', 'Erreur PDF : ' . $e->getMessage());
            return null;
        }
    }

    // ─────────────────────────────────────────────
    // HTML du PDF — CSS minimal compatible DomPDF
    // ─────────────────────────────────────────────

    private function generatePdfHtml(array $data, array $totals): string
    {
        $debut  = $this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '-';
        $fin    = $this->dateFin   ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y')   : '-';
        $nom    = htmlspecialchars($this->entreprise->nom,  ENT_QUOTES, 'UTF-8');
        $code   = htmlspecialchars($this->entreprise->code, ENT_QUOTES, 'UTF-8');
        $genere = date('d/m/Y H:i');

        // CSS volontairement simple — DomPDF ne supporte pas tout le CSS3
        $css = '
            * { margin: 0; padding: 0; }
            body {
                font-family: helvetica, sans-serif;
                font-size: 7pt;
                color: #111;
                background: #fff;
            }
            .page-header {
                border-bottom: 2px solid #2c3e50;
                padding-bottom: 5px;
                margin-bottom: 10px;
            }
            .page-header h1 {
                font-size: 12pt;
                font-weight: bold;
                color: #2c3e50;
            }
            .page-header .meta {
                font-size: 7pt;
                color: #555;
                margin-top: 3px;
            }
            .account-block {
                margin-bottom: 10px;
                page-break-inside: avoid;
            }
            .account-title {
                background-color: #2c3e50;
                color: #fff;
                font-weight: bold;
                font-size: 8pt;
                padding: 3px 6px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                font-size: 7pt;
            }
            thead th {
                background-color: #495057;
                color: #fff;
                padding: 3px 5px;
                text-align: left;
                font-weight: bold;
                border: 1px solid #333;
            }
            th.right, td.right {
                text-align: right;
            }
            tbody td {
                padding: 2px 5px;
                border: 1px solid #ddd;
                background: #fff;
            }
            .row-even td {
                background-color: #f5f5f5;
            }
            .total-row td {
                background-color: #e0e0e0;
                font-weight: bold;
                border-top: 2px solid #999;
            }
            .solde-row td {
                font-weight: bold;
                background: #fff;
            }
            .solde-debit {
                color: #cc0000;
                background-color: #fff0f0;
            }
            .solde-credit {
                color: #006600;
                background-color: #f0fff0;
            }
            .summary-box {
                width: 60%;
                margin: 15px auto 0;
                border: 2px solid #2c3e50;
            }
            .summary-box th {
                background-color: #2c3e50;
                color: #fff;
                padding: 4px 8px;
                text-align: center;
                font-size: 8pt;
                border: none;
            }
            .summary-box td {
                padding: 3px 8px;
                border: 1px solid #ccc;
                font-size: 7.5pt;
                background: #fff;
            }
            .summary-highlight td {
                background-color: #dbeafe;
                font-weight: bold;
            }
            .footer {
                text-align: center;
                margin-top: 10px;
                font-size: 6.5pt;
                color: #777;
                border-top: 1px solid #ddd;
                padding-top: 4px;
            }
        ';

        $out  = '<!DOCTYPE html><html><head>';
        $out .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $out .= '<style>' . $css . '</style></head><body>';

        // En-tête
        $out .= '<div class="page-header">';
        $out .= '<h1>GRAND LIVRE G&Eacute;N&Eacute;RAL</h1>';
        $out .= '<div class="meta">';
        $out .= '<strong>' . $nom . '</strong>';
        $out .= ' | Code : ' . $code;
        $out .= ' | P&eacute;riode : ' . $debut . ' au ' . $fin;
        $out .= ' | G&eacute;n&eacute;r&eacute; le : ' . $genere;
        $out .= '</div></div>';

        // Données par compte
        foreach ($data as $account) {
            $solde      = $account['total_debit'] - $account['total_credit'];
            $isDebiteur = $solde > 0;

            $out .= '<div class="account-block">';
            $out .= '<div class="account-title">';
            $out .= 'Compte : ' . htmlspecialchars($account['code'],    ENT_QUOTES, 'UTF-8');
            $out .= ' - '       . htmlspecialchars($account['intitule'], ENT_QUOTES, 'UTF-8');
            $out .= ' (' . $account['nombre_ecritures'] . ' &eacute;criture(s))';
            $out .= '</div>';

            $out .= '<table>';
            $out .= '<thead><tr>';
            $out .= '<th width="9%">Date</th>';
            $out .= '<th width="8%">Pi&egrave;ce</th>';
            $out .= '<th width="6%">Journal</th>';
            $out .= '<th width="47%">Libell&eacute;</th>';
            $out .= '<th class="right" width="15%">D&eacute;bit</th>';
            $out .= '<th class="right" width="15%">Cr&eacute;dit</th>';
            $out .= '</tr></thead><tbody>';

            $i = 0;
            foreach ($account['ecritures'] as $e) {
                $cls = ($i % 2 === 1) ? ' class="row-even"' : '';
                $out .= '<tr' . $cls . '>';
                $out .= '<td>' . $e['date'] . '</td>';
                $out .= '<td>' . htmlspecialchars($e['piece'],   ENT_QUOTES, 'UTF-8') . '</td>';
                $out .= '<td>' . htmlspecialchars($e['journal'], ENT_QUOTES, 'UTF-8') . '</td>';
                $out .= '<td>' . htmlspecialchars($e['libelle'], ENT_QUOTES, 'UTF-8') . '</td>';
                $out .= '<td class="right">' . ($e['debit']  > 0 ? number_format($e['debit'],  0, ',', ' ') : '') . '</td>';
                $out .= '<td class="right">' . ($e['credit'] > 0 ? number_format($e['credit'], 0, ',', ' ') : '') . '</td>';
                $out .= '</tr>';
                $i++;
            }

            // Total
            $out .= '<tr class="total-row">';
            $out .= '<td colspan="4" class="right">TOTAL ' . htmlspecialchars($account['code'], ENT_QUOTES, 'UTF-8') . '</td>';
            $out .= '<td class="right">' . number_format($account['total_debit'],  0, ',', ' ') . '</td>';
            $out .= '<td class="right">' . number_format($account['total_credit'], 0, ',', ' ') . '</td>';
            $out .= '</tr>';

            // Solde
            $soldeF = number_format(abs($solde), 0, ',', ' ');
            $out .= '<tr class="solde-row">';
            $out .= '<td colspan="4" class="right">SOLDE ' . htmlspecialchars($account['code'], ENT_QUOTES, 'UTF-8') . '</td>';
            if ($isDebiteur) {
                $out .= '<td class="right solde-debit">' . $soldeF . '</td><td></td>';
            } else {
                $out .= '<td></td><td class="right solde-credit">' . $soldeF . '</td>';
            }
            $out .= '</tr>';

            $out .= '</tbody></table></div>';
        }

        // Récapitulatif global
        $soldeLabel = $totals['is_debiteur'] ? 'D&eacute;biteur' : 'Cr&eacute;diteur';
        $soldeColor = $totals['is_debiteur'] ? '#cc0000' : '#006600';

        $out .= '<table class="summary-box">';
        $out .= '<thead><tr><th colspan="2">R&Eacute;CAPITULATIF G&Eacute;N&Eacute;RAL</th></tr></thead>';
        $out .= '<tbody>';
        $out .= '<tr><td>Nombre de comptes</td><td class="right">' . $totals['total_comptes'] . '</td></tr>';
        $out .= '<tr class="row-even"><td>Nombre d\'&eacute;critures</td><td class="right">' . number_format($totals['total_ecritures'], 0, ',', ' ') . '</td></tr>';
        $out .= '<tr><td>Total D&eacute;bit</td><td class="right">' . number_format($totals['total_debit'], 0, ',', ' ') . '</td></tr>';
        $out .= '<tr class="row-even"><td>Total Cr&eacute;dit</td><td class="right">' . number_format($totals['total_credit'], 0, ',', ' ') . '</td></tr>';
        $out .= '<tr class="summary-highlight">';
        $out .= '<td>Solde Global</td>';
        $out .= '<td class="right" style="color:' . $soldeColor . '">';
        $out .= number_format($totals['solde_global_absolu'], 0, ',', ' ') . ' (' . $soldeLabel . ')';
        $out .= '</td></tr>';
        $out .= '</tbody></table>';

        $out .= '<div class="footer">Document g&eacute;n&eacute;r&eacute; automatiquement - ' . $nom . ' - ' . $genere . '</div>';
        $out .= '</body></html>';

        return $out;
    }

    // ─────────────────────────────────────────────
    // Render
    // ─────────────────────────────────────────────

    public function render()
    {
        return view('livewire.grand-livre.general', [
            'recapData'    => $this->recapData,
            'recapStats'   => $this->recapStats,
            'accountsList' => $this->accountsList,
            'loadedCount'  => $this->loadedCount,
            'totalCount'   => $this->totalCount,
            'hasMore'      => $this->hasMore,
            'isLoading'    => $this->isLoading,
            'exporting'    => $this->exporting,
        ])->layout('layouts.app');
    }
}
