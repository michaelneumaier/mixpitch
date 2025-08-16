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
    @fluxAppearance
    
    <link href="{{ asset('css/custom.css') }}" rel="stylesheet">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('css/homepage.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/star-rating.css') }}">
    <script src="https://unpkg.com/wavesurfer.js"></script>

    @livewireStyles

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
</head>

<body class="font-sans antialiased bg-gray-50">
    <flux:sidebar sticky stashable class="bg-white border-r border-gray-200">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="top" />

        <flux:brand href="{{ url('/') }}" logo="{{ asset('logo.png') }}" name="MixPitch" class="px-2 dark:hidden" />
        <flux:brand href="{{ url('/') }}" logo="{{ asset('logo.png') }}" name="MixPitch" class="px-2 hidden dark:block" />

        <flux:navlist variant="outline">
            <flux:navlist.item icon="home" href="{{ route('projects.index') }}" :current="request()->routeIs('projects.*')">
                Projects
            </flux:navlist.item>
            
            <flux:navlist.item icon="currency-dollar" href="{{ route('pricing') }}" :current="request()->routeIs('pricing')">
                Pricing
            </flux:navlist.item>
            
            <flux:navlist.item icon="information-circle" href="{{ route('about') }}" :current="request()->routeIs('about')">
                About
            </flux:navlist.item>

            @auth
            <flux:navlist.item icon="squares-2x2" href="{{ route('dashboard') }}" :current="request()->routeIs('dashboard')">
                Dashboard
            </flux:navlist.item>

            @if(Auth::check() && Auth::user()->canAccessPanel(\Filament\Panel::make()))
            <flux:navlist.item icon="cog-6-tooth" href="{{ route('filament.admin.pages.dashboard') }}">
                Admin Dashboard
            </flux:navlist.item>
            @endif
            @endauth
        </flux:navlist>

        <flux:spacer />

        @auth
        <flux:navlist variant="outline">
            @if(Auth::user()->username)
            <flux:navlist.item icon="user" href="{{ route('profile.username', ['username' => '@' . Auth::user()->username]) }}">
                Public Profile
            </flux:navlist.item>
            @endif
            
            <flux:navlist.item icon="adjustments-horizontal" href="{{ route('profile.edit') }}">
                Account Settings
            </flux:navlist.item>
            
            <flux:navlist.item icon="credit-card" href="{{ route('billing') }}">
                Billing & Payments
            </flux:navlist.item>

            @if(Auth::user()->hasRole('producer') || Auth::user()->hasRole('admin'))
            <flux:navlist.item icon="paint-brush" href="{{ route('settings.branding.edit') }}">
                Branding Settings
            </flux:navlist.item>
            @endif
        </flux:navlist>

        <flux:separator />

        <flux:dropdown position="top" align="start" class="max-w-xs">
            <flux:button variant="ghost" class="group w-full">
                <div class="flex items-center gap-3">
                    <flux:avatar size="sm" src="{{ Auth::user()->profile_photo_url }}" />
                    <div class="text-left min-w-0 flex-1">
                        <div class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->name }}</div>
                        <div class="text-xs text-gray-500 truncate">
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

            @auth
            <!-- Notifications -->
            <livewire:notification-list />
            @endauth
        </flux:header>

        @if (isset($header))
        <flux:header>
            {{ $header }}
        </flux:header>
        @endif

        <!-- Page Content -->
        <main class="p-6">
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
</body>
</html>