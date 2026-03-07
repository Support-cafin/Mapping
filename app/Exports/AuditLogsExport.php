<?php

namespace App\Exports;

use App\Models\AuditLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromQuery, WithMapping, WithHeadings, WithStyles
{
    private $entrepriseId;
    private $filters;
    
    public function __construct(int $entrepriseId, array $filters = [])
    {
        $this->entrepriseId = $entrepriseId;
        $this->filters = $filters;
    }
    
    public function query()
    {
        $query = AuditLog::with(['user', 'entreprise'])
            ->where('entreprise_id', $this->entrepriseId);
            
        if (!empty($this->filters['date_start']) && !empty($this->filters['date_end'])) {
            $query->whereBetween('created_at', [
                $this->filters['date_start'] . ' 00:00:00',
                $this->filters['date_end'] . ' 23:59:59'
            ]);
        }
        
        if (!empty($this->filters['model'])) {
            $query->where('model', $this->filters['model']);
        }
        
        if (!empty($this->filters['action'])) {
            $query->where('action', $this->filters['action']);
        }
        
        if (!empty($this->filters['user_id'])) {
            $query->where('user_id', $this->filters['user_id']);
        }
        
        return $query->orderBy('created_at', 'desc');
    }
    
    public function headings(): array
    {
        return [
            'ID',
            'Date et heure',
            'Utilisateur',
            'Type d\'action',
            'Modèle',
            'ID Modèle',
            'Description',
            'Changements',
            'Adresse IP',
            'Navigateur',
        ];
    }
    
    public function map($log): array
    {
        return [
            $log->id,
            $log->created_at->format('d/m/Y H:i:s'),
            $log->user->name ?? 'Système',
            $log->formatted_action,
            $log->model,
            $log->model_id,
            $log->detailed_description,
            $log->changes_summary,
            $log->ip_address,
            $log->user_agent,
        ];
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            // Style pour l'en-tête
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E5E7EB']
                ]
            ],
            
            // Largeur automatique des colonnes
            'A' => ['width' => 10],
            'B' => ['width' => 20],
            'C' => ['width' => 20],
            'D' => ['width' => 15],
            'E' => ['width' => 15],
            'F' => ['width' => 10],
            'G' => ['width' => 50],
            'H' => ['width' => 40],
            'I' => ['width' => 15],
            'J' => ['width' => 40],
        ];
    }
}