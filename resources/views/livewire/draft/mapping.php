{{-- resources/views/livewire/mapping/dual-panel.blade.php --}}
<div class="p-6 space-y-6 mt-12 bg-gray-50 min-h-screen">


    <!-- PROGRESSION -->
    <div class="bg-white rounded-lg shadow-sm border p-4" style="height: 80px;">
        <div class="flex justify-between items-center mb-2">
            <span class="text-sm text-gray-600" style="font-size: 9px;">Progression : <strong style="font-size: 9px;">{{ $mapped }} / {{ $total }}</strong> comptes mappés</span>
            <span class="text-lg font-bold text-blue-600" style="font-size: 9px;">{{ $percentage }}%</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3">
            <div class="bg-blue-600 h-2 rounded-full transition-all" style="width: {{ $percentage }}%"></div>
        </div>
    </div>
    

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- === PANEL GAUCHE : ANCIENS COMPTES === -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col">
            <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                <h2 class="font-semibold text-gray-700" style="font-size: 9px;">
                    ENTITE
                    <span class="text-sm text-gray-500" style="font-size: 9px;">({{ $oldRoots->count() }})</span>
                     {{-- (Entité Source) --}}
                    </h2>
                <div class="flex items-center gap-3">
                    <input type="text" wire:model.debounce.300ms="searchOld"
                           class="px-3 py-1.5 text-sm border rounded-md w-64"
                           placeholder="Rechercher..." style="font-size: 9px; height: 30px;">
                    
                    <!-- Pour les anciens comptes -->
                    <div class="flex gap-2 ml-2">
                        <button wire:click="$set('showAddAccountModal', true)" 
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            +
                        </button>
                        <button wire:click="$set('showImportModal', true)" 
                                wire:loading.attr="disabled"
                                type="button"
                                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                            📁
                        </button>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-blue-200 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-600" style="font-size: 9px;">N° Compte</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-600" style="font-size: 9px;">Intitulé</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @include('livewire.mapping.table.tree-old', ['nodes' => $oldRoots, 'level' => 0])
                    </tbody>
                </table>
            </div>
        </div>

        <!-- === PANEL DROIT : NOUVEAUX COMPTES === -->
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden flex flex-col">
            <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                <h2 class="font-semibold text-gray-700" style="font-size: 9px;">
                    SYCEBNL
                    <span class="text-sm text-gray-500" style="font-size: 9px;">({{ $newRoots->count() }})</span>
                </h2>
                <div class="flex items-center gap-3">
                    <input type="text" wire:model.debounce.300ms="searchNew"
                           class="px-3 py-1.5 text-sm border rounded-md w-64"
                           placeholder="Rechercher..." style="font-size: 9px; height: 30px;">
                                        
                    <!-- Pour les nouveaux comptes (identique mais on va pré-remplir le type) -->
                  <div class="flex gap-2 ml-2">
                        <button wire:click.prevent="openAddModal('new')" 
                                type="button"
                                class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            +
                        </button>
                        <button wire:click.prevent="openImportModal('new')" 
                                type="button"
                                class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                            📁
                        </button>                                       
                    </div>
                </div>
            </div>

            @if($selectedOldAccount)
                <div class="px-4 py-2 bg-blue-50 border-b text-sm">
                    Sélectionné :
                    <strong style="font-size: 9px;">
                        {{ $selectedOldAccount->code }}
                    </strong>
                    — {{ $selectedOldAccount->intitule }}
                </div>
            @endif


            <div class="flex-1 overflow-y-auto">
                <table class="w-full">
                    <thead class="bg-blue-200 sticky top-0">
                        <tr>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-600" style="font-size: 9px;">N° Compte</th>
                            <th class="text-left px-4 py-2 text-xs font-medium text-gray-600" style="font-size: 9px;">Intitulé</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @include('livewire.mapping.table.tree-new', ['nodes' => $newRoots, 'level' => 0])
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!--section brouillon-->

    <!-- MODAL D'AJOUT DE COMPTE -->
    @if($showAddAccountModal)
    <div wire:key="modal-add-account">
        <div class="fixed inset-0 z-[9999] overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            style="display: block;">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal Container -->
            <div class="fixed inset-0 z-[10000] overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    
                    <!-- Modal Panel -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <!-- Close button (X) en haut à droite -->
                        <button wire:click="closeAddModal" 
                                type="button"
                                class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                            <span class="sr-only">Fermer</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-100">
                                <svg class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900" id="modal-title">
                                    Ajouter un {{ $accountType === 'old' ? 'ancien' : 'nouveau' }} compte
                                </h3>
                                
                                <div class="mt-6 space-y-4 text-left">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Code du compte</label>
                                        <input type="text" 
                                            wire:model="accountCode" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Ex: 411100">
                                        @error('accountCode') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé</label>
                                        <input type="text" 
                                            wire:model="accountIntitule" 
                                            class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Ex: Clients - Vente de marchandises">
                                        @error('accountIntitule') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Compte parent</label>
                                        <select wire:model="accountParentId" 
                                                class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Aucun (compte racine)</option>
                                            @foreach(($accountType === 'old' ? $oldParents : $newParents) as $parent)
                                                <option value="{{ $parent['id'] }}">{{ $parent['text'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="button" 
                                    wire:click.prevent="addAccount" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex w-full justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 disabled:opacity-50 sm:col-start-2">
                                <span wire:loading.remove wire:target="addAccount">Ajouter</span>
                                <span wire:loading wire:target="addAccount">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                            <button type="button" 
                                    wire:click.prevent="closeAddModal" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- MODAL D'IMPORT EXCEL -->
    @if($showImportModal)
    <div wire:key="modal-import-account">
        <div class="fixed inset-0 z-[9999] overflow-y-auto" 
            aria-labelledby="modal-title" 
            role="dialog" 
            aria-modal="true"
            style="display: block;">
            
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <!-- Modal Container -->
            <div class="fixed inset-0 z-[10000] overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    
                    <!-- Modal Panel -->
                    <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                        
                        <!-- Close button (X) -->
                        <button wire:click="closeImportModal" 
                                type="button"
                                class="absolute top-4 right-4 text-gray-400 hover:text-gray-500">
                            <span class="sr-only" style="font-size: 9px;">Fermer</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>

                        <div>
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
                                <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                            </div>
                            
                            <div class="mt-3 text-center sm:mt-5">
                                <h3 class="text-lg font-semibold leading-6 text-gray-900" style="font-size: 9px;">
                                    Importer des {{ $importType === 'old' ? 'anciens' : 'nouveaux' }} comptes
                                </h3>
                                
                                <div class="mt-6 space-y-4 text-left">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2" style="font-size: 9px;">Fichier Excel</label>
                                        <input type="file" 
                                            wire:model="excelFile" 
                                            accept=".xlsx,.xls,.csv"
                                            class="w-full text-sm text-gray-500
                                                    file:mr-4 file:py-2 file:px-4
                                                    file:rounded-full file:border-0
                                                    file:text-sm file:font-semibold
                                                    file:bg-green-50 file:text-green-700
                                                    hover:file:bg-green-100">
                                        <p class="mt-2 text-xs text-gray-500" style="font-size: 9px;">
                                            Formats supportés: .xlsx, .xls, .csv
                                        </p>
                                        @error('excelFile') <span class="text-red-500 text-xs" style="font-size: 9px;">{{ $message }}</span> @enderror
                                    </div>
                                    
                                    <div class="flex items-center justify-between">
                                        <button wire:click.prevent="downloadTemplate('{{ $importType }}')"
                                                type="button"
                                                class="text-sm px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200" style="font-size: 9px;">
                                            📥 Télécharger le template
                                        </button>
                                    </div>
                                    
                                    @if($importing)
                                        <div class="mt-4">
                                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                                <span style="font-size: 9px;">Importation en cours...</span>
                                            </div>
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-green-600 h-2 rounded-full animate-pulse" style="width: 100%"></div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($importSuccess)
                                        <div class="mt-4 p-3 bg-green-100 text-green-800 rounded-md" style="font-size: 9px;">
                                            ✓ Importation réussie !
                                        </div>
                                    @endif
                                    
                                    @if(count($importErrors) > 0)
                                        <div class="mt-4 p-3 bg-red-100 text-red-800 rounded-md">
                                            <h4 class="font-semibold mb-2" style="font-size: 9px;">Erreurs d'importation :</h4>
                                            <ul class="text-sm space-y-1 max-h-32 overflow-y-auto" style="font-size: 9px;">
                                                @foreach($importErrors as $error)
                                                    <li>• {{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-5 sm:mt-6 sm:grid sm:grid-flow-row-dense sm:grid-cols-2 sm:gap-3">
                            <button type="button" 
                                    wire:click.prevent="importAccounts" 
                                    wire:loading.attr="disabled"
                                    class="inline-flex w-full justify-center rounded-md bg-green-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-green-500 disabled:opacity-50 sm:col-start-2">
                                <span wire:loading.remove wire:target="importAccounts" style="font-size: 9px;">Importer</span>
                                <span wire:loading wire:target="importAccounts">
                                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </span>
                            </button>
                            <button type="button" 
                                    wire:click.prevent="closeImportModal" 
                                    class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:col-start-1 sm:mt-0" style="font-size: 9px;">
                                Annuler
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Confirmation remap - 100% Tailwind, sans SweetAlert2 --}}
    <div x-data="{ showConfirm: false, pendingOldId: null, currentNew: '' }"
         x-show="showConfirm"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="fixed inset-0 z-[99999] flex items-center justify-center"
         style="display: none;"
         @confirm-remap.window="
             showConfirm = true;
             pendingOldId = $event.detail.oldId;
             currentNew = $event.detail.newCode + ' - ' + $event.detail.newIntitule;
         "
         @keydown.escape.window="showConfirm = false">
    
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black bg-opacity-50" @click="showConfirm = false"></div>
    
        <!-- Modal -->
        <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 p-6">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3.01L12.732 4.01c-.77-1.333-2.694-1.333-3.464 0L3.34 16.99c-.77 1.333.192 3.01 1.732 3.01z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-lg font-semibold text-gray-900">Compte déjà mappé</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        Ce compte ancien est déjà mappé
                    </p>
                    <!--<p class="mt-2 text-sm font-bold text-blue-700 bg-blue-50 px-3 py-2 rounded-lg inline-block">-->
                    <!--    <span x-text="currentNew"></span>-->
                    <!--</p>-->
                    <p class="mt-3 text-sm text-gray-700">
                        Voulez-vous <strong>remplacer</strong> ce mapping ?
                    </p>
                </div>
            </div>
    
            <div class="mt-6 flex justify-end gap-3">
                <button @click="showConfirm = false; $wire.set('selectedOld', null)"
                        class="px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                    Non, annuler
                </button>
                <button @click="
                    showConfirm = false;
                    $wire.call('confirmRemap', pendingOldId)
                "
                        class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shadow-md">
                    Oui, changer
                </button>
            </div>
        </div>
    </div>
    {{-- Scroll fluide + effet visuel quand on confirme le remap --}}
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('scroll-to-old', (accountId) => {
                setTimeout(() => {
                    const row = document.querySelector(`tr[wire\\:click="selectOld(${accountId})"]`);
                    if (row) {
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
                        // Effet flash bleu
                        row.classList.add('bg-blue-200', 'ring-4', 'ring-blue-600');
                        setTimeout(() => {
                            row.classList.remove('bg-blue-200', 'ring-4', 'ring-blue-600');
                        }, 1500);
                    }
                }, 150);
            });
        });
    </script>
</div>





















/// composant
<?php
namespace App\Livewire\Mapping;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\OldAccount;
use App\Models\NewAccount;
use App\Models\AccountMapping;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\OldAccountsImport;
use App\Imports\NewAccountsImport;

class DualPanel extends Component
{
    use WithFileUploads;
    
    public array $mappedToSelectedOld = [];

    public $entreprise;

    public $searchOld = '';
    public $searchNew = '';

    public $selectedOld = null;
    public $selectedOldAccount = null;

    public $expandedOld = [];
    public $expandedNew = [];

    public $oldRoots = [];
    public $newRoots = [];

    public $mappings = [];
    public $mappedOldIds = [];

    public $total = 0;
    public $mapped = 0;
    public $percentage = 0;

    // Pour l'ajout manuel de comptes
    public $showAddAccountModal = false;
    public $accountType = 'old'; // 'old' ou 'new'
    public $accountCode = '';
    public $accountIntitule = '';
    public $accountClasse = '';
    public $accountGroupe = '';
    public $accountParentId = null;
    
    // Pour l'import Excel
    public $showImportModal = false;
    public $importType = 'old'; // 'old' ou 'new'
    public $excelFile;
    public $importing = false;
    public $importErrors = [];
    public $importSuccess = false;

    // Filtres
    public $filterClass = '';
    public $filterGroup = '';
    public $filterLevel = '';

    // Liste des parents pour le select
    public $oldParents = [];
    public $newParents = [];

    public function mount()
    {
        $this->entreprise = auth()->user()->entreprise;
        $this->loadData();
        $this->loadParentLists();
    }

    public function loadData()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
        $this->loadMappings();
        $this->refreshStats();
    }

    public function loadOldAccounts()
    {
        $query = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->with('children')
            ->orderBy('code');

        if ($this->searchOld) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->searchOld}%")
                  ->orWhere('intitule', 'like', "%{$this->searchOld}%");
            });
        }

        if ($this->filterClass) {
            $query->where('classe', $this->filterClass);
        }

        if ($this->filterGroup) {
            $query->where('groupe', $this->filterGroup);
        }

        if ($this->filterLevel) {
            $query->where('niveau', $this->filterLevel);
        }

        $this->oldRoots = $query->whereNull('parent_id')->get();
    }

    public function loadNewAccounts()
    {
        $query = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->with('children')
            ->orderBy('code');

        if ($this->searchNew) {
            $query->where(function ($q) {
                $q->where('code', 'like', "%{$this->searchNew}%")
                  ->orWhere('intitule', 'like', "%{$this->searchNew}%");
            });
        }

        if ($this->filterClass) {
            $query->where('classe', $this->filterClass);
        }

        if ($this->filterGroup) {
            $query->where('groupe', $this->filterGroup);
        }

        if ($this->filterLevel) {
            $query->where('niveau', $this->filterLevel);
        }

        $this->newRoots = $query->whereNull('parent_id')->get();
    }

    public function loadMappings()
    {
        $this->mappings = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->with(['oldAccount', 'newAccount'])
            ->get()
            ->sortBy(function($mapping) {
                return $mapping->oldAccount->code ?? 0;
                
            })
            ->values(); // Réindexer le tableau après le tri

        $this->mappedOldIds = $this->mappings->pluck('old_account_id')->toArray();
    }

    public function loadParentLists()
    {
        $this->oldParents = OldAccount::where('entreprise_id', $this->entreprise->id)
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get(['id', 'code', 'intitule'])
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'text' => "{$account->code} - {$account->intitule}"
                ];
            })->toArray();

        $this->newParents = NewAccount::where('entreprise_id', $this->entreprise->id)
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get(['id', 'code', 'intitule'])
            ->map(function ($account) {
                return [
                    'id' => $account->id,
                    'text' => "{$account->code} - {$account->intitule}"
                ];
            })->toArray();
    }

    // Ajout manuel d'un compte
    public function addAccount()
    {
        $this->validate([
            'accountType' => 'required|in:old,new',
            'accountCode' => 'required|string|max:20',
            'accountIntitule' => 'required|string|max:255',
            'accountClasse' => 'nullable|string|max:10',
            'accountGroupe' => 'nullable|string|max:10',
            'accountParentId' => 'nullable|exists:' . ($this->accountType === 'old' ? 'old_accounts' : 'new_accounts') . ',id',
        ]);

        $data = [
            'entreprise_id' => $this->entreprise->id,
            'code' => $this->accountCode,
            'intitule' => $this->accountIntitule,
            'classe' => $this->accountClasse,
            'groupe' => $this->accountGroupe,
            'parent_id' => $this->accountParentId,
        ];

        if ($this->accountType === 'old') {
            // Vérifier si le code existe déjà
            $exists = OldAccount::where('entreprise_id', $this->entreprise->id)
                ->where('code', $this->accountCode)
                ->exists();
            
            if ($exists) {
                session()->flash('notify', ['type' => 'error', 'message' => 'Ce code de compte existe déjà !']);
                return;
            }
            
            OldAccount::create($data);
        } else {
            $exists = NewAccount::where('entreprise_id', $this->entreprise->id)
                ->where('code', $this->accountCode)
                ->exists();
            
            if ($exists) {
                session()->flash('notify', ['type' => 'error', 'message' => 'Ce code de compte existe déjà !']);
                return;
            }
            
            NewAccount::create($data);
        }

        $this->resetAccountForm();
        $this->loadData();
        $this->loadParentLists();
        $this->showAddAccountModal = false;
        
        session()->flash('notify', ['type' => 'success', 'message' => 'Compte ajouté avec succès !']);
    }

    // Import Excel
// Dans DualPanel.php - Remplacez la méthode importAccounts()

public function importAccounts()
{
    $this->validate([
        'importType' => 'required|in:old,new',
        'excelFile' => 'required|file|mimes:xlsx,xls,csv|max:10240',
    ]);

    $this->importing = true;
    $this->importErrors = [];
    $this->importSuccess = false;

    try {
        \Illuminate\Support\Facades\Log::info('Starting import', [
            'type' => $this->importType,
            'file' => $this->excelFile->getClientOriginalName(),
            'entreprise_id' => $this->entreprise->id
        ]);

        $importClass = $this->importType === 'old' 
            ? new \App\Imports\OldAccountsImport($this->entreprise->id)
            : new \App\Imports\NewAccountsImport($this->entreprise->id);

        // Import avec gestion d'erreurs
        Excel::import($importClass, $this->excelFile->getRealPath());

        $importedCount = $importClass->getImportedCount();
        $errors = $importClass->getErrors();

        \Illuminate\Support\Facades\Log::info('Import finished', [
            'imported' => $importedCount,
            'errors_count' => count($errors)
        ]);

        // Vérification des résultats
        if ($importedCount === 0) {
            if (empty($errors)) {
                $this->importErrors[] = "❌ Aucun compte importé. Vérifiez le format de votre fichier :";
                $this->importErrors[] = "• La première ligne doit contenir les en-têtes : code, intitule, parent_code";
                $this->importErrors[] = "• Les lignes suivantes doivent contenir les données";
                $this->importErrors[] = "• Le fichier ne doit pas être vide";
                
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Aucun compte importé. Vérifiez le format du fichier.'
                ]);
            } else {
                $this->importErrors = $errors;
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'Erreurs d\'importation détectées. Consultez les détails.'
                ]);
            }
            $this->importing = false;
            return;
        }

        // Succès
        $this->importSuccess = true;
        $this->importErrors = $errors;

        // Recharger les données
        $this->loadData();
        $this->loadParentLists();

        $message = "✓ $importedCount compte(s) importé(s) avec succès !";
        if (!empty($errors)) {
            $message .= " ⚠️ " . count($errors) . " avertissement(s).";
        }

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => $message
        ]);

        // Fermer le modal après 2 secondes en cas de succès total
        if (empty($errors)) {
            $this->dispatch('close-modal-after-delay');
        }

    } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
        $failures = $e->failures();
        
        foreach ($failures as $failure) {
            $this->importErrors[] = "Ligne {$failure->row()}: " . implode(', ', $failure->errors());
        }
        
        \Illuminate\Support\Facades\Log::error('Import validation failed', [
            'failures' => $failures
        ]);
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreurs de validation. Vérifiez les détails.'
        ]);
        
    } catch (\Exception $e) {
        $errorMessage = $e->getMessage();
        $this->importErrors[] = "❌ Erreur d'importation: " . $errorMessage;
        
        \Illuminate\Support\Facades\Log::error('Import failed', [
            'error' => $errorMessage,
            'trace' => $e->getTraceAsString()
        ]);
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erreur lors de l\'importation: ' . $errorMessage
        ]);
    }

    $this->importing = false;
}


    public function downloadTemplate($type)
    {
        $filename = $type === 'old' ? 'template_old_accounts.xlsx' : 'template_new_accounts.xlsx';
        $filepath = storage_path('app/templates/' . $filename);
        
        if (!file_exists($filepath)) {
            $this->createAccountsTemplateFile($type);
        }
        
        return response()->download($filepath);
    }
    
// Dans DualPanel.php - Remplacez la méthode createAccountsTemplateFile()

private function createAccountsTemplateFile($type)
{
    // Créer le répertoire s'il n'existe pas
    $dir = storage_path('app/templates');
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }

    $filename = $type === 'old' 
        ? 'template_old_accounts.xlsx' 
        : 'template_new_accounts.xlsx';
    
    $filepath = storage_path('app/templates/' . $filename);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // En-têtes (IMPORTANT: exactement ces noms)
    $sheet->setCellValue('A1', 'code');
    $sheet->setCellValue('B1', 'intitule');
    $sheet->setCellValue('C1', 'parent_code');

    // Style des en-têtes
    $headerStyle = [
        'font' => [
            'bold' => true,
            'color' => ['rgb' => 'FFFFFF'],
            'size' => 12
        ],
        'fill' => [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => '4F46E5']
        ],
        'alignment' => [
            'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        ]
    ];
    $sheet->getStyle('A1:C1')->applyFromArray($headerStyle);

    // Exemples de données
    $examples = [
        ['1', 'CAPITAUX', ''],
        ['10', 'Capital', '1'],
        ['101', 'Capital social', '10'],
        ['1011', 'Capital souscrit appelé versé', '101'],
        ['2', 'IMMOBILISATIONS', ''],
        ['21', 'Immobilisations corporelles', '2'],
        ['211', 'Terrains', '21'],
        ['4', 'COMPTES DE TIERS', ''],
        ['41', 'Clients et comptes rattachés', '4'],
        ['411', 'Clients', '41'],
        ['4111', 'Clients - ventes de biens', '411'],
    ];

    $row = 2;
    foreach ($examples as $example) {
        $sheet->setCellValue('A' . $row, $example[0]);
        $sheet->setCellValue('B' . $row, $example[1]);
        $sheet->setCellValue('C' . $row, $example[2]);
        
        // Style alterné pour les lignes
        if ($row % 2 == 0) {
            $sheet->getStyle('A' . $row . ':C' . $row)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F3F4F6');
        }
        
        $row++;
    }

    // Ajuster la largeur des colonnes
    $sheet->getColumnDimension('A')->setWidth(15);
    $sheet->getColumnDimension('B')->setWidth(40);
    $sheet->getColumnDimension('C')->setWidth(15);

    // Bordures
    $styleArray = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                'color' => ['rgb' => 'CCCCCC'],
            ],
        ],
    ];
    $sheet->getStyle('A1:C' . ($row - 1))->applyFromArray($styleArray);

    // Note explicative
    $noteRow = $row + 2;
    $sheet->setCellValue('A' . $noteRow, 'NOTES IMPORTANTES:');
    $sheet->getStyle('A' . $noteRow)->getFont()->setBold(true);
    
    $sheet->setCellValue('A' . ($noteRow + 1), '• La première ligne DOIT contenir les en-têtes: code, intitule, parent_code');
    $sheet->setCellValue('A' . ($noteRow + 2), '• Le code est obligatoire (ex: 101, 1011, 411)');
    $sheet->setCellValue('A' . ($noteRow + 3), '• L\'intitulé est obligatoire (ex: Capital social, Clients)');
    $sheet->setCellValue('A' . ($noteRow + 4), '• Le parent_code est optionnel (laissez vide pour les comptes racines)');
    $sheet->setCellValue('A' . ($noteRow + 5), '• Si vous spécifiez un parent_code, il doit exister dans le fichier ou dans la base');
    
    $sheet->mergeCells('A' . ($noteRow + 1) . ':C' . ($noteRow + 1));
    $sheet->mergeCells('A' . ($noteRow + 2) . ':C' . ($noteRow + 2));
    $sheet->mergeCells('A' . ($noteRow + 3) . ':C' . ($noteRow + 3));
    $sheet->mergeCells('A' . ($noteRow + 4) . ':C' . ($noteRow + 4));
    $sheet->mergeCells('A' . ($noteRow + 5) . ':C' . ($noteRow + 5));
    
    $sheet->getStyle('A' . ($noteRow + 1) . ':A' . ($noteRow + 5))->getAlignment()->setWrapText(true);

    // Sauvegarde
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save($filepath);
    
    \Illuminate\Support\Facades\Log::info('Template created', ['path' => $filepath]);
}


    public function resetAccountForm()
    {
        $this->accountCode = '';
        $this->accountIntitule = '';
        $this->accountClasse = '';
        $this->accountGroupe = '';
        $this->accountParentId = null;
    }

    // Méthodes existantes (à conserver)
    public function toggleOld($id)
    {
        if (in_array($id, $this->expandedOld)) {
            $this->expandedOld = array_diff($this->expandedOld, [$id]);
        } else {
            $this->expandedOld[] = $id;
        }
    }

    public function toggleNew($id)
    {
        if (in_array($id, $this->expandedNew)) {
            $this->expandedNew = array_diff($this->expandedNew, [$id]);
        } else {
            $this->expandedNew[] = $id;
        }
    }

public function selectOld($id)
{
    // Toggle sélection
    $this->selectedOld = $id === $this->selectedOld ? null : $id;

    if (!$this->selectedOld) {
        $this->mappedToSelectedOld = [];
        return;
    }

    // Compte sélectionné
    $this->selectedOldAccount = OldAccount::find($id);

    // 🔥 CHARGEMENT UNIQUE DES MAPPINGS (clé performance)
    $this->mappedToSelectedOld = AccountMapping::where('entreprise_id', $this->entreprise->id)
        ->where('old_account_id', $id)
        ->pluck('new_account_id')
        ->toArray();

    // Si déjà mappé → popup confirmation
    if (!empty($this->mappedToSelectedOld)) {
        $mapping = AccountMapping::where('entreprise_id', $this->entreprise->id)
            ->where('old_account_id', $id)
            ->with('newAccount')
            ->first();

        if ($mapping && $mapping->newAccount) {
            $this->dispatch('confirm-remap', [
                'oldId'       => $id,
                'newCode'     => $mapping->newAccount->code,
                'newIntitule' => $mapping->newAccount->intitule,
            ]);
        }
    }
}



    public function confirmRemap($oldId)
    {
        $this->selectedOld = $oldId;
    
        // ON FORCE L'OUVERTURE DES PARENTS + RECHARGE VISUELLE
        $account = OldAccount::find($oldId);
        if ($account) {
            $parentIds = [];
            $current = $account;
    
            while ($current->parent_id) {
                $current = $current->parent;
                if ($current) {
                    $parentIds[] = $current->id;
                }
            }
    
            $this->expandedOld = array_unique(array_merge($this->expandedOld, $parentIds));
        }
    
        // ON FORCE LE RE-RENDER DU TABLEAU GAUCHE POUR QUE LA SÉLECTION APPARAISSE
        $this->loadOldAccounts();
    
        // Message + scroll fluide
        session()->flash('notify', [
            'type' => 'info',
            'message' => 'Compte sélectionné — choisissez maintenant le nouveau compte cible.'
        ]);
    
        // Scroll automatique vers le compte sélectionné
        $this->dispatch('scroll-to-old', $oldId);
    }
    
    public function mapToNew($newId)
    {
        if (!$this->selectedOld) return;
    
        AccountMapping::updateOrCreate(
            [
                'entreprise_id' => $this->entreprise->id,
                'old_account_id' => $this->selectedOld,
            ],
            [
                'new_account_id' => $newId,
                'coefficient' => 1,
                'commentaire' => null,
            ]
        );
    
        // 🔥 Mise à jour locale (ultra rapide)
        $this->mappedToSelectedOld = [$newId];
    
        // Mise à jour stats & liste mappée OLD
        $this->loadMappings();
        $this->refreshStats();
    
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Compte mappé avec succès ✔'
        ]);
    }


    public function deleteMapping($mappingId)
    {
        AccountMapping::find($mappingId)->delete();
        $this->loadData();
    }

    public function resetAll()
    {
        AccountMapping::where('entreprise_id', $this->entreprise->id)->delete();
        $this->loadData();
    }

    public function refreshStats()
    {
        $this->total = OldAccount::where('entreprise_id', $this->entreprise->id)->count();
        $this->mapped = AccountMapping::where('entreprise_id', $this->entreprise->id)->count();
        $this->percentage = $this->total > 0 ? round(($this->mapped / $this->total) * 100) : 0;
    }

    public function updatedSearchOld()
    {
        $this->loadOldAccounts();
    }

    public function updatedSearchNew()
    {
        $this->loadNewAccounts();
    }

    public function updatedFilterClass()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
    }

    public function updatedFilterGroup()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
    }

    // Ajoutez ces méthodes dans votre composant DualPanel.php

    public function openAddModal($type)
    {
        $this->accountType = $type;
        $this->showAddAccountModal = true;
        $this->loadParentLists(); // Recharge les parents pour le select
    }

    public function openImportModal($type)
    {
        $this->importType = $type;
        $this->showImportModal = true;
        $this->importErrors = [];
        $this->importSuccess = false;
    }

    // Dans DualPanel.php - Ajoute cette méthode si elle n'existe pas déjà
    public function closeAddModal()
    {
        $this->showAddAccountModal = false;
        $this->resetAccountForm();
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->excelFile = null;
        $this->importErrors = [];
        $this->importSuccess = false;
    }

    public function updatedFilterLevel()
    {
        $this->loadOldAccounts();
        $this->loadNewAccounts();
    }

    public function updatedAccountType()
    {
        $this->accountParentId = null;
    }

    public function render()
    {
        return view('livewire.mapping.dual-panel')->layout('layouts.app');
    }
}















// table OLD
@foreach($nodes as $node)
@php
    $hasChildren = $node->children->isNotEmpty();
    $isExpanded  = in_array($node->id, $expandedOld);
    $isSelected  = $selectedOld === $node->id;
    $isMapped    = in_array($node->id, $mappedOldIds ?? []);
@endphp

<tr wire:key="old-{{ $node->id }}"
    class="group transition-all duration-150
           {{ $isSelected ? 'bg-blue-100 ring-2 ring-blue-500 ring-inset shadow-sm' : '' }}
           {{ $isMapped && !$isSelected ? 'bg-green-50' : '' }}
           hover:bg-gray-100">

    <!-- CODE -->
    <td class="px-4 py-1 font-mono text-xs"
        style="padding-left: {{ $level * 20 + 16 }}px;">

        <div class="flex items-center gap-2">

            {{-- Expand --}}
            @if($hasChildren)
                <button type="button"
                        wire:click.stop="toggleOld({{ $node->id }})"
                        class="w-4 h-4 flex items-center justify-center
                               text-gray-500 hover:text-gray-800 transition">
                    <svg class="w-3 h-3 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="w-4"></span>
            @endif

            {{-- Select --}}
            <button type="button"
                    wire:click="selectOld({{ $node->id }})"
                    class="flex items-center gap-2 text-left w-full">

                <span class="{{ $isMapped ? 'text-blue-700 font-semibold' : 'text-gray-700' }}">
                    {{ $node->code }}
                </span>
                
                {{-- BADGE ÉTAT --}}
                @if($isSelected)
                    <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-blue-600 text-white">
                        Sélectionné
                    </span>
                @elseif($isMapped)
                    <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-green-600 text-white">
                        Mappé
                    </span>
                @else
                    <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-red-500 text-white">
                        Non mappé
                    </span>
                @endif
            </button>
        </div>
    </td>

    <!-- INTITULÉ -->
    <td class="py-1 pr-4 text-xs text-gray-700">
        <button type="button"
                wire:click="selectOld({{ $node->id }})"
                class="text-left w-full">
            {{ $node->intitule }}
        </button>
    </td>
</tr>

{{-- CHILDREN --}}
@if($isExpanded && $hasChildren)
    @include('livewire.mapping.table.tree-old', [
        'nodes' => $node->children,
        'level' => $level + 1
    ])
@endif
@endforeach










// new
@foreach($nodes as $node)
@php
    $hasChildren = $node->children->isNotEmpty();
    $isExpanded  = in_array($node->id, $expandedNew);
    $isMappedTo  = in_array($node->id, $mappedToSelectedOld);
@endphp

<tr wire:key="new-{{ $node->id }}"
    class="group transition-all duration-150
           hover:bg-blue-50
           {{ $isMappedTo ? 'bg-blue-100 font-medium' : '' }}">

    <!-- CODE -->
    <td class="px-4 py-1 font-mono text-xs"
        style="padding-left: {{ $level * 20 + 16 }}px;">
        <div class="flex items-center gap-2">

            {{-- Expand --}}
            @if($hasChildren)
                <button type="button"
                        wire:click.stop="toggleNew({{ $node->id }})"
                        class="w-4 h-4 flex items-center justify-center
                               text-gray-500 hover:text-gray-800 transition">
                    <svg class="w-3 h-3 transition-transform {{ $isExpanded ? 'rotate-90' : '' }}"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            @else
                <span class="w-4"></span>
            @endif

            <span class="text-blue-700">
                {{ $node->code }}
            </span>
            
            @if($isMappedTo)
                <span class="ml-2 px-1.5 py-0.5 text-[10px] rounded bg-blue-600 text-white">
                    Compte cible
                </span>
            @endif
        </div>
    </td>

    <!-- INTITULÉ -->
    <td class="py-1 pr-4 text-xs text-gray-700">
        <button type="button"
                wire:click="mapToNew({{ $node->id }})"
                wire:loading.attr="disabled"
                wire:target="mapToNew"
                class="text-left w-full hover:text-blue-700 transition">
            {{ $node->intitule }}
        </button>
    </td>
</tr>

{{-- CHILDREN --}}
@if($isExpanded && $hasChildren)
    @include('livewire.mapping.table.tree-new', [
        'nodes' => $node->children,
        'level' => $level + 1
    ])
@endif
@endforeach
