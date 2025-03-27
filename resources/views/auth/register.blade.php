<x-guest-layout>
    @push('scripts')
        {!! app('recaptcha')->htmlScriptTagJsApi([
            'action' => 'register',
            'callback_then' => 'recaptchaCallback',
        ]) !!}
        <script>
            function recaptchaCallback(token) {
                document.getElementById('g-recaptcha-response').value = token;
            }
        </script>
    @endpush
    
    <x-authentication-card>
        <x-slot name="logo">
            <x-authentication-card-logo />
        </x-slot>

        <h2 class="text-2xl font-bold text-center mb-6 text-gray-800">Create Your Account</h2>
        <p class="text-center text-gray-600 mb-8">Join MixPitch and start collaborating</p>

        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <div>
                <x-label for="name" value="{{ __('Name') }}" />
                <x-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required
                    autofocus autocomplete="name" />
            </div>

            <div class="mt-4">
                <x-label for="email" value="{{ __('Email') }}" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required
                    autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="{{ __('Password') }}" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required
                    autocomplete="new-password" />
                <p class="text-xs text-gray-500 mt-1">Must be at least 8 characters</p>
            </div>

            <div class="mt-4">
                <x-label for="password_confirmation" value="{{ __('Confirm Password') }}" />
                <x-input id="password_confirmation" class="block mt-1 w-full" type="password"
                    name="password_confirmation" required autocomplete="new-password" />
            </div>

            @if (Laravel\Jetstream\Jetstream::hasTermsAndPrivacyPolicyFeature())
            <div class="mt-4">
                <x-label for="terms">
                    <div class="flex items-center">
                        <x-checkbox name="terms" id="terms" required />

                        <div class="ml-2 text-gray-600">
                            {!! __('I agree to the :terms_of_service and :privacy_policy', [
                            'terms_of_service' => '<a target="_blank" href="'.route('terms.show').'"
                                class="text-primary hover:text-primary-focus transition-colors">'.__('Terms of
                                Service').'</a>',
                            'privacy_policy' => '<a target="_blank" href="'.route('policy.show').'"
                                class="text-primary hover:text-primary-focus transition-colors">'.__('Privacy
                                Policy').'</a>',
                            ]) !!}
                        </div>
                    </div>
                </x-label>
            </div>
            @endif

            <!-- Add reCAPTCHA v3 - this will be invisible to users -->
            <div class="mt-4">
                <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @if ($errors->has('recaptcha'))
                    <div class="mt-1 text-sm text-red-600">
                        {{ $errors->first('recaptcha') }}
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-end mt-6">
                <a class="text-sm text-primary hover:text-primary-focus transition-colors" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-button class="ml-4">
                    {{ __('Register') }}
                </x-button>
            </div>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-200 text-center">
            <p class="text-gray-600">By signing up, you agree to our
                <a href="#" class="text-primary hover:text-primary-focus transition-colors">Terms of Service</a> and
                <a href="#" class="text-primary hover:text-primary-focus transition-colors">Privacy Policy</a>
            </p>
        </div>
        
        <!-- Social Login Options -->
        <div class="mt-6">
            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">Or sign up with</span>
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
                    Sign up with Google
                </a>
            </div>
        </div>
    </x-authentication-card>
</x-guest-layout>