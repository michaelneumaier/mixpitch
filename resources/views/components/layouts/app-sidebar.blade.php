<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Mix Pitch') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />
    
    
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/homepage.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/star-rating.css') }}">
    <script src="https://unpkg.com/wavesurfer.js"></script>

    @livewireStyles
    <!-- Anti-flash: Apply theme before anything else loads -->
    <script>
        // Immediately apply saved theme to prevent flash
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

        /* Remove default Flux UI padding from main content area */
        [data-flux-main] {
            padding: 0 !important;
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

        /* Hide scrollbar for webkit browsers */
        .scrollbar-hide {
            -ms-overflow-style: none;  /* Internet Explorer 10+ */
            scrollbar-width: none;  /* Firefox */
        }
        .scrollbar-hide::-webkit-scrollbar {
            display: none;  /* Safari and Chrome */
        }

        /* Allow notifications to overflow from sidebar */
        flux\\:sidebar {
            overflow: visible !important;
        }
        
        [data-flux-sidebar] {
            overflow: visible !important;
        }
    </style>

</head>

<body class="font-sans antialiased bg-gray-50 dark:bg-gray-900">
    <flux:sidebar sticky stashable class="bg-gradient-to-br from-blue-50/70 via-purple-50/70 to-pink-50/70 backdrop-blur-lg dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="top" />

        <div class="relative flex items-center justify-between pl-4 py-3 overflow-visible">
            <flux:brand href="{{ url('/') }}" logo="{{ asset('logo.png') }}" class="!p-0" />
            
            @auth
            <!-- Always visible notifications -->
            <div class="relative z-50 overflow-visible">
                <livewire:notification-list />
            </div>
            @endauth
        </div>

        <flux:navlist variant="outline">
            <flux:navlist.item icon="home" href="{{ route('projects.index') }}" :current="request()->routeIs('projects.*')">
                Projects
            </flux:navlist.item>

            @auth
            <flux:navlist.item icon="squares-2x2" href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')">
                Dashboard
            </flux:navlist.item>

            <flux:navlist.item icon="users" href="{{ route('producer.client-management') }}" :current="request()->routeIs('producer.client-management')">
                Client Management
            </flux:navlist.item>

            @if(Auth::check() && Auth::user()->canAccessPanel(\Filament\Panel::make()))
            <flux:navlist.item icon="cog-6-tooth" href="{{ route('filament.admin.pages.dashboard') }}">
                Admin Dashboard
            </flux:navlist.item>
            @endif
            @endauth
        </flux:navlist>

        @auth
        <!-- My Work Section -->
        <flux:navlist variant="outline" class="my-4">
            <livewire:sidebar-work-nav />
        </flux:navlist>

        <!-- Finances Section (with visual separation) -->
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <flux:navlist variant="outline">
                <livewire:sidebar-finances-nav />
            </flux:navlist>
        </div>
        @endauth

        <flux:spacer />

        <!-- Dark Mode Toggle -->
        <div class="px-2 pb-2" 
             x-data="{ 
                isDark: document.documentElement.classList.contains('dark'),
                toggle() {
                    console.log('Button clicked!');
                    if (typeof window.toggleDarkMode === 'function') {
                        window.toggleDarkMode();
                        this.isDark = document.documentElement.classList.contains('dark');
                    } else {
                        console.error('toggleDarkMode function not found!');
                    }
                }
             }"
             x-on:theme-changed.window="isDark = $event.detail.isDark">
            <flux:button x-on:click="toggle()" 
                         variant="ghost" 
                         size="sm" 
                         class="w-full group justify-start">
                <flux:icon.sun x-show="isDark" variant="micro" class="text-gray-500 dark:text-gray-400" />
                <flux:icon.moon x-show="!isDark" variant="micro" class="text-gray-500 dark:text-gray-400" />
                <span class="text-sm text-gray-700 dark:text-gray-300 ml-2" x-text="isDark ? 'Light Mode' : 'Dark Mode'"></span>
            </flux:button>
        </div>

        @auth
        <flux:dropdown position="top" align="start" class="max-w-xs">
            <flux:button variant="ghost" class="group w-full">
                <div class="flex items-center gap-3">
                    <flux:avatar size="sm" src="{{ Auth::user()->profile_photo_url }}" />
                    <div class="text-left min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                            @if(Auth::user()->username)
                                {{ Auth::user()->username }}
                            @else
                                {{ Auth::user()->email }}
                            @endif
                        </div>
                    </div>
                </div>
                <flux:icon.chevron-up-down variant="micro" class="group-data-[open]:rotate-180 duration-300" />
            </flux:button>

            <flux:menu>
                @if(Auth::user()->username)
                <flux:menu.item icon="user" href="{{ route('profile.username', ['username' => '@' . Auth::user()->username]) }}">
                    Public Profile
                </flux:menu.item>
                @endif
                
                <flux:menu.item icon="adjustments-horizontal" href="{{ route('profile.edit') }}">
                    Account Settings
                </flux:menu.item>
                
                <flux:menu.item icon="credit-card" href="{{ route('billing') }}">
                    Billing & Payments
                </flux:menu.item>

                @if(Auth::user()->hasRole('producer') || Auth::user()->hasRole('admin'))
                <flux:menu.item icon="paint-brush" href="{{ route('settings.branding.edit') }}">
                    Branding Settings
                </flux:menu.item>
                @endif

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:menu.item icon="arrow-right-start-on-rectangle" type="submit">
                        Sign out
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
        @else
        <div class="space-y-2 p-4">
            <flux:button href="{{ route('login') }}" variant="ghost" size="sm" class="w-full">
                Log in
            </flux:button>
            <flux:button href="{{ route('register') }}" variant="primary" size="sm" class="w-full">
                Get Started
            </flux:button>
        </div>
        @endauth
    </flux:sidebar>

    <flux:main>
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" />
            
            <flux:spacer />
        </flux:header>

        @if (isset($header))
        <flux:header>
            {{ $header }}
        </flux:header>
        @endif

        <!-- Page Content -->
        <main>
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </flux:main>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.3/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    {{-- Choices.js for Tag Inputs --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

    @yield('scripts')
    @livewireScripts
    @stack('scripts')

    <x-toaster-hub />
    
    {{-- Alpine component to handle URL opening/download --}}
    <div x-data 
         x-on:open-url.window="() => { 
             let url = $event.detail.url;
             let filename = $event.detail.filename;
             console.log('Alpine caught open-url event with URL:', url, 'Filename:', filename); 
             
             if (url) { 
                 const link = document.createElement('a');
                 link.href = url;
                 
                 // Add the download attribute if filename is provided
                 if (filename) {
                    link.setAttribute('download', filename);
                 } else {
                    // Optional: try to extract filename from URL as a fallback
                    try {
                        const urlParts = new URL(url);
                        const pathParts = urlParts.pathname.split('/');
                        link.setAttribute('download', pathParts[pathParts.length - 1]);
                    } catch (e) {
                        console.warn('Could not parse URL to extract filename.');
                    }
                 }
                 
                 // Append to body, click, and remove (modern approach)
                 document.body.appendChild(link);
                 link.click();
                 document.body.removeChild(link);
                 
             } else { 
                 console.error('Received open-url event but URL was missing.'); 
             } 
         }">
        {{-- This div doesn't render anything visible, it just listens for the event --}}
    </div>

    <!-- Global Modals - Positioned at root level for proper z-index behavior -->
    <x-pitch-action-modals />
    
    @fluxScripts
    
    <!-- Dark mode fix - runs after all other scripts -->
    <script>
        console.log('=== MIXPITCH DARK MODE (AFTER FLUX) ===');
        
        function applyDarkMode(isDark) {
            console.log('Applying dark mode:', isDark);
            
            if (isDark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
            
            // Store preference
            const themeValue = isDark ? 'dark' : 'light';
            try {
                sessionStorage.setItem('mixpitch_theme', themeValue);
                localStorage.setItem('theme', themeValue);
                window.mixpitchTheme = themeValue;
                console.log('Stored theme:', themeValue);
            } catch (e) {
                console.error('Storage failed:', e);
            }
        }
        
        function getCurrentTheme() {
            // Check sessionStorage first (most reliable)
            let saved = sessionStorage.getItem('mixpitch_theme');
            if (saved) {
                console.log('Found saved theme:', saved);
                return saved;
            }
            
            // Fallback to system preference
            const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            console.log('Using system preference:', systemDark);
            return systemDark ? 'dark' : 'light';
        }
        
        function toggleTheme() {
            console.log('=== TOGGLE CALLED ===');
            const currentIsDark = document.documentElement.classList.contains('dark');
            const newIsDark = !currentIsDark;
            
            console.log('Toggle:', currentIsDark, '→', newIsDark);
            applyDarkMode(newIsDark);
            
            // Update Alpine component
            window.dispatchEvent(new CustomEvent('theme-changed', { 
                detail: { isDark: newIsDark } 
            }));
        }
        
        // Override Flux's theme application with our stored preference
        function applyStoredTheme() {
            const savedTheme = getCurrentTheme();
            console.log('Applying stored theme on load:', savedTheme);
            applyDarkMode(savedTheme === 'dark');
        }
        
        // Apply immediately and after a short delay to override Flux
        applyStoredTheme();
        setTimeout(applyStoredTheme, 100);
        setTimeout(applyStoredTheme, 500);
        
        // Make toggle function global
        window.toggleDarkMode = toggleTheme;
        
        console.log('=== DARK MODE READY ===');
    </script>
</body>
</html>