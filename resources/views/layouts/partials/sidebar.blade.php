<aside
    x-show="sidebarOpen || window.innerWidth >= 1024"
    x-transition
    x-cloak
    @click.away="if (window.innerWidth < 1024) sidebarOpen = false"
    class="fixed inset-y-0 left-0 z-50 bg-white
           border-r border-gray-200
           lg:static flex flex-col
           shadow-lg lg:shadow-none"
    style="width: 240px;"
>
       
   <!-- 🔷 LOGO / APP -->
<div class="flex items-center px-3 py-1 border-b border-gray-100 relative">

    <!-- Logo -->
    <div class="flex items-center justify-center">
        <img src="{{ asset('images/logo.png') }}"
     alt="Crystal Compta"
     class="h-17 scale-110 w-auto object-contain">

    </div>

    <!-- Close mobile -->
    <button @click="sidebarOpen = false"
            class="absolute right-2 top-2 lg:hidden p-1 rounded-md hover:bg-gray-100">
        <ion-icon name="close-outline" class="text-xl"></ion-icon>
    </button>

</div>


    <!-- 🧭 NAVIGATION -->
    <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">

        @php
            $navItems = [
                ['route' => 'parametres.exercices', 'icon' => 'git-compare-outline', 'label' => 'Exercices comptables'],
                ['route' => 'mapping.dual_panel', 'icon' => 'grid-outline', 'label' => 'Plan Comptable'],
                ['route' => 'plan.index', 'icon' => 'git-compare-outline', 'label' => 'Mappings'],
                ['route' => 'grand-livre.detail', 'icon' => 'book-outline', 'label' => 'Grands Livres'],
                ['route' => 'balances.index', 'icon' => 'folder-open-outline', 'label' => 'Balances'],
                ['route' => 'donateurs.index', 'icon' => 'people-outline', 'label' => 'Donateurs'],
            ];
        @endphp

        @foreach($navItems as $item)
            <a href="{{ route($item['route']) }}"
               class="
               {{ request()->routeIs($item['route'])
                    ? 'bg-primary/10 text-primary border-primary'
                    : 'text-gray-600 hover:bg-gray-100 border-transparent'
               }}
               flex items-center gap-3 px-4 py-3 rounded-xl
               border transition-all duration-200 group
               ">
               
                <ion-icon name="{{ $item['icon'] }}"
                          class="
                          text-xl
                          {{ request()->routeIs($item['route']) ? 'text-primary' : 'text-gray-400' }}
                          group-hover:text-primary transition
                          ">
                </ion-icon>

                <span class="text-sm font-medium">
                    {{ $item['label'] }}
                </span>
            </a>
        @endforeach
        
        <!-- 📊 ÉTATS FINANCIERS -->
        <!--<div class="pt-6 mt-6 border-t border-gray-100">
            <p class="px-4 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">
                États financiers
            </p>
        
            <a href="{{ route('bilan') }}"
               class="
               {{ request()->routeIs('bilan') || request()->routeIs('compte.resultat') || request()->routeIs('flux.tresorerie')
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                    : 'text-gray-600 hover:bg-emerald-50'
               }}
               flex items-center gap-3 px-4 py-3 rounded-xl
               border transition-all duration-200 group
               ">
                
                <ion-icon name="file-tray-full-outline"
                          class="
                          text-xl
                          {{ request()->routeIs('bilan') || request()->routeIs('compte.resultat') || request()->routeIs('flux.tresorerie')
                                ? 'text-emerald-600'
                                : 'text-gray-400 group-hover:text-emerald-600'
                          }}
                          transition
                          ">
                </ion-icon>
        
                <span class="text-sm font-medium">
                    Bilan & États financiers
                </span>
            </a>
            <a href="{{ route('compte.resultat') }}"
               class="
               {{ request()->routeIs('compte.resultat')
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-300'
                    : 'text-gray-600 hover:bg-emerald-50'
               }}
               flex items-center gap-3 px-4 py-3 rounded-xl
               border transition-all duration-200 group
               ">
                
                <ion-icon name="trending-up-outline"
                          class="
                          text-xl
                          {{ request()->routeIs('compte.resultat')
                                ? 'text-emerald-600'
                                : 'text-gray-400 group-hover:text-emerald-600'
                          }}
                          transition
                          ">
                </ion-icon>
            
                <span class="text-sm font-medium">
                    Compte de résultat
                </span>
            </a>

        </div>-->

        @php $ong = DB::table('entreprises')->where('id', Auth::user()->entreprise_id)->where('type_compte', 'premium')->first(); @endphp
        <!-- 🔐 ADMIN -->
        @if($ong)
            <div class="pt-6 mt-6 border-t border-gray-100">
                <p class="px-4 mb-2 text-[11px] font-semibold text-gray-400 uppercase">
                    Administration
                </p>

                <a href="{{ route('admin.entreprises.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-purple-50 transition">
                    <ion-icon name="business-outline" class="text-xl text-purple-500"></ion-icon>
                    <span class="text-sm font-medium">Mes Entreprises</span>
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-purple-50 transition">
                    <ion-icon name="people-circle-outline" class="text-xl text-purple-500"></ion-icon>
                    <span class="text-sm font-medium">Mes Utilisateurs</span>
                </a>
            </div>
        @else
        <div class="pt-6 mt-6 border-t border-gray-100">
                <p class="px-4 mb-2 text-[11px] font-semibold text-gray-400 uppercase">
                    Administration
                </p>

                <a href="{{ route('admin.entreprises.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-purple-50 transition">
                    <ion-icon name="business-outline" class="text-xl text-purple-500"></ion-icon>
                    <span class="text-sm font-medium">Mon Entreprise</span>
                </a>

                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-purple-50 transition">
                    <ion-icon name="people-circle-outline" class="text-xl text-purple-500"></ion-icon>
                    <span class="text-sm font-medium">Mes Utilisateurs</span>
                </a>
            </div>
        @endif
        
        {{-- @if(auth()->user()->is_admin)
            <div class="pt-6 mt-6 border-t border-gray-100">
                <p class="px-4 mb-2 text-[11px] font-semibold text-gray-400 uppercase">
                    Administration
                </p>

    
                <a href="{{ route('admin.users.index') }}"
                   class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-purple-50 transition">
                    <ion-icon name="people-circle-outline" class="text-xl text-purple-500"></ion-icon>
                    <span class="text-sm font-medium">Utilisateurs</span>
                </a>
            </div>
        @endif --}}
    </nav>

    <!-- 👤 FOOTER USER -->
    <div class="px-4 py-4 border-t border-gray-100">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center">
                <ion-icon name="person-outline"></ion-icon>
            </div>
            <div class="leading-tight">
                <p class="text-sm font-semibold text-gray-800">
                    {{ auth()->user()->name }}
                </p>
                <p class="text-[11px] text-gray-500">
                    {{ auth()->user()->entreprise->nom ?? 'Entreprise' }}
                </p>
            </div>
        </div>
    </div> 

</aside>
