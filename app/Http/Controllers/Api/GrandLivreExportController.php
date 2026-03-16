<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use App\Exports\GrandLivreExport;
use App\Models\GrandLivre;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class GrandLivreExportController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/grand-livre/template",
     *     tags={"Grand Livre"},
     *     summary="Télécharger le template CSV pour l'import",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Fichier CSV",
     *         @OA\MediaType(mediaType="text/csv")
     *     )
     * )
     */
    public function template()
    {
        try {
            $path = 'templates/template-grand-livre.csv';
            Storage::disk('public')->put($path, implode(';', ['Date','Piece','Journal','Compte','Libelle','Debit','Credit'])."\n");
            return response()->download(storage_path('app/public/'.$path));
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/grand-livre/export/{format}",
     *     tags={"Grand Livre"},
     *     summary="Exporter le grand livre en Excel ou PDF",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="format", in="path", required=true, @OA\Schema(type="string", enum={"excel","pdf"})),
     *     @OA\Parameter(name="filters[dateDebut]",   in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="filters[dateFin]",     in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="filters[journalCode]", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="filters[exercice]",    in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="filters[accountType]", in="query", @OA\Schema(type="string", enum={"old","new"})),
     *     @OA\Parameter(name="filters[accountId]",   in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier Excel ou PDF"),
     *     @OA\Response(response=400, description="Format invalide", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function export(Request $request, string $format)
    {
        try {
            $entreprise = Auth::user()->entreprise;
            $filters    = $request->query('filters', []);

            $query = GrandLivre::with(['oldAccount','newAccount'])->forEntreprise($entreprise->id)->valides();

            if (!empty($filters['dateDebut']) && !empty($filters['dateFin'])) $query->forPeriode($filters['dateDebut'], $filters['dateFin']);
            if (!empty($filters['exercice']))    $query->forExercice($filters['exercice']);
            if (!empty($filters['journalCode'])) $query->where('journal_code', $filters['journalCode']);
            if (!empty($filters['accountType']) && !empty($filters['accountId'])) $query->forAccount($filters['accountType'], $filters['accountId']);

            $ecritures = $query->orderBy('date_ecriture')->get();

            if ($format === 'excel') {
                return Excel::download(new GrandLivreExport($ecritures), 'grand-livre-'.date('Y-m-d').'.xlsx');
            }

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('exports.grand-livre-pdf', [
                    'ecritures' => $ecritures, 'entreprise' => $entreprise, 'filters' => $filters,
                    'totals'    => ['debit'=>$ecritures->sum('debit'),'credit'=>$ecritures->sum('credit'),'solde'=>$ecritures->sum('debit')-$ecritures->sum('credit')],
                ]);
                return $pdf->download('grand-livre-'.date('Y-m-d').'.pdf');
            }

            return $this->error('Format non supporté. Utilisez "excel" ou "pdf".');
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
