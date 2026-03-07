<?php
// app/Http/Controllers/GrandLivreExportPdfController.php

namespace App\Http\Controllers;

use App\Models\GrandLivre;
use App\Models\Entreprise;
use App\Models\OldAccount;
use App\Models\Exercice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrandLivreExportPdfController extends Controller
{
    public function exportPdf(Request $request)
    {
        try {
            $data = $this->getGrandLivreData($request->all());

            if (!$data['hasData']) {
                return back()->with('error', 'Aucune écriture à exporter pour cette période');
            }

            $pdf = Pdf::loadView('exports.grandlivre-pdf', $data)
                ->setPaper('A4', 'landscape')
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isRemoteEnabled'      => false,   // désactiver si pas d'images externes
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled'         => false,
                ]);

            return $pdf->download('grand_livre_' . date('Y-m-d_H-i') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur export PDF: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'export PDF: ' . $e->getMessage());
        }
    }

    public function exportPdfGeneral(Request $request)
    {
        try {
            $data = $this->getGrandLivreGeneralData($request->all());

            if (empty($data['comptes'])) {
                return back()->with('error', 'Aucune écriture à exporter pour cette période');
            }

            $pdf = Pdf::loadView('exports.grand-livre-general-pdf', $data)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'defaultFont'          => 'DejaVu Sans',
                    'isRemoteEnabled'      => false,
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled'         => false,
                ]);

            return $pdf->download('grand_livre_general_' . date('Y-m-d_H-i') . '.pdf');

        } catch (\Exception $e) {
            Log::error('Erreur export PDF général: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'export PDF: ' . $e->getMessage());
        }
    }

    /*public function print(Request $request)
    {
        try {
            $data = $this->getGrandLivreData($request->all());

            if (!$data['hasData']) {
                return back()->with('error', 'Aucune écriture à imprimer pour cette période');
            }

            return view('exports.grand-livre-print', $data);

        } catch (\Exception $e) {
            Log::error('Erreur impression: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'impression: ' . $e->getMessage());
        }
    }*/
    
     public function print(Request $request)
    {
        try {
            // On utilise getGrandLivreGeneralData pour avoir les données
            // regroupées par compte (même structure que exportPdfGeneral)
            $data = $this->getGrandLivreGeneralData($request->all());

            if (empty($data['comptes'])) {
                return back()->with('error', 'Aucune écriture à imprimer pour cette période');
            }

            return view('exports.grand-livre-print', $data);

        } catch (\Exception $e) {
            Log::error('Erreur impression: ' . $e->getMessage());
            return back()->with('error', 'Erreur lors de l\'impression: ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // DONNÉES GRAND LIVRE DÉTAILLÉ
    // Optimisations :
    //  - select() limité aux colonnes utiles seulement
    //  - totaux calculés en SQL (pas en PHP)
    //  - eager load avec select limité
    // -------------------------------------------------------------------------
    private function getGrandLivreData(array $filters): array
    {
        [$entreprise, $exo] = $this->resolveEntrepriseAndExo($filters);

        // Requête de base sans select (pour les agrégats et les données)
        $baseConstraints = function ($q) use ($entreprise, $exo, $filters) {
            $q->where('entreprise_id', $entreprise->id)
              ->where('exercice_id', $exo->id)
              ->where('validated', true);
            $this->applyCommonFilters($q, $filters);
        };

        // Totaux en SQL — requête agrégée séparée (pas de select() colonnes)
        $totauxRow = GrandLivre::query()
            ->tap($baseConstraints)
            ->selectRaw('COALESCE(SUM(debit), 0) as total_debit, COALESCE(SUM(credit), 0) as total_credit')
            ->first();

        // Requête principale avec colonnes limitées
        $query = GrandLivre::select([
                'id', 'date_ecriture', 'journal_code', 'piece',
                'libelle', 'debit', 'credit', 'source',
                'old_account_id', 'lettre',
            ])
            ->with(['oldAccount:id,code,intitule'])
            ->tap($baseConstraints);

        $totalDebit  = (float) $totauxRow->total_debit;
        $totalCredit = (float) $totauxRow->total_credit;

        $ecritures = $query->orderBy('date_ecriture')->orderBy('id')->get();

        return [
            'entreprise'     => $entreprise,
            'ecritures'      => $ecritures,
            'hasData'        => $ecritures->isNotEmpty(),
            'totaux'         => [
                'debit'  => $totalDebit,
                'credit' => $totalCredit,
                'solde'  => abs($totalDebit - $totalCredit),
            ],
            'filters'        => $filters,
            'dateGeneration' => now()->format('d/m/Y H:i:s'),
        ];
    }

    // -------------------------------------------------------------------------
    // DONNÉES GRAND LIVRE GÉNÉRAL (regroupé par compte)
    // Optimisations :
    //  - Suppression du N+1 : une seule requête groupBy pour les totaux
    //  - Toutes les écritures chargées en une passe (whereIn)
    //  - select() limité
    // -------------------------------------------------------------------------
    /*private function getGrandLivreGeneralData(array $filters): array
    {
        [$entreprise, $exo] = $this->resolveEntrepriseAndExo($filters);

        $baseQuery = GrandLivre::query()
            ->where('entreprise_id', $entreprise->id)
            ->where('exercice_id', $exo->id)
            ->where('validated', true);

        if (!empty($filters['dateDebut']) && !empty($filters['dateFin'])) {
            $baseQuery->whereBetween('date_ecriture', [$filters['dateDebut'], $filters['dateFin']]);
        }

        // Étape 1 : agrégation par compte en SQL (une seule requête)
        $aggregates = (clone $baseQuery)
            ->select('old_account_id',
                DB::raw('COALESCE(SUM(debit), 0)  as total_debit'),
                DB::raw('COALESCE(SUM(credit), 0) as total_credit'),
                DB::raw('COUNT(*) as nb')
            )
            ->groupBy('old_account_id')
            ->having('nb', '>', 0)
            ->get()
            ->keyBy('old_account_id');

        if ($aggregates->isEmpty()) {
            return [
                'entreprise'           => $entreprise,
                'comptes'              => [],
                'total_general_debit'  => 0,
                'total_general_credit' => 0,
                'total_general_solde'  => 0,
                'filters'              => $filters,
                'dateGeneration'       => now()->format('d/m/Y H:i:s'),
            ];
        }

        $accountIds = $aggregates->keys()->toArray();

        // Étape 2 : charger les comptes dans le bon ordre
        $accounts = OldAccount::select('id', 'code', 'intitule')
            ->where('entreprise_id', $entreprise->id)
            ->where('exercice_id', $exo->id)
            ->whereIn('id', $accountIds)
            ->orderByRaw('LENGTH(code) ASC, code ASC')
            ->get();

        // Étape 3 : charger TOUTES les écritures en une seule requête (whereIn)
        $allEcritures = (clone $baseQuery)
            ->select([
                'id', 'old_account_id', 'new_account_id',
                'date_ecriture', 'journal_code', 'piece',
                'libelle', 'debit', 'credit',
            ])
            ->with('newAccount:id,code')
            ->whereIn('old_account_id', $accountIds)
            ->orderBy('old_account_id')
            ->orderBy('date_ecriture')
            ->get()
            ->groupBy('old_account_id');

        // Étape 4 : assembler les données
        $comptesData          = [];
        $totalGeneralDebit    = 0;
        $totalGeneralCredit   = 0;

        foreach ($accounts as $account) {
            $agg         = $aggregates->get($account->id);
            $totalDebit  = (float) $agg->total_debit;
            $totalCredit = (float) $agg->total_credit;
            $solde       = $totalDebit - $totalCredit;

            $comptesData[] = [
                'code'         => $account->code,
                'intitule'     => $account->intitule,
                'ecritures'    => $allEcritures->get($account->id, collect()),
                'total_debit'  => $totalDebit,
                'total_credit' => $totalCredit,
                'solde'        => $solde,
                'solde_absolu' => abs($solde),
                'type_solde'   => $solde > 0 ? 'Débiteur' : ($solde < 0 ? 'Créditeur' : 'Nul'),
            ];

            $totalGeneralDebit  += $totalDebit;
            $totalGeneralCredit += $totalCredit;
        }

        return [
            'entreprise'           => $entreprise,
            'comptes'              => $comptesData,
            'total_general_debit'  => $totalGeneralDebit,
            'total_general_credit' => $totalGeneralCredit,
            'total_general_solde'  => abs($totalGeneralDebit - $totalGeneralCredit),
            'filters'              => $filters,
            'dateGeneration'       => now()->format('d/m/Y H:i:s'),
        ];
    }*/
    
    private function getGrandLivreGeneralData(array $filters): array
{
    [$entreprise, $exo] = $this->resolveEntrepriseAndExo($filters);

    $baseQuery = GrandLivre::query()
        ->where('entreprise_id', $entreprise->id)
        ->where('exercice_id', $exo->id)
        ->where('validated', true);

    if (!empty($filters['dateDebut']) && !empty($filters['dateFin'])) {
        $baseQuery->whereBetween('date_ecriture', [$filters['dateDebut'], $filters['dateFin']]);
    }

    // Étape 1 : agrégation par NEW account (SYCEBNL) en SQL
    $aggregates = (clone $baseQuery)
        ->select(
            'new_account_id',
            DB::raw('COALESCE(SUM(debit), 0)  as total_debit'),
            DB::raw('COALESCE(SUM(credit), 0) as total_credit'),
            DB::raw('COUNT(*) as nb')
        )
        ->whereNotNull('new_account_id')   // seulement les écritures mappées
        ->groupBy('new_account_id')
        ->having('nb', '>', 0)
        ->get()
        ->keyBy('new_account_id');

    if ($aggregates->isEmpty()) {
        return [
            'entreprise'           => $entreprise,
            'comptes'              => [],
            'total_general_debit'  => 0,
            'total_general_credit' => 0,
            'total_general_solde'  => 0,
            'filters'              => $filters,
            'dateGeneration'       => now()->format('d/m/Y H:i:s'),
        ];
    }

    $newAccountIds = $aggregates->keys()->toArray();

    // Étape 2 : charger les NEW accounts (SYCEBNL) dans le bon ordre
    // Adapter \App\Models\NewAccount selon le vrai nom de votre modèle
    $newAccounts = \App\Models\NewAccount::select('id', 'code', 'intitule')
        ->whereIn('id', $newAccountIds)
        ->orderByRaw('LENGTH(code) ASC, code ASC')
        ->get();

    // Étape 3 : toutes les écritures en une seule requête groupées par new_account_id
    $allEcritures = (clone $baseQuery)
        ->select([
            'id', 'old_account_id', 'new_account_id',
            'date_ecriture', 'journal_code', 'piece',
            'libelle', 'debit', 'credit',
        ])
        ->with('oldAccount:id,code,intitule')   // compte entité affiché dans les lignes
        ->whereIn('new_account_id', $newAccountIds)
        ->orderBy('new_account_id')
        ->orderBy('date_ecriture')
        ->get()
        ->groupBy('new_account_id');

    // Étape 4 : assembler
    $comptesData        = [];
    $totalGeneralDebit  = 0;
    $totalGeneralCredit = 0;

    foreach ($newAccounts as $account) {
        $agg         = $aggregates->get($account->id);
        $totalDebit  = (float) $agg->total_debit;
        $totalCredit = (float) $agg->total_credit;
        $solde       = $totalDebit - $totalCredit;

        $comptesData[] = [
            'code'         => $account->code,       // code SYCEBNL → header du bloc
            'intitule'     => $account->intitule,   // intitulé SYCEBNL
            'ecritures'    => $allEcritures->get($account->id, collect()),
            'total_debit'  => $totalDebit,
            'total_credit' => $totalCredit,
            'solde'        => $solde,
            'solde_absolu' => abs($solde),
            'type_solde'   => $solde > 0 ? 'Débiteur' : ($solde < 0 ? 'Créditeur' : 'Nul'),
        ];

        $totalGeneralDebit  += $totalDebit;
        $totalGeneralCredit += $totalCredit;
    }

    return [
        'entreprise'           => $entreprise,
        'comptes'              => $comptesData,
        'total_general_debit'  => $totalGeneralDebit,
        'total_general_credit' => $totalGeneralCredit,
        'total_general_solde'  => abs($totalGeneralDebit - $totalGeneralCredit),
        'filters'              => $filters,
        'dateGeneration'       => now()->format('d/m/Y H:i:s'),
    ];
}

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Résout l'entreprise et l'exercice actif ou lève une exception */
    private function resolveEntrepriseAndExo(array $filters): array
    {
        $entrepriseId = $filters['entreprise_id'] ?? null;
        if (!$entrepriseId) {
            throw new \Exception('ID entreprise manquant');
        }

        $entreprise = Entreprise::find($entrepriseId);
        if (!$entreprise) {
            throw new \Exception('Entreprise non trouvée');
        }

        $exo = Exercice::where('statut', 1)->first();
        if (!$exo) {
            throw new \Exception('Aucun exercice actif trouvé');
        }

        return [$entreprise, $exo];
    }

    /** Applique les filtres communs sur un query builder GrandLivre */
    private function applyCommonFilters($query, array $filters): void
    {
        if (!empty($filters['dateDebut']) && !empty($filters['dateFin'])) {
            $query->whereBetween('date_ecriture', [$filters['dateDebut'], $filters['dateFin']]);
        }

        if (!empty($filters['journalCode'])) {
            $query->where('journal_code', $filters['journalCode']);
        }

        if (!empty($filters['accountType']) && !empty($filters['accountId'])) {
            $col = $filters['accountType'] === 'old' ? 'old_account_id' : 'new_account_id';
            $query->where($col, $filters['accountId']);
        }

        if (!empty($filters['lettre'])) {
            $filters['lettre'] === 'non'
                ? $query->whereNull('lettre')
                : $query->where('lettre', $filters['lettre']);
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('libelle', 'like', "%{$s}%")
                  ->orWhere('piece', 'like', "%{$s}%")
                  ->orWhereHas('oldAccount', fn($sub) =>
                      $sub->where('code', 'like', "%{$s}%")
                          ->orWhere('intitule', 'like', "%{$s}%")
                  );
            });
        }

        if (!empty($filters['sourceFilter']) && $filters['sourceFilter'] !== 'all') {
            $query->where('source', $filters['sourceFilter']);
        }

        if (!empty($filters['mappingFilter']) && $filters['mappingFilter'] !== 'all') {
            $filters['mappingFilter'] === 'mapped'
                ? $query->whereNotNull('new_account_id')
                : $query->whereNull('new_account_id');
        }
    }
}