<header class="fixed top-0 left-0 right-0 lg:left-[140px] h-12 bg-white/95 backdrop-blur-sm
               border-b border-gray-200 z-40 shadow-sm">
    <div class="h-full px-4 sm:px-6 lg:px-8 flex items-center justify-between">

        <!-- Mobile Menu -->
        <button @click="sidebarOpen = !sidebarOpen"
                class="lg:hidden p-2 rounded-xl hover:bg-gray-100 transition">
            <ion-icon name="menu" class="text-2xl"></ion-icon>
        </button>
        @php $exo = DB::table('exercices')->where('statut', 1)->first(); @endphp
        @php $eentre = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->first(); @endphp

    @if($eentre)
    <div class="px-6 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-medium shadow-sm">
       ONG :  {{ $eentre->nom }} 
    </div>
    @endif
    
     @if($eentre)
    <div class="px-6 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-medium shadow-sm">
        ONG :   {{ $eentre->nom }} 
    </div>
    @endif

        <!-- User Menu -->
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                    class="flex items-center gap-3 px-4 py-1 rounded-full hover:bg-gray-100 transition">
                  @if($exo)
                        <div class="px-6 py-1 rounded-full bg-blue-50 text-blue-700 text-sm font-medium shadow-sm">
                            📅 {{ $exo->libelle }}
                        </div>
                    @endif
                <div class="w-8 h-8 rounded-full bg-secondary flex items-center justify-center">
                    <ion-icon name="person" class="text-xl text-primary"></ion-icon>
                </div>
                
                <span class="hidden sm:block font-medium text-gray-700">
                    {{ auth()->user()->name }}
                </span>
                <ion-icon name="chevron-down" class="text-lg text-gray-500"></ion-icon>
            </button>

            <div x-show="open"
                 x-transition
                 @click.away="open = false"
                 x-cloak
                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden">
                 
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
                    <ion-icon name="person-outline" class="text-lg"></ion-icon>
                    <span>Mon profil</span>
                </a>

                <a href="{{ route('audit-logs.index') }}" 
                   class="flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-100 rounded-lg">
                    <i class="fas fa-history text-gray-500"></i>
                    <span>Log d'activité</span>
                    <!-- Badge pour les nouvelles actions (optionnel) -->
                    @php
                        $newLogsCount = auth()->check() ? 
                            \App\Models\AuditLog::where('entreprise_id', auth()->user()->entreprise_id)
                                ->where('created_at', '>', now()->subDay())
                                ->count() : 0;
                    @endphp
                    @if($newLogsCount > 0)
                        <span class="ml-auto px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded-full">
                            {{ $newLogsCount }}
                        </span>
                    @endif
                </a>

                <hr class="border-gray-200">

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-3 px-5 py-3 text-red-600 hover:bg-red-50 transition">
                        <ion-icon name="log-out-outline" class="text-lg"></ion-icon>
                        <span>Déconnexion</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
