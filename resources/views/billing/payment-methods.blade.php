<x-layouts.app-sidebar>

<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="mb-2">Payment Methods</flux:heading>
                <flux:subheading>Manage your credit cards and payment options</flux:subheading>
            </div>
            <flux:button id="addCard" variant="primary" icon="plus">
                Add New Card
            </flux:button>
        </div>
    </div>

    <div class="space-y-8">
        @if (session('success'))
            <flux:callout variant="success" class="mb-6">
                {{ session('success') }}
            </flux:callout>
        @endif

        @if ($errors->any())
            <flux:callout variant="danger" class="mb-6">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </flux:callout>
        @endif

                    
        <!-- Payment Method Form (hidden by default) -->
        <div id="paymentMethodForm" class="mb-8 hidden">
            <flux:card>
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <flux:icon name="credit-card" class="w-6 h-6 text-blue-600 dark:text-blue-400 mr-3" />
                        <flux:heading size="lg">Add a New Payment Method</flux:heading>
                    </div>
                    
                    <form id="payment-form" action="{{ route('billing.payment.update') }}" method="POST">
                        @csrf
                        <div class="mb-6">
                            <flux:label class="mb-3" icon="credit-card">
                                Credit or Debit Card Information
                            </flux:label>
                            <div class="relative">
                                <div id="card-element" class="p-4 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-400 transition-all duration-200"></div>
                                <div class="absolute top-2 right-2">
                                    <div class="flex items-center space-x-1">
                                        <i class="fab fa-cc-visa text-blue-600 text-sm"></i>
                                        <i class="fab fa-cc-mastercard text-orange-600 text-sm"></i>
                                        <i class="fab fa-cc-amex text-blue-800 text-sm"></i>
                                        <i class="fab fa-cc-discover text-orange-500 text-sm"></i>
                                    </div>
                                </div>
                            </div>
                            <div id="card-errors" class="text-red-600 text-sm mt-2 flex items-center">
                                <flux:icon name="exclamation-circle" class="w-4 h-4 mr-1 hidden error-icon" />
                                <span class="error-text"></span>
                            </div>
                            <div class="mt-2 text-xs text-blue-600 dark:text-blue-400 flex items-center">
                                <flux:icon name="shield-check" class="w-4 h-4 mr-1" />
                                Your payment information is encrypted and secure
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <flux:button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" variant="primary" icon="check">
                                <span id="button-text">Add Card</span>
                                <span id="spinner" class="hidden">
                                    Processing...
                                </span>
                            </flux:button>
                            <flux:button type="button" id="cancelAdd" variant="ghost" icon="x-mark">
                                Cancel
                            </flux:button>
                        </div>
                    </form>
                </div>
            </flux:card>
        </div>

        <!-- List of Payment Methods -->
        <flux:card>
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <flux:icon name="credit-card" class="w-6 h-6 text-gray-600 dark:text-gray-400 mr-3" />
                    <flux:heading size="lg">Your Payment Methods</flux:heading>
                </div>
                
                <div class="space-y-4">
                    @forelse($paymentMethods as $method)
                        <flux:card class="{{ $defaultPaymentMethod && $method->id === $defaultPaymentMethod->id ? 'border-blue-200 bg-blue-50 dark:bg-blue-900/20' : '' }}">
                            <div class="p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-12 h-12 bg-gray-100 dark:bg-gray-800 rounded-lg mr-4">
                                            @if($method->card->brand === 'visa')
                                                <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                                            @elseif($method->card->brand === 'mastercard')
                                                <i class="fab fa-cc-mastercard text-2xl text-orange-600"></i>
                                            @elseif($method->card->brand === 'amex')
                                                <i class="fab fa-cc-amex text-2xl text-blue-800"></i>
                                            @elseif($method->card->brand === 'discover')
                                                <i class="fab fa-cc-discover text-2xl text-orange-500"></i>
                                            @else
                                                <i class="fas fa-credit-card text-2xl text-gray-700"></i>
                                            @endif
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($method->card->brand) }} ending in {{ $method->card->last4 }}</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center">
                                                <flux:icon name="calendar" class="w-4 h-4 mr-1" />
                                                Expires {{ $method->card->exp_month }}/{{ $method->card->exp_year }}
                                            </div>
                                            @if($defaultPaymentMethod && $method->id === $defaultPaymentMethod->id)
                                                <div class="mt-2">
                                                    <flux:badge color="blue" size="sm">
                                                        <flux:icon name="check-circle" class="w-3 h-3 mr-1" />
                                                        Default
                                                    </flux:badge>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if(!$defaultPaymentMethod || $method->id !== $defaultPaymentMethod->id)
                                            <form method="POST" action="{{ route('billing.payment.update') }}">
                                                @csrf
                                                <input type="hidden" name="payment_method" value="{{ $method->id }}">
                                                <flux:button type="submit" variant="outline" size="sm" icon="star">
                                                    Make Default
                                                </flux:button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('billing.payment.remove') }}" onsubmit="return confirm('Are you sure you want to remove this payment method?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="payment_method_id" value="{{ $method->id }}">
                                            <flux:button type="submit" variant="danger" size="sm" icon="trash" />
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </flux:card>
                    @empty
                        <div class="text-center py-12">
                            <flux:icon name="credit-card" class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                            <flux:heading size="lg" class="mb-2">No Payment Methods</flux:heading>
                            <flux:text class="text-gray-600 dark:text-gray-400 mb-4">You don't have any payment methods yet.</flux:text>
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-500">Add a credit or debit card to make payments.</flux:text>
                        </div>
                    @endforelse
                </div>
            </div>
        </flux:card>

        <!-- Actions -->
        <flux:card>
            <div class="p-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <flux:button href="{{ route('billing') }}" variant="ghost" icon="arrow-left">
                        Back to Billing
                    </flux:button>
                    
                    <flux:button href="{{ route('billing.portal') }}" variant="primary" icon="arrow-top-right-on-square">
                        Stripe Billing Portal
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </div>
</div>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Stripe
                const stripe = Stripe(`{{ env('STRIPE_KEY') }}`);
                const elements = stripe.elements();
                
                // Enhanced styling for Stripe Elements to match Flux UI
                const style = {
                    base: {
                        color: '#1f2937',
                        fontFamily: '"Inter", "Helvetica Neue", Helvetica, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        fontWeight: '500',
                        '::placeholder': {
                            color: '#9ca3af'
                        },
                        ':focus': {
                            color: '#1f2937'
                        }
                    },
                    invalid: {
                        color: '#dc2626',
                        iconColor: '#dc2626'
                    },
                    complete: {
                        color: '#059669',
                        iconColor: '#059669'
                    }
                };
                
                // Create card element
                const cardElement = elements.create('card', { style: style });
                cardElement.mount('#card-element');
                
                // Enhanced validation error handling
                cardElement.on('change', function(event) {
                    const displayError = document.getElementById('card-errors');
                    const errorIcon = displayError.querySelector('.error-icon');
                    const errorText = displayError.querySelector('.error-text');
                    
                    if (event.error) {
                        errorIcon.classList.remove('hidden');
                        errorText.textContent = event.error.message;
                        displayError.classList.add('text-red-600');
                        displayError.classList.remove('text-green-600');
                    } else if (event.complete) {
                        errorIcon.classList.add('hidden');
                        errorText.textContent = 'Payment information is valid';
                        displayError.classList.remove('text-red-600');
                        displayError.classList.add('text-green-600');
                    } else {
                        errorIcon.classList.add('hidden');
                        errorText.textContent = '';
                        displayError.classList.remove('text-red-600', 'text-green-600');
                    }
                });
                
                // Handle form submission
                const form = document.getElementById('payment-form');
                const cardButton = document.getElementById('card-button');
                const clientSecret = cardButton.dataset.secret;
                
                form.addEventListener('submit', async function(event) {
                    event.preventDefault();
                    
                    // Disable the submit button to prevent repeated clicks
                    cardButton.disabled = true;
                    document.getElementById('button-text').classList.add('hidden');
                    document.getElementById('spinner').classList.remove('hidden');
                    
                    const { setupIntent, error } = await stripe.confirmCardSetup(
                        clientSecret, {
                            payment_method: {
                                card: cardElement
                            }
                        }
                    );
                    
                    if (error) {
                        // Show error to customer with enhanced styling
                        const errorElement = document.getElementById('card-errors');
                        const errorIcon = errorElement.querySelector('.error-icon');
                        const errorText = errorElement.querySelector('.error-text');
                        
                        errorIcon.classList.remove('hidden');
                        errorText.textContent = error.message;
                        errorElement.classList.add('text-red-600');
                        
                        // Re-enable the submit button
                        cardButton.disabled = false;
                        document.getElementById('button-text').classList.remove('hidden');
                        document.getElementById('spinner').classList.add('hidden');
                    } else {
                        // The card has been verified successfully
                        // Create a hidden input with the payment method ID
                        const hiddenInput = document.createElement('input');
                        hiddenInput.setAttribute('type', 'hidden');
                        hiddenInput.setAttribute('name', 'payment_method');
                        hiddenInput.setAttribute('value', setupIntent.payment_method);
                        form.appendChild(hiddenInput);
                        
                        // Submit the form
                        form.submit();
                    }
                });
                
                // Toggle payment method form
                const addCardButton = document.getElementById('addCard');
                const cancelAddButton = document.getElementById('cancelAdd');
                const paymentMethodForm = document.getElementById('paymentMethodForm');
                
                addCardButton.addEventListener('click', function() {
                    paymentMethodForm.classList.remove('hidden');
                    addCardButton.style.display = 'none';
                });
                
                cancelAddButton.addEventListener('click', function() {
                    paymentMethodForm.classList.add('hidden');
                    addCardButton.style.display = 'inline-flex';
                });
            });
        </script>
    @endpush
</x-layouts.app-sidebar> 