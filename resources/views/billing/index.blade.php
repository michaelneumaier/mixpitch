@extends('components.layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen py-12">
    <!-- Enhanced Header Section -->
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-8">
        <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-xl p-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="mb-6 lg:mb-0">
                    <h1 class="text-4xl lg:text-5xl font-bold bg-gradient-to-r from-green-600 via-blue-600 to-purple-600 bg-clip-text text-transparent mb-2">
                        Billing & Payments
                    </h1>
                    <p class="text-gray-600 text-lg">Manage your payment methods, billing history, and subscriptions</p>
                </div>
                
                <!-- Billing Stats -->
                <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="bg-gradient-to-br from-green-100/80 to-emerald-100/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4 text-center">
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mb-2 mx-auto">
                            <i class="fas fa-credit-card text-white text-sm"></i>
                        </div>
                        <div class="text-sm font-medium text-green-800">Payment Methods</div>
                        <div class="text-lg font-bold text-green-900">{{ $hasPaymentMethod ? '1' : '0' }}</div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-blue-100/80 to-indigo-100/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mb-2 mx-auto">
                            <i class="fas fa-receipt text-white text-sm"></i>
                        </div>
                        <div class="text-sm font-medium text-blue-800">Total Invoices</div>
                        <div class="text-lg font-bold text-blue-900">{{ count($invoices) }}</div>
                    </div>
                    
                    <div class="bg-gradient-to-br from-purple-100/80 to-indigo-100/80 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4 text-center col-span-2 lg:col-span-1">
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mb-2 mx-auto">
                            <i class="fas fa-chart-line text-white text-sm"></i>
                        </div>
                        <div class="text-sm font-medium text-purple-800">Status</div>
                        <div class="text-lg font-bold text-purple-900">{{ $hasPaymentMethod ? 'Active' : 'Setup Required' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-green-50/30">
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <!-- Include Subscription Overview -->
                @include('billing.components.subscription-overview', [
                    'user' => $user,
                    'isSubscribed' => $isSubscribed,
                    'subscription' => $subscription,
                    'onGracePeriod' => $onGracePeriod,
                    'limits' => $limits,
                    'usage' => $usage,
                    'billingSummary' => $billingSummary
                ])
                
                <div class="bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-xl overflow-hidden">
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

                        <!-- Payment Method Section -->
                        <div class="mb-8">
                            <div class="flex items-center mb-6">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4">
                                    <i class="fas fa-credit-card text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Payment Methods</h3>
                            </div>
                            
                            @if($hasPaymentMethod && $paymentMethod)
                                <div class="bg-gradient-to-br from-white/90 to-gray-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 mb-6 shadow-lg hover:shadow-xl transition-all duration-300 group">
                                    <div class="flex items-center gap-6">
                                        <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-2xl group-hover:scale-105 transition-transform duration-300">
                                            @if(isset($paymentMethod->card->brand))
                                                @if($paymentMethod->card->brand === 'visa')
                                                    <i class="fab fa-cc-visa text-3xl text-blue-600"></i>
                                                @elseif($paymentMethod->card->brand === 'mastercard')
                                                    <i class="fab fa-cc-mastercard text-3xl text-orange-600"></i>
                                                @elseif($paymentMethod->card->brand === 'amex')
                                                    <i class="fab fa-cc-amex text-3xl text-blue-800"></i>
                                                @elseif($paymentMethod->card->brand === 'discover')
                                                    <i class="fab fa-cc-discover text-3xl text-orange-500"></i>
                                                @else
                                                    <i class="fas fa-credit-card text-3xl text-gray-700"></i>
                                                @endif
                                            @else
                                                <i class="fas fa-credit-card text-3xl text-gray-700"></i>
                                            @endif
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-lg font-bold text-gray-900 mb-1">
                                                {{ isset($paymentMethod->card->brand) ? ucfirst($paymentMethod->card->brand) : 'Payment Method' }} 
                                                @if(isset($paymentMethod->card->last4))
                                                    •••• {{ $paymentMethod->card->last4 }}
                                                @endif
                                            </div>
                                            <div class="text-sm text-gray-600 flex items-center">
                                                <i class="fas fa-calendar-alt mr-2"></i>
                                                @if(isset($paymentMethod->card->exp_month) && isset($paymentMethod->card->exp_year))
                                                    Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                                                @else
                                                    Payment method details available
                                                @endif
                                            </div>
                                            <div class="mt-2">
                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100/80 text-green-800 border border-green-200/50">
                                                    <i class="fas fa-check-circle mr-1"></i>
                                                    Default Payment Method
                                                </span>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('billing.payment.remove') }}" class="ml-auto">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-100 to-pink-100 hover:from-red-200 hover:to-pink-200 text-red-700 rounded-xl border border-red-200/50 transition-all duration-200 hover:scale-105">
                                                <i class="fas fa-trash-alt mr-2"></i>
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button id="updateCard" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-edit mr-2"></i>
                                        Update Payment Method
                                    </button>
                                    <a href="{{ route('billing.payment-methods') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-credit-card mr-2"></i>
                                        Manage All Methods
                                    </a>
                                </div>
                            @else
                                <div class="bg-gradient-to-br from-amber-50/90 to-orange-50/90 backdrop-blur-sm border border-amber-200/50 rounded-2xl p-6 mb-6 shadow-lg">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-500 rounded-xl mr-4">
                                            <i class="fas fa-exclamation-triangle text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold text-amber-800 mb-1">No Payment Method</h4>
                                            <p class="text-amber-700">Add a payment method to start making payments and manage your billing.</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-3">
                                    <button id="addCard" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-plus mr-2"></i>
                                        Add Payment Method
                                    </button>
                                    <a href="{{ route('billing.payment-methods') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-credit-card mr-2"></i>
                                        Manage Payment Methods
                                    </a>
                                </div>
                            @endif

                            <!-- Enhanced Payment Method Form (hidden by default) -->
                            <div id="paymentMethodForm" class="mt-6 hidden">
                                <div class="bg-gradient-to-br from-blue-50/90 to-indigo-50/90 backdrop-blur-sm border border-blue-200/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex items-center mb-4">
                                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                                            <i class="fas fa-lock text-white text-sm"></i>
                                        </div>
                                        <h4 class="text-lg font-bold text-blue-800">{{ $hasPaymentMethod ? 'Update Payment Method' : 'Add Payment Method' }}</h4>
                                    </div>
                                    
                                    <form id="payment-form" action="{{ route('billing.payment.update') }}" method="POST">
                                        @csrf
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

                                        <div class="flex flex-wrap gap-3">
                                            <button type="submit" id="card-button" data-secret="{{ $intent->client_secret }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:scale-100">
                                                <span id="button-text" class="flex items-center">
                                                    <i class="fas fa-save mr-2"></i>
                                                    {{ $hasPaymentMethod ? 'Update Card' : 'Add Card' }}
                                                </span>
                                                <span id="spinner" class="hidden flex items-center">
                                                    <i class="fas fa-spinner fa-spin mr-2"></i>
                                                    Processing...
                                                </span>
                                            </button>
                                            <button type="button" onclick="document.getElementById('paymentMethodForm').classList.add('hidden')" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                                <i class="fas fa-times mr-2"></i>
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Stripe Connect Section -->
                        <div class="mb-8">
                            <div class="flex items-center mb-6">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4">
                                    <i class="fas fa-university text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Payout Setup</h3>
                            </div>
                            
                            @php
                                $stripeConnectService = app(\App\Services\StripeConnectService::class);
                                $accountStatus = $stripeConnectService->getDetailedAccountStatus($user);
                                $canReceivePayouts = $stripeConnectService->isAccountReadyForPayouts($user);
                            @endphp
                            
                            <div class="bg-white border border-gray-200 rounded-xl p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        @php
                                            $statusColors = [
                                                'not_created' => ['bg' => 'bg-gray-100', 'text' => 'text-gray-800', 'dot' => 'bg-gray-400'],
                                                'incomplete' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-800', 'dot' => 'bg-yellow-400'],
                                                'action_required' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-800', 'dot' => 'bg-orange-400'],
                                                'past_due' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'dot' => 'bg-red-500'],
                                                'pending_verification' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-800', 'dot' => 'bg-blue-400'],
                                                'under_review' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-800', 'dot' => 'bg-purple-400'],
                                                'restricted' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'dot' => 'bg-red-400'],
                                                'active' => ['bg' => 'bg-green-100', 'text' => 'text-green-800', 'dot' => 'bg-green-400'],
                                                'error' => ['bg' => 'bg-red-100', 'text' => 'text-red-800', 'dot' => 'bg-red-500'],
                                            ];
                                            $colors = $statusColors[$accountStatus['status']] ?? $statusColors['error'];
                                        @endphp

                                        <div class="flex items-center mb-3">
                                            <div class="w-3 h-3 {{ $colors['dot'] }} rounded-full mr-3"></div>
                                            <h4 class="text-lg font-semibold text-gray-900">{{ $accountStatus['status_display'] ?? 'Unknown Status' }}</h4>
                                        </div>
                                        
                                        <p class="text-gray-600 mb-4">{{ $accountStatus['status_description'] ?? 'Unable to determine account status.' }}</p>
                                        
                                        @if(isset($accountStatus['deadline']) && $accountStatus['deadline'])
                                            <div class="mb-4 p-3 bg-orange-50 border border-orange-200 rounded-lg">
                                                <p class="text-sm font-medium text-orange-800">
                                                    <i class="fas fa-clock mr-1"></i>
                                                    Deadline: {{ $accountStatus['deadline']->format('M j, Y \a\t g:i A') }}
                                                </p>
                                            </div>
                                        @endif

                                        @if(isset($accountStatus['next_steps']) && !empty($accountStatus['next_steps']))
                                            <div class="mb-4">
                                                <p class="text-sm font-medium text-gray-700 mb-2">Next Steps:</p>
                                                <ul class="text-sm text-gray-600 space-y-1">
                                                    @foreach(array_slice($accountStatus['next_steps'], 0, 3) as $step)
                                                        <li class="flex items-start">
                                                            @if(str_starts_with($step, '•'))
                                                                <span class="text-gray-400 mr-2 mt-0.5">•</span>
                                                                <span>{{ substr($step, 2) }}</span>
                                                            @else
                                                                <span>{{ $step }}</span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                    @if(count($accountStatus['next_steps']) > 3)
                                                        <li class="text-gray-500 italic">... and {{ count($accountStatus['next_steps']) - 3 }} more steps</li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @endif

                                        <!-- Capability Status -->
                                        @if($accountStatus['status'] !== 'not_created')
                                            <div class="grid grid-cols-2 gap-4 mb-4">
                                                <div class="flex items-center">
                                                    @if($accountStatus['charges_enabled'] ?? false)
                                                        <div class="w-4 h-4 bg-green-400 rounded-full flex items-center justify-center mr-2">
                                                            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="w-4 h-4 bg-red-400 rounded-full flex items-center justify-center mr-2">
                                                            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <span class="text-sm text-gray-600">Charges {{ ($accountStatus['charges_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</span>
                                                </div>
                                                <div class="flex items-center">
                                                    @if($accountStatus['payouts_enabled'] ?? false)
                                                        <div class="w-4 h-4 bg-green-400 rounded-full flex items-center justify-center mr-2">
                                                            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @else
                                                        <div class="w-4 h-4 bg-red-400 rounded-full flex items-center justify-center mr-2">
                                                            <svg class="w-2 h-2 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <span class="text-sm text-gray-600">Payouts {{ ($accountStatus['payouts_enabled'] ?? false) ? 'Enabled' : 'Disabled' }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="flex flex-col sm:flex-row gap-3 mt-6">
                                    @if($accountStatus['status'] === 'not_created' || $accountStatus['status'] === 'incomplete' || $accountStatus['status'] === 'action_required')
                                        <a href="{{ route('stripe.connect.setup') }}" 
                                           class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-gradient-to-r from-purple-500 to-indigo-600 hover:from-purple-600 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200">
                                            <i class="fas fa-university mr-2"></i>
                                            {{ $accountStatus['status'] === 'not_created' ? 'Set Up Stripe Connect' : 'Complete Setup' }}
                                        </a>
                                    @elseif($accountStatus['status'] === 'active')
                                        <a href="{{ route('stripe.connect.dashboard') }}" 
                                           class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200">
                                            <i class="fas fa-external-link-alt mr-2"></i>
                                            Manage Account
                                        </a>
                                    @endif
                                    
                                    <a href="{{ route('stripe.connect.setup') }}" 
                                       class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Billing History Section -->
                        <div>
                            <div class="flex items-center mb-6">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4">
                                    <i class="fas fa-history text-white"></i>
                                </div>
                                <h3 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">Billing History</h3>
                            </div>
                            
                            @if(count($invoices) > 0)
                                <div class="bg-gradient-to-br from-white/90 to-purple-50/90 backdrop-blur-sm border border-white/50 rounded-2xl overflow-hidden shadow-lg">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full">
                                            <thead>
                                                <tr class="bg-gradient-to-r from-purple-100/80 to-indigo-100/80 backdrop-blur-sm">
                                                    <th class="px-6 py-4 text-left text-xs font-bold text-purple-700 uppercase tracking-wider">
                                                        <i class="fas fa-calendar-alt mr-2"></i>Date
                                                    </th>
                                                    <th class="px-6 py-4 text-left text-xs font-bold text-purple-700 uppercase tracking-wider">
                                                        <i class="fas fa-dollar-sign mr-2"></i>Amount
                                                    </th>
                                                    <th class="px-6 py-4 text-left text-xs font-bold text-purple-700 uppercase tracking-wider">
                                                        <i class="fas fa-info-circle mr-2"></i>Status
                                                    </th>
                                                    <th class="px-6 py-4 text-left text-xs font-bold text-purple-700 uppercase tracking-wider">
                                                        <i class="fas fa-cog mr-2"></i>Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-purple-100/50">
                                                @foreach($invoices as $invoice)
                                                    <tr class="hover:bg-gradient-to-r hover:from-purple-50/50 hover:to-indigo-50/50 transition-all duration-200 group">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="flex items-center">
                                                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-lg mr-3 group-hover:scale-105 transition-transform duration-200">
                                                                    <i class="fas fa-receipt text-purple-600 text-sm"></i>
                                                                </div>
                                                                <div>
                                                                    <div class="text-sm font-medium text-gray-900">
                                                                        {{ $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('M d, Y') : $invoice->date()->format('M d, Y') }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-500">
                                                                        {{ $invoice->date instanceof \Carbon\Carbon ? $invoice->date->format('g:i A') : $invoice->date()->format('g:i A') }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="text-lg font-bold text-gray-900">
                                                                @if(isset($invoice->stripe_invoice))
                                                                    ${{ number_format($invoice->total / 100, 2) }}
                                                                @else
                                                                    ${{ number_format(floatval($invoice->total()) / 100, 2) }}
                                                                @endif
                                                            </div>
                                                            <div class="text-xs text-gray-500">USD</div>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            @if($invoice->paid)
                                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200/50 shadow-sm">
                                                                    <i class="fas fa-check-circle mr-1"></i>
                                                                    Paid
                                                                </span>
                                                            @else
                                                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gradient-to-r from-red-100 to-pink-100 text-red-800 border border-red-200/50 shadow-sm">
                                                                    <i class="fas fa-exclamation-circle mr-1"></i>
                                                                    Unpaid
                                                                </span>
                                                            @endif
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <div class="flex items-center gap-2">
                                                                <a href="{{ route('billing.invoice.show', $invoice->id) }}" class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-700 rounded-lg border border-blue-200/50 transition-all duration-200 hover:scale-105 text-xs">
                                                                    <i class="fas fa-eye mr-1"></i>
                                                                    View
                                                                </a>
                                                                <a href="{{ route('billing.invoice.download', $invoice->id) }}" class="inline-flex items-center px-3 py-1.5 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-lg border border-gray-200/50 transition-all duration-200 hover:scale-105 text-xs">
                                                                    <i class="fas fa-download mr-1"></i>
                                                                    Download
                                                                </a>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="mt-6 text-center">
                                    <a href="{{ route('billing.invoices') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-list mr-2"></i>
                                        View All Invoices
                                        <i class="fas fa-arrow-right ml-2"></i>
                                    </a>
                                </div>
                            @else
                                <div class="bg-gradient-to-br from-gray-50/90 to-purple-50/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-8 text-center shadow-lg">
                                    <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-200 to-purple-200 rounded-2xl mx-auto mb-4">
                                        <i class="fas fa-receipt text-gray-500 text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-800 mb-2">No Billing History</h4>
                                    <p class="text-gray-600 mb-4">You haven't made any payments yet.</p>
                                    <p class="text-sm text-gray-500">Invoices and payment history will appear here once you make your first purchase.</p>
                                    
                                    <div class="mt-6">
                                        <button onclick="document.getElementById('amount').focus()" class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-100 to-emerald-100 hover:from-green-200 hover:to-emerald-200 text-green-700 rounded-xl border border-green-200/50 transition-all duration-200 hover:scale-105">
                                            <i class="fas fa-plus mr-2"></i>
                                            Make Your First Payment
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if($hasPaymentMethod)
                            <div class="mt-8 pt-8 border-t border-purple-200/50">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                                        <i class="fas fa-external-link-alt text-white"></i>
                                    </div>
                                    <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Billing Portal</h3>
                                </div>
                                
                                <div class="bg-gradient-to-br from-indigo-50/90 to-purple-50/90 backdrop-blur-sm border border-indigo-200/50 rounded-2xl p-6 mb-6 shadow-lg">
                                    <div class="flex items-center mb-4">
                                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-4">
                                            <i class="fas fa-shield-alt text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold text-indigo-800">Secure Stripe Portal</h4>
                                            <p class="text-sm text-indigo-600">Powered by Stripe's enterprise-grade security</p>
                                        </div>
                                    </div>
                                    <p class="text-indigo-700 mb-4">
                                        Access Stripe's secure customer portal to manage your subscription, update payment methods, view detailed billing history, and download invoices.
                                    </p>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                        <div class="bg-white/60 backdrop-blur-sm border border-indigo-200/50 rounded-xl p-3 text-center">
                                            <i class="fas fa-credit-card text-indigo-600 text-lg mb-2"></i>
                                            <div class="text-xs font-medium text-indigo-800">Payment Methods</div>
                                        </div>
                                        <div class="bg-white/60 backdrop-blur-sm border border-indigo-200/50 rounded-xl p-3 text-center">
                                            <i class="fas fa-file-invoice text-indigo-600 text-lg mb-2"></i>
                                            <div class="text-xs font-medium text-indigo-800">Invoice History</div>
                                        </div>
                                        <div class="bg-white/60 backdrop-blur-sm border border-indigo-200/50 rounded-xl p-3 text-center">
                                            <i class="fas fa-cog text-indigo-600 text-lg mb-2"></i>
                                            <div class="text-xs font-medium text-indigo-800">Account Settings</div>
                                        </div>
                                    </div>
                                </div>
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
                                                    @if(isset($paymentMethod->card->brand))
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
                                                    @else
                                                        <i class="fas fa-credit-card text-xl text-gray-700"></i>
                                                    @endif
                                                </div>
                                                <div class="ml-3">
                                                    <div class="font-medium">
                                                        @if(isset($paymentMethod->card->brand) && isset($paymentMethod->card->last4))
                                                            {{ ucfirst($paymentMethod->card->brand) }} ending in {{ $paymentMethod->card->last4 }}
                                                        @else
                                                            Payment method configured
                                                        @endif
                                                    </div>
                                                    <div class="text-xs text-gray-600">
                                                        @if(isset($paymentMethod->card->exp_month) && isset($paymentMethod->card->exp_year))
                                                            Expires {{ $paymentMethod->card->exp_month }}/{{ $paymentMethod->card->exp_year }}
                                                        @else
                                                            Details available in billing portal
                                                        @endif
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
                                <a href="{{ route('billing.portal') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-external-link-alt mr-2"></i>
                                    Access Billing Portal
                                    <i class="fas fa-arrow-right ml-2"></i>
                                </a>
                            </div>
                        @endif
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