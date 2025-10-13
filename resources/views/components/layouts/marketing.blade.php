<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Mix Pitch') }}</title>
    
    {{-- PWA Meta Tags --}}
    <x-pwa-meta />
    
    <!-- SEO Meta Tags -->
    @isset($description)
        <meta name="description" content="{{ $description }}">
    @endisset
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/homepage.css') }}" rel="stylesheet">

    @livewireStyles
    
    <!-- Anti-flash: Apply theme before anything else loads -->
    <script>
        (function() {
            const saved = sessionStorage.getItem('mixpitch_theme');
            if (saved === 'light') {
                document.documentElement.classList.remove('dark');
            } else if (saved === 'dark') {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>

    @stack('styles')
</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <!-- Marketing Navigation Header -->
    <nav class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 sticky top-0 z-50">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center">
                        <img src="{{ asset('images/logo.png') }}" alt="MixPitch" class="h-8 w-auto">
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ url('/') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition-colors {{ request()->is('/') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        Home
                    </a>
                    <a href="{{ route('projects.index') }}" wire:navigate class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('projects.*') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        Projects
                    </a>
                    <a href="{{ route('pricing') }}" wire:navigate class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('pricing') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        Pricing
                    </a>
                    <a href="{{ route('about') }}" wire:navigate class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('about') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        About
                    </a>
                </div>

                <!-- Dark Mode Toggle & Auth Buttons -->
                <div class="flex items-center space-x-4">
                    <!-- Dark Mode Toggle -->
                    <button x-data="{ isDark: document.documentElement.classList.contains('dark') }" 
                            @click="
                                if (typeof window.toggleDarkMode === 'function') {
                                    window.toggleDarkMode();
                                    isDark = document.documentElement.classList.contains('dark');
                                }
                            "
                            x-on:theme-changed.window="isDark = $event.detail.isDark"
                            class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        <svg x-show="isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg x-show="!isDark" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>

                    @guest
                        <!-- Guest Actions -->
                        <div class="hidden md:flex items-center space-x-3">
                            <a href="{{ route('login') }}" class="text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 px-3 py-2 text-sm font-medium transition-colors">
                                Log in
                            </a>
                            <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                Get Started
                            </a>
                        </div>
                    @else
                        <!-- Authenticated User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                <img src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" class="w-8 h-8 rounded-full">
                                <span class="hidden md:block text-sm font-medium">{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            
                            <div x-show="open" @click.away="open = false" x-cloak
                                 class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1">
                                <a href="{{ route('dashboard') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Dashboard
                                </a>
                                <a href="{{ route('profile.edit') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Account Settings
                                </a>
                                <a href="{{ route('billing') }}" wire:navigate class="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                    Billing
                                </a>
                                <div class="border-t border-gray-200 dark:border-gray-600"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700">
                                        Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endguest

                    <!-- Mobile Menu Button -->
                    <div class="md:hidden" x-data="{ open: false }">
                        <button @click="open = !open" 
                                class="p-2 rounded-lg text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        
                        <!-- Mobile Navigation Menu -->
                        <div x-show="open" @click.away="open = false" x-cloak class="absolute top-16 left-0 right-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 pb-4 space-y-2">
                    <a href="{{ url('/') }}" class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->is('/') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        Home
                    </a>
                    <a href="{{ route('projects.index') }}" wire:navigate class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('projects.*') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        Projects
                    </a>
                    <a href="{{ route('pricing') }}" wire:navigate class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('pricing') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        Pricing
                    </a>
                    <a href="{{ route('about') }}" wire:navigate class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors {{ request()->routeIs('about') ? 'text-blue-600 dark:text-blue-400' : '' }}">
                        About
                    </a>
                    
                    @guest
                        <div class="pt-2 border-t border-gray-200 dark:border-gray-600 mt-4">
                            <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-medium text-gray-700 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                Log in
                            </a>
                            <a href="{{ route('register') }}" class="block px-3 py-2 text-base font-medium bg-blue-600 hover:bg-blue-700 text-white rounded-lg mt-2 text-center transition-colors">
                                Get Started
                            </a>
                        </div>
                    @endguest
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main>
        {{ $slot ?? '' }}
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div class="col-span-1 md:col-span-2">
                    <img src="{{ asset('images/logo.png') }}" alt="MixPitch" class="h-8 w-auto mb-4">
                    <p class="text-gray-400 max-w-md">
                        Connecting musicians and audio professionals to create amazing music together.
                    </p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h3 class="font-semibold mb-4">Platform</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('projects.index') }}" wire:navigate class="hover:text-white transition-colors">Browse Projects</a></li>
                        <li><a href="{{ route('pricing') }}" wire:navigate class="hover:text-white transition-colors">Pricing</a></li>
                        <li><a href="{{ route('about') }}" wire:navigate class="hover:text-white transition-colors">About Us</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h3 class="font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; {{ date('Y') }} MixPitch. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    @livewireScripts
    @stack('scripts')

    <!-- Dark mode script -->
    <script>
        function applyDarkMode(isDark) {
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            const themeValue = isDark ? 'dark' : 'light';
            try {
                sessionStorage.setItem('mixpitch_theme', themeValue);
                localStorage.setItem('theme', themeValue);
                window.mixpitchTheme = themeValue;
            } catch (e) {
                console.error('Storage failed:', e);
            }
        }
        
        function getCurrentTheme() {
            let saved = sessionStorage.getItem('mixpitch_theme');
            if (saved) {
                return saved;
            }
            
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            return systemDark ? 'dark' : 'light';
        }
        
        function toggleTheme() {
            const currentIsDark = document.documentElement.classList.contains('dark');
            const newIsDark = !currentIsDark;
            
            applyDarkMode(newIsDark);
            
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { isDark: newIsDark } 
            }));
        }
        
        function applyStoredTheme() {
            const savedTheme = getCurrentTheme();
            applyDarkMode(savedTheme === 'dark');
        }
        
        applyStoredTheme();
        setTimeout(applyStoredTheme, 100);
        
        window.toggleDarkMode = toggleTheme;
    </script>
</body>
</html>