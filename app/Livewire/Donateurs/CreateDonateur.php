<?php

namespace App\Livewire\Donateurs;

use App\Models\Donateur;
use App\Models\AccountMapping;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CreateDonateur extends Component
{
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

    protected $rules = [
        'numero_enregistrement' => 'required|unique:donateurs,numero_enregistrement,NULL,id,entreprise_id,' . Auth::user()->entreprise_id,
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
    ];

    public function mount()
    {
        $this->entreprise_id = Auth::user()->entreprise_id;
        
        if (!$this->entreprise_id) {
            session()->flash('error', 'Vous devez être associé à une entreprise pour créer un donateur.');
            return redirect()->route('donateurs.index');
        }

        $this->generateAutoNumber();
        $this->date = date('Y-m-d');
        $this->email = [''];
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
            'created_by' => Auth::id(),
        ];

        Donateur::create($data);
        session()->flash('success', 'Donateur créé avec succès.');

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
        
        return view('livewire.donateurs.create-donateur', [
            'comptes' => $comptes
        ])->layout('layouts.app');
    }
}