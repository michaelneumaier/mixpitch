@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <!-- Breadcrumbs -->
        <div class="flex items-center text-sm mb-4 text-gray-600">
            <a href="{{ route('dashboard') }}" class="hover:text-primary">Dashboard</a>
            <svg class="h-4 w-4 mx-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <a href="{{ route('pitches.show', $pitch) }}" class="hover:text-primary">Pitch Details</a>
            <svg class="h-4 w-4 mx-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
            <span>Payment</span>
        </div>

        <!-- Payment Processing Step Indicator -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 bg-success rounded-full flex items-center justify-center text-white font-bold">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <span class="mt-2 text-sm font-medium text-success">Overview</span>
                </div>
                <div class="flex-1 h-1 bg-gray-300 mx-4"></div>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold">
                        <span>2</span>
                    </div>
                    <span class="mt-2 text-sm font-medium text-gray-600">Processing</span>
                </div>
                <div class="flex-1 h-1 bg-gray-300 mx-4"></div>
                <div class="flex flex-col items-center">
                    <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-white font-bold">
                        <span>3</span>
                    </div>
                    <span class="mt-2 text-sm font-medium text-gray-600">Receipt</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
            <!-- Header -->
            <div class="bg-primary-100 px-6 py-4">
                <h1 class="text-2xl font-bold text-gray-900">Payment Overview</h1>
                <p class="text-gray-600">Review the details and proceed with payment</p>
            </div>

            <!-- Project Information -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-700 mb-3">Project Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <div class="mb-4">
                            <h3 class="font-medium text-gray-700">Project Name</h3>
                            <p>{{ $project->name }}</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="font-medium text-gray-700">Project Type</h3>
                            <p>{{ Str::title($project->project_type) }}</p>
                        </div>
                        
                        <div>
                            <h3 class="font-medium text-gray-700">Project Owner</h3>
                            <p>{{ $project->user->name }}</p>
                        </div>
                    </div>
                    
                    <div>
                        <div class="mb-4">
                            <h3 class="font-medium text-gray-700">Pitch Title</h3>
                            <p>{{ $pitch->title }}</p>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="font-medium text-gray-700">Status</h3>
                            <div class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Completed
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h3 class="font-medium text-gray-700">Completion Date</h3>
                            <p>
                                @if($pitch->completed_at && is_object($pitch->completed_at))
                                    {{ $pitch->completed_at->format('F j, Y') }}
                                @else
                                    Recently completed
                                @endif
                            </p>
                        </div>
                        
                        <div>
                            <h3 class="font-medium text-gray-700">Collaboration Type</h3>
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($project->collaboration_type as $type => $value)
                                    @if($value)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                        {{ ucfirst($type) }}
                                    </span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-700 mb-3">Payment Details</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div class="flex justify-between items-center">
                        <div>
                            <span class="text-gray-600">Amount Due</span>
                            <div class="text-2xl font-bold text-gray-900">${{ number_format($paymentAmount, 2) }}</div>
                        </div>
                        <div class="bg-primary-50 p-3 rounded-lg border border-primary-100">
                            <span class="text-primary-800 font-medium">Fixed Price</span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-sm text-gray-600">
                    <p class="mb-2">
                        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
                        This is a one-time payment for the completed work.
                    </p>
                    <p>
                        <i class="fas fa-shield-alt text-green-500 mr-1"></i>
                        Payments are processed securely through Stripe.
                    </p>
                </div>
            </div>

            <!-- Payment Form -->
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="font-bold text-gray-700 mb-3">Payment Method</h3>
                
                <form id="payment-form" action="{{ route('pitches.payment.process', $pitch) }}" method="POST">
                    @csrf
                    <input type="hidden" name="amount" value="{{ $paymentAmount }}">
                    <input type="hidden" name="pitch_id" value="{{ $pitch->id }}">
                    
                    <!-- Alert for errors -->
                    @if (session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-800 rounded-md p-4 mb-4">
                        <div class="flex">
                            <svg class="h-5 w-5 text-red-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                    @endif
                    
                    @php
                        $user = Auth::user();
                        $hasPaymentMethod = $user->hasDefaultPaymentMethod();
                        $paymentMethod = $hasPaymentMethod ? $user->defaultPaymentMethod() : null;
                        $intent = $user->createSetupIntent();
                    @endphp
                    
                    <!-- Display existing payment methods if available -->
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
                                    <i class="fab fa-cc-discover text-2xl text-orange-700"></i>
                                @else
                                    <i class="fas fa-credit-card text-2xl text-gray-700"></i>
                                @endif
                            </div>
                            
                            <div>
                                <div class="font-medium">•••• •••• •••• {{ $paymentMethod->card->last4 }}</div>
                                <div class="text-sm text-gray-600">Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}</div>
                            </div>
                            
                            <input type="hidden" name="payment_method" value="{{ $paymentMethod->id }}">
                            <input type="hidden" name="use_existing_method" value="1">
                        </div>
                    </div>
                    
                    <div class="text-center my-2">
                        <span class="text-sm text-gray-500">OR</span>
                    </div>
                    @endif
                    
                    <!-- New payment method section -->
                    <div class="bg-gray-50 p-4 rounded-md border border-gray-200 mb-4">
                        <h4 class="font-medium mb-3">{{ $hasPaymentMethod ? 'Use a new payment method' : 'Enter payment details' }}</h4>
                        
                        <div class="mb-4">
                            <div id="card-element" class="border border-gray-300 p-3 rounded-md bg-white"></div>
                            <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                        </div>
                        
                        <div class="text-sm text-gray-500 mt-2">
                            <p>
                                <i class="fas fa-lock mr-1"></i>
                                Your payment information is secure and encrypted
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button type="submit" id="submit-button" class="btn btn-primary px-6">
                            Process Payment <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Terms -->
            <div class="px-6 py-4 bg-gray-50">
                <h3 class="font-bold text-gray-700 mb-3">Terms & Conditions</h3>
                <div class="text-sm text-gray-600">
                    <p class="mb-2">
                        By completing this payment, you acknowledge that:
                    </p>
                    <ul class="list-disc pl-5 space-y-1">
                        <li>The work has been completed to your satisfaction.</li>
                        <li>You agree to the project collaboration terms.</li>
                        <li>Upon payment, you will receive full ownership rights to the delivered work as specified in the project details.</li>
                        <li>The payment is final and non-refundable.</li>
                    </ul>
                </div>
                
                <div class="mt-4 border-t border-gray-200 pt-4">
                    <a href="{{ route('pitches.show', $pitch) }}" class="text-gray-600 hover:text-gray-900 font-medium">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Pitch
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Create a Stripe client
        const stripe = Stripe('{{ config('cashier.key') }}');
        const elements = stripe.elements();
        
        // Create an instance of the card Element
        const cardElement = elements.create('card', {
            style: {
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
            }
        });
        
        // Add an instance of the card Element into the `card-element` div
        cardElement.mount('#card-element');
        
        // Handle real-time validation errors from the card Element
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
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Check if using existing payment method
            const useExistingMethod = document.querySelector('input[name="use_existing_method"]');
            if (useExistingMethod && useExistingMethod.value === '1') {
                // Submit the form directly if using existing payment method
                form.submit();
                return;
            }
            
            // Otherwise handle the new payment method
            const submitButton = document.getElementById('submit-button');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            
            stripe.confirmCardSetup(
                '{{ $intent->client_secret }}',
                {
                    payment_method: {
                        card: cardElement,
                    }
                }
            ).then(function(result) {
                if (result.error) {
                    // Show error to customer
                    const errorElement = document.getElementById('card-errors');
                    errorElement.textContent = result.error.message;
                    submitButton.disabled = false;
                    submitButton.innerHTML = 'Process Payment <i class="fas fa-arrow-right ml-2"></i>';
                } else {
                    // Add payment method ID to form
                    const paymentMethodInput = document.createElement('input');
                    paymentMethodInput.setAttribute('type', 'hidden');
                    paymentMethodInput.setAttribute('name', 'payment_method');
                    paymentMethodInput.setAttribute('value', result.setupIntent.payment_method);
                    form.appendChild(paymentMethodInput);
                    
                    // Submit the form
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

@endsection 