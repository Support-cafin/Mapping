<?php

namespace App\Livewire\Parametre;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Exercice;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ExerciceManager extends Component
{
    use WithPagination;

    // Propriétés pour la liste
    public $search = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Propriétés pour le formulaire
    public $exercice_id;
    public $libelle;
    public $date_debut;
    public $date_fin;
    public $statut = 0;
    
    // Propriétés pour l'interface
    public $showModal = false;
    public $showDeleteModal = false;
    public $isEditing = false;
    public $confirmingExerciceDeletion = false;
    
    // Règles de validation
    protected function rules()
    {
        return [
            'libelle' => 'required|string|max:100',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'statut' => 'boolean',
        ];
    }

    protected $messages = [
        'libelle.required' => 'Le libellé est obligatoire',
        'libelle.max' => 'Le libellé ne doit pas dépasser 100 caractères',
        'date_debut.required' => 'La date de début est obligatoire',
        'date_debut.date' => 'La date de début n\'est pas valide',
        'date_fin.required' => 'La date de fin est obligatoire',
        'date_fin.date' => 'La date de fin n\'est pas valide',
        'date_fin.after' => 'La date de fin doit être postérieure à la date de début',
    ];

    // Initialisation
    public function mount()
    {
        $this->resetFilters();
    }

    // Réinitialiser les filtres
    public function resetFilters()
    {
        $this->search = '';
        $this->perPage = 10;
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        $this->resetPage();
    }

    // Réinitialiser le formulaire
    public function resetForm()
    {
        $this->reset([
            'exercice_id', 
            'libelle', 
            'date_debut', 
            'date_fin', 
            'statut',
            'isEditing'
        ]);
        $this->statut = 0;
        $this->resetValidation();
    }

    // Ouvrir le modal pour créer
    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
        $this->isEditing = false;
    }

    // Ouvrir le modal pour éditer
    public function edit($id)
    {
        $this->resetForm();
        
        $exercice = Exercice::findOrFail($id);
        
        $this->exercice_id = $exercice->id;
        $this->libelle = $exercice->libelle;
        $this->date_debut = $exercice->date_debut->format('Y-m-d');
        $this->date_fin = $exercice->date_fin->format('Y-m-d');
        $this->statut = $exercice->statut;
        
        $this->showModal = true;
        $this->isEditing = true;
    }

    // Sauvegarder l'exercice
    public function save()
    {
        $this->validate();

        try {
            // Vérifier si on essaie d'activer un exercice
            if ($this->statut == Exercice::STATUT_ACTIF) {
                // Vérifier s'il y a déjà un exercice actif
                if (Exercice::autreExerciceActif($this->exercice_id)) {
                    $this->addError('statut', 'Un exercice est déjà actif. Veuillez fermer l\'exercice actif avant d\'en ouvrir un nouveau.');
                    return;
                }
            }

            $data = [
                'libelle' => $this->libelle,
                'date_debut' => $this->date_debut,
                'date_fin' => $this->date_fin,
                'statut' => $this->statut,
            ];

            if ($this->exercice_id) {
                // Update
                $exercice = Exercice::find($this->exercice_id);
                $exercice->update($data);
                $message = 'Exercice modifié avec succès';
            } else {
                // Create
                Exercice::create($data);
                $message = 'Exercice créé avec succès';
            }

            $this->showModal = false;
            $this->resetForm();
            $this->dispatch('toast', type: 'success', message: $message);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Erreur: ' . $e->getMessage());
        }
    }

    // Confirmer la suppression
    public function confirmDelete($id)
    {
        $exercice = Exercice::find($id);
        
        // Vérifier si c'est un exercice actif
        if ($exercice && $exercice->estActif()) {
            $this->dispatch('toast', type: 'error', message: 'Impossible de supprimer un exercice actif');
            return;
        }
        
        $this->confirmingExerciceDeletion = $id;
    }

    // Supprimer l'exercice
    public function delete($id)
    {
        try {
            $exercice = Exercice::find($id);
            
            if (!$exercice) {
                $this->dispatch('toast', type: 'error', message: 'Exercice non trouvé');
                return;
            }

            // Empêcher la suppression d'un exercice actif
            if ($exercice->estActif()) {
                $this->dispatch('toast', type: 'error', message: 'Impossible de supprimer un exercice actif');
                return;
            }

            $exercice->delete();
            
            $this->confirmingExerciceDeletion = false;
            $this->dispatch('toast', type: 'success', message: 'Exercice supprimé avec succès');
            
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Erreur: ' . $e->getMessage());
        }
    }

    // Changer le statut
    public function toggleStatut($id)
    {
        try {
            $exercice = Exercice::find($id);
            
            if (!$exercice) {
                $this->dispatch('toast', type: 'error', message: 'Exercice non trouvé');
                return;
            }

            $nouveauStatut = !$exercice->statut;
            
            // Si on veut activer, vérifier qu'aucun autre exercice n'est actif
            if ($nouveauStatut == Exercice::STATUT_ACTIF) {
                if (Exercice::autreExerciceActif($id)) {
                    $this->dispatch('toast', type: 'error', message: 'Un exercice est déjà actif');
                    return;
                }
            }

            $exercice->update(['statut' => $nouveauStatut]);
            
            $message = $nouveauStatut ? 'Exercice activé avec succès' : 'Exercice fermé avec succès';
            $this->dispatch('toast', type: 'success', message: $message);
            
        } catch (\Exception $e) {
            $this->dispatch('toast', type: 'error', message: 'Erreur: ' . $e->getMessage());
        }
    }

    // Trier les colonnes
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    // Reset page quand on cherche
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Obtenir les exercices
    public function getExercicesProperty()
    {
        return Exercice::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('libelle', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('livewire.parametre.exercice-manager', [
            'exercices' => $this->exercices,
            'totalExercices' => Exercice::count(),
            'actifsCount' => Exercice::actif()->count(),
            'fermesCount' => Exercice::ferme()->count(),
        ])->layout('layouts.app');
    }
}