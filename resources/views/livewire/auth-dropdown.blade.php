<div>
    @guest
    <div class="relative" x-data="{ isOpen: @entangle('isOpen').live }" @click.away="isOpen = false">
        <button
            @click="if ($wire.tab === 'login' && isOpen) { isOpen = false; } else { isOpen = true; $wire.set('tab', 'login'); }"
            class="focus:outline-none border-b-2 {{ $tab === 'login' && $isOpen ? 'border-primary' : 'border-transparent' }}">
            <span class="font-bold">Login</span>
        </button>
        &nbsp;
        <button
            @click="if ($wire.tab === 'register' && isOpen) { isOpen = false; } else { isOpen = true; $wire.set('tab', 'register'); }"
            class="focus:outline-none border-b-2 {{ $tab === 'register' && $isOpen ? 'border-primary' : 'border-transparent' }}">
            Register
        </button>

        <!-- Dropdown Body -->
        @if($isOpen)
        <div x-show="isOpen"
            class="absolute z-50 right-0 mt-2 w-72 md:w-96 p-4 bg-base-200 bg-opacity-50 backdrop-blur-md rounded-md shadow-lg ring-1 ring-black ring-opacity-5">

            <!-- Login Form -->
            @if($tab === 'login')
            <form wire:submit="submitLoginForm">
                <div class="mb-4">
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
                    <input type="email" wire:model.blur="loginForm.email" class="w-full px-3 py-2 border rounded-md">
                    @error('loginForm.email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Password</label>
                    <input type="password" wire:model.blur="loginForm.password"
                        class="w-full px-3 py-2 border rounded-md">
                    @error('loginForm.password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4 flex items-center">
                    <button type="submit" class="btn btn-primary mr-3">Login</button>

                    <!-- Tailwind Spinner for Login -->
                    <span wire:loading.target="submitLoginForm" hidden
                        class="w-4 h-4 border-t-2 border-r-2 border-blue-500 rounded-full animate-spin ml-2"></span>

                </div>
            </form>

            @endif

            <!-- Register Form -->
            @if($tab === 'register')
            <form wire:submit="submitRegisterForm">
                <div class="mb-4">
                    <label for="name" class="block mb-2 text-sm font-medium text-gray-600">Name</label>
                    <input type="text" wire:model.blur="registerForm.name" class="w-full px-3 py-2 border rounded-md">
                    @error('registerForm.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
                    <input type="email" wire:model.blur="registerForm.email" class="w-full px-3 py-2 border rounded-md">
                    @error('registerForm.email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Password</label>
                    <input type="password" wire:model.blur="registerForm.password"
                        class="w-full px-3 py-2 border rounded-md">
                    @error('registerForm.password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
                <div class="mb-4">
                    <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-600">Confirm
                        Password</label>
                    <input type="password" wire:model.blur="registerForm.password_confirmation"
                        class="w-full px-3 py-2 border rounded-md">
                </div>
                <div class="mb-4">
                    <button type="submit" class="btn btn-primary">Register</button>
                    <span wire:loading.target="submitRegisterForm" hidden
                        class="w-4 h-4 border-t-2 border-r-2 border-blue-500 rounded-full animate-spin ml-2"></span>
                </div>
            </form>
            @endif
        </div>
        @endif
    </div>
    @else
    <div x-data="{ open: false }" @click.away="open = false" class="relative cursor-default">
        <div class="flex items-center md:px-4" @click="open = !open">
            @if (Laravel\Jetstream\Jetstream::managesProfilePhotos())
            <div class="shrink-0 mr-3">
                <img class="h-10 w-10 rounded-full object-cover" src="{{ Auth::user()->profile_photo_url }}"
                    alt="{{ Auth::user()->name }}" />
            </div>
            @endif

            <div>
                <span class="hidden md:flex text-base text-gray-800 dark:text-gray-200 max-w-xs truncate">
                    {{
                    Auth::user()->name
                    }}
                </span>
                <!-- <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div> -->
            </div>
        </div>
        <!-- <button @click="open = !open" class="block">
                        {{ Auth::user()->name }}
                    </button> -->

        <!-- Dropdown Body -->
        <div x-cloak x-show="open"
            class="origin-top-right absolute z-50 right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5">
            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="options-menu">
                <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    role="menuitem">Dashboard</a>
                <div class="border-t border-gray-100"></div>
                <a href="{{ route('profile.show') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    role="menuitem">User
                    Profile</a>
                <div class="border-t border-gray-100"></div>
                <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                    Logout
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </div>
    </div>
    @endguest
</div>