<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-100">
                    {{ __('Payment Method') }}
                </h3>
                
                @if($hasPaymentMethod)
                    <x-filament::button wire:click="togglePaymentMethodForm" color="primary" size="sm">
                        {{ __('Update Payment Method') }}
                    </x-filament::button>
                @else
                    <x-filament::button wire:click="togglePaymentMethodForm" color="primary" size="sm">
                        {{ __('Add Payment Method') }}
                    </x-filament::button>
                @endif
            </div>
            
            @if($hasPaymentMethod)
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 flex items-center">
                    <div class="mr-4">
                        @if($paymentMethod['brand'] === 'visa')
                            <svg class="w-10 h-10 text-blue-600" viewBox="0 0 48 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M44 24C44 35.046 35.046 44 24 44C12.954 44 4 35.046 4 24C4 12.954 12.954 4 24 4C35.046 4 44 12.954 44 24Z" />
                                <path d="M18.4 19.8L14 32H17.2L21.6 19.8H18.4Z" fill="white" />
                                <path d="M29.2 20.2C28.4 19.9 27.2 19.6 25.8 19.6C22.6 19.6 20.4 21.2 20.4 23.5C20.4 25.3 22 26.3 23.2 26.9C24.4 27.5 24.8 27.9 24.8 28.4C24.8 29.2 23.8 29.5 22.9 29.5C21.7 29.5 21 29.3 19.9 28.8L19.5 28.6L19 31.2C19.9 31.6 21.5 31.9 23.2 31.9C26.6 31.9 28.7 30.3 28.7 27.8C28.7 26.4 27.9 25.3 26 24.4C24.9 23.8 24.2 23.4 24.2 22.8C24.2 22.3 24.8 21.8 26 21.8C27 21.8 27.8 22 28.4 22.3L28.7 22.4L29.2 20.2Z" fill="white" />
                                <path d="M35.6 19.8H33.1C32.4 19.8 31.8 20 31.6 20.7L27.6 32H31C31 32 31.5 30.6 31.6 30.3C32 30.3 34.6 30.3 35.1 30.3C35.2 30.7 35.5 32 35.5 32H38.6L35.6 19.8ZM32.6 27.8C32.9 27.1 34 24.3 34 24.3C34 24.3 34.3 23.5 34.5 23L34.7 24.2C34.7 24.2 35.3 27.2 35.5 27.8H32.6Z" fill="white" />
                            </svg>
                        @elseif($paymentMethod['brand'] === 'mastercard')
                            <svg class="w-10 h-10" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24 34.9C29.5 34.9 34 30.4 34 24.9C34 19.4 29.5 14.9 24 14.9C18.5 14.9 14 19.4 14 24.9C14 30.4 18.5 34.9 24 34.9Z" fill="#D4001A" />
                                <path d="M27 14.9C22.9 14.9 19.3 17.1 17.5 20.4C17.1 21.1 16.8 21.9 16.5 22.6C16.2 23.4 16 24.1 16 24.9C16 25.7 16.1 26.4 16.3 27.2C16.5 27.9 16.7 28.7 17.1 29.4C18.9 32.7 22.6 34.9 26.6 34.9C31.1 34.9 34.9 31.8 36.2 27.5C36.4 26.7 36.5 25.8 36.5 24.9C36.5 24 36.4 23.1 36.2 22.3C34.9 18 31.1 14.9 27 14.9Z" fill="#FF5F00" />
                                <path d="M44 24.9C44 19.4 39.5 14.9 34 14.9C29.9 14.9 26.4 17.1 24.6 20.4C23.7 21.8 23.3 23.3 23.3 24.9C23.3 26.5 23.7 28 24.6 29.4C26.4 32.7 29.9 34.9 34 34.9C39.5 34.9 44 30.4 44 24.9Z" fill="#F79E1B" />
                            </svg>
                        @elseif($paymentMethod['brand'] === 'amex')
                            <svg class="w-10 h-10 text-blue-800" viewBox="0 0 48 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M44 24C44 35.046 35.046 44 24 44C12.954 44 4 35.046 4 24C4 12.954 12.954 4 24 4C35.046 4 44 12.954 44 24Z" />
                                <path d="M12 18L14.5 22L17 18H42V30H12V18Z" fill="white" />
                                <path d="M16 21L13 25H15L15.5 24H18.5L19 25H21L18 21H16ZM16 23L17 22L18 23H16Z" fill="currentColor" />
                                <path d="M21 25V21H24L25.5 22.5L27 21H30V25H28V22.5H27L26 25H25L24 22.5H23V25H21Z" fill="currentColor" />
                                <path d="M30 23V21H35V22H32V22.5H35V23.5H32V24H35V25H30V23Z" fill="currentColor" />
                            </svg>
                        @elseif($paymentMethod['brand'] === 'discover')
                            <svg class="w-10 h-10 text-orange-500" viewBox="0 0 48 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <rect x="4" y="12" width="40" height="24" rx="4" fill="currentColor" />
                                <path d="M28 24C28 26.2 26.2 28 24 28C21.8 28 20 26.2 20 24C20 21.8 21.8 20 24 20C26.2 20 28 21.8 28 24Z" fill="#FF6D00" />
                                <path d="M12 30H36V32H12V30Z" fill="white" />
                            </svg>
                        @else
                            <svg class="w-10 h-10 text-gray-600" viewBox="0 0 48 48" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <rect x="4" y="12" width="40" height="24" rx="4" fill="currentColor" />
                                <path d="M24 18L27 24H21L24 18Z" fill="white" />
                                <path d="M24 30L21 24H27L24 30Z" fill="white" />
                            </svg>
                        @endif
                    </div>
                    <div>
                        <p class="font-medium text-gray-900 dark:text-gray-100">
                            {{ ucfirst($paymentMethod['brand']) }} ending in {{ $paymentMethod['last4'] }}
                        </p>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Expires') }} {{ $paymentMethod['exp_month'] }}/{{ $paymentMethod['exp_year'] }}
                        </p>
                    </div>
                    <div class="ml-auto">
                        <x-filament::button wire:click="removePaymentMethod" color="danger" size="sm">
                            {{ __('Remove') }}
                        </x-filament::button>
                    </div>
                </div>
            @else
                <div class="bg-yellow-50 dark:bg-yellow-900 rounded-lg p-4 border border-yellow-200 dark:border-yellow-700 text-yellow-800 dark:text-yellow-200">
                    <p>{{ __('You don\'t have a payment method on file.') }}</p>
                </div>
            @endif
            
            @if($showPaymentMethodForm)
                <div class="mt-4">
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <h4 class="text-base font-medium text-gray-900 dark:text-gray-100 mb-2">
                            {{ __('Add Credit or Debit Card') }}
                        </h4>
                        
                        <div id="card-element" class="p-3 border border-gray-300 dark:border-gray-600 rounded-lg mb-3 bg-white dark:bg-gray-700"></div>
                        <div id="card-errors" class="text-sm text-red-600 dark:text-red-400 mb-3 hidden"></div>
                        
                        <div class="flex justify-end">
                            <x-filament::button id="card-button" color="primary" size="sm">
                                {{ __('Save Card') }}
                            </x-filament::button>
                        </div>
                    </div>
                </div>
                
                <form id="payment-form" data-secret="{{ $setupIntent }}" class="hidden"></form>
                
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        if (typeof Stripe !== 'undefined' && document.querySelector('#card-element')) {
                            const stripeKey = "{{ config('cashier.key') }}";
                            const stripe = Stripe(stripeKey);
                            
                            const elements = stripe.elements();
                            
                            // Create card element
                            const cardElement = elements.create('card', {
                                style: {
                                    base: {
                                        color: document.documentElement.classList.contains('dark') ? '#FFFFFF' : '#31363F',
                                        fontSmoothing: 'antialiased',
                                        fontSize: '16px',
                                        '::placeholder': {
                                            color: document.documentElement.classList.contains('dark') ? '#6B7280' : '#9CA3AF'
                                        }
                                    },
                                    invalid: {
                                        color: document.documentElement.classList.contains('dark') ? '#F87171' : '#DC2626',
                                        iconColor: document.documentElement.classList.contains('dark') ? '#F87171' : '#DC2626'
                                    }
                                }
                            });
                            cardElement.mount('#card-element');
                            
                            // Handle validation errors
                            cardElement.addEventListener('change', function(event) {
                                const displayError = document.getElementById('card-errors');
                                if (event.error) {
                                    displayError.textContent = event.error.message;
                                    displayError.classList.remove('hidden');
                                } else {
                                    displayError.textContent = '';
                                    displayError.classList.add('hidden');
                                }
                            });
                            
                            // Handle form submission
                            const cardButton = document.getElementById('card-button');
                            const paymentForm = document.getElementById('payment-form');
                            
                            cardButton.addEventListener('click', function(event) {
                                cardButton.disabled = true;
                                cardButton.classList.add('opacity-50', 'cursor-not-allowed');
                                cardButton.textContent = 'Processing...';
                                
                                stripe.confirmCardSetup(
                                    "{{ $setupIntent }}",
                                    {
                                        payment_method: {
                                            card: cardElement,
                                            billing_details: {
                                                name: "{{ Auth::user()->name }}"
                                            }
                                        }
                                    }
                                ).then(function(result) {
                                    if (result.error) {
                                        // Show error to customer
                                        const errorElement = document.getElementById('card-errors');
                                        errorElement.textContent = result.error.message;
                                        errorElement.classList.remove('hidden');
                                        
                                        cardButton.disabled = false;
                                        cardButton.classList.remove('opacity-50', 'cursor-not-allowed');
                                        cardButton.textContent = 'Save Card';
                                    } else {
                                        // Update the payment method on server using Livewire
                                        Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).call('updatePaymentMethod', result.setupIntent.payment_method);
                                        
                                        cardElement.clear();
                                        
                                        cardButton.disabled = false;
                                        cardButton.classList.remove('opacity-50', 'cursor-not-allowed');
                                        cardButton.textContent = 'Save Card';
                                    }
                                });
                            });
                        }
                    });
                </script>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget> 