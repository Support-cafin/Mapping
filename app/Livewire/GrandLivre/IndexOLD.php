<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\Journal;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Services\GrandLivreImportService;
use App\Jobs\ImportGrandLivreJob;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class Index extends Component
{
    use WithPagination, WithFileUploads;
    
    // Entreprise
    public $entreprise;

    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
        'mappingDeleted' => 'handleMappingDeleted'
    ];
    
    public $sourceFilter = 'all';
    
    // Ajouter ces propriétés
    public $editingEcriture = null;
    public $editDate;
    public $editJournal;
    public $editPiece;
    public $editLibelle;
    public $editDebit;
    public $editCredit;
    public $editOldAccountId;
    public $editExercice;
    public $editLettre;
    public $showEditModal = false;
    
    // Filtres
    public $search = '';
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $journalCode = '';
    public $accountType = 'all'; // all, old, new
    public $accountId = '';
    public $viewMode = 'table'; // table, by_account, by_journal
    public $lettre = '';
    
    // Import
    public $importFile;
    public $showImportModal = false;
    public $importExercice;
    public $autoValidate = true;
    public $importResult = null;
    
    public $importing = false;
    public $importErrors = [];
    public $importSuccess = false;
    public $activeTab = 'detail'; // 'detail' ou 'recap'
    
    // Stats
    public $stats = [
        'total' => 0,
        'total_debit' => 0,
        'total_credit' => 0,
        'solde' => 0,
        'count_journaux' => 0,
    ];
    
    // Pagination
    // public $perPage = 500;
    public $sortField = 'date_ecriture';
    public $sortDirection = 'desc';
    public $sortManualLast = true;  // Nouvelle propriété : tri manuel en dernier
    
    protected $queryString = [
        'search' => ['except' => ''],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'journalCode' => ['except' => ''],
        'accountType' => ['except' => 'all'],
        'accountId' => ['except' => ''],
        'viewMode' => ['except' => 'table'],
        'lettre' => ['except' => ''],
        'perPage' => ['except' => 50],
    ];
        
    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
    
        // ❌ Aucun filtre actif par défaut
        $this->search = '';
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-06-30';
        $this->exercice = null;
        $this->journalCode = '';
        $this->accountType = 'all';
        $this->accountId = '';
        $this->lettre = '';
    
        $this->importExercice = date('Y');
    
        $this->updateStats();
    }

    
    public function updated($property)
    {
        // Reset à la page 1 quand les filtres changent
        if (in_array($property, ['search', 'dateDebut', 'dateFin', 'exercice', 'journalCode', 'accountType', 'accountId', 'lettre'])) {
            $this->resetPage();
            $this->updateStats();
        }
    }
    
    public function updateStats()
    {
        $query = $this->getBaseQuery();
        
        $this->stats['total'] = $query->count();
        $this->stats['total_debit'] = $query->sum('debit');
        $this->stats['total_credit'] = $query->sum('credit');
        // $this->stats['solde'] = $this->stats['total_debit'] - $this->stats['total_credit'];
        $this->stats['solde'] = abs($this->stats['total_debit'] - $this->stats['total_credit']);
        $this->stats['count_journaux'] = $query->distinct('journal_code')->count('journal_code');
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }
    
    public function resetFilters()
    {
        $this->reset([
            'search',
            'dateDebut',
            'dateFin',
            'exercice',
            'journalCode',
            'accountType',
            'accountId',
            'lettre'
        ]);
    
        // Pas de filtre automatique
        $this->accountType = 'all';
    
        $this->resetPage();
        $this->updateStats();
    }

    // Dans votre composant GrandLivre/Index.php existant
    // Ajoutez cette propriété et ces méthodes
    
    
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }
    
    // Méthode pour récupérer les données récapitulées (à ajouter dans Index.php)
// Dans votre composant GrandLivre/Index.php
public function getRecapData()
{
    // 1. Récupérer les comptes SYCEBNL qui ont des mappings
    $newAccounts = NewAccount::where('entreprise_id', $this->entreprise->id)
        ->whereHas('mappings')
        ->orderBy('code')
        ->get();

    $recapData = [];

    foreach ($newAccounts as $newAccount) {
        // 2. Récupérer les anciens comptes mappés à ce compte SYCEBNL
        $oldAccountIds = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('new_account_id', $newAccount->id)
            ->pluck('old_account_id')
            ->toArray();

        if (empty($oldAccountIds)) {
            continue;
        }

        // 3. Récupérer TOUTES les écritures détaillées (comme dans la capture)
        $ecritures = GrandLivre::with(['oldAccount'])
            ->where('entreprise_id', $this->entreprise->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->valides()
            ->when($this->dateDebut && $this->dateFin, function ($q) {
                $q->whereBetween('date_ecriture', [$this->dateDebut, $this->dateFin]);
            })
            ->when($this->exercice, function ($q) {
                $q->where('exercice', $this->exercice);
            })
            ->when($this->journalCode, function ($q) {
                $q->where('journal_code', $this->journalCode);
            })
            ->when($this->lettre, function ($q) {
                if ($this->lettre === 'non') {
                    $q->whereNull('lettre');
                } else {
                    $q->where('lettre', $this->lettre);
                }
            })
            ->orderBy('date_ecriture')
            ->get();

        // 4. Calculer les totaux
        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde = $totalDebit - $totalCredit;

        // 5. Organiser par ancien compte (pour le sous-total par ancien compte)
        $oldAccountsData = [];
        foreach ($oldAccountIds as $oldId) {
            $oldEcritures = $ecritures->where('old_account_id', $oldId);
            
            if ($oldEcritures->count() > 0) {
                $oldAccount = OldAccount::find($oldId);
                $oldTotalDebit = $oldEcritures->sum('debit');
                $oldTotalCredit = $oldEcritures->sum('credit');
                
                $oldAccountsData[] = [
                    'account' => $oldAccount,
                    'ecritures' => $oldEcritures,
                    'total_debit' => $oldTotalDebit,
                    'total_credit' => $oldTotalCredit,
                    'solde' => $oldTotalDebit - $oldTotalCredit,
                ];
            }
        }

        // 6. Ajouter au tableau principal seulement si il y a des écritures
        if ($ecritures->count() > 0) {
            $recapData[] = [
                'new_account' => $newAccount,
                'new_account_code' => $newAccount->code,
                'new_account_intitule' => $newAccount->intitule,
                'ecritures' => $ecritures, // Toutes les écritures détaillées
                'old_accounts_data' => $oldAccountsData, // Groupé par ancien compte
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'solde' => $solde,
                'nombre_ecritures' => $ecritures->count(),
            ];
        }
    }

    // 7. Filtrer par recherche si nécessaire
    if ($this->search) {
        $recapData = collect($recapData)->filter(function ($item) {
            return stripos($item['new_account_code'], $this->search) !== false ||
                   stripos($item['new_account_intitule'], $this->search) !== false;
        })->values()->all();
    }

    return $recapData;
}

// Méthode pour les stats récap
public function getRecapStats()
{
    $recapData = $this->getRecapData();
    
    $totalDebit = 0;
    $totalCredit = 0;
    $totalEcritures = 0;
    
    foreach ($recapData as $item) {
        $totalDebit += $item['total_debit'];
        $totalCredit += $item['total_credit'];
        $totalEcritures += $item['nombre_ecritures'];
    }
    
    return [
        'total_comptes' => count($recapData),
        'total_ecritures' => $totalEcritures,
        'total_debit' => $totalDebit,
        'total_credit' => $totalCredit,
        'solde_global' => $totalDebit - $totalCredit,
    ];
}
    
    // Dans App\Livewire\GrandLivre\Index.php
    public function export($format = 'excel')
    {
        if ($format !== 'excel') {
            // Pour PDF, vous pouvez utiliser une autre logique
            return redirect()->route('grand-livre.export-pdf', [
                'filters' => $this->getFiltersArray(),
                'tab' => $this->activeTab,
            ]);
        }
        
        // Pour Excel, choisir la bonne classe d'export selon l'onglet
        if ($this->activeTab === 'recap') {
            return redirect()->route('grand-livre.export-general', [
                'filters' => $this->getFiltersArray(),
            ]);
        } else {
            return redirect()->route('grand-livre.export-detail', [
                'filters' => $this->getFiltersArray(),
            ]);
        }
    }
    
    public function deleteEcriture($id)
    {
        $ecriture = GrandLivre::find($id);
        
        if ($ecriture && $ecriture->entreprise_id === $this->entreprise->id) {
            $ecriture->delete();
            $this->updateStats();
            session()->flash('success', 'Écriture supprimée avec succès.');
        }
    }
    
    public function lettrerEcriture($id, $lettre)
    {
        $ecriture = GrandLivre::find($id);
        
        if ($ecriture && $ecriture->entreprise_id === $this->entreprise->id) {
            $ecriture->update(['lettre' => $lettre]);
            session()->flash('success', 'Écriture lettrée: ' . $lettre);
        }
    }
    
    protected function getBaseQuery()
    {
        $query = GrandLivre::with(['oldAccount', 'newAccount']) // Plus besoin de 'journal'
            ->forEntreprise($this->entreprise->id)
            ->valides()
            ->when($this->dateDebut && $this->dateFin, function ($q) {
                $q->forPeriode($this->dateDebut, $this->dateFin);
            })
            ->when($this->exercice, function ($q) {
                $q->forExercice($this->exercice);
            })
            ->when($this->journalCode, function ($q) { // ← MODIFIÉ
                $q->where('journal_code', $this->journalCode);
            })
            ->when($this->accountType !== 'all' && $this->accountId, function ($q) {
                $q->forAccount($this->accountType, $this->accountId);
            })
            ->when($this->lettre, function ($q) {
                if ($this->lettre === 'non') {
                    $q->nonLettres();
                } else {
                    $q->lettres($this->lettre);
                }
            })
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('piece', 'like', '%' . $this->search . '%')
                          ->orWhere('libelle', 'like', '%' . $this->search . '%')
                          ->orWhere('journal_code', 'like', '%' . $this->search . '%') // ← AJOUTÉ
                          ->orWhereHas('oldAccount', function ($q) {
                              $q->where('code', 'like', '%' . $this->search . '%')
                                ->orWhere('intitule', 'like', '%' . $this->search . '%');
                          })
                          ->orWhereHas('newAccount', function ($q) {
                              $q->where('code', 'like', '%' . $this->search . '%')
                                ->orWhere('intitule', 'like', '%' . $this->search . '%');
                          });
                });
            })
            ->when($this->sourceFilter !== 'all', function ($q) {
                if ($this->sourceFilter === 'manuel') {
                    $q->where('source', 'manuel');
                } elseif ($this->sourceFilter === 'import') {
                    $q->where('source', '!=', 'manuel')->orWhereNull('source');
                }
            });
        
        return $query;
    }
    
    
    // MODIFIER
    // Ajouter ces méthodes
    public function editEcriture($id)
    {
        $this->editingEcriture = GrandLivre::with(['oldAccount', 'newAccount'])
            ->where('entreprise_id', $this->entreprise->id)
            ->findOrFail($id);
        
        // Remplir les champs du formulaire
        $this->editDate = $this->editingEcriture->date_ecriture->format('Y-m-d');
        $this->editJournal = $this->editingEcriture->journal_code;
        $this->editPiece = $this->editingEcriture->piece;
        $this->editLibelle = $this->editingEcriture->libelle;
        $this->editDebit = $this->editingEcriture->debit;
        $this->editCredit = $this->editingEcriture->credit;
        $this->editOldAccountId = $this->editingEcriture->old_account_id;
        $this->editExercice = $this->editingEcriture->exercice;
        $this->editLettre = $this->editingEcriture->lettre;
        
        $this->showEditModal = true;
        $this->dispatch('show-edit-modal');
    }
    
    public function updateEcriture()
    {
        // Validation
        $this->validate([
            'editDate' => 'required|date',
            'editOldAccountId' => 'required|exists:old_accounts,id',
            'editDebit' => 'required_without:editCredit|numeric|min:0',
            'editCredit' => 'required_without:editDebit|numeric|min:0',
            'editLibelle' => 'nullable|string|max:255',
            'editPiece' => 'nullable|string|max:100',
            'editJournal' => 'nullable|string|max:20',
            'editExercice' => 'required|integer',
            'editLettre' => 'nullable|string|max:10',
        ], [
            'editDebit.required_without' => 'Le débit ou le crédit doit être renseigné',
            'editCredit.required_without' => 'Le crédit ou le débit doit être renseigné',
            'editOldAccountId.required' => 'Le compte est requis',
        ]);
        
        try {
            // Vérifier que l'écriture appartient bien à l'entreprise
            if ($this->editingEcriture->entreprise_id !== $this->entreprise->id) {
                throw new \Exception('Cette écriture ne vous appartient pas');
            }
            
            // Vérifier si l'écriture est mappée (on ne peut pas changer le compte si mappé)
            if ($this->editingEcriture->newAccount && $this->editingEcriture->old_account_id != $this->editOldAccountId) {
                throw new \Exception('Impossible de modifier le compte d\'une écriture déjà mappée');
            }
            
            // Mettre à jour l'écriture
            $this->editingEcriture->update([
                'date_ecriture' => $this->editDate,
                'journal_code' => $this->editJournal,
                'piece' => $this->editPiece,
                'libelle' => $this->editLibelle,
                'debit' => $this->editDebit ?? 0,
                'credit' => $this->editCredit ?? 0,
                'old_account_id' => $this->editOldAccountId,
                'exercice' => $this->editExercice,
                'lettre' => $this->editLettre,
                // Synchroniser le mapping si nécessaire
                'synced' => false,
            ]);
            
            // Resynchroniser le mapping
            $this->editingEcriture->syncMapping();
            
            $this->showEditModal = false;
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Écriture modifiée avec succès'
            ]);
            
            // Recharger les données
            $this->updateStats();
            
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
    
    public function closeEditModal()
    {
        $this->showEditModal = false;
        $this->resetEditFields();
        $this->dispatch('close-edit-modal');
    }
    
    public function getOldAccountsProperty()
    {
        return \App\Models\OldAccount::where('entreprise_id', $this->entreprise->id)
            ->orderBy('code')
            ->get();
    }
    
    protected function resetEditFields()
    {
        $this->reset([
            'editDate',
            'editJournal',
            'editPiece',
            'editLibelle',
            'editDebit',
            'editCredit',
            'editOldAccountId',
            'editExercice',
            'editLettre',
            'editingEcriture',
        ]);
    }
    
    // Ajoutez cette méthode dans App\Livewire\GrandLivre\Index.php

    public function downloadTemplate()
    {
        $filepath = storage_path('app/templates/template_grand_livre.xlsx');
        
        if (!file_exists($filepath)) {
            $this->createGrandLivreTemplate();
        }
        
        return response()->download($filepath, 'template_grand_livre.xlsx');
    }
    
    private function createGrandLivreTemplate()
    {
        // Créer le répertoire s'il n'existe pas
        $dir = storage_path('app/templates');
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    
        $filepath = storage_path('app/templates/template_grand_livre.xlsx');
    
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // En-têtes (IMPORTANT: exactement ces noms)
        $headers = ['date', 'journal', 'compte', 'libelle', 'piece', 'debit', 'credit'];
        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . '1', $header);
        }
    
        // Style des en-têtes
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 12
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ]
        ];
        $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);
    
        // Exemples de données
        $examples = [
            ['15/01/2024', 'ACH', '401', 'Achat marchandises', 'F-2024-001', '', '50000'],
            ['15/01/2024', 'ACH', '607', 'Achat marchandises', 'F-2024-001', '50000', ''],
            ['20/01/2024', 'VTE', '411', 'Vente client ABC', 'V-2024-015', '120000', ''],
            ['20/01/2024', 'VTE', '701', 'Vente client ABC', 'V-2024-015', '', '100000'],
            ['20/01/2024', 'VTE', '445', 'TVA collectée', 'V-2024-015', '', '20000'],
            ['25/01/2024', 'BQ', '512', 'Virement reçu', 'VIR-001', '120000', ''],
            ['25/01/2024', 'BQ', '411', 'Virement reçu', 'VIR-001', '', '120000'],
        ];
    
        $row = 2;
        foreach ($examples as $example) {
            $sheet->setCellValue('A' . $row, $example[0]);
            $sheet->setCellValue('B' . $row, $example[1]);
            $sheet->setCellValue('C' . $row, $example[2]);
            $sheet->setCellValue('D' . $row, $example[3]);
            $sheet->setCellValue('E' . $row, $example[4]);
            $sheet->setCellValue('F' . $row, $example[5]);
            $sheet->setCellValue('G' . $row, $example[6]);
            
            // Style alterné
            if ($row % 2 == 0) {
                $sheet->getStyle('A' . $row . ':G' . $row)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F3F4F6');
            }
            
            $row++;
        }
    
        // Ajuster la largeur des colonnes
        $sheet->getColumnDimension('A')->setWidth(12); // date
        $sheet->getColumnDimension('B')->setWidth(10); // journal
        $sheet->getColumnDimension('C')->setWidth(10); // compte
        $sheet->getColumnDimension('D')->setWidth(35); // libelle
        $sheet->getColumnDimension('E')->setWidth(15); // piece
        $sheet->getColumnDimension('F')->setWidth(15); // debit
        $sheet->getColumnDimension('G')->setWidth(15); // credit
    
        // Bordures
        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'CCCCCC'],
                ],
            ],
        ];
        $sheet->getStyle('A1:G' . ($row - 1))->applyFromArray($styleArray);
    
        // Format nombre pour débit et crédit
        $sheet->getStyle('F2:G' . ($row - 1))->getNumberFormat()
            ->setFormatCode('#,##0.00');
    
        // Notes explicatives
        $noteRow = $row + 2;
        $sheet->setCellValue('A' . $noteRow, 'INSTRUCTIONS D\'IMPORT :');
        $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A' . $noteRow)->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('FEF3C7');
        
        $instructions = [
            '1. La première ligne DOIT contenir les en-têtes : date, journal, compte, libelle, piece, debit, credit',
            '2. DATE : Format JJ/MM/AAAA (ex: 15/01/2024) ou format Excel',
            '3. JOURNAL : Code journal existant (ex: ACH, VTE, BQ) - optionnel',
            '4. COMPTE : Code du compte existant (ancien ou nouveau plan) - OBLIGATOIRE',
            '5. LIBELLE : Description de l\'écriture - recommandé',
            '6. PIECE : Numéro de pièce justificative - optionnel',
            '7. DEBIT : Montant au débit (laisser vide si crédit)',
            '8. CREDIT : Montant au crédit (laisser vide si débit)',
            '9. Une seule des colonnes DEBIT ou CREDIT doit être renseignée par ligne',
            '10. Les montants peuvent utiliser la virgule ou le point comme séparateur décimal',
        ];
        
        $instructionRow = $noteRow + 1;
        foreach ($instructions as $instruction) {
            $sheet->setCellValue('A' . $instructionRow, $instruction);
            $sheet->mergeCells('A' . $instructionRow . ':G' . $instructionRow);
            $sheet->getStyle('A' . $instructionRow)->getAlignment()->setWrapText(true);
            $instructionRow++;
        }
    
        // Exemple de plan comptable
        $planRow = $instructionRow + 2;
        $sheet->setCellValue('A' . $planRow, 'EXEMPLES DE CODES COMPTES COURANTS :');
        $sheet->getStyle('A' . $planRow)->getFont()->setBold(true)->setSize(11);
        
        $plansExamples = [
            '• 101 : Capital',
            '• 401 : Fournisseurs',
            '• 411 : Clients',
            '• 445 : État - TVA',
            '• 512 : Banque',
            '• 607 : Achats de marchandises',
            '• 701 : Ventes de marchandises',
        ];
        
        $planExRow = $planRow + 1;
        foreach ($plansExamples as $planEx) {
            $sheet->setCellValue('A' . $planExRow, $planEx);
            $sheet->mergeCells('A' . $planExRow . ':G' . $planExRow);
            $planExRow++;
        }
    
        // Sauvegarde
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filepath);
        
        \Illuminate\Support\Facades\Log::info('Template Grand Livre créé', ['path' => $filepath]);
    }
    
    
    public function import()
    {
        $this->validate([
            'importFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'importExercice' => 'required|integer|min:2000|max:' . (date('Y') + 5),
        ]);
    
        $this->importing = true;
        $this->importErrors = [];
        $this->importSuccess = false;
    
        try {
            \Illuminate\Support\Facades\Log::info('Début import Grand Livre', [
                'file' => $this->importFile->getClientOriginalName(),
                'entreprise_id' => $this->entreprise->id,
                'exercice' => $this->importExercice,
                'auto_validate' => $this->autoValidate
            ]);
    
            $importClass = new \App\Imports\GrandLivreImport(
                $this->entreprise->id,
                $this->importExercice,
                $this->autoValidate
            );
    
            // Import avec gestion d'erreurs
            \Maatwebsite\Excel\Facades\Excel::import($importClass, $this->importFile->getRealPath());
    
            $importedCount = $importClass->getImportedCount();
            $errors = $importClass->getErrors();
            $warnings = $importClass->getWarnings();
    
            \Illuminate\Support\Facades\Log::info('Import terminé', [
                'imported' => $importedCount,
                'errors_count' => count($errors),
                'warnings_count' => count($warnings)
            ]);
    
            // Vérification des résultats
            if ($importedCount === 0) {
                if (empty($errors)) {
                    $this->importErrors[] = "❌ Aucune écriture importée. Vérifiez le format du fichier :";
                    $this->importErrors[] = "• La ligne 1 doit contenir : date, journal, compte, libelle, piece, debit, credit";
                    $this->importErrors[] = "• Les lignes suivantes doivent contenir les données";
                    $this->importErrors[] = "• Au moins une colonne débit ou crédit doit être renseignée";
                    
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Aucune écriture importée. Vérifiez le format du fichier.'
                    ]);
                } else {
                    $this->importErrors = $errors;
                    $this->dispatch('notify', [
                        'type' => 'error',
                        'message' => 'Erreurs d\'importation détectées.'
                    ]);
                }
                $this->importing = false;
                return;
            }
    
            // Succès
            $this->importSuccess = true;
            $this->importErrors = array_merge($errors, $warnings);
    
            // Recharger les données
            $this->resetPage();
            $this->updateStats();
    
            $message = "✓ $importedCount écriture(s) importée(s) avec succès !";
            
            if (!empty($warnings)) {
                $message .= " ⚠️ " . count($warnings) . " avertissement(s).";
            }
    
            if (!empty($errors)) {
                $message .= " ❌ " . count($errors) . " erreur(s).";
            }
    
            $this->dispatch('notify', [
                'type' => count($errors) > 0 ? 'warning' : 'success',
                'message' => $message
            ]);
    
            // Fermer le modal après 2 secondes si pas d'erreurs
            if (empty($errors)) {
                $this->dispatch('close-modal-after-delay');
            }
    
            session()->flash('success', $message);
    
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            
            foreach ($failures as $failure) {
                $this->importErrors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
            }
            
            \Illuminate\Support\Facades\Log::error('Import validation failed', [
                'failures' => $failures
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreurs de validation. Vérifiez les détails.'
            ]);
            
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            $this->importErrors[] = "❌ Erreur d'importation : " . $errorMessage;
            
            \Illuminate\Support\Facades\Log::error('Import failed', [
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur lors de l\'importation : ' . $errorMessage
            ]);
        }
    
        $this->importing = false;
    }
    
    public function getEcrituresProperty()
    {
        $query = $this->getBaseQuery();
        
        // Récupérer toutes les écritures
        $ecritures = $query->get();
        
        // Séparer les écritures manuelles des autres
        $manualEcritures = $ecritures->where('source', 'manuel');
        $otherEcritures = $ecritures->where('source', '!=', 'manuel');
        
        // Trier les autres écritures selon le tri sélectionné
        $sortedOther = $this->sortCollection($otherEcritures);
        
        // Trier les écritures manuelles selon le même critère
        $sortedManual = $this->sortCollection($manualEcritures);
        
        // Combiner : autres écritures d'abord, puis manuelles
        return $sortedOther->merge($sortedManual);
    }
    
    private function sortCollection($collection)
    {
        if ($this->sortDirection === 'asc') {
            return $collection->sortBy($this->sortField);
        } else {
            return $collection->sortByDesc($this->sortField);
        }
    }
    
    public function getJournauxProperty()
    {
        // Récupérer la liste unique des codes journaux utilisés
        return GrandLivre::forEntreprise($this->entreprise->id)
            ->select('journal_code')
            ->distinct()
            ->whereNotNull('journal_code')
            ->orderBy('journal_code')
            ->pluck('journal_code')
            ->map(function($code) {
                return ['code' => $code, 'intitule' => $code];
            });
    }
    
    public function getAccountsProperty()
    {
        if ($this->accountType === 'old') {
            return OldAccount::where('entreprise_id', $this->entreprise->id)
                ->orderBy('code')
                ->get();
        } elseif ($this->accountType === 'new') {
            return NewAccount::where('entreprise_id', $this->entreprise->id)
                ->orderBy('code')
                ->get();
        }
        
        return collect();
    }
    
    public function syncAllMappings()
    {
        try {
            $updated = 0;
            
            GrandLivre::forEntreprise($this->entreprise->id)
                ->whereNotNull('old_account_id')
                ->chunk(100, function ($ecritures) use (&$updated) {
                    foreach ($ecritures as $ecriture) {
                        if ($ecriture->syncMapping()) {
                            $updated++;
                        }
                    }
                });
            
            session()->flash('success', "$updated écriture(s) synchronisée(s) avec les mappings.");
            $this->updateStats();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Erreur lors de la synchronisation: ' . $e->getMessage());
        }
    }
    
    public function getFiltersArray(): array
    {
        return [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'journalCode' => $this->journalCode,
            'accountType' => $this->accountType,
            'accountId' => $this->accountId,
            'lettre' => $this->lettre,
            'search' => $this->search,
        ];
    }

    public function handleMappingDeleted($oldAccountId)
    {
        // Mettre à jour les écritures localement
        // $this->resetPage(); // Recharger la pagination
        $this->updateStats(); // Mettre à jour les stats
    }
    
    public function render()
    {
        return view('livewire.grand-livre.index', [
            'ecritures' => $this->ecritures,
            'journaux' => $this->journaux,
            'accounts' => $this->accounts,
            'stats' => $this->stats,
        ])->layout('layouts.app');
    }
}