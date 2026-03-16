<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class GrandLivreGeneralExportController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/grand-livre/general/export/pdf",
     *     tags={"Grand Livre"},
     *     summary="Export PDF grand livre général (regroupé par compte SYCEBNL)",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="entreprise_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dateDebut",     in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="dateFin",       in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="exercice",      in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="journalCode",   in="query", @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Fichier PDF", @OA\MediaType(mediaType="application/pdf")),
     *     @OA\Response(response=422, description="Validation",  @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function pdf(Request $request)
    {
        try {
            $validated = $request->validate([
                'dateDebut'     => 'nullable|date',
                'dateFin'       => 'nullable|date|after_or_equal:dateDebut',
                'exercice'      => 'nullable|integer',
                'journalCode'   => 'nullable|string',
                'lettre'        => 'nullable|string',
                'search'        => 'nullable|string',
                'entreprise_id' => 'required|integer|exists:entreprises,id',
            ]);

            return (new GrandLivreExportPdfController())->exportPdfGeneral(new Request($validated));
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/grand-livre/general/export/excel",
     *     tags={"Grand Livre"},
     *     summary="Export Excel grand livre général",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="entreprise_id", in="query", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="dateDebut",     in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="dateFin",       in="query", @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="exercice",      in="query", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Fichier Excel",
     *         @OA\MediaType(mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")
     *     ),
     *     @OA\Response(response=422, description="Validation", @OA\JsonContent(ref="#/components/schemas/ApiError"))
     * )
     */
    public function excel(Request $request)
    {
        try {
            $validated = $request->validate([
                'dateDebut'     => 'nullable|date',
                'dateFin'       => 'nullable|date|after_or_equal:dateDebut',
                'exercice'      => 'nullable|integer',
                'journalCode'   => 'nullable|string',
                'lettre'        => 'nullable|string',
                'search'        => 'nullable|string',
                'entreprise_id' => 'required|integer|exists:entreprises,id',
            ]);

            return Excel::download(
                new \App\Exports\GrandLivreGeneralExport($validated),
                'grand_livre_general_'.now()->format('Y-m-d_H-i').'.xlsx'
            );
        } catch (ValidationException $e) {
            return $this->validationError($e->errors());
        } catch (\Throwable $e) {
            return $this->serverError($e->getMessage());
        }
    }
}
