<?php

namespace App\Livewire\Import;

use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

class ImportMappingResults extends Component
{
    public $importedCount = 0;
    public $errors = [];
    public $allMessages = [];
    public $showResults = false;
    public $summary = [
        'total' => 0,
        'success' => 0,
        'errors' => 0,
        'timestamp' => null,
        'filename' => '',
        'duration' => 0
    ];

    protected $listeners = [
        'show-mapping-results' => 'loadResults',
        'mapping-import-completed' => 'handleImportCompleted'
    ];

    public function mount()
    {
        $this->loadFromSession();
    }

    public function loadResults($data)
    {
        $this->importedCount = $data['imported'] ?? 0;
        $this->errors = $data['errors'] ?? [];
        $this->allMessages = $data['all_messages'] ?? [];
        
        $this->summary = [
            'total' => count($this->allMessages),
            'success' => $this->importedCount,
            'errors' => count($this->errors),
            'timestamp' => now()->format('d/m/Y H:i:s'),
            'filename' => $data['filename'] ?? 'fichier inconnu',
            'duration' => $data['duration'] ?? 0
        ];
        
        $this->showResults = true;
        $this->saveToSession();
    }

    public function handleImportCompleted($data)
    {
        $this->loadResults($data);
    }

    private function loadFromSession()
    {
        if (session()->has('mapping_import_results')) {
            $data = session('mapping_import_results');
            $this->importedCount = $data['imported'] ?? 0;
            $this->errors = $data['errors'] ?? [];
            $this->allMessages = $data['all_messages'] ?? [];
            $this->summary = $data['summary'] ?? $this->summary;
        }
    }

    private function saveToSession()
    {
        session()->put('mapping_import_results', [
            'imported' => $this->importedCount,
            'errors' => $this->errors,
            'all_messages' => $this->allMessages,
            'summary' => $this->summary
        ]);
    }

    public function closeResults()
    {
        $this->showResults = false;
        $this->reset();
        session()->forget('mapping_import_results');
        $this->dispatch('mapping-results-closed');
    }

    public function downloadPdf()
    {
        try {
            $data = [
                'importedCount' => $this->importedCount,
                'errors' => $this->errors,
                'allMessages' => $this->allMessages,
                'summary' => $this->summary,
                'entreprise' => auth()->user()->entreprise->nom ?? 'N/A',
                'user' => auth()->user()->name ?? 'Utilisateur',
                'generated_at' => now()->format('d/m/Y H:i:s')
            ];

            $pdf = Pdf::loadView('pdf.mapping-import-results', $data);
            
            $filename = 'import_mappings_' . now()->format('Y-m-d_H-i-s') . '.pdf';
            
            return response()->streamDownload(
                fn () => print($pdf->output()),
                $filename
            );
            
        } catch (\Exception $e) {
            Log::error('Erreur génération PDF mapping', ['error' => $e->getMessage()]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erreur lors de la génération du PDF'
            ]);
        }
    }

    public function render()
    {
        return view('livewire.import.import-mapping-results');
    }
}