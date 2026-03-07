<?php

namespace App\Livewire\Financial;

use Livewire\Component;
use App\Models\Balance;
use App\Models\BilanReferenceMapping;
use Carbon\Carbon;

class CompteDeResultat extends Component
{
    public $entreprise;
    public $dateDebut;
    public $dateFin;
    public $exercice;
    
    public $resultatData = [];
    public $totaux = [];
    
    // Liste des références pour le compte de résultat selon le modèle
    private $referencesResultat = [
        // PRODUITS (classe 7)
        'RA', 'RB', 'RC', 'RD', 'RE', 'RF', 'RG', 'RH',
        // CHARGES (classe 6)
        'TA', 'TB', 'TC', 'TD', 'TE', 'TF', 'TG', 'TH', 'TI', 'TJ', 'TK', 'TL',
        // CHARGES ET PRODUITS H.A.O.
        'TM', 'TN',
        // TOTAUX ET RÉSULTATS
        'XA', 'XB', 'XC', 'XD', 'XE'
    ];
    
    // Liste des comptes de variations de stocks (classe 603)
    private $comptesVariationStocks = [
        '6031', '6032', '6033', '6034', '6035', '6036', '6037', '60371', '60372', '60373', '60374', '6038', '6039'
    ];
    
    public function mount($entreprise = null, $dateDebut = null, $dateFin = null, $exercice = null)
    {
        $this->entreprise = $entreprise ?? auth()->user()->entreprise;
        
        $this->exercice = $exercice ?? 2024;
        $carbonDateDebut = Carbon::create($this->exercice, 1, 1);
        $carbonDateFin = Carbon::create($this->exercice, 12, 31);
        
        $this->dateDebut = $dateDebut ?? $carbonDateDebut->format('Y-m-d');
        $this->dateFin = $dateFin ?? $carbonDateFin->format('Y-m-d');
        
        $this->calculerResultat();
    }
    
    public function calculerResultat()
    {
        try {
            // 1. Initialiser la structure complète
            $this->initialiserStructureComplete();
            
            // 2. Récupérer les mappings pour les références du compte de résultat
            $mappings = BilanReferenceMapping::whereIn('reference_code', $this->referencesResultat)
                ->get()
                ->groupBy('reference_code');
            
            // 🔴 IMPORTANT: AFFICHER TOUS LES MAPPINGS POUR TG
            if (isset($mappings['TG'])) {
                \Log::info('=== MAPPINGS POUR TG ===');
                foreach ($mappings['TG'] as $mapping) {
                    \Log::info("Compte TG mappé: " . $mapping->account_code);
                }
            } else {
                \Log::warning('AUCUN MAPPING TROUVÉ POUR TG');
            }
            
            // 3. Récupérer les balances pour l'exercice
            $balances = Balance::where('entreprise_id', $this->entreprise->id)
                ->where('periode_debut', '>=', $this->dateDebut)
                ->where('periode_fin', '<=', $this->dateFin)
                ->get();
            
            // 4. Créer un tableau des soldes par compte
            $soldesParCompte = [];
            foreach ($balances as $balance) {
                $soldesParCompte[$balance->compte_code] = [
                    'debit' => (float) $balance->solde_debiteur,
                    'credit' => (float) $balance->solde_crediteur,
                    'solde' => (float) $balance->solde_debiteur - (float) $balance->solde_crediteur
                ];
            }
            
            \Log::info('=== DÉBUT CALCUL COMPTE DE RÉSULTAT ===');
            \Log::info('Nombre de mappings trouvés: ' . $mappings->count());
            
            // 🔴 VÉRIFIER SPÉCIFIQUEMENT LE COMPTE 631800
            if (isset($soldesParCompte['631800'])) {
                \Log::info('=== COMPTE 631800 TROUVÉ ===');
                \Log::info('Débit: ' . $soldesParCompte['631800']['debit']);
                \Log::info('Crédit: ' . $soldesParCompte['631800']['credit']);
                \Log::info('Solde: ' . $soldesParCompte['631800']['solde']);
            } else {
                \Log::warning('COMPTE 631800 NON TROUVÉ DANS LES BALANCES');
            }
            
            // 5. Calculer les montants pour chaque référence
            foreach ($mappings as $reference => $mappingItems) {
                $total = 0;
                
                foreach ($mappingItems as $mapping) {
                    $compteCode = $mapping->account_code;
                    
                    if (isset($soldesParCompte[$compteCode])) {
                        $solde = $soldesParCompte[$compteCode];
                        
                        // Vérifier si c'est un compte de variation de stocks
                        $estVariationStock = false;
                        foreach ($this->comptesVariationStocks as $prefix) {
                            if (strpos($compteCode, $prefix) === 0) {
                                $estVariationStock = true;
                                break;
                            }
                        }
                        
                        // RÈGLES COMPTABLES CORRECTES
                        if ($estVariationStock) {
                            // VARIATIONS DE STOCKS (603...)
                            $montant = $solde['debit'] - $solde['credit'];
                            $total += $montant;
                            \Log::info("Variation stock $reference - Compte $compteCode: Débit={$solde['debit']}, Crédit={$solde['credit']}, Montant=$montant");
                        } else {
                            // AUTRES COMPTES
                            $classe = substr($compteCode, 0, 1);
                            
                            if ($classe == '6') {
                                // CHARGES: on prend le débit
                                $total += $solde['debit'];
                                \Log::info("Charge $reference - Compte $compteCode: débit = " . $solde['debit']);
                            } elseif ($classe == '7') {
                                // PRODUITS: on prend le crédit
                                $total += $solde['credit'];
                                \Log::info("Produit $reference - Compte $compteCode: crédit = " . $solde['credit']);
                            } elseif ($classe == '8') {
                                // COMPTES SPECIAUX (H.A.O.)
                                if (in_array($reference, ['TM', 'TN'])) {
                                    $montant = $solde['debit'] - $solde['credit'];
                                    $total += $montant;
                                    \Log::info("Compte HAO $reference - Compte $compteCode: Débit={$solde['debit']}, Crédit={$solde['credit']}, Montant=$montant");
                                } else {
                                    $total += $solde['debit'];
                                    \Log::info("Compte spécial $reference - Compte $compteCode: débit = " . $solde['debit']);
                                }
                            }
                        }
                    } else {
                        \Log::warning("Compte $compteCode non trouvé dans les balances pour la référence $reference");
                    }
                }
                
                // Mettre à jour le montant pour cette référence
                if (isset($this->resultatData[$reference])) {
                    $this->resultatData[$reference]['montant'] = $total;
                }
                
                \Log::info("Référence $reference: montant total = " . number_format($total, 2, ',', ' '));
            }
            
            // 6. Calculer les totaux automatiquement
            $this->calculerTotaux();
            
            // 7. Afficher les résultats finaux
            \Log::info('=== RÉSULTATS FINAUX ===');
            $resultatsAffiches = ['RA', 'RB', 'RC', 'XA', 'TD', 'TE', 'TF', 'TG', 'TH', 'TI', 'TJ', 'TK', 'TL', 'XB', 'XC', 'TM', 'TN', 'XD', 'XE'];
            foreach ($resultatsAffiches as $ref) {
                if (isset($this->resultatData[$ref])) {
                    \Log::info("$ref: " . number_format($this->resultatData[$ref]['montant'], 2, ',', ' '));
                }
            }
            \Log::info('=== FIN CALCUL ===');
            
        } catch (\Exception $e) {
            \Log::error('Erreur calcul compte de résultat: ' . $e->getMessage() . ' - ' . $e->getTraceAsString());
            session()->flash('error', 'Erreur calcul: ' . $e->getMessage());
        }
    }
    
    private function initialiserStructureComplete()
    {
        // Structure complète selon le modèle de l'image
        $structure = [
            // PRODUITS
            'RA' => ['libelle' => 'Cotisations', 'note' => '23', 'type' => 'produit'],
            'RB' => ['libelle' => 'Dotations consomptibles transférées au compte de résultat', 'note' => '23', 'type' => 'produit'],
            'RC' => ['libelle' => 'Revenus liés à la générosité', 'note' => '23', 'type' => 'produit'],
            'RD' => ['libelle' => 'Ventes de marchandises', 'note' => '23', 'type' => 'produit'],
            'RE' => ['libelle' => 'Ventes de services et produits finis', 'note' => '23', 'type' => 'produit'],
            'RF' => ['libelle' => 'Subventions d\'exploitation', 'note' => '23', 'type' => 'produit'],
            'RG' => ['libelle' => 'Autres produits et transferts de charges', 'note' => '23', 'type' => 'produit'],
            'RH' => ['libelle' => 'Reprises de provisions, dépréciations, subventions et autres reprises', 'note' => '5D&30', 'type' => 'produit'],
            
            // TOTAUX PRODUITS
            'XA' => ['libelle' => 'REVENUS DES ACTIVITES ORDINAIRES (Somme RA à RG)', 'note' => '', 'type' => 'total_produits'],
            
            // CHARGES
            'TA' => ['libelle' => 'Achats de biens et services liés à l\'activité', 'note' => '24', 'type' => 'charge'],
            'TB' => ['libelle' => 'Variation de stocks des achats de biens et services liés à l\'activité', 'note' => '8', 'type' => 'charge'],
            'TC' => ['libelle' => 'Achats de marchandises et matières premières', 'note' => '24', 'type' => 'charge'],
            'TD' => ['libelle' => 'Autres achats', 'note' => '24', 'type' => 'charge'],
            'TE' => ['libelle' => 'Variation de stocks de marchandises, de matières premières et autres', 'note' => '8', 'type' => 'charge'],
            'TF' => ['libelle' => 'Transports', 'note' => '25', 'type' => 'charge'],
            'TG' => ['libelle' => 'Services extérieurs', 'note' => '26', 'type' => 'charge'],
            'TH' => ['libelle' => 'Impôts et taxes', 'note' => '27', 'type' => 'charge'],
            'TI' => ['libelle' => 'Autres charges', 'note' => '28', 'type' => 'charge'],
            'TJ' => ['libelle' => 'Charges de personnel', 'note' => '29', 'type' => 'charge'],
            'TK' => ['libelle' => 'Frais financiers et charges assimilées', 'note' => '31', 'type' => 'charge'],
            'TL' => ['libelle' => 'Dotations aux amortissements, aux provisions, aux dépréciations et autres', 'note' => '5D&30', 'type' => 'charge'],
            
            // TOTAUX CHARGES
            'XB' => ['libelle' => 'CHARGES DES ACTIVITES ORDINAIRES (Somme TA à TL)', 'note' => '', 'type' => 'total_charges'],
            
            // RÉSULTAT ORDINAIRE
            'XC' => ['libelle' => 'RESULTAT DES ACTIVITES ORDINAIRES (XA - XB)', 'note' => '', 'type' => 'resultat_ordinaire'],
            
            // H.A.O.
            'TM' => ['libelle' => 'Produits H.A.O.', 'note' => '32', 'type' => 'produit_hao'],
            'TN' => ['libelle' => 'Charges H.A.O.', 'note' => '32', 'type' => 'charge_hao'],
            
            // RÉSULTAT H.A.O.
            'XD' => ['libelle' => 'RESULTAT H.A.O. (TM - TN)', 'note' => '', 'type' => 'resultat_hao'],
            
            // RÉSULTAT NET
            'XE' => ['libelle' => 'RESULTAT NET DE L\'EXERCICE(+excédent, -déficit) (XC+XD)', 'note' => '', 'type' => 'resultat_net'],
        ];
        
        foreach ($structure as $ref => $data) {
            $this->resultatData[$ref] = [
                'libelle' => $data['libelle'],
                'note' => $data['note'],
                'type' => $data['type'],
                'montant' => 0,
                'montant_n1' => 0
            ];
        }
    }
    
    private function calculerTotaux()
    {
        // 1. Calculer XA - TOTAL REVENUS DES ACTIVITÉS ORDINAIRES (somme de RA à RG)
        $revenusOrdinairesRefs = ['RA', 'RB', 'RC', 'RD', 'RE', 'RF', 'RG'];
        $totalRevenusOrdinaires = 0;
        foreach ($revenusOrdinairesRefs as $ref) {
            if (isset($this->resultatData[$ref])) {
                $totalRevenusOrdinaires += $this->resultatData[$ref]['montant'] ?? 0;
            }
        }
        $this->resultatData['XA']['montant'] = $totalRevenusOrdinaires;
        
        // 2. Calculer XB - TOTAL CHARGES DES ACTIVITÉS ORDINAIRES (somme de TA à TL)
        $chargesOrdinairesRefs = ['TA', 'TB', 'TC', 'TD', 'TE', 'TF', 'TG', 'TH', 'TI', 'TJ', 'TK', 'TL'];
        $totalChargesOrdinaires = 0;
        foreach ($chargesOrdinairesRefs as $ref) {
            if (isset($this->resultatData[$ref])) {
                $totalChargesOrdinaires += $this->resultatData[$ref]['montant'] ?? 0;
            }
        }
        $this->resultatData['XB']['montant'] = $totalChargesOrdinaires;
        
        // 3. Calculer XC - Résultat des activités ordinaires (XA - XB)
        $resultatOrdinaire = $totalRevenusOrdinaires - $totalChargesOrdinaires;
        $this->resultatData['XC']['montant'] = $resultatOrdinaire;
        
        // 4. Calculer XD - Résultat H.A.O. (TM - TN)
        $produitsHAO = $this->resultatData['TM']['montant'] ?? 0;
        $chargesHAO = $this->resultatData['TN']['montant'] ?? 0;
        $resultatHAO = $produitsHAO - $chargesHAO;
        $this->resultatData['XD']['montant'] = $resultatHAO;
        
        // 5. Calculer XE - Résultat net (XC + XD)
        $resultatNet = $resultatOrdinaire + $resultatHAO;
        $this->resultatData['XE']['montant'] = $resultatNet;
        
        // 6. Mettre à jour les totaux globaux
        $this->totaux = [
            'revenus_ordinaires' => $totalRevenusOrdinaires,
            'charges_ordinaires' => $totalChargesOrdinaires,
            'resultat_ordinaire' => $resultatOrdinaire,
            'produits_hao' => $produitsHAO,
            'charges_hao' => $chargesHAO,
            'resultat_hao' => $resultatHAO,
            'resultat_net' => $resultatNet
        ];
        
        \Log::info('=== VÉRIFICATION RÉSULTAT NET ===');
        \Log::info('XA (Produits): ' . number_format($totalRevenusOrdinaires, 2, ',', ' '));
        \Log::info('XB (Charges): ' . number_format($totalChargesOrdinaires, 2, ',', ' '));
        \Log::info('XC (Résultat ordinaire): ' . number_format($resultatOrdinaire, 2, ',', ' '));
        \Log::info('XD (Résultat HAO): ' . number_format($resultatHAO, 2, ',', ' '));
        \Log::info('XE (Résultat net): ' . number_format($resultatNet, 2, ',', ' '));
    }
    
    /**
     * MÉTHODE DE CORRECTION: Ajouter le compte 631800 au mapping de TG
     * Exécutez cette méthode une seule fois pour corriger la base de données
     */
    public function corrigerMappingTG()
    {
        try {
            // Vérifier si le compte 631800 est déjà mappé à TG
            $existant = BilanReferenceMapping::where('reference_code', 'TG')
                ->where('account_code', '631800')
                ->first();
            
            if (!$existant) {
                // Ajouter le mapping manquant
                BilanReferenceMapping::create([
                    'reference_code' => 'TG',
                    'account_code' => '631800',
                    'description' => 'Services extérieurs - Compte 631800',
                    'type' => 'charge'
                ]);
                
                \Log::info('✅ Mapping TG - Compte 631800 ajouté avec succès');
                session()->flash('success', 'Mapping pour le compte 631800 ajouté à TG');
            } else {
                \Log::info('ℹ️ Le mapping TG - Compte 631800 existe déjà');
                session()->flash('info', 'Le mapping pour le compte 631800 existe déjà');
            }
            
            // Recalculer le résultat
            $this->calculerResultat();
            
        } catch (\Exception $e) {
            \Log::error('Erreur lors de la correction du mapping: ' . $e->getMessage());
            session()->flash('error', 'Erreur: ' . $e->getMessage());
        }
    }
    
    public function refreshResultat()
    {
        $this->calculerResultat();
        session()->flash('success', 'Compte de résultat recalculé pour l\'exercice ' . $this->exercice);
    }
    
    public function render()
    {
        // Organiser les données dans l'ordre d'affichage correct
        $ordreAffichage = [
            // Produits
            'RA', 'RB', 'RC', 'RD', 'RE', 'RF', 'RG', 'RH',
            'XA',
            
            // Charges
            'TA', 'TB', 'TC', 'TD', 'TE', 'TF', 'TG', 'TH', 'TI', 'TJ', 'TK', 'TL',
            'XB',
            
            // Résultat ordinaire
            'XC',
            
            // H.A.O.
            'TM', 'TN', 'XD',
            
            // Résultat net
            'XE'
        ];
        
        // Créer un tableau ordonné pour la vue
        $resultatOrdonne = [];
        foreach ($ordreAffichage as $ref) {
            if (isset($this->resultatData[$ref])) {
                $resultatOrdonne[$ref] = $this->resultatData[$ref];
            }
        }
        
        return view('livewire.financial.compte-de-resultat', [
            'resultatData' => $resultatOrdonne,
            'totaux' => $this->totaux,
            'entreprise' => $this->entreprise,
            'exercice' => $this->exercice,
            'dateDebut' => $this->dateDebut,
            'dateFin' => $this->dateFin
        ])->layout('layouts.app');
    }
}