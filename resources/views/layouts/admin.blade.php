<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }} • @yield('title', 'Administration')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://rsms.me/">
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select/dist/js/tom-select.complete.min.js"></script>
    
    @livewireStyles
    
    <!-- Vite Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>

<body class="h-full bg-gray-50 font-sans antialiased" 
      x-data="{ sidebarOpen: false, userOpen: false }"
      @keydown.window.escape="sidebarOpen = false; userOpen = false">

    <div class="flex h-full">
        <!-- Sidebar -->
        @include('layouts.partials.sidebar')

        <!-- Backdrop pour mobile -->
        <div x-show="sidebarOpen"
             x-transition.opacity
             class="fixed inset-0 bg-black/50 z-40 lg:hidden"
             @click="sidebarOpen = false"
             x-cloak>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Header -->
            @include('layouts.partials.header')

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto pt-20 lg:pt-16 pb-8">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            @include('layouts.partials.footer')
        </div>
    </div>

    @livewireScripts
    
    <!-- Ionicons -->
    <script type="module" src="https://unpkg.com/ionicons@7.4.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.4.0/dist/ionicons/ionicons.js"></script>
    
    @stack('scripts')
    
    <!-- Toast Notifications -->
    @include('components.toast-notification')
    
    <script>
        // Alpine.js est déjà inclus dans app.js
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js initialized');
        });
        
        // Confirmation avant suppression
        window.addEventListener('DOMContentLoaded', function() {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('confirm-delete', function(data) {
                    if (confirm('Êtes-vous sûr de vouloir supprimer cet élément ?')) {
                        Livewire.dispatch('delete-confirmed', data);
                    }
                });
            }
        });
    </script>
</body>
</html>