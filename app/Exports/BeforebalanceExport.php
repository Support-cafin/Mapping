<?php

namespace App\Exports;

use App\Models\NewAccount;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class BalanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    private $entreprise;
    private $dateDebut;
    private $dateFin;
    private $exercice;
    private $search;
    private $balanceType;
    private $exerciceId;
    
    public function __construct($entreprise, $dateDebut, $dateFin, $exercice = null, $search = null, $balanceType = '4colonnes')
    {
        $this->entreprise = $entreprise;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->exercice = $exercice;
        $this->search = $search;
        $this->balanceType = $balanceType;
        
        // Récupérer l'exercice actif
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $this->exerciceId = $exo ? $exo->id : null;
    }
    
    /**
     * Récupère tous les comptes parents (ceux qui ont des enfants)
     */
    private function getParentAccounts()
    {
        if (!$this->exerciceId) {
            return collect([]);
        }
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Récupérer les comptes qui sont parents (qui ont des enfants)
        $parentIds = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        
        // Ajouter aussi les comptes racines qui pourraient avoir des mappings
        $rootAccountIds = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->whereNull('parent_id')
            ->whereHas('mappings', function($query) {
                $query->where('exercice_id', $this->exerciceId);
            })
            ->pluck('id')
            ->toArray();
        
        // Fusionner les deux listes
        $allParentIds = array_unique(array_merge($parentIds, $rootAccountIds));
        
        // Récupérer les détails des comptes parents
        $parentAccounts = NewAccount::whereIn('id', $allParentIds)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('code', 'like', '%' . $this->search . '%')
                      ->orWhere('intitule', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('code')
            ->get();
        
        return $parentAccounts;
    }
    
    /**
     * Récupère tous les enfants d'un compte parent (récursivement)
     */
    private function getAllChildrenIds($parentId)
    {
        $childrenIds = [];
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $directChildren = NewAccount::where('parent_id', $parentId)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->pluck('id')
            ->toArray();
        
        foreach ($directChildren as $childId) {
            $childrenIds[] = $childId;
            $grandChildren = $this->getAllChildrenIds($childId);
            $childrenIds = array_merge($childrenIds, $grandChildren);
        }
        
        return $childrenIds;
    }
    
    /**
     * Calcule le solde pour un compte parent en agrégeant tous ses enfants
     */
    private function calculateParentBalance($parentAccount)
    {
        if (!$this->exerciceId) {
            return null;
        }
        
        // Récupérer tous les IDs des comptes enfants (récursivement)
        $allAccountIds = [$parentAccount->id];
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $hasChildren = NewAccount::where('parent_id', $parentAccount->id)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->exists();
        
        if ($hasChildren) {
            $childrenIds = $this->getAllChildrenIds($parentAccount->id);
            $allAccountIds = array_merge($allAccountIds, $childrenIds);
        }
        
        // Récupérer tous les anciens comptes mappés à ces comptes SYCEBNL
        $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $this->entreprise->id)
            //->where('exercice_id', $this->exerciceId)
            ->where('exercice_id', $exo->id)
            ->pluck('old_account_id')
            ->unique()
            ->toArray();
        
        if (empty($oldAccountIds)) {
            return null;
        }
        
        // Récupérer les écritures pour ces anciens comptes
        $query = GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $exo->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('validated', true);
        
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
        
        $ecritures = $query->get();
        
        if ($ecritures->isEmpty()) {
            return null;
        }
        
        // Calcul des totaux
        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde = $totalDebit - $totalCredit;
        
        return [
            'code' => $parentAccount->code,
            'intitule' => $parentAccount->intitule,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'solde' => $solde,
            'has_children' => $hasChildren,
            'children_count' => count($childrenIds ?? []),
        ];
    }
    
    public function collection()
    {
        if ($this->balanceType === '4colonnes') {
            return $this->getBalance4Colonnes();
        } else {
            return $this->getBalance6Colonnes();
        }
    }
    
    /**
     * BALANCE À 4 COLONNES - UNIQUEMENT LES COMPTES PRINCIPAUX
     */
    private function getBalance4Colonnes()
    {
        $balances = [];
        
        // Récupérer les comptes parents
        $parentAccounts = $this->getParentAccounts();
        
        foreach ($parentAccounts as $parentAccount) {
            $balanceData = $this->calculateParentBalance($parentAccount);
            
            if ($balanceData) {
                $balances[] = $balanceData;
            }
        }
        
        return collect($balances);
    }
    
    /**
     * BALANCE À 6 COLONNES - UNIQUEMENT LES COMPTES PRINCIPAUX
     */
    private function getBalance6Colonnes()
    {
        $balances = [];
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Récupérer les comptes parents
        $parentAccounts = $this->getParentAccounts();
        
        foreach ($parentAccounts as $parentAccount) {
            // Récupérer tous les IDs des comptes enfants
            $allAccountIds = [$parentAccount->id];
            
            $hasChildren = NewAccount::where('parent_id', $parentAccount->id)
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $this->entreprise->id)
                ->exists();
            
            if ($hasChildren) {
                $childrenIds = $this->getAllChildrenIds($parentAccount->id);
                $allAccountIds = array_merge($allAccountIds, $childrenIds);
            }
            
            // Récupérer les soldes d'ouverture (RAN) pour tous les comptes (parent + enfants)
            $soldesRAN = DB::table('grand_livres')
                ->select(DB::raw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit'))
                ->where('entreprise_id', $this->entreprise->id)
                //->where('exercice_id', $this->exerciceId)
                ->where('exercice_id', $this->entreprise->id)
                ->whereIn('new_account_id', $allAccountIds)
                ->where('journal_code', 'RAN')
                ->first();
            
            $ouvertureDebit = $soldesRAN->total_debit ?? 0;
            $ouvertureCredit = $soldesRAN->total_credit ?? 0;
            
            // Récupérer les anciens comptes mappés
            $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
                ->where('entreprise_id', $this->entreprise->id)
                //->where('exercice_id', $this->exerciceId)
                ->where('exercice_id', $this->entreprise->id)
                ->pluck('old_account_id')
                ->unique()
                ->toArray();
            
            if (empty($oldAccountIds)) {
                continue;
            }
            
            // Récupérer les mouvements de la période (hors RAN)
            $mouvementsQuery = GrandLivre::where('entreprise_id', $this->entreprise->id)
                //->where('exercice_id', $this->exerciceId)
                ->where('exercice_id', $this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->where('journal_code', '!=', 'RAN')
                ->where('validated', true);
            
            if ($this->dateDebut && $this->dateFin) {
                $mouvementsQuery->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            }
            
            $mouvements = $mouvementsQuery->get();
            
            $mouvementDebit = $mouvements->sum('debit');
            $mouvementCredit = $mouvements->sum('credit');
            
            // Ignorer si tout est à zéro
            if ($ouvertureDebit == 0 && $ouvertureCredit == 0 && 
                $mouvementDebit == 0 && $mouvementCredit == 0) {
                continue;
            }
            
            $totalDebit = $ouvertureDebit + $mouvementDebit;
            $totalCredit = $ouvertureCredit + $mouvementCredit;
            $soldeCloture = $totalDebit - $totalCredit;
            
            $balances[] = [
                'code' => $parentAccount->code,
                'intitule' => $parentAccount->intitule,
                'ouverture_debit' => $ouvertureDebit,
                'ouverture_credit' => $ouvertureCredit,
                'mouvement_debit' => $mouvementDebit,
                'mouvement_credit' => $mouvementCredit,
                'cloture_debit' => $soldeCloture > 0 ? $soldeCloture : 0,
                'cloture_credit' => $soldeCloture < 0 ? abs($soldeCloture) : 0,
                'solde' => $soldeCloture,
                'has_children' => $hasChildren,
            ];
        }
        
        return collect($balances);
    }
    
    public function headings(): array
    {
        $headings = [
            ['BALANCE DES COMPTES SYCEBNL', '', '', '', '', ''],
            ['Entreprise: ' . ($this->entreprise->nom ?? $this->entreprise->name ?? 'N/A'), '', '', '', '', ''],
            ['Période: ' . $this->dateDebut . ' au ' . $this->dateFin, '', '', '', '', ''],
            ['Type: ' . ($this->balanceType === '4colonnes' ? 'Balance à 4 colonnes' : 'Balance à 6 colonnes'), '', '', '', '', ''],
            ['Date d\'export: ' . now()->format('d/m/Y H:i:s'), '', '', '', '', ''],
            //['(Uniquement les comptes principaux - enfants agrégés)', '', '', '', '', ''],
            [], // Ligne vide
        ];
        
        if ($this->balanceType === '4colonnes') {
            $headings[] = [
                'Code SYCEBNL',
                'Libellé du compte',
                //'Nb enfants',
                'Mouvement Débit',
                'Mouvement Crédit',
                'Solde Débiteur',
                'Solde Créditeur',
            ];
        } else {
            $headings[] = [
                'Code SYCEBNL',
                'Libellé du compte',
                //'Nb enfants',
                'Solde d\'ouverture Débiteur (A)',
                'Solde d\'ouverture Créditeur (B)',
                'Mouvement Débit (C)',
                'Mouvement Crédit (D)',
                'Solde clôture Débiteur',
                'Solde clôture Créditeur',
            ];
        }
        
        return $headings;
    }
    
    public function map($balance): array
    {
        $nbEnfants = $balance['has_children'] ? $balance['children_count'] : '-';
        
        if ($this->balanceType === '4colonnes') {
            $soldeDebiteur = $balance['solde'] > 0 ? $balance['solde'] : 0;
            $soldeCrediteur = $balance['solde'] < 0 ? abs($balance['solde']) : 0;
            
            return [
                $balance['code'],
                $balance['intitule'],
                //$nbEnfants,
                $balance['total_debit'] > 0 ? number_format($balance['total_debit'], 0, '', ' ') : '',
                $balance['total_credit'] > 0 ? number_format($balance['total_credit'], 0, '', ' ') : '',
                $soldeDebiteur > 0 ? number_format($soldeDebiteur, 0, '', ' ') : '',
                $soldeCrediteur > 0 ? number_format($soldeCrediteur, 0, '', ' ') : '',
            ];
        } else {
            return [
                $balance['code'],
                $balance['intitule'],
                //$nbEnfants,
                $balance['ouverture_debit'] > 0 ? number_format($balance['ouverture_debit'], 0, '', ' ') : '',
                $balance['ouverture_credit'] > 0 ? number_format($balance['ouverture_credit'], 0, '', ' ') : '',
                $balance['mouvement_debit'] > 0 ? number_format($balance['mouvement_debit'], 0, '', ' ') : '',
                $balance['mouvement_credit'] > 0 ? number_format($balance['mouvement_credit'], 0, '', ' ') : '',
                $balance['cloture_debit'] > 0 ? number_format($balance['cloture_debit'], 0, '', ' ') : '',
                $balance['cloture_credit'] > 0 ? number_format($balance['cloture_credit'], 0, '', ' ') : '',
            ];
        }
    }
    
    public function columnWidths(): array
    {
        if ($this->balanceType === '4colonnes') {
            return [
                'A' => 15, // Code
                'B' => 45, // Libellé
                //'C' => 10, // Nb enfants
                'D' => 18, // Mouvement Débit
                'E' => 18, // Mouvement Crédit
                'F' => 18, // Solde Débiteur
                'G' => 18, // Solde Créditeur
            ];
        } else {
            return [
                'A' => 15, // Code
                'B' => 40, // Libellé
                //'C' => 10, // Nb enfants
                'D' => 16, // Ouverture Débit
                'E' => 16, // Ouverture Crédit
                'F' => 16, // Mouvement Débit
                'G' => 16, // Mouvement Crédit
                'H' => 16, // Clôture Débit
                'I' => 16, // Clôture Crédit
            ];
        }
    }
    
    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->balanceType === '4colonnes' ? 'G' : 'I';
        $headerRow = 8; // Ligne des en-têtes de colonnes
        
        // Fusion des cellules d'en-tête
        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->mergeCells('A2:' . $lastColumn . '2');
        $sheet->mergeCells('A3:' . $lastColumn . '3');
        $sheet->mergeCells('A4:' . $lastColumn . '4');
        $sheet->mergeCells('A5:' . $lastColumn . '5');
        $sheet->mergeCells('A6:' . $lastColumn . '6');
        
        // Style du titre principal
        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
                'color' => ['rgb' => '2D3748']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ]
        ]);
        
        // Style des sous-titres
        $sheet->getStyle('A2:A6')->applyFromArray([
            'font' => [
                'size' => 11,
                'color' => ['rgb' => '4A5568']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ]
        ]);
        
        // Ligne des en-têtes de tableau
        $sheet->getStyle('A' . $headerRow . ':' . $lastColumn . $headerRow)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C5282']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '1A365D']
                ]
            ]
        ]);
        
        $lastRow = $sheet->getHighestRow();
        if ($lastRow > $headerRow) {
            $dataStartRow = $headerRow + 1;
            $dataRange = 'A' . $dataStartRow . ':' . $lastColumn . $lastRow;
            
            // Style des données
            $sheet->getStyle($dataRange)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'E2E8F0']
                    ]
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ]);
            
            // Alignement des colonnes
            $sheet->getStyle('A' . $dataStartRow . ':A' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]);
            
            $sheet->getStyle('B' . $dataStartRow . ':B' . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]
            ]);
            
            $sheet->getStyle('C' . $dataStartRow . ':' . $lastColumn . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]
            ]);
            
            // Couleurs alternées
            for ($row = $dataStartRow; $row <= $lastRow; $row++) {
                $bgColor = $row % 2 == 0 ? 'F7FAFC' : 'FFFFFF';
                $sheet->getStyle('A' . $row . ':' . $lastColumn . $row)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $bgColor]
                    ]
                ]);
            }
        }
        
        $sheet->getRowDimension($headerRow)->setRowHeight(40);
        
        return [];
    }
    
    public function title(): string
    {
        $type = $this->balanceType === '4colonnes' ? '4col' : '6col';
        return 'Balance_' . $type . '_' . str_replace('-', '_', $this->dateDebut);
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();
                $headerRow = 8;
                
                if ($lastRow > $headerRow) {
                    $totalRow = $lastRow + 2;
                    
                    if ($this->balanceType === '4colonnes') {
                        $this->addTotals4Colonnes($sheet, $totalRow, $lastRow);
                    } else {
                        $this->addTotals6Colonnes($sheet, $totalRow, $lastRow);
                    }
                }
            }
        ];
    }
    
    private function addTotals4Colonnes($sheet, $totalRow, $lastRow)
    {
        $totalMouvementDebit = 0;
        $totalMouvementCredit = 0;
        $totalSoldeDebiteur = 0;
        $totalSoldeCrediteur = 0;
        
        for ($row = 9; $row <= $lastRow; $row++) {
            $mouvDebit = (float) str_replace([' ', ''], '', $sheet->getCell('D' . $row)->getValue() ?: 0);
            $mouvCredit = (float) str_replace([' ', ''], '', $sheet->getCell('E' . $row)->getValue() ?: 0);
            $soldeDeb = (float) str_replace([' ', ''], '', $sheet->getCell('F' . $row)->getValue() ?: 0);
            $soldeCred = (float) str_replace([' ', ''], '', $sheet->getCell('G' . $row)->getValue() ?: 0);
            
            $totalMouvementDebit += $mouvDebit;
            $totalMouvementCredit += $mouvCredit;
            $totalSoldeDebiteur += $soldeDeb;
            $totalSoldeCrediteur += $soldeCred;
        }
        
        $sheet->setCellValue('B' . $totalRow, 'TOTAUX GÉNÉRAUX');
        $sheet->setCellValue('D' . $totalRow, $totalMouvementDebit > 0 ? number_format($totalMouvementDebit, 0, '', ' ') : '');
        $sheet->setCellValue('E' . $totalRow, $totalMouvementCredit > 0 ? number_format($totalMouvementCredit, 0, '', ' ') : '');
        $sheet->setCellValue('F' . $totalRow, $totalSoldeDebiteur > 0 ? number_format($totalSoldeDebiteur, 0, '', ' ') : '');
        $sheet->setCellValue('G' . $totalRow, $totalSoldeCrediteur > 0 ? number_format($totalSoldeCrediteur, 0, '', ' ') : '');
        
        $this->styleTotalRow($sheet, $totalRow, 'B', 'G');
        
        // Ligne d'équilibre
        $equilibreRow = $totalRow + 1;
        if (abs($totalSoldeDebiteur - $totalSoldeCrediteur) < 0.01) {
            $sheet->setCellValue('B' . $equilibreRow, '✓ BALANCE ÉQUILIBRÉE');
            $sheet->mergeCells('B' . $equilibreRow . ':G' . $equilibreRow);
            $sheet->getStyle('B' . $equilibreRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '276749']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FFF4']]
            ]);
        } else {
            $diff = abs($totalSoldeDebiteur - $totalSoldeCrediteur);
            $sheet->setCellValue('B' . $equilibreRow, '✗ DÉSÉQUILIBRE: ' . number_format($diff, 0, '', ' '));
            $sheet->mergeCells('B' . $equilibreRow . ':G' . $equilibreRow);
            $sheet->getStyle('B' . $equilibreRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'C53030']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF5F5']]
            ]);
        }
    }
    
    private function addTotals6Colonnes($sheet, $totalRow, $lastRow)
    {
        $totalOuvertureDebit = 0;
        $totalOuvertureCredit = 0;
        $totalMouvementDebit = 0;
        $totalMouvementCredit = 0;
        $totalClotureDebit = 0;
        $totalClotureCredit = 0;
        
        for ($row = 9; $row <= $lastRow; $row++) {
            $ouvDeb = (float) str_replace([' ', ''], '', $sheet->getCell('D' . $row)->getValue() ?: 0);
            $ouvCred = (float) str_replace([' ', ''], '', $sheet->getCell('E' . $row)->getValue() ?: 0);
            $mouvDeb = (float) str_replace([' ', ''], '', $sheet->getCell('F' . $row)->getValue() ?: 0);
            $mouvCred = (float) str_replace([' ', ''], '', $sheet->getCell('G' . $row)->getValue() ?: 0);
            $clotDeb = (float) str_replace([' ', ''], '', $sheet->getCell('H' . $row)->getValue() ?: 0);
            $clotCred = (float) str_replace([' ', ''], '', $sheet->getCell('I' . $row)->getValue() ?: 0);
            
            $totalOuvertureDebit += $ouvDeb;
            $totalOuvertureCredit += $ouvCred;
            $totalMouvementDebit += $mouvDeb;
            $totalMouvementCredit += $mouvCred;
            $totalClotureDebit += $clotDeb;
            $totalClotureCredit += $clotCred;
        }
        
        $sheet->setCellValue('B' . $totalRow, 'TOTAUX GÉNÉRAUX');
        $sheet->setCellValue('D' . $totalRow, $totalOuvertureDebit > 0 ? number_format($totalOuvertureDebit, 0, '', ' ') : '');
        $sheet->setCellValue('E' . $totalRow, $totalOuvertureCredit > 0 ? number_format($totalOuvertureCredit, 0, '', ' ') : '');
        $sheet->setCellValue('F' . $totalRow, $totalMouvementDebit > 0 ? number_format($totalMouvementDebit, 0, '', ' ') : '');
        $sheet->setCellValue('G' . $totalRow, $totalMouvementCredit > 0 ? number_format($totalMouvementCredit, 0, '', ' ') : '');
        $sheet->setCellValue('H' . $totalRow, $totalClotureDebit > 0 ? number_format($totalClotureDebit, 0, '', ' ') : '');
        $sheet->setCellValue('I' . $totalRow, $totalClotureCredit > 0 ? number_format($totalClotureCredit, 0, '', ' ') : '');
        
        $this->styleTotalRow($sheet, $totalRow, 'B', 'I');
        
        // Vérification de l'équilibre (A+C = B+D)
        $totalDebitGeneral = $totalOuvertureDebit + $totalMouvementDebit;
        $totalCreditGeneral = $totalOuvertureCredit + $totalMouvementCredit;
        
        $equilibreRow = $totalRow + 1;
        $sheet->setCellValue('B' . $equilibreRow, 'ÉQUILIBRE (A+C) = (B+D) : ' . 
            number_format($totalDebitGeneral, 0, '', ' ') . ' = ' . 
            number_format($totalCreditGeneral, 0, '', ' '));
        $sheet->mergeCells('B' . $equilibreRow . ':I' . $equilibreRow);
        
        if (abs($totalDebitGeneral - $totalCreditGeneral) < 0.01) {
            $sheet->getStyle('B' . $equilibreRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '276749']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FFF4']]
            ]);
        } else {
            $sheet->getStyle('B' . $equilibreRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'C53030']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF5F5']]
            ]);
        }
    }
    
    private function styleTotalRow($sheet, $row, $startCol, $endCol)
    {
        $sheet->getStyle($startCol . $row . ':' . $endCol . $row)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 11,
                'color' => ['rgb' => '1A202C']
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EDF2F7']
            ],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2D3748']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '2D3748']],
            ]
        ]);
        
        $sheet->getStyle($startCol . $row . ':' . $endCol . $row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}