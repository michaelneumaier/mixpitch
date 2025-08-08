@php
use Filament\Panel;
@endphp

<!-- Next-Level Navigation -->
<nav x-data="{ open: false }" class="relative bg-white/95 backdrop-blur-md border-b border-white/20 sticky top-0 z-50 shadow-lg">
    <!-- Background Effects -->
    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
    
    <!-- Primary Navigation Menu -->
    <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center group">
                        <img src="{{ asset('logo.png') }}" alt="MixPitch Logo" class="h-10 w-auto mr-3">
                    </a>
                </div>

                <!-- Navigation Links (Desktop) -->
                <div class="hidden sm:ml-10 sm:flex sm:items-center sm:space-x-1">
                    <a href="{{ route('projects.index') }}"
                        class="group relative inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('projects.*') ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 {{ request()->routeIs('projects.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-500' }} transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Projects
                        @if(!request()->routeIs('projects.*'))
                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        @endif
                    </a>
                    
                    <a href="{{ route('pricing') }}"
                        class="group relative inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('pricing') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 {{ request()->routeIs('pricing') ? 'text-white' : 'text-gray-500 group-hover:text-pink-500' }} transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                        Pricing
                        @if(!request()->routeIs('pricing'))
                        <div class="absolute inset-0 bg-gradient-to-r from-purple-500/20 to-pink-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        @endif
                    </a>
                    
                    <a href="{{ route('about') }}"
                        class="group relative inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-300 {{ request()->routeIs('about') ? 'bg-gradient-to-r from-indigo-500 to-blue-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 {{ request()->routeIs('about') ? 'text-white' : 'text-gray-500 group-hover:text-blue-500' }} transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        About
                        @if(!request()->routeIs('about'))
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500/20 to-blue-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        @endif
                    </a>
                </div>
            </div>

            <!-- Right Side Items -->
            <div class="hidden sm:flex sm:items-center sm:ml-6 space-x-3">
                @auth
                <!-- Notifications -->
                <div class="relative mr-2">
                    <livewire:notification-list />
                </div>

                <!-- Auth Dropdown -->
                <livewire:auth-dropdown />
                
                @else
                <a href="{{ route('login') }}"
                    class="group relative inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2 text-gray-500 group-hover:text-blue-500 transition-colors duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Log in
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-500/20 to-purple-500/20 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                </a>
                
                <a href="{{ route('register') }}"
                    class="group relative overflow-hidden inline-flex items-center px-6 py-2 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold rounded-xl transition-all duration-300 transform hover:scale-105 hover:shadow-xl">
                    <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                    <span class="relative flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        Get Started
                    </span>
                </a>
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
                    class="group inline-flex items-center justify-center p-2 rounded-xl text-gray-500 hover:text-gray-700 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md focus:outline-none focus:bg-white/60 focus:text-gray-700 transition-all duration-300">
                    <svg class="h-6 w-6 transition-transform duration-300" :class="{'rotate-90': open}" stroke="currentColor" fill="none" viewBox="0 0 24 24">
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
        <div class="relative bg-white/95 backdrop-blur-md border-t border-white/20">
            <!-- Background Effects -->
            <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
            
            <div class="relative z-10 pt-2 pb-3 space-y-1">
                <a href="{{ route('projects.index') }}"
                    class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl {{ request()->routeIs('projects.*') ? 'bg-gradient-to-r from-blue-500 to-purple-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }} text-base font-medium transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 mr-3 {{ request()->routeIs('projects.*') ? 'text-white' : 'text-gray-500 group-hover:text-purple-500' }} transition-colors duration-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Projects
                </a>
                
                <a href="{{ route('pricing') }}"
                    class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl {{ request()->routeIs('pricing') ? 'bg-gradient-to-r from-purple-500 to-pink-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }} text-base font-medium transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 mr-3 {{ request()->routeIs('pricing') ? 'text-white' : 'text-gray-500 group-hover:text-pink-500' }} transition-colors duration-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Pricing
                </a>
                
                <a href="{{ route('about') }}"
                    class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl {{ request()->routeIs('about') ? 'bg-gradient-to-r from-indigo-500 to-blue-500 text-white shadow-lg' : 'text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md' }} text-base font-medium transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 mr-3 {{ request()->routeIs('about') ? 'text-white' : 'text-gray-500 group-hover:text-blue-500' }} transition-colors duration-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    About
                </a>
                
                @auth
                <a href="{{ route('dashboard') }}"
                    class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md text-base font-medium transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500 group-hover:text-green-500 transition-colors duration-300" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    Dashboard
                </a>

                @if(Auth::check() && Auth::user()->canAccessPanel(Panel::make()))
                <a href="{{ route('filament.admin.pages.dashboard') }}"
                    class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl text-gray-700 hover:text-gray-900 hover:bg-white/60 hover:backdrop-blur-md hover:shadow-md text-base font-medium transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500 group-hover:text-indigo-500 transition-colors duration-300" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                    </svg>
                    Admin Dashboard
                </a>
                @endif

                @if(Auth::check() && !Auth::user()->hasCompletedProfile())
                <a href="{{ route('profile.edit') }}"
                    class="group flex items-center pl-4 pr-4 py-3 mx-3 rounded-xl bg-gradient-to-r from-amber-500 to-orange-500 text-white shadow-lg text-base font-medium transition-all duration-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-white" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Complete Profile
                </a>
                @endif
                @endauth
            </div>

            <!-- Mobile Authentication Links -->
            <div class="border-t border-white/20 bg-white/50 backdrop-blur-md">
                @auth
                <div class="flex items-center px-4 py-4">
                    <div class="shrink-0 mr-3">
                        <img class="h-12 w-12 rounded-full object-cover border-2 border-purple-200 shadow-lg"
                            src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}">
                    </div>
                    <div>
                        <div class="font-semibold text-base text-gray-900">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-gray-600 truncate max-w-[200px]">
                            @if(Auth::user()->username)
                            {{ Auth::user()->username }}
                            @else
                            {{ Auth::user()->email }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="mt-3 space-y-2 px-3 pb-3">
                    @if (Auth::user()->username)
                    <a href="{{ route('profile.username', ['username' => '@' . Auth::user()->username]) }}"
                        class="group flex items-center px-4 py-3 bg-white/60 backdrop-blur-md rounded-xl text-gray-700 hover:text-gray-900 hover:bg-white/80 hover:shadow-md transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500 group-hover:text-blue-500 transition-colors duration-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="text-base font-medium">Public Profile</span>
                    </a>
                    @endif

                    <a href="{{ route('profile.edit') }}"
                        class="group flex items-center px-4 py-3 bg-white/60 backdrop-blur-md rounded-xl text-gray-700 hover:text-gray-900 hover:bg-white/80 hover:shadow-md transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-gray-500 group-hover:text-purple-500 transition-colors duration-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <span class="text-base font-medium">Account Settings</span>
                    </a>

                    <!-- Logout Button -->
                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <button type="submit"
                            class="group flex w-full items-center px-4 py-3 bg-gradient-to-r from-red-500 to-pink-500 text-white rounded-xl hover:from-red-600 hover:to-pink-600 shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-3 text-white" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span class="font-semibold text-base">Sign Out</span>
                        </button>
                    </form>
                </div>
                @else
                <div class="space-y-3 px-4 py-4">
                    <div class="text-center mb-4">
                        <p class="text-sm font-medium text-gray-600 mb-3">Join the MixPitch community</p>
                    </div>
                    
                    <a href="{{ route('login') }}"
                        class="group flex items-center justify-center px-4 py-3 bg-white/60 backdrop-blur-md border border-white/40 rounded-xl text-gray-700 hover:text-gray-900 hover:bg-white/80 hover:shadow-md transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-500 group-hover:text-blue-500 transition-colors duration-300" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>
                        <span class="font-semibold">Log In</span>
                    </a>

                    <a href="{{ route('register') }}"
                        class="group relative overflow-hidden flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                        <span class="relative flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                            <span class="font-semibold">Create Account</span>
                        </span>
                    </a>
                </div>
                @endauth
            </div>
        </div>
    </div>
</nav>