<?php

namespace App\Livewire\Donateurs;

use App\Models\Donateur;
use App\Models\AccountMapping;
use App\Models\OldAccount;
use App\Models\NewAccount;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class FormulaireDonateur extends Component
{
    public $donateurId;
    public $numero_enregistrement;
    public $date;
    public $nom_prenoms;
    public $denomination;
    public $registre_commerce;
    public $numero_identification_fiscal;
    public $adresse_siege_social;
    public $email = [];
    public $montant_don;
    public $mode_liberation = 'virement';
    public $devise = 'XOF';
    public $signature_representant = false;
    public $notes;
    public $statut = 'enregistré';
    public $compte_id;
    public $entreprise_id;
    public $isEditMode = false;

    protected $rules = [
        'numero_enregistrement' => 'required',
        'date' => 'required|date',
        'denomination' => 'required|string|max:255',
        'nom_prenoms' => 'nullable|string|max:255',
        'registre_commerce' => 'nullable|string|max:100',
        'numero_identification_fiscal' => 'nullable|string|max:50',
        'adresse_siege_social' => 'nullable|string',
        'email' => 'nullable|array',
        'email.*' => 'nullable|email',
        'montant_don' => 'required|numeric|min:0',
        'mode_liberation' => 'required|in:espèces,chèque,virement,nature',
        'devise' => 'required|string|max:3',
        'signature_representant' => 'boolean',
        'notes' => 'nullable|string',
        'statut' => 'nullable|in:enregistré,validé,comptabilisé,annulé',
        'compte_id' => 'nullable|exists:old_accounts,id',
        'entreprise_id' => 'required|exists:entreprises,id',
    ];

    public function mount($donateur = null)
    {
        // Récupérer l'ID de l'entreprise de l'utilisateur connecté
        $this->entreprise_id = Auth::user()->entreprise_id;
        
        if (!$this->entreprise_id) {
            session()->flash('error', 'Vous devez être associé à une entreprise pour créer un donateur.');
            return redirect()->route('donateurs.index');
        }

        // Vérifier si $donateur est un ID (string) ou un objet
        if ($donateur) {
            $this->isEditMode = true;
            
            // Si c'est un ID (string), charger le donateur
            if (is_numeric($donateur)) {
                $this->donateurId = (int)$donateur;
                $donateurModel = Donateur::where('id', $this->donateurId)
                    ->where('entreprise_id', $this->entreprise_id)
                    ->first();
                    
                if (!$donateurModel) {
                    session()->flash('error', 'Donateur non trouvé ou vous n\'avez pas accès.');
                    return redirect()->route('donateurs.index');
                }
                
                $this->loadDonateurData($donateurModel);
            } 
            // Si c'est déjà un modèle Donateur (peu probable mais pour sécurité)
            elseif ($donateur instanceof Donateur) {
                $this->donateurId = $donateur->id;
                $this->loadDonateurData($donateur);
            }
            
            // Modifier la règle d'unicité pour l'édition
            $this->rules['numero_enregistrement'] = 'required|unique:donateurs,numero_enregistrement,' . $this->donateurId;
        } else {
            $this->isEditMode = false;
            $this->generateAutoNumber();
            $this->date = date('Y-m-d');
            $this->email = [''];
        }
    }

    private function loadDonateurData(Donateur $donateur)
    {
        $this->numero_enregistrement = $donateur->numero_enregistrement;
        $this->date = $donateur->date->format('Y-m-d');
        $this->nom_prenoms = $donateur->nom_prenoms;
        $this->denomination = $donateur->denomination;
        $this->registre_commerce = $donateur->registre_commerce;
        $this->numero_identification_fiscal = $donateur->numero_identification_fiscal;
        $this->adresse_siege_social = $donateur->adresse_siege_social;
        
        // Gestion des emails
        $this->email = $this->parseEmails($donateur->email);
        
        $this->montant_don = (float)$donateur->montant_don;
        $this->mode_liberation = $donateur->mode_liberation;
        $this->devise = $donateur->devise;
        $this->signature_representant = (bool)$donateur->signature_representant;
        $this->notes = $donateur->notes;
        $this->statut = $donateur->statut;
        $this->compte_id = $donateur->compte_id;
    }

    private function generateAutoNumber()
    {
        $lastDonateur = Donateur::where('entreprise_id', $this->entreprise_id)
            ->whereYear('created_at', date('Y'))
            ->latest()
            ->first();
            
        $lastNumber = $lastDonateur ? intval(substr($lastDonateur->numero_enregistrement, -4)) : 0;
        $this->numero_enregistrement = 'DON-' . date('Y') . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    private function parseEmails($emailData)
    {
        if (empty($emailData)) {
            return [''];
        }
        
        if (is_array($emailData)) {
            return !empty($emailData) ? $emailData : [''];
        }
        
        $decoded = json_decode($emailData, true);
        if (is_array($decoded)) {
            return !empty($decoded) ? $decoded : [''];
        }
        
        if (is_string($emailData) && !empty(trim($emailData))) {
            return [trim($emailData)];
        }
        
        return [''];
    }

    public function addEmail()
    {
        $this->email[] = '';
    }

    public function removeEmail($index)
    {
        if (count($this->email) > 1) {
            unset($this->email[$index]);
            $this->email = array_values($this->email);
        }
    }

    public function save()
    {
        $this->validate();
       
        // Filtrer les emails vides
        $filteredEmails = array_filter($this->email, function($email) {
            return !empty(trim($email));
        });
        
        $data = [
            'numero_enregistrement' => $this->numero_enregistrement,
            'date' => $this->date,
            'nom_prenoms' => $this->nom_prenoms,
            'denomination' => $this->denomination,
            'registre_commerce' => $this->registre_commerce,
            'numero_identification_fiscal' => $this->numero_identification_fiscal,
            'adresse_siege_social' => $this->adresse_siege_social,
            'email' => !empty($filteredEmails) ? json_encode($filteredEmails) : null,
            'montant_don' => $this->montant_don,
            'mode_liberation' => $this->mode_liberation,
            'devise' => $this->devise,
            'signature_representant' => $this->signature_representant,
            'notes' => $this->notes,
            'statut' => $this->statut,
            'compte_id' => $this->compte_id,
            'entreprise_id' => $this->entreprise_id,
        ];

        if ($this->isEditMode) {
            $data['updated_by'] = Auth::id();
            Donateur::where('id', $this->donateurId)
                ->where('entreprise_id', $this->entreprise_id)
                ->update($data);
            session()->flash('success', 'Donateur mis à jour avec succès.');
        } else {
            $data['created_by'] = Auth::id();
            Donateur::create($data);
            session()->flash('success', 'Donateur créé avec succès.');
        }

        return redirect()->route('donateurs.index');
    }

    public function render()
    {  
        $comptes = AccountMapping::with('newAccount')
            ->where('type', 'don')
            ->where('entreprise_id', $this->entreprise_id)
             ->select('new_account_id')
            ->groupBy('new_account_id')
            ->get();
        
        return view('livewire.donateurs.formulaire-donateur', [
            'comptes' => $comptes,
            'isEditMode' => $this->isEditMode
        ])->layout('layouts.app');
    }
}