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

    public $selectedAccounts     = [];
    public $selectAll            = false;
    public $tempSelectedAccounts = [];
    public $showModal            = false;

    public $dateDebut;
    public $dateFin;
    public $exercice;

    public $perPage      = 100;
    public $currentPage  = 1;
    public $totalCount   = 0;
    public $exporting    = false;

    private ?object     $cachedExo          = null;
    private array       $paginatedData       = [];
    private ?Collection $cachedAccountsList  = null;

    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
    ];

    protected $queryString = [
        'dateDebut'        => ['except' => ''],
        'dateFin'          => ['except' => ''],
        'exercice'         => ['except' => ''],
        'selectedAccounts' => ['except' => []],
        'currentPage'      => ['except' => 1],
    ];

    // ─────────────────────────────────────────────
    // Mount
    // ─────────────────────────────────────────────

    public function mount(): void
    {
         $exo = DB::table('exercices')->where('statut', 1)->first();
        // Filtres par défaut
        $this->entreprise = auth()->user()->entreprise;
        $this->dateDebut = $exo->date_debut ?? '2024-12-31';
        $this->dateFin = $exo->date_fin ?? '2024-12-31';
        //$this->dateDebut  = '2024-01-01';
        //$this->dateFin    = '2024-12-31';
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

    // ─────────────────────────────────────────────
    // Actions modal sélection comptes
    // ─────────────────────────────────────────────

    public function openModal(): void
    {
        $this->tempSelectedAccounts = $this->selectedAccounts;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function toggleAccount(string $code): void
    {
        if (in_array($code, $this->tempSelectedAccounts)) {
            $this->tempSelectedAccounts = array_values(
                array_filter($this->tempSelectedAccounts, fn($c) => $c !== $code)
            );
        } else {
            $this->tempSelectedAccounts[] = $code;
        }
    }

    public function selectAllAccounts(): void
    {
        $this->tempSelectedAccounts = $this->getAllAccountCodes();
    }

    public function clearSelectedAccounts(): void
    {
        $this->selectedAccounts     = [];
        $this->tempSelectedAccounts = [];
        $this->resetPagination();
    }

    public function validateAccountSelection(): void
    {
        $this->selectedAccounts = $this->tempSelectedAccounts;
        $this->showModal        = false;
        $this->resetPagination();
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
    // Pagination numérotée
    // ─────────────────────────────────────────────

    public function goToPage(int $page): void
    {
        $this->currentPage   = max(1, min($page, $this->getTotalPages()));
        $this->paginatedData = [];
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->getTotalPages()) {
            $this->currentPage++;
            $this->paginatedData = [];
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->paginatedData = [];
        }
    }

    private function getTotalPages(): int
    {
        $total = $this->getTotalAccountsCount();
        return max(1, (int) ceil($total / $this->perPage));
    }

    public function resetPagination(): void
    {
        $this->currentPage        = 1;
        $this->paginatedData      = [];
        $this->cachedExo          = null;
        $this->cachedAccountsList = null;
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
            $offset = ($this->currentPage - 1) * $this->perPage;

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
                ->offset($offset)
                ->limit($this->perPage)
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
            ->tap(fn($q) => $this->applyQueryFilters($q))
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
        $this->totalCount = $this->getTotalAccountsCount();
        return $this->getPaginatedData();
    }

    public function getRecapStatsProperty(): array
    {
        $data = $this->getPaginatedData();

        $stats = [
            'total_comptes'   => $this->totalCount,
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
        if ($this->cachedAccountsList === null) {
            $this->cachedAccountsList = $this->baseAccountQuery()
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
                    $item->total_ecritures = (int)   $item->total_ecritures;
                    $item->total_debit     = (float)  $item->total_debit;
                    $item->total_credit    = (float)  $item->total_credit;
                    $item->solde           = $item->total_debit - $item->total_credit;
                    return $item;
                });
        }
        return $this->cachedAccountsList;
    }

    // ─────────────────────────────────────────────
    // Reset filtres
    // ─────────────────────────────────────────────

    public function resetFilters(): void
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Filtres par défaut
        
        $this->reset(['dateDebut', 'dateFin', 'exercice', 'selectedAccounts', 'selectAll']);
        //$this->dateDebut = '2024-01-01';
        //$this->dateFin   = '2024-12-31';
        $this->dateDebut = $exo->date_debut ?? '2024-12-31';
        $this->dateFin = $exo->date_fin ?? '2024-12-31';
        $this->resetPagination();
    }

    // ─────────────────────────────────────────────
    // Export : données communes
    // ─────────────────────────────────────────────

    private function getExportData(): array
    {
        try {
            Log::info('=== EXPORT COMPTES MAPPÉS ===');

            $exo = $this->getExo();

            $mappings = DB::table('account_mappings as am')
                ->select([
                    'am.id as mapping_id',
                    'am.old_account_id',
                    'am.new_account_id',
                    'na.code as new_code',
                    'na.intitule as new_intitule',
                    'oa.code as old_code',
                    'oa.intitule as old_intitule',
                ])
                ->join('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->join('old_accounts as oa', 'am.old_account_id', '=', 'oa.id')
                ->where('am.entreprise_id', $this->entreprise->id)
                ->orderBy('na.code')
                ->get();

            Log::info('Nombre de mappings trouvés: ' . $mappings->count());

            if ($mappings->isEmpty()) {
                Log::warning('Aucun mapping trouvé');
                return [];
            }

            $oldAccountIds = $mappings->pluck('old_account_id')->toArray();

            $ecrituresQuery = DB::table('grand_livres as gl')
                ->select(['gl.*', 'oa.code as old_code', 'oa.intitule as old_intitule'])
                ->join('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
                ->where('gl.entreprise_id', $this->entreprise->id)
                ->where('gl.exercice_id', $exo->id ?? '')
                ->whereIn('gl.old_account_id', $oldAccountIds);

            if ($this->dateDebut && $this->dateFin) {
                $ecrituresQuery->whereBetween('gl.date_ecriture', [$this->dateDebut, $this->dateFin]);
            }

            if (!empty($this->selectedAccounts)) {
                $selectedOldIds = DB::table('account_mappings as am')
                    ->join('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                    ->where('am.entreprise_id', $this->entreprise->id)
                    ->whereIn('na.code', $this->selectedAccounts)
                    ->pluck('am.old_account_id')
                    ->toArray();
                $ecrituresQuery->whereIn('gl.old_account_id', $selectedOldIds);
            }

            $allEcritures = $ecrituresQuery->orderBy('gl.date_ecriture')->get();

            Log::info('Nombre total d\'écritures trouvées: ' . $allEcritures->count());

            if ($allEcritures->isEmpty()) {
                return [];
            }

            $mappingByOldId = [];
            foreach ($mappings as $mapping) {
                $mappingByOldId[$mapping->old_account_id] = [
                    'new_code'     => $mapping->new_code,
                    'new_intitule' => $mapping->new_intitule,
                    'old_code'     => $mapping->old_code,
                    'old_intitule' => $mapping->old_intitule,
                ];
            }

            $groupedByNewAccount = [];

            foreach ($allEcritures as $ecriture) {
                $oldAccountId = $ecriture->old_account_id;

                if (!isset($mappingByOldId[$oldAccountId])) continue;

                $newInfo = $mappingByOldId[$oldAccountId];
                $newCode = $newInfo['new_code'];

                if (!isset($groupedByNewAccount[$newCode])) {
                    $groupedByNewAccount[$newCode] = [
                        'new_code'         => $newInfo['new_code'],
                        'new_intitule'     => $newInfo['new_intitule'],
                        'old_code'         => $newInfo['old_code'],
                        'old_intitule'     => $newInfo['old_intitule'],
                        'ecritures'        => [],
                        'total_debit'      => 0,
                        'total_credit'     => 0,
                        'nombre_ecritures' => 0,
                    ];
                }

                $groupedByNewAccount[$newCode]['ecritures'][] = [
                    'date'                 => $ecriture->date_ecriture
                                                ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y')
                                                : '',
                    'piece'                => $ecriture->piece ?? '',
                    'journal'              => $ecriture->journal_code ?? '',
                    'old_account_code'     => $ecriture->old_code,
                    'old_account_intitule' => $ecriture->old_intitule,
                    'libelle'              => $ecriture->libelle ?? '',
                    'debit'                => (float) $ecriture->debit,
                    'credit'               => (float) $ecriture->credit,
                ];

                $groupedByNewAccount[$newCode]['total_debit']      += (float) $ecriture->debit;
                $groupedByNewAccount[$newCode]['total_credit']     += (float) $ecriture->credit;
                $groupedByNewAccount[$newCode]['nombre_ecritures']++;
            }

            ksort($groupedByNewAccount);

            return array_values($groupedByNewAccount);

        } catch (\Exception $e) {
            Log::error('Erreur dans getExportData: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
            return [];
        }
    }

    // ─────────────────────────────────────────────
    // Export Excel
    // ─────────────────────────────────────────────

    public function exportExcel()
    {
        if ($this->exporting) return;
        $this->exporting = true;

        try {
            $data = $this->getExportData();

            if (empty($data)) {
                $this->addError('export', 'Aucune donnée à exporter');
                $this->exporting = false;
                return null;
            }

            $fileName    = 'grand_livre_' . $this->entreprise->code . '_' . date('Ymd_His') . '.xlsx';
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();

            // En-tête du document
            $sheet->setCellValue('A1', 'GRAND LIVRE GÉNÉRAL');
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $sheet->setCellValue('A2', $this->entreprise->nom . ' (' . $this->entreprise->code . ')');
            $sheet->mergeCells('A2:H2');

            $debut = $this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '';
            $fin   = $this->dateFin   ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y')   : '';
            $sheet->setCellValue('A3', "Période : $debut au $fin");
            $sheet->mergeCells('A3:H3');

            $row             = 5;
            $grandTotalDebit = 0;
            $grandTotalCredit = 0;

            foreach ($data as $account) {
                // En-tête compte SYCEBNL
                $sheet->setCellValue('A' . $row, 'COMPTE SYCEBNL ' . $account['new_code'] . ' — ' . $account['new_intitule']);
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle("A{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('D0D8E4');
                $row++;

                // Compte entité associé
                //$sheet->setCellValue('A' . $row, 'Compte Entité associé : ' . $account['old_code']);
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}")->getFont()->setItalic(true);
                $row++;

                // En-têtes colonnes
                $headers = ['Date', 'Pièce', 'Journal', 'Compte Entité', 'Libellé', 'Débit', 'Crédit'];
                $cols    = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
                foreach ($cols as $index => $col) {
                    $sheet->setCellValue($col . $row, $headers[$index]);
                    if (in_array($col, ['F', 'G'])) {
                        $sheet->getStyle($col . $row)->getAlignment()
                            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    }
                }
                $sheet->getStyle("A{$row}:G{$row}")->getFont()->setBold(true);
                $sheet->getStyle("A{$row}:G{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E8ECF0');
                $row++;

                // Lignes d'écritures
                // Remplacez le bloc "Lignes d'écritures" par ceci :
                foreach ($account['ecritures'] as $e) {
                    $libelle = (string) ($e['libelle'] ?? '');
                    if (preg_match('/^[><=+@-]/', $libelle)) {
                        \Log::warning('LIBELLÉ PROBLÉMATIQUE TROUVÉ', [
                            'libelle'          => $libelle,
                            'compte'           => $account['new_code'],
                            'date'             => $e['date'],
                            'piece'            => $e['piece'],
                        ]);
                    }
                    // ✅ setCellValueExplicit pour toutes les cellules texte
                    $sheet->setCellValueExplicit(
                        'A' . $row, 
                        $e['date'],
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $sheet->setCellValueExplicit(
                        'B' . $row, 
                        (string) ($e['piece'] ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $sheet->setCellValueExplicit(
                        'C' . $row, 
                        (string) ($e['journal'] ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $sheet->setCellValueExplicit(
                        'D' . $row, 
                        (string) ($e['old_account_code'] ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $sheet->getStyle('D' . $row)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    
                    // ✅ COLONNE E = libellé — c'est ici que le '>' se trouve !
                    $sheet->setCellValueExplicit(
                        'E' . $row, 
                        (string) ($e['libelle'] ?? ''),
                        \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    );
                    $sheet->getStyle('E' . $row)->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
                    
                    // ✅ Colonnes numériques — garder setCellValue normale
                    if ($e['debit'] > 0) {
                        $sheet->setCellValue('F' . $row, $e['debit']);
                        $sheet->getStyle('F' . $row)->getAlignment()
                            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    }
                    if ($e['credit'] > 0) {
                        $sheet->setCellValue('G' . $row, $e['credit']);
                        $sheet->getStyle('G' . $row)->getAlignment()
                            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    }
                    
                    $sheet->getStyle("F{$row}:G{$row}")
                          ->getNumberFormat()
                          ->setFormatCode('#,##0');
                    $row++;
                }

                // Total compte
                $sheet->setCellValue("D{$row}", 'TOTAL ' . $account['new_code']);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $sheet->setCellValue("F{$row}", $account['total_debit']);
                $sheet->setCellValue("G{$row}", $account['total_credit']);
                $sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);
                $sheet->getStyle("F{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle("F{$row}:G{$row}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                $row++;

                // Solde compte
                $solde = $account['total_debit'] - $account['total_credit'];
                $sheet->setCellValue("D{$row}", 'SOLDE ' . $account['new_code']);
                $sheet->getStyle("D{$row}")->getFont()->setBold(true);
                $sheet->getStyle("D{$row}")->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                if ($solde > 0) {
                    $sheet->setCellValue("F{$row}", abs($solde));
                    $sheet->getStyle("F{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                    $sheet->setCellValue("G{$row}", '');
                } else {
                    $sheet->setCellValue("F{$row}", '');
                    $sheet->setCellValue("G{$row}", abs($solde));
                    $sheet->getStyle("G{$row}")->getAlignment()
                        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
                }
                $sheet->getStyle("F{$row}:G{$row}")->getFont()->setBold(true);
                $sheet->getStyle("F{$row}:G{$row}")->getNumberFormat()->setFormatCode('#,##0');
                $row += 2;

                $grandTotalDebit  += $account['total_debit'];
                $grandTotalCredit += $account['total_credit'];
            }

            // Récapitulatif général
            $row++;
            $sheet->setCellValue('A' . $row, 'RÉCAPITULATIF GÉNÉRAL');
            $sheet->mergeCells('A' . $row . ':G' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('D9E1F2');
            $row++;

            $sheet->setCellValue('D' . $row, 'TOTAL GÉNÉRAL DÉBIT');
            $sheet->getStyle('D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('F' . $row, $grandTotalDebit);
            $sheet->getStyle('F' . $row)->getFont()->setBold(true);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('F' . $row)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $row++;

            $sheet->setCellValue('D' . $row, 'TOTAL GÉNÉRAL CRÉDIT');
            $sheet->getStyle('D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $sheet->setCellValue('G' . $row, $grandTotalCredit);
            $sheet->getStyle('G' . $row)->getFont()->setBold(true);
            $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('G' . $row)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            $row++;

            $grandTotalSolde = $grandTotalDebit - $grandTotalCredit;
            $sheet->setCellValue('D' . $row, 'SOLDE GÉNÉRAL');
            $sheet->getStyle('D' . $row)->getFont()->setBold(true);
            $sheet->getStyle('D' . $row)->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            if ($grandTotalSolde > 0) {
                $sheet->setCellValue('F' . $row, abs($grandTotalSolde));
                $sheet->getStyle('F' . $row)->getFont()->setBold(true);
                $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('F' . $row)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            } else {
                $sheet->setCellValue('G' . $row, abs($grandTotalSolde));
                $sheet->getStyle('G' . $row)->getFont()->setBold(true);
                $sheet->getStyle('G' . $row)->getNumberFormat()->setFormatCode('#,##0');
                $sheet->getStyle('G' . $row)->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
            }
            $sheet->getStyle('D' . $row . ':G' . $row)->getBorders()->getTop()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);
            $sheet->getStyle('D' . $row . ':G' . $row)->getBorders()->getBottom()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_DOUBLE);

            // Largeurs colonnes
            foreach (['A' => 12, 'B' => 15, 'C' => 10, 'D' => 35, 'E' => 45, 'F' => 18, 'G' => 18] as $col => $width) {
                $sheet->getColumnDimension($col)->setWidth($width);
            }
            $sheet->getStyle('F:G')->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

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
    // Export PDF
    // ─────────────────────────────────────────────

    public function exportPdf()
    {
        if ($this->exporting) return;
        $this->exporting = true;

        try {
            while (ob_get_level() > 0) ob_end_clean();

            $data     = $this->getExportData();
            $totals   = $this->recapStats;
            $fileName = 'grand_livre_' . $this->entreprise->code . '_' . date('Ymd_His') . '.pdf';
            $html     = $this->generatePdfHtml($data, $totals);

            $tempDir = storage_path('app/temp_pdf/');
            if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
            $tempFile = $tempDir . uniqid('gl_', true) . '.pdf';

            $pdf    = Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');
            $dompdf = $pdf->getDomPDF();
            $dompdf->set_option('defaultFont', 'helvetica');
            $dompdf->set_option('isRemoteEnabled', false);
            $dompdf->set_option('isHtml5ParserEnabled', true);
            $dompdf->set_option('isFontSubsettingEnabled', false);
            $dompdf->set_option('dpi', 96);

            $pdf->save($tempFile);
            $this->exporting = false;

            return response()->download($tempFile, $fileName, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Erreur export PDF', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->exporting = false;
            $this->addError('export', 'Erreur PDF : ' . $e->getMessage());
            return null;
        }
    }

    private function generatePdfHtml(array $data, array $totals): string
    {
        $debut  = $this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '-';
        $fin    = $this->dateFin   ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y')   : '-';
        $nom    = htmlspecialchars($this->entreprise->nom,  ENT_QUOTES, 'UTF-8');
        $code   = htmlspecialchars($this->entreprise->code, ENT_QUOTES, 'UTF-8');
        $genere = date('d/m/Y H:i');

        $css = '
            * { margin: 0; padding: 0; }
            body { font-family: helvetica, sans-serif; font-size: 7pt; color: #111; background: #fff; }
            .page-header { border-bottom: 2px solid #2c3e50; padding-bottom: 5px; margin-bottom: 10px; }
            .page-header h1 { font-size: 12pt; font-weight: bold; color: #2c3e50; }
            .page-header .meta { font-size: 7pt; color: #555; margin-top: 3px; }
            .account-block { margin-bottom: 10px; page-break-inside: avoid; }
            .account-title { background-color: #2c3e50; color: #fff; font-weight: bold; font-size: 8pt; padding: 3px 6px; }
            table { width: 100%; border-collapse: collapse; font-size: 7pt; }
            thead th { background-color: #495057; color: #fff; padding: 3px 5px; text-align: left; font-weight: bold; border: 1px solid #333; }
            th.right, td.right { text-align: right; }
            tbody td { padding: 2px 5px; border: 1px solid #ddd; background: #fff; }
            .row-even td { background-color: #f5f5f5; }
            .total-row td { background-color: #e0e0e0; font-weight: bold; border-top: 2px solid #999; }
            .solde-row td { font-weight: bold; background: #fff; }
            .solde-debit { color: #cc0000; background-color: #fff0f0; }
            .solde-credit { color: #006600; background-color: #f0fff0; }
            .summary-box { width: 60%; margin: 15px auto 0; border: 2px solid #2c3e50; }
            .summary-box th { background-color: #2c3e50; color: #fff; padding: 4px 8px; text-align: center; font-size: 8pt; border: none; }
            .summary-box td { padding: 3px 8px; border: 1px solid #ccc; font-size: 7.5pt; background: #fff; }
            .summary-highlight td { background-color: #dbeafe; font-weight: bold; }
            .footer { text-align: center; margin-top: 10px; font-size: 6.5pt; color: #777; border-top: 1px solid #ddd; padding-top: 4px; }
        ';

        $out  = '<!DOCTYPE html><html><head>';
        $out .= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>';
        $out .= '<style>' . $css . '</style></head><body>';
        $out .= '<div class="page-header">';
        $out .= '<h1>GRAND LIVRE G&Eacute;N&Eacute;RAL</h1>';
        $out .= '<div class="meta"><strong>' . $nom . '</strong> | Code : ' . $code;
        $out .= ' | P&eacute;riode : ' . $debut . ' au ' . $fin . ' | G&eacute;n&eacute;r&eacute; le : ' . $genere . '</div></div>';

        foreach ($data as $account) {
            $solde      = $account['total_debit'] - $account['total_credit'];
            $isDebiteur = $solde > 0;

            $out .= '<div class="account-block">';
            $out .= '<div class="account-title">Compte : ' . htmlspecialchars($account['new_code'] ?? $account['code'] ?? '', ENT_QUOTES, 'UTF-8');
            $out .= ' - ' . htmlspecialchars($account['new_intitule'] ?? $account['intitule'] ?? '', ENT_QUOTES, 'UTF-8');
            $out .= ' (' . ($account['nombre_ecritures'] ?? 0) . ' &eacute;criture(s))</div>';
            $out .= '<table><thead><tr>';
            $out .= '<th width="9%">Date</th><th width="8%">Pi&egrave;ce</th><th width="6%">Journal</th>';
            $out .= '<th width="47%">Libell&eacute;</th><th class="right" width="15%">D&eacute;bit</th><th class="right" width="15%">Cr&eacute;dit</th>';
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

            $out .= '<tr class="total-row"><td colspan="4" class="right">TOTAL ' . htmlspecialchars($account['new_code'] ?? $account['code'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            $out .= '<td class="right">' . number_format($account['total_debit'],  0, ',', ' ') . '</td>';
            $out .= '<td class="right">' . number_format($account['total_credit'], 0, ',', ' ') . '</td></tr>';

            $soldeF = number_format(abs($solde), 0, ',', ' ');
            $out .= '<tr class="solde-row"><td colspan="4" class="right">SOLDE ' . htmlspecialchars($account['new_code'] ?? $account['code'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>';
            if ($isDebiteur) {
                $out .= '<td class="right solde-debit">' . $soldeF . '</td><td></td>';
            } else {
                $out .= '<td></td><td class="right solde-credit">' . $soldeF . '</td>';
            }
            $out .= '</tr></tbody></table></div>';
        }

        $soldeLabel = $totals['is_debiteur'] ? 'D&eacute;biteur' : 'Cr&eacute;diteur';
        $soldeColor = $totals['is_debiteur'] ? '#cc0000' : '#006600';
        $out .= '<table class="summary-box"><thead><tr><th colspan="2">R&Eacute;CAPITULATIF G&Eacute;N&Eacute;RAL</th></tr></thead><tbody>';
        $out .= '<tr><td>Nombre de comptes</td><td class="right">' . $totals['total_comptes'] . '</td></tr>';
        $out .= '<tr class="row-even"><td>Nombre d\'&eacute;critures</td><td class="right">' . number_format($totals['total_ecritures'], 0, ',', ' ') . '</td></tr>';
        $out .= '<tr><td>Total D&eacute;bit</td><td class="right">' . number_format($totals['total_debit'], 0, ',', ' ') . '</td></tr>';
        $out .= '<tr class="row-even"><td>Total Cr&eacute;dit</td><td class="right">' . number_format($totals['total_credit'], 0, ',', ' ') . '</td></tr>';
        $out .= '<tr class="summary-highlight"><td>Solde Global</td><td class="right" style="color:' . $soldeColor . '">';
        $out .= number_format($totals['solde_global_absolu'], 0, ',', ' ') . ' (' . $soldeLabel . ')</td></tr>';
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
            'totalCount'   => $this->totalCount,
            'totalPages'   => $this->getTotalPages(),
            'currentPage'  => $this->currentPage,
            'exporting'    => $this->exporting,
        ])->layout('layouts.app');
    }
}
