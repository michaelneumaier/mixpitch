@php
use Filament\Panel;
@endphp

<nav x-data="{ open: false }" class="bg-base-100 border-b border-base-200 sticky top-0 z-50 shadow-sm">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center">
                        <div class="text-2xl hidden font-bold text-primary">MixPitch</div>
                        <img src="{{ asset('logo.png') }}" alt="MixPitch Logo" class="h-7 w-auto mb-1">
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden sm:ml-10 sm:flex sm:items-center sm:space-x-8">
                    <a href="{{ route('projects.index') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('projects.*') ? 'border-primary text-primary font-semibold' : 'border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-800' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        Projects
                    </a>
                    <a href="{{ route('pricing') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('pricing') ? 'border-primary text-primary font-semibold' : 'border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-800' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        Pricing
                    </a>
                    <a href="{{ route('about') }}"
                        class="inline-flex items-center px-1 pt-1 border-b-2 {{ request()->routeIs('about') ? 'border-primary text-primary font-semibold' : 'border-transparent hover:border-gray-300 text-gray-600 hover:text-gray-800' }} text-sm font-medium leading-5 transition duration-150 ease-in-out">
                        About
                    </a>
                </div>
            </div>

            <!-- Right Side Items -->
            <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-3">
                @auth
                <!-- Notifications -->
                <div class="relative mr-4">
                    <livewire:notification-list />
                </div>

                <!-- Auth Dropdown -->
                <livewire:auth-dropdown />
                @else
                <a href="{{ route('login') }}"
                    class="text-sm text-gray-600 hover:text-gray-800 px-3 py-2 rounded-md hover:bg-gray-100 transition-all duration-150">Log
                    in</a>
                <a href="{{ route('register') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-focus active:bg-primary-focus focus:outline-none focus:border-primary-focus focus:ring focus:ring-primary-200 disabled:opacity-25 transition">Register</a>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="flex items-center -mr-2 sm:hidden">
                @auth
                <!-- Notifications -->
                <div class="relative mr-4">
                    <livewire:notification-list />
                </div>
                @endauth
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-700 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1 bg-gradient-to-r from-base-100 to-base-200">
            <a href="{{ route('projects.index') }}"
                class="flex items-center pl-3 pr-4 py-3 border-l-4 {{ request()->routeIs('projects.*') ? 'border-primary text-primary bg-primary/5 font-semibold' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 hover:bg-gray-50' }} text-base font-medium transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-3 {{ request()->routeIs('projects.*') ? 'text-primary' : 'text-gray-400' }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                Projects
            </a>
            <a href="{{ route('pricing') }}"
                class="flex items-center pl-3 pr-4 py-3 border-l-4 {{ request()->routeIs('pricing') ? 'border-primary text-primary bg-primary/5 font-semibold' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 hover:bg-gray-50' }} text-base font-medium transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-3 {{ request()->routeIs('pricing') ? 'text-primary' : 'text-gray-400' }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Pricing
            </a>
            <a href="{{ route('about') }}"
                class="flex items-center pl-3 pr-4 py-3 border-l-4 {{ request()->routeIs('about') ? 'border-primary text-primary bg-primary/5 font-semibold' : 'border-transparent text-gray-600 hover:text-gray-800 hover:border-gray-300 hover:bg-gray-50' }} text-base font-medium transition duration-150 ease-in-out">
                <svg xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 mr-3 {{ request()->routeIs('about') ? 'text-primary' : 'text-gray-400' }}"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                About
            </a>
            <a href="{{ route('dashboard') }}"
                class="flex items-center px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-base font-medium">Dashboard</span>
            </a>

            @if(Auth::check() && Auth::user()->canAccessPanel(Panel::make()))
            <a href="{{ route('filament.admin.pages.dashboard') }}"
                class="flex items-center px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                <span class="text-base font-medium">Admin Dashboard</span>
            </a>
            @endif

            @if(Auth::check() && !Auth::user()->hasCompletedProfile())
            <a href="{{ route('profile.edit') }}"
                class="flex items-center px-4 py-3 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-indigo-500" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <span class="text-base font-medium">Set Up Profile</span>
            </a>
            @endif
        </div>

        <!-- Mobile Authentication Links -->
        <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
            @auth
            <div class="flex items-center px-4 py-3">
                <div class="shrink-0 mr-3">
                    <img class="h-12 w-12 rounded-full object-cover border-2 border-primary/20"
                        src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
                </div>
                <div>
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500 truncate max-w-[200px]">
                        @if(Auth::user()->username)
                        {{ Auth::user()->username }}
                        @else
                        {{ Auth::user()->email }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-3 space-y-1 bg-white rounded-lg mx-3 overflow-hidden shadow-sm border border-gray-100">
                @if (Auth::user()->username)
                <a href="{{ route('profile.username', ['username' => '@' . Auth::user()->username]) }}"
                    class="flex items-center px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    <span class="text-base font-medium">Public Profile</span>
                </a>
                @endif

                <a href="{{ route('profile.edit') }}"
                    class="flex items-center px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="text-base font-medium">Account Settings</span>
                </a>

                <a href="{{ route('dashboard') }}"
                    class="flex items-center px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <span class="text-base font-medium">Dashboard</span>
                </a>

                @if(Auth::check() && Auth::user()->canAccessPanel(Panel::make()))
                <a href="{{ route('filament.admin.pages.dashboard') }}"
                    class="flex items-center px-4 py-3 text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    <span class="text-base font-medium">Admin Dashboard</span>
                </a>
                @endif

                @if(Auth::check() && !Auth::user()->hasCompletedProfile())
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center px-4 py-3 text-indigo-700 bg-indigo-50 hover:bg-indigo-100 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-indigo-500" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="text-base font-medium">Set Up Profile</span>
                </a>
                @endif
            </div>

            <!-- Logout Button -->
            <div class="mt-3 mx-3">
                <form method="POST" action="{{ route('logout') }}" x-data>
                    @csrf
                    <button type="submit"
                        class="flex w-full items-center justify-center px-4 py-3 bg-red-50 text-red-700 rounded-lg border border-red-100 hover:bg-red-100 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-red-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        <span class="font-medium text-base">Sign Out</span>
                    </button>
                </form>
            </div>
            @else
            <div class="space-y-3 px-4 py-2">
                <div class="flex flex-col space-y-2">
                    <p class="text-sm font-medium text-gray-500 mb-1">Already have an account?</p>
                    <a href="{{ route('login') }}"
                        class="flex items-center justify-center px-4 py-2 bg-white border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span class="font-medium">Log In</span>
                    </a>
                </div>

                <div class="flex flex-col space-y-2">
                    <p class="text-sm font-medium text-gray-500 mb-1">New to MixPitch?</p>
                    <a href="{{ route('register') }}"
                        class="flex items-center justify-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary-focus transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="font-medium">Create Account</span>
                    </a>
                </div>
            </div>
            @endauth
        </div>
    </div>
</nav>