<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
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
<body class="antialiased bg-base-200">
<div class="flex flex-col min-h-screen">
    <!-- Header -->
    <header class="bg-base-100 shadow-md">
        <div class="navbar max-w-7xl mx-auto">
            <div class="navbar-start">
                <a href="/" class="btn btn-ghost normal-case text-xl">
                    <img src="{{ asset('android-chrome-192x192.png') }}" alt="Logo" class="h-8 w-8 mr-2">
                    Infinite Quiz
                </a>
            </div>
            <div class="navbar-end">
                <div class="dropdown dropdown-end mr-4">
                    <button tabindex="0" role="button" class="btn btn-ghost">
                        Theme
                        <svg width="12px" height="12px" class="h-2 w-2 fill-current opacity-60 inline-block" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 2048 2048"><path d="M1799 349l242 241-1017 1017L7 590l242-241 775 775 775-775z"></path></svg>
                    </button>
                    <ul tabindex="0" id="theme-menu" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-52 mt-4">
                        <li><button type="button" data-set-theme="light" class="btn btn-ghost btn-sm justify-start">Light</button></li>
                        <li><button type="button" data-set-theme="dark" class="btn btn-ghost btn-sm justify-start">Dark</button></li>
                        <li><button type="button" data-set-theme="cupcake" class="btn btn-ghost btn-sm justify-start">Cupcake</button></li>
                    </ul>
                </div>
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/dashboard') }}" class="btn btn-ghost">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-ghost">Log in</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="btn btn-primary ml-2">Register</a>
                        @endif
                    @endauth
                @endif
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="flex-grow">
        <!-- Hero Section -->
        <div class="hero min-h-[60vh] bg-base-100">
            <div class="hero-content text-center">
                <div class="max-w-md">
                    <h1 class="text-5xl font-bold">Dynamic AI-Powered Quizzes</h1>
                    <p class="py-6">Enter any topic and choose from a variety of cutting-edge AI models to generate unique, challenging quizzes instantly. Perfect for studying, teaching, or just for fun!</p>
                    <a href="{{ route('register') }}" class="btn btn-primary">Get Started</a>
                </div>
            </div>
        </div>
        
        <!-- How It Works Section -->
        <div class="py-24 bg-base-200">
            <div class="max-w-7xl mx-auto px-6 lg:px-8">
                <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
                    <!-- Step 1 -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body items-center">
                            <div class="text-3xl font-bold text-primary">1</div>
                            <h3 class="card-title mt-2">Enter a Topic</h3>
                            <p>Simply type in any subject you can think of, from "Ancient History" to "Modern Web Development".</p>
                        </div>
                    </div>
                    <!-- Step 2 -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body items-center">
                            <div class="text-3xl font-bold text-primary">2</div>
                            <h3 class="card-title mt-2">Choose an AI Model</h3>
                            <p>Select from a curated list of powerful Large Language Models (LLMs) to tailor your quiz's style and difficulty.</p>
                        </div>
                    </div>
                    <!-- Step 3 -->
                    <div class="card bg-base-100 shadow-xl">
                        <div class="card-body items-center">
                            <div class="text-3xl font-bold text-primary">3</div>
                            <h3 class="card-title mt-2">Start Answering</h3>
                            <p>The AI generates questions one by one, adapting to the quiz history. Test your knowledge and learn as you go!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <footer class="footer footer-center p-4 bg-base-300 text-base-content">
        <div>
            <p>Copyright © {{ date('Y') }} - All right reserved by Infinite Quiz</p>
        </div>
    </footer>
</div>
</body>
</html>
