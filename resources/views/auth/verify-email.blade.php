<x-guest-layout>
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 relative">
        <!-- Background Effects -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
        
        <!-- Decorative Elements -->
        <div class="absolute top-20 left-10 w-20 h-20 bg-blue-200/30 rounded-full blur-xl"></div>
        <div class="absolute bottom-20 right-10 w-32 h-32 bg-purple-200/30 rounded-full blur-xl"></div>
        <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-pink-200/30 rounded-full blur-xl"></div>

        <!-- Logo Section -->
        <div class="relative z-10 mb-8">
            <a href="/" class="flex items-center group">
                <img src="{{ asset('logo.png') }}" alt="MixPitch Logo" class="h-12 w-auto mr-3 transition-transform duration-300 group-hover:scale-105">
            </a>
        </div>

        <!-- Main Card -->
        <div class="relative z-10 w-full sm:max-w-md">
            <div class="bg-white/95 backdrop-blur-md shadow-xl border border-white/20 rounded-2xl p-8 space-y-6">
                <!-- Header -->
                <div class="text-center space-y-2">
                    <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl mx-auto mb-4 shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">Verify Your Email</h1>
                    <p class="text-gray-600">Check your inbox for a verification link</p>
                </div>

                <!-- Description -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-500 mt-0.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-blue-800">
                                Before continuing, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Success Message -->
                @if (session('status') == 'verification-link-sent')
                <div class="bg-green-50 border border-green-200 rounded-xl p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                A new verification link has been sent to the email address you provided in your profile settings.
                            </p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Actions -->
                <div class="space-y-4">
                    <!-- Resend Verification Button -->
                    <form method="POST" action="{{ route('verification.send') }}" id="verification-form">
                        @csrf
                        <button type="submit" id="verification-button"
                            class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-xl text-white bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-[transform,colors,shadow] duration-200 hover:scale-[1.02] shadow-lg hover:shadow-xl disabled:opacity-75 disabled:cursor-not-allowed disabled:transform-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 7.89a2 2 0 002.83 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span id="verification-text">Resend Verification Email</span>
                            <svg id="verification-spinner" class="hidden animate-spin ml-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>

                    <!-- Secondary Actions -->
                    <div class="flex flex-col sm:flex-row gap-2 pt-4 border-t border-gray-100">
                        <a href="{{ route('profile.edit') }}"
                            class="group relative flex-1 flex justify-center items-center py-2.5 px-4 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-[colors,border-color] duration-200 hover:border-blue-300 hover:text-blue-700">
                            <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            Edit Profile
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="flex-1">
                            @csrf
                            <button type="submit"
                                class="group relative w-full flex justify-center items-center py-2.5 px-4 border border-gray-300 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-[colors,border-color] duration-200 hover:border-red-300 hover:text-red-700">
                                <svg class="w-4 h-4 mr-2 text-gray-500 group-hover:text-red-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 mt-8 text-center">
            <p class="text-sm text-gray-500">
                © {{ date('Y') }} MixPitch. All rights reserved.
            </p>
        </div>
    </div>
</x-guest-layout>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('verification-form');
        const button = document.getElementById('verification-button');
        const buttonText = document.getElementById('verification-text');
        const spinner = document.getElementById('verification-spinner');
        
        form.addEventListener('submit', function() {
            // Disable the button
            button.disabled = true;
            
            // Show loading spinner
            buttonText.textContent = 'Sending...';
            spinner.classList.remove('hidden');
        });
    });
</script>
