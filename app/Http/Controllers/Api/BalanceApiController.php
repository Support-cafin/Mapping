<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BalanceApiController extends Controller
{
    use ApiResponseTrait;

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function exerciceActif(): ?object
    {
        return DB::table('exercices')->where('statut', 1)->first();
    }

    private function entrepriseId(): int
    {
        return auth()->user()->entreprise_id;
    }

    private function entreprise(): object
    {
        return auth()->user()->entreprise;
    }

    /**
     * Construire la requête de base Grand Livre filtrée
     */
    private function baseGrandLivreQuery(int $entrepriseId, ?object $exo, Request $request)
    {
        $query = GrandLivre::where('entreprise_id', $entrepriseId)
            ->where('exercice_id', $exo->id ?? '')
            ->valides()
            ->whereNotNull('new_account_id');

        $debut = $request->filled('dateDebut') ? $request->dateDebut
               : ($request->filled('date_debut') ? $request->date_debut
               : $exo?->date_debut);
        $fin   = $request->filled('dateFin') ? $request->dateFin
               : ($request->filled('date_fin') ? $request->date_fin
               : $exo?->date_fin);

        if ($debut && $fin) {
            $query->whereBetween('date_ecriture', [$debut, $fin]);
        }

        if ($request->filled('exercice')) {
            $query->where('exercice', $request->exercice);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('newAccount', fn($q) =>
            $q->where('code', 'like', "%{$search}%")
                ->orWhere('intitule', 'like', "%{$search}%")
            );
        }

        return $query;
    }

    // ─── Balance 4 colonnes ───────────────────────────────────────────────────

    /**
     * GET /api/balance/4colonnes
     * Retourne la balance à 4 colonnes : mouvement débit/crédit + solde débiteur/créditeur
     * Optimisé : 3 requêtes au lieu de N+1
     */
    public function balance4Colonnes(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $perPage      = min((int) $request->get('per_page', 50), 500);
            $page         = max(1, (int) $request->get('page', 1));

            // 1. Arbre des comptes (1 requête)
            $comptes = NewAccount::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNull('parent_id')
                ->with('childrenRecursive')
                ->orderBy('code')
                ->get();

            // 2. Agrégats en masse groupés par (new_account_id, old_account_id) — 1 requête
            $agg = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                ->select(
                    'new_account_id',
                    'old_account_id',
                    DB::raw('SUM(debit) as total_debit'),
                    DB::raw('SUM(credit) as total_credit'),
                    DB::raw('COUNT(*) as cnt')
                )
                ->groupBy('new_account_id', 'old_account_id')
                ->get();

            $aggByAccount = $agg->groupBy('new_account_id');

            // 3. Charger les anciens comptes (1 requête)
            $oldAccounts = \App\Models\OldAccount::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->get(['id', 'code', 'intitule'])
                ->keyBy('id');

            // 4. Construction des lignes en PHP (0 requête supplémentaire)
            $balances    = [];
            $totalDebit  = 0;
            $totalCredit = 0;
            $totalSoldeD = 0;
            $totalSoldeC = 0;

            foreach ($comptes as $compte) {
                $allIds = $this->getAllDescendantIds($compte);

                $debit = $credit = $count = 0;
                $oldAccountsGrouped = [];

                foreach ($allIds as $aid) {
                    $rows = $aggByAccount->get($aid);
                    if (!$rows) continue;
                    foreach ($rows as $row) {
                        $debit  += $row->total_debit;
                        $credit += $row->total_credit;
                        $count  += $row->cnt;
                        $oaId = $row->old_account_id;
                        if (!isset($oldAccountsGrouped[$oaId])) {
                            $oldAccountsGrouped[$oaId] = ['debit' => 0, 'credit' => 0];
                        }
                        $oldAccountsGrouped[$oaId]['debit']  += $row->total_debit;
                        $oldAccountsGrouped[$oaId]['credit'] += $row->total_credit;
                    }
                }

                if ($count === 0) continue;

                $debit  = round($debit, 2);
                $credit = round($credit, 2);
                $solde  = round($debit - $credit, 2);

                $oldAccountsData = [];
                foreach ($oldAccountsGrouped as $oaId => $vals) {
                    $oa = $oldAccounts->get($oaId);
                    $oldAccountsData[] = [
                        'id'       => $oaId,
                        'code'     => $oa?->code ?? 'N/A',
                        'intitule' => $oa?->intitule ?? 'Inconnu',
                        'debit'    => round($vals['debit'], 2),
                        'credit'   => round($vals['credit'], 2),
                    ];
                }

                $childrenData = [];
                foreach ($compte->children ?? [] as $child) {
                    $childIds = $this->getAllDescendantIds($child);
                    $cd = $cc = 0;
                    foreach ($childIds as $cid) {
                        $rows = $aggByAccount->get($cid);
                        if (!$rows) continue;
                        foreach ($rows as $r) { $cd += $r->total_debit; $cc += $r->total_credit; }
                    }
                    if ($cd == 0 && $cc == 0) continue;
                    $cs = round($cd - $cc, 2);
                    $childrenData[] = [
                        'id'       => $child->id,
                        'code'     => $child->code,
                        'intitule' => $child->intitule,
                        'debit'    => round($cd, 2),
                        'credit'   => round($cc, 2),
                        'solde'    => $cs,
                    ];
                }

                $balances[]   = [
                    'id'               => $compte->id,
                    'code'             => $compte->code,
                    'intitule'         => $compte->intitule,
                    'total_debit'      => $debit,
                    'total_credit'     => $credit,
                    'solde'            => $solde,
                    'solde_debiteur'   => $solde > 0 ? $solde : 0,
                    'solde_crediteur'  => $solde < 0 ? abs($solde) : 0,
                    'ecritures_count'  => $count,
                    'has_children'     => count($childrenData) > 0,
                    'children_count'   => count($childrenData),
                    'old_accounts_data'=> $oldAccountsData,
                    'children_data'    => $childrenData,
                ];

                $totalDebit  += $debit;
                $totalCredit += $credit;
                if ($solde > 0) { $totalSoldeD += $solde; } else { $totalSoldeC += abs($solde); }
            }

            // 5. Pagination
            $total         = count($balances);
            $lastPage      = max(1, (int) ceil($total / $perPage));
            $page          = min($page, $lastPage);
            $pagedBalances = array_values(array_slice($balances, ($page - 1) * $perPage, $perPage));

            return $this->success('Balance à 4 colonnes.', [
                'balances'     => $pagedBalances,
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $lastPage,
                'totaux' => [
                    'total_debit'           => round($totalDebit, 2),
                    'total_credit'          => round($totalCredit, 2),
                    'total_solde_debiteur'  => round($totalSoldeD, 2),
                    'total_solde_crediteur' => round($totalSoldeC, 2),
                    'equilibre'             => abs($totalSoldeD - $totalSoldeC) < 0.01,
                ],
                'stats' => [
                    'total_ecritures' => array_sum(array_column($balances, 'ecritures_count')),
                    'total_comptes'   => $total,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Balance 6 colonnes ───────────────────────────────────────────────────

    /**
     * GET /api/balance/6colonnes
     * Retourne la balance à 6 colonnes : ouverture (RAN) + mouvement + solde clôture
     * Optimisé : 3 requêtes au lieu de N+1
     */
    public function balance6Colonnes(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();
            $perPage      = min((int) $request->get('per_page', 50), 500);
            $page         = max(1, (int) $request->get('page', 1));

            // 1. Arbre des comptes (1 requête)
            $comptes = NewAccount::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNull('parent_id')
                ->with('childrenRecursive')
                ->orderBy('code')
                ->get();

            // 2. Agrégats RAN en masse — 1 requête (sans filtre de date)
            $ranAgg = GrandLivre::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNotNull('new_account_id')
                ->where('journal_code', 'RAN')
                ->select('new_account_id', DB::raw('SUM(debit) as d, SUM(credit) as c, COUNT(*) as cnt'))
                ->groupBy('new_account_id')
                ->get()
                ->keyBy('new_account_id');

            // 3. Agrégats mouvements en masse (hors RAN, avec filtre date) — 1 requête
            $mvtAgg = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                ->where('journal_code', '!=', 'RAN')
                ->select('new_account_id', DB::raw('SUM(debit) as d, SUM(credit) as c, COUNT(*) as cnt'))
                ->groupBy('new_account_id')
                ->get()
                ->keyBy('new_account_id');

            // 4. Construction des lignes en PHP (0 requête supplémentaire)
            $balances = [];
            $totaux = [
                'ouverture_debit'  => 0, 'ouverture_credit' => 0,
                'mouvement_debit'  => 0, 'mouvement_credit' => 0,
                'cloture_debit'    => 0, 'cloture_credit'   => 0,
            ];

            foreach ($comptes as $compte) {
                $allIds = $this->getAllDescendantIds($compte);

                $od = $oc = $md = $mc = $cnt = 0;
                foreach ($allIds as $aid) {
                    $r = $ranAgg->get($aid);
                    $m = $mvtAgg->get($aid);
                    $od  += $r?->d   ?? 0;
                    $oc  += $r?->c   ?? 0;
                    $md  += $m?->d   ?? 0;
                    $mc  += $m?->c   ?? 0;
                    $cnt += ($r?->cnt ?? 0) + ($m?->cnt ?? 0);
                }

                $od = round($od, 2); $oc = round($oc, 2);
                $md = round($md, 2); $mc = round($mc, 2);

                if (abs($od) < 0.001 && abs($oc) < 0.001 && abs($md) < 0.001 && abs($mc) < 0.001) continue;

                $soldeCloture = round(($od + $md) - ($oc + $mc), 2);

                $childrenData = [];
                foreach ($compte->children ?? [] as $child) {
                    $childIds = $this->getAllDescendantIds($child);
                    $cod = $coc = $cmd = $cmc = 0;
                    foreach ($childIds as $cid) {
                        $r = $ranAgg->get($cid);
                        $m = $mvtAgg->get($cid);
                        $cod += $r?->d ?? 0; $coc += $r?->c ?? 0;
                        $cmd += $m?->d ?? 0; $cmc += $m?->c ?? 0;
                    }
                    if (abs($cod) < 0.001 && abs($coc) < 0.001 && abs($cmd) < 0.001 && abs($cmc) < 0.001) continue;
                    $cod = round($cod, 2); $coc = round($coc, 2);
                    $cmd = round($cmd, 2); $cmc = round($cmc, 2);
                    $csc = round(($cod + $cmd) - ($coc + $cmc), 2);
                    $childrenData[] = [
                        'id'               => $child->id,
                        'code'             => $child->code,
                        'intitule'         => $child->intitule,
                        'ouverture_debit'  => $cod,
                        'ouverture_credit' => $coc,
                        'mouvement_debit'  => $cmd,
                        'mouvement_credit' => $cmc,
                        'cloture_debit'    => $csc > 0 ? $csc : 0,
                        'cloture_credit'   => $csc < 0 ? abs($csc) : 0,
                        'solde_cloture'    => $csc,
                    ];
                }

                $balances[] = [
                    'id'               => $compte->id,
                    'code'             => $compte->code,
                    'intitule'         => $compte->intitule,
                    'ouverture_debit'  => $od,
                    'ouverture_credit' => $oc,
                    'mouvement_debit'  => $md,
                    'mouvement_credit' => $mc,
                    'cloture_debit'    => $soldeCloture > 0 ? $soldeCloture : 0,
                    'cloture_credit'   => $soldeCloture < 0 ? abs($soldeCloture) : 0,
                    'solde_cloture'    => $soldeCloture,
                    'has_children'     => count($childrenData) > 0,
                    'children_count'   => count($childrenData),
                    'children_data'    => $childrenData,
                    'ecritures_count'  => $cnt,
                ];

                $totaux['ouverture_debit']  += $od;
                $totaux['ouverture_credit'] += $oc;
                $totaux['mouvement_debit']  += $md;
                $totaux['mouvement_credit'] += $mc;
                if ($soldeCloture > 0) { $totaux['cloture_debit']  += $soldeCloture; }
                else                  { $totaux['cloture_credit'] += abs($soldeCloture); }
            }

            // 5. Pagination
            $total         = count($balances);
            $lastPage      = max(1, (int) ceil($total / $perPage));
            $page          = min($page, $lastPage);
            $pagedBalances = array_values(array_slice($balances, ($page - 1) * $perPage, $perPage));

            $totalDebitGeneral  = round($totaux['ouverture_debit'] + $totaux['mouvement_debit'], 2);
            $totalCreditGeneral = round($totaux['ouverture_credit'] + $totaux['mouvement_credit'], 2);

            return $this->success('Balance à 6 colonnes.', [
                'balances'     => $pagedBalances,
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $lastPage,
                'totaux'   => array_map(fn($v) => round($v, 2), $totaux),
                'equilibre' => [
                    'total_debit_general'  => $totalDebitGeneral,
                    'total_credit_general' => $totalCreditGeneral,
                    'est_equilibre'        => abs($totalDebitGeneral - $totalCreditGeneral) < 0.01,
                ],
                'stats' => [
                    'total_comptes'   => $total,
                    'total_ecritures' => array_sum(array_column($balances, 'ecritures_count')),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Détail d'un compte ───────────────────────────────────────────────────

    /**
     * GET /api/balance/compte/{id}/details
     * Retourne le détail d'un compte SYCEBNL (par ancien compte + sous-comptes)
     */
    public function detailCompte(Request $request, int $id): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $compte = NewAccount::where('entreprise_id', $entrepriseId)
                ->with('childrenRecursive')
                ->find($id);

            if (!$compte) {
                return $this->notFound('Compte introuvable.');
            }

            $allIds   = $this->getAllDescendantIds($compte);
            $ecritures = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                ->whereIn('new_account_id', $allIds)
                ->with('oldAccount:id,code,intitule')
                ->get(['debit', 'credit', 'old_account_id', 'new_account_id']);

            // Détail par ancien compte
            $oldAccountsData = $ecritures
                ->groupBy('old_account_id')
                ->map(function ($group) {
                    $oa = $group->first()->oldAccount;
                    return [
                        'id'       => $group->first()->old_account_id,
                        'code'     => $oa?->code ?? 'N/A',
                        'intitule' => $oa?->intitule ?? 'Inconnu',
                        'debit'    => round($group->sum('debit'), 2),
                        'credit'   => round($group->sum('credit'), 2),
                    ];
                })->values()->toArray();

            // Sous-comptes
            $childrenData = [];
            foreach ($compte->children ?? [] as $child) {
                $childIds = $this->getAllDescendantIds($child);
                $childEcritures = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                    ->whereIn('new_account_id', $childIds)
                    ->get(['debit', 'credit']);

                if ($childEcritures->isEmpty()) continue;

                $cd = round($childEcritures->sum('debit'), 2);
                $cc = round($childEcritures->sum('credit'), 2);

                $childrenData[] = [
                    'id'       => $child->id,
                    'code'     => $child->code,
                    'intitule' => $child->intitule,
                    'debit'    => $cd,
                    'credit'   => $cc,
                    'solde'    => round($cd - $cc, 2),
                ];
            }

            $totalDebit  = round($ecritures->sum('debit'), 2);
            $totalCredit = round($ecritures->sum('credit'), 2);
            $solde       = round($totalDebit - $totalCredit, 2);

            return $this->success('Détail du compte.', [
                'compte' => [
                    'id'       => $compte->id,
                    'code'     => $compte->code,
                    'intitule' => $compte->intitule,
                ],
                'total_debit'       => $totalDebit,
                'total_credit'      => $totalCredit,
                'solde'             => $solde,
                'solde_debiteur'    => $solde > 0 ? $solde : 0,
                'solde_crediteur'   => $solde < 0 ? abs($solde) : 0,
                'old_accounts_data' => $oldAccountsData,
                'children_data'     => $childrenData,
                'has_children'      => count($childrenData) > 0,
                'children_count'    => count($childrenData),
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Stats globales ───────────────────────────────────────────────────────

    /**
     * GET /api/balance/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $query = $this->baseGrandLivreQuery($entrepriseId, $exo, $request);

            return $this->success('Statistiques de la balance.', [
                'total_ecritures' => $query->count(),
                'total_debit'     => round($query->sum('debit'), 2),
                'total_credit'    => round($query->sum('credit'), 2),
                'total_comptes'   => $query->distinct('new_account_id')->count('new_account_id'),
                'exercice_actif'  => $exo,
                'entreprise'      => [
                    'id'  => $this->entreprise()->id,
                    'nom' => $this->entreprise()->nom ?? $this->entreprise()->name,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Helper récursif ──────────────────────────────────────────────────────

    /**
     * Récupère l'ID du compte + tous ses descendants (récursif)
     */
    private function getAllDescendantIds($compte): array
    {
        $ids = [$compte->id];

        foreach ($compte->childrenRecursive ?? [] as $child) {
            $ids = array_merge($ids, $this->getAllDescendantIds($child));
        }

        return $ids;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINTS — Balance Tiers
    // ─────────────────────────────────────────────────────────────────────────

    /** GET /api/balance/tiers */
    public function balanceTiers(Request $request)
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();

            if (!$exo) return $this->success(['balances' => [], 'totaux' => [], 'stats' => []]);

            $balances = [];
            foreach ($this->getTiersAccounts($entreprise, $request->get('search')) as $account) {
                $data = $this->calculateTiersBalance($account, $entreprise, $exo, $request->get('dateDebut'), $request->get('dateFin'));
                if ($data) $balances[] = $data;
            }

            $soldeDebiteur  = collect($balances)->sum('solde_debiteur');
            $soldeCrediteur = collect($balances)->sum('solde_crediteur');

            return $this->success([
                'balances' => $balances,
                'totaux'   => [
                    'total_debit'           => collect($balances)->sum('total_debit'),
                    'total_credit'          => collect($balances)->sum('total_credit'),
                    'total_solde_debiteur'  => $soldeDebiteur,
                    'total_solde_crediteur' => $soldeCrediteur,
                    'equilibre'             => abs($soldeDebiteur - $soldeCrediteur) < 1,
                ],
                'stats' => [
                    'total_comptes'   => count($balances),
                    'total_ecritures' => collect($balances)->sum('ecritures_count'),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /** GET /api/balance/tiers/compte/{id}/details */
    public function detailTiers(Request $request, int $id)
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();
            $account    = NewAccount::where('id', $id)->where('entreprise_id', $entreprise->id)->firstOrFail();
            $data       = $this->calculateTiersBalance($account, $entreprise, $exo, $request->get('dateDebut'), $request->get('dateFin'));

            return $data ? $this->success($data) : $this->notFound('Aucune donnée pour ce compte');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /** GET /api/balance/tiers/export */
    public function exportTiers(Request $request)
    {
        try {
            $entreprise = $this->getEntreprise();
            $dateDebut  = $request->get('dateDebut', date('Y-01-01'));
            $dateFin    = $request->get('dateFin',   date('Y-12-31'));
            $filename   = 'balance_tiers_' . str_replace('-', '_', $dateDebut) . '_' . str_replace('-', '_', $dateFin) . '.xlsx';

            return Excel::download(new BalanceExport($entreprise, $dateDebut, $dateFin, null, $request->get('search'), 'tiers'), $filename);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
