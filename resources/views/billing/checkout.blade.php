<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Checkout') }}
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

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-2">Payment Details</h3>
                        <p class="text-gray-600 mb-4">Complete your payment information below.</p>

                        <form id="payment-form" action="{{ route('billing.payment.process') }}" method="POST">
                            @csrf
                            <input type="hidden" name="price_id" value="{{ $priceId ?? '' }}">

                            <div class="mb-4">
                                <label for="card-element" class="block text-sm font-medium text-gray-700 mb-2">
                                    Credit or debit card
                                </label>
                                <div id="card-element" class="p-4 border border-gray-300 rounded-md"></div>
                                <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                            </div>

                            <button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" class="bg-primary hover:bg-primary-focus text-white px-4 py-2 rounded shadow transition-colors">
                                <span id="button-text">Complete Payment</span>
                                <span id="spinner" class="hidden">
                                    <i class="fas fa-spinner fa-spin"></i>
                                </span>
                            </button>
                            
                            <a href="{{ route('billing') }}" class="ml-4 text-gray-600 hover:text-gray-800 transition-colors">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Billing
                            </a>
                        </form>
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
            });
        </script>
    @endpush
</x-app-layout> 