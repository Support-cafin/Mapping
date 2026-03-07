@php
    function arrondirBalance($montant, $precision = 2) {
        return round(floatval($montant), $precision);
    }
    
    function sontEgauxBalance($a, $b, $tolerance = 0.01) {
        return abs($a - $b) < $tolerance;
    }
@endphp

<div class="bg-white rounded-lg shadow border overflow-hidden">
    <div class="px-6 py-3 border-b bg-gray-50">
        <div class="flex justify-between items-center">
            <h3 style="font-size: 13px;" class="font-semibold text-gray-900">
                Balance à 6 colonnes
            </h3>
            <div class="text-sm text-gray-600">
                {{ $stats['total_ecritures'] }} écritures au total
            </div>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Compte SYCEBNL
                    </th>
                    <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Intitulé
                    </th>
                    <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Solde d'ouverture
                    </th>
                    <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Mouvement
                    </th>
                    <th colspan="2" style="font-size: 13px;" class="px-6 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Solde clôture
                    </th>
                    <th style="font-size: 13px;" class="px-6 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Actions
                    </th>
                </tr>
                <tr class="bg-gray-50">
                    <th></th>
                    <th></th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Débit (A)
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Crédit (B)
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Débit (C)
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Crédit (D)
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Débiteur
                    </th>
                    <th style="font-size: 10px;" class="px-6 py-1 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-x">
                        Créditeur
                    </th>
                    <th></th>
                </tr>
            </thead>
            
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $totalOuvertureDebit = 0;
                    $totalOuvertureCredit = 0;
                    $totalMouvementDebit = 0;
                    $totalMouvementCredit = 0;
                    $totalClotureDebit = 0;
                    $totalClotureCredit = 0;
                    $comptesAffiches = 0;
                    
                    // Récupérer l'exercice en cours
                    $exo = DB::table('exercices')->where('statut', 1)->first();
                @endphp
                
                @forelse($balances as $balance)
                    @php
                        // Déterminer si c'est un compte parent
                        $hasChildren = isset($balance['has_children']) && $balance['has_children'];
                        $childrenCount = $balance['children_count'] ?? 0;
                        $oldAccountsCount = isset($balance['old_accounts_data']) ? count($balance['old_accounts_data']) : 0;
                        
                        // Récupérer les soldes d'ouverture (RAN) pour ce compte parent (incluant ses enfants)
                        $allAccountIds = [$balance['id']];
                        
                        if ($hasChildren) {
                            // Pour les parents, on doit aussi chercher les RAN de tous les enfants
                            $childrenIds = isset($balance['children_data']) 
                                ? collect($balance['children_data'])->pluck('id')->toArray() 
                                : [];
                            $allAccountIds = array_merge($allAccountIds, $childrenIds);
                        }
                        
                        // Récupérer les écritures RAN pour tous les comptes (parent + enfants)
                        $soldesRAN = DB::table('grand_livres')
                            ->join('new_accounts', 'new_accounts.id', 'grand_livres.new_account_id')
                            ->whereIn('grand_livres.new_account_id', $allAccountIds)
                            ->where('grand_livres.journal_code', 'RAN')
                            ->where('grand_livres.entreprise_id', $entreprise->id)
                            ->where('grand_livres.exercice_id', $exo?->id)
                            ->get();
                        
                        $ouvertureDebit = arrondirBalance($soldesRAN->sum('debit'));
                        $ouvertureCredit = arrondirBalance($soldesRAN->sum('credit'));
                        
                        // Mouvements (exclure RAN)
                        $mouvementDebit = 0;
                        $mouvementCredit = 0;
                        
                        if (isset($balance['ecritures']) && $balance['ecritures']->count() > 0) {
                            $ecrituresSansRAN = $balance['ecritures']->filter(function($ecriture) {
                                return $ecriture->journal_code !== 'RAN';
                            });
                            
                            $mouvementDebit = arrondirBalance($ecrituresSansRAN->sum('debit'));
                            $mouvementCredit = arrondirBalance($ecrituresSansRAN->sum('credit'));
                        }
                        
                        $totalDebit = arrondirBalance($ouvertureDebit + $mouvementDebit);
                        $totalCredit = arrondirBalance($ouvertureCredit + $mouvementCredit);
                        
                        $soldeCloture = arrondirBalance($totalDebit - $totalCredit);
                        $isDebiteurCloture = $soldeCloture > 0;
                        $soldeClotureAbsolu = abs($soldeCloture);
                        
                        // Déterminer si on affiche
                        $hasOuverture = (abs($ouvertureDebit) > 0.001) || (abs($ouvertureCredit) > 0.001);
                        $hasMouvements = (abs($mouvementDebit) > 0.001) || (abs($mouvementCredit) > 0.001);
                        $hasCloture = (abs($soldeCloture) > 0.001);
                        
                        $doitAfficher = $hasOuverture || $hasMouvements || $hasCloture;
                        
                        if ($doitAfficher) {
                            $comptesAffiches++;
                            $totalOuvertureDebit = arrondirBalance($totalOuvertureDebit + $ouvertureDebit);
                            $totalOuvertureCredit = arrondirBalance($totalOuvertureCredit + $ouvertureCredit);
                            $totalMouvementDebit = arrondirBalance($totalMouvementDebit + $mouvementDebit);
                            $totalMouvementCredit = arrondirBalance($totalMouvementCredit + $mouvementCredit);
                            
                            if ($soldeCloture > 0) {
                                $totalClotureDebit = arrondirBalance($totalClotureDebit + $soldeCloture);
                            } else {
                                $totalClotureCredit = arrondirBalance($totalClotureCredit + abs($soldeCloture));
                            }
                        }
                    @endphp
                    
                    @if($doitAfficher)
                    <tr class="hover:bg-gray-50 {{ $hasChildren ? 'bg-blue-50/30' : '' }}">
                        <td class="px-6 py-2 whitespace-nowrap border-x">
                            <div style="font-size: 13px;" class="font-mono font-bold {{ $hasChildren ? 'text-indigo-700' : 'text-blue-700' }}">
                                {{ $balance['code'] }}
                            </div>
                            @if($hasChildren)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800 mt-1">
                                    <i class="fas fa-sitemap mr-1"></i> Parent ({{ $childrenCount }})
                                </span>
                            @endif
                        </td>
                        
                        <td class="px-6 py-2 border-x">
                            <div style="font-size: 13px;" class="font-medium text-gray-900">
                                {{ Str::limit($balance['intitule'], 30) }}
                            </div>
                            
                            <!-- Affichage conditionnel selon le type de compte -->
                            @if($hasChildren && $childrenCount > 0)
                                <div style="font-size: 10px;" class="text-indigo-600 mt-1">
                                    <i class="fas fa-sitemap mr-1"></i>
                                    {{ $childrenCount }} compte(s) enfant(s) agrégé(s)
                                </div>
                            @endif
                            
                            @if($oldAccountsCount > 0)
                                <div style="font-size: 10px;" class="text-gray-500 mt-1">
                                    <i class="fas fa-history mr-1"></i>
                                    {{ $oldAccountsCount }} compte entité
                                </div>
                            @endif
                            
                            <!-- Aperçu des enfants si c'est un parent -->
                            @if($hasChildren && isset($balance['children_data']) && count($balance['children_data']) > 0)
                                <div class="mt-2 text-xs text-gray-500 border-l-2 border-indigo-200 pl-2">
                                    <div class="font-medium text-indigo-600 mb-1">Enfants :</div>
                                    @foreach(array_slice($balance['children_data'], 0, 3) as $child)
                                        <div class="flex justify-between text-[10px]">
                                            <span>{{ $child['code'] }}</span>
                                            <span class="font-mono">{{ number_format($child['solde'] ?? 0, 0, ',', ' ') }}</span>
                                        </div>
                                    @endforeach
                                    @if(count($balance['children_data']) > 3)
                                        <div class="text-[10px] text-gray-400 mt-1">
                                            ... et {{ count($balance['children_data']) - 3 }} autre(s)
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </td>
                        
                        <!-- Solde d'ouverture Débit -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($ouvertureDebit > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($ouvertureDebit, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>
                        
                        <!-- Solde d'ouverture Crédit -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($ouvertureCredit > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($ouvertureCredit, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>
                        
                        <!-- Mouvement Débit -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($mouvementDebit > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($mouvementDebit, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>
                        
                        <!-- Mouvement Crédit -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($mouvementCredit > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($mouvementCredit, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>
                        
                        <!-- Solde clôture Débiteur -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if($isDebiteurCloture)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($soldeClotureAbsolu, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>
                        
                        <!-- Solde clôture Créditeur -->
                        <td class="px-6 py-2 whitespace-nowrap text-right border-x">
                            @if(!$isDebiteurCloture && $soldeClotureAbsolu > 0)
                                <div style="font-size: 13px;" class="text-black-600 font-medium">
                                    {{ number_format($soldeClotureAbsolu, 0, ',', ' ') }}
                                </div>
                            @else
                                <div style="font-size: 13px;" class="text-gray-400">-</div>
                            @endif
                        </td>
                        
                        <!-- Actions -->
                        <td class="px-6 py-2 whitespace-nowrap border-x">
                            @if($balance['ecritures_count'] > 0)
                                <button style="font-size: 13px;" wire:click="showDetails({{ $balance['id'] }})"
                                        class="text-blue-600 hover:text-blue-800 flex items-center gap-1">
                                    <i class="fas fa-eye"></i>
                                    Détails
                                    @if($hasChildren)
                                        <span class="ml-1 text-xs bg-indigo-100 text-indigo-800 px-1.5 py-0.5 rounded-full">
                                            {{ $childrenCount }}
                                        </span>
                                    @endif
                                </button>
                            @else
                                <span style="font-size: 10px;" class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center border-x">
                            <div class="text-gray-400">
                                <i class="fas fa-scale-balanced text-3xl mb-3"></i>
                                <p style="font-size: 13px;" class="font-medium mb-1">Aucun compte trouvé</p>
                                <p style="font-size: 13px;" class="text-gray-500">
                                    Vérifiez vos filtres ou créez des mappings de comptes
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                
                @if(count($balances) > 0 && $comptesAffiches === 0)
                <tr>
                    <td colspan="9" class="px-6 py-8 text-center border-x">
                        <div class="text-gray-400">
                            <i class="fas fa-filter text-3xl mb-3"></i>
                            <p style="font-size: 13px;" class="font-medium mb-1">Aucun compte avec activité dans cette période</p>
                            <p style="font-size: 13px;" class="text-gray-500">
                                Modifiez vos dates de filtre
                            </p>
                        </div>
                    </td>
                </tr>
                @endif
            </tbody>
            
            @if($comptesAffiches > 0)
                @php
                    $totalDebitGeneral = arrondirBalance($totalOuvertureDebit + $totalMouvementDebit);
                    $totalCreditGeneral = arrondirBalance($totalOuvertureCredit + $totalMouvementCredit);
                    $equilibreGeneral = sontEgauxBalance($totalDebitGeneral, $totalCreditGeneral);
                @endphp
                
                <tfoot class="bg-gray-50 font-bold border-t">
                    <tr>
                        <td colspan="2" style="font-size: 13px;" class="px-6 py-2 text-right text-gray-700 border-x">
                            TOTAUX ({{ $comptesAffiches }} comptes)
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalOuvertureDebit, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalOuvertureCredit, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalMouvementDebit, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalMouvementCredit, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalClotureDebit, 0, ',', ' ') }}
                        </td>
                        <td style="font-size: 13px;" class="px-6 py-2 text-right text-black-600 border-x">
                            {{ number_format($totalClotureCredit, 0, ',', ' ') }}
                        </td>
                        <td class="border-x"></td>
                    </tr>
                    <tr class="bg-blue-50">
                        <td colspan="9" class="px-6 py-2 text-center border-x">
                            <span style="font-size: 12px;" class="font-medium text-blue-800">
                                ÉQUILIBRE: (A + C) = (B + D) → 
                                {{ number_format($totalDebitGeneral, 0, ',', ' ') }} = {{ number_format($totalCreditGeneral, 0, ',', ' ') }}
                                @if($equilibreGeneral)
                                    <i class="fas fa-check-circle text-green-600 ml-2"></i>
                                @else
                                    <i class="fas fa-exclamation-triangle text-red-600 ml-2"></i>
                                @endif
                            </span>
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>