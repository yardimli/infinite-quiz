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
    
    <script>
        (function() {
            const storageKey = 'theme';
            const defaultTheme = 'cupcake';
            const availableThemes = ['light', 'dark', 'cupcake'];
            
            // Get the theme from localStorage.
            const storedTheme = localStorage.getItem(storageKey);
            
            // Check if the stored theme is one of the available themes.
            if (storedTheme && availableThemes.includes(storedTheme)) {
                // If a valid theme is stored, apply it.
                document.documentElement.setAttribute('data-theme', storedTheme);
            } else {
                // Otherwise, fall back to the user's system preference for dark mode, or the default theme.
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : defaultTheme);
            }
        })();
    </script>
</head>
<body class="font-sans antialiased">
<div class="min-h-screen bg-base-200">
    @include('layouts.navigation')
    
    <!-- Page Heading -->
    @hasSection('header')
        <header class="bg-base-100 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                @yield('header')
            </div>
        </header>
    @endif
    
    <!-- Page Content -->
    <main>
        @yield('content')
    </main>
</div>
</body>
</html>
