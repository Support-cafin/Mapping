<div id="sentinel" class="h-24 flex items-center justify-center">
    @if($hasMore && $totalCount > 0)
        <div class="text-center py-6 w-full">
            <!-- Loading très visible -->
            <div class="inline-flex flex-col items-center space-y-2 bg-blue-50 px-6 py-4 rounded-xl border-2 border-blue-300 shadow-lg">
                <!-- Spinner géant -->
                <div class="animate-spin rounded-full h-12 w-12 border-4 border-blue-300 border-t-blue-700 mb-2"></div>
                
                <!-- Message principal -->
                <span class="text-base font-semibold text-blue-800">
                    ⏳ Chargement en cours...
                </span>
                
                <!-- Progression -->
                <span class="text-sm text-blue-600">
                    {{ number_format(min($loadedCount, $totalCount), 0, ',', ' ') }} / {{ number_format($totalCount, 0, ',', ' ') }} écritures
                </span>
                
                <!-- Barre de progression -->
                @php
                    $percentage = $totalCount > 0 ? round(($loadedCount / $totalCount) * 100) : 0;
                @endphp
                <div class="w-48 h-2 bg-blue-200 rounded-full mt-2">
                    <div class="h-2 bg-blue-600 rounded-full transition-all duration-300" 
                         style="width: {{ $percentage }}%"></div>
                </div>
                <span class="text-xs text-blue-500">{{ $percentage }}%</span>
            </div>
        </div>
    @elseif($totalCount > 0)
        <div class="text-center py-6">
            <div class="inline-flex items-center space-x-3 text-green-700 bg-green-50 px-6 py-3 rounded-xl border-2 border-green-300">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                <div class="text-left">
                    <span class="font-semibold block">Terminé !</span>
                    <span class="text-sm">{{ number_format($totalCount, 0, ',', ' ') }} écritures chargées</span>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Script pour l'intersection observer avec logs -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const sentinel = document.getElementById('sentinel');
        if (!sentinel) {
            console.error('❌ Sentinel non trouvé');
            return;
        }
        
        console.log('✅ Sentinel trouvé', {
            hasMore: @json($hasMore),
            loadedCount: @json($loadedCount),
            totalCount: @json($totalCount)
        });
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                console.log('👁️ Sentinel intersection:', {
                    isIntersecting: entry.isIntersecting,
                    ratio: entry.intersectionRatio
                });
                
                if (entry.isIntersecting && @json($hasMore)) {
                    console.log('📥 Chargement de plus d\'écritures...');
                    
                    // Désactiver temporairement l'observer
                    observer.unobserve(sentinel);
                    
                    @this.loadMore()
                        .then(() => {
                            console.log('✅ Chargement réussi');
                            // Réactiver l'observer après un délai
                            setTimeout(() => {
                                if (@json($hasMore)) {
                                    observer.observe(sentinel);
                                    console.log('🔄 Observer réactivé');
                                }
                            }, 500);
                        })
                        .catch(error => {
                            console.error('❌ Erreur:', error);
                            // Réactiver l'observer même en cas d'erreur
                            setTimeout(() => {
                                observer.observe(sentinel);
                            }, 1000);
                        });
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '50px'
        });
        
        observer.observe(sentinel);
    });
</script>