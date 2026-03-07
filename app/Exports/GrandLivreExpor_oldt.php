<?php

namespace App\Exports;

use App\Models\GrandLivre;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GrandLivreExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $ecritures;
    
    public function __construct($ecritures)
    {
        $this->ecritures = $ecritures;
    }
    
    public function collection()
    {
        return $this->ecritures;
    }
    
    public function headings(): array
    {
        return [
            'Date',
            'Journal',
            'Pièce',
            'Code Compte',
            'Intitulé Compte',
            'Libellé',
            'Débit',
            'Crédit',
            'Lettre',
            'Exercice',
        ];
    }
    
    public function map($ecriture): array
    {
        $compte = $ecriture->oldAccount ?? $ecriture->newAccount;
        
        return [
            $ecriture->date_ecriture->format('d/m/Y'),
            $ecriture->journal?->code,
            $ecriture->piece,
            $compte?->code,
            $compte?->intitule,
            $ecriture->libelle,
            $ecriture->debit,
            $ecriture->credit,
            $ecriture->lettre,
            $ecriture->exercice,
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        // Style de l'en-tête
        $sheet->getStyle('A1:J1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FFE0E0E0'],
            ],
        ]);
        
        // Formater les colonnes monétaires
        $sheet->getStyle('G2:G' . ($this->ecritures->count() + 1))->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle('H2:H' . ($this->ecritures->count() + 1))->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Auto-width
        foreach(range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        return [];
    }
}