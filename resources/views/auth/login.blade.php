<x-guest-layout>
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Welcome Back!</h2>
        <p class="text-center text-gray-600 mb-8">Sign in to your MixPitch account</p>

        <x-validation-errors class="mb-4" />

        @if (session('status'))
        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium text-green-800">
                        {{ session('status') }}
                    </p>
                </div>
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="login-form">
            @csrf

            <div>
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                    autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required
                    autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <label for="remember_me" class="inline-flex items-center">
                    <x-checkbox id="remember_me" name="remember" />
                    <span class="ml-2 text-gray-600">{{ __('Remember me') }}</span>
                </label>
            </div>

            <div class="flex items-center justify-between mt-6">
                @if (Route::has('password.request'))
                <a class="text-sm text-primary hover:text-primary-focus transition-colors"
                    href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
                @endif

                <x-button id="login-button" type="submit">
                    <span id="login-text">{{ __('Log in') }}</span>
                    <svg id="login-spinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </x-button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-gray-600">Don't have an account?
                <a href="{{ route('register') }}"
                    class="text-primary hover:text-primary-focus transition-colors font-medium">
                    Sign up
                </a>
            </p>
        </div>
        
        <!-- Social Login Options -->
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Or continue with</span>
                </div>
            </div>
            
            <div class="mt-6">
                <a href="{{ route('socialite.redirect', ['provider' => 'google']) }}" 
                   class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                    <svg class="h-5 w-5 mr-2" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 48 48">
                        <defs><path id="a" d="M44.5 20H24v8.5h11.8C34.7 33.9 30.1 37 24 37c-7.2 0-13-5.8-13-13s5.8-13 13-13c3.1 0 5.9 1.1 8.1 2.9l6.4-6.4C34.6 4.1 29.6 2 24 2 11.8 2 2 11.8 2 24s9.8 22 22 22c11 0 21-8 21-22 0-1.3-.2-2.7-.5-4z"/></defs>
                        <clipPath id="b"><use xlink:href="#a" overflow="visible"/></clipPath>
                        <path clip-path="url(#b)" fill="#FBBC05" d="M0 37V11l17 13z"/>
                        <path clip-path="url(#b)" fill="#EA4335" d="M0 11l17 13 7-6.1L48 14V0H0z"/>
                        <path clip-path="url(#b)" fill="#34A853" d="M0 37l30-23 7.9 1L48 0v48H0z"/>
                        <path clip-path="url(#b)" fill="#4285F4" d="M48 48L17 24l-4-3 35-10z"/>
                    </svg>
                    Sign in with Google
                </a>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('login-form');
        const button = document.getElementById('login-button');
        const buttonText = document.getElementById('login-text');
        const spinner = document.getElementById('login-spinner');
        
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