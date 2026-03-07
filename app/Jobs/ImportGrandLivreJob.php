<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Entreprise;
use App\Models\User;
use App\Services\GrandLivreImportService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ImportGrandLivreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 1200; // 20 minutes
    
    protected $entreprise;
    protected $filePath;
    protected $userId;
    protected $exercice;
    protected $options;
    
    public function __construct(Entreprise $entreprise, string $filePath, int $userId, int $exercice = null, array $options = [])
    {
        $this->entreprise = $entreprise;
        $this->filePath = $filePath;
        $this->userId = $userId;
        $this->exercice = $exercice ?? date('Y');
        $this->options = $options;
    }
    
    public function handle(): void
    {
        $file = new UploadedFile(
            Storage::path($this->filePath),
            basename($this->filePath),
            null,
            null,
            true
        );
        
        $service = new GrandLivreImportService($this->entreprise, $this->exercice);
        $result = $service->import($file, $this->options);
        
        // Notifier l'utilisateur
        $user = User::find($this->userId);
        if ($user) {
            // Vous pouvez envoyer une notification, un email, ou stocker le résultat
            session()->flash('import_result', $result);
        }
        
        // Nettoyer le fichier temporaire
        Storage::delete($this->filePath);
    }
    
    public function failed(\Throwable $exception): void
    {
        \Log::error('Import Grand Livre failed: ' . $exception->getMessage());
        Storage::delete($this->filePath);
    }
}