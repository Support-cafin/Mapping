<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithCustomChunkSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Facades\DB;

class GrandLivreGeneralExport implements FromQuery, WithHeadings, WithMapping, WithStyles, 
    WithColumnWidths, WithTitle, WithEvents, WithChunkReading, WithCustomChunkSize
{
    use Exportable;
    
    protected $entrepriseId;
    protected $entreprise;
    protected $filters;
    protected $accountGroups = [];
    protected $recapStats;
    protected $currentAccount = null;
    protected $accountEcritureCount = 0;
    
    public function __construct($entrepriseId, $filters)
    {
        $this->entrepriseId = $entrepriseId;
        $this->filters = $filters;
        
        if (is_numeric($entrepriseId)) {
            $this->entreprise = \App\Models\Entreprise::find($entrepriseId);
        } else {
            $this->entreprise = $entrepriseId;
        }
        
        // Pré-calculer les groupes de comptes une seule fois
        $this->accountGroups = $this->getAccountGroups();
        $this->recapStats = $this->calculateRecapStats();
    }
    
    public function query()
    {
        // Construire la requête optimisée
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
                     ->where('am.entreprise_id', $this->entrepriseId);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entrepriseId)
            ->whereNotNull('na.id');
        
        $this->applyFilters($query);
        
        // Trier pour le chunking
        $query->orderBy('na.code')
              ->orderBy('gl.date_ecriture')
              ->orderBy('gl.id');
        
        return $query;
    }
    
    public function chunkSize(): int
    {
        return 10000;
    }
    
    protected function getAccountGroups()
    {
        $query = DB::table('grand_livres as gl')
            ->select([
                'na.code as new_account_code',
                'na.intitule as new_account_intitule',
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit'),
                DB::raw('COUNT(gl.id) as nombre_ecritures')
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entrepriseId);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entrepriseId)
            ->whereNotNull('na.id')
            ->groupBy('na.code', 'na.intitule')
            ->orderBy('na.code');
        
        $this->applyFilters($query);
        
        return $query->get()->mapWithKeys(function ($item) {
            return [
                $item->new_account_code => [
                    'new_account_intitule' => $item->new_account_intitule,
                    'total_debit' => (float) $item->total_debit,
                    'total_credit' => (float) $item->total_credit,
                    'nombre_ecritures' => (int) $item->nombre_ecritures,
                    'processed_ecritures' => 0
                ]
            ];
        })->toArray();
    }
    
    protected function calculateRecapStats()
    {
        $stats = [
            'total_comptes' => 0,
            'total_ecritures' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
        ];
        
        foreach ($this->accountGroups as $account) {
            $stats['total_comptes']++;
            $stats['total_ecritures'] += $account['nombre_ecritures'];
            $stats['total_debit'] += $account['total_debit'];
            $stats['total_credit'] += $account['total_credit'];
        }
        
        $stats['solde_global'] = $stats['total_debit'] - $stats['total_credit'];
        
        return $stats;
    }
    
    protected function applyFilters($query)
    {
        if (!empty($this->filters['dateDebut']) && !empty($this->filters['dateFin'])) {
            $query->whereBetween('gl.date_ecriture', [
                $this->filters['dateDebut'],
                $this->filters['dateFin']
            ]);
        }
        
        if (!empty($this->filters['exercice'])) {
            $query->where('gl.exercice', $this->filters['exercice']);
        }
        
        if (!empty($this->filters['journalCode'])) {
            $query->where('gl.journal_code', $this->filters['journalCode']);
        }
        
        if (!empty($this->filters['lettre'])) {
            if ($this->filters['lettre'] === 'non') {
                $query->whereNull('gl.lettre');
            } else {
                $query->where('gl.lettre', $this->filters['lettre']);
            }
        }
        
        if (!empty($this->filters['search'])) {
            $searchTerm = '%' . $this->filters['search'] . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('gl.libelle', 'like', $searchTerm)
                  ->orWhere('gl.piece', 'like', $searchTerm)
                  ->orWhere('oa.code', 'like', $searchTerm)
                  ->orWhere('oa.intitule', 'like', $searchTerm)
                  ->orWhere('na.code', 'like', $searchTerm)
                  ->orWhere('na.intitule', 'like', $searchTerm);
            });
        }
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Pièce',
            'Journal',
            'Compte',
            'Compte SYCEBNL',
            'Libellé',
            'Débit',
            'Crédit',
        ];
    }
    
    public function map($row): array
    {
        $output = [];
        $newAccountCode = $row->new_account_code;
        
        // Si c'est un nouveau compte
        if ($this->currentAccount !== $newAccountCode) {
            // Si on avait un compte précédent, ajouter son footer
            if ($this->currentAccount !== null) {
                $output = array_merge($output, $this->getAccountFooter($this->currentAccount));
                $output[] = $this->getEmptyRow();
            }
            
            // Réinitialiser pour le nouveau compte
            $this->currentAccount = $newAccountCode;
            $this->accountEcritureCount = 0;
            
            // Ajouter l'en-tête du compte
            $output[] = $this->getAccountHeader($row);
        }
        
        // Ajouter l'écriture détaillée
        $output[] = $this->getDetailRow($row);
        
        $this->accountEcritureCount++;
        $this->accountGroups[$newAccountCode]['processed_ecritures']++;
        
        // Si c'est la dernière écriture de ce compte
        if ($this->accountGroups[$newAccountCode]['processed_ecritures'] >= $this->accountGroups[$newAccountCode]['nombre_ecritures']) {
            $output = array_merge($output, $this->getAccountFooter($newAccountCode));
            $output[] = $this->getEmptyRow();
            $this->currentAccount = null;
        }
        
        return $output;
    }
    
    protected function getAccountHeader($row): array
    {
        // CORRECTION: Format correct pour l'en-tête
        return [
            '', // A: Date vide
            '', // B: Pièce vide  
            '', // C: Journal vide
            '', // D: Compte ancien vide
            $row->new_account_code, // E: Compte SYCEBNL (101100)
            $row->new_account_intitule, // F: Libellé du compte ("Dotation non consomptible...")
            '', // G: Débit vide
            '', // H: Crédit vide
        ];
    }
    
    protected function getDetailRow($row): array
    {
        // CORRECTION: Format correct pour les écritures détaillées
        return [
            $row->date_ecriture ? \Carbon\Carbon::parse($row->date_ecriture)->format('d/m/Y') : '', // A: Date
            $row->piece ?? '', // B: Pièce
            $row->journal_code ?? '', // C: Journal
            $row->old_account_code ?? '', // D: Compte ancien
            '', // E: Compte SYCEBNL VIDE pour les écritures
            $row->libelle ?? '', // F: Libellé de l'écriture
            $row->debit > 0 ? number_format($row->debit, 0, '', ' ') : '', // G: Débit
            $row->credit > 0 ? number_format($row->credit, 0, '', ' ') : '', // H: Crédit
        ];
    }
    
    protected function getAccountFooter($accountCode): array
    {
        $account = $this->accountGroups[$accountCode];
        $soldeCompte = $account['total_debit'] - $account['total_credit'];
        
        $dateSolde = !empty($this->filters['dateFin']) 
            ? \Carbon\Carbon::parse($this->filters['dateFin'])->format('d/m/Y')
            : now()->format('d/m/Y');
        
        return [
            [ // Total du compte
                '', '', '', '', '', // A-E vides
                'TOTAL ' . $accountCode, // F: Libellé "TOTAL 101100"
                number_format($account['total_debit'], 0, '', ' '), // G: Total débit
                number_format($account['total_credit'], 0, '', ' '), // H: Total crédit
            ],
            [ // Solde du compte
                '', '', '', '', '', // A-E vides
                'SOLDE ' . $accountCode . ' au ' . $dateSolde, // F: Libellé "SOLDE 101100 au..."
                $soldeCompte > 0 ? number_format(abs($soldeCompte), 0, '', ' ') : '', // G: Débit si solde positif
                $soldeCompte < 0 ? number_format(abs($soldeCompte), 0, '', ' ') : '', // H: Crédit si solde négatif
            ]
        ];
    }
    
    protected function getEmptyRow(): array
    {
        // Ligne de séparation complètement vide
        return ['', '', '', '', '', '', '', ''];
    }
    
    public function title(): string
    {
        return 'Grand Livre Général';
    }
    
    public function columnWidths(): array
    {
        return [
            'A' => 12, // Date
            'B' => 10, // Pièce
            'C' => 8,  // Journal
            'D' => 12, // Compte
            'E' => 15, // Compte SYCEBNL
            'F' => 40, // Libellé
            'G' => 15, // Débit
            'H' => 15, // Crédit
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Style par défaut
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
        
        // Alignement
        $sheet->getStyle('A:F')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('G:H')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                
                // Configuration performance
                ini_set('memory_limit', '2048M');
                set_time_limit(300);
                
                // Titre principal
                $this->addMainTitle($sheet);
                
                // Période et informations
                $this->addPeriodInfo($sheet);
                
                // En-têtes des colonnes
                $this->addColumnHeaders($sheet);
                
                // Appliquer les styles aux données
                $this->applyDataStyles($sheet, $highestRow);
                
                // Ajouter les totaux généraux
                $this->addGlobalTotals($sheet, $highestRow);
                
                // Optimisations
                $sheet->freezePane('A7');
            },
        ];
    }
    
    protected function addMainTitle(Worksheet $sheet)
    {
        $title = 'GRAND LIVRE GÉNÉRAL';
        if ($this->entreprise) {
            $title .= ' - ' . $this->entreprise->nom . ' (' . $this->entreprise->code . ')';
        }
        
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => '1E3A8A'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E0F2FE'],
            ],
        ]);
    }
    
    protected function addPeriodInfo(Worksheet $sheet)
    {
        // Période
        $periode = '';
        if (!empty($this->filters['dateDebut']) && !empty($this->filters['dateFin'])) {
            $periode = 'Période : ' . 
                \Carbon\Carbon::parse($this->filters['dateDebut'])->format('d/m/Y') . 
                ' au ' . 
                \Carbon\Carbon::parse($this->filters['dateFin'])->format('d/m/Y');
        }
        $sheet->setCellValue('A2', $periode);
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Exercice
        $exercice = !empty($this->filters['exercice']) 
            ? 'Exercice : ' . $this->filters['exercice']
            : 'Exercice : ' . date('Y');
        $sheet->setCellValue('A3', $exercice);
        $sheet->mergeCells('A3:H3');
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Date d'export
        $sheet->setCellValue('A4', 'Exporté le : ' . now()->format('d/m/Y à H:i'));
        $sheet->mergeCells('A4:H4');
        $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        
        // Ligne vide
        $sheet->setCellValue('A5', '');
    }
    
    protected function addColumnHeaders(Worksheet $sheet)
    {
        // En-têtes des colonnes
        $headers = $this->headings();
        for ($i = 0; $i < count($headers); $i++) {
            $column = chr(65 + $i);
            $sheet->setCellValue($column . '6', $headers[$i]);
        }
        
        // Style des en-têtes
        $sheet->getStyle('A6:H6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F2937'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
        
        $sheet->getRowDimension(6)->setRowHeight(25);
    }
    
    protected function applyDataStyles(Worksheet $sheet, $highestRow)
    {
        $startRow = 7;
        
        for ($row = $startRow; $row <= $highestRow; $row++) {
            $cellF = $sheet->getCell('F' . $row)->getValue();
            $cellE = $sheet->getCell('E' . $row)->getValue();
            $cellG = $sheet->getCell('G' . $row)->getValue();
            $cellH = $sheet->getCell('H' . $row)->getValue();
            
            // Ligne complètement vide = séparation
            if ($cellF === '' && $cellG === '' && $cellH === '' && $cellE === '') {
                $sheet->getRowDimension($row)->setRowHeight(5);
                continue;
            }
            
            // Détecter l'en-tête de compte (colonne E remplie et colonne F remplie)
            if (!empty($cellE) && !empty($cellF) && $cellG === '' && $cellH === '') {
                // Vérifier que ce n'est pas un TOTAL ou SOLDE
                if (strpos($cellF, 'TOTAL') !== 0 && strpos($cellF, 'SOLDE') !== 0) {
                    // C'est un en-tête de compte - fusionner
                    $sheet->mergeCells('A' . $row . ':H' . $row);
                    $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '1E40AF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'E0F2FE'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                        ],
                    ]);
                    continue;
                }
            }
            
            // Total de compte
            if (strpos($cellF ?? '', 'TOTAL') === 0) {
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '1F2937'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F3F4F6'],
                    ],
                ]);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                
                // Couleurs pour débit/crédit
                if (!empty($cellG)) $sheet->getStyle('G' . $row)->getFont()->getColor()->setARGB('991B1B');
                if (!empty($cellH)) $sheet->getStyle('H' . $row)->getFont()->getColor()->setARGB('166534');
                continue;
            }
            
            // Solde de compte
            if (strpos($cellF ?? '', 'SOLDE') === 0) {
                $isDebiteur = !empty($cellG);
                $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => $isDebiteur ? '991B1B' : '166534'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $isDebiteur ? 'FEF2F2' : 'F0FDF4'],
                    ],
                ]);
                $sheet->getStyle('F' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                continue;
            }
            
            // Écriture détaillée normale
            if (!empty($cellG) || !empty($cellH)) {
                // Couleurs pour débit/crédit
                if (!empty($cellG)) $sheet->getStyle('G' . $row)->getFont()->getColor()->setARGB('DC2626');
                if (!empty($cellH)) $sheet->getStyle('H' . $row)->getFont()->getColor()->setARGB('16A34A');
                
                // Alternance de couleurs
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':H' . $row)
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setRGB('F9FAFB');
                }
            }
            
            // Bordures pour toutes les lignes
            $sheet->getStyle('A' . $row . ':H' . $row)->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFCCCCCC'));
        }
    }
    
    protected function addGlobalTotals(Worksheet $sheet, $highestRow)
    {
        $totalRow = $highestRow + 2;
        $soldeRow = $totalRow + 1;
        
        // Totaux généraux
        $sheet->setCellValue('A' . $totalRow, 'TOTAUX GÉNÉRAUX');
        $sheet->mergeCells('A' . $totalRow . ':F' . $totalRow);
        $sheet->getStyle('A' . $totalRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $sheet->setCellValue('G' . $totalRow, number_format($this->recapStats['total_debit'], 0, '', ' '));
        $sheet->setCellValue('H' . $totalRow, number_format($this->recapStats['total_credit'], 0, '', ' '));
        
        $sheet->getStyle('A' . $totalRow . ':H' . $totalRow)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        
        // Solde global
        $soldeGlobal = $this->recapStats['solde_global'];
        $soldeText = number_format(abs($soldeGlobal), 0, '', ' ');
        
        $sheet->setCellValue('A' . $soldeRow, 'SOLDE GLOBAL');
        $sheet->mergeCells('A' . $soldeRow . ':F' . $soldeRow);
        $sheet->getStyle('A' . $soldeRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        
        if ($soldeGlobal > 0) {
            $sheet->setCellValue('G' . $soldeRow, $soldeText . ' (Débit)');
            $sheet->getStyle('G' . $soldeRow)->getFont()->getColor()->setARGB('991B1B');
        } else {
            $sheet->setCellValue('H' . $soldeRow, $soldeText . ' (Crédit)');
            $sheet->getStyle('H' . $soldeRow)->getFont()->getColor()->setARGB('166534');
        }
        
        $sheet->getStyle('A' . $soldeRow . ':H' . $soldeRow)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BFDBFE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
    }
}