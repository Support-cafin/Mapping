{{-- resources/views/livewire/balances/manage-balances.blade.php --}}
<div class="p-6 space-y-6 mt-12 bg-gray-50 min-h-screen">
    <div class="bg-white rounded-lg shadow-sm border p-4">
        <h1 class="text-2xl font-bold text-gray-800 mb-6">Gestion des Balances</h1>
        
        <!-- Onglets -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="flex space-x-4">
                <button wire:click="$set('typeFilter', 'all')"
                    class="px-4 py-2 text-sm font-medium {{ $typeFilter === 'all' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    Toutes les balances
                </button>
                <button wire:click="$set('typeFilter', 'old')"
                    class="px-4 py-2 text-sm font-medium {{ $typeFilter === 'old' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    Anciennes balances
                </button>
                <button wire:click="$set('typeFilter', 'new')"
                    class="px-4 py-2 text-sm font-medium {{ $typeFilter === 'new' ? 'border-b-2 border-blue-500 text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">
                    Nouvelles balances
                </button>
            </nav>
        </div>

        <!-- Formulaire d'ajout manuel -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-blue-800 mb-4">Ajouter une balance manuellement</h3>
            
            <form wire:submit.prevent="saveBalance" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Type de balance -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select wire:model.live="balanceType" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="old">Ancien compte</option>
                            <option value="new">Nouveau compte</option>
                        </select>
                    </div>
                    
                    <!-- Compte -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Compte</label>
                        <select wire:model="account_id" 
                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Sélectionner un compte</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- Période -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                        <input type="text" wire:model="periode" 
                               placeholder="MM (ex: 01, 02...)"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    
                    <!-- Exercice -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Exercice</label>
                        <input type="number" wire:model="exercice" 
                               min="2000" max="2100"
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                </div>
                
                <!-- Montants -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Débit</label>
                        <input type="number" step="0.01" wire:model="debit" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Crédit</label>
                        <input type="number" step="0.01" wire:model="credit" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Solde</label>
                        <input type="number" step="0.01" wire:model="solde" 
                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>
                </div>
                
                <!-- Bouton d'ajout -->
                <div class="flex justify-end">
                    <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Ajouter la balance
                    </button>
                </div>
            </form>
        </div>

        <!-- Import Excel -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
            <h3 class="font-semibold text-green-800 mb-4">Importer des balances depuis Excel</h3>
            
            <div class="space-y-4">
                <div class="flex items-center space-x-4">
                    <select wire:model="importType" 
                            class="border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500">
                        <option value="old">Anciennes balances</option>
                        <option value="new">Nouvelles balances</option>
                    </select>
                    
                    <div class="flex-1">
                        <input type="file" wire:model="excelFile" 
                               accept=".xlsx,.xls,.csv"
                               class="w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-green-50 file:text-green-700
                                      hover:file:bg-green-100">
                    </div>
                    
                    <div class="flex space-x-2">
                        <button wire:click="downloadTemplate('old')"
                                class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                            Template ancien
                        </button>
                        <button wire:click="downloadTemplate('new')"
                                class="px-4 py-2 text-sm bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200">
                            Template nouveau
                        </button>
                        <button wire:click="importBalances" 
                                wire:loading.attr="disabled"
                                wire:target="importBalances"
                                class="px-6 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 
                                       disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="importBalances">Importer</span>
                            <span wire:loading wire:target="importBalances">
                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                        </button>
                    </div>
                </div>
                
                <!-- Progression et erreurs -->
                @if($importing)
                    <div class="mt-4">
                        <div class="flex justify-between text-sm text-gray-600 mb-1">
                            <span>Importation en cours...</span>
                            <span>{{ $importProgress }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full transition-all duration-300" 
                                 style="width: {{ $importProgress }}%"></div>
                        </div>
                    </div>
                @endif
                
                @if($importSuccess)
                    <div class="mt-4 p-3 bg-green-100 text-green-800 rounded-md">
                        ✓ Importation réussie !
                    </div>
                @endif
                
                @if(count($importErrors) > 0)
                    <div class="mt-4 p-3 bg-red-100 text-red-800 rounded-md">
                        <h4 class="font-semibold mb-2">Erreurs d'importation :</h4>
                        <ul class="text-sm space-y-1">
                            @foreach($importErrors as $error)
                                <li>• {{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        <!-- Filtres -->
        <div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Exercice</label>
                    <select wire:model.live="exerciceFilter" 
                            class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tous</option>
                        @foreach(range(date('Y'), date('Y') - 5) as $year)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Période</label>
                    <select wire:model.live="periodeFilter" 
                            class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Toutes</option>
                        @foreach(range(1, 12) as $month)
                            <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}">
                                {{ str_pad($month, 2, '0', STR_PAD_LEFT) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                    <input type="text" wire:model.live.debounce.300ms="search" 
                           placeholder="Rechercher par numéro de compte..."
                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </div>

        <!-- Tableaux des balances -->
        @if($typeFilter === 'all' || $typeFilter === 'old')
            <div class="mb-8">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Anciennes balances</h3>
                    @if($oldBalances->count() > 0)
                        <button wire:click="deleteAllBalances('old')"
                                wire:confirm="Êtes-vous sûr de vouloir supprimer toutes les anciennes balances ?"
                                class="px-4 py-2 text-sm bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                            Tout supprimer
                        </button>
                    @endif
                </div>
                
                @if($oldBalances->count() > 0)
                    <div class="overflow-x-auto bg-white rounded-lg shadow-sm border">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compte</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Débit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crédit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solde</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exercice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($oldBalances as $balance)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="font-mono text-blue-700">{{ $balance->oldAccount->code ?? 'N/A' }}</div>
                                            <div class="text-sm text-gray-500">{{ $balance->oldAccount->intitule ?? '' }}</div>
                                        </td>
                                        <td class="px-6 py-4 font-mono">{{ number_format($balance->debit, 2, ',', ' ') }}</td>
                                        <td class="px-6 py-4 font-mono">{{ number_format($balance->credit, 2, ',', ' ') }}</td>
                                        <td class="px-6 py-4 font-mono font-semibold {{ $balance->solde >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($balance->solde, 2, ',', ' ') }}
                                        </td>
                                        <td class="px-6 py-4">{{ $balance->periode }}</td>
                                        <td class="px-6 py-4">{{ $balance->exercice }}</td>
                                        <td class="px-6 py-4">
                                            <button wire:click="deleteBalance('old', {{ $balance->id }})"
                                                    wire:confirm="Supprimer cette balance ?"
                                                    class="text-sm px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="px-6 py-3 border-t">
                            {{ $oldBalances->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                        <p class="text-gray-500">Aucune ancienne balance trouvée</p>
                    </div>
                @endif
            </div>
        @endif

        @if($typeFilter === 'all' || $typeFilter === 'new')
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Nouvelles balances</h3>
                    @if($newBalances->count() > 0)
                        <button wire:click="deleteAllBalances('new')"
                                wire:confirm="Êtes-vous sûr de vouloir supprimer toutes les nouvelles balances ?"
                                class="px-4 py-2 text-sm bg-red-100 text-red-700 rounded-md hover:bg-red-200">
                            Tout supprimer
                        </button>
                    @endif
                </div>
                
                @if($newBalances->count() > 0)
                    <div class="overflow-x-auto bg-white rounded-lg shadow-sm border">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compte</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Débit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Crédit</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Solde</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Période</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exercice</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($newBalances as $balance)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4">
                                            <div class="font-mono text-blue-700">{{ $balance->newAccount->code ?? 'N/A' }}</div>
                                            <div class="text-sm text-gray-500">{{ $balance->newAccount->intitule ?? '' }}</div>
                                        </td>
                                        <td class="px-6 py-4 font-mono">{{ number_format($balance->debit, 2, ',', ' ') }}</td>
                                        <td class="px-6 py-4 font-mono">{{ number_format($balance->credit, 2, ',', ' ') }}</td>
                                        <td class="px-6 py-4 font-mono font-semibold {{ $balance->solde >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ number_format($balance->solde, 2, ',', ' ') }}
                                        </td>
                                        <td class="px-6 py-4">{{ $balance->periode }}</td>
                                        <td class="px-6 py-4">{{ $balance->exercice }}</td>
                                        <td class="px-6 py-4">
                                            <button wire:click="deleteBalance('new', {{ $balance->id }})"
                                                    wire:confirm="Supprimer cette balance ?"
                                                    class="text-sm px-3 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                                Supprimer
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="px-6 py-3 border-t">
                            {{ $newBalances->links() }}
                        </div>
                    </div>
                @else
                    <div class="text-center py-12 bg-white rounded-lg border border-gray-200">
                        <p class="text-gray-500">Aucune nouvelle balance trouvée</p>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>