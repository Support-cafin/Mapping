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

        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->whereBetween('date_ecriture', [$request->date_debut, $request->date_fin]);
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
     */
    public function balance4Colonnes(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            // Récupérer tous les comptes SYCEBNL racines (sans parent)
            $comptes = NewAccount::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNull('parent_id')
                ->with('childrenRecursive')
                ->orderBy('code')
                ->get();

            $balances = [];
            $totalDebit   = 0;
            $totalCredit  = 0;
            $totalSoldeD  = 0;
            $totalSoldeC  = 0;

            foreach ($comptes as $compte) {
                // Récupérer tous les IDs du compte + ses enfants
                $allIds = $this->getAllDescendantIds($compte);

                // Écritures Grand Livre pour ces comptes
                $ecritures = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                    ->whereIn('new_account_id', $allIds)
                    ->get(['debit', 'credit', 'old_account_id', 'new_account_id']);

                if ($ecritures->isEmpty()) continue;

                $debit  = round($ecritures->sum('debit'), 2);
                $credit = round($ecritures->sum('credit'), 2);
                $solde  = round($debit - $credit, 2);

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

                // Sous-comptes (enfants directs)
                $childrenData = [];
                foreach ($compte->children ?? [] as $child) {
                    $childIds = $this->getAllDescendantIds($child);
                    $childEcritures = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                        ->whereIn('new_account_id', $childIds)
                        ->get(['debit', 'credit']);

                    if ($childEcritures->isEmpty()) continue;

                    $cd = round($childEcritures->sum('debit'), 2);
                    $cc = round($childEcritures->sum('credit'), 2);
                    $cs = round($cd - $cc, 2);

                    $childrenData[] = [
                        'id'       => $child->id,
                        'code'     => $child->code,
                        'intitule' => $child->intitule,
                        'debit'    => $cd,
                        'credit'   => $cc,
                        'solde'    => $cs,
                    ];
                }

                $row = [
                    'id'               => $compte->id,
                    'code'             => $compte->code,
                    'intitule'         => $compte->intitule,
                    'total_debit'      => $debit,
                    'total_credit'     => $credit,
                    'solde'            => $solde,
                    'solde_debiteur'   => $solde > 0 ? $solde : 0,
                    'solde_crediteur'  => $solde < 0 ? abs($solde) : 0,
                    'ecritures_count'  => $ecritures->count(),
                    'has_children'     => count($childrenData) > 0,
                    'children_count'   => count($childrenData),
                    'old_accounts_data'=> $oldAccountsData,
                    'children_data'    => $childrenData,
                ];

                $balances[]   = $row;
                $totalDebit  += $debit;
                $totalCredit += $credit;

                if ($solde > 0) {
                    $totalSoldeD += $solde;
                } else {
                    $totalSoldeC += abs($solde);
                }
            }

            return $this->success('Balance à 4 colonnes.', [
                'balances' => $balances,
                'totaux' => [
                    'total_debit'      => round($totalDebit, 2),
                    'total_credit'     => round($totalCredit, 2),
                    'total_solde_debiteur'  => round($totalSoldeD, 2),
                    'total_solde_crediteur' => round($totalSoldeC, 2),
                    'equilibre'        => abs($totalSoldeD - $totalSoldeC) < 0.01,
                ],
                'stats' => [
                    'total_ecritures' => array_sum(array_column($balances, 'ecritures_count')),
                    'total_comptes'   => count($balances),
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
     */
    public function balance6Colonnes(Request $request): JsonResponse
    {
        try {
            $exo          = $this->exerciceActif();
            $entrepriseId = $this->entrepriseId();

            $comptes = NewAccount::where('entreprise_id', $entrepriseId)
                ->where('exercice_id', $exo->id ?? '')
                ->whereNull('parent_id')
                ->with('childrenRecursive')
                ->orderBy('code')
                ->get();

            $balances = [];
            $totaux = [
                'ouverture_debit'  => 0, 'ouverture_credit' => 0,
                'mouvement_debit'  => 0, 'mouvement_credit' => 0,
                'cloture_debit'    => 0, 'cloture_credit'   => 0,
            ];

            foreach ($comptes as $compte) {
                $allIds = $this->getAllDescendantIds($compte);

                // Soldes d'ouverture (journal RAN)
                $ran = GrandLivre::where('entreprise_id', $entrepriseId)
                    ->where('exercice_id', $exo->id ?? '')
                    ->whereIn('new_account_id', $allIds)
                    ->where('journal_code', 'RAN')
                    ->get(['debit', 'credit']);

                $ouvertureDebit  = round($ran->sum('debit'), 2);
                $ouvertureCredit = round($ran->sum('credit'), 2);

                // Mouvements (hors RAN)
                $mouvements = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                    ->whereIn('new_account_id', $allIds)
                    ->where('journal_code', '!=', 'RAN')
                    ->get(['debit', 'credit']);

                $mouvementDebit  = round($mouvements->sum('debit'), 2);
                $mouvementCredit = round($mouvements->sum('credit'), 2);

                // Solde de clôture
                $totalDebit  = round($ouvertureDebit + $mouvementDebit, 2);
                $totalCredit = round($ouvertureCredit + $mouvementCredit, 2);
                $soldeCloture = round($totalDebit - $totalCredit, 2);

                $hasOuverture  = abs($ouvertureDebit) > 0.001 || abs($ouvertureCredit) > 0.001;
                $hasMouvements = abs($mouvementDebit) > 0.001 || abs($mouvementCredit) > 0.001;
                $hasCloture    = abs($soldeCloture) > 0.001;

                if (!$hasOuverture && !$hasMouvements && !$hasCloture) continue;

                // Sous-comptes
                $childrenData = [];
                foreach ($compte->children ?? [] as $child) {
                    $childIds = $this->getAllDescendantIds($child);

                    $childRan = GrandLivre::where('entreprise_id', $entrepriseId)
                        ->where('exercice_id', $exo->id ?? '')
                        ->whereIn('new_account_id', $childIds)
                        ->where('journal_code', 'RAN')
                        ->get(['debit', 'credit']);

                    $childMvt = $this->baseGrandLivreQuery($entrepriseId, $exo, $request)
                        ->whereIn('new_account_id', $childIds)
                        ->where('journal_code', '!=', 'RAN')
                        ->get(['debit', 'credit']);

                    $cod = round($childRan->sum('debit'), 2);
                    $coc = round($childRan->sum('credit'), 2);
                    $cmd = round($childMvt->sum('debit'), 2);
                    $cmc = round($childMvt->sum('credit'), 2);
                    $csc = round(($cod + $cmd) - ($coc + $cmc), 2);

                    if (abs($cod) < 0.001 && abs($coc) < 0.001 && abs($cmd) < 0.001 && abs($cmc) < 0.001) continue;

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
                    'ouverture_debit'  => $ouvertureDebit,
                    'ouverture_credit' => $ouvertureCredit,
                    'mouvement_debit'  => $mouvementDebit,
                    'mouvement_credit' => $mouvementCredit,
                    'cloture_debit'    => $soldeCloture > 0 ? $soldeCloture : 0,
                    'cloture_credit'   => $soldeCloture < 0 ? abs($soldeCloture) : 0,
                    'solde_cloture'    => $soldeCloture,
                    'has_children'     => count($childrenData) > 0,
                    'children_count'   => count($childrenData),
                    'children_data'    => $childrenData,
                    'ecritures_count'  => $mouvements->count() + $ran->count(),
                ];

                $totaux['ouverture_debit']  += $ouvertureDebit;
                $totaux['ouverture_credit'] += $ouvertureCredit;
                $totaux['mouvement_debit']  += $mouvementDebit;
                $totaux['mouvement_credit'] += $mouvementCredit;

                if ($soldeCloture > 0) {
                    $totaux['cloture_debit']  += $soldeCloture;
                } else {
                    $totaux['cloture_credit'] += abs($soldeCloture);
                }
            }

            // Équilibre
            $totalDebitGeneral  = round($totaux['ouverture_debit'] + $totaux['mouvement_debit'], 2);
            $totalCreditGeneral = round($totaux['ouverture_credit'] + $totaux['mouvement_credit'], 2);

            return $this->success('Balance à 6 colonnes.', [
                'balances' => $balances,
                'totaux'   => array_map(fn($v) => round($v, 2), $totaux),
                'equilibre' => [
                    'total_debit_general'  => $totalDebitGeneral,
                    'total_credit_general' => $totalCreditGeneral,
                    'est_equilibre'        => abs($totalDebitGeneral - $totalCreditGeneral) < 0.01,
                ],
                'stats' => [
                    'total_comptes'   => count($balances),
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
