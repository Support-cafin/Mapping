<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Entreprise;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GrandLivrePdfController extends Controller
{
    public function export($entrepriseId)
    {
        // Configuration
        set_time_limit(300);
        ini_set('memory_limit', '1024M');
        
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        $entreprise = Entreprise::find($entrepriseId);
        
        if (!$entreprise) {
            return back()->with('error', 'Entreprise non trouvée.');
        }
        
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $dateDebut = request('dateDebut');
        $dateFin = request('dateFin');
        $journalCode = request('journalCode');
        $search = request('search');
        $exercice = request('exercice');
        
        try {
            Log::info('Début génération PDF avec écritures', ['entreprise_id' => $entreprise->id]);
            
            // OPTIMISATION : Récupérer les comptes avec pagination
            $accounts = $this->getAccountsWithPaginatedEntries($entreprise, $dateDebut, $dateFin, $journalCode, $search);
            
            if ($accounts->isEmpty()) {
                return back()->with('error', 'Aucune donnée à exporter.');
            }
            
            // Calculer les totaux globaux
            $totals = $this->calculateTotals($accounts, $entreprise, $dateDebut, $dateFin, $journalCode);
            
            // Générer le PDF
            $pdf = PDF::loadView('pdf.grand-livre-detailed-optimized', [
                'accounts' => $accounts,
                'totals' => $totals,
                'entreprise' => $entreprise,
                'filters' => [
                    'date_debut' => $dateDebut ? Carbon::parse($dateDebut)->format('d/m/Y') : '',
                    'date_fin' => $dateFin ? Carbon::parse($dateFin)->format('d/m/Y') : '',
                    'journal' => $journalCode, 
                    'exercice' => $exercice,
                    'search' => $search
                ]
            ]);
            
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => false,
                'dpi' => 72,
                'compress' => true,
                'isPhpEnabled' => false,
                'isHtml5ParserEnabled' => true,
            ]);
            
            $fileName = 'grand_livre_' . $entreprise->code . '_' . date('Ymd_His') . '.pdf';
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erreur export PDF', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }
    
    /**
     * Récupère les comptes avec écritures paginées pour optimiser les performances
     */
    private function getAccountsWithPaginatedEntries($entreprise, $dateDebut, $dateFin, $journalCode, $search)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // 1. D'abord, récupérer tous les comptes (sans les écritures)
        $accountsList = DB::table('grand_livres as gl')
            ->select([
                'na.code',
                'na.intitule',
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit'),
                DB::raw('COUNT(gl.id) as nombre_ecritures')
            ])
            ->leftJoin('account_mappings as am', function($join) use ($entreprise) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $entreprise->id)
                     ->where('am.exercice_id', $exo->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id')
            ->when($dateDebut && $dateFin, function($query) use ($dateDebut, $dateFin) {
                return $query->whereBetween('gl.date_ecriture', [$dateDebut, $dateFin]);
            })
            ->when($journalCode, function($query, $journalCode) {
                return $query->where('gl.journal_code', $journalCode);
            })
            ->groupBy('na.code', 'na.intitule')
            ->orderBy('na.code')
            ->get();
        
        // Limiter à 30 comptes maximum pour éviter les timeouts
        $accountsList = $accountsList->take(10);
        
        $detailedAccounts = collect();
        
        foreach ($accountsList as $account) {
            // 2. Pour chaque compte, récupérer les écritures (limitée)
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
                ->leftJoin('account_mappings as am', function($join) use ($entreprise) {
                    $join->on('gl.old_account_id', '=', 'am.old_account_id')
                         ->where('am.entreprise_id', $entreprise->id)
                         ->where('am.exercice_id', $exo->id);
                })
                ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
                ->leftJoin('old_accounts as oa', 'gl.old_account_id', '=', 'oa.id')
                ->where('na.code', $account->code)
                ->where('gl.entreprise_id', $entreprise->id)
                ->where('gl.exercice_id', $exo->id)
                ->whereNotNull('na.id')
                ->when($dateDebut && $dateFin, function($query) use ($dateDebut, $dateFin) {
                    return $query->whereBetween('gl.date_ecriture', [$dateDebut, $dateFin]);
                })
                ->when($journalCode, function($query, $journalCode) {
                    return $query->where('gl.journal_code', $journalCode);
                })
                ->when($search, function($query, $search) {
                    $searchTerm = '%' . $search . '%';
                    return $query->where(function($q) use ($searchTerm) {
                        $q->where('gl.libelle', 'like', $searchTerm)
                          ->orWhere('oa.code', 'like', $searchTerm);
                    });
                })
                ->orderBy('gl.date_ecriture')
                ->orderBy('gl.id')
                ->limit(100) // Limiter à 100 écritures par compte
                ->get();
            
            $solde = $account->total_debit - $account->total_credit;
            
            $detailedAccounts->push((object) [
                'code' => $account->code,
                'intitule' => $account->intitule,
                'total_debit' => $account->total_debit,
                'total_credit' => $account->total_credit,
                'nombre_ecritures' => $account->nombre_ecritures,
                'ecritures_count' => $ecritures->count(),
                'solde' => $solde,
                'ecritures' => $ecritures,
                'has_more' => $account->nombre_ecritures > 100 // Indiquer si il y a plus d'écritures
            ]);
        }
        
        return $detailedAccounts;
    }
    
    /**
     * Calcule les totaux globaux
     */
    private function calculateTotals($accounts, $entreprise, $dateDebut, $dateFin, $journalCode)
    {
        $exo = DB::table('exercices')->where('statut', 1)->first();
        // Récupérer les totaux exacts depuis la base
        $totalsQuery = DB::table('grand_livres as gl')
            ->select([
                DB::raw('SUM(gl.debit) as total_debit'),
                DB::raw('SUM(gl.credit) as total_credit'),
                DB::raw('COUNT(gl.id) as total_ecritures')
            ])
            ->leftJoin('account_mappings as am', function($join) use ($entreprise) {
                $join->on('gl.old_account_id', '=', 'am.old_account_id')
                     ->where('am.entreprise_id', $entreprise->id)
                     ->where('am.exercice_id', $exo->id);
            })
            ->leftJoin('new_accounts as na', 'am.new_account_id', '=', 'na.id')
            ->where('gl.entreprise_id', $entreprise->id)
            ->where('gl.exercice_id', $exo->id)
            ->whereNotNull('na.id');
        
        if ($dateDebut && $dateFin) {
            $totalsQuery->whereBetween('gl.date_ecriture', [$dateDebut, $dateFin]);
        }
        
        if ($journalCode) {
            $totalsQuery->where('gl.journal_code', $journalCode);
        }
        
        $globalTotals = $totalsQuery->first();
        
        $soldeGlobal = ($globalTotals->total_debit ?? 0) - ($globalTotals->total_credit ?? 0);
        
        return [
            'total_debit' => $globalTotals->total_debit ?? 0,
            'total_credit' => $globalTotals->total_credit ?? 0,
            'total_ecritures' => $globalTotals->total_ecritures ?? 0,
            'total_comptes' => $accounts->count(),
            'solde_global' => abs($soldeGlobal),
            'is_debiteur' => $soldeGlobal > 0,
            'comptes_affichés' => $accounts->count(),
            'écritures_affichées' => $accounts->sum('ecritures_count')
        ];
    }
}