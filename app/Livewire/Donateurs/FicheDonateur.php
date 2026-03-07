<?php

namespace App\Livewire\Donateurs;

use App\Models\Donateur;
use App\Models\GrandLivre;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class FicheDonateur extends Component
{
    public $donateur;
    public $donateurId;
    public $showConfirmationModal = false;
    public $actionToConfirm;
    public $confirmationMessage;
    public $entreprise_id;
    public $nouveauStatut;

    public function mount($donateur = null)
    {
        // Récupérer l'ID de l'entreprise de l'utilisateur connecté
        $this->entreprise_id = Auth::user()->entreprise_id;
        
        if (!$this->entreprise_id) {
            abort(403, 'Vous devez être associé à une entreprise pour accéder aux donateurs.');
        }
        
        // Si $donateur est une chaîne (ID), utilisez-le directement
        if (is_numeric($donateur)) {
            $this->donateurId = $donateur;
        } 
        // Si c'est un modèle, prenez son ID
        elseif (is_object($donateur) && isset($donateur->id)) {
            $this->donateurId = $donateur->id;
        }
        // Sinon, essayez de trouver par le paramètre de route
        else {
            $this->donateurId = $donateur;
        }
        
        $this->loadDonateur();
    }

    public function loadDonateur()
    {
        // Charger le donateur avec vérification de l'entreprise
        $donateur = Donateur::with(['newAccount', 'createur', 'modificateur'])
            ->where('id', $this->donateurId)
            ->where('entreprise_id', $this->entreprise_id)
            ->first();
            
        if (!$donateur) {
            abort(404, 'Donateur non trouvé ou vous n\'avez pas accès à ce donateur.');
        }
        
        $this->donateur = $donateur;
    }

    public function changerStatut($nouveauStatut)
    {
        $messages = [
            'validé' => 'Êtes-vous sûr de vouloir valider ce don ?',
            'comptabilisé' => 'Êtes-vous sûr de vouloir comptabiliser ce don ? Cela créera une écriture comptable.',
            'annulé' => 'Êtes-vous sûr de vouloir annuler ce don ?',
            'enregistré' => 'Êtes-vous sûr de vouloir réactiver ce don ?',
        ];
    
        $this->confirmationMessage = $messages[$nouveauStatut] ?? 'Confirmez-vous cette action ?';
        $this->nouveauStatut = $nouveauStatut;
        $this->showConfirmationModal = true;
    }

    public function imprimerFiche()
    {
        // Vérifier que le donateur appartient à l'entreprise
        $donateur = Donateur::where('id', $this->donateurId)
            ->where('entreprise_id', $this->entreprise_id)
            ->first();
            
        if (!$donateur) {
            session()->flash('error', 'Donateur non trouvé ou vous n\'avez pas accès.');
            return;
        }
        
        return redirect()->route('donateurs.fiche.pdf', $this->donateurId);
    }

    public function getGrandLivreProperty()
    {
        if ($this->donateur && $this->donateur->statut === 'comptabilisé' && $this->donateur->compte_id) {
            return GrandLivre::where('new_account_id', $this->donateur->compte_id)
                ->where('entreprise_id', $this->entreprise_id)
                ->with('lignes.compte')
                ->first();
        }
        return null;
    }

    public function executeConfirmedAction()
    {
        if ($this->nouveauStatut) {
            // Vérifier à nouveau que le donateur appartient à l'entreprise
            $donateur = Donateur::where('id', $this->donateurId)
                ->where('entreprise_id', $this->entreprise_id)
                ->first();
                
            if (!$donateur) {
                session()->flash('error', 'Donateur non trouvé ou vous n\'avez pas accès.');
                $this->showConfirmationModal = false;
                return;
            }
            
            $donateur->update([
                'statut' => $this->nouveauStatut,
                'updated_by' => auth()->id()
            ]);
            
            $this->loadDonateur();
            session()->flash('message', "Statut changé en " . ucfirst($this->nouveauStatut));
            
            $this->nouveauStatut = null;
        }
        
        $this->showConfirmationModal = false;
        $this->confirmationMessage = '';
    }

    public function render()
    {
        // Vérifiez si le donateur existe avant de rendre la vue
        if (!$this->donateur) {
            return view('livewire.donateurs.not-found')
                ->layout('layouts.app');
        }
        
        return view('livewire.donateurs.fiche-donateur', [
            'ecriture' => $this->grandLivre
        ])->layout('layouts.app');
    }
}