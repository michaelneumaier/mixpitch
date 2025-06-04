@extends('components.layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <!-- Background Effects -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-green-400/20 to-blue-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-blue-400/20 to-purple-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-gradient-to-r from-purple-300/10 to-green-300/10 rounded-full blur-2xl"></div>
    </div>

    <div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-green-50/30">
        <div class="py-12">
            <!-- Enhanced Header Section -->
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 mb-8">
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-xl p-8">
                    <div class="text-center">
                        <h1 class="text-4xl lg:text-5xl font-bold bg-gradient-to-r from-green-600 via-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                            Secure Checkout
                        </h1>
                        <p class="text-gray-600 text-lg">Complete your payment securely with Stripe</p>
                        
                        <!-- Security Badges -->
                        <div class="flex items-center justify-center gap-6 mt-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-2">
                                    <i class="fas fa-shield-alt text-white text-sm"></i>
                                </div>
                                <span>SSL Encrypted</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-2">
                                    <i class="fas fa-lock text-white text-sm"></i>
                                </div>
                                <span>PCI Compliant</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-2">
                                    <i class="fab fa-stripe text-white text-sm"></i>
                                </div>
                                <span>Powered by Stripe</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-xl overflow-hidden">
                    <div class="p-6 sm:p-8">
                        @if (session('success'))
                            <div class="mb-6 p-4 bg-gradient-to-r from-green-100/80 to-emerald-100/80 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-sm">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                        <i class="fas fa-check text-white text-sm"></i>
                                    </div>
                                    <span class="text-green-800 font-medium">{{ session('success') }}</span>
                                </div>
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="mb-6 p-4 bg-gradient-to-r from-red-100/80 to-pink-100/80 backdrop-blur-sm border border-red-200/50 rounded-xl shadow-sm">
                                <div class="flex items-start">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-red-500 to-pink-600 rounded-lg mr-3 mt-0.5">
                                        <i class="fas fa-exclamation-triangle text-white text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <h4 class="text-red-800 font-medium mb-2">Please fix the following errors:</h4>
                                        <ul class="list-disc pl-4 text-red-700 space-y-1">
                                            @foreach ($errors->all() as $error)
                                                <li class="text-sm">{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Payment Form Section -->
                        <div class="bg-gradient-to-br from-blue-50/90 to-indigo-50/90 backdrop-blur-sm border border-blue-200/50 rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center mb-6">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4">
                                    <i class="fas fa-credit-card text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Payment Information</h3>
                            </div>

                            <form id="payment-form" action="{{ route('billing.payment.process') }}" method="POST">
                                @csrf
                                <input type="hidden" name="price_id" value="{{ $priceId ?? '' }}">

                                <div class="mb-6">
                                    <label for="card-element" class="block text-sm font-medium text-blue-700 mb-3">
                                        <i class="fas fa-credit-card mr-2"></i>
                                        Credit or Debit Card Information
                                    </label>
                                    <div class="relative">
                                        <div id="card-element" class="p-4 bg-white/80 backdrop-blur-sm border border-blue-200/50 rounded-xl shadow-sm focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-400 transition-all duration-200"></div>
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
                                        <i class="fas fa-exclamation-circle mr-1 hidden error-icon"></i>
                                        <span class="error-text"></span>
                                    </div>
                                    <div class="mt-2 text-xs text-blue-600 flex items-center">
                                        <i class="fas fa-shield-alt mr-1"></i>
                                        Your payment information is encrypted and secure
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row gap-4">
                                    <button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                        <span id="button-text" class="flex items-center">
                                            <i class="fas fa-lock mr-2"></i>
                                            Complete Secure Payment
                                        </span>
                                        <span id="spinner" class="hidden flex items-center">
                                            <i class="fas fa-spinner fa-spin mr-2"></i>
                                            Processing Payment...
                                        </span>
                                    </button>
                                    
                                    <a href="{{ route('billing') }}" class="inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Back to Billing
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Trust Indicators -->
                        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-gradient-to-br from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4 text-center">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mx-auto mb-3">
                                    <i class="fas fa-shield-alt text-white"></i>
                                </div>
                                <h4 class="text-sm font-bold text-green-800 mb-1">Bank-Level Security</h4>
                                <p class="text-xs text-green-600">256-bit SSL encryption protects your data</p>
                            </div>
                            
                            <div class="bg-gradient-to-br from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mx-auto mb-3">
                                    <i class="fas fa-user-shield text-white"></i>
                                </div>
                                <h4 class="text-sm font-bold text-blue-800 mb-1">Privacy Protected</h4>
                                <p class="text-xs text-blue-600">We never store your payment details</p>
                            </div>
                            
                            <div class="bg-gradient-to-br from-purple-50/80 to-indigo-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4 text-center">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mx-auto mb-3">
                                    <i class="fas fa-clock text-white"></i>
                                </div>
                                <h4 class="text-sm font-bold text-purple-800 mb-1">Instant Processing</h4>
                                <p class="text-xs text-purple-600">Payments processed in real-time</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Stripe
            const stripe = Stripe(`{{ env('STRIPE_KEY') }}`);
            const elements = stripe.elements();
            
            // Enhanced Glass Morphism Styling for Stripe Elements
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
                    // Show error to your customer
                    const errorElement = document.getElementById('card-errors');
                    const errorIcon = errorElement.querySelector('.error-icon');
                    const errorText = errorElement.querySelector('.error-text');
                    
                    errorIcon.classList.remove('hidden');
                    errorText.textContent = error.message;
                    errorElement.classList.add('text-red-600');
                    errorElement.classList.remove('text-green-600');
                    
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