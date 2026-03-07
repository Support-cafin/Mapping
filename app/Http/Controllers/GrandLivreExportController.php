<?php

namespace App\Http\Controllers;

use App\Exports\GrandLivreExport;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\GrandLivre;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class GrandLivreExportController extends Controller
{
    /**
     * 🔹 Téléchargement du template Excel pour l'import
     */
    public function template()
    {
        // Colonnes du fichier Excel attendu
        $headers = [
            'Date',            // Format : 31/12/2024
            'Piece',           // N° de pièce
            'Journal',         // Code journal (ex : XDIF)
            'Compte',          // Code ancien compte (ex : 810)
            'Libelle',         // Texte
            'Debit',           // Montant
            'Credit',          // Montant
        ];

        // Générer un fichier CSV propre
        $path = 'templates/template-grand-livre.csv';

        Storage::disk('public')->put($path, implode(';', $headers)."\n");

        return response()->download(storage_path('app/public/'.$path));
    }

    /**
     * 🔹 Export Excel ou PDF du grand-livre
     */
    public function export(Request $request, $format)
    {
        $entreprise = auth()->user()->entreprise;
        $filters = $request->query('filters', []);
        
        $query = GrandLivre::with(['oldAccount', 'newAccount'])
            ->forEntreprise($entreprise->id)
            ->valides();
        
        // Application des filtres
        if (!empty($filters)) {
            if (!empty($filters['dateDebut']) && !empty($filters['dateFin'])) {
                $query->forPeriode($filters['dateDebut'], $filters['dateFin']);
            }
            
            if (!empty($filters['exercice'])) {
                $query->forExercice($filters['exercice']);
            }
            
            if (!empty($filters['journalCode'])) {
                $query->where('journal_code', $filters['journalCode']);
            }
            
            if (!empty($filters['accountType']) && !empty($filters['accountId'])) {
                $query->forAccount($filters['accountType'], $filters['accountId']);
            }
        }
        
        $ecritures = $query->orderBy('date_ecriture')->get();
        
        if ($format === 'excel') {
            return Excel::download(
                new GrandLivreExport($ecritures), 
                'grand-livre-' . date('Y-m-d') . '.xlsx'
            );
        }
        
        if ($format === 'pdf') {
            $pdf = Pdf::loadView('exports.grand-livre-pdf', [
                'ecritures' => $ecritures,
                'entreprise' => $entreprise,
                'filters' => $filters,
                'totals' => [
                    'debit' => $ecritures->sum('debit'),
                    'credit' => $ecritures->sum('credit'),
                    'solde' => $ecritures->sum('debit') - $ecritures->sum('credit'),
                ],
            ]);
            
            return $pdf->download('grand-livre-' . date('Y-m-d') . '.pdf');
        }
        
        return back()->with('error', 'Format non supporté');
    }
}
