<?php

namespace App\Http\Controllers;

use App\Models\Donateur;
use App\Models\Entreprise;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DonateurPdfController extends Controller
{
    /*public function export(Request $request)
    {
        // Récupérer l'ID de l'entreprise de l'utilisateur connecté
        $entreprise_id = Auth::user()->entreprise_id;
        
        if (!$entreprise_id) {
            abort(403, 'Vous devez être associé à une entreprise pour exporter les donateurs.');
        }
        
        // Récupérer les informations de l'entreprise
        $entreprise = Entreprise::find($entreprise_id);
        
        if (!$entreprise) {
            abort(404, 'Entreprise non trouvée.');
        }
        
        $query = Donateur::where('entreprise_id', $entreprise_id);
        
        // Appliquer les mêmes filtres que dans la liste
        if ($request->has('search') && !empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('denomination', 'like', '%' . $request->search . '%')
                  ->orWhere('nom_prenoms', 'like', '%' . $request->search . '%')
                  ->orWhere('numero_enregistrement', 'like', '%' . $request->search . '%')
                  ->orWhere('registre_commerce', 'like', '%' . $request->search . '%')
                  ->orWhere('numero_identification_fiscal', 'like', '%' . $request->search . '%');
            });
        }
        
        if ($request->has('selectedYear') && !empty($request->selectedYear)) {
            $query->whereYear('date', $request->selectedYear);
        }
        
        if ($request->has('selectedMonth') && !empty($request->selectedMonth)) {
            $query->whereMonth('date', $request->selectedMonth);
        }
        
        if ($request->has('selectedStatut') && !empty($request->selectedStatut)) {
            $query->where('statut', $request->selectedStatut);
        }
        
        // Trier selon les mêmes critères 
        $sortField = $request->get('sortField', 'date');
        $sortDirection = $request->get('sortDirection', 'desc');
        $query->orderBy($sortField, $sortDirection);
        
        $donateurs = $query->get();
        
        // Diviser les donateurs en groupes de 2 pour l'affichage
        $donateursGrouped = $donateurs->chunk(2);
        
        $data = [
            'donateursGrouped' => $donateursGrouped,
            'totalDonateurs' => $donateurs->count(),
            'totalMontant' => $donateurs->sum('montant_don'),
            'filters' => [
                'search' => $request->search,
                'year' => $request->selectedYear,
                'month' => $request->selectedMonth,
                'statut' => $request->selectedStatut,
            ],
            'dateGeneration' => now()->format('d/m/Y H:i'),
            'entreprise' => $entreprise,
        ];
        
        $pdf = Pdf::loadView('pdf.donateurs-liste', $data)
                  ->setPaper('A4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'helvetica',
                      'isHtml5ParserEnabled' => true,
                      'isRemoteEnabled' => true,
                  ]);
        
        $filename = 'registre-donateurs-' . $entreprise->code . '-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }*/
    public function export(Request $request)
{
    // Récupérer l'ID de l'entreprise de l'utilisateur connecté
    $entreprise_id = Auth::user()->entreprise_id;
    
    if (!$entreprise_id) {
        abort(403, 'Vous devez être associé à une entreprise pour exporter les donateurs.');
    }
    
    // Récupérer les informations de l'entreprise
    $entreprise = Entreprise::find($entreprise_id);
    
    if (!$entreprise) {
        abort(404, 'Entreprise non trouvée.');
    }
    
    $query = Donateur::where('entreprise_id', $entreprise_id)->orderBy('date', 'asc');
    
    // Appliquer les mêmes filtres que dans la liste
    if ($request->has('search') && !empty($request->search)) {
        $query->where(function ($q) use ($request) {
            $q->where('denomination', 'like', '%' . $request->search . '%')
              ->orWhere('nom_prenoms', 'like', '%' . $request->search . '%')
              ->orWhere('numero_enregistrement', 'like', '%' . $request->search . '%')
              ->orWhere('registre_commerce', 'like', '%' . $request->search . '%')
              ->orWhere('numero_identification_fiscal', 'like', '%' . $request->search . '%');
        });
    }
    
    if ($request->has('selectedYear') && !empty($request->selectedYear)) {
        $query->whereYear('date', $request->selectedYear);
    }
    
    if ($request->has('selectedMonth') && !empty($request->selectedMonth)) {
        $query->whereMonth('date', $request->selectedMonth);
    }
    
    if ($request->has('selectedStatut') && !empty($request->selectedStatut)) {
        $query->where('statut', $request->selectedStatut);
    }
    
    // Trier selon les mêmes critères
    $sortField = $request->get('sortField', 'date');
    $sortDirection = $request->get('sortDirection', 'desc');
    $query->orderBy($sortField, $sortDirection);
    
    $donateurs = $query->get();
    
    // CORRECTION : Utiliser array_chunk au lieu de Collection::chunk() si nécessaire
    // Diviser les donateurs en groupes de 2 pour l'affichage
    $donateursArray = $donateurs->toArray();
    $donateursGrouped = array_chunk($donateursArray, 2);
    
    // OU utiliser la méthode chunk() avec ->all() pour convertir en array
    // $donateursGrouped = $donateurs->chunk(2)->all();
    
    $data = [
        'donateurs' => $donateurs, // Ajouter la collection complète pour debug
        'donateursGrouped' => $donateursGrouped,
        'totalDonateurs' => $donateurs->count(),
        'totalMontant' => $donateurs->sum('montant_don'),
        'filters' => [
            'search' => $request->search,
            'year' => $request->selectedYear,
            'month' => $request->selectedMonth,
            'statut' => $request->selectedStatut,
        ],
        'dateGeneration' => now()->format('d/m/Y H:i'),
        'entreprise' => $entreprise,
    ];
    
    $pdf = Pdf::loadView('pdf.donateurs-liste', $data)
              ->setPaper('A4', 'portrait')
              ->setOptions([
                  'defaultFont' => 'helvetica',
                  'isHtml5ParserEnabled' => true,
                  'isRemoteEnabled' => true,
              ]);
    
    $filename = 'registre-donateurs-' . $entreprise->code . '-' . now()->format('Y-m-d') . '.pdf';
    
    return $pdf->download($filename);
}
    
    // Dans DonateurPdfController
    public function ficheIndividuelle($id)
    {
        $entreprise_id = Auth::user()->entreprise_id;
        
        if (!$entreprise_id) {
            abort(403, 'Vous devez être associé à une entreprise.');
        }
        
        $entreprise = Entreprise::find($entreprise_id);
        
        $donateur = Donateur::with(['oldAccount', 'createur', 'modificateur'])
            ->where('id', $id)
            ->where('entreprise_id', $entreprise_id)
            ->firstOrFail();
        
        $data = [
            'donateur' => $donateur,
            'entreprise' => $entreprise,
            'dateGeneration' => now()->format('d/m/Y H:i'),
        ];
        
        $pdf = Pdf::loadView('pdf.donateur-fiche', $data)
                  ->setPaper('A4', 'portrait')
                  ->setOptions([
                      'defaultFont' => 'helvetica',
                      'isHtml5ParserEnabled' => true,
                  ]);
        
        $filename = 'fiche-donateur-' . $donateur->numero_enregistrement . '.pdf';
        
        return $pdf->download($filename);
    }
}