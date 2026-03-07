<?php

namespace App\Exports;

use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BalancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithTitle
{
    private $entrepriseId;
    private $type;
    private $dateDebut;
    private $dateFin;
    private $exercice;
    
    public function __construct($entrepriseId, $type, $dateDebut, $dateFin, $exercice)
    {
        $this->entrepriseId = $entrepriseId;
        $this->type = $type;
        $this->dateDebut = $dateDebut;
        $this->dateFin = $dateFin;
        $this->exercice = $exercice;
    }
    
    public function collection()
    {
        if ($this->type === 'old') {
            return $this->getOldBalances();
        } else {
            return $this->getNewBalances();
        }
    }
    
    private function getOldBalances()
    {
        $accounts = OldAccount::where('entreprise_id', $this->entrepriseId)
            ->orderBy('code')
            ->get();
        
        $balances = [];
        
        foreach ($accounts as $account) {
            $ecritures = GrandLivre::forEntreprise($this->entrepriseId)
                ->where('old_account_id', $account->id)
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->where('exercice', $this->exercice);
                })
                ->valides()
                ->get();
            
            if ($ecritures->count() > 0) {
                $balances[] = [
                    'type' => 'old',
                    'code' => $account->code,
                    'intitule' => $account->intitule,
                    'total_debit' => $ecritures->sum('debit'),
                    'total_credit' => $ecritures->sum('credit'),
                    'solde' => $ecritures->sum('debit') - $ecritures->sum('credit'),
                    'ecritures_count' => $ecritures->count(),
                ];
            }
        }
        
        return collect($balances);
    }
    
    private function getNewBalances()
    {
        $accounts = NewAccount::where('entreprise_id', $this->entrepriseId)
            ->whereHas('mappings')
            ->orderBy('code')
            ->get();
        
        $balances = [];
        
        foreach ($accounts as $account) {
            $oldAccountIds = AccountMapping::where('new_account_id', $account->id)
                ->pluck('old_account_id')
                ->toArray();
            
            if (empty($oldAccountIds)) {
                continue;
            }
            
            $ecritures = GrandLivre::forEntreprise($this->entrepriseId)
                ->whereIn('old_account_id', $oldAccountIds)
                ->when($this->dateDebut && $this->dateFin, function ($q) {
                    $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
                })
                ->when($this->exercice, function ($q) {
                    $q->where('exercice', $this->exercice);
                })
                ->valides()
                ->get();
            
            if ($ecritures->count() > 0) {
                $balances[] = [
                    'type' => 'new',
                    'code' => $account->code,
                    'intitule' => $account->intitule,
                    'total_debit' => $ecritures->sum('debit'),
                    'total_credit' => $ecritures->sum('credit'),
                    'solde' => $ecritures->sum('debit') - $ecritures->sum('credit'),
                    'ecritures_count' => $ecritures->count(),
                    'old_accounts_count' => count($oldAccountIds),
                ];
            }
        }
        
        return collect($balances);
    }
    
    public function headings(): array
    {
        if ($this->type === 'old') {
            return [
                'Code Compte',
                'Intitulé',
                'Total Débit',
                'Total Crédit',
                'Solde',
                'Nature',
                'Nombre écritures',
            ];
        } else {
            return [
                'Code Compte SYCEBNL',
                'Intitulé',
                'Total Débit',
                'Total Crédit',
                'Solde',
                'Nature',
                'Nombre écritures',
                'Nombre anciens comptes',
            ];
        }
    }
    
    public function map($balance): array
    {
        if ($this->type === 'old') {
            return [
                $balance['code'],
                $balance['intitule'],
                $balance['total_debit'],
                $balance['total_credit'],
                $balance['solde'],
                $balance['solde'] > 0 ? 'Débiteur' : ($balance['solde'] < 0 ? 'Créditeur' : 'Équilibré'),
                $balance['ecritures_count'],
            ];
        } else {
            return [
                $balance['code'],
                $balance['intitule'],
                $balance['total_debit'],
                $balance['total_credit'],
                $balance['solde'],
                $balance['solde'] > 0 ? 'Débiteur' : ($balance['solde'] < 0 ? 'Créditeur' : 'Équilibré'),
                $balance['ecritures_count'],
                $balance['old_accounts_count'],
            ];
        }
    }
    
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E5E7EB']
                ]
            ],
            
            'A' => ['width' => 15],
            'B' => ['width' => 40],
            'C' => ['width' => 15],
            'D' => ['width' => 15],
            'E' => ['width' => 15],
            'F' => ['width' => 15],
            'G' => ['width' => 15],
            'H' => ['width' => 15],
            
            'C2:E1000' => [
                'numberFormat' => [
                    'formatCode' => '#,##0.00" FCFA"'
                ]
            ],
        ];
    }
    
    public function title(): string
    {
        return 'Balance ' . ($this->type === 'old' ? 'Comptes Entité' : 'Comptes SYCEBNL');
    }
}