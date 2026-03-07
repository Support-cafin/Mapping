<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Exports\BalanceExport;
use Maatwebsite\Excel\Facades\Excel;

class BalanceController extends Controller
{
    /**
     * Rediriger vers la route Livewire
     */
    public function index(Request $request)
    {
        // Livewire gère sa propre logique, le contrôleur sert juste de route
        // Vous pouvez passer des paramètres si besoin
        return view('balance.index'); // Vue qui contient le composant Livewire
    }
    
    /**
     * Export des balances
     */
    public function export(Request $request)
    {
        $entreprise = Auth::user()->entreprise;
        $type = $request->get('type', 'old');
        $dateDebut = $request->get('dateDebut');
        $dateFin = $request->get('dateFin');
        $exercice = $request->get('exercice');
        $format = $request->get('format', 'excel');
        
        $filename = 'balance-' . $type . '-' . ($dateDebut ?: 'all') . '-' . ($dateFin ?: 'all') . '-' . now()->format('Y-m-d');
        
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\BalancesExport($entreprise->id, $type, $dateDebut, $dateFin, $exercice),
            $filename . '.xlsx'
        );
    }
    
    public function exportExcel(Request $request)
    {
        $entreprise = auth()->user()->entreprise;
        $dateDebut = $request->get('dateDebut', '2024-01-01');
        $dateFin = $request->get('dateFin', '2024-12-31');
        $exercice = $request->get('exercice');
        $search = $request->get('search');
        
        $filename = 'balance_' . $entreprise->id . '_' . date('Y-m-d') . '.xlsx';
        
        return Excel::download(
            new BalanceExport($entreprise, $dateDebut, $dateFin, $exercice, $search),
            $filename
        );
    }
}