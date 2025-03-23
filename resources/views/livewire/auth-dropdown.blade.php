@php
use Filament\Panel;
@endphp

<div>
    @guest
    <div class="relative" x-data="{ isOpen: @entangle('isOpen').live, tab: @entangle('tab').live }"
        @click.away="isOpen = false">
        <!-- Login/Register Toggles -->
        <div class="flex space-x-2">
            <button
                @click="if (tab === 'login' && isOpen) { isOpen = false; } else { isOpen = true; $wire.set('tab', 'login'); }"
                class="px-4 py-2 text-sm font-medium transition-all duration-200 rounded-md hover:bg-gray-100 focus:outline-none border-b-2 {{ $tab === 'login' && $isOpen ? 'border-primary text-primary' : 'border-transparent' }}">
                Log In
            </button>
            <button
                @click="if (tab === 'register' && isOpen) { isOpen = false; } else { isOpen = true; $wire.set('tab', 'register'); }"
                class="px-4 py-2 text-sm font-medium transition-all duration-200 rounded-md bg-primary hover:bg-primary-focus text-white focus:outline-none">
                Register
            </button>
        </div>

        <!-- Dropdown Panel -->
        <div x-show="isOpen" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute z-50 right-0 mt-3 w-80 md:w-96 bg-white rounded-xl shadow-xl ring-1 ring-gray-200 divide-y divide-gray-100">

            <!-- Login Form -->
            <div x-show="tab === 'login'" class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Welcome Back</h3>
                <form wire:submit="submitLoginForm">
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input type="email" wire:model.blur="loginForm.email" id="email"
                                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition">
                            </div>
                            @error('loginForm.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" wire:model.blur="loginForm.password" id="password"
                                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition">
                            </div>
                            @error('loginForm.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                            class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-focus focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <span wire:loading.remove wire:target="submitLoginForm">Sign In</span>
                            <svg wire:loading wire:target="submitLoginForm"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Register Form -->
            <div x-show="tab === 'register'" class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create Your Account</h3>
                <form wire:submit="submitRegisterForm">
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Full Name</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input type="text" wire:model.blur="registerForm.name" id="name"
                                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition">
                            </div>
                            @error('registerForm.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="register-email" class="block text-sm font-medium text-gray-700">Email
                                Address</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input type="email" wire:model.blur="registerForm.email" id="register-email"
                                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition">
                            </div>
                            @error('registerForm.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="register-password"
                                class="block text-sm font-medium text-gray-700">Password</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" wire:model.blur="registerForm.password" id="register-password"
                                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition">
                            </div>
                            @error('registerForm.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm
                                Password</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <input type="password" wire:model.blur="registerForm.password_confirmation"
                                    id="password_confirmation"
                                    class="pl-10 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring focus:ring-primary focus:ring-opacity-20 transition">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit"
                            class="w-full flex justify-center items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary-focus focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            <span wire:loading.remove wire:target="submitRegisterForm">Create Account</span>
                            <svg wire:loading wire:target="submitRegisterForm"
                                class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @else
    <div x-data="{ open: false }" @click.away="open = false" class="relative">
        <!-- User Profile Button -->
        <button @click="open = !open" class="flex items-center space-x-2 rounded-full focus:outline-none"
            id="user-menu-button">
            <div class="flex items-center space-x-3">
                @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
                <img class="h-9 w-9 rounded-full border-2 border-transparent hover:border-primary object-cover transition duration-150 ease-in-out"
                    src="{{ Auth::user()->profile_photo_url }}" alt="{{ Auth::user()->name }}" />
                @endif

                <div class="hidden md:block">
                    <div class="text-sm font-medium text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-gray-500 truncate max-w-[120px]">
                        @if(Auth::user()->username)
                        {{ Auth::user()->username }}
                        @else
                        {{ Auth::user()->email }}
                        @endif
                    </div>
                </div>

                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                    fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clip-rule="evenodd" />
                </svg>
            </div>
        </button>

        <!-- Dropdown Panel -->
        <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            class="absolute right-0 z-50 mt-2 w-48 rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">

            <div class="border-b border-gray-100 pb-2 pt-1 px-4">
                <p class="text-xs text-gray-500">Signed in as</p>
                <p class="text-sm font-medium text-gray-900 truncate">{{ Auth::user()->email }}</p>
            </div>

            <a href="{{ route('dashboard') }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>

            <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                Account Settings
            </a>

            <a href="{{ route('billing') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Billing & Payments
            </a>

            @if(Auth::user()->username)
            <a href="{{ route('profile.username', ['username' => '@' . Auth::user()->username]) }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                My Public Profile
            </a>
            @else
            <a href="{{ route('profile.edit') }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
                Set Up Profile
            </a>
            @endif

            @if(Auth::check() && Auth::user()->canAccessPanel(Panel::make()))
            <a href="{{ route('filament.admin.pages.dashboard') }}"
                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                <svg class="mr-3 h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z" />
                </svg>
                Admin Dashboard
            </a>
            @endif

            <div class="border-t border-gray-100 mt-1"></div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex w-full items-center px-4 py-2 text-sm text-red-700 hover:bg-red-50"
                    role="menuitem">
                    <svg class="mr-3 h-5 w-5 text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Sign Out
                </button>
            </form>
        </div>
    </div>
    @endguest
</div>