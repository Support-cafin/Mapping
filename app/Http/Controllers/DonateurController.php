<?php

namespace App\Http\Controllers;

use App\Models\Donateur;
use Barryvdh\DomPDF\Facade\Pdf;

class DonateurController extends Controller
{
    public function fichePdf($id)
    {
        $donateur = Donateur::with(['oldAccount', 'createur'])->findOrFail($id);
        
        $pdf = Pdf::loadView('pdf.donateur-fiche', compact('donateur'));
        
        return $pdf->download("donateur-{$donateur->numero_enregistrement}.pdf");
    }
}