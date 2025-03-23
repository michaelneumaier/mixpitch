<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Payment Methods') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                <div class="p-6 sm:p-8">
                    @if (session('success'))
                        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md border border-green-200">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md border border-red-200">
                            <ul class="list-disc pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold">Your Payment Methods</h3>
                        <button id="addCard" class="bg-primary hover:bg-primary-focus text-white px-3 py-2 rounded shadow transition-colors text-sm">
                            <i class="fas fa-plus mr-1"></i> Add New Card
                        </button>
                    </div>
                    
                    <!-- Payment Method Form (hidden by default) -->
                    <div id="paymentMethodForm" class="mb-8 hidden p-5 bg-gray-50 rounded-lg border border-gray-200">
                        <h4 class="text-md font-medium mb-3">Add a New Payment Method</h4>
                        <form id="payment-form" action="{{ route('billing.payment.update') }}" method="POST">
                            @csrf
                            <div class="mb-4">
                                <label for="card-element" class="block text-sm font-medium text-gray-700 mb-2">
                                    Credit or debit card
                                </label>
                                <div id="card-element" class="p-4 border border-gray-300 rounded-md bg-white"></div>
                                <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                            </div>

                            <div class="flex gap-2">
                                <button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" class="bg-primary hover:bg-primary-focus text-white px-4 py-2 rounded shadow transition-colors">
                                    <span id="button-text">Add Card</span>
                                    <span id="spinner" class="hidden">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                                <button type="button" id="cancelAdd" class="bg-gray-200 hover:bg-gray-300 text-gray-800 px-4 py-2 rounded shadow transition-colors">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- List of Payment Methods -->
                    <div class="space-y-4">
                        @forelse($paymentMethods as $method)
                            <div class="p-4 border rounded-lg flex items-center justify-between {{ $defaultPaymentMethod && $method->id === $defaultPaymentMethod->id ? 'bg-blue-50 border-blue-200' : 'bg-white border-gray-200' }}">
                                <div class="flex items-center">
                                    <div class="mr-3">
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
                                        <div class="font-medium">{{ ucfirst($method->card->brand) }} ending in {{ $method->card->last4 }}</div>
                                        <div class="text-sm text-gray-600">Expires {{ $method->card->exp_month }}/{{ $method->card->exp_year }}</div>
                                        @if($defaultPaymentMethod && $method->id === $defaultPaymentMethod->id)
                                            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                Default
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if(!$defaultPaymentMethod || $method->id !== $defaultPaymentMethod->id)
                                        <form method="POST" action="{{ route('billing.payment.update') }}">
                                            @csrf
                                            <input type="hidden" name="payment_method" value="{{ $method->id }}">
                                            <button type="submit" class="text-blue-600 hover:text-blue-800 transition-colors text-sm bg-transparent border border-blue-200 hover:bg-blue-50 px-2 py-1 rounded">
                                                Make Default
                                            </button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('billing.payment.remove') }}" onsubmit="return confirm('Are you sure you want to remove this payment method?');">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="payment_method_id" value="{{ $method->id }}">
                                        <button type="submit" class="text-red-600 hover:text-red-800 transition-colors text-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 bg-gray-50 rounded-lg border border-gray-200">
                                <i class="fas fa-credit-card text-4xl text-gray-300 mb-3"></i>
                                <p class="mb-2">You don't have any payment methods yet.</p>
                                <p class="text-sm">Add a credit or debit card to make payments.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('billing.index') }}" class="text-gray-600 hover:text-gray-800 transition-colors flex items-center">
                                <i class="fas fa-arrow-left mr-2"></i> Back to Billing
                            </a>
                            
                            <a href="{{ route('billing.portal') }}" class="inline-block bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded shadow transition-colors">
                                Stripe Billing Portal
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize Stripe
                const stripe = Stripe(`{{ env('STRIPE_KEY') }}`);
                const elements = stripe.elements();
                
                // Custom styling
                const style = {
                    base: {
                        color: '#32325d',
                        fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                        fontSmoothing: 'antialiased',
                        fontSize: '16px',
                        '::placeholder': {
                            color: '#aab7c4'
                        }
                    },
                    invalid: {
                        color: '#fa755a',
                        iconColor: '#fa755a'
                    }
                };
                
                // Create card element
                const cardElement = elements.create('card', { style: style });
                cardElement.mount('#card-element');
                
                // Handle validation errors
                cardElement.on('change', function(event) {
                    const displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
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
                        // Show error to your customer
                        const errorElement = document.getElementById('card-errors');
                        errorElement.textContent = error.message;
                        
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
                    addCardButton.classList.add('hidden');
                });
                
                cancelAddButton.addEventListener('click', function() {
                    paymentMethodForm.classList.add('hidden');
                    addCardButton.classList.remove('hidden');
                });
            });
        </script>
    @endpush
</x-app-layout> 