<?php

// App/Livewire/Balances/ManageBalances.php
namespace App\Livewire\Balances;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Entreprise;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\OldBalance;
use App\Models\NewBalance;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OldBalancesImport;
use App\Imports\NewBalancesImport;

class ManageBalances extends Component
{
    use WithFileUploads, WithPagination;

    public $entreprise;
    
    // Pour l'ajout manuel
    public $balanceType = 'old'; // 'old' ou 'new'
    public $account_id;
    public $debit = 0;
    public $credit = 0;
    public $solde = 0;
    public $periode;
    public $exercice;
    
    // Pour l'import Excel
    public $importType = 'old'; // 'old' ou 'new'
    public $excelFile;
    public $importing = false;
    public $importProgress = 0;
    public $importErrors = [];
    public $importSuccess = false;
    
    // Filtres et données
    public $search = '';
    public $typeFilter = 'all'; // 'all', 'old', 'new'
    public $exerciceFilter;
    public $periodeFilter;
    
    // Supprimez ces propriétés
    // public $oldBalances = [];
    // public $newBalances = [];
    
    public $accounts = []; // Pour le select

    // Ajoutez des propriétés pour la pagination
    public $perPage = 20;
    public $oldPage = 1;
    public $newPage = 1;

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->exercice = date('Y');
        $this->periode = date('m');
        $this->loadAccounts();
        
        // Initialisez les filtres d'exercice avec l'année en cours
        $this->exerciceFilter = date('Y');
    }

    public function loadAccounts()
    {
        if ($this->balanceType === 'old') {
            $this->accounts = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->orderBy('code')
                ->get(['id', 'code', 'intitule'])
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'text' => "{$account->code} - {$account->intitule}"
                    ];
                })->toArray();
        } else {
            $this->accounts = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->orderBy('code')
                ->get(['id', 'code', 'intitule'])
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'text' => "{$account->code} - {$account->intitule}"
                    ];
                })->toArray();
        }
    }

    // Supprimez la méthode loadBalances() et utilisez plutôt des propriétés computed

    public function getOldBalancesProperty()
    {
        $query = OldBalance::where('entreprise_id', $this->entreprise->id)
            ->with('oldAccount')
            ->orderBy('exercice', 'desc')
            ->orderBy('periode', 'desc');

        if ($this->exerciceFilter) {
            $query->where('exercice', $this->exerciceFilter);
        }

        if ($this->periodeFilter) {
            $query->where('periode', $this->periodeFilter);
        }

        if ($this->search) {
            $query->whereHas('oldAccount', function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('intitule', 'like', "%{$this->search}%");
            });
        }

        return $query->paginate($this->perPage, ['*'], 'old_page');
    }

    public function getNewBalancesProperty()
    {
        $query = NewBalance::where('entreprise_id', $this->entreprise->id)
            ->with('newAccount')
            ->orderBy('exercice', 'desc')
            ->orderBy('periode', 'desc');

        if ($this->exerciceFilter) {
            $query->where('exercice', $this->exerciceFilter);
        }

        if ($this->periodeFilter) {
            $query->where('periode', $this->periodeFilter);
        }

        if ($this->search) {
            $query->whereHas('newAccount', function ($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhere('intitule', 'like', "%{$this->search}%");
            });
        }

        return $query->paginate($this->perPage, ['*'], 'new_page');
    }

    public function saveBalance()
    {
        $this->validate([
            'balanceType' => 'required|in:old,new',
            'account_id' => 'required|exists:' . ($this->balanceType === 'old' ? 'old_accounts' : 'new_accounts') . ',id',
            'debit' => 'required|numeric|min:0',
            'credit' => 'required|numeric|min:0',
            'solde' => 'required|numeric',
            'periode' => 'required|string|max:20',
            'exercice' => 'required|integer|min:2000|max:2100',
        ]);

        $data = [
            'entreprise_id' => $this->entreprise->id,
            'debit' => $this->debit,
            'credit' => $this->credit,
            'solde' => $this->solde,
            'periode' => $this->periode,
            'exercice' => $this->exercice,
        ];

        if ($this->balanceType === 'old') {
            $data['old_account_id'] = $this->account_id;
            OldBalance::create($data);
        } else {
            $data['new_account_id'] = $this->account_id;
            NewBalance::create($data);
        }

        $this->resetForm();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Balance ajoutée avec succès!']);
    }

    public function importBalances()
    {
        $this->validate([
            'importType' => 'required|in:old,new',
            'excelFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ]);

        $this->importing = true;
        $this->importErrors = [];
        $this->importSuccess = false;

        try {
            $importClass = $this->importType === 'old' 
                ? new OldBalancesImport($this->entreprise->id)
                : new NewBalancesImport($this->entreprise->id);

            Excel::import($importClass, $this->excelFile);
            
            $this->importSuccess = true;
            $this->importErrors = $importClass->getErrors();
            
            $this->dispatch('notify', [
                'type' => 'success', 
                'message' => 'Importation terminée! ' . $importClass->getImportedCount() . ' lignes importées.'
            ]);
            
        } catch (\Exception $e) {
            $this->importErrors[] = 'Erreur lors de l\'importation: ' . $e->getMessage();
            $this->dispatch('notify', ['type' => 'error', 'message' => 'Erreur d\'importation']);
        }

        $this->importing = false;
        $this->excelFile = null;
    }

    public function deleteBalance($type, $id)
    {
        if ($type === 'old') {
            OldBalance::find($id)->delete();
        } else {
            NewBalance::find($id)->delete();
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Balance supprimée!']);
    }

    public function deleteAllBalances($type)
    {
        if ($type === 'old') {
            OldBalance::where('entreprise_id', $this->entreprise->id)->delete();
        } else {
            NewBalance::where('entreprise_id', $this->entreprise->id)->delete();
        }

        $this->dispatch('notify', ['type' => 'success', 'message' => 'Toutes les balances ont été supprimées!']);
    }

    public function resetForm()
    {
        $this->account_id = null;
        $this->debit = 0;
        $this->credit = 0;
        $this->solde = 0;
        $this->periode = date('m');
    }

    public function updatedBalanceType()
    {
        $this->loadAccounts();
        $this->account_id = null;
    }

    public function updatedExerciceFilter()
    {
        $this->resetPage();
    }

    public function updatedPeriodeFilter()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function resetPage()
    {
        $this->resetPage('old_page');
        $this->resetPage('new_page');
    }

    public function downloadTemplate($type)
    {
        $filename = $type === 'old' ? 'template_old_balances.xlsx' : 'template_new_balances.xlsx';
        $filepath = storage_path('app/templates/' . $filename);
        
        if (!file_exists($filepath)) {
            $this->createTemplateFile($type);
        }
        
        return response()->download($filepath);
    }

    private function createTemplateFile($type)
    {
        // Créer un template Excel simple
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $headers = [
            'A1' => 'numero_compte',
            'B1' => 'debit',
            'C1' => 'credit',
            'D1' => 'solde',
            'E1' => 'periode',
            'F1' => 'exercice'
        ];
        
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Ajouter des exemples
        $sheet->setCellValue('A2', $type === 'old' ? '411100' : '4111');
        $sheet->setCellValue('B2', '1000.00');
        $sheet->setCellValue('C2', '0.00');
        $sheet->setCellValue('D2', '1000.00');
        $sheet->setCellValue('E2', '01');
        $sheet->setCellValue('F2', date('Y'));
        
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save(storage_path('app/templates/' . ($type === 'old' ? 'template_old_balances.xlsx' : 'template_new_balances.xlsx')));
    }

    public function render()
    {
        return view('livewire.balances.manage-balances', [
            'oldBalances' => $this->oldBalances,
            'newBalances' => $this->newBalances,
        ])->layout('layouts.app');
    }
}