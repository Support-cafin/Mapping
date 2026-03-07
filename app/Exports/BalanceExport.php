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

        // Récupérer les comptes qui sont parents (qui ont des enfants)
        $parentIds = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
            ->whereNotNull('parent_id')
            ->distinct()
            ->pluck('parent_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        
        // Ajouter aussi les comptes racines qui pourraient avoir des mappings
        $rootAccountIds = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
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
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
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

        $directChildren = NewAccount::where('parent_id', $parentId)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
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

        $hasChildren = NewAccount::where('parent_id', $parentAccount->id)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
            ->exists();
        
        if ($hasChildren) {
            $childrenIds = $this->getAllChildrenIds($parentAccount->id);
            $allAccountIds = array_merge($allAccountIds, $childrenIds);
        }
        
        // Récupérer tous les anciens comptes mappés à ces comptes SYCEBNL
        $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
            ->pluck('old_account_id')
            ->unique()
            ->toArray();
        
        if (empty($oldAccountIds)) {
            return null;
        }
        
        // Récupérer les écritures pour ces anciens comptes
        $query = GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
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

        // Récupérer les comptes parents
        $parentAccounts = $this->getParentAccounts();
        
        foreach ($parentAccounts as $parentAccount) {
            // Récupérer tous les IDs des comptes enfants
            $allAccountIds = [$parentAccount->id];
            $childrenIds = [];
            
            $hasChildren = NewAccount::where('parent_id', $parentAccount->id)
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
                ->exists();
            
            if ($hasChildren) {
                $childrenIds = $this->getAllChildrenIds($parentAccount->id);
                $allAccountIds = array_merge($allAccountIds, $childrenIds);
            }
            
            // ✅ FIX Bug #2 — Récupérer les anciens comptes mappés D'ABORD
            // pour que les RAN et les mouvements utilisent la même base (old_account_id)
            $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
                ->pluck('old_account_id')
                ->unique()
                ->toArray();

            if (empty($oldAccountIds)) {
                continue;
            }

            // Récupérer les soldes d'ouverture (RAN) via old_account_id ✅ FIX Bug #2
            $soldesRAN = DB::table('grand_livres')
                ->select(DB::raw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit'))
                ->where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
                ->whereIn('old_account_id', $oldAccountIds) // ✅ FIX Bug #2 — cohérent avec les mouvements
                ->where('journal_code', 'RAN')
                ->first();
            
            $ouvertureDebit = $soldesRAN->total_debit ?? 0;
            $ouvertureCredit = $soldesRAN->total_credit ?? 0;
            
            // Récupérer les mouvements de la période (hors RAN)
            $mouvementsQuery = GrandLivre::where('entreprise_id', $this->entreprise->id)
                ->where('exercice_id', $this->exerciceId) // ✅ FIX Bug #1
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
            [], // Ligne vide
        ];
        
        if ($this->balanceType === '4colonnes') {
            $headings[] = [
                'Code SYCEBNL',
                'Libellé du compte',
                'Mouvement Débit',
                'Mouvement Crédit',
                'Solde Débiteur',
                'Solde Créditeur',
            ];
        } else {
            $headings[] = [
                'Code SYCEBNL',
                'Libellé du compte',
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
        if ($this->balanceType === '4colonnes') {
            $soldeDebiteur = $balance['solde'] > 0 ? $balance['solde'] : 0;
            $soldeCrediteur = $balance['solde'] < 0 ? abs($balance['solde']) : 0;
            
            return [
                $balance['code'],
                $balance['intitule'],
                $balance['total_debit'] > 0 ? number_format($balance['total_debit'], 0, '', ' ') : '',
                $balance['total_credit'] > 0 ? number_format($balance['total_credit'], 0, '', ' ') : '',
                $soldeDebiteur > 0 ? number_format($soldeDebiteur, 0, '', ' ') : '',
                $soldeCrediteur > 0 ? number_format($soldeCrediteur, 0, '', ' ') : '',
            ];
        } else {
            return [
                $balance['code'],
                $balance['intitule'],
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
                'A' => 15,
                'B' => 45,
                'C' => 18,
                'D' => 18,
                'E' => 18,
                'F' => 18,
            ];
        } else {
            return [
                'A' => 15,
                'B' => 40,
                'C' => 16,
                'D' => 16,
                'E' => 16,
                'F' => 16,
                'G' => 16,
                'H' => 16,
            ];
        }
    }
    
    public function styles(Worksheet $sheet)
    {
        $lastColumn = $this->balanceType === '4colonnes' ? 'F' : 'H';
        $headerRow = 7; // Ligne des en-têtes de colonnes (6 lignes d'en-tête + 1 vide = ligne 7)
        
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
                $headerRow = 7;
                
                if ($lastRow > $headerRow) {
                    $totalRow = $lastRow + 2;
                    
                    if ($this->balanceType === '4colonnes') {
                        $this->addTotals4Colonnes($sheet, $totalRow, $lastRow, $headerRow);
                    } else {
                        $this->addTotals6Colonnes($sheet, $totalRow, $lastRow, $headerRow);
                    }
                }
            }
        ];
    }

    /**
     * ✅ FIX Bug #3 — Nettoie correctement les espaces insécables (\xc2\xa0)
     * utilisés par number_format comme séparateur de milliers
     */
    private function cleanNumber($value): float
    {
        if (empty($value)) return 0.0;
        // Supprime l'espace insécable UTF-8 (\xc2\xa0), l'espace normal, et les espaces larges
        $cleaned = preg_replace('/[\x{00A0}\x{202F}\s]/u', '', (string) $value);
        return (float) $cleaned;
    }
    
    private function addTotals4Colonnes($sheet, $totalRow, $lastRow, $headerRow = 7)
    {
        $totalMouvementDebit = 0;
        $totalMouvementCredit = 0;
        $totalSoldeDebiteur = 0;
        $totalSoldeCrediteur = 0;
        
        $dataStartRow = $headerRow + 1;
        for ($row = $dataStartRow; $row <= $lastRow; $row++) {
            $totalMouvementDebit  += $this->cleanNumber($sheet->getCell('C' . $row)->getValue()); // ✅ FIX Bug #3
            $totalMouvementCredit += $this->cleanNumber($sheet->getCell('D' . $row)->getValue()); // ✅ FIX Bug #3
            $totalSoldeDebiteur   += $this->cleanNumber($sheet->getCell('E' . $row)->getValue()); // ✅ FIX Bug #3
            $totalSoldeCrediteur  += $this->cleanNumber($sheet->getCell('F' . $row)->getValue()); // ✅ FIX Bug #3
        }
        
        $sheet->setCellValue('B' . $totalRow, 'TOTAUX GÉNÉRAUX');
        $sheet->setCellValue('C' . $totalRow, $totalMouvementDebit > 0 ? number_format($totalMouvementDebit, 0, '', ' ') : '');
        $sheet->setCellValue('D' . $totalRow, $totalMouvementCredit > 0 ? number_format($totalMouvementCredit, 0, '', ' ') : '');
        $sheet->setCellValue('E' . $totalRow, $totalSoldeDebiteur > 0 ? number_format($totalSoldeDebiteur, 0, '', ' ') : '');
        $sheet->setCellValue('F' . $totalRow, $totalSoldeCrediteur > 0 ? number_format($totalSoldeCrediteur, 0, '', ' ') : '');
        
        $this->styleTotalRow($sheet, $totalRow, 'B', 'F');
        
        // Ligne d'équilibre
        $equilibreRow = $totalRow + 1;
        if (abs($totalSoldeDebiteur - $totalSoldeCrediteur) < 0.01) {
            $sheet->setCellValue('B' . $equilibreRow, '✓ BALANCE ÉQUILIBRÉE');
            $sheet->mergeCells('B' . $equilibreRow . ':F' . $equilibreRow);
            $sheet->getStyle('B' . $equilibreRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => '276749']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0FFF4']]
            ]);
        } else {
            $diff = abs($totalSoldeDebiteur - $totalSoldeCrediteur);
            $sheet->setCellValue('B' . $equilibreRow, '✗ DÉSÉQUILIBRE: ' . number_format($diff, 0, '', ' '));
            $sheet->mergeCells('B' . $equilibreRow . ':F' . $equilibreRow);
            $sheet->getStyle('B' . $equilibreRow)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'C53030']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF5F5']]
            ]);
        }
    }
    
    private function addTotals6Colonnes($sheet, $totalRow, $lastRow, $headerRow = 7)
    {
        $totalOuvertureDebit = 0;
        $totalOuvertureCredit = 0;
        $totalMouvementDebit = 0;
        $totalMouvementCredit = 0;
        $totalClotureDebit = 0;
        $totalClotureCredit = 0;
        
        $dataStartRow = $headerRow + 1;
        for ($row = $dataStartRow; $row <= $lastRow; $row++) {
            $totalOuvertureDebit  += $this->cleanNumber($sheet->getCell('C' . $row)->getValue()); // ✅ FIX Bug #3
            $totalOuvertureCredit += $this->cleanNumber($sheet->getCell('D' . $row)->getValue()); // ✅ FIX Bug #3
            $totalMouvementDebit  += $this->cleanNumber($sheet->getCell('E' . $row)->getValue()); // ✅ FIX Bug #3
            $totalMouvementCredit += $this->cleanNumber($sheet->getCell('F' . $row)->getValue()); // ✅ FIX Bug #3
            $totalClotureDebit    += $this->cleanNumber($sheet->getCell('G' . $row)->getValue()); // ✅ FIX Bug #3
            $totalClotureCredit   += $this->cleanNumber($sheet->getCell('H' . $row)->getValue()); // ✅ FIX Bug #3
        }
        
        $sheet->setCellValue('B' . $totalRow, 'TOTAUX GÉNÉRAUX');
        $sheet->setCellValue('C' . $totalRow, $totalOuvertureDebit > 0 ? number_format($totalOuvertureDebit, 0, '', ' ') : '');
        $sheet->setCellValue('D' . $totalRow, $totalOuvertureCredit > 0 ? number_format($totalOuvertureCredit, 0, '', ' ') : '');
        $sheet->setCellValue('E' . $totalRow, $totalMouvementDebit > 0 ? number_format($totalMouvementDebit, 0, '', ' ') : '');
        $sheet->setCellValue('F' . $totalRow, $totalMouvementCredit > 0 ? number_format($totalMouvementCredit, 0, '', ' ') : '');
        $sheet->setCellValue('G' . $totalRow, $totalClotureDebit > 0 ? number_format($totalClotureDebit, 0, '', ' ') : '');
        $sheet->setCellValue('H' . $totalRow, $totalClotureCredit > 0 ? number_format($totalClotureCredit, 0, '', ' ') : '');
        
        $this->styleTotalRow($sheet, $totalRow, 'B', 'H');
        
        // Vérification de l'équilibre (A+C = B+D)
        $totalDebitGeneral = $totalOuvertureDebit + $totalMouvementDebit;
        $totalCreditGeneral = $totalOuvertureCredit + $totalMouvementCredit;
        
        $equilibreRow = $totalRow + 1;
        $sheet->setCellValue('B' . $equilibreRow, 'ÉQUILIBRE (A+C) = (B+D) : ' . 
            number_format($totalDebitGeneral, 0, '', ' ') . ' = ' . 
            number_format($totalCreditGeneral, 0, '', ' '));
        $sheet->mergeCells('B' . $equilibreRow . ':H' . $equilibreRow);
        
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