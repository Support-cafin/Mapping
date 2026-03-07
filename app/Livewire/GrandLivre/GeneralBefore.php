<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Exports\GrandLivreGeneralExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class General extends Component
{
    public $entreprise;
    
    public $exportProgress = 0;
    public $exportMessage = '';
    
    protected $listeners = [
        'refreshGrandLivre' => '$refresh',
        'loadMore' => 'loadMore',
    ];
 
    // Filtres
    public $search = '';
    public $dateDebut;
    public $dateFin;
    public $exercice;
    public $journalCode = '';
    public $lettre = '';

    // Pagination et infinite scroll
    public $perPage = 50; // Augmenté pour moins de requêtes
    public $loadedCount = 0;
    public $totalCount = 0;
    public $hasMore = true;
    public $isLoading = false;
    public $exporting = false;

    // Données paginées (pas dans le cache)
    private $paginatedData = [];

    // QueryString pour persistance
    protected $queryString = [
        'search' => ['except' => ''],
        'dateDebut' => ['except' => ''],
        'dateFin' => ['except' => ''],
        'exercice' => ['except' => ''],
        'journalCode' => ['except' => ''],
        'lettre' => ['except' => ''],
    ];

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        
        // Filtres par défaut
        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';
        $this->exercice = null;
        $this->journalCode = '';
        $this->lettre = '';
    }

    public function updated($property)
    {
        $filterProperties = [
            'search', 'dateDebut', 'dateFin', 'exercice', 'journalCode', 'lettre'
        ];

        if (in_array($property, $filterProperties)) {
            $this->resetPagination();
        }
    }

    public function resetPagination()
    {
        $this->loadedCount = 0;
        $this->hasMore = true;
        $this->isLoading = false;
        $this->paginatedData = [];
    }

    public function loadMore()
    {
        if ($this->isLoading || !$this->hasMore) {
            return;
        }

        $this->isLoading = true;
        $this->loadedCount += $this->perPage;
        $this->isLoading = false;
        
        // Vérifier s'il reste des données
        $totalAccounts = $this->getTotalAccountsCount();
        if ($this->loadedCount >= $totalAccounts) {
            $this->hasMore = false;
        }
    }

    /**
     * Compte le nombre total de comptes SYCEBNL avec filtres
     */
    private function getTotalAccountsCount(): int
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = DB::table('grand_livres as gl')
            ->select(DB::raw('COUNT(DISTINCT na.code) as total'))
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id');
        
        $this->applyQueryFilters($query);
        
        return $query->value('total') ?? 0;
    }

    /**
     * Récupère les données paginées
     */
    private function getPaginatedData(): array
    {
        if (empty($this->paginatedData)) {
            $this->paginatedData = $this->fetchPaginatedData();
        }
        
        return $this->paginatedData;
    }

    /**
     * Récupère les données paginées depuis la base
     */
    private function fetchPaginatedData(): array
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            // D'abord, récupérer les comptes SYCEBNL paginés
            $accountsQuery = DB::table('grand_livres as gl')
                ->select([
                    'na.code as new_account_code',
                    'na.intitule as new_account_intitule',
                    DB::raw('SUM(gl.debit) as total_debit'),
                    DB::raw('SUM(gl.credit) as total_credit'),
                    DB::raw('COUNT(gl.id) as nombre_ecritures')
                ])
                ->leftJoin('account_mappings as am', function($join) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                         ->where('am.entreprise_id', $this->entreprise->id);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->where('gl.entreprise_id', $this->entreprise->id)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id')
                ->groupBy('na.code', 'na.intitule');
            
            $this->applyQueryFilters($accountsQuery);
            $accountsQuery->orderBy('na.code');
            
            // Pagination manuelle
            $accounts = $accountsQuery->limit($this->loadedCount)->get();
            
            if ($accounts->isEmpty()) {
                return [];
            }
            
            // Pour chaque compte, récupérer les écritures
            $result = [];
            $accountCodes = $accounts->pluck('new_account_code')->toArray();
            
            // Récupérer toutes les écritures pour ces comptes en une seule requête
            $ecritures = $this->getEcrituresForAccounts($accountCodes);
            
            foreach ($accounts as $account) {
                $accountEcritures = $ecritures->where('new_account_code', $account->new_account_code);
                
                $result[] = [
                    'new_account_code' => $account->new_account_code,
                    'new_account_intitule' => $account->new_account_intitule,
                    'ecritures' => $accountEcritures,
                    'total_debit' => (float) $account->total_debit,
                    'total_credit' => (float) $account->total_credit,
                    'nombre_ecritures' => (int) $account->nombre_ecritures,
                ];
            }
            
            return $result;
            
        } catch (\Exception $e) {
            \Log::error('Erreur fetchPaginatedData', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Récupère les écritures pour une liste de comptes
     */
    private function getEcrituresForAccounts(array $accountCodes): Collection
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
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
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id');
        
        $this->applyQueryFilters($query);
        $query->orderBy('na.code')
              ->orderBy('gl.date_ecriture')
              ->orderBy('gl.id');
        
        return $query->get();
    }

    /**
     * Applique les filtres à une requête DB
     */
    private function applyQueryFilters($query): void
    {
        if ($this->dateDebut && $this->dateFin) {
            $query->whereBetween('gl.date_ecriture', [$this->dateDebut, $this->dateFin]);
        }
        
        if ($this->exercice) {
            $query->where('gl.exercice', $this->exercice);
        }
        
        if ($this->journalCode) {
            $query->where('gl.journal_code', $this->journalCode);
        }
        
        if ($this->lettre) {
            if ($this->lettre === 'non') {
                $query->whereNull('gl.lettre');
            } else {
                $query->where('gl.lettre', $this->lettre);
            }
        }
        
        if ($this->search) {
            $searchTerm = '%' . $this->search . '%';
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

    /**
     * Propriété pour les données affichées
     */
    public function getRecapDataProperty(): array
    {
        if ($this->loadedCount === 0) {
            $this->loadedCount = $this->perPage;
        }
        
        $this->totalCount = $this->getTotalAccountsCount();
        
        if ($this->loadedCount >= $this->totalCount) {
            $this->hasMore = false;
        }
        
        return $this->getPaginatedData();
    }
    
    /**
     * Statistiques légères (calculées à la volée)
     */
    public function getRecapStatsProperty(): array
    {
        $data = $this->getPaginatedData();
        
        $stats = [
            'total_comptes' => count($data),
            'total_ecritures' => 0,
            'total_debit' => 0,
            'total_credit' => 0,
        ];
        
        foreach ($data as $item) {
            $stats['total_ecritures'] += $item['nombre_ecritures'];
            $stats['total_debit'] += $item['total_debit'];
            $stats['total_credit'] += $item['total_credit'];
        }
        
        $soldeGlobal = $stats['total_debit'] - $stats['total_credit'];
        
        return array_merge($stats, [
            'solde_global' => $soldeGlobal,
            'solde_global_absolu' => abs($soldeGlobal),
            'is_debiteur' => $soldeGlobal > 0,
            'is_crediteur' => $soldeGlobal < 0,
            'is_equilibre' => $soldeGlobal == 0,
        ]);
    }

    public function getJournauxProperty()
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        return GrandLivre::where('entreprise_id', $this->entreprise->id)
            ->select('journal_code')
            ->distinct()
            ->where('exercice_id', $exo->id)
            ->whereNotNull('journal_code')
            ->where('journal_code', '!=', '')
            ->orderBy('journal_code')
            ->pluck('journal_code');
    }

    public function resetFilters()
    {
        $this->reset([
            'search',
            'dateDebut',
            'dateFin',
            'exercice',
            'journalCode',
            'lettre',
        ]);

        $this->dateDebut = '2024-01-01';
        $this->dateFin = '2024-12-31';

        $this->resetPagination();
    }

    public function export($format = 'excel')
{
    // Empêcher les déclenchements multiples
    if ($this->exporting) {
        return;
    }

    $this->exporting = true;
    $this->exportProgress = 5;
    $this->exportMessage = 'Préparation de l\'export...';

    try {
        // Récupérer les filtres
        $filters = $this->getFiltersArray();
        
        $this->exportProgress = 20;
        $this->exportMessage = 'Récupération des données...';
        
        // Créer l'export optimisé
        $export = new GrandLivreGeneralExport(
            $this->entreprise->id,
            $filters
        );
        
        $this->exportProgress = 40;
        $this->exportMessage = 'Génération du fichier Excel...';
        
        // Générer un nom de fichier unique
        $fileName = 'grand_livre_general_' . $this->entreprise->code . '_' . date('Ymd_His') . '.xlsx';
        
        $this->exportProgress = 60;
        $this->exportMessage = 'Téléchargement...';
        
        // Exporter avec chunking pour optimiser la mémoire
        return Excel::download(
            $export,
            $fileName,
            \Maatwebsite\Excel\Excel::XLSX,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'memory' => '2048M',
                'timeout' => 300,
            ]
        );
        
    } catch (\Exception $e) {
        \Log::error('Erreur export Excel', [
            'entreprise_id' => $this->entreprise->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->exporting = false;
        $this->exportProgress = 0;
        $this->exportMessage = 'Erreur lors de l\'export : ' . $e->getMessage();
        
        session()->flash('error', 'Erreur lors de l\'export Excel : ' . $e->getMessage());
        return null;
    } finally {
        $this->exporting = false;
    }
}


    /**
     * Version alternative avec export direct (sans class)
     */
    public function exportDirect()
    {
        if ($this->exporting) {
            return;
        }

        $this->exporting = true;
        
        try {
            // Récupérer les données pour l'export (version simplifiée)
            $dataForExport = $this->getDataForExport();
            
            // Générer le fichier Excel directement
            return $this->generateExcelFile($dataForExport);
            
        } catch (\Exception $e) {
            \Log::error('Erreur export direct', ['error' => $e->getMessage()]);
            $this->exporting = false;
            session()->flash('error', 'Erreur export : ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère les données pour l'export (optimisé)
     */
    private function getDataForExport(): array
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        try {
            // Requête optimisée pour l'export
            $query = DB::table('grand_livres as gl')
                ->select([
                    'gl.date_ecriture',
                    'gl.piece',
                    'gl.journal_code',
                    'oa.code as old_account_code',
                    'oa.intitule as old_account_intitule',
                    'na.code as new_account_code',
                    'na.intitule as new_account_intitule',
                    'gl.libelle',
                    'gl.debit',
                    'gl.credit',
                    DB::raw('(gl.debit - gl.credit) as solde')
                ])
                ->leftJoin('account_mappings as am', function($join) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                         ->where('am.entreprise_id', $this->entreprise->id);
                })
                ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->where('gl.entreprise_id', $this->entreprise->id)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id')
                ->orderBy('na.code')
                ->orderBy('gl.date_ecriture')
                ->orderBy('gl.id');
            
            // Appliquer les mêmes filtres
            if ($this->dateDebut && $this->dateFin) {
                $query->whereBetween('gl.date_ecriture', [$this->dateDebut, $this->dateFin]);
            }
            
            if ($this->exercice) {
                $query->where('gl.exercice', $this->exercice);
            }
            
            if ($this->journalCode) {
                $query->where('gl.journal_code', $this->journalCode);
            }
            
            if ($this->search) {
                $searchTerm = '%' . $this->search . '%';
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('gl.libelle', 'like', $searchTerm)
                      ->orWhere('gl.piece', 'like', $searchTerm)
                      ->orWhere('oa.code', 'like', $searchTerm)
                      ->orWhere('oa.intitule', 'like', $searchTerm)
                      ->orWhere('na.code', 'like', $searchTerm)
                      ->orWhere('na.intitule', 'like', $searchTerm);
                });
            }
            
            return $query->get()->toArray();
            
        } catch (\Exception $e) {
            \Log::error('Erreur getDataForExport', ['error' => $e->getMessage()]);
            return [];
        }
    }
    
    /**
     * Génère le fichier Excel directement
     */
    private function generateExcelFile($data)
    {
        // Créer le fichier Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // En-têtes
        $headers = [
            'Date', 'Pièce', 'Journal', 'Compte Ancien', 
            'Libellé Compte Ancien', 'Compte SYCEBNL', 
            'Libellé Compte SYCEBNL', 'Libellé Écriture',
            'Débit', 'Crédit', 'Solde'
        ];
        
        $sheet->fromArray($headers, null, 'A1');
        
        // Données
        $rowData = [];
        foreach ($data as $item) {
            $rowData[] = [
                $item->date_ecriture ? \Carbon\Carbon::parse($item->date_ecriture)->format('d/m/Y') : '',
                $item->piece,
                $item->journal_code,
                $item->old_account_code,
                $item->old_account_intitule,
                $item->new_account_code,
                $item->new_account_intitule,
                $item->libelle,
                $item->debit,
                $item->credit,
                $item->solde
            ];
        }
        
        $sheet->fromArray($rowData, null, 'A2');
        
        // Style
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);
        $sheet->getStyle('I:K')->getNumberFormat()->setFormatCode('#,##0.00');
        
        // Largeur automatique
        foreach (range('A', 'K') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Nom du fichier
        $fileName = 'grand_livre_general_' . $this->entreprise->code . '_' . date('Ymd_His') . '.xlsx';
        
        // Écrire le fichier
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Stocker temporairement
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer->save($tempFile);
        
        // Retourner la réponse de téléchargement
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
    
    /**
     * Version simple sans progression (pour test)
     */
    public function exportSimple()
    {
        try {
            $filters = $this->getFiltersArray();
            
            // Log pour débogage
            \Log::info('Export Excel lancé', [
                'entreprise_id' => $this->entreprise->id,
                'filters' => $filters
            ]);
            
            // Redirection vers la route d'export (séparée)
            return redirect()->route('grand-livre.export-general-simple', $filters);
            
        } catch (\Exception $e) {
            \Log::error('Erreur export simple', ['error' => $e->getMessage()]);
            session()->flash('error', 'Erreur export : ' . $e->getMessage());
            return null;
        }
    }
    
    public function getFiltersArray(): array
    {
        return [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'journalCode' => $this->journalCode,
            'lettre' => $this->lettre,
            'search' => $this->search,
            'entreprise_id' => $this->entreprise->id,
        ];
    }

    /**
     * Génère le nom du fichier d'export
     */
    private function generateExportFilename($format): string
    {
        $date = now()->format('Y-m-d');
        $entrepriseCode = $this->entreprise->code;
        $period = '';
        
        if ($this->dateDebut && $this->dateFin) {
            $start = \Carbon\Carbon::parse($this->dateDebut)->format('d-m-Y');
            $end = \Carbon\Carbon::parse($this->dateFin)->format('d-m-Y');
            $period = "_{$start}_au_{$end}";
        }
        
        $extension = $format === 'pdf' ? 'pdf' : 'xlsx';
        
        return "Grand_Livre_General_{$entrepriseCode}{$period}_{$date}.{$extension}";
    }

    /**
     * Export vers PDF - redirection vers une route dédiée
     */
    /*private function exportToPdf()
    {
        // Créer un tableau de filtres sérialisé
        $filters = [
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin,
            'exercice' => $this->exercice,
            'journalCode' => $this->journalCode,
            'lettre' => $this->lettre,
            'search' => $this->search,
            'entreprise_id' => $this->entreprise->id,
        ];
        
        // Générer un token unique pour éviter les conflits
        $token = md5(serialize($filters) . time());
        
        // Stocker les filtres en session avec un token
        session()->put("export_filters_{$token}", $filters);
        
        // Rediriger vers une route dédiée pour l'export PDF
        // Cette route doit être différente de la page actuelle
        return redirect()->route('grand-livre.export-pdf', ['token' => $token]);
    }*/
    
    /**
     * Export PDF optimisé - VERSION CORRIGÉE
     */
    public function exportPdfFinal()
    {
        try {
            // Désactiver le buffering de sortie
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            // Configuration mémoire/temps
            ini_set('memory_limit', '512M');
            set_time_limit(300);
            
            Log::info('Export PDF Final - Démarrage', [
                'entreprise_id' => $this->entreprise->id
            ]);
            
            // Récupérer les données
            $allAccounts = $this->getAllAccountsWithTotals();
            
            if ($allAccounts->isEmpty()) {
                session()->flash('error', 'Aucune donnée à exporter.');
                return null;
            }
            
            // Calculer les totaux
            $globalTotals = $this->calculateAccurateGlobalTotals($allAccounts);
            
            // Générer le PDF
            $fileName = $this->generateExportFilename('pdf');
            
            // Utiliser une approche différente pour éviter les problèmes de buffer
            return $this->generateAndStreamPdf($allAccounts, $globalTotals, $fileName);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF final', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Erreur: ' . $e->getMessage());
            return null;
        } finally {
            ini_set('memory_limit', '128M');
            set_time_limit(30);
        }
    }
    
    /**
     * Génère et stream le PDF (sans problèmes de buffer)
     */
    private function generateAndStreamPdf(Collection $accounts, array $totals, string $fileName)
    {
        // Nettoyer les buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Générer le HTML
        $html = $this->generateSimplePdfHtml($accounts, $totals);
        
        // Options minimales pour DomPDF
        $pdf = PDF::loadHTML($html);
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'defaultFont' => 'Arial',
            'isRemoteEnabled' => false,
            'dpi' => 72,
            'compress' => true,
            'isPhpEnabled' => false,
            'isHtml5ParserEnabled' => true,
        ]);
        
        // Sauvegarder dans un fichier temporaire
        $tempDir = storage_path('app/temp_pdf/');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $tempFile = $tempDir . uniqid('grand_livre_', true) . '.pdf';
        $pdf->save($tempFile);
        
        Log::info('PDF sauvegardé temporairement', ['file' => $tempFile]);
        
        // Nettoyer la mémoire
        unset($pdf);
        unset($html);
        gc_collect_cycles();
        
        // Retourner le fichier pour téléchargement
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
    
    /**
     * Génère un HTML simple pour le PDF
     */
    private function generateSimplePdfHtml(Collection $accounts, array $totals): string
    {
        $dateDebut = $this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '';
        $dateFin = $this->dateFin ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y') : '';
        
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Grand Livre Général</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 9px; margin: 10px; }
                .header { text-align: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #000; }
                h1 { font-size: 14px; margin: 0 0 5px 0; color: #1e40af; }
                .info { font-size: 8px; color: #666; margin: 2px 0; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 8px; }
                th { background: #e0e0e0; padding: 4px; border: 1px solid #ccc; font-weight: bold; text-align: left; }
                td { padding: 4px; border: 1px solid #ccc; }
                .total-row { background: #f0f0f0; font-weight: bold; }
                .debit { color: #c00; text-align: right; }
                .credit { color: #090; text-align: right; }
                .solde-debit { background: #ffe6e6; }
                .solde-credit { background: #e6ffe6; }
                .global { background: #333; color: white; padding: 10px; margin-top: 15px; border-radius: 5px; }
                .footer { text-align: center; margin-top: 15px; padding-top: 5px; border-top: 1px solid #ddd; font-size: 7px; color: #777; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>GRAND LIVRE GÉNÉRAL</h1>
                <div class="info">' . htmlspecialchars($this->entreprise->nom) . ' (' . htmlspecialchars($this->entreprise->code) . ')</div>';
        
        if ($dateDebut && $dateFin) {
            $html .= '<div class="info">Période: ' . $dateDebut . ' au ' . $dateFin . '</div>';
        }
        
        $html .= '<div class="info">Généré le: ' . date('d/m/Y H:i') . '</div>
            </div>
            
            <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 8px; margin-bottom: 10px; font-size: 8px;">
                <strong>Document de synthèse</strong> - ' . number_format($totals['total_ecritures'], 0, ',', ' ') . ' écritures regroupées en ' . $totals['total_comptes'] . ' comptes.
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th width="12%">Compte</th>
                        <th width="40%">Intitulé</th>
                        <th width="10%">Écritures</th>
                        <th width="15%">Débit</th>
                        <th width="15%">Crédit</th>
                        <th width="8%">Solde</th>
                    </tr>
                </thead>
                <tbody>';
        
        foreach ($accounts as $account) {
            $solde = $account->total_debit - $account->total_credit;
            $soldeClass = $solde > 0 ? 'solde-debit' : 'solde-credit';
            $soldeText = number_format(abs($solde), 0, ',', ' ');
            
            $html .= '<tr>
                <td><strong>' . htmlspecialchars($account->new_account_code) . '</strong></td>
                <td>' . htmlspecialchars(substr($account->new_account_intitule, 0, 60)) . '</td>
                <td>' . number_format($account->nombre_ecritures, 0, ',', ' ') . '</td>
                <td class="debit">' . number_format($account->total_debit, 0, ',', ' ') . '</td>
                <td class="credit">' . number_format($account->total_credit, 0, ',', ' ') . '</td>
                <td class="' . $soldeClass . '">' . $soldeText . '</td>
            </tr>';
        }
        
        $html .= '<tr class="total-row">
                <td colspan="2"><strong>TOTAUX GÉNÉRAUX</strong></td>
                <td><strong>' . number_format($totals['total_ecritures'], 0, ',', ' ') . '</strong></td>
                <td class="debit"><strong>' . number_format($totals['total_debit'], 0, ',', ' ') . '</strong></td>
                <td class="credit"><strong>' . number_format($totals['total_credit'], 0, ',', ' ') . '</strong></td>
                <td class="' . ($totals['is_debiteur'] ? 'solde-debit' : 'solde-credit') . '">
                    <strong>' . number_format($totals['solde_global'], 2, ',', ' ') . '</strong>
                </td>
            </tr>
            </tbody>
        </table>
        
        <div class="global">
            <div style="text-align: center;">
                <div style="font-size: 10px; margin-bottom: 5px;">SOLDE GLOBAL</div>
                <div style="font-size: 16px; font-weight: bold; color: ' . ($totals['is_debiteur'] ? '#ff9999' : '#99ff99') . ';">
                    ' . number_format($totals['solde_global'], 2, ',', ' ') . '
                    (' . ($totals['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT') . ')
                </div>
                <div style="font-size: 8px; margin-top: 5px; opacity: 0.8;">
                    Débit total: ' . number_format($totals['total_debit'], 0, ',', ' ') . ' | 
                    Crédit total: ' . number_format($totals['total_credit'], 0, ',', ' ') . '
                </div>
            </div>
        </div>
        
        <div class="footer">
            Document généré automatiquement - ' . config('app.name') . ' - Page 1/1
        </div>
        </body>
        </html>';
        
        return $html;
    }
    
    /**
     * Méthode alternative utilisant TCPDF (si DomPDF pose toujours problème)
     */
    public function exportPdfAlternative()
    {
        try {
            // Installer TCPDF si nécessaire: composer require tecnickcom/tcpdf
            
            // Désactiver les buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Récupérer les données
            $accounts = $this->getAllAccountsWithTotals();
            
            if ($accounts->isEmpty()) {
                session()->flash('error', 'Aucune donnée à exporter.');
                return null;
            }
            
            $totals = $this->calculateAccurateGlobalTotals($accounts);
            $fileName = $this->generateExportFilename('pdf');
            
            // Utiliser TCPDF directement
            $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
            
            // Configuration du document
            $pdf->SetCreator(config('app.name'));
            $pdf->SetAuthor($this->entreprise->nom);
            $pdf->SetTitle('Grand Livre Général');
            $pdf->SetSubject('Export PDF');
            
            // Marges
            $pdf->SetMargins(10, 10, 10);
            $pdf->SetAutoPageBreak(true, 10);
            
            // Ajouter une page
            $pdf->AddPage();
            
            // En-tête
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'GRAND LIVRE GÉNÉRAL', 0, 1, 'C');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, $this->entreprise->nom . ' (' . $this->entreprise->code . ')', 0, 1, 'C');
            $pdf->Cell(0, 6, 'Généré le: ' . date('d/m/Y H:i'), 0, 1, 'C');
            
            // Ligne
            $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
            $pdf->Ln(5);
            
            // Information
            $pdf->SetFont('helvetica', 'I', 9);
            $pdf->MultiCell(0, 5, 'Document de synthèse - ' . number_format($totals['total_ecritures'], 0, ',', ' ') . 
                          ' écritures regroupées en ' . $totals['total_comptes'] . ' comptes.', 0, 'L');
            $pdf->Ln(3);
            
            // Tableau
            $pdf->SetFont('helvetica', 'B', 8);
            
            // En-têtes du tableau
            $header = ['Compte', 'Intitulé', 'Écritures', 'Débit', 'Crédit', 'Solde'];
            $widths = [20, 80, 20, 25, 25, 20];
            
            for ($i = 0; $i < count($header); $i++) {
                $pdf->Cell($widths[$i], 7, $header[$i], 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Données
            $pdf->SetFont('helvetica', '', 8);
            $fill = false;
            
            foreach ($accounts as $account) {
                $solde = $account->total_debit - $account->total_credit;
                
                // Compte
                $pdf->Cell($widths[0], 6, $account->new_account_code, 'LR', 0, 'L', $fill);
                
                // Intitulé
                $pdf->Cell($widths[1], 6, substr($account->new_account_intitule, 0, 50), 'LR', 0, 'L', $fill);
                
                // Écritures
                $pdf->Cell($widths[2], 6, number_format($account->nombre_ecritures, 0, ',', ' '), 'LR', 0, 'R', $fill);
                
                // Débit
                $pdf->Cell($widths[3], 6, number_format($account->total_debit, 0, ',', ' '), 'LR', 0, 'R', $fill);
                
                // Crédit
                $pdf->Cell($widths[4], 6, number_format($account->total_credit, 0, ',', ' '), 'LR', 0, 'R', $fill);
                
                // Solde
                $pdf->Cell($widths[5], 6, number_format(abs($solde), 0, ',', ' '), 'LR', 0, 'R', $fill);
                
                $pdf->Ln();
                $fill = !$fill;
            }
            
            // Ligne de fermeture
            $pdf->Cell(array_sum($widths), 0, '', 'T');
            $pdf->Ln(5);
            
            // Totaux globaux
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 8, 'TOTAUX GÉNÉRAUX', 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 6, 'Débit total: ' . number_format($totals['total_debit'], 0, ',', ' '), 0, 1, 'L');
            $pdf->Cell(0, 6, 'Crédit total: ' . number_format($totals['total_credit'], 0, ',', ' '), 0, 1, 'L');
            
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'SOLDE GLOBAL: ' . number_format($totals['solde_global'], 2, ',', ' ') . 
                      ' (' . ($totals['is_debiteur'] ? 'DÉBIT' : 'CRÉDIT') . ')', 0, 1, 'C', 0, '', 0, false, 'C', 'B');
            
            // Pied de page
            $pdf->SetY(-15);
            $pdf->SetFont('helvetica', 'I', 8);
            $pdf->Cell(0, 10, 'Page ' . $pdf->getAliasNumPage() . '/' . $pdf->getAliasNbPages(), 0, 0, 'C');
            
            // Output
            $pdfContent = $pdf->Output('', 'S');
            
            // Stream le PDF
            return response()->streamDownload(
                function () use ($pdfContent) {
                    echo $pdfContent;
                },
                $fileName,
                ['Content-Type' => 'application/pdf']
            );
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF alternative', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Erreur: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Solution la plus simple - PDF basique sans bibliothèque externe
     */
    public function exportPdfSimple()
    {
        try {
            // Nettoyer les buffers
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Récupérer les données
            $accounts = $this->getAllAccountsWithTotals();
            
            if ($accounts->isEmpty()) {
                session()->flash('error', 'Aucune donnée à exporter.');
                return null;
            }
            
            $totals = $this->calculateAccurateGlobalTotals($accounts);
            $fileName = $this->generateExportFilename('pdf');
            
            // Générer un HTML très simple
            $html = $this->generateBasicHtml($accounts, $totals);
            
            // Utiliser DomPDF avec moins d'options
            $pdf = PDF::loadHTML($html);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'helvetica',
                'isRemoteEnabled' => false,
                'dpi' => 72,
            ]);
            
            // Sauvegarder directement dans la réponse
            $pdfContent = $pdf->output();
            
            return response()->streamDownload(
                function () use ($pdfContent) {
                    echo $pdfContent;
                },
                $fileName,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                ]
            );
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF simple', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Erreur: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Génère un HTML basique
     */
    private function generateBasicHtml(Collection $accounts, array $totals): string
    {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Grand Livre Général</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 10px; }
                .header { text-align: center; margin-bottom: 20px; }
                h1 { color: #2c3e50; margin-bottom: 5px; }
                .info { color: #7f8c8d; font-size: 12px; margin: 3px 0; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th { background: #34495e; color: white; padding: 8px; text-align: left; }
                td { padding: 6px; border: 1px solid #ddd; }
                tr:nth-child(even) { background: #f8f9fa; }
                .total { background: #e9ecef !important; font-weight: bold; }
                .debit { color: #e74c3c; text-align: right; }
                .credit { color: #27ae60; text-align: right; }
                .footer { text-align: center; margin-top: 20px; font-size: 11px; color: #95a5a6; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>GRAND LIVRE GÉNÉRAL</h1>
                <div class="info"><?= htmlspecialchars($this->entreprise->nom) ?> (<?= htmlspecialchars($this->entreprise->code) ?>)</div>
                <div class="info">Généré le: <?= date('d/m/Y H:i') ?></div>
            </div>
            
            <table>
                <tr>
                    <th>Compte</th>
                    <th>Intitulé</th>
                    <th>Écritures</th>
                    <th>Débit</th>
                    <th>Crédit</th>
                    <th>Solde</th>
                </tr>
                <?php foreach ($accounts as $account): 
                    $solde = $account->total_debit - $account->total_credit;
                    $soldeColor = $solde > 0 ? '#e74c3c' : '#27ae60';
                ?>
                <tr>
                    <td><strong><?= $account->new_account_code ?></strong></td>
                    <td><?= htmlspecialchars(substr($account->new_account_intitule, 0, 50)) ?></td>
                    <td><?= number_format($account->nombre_ecritures, 0, ',', ' ') ?></td>
                    <td class="debit"><?= number_format($account->total_debit, 0, ',', ' ') ?></td>
                    <td class="credit"><?= number_format($account->total_credit, 0, ',', ' ') ?></td>
                    <td style="color: <?= $soldeColor ?>; text-align: right;">
                        <?= number_format(abs($solde), 0, ',', ' ') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr class="total">
                    <td colspan="2">TOTAUX GÉNÉRAUX</td>
                    <td><?= number_format($totals['total_ecritures'], 0, ',', ' ') ?></td>
                    <td class="debit"><?= number_format($totals['total_debit'], 0, ',', ' ') ?></td>
                    <td class="credit"><?= number_format($totals['total_credit'], 0, ',', ' ') ?></td>
                    <td style="color: <?= $totals['is_debiteur'] ? '#e74c3c' : '#27ae60' ?>; text-align: right; font-weight: bold;">
                        <?= number_format($totals['solde_global'], 2, ',', ' ') ?>
                        (<?= $totals['is_debiteur'] ? 'Débit' : 'Crédit' ?>)
                    </td>
                </tr>
            </table>
            
            <div class="footer">
                Document généré automatiquement - <?= config('app.name') ?>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Récupère tous les comptes avec leurs totaux
     */
    private function getAllAccountsWithTotals(): Collection
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
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
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo-->id)
            ->whereNotNull('na.id')
            ->groupBy('na.code', 'na.intitule')
            ->orderBy('na.code');
        
        $this->applyQueryFilters($query);
        
        return $query->get();
    }
    
     /**
     * Calcule les totaux globaux exacts
     */
    private function calculateAccurateGlobalTotals(Collection $accounts): array
    {
        $totals = [
            'total_debit' => 0,
            'total_credit' => 0,
            'total_ecritures' => 0,
            'total_comptes' => $accounts->count()
        ];
        
        foreach ($accounts as $account) {
            $totals['total_debit'] += (float) $account->total_debit;
            $totals['total_credit'] += (float) $account->total_credit;
            $totals['total_ecritures'] += (int) $account->nombre_ecritures;
        }
        
        $solde = $totals['total_debit'] - $totals['total_credit'];
        $totals['solde_global'] = abs($solde);
        $totals['is_debiteur'] = $solde > 0;
        
        return $totals;
    }
    
    /**
     * Détermine la stratégie d'export basée sur la taille
     */
    private function determineExportStrategy(Collection $accounts, int $totalEcritures): string
    {
        // Si plus de 15,000 écritures → Synthèse seulement
        if ($totalEcritures > 15000) {
            return 'summary';
        }
        
        // Si plus de 5,000 écritures → Paginé par compte
        if ($totalEcritures > 5000) {
            return 'paginated';
        }
        
        // Sinon → Complet avec optimisation
        return 'complete';
    }
    
    /**
     * Génère un PDF de synthèse (rapide)
     */
    private function generateSummaryPdf(Collection $accounts, array $globalTotals): \Barryvdh\DomPDF\PDF
    {
        $html = $this->generateSummaryHtml($accounts, $globalTotals);
        return PDF::loadHTML($html);
    }
    
    /**
     * Génère un HTML de synthèse
     */
    private function generateSummaryHtml(Collection $accounts, array $globalTotals): string
    {
        return view('pdf.grand-livre-summary', [
            'accounts' => $accounts,
            'totals' => $globalTotals,
            'entreprise' => $this->entreprise,
            'filters' => $this->getFormattedFilters(),
            'date_generation' => now()->format('d/m/Y H:i')
        ])->render();
    }
    
    /**
     * Génère un PDF paginé par compte
     */
    private function generatePaginatedPdf(Collection $accounts, array $globalTotals): \Barryvdh\DomPDF\PDF
    {
        // Limiter à 10 comptes détaillés maximum
        $accountsToDetail = $accounts->take(10);
        $remainingAccounts = $accounts->slice(10);
        
        // Récupérer les écritures pour les comptes détaillés
        $detailedAccounts = $this->getDetailedAccounts($accountsToDetail->pluck('new_account_code')->toArray());
        
        $html = view('pdf.grand-livre-paginated', [
            'detailed_accounts' => $detailedAccounts,
            'remaining_accounts' => $remainingAccounts,
            'totals' => $globalTotals,
            'entreprise' => $this->entreprise,
            'filters' => $this->getFormattedFilters(),
            'date_generation' => now()->format('d/m/Y H:i'),
            'has_more_accounts' => $remainingAccounts->isNotEmpty()
        ])->render();
        
        return PDF::loadHTML($html);
    }
    
    /**
     * Récupère les comptes avec écritures détaillées
     */
    private function getDetailedAccounts(array $accountCodes): Collection
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        if (empty($accountCodes)) {
            return collect();
        }
        
        $detailedAccounts = collect();
        
        foreach ($accountCodes as $accountCode) {
            // Récupérer le compte
            $account = DB::table('new_accounts')
                ->where('code', $accountCode)
                ->first();
            
            if (!$account) continue;
            
            // Récupérer les écritures (limitée à 50 pour éviter la surcharge)
            $ecritures = DB::table('grand_livres as gl')
                ->select([
                    'gl.date_ecriture',
                    'gl.piece',
                    'gl.journal_code',
                    'gl.libelle',
                    'gl.debit',
                    'gl.credit',
                    'oa.code as old_account_code'
                ])
                ->leftJoin('account_mappings as am', function($join) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                         ->where('am.entreprise_id', $this->entreprise->id);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
                ->where('na.code', $accountCode)
                ->where('gl.entreprise_id', $this->entreprise->id)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id')
                ->orderBy('gl.date_ecriture')
                ->limit(50) // Limite à 50 écritures par compte
                ->get();
            
            // Calculer les totaux réels (sans limite)
            $totals = DB::table('grand_livres as gl')
                ->select([
                    DB::raw('SUM(gl.debit) as total_debit'),
                    DB::raw('SUM(gl.credit) as total_credit'),
                    DB::raw('COUNT(gl.id) as total_ecritures')
                ])
                ->leftJoin('account_mappings as am', function($join) use ($accountCode) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                         ->where('am.entreprise_id', $this->entreprise->id);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->where('na.code', $accountCode)
                ->where('gl.entreprise_id', $this->entreprise->id)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id')
                ->first();
            
            $detailedAccounts->push((object) [
                'code' => $account->code,
                'intitule' => $account->intitule,
                'ecritures' => $ecritures,
                'total_debit' => $totals->total_debit ?? 0,
                'total_credit' => $totals->total_credit ?? 0,
                'total_ecritures' => $totals->total_ecritures ?? 0,
                'has_more_ecritures' => ($totals->total_ecritures ?? 0) > 50
            ]);
        }
        
        return $detailedAccounts;
    }
    
    /**
     * Génère un PDF complet optimisé
     */
    private function generateCompleteOptimizedPdf(Collection $accounts, array $globalTotals): \Barryvdh\DomPDF\PDF
    {
        // Récupérer toutes les écritures groupées par compte (limitée)
        $allEcritures = $this->getAllEcrituresGrouped($accounts->pluck('new_account_code')->toArray());
        
        $html = view('pdf.grand-livre-complete', [
            'accounts' => $accounts,
            'ecritures' => $allEcritures,
            'totals' => $globalTotals,
            'entreprise' => $this->entreprise,
            'filters' => $this->getFormattedFilters(),
            'date_generation' => now()->format('d/m/Y H:i')
        ])->render();
        
        return PDF::loadHTML($html);
    }
    
    /**
     * Récupère toutes les écritures groupées par compte (optimisé)
     */
    private function getAllEcrituresGrouped(array $accountCodes): Collection
    {
        // Récupérer les écritures avec pagination par compte
        $ecritures = DB::table('grand_livres as gl')
            ->select([
                'gl.date_ecriture',
                'gl.piece',
                'gl.journal_code',
                'gl.libelle',
                'gl.debit',
                'gl.credit',
                'oa.code as old_account_code',
                'na.code as new_account_code'
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id)
                     ->where('am.exercice_id', $exo->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id')
            ->orderBy('na.code')
            ->orderBy('gl.date_ecriture')
            ->limit(2000) // Limite globale pour éviter la surcharge
            ->get();
        
        return $ecritures->groupBy('new_account_code');
    }
    
    /**
     * Options optimisées pour DomPDF
     */
    private function getOptimizedPdfOptions(): array
    {
        return [
            'defaultFont' => 'Arial',
            'isRemoteEnabled' => false,
            'dpi' => 72,
            'compress' => true,
            'isPhpEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'isJavascriptEnabled' => false,
            'debugKeepTemp' => false,
            'debugCss' => false,
            'debugLayout' => false,
        ];
    }
    
    /**
     * Filtres formatés
     */
    private function getFormattedFilters(): array
    {
        return [
            'date_debut' => $this->dateDebut ? \Carbon\Carbon::parse($this->dateDebut)->format('d/m/Y') : '',
            'date_fin' => $this->dateFin ? \Carbon\Carbon::parse($this->dateFin)->format('d/m/Y') : '',
            'journal' => $this->journalCode,
            'exercice' => $this->exercice,
            'search' => $this->search
        ];
    }
    
    
    
    
    
    // ... (les autres méthodes restent inchangées)


    /**
     * Export vers Excel - génération directe
     */
    private function exportToExcel()
    {
        // Utiliser une réponse streamée pour les gros fichiers
        return response()->streamDownload(function () {
            $this->generateExcelContent();
        }, $this->generateExportFilename('excel'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Génère le contenu Excel
     */
    private function generateExcelContent(): void
    {
        // Utiliser une bibliothèque comme PhpSpreadsheet ou Laravel Excel
        // Voici un exemple simple avec CSV pour éviter les dépendances
        
        $handle = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($handle, [
            'Date', 'Pièce', 'Journal', 'Compte Ancien', 'Compte SYCEBNL',
            'Libellé', 'Débit', 'Crédit'
        ], ';');
        
        // Récupérer les données pour l'export
        $exportData = $this->getExportData();
        
        foreach ($exportData as $group) {
            // En-tête du groupe
            fputcsv($handle, [
                'COMPTE SYCEBNL:',
                $group['new_account_code'],
                $group['new_account_intitule'],
                '', '', '', '', ''
            ], ';');
            
            // Écritures
            foreach ($group['ecritures'] as $ecriture) {
                fputcsv($handle, [
                    $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '',
                    $ecriture->piece ?? '',
                    $ecriture->journal_code ?? '',
                    $ecriture->old_account_code ?? '',
                    $group['new_account_code'],
                    $ecriture->libelle ?? '',
                    $ecriture->debit > 0 ? number_format($ecriture->debit, 2, ',', ' ') : '',
                    $ecriture->credit > 0 ? number_format($ecriture->credit, 2, ',', ' ') : ''
                ], ';');
            }
            
            // Totaux
            fputcsv($handle, [
                'TOTAL', '', '', '', '', '',
                number_format($group['total_debit'], 2, ',', ' '),
                number_format($group['total_credit'], 2, ',', ' ')
            ], ';');
            
            // Solde
            $solde = $group['total_debit'] - $group['total_credit'];
            fputcsv($handle, [
                'SOLDE', '', '', '', '', '',
                $solde > 0 ? number_format(abs($solde), 2, ',', ' ') : '',
                $solde < 0 ? number_format(abs($solde), 2, ',', ' ') : ''
            ], ';');
            
            // Ligne vide
            fputcsv($handle, [''], ';');
        }
        
        fclose($handle);
    }

    /**
     * Récupère les données pour l'export
     */
    private function getExportData(): array
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Récupérer TOUS les comptes (pas de pagination pour l'export)
        $accountsQuery = DB::table('grand_livres as gl')
            ->select([
                'na.code as new_account_code',
                'na.intitule as new_account_intitule',
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit')
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id')
            ->groupBy('na.code', 'na.intitule')
            ->orderBy('na.code');
        
        $this->applyQueryFilters($accountsQuery);
        $accounts = $accountsQuery->get();
        
        if ($accounts->isEmpty()) {
            return [];
        }
        
        $result = [];
        $accountCodes = $accounts->pluck('new_account_code')->toArray();
        
        // Récupérer toutes les écritures
        $ecritures = $this->getAllEcrituresForExport($accountCodes);
        
        foreach ($accounts as $account) {
            $accountEcritures = $ecritures->where('new_account_code', $account->new_account_code);
            
            $result[] = [
                'new_account_code' => $account->new_account_code,
                'new_account_intitule' => $account->new_account_intitule,
                'ecritures' => $accountEcritures,
                'total_debit' => (float) $account->total_debit,
                'total_credit' => (float) $account->total_credit,
            ];
        }
        
        return $result;
    }

    /**
     * Récupère toutes les écritures pour l'export
     */
    private function getAllEcrituresForExport(array $accountCodes): Collection
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        $query = DB::table('grand_livres as gl')
            ->select([
                'gl.date_ecriture',
                'gl.piece',
                'gl.journal_code',
                'gl.libelle',
                'gl.debit',
                'gl.credit',
                'oa.code as old_account_code',
                'na.code as new_account_code'
            ])
            ->leftJoin('account_mappings as am', function($join) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $this->entreprise->id)
                     ->where('am.entreprise_id', $this->entreprise->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
            ->where('gl.entreprise_id', $this->entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereIn('na.code', $accountCodes)
            ->whereNotNull('na.id');
        
        $this->applyQueryFilters($query);
        $query->orderBy('na.code')
              ->orderBy('gl.date_ecriture')
              ->orderBy('gl.id');
        
        return $query->get();
    }

    /**
     * Fonction alternative plus simple pour l'export Excel
     */
    public function exportExcelSimple()
    {
        $this->exporting = true;
        
        try {
            // Générer un CSV simple
            $filename = $this->generateExportFilename('excel');
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ];
            
            $callback = function() {
                $this->generateCsvContent();
            };
            
            $this->exporting = false;
            return response()->stream($callback, 200, $headers);
            
        } catch (\Exception $e) {
            $this->exporting = false;
            $this->addError('export', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Génère le contenu CSV
     */
    private function generateCsvContent(): void
    {
        $handle = fopen('php://output', 'w');
        
        // BOM pour UTF-8
        fwrite($handle, "\xEF\xBB\xBF");
        
        // En-têtes
        fputcsv($handle, [
            'Date', 'Pièce', 'Journal', 'Compte Ancien', 
            'Compte SYCEBNL', 'Intitulé SYCEBNL', 'Libellé', 
            'Débit', 'Crédit'
        ], ';');
        
        // Données
        $exportData = $this->getExportData();
        
        foreach ($exportData as $group) {
            foreach ($group['ecritures'] as $ecriture) {
                fputcsv($handle, [
                    $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '',
                    $ecriture->piece ?? '',
                    $ecriture->journal_code ?? '',
                    $ecriture->old_account_code ?? '',
                    $group['new_account_code'],
                    $group['new_account_intitule'],
                    $ecriture->libelle ?? '',
                    $ecriture->debit > 0 ? str_replace('.', ',', (string) $ecriture->debit) : '',
                    $ecriture->credit > 0 ? str_replace('.', ',', (string) $ecriture->credit) : ''
                ], ';');
            }
            
            // Ligne de total
            fputcsv($handle, [
                '', '', '', '', 'TOTAL ' . $group['new_account_code'], '',
                '',
                str_replace('.', ',', (string) $group['total_debit']),
                str_replace('.', ',', (string) $group['total_credit'])
            ], ';');
            
            // Ligne de solde
            $solde = $group['total_debit'] - $group['total_credit'];
            fputcsv($handle, [
                '', '', '', '', 'SOLDE ' . $group['new_account_code'], '',
                '',
                $solde > 0 ? str_replace('.', ',', (string) abs($solde)) : '',
                $solde < 0 ? str_replace('.', ',', (string) abs($solde)) : ''
            ], ';');
            
            // Ligne vide
            fputcsv($handle, [''], ';');
        }
        
        fclose($handle);
    }

    /**
     * Méthode pour télécharger directement un fichier
     */
    public function downloadExport()
    {
        $filename = $this->generateExportFilename('excel');
        $filepath = storage_path("app/exports/{$filename}");
        
        // Créer le répertoire si nécessaire
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        // Générer le fichier
        $this->createExportFile($filepath);
        
        // Télécharger
        return response()->download($filepath)->deleteFileAfterSend(true);
    }

    /**
     * Crée le fichier d'export
     */
    private function createExportFile(string $filepath): void
    {
        $handle = fopen($filepath, 'w');
        fwrite($handle, "\xEF\xBB\xBF"); // BOM UTF-8
        
        // Écrire le contenu
        $exportData = $this->getExportData();
        
        // En-têtes
        fputcsv($handle, [
            'Compte SYCEBNL', 'Intitulé', 'Date', 'Pièce', 'Journal',
            'Compte Ancien', 'Libellé', 'Débit', 'Crédit'
        ], ';');
        
        foreach ($exportData as $group) {
            foreach ($group['ecritures'] as $ecriture) {
                fputcsv($handle, [
                    $group['new_account_code'],
                    $group['new_account_intitule'],
                    $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '',
                    $ecriture->piece ?? '',
                    $ecriture->journal_code ?? '',
                    $ecriture->old_account_code ?? '',
                    $ecriture->libelle ?? '',
                    $ecriture->debit > 0 ? number_format($ecriture->debit, 2, ',', '') : '',
                    $ecriture->credit > 0 ? number_format($ecriture->credit, 2, ',', '') : ''
                ], ';');
            }
        }
        
        fclose($handle);
    }


    public function render()
    {
        return view('livewire.grand-livre.general', [
            'recapData' => $this->recapData,
            'recapStats' => $this->recapStats,
            'journaux' => $this->journaux,
            'loadedCount' => $this->loadedCount,
            'totalCount' => $this->totalCount,
            'hasMore' => $this->hasMore,
            'isLoading' => $this->isLoading,
            'exporting' => $this->exporting,
        ])->layout('layouts.app');
    }
}