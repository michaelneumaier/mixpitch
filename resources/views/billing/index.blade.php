<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Billing') }}
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
                        <h3 class="text-lg font-semibold mb-4">Payment Method</h3>
                        @if($hasPaymentMethod)
                            <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                                <div class="flex items-center gap-4">
                                    <div>
                                        @if($paymentMethod->card->brand === 'visa')
                                            <i class="fab fa-cc-visa text-2xl text-blue-600"></i>
                                        @elseif($paymentMethod->card->brand === 'mastercard')
                                            <i class="fab fa-cc-mastercard text-2xl text-orange-600"></i>
                                        @elseif($paymentMethod->card->brand === 'amex')
                                            <i class="fab fa-cc-amex text-2xl text-blue-800"></i>
                                        @elseif($paymentMethod->card->brand === 'discover')
                                            <i class="fab fa-cc-discover text-2xl text-orange-500"></i>
                                        @else
                                            <i class="fas fa-credit-card text-2xl text-gray-700"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-medium">{{ ucfirst($paymentMethod->card->brand) }} ending in {{ $paymentMethod->card->last4 }}</div>
                                        <div class="text-sm text-gray-600">Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}</div>
                                    </div>
                                    <form method="POST" action="{{ route('billing.payment.remove') }}" class="ml-auto">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800 transition-colors text-sm">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button id="updateCard" class="bg-primary hover:bg-primary-focus text-white px-4 py-2 rounded shadow transition-colors">
                                    Update Payment Method
                                </button>
                                <a href="{{ route('billing.payment-methods') }}" class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded shadow transition-colors">
                                    <i class="fas fa-credit-card mr-1"></i> Manage All Payment Methods
                                </a>
                            </div>
                        @else
                            <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200 mb-4 text-yellow-800">
                                <p>You don't have a payment method on file.</p>
                            </div>
                            <div class="flex gap-2">
                                <button id="addCard" class="bg-primary hover:bg-primary-focus text-white px-4 py-2 rounded shadow transition-colors">
                                    Add Payment Method
                                </button>
                                <a href="{{ route('billing.payment-methods') }}" class="px-4 py-2 bg-blue-100 hover:bg-blue-200 text-blue-700 rounded shadow transition-colors">
                                    <i class="fas fa-credit-card mr-1"></i> Manage Payment Methods
                                </a>
                            </div>
                        @endif

                        <!-- Payment Method Form (hidden by default) -->
                        <div id="paymentMethodForm" class="mt-6 hidden">
                            <form id="payment-form" action="{{ route('billing.payment.update') }}" method="POST">
                                @csrf
                                <div class="mb-4">
                                    <label for="card-element" class="block text-sm font-medium text-gray-700 mb-2">
                                        Credit or debit card
                                    </label>
                                    <div id="card-element" class="p-4 border border-gray-300 rounded-md"></div>
                                    <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                                </div>

                                <button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" class="bg-primary hover:bg-primary-focus text-white px-4 py-2 rounded shadow transition-colors">
                                    <span id="button-text">
                                        {{ $hasPaymentMethod ? 'Update Card' : 'Add Card' }}
                                    </span>
                                    <span id="spinner" class="hidden">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-semibold mb-4">One-Time Payment</h3>
                        <form action="{{ route('billing.payment.process') }}" method="POST" id="one-time-payment-form">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (USD)</label>
                                    <input type="number" step="0.01" min="1" id="amount" name="amount" class="w-full p-2 border border-gray-300 rounded-md" required>
                                </div>
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                                    <input type="text" id="description" name="description" class="w-full p-2 border border-gray-300 rounded-md" placeholder="What is this payment for?">
                                </div>
                            </div>
                            
                            @if(!$hasPaymentMethod)
                                <div class="bg-yellow-50 p-4 rounded-md border border-yellow-200 mb-4 text-yellow-800">
                                    <p>You need to add a payment method before making a payment.</p>
                                </div>
                            @else
                                <input type="hidden" name="payment_method" value="{{ $paymentMethod->id }}">
                            @endif
                            
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow transition-colors" {{ !$hasPaymentMethod ? 'disabled' : '' }}>
                                Make Payment
                            </button>
                        </form>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold mb-4">Billing History</h3>
                        
                        @if(count($invoices) > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($invoices as $invoice)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $invoice->date()->format('M d, Y') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    ${{ number_format($invoice->total() / 100, 2) }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                    @if($invoice->paid)
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                            Paid
                                                        </span>
                                                    @else
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                            Unpaid
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex items-center gap-3">
                                                        <a href="{{ route('billing.invoice.show', $invoice->id) }}" class="text-primary hover:text-primary-focus">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                        <a href="{{ route('billing.invoice.download', $invoice->id) }}" class="text-gray-700 hover:text-gray-900">
                                                            <i class="fas fa-download"></i> Download
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4 text-right">
                                <a href="{{ route('billing.invoices') }}" class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium rounded-md transition-colors">
                                    View All Invoices <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        @else
                            <div class="bg-gray-50 p-6 rounded-md border border-gray-200 text-center text-gray-700">
                                <i class="fas fa-receipt text-gray-400 text-4xl mb-3"></i>
                                <p>No billing history available.</p>
                                <p class="text-sm mt-2">Payments and invoices will appear here once you make a purchase.</p>
                            </div>
                        @endif
                    </div>

                    @if($hasPaymentMethod)
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-semibold mb-4">Billing Portal</h3>
                            <p class="text-gray-600 mb-4">
                                Manage your subscription, payment methods, and billing history directly through Stripe's secure portal.
                            </p>
                            @if(session('errors') && session('errors')->has('error') && strpos(session('errors')->first('error'), 'Customer Portal') !== false)
                                <div class="p-4 mb-4 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-sm font-medium text-yellow-800">Configuration Required</h3>
                                            <div class="mt-2 text-sm text-yellow-700">
                                                <p>{{ session('errors')->first('error') }}</p>
                                                <p class="mt-1">To fix this, please follow these steps:</p>
                                                <ol class="list-decimal pl-5 mt-1 space-y-1">
                                                    <li>Log in to your <a href="https://dashboard.stripe.com/test/settings/billing/portal" class="text-yellow-800 underline font-medium" target="_blank">Stripe Dashboard</a></li>
                                                    <li>Go to Settings > Customer Portal</li>
                                                    <li>Configure your portal settings (branding, features, etc.)</li>
                                                    <li>Save your configuration</li>
                                                    <li>Return here and try again</li>
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Local Payment Method Management -->
                                <div class="mt-6 mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                                    <h4 class="font-medium text-blue-800 mb-2">Manage Your Payment Methods Locally</h4>
                                    <p class="text-sm text-blue-700 mb-4">
                                        While the Stripe Customer Portal is being configured, you can still manage your payment methods here:
                                    </p>
                                    
                                    <!-- Current Payment Method -->
                                    <div class="mb-4">
                                        <h5 class="text-sm font-medium text-blue-800 mb-2">Current Default Payment Method</h5>
                                        <div class="p-3 bg-white rounded border border-blue-100 flex items-center">
                                            <div>
                                                @if($paymentMethod->card->brand === 'visa')
                                                    <i class="fab fa-cc-visa text-xl text-blue-600"></i>
                                                @elseif($paymentMethod->card->brand === 'mastercard')
                                                    <i class="fab fa-cc-mastercard text-xl text-orange-600"></i>
                                                @elseif($paymentMethod->card->brand === 'amex')
                                                    <i class="fab fa-cc-amex text-xl text-blue-800"></i>
                                                @elseif($paymentMethod->card->brand === 'discover')
                                                    <i class="fab fa-cc-discover text-xl text-orange-500"></i>
                                                @else
                                                    <i class="fas fa-credit-card text-xl text-gray-700"></i>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <div class="font-medium">{{ ucfirst($paymentMethod->card->brand) }} ending in {{ $paymentMethod->card->last4 }}</div>
                                                <div class="text-xs text-gray-600">Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="flex flex-wrap gap-2">
                                        <button id="updateCardLocal" class="text-sm px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                            Update Payment Method
                                        </button>
                                        <form method="POST" action="{{ route('billing.payment.remove') }}" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-sm px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded transition-colors">
                                                Remove Payment Method
                                            </button>
                                        </form>
                                    </div>
                                    
                                    <!-- Local Payment Method Form (hidden by default) -->
                                    <div id="paymentMethodFormLocal" class="mt-4 hidden">
                                        <h5 class="text-sm font-medium text-blue-800 mb-2">Enter New Payment Details</h5>
                                        <form id="payment-form-local" action="{{ route('billing.payment.update') }}" method="POST">
                                            @csrf
                                            <div class="mb-3">
                                                <div id="card-element-local" class="p-3 border border-blue-200 rounded-md bg-white"></div>
                                                <div id="card-errors-local" class="text-red-600 text-xs mt-1"></div>
                                            </div>
                                            <button type="submit" id="card-button-local" data-secret="{{ $intent->client_secret }}" class="text-sm px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors">
                                                <span id="button-text-local">Update Card</span>
                                                <span id="spinner-local" class="hidden">
                                                    <i class="fas fa-spinner fa-spin"></i>
                                                </span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                            <a href="{{ route('billing.portal') }}" class="inline-block bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded shadow transition-colors">
                                Manage Billing
                            </a>
                        </div>
                    @endif
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
                if (form) {
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
                }
                
                // Toggle payment method form
                const addCardButton = document.getElementById('addCard');
                const updateCardButton = document.getElementById('updateCard');
                const paymentMethodForm = document.getElementById('paymentMethodForm');
                
                if (addCardButton) {
                    addCardButton.addEventListener('click', function() {
                        paymentMethodForm.classList.toggle('hidden');
                    });
                }
                
                if (updateCardButton) {
                    updateCardButton.addEventListener('click', function() {
                        paymentMethodForm.classList.toggle('hidden');
                    });
                }
                
                // --- Local Payment Form Handling ---
                const updateCardLocalButton = document.getElementById('updateCardLocal');
                const paymentMethodFormLocal = document.getElementById('paymentMethodFormLocal');
                
                if (updateCardLocalButton && paymentMethodFormLocal) {
                    // Create local card element
                    const cardElementLocal = elements.create('card', { style: style });
                    cardElementLocal.mount('#card-element-local');
                    
                    // Handle validation errors for local form
                    cardElementLocal.on('change', function(event) {
                        const displayError = document.getElementById('card-errors-local');
                        if (event.error) {
                            displayError.textContent = event.error.message;
                        } else {
                            displayError.textContent = '';
                        }
                    });
                    
                    // Toggle local payment method form
                    updateCardLocalButton.addEventListener('click', function() {
                        paymentMethodFormLocal.classList.toggle('hidden');
                    });
                    
                    // Handle local form submission
                    const formLocal = document.getElementById('payment-form-local');
                    const cardButtonLocal = document.getElementById('card-button-local');
                    const clientSecretLocal = cardButtonLocal.dataset.secret;
                    
                    formLocal.addEventListener('submit', async function(event) {
                        event.preventDefault();
                        
                        // Disable the submit button to prevent repeated clicks
                        cardButtonLocal.disabled = true;
                        document.getElementById('button-text-local').classList.add('hidden');
                        document.getElementById('spinner-local').classList.remove('hidden');
                        
                        const { setupIntent, error } = await stripe.confirmCardSetup(
                            clientSecretLocal, {
                                payment_method: {
                                    card: cardElementLocal
                                }
                            }
                        );
                        
                        if (error) {
                            // Show error to your customer
                            const errorElement = document.getElementById('card-errors-local');
                            errorElement.textContent = error.message;
                            
                            // Re-enable the submit button
                            cardButtonLocal.disabled = false;
                            document.getElementById('button-text-local').classList.remove('hidden');
                            document.getElementById('spinner-local').classList.add('hidden');
                        } else {
                            // The card has been verified successfully
                            // Create a hidden input with the payment method ID
                            const hiddenInput = document.createElement('input');
                            hiddenInput.setAttribute('type', 'hidden');
                            hiddenInput.setAttribute('name', 'payment_method');
                            hiddenInput.setAttribute('value', setupIntent.payment_method);
                            formLocal.appendChild(hiddenInput);
                            
                            // Submit the form
                            formLocal.submit();
                        }
                    });
                }
            });
        </script>
    @endpush
</x-app-layout> 