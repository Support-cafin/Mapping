<?php

namespace App\Http\Controllers;

use App\Exports\BalanceExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class BalanceExportController extends Controller
{
    public function export(Request $request)
    {
        $request->validate([
            'dateDebut' => 'required|date',
            'dateFin' => 'required|date|after_or_equal:dateDebut',
        ]);
        
        $entreprise = auth()->user()->entreprise;
        $balanceType = $request->get('balanceType', '4colonnes');
        
        $filename = 'balance_' . $balanceType . '_' . 
                    str_replace('-', '_', $request->dateDebut) . '_' . 
                    str_replace('-', '_', $request->dateFin) . '.xlsx';
        
        return Excel::download(
            new BalanceExport(
                $entreprise,
                $request->dateDebut,
                $request->dateFin,
                $request->exercice,
                $request->search,
                $balanceType
            ),
            $filename
        );
    }
}