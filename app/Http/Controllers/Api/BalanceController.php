<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Exports\BalanceExport;
use App\Models\GrandLivre;
use App\Models\AccountMapping;
use App\Models\NewAccount;
use App\Models\OldAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BalanceController extends Controller
{
    use ApiResponseTrait;

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS COMMUNS
    // ─────────────────────────────────────────────────────────────────────────

    private function getEntreprise()
    {
        return Auth::user()->entreprise;
    }

    private function getExerciceActif()
    {
        return DB::table('exercices')->where('statut', 1)->first();
    }

    /**
     * Récupère tous les IDs enfants récursivement (identique Livewire getAllChildrenIds)
     */
    private function getAllChildrenIds(int $parentId, int $entrepriseId): array
    {
        $childrenIds    = [];
        $directChildren = NewAccount::where('parent_id', $parentId)
            ->where('entreprise_id', $entrepriseId)
            ->pluck('id')
            ->toArray();

        foreach ($directChildren as $childId) {
            $childrenIds[] = $childId;
            $childrenIds   = array_merge($childrenIds, $this->getAllChildrenIds($childId, $entrepriseId));
        }

        return $childrenIds;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BALANCE GÉNÉRALE — helpers (identiques au Livewire Balance\Index)
    // ─────────────────────────────────────────────────────────────────────────

    private function getParentAccounts($entreprise, $exo, ?string $search = null)
    {
        $parentIds = NewAccount::where('entreprise_id', $entreprise->id)
            ->whereNotNull('parent_id')
            ->distinct()->pluck('parent_id')
            ->filter()->unique()->values()->toArray();

        $rootAccountIds = NewAccount::where('entreprise_id', $entreprise->id)
            ->whereNull('parent_id')
            ->whereHas('mappings', fn($q) => $q->where('exercice_id', $exo->id ?? ''))
            ->pluck('id')->toArray();

        $allParentIds = array_unique(array_merge($parentIds, $rootAccountIds));

        return NewAccount::whereIn('id', $allParentIds)
            ->where('entreprise_id', $entreprise->id)
            ->when($search, fn($q) => $q->where(
                fn($i) => $i->where('code', 'like', "%{$search}%")
                    ->orWhere('intitule', 'like', "%{$search}%")
            ))
            ->orderBy('code')
            ->get();
    }

    private function calculateParentBalance($parentAccount, $entreprise, $exo, ?string $dateDebut, ?string $dateFin): ?array
    {
        $hasChildren = NewAccount::where('parent_id', $parentAccount->id)
            ->where('entreprise_id', $entreprise->id)->exists();

        $allAccountIds = [$parentAccount->id];
        if ($hasChildren) {
            $allAccountIds = array_merge($allAccountIds, $this->getAllChildrenIds($parentAccount->id, $entreprise->id));
        }

        $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->pluck('old_account_id')->unique()->toArray();

        if (empty($oldAccountIds)) return null;

        $mappings = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->with('oldAccount')->get();

        $query = GrandLivre::where('entreprise_id', $entreprise->id)
            ->where('exercice_id', $exo->id ?? '')
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('validated', true);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_ecriture', [$dateDebut, $dateFin]);
        }

        $ecritures = $query->get();
        if ($ecritures->isEmpty()) return null;

        $totalDebit  = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde       = $totalDebit - $totalCredit;

        // Détail par ancien compte
        $oldAccountsData = [];
        foreach ($mappings->groupBy('old_account_id') as $oldAccountId => $accountMappings) {
            $oldEcritures = $ecritures->where('old_account_id', $oldAccountId);
            if ($oldEcritures->isNotEmpty()) {
                $oldAccount = $mappings->firstWhere('old_account_id', $oldAccountId)->oldAccount;
                $d = $oldEcritures->sum('debit');
                $c = $oldEcritures->sum('credit');
                $oldAccountsData[] = [
                    'id'       => $oldAccountId,
                    'code'     => $oldAccount->code,
                    'intitule' => $oldAccount->intitule,
                    'debit'    => $d,
                    'credit'   => $c,
                    'solde'    => $d - $c,
                ];
            }
        }

        // Détail par compte enfant SYCEBNL
        $childrenData = [];
        foreach ($allAccountIds as $accountId) {
            if ($accountId == $parentAccount->id) continue;
            $childAccount = NewAccount::find($accountId);
            if (!$childAccount) continue;
            $childMappings      = $mappings->where('new_account_id', $accountId);
            $childOldAccountIds = $childMappings->pluck('old_account_id')->toArray();
            $childEcritures     = $ecritures->filter(fn($e) => in_array($e->old_account_id, $childOldAccountIds));
            if ($childEcritures->isNotEmpty()) {
                $cd = $childEcritures->sum('debit');
                $cc = $childEcritures->sum('credit');
                $childrenData[] = [
                    'id'          => $childAccount->id,
                    'code'        => $childAccount->code,
                    'intitule'    => $childAccount->intitule,
                    'debit'       => $cd,
                    'credit'      => $cc,
                    'solde'       => $cd - $cc,
                    'solde_absolu'=> abs($cd - $cc),
                ];
            }
        }

        return [
            'id'                 => $parentAccount->id,
            'code'               => $parentAccount->code,
            'intitule'           => $parentAccount->intitule,
            'total_debit'        => $totalDebit,
            'total_credit'       => $totalCredit,
            'solde'              => $solde,
            'solde_debiteur'     => $solde > 0 ? $solde : 0,
            'solde_crediteur'    => $solde < 0 ? abs($solde) : 0,
            'ecritures_count'    => $ecritures->count(),
            'old_accounts_data'  => $oldAccountsData,
            'old_accounts_count' => count($oldAccountsData),
            'children_data'      => $childrenData,
            'children_count'     => count($childrenData),
            'has_children'       => $hasChildren,
            '_ecritures'         => $ecritures, // utilisé pour 6 colonnes, supprimé avant envoi
        ];
    }

    private function calcSixColonnes(array $balance): array
    {
        $ecritures       = $balance['_ecritures'];
        $ranEcritures    = $ecritures->filter(fn($e) => $e->journal_code === 'RAN');
        $mvtEcritures    = $ecritures->filter(fn($e) => $e->journal_code !== 'RAN');
        $ouvertureDebit  = round($ranEcritures->sum('debit'),  2);
        $ouvertureCredit = round($ranEcritures->sum('credit'), 2);
        $mouvDebit       = round($mvtEcritures->sum('debit'),  2);
        $mouvCredit      = round($mvtEcritures->sum('credit'), 2);
        $soldeCloture    = round(($ouvertureDebit + $mouvDebit) - ($ouvertureCredit + $mouvCredit), 2);

        return [
            'id'                 => $balance['id'],
            'code'               => $balance['code'],
            'intitule'           => $balance['intitule'],
            'has_children'       => $balance['has_children'],
            'children_count'     => $balance['children_count'],
            'ecritures_count'    => $balance['ecritures_count'],
            'ouverture_debit'    => $ouvertureDebit,
            'ouverture_credit'   => $ouvertureCredit,
            'mouvement_debit'    => $mouvDebit,
            'mouvement_credit'   => $mouvCredit,
            'cloture_debit'      => $soldeCloture > 0 ? $soldeCloture : 0,
            'cloture_credit'     => $soldeCloture < 0 ? abs($soldeCloture) : 0,
            'solde_cloture'      => $soldeCloture,
            'old_accounts_data'  => $balance['old_accounts_data'],
            'old_accounts_count' => $balance['old_accounts_count'],
            'children_data'      => $balance['children_data'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // BALANCE TIERS — helpers (identiques au Livewire Balance\TiersBalance)
    // ─────────────────────────────────────────────────────────────────────────

    private function getTiersAccounts($entreprise, ?string $search = null)
    {
        return NewAccount::where('entreprise_id', $entreprise->id)
            ->where('code', 'like', '4%')
            ->whereNotNull('parent_id')
            ->when($search, fn($q) => $q->where(
                fn($i) => $i->where('code', 'like', "%{$search}%")
                    ->orWhere('intitule', 'like', "%{$search}%")
            ))
            ->orderBy('code')
            ->get();
    }

    private function calculateTiersBalance($account, $entreprise, $exo, ?string $dateDebut, ?string $dateFin): ?array
    {
        $allAccountIds = $this->getAllChildrenIds($account->id, $entreprise->id);
        array_unshift($allAccountIds, $account->id);

        $oldAccountIds = AccountMapping::whereIn('new_account_id', $allAccountIds)
            ->where('entreprise_id', $entreprise->id)
            ->where('exercice_id', $exo->id)
            ->pluck('old_account_id')->unique()->toArray();

        if (empty($oldAccountIds)) return null;

        $query = GrandLivre::where('entreprise_id', $entreprise->id)
            ->whereIn('old_account_id', $oldAccountIds)
            ->where('validated', true);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_ecriture', [$dateDebut, $dateFin]);
        }

        $ecritures = $query->get();
        if ($ecritures->isEmpty()) return null;

        $totalDebit  = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');
        $solde       = $totalDebit - $totalCredit;

        $oldAccountsData = [];
        foreach ($oldAccountIds as $oldId) {
            $oldEcritures = $ecritures->where('old_account_id', $oldId);
            if ($oldEcritures->isNotEmpty()) {
                $oldAccount      = OldAccount::find($oldId);
                $d               = $oldEcritures->sum('debit');
                $c               = $oldEcritures->sum('credit');
                $oldAccountsData[] = [
                    'id'       => $oldId,
                    'code'     => $oldAccount->code    ?? 'N/A',
                    'intitule' => $oldAccount->intitule ?? 'N/A',
                    'debit'    => $d,
                    'credit'   => $c,
                    'solde'    => $d - $c,
                ];
            }
        }

        return [
            'id'                => $account->id,
            'code'              => $account->code,
            'intitule'          => $account->intitule,
            'total_debit'       => $totalDebit,
            'total_credit'      => $totalCredit,
            'solde'             => $solde,
            'solde_debiteur'    => $solde > 0 ? $solde : 0,
            'solde_crediteur'   => $solde < 0 ? abs($solde) : 0,
            'ecritures_count'   => $ecritures->count(),
            'old_accounts_data' => $oldAccountsData,
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINTS — Stats
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/balance/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();

            $query = GrandLivre::where('entreprise_id', $entreprise->id)
                ->where('exercice_id', $exo->id ?? '')
                ->where('validated', true);

            if ($request->filled('dateDebut') && $request->filled('dateFin')) {
                $query->whereBetween('date_ecriture', [$request->dateDebut, $request->dateFin]);
            }

            return $this->success('OK', [
                'total_ecritures' => $query->count(),
                'total_debit'     => round($query->sum('debit'), 2),
                'total_credit'    => round($query->sum('credit'), 2),
                'exercice_actif'  => $exo,
                'entreprise'      => [
                    'id'  => $entreprise->id,
                    'nom' => $entreprise->nom ?? $entreprise->name,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINTS — Balance Générale (4 & 6 colonnes)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/balance/4colonnes
     */
    public function balance4Colonnes(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();
            $balances   = [];

            foreach ($this->getParentAccounts($entreprise, $exo, $request->get('search')) as $parent) {
                $data = $this->calculateParentBalance(
                    $parent, $entreprise, $exo,
                    $request->get('dateDebut'), $request->get('dateFin')
                );
                if ($data && $data['ecritures_count'] > 0) {
                    unset($data['_ecritures']);
                    $balances[] = $data;
                }
            }

            $soldeDebiteur  = collect($balances)->sum('solde_debiteur');
            $soldeCrediteur = collect($balances)->sum('solde_crediteur');

            return $this->success('OK', [
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

    /**
     * GET /api/balance/6colonnes
     */
    public function balance6Colonnes(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();
            $balances6  = [];

            foreach ($this->getParentAccounts($entreprise, $exo, $request->get('search')) as $parent) {
                $data = $this->calculateParentBalance(
                    $parent, $entreprise, $exo,
                    $request->get('dateDebut'), $request->get('dateFin')
                );
                if ($data && $data['ecritures_count'] > 0) {
                    $balances6[] = $this->calcSixColonnes($data);
                }
            }

            $totOuvD = round(collect($balances6)->sum('ouverture_debit'),  2);
            $totOuvC = round(collect($balances6)->sum('ouverture_credit'), 2);
            $totMvtD = round(collect($balances6)->sum('mouvement_debit'),  2);
            $totMvtC = round(collect($balances6)->sum('mouvement_credit'), 2);

            return $this->success('OK', [
                'balances' => $balances6,
                'totaux'   => [
                    'ouverture_debit'  => $totOuvD,
                    'ouverture_credit' => $totOuvC,
                    'mouvement_debit'  => $totMvtD,
                    'mouvement_credit' => $totMvtC,
                    'cloture_debit'    => round(collect($balances6)->sum('cloture_debit'),  2),
                    'cloture_credit'   => round(collect($balances6)->sum('cloture_credit'), 2),
                ],
                'equilibre' => [
                    'total_debit'  => round($totOuvD + $totMvtD, 2),
                    'total_credit' => round($totOuvC + $totMvtC, 2),
                    'equilibre'    => abs(($totOuvD + $totMvtD) - ($totOuvC + $totMvtC)) < 1,
                ],
                'stats' => [
                    'total_comptes'   => count($balances6),
                    'total_ecritures' => collect($balances6)->sum('ecritures_count'),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/balance/compte/{id}/details
     */
    public function detailCompte(Request $request, int $id): JsonResponse
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();
            $account    = NewAccount::where('id', $id)
                ->where('entreprise_id', $entreprise->id)
                ->firstOrFail();

            $data = $this->calculateParentBalance(
                $account, $entreprise, $exo,
                $request->get('dateDebut'), $request->get('dateFin')
            );

            if (!$data) return $this->notFound('Aucune donnée pour ce compte');
            unset($data['_ecritures']);
            return $this->success('OK', $data);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINTS — Balance Tiers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/balance/tiers
     */
    public function balanceTiers(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();

            if (!$exo) {
                return $this->success('OK', ['balances' => [], 'totaux' => [], 'stats' => []]);
            }

            $balances = [];
            foreach ($this->getTiersAccounts($entreprise, $request->get('search')) as $account) {
                $data = $this->calculateTiersBalance(
                    $account, $entreprise, $exo,
                    $request->get('dateDebut'), $request->get('dateFin')
                );
                if ($data) $balances[] = $data;
            }

            $soldeDebiteur  = collect($balances)->sum('solde_debiteur');
            $soldeCrediteur = collect($balances)->sum('solde_crediteur');

            return $this->success('OK', [
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

    /**
     * GET /api/balance/tiers/compte/{id}/details
     */
    public function detailTiers(Request $request, int $id): JsonResponse
    {
        try {
            $entreprise = $this->getEntreprise();
            $exo        = $this->getExerciceActif();
            $account    = NewAccount::where('id', $id)
                ->where('entreprise_id', $entreprise->id)
                ->firstOrFail();

            $data = $this->calculateTiersBalance(
                $account, $entreprise, $exo,
                $request->get('dateDebut'), $request->get('dateFin')
            );

            return $data
                ? $this->success('OK', $data)
                : $this->notFound('Aucune donnée pour ce compte');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENDPOINTS — Exports Excel
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * GET /api/balance/export/typed
     * balanceType : 4colonnes | 6colonnes | tiers
     */
    public function exportTyped(Request $request)
    {
        try {
            $entreprise  = $this->getEntreprise();
            $balanceType = $request->get('balanceType', '4colonnes');
            $dateDebut   = $request->get('dateDebut', date('Y-01-01'));
            $dateFin     = $request->get('dateFin',   date('Y-12-31'));
            $filename    = 'balance_' . $balanceType
                . '_' . str_replace('-', '_', $dateDebut)
                . '_' . str_replace('-', '_', $dateFin) . '.xlsx';

            return Excel::download(
                new BalanceExport($entreprise, $dateDebut, $dateFin, null, $request->get('search'), $balanceType),
                $filename
            );
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/balance/tiers/export
     */
    public function exportTiers(Request $request)
    {
        try {
            $entreprise = $this->getEntreprise();
            $dateDebut  = $request->get('dateDebut', date('Y-01-01'));
            $dateFin    = $request->get('dateFin',   date('Y-12-31'));
            $filename   = 'balance_tiers_'
                . str_replace('-', '_', $dateDebut) . '_'
                . str_replace('-', '_', $dateFin) . '.xlsx';

            return Excel::download(
                new BalanceExport($entreprise, $dateDebut, $dateFin, null, $request->get('search'), 'tiers'),
                $filename
            );
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/balance/export/excel
     */
    public function exportExcel(Request $request)
    {
        try {
            $entreprise = $this->getEntreprise();
            return Excel::download(
                new BalanceExport(
                    $entreprise,
                    $request->get('dateDebut', '2024-01-01'),
                    $request->get('dateFin',   '2024-12-31'),
                    $request->get('exercice'),
                    $request->get('search')
                ),
                'balance_' . $entreprise->id . '_' . date('Y-m-d') . '.xlsx'
            );
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * GET /api/balance/export
     */
    public function export(Request $request)
    {
        try {
            $entreprise = $this->getEntreprise();
            $type       = $request->get('type', 'old');
            $dateDebut  = $request->get('dateDebut');
            $dateFin    = $request->get('dateFin');
            $exercice   = $request->get('exercice');
            $filename   = 'balance-' . $type . '-' . ($dateDebut ?: 'all') . '-' . ($dateFin ?: 'all') . '-' . now()->format('Y-m-d') . '.xlsx';

            return Excel::download(
                new BalanceExport($entreprise->id, $type, $dateDebut, $dateFin, $exercice),
                $filename
            );
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
