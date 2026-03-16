<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Entreprise;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrandLivrePdfController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/grand-livre/{entrepriseId}/export/pdf",
     *     tags={"Grand Livre"},
     *     summary="Export PDF optimisé par entreprise (max 10 comptes, 100 écritures/compte)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="entrepriseId", in="path",  required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dateDebut",    in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="dateFin",      in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="journalCode",  in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search",       in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="exercice",     in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier PDF",      @OA\MediaType(mediaType="application/pdf")),
     *     @OA\Response(response=400, description="Aucune donnée",    @OA\JsonContent(ref="#/components/schemas/ApiError")),
     *     @OA\Response(response=404, description="Entreprise absent",@OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function export(Request $request, int $entrepriseId)
    {
        set_time_limit(300);
        ini_set('memory_limit', '1024M');

        try {
            $entreprise = Entreprise::find($entrepriseId);
            if (!$entreprise) return $this->notFound('Entreprise non trouvée.');

            $filters = $request->only(['dateDebut','dateFin','journalCode','search','exercice']);
            $accounts = $this->getAccountsWithEntries($entreprise, $filters);
            if ($accounts->isEmpty()) return $this->error('Aucune donnée à exporter.');

            $pdf = Pdf::loadView('pdf.grand-livre-detailed-optimized', [
                'accounts'   => $accounts,
                'totals'     => $this->calculateTotals($accounts, $entreprise, $filters),
                'entreprise' => $entreprise,
                'filters'    => [
                    'date_debut' => !empty($filters['dateDebut']) ? Carbon::parse($filters['dateDebut'])->format('d/m/Y') : '',
                    'date_fin'   => !empty($filters['dateFin'])   ? Carbon::parse($filters['dateFin'])->format('d/m/Y')   : '',
                    'journal'    => $filters['journalCode'] ?? null,
                    'exercice'   => $filters['exercice']    ?? null,
                    'search'     => $filters['search']      ?? null,
                ],
            ]);
            $pdf->setPaper('A4','landscape')->setOptions(['defaultFont'=>'sans-serif','isRemoteEnabled'=>false,'dpi'=>72,'compress'=>true,'isPhpEnabled'=>false,'isHtml5ParserEnabled'=>true]);

            return response($pdf->output(), 200, ['Content-Type'=>'application/pdf','Content-Disposition'=>'attachment; filename="grand_livre_'.$entreprise->code.'_'.date('Ymd_His').'.pdf"']);
        } catch (\Throwable $e) {
            Log::error('Erreur export PDF', ['error'=>$e->getMessage()]);
            return $this->serverError($e->getMessage());
        }
    }

    private function getAccountsWithEntries(Entreprise $entreprise, array $filters)
    {
        $exo = DB::table('exercices')->where('statut',1)->first();
        $join = function($join) use ($entreprise,$exo) {
            $join->on('gl.old_account_id','=','am.old_account_id')->where('am.entreprise_id',$entreprise->id)->where('am.exercice_id',$exo->id);
        };

        $accounts = DB::table('grand_livres as gl')
            ->select(['na.code','na.intitule',DB::raw('SUM(gl.debit) as total_debit'),DB::raw('SUM(gl.credit) as total_credit'),DB::raw('COUNT(gl.id) as nombre_ecritures')])
            ->leftJoin('account_mappings as am',$join)->leftJoin('new_accounts as na','am.new_account_id','=','na.id')
            ->where('gl.entreprise_id',$entreprise->id)->where('gl.exercice_id',$exo->id)->whereNotNull('na.id')
            ->when(!empty($filters['dateDebut']) && !empty($filters['dateFin']),fn($q)=>$q->whereBetween('gl.date_ecriture',[$filters['dateDebut'],$filters['dateFin']]))
            ->when(!empty($filters['journalCode']),fn($q)=>$q->where('gl.journal_code',$filters['journalCode']))
            ->groupBy('na.code','na.intitule')->orderBy('na.code')->get()->take(10);

        return $accounts->map(function ($account) use ($entreprise,$exo,$filters,$join) {
            $ecritures = DB::table('grand_livres as gl')
                ->select(['gl.date_ecriture','gl.piece','gl.journal_code','gl.libelle','gl.debit','gl.credit','oa.code as old_account_code'])
                ->leftJoin('account_mappings as am',$join)->leftJoin('new_accounts as na','am.new_account_id','=','na.id')->leftJoin('old_accounts as oa','gl.old_account_id','=','oa.id')
                ->where('na.code',$account->code)->where('gl.entreprise_id',$entreprise->id)->where('gl.exercice_id',$exo->id)->whereNotNull('na.id')
                ->when(!empty($filters['dateDebut']) && !empty($filters['dateFin']),fn($q)=>$q->whereBetween('gl.date_ecriture',[$filters['dateDebut'],$filters['dateFin']]))
                ->when(!empty($filters['journalCode']),fn($q)=>$q->where('gl.journal_code',$filters['journalCode']))
                ->when(!empty($filters['search']),function($q) use ($filters) { $s='%'.$filters['search'].'%'; $q->where(fn($sub)=>$sub->where('gl.libelle','like',$s)->orWhere('oa.code','like',$s)); })
                ->orderBy('gl.date_ecriture')->orderBy('gl.id')->limit(100)->get();

            $solde = $account->total_debit - $account->total_credit;
            return (object)['code'=>$account->code,'intitule'=>$account->intitule,'total_debit'=>$account->total_debit,'total_credit'=>$account->total_credit,'nombre_ecritures'=>$account->nombre_ecritures,'ecritures_count'=>$ecritures->count(),'solde'=>$solde,'ecritures'=>$ecritures,'has_more'=>$account->nombre_ecritures>100];
        });
    }

    private function calculateTotals($accounts, Entreprise $entreprise, array $filters): array
    {
        $exo  = DB::table('exercices')->where('statut',1)->first();
        $totals = DB::table('grand_livres as gl')
            ->select([DB::raw('SUM(gl.debit) as total_debit'),DB::raw('SUM(gl.credit) as total_credit'),DB::raw('COUNT(gl.id) as total_ecritures')])
            ->leftJoin('account_mappings as am',function($join) use ($entreprise,$exo){ $join->on('gl.old_account_id','=','am.old_account_id')->where('am.entreprise_id',$entreprise->id)->where('am.exercice_id',$exo->id); })
            ->leftJoin('new_accounts as na','am.new_account_id','=','na.id')
            ->where('gl.entreprise_id',$entreprise->id)->where('gl.exercice_id',$exo->id)->whereNotNull('na.id')
            ->when(!empty($filters['dateDebut']) && !empty($filters['dateFin']),fn($q)=>$q->whereBetween('gl.date_ecriture',[$filters['dateDebut'],$filters['dateFin']]))
            ->when(!empty($filters['journalCode']),fn($q)=>$q->where('gl.journal_code',$filters['journalCode']))
            ->first();

        $solde = ($totals->total_debit??0)-($totals->total_credit??0);
        return ['total_debit'=>$totals->total_debit??0,'total_credit'=>$totals->total_credit??0,'total_ecritures'=>$totals->total_ecritures??0,'total_comptes'=>$accounts->count(),'solde_global'=>abs($solde),'is_debiteur'=>$solde>0,'comptes_affiches'=>$accounts->count(),'ecritures_affichees'=>$accounts->sum('ecritures_count')];
    }
}
