<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('password.confirm') }}" id="confirm-password-form">
            @csrf

            <div>
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" autofocus />
            </div>

            <div class="flex justify-end mt-4">
                <x-button id="confirm-password-button" class="ml-4">
                    <span id="confirm-password-text">{{ __('Confirm') }}</span>
                    <svg id="confirm-password-spinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </x-button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('confirm-password-form');
        const button = document.getElementById('confirm-password-button');
        const buttonText = document.getElementById('confirm-password-text');
        const spinner = document.getElementById('confirm-password-spinner');
        
        form.addEventListener('submit', function() {
            // Disable the button
            button.disabled = true;
            button.classList.add('opacity-75', 'cursor-not-allowed');
            
            // Show loading spinner
            buttonText.classList.add('mr-2');
            spinner.classList.remove('hidden');
        });
    });
</script>
