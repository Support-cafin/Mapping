<?php

namespace App\Exports;

use App\Models\Donateur;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DonateursExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Donateur::with('compte')->get();
    }

    public function headings(): array
    {
        return [
            'N° Enregistrement',
            'Date',
            'Donateur',
            'Nom Prénoms',
            'NIF',
            'Adresse',
            'Email',
            'Montant',
            'Devise',
            'Mode',
            'Statut',
            'Compte Comptable',
            'Date Création'
        ];
    }

    public function map($donateur): array
    {
        $emails = is_array($donateur->email) ? implode(', ', $donateur->email) : $donateur->email;
        
        return [
            $donateur->numero_enregistrement,
            $donateur->date->format('d/m/Y'),
            $donateur->denomination,
            $donateur->nom_prenoms,
            $donateur->numero_identification_fiscal,
            $donateur->adresse_siege_social,
            $emails,
            $donateur->montant_don,
            $donateur->devise,
            ucfirst($donateur->mode_liberation),
            ucfirst($donateur->statut),
            $donateur->compte ? $donateur->compte->libelle : '',
            $donateur->created_at->format('d/m/Y H:i')
        ];
    }
}