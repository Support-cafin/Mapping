<?php

namespace App\Policies;

use App\Models\GrandLivre;
use App\Models\User;

class GrandLivrePolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GrandLivre $grandLivre = null): bool
    {
        // Si l'utilisateur est admin
        if ($user->is_admin) {
            // Si on vérifie un modèle spécifique
            if ($grandLivre) {
                return $user->entreprise_id === $grandLivre->entreprise_id;
            }
            // Si on vérifie la capacité générale
            return true;
        }
        
        return false;
    }
    
    /**
     * Determine whether the user can delete multiple models.
     */
    public function deleteMultiple(User $user): bool
    {
        return $user->is_admin;
    }
    
    /**
     * Determine whether the user can delete all models.
     */
    public function deleteAll(User $user): bool
    {
        return $user->is_admin;
    }
}