<div id="gl-root"
     class="p-0 mt-12 bg-gray-50 flex flex-col h-screen"
     x-data="{
         exporting: @entangle('exporting'),
         showModal: false,
     }">

    <style>
        .table-container {
            height: calc(100vh - 185px);
            overflow-y: auto;
            overflow-x: auto;
        }
        th { font-size: 9px !important; padding: 4px 6px !important; background-color: #495057 !important; color: #f8f9fa !important; font-weight: 600; }
        .table-row td        { font-size: 9px !important; padding: 3px 5px !important; color: #212529; }
        .account-header td   { font-size: 10px !important; padding: 4px 6px !important; color: #212529; }
        .account-header      { background-color: #e9ecef !important; border-top: 2px solid #adb5bd; border-bottom: 1px solid #adb5bd; font-weight: 600; }
        .total-row           { background-color: #e9ecef !important; font-weight: 600; }
        .total-row td        { font-size: 9px !important; padding: 4px 6px !important; }
        .solde-row td        { font-size: 9px !important; padding: 2px 5px !important; background-color: #f1f3f5 !important; font-weight: 600; }
        .amount-col          { min-width: 90px; text-align: right; font-family: monospace; font-size: 9px !important; color: #212529; }
        .solde-debit         { background-color: #f8d7da !important; color: #721c24; font-weight: bold; }
        .solde-credit        { background-color: #d4edda !important; color: #155724; font-weight: bold; }

        .stats-bar {
            position: sticky; bottom: 0;
            background: #e9ecef;
            border-top: 2px solid #ced4da;
            height: 36px;
            font-size: 9px;
            z-index: 20;
        }
        .stat-item { display: flex; flex-direction: column; justify-content: center; padding: 0 10px; border-right: 1px solid #ced4da; }
        .stat-item:last-child { border-right: none; }
        .stat-label { font-size: 8px; color: #495057; font-weight: 500; text-transform: uppercase; letter-spacing: .05em; }
        .stat-value { font-size: 10px; font-weight: 700; color: #212529; }

        .filter-card { background: #f1f3f5; border: 1px solid #ced4da; border-radius: 4px; padding: 6px; margin-bottom: 4px; }

        /* Scrollbar */
        .scrollbar-thin::-webkit-scrollbar       { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-track  { background: #e9ecef; }
        .scrollbar-thin::-webkit-scrollbar-thumb  { background: #adb5bd; border-radius: 3px; }

        /* Modal */
        .modal-bg  { position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 900; }
        .modal-box { position: fixed; top: 50%; left: 50%; transform: translate(-50%,-50%); background: white; padding: 16px; border-radius: 6px; width: min(600px, 94vw); max-height: 84vh; overflow-y: auto; z-index: 901; box-shadow: 0 10px 40px rgba(0,0,0,.25); }
        .account-item { padding: 5px 8px; border-bottom: 1px solid #e9ecef; font-size: 9px; cursor: pointer; user-select: none; }
        .account-item:hover { background: #f8f9fa; }
        .account-item.selected { background: #dbeafe; }

        /* Pagination */
        .pagination-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 26px; height: 22px; padding: 0 6px; font-size: 9px; border-radius: 3px; border: 1px solid #ced4da; cursor: pointer; transition: all .15s; }
        .pagination-btn:hover:not(:disabled) { background: #e7f1ff; border-color: #3b82f6; color: #1d4ed8; }
        .pagination-btn.active { background: #3b82f6; color: white; border-color: #3b82f6; font-weight: 700; }
        .pagination-btn:disabled { opacity: .4; cursor: not-allowed; }
    </style>

    {{-- ═══════════════════════════════════════════
         BARRE SUPÉRIEURE
    ═══════════════════════════════════════════ --}}
    <div class="flex-shrink-0 px-3 py-1 bg-white border-b shadow-sm no-print">
        <div class="flex justify-between items-center">

            <div class="flex items-center space-x-2">
                <h1 class="font-semibold text-gray-800 text-sm">Grand Livre Général</h1>
                <span class="text-[9px] bg-gray-100 text-gray-500 px-2 py-0.5 rounded">{{ $entreprise->code }}</span>
            </div>

            <div class="flex items-center space-x-1.5">
                <a href="{{ route('grand-livre.detail') }}"
                   class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-[10px] hover:bg-purple-200 border border-purple-200">
                    <i class="fas fa-list mr-1"></i>Grand Livre
                </a>

                {{-- Excel --}}
                <button wire:click="exportExcel"
                        :disabled="exporting"
                        class="px-2 py-1 bg-green-600 text-white rounded text-[10px] hover:bg-green-700 border border-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading wire:target="exportExcel">
                        <i class="fas fa-spinner fa-spin mr-1"></i>Excel…
                    </span>
                    <span wire:loading.remove wire:target="exportExcel">
                        <i class="fas fa-file-excel mr-1"></i>Excel
                    </span>
                </button>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         FILTRES
    ═══════════════════════════════════════════ --}}
    <div class="filter-card mx-3 mt-1 no-print">
        <div class="grid grid-cols-4 gap-2 items-end">
            <div>
                <label class="text-[8px] text-gray-600 block mb-0.5 font-medium">Date début</label>
                <input type="date" wire:model.live="dateDebut"
                       class="w-full px-1 py-0.5 border border-gray-300 rounded text-[9px] bg-white">
            </div>
            <div>
                <label class="text-[8px] text-gray-600 block mb-0.5 font-medium">Date fin</label>
                <input type="date" wire:model.live="dateFin"
                       class="w-full px-1 py-0.5 border border-gray-300 rounded text-[9px] bg-white">
            </div>
            <div class="flex items-end">
                <button wire:click="resetFilters"
                        class="px-2 py-0.5 bg-gray-300 text-gray-700 rounded text-[9px] hover:bg-gray-400 border border-gray-400">
                    <i class="fas fa-redo mr-1"></i>Reset
                </button>
            </div>
        </div>

        {{-- Sélecteur comptes --}}
        <div class="mt-2 pt-1 border-t border-gray-300 flex items-center justify-between flex-wrap gap-1">
            <div class="flex items-center space-x-2">
                <span class="text-[9px] font-medium text-gray-700">Comptes sélectionnés :</span>
                @if(count($selectedAccounts) > 0)
                    <span class="text-[9px] bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-bold">
                        {{ count($selectedAccounts) }}
                    </span>
                    <button wire:click="clearSelectedAccounts"
                            class="text-[8px] text-red-500 hover:text-red-700 underline">
                        Effacer tout
                    </button>
                @else
                    <span class="text-[9px] text-gray-400">Tous les comptes</span>
                @endif
            </div>

            <button @click="showModal = true"
                    class="px-2 py-0.5 bg-blue-600 text-white rounded text-[9px] hover:bg-blue-700">
                <i class="fas fa-sliders-h mr-1"></i>Filtrer les comptes
            </button>
        </div>

        {{-- Aperçu tags --}}
        @if(!empty($selectedAccounts) && count($selectedAccounts) <= 8)
            <div class="mt-1 flex flex-wrap gap-1">
                @foreach($selectedAccounts as $sc)
                    <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-50 text-blue-800 rounded text-[8px] border border-blue-200">
                        {{ $sc }}
                        <button wire:click="toggleAccount('{{ $sc }}')"
                                class="ml-1 text-blue-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                @endforeach
            </div>
        @elseif(count($selectedAccounts) > 8)
            <div class="mt-1 text-[8px] text-gray-500">{{ count($selectedAccounts) }} comptes sélectionnés</div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════
         MODAL SÉLECTION COMPTES
         Toute la logique de coche est gérée en Alpine JS
         → zéro round-trip Livewire pendant la sélection
         → UN seul appel Livewire au clic "Valider"
    ═══════════════════════════════════════════ --}}
    <div x-show="showModal"
         x-cloak
         @keydown.escape.window="showModal = false"
         class="no-print"
         x-data="{
             tempSelected: @js($tempSelectedAccounts),
             search: '',
             allCodes: @js($accountsList->pluck('code')->toArray()),
             toggle(code) {
                 const idx = this.tempSelected.indexOf(code);
                 if (idx === -1) this.tempSelected.push(code);
                 else this.tempSelected.splice(idx, 1);
             },
             isSelected(code) {
                 return this.tempSelected.includes(code);
             },
             selectAll() {
                 this.tempSelected = [...this.allCodes];
             },
             clearAll() {
                 this.tempSelected = [];
             },
             validate() {
                 $wire.set('tempSelectedAccounts', this.tempSelected);
                 $wire.call('validateAccountSelection');
                 showModal = false;
             }
         }"
         @open-modal.window="
             tempSelected = @js($tempSelectedAccounts);
             search = '';
         ">

        <div class="modal-bg" @click="showModal = false"></div>

        <div class="modal-box">
            {{-- Titre --}}
            <div class="flex justify-between items-center mb-3">
                <h3 class="font-semibold text-gray-800 text-[11px]">
                    Sélectionner les comptes
                    <span class="ml-2 text-blue-700 bg-blue-100 px-2 py-0.5 rounded text-[9px]"
                          x-text="tempSelected.length + ' sélectionné(s)'"></span>
                </h3>
                <button @click="showModal = false"
                        class="text-gray-500 hover:text-gray-800 text-lg leading-none">&times;</button>
            </div>

            {{-- Recherche --}}
            <input type="text"
                   x-model="search"
                   placeholder="Rechercher par code ou intitulé…"
                   class="w-full mb-2 px-2 py-1 border border-gray-300 rounded text-[9px] focus:outline-none focus:border-blue-400">

            {{-- Boutons rapides --}}
            <div class="mb-2 flex items-center gap-2">
                <button @click="selectAll()"
                        class="px-2 py-1 bg-blue-600 text-white rounded text-[9px] hover:bg-blue-700">
                    <i class="fas fa-check-double mr-1"></i>Tout sélectionner
                </button>
                <button @click="clearAll()"
                        class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-[9px] hover:bg-gray-300">
                    <i class="fas fa-times mr-1"></i>Tout désélectionner
                </button>
            </div>

            {{-- Liste des comptes --}}
            <div class="border border-gray-200 rounded overflow-y-auto" style="max-height: 52vh;">
                @foreach($accountsList as $account)
                    <div class="account-item flex items-center"
                         :class="{ 'selected': isSelected('{{ $account->code }}') }"
                         x-show="
                             search === '' ||
                             '{{ strtolower($account->code . ' ' . $account->intitule) }}'.includes(search.toLowerCase())
                         "
                         @click="toggle('{{ $account->code }}')"
                         wire:key="acc-{{ $account->code }}">

                        {{-- Checkbox visuelle --}}
                        <div class="w-4 h-4 mr-2 flex-shrink-0 flex items-center justify-center rounded border"
                             :class="isSelected('{{ $account->code }}')
                                 ? 'bg-blue-600 border-blue-600'
                                 : 'border-gray-400 bg-white'">
                            <template x-if="isSelected('{{ $account->code }}')">
                                <i class="fas fa-check text-white" style="font-size:7px;"></i>
                            </template>
                        </div>

                        <div class="flex-1 min-w-0">
                            <span class="font-semibold text-gray-800">{{ $account->code }}</span>
                            <span class="text-gray-600 ml-1 truncate">{{ Str::limit($account->intitule, 45) }}</span>
                        </div>

                        <div class="text-[8px] text-gray-400 ml-2 flex-shrink-0">
                            {{ number_format($account->total_ecritures, 0, ',', ' ') }} écr.
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pied de modal --}}
            <div class="mt-3 flex justify-between items-center">
                <span class="text-[9px] text-gray-500">
                    {{ count($accountsList) }} compte(s) disponible(s)
                </span>
                <div class="flex gap-2">
                    <button @click="showModal = false"
                            class="px-3 py-1 bg-gray-500 text-white rounded text-[9px] hover:bg-gray-600">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                    <button @click="validate()"
                            class="px-3 py-1 bg-green-600 text-white rounded text-[9px] hover:bg-green-700">
                        <i class="fas fa-check mr-1"></i>Valider
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════
         ZONE PRINCIPALE
    ═══════════════════════════════════════════ --}}
    <div class="flex-1 min-h-0 bg-white mx-3 mb-1 border border-gray-300 rounded" id="print-zone">

        {{-- En-tête impression --}}
        <div class="print-only" style="padding:8px 0 6px; border-bottom:2px solid #2c3e50; margin-bottom:6px;">
            <div style="font-size:12pt; font-weight:bold; color:#2c3e50;">GRAND LIVRE GÉNÉRAL</div>
            <div style="font-size:8pt; color:#555; margin-top:3px;">
                <strong>{{ $entreprise->nom }}</strong>
                — {{ $entreprise->code }}
                &nbsp;|&nbsp; Période : {{ $dateDebut ? \Carbon\Carbon::parse($dateDebut)->format('d/m/Y') : '—' }}
                au {{ $dateFin ? \Carbon\Carbon::parse($dateFin)->format('d/m/Y') : '—' }}
                &nbsp;|&nbsp; Imprimé le {{ now()->format('d/m/Y à H:i') }}
            </div>
        </div>

        <div class="table-container scrollbar-thin">
            @if($totalCount === 0)
                <div class="flex flex-col items-center justify-center h-full text-gray-400">
                    <i class="fas fa-layer-group text-3xl mb-2"></i>
                    <p class="text-sm">Aucune écriture trouvée</p>
                    <p class="text-xs mt-1">Modifiez vos critères</p>
                </div>
            @else
                <table class="min-w-full border-collapse">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th class="w-20 text-center">Date</th>
                            <th class="w-14">Pièce</th>
                            <th class="w-16">Journal</th>
                            <th class="w-20">Compte</th>
                            <th>Libellé</th>
                            <th class="w-28 text-right">Débit</th>
                            <th class="w-28 text-right">Crédit</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($recapData as $group)
                            {{-- En-tête compte --}}
                            <tr class="account-header">
                                <td colspan="7" class="px-2 py-0.5">
                                    <div class="flex justify-between items-center">
                                        <div>
                                            <span class="font-bold text-[10px]">{{ $group['new_account_code'] }}</span>
                                            <span class="text-gray-700 text-[9px] ml-2">{{ Str::limit($group['new_account_intitule'], 60) }}</span>
                                        </div>
                                        <span class="text-[8px] bg-gray-200 px-1.5 py-0.5 rounded">
                                            {{ $group['nombre_ecritures'] }} écrit.
                                        </span>
                                    </div>
                                </td>
                            </tr>

                            {{-- Écritures --}}
                            @foreach($group['ecritures'] as $ecriture)
                                <tr class="table-row hover:bg-gray-50 border-b border-gray-100">
                                    <td class="px-1 py-0.5 text-center">
                                        {{ $ecriture->date_ecriture ? \Carbon\Carbon::parse($ecriture->date_ecriture)->format('d/m/Y') : '' }}
                                    </td>
                                    <td class="px-1 py-0.5">{{ $ecriture->piece ?? '' }}</td>
                                    <td class="px-1 py-0.5">{{ $ecriture->journal_code ?? '' }}</td>
                                    <td class="px-1 py-0.5">{{ $ecriture->old_account_code ?? '' }}</td>
                                    <td class="px-1 py-0.5">{{ Str::limit($ecriture->libelle, 55) }}</td>
                                    <td class="amount-col px-1 py-0.5">
                                        @if($ecriture->debit > 0){{ number_format($ecriture->debit, 0, ',', ' ') }}@endif
                                    </td>
                                    <td class="amount-col px-1 py-0.5">
                                        @if($ecriture->credit > 0){{ number_format($ecriture->credit, 0, ',', ' ') }}@endif
                                    </td>
                                </tr>
                            @endforeach

                            {{-- Total compte --}}
                            @php $solde = $group['total_debit'] - $group['total_credit']; @endphp
                            <tr class="total-row">
                                <td colspan="4" class="px-1 py-0.5 text-right font-semibold">TOTAL {{ $group['new_account_code'] }}</td>
                                <td class="px-1 py-0.5 text-[8px] text-gray-600">{{ $group['nombre_ecritures'] }} écrit.</td>
                                <td class="amount-col px-1 py-0.5 font-bold">{{ number_format($group['total_debit'],  0, ',', ' ') }}</td>
                                <td class="amount-col px-1 py-0.5 font-bold">{{ number_format($group['total_credit'], 0, ',', ' ') }}</td>
                            </tr>

                            {{-- Solde compte --}}
                            <tr class="solde-row">
                                <td colspan="5" class="px-1 py-0.5 text-right font-semibold">SOLDE {{ $group['new_account_code'] }}</td>
                                @if($solde > 0)
                                    <td class="amount-col px-1 py-0.5 solde-debit">{{ number_format(abs($solde), 0, ',', ' ') }}</td>
                                    <td></td>
                                @else
                                    <td></td>
                                    <td class="amount-col px-1 py-0.5 solde-credit">{{ number_format(abs($solde), 0, ',', ' ') }}</td>
                                @endif
                            </tr>

                            <tr><td colspan="7" style="height:4px;"></td></tr>
                        @endforeach
                    </tbody>

                    {{-- ─── PAGINATION NUMÉROTÉE ─── --}}
                    <tfoot>
                        <tr>
                            <td colspan="7" class="px-3 py-2 bg-gray-50 border-t border-gray-200 no-print">
                                <div class="flex items-center justify-between">

                                    {{-- Info page --}}
                                    <span class="text-[8px] text-gray-500">
                                        Page <strong>{{ $currentPage }}</strong> / <strong>{{ $totalPages }}</strong>
                                        &nbsp;—&nbsp; {{ $totalCount }} compte(s) au total
                                        &nbsp;—&nbsp; {{ $perPage }} par page
                                    </span>

                                    {{-- Contrôles --}}
                                    <div class="flex items-center gap-1" wire:loading.class="opacity-50 pointer-events-none">

                                        {{-- ← Première --}}
                                        <button
                                            {{ $currentPage <= 1 ? 'disabled' : 'wire:click=goToPage(1)' }}
                                            class="pagination-btn {{ $currentPage <= 1 ? 'opacity-30 cursor-not-allowed' : '' }}"
                                            title="Première page">«</button>

                                        {{-- ← Précédent --}}
                                        <button
                                            {{ $currentPage <= 1 ? 'disabled' : 'wire:click=previousPage' }}
                                            class="pagination-btn {{ $currentPage <= 1 ? 'opacity-30 cursor-not-allowed' : '' }}"
                                            title="Page précédente">‹</button>

                                        {{-- Pages numérotées (fenêtre glissante ±3) --}}
                                        @php
                                            $w     = 3;
                                            $start = max(1, $currentPage - $w);
                                            $end   = min($totalPages, $currentPage + $w);
                                        @endphp

                                        @if($start > 1)
                                            <button wire:click="goToPage(1)" class="pagination-btn">1</button>
                                            @if($start > 2)
                                                <span class="text-[9px] text-gray-400 px-1">…</span>
                                            @endif
                                        @endif

                                        @for($p = $start; $p <= $end; $p++)
                                            <button wire:click="goToPage({{ $p }})"
                                                    class="pagination-btn {{ $p === $currentPage ? 'active' : '' }}">
                                                {{ $p }}
                                            </button>
                                        @endfor

                                        @if($end < $totalPages)
                                            @if($end < $totalPages - 1)
                                                <span class="text-[9px] text-gray-400 px-1">…</span>
                                            @endif
                                            <button wire:click="goToPage({{ $totalPages }})" class="pagination-btn">
                                                {{ $totalPages }}
                                            </button>
                                        @endif

                                        {{-- Suivant → --}}
                                        <button
                                            {{ $currentPage >= $totalPages ? 'disabled' : 'wire:click=nextPage' }}
                                            class="pagination-btn {{ $currentPage >= $totalPages ? 'opacity-30 cursor-not-allowed' : '' }}"
                                            title="Page suivante">›</button>

                                        {{-- Dernière → --}}
                                        <button
                                            {{ $currentPage >= $totalPages ? 'disabled' : 'wire:click=goToPage('.$totalPages.')' }}
                                            class="pagination-btn {{ $currentPage >= $totalPages ? 'opacity-30 cursor-not-allowed' : '' }}"
                                            title="Dernière page">»</button>

                                        {{-- Spinner chargement --}}
                                        <span wire:loading wire:target="goToPage,nextPage,previousPage"
                                              class="ml-1 text-blue-500">
                                            <svg class="animate-spin h-3 w-3 inline" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            @endif
        </div>

        {{-- ─── BARRE DE STATS ─── --}}
        @if($totalCount > 0)
            <div class="stats-bar flex items-center justify-around no-print">
                <div class="stat-item">
                    <div class="stat-label">Comptes (total)</div>
                    <div class="stat-value">{{ number_format($totalCount, 0, ',', ' ') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Écritures (page)</div>
                    <div class="stat-value">{{ number_format($recapStats['total_ecritures'], 0, ',', ' ') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Débit (page)</div>
                    <div class="stat-value">{{ number_format($recapStats['total_debit'], 0, ',', ' ') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Crédit (page)</div>
                    <div class="stat-value">{{ number_format($recapStats['total_credit'], 0, ',', ' ') }}</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Solde (page)</div>
                    <div class="flex items-center gap-1">
                        <span class="stat-value">{{ number_format($recapStats['solde_global_absolu'], 0, ',', ' ') }}</span>
                        <span class="text-[7px] font-bold {{ $recapStats['is_debiteur'] ? 'text-red-600' : 'text-green-600' }}">
                            ({{ $recapStats['is_debiteur'] ? 'D' : 'C' }})
                        </span>
                    </div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Page</div>
                    <div class="stat-value">{{ $currentPage }} / {{ $totalPages }}</div>
                </div>
            </div>

            {{-- Récap impression --}}
            <div class="print-only" style="border-top:2px solid #2c3e50; padding-top:8px; margin-top:8px;">
                <table style="width:55%; margin:0 auto; border-collapse:collapse; font-size:8pt;">
                    <tr style="background:#2c3e50; color:#fff;">
                        <th colspan="2" style="padding:4px 8px; text-align:center;">RÉCAPITULATIF</th>
                    </tr>
                    <tr><td style="padding:3px 8px;">Total comptes</td><td style="text-align:right; padding:3px 8px; font-weight:bold;">{{ $totalCount }}</td></tr>
                    <tr style="background:#f5f5f5;"><td style="padding:3px 8px;">Total Débit</td><td style="text-align:right; padding:3px 8px; font-weight:bold;">{{ number_format($recapStats['total_debit'], 0, ',', ' ') }}</td></tr>
                    <tr><td style="padding:3px 8px;">Total Crédit</td><td style="text-align:right; padding:3px 8px; font-weight:bold;">{{ number_format($recapStats['total_credit'], 0, ',', ' ') }}</td></tr>
                    <tr style="background:#dbeafe; font-weight:bold;">
                        <td style="padding:4px 8px;">Solde Global</td>
                        <td style="text-align:right; padding:4px 8px; color:{{ $recapStats['is_debiteur'] ? '#c00' : '#060' }};">
                            {{ number_format($recapStats['solde_global_absolu'], 0, ',', ' ') }}
                            ({{ $recapStats['is_debiteur'] ? 'Débiteur' : 'Créditeur' }})
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    </div>

    <style media="print">
        @page { size: landscape; margin: 0.7cm; }
        body * { visibility: hidden; }
        #print-zone, #print-zone * { visibility: visible; }
        #print-zone { position: absolute !important; top: 0 !important; left: 0 !important; width: 100% !important; margin: 0 !important; border: none !important; }
        .no-print { display: none !important; visibility: hidden !important; }
        .print-only { visibility: visible !important; display: block !important; }
        .table-container { height: auto !important; overflow: visible !important; }
        .stats-bar { display: none !important; }
        th { background-color: #333 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .account-header { background-color: #dde3ea !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .total-row { background-color: #e8ecf0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .solde-debit { background-color: #fde8ea !important; color: #c00 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .solde-credit { background-color: #e8fde8 !important; color: #060 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        thead { display: table-header-group; }
        tfoot { display: table-footer-group; }
        tr { page-break-inside: avoid; }
    </style>

    @push('scripts')
    <script>
        window.addEventListener('beforeprint', () => document.body.classList.add('is-printing'));
        window.addEventListener('afterprint',  () => document.body.classList.remove('is-printing'));
    </script>
    @endpush
</div>
