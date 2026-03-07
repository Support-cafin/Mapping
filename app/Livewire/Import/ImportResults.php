<?php

namespace App\Livewire\Import;

use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImportResults extends Component
{
    public $importType = '';
    public $importedCount = 0;
    public $errors = [];
    public $warnings = [];
    public $allMessages = [];
    public $showResults = false;
    public $summary = [
        'total' => 0,
        'success' => 0,
        'errors' => 0,
        'warnings' => 0,
        'timestamp' => null,
        'filename' => '',
        'duration' => 0
    ];

    protected $listeners = [
        'show-import-results' => 'loadResults',
        'import-completed' => 'handleImportCompleted'
    ];

    public function mount()
    {
        $this->loadFromSession();
    }

    public function loadResults($data)
    {
        $this->importType = $data['type'] ?? 'old';
        $this->importedCount = $data['imported'] ?? 0;
        $this->errors = $data['errors'] ?? [];
        $this->warnings = $data['warnings'] ?? [];
        $this->allMessages = $data['all_messages'] ?? [];
        
        $this->summary = [
            'total' => count($this->allMessages),
            'success' => $this->importedCount,
            'errors' => count($this->errors),
            'warnings' => count($this->warnings),
            'timestamp' => now()->format('d/m/Y H:i:s'),
            'filename' => $data['filename'] ?? 'fichier inconnu',
            'duration' => $data['duration'] ?? 0
        ];
        
        $this->showResults = true;
        
        // Sauvegarder en session pour persistance
        $this->saveToSession();
    }

    public function handleImportCompleted($data)
    {
        $this->loadResults($data);
    }

    private function loadFromSession()
    {
        if (session()->has('import_results')) {
            $data = session('import_results');
            $this->importType = $data['type'] ?? 'old';
            $this->importedCount = $data['imported'] ?? 0;
            $this->errors = $data['errors'] ?? [];
            $this->warnings = $data['warnings'] ?? [];
            $this->allMessages = $data['all_messages'] ?? [];
            $this->summary = $data['summary'] ?? $this->summary;
        }
    }

    private function saveToSession()
    {
        session()->put('import_results', [
            'type' => $this->importType,
            'imported' => $this->importedCount,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'all_messages' => $this->allMessages,
            'summary' => $this->summary
        ]);
    }

    public function closeResults()
    {
        $this->showResults = false;
        $this->reset();
        session()->forget('import_results');
        $this->dispatch('results-closed');
    }

    public function downloadPdf()
    {
        try {
            $data = [
                'importType' => $this->importType,
                'importedCount' => $this->importedCount,
                'errors' => $this->errors,
                'warnings' => $this->warnings,
                'allMessages' => $this->allMessages,
                'summary' => $this->summary,
                'entreprise' => auth()->user()->entreprise->nom ?? 'N/A',
                'user' => auth()->user()->name ?? 'Utilisateur',
                'generated_at' => now()->format('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('pdf.import-results', $data);
            
            $filename = 'import_' . $this->importType . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return response()->streamDownload(
                fn () => print($pdf->output()),
                $filename
            );
            
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur lors de la génération du PDF'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.import.import-results');
    }
}