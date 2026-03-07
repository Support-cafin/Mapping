<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;

class CleanupAuditLogs extends Command
{
    protected $signature = 'audit:cleanup {--days=90 : Keep logs for this many days}';
    protected $description = 'Cleanup old audit logs';

    public function handle()
    {
        $days = $this->option('days');
        $cutoffDate = now()->subDays($days);

        $deleted = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        $this->info("Deleted {$deleted} audit logs older than {$days} days.");
        
        return Command::SUCCESS;
    }
}