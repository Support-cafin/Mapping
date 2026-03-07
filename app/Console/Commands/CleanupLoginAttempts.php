<?php
// app/Console/Commands/CleanupLoginAttempts.php

namespace App\Console\Commands;

use App\Models\LoginAttempt;
use Illuminate\Console\Command;

class CleanupLoginAttempts extends Command
{
    protected $signature = 'login-attempts:cleanup {--days=30 : Supprimer les logs plus anciens que X jours}';
    protected $description = 'Nettoyer les logs de tentatives de connexion anciennes';

    public function handle()
    {
        $days = $this->option('days');
        $deleted = LoginAttempt::where('attempted_at', '<', now()->subDays($days))->delete();
        
        $this->info("{$deleted} logs de tentatives supprimés (plus anciens que {$days} jours).");
        
        // Nettoyer aussi le cache
        \Illuminate\Support\Facades\Cache::flush();
        $this->info('Cache nettoyé.');
        
        return 0;
    }
}