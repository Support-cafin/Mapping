<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} • {{ $pageTitle ?? 'Dashboard' }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    
    @livewireStyles
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-full bg-gray-50 font-sans antialiased"
      x-data="{ sidebarOpen: false, userOpen: false }"
      @keydown.window.escape="sidebarOpen = false; userOpen = false">

    <div class="flex h-full">
    
        @include('layouts.partials.sidebar')
    
        <!-- Backdrop Mobile -->
        <div x-show="sidebarOpen"
             x-transition.opacity
             class="fixed inset-0 bg-black/50 z-40 lg:hidden"
             @click="sidebarOpen = false">
        </div>
    
        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
    
            <!-- Header ajusté -->
            @include('layouts.partials.header')
    
            <!-- Page Content -->
            <main wire:ignore.self class="flex-1 overflow-y-auto pt-20 lg:pt-16 pb-8">
                <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8">
                    {{ $slot }}
                </div>
                @livewire('import.import-results')
                @livewire('import.import-mapping-results')
            </main>
            
            @include('layouts.partials.footer')
        </div>
    </div>


    <!-- Livewire V3 Script (VERSION CORRECTE) -->
    @livewireScripts
    
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.4.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.4.0/dist/ionicons/ionicons.js"></script>
    
    <!-- Initialisation Livewire -->
    <script>
        document.addEventListener('livewire:init', () => {
            console.log('Livewire initialized!');
        });
        
        document.addEventListener('livewire:navigate', () => {
            console.log('Livewire navigation!');
        });
    </script>
    
    
    <script>
    // Confirmation avant suppression
    window.addEventListener('DOMContentLoaded', function() {
        // Écouter les événements de suppression
        Livewire.on('confirm-delete', function(data) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce compte ?')) {
                Livewire.dispatch('delete-account-confirmed', data);
            }
        });
    });
    
    // Empêcher la propagation des clics sur les boutons d'action
    document.addEventListener('click', function(e) {
        if (e.target.closest('[wire\\:click]')) {
            const wireClick = e.target.closest('[wire\\:click]').getAttribute('wire:click');
            if (wireClick && wireClick.includes('openEditModal') || wireClick.includes('openDeleteModal')) {
                e.stopPropagation();
            }
        }
    });
</script>
    
    
    @include('components.toast-notification')
    
    @stack('scripts')
</body>
</html>