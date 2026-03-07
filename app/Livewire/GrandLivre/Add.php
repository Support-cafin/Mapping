<?php

namespace App\Livewire\GrandLivre;

use Livewire\Component;
use App\Models\GrandLivre;
use App\Models\OldAccount;
use App\Models\AccountMapping;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class Add extends Component
{
    public $date_ecriture;
    public $journal_code; // ← MODIFIÉ : string au lieu de journal_id
    public $libelle;
    public $debit = 0;
    public $credit = 0;
    public $old_account_id;
    public $piece;
    
    // Pour afficher le mapping
    public $mapped_new_account = null;
    public $mapping_exists = false;
    public $mapping_details = null;

    // Liste des journaux courants (codes uniques)
    public $journaux_codes = [];

    public function mount()
    {
        $this->date_ecriture = date('Y-m-d');
        $this->loadJournauxCodes();
    }

    /**
     * Charge les codes journaux déjà utilisés dans le grand livre
     */
    private function loadJournauxCodes()
    {
        $this->journaux_codes = GrandLivre::forEntreprise(auth()->user()->entreprise_id)
            ->select('journal_code')
            ->distinct()
            ->whereNotNull('journal_code')
            ->orderBy('journal_code')
            ->pluck('journal_code')
            ->toArray();
    }

    public function updatedDebit()
    {
        if ($this->debit > 0) {
            $this->credit = 0;
        }
    }

    public function updatedCredit()
    {
        if ($this->credit > 0) {
            $this->debit = 0;
        }
    }

    public function updated($property)
    {
        // Vérifier si l'ancien compte est mappé lorsqu'il change
        if ($property === 'old_account_id') {
            $this->checkMapping();
        }
    }

    /**
     * Vérifie si le compte ancien sélectionné a un mapping
     */
    public function checkMapping()
    {
        if ($this->old_account_id) {
            $mapping = AccountMapping::where('old_account_id', $this->old_account_id)
                ->where('entreprise_id', auth()->user()->entreprise_id)
                ->with('newAccount')
                ->first();
            
            if ($mapping) {
                $this->mapped_new_account = $mapping->newAccount;
                $this->mapping_exists = true;
                $this->mapping_details = $mapping;
                
                Log::info('Mapping trouvé', [
                    'old_account_id' => $this->old_account_id,
                    'new_account_id' => $mapping->new_account_id,
                    'new_account_code' => $mapping->newAccount->code ?? 'N/A'
                ]);
            } else {
                $this->mapped_new_account = null;
                $this->mapping_exists = false;
                $this->mapping_details = null;
                
                Log::info('Aucun mapping trouvé', [
                    'old_account_id' => $this->old_account_id
                ]);
            }
        } else {
            $this->mapped_new_account = null;
            $this->mapping_exists = false;
            $this->mapping_details = null;
        }
    }

    public function save()
    {
        // Log des valeurs avant validation
        Log::info('Tentative de création écriture', [
            'debit' => $this->debit,
            'credit' => $this->credit,
            'journal_code' => $this->journal_code,
            'old_account_id' => $this->old_account_id
        ]);
        
        // Validation
        $this->validate([
            'date_ecriture' => 'required|date',
            'journal_code' => 'nullable|string|max:20', // ← MODIFIÉ : optionnel
            'libelle' => 'required|string|max:255',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'piece' => 'nullable|string|max:255',
            'old_account_id' => 'required|exists:old_accounts,id',
        ], [
            'old_account_id.required' => 'Veuillez choisir un compte.',
            'libelle.required' => 'Le libellé est obligatoire.',
        ]);

        // Conversion en nombres
        $debit = floatval($this->debit);
        $credit = floatval($this->credit);
        
        // Validation Débit/Crédit
        if ($debit == 0 && $credit == 0) {
            $this->addError('debit', 'Vous devez saisir un montant (débit OU crédit).');
            $this->addError('credit', 'Vous devez saisir un montant (débit OU crédit).');
            return;
        }
        
        if ($debit > 0 && $credit > 0) {
            $this->addError('debit', 'Choisir uniquement Débit OU Crédit, pas les deux.');
            $this->addError('credit', 'Choisir uniquement Débit OU Crédit, pas les deux.');
            return;
        }

        // Normaliser le code journal (majuscules, trim)
        $journalCode = $this->journal_code ? strtoupper(trim($this->journal_code)) : null;

        // Récupérer le mapping pour le nouveau compte
        $newAccountId = null;
        if ($this->old_account_id) {
            $mapping = AccountMapping::where('old_account_id', $this->old_account_id)
                ->where('entreprise_id', auth()->user()->entreprise_id)
                ->first();
            
            if ($mapping) {
                $newAccountId = $mapping->new_account_id;
                Log::info('Mapping appliqué', ['new_account_id' => $newAccountId]);
            } else {
                Log::warning('Aucun mapping trouvé pour ce compte');
            }
        }

        // Calculer les métadonnées
        $date = Carbon::parse($this->date_ecriture);
        $exercice = $date->year;
        $mois = $date->month;
        $trimestre = ceil($mois / 3);
        $solde = abs($debit - $credit);

        try {
            $ecriture = GrandLivre::create([
                'entreprise_id' => auth()->user()->entreprise_id,
                'date_ecriture' => $this->date_ecriture,
                'journal_code' => $journalCode, // ← STRING directement
                'libelle' => $this->libelle,
                'debit' => $debit,
                'credit' => $credit,
                'piece' => $this->piece,
                'old_account_id' => $this->old_account_id,
                'new_account_id' => $newAccountId, // Auto-détecté depuis mapping
                'exercice' => $exercice,
                'mois' => $mois,
                'trimestre' => $trimestre,
                'solde' => $solde,
                'source' => 'manuel',
                'validated' => true,
            ]);
            
            Log::info('Écriture créée avec succès', [
                'id' => $ecriture->id,
                'debit' => $ecriture->debit,
                'credit' => $ecriture->credit,
                'journal_code' => $ecriture->journal_code,
                'new_account_id' => $ecriture->new_account_id
            ]);

            // Message de succès avec info mapping
            $message = 'Écriture ajoutée avec succès.';
            if ($newAccountId) {
                $message .= ' ✓ Compte mappé automatiquement.';
            } else {
                $message .= ' ⚠️ Compte non mappé vers nouveau plan.';
            }
            
            session()->flash('success', $message);
            
        } catch (\Exception $e) {
            Log::error('Erreur création écriture', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Erreur lors de l\'ajout: ' . $e->getMessage());
            return;
        }

        // Fermer le modal
        $this->dispatch('close-add-modal');

        // Reset du formulaire
        $this->reset([
            'libelle',
            'debit',
            'credit',
            'old_account_id',
            'piece',
            'journal_code',
            'mapped_new_account',
            'mapping_exists',
            'mapping_details'
        ]);
        
        $this->date_ecriture = date('Y-m-d');

        // Recharger la liste des journaux
        $this->loadJournauxCodes();

        // Rafraîchir la vue principale
        $this->dispatch('refresh-grand-livre');
    }

    public function render()
    {
        return view('livewire.grand-livre.add', [
            'old_accounts' => OldAccount::where('entreprise_id', auth()->user()->entreprise_id)
                ->orderBy('code')
                ->get(),
            'journaux_codes' => $this->journaux_codes,
        ]);
    }
}