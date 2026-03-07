<?php

namespace App\Traits;

use App\Models\GrandLivre;
use App\Models\AccountMapping;

trait AutoUpdatesGrandLivre
{
    /**
     * Met à jour les écritures du Grand Livre pour un ancien compte donné
     */
    public function updateGrandLivreForOldAccount($oldAccountId, $entrepriseId, $newAccountId = null)
    {
        $query = GrandLivre::where('old_account_id', $oldAccountId)
            ->where('entreprise_id', $entrepriseId);
        
        if ($newAccountId) {
            // Pour les ajouts/modifications de mapping
            $query->whereNull('new_account_id')
                  ->update(['new_account_id' => $newAccountId]);
        } else {
            // Pour les suppressions de mapping
            $query->whereNotNull('new_account_id')
                  ->update(['new_account_id' => null]);
        }
        
        return $query->count(); // Retourne le nombre d'écritures mises à jour
    }
    
    /**
     * Met à jour toutes les écritures non mappées avec les mappings existants
     */
    public function syncAllGrandLivreMappings($entrepriseId)
    {
        $mappings = AccountMapping::where('entreprise_id', $entrepriseId)->get();
        $updatedCount = 0;
        
        foreach ($mappings as $mapping) {
            $count = GrandLivre::where('old_account_id', $mapping->old_account_id)
                ->where('entreprise_id', $entrepriseId)
                ->whereNull('new_account_id')
                ->update(['new_account_id' => $mapping->new_account_id]);
            
            $updatedCount += $count;
        }
        
        return $updatedCount;
    }
}