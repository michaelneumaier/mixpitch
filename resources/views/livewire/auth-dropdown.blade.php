<div class="relative" x-data="{ isOpen: @entangle('isOpen') }" @click.away="isOpen = false">

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
        <form wire:submit.prevent="submitLoginForm">
            <div class="mb-4">
                <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
                <input type="email" wire:model.lazy="loginForm.email" class="w-full px-3 py-2 border rounded-md">
                @error('loginForm.email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Password</label>
                <input type="password" wire:model.lazy="loginForm.password" class="w-full px-3 py-2 border rounded-md">
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
        <form wire:submit.prevent="submitRegisterForm">
            <div class="mb-4">
                <label for="name" class="block mb-2 text-sm font-medium text-gray-600">Name</label>
                <input type="text" wire:model.lazy="registerForm.name" class="w-full px-3 py-2 border rounded-md">
                @error('registerForm.name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="email" class="block mb-2 text-sm font-medium text-gray-600">Email</label>
                <input type="email" wire:model.lazy="registerForm.email" class="w-full px-3 py-2 border rounded-md">
                @error('registerForm.email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="password" class="block mb-2 text-sm font-medium text-gray-600">Password</label>
                <input type="password" wire:model.lazy="registerForm.password"
                    class="w-full px-3 py-2 border rounded-md">
                @error('registerForm.password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="password_confirmation" class="block mb-2 text-sm font-medium text-gray-600">Confirm
                    Password</label>
                <input type="password" wire:model.lazy="registerForm.password_confirmation"
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