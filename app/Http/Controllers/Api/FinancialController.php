<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Balance;
use App\Models\BilanReferenceMapping;
use App\Models\GrandLivre;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialController extends Controller
{
    use ApiResponseTrait;

    private function entreprise()
    {
        return Auth::user()->entreprise;
    }

    // ─── BILAN ────────────────────────────────────────────────────────────────

    public function bilan(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->entreprise();
            $exercice   = (int) $request->get('exercice', date('Y'));
            $dateDebut  = $request->get('date_debut', Carbon::create($exercice, 1, 1)->format('Y-m-d'));
            $dateFin    = $request->get('date_fin',   Carbon::create($exercice, 12, 31)->format('Y-m-d'));

            $balances = Balance::where('entreprise_id', $entreprise->id)
                ->where('periode_debut', '>=', $dateDebut)
                ->where('periode_fin',   '<=', $dateFin)
                ->get();

            $soldes = [];
            foreach ($balances as $b) {
                $soldes[$b->compte_code] = [
                    'debit'      => (float) $b->solde_debiteur,
                    'credit'     => (float) $b->solde_crediteur,
                    'mvt_debit'  => (float) $b->mouvement_debit,
                    'mvt_credit' => (float) $b->mouvement_credit,
                ];
            }

            $actif  = $this->buildActif($soldes);
            $passif = $this->buildPassif($soldes);
            $totaux = $this->computeBilanTotaux($actif, $passif);

            return $this->success('Bilan.', compact('actif', 'passif', 'totaux', 'exercice', 'dateDebut', 'dateFin'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── COMPTE DE RÉSULTAT ───────────────────────────────────────────────────

    public function compteDeResultat(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->entreprise();
            $exercice   = (int) $request->get('exercice', date('Y'));
            $dateDebut  = $request->get('date_debut', Carbon::create($exercice, 1, 1)->format('Y-m-d'));
            $dateFin    = $request->get('date_fin',   Carbon::create($exercice, 12, 31)->format('Y-m-d'));

            $refs = ['RA','RB','RC','RD','RE','RF','RG','RH','TA','TB','TC','TD','TE','TF','TG','TH','TI','TJ','TK','TL','TM','TN'];

            $mappings = BilanReferenceMapping::whereIn('reference_code', $refs)
                ->get()
                ->groupBy('reference_code');

            $balances = Balance::where('entreprise_id', $entreprise->id)
                ->where('periode_debut', '>=', $dateDebut)
                ->where('periode_fin',   '<=', $dateFin)
                ->get();

            $soldes = [];
            foreach ($balances as $b) {
                $soldes[$b->compte_code] = [
                    'debit'  => (float) $b->solde_debiteur,
                    'credit' => (float) $b->solde_crediteur,
                ];
            }

            $variationStockPrefixes = ['6031','6032','6033','6034','6035','6036','6037','6038','6039'];

            $resultat = $this->initResultatStructure();

            foreach ($mappings as $ref => $items) {
                $total = 0;
                foreach ($items as $item) {
                    $code  = $item->account_code;
                    $solde = $soldes[$code] ?? null;
                    if (!$solde) {
                        continue;
                    }
                    $isVariationStock = collect($variationStockPrefixes)->contains(fn($p) => str_starts_with($code, $p));
                    $classe = substr($code, 0, 1);

                    if ($isVariationStock) {
                        $total += $solde['debit'] - $solde['credit'];
                    } elseif ($classe === '6') {
                        $total += $solde['debit'];
                    } elseif ($classe === '7') {
                        $total += $solde['credit'];
                    } elseif ($classe === '8') {
                        $total += in_array($ref, ['TM', 'TN'])
                            ? $solde['debit'] - $solde['credit']
                            : $solde['debit'];
                    }
                }
                if (isset($resultat[$ref])) {
                    $resultat[$ref]['montant'] = $total;
                }
            }

            $totaux = $this->computeResultatTotaux($resultat);

            return $this->success('Compte de résultat.', compact('resultat', 'totaux', 'exercice', 'dateDebut', 'dateFin'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── FLUX DE TRÉSORERIE ───────────────────────────────────────────────────

    public function fluxTresorerie(Request $request): JsonResponse
    {
        try {
            $entreprise = $this->entreprise();
            $exercice   = (int) $request->get('exercice', date('Y'));
            $dateDebut  = $request->get('date_debut', "{$exercice}-01-01");
            $dateFin    = $request->get('date_fin',   "{$exercice}-12-31");

            $comptes = NewAccount::where('entreprise_id', $entreprise->id)
                ->whereHas('mappings')
                ->get();

            $soldesParClasse = [];
            foreach ($comptes as $compte) {
                $oldIds = AccountMapping::where('new_account_id', $compte->id)
                    ->where('entreprise_id', $entreprise->id)
                    ->pluck('old_account_id')
                    ->toArray();

                if (empty($oldIds)) {
                    continue;
                }

                $ecritures = GrandLivre::where('entreprise_id', $entreprise->id)
                    ->whereIn('old_account_id', $oldIds)
                    ->whereBetween('date_ecriture', [$dateDebut, $dateFin])
                    ->where('validated', true)
                    ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
                    ->first();

                $solde  = abs((float) $ecritures->total_debit - (float) $ecritures->total_credit);
                $classe = substr($compte->code, 0, 2);
                $soldesParClasse[$classe] = ($soldesParClasse[$classe] ?? 0) + $solde;
            }

            $flux = [
                'operationnel' => [
                    'encaissements' => ($soldesParClasse['70'] ?? 0) + ($soldesParClasse['71'] ?? 0) + ($soldesParClasse['74'] ?? 0),
                    'decaissements' => ($soldesParClasse['60'] ?? 0) + ($soldesParClasse['61'] ?? 0) + ($soldesParClasse['64'] ?? 0),
                ],
                'investissement' => [
                    'acquisitions' => ($soldesParClasse['20'] ?? 0) + ($soldesParClasse['21'] ?? 0) + ($soldesParClasse['22'] ?? 0),
                    'cessions'     => 0,
                ],
                'financement' => [
                    'entrees' => ($soldesParClasse['10'] ?? 0) + ($soldesParClasse['16'] ?? 0),
                    'sorties' => 0,
                ],
            ];

            $flux['operationnel']['net']   = $flux['operationnel']['encaissements'] - $flux['operationnel']['decaissements'];
            $flux['investissement']['net'] = $flux['investissement']['cessions'] - $flux['investissement']['acquisitions'];
            $flux['financement']['net']    = $flux['financement']['entrees'] - $flux['financement']['sorties'];

            $tresorerieDebut = $this->getTresorerieDebut($entreprise->id, $exercice);
            $variation       = $flux['operationnel']['net'] + $flux['investissement']['net'] + $flux['financement']['net'];

            $tresorerie = [
                'debut'     => $tresorerieDebut,
                'variation' => $variation,
                'fin'       => $tresorerieDebut + $variation,
            ];

            return $this->success('Flux de trésorerie.', compact('flux', 'tresorerie', 'exercice', 'dateDebut', 'dateFin'));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ─── Helpers Bilan ────────────────────────────────────────────────────────

    private function buildActif(array $soldes): array
    {
        $s = fn(string $code, string $side) => $soldes[$code][$side] ?? 0;

        $brutAL  = $s('244200', 'debit') + $s('249400', 'debit');
        $amortAL = abs($s('284400', 'credit'));
        $brutAM  = $s('245100', 'debit');
        $amortAM = abs($s('284500', 'credit'));

        $stocks       = collect($soldes)->filter(fn($v, $k) => str_starts_with($k, '3') && $v['debit'] > 0)->sum('debit');
        $fournisDeb   = $s('409100', 'debit');
        $autresCreances = $s('421100', 'debit') + $s('458000', 'debit') + $s('471000', 'debit');
        $tresorerie   = $s('521100', 'debit') + $s('571000', 'debit');

        $data = [
            'AH' => ['libelle' => 'IMMOBILISATIONS CORPORELLES',              'brut' => $brutAL + $brutAM,   'amortissement' => $amortAL + $amortAM, 'net' => ($brutAL - $amortAL) + ($brutAM - $amortAM)],
            'AL' => ['libelle' => 'Matériel, mobilier et actifs biologiques', 'brut' => $brutAL,             'amortissement' => $amortAL,             'net' => $brutAL - $amortAL],
            'AM' => ['libelle' => 'Matériel de transport',                    'brut' => $brutAM,             'amortissement' => $amortAM,             'net' => $brutAM - $amortAM],
            'AZ' => ['libelle' => 'TOTAL ACTIF IMMOBILISÉ',                   'brut' => $brutAL + $brutAM,   'amortissement' => $amortAL + $amortAM, 'net' => ($brutAL - $amortAL) + ($brutAM - $amortAM)],
            'BB' => ['libelle' => 'Stocks et encours',                        'brut' => $stocks,             'amortissement' => 0,                   'net' => $stocks],
            'BC' => ['libelle' => 'Fournisseurs débiteurs',                   'brut' => $fournisDeb,         'amortissement' => 0,                   'net' => $fournisDeb],
            'BE' => ['libelle' => 'Autres créances',                          'brut' => $autresCreances,     'amortissement' => 0,                   'net' => $autresCreances],
            'BT' => ['libelle' => 'TOTAL ACTIF CIRCULANT',                    'brut' => $stocks + $fournisDeb + $autresCreances, 'amortissement' => 0, 'net' => $stocks + $fournisDeb + $autresCreances],
            'BW' => ['libelle' => 'Banques, établissements financiers, caisses', 'brut' => $tresorerie,     'amortissement' => 0,                   'net' => $tresorerie],
            'BX' => ['libelle' => 'TOTAL TRÉSORERIE ACTIF',                   'brut' => $tresorerie,         'amortissement' => 0,                   'net' => $tresorerie],
        ];

        $data['BZ'] = [
            'libelle'       => 'TOTAL GÉNÉRAL',
            'brut'          => ($data['AZ']['brut'] ?? 0) + ($data['BT']['brut'] ?? 0) + ($data['BX']['brut'] ?? 0),
            'amortissement' => ($data['AZ']['amortissement'] ?? 0) + ($data['BT']['amortissement'] ?? 0),
            'net'           => ($data['AZ']['net'] ?? 0) + ($data['BT']['net'] ?? 0) + ($data['BX']['net'] ?? 0),
        ];

        return $data;
    }

    private function buildPassif(array $soldes): array
    {
        $s = fn(string $code, string $side) => $soldes[$code][$side] ?? 0;

        $ca = $s('101100', 'credit');
        $cd = $s('104100', 'credit') - $s('104900', 'debit');
        $di = $s('421100', 'credit') + $s('431800', 'credit') + $s('458000', 'credit') + $s('471000', 'credit');

        $ck = $ca + $cd;

        $data = [
            'CA' => ['libelle' => 'Dotation non consomptible sans droit reprise', 'net' => $ca],
            'CD' => ['libelle' => 'Dotation consomptible',                        'net' => $cd],
            'CH' => ['libelle' => 'Résultat net de l\'exercice',                  'net' => 0],
            'CK' => ['libelle' => 'TOTAL FONDS PROPRES ET ASSIMILÉS',             'net' => $ck],
            'CZ' => ['libelle' => 'TOTAL RESSOURCES PROPRES ET ASSIMILÉES',       'net' => $ck],
            'DE' => ['libelle' => 'TOTAL RESSOURCES STABLES',                     'net' => $ck],
            'DI' => ['libelle' => 'Autres dettes',                                'net' => $di],
            'DV' => ['libelle' => 'TOTAL PASSIF CIRCULANT',                       'net' => $di],
        ];

        $totalPassif = $ck + $di;
        $data['DZ']  = ['libelle' => 'TOTAL GÉNÉRAL', 'net' => $totalPassif];

        return $data;
    }

    private function computeBilanTotaux(array $actif, array $passif): array
    {
        $totalActif  = $actif['BZ']['net']  ?? 0;
        $totalPassif = $passif['DZ']['net'] ?? 0;
        $difference  = $totalActif - $totalPassif;

        // Équilibrer via CH (résultat net)
        if (abs($difference) >= 1) {
            $passif['CH']['net'] = $difference;
            $ck = ($passif['CA']['net'] ?? 0) + ($passif['CD']['net'] ?? 0) + $difference;
            $passif['CK']['net'] = $ck;
            $passif['CZ']['net'] = $ck;
            $passif['DE']['net'] = $ck;
            $totalPassif = $ck + ($passif['DV']['net'] ?? 0);
            $passif['DZ']['net'] = $totalPassif;
        }

        return [
            'actif'     => $totalActif,
            'passif'    => $totalPassif,
            'difference'=> $totalActif - $totalPassif,
            'equilibre' => abs($totalActif - $totalPassif) < 1,
        ];
    }

    // ─── Helpers Compte de Résultat ───────────────────────────────────────────

    private function initResultatStructure(): array
    {
        $structure = [
            'RA' => ['libelle' => 'Cotisations',                                                     'type' => 'produit'],
            'RB' => ['libelle' => 'Dotations consomptibles transférées au compte de résultat',       'type' => 'produit'],
            'RC' => ['libelle' => 'Revenus liés à la générosité',                                    'type' => 'produit'],
            'RD' => ['libelle' => 'Ventes de marchandises',                                          'type' => 'produit'],
            'RE' => ['libelle' => 'Ventes de services et produits finis',                            'type' => 'produit'],
            'RF' => ['libelle' => 'Subventions d\'exploitation',                                     'type' => 'produit'],
            'RG' => ['libelle' => 'Autres produits et transferts de charges',                        'type' => 'produit'],
            'RH' => ['libelle' => 'Reprises de provisions, dépréciations et autres reprises',        'type' => 'produit'],
            'XA' => ['libelle' => 'REVENUS DES ACTIVITÉS ORDINAIRES',                               'type' => 'total'],
            'TA' => ['libelle' => 'Achats de biens et services liés à l\'activité',                 'type' => 'charge'],
            'TB' => ['libelle' => 'Variation de stocks des achats',                                  'type' => 'charge'],
            'TC' => ['libelle' => 'Achats de marchandises et matières premières',                    'type' => 'charge'],
            'TD' => ['libelle' => 'Autres achats',                                                   'type' => 'charge'],
            'TE' => ['libelle' => 'Variation de stocks de marchandises et matières premières',       'type' => 'charge'],
            'TF' => ['libelle' => 'Transports',                                                      'type' => 'charge'],
            'TG' => ['libelle' => 'Services extérieurs',                                             'type' => 'charge'],
            'TH' => ['libelle' => 'Impôts et taxes',                                                 'type' => 'charge'],
            'TI' => ['libelle' => 'Autres charges',                                                  'type' => 'charge'],
            'TJ' => ['libelle' => 'Charges de personnel',                                            'type' => 'charge'],
            'TK' => ['libelle' => 'Frais financiers et charges assimilées',                          'type' => 'charge'],
            'TL' => ['libelle' => 'Dotations aux amortissements et provisions',                      'type' => 'charge'],
            'XB' => ['libelle' => 'CHARGES DES ACTIVITÉS ORDINAIRES',                               'type' => 'total'],
            'XC' => ['libelle' => 'RÉSULTAT DES ACTIVITÉS ORDINAIRES (XA - XB)',                    'type' => 'resultat'],
            'TM' => ['libelle' => 'Produits H.A.O.',                                                 'type' => 'produit_hao'],
            'TN' => ['libelle' => 'Charges H.A.O.',                                                  'type' => 'charge_hao'],
            'XD' => ['libelle' => 'RÉSULTAT H.A.O. (TM - TN)',                                      'type' => 'resultat'],
            'XE' => ['libelle' => 'RÉSULTAT NET DE L\'EXERCICE (XC + XD)',                          'type' => 'resultat_net'],
        ];

        return collect($structure)->map(fn($v) => ['libelle' => $v['libelle'], 'type' => $v['type'], 'montant' => 0])->all();
    }

    private function computeResultatTotaux(array &$resultat): array
    {
        $prodRefs    = ['RA','RB','RC','RD','RE','RF','RG'];
        $chargeRefs  = ['TA','TB','TC','TD','TE','TF','TG','TH','TI','TJ','TK','TL'];

        $xa = collect($prodRefs)->sum(fn($r) => $resultat[$r]['montant'] ?? 0);
        $xb = collect($chargeRefs)->sum(fn($r) => $resultat[$r]['montant'] ?? 0);
        $xc = $xa - $xb;
        $xd = ($resultat['TM']['montant'] ?? 0) - ($resultat['TN']['montant'] ?? 0);
        $xe = $xc + $xd;

        $resultat['XA']['montant'] = $xa;
        $resultat['XB']['montant'] = $xb;
        $resultat['XC']['montant'] = $xc;
        $resultat['XD']['montant'] = $xd;
        $resultat['XE']['montant'] = $xe;

        return [
            'revenus_ordinaires'  => $xa,
            'charges_ordinaires'  => $xb,
            'resultat_ordinaire'  => $xc,
            'produits_hao'        => $resultat['TM']['montant'] ?? 0,
            'charges_hao'         => $resultat['TN']['montant'] ?? 0,
            'resultat_hao'        => $xd,
            'resultat_net'        => $xe,
        ];
    }

    // ─── Helpers Flux de Trésorerie ───────────────────────────────────────────

    private function getTresorerieDebut(int $entrepriseId, int $exercice): float
    {
        $tresorerieCodes = ['521100', '571000', '52', '57'];
        $debut           = Carbon::create($exercice, 1, 1)->format('Y-m-d');

        $result = GrandLivre::where('entreprise_id', $entrepriseId)
            ->where('date_ecriture', '<', $debut)
            ->where('validated', true)
            ->whereHas('oldAccount', fn($q) => $q->where(function ($w) {
                $w->where('code', 'like', '52%')->orWhere('code', 'like', '57%');
            }))
            ->selectRaw('SUM(debit) - SUM(credit) as solde')
            ->value('solde');

        return (float) $result;
    }
}
