<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Charger reCAPTCHA AVANT le reste -->
    <script>
        // Précharger reCAPTCHA
        (function() {
            var script = document.createElement('script');
            script.src = 'https://www.google.com/recaptcha/enterprise.js?render={{ config("services.recaptcha.site_key") }}';
            script.async = true;
            script.defer = true;
            script.onload = function() {
                console.log('reCAPTCHA Enterprise chargé');
                window.recaptchaReady = true;
            };
            document.head.appendChild(script);
        })();
    </script>
</head>

<body class="font-sans antialiased bg-gray-50 text-gray-900">
    <div class="min-h-screen flex">
        
        <!-- Left Side Illustration -->
        <div class="hidden lg:flex w-1/2 bg-gradient-to-br from-[#1885F6] to-[#6AC0F6] items-center justify-center p-10">
             <img src="{{ asset('images/crystal-compta.png') }}" alt="log">
        </div>

        <!-- Right Side (Auth Card) -->
        <div class="w-full lg:w-1/2 flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>
    </div>
</body>
</html>