// App\Jobs\SyncGrandLivreMappings.php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\GrandLivre;
use App\Models\AccountMapping;

class SyncGrandLivreMappings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $entrepriseId;
    protected $oldAccountId;

    public function __construct($entrepriseId, $oldAccountId = null)
    {
        $this->entrepriseId = $entrepriseId;
        $this->oldAccountId = $oldAccountId;
    }

    public function handle()
    {
        if ($this->oldAccountId) {
            // Mettre à jour un compte spécifique
            $this->syncSingleAccount();
        } else {
            // Mettre à jour tous les comptes
            $this->syncAllAccounts();
        }
    }
    
    private function syncSingleAccount()
    {
        // Trouver le mapping actuel
        $mapping = AccountMapping::where('old_account_id', $this->oldAccountId)
            ->where('entreprise_id', $this->entrepriseId)
            ->first();
        
        // Mettre à jour les écritures
        if ($mapping) {
            GrandLivre::where('old_account_id', $this->oldAccountId)
                ->where('entreprise_id', $this->entrepriseId)
                ->update(['new_account_id' => $mapping->new_account_id]);
        } else {
            GrandLivre::where('old_account_id', $this->oldAccountId)
                ->where('entreprise_id', $this->entrepriseId)
                ->update(['new_account_id' => null]);
        }
    }
    
    private function syncAllAccounts()
    {
        // Récupérer tous les mappings
        $mappings = AccountMapping::where('entreprise_id', $this->entrepriseId)
            ->get()
            ->keyBy('old_account_id');
        
        // Mettre à jour toutes les écritures
        $oldAccountIds = $mappings->keys()->toArray();
        
        // Réinitialiser tous les comptes non mappés
        GrandLivre::where('entreprise_id', $this->entrepriseId)
            ->whereNotIn('old_account_id', $oldAccountIds)
            ->update(['new_account_id' => null]);
            
        // Appliquer les mappings
        foreach ($mappings as $oldAccountId => $mapping) {
            GrandLivre::where('old_account_id', $oldAccountId)
                ->where('entreprise_id', $this->entrepriseId)
                ->update(['new_account_id' => $mapping->new_account_id]);
        }
    }
}