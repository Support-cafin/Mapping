@if(!$donateur)
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="p-6 text-center">
            <i class="fas fa-user-slash text-4xl text-gray-300 mb-3"></i>
            <h2 class="text-lg font-semibold text-gray-700 mb-2">Donateur non trouvé</h2>
            <p class="text-gray-500 mb-4">
                Le donateur que vous recherchez n'existe pas ou vous n'y avez pas accès.
            </p>
            <a href="{{ route('donateurs.index') }}" 
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour à la liste des donateurs
            </a>
        </div>
    </div>
@else
<div class="container mx-auto px-4 py-8">
    <!-- Bouton retour -->
    <div class="mb-6">
        <a href="{{ route('donateurs.index') }}" 
           class="inline-flex items-center text-blue-600 hover:text-blue-800">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Retour à la liste
        </a>
    </div>

    <!-- En-tête de la fiche -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Fiche Donateur</h1>
                <p class="text-gray-600">{{ $donateur->numero_enregistrement }}</p>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Badge statut -->
                <!-- Boutons d'action -->
                <div class="flex space-x-2">
                     <a href="{{ route('donateurs.index') }}" 
                       class="inline-flex items-center text-blue-600 hover:text-blue-800">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Retour à la liste
                    </a>
                    <a href="{{ route('donateurs.edit', $donateur) }}" 
                       class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Modifier
                    </a>
                    <button wire:click="imprimerFiche" 
                            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimer
                    </button>
                </div>
            </div>
        </div>

        <!-- Informations principales -->
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Colonne gauche : Informations du donateur -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Informations du donateur</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Dénomination</label>
                            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $donateur->denomination }}</p>
                        </div>

                        @if($donateur->nom_prenoms)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Nom et prénoms</label>
                            <p class="mt-1 text-gray-900">{{ $donateur->nom_prenoms }}</p>
                        </div>
                        @endif

                        @if($donateur->registre_commerce)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Registre de commerce</label>
                            <p class="mt-1 text-gray-900">{{ $donateur->registre_commerce }}</p>
                        </div>
                        @endif

                        @if($donateur->numero_identification_fiscal)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Numéro d'identification fiscal</label>
                            <p class="mt-1 text-gray-900">{{ $donateur->numero_identification_fiscal }}</p>
                        </div>
                        @endif

                        @if($donateur->adresse_siege_social)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Adresse siège social</label>
                            <p class="mt-1 text-gray-900 whitespace-pre-line">{{ $donateur->adresse_siege_social }}</p>
                        </div>
                        @endif

                        @if($donateur->email && is_array($donateur->email) && count($donateur->email) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Email(s)</label>
                            <div class="mt-1">
                                @foreach($donateur->email as $email)
                                    <p class="text-gray-900">{{ $email }}</p>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Colonne droite : Détails du don -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 pb-2 border-b">Détails du don</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Date du don</label>
                            <p class="mt-1 text-gray-900 font-semibold">
                                {{ $donateur->date->format('d/m/Y') }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Montant</label>
                            <p class="mt-1 text-2xl font-bold text-green-600">
                                {{ number_format($donateur->montant_don, 0, ',', ' ') }} {{ $donateur->devise }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Mode de libération</label>
                            <span class="mt-1 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                {{ $donateur->mode_liberation === 'virement' ? 'bg-blue-100 text-blue-800' : 
                                   ($donateur->mode_liberation === 'chèque' ? 'bg-green-100 text-green-800' : 
                                   ($donateur->mode_liberation === 'espèces' ? 'bg-yellow-100 text-yellow-800' : 
                                   'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($donateur->mode_liberation) }}
                            </span>
                        </div>

                        @if($donateur->compte)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Compte comptable</label>
                            <p class="mt-1 text-gray-900">
                                {{ $donateur->compte->numero }} - {{ $donateur->compte->libelle }}
                            </p>
                        </div>
                        @endif

                        <div>
                            <label class="block text-sm font-medium text-gray-500">Signature du représentant</label>
                            <div class="mt-1 flex items-center">
                                @if($donateur->signature_representant)
                                    <span class="inline-flex items-center text-green-600">
                                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Signé
                                    </span>
                                @else
                                    <span class="inline-flex items-center text-gray-400">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                        Non signé
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($donateur->notes)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Notes</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                                <p class="text-gray-900 whitespace-pre-line">{{ $donateur->notes }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Informations d'audit -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-500 mb-4">Informations de suivi</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-400">Créé le</label>
                        <p class="mt-1 text-sm text-gray-900">
                            {{ $donateur->created_at->format('d/m/Y H:i') }}
                        </p>
                        @if($donateur->createur)
                        <p class="text-xs text-gray-500">par {{ $donateur->createur->name }}</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-400">Modifié le</label>
                        <p class="mt-1 text-sm text-gray-900">
                            {{ $donateur->updated_at->format('d/m/Y H:i') }}
                        </p>
                        @if($donateur->modificateur)
                        <p class="text-xs text-gray-500">par {{ $donateur->modificateur->name }}</p>
                        @endif
                    </div>
                    <!--<div>
                        <label class="block text-xs font-medium text-gray-400">Dernier statut</label>
                        <p class="mt-1 text-sm font-medium 
                            {{ $donateur->statut === 'comptabilisé' ? 'text-green-600' : 
                               ($donateur->statut === 'validé' ? 'text-yellow-600' : 
                               ($donateur->statut === 'annulé' ? 'text-red-600' : 
                               'text-blue-600')) }}">
                            {{ ucfirst($donateur->statut) }}
                        </p>
                    </div>-->
                </div>
            </div>
        </div>
    </div>

    <!-- Section Écriture Comptable (si comptabilisé) -->
    @if($donateur->statut === 'comptabilisé' && $ecriture)
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="px-6 py-4 bg-green-50 border-b border-green-200">
            <h2 class="text-lg font-semibold text-green-800 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Écriture Comptable Générée
            </h2>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Compte
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Libellé
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Débit
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Crédit
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($ecriture->lignes as $ligne)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $ligne->compte->numero }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $ligne->compte->libelle }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($ligne->debit > 0)
                                    {{ number_format($ligne->debit, 0, ',', ' ') }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if($ligne->credit > 0)
                                    {{ number_format($ligne->credit, 0, ',', ' ') }}
                                @endif
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-50 font-semibold">
                            <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                Total
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($ecriture->lignes->sum('debit'), 0, ',', ' ') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ number_format($ecriture->lignes->sum('credit'), 0, ',', ' ') }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-4 text-sm text-gray-500">
                Journal : {{ $ecriture->journal->libelle ?? 'Non spécifié' }} | 
                N° pièce : {{ $ecriture->numero_piece ?? 'N/A' }} | 
                Date comptable : {{ $ecriture->date->format('d/m/Y') }}
            </div>
        </div>
    </div>
    @endif

    <!-- Actions de changement de statut -->
   <!-- <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Actions</h2>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3">
                @if($donateur->statut === 'enregistré')
                    <button wire:click="changerStatut('validé')" 
                            class="px-4 py-2 bg-yellow-100 text-yellow-700 rounded-lg hover:bg-yellow-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Valider le don
                    </button>
                @endif

                @if($donateur->statut === 'validé')
                    <button wire:click="changerStatut('comptabilisé')" 
                            class="px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Comptabiliser
                    </button>
                @endif

                @if(in_array($donateur->statut, ['enregistré', 'validé']))
                    <button wire:click="changerStatut('annulé')" 
                            class="px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        Annuler le don
                    </button>
                @endif

                @if($donateur->statut === 'annulé')
                    <button wire:click="changerStatut('enregistré')" 
                            class="px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        Réactiver
                    </button>
                @endif
            </div>
        </div>
    </div> -->
</div>
<!-- Modal de confirmation -->
@if($showConfirmationModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div class="mt-3 text-center">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Confirmation</h3>
                    <div class="mt-2 px-7 py-3">
                        <p class="text-sm text-gray-500">{{ $confirmationMessage }}</p>
                    </div>
                    <div class="items-center px-4 py-3">
                        <div class="flex justify-center space-x-4">
                            <button wire:click="$set('showConfirmationModal', false)"
                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                                Annuler
                            </button>
                            <button wire:click="executeConfirmedAction"
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Confirmer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
    // Ajoutez cette méthode au composant Livewire
    window.addEventListener('donateur:statut-changed', event => {
        alert(event.detail.message);
    });
</script>
@endif
