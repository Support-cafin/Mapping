<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Models\GrandLivre;
use App\Models\Entreprise;
use App\Models\NewAccount;
use App\Models\Exercice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GrandLivreExportPdfController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/grand-livre/export/pdf/detail",
     *     tags={"Grand Livre"},
     *     summary="Export PDF grand livre détaillé (par écriture)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="entreprise_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dateDebut",     in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="dateFin",       in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="journalCode",   in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="search",        in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="lettre",        in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sourceFilter",  in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="mappingFilter", in="query", @OA\Schema(type="string", enum={"all","mapped","unmapped"})),
     *     @OA\Response(response=200, description="Fichier PDF",   @OA\MediaType(mediaType="application/pdf")),
     *     @OA\Response(response=400, description="Aucune donnée", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function exportPdf(Request $request)
    {
        try {
            $data = $this->getGrandLivreData($request->all());
            if (!$data['hasData']) return $this->error('Aucune écriture à exporter pour cette période.');

            $pdf = Pdf::loadView('exports.grandlivre-pdf', $data)
                ->setPaper('A4','landscape')
                ->setOptions(['defaultFont'=>'DejaVu Sans','isRemoteEnabled'=>false,'isHtml5ParserEnabled'=>true,'isPhpEnabled'=>false]);

            return $pdf->download('grand_livre_'.date('Y-m-d_H-i').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Erreur export PDF: '.$e->getMessage());
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/grand-livre/export/pdf/general",
     *     tags={"Grand Livre"},
     *     summary="Export PDF grand livre général (regroupé par compte)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="entreprise_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dateDebut",     in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="dateFin",       in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Fichier PDF", @OA\MediaType(mediaType="application/pdf")),
     *     @OA\Response(response=400, description="Aucune donnée", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function exportPdfGeneral(Request $request)
    {
        try {
            $data = $this->getGrandLivreGeneralData($request->all());
            if (empty($data['comptes'])) return $this->error('Aucune écriture à exporter pour cette période.');

            $pdf = Pdf::loadView('exports.grand-livre-general-pdf', $data)
                ->setPaper('A4','portrait')
                ->setOptions(['defaultFont'=>'DejaVu Sans','isRemoteEnabled'=>false,'isHtml5ParserEnabled'=>true,'isPhpEnabled'=>false]);

            return $pdf->download('grand_livre_general_'.date('Y-m-d_H-i').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Erreur export PDF général: '.$e->getMessage());
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/grand-livre/print",
     *     tags={"Grand Livre"},
     *     summary="Obtenir les données JSON du grand livre général (pour impression Angular)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="entreprise_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dateDebut",     in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="dateFin",       in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Données du grand livre", @OA\JsonContent(ref="#/components/schemas/ApiResponse")),
     *     @OA\Response(response=400, description="Aucune donnée",          @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function print(Request $request): JsonResponse
    {
        try {
            $data = $this->getGrandLivreGeneralData($request->all());
            if (empty($data['comptes'])) return $this->error('Aucune écriture à imprimer pour cette période.');
            return $this->success('Données du grand livre récupérées.', $data);
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    // ── Privé ──────────────────────────────────────────────────────────────────

    private function getGrandLivreData(array $filters): array
    {
        [$entreprise, $exo] = $this->resolveEntrepriseAndExo($filters);

        $base = function ($q) use ($entreprise, $exo, $filters) {
            $q->where('entreprise_id', $entreprise->id)->where('exercice_id', $exo->id)->where('validated', true);
            $this->applyCommonFilters($q, $filters);
        };

        $totaux    = GrandLivre::query()->tap($base)->selectRaw('COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')->first();
        $ecritures = GrandLivre::select(['id','date_ecriture','journal_code','piece','libelle','debit','credit','source','old_account_id','lettre'])->with(['oldAccount:id,code,intitule'])->tap($base)->orderBy('date_ecriture')->orderBy('id')->get();

        return ['entreprise'=>$entreprise,'ecritures'=>$ecritures,'hasData'=>$ecritures->isNotEmpty(),'totaux'=>['debit'=>(float)$totaux->total_debit,'credit'=>(float)$totaux->total_credit,'solde'=>abs($totaux->total_debit-$totaux->total_credit)],'filters'=>$filters,'dateGeneration'=>now()->format('d/m/Y H:i:s')];
    }

    private function getGrandLivreGeneralData(array $filters): array
    {
        [$entreprise, $exo] = $this->resolveEntrepriseAndExo($filters);

        $base = GrandLivre::query()->where('entreprise_id',$entreprise->id)->where('exercice_id',$exo->id)->where('validated',true);
        if (!empty($filters['dateDebut']) && !empty($filters['dateFin'])) $base->whereBetween('date_ecriture',[$filters['dateDebut'],$filters['dateFin']]);

        $aggregates = (clone $base)->select('new_account_id',DB::raw('COALESCE(SUM(debit),0) as total_debit'),DB::raw('COALESCE(SUM(credit),0) as total_credit'),DB::raw('COUNT(*) as nb'))->whereNotNull('new_account_id')->groupBy('new_account_id')->having('nb','>',0)->get()->keyBy('new_account_id');

        if ($aggregates->isEmpty()) return ['entreprise'=>$entreprise,'comptes'=>[],'total_general_debit'=>0,'total_general_credit'=>0,'total_general_solde'=>0,'filters'=>$filters,'dateGeneration'=>now()->format('d/m/Y H:i:s')];

        $ids        = $aggregates->keys()->toArray();
        $accounts   = NewAccount::select('id','code','intitule')->whereIn('id',$ids)->orderByRaw('LENGTH(code) ASC, code ASC')->get();
        $ecritures  = (clone $base)->select(['id','old_account_id','new_account_id','date_ecriture','journal_code','piece','libelle','debit','credit'])->with('oldAccount:id,code,intitule')->whereIn('new_account_id',$ids)->orderBy('new_account_id')->orderBy('date_ecriture')->get()->groupBy('new_account_id');

        $comptes=[]; $td=0; $tc=0;
        foreach ($accounts as $a) {
            $agg=$aggregates->get($a->id); $d=(float)$agg->total_debit; $c=(float)$agg->total_credit; $s=$d-$c;
            $comptes[]=['code'=>$a->code,'intitule'=>$a->intitule,'ecritures'=>$ecritures->get($a->id,collect()),'total_debit'=>$d,'total_credit'=>$c,'solde'=>$s,'solde_absolu'=>abs($s),'type_solde'=>$s>0?'Débiteur':($s<0?'Créditeur':'Nul')];
            $td+=$d; $tc+=$c;
        }

        return ['entreprise'=>$entreprise,'comptes'=>$comptes,'total_general_debit'=>$td,'total_general_credit'=>$tc,'total_general_solde'=>abs($td-$tc),'filters'=>$filters,'dateGeneration'=>now()->format('d/m/Y H:i:s')];
    }

    private function resolveEntrepriseAndExo(array $filters): array
    {
        if (empty($filters['entreprise_id'])) throw new \RuntimeException('ID entreprise manquant.');
        $entreprise = Entreprise::find($filters['entreprise_id']);
        if (!$entreprise) throw new \RuntimeException('Entreprise non trouvée.');
        $exo = Exercice::where('statut',1)->first();
        if (!$exo) throw new \RuntimeException('Aucun exercice actif trouvé.');
        return [$entreprise, $exo];
    }

    private function applyCommonFilters($query, array $f): void
    {
        if (!empty($f['dateDebut']) && !empty($f['dateFin'])) $query->whereBetween('date_ecriture',[$f['dateDebut'],$f['dateFin']]);
        if (!empty($f['journalCode'])) $query->where('journal_code',$f['journalCode']);
        if (!empty($f['accountType']) && !empty($f['accountId'])) $query->where($f['accountType']==='old'?'old_account_id':'new_account_id',$f['accountId']);
        if (!empty($f['lettre'])) { $f['lettre']==='non'?$query->whereNull('lettre'):$query->where('lettre',$f['lettre']); }
        if (!empty($f['search'])) { $s=$f['search']; $query->where(fn($q)=>$q->where('libelle','like',"%$s%")->orWhere('piece','like',"%$s%")->orWhereHas('oldAccount',fn($sub)=>$sub->where('code','like',"%$s%")->orWhere('intitule','like',"%$s%"))); }
        if (!empty($f['sourceFilter']) && $f['sourceFilter']!=='all') $query->where('source',$f['sourceFilter']);
        if (!empty($f['mappingFilter']) && $f['mappingFilter']!=='all') { $f['mappingFilter']==='mapped'?$query->whereNotNull('new_account_id'):$query->whereNull('new_account_id'); }
    }
}
