<div>
    @if($showResults)
    <div class="fixed inset-0 z-[10000] overflow-y-auto" 
         x-data="{ show: @entangle('showResults') }"
         x-show="show"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <!-- Backdrop -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal Container - Centré avec taille réduite -->
        <div class="fixed inset-0 z-[10001] overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                
                <!-- Modal Panel - Taille réduite à max-w-2xl au lieu de max-w-4xl -->
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all w-full max-w-2xl">
                    
                    <!-- Header plus compact -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <div class="flex-shrink-0 w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-semibold text-white">
                                        Résultats d'importation
                                    </h3>
                                    <p class="text-xs text-blue-100">
                                        {{ $importType === 'old' ? 'Anciens comptes' : 'Nouveaux comptes' }} • {{ $summary['timestamp'] }}
                                    </p>
                                </div>
                            </div>
                            <button wire:click="closeResults" class="text-white hover:text-blue-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Résumé plus compact -->
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <div class="grid grid-cols-4 gap-2">
                            <div class="bg-white p-2 rounded shadow-sm border-l-4 border-blue-500">
                                <p class="text-[10px] text-gray-500 uppercase">Total</p>
                                <p class="text-lg font-bold text-gray-800">{{ $summary['total'] }}</p>
                            </div>
                            <div class="bg-white p-2 rounded shadow-sm border-l-4 border-green-500">
                                <p class="text-[10px] text-gray-500 uppercase">Importés</p>
                                <p class="text-lg font-bold text-green-600">{{ $importedCount }}</p>
                            </div>
                            <div class="bg-white p-2 rounded shadow-sm border-l-4 border-yellow-500">
                                <p class="text-[10px] text-gray-500 uppercase">Avert.</p>
                                <p class="text-lg font-bold text-yellow-600">{{ $summary['warnings'] }}</p>
                            </div>
                            <div class="bg-white p-2 rounded shadow-sm border-l-4 border-red-500">
                                <p class="text-[10px] text-gray-500 uppercase">Erreurs</p>
                                <p class="text-lg font-bold text-red-600">{{ $summary['errors'] }}</p>
                            </div>
                        </div>
                        
                        <div class="mt-2 text-[10px] text-gray-500 flex items-center justify-between">
                            <span>Fichier: <span class="font-mono">{{ Str::limit($summary['filename'], 20) }}</span></span>
                            <span>Temps: {{ number_format($summary['duration'], 2) }}s</span>
                        </div>
                    </div>

                    <!-- Liste des messages - Hauteur réduite -->
                    <div class="px-4 py-3 max-h-64 overflow-y-auto">
                        @if(count($allMessages) > 0)
                            <div class="space-y-1.5">
                                @foreach($allMessages as $index => $message)
                                    @php
                                        $isError = str_contains($message, '❌') || str_contains($message, 'Erreur');
                                        $isWarning = str_contains($message, '⚠️') || str_contains($message, 'Attention');
                                        $isSuccess = str_contains($message, '✓') || str_contains($message, 'succès');
                                    @endphp
                                    <div class="p-2 rounded {{ 
                                        $isError ? 'bg-red-50 border-l-4 border-red-500' : 
                                        ($isWarning ? 'bg-yellow-50 border-l-4 border-yellow-500' : 
                                        ($isSuccess ? 'bg-green-50 border-l-4 border-green-500' : 'bg-gray-50 border-l-4 border-gray-500')) 
                                    }}">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mr-2">
                                                @if($isError)
                                                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                @elseif($isWarning)
                                                    <svg class="w-4 h-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3.01L12.732 4.01c-.77-1.333-2.694-1.333-3.464 0L3.34 16.99c-.77 1.333.192 3.01 1.732 3.01z"/>
                                                    </svg>
                                                @elseif($isSuccess)
                                                    <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="flex-1 text-xs {{ $isError ? 'text-red-700' : ($isWarning ? 'text-yellow-700' : ($isSuccess ? 'text-green-700' : 'text-gray-700')) }}">
                                                {!! nl2br(e($message)) !!}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-6">
                                <svg class="mx-auto h-8 w-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-2 text-xs text-gray-500">Aucun message à afficher</p>
                            </div>
                        @endif
                    </div>

                    <!-- Footer plus compact -->
                    <div class="px-4 py-2 bg-gray-50 border-t flex items-center justify-between">
                        <div class="text-xs text-gray-500">
                            @if($summary['errors'] > 0)
                                <span class="text-red-600 font-medium">{{ $summary['errors'] }} erreur(s)</span>
                            @endif
                            @if($summary['warnings'] > 0)
                                <span class="text-yellow-600 font-medium ml-2">{{ $summary['warnings'] }} avert.</span>
                            @endif
                        </div>
                        
                        <div class="flex gap-2">
                            <button wire:click="downloadPdf"
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded hover:bg-blue-700 transition shadow-sm">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                PDF
                            </button>
                            
                            <button wire:click="closeResults"
                                    class="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded hover:bg-gray-200 transition">
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>