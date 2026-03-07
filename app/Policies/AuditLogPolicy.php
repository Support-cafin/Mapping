<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AuditLog;

class AuditLogPolicy
{
    /**
     * Vérifier si l'utilisateur peut voir le log
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        return $user->entreprise_id === $auditLog->entreprise_id;
    }
}