<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5 dark:from-blue-600/10 dark:via-purple-600/10 dark:to-pink-600/10"></div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-20 left-10 w-20 h-20 bg-blue-200/30 dark:bg-blue-600/20 rounded-full blur-xl"></div>
        <div class="absolute bottom-20 right-10 w-32 h-32 bg-purple-200/30 dark:bg-purple-600/20 rounded-full blur-xl"></div>
        <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-pink-200/30 dark:bg-pink-600/20 rounded-full blur-xl"></div>

        <!-- Logo Section -->
        <div class="relative z-10 mb-8">
            <a href="/" class="flex items-center group">
                <img src="{{ asset('logo.png') }}" alt="MixPitch Logo" class="h-12 w-auto mr-3 transition-transform duration-300 group-hover:scale-105">
                <div class="text-3xl font-bold">
                    <span class="text-gray-600 dark:text-gray-400">Mix</span><span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">Pitch</span>
                </div>
            </a>
        </div>

        <!-- Main Card -->
        <div class="relative z-10 w-full sm:max-w-md">
            <div class="bg-white/95 dark:bg-gray-800/95 backdrop-blur-md shadow-xl border border-white/20 dark:border-gray-700/50 rounded-2xl p-8 space-y-6">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Reset Password</h1>
                    <p class="text-gray-600 dark:text-gray-400">Enter your email to receive a reset link</p>
                </div>

                <!-- Description -->
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Status Message -->
                @if (session('status'))
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800 dark:text-green-300">{{ session('status') }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Validation Errors -->
                <x-validation-errors class="mb-4" />

                <!-- Reset Form -->
                <form method="POST" action="{{ route('password.email') }}" id="forgot-password-form" class="space-y-6">
                    @csrf

                    <!-- Email Field -->
                    <div class="space-y-2">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl shadow-sm placeholder-gray-400 dark:placeholder-gray-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400 transition-all duration-200"
                                placeholder="Enter your email address">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="forgot-password-button"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-[1.02] shadow-lg hover:shadow-xl disabled:opacity-75 disabled:cursor-not-allowed disabled:transform-none">
                        <span id="forgot-password-text">Send Reset Link</span>
                        <svg id="forgot-password-spinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </button>
                </form>

                <!-- Back to Login -->
                <div class="text-center pt-4 border-t border-gray-100 dark:border-gray-700">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Remember your password?
                        <a href="{{ route('login') }}" wire:navigate class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 dark:hover:text-blue-300 transition-colors">
                            Back to sign in
                        </a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 mt-8 text-center">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                © {{ date('Y') }} MixPitch. All rights reserved.
            </p>
        </div>
    </div>
</x-guest-layout>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('forgot-password-form');
        const button = document.getElementById('forgot-password-button');
        const buttonText = document.getElementById('forgot-password-text');
        const spinner = document.getElementById('forgot-password-spinner');
        
        form.addEventListener('submit', function() {
            // Disable the button
            button.disabled = true;
            
            // Show loading spinner
            buttonText.textContent = 'Sending...';
            spinner.classList.remove('hidden');
        });
    });
</script>
