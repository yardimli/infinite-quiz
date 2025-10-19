<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <!-- Theme Initializer Script -->
    <script>
        (function() {
            const storageKey = 'theme';
            const defaultTheme = 'cupcake'; // Set cupcake as the default theme.
            const availableThemes = ['light', 'dark', 'cupcake'];
            
            const storedTheme = localStorage.getItem(storageKey);
            
            if (storedTheme && availableThemes.includes(storedTheme)) {
                document.documentElement.setAttribute('data-theme', storedTheme);
            } else {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : defaultTheme);
            }
        })();
    </script>
</head>
<body class="font-sans text-base-content antialiased">
<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-base-200">
    <div>
        <a href="/">
            <img src="{{ asset('android-chrome-192x192.png') }}" alt="Application Logo" class="w-20 h-20">
        </a>
    </div>
    
    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-base-100 shadow-md overflow-hidden sm:rounded-lg">
        @yield('content')
    </div>
</div>
</body>
</html>
