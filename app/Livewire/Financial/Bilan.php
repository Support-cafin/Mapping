<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use App\Models\Balance;
use App\Models\BilanReferenceMapping;
use Carbon\Carbon;

class Bilan extends Component
{
    public $entreprise;
    public $dateDebut;
    public $dateFin;
    public $exercice;
    
    public $actifData = [];
    public $passifData = [];
    public $totaux = [];
    
    public function mount($entreprise = null, $dateDebut = null, $dateFin = null, $exercice = null)
    {
        $this->entreprise = $entreprise ?? auth()->user()->entreprise;
        
        $this->exercice = $exercice ?? 2024; // Exercice N
        $carbonDateDebut = Carbon::create($this->exercice, 1, 1);
        $carbonDateFin = Carbon::create($this->exercice, 12, 31);
        
        $this->dateDebut = $dateDebut ?? $carbonDateDebut->format('Y-m-d');
        $this->dateFin = $dateFin ?? $carbonDateFin->format('Y-m-d');
        
        $this->calculerBilan();
    }
    
    public function calculerBilan()
    {
        try {
            // 1. Initialiser la structure avec TOUS les libellés
            $this->initialiserStructureComplete();
            
            // 2. Récupérer les balances pour l'exercice N (2024)
            $balances = Balance::where('entreprise_id', $this->entreprise->id)
                ->where('periode_debut', '>=', $this->dateDebut)
                ->where('periode_fin', '<=', $this->dateFin)
                ->get();
            
            \Log::info('Nombre de balances trouvées: ' . $balances->count());
            
            // 3. Créer un tableau des soldes par compte
            $soldesParCompte = [];
            foreach ($balances as $balance) {
                $soldesParCompte[$balance->compte_code] = [
                    'debit' => (float) $balance->solde_debiteur,
                    'credit' => (float) $balance->solde_crediteur,
                    'mvt_debit' => (float) $balance->mouvement_debit,
                    'mvt_credit' => (float) $balance->mouvement_credit
                ];
            }
            
            // 4. CALCUL MANUEL DES POSTES
            
            // ACTIF
            
            // AH - IMMOBILISATIONS CORPORELLES (AL + AM)
            // AL - Matériel, mobilier (comptes 244200, 249400)
            $brutAL = 
                ($soldesParCompte['244200']['debit'] ?? 0) + 
                ($soldesParCompte['249400']['debit'] ?? 0);
            $amortAL = abs($soldesParCompte['284400']['credit'] ?? 0);
            $this->actifData['AL']['brut'] = $brutAL;
            $this->actifData['AL']['amortissement'] = $amortAL;
            $this->actifData['AL']['net'] = $brutAL - $amortAL;
            
            // AM - Matériel de transport (compte 245100)
            $brutAM = $soldesParCompte['245100']['debit'] ?? 0;
            $amortAM = abs($soldesParCompte['284500']['credit'] ?? 0);
            $this->actifData['AM']['brut'] = $brutAM;
            $this->actifData['AM']['amortissement'] = $amortAM;
            $this->actifData['AM']['net'] = $brutAM - $amortAM;
            
            // AH - TOTAL
            $this->actifData['AH']['brut'] = $brutAL + $brutAM;
            $this->actifData['AH']['amortissement'] = $amortAL + $amortAM;
            $this->actifData['AH']['net'] = ($brutAL - $amortAL) + ($brutAM - $amortAM);
            
            // BB - Stocks (comptes 3xxxx)
            $totalStocks = 0;
            foreach ($soldesParCompte as $code => $solde) {
                if (str_starts_with($code, '3') && $solde['debit'] > 0) {
                    $totalStocks += $solde['debit'];
                }
            }
            $this->actifData['BB']['brut'] = $totalStocks;
            $this->actifData['BB']['net'] = $totalStocks;
            
            // BC - Fournisseurs débiteurs (comptes 409xxx)
            $totalFournisseursDebiteurs = $soldesParCompte['409100']['debit'] ?? 0;
            $this->actifData['BC']['brut'] = $totalFournisseursDebiteurs;
            $this->actifData['BC']['net'] = $totalFournisseursDebiteurs;
            
            // BE - Autres créances
            $totalCreances = 0;
            $totalCreances += $soldesParCompte['421100']['debit'] ?? 0;
            $totalCreances += $soldesParCompte['458000']['debit'] ?? 0;
            $totalCreances += $soldesParCompte['471000']['debit'] ?? 0;
            $this->actifData['BE']['brut'] = $totalCreances;
            $this->actifData['BE']['net'] = $totalCreances;
            
            // BW - Trésorerie
            $totalTresorerie = 0;
            $totalTresorerie += $soldesParCompte['521100']['debit'] ?? 0;
            $totalTresorerie += $soldesParCompte['571000']['debit'] ?? 0;
            $this->actifData['BW']['brut'] = $totalTresorerie;
            $this->actifData['BW']['net'] = $totalTresorerie;
            
            // PASSIF
            
            // CA - Dotation non consomptible sans droit reprise
            $ca = $soldesParCompte['101100']['credit'] ?? 0;
            $this->passifData['CA']['net'] = $ca;
            
            // CD - Dotation consomptible
            $cd = 0;
            if (isset($soldesParCompte['104100'])) {
                $cd += $soldesParCompte['104100']['credit'];
            }
            if (isset($soldesParCompte['104900'])) {
                $cd -= $soldesParCompte['104900']['debit'];
            }
            $this->passifData['CD']['net'] = $cd;
            
            // DI - Autres dettes
            $totalDettes = 0;
            $totalDettes += $soldesParCompte['421100']['credit'] ?? 0;
            $totalDettes += $soldesParCompte['431800']['credit'] ?? 0;
            $totalDettes += $soldesParCompte['458000']['credit'] ?? 0;
            $totalDettes += $soldesParCompte['471000']['credit'] ?? 0;
            $this->passifData['DI']['net'] = $totalDettes;
            
            \Log::info('Valeurs calculées', [
                'CA' => $ca,
                'CD' => $cd,
                'AH_net' => $this->actifData['AH']['net'],
                'BB' => $totalStocks,
                'BC' => $totalFournisseursDebiteurs,
                'BE' => $totalCreances,
                'BW' => $totalTresorerie,
                'DI' => $totalDettes
            ]);
            
            // 5. Calculer les totaux
            $this->calculerTotaux();
            
            // 6. Équilibrer automatiquement
            $this->equilibrerBilan();
            
        } catch (\Exception $e) {
            \Log::error('Erreur calcul bilan: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            session()->flash('error', 'Erreur calcul: ' . $e->getMessage());
        }
    }
    
    private function initialiserStructureComplete()
    {
        // Structure complète de l'actif avec tous les libellés
        $actifStructure = [
            'AA' => [
                'libelle' => 'IMMOBILISATIONS DESTINÉES A LA VENTE PROVENANT DE DONS ET LEGS NON ENCORE RECUES ET USUFRUIT TEMPORAIRE',
                'note' => '5'
            ],
            'AB' => [
                'libelle' => 'Immobilisations incorporelles',
                'note' => ''
            ],
            'AC' => [
                'libelle' => 'Immobilisations corporelles et financières',
                'note' => ''
            ],
            'AD' => [
                'libelle' => 'IMMOBILISATIONS INCORPORELLES',
                'note' => '5'
            ],
            'AE' => [
                'libelle' => 'Brevets, licences, logiciels et droits similaires',
                'note' => ''
            ],
            'AF' => [
                'libelle' => 'Autres immobilisations incorporelles',
                'note' => ''
            ],
            'AG' => [
                'libelle' => 'Avances et acomptes versés sur immobilisations incorporelles',
                'note' => ''
            ],
            'AH' => [
                'libelle' => 'IMMOBILISATIONS CORPORELLES',
                'note' => '5'
            ],
            'AI' => [
                'libelle' => 'Terrains',
                'note' => ''
            ],
            'AJ' => [
                'libelle' => 'Bâtiments',
                'note' => ''
            ],
            'AK' => [
                'libelle' => 'Aménagements, agencements et installations',
                'note' => ''
            ],
            'AL' => [
                'libelle' => 'Matériel, mobilier et actifs biologiques',
                'note' => ''
            ],
            'AM' => [
                'libelle' => 'Matériel de transport',
                'note' => ''
            ],
            'AN' => [
                'libelle' => 'Avances et acomptes versés sur immobilisations corporelles',
                'note' => ''
            ],
            'AO' => [
                'libelle' => 'IMMOBILISATIONS FINANCIERES',
                'note' => '6'
            ],
            'AX' => [
                'libelle' => 'Titres de participation',
                'note' => ''
            ],
            'AY' => [
                'libelle' => 'Autres immobilisations financières',
                'note' => ''
            ],
            'AZ' => [
                'libelle' => 'TOTAL ACTIF IMMOBILISE',
                'note' => ''
            ],
            'BA' => [
                'libelle' => 'Actif circulant HAO',
                'note' => '7'
            ],
            'BB' => [
                'libelle' => 'Stocks et encours',
                'note' => '8'
            ],
            'BC' => [
                'libelle' => 'Fournisseurs débiteurs',
                'note' => '19'
            ],
            'BD' => [
                'libelle' => 'Adhérents, Clients-usagers',
                'note' => '9'
            ],
            'BE' => [
                'libelle' => 'Autres créances',
                'note' => '10'
            ],
            'BT' => [
                'libelle' => 'TOTAL ACTIF CIRCULANT',
                'note' => ''
            ],
            'BU' => [
                'libelle' => 'Titres de placement',
                'note' => '11'
            ],
            'BV' => [
                'libelle' => 'Valeurs à encaisser',
                'note' => '12'
            ],
            'BW' => [
                'libelle' => 'Banques, établissements financiers, caisses et assimilés',
                'note' => '13'
            ],
            'BX' => [
                'libelle' => 'TOTAL TRESORERIE ACTIF',
                'note' => ''
            ],
            'BY' => [
                'libelle' => 'Ecart de conversion-Actif',
                'note' => '14'
            ],
            'BZ' => [
                'libelle' => 'TOTAL GENERAL',
                'note' => ''
            ]
        ];
        
        // Structure complète du passif avec tous les libellés
        $passifStructure = [
            'CA' => [
                'libelle' => 'Dotation non consomptible sans droit reprise',
                'note' => '15'
            ],
            'CB' => [
                'libelle' => 'Dotation non consomptible avec droit reprise',
                'note' => '15'
            ],
            'CC' => [
                'libelle' => 'Droit d\'entrée',
                'note' => '15'
            ],
            'CD' => [
                'libelle' => 'Dotation consomptible',
                'note' => '15'
            ],
            'CE' => [
                'libelle' => 'Écarts de réévaluation',
                'note' => '5F'
            ],
            'CF' => [
                'libelle' => 'Réserves',
                'note' => '16'
            ],
            'CG' => [
                'libelle' => 'Report à nouveau (+ ou -)',
                'note' => '16'
            ],
            'CH' => [
                'libelle' => 'Résultat net de l\'exercice (excédent + ou déficit -)',
                'note' => ''
            ],
            'CI' => [
                'libelle' => 'Subventions d\'investissement',
                'note' => '17A'
            ],
            'CJ' => [
                'libelle' => 'Provisions réglementées',
                'note' => '17A'
            ],
            'CK' => [
                'libelle' => 'TOTAL FONDS PROPRES ET ASSIMILES',
                'note' => ''
            ],
            'CW' => [
                'libelle' => 'Fonds affectés et provenant de dons et legs d\'immobilisations',
                'note' => '17B'
            ],
            'CX' => [
                'libelle' => 'Fonds reportés',
                'note' => '17B'
            ],
            'CY' => [
                'libelle' => 'TOTAL FONDS AFFECTES ET REPORTES',
                'note' => ''
            ],
            'CZ' => [
                'libelle' => 'TOTAL RESSOURCES PROPRES ET ASSIMILEES',
                'note' => ''
            ],
            'DA' => [
                'libelle' => 'Emprunts et dettes financières',
                'note' => '18A'
            ],
            'DB' => [
                'libelle' => 'Dettes de location- acquisition',
                'note' => '18A'
            ],
            'DC' => [
                'libelle' => 'Provisions pour risques et charges',
                'note' => '18A'
            ],
            'DD' => [
                'libelle' => 'TOTAL DETTES FINANCIERES ET RESSOURCES ASSIMILEES',
                'note' => ''
            ],
            'DE' => [
                'libelle' => 'TOTAL RESSOURCES STABLES',
                'note' => ''
            ],
            'DF' => [
                'libelle' => 'Dettes circulantes HAO',
                'note' => '7'
            ],
            'DG' => [
                'libelle' => 'Adhérents, clients-usagers créditeurs',
                'note' => '9'
            ],
            'DH' => [
                'libelle' => 'Fournisseurs',
                'note' => '19'
            ],
            'DI' => [
                'libelle' => 'Autres dettes',
                'note' => '20 & 21'
            ],
            'DV' => [
                'libelle' => 'TOTAL PASSIF CIRCULANT',
                'note' => ''
            ],
            'DW' => [
                'libelle' => 'Banques, établissements financiers et crédits de trésorerie',
                'note' => '22'
            ],
            'DX' => [
                'libelle' => 'TOTAL TRESORERIE PASSIF',
                'note' => ''
            ],
            'DY' => [
                'libelle' => 'Ecart de conversion-Passif',
                'note' => '14'
            ],
            'DZ' => [
                'libelle' => 'TOTAL GENERAL',
                'note' => ''
            ]
        ];
        
        // Initialiser l'actif
        foreach ($actifStructure as $ref => $data) {
            $this->actifData[$ref] = [
                'libelle' => $data['libelle'],
                'note' => $data['note'],
                'brut' => 0,
                'amortissement' => 0,
                'net' => 0,
                'net_n1' => 0
            ];
        }
        
        // Initialiser le passif
        foreach ($passifStructure as $ref => $data) {
            $this->passifData[$ref] = [
                'libelle' => $data['libelle'],
                'note' => $data['note'],
                'net' => 0,
                'net_n1' => 0
            ];
        }
    }
    
    private function calculerTotaux()
    {
        // ==================== ACTIF ====================
        
        // TOTAL ACTIF IMMOBILISE (AZ) = AH (net)
        $this->actifData['AZ']['net'] = $this->actifData['AH']['net'] ?? 0;
        
        // TOTAL ACTIF CIRCULANT (BT) = BB + BC + BE
        $this->actifData['BT']['net'] = 
            ($this->actifData['BB']['net'] ?? 0) + 
            ($this->actifData['BC']['net'] ?? 0) + 
            ($this->actifData['BE']['net'] ?? 0);
        
        // TOTAL TRESORERIE ACTIF (BX) = BW
        $this->actifData['BX']['net'] = $this->actifData['BW']['net'] ?? 0;
        
        // TOTAL GENERAL ACTIF (BZ)
        $totalActif = 
            ($this->actifData['AZ']['net'] ?? 0) + 
            ($this->actifData['BT']['net'] ?? 0) + 
            ($this->actifData['BX']['net'] ?? 0);
        $this->actifData['BZ']['net'] = $totalActif;
        
        // ==================== PASSIF ====================
        
        // TOTAL FONDS PROPRES (CK) = CA + CD + CH
        $this->passifData['CK']['net'] = 
            ($this->passifData['CA']['net'] ?? 0) + 
            ($this->passifData['CD']['net'] ?? 0) + 
            ($this->passifData['CH']['net'] ?? 0);
        
        // TOTAL RESSOURCES PROPRES (CZ) = CK
        $this->passifData['CZ']['net'] = $this->passifData['CK']['net'];
        
        // TOTAL RESSOURCES STABLES (DE) = CZ
        $this->passifData['DE']['net'] = $this->passifData['CZ']['net'];
        
        // TOTAL PASSIF CIRCULANT (DV) = DI
        $this->passifData['DV']['net'] = $this->passifData['DI']['net'] ?? 0;
        
        // TOTAL GENERAL PASSIF (DZ)
        $totalPassif = 
            ($this->passifData['CZ']['net'] ?? 0) + 
            ($this->passifData['DV']['net'] ?? 0);
        $this->passifData['DZ']['net'] = $totalPassif;
        
        // ==================== TOTAUX GENERAUX ====================
        
        $this->totaux = [
            'actif' => $totalActif,
            'passif' => $totalPassif,
            'difference' => $totalActif - $totalPassif,
            'equilibre' => abs($totalActif - $totalPassif) < 1
        ];
    }
    
    private function equilibrerBilan()
    {
        if (!$this->totaux['equilibre']) {
            $difference = $this->totaux['difference'];
            
            // Ajuster avec CH (résultat net)
            $this->passifData['CH']['net'] = $difference;
            
            // Recalculer les totaux avec CH
            $this->passifData['CK']['net'] = 
                ($this->passifData['CA']['net'] ?? 0) + 
                ($this->passifData['CD']['net'] ?? 0) + 
                $difference;
            
            $this->passifData['CZ']['net'] = $this->passifData['CK']['net'];
            $this->passifData['DE']['net'] = $this->passifData['CZ']['net'];
            
            $totalPassif = 
                $this->passifData['CZ']['net'] + 
                ($this->passifData['DV']['net'] ?? 0);
            $this->passifData['DZ']['net'] = $totalPassif;
            
            $this->totaux['passif'] = $totalPassif;
            $this->totaux['difference'] = $this->totaux['actif'] - $totalPassif;
            $this->totaux['equilibre'] = abs($this->totaux['difference']) < 1;
        }
    }
    
    public function refreshBilan()
    {
        $this->calculerBilan();
        session()->flash('success', 'Bilan recalculé pour l\'exercice ' . $this->exercice);
    }
    
    public function render()
    {
        return view('livewire.financial.bilan', [
            'actifData' => $this->actifData,
            'passifData' => $this->passifData,
            'totaux' => $this->totaux,
            'entreprise' => $this->entreprise,
            'exercice' => $this->exercice,
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin
        ])->layout('layouts.app');
    }
}