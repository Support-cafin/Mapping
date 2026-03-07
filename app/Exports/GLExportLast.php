<?php

namespace App\Exports;

use App\Models\GrandLivre;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GrandLivreExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithColumnWidths, WithTitle, WithEvents
{
    protected $entreprise;
    protected $filters;
    protected $tab;
    
    public function __construct($entreprise, $filters, $tab = 'detail')
    {
        $this->entreprise = $entreprise;
        $this->filters = $filters;
        $this->tab = $tab;
    }
    
    public function collection()
    {
        if ($this->tab === 'recap') {
            return $this->getRecapCollection();
        }
        
        return $this->getDetailCollection();
    }
    
    protected function getDetailCollection()
    {
        $query = GrandLivre::with(['oldAccount', 'newAccount'])
            ->forEntreprise($this->entreprise->id)
            ->valides();
            
        // Appliquer les filtres
        if (!empty($this->filters['dateDebut']) && !empty($this->filters['dateFin'])) {
            $query->forPeriode($this->filters['dateDebut'], $this->filters['dateFin']);
        }
        
        if (!empty($this->filters['exercice'])) {
            $query->forExercice($this->filters['exercice']);
        }
        
        if (!empty($this->filters['journalCode'])) {
            $query->where('journal_code', $this->filters['journalCode']);
        }
        
        // MODIFICATION ICI - FILTRE PAR PLUSIEURS COMPTES
        if (!empty($this->filters['accountType']) && $this->filters['accountType'] !== 'all') {
            if (!empty($this->filters['selectedAccounts'])) {
                if ($this->filters['accountType'] === 'old') {
                    $query->whereIn('old_account_id', $this->filters['selectedAccounts']);
                } elseif ($this->filters['accountType'] === 'new') {
                    $query->whereIn('new_account_id', $this->filters['selectedAccounts']);
                }
            }
        }
        
        if (!empty($this->filters['lettre'])) {
            if ($this->filters['lettre'] === 'non') {
                $query->nonLettres();
            } else {
                $query->lettres($this->filters['lettre']);
            }
        }
        
        if (!empty($this->filters['search'])) {
            $query->where(function ($q) {
                $q->where('piece', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('libelle', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('journal_code', 'like', '%' . $this->filters['search'] . '%');
            });
        }
        
        return $query->orderBy('date_ecriture')->get();
    }
    
    protected function getRecapCollection()
    {
        // Récupérer les données récapitulées de la même manière que dans getRecapData()
        $newAccounts = \App\Models\NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereHas('mappings')
            ->orderBy('code')
            ->get();
            
        $recapData = [];
        
        foreach ($newAccounts as $newAccount) {
            $oldAccountIds = \App\Models\AccountMapping::where('entreprise_id', $this->entreprise->id)
                ->where('new_account_id', $newAccount->id)
                ->pluck('old_account_id')
                ->toArray();
                
            if (empty($oldAccountIds)) continue;
            
            $ecritures = GrandLivre::with(['oldAccount'])
                ->where('entreprise_id', $this->entreprise->id)
                ->whereIn('old_account_id', $oldAccountIds)
                ->valides();
                
            // Appliquer les mêmes filtres
            if (!empty($this->filters['dateDebut']) && !empty($this->filters['dateFin'])) {
                $ecritures->whereBetween('date_ecriture', [$this->filters['dateDebut'], $this->filters['dateFin']]);
            }
            
            if (!empty($this->filters['exercice'])) {
                $ecritures->where('exercice', $this->filters['exercice']);
            }
            
            if (!empty($this->filters['journalCode'])) {
                $ecritures->where('journal_code', $this->filters['journalCode']);
            }
            
            // MODIFICATION ICI - FILTRE PAR PLUSIEURS COMPTES POUR LE RÉCAP AUSSI
            if (!empty($this->filters['accountType']) && $this->filters['accountType'] !== 'all') {
                if (!empty($this->filters['selectedAccounts'])) {
                    if ($this->filters['accountType'] === 'old') {
                        $ecritures->whereIn('old_account_id', $this->filters['selectedAccounts']);
                    } elseif ($this->filters['accountType'] === 'new') {
                        // Pour le récap, on filtre sur les anciens comptes qui sont mappés aux nouveaux comptes sélectionnés
                        $oldAccountIds = \App\Models\AccountMapping::where('entreprise_id', $this->entreprise->id)
                            ->whereIn('new_account_id', $this->filters['selectedAccounts'])
                            ->pluck('old_account_id')
                            ->toArray();
                        $ecritures->whereIn('old_account_id', $oldAccountIds);
                    }
                }
            }
            
            if (!empty($this->filters['lettre'])) {
                if ($this->filters['lettre'] === 'non') {
                    $ecritures->whereNull('lettre');
                } else {
                    $ecritures->where('lettre', $this->filters['lettre']);
                }
            }
            
            $ecritures = $ecritures->orderBy('date_ecriture')->get();
            
            if ($ecritures->count() > 0) {
                // Pour chaque écriture, créer une ligne dans l'export
                foreach ($ecritures as $ecriture) {
                    $recapData[] = [
                        'date' => $ecriture->date_ecriture,
                        'piece' => $ecriture->piece,
                        'journal' => $ecriture->journal_code,
                        'compte_ancien' => $ecriture->oldAccount->code ?? '',
                        'compte_sycebnl' => $newAccount->code,
                        'libelle' => $ecriture->libelle,
                        'debit' => $ecriture->debit,
                        'credit' => $ecriture->credit,
                        'new_account_code' => $newAccount->code,
                        'new_account_intitule' => $newAccount->intitule,
                    ];
                }
            }
        }
        
        return collect($recapData);
    }
    
    public function headings(): array
    {
        if ($this->tab === 'recap') {
            return [
                'Date',
                'N° de pièce',
                'Code journal',
                'N° de compte (Ancien)',
                'Compte SYCEBNL',
                'Libellé',
                'Montant débit',
                'Montant crédit',
            ];
        }
        
        return [
            'Date',
            'Journal',
            'Pièce',
            'Compte Entité',
            'Libellé',
            'Montant Débit',
            'Montant Crédit',
            'Compte SYCEBNL',
            'Statut Mapping',
        ];
    }
    
    public function map($row): array
    {
        if ($this->tab === 'recap') {
            return [
                $row['date'] ? \Carbon\Carbon::parse($row['date'])->format('d/m/Y') : '',
                $row['piece'] ?? '',
                $row['journal'] ?? '',
                $row['compte_ancien'] ?? '',
                $row['compte_sycebnl'] ?? '',
                $row['libelle'] ?? '',
                $row['debit'] > 0 ? number_format($row['debit'], 0, '', ' ') : '',
                $row['credit'] > 0 ? number_format($row['credit'], 0, '', ' ') : '',
            ];
        }
        
        // Pour le mode détail
        return [
            $row->date_ecriture->format('d/m/Y'),
            $row->journal_code,
            $row->piece,
            $row->oldAccount ? $row->oldAccount->code : '',
            $row->libelle ?? '',
            $row->debit > 0 ? number_format($row->debit, 0, '', ' ') : '',
            $row->credit > 0 ? number_format($row->credit, 0, '', ' ') : '',
            $row->newAccount ? $row->newAccount->code : '',
            $row->newAccount ? 'Mappé' : 'Non mappé',
        ];
    }
    
    public function title(): string
    {
        if ($this->tab === 'recap') {
            return 'GRAND LIVRE GENERAL';
        }
        
        return 'GRAND LIVRE';
    }
    
    public function columnWidths(): array
    {
        if ($this->tab === 'recap') {
            return [
                'A' => 12, // Date
                'B' => 15, // Pièce
                'C' => 12, // Journal
                'D' => 18, // Compte ancien
                'E' => 15, // Compte SYCEBNL
                'F' => 40, // Libellé
                'G' => 18, // Débit
                'H' => 18, // Crédit
            ];
        }
        
        return [
            'A' => 12, // Date
            'B' => 10, // Journal
            'C' => 15, // Pièce
            'D' => 15, // Compte Entité
            'E' => 40, // Libellé
            'F' => 18, // Débit
            'G' => 18, // Crédit
            'H' => 15, // Compte SYCEBNL
            'I' => 15, // Statut
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Style par défaut pour tout le document
        $sheet->getStyle('A1:Z1000')->getFont()->setName('Arial')->setSize(10);
        
        // En-têtes
        $sheet->getStyle('A1:' . ($this->tab === 'recap' ? 'H1' : 'I1'))->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4F81BD'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        
        // Bordures pour toutes les cellules avec données
        $lastRow = $sheet->getHighestRow();
        $lastColumn = $this->tab === 'recap' ? 'H' : 'I';
        
        $sheet->getStyle('A1:' . $lastColumn . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);
        
        // Alignement des montants
        $montantColumns = $this->tab === 'recap' ? ['G', 'H'] : ['F', 'G'];
        foreach ($montantColumns as $col) {
            $sheet->getStyle($col . '2:' . $col . $lastRow)
                  ->getAlignment()
                  ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }
        
        // Couleurs pour les montants (optionnel)
        if ($this->tab === 'detail') {
            // Débit en noir
            $sheet->getStyle('F2:F' . $lastRow)
                  ->getFont()
                  ->getColor()
                  ->setARGB('000000');
                  
            // Crédit en noir
            $sheet->getStyle('G2:G' . $lastRow)
                  ->getFont()
                  ->getColor()
                  ->setARGB('000000');
        }
    }
    
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Fusionner les cellules pour le titre principal
                $sheet = $event->sheet->getDelegate();
                
                // Ajouter un titre principal
                $sheet->insertNewRowBefore(1, 3);
                
                $title = $this->tab === 'recap' 
                    ? 'GRAND LIVRE GÉNÉRAL - ' . $this->entreprise->nom . ' (' . $this->entreprise->code . ')'
                    : 'GRAND LIVRE - ' . $this->entreprise->nom . ' (' . $this->entreprise->code . ')';
                    
                $sheet->setCellValue('A1', $title);
                
                // Fusionner les cellules pour le titre
                $lastColumn = $this->tab === 'recap' ? 'H' : 'I';
                $sheet->mergeCells('A1:' . $lastColumn . '1');
                
                // Style du titre
                $sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 14,
                        'color' => ['rgb' => '1F497D'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                
                // Filtrer les dates
                $periode = '';
                if (!empty($this->filters['dateDebut']) && !empty($this->filters['dateFin'])) {
                    $periode = 'Période : ' . 
                        \Carbon\Carbon::parse($this->filters['dateDebut'])->format('d/m/Y') . 
                        ' au ' . 
                        \Carbon\Carbon::parse($this->filters['dateFin'])->format('d/m/Y');
                }
                
                // Ajouter l'information des comptes sélectionnés
                if (!empty($this->filters['accountType']) && $this->filters['accountType'] !== 'all' && !empty($this->filters['selectedAccounts'])) {
                    $compteInfo = $this->filters['accountType'] === 'old' ? 'Comptes entité' : 'Comptes SYCEBNL';
                    $compteInfo .= ' sélectionnés : ' . count($this->filters['selectedAccounts']) . ' compte(s)';
                    
                    if (!empty($periode)) {
                        $periode .= ' | ' . $compteInfo;
                    } else {
                        $periode = $compteInfo;
                    }
                }
                
                $sheet->setCellValue('A2', $periode);
                $sheet->mergeCells('A2:' . $lastColumn . '2');
                $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Date d'export
                $sheet->setCellValue('A3', 'Exporté le : ' . now()->format('d/m/Y à H:i'));
                $sheet->mergeCells('A3:' . $lastColumn . '3');
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                
                // Décaler les en-têtes
                $sheet->fromArray($this->headings(), null, 'A4', true);
                
                // Décaler les données
                $data = $this->collection()->map([$this, 'map'])->toArray();
                $sheet->fromArray($data, null, 'A5', true);
                
                // Ajuster les largeurs après l'ajout du titre
                foreach ($this->columnWidths() as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }
                
                // Total général
                $lastDataRow = 4 + count($data);
                $totalRow = $lastDataRow + 2;
                 
                if ($this->tab === 'detail') {
                    $totalDebit = $this->collection()->sum('debit');
                    $totalCredit = $this->collection()->sum('credit');
                    
                    $sheet->setCellValue('E' . $totalRow, 'TOTAUX :');
                    $sheet->getStyle('E' . $totalRow)->getFont()->setBold(true);
                    
                    $sheet->setCellValue('F' . $totalRow, number_format($totalDebit, 0, '', ' ') . ' FCFA');
                    $sheet->getStyle('F' . $totalRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                    
                    $sheet->setCellValue('G' . $totalRow, number_format($totalCredit, 0, '', ' ') . ' FCFA');
                    $sheet->getStyle('G' . $totalRow)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                    ]);
                    
                    // Différence
                    $diffRow = $totalRow + 1;
                    $difference = abs($totalDebit - $totalCredit);
                    $sheet->setCellValue('E' . $diffRow, 'SOLDE :');
                    $sheet->getStyle('E' . $diffRow)->getFont()->setBold(true);
                    
                    // Définition de la cellule de destination et de la couleur
                    if ($totalDebit >= $totalCredit) {
                        // Solde débiteur : afficher dans la colonne F (Débit)
                        $sheet->setCellValue('F' . $diffRow, number_format($difference, 0, '', ' ') . ' FCFA');
                        // Laisser la colonne G vide
                        $sheet->setCellValue('G' . $diffRow, '');
                        
                        $sheet->getStyle('F' . $diffRow)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        ]);
                    } else {
                        // Solde créditeur : afficher dans la colonne G (Crédit)
                        $sheet->setCellValue('G' . $diffRow, number_format($difference, 0, '', ' ') . ' FCFA');
                        // Laisser la colonne F vide
                        $sheet->setCellValue('F' . $diffRow, '');
                        
                        $sheet->getStyle('G' . $diffRow)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                        ]);
                    }
                    
                }
            },
        ];
    }
}