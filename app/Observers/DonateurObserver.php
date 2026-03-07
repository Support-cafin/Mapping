<?php

namespace App\Observers;

use App\Models\Donateur;
use App\Models\AccountMapping;
use App\Models\GrandLivre;

class DonateurObserver
{
    public function updated(Donateur $donateur)
    {
        // Quand un donateur est marqué comme "comptabilisé"
        if ($donateur->statut === 'comptabilisé' && $donateur->compte_id) {
            $this->createEcritureComptable($donateur);
        }
    }

    private function createEcritureComptable(Donateur $donateur)
    {
        // Créer l'écriture comptable
        $ecriture = AccountMapping::create([
            'date' => $donateur->date,
            'libelle' => "Don de {$donateur->denomination}",
            'reference' => $donateur->numero_enregistrement,
            'journal_id' => 1, // Journal des dons
            'created_by' => auth()->id(),
        ]);

        // Débit: Compte de dons
        $ecriture->lignes()->create([
            'compte_id' => $donateur->compte_id,
            'debit' => $donateur->montant_don,
            'credit' => 0,
        ]);

        // Crédit: Compte de trésorerie (selon le mode de libération)
        $compteTresorerie = $this->getCompteTresorerie($donateur->mode_liberation);
        $ecriture->lignes()->create([
            'compte_id' => $compteTresorerie,
            'debit' => 0,
            'credit' => $donateur->montant_don,
        ]);

        // Mettre à jour le grand livre
        GrandLivre::updateFromEcriture($ecriture);
    }

    private function getCompteTresorerie($mode)
    {
        // Retourne le compte de trésorerie approprié selon le mode
        $comptes = [
            'espèces' => 530, // Caisse
            'chèque' => 512,  // Banque
            'virement' => 512, // Banque
            'nature' => 31,   // Stocks
        ];

        return $comptes[$mode] ?? 512;
    }
}