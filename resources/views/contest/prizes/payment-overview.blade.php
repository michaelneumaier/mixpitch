@extends('components.layouts.app')

@section('title', 'Contest Prize Payment')

@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<div class="min-h-screen bg-gradient-to-br from-purple-50 via-white to-indigo-50 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <div class="flex justify-center mb-6">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 p-4 rounded-full">
                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                </div>
            </div>
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Contest Prize Payment</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Process payments for contest winners and schedule their payouts.
            </p>
        </div>

        <!-- Contest Information -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-purple-50 to-indigo-50">
                <h2 class="text-2xl font-semibold text-gray-900 mb-2">{{ $project->name }}</h2>
                <div class="flex items-center text-sm text-gray-600">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span>Contest finalized on {{ $project->judging_finalized_at->format('M j, Y \a\t g:i A') }}</span>
                </div>
            </div>

            <!-- Status Messages -->
            @if(session('success'))
                <div class="px-8 py-4 bg-green-50 border-b border-green-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('info'))
                <div class="px-8 py-4 bg-blue-50 border-b border-blue-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">{{ session('info') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="px-8 py-4 bg-red-50 border-b border-red-200">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <div class="text-sm font-medium text-red-800">
                                @foreach($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Prize Winners Section -->
            <div class="px-8 py-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-900">Contest Winners & Prizes</h3>
                    <div class="text-sm text-gray-600">
                        <span class="font-medium">Total Prize Pool:</span> 
                        <span class="text-lg font-bold text-purple-600 ml-1">${{ number_format($totalPrizeAmount, 2) }}</span>
                    </div>
                </div>
                
                @if(empty($allWinners))
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No Cash Prizes</h3>
                        <p class="mt-1 text-sm text-gray-500">This contest doesn't have any cash prizes to pay out.</p>
                        <div class="mt-6">
                            <a href="{{ route('projects.manage', $project) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                Back to Contest Management
                            </a>
                        </div>
                    </div>
                @else
                    <!-- Winner Cards -->
                    <div class="space-y-4">
                        @foreach($allWinners as $index => $winner)
                            @php
                                $status = $winner['stripe_status'];
                                $isFullyVerified = $status['status'] === 'active';
                                $hasAccount = isset($winner['user']->stripe_account_id) && $winner['user']->stripe_account_id;
                                $isPaid = $winner['pitch']->payment_status === 'paid';
                                $canPay = $isFullyVerified && !$isPaid;
                                $shouldAutoExpand = $canPay; // Auto-expand winners ready for payment
                            @endphp
                            
                            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden winner-card" data-winner-id="{{ $winner['prize']->id }}">
                                <!-- Winner Summary (Always Visible) -->
                                <div class="p-6 cursor-pointer winner-summary" onclick="toggleWinnerCard({{ $winner['prize']->id }})">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-4">
                                            <!-- Prize Placement Badge -->
                                            <div class="flex-shrink-0">
                                                @if($winner['prize']->placement === '1st')
                                                    <div class="w-14 h-14 bg-yellow-400 rounded-full flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">1st</span>
                                                    </div>
                                                @elseif($winner['prize']->placement === '2nd')
                                                    <div class="w-14 h-14 bg-gray-400 rounded-full flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">2nd</span>
                                                    </div>
                                                @elseif($winner['prize']->placement === '3rd')
                                                    <div class="w-14 h-14 bg-amber-600 rounded-full flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-lg">3rd</span>
                                                    </div>
                                                @else
                                                    <div class="w-14 h-14 bg-purple-500 rounded-full flex items-center justify-center shadow-lg">
                                                        <span class="text-white font-bold text-sm">{{ $winner['prize']->placement }}</span>
                                                    </div>
                                                @endif
                                            </div>

                                            <!-- Winner Info -->
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-3 mb-1">
                                                    <h4 class="text-lg font-semibold text-gray-900">
                                                        {{ $winner['prize']->getPlacementDisplayName() }}
                                                    </h4>
                                                    <!-- Status Badge -->
                                                    @if($isPaid)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-check-circle mr-1"></i>
                                                            Paid
                                                        </span>
                                                    @elseif($isFullyVerified)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <i class="fas fa-check-circle mr-1"></i>
                                                            Ready for Payment
                                                        </span>
                                                    @elseif($hasAccount)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            <i class="fas fa-clock mr-1"></i>
                                                            Setup Pending
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                            <i class="fas fa-exclamation-circle mr-1"></i>
                                                            Setup Required
                                                        </span>
                                                    @endif
                                                </div>
                                                
                                                                                <!-- Winner Name & Prize Amount -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3 flex-1 min-w-0 mr-4">
                                        <!-- Profile Image -->
                                        <div class="flex-shrink-0">
                                            <img class="h-8 w-8 rounded-full object-cover border border-gray-200" 
                                                 src="{{ $winner['user']->profile_photo_url }}" 
                                                 alt="{{ $winner['user']->name ?? 'User' }}">
                                        </div>
                                        <!-- User Name with Link -->
                                        <div class="min-w-0">
                                            <div class="text-sm font-medium truncate">
                                                <x-user-link :user="$winner['user']" />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right flex-shrink-0">
                                        <div class="text-lg sm:text-xl font-bold text-green-600">
                                            ${{ number_format($winner['prize']->cash_amount, 2) }}
                                        </div>
                                        @if($isPaid)
                                            <div class="text-xs text-green-600">
                                                Paid {{ $winner['pitch']->payment_completed_at ? $winner['pitch']->payment_completed_at->format('M j') : 'Recently' }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                            </div>
                                        </div>

                                        <!-- Expand/Collapse Arrow -->
                                        <div class="ml-4 flex-shrink-0">
                                            <i class="fas fa-chevron-down text-gray-400 transition-transform duration-200 expand-arrow" id="arrow-{{ $winner['prize']->id }}"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Winner Details (Collapsible) -->
                                <div class="winner-details {{ $shouldAutoExpand ? '' : 'hidden' }}" id="details-{{ $winner['prize']->id }}">
                                    <div class="px-6 pb-6 border-t border-gray-100">
                                        <!-- Detailed Winner Information -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 mt-4">
                                            <!-- Winner Details -->
                                            <div>
                                                <h5 class="text-sm font-medium text-gray-900 mb-3">Winner Information</h5>
                                                <div class="space-y-3">
                                                    <div class="flex items-start space-x-3">
                                                        <!-- Profile Image -->
                                                        <div class="flex-shrink-0">
                                                            <img class="h-12 w-12 rounded-full object-cover border border-gray-200" 
                                                                 src="{{ $winner['user']->profile_photo_url }}" 
                                                                 alt="{{ $winner['user']->name ?? 'User' }}">
                                                        </div>
                                                        <div class="flex-1 min-w-0">
                                                            <!-- User Name with Link -->
                                                            <div class="text-sm font-medium text-gray-900">
                                                                <x-user-link :user="$winner['user']" />
                                                            </div>
                                                            @if($winner['user']->username)
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    <i class="fas fa-at mr-1"></i>{{ $winner['user']->username }}
                                                                </p>
                                                            @endif
                                                            @if($winner['user']->created_at)
                                                                <p class="text-xs text-gray-500 mt-1">
                                                                    <i class="fas fa-calendar-alt mr-1"></i>
                                                                    Member since {{ $winner['user']->created_at->format('M j, Y') }}
                                                                </p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Pitch Details -->
                                            <div>
                                                <h5 class="text-sm font-medium text-gray-900 mb-3">Winning Pitch</h5>
                                                <div class="space-y-2">
                                                    <p class="text-xs text-gray-600">
                                                        Submitted {{ $winner['pitch']->created_at->format('M j, Y') }}
                                                    </p>
                                                    @if($winner['pitch']->description)
                                                        <p class="text-xs text-gray-600 line-clamp-2">
                                                            {{ Str::limit($winner['pitch']->description, 100) }}
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Stripe Connect Status -->
                                        @php
                                            // Determine status colors and messages
                                            if ($isFullyVerified) {
                                                $statusBg = 'bg-green-50 border-green-200';
                                                $statusIcon = 'text-green-500';
                                                $statusTextColor = 'text-green-800';
                                                $statusDescColor = 'text-green-700';
                                                $customStatusDisplay = 'Ready for Payment';
                                                $customStatusDescription = $winner['user']->name . ' has completed their Stripe Connect setup and can receive payments.';
                                                $customNextSteps = ['You can now process payment to this winner.'];
                                            } elseif ($hasAccount) {
                                                $statusBg = 'bg-yellow-50 border-yellow-200';
                                                $statusIcon = 'text-yellow-500';
                                                $statusTextColor = 'text-yellow-800';
                                                $statusDescColor = 'text-yellow-700';
                                                $customStatusDisplay = 'Setup In Progress';
                                                $customStatusDescription = $winner['user']->name . ' has started their Stripe Connect setup but verification is still pending.';
                                                $customNextSteps = ['Winner needs to complete their account verification.', 'Payment will be available once verification is complete.'];
                                            } else {
                                                $statusBg = 'bg-red-50 border-red-200';
                                                $statusIcon = 'text-red-500';
                                                $statusTextColor = 'text-red-800';
                                                $statusDescColor = 'text-red-700';
                                                $customStatusDisplay = 'Setup Required';
                                                $customStatusDescription = $winner['user']->name . ' needs to create their Stripe Connect account to receive payments.';
                                                $customNextSteps = ['Winner must set up their Stripe Connect account.', 'They will be notified to complete the setup process.'];
                                            }
                                        @endphp
                                        
                                        <div class="{{ $statusBg }} border rounded-lg p-4 mb-6">
                                            <div class="flex items-start">
                                                @if($isFullyVerified)
                                                    <i class="fas fa-check-circle {{ $statusIcon }} mt-0.5 mr-3"></i>
                                                @elseif($hasAccount)
                                                    <i class="fas fa-clock {{ $statusIcon }} mt-0.5 mr-3"></i>
                                                @else
                                                    <i class="fas fa-info-circle {{ $statusIcon }} mt-0.5 mr-3"></i>
                                                @endif
                                                <div class="flex-1">
                                                    <h5 class="text-sm font-medium {{ $statusTextColor }}">
                                                        {{ $customStatusDisplay }}
                                                    </h5>
                                                    <p class="text-sm {{ $statusDescColor }} mt-1">
                                                        {{ $customStatusDescription }}
                                                    </p>
                                                    @if(!empty($customNextSteps))
                                                        <div class="mt-3">
                                                            <p class="text-xs font-medium {{ $statusTextColor }}">Next Steps:</p>
                                                            <ul class="text-xs {{ $statusDescColor }} mt-1 space-y-0.5 list-disc list-inside">
                                                                @foreach(array_slice($customNextSteps, 0, 3) as $step)
                                                                    <li>{{ $step }}</li>
                                                                @endforeach
                                                            </ul>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Action Section -->
                                        @if($isPaid)
                                            <!-- Already Paid -->
                                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                                                        <div>
                                                            <h5 class="text-sm font-medium text-green-800">Payment Completed</h5>
                                                            <p class="text-sm text-green-700">
                                                                Paid on {{ $winner['pitch']->payment_completed_at ? $winner['pitch']->payment_completed_at->format('M j, Y \a\t g:i A') : 'N/A' }}
                                                            </p>
                                                            @if($winner['pitch']->final_invoice_id)
                                                                <div class="mt-2">
                                                                    <a href="{{ route('projects.pitches.payment.receipt', ['project' => $project, 'pitch' => $winner['pitch']]) }}" 
                                                                       class="inline-flex items-center text-xs text-green-700 hover:text-green-800 font-medium">
                                                                        <i class="fas fa-receipt mr-1"></i>
                                                                        View Invoice
                                                                    </a>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-lg font-bold text-green-600">
                                                            ${{ number_format($winner['prize']->cash_amount, 2) }}
                                                        </div>
                                                        <div class="text-xs text-green-600">Completed</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($canPay)
                                            <!-- Payment Form -->
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                                <div class="mb-4">
                                                    <h5 class="text-sm font-medium text-blue-800 mb-1">Ready to Process Payment</h5>
                                                    <p class="text-sm text-blue-700">
                                                        {{ $winner['user']->name ?? $winner['user']->email }} has completed their Stripe Connect setup and is ready to receive their prize payout.
                                                    </p>
                                                </div>
                                                
                                                <form action="{{ route('contest.prizes.process.individual', [$project, $winner['prize']->id]) }}" method="POST" class="individual-payment-form">
                                                    @csrf
                                                    
                                                    <!-- Payment Method Selection -->
                                                    <div class="mb-4">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                                        <div class="space-y-2">
                                                            @if($paymentMethods && $paymentMethods->count() > 0)
                                                                @foreach($paymentMethods as $method)
                                                                    <div class="relative">
                                                                        <input type="radio" 
                                                                               id="payment_method_{{ $method->id }}_{{ $winner['prize']->id }}" 
                                                                               name="payment_method_selection" 
                                                                               value="{{ $method->id }}" 
                                                                               class="sr-only peer"
                                                                               {{ $defaultPaymentMethod && $method->id === $defaultPaymentMethod->id ? 'checked' : '' }}>
                                                                        <label for="payment_method_{{ $method->id }}_{{ $winner['prize']->id }}" 
                                                                               class="flex items-center p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-300 peer-checked:ring-2 peer-checked:ring-blue-200 transition-all">
                                                                            <div class="flex items-center flex-1">
                                                                                <div class="mr-3">
                                                                                    @if($method->card->brand === 'visa')
                                                                                        <i class="fab fa-cc-visa text-xl text-blue-600"></i>
                                                                                    @elseif($method->card->brand === 'mastercard')
                                                                                        <i class="fab fa-cc-mastercard text-xl text-orange-600"></i>
                                                                                    @elseif($method->card->brand === 'amex')
                                                                                        <i class="fab fa-cc-amex text-xl text-blue-800"></i>
                                                                                    @elseif($method->card->brand === 'discover')
                                                                                        <i class="fab fa-cc-discover text-xl text-orange-700"></i>
                                                                                    @else
                                                                                        <i class="fas fa-credit-card text-xl text-gray-700"></i>
                                                                                    @endif
                                                                                </div>
                                                                                <div>
                                                                                    <div class="font-medium text-gray-900">{{ ucfirst($method->card->brand) }} •••• {{ $method->card->last4 }}</div>
                                                                                    <div class="text-sm text-gray-600">Expires {{ $method->card->exp_month }}/{{ $method->card->exp_year }}</div>
                                                                                </div>
                                                                            </div>
                                                                        </label>
                                                                    </div>
                                                                @endforeach
                                                            @endif
                                                            
                                                            <!-- Add New Payment Method Option -->
                                                            <div class="relative">
                                                                <input type="radio" 
                                                                       id="payment_method_new_{{ $winner['prize']->id }}" 
                                                                       name="payment_method_selection" 
                                                                       value="new" 
                                                                       class="sr-only peer"
                                                                       {{ (!$paymentMethods || $paymentMethods->count() === 0) ? 'checked' : '' }}>
                                                                <label for="payment_method_new_{{ $winner['prize']->id }}" 
                                                                       class="flex items-center p-3 bg-white border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 peer-checked:bg-blue-50 peer-checked:border-blue-300 peer-checked:ring-2 peer-checked:ring-blue-200 transition-all">
                                                                    <div class="flex items-center flex-1">
                                                                        <div class="mr-3">
                                                                            <i class="fas fa-plus-circle text-xl text-blue-600"></i>
                                                                        </div>
                                                                        <div>
                                                                            <div class="font-medium text-gray-900">Add New Payment Method</div>
                                                                            <div class="text-sm text-gray-600">Enter card details below</div>
                                                                        </div>
                                                                    </div>
                                                                </label>
                                                            </div>
                                                        </div>
                                                        
                                                        <!-- New Card Details (shown when "Add New" is selected) -->
                                                        <div id="new_card_details_{{ $winner['prize']->id }}" class="mt-4 p-4 border border-gray-200 rounded-lg bg-gray-50" style="{{ (!$paymentMethods || $paymentMethods->count() === 0) ? '' : 'display: none;' }}">
                                                            <div class="mb-3">
                                                                <label class="block text-sm font-medium text-gray-700 mb-2">Card Information</label>
                                                                <div id="card-element-{{ $winner['prize']->id }}" class="p-3 border border-gray-300 rounded-md bg-white">
                                                                    <!-- Stripe Elements will create form elements here -->
                                                                </div>
                                                                <div id="card-errors-{{ $winner['prize']->id }}" class="text-red-600 text-sm mt-2" role="alert"></div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Confirmation and Payment -->
                                                    <div class="space-y-4">
                                                        <div class="flex items-start space-x-3">
                                                            <div class="flex items-center h-5 mt-1">
                                                                <input id="confirm_payment_{{ $winner['prize']->id }}" name="confirm_payment" type="checkbox" required 
                                                                       class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 rounded">
                                                            </div>
                                                            <div class="flex-1">
                                                                <label for="confirm_payment_{{ $winner['prize']->id }}" class="text-sm font-medium text-gray-700">
                                                                    I confirm payment of ${{ number_format($winner['prize']->cash_amount, 2) }} to {{ $winner['user']->name ?? $winner['user']->email }}
                                                                </label>
                                                                <p class="text-xs text-gray-600 mt-1">
                                                                    Payment will be processed immediately. Winner receives payout after {{ app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('contest')['description'] }}.
                                                                </p>
                                                            </div>
                                                        </div>
                                                        <div class="flex justify-end">
                                                            <button type="submit" 
                                                                    class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                                                <i class="fas fa-credit-card mr-2"></i>
                                                                Pay ${{ number_format($winner['prize']->cash_amount, 2) }}
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <input type="hidden" name="payment_method_id" class="payment-method-id">
                                                </form>
                                            </div>
                                        @else
                                            <!-- Cannot Pay Yet -->
                                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                                <div class="flex items-start justify-between">
                                                    <div class="flex items-start">
                                                        <i class="fas fa-clock text-yellow-500 mr-3 mt-0.5"></i>
                                                        <div>
                                                            <h5 class="text-sm font-medium text-yellow-800">
                                                                @if(!$hasAccount)
                                                                    Stripe Connect Setup Required
                                                                @else
                                                                    Account Verification In Progress
                                                                @endif
                                                            </h5>
                                                            <p class="text-sm text-yellow-700">
                                                                @if(!$hasAccount)
                                                                    {{ $winner['user']->name ?? $winner['user']->email }} needs to create their Stripe Connect account to receive payouts.
                                                                @else
                                                                    {{ $winner['user']->name ?? $winner['user']->email }} has started setup but verification is still in progress.
                                                                @endif
                                                            </p>
                                                        </div>
                                                    </div>
                                                    @if(auth()->id() === $winner['user']->id)
                                                        <a href="{{ route('stripe.connect.setup') }}" 
                                                           class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-yellow-600 hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                                            <i class="fas fa-cog mr-2"></i>
                                                            {{ !$hasAccount ? 'Set Up Payouts' : 'Complete Setup' }}
                                                        </a>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <!-- Information Section -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">How Individual Prize Payments Work</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <ul class="list-disc pl-5 space-y-1">
                            <li><strong>Click any winner card</strong> to expand and see detailed status information</li>
                            <li><strong>Pay winners individually</strong> as they complete their Stripe Connect setup</li>
                            <li><strong>Cards ready for payment</strong> are automatically expanded with payment forms</li>
                            <li><strong>{{ ucfirst(app(\App\Services\PayoutHoldService::class)->getHoldPeriodInfo('contest')['description']) }}</strong> applies to all payouts for fraud protection</li>
                            <li><strong>Winners are notified</strong> automatically when their payout is scheduled</li>
                            <li><strong>No need to wait</strong> for all winners - pay as they become ready</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-6 text-center">
            <a href="{{ route('projects.manage', $project) }}" 
               class="inline-flex items-center px-6 py-3 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Contest Management
            </a>
        </div>
    </div>
</div>

<!-- Stripe Elements -->
@if($setupIntent)
<script src="https://js.stripe.com/v3/"></script>
<script>
    const stripe = Stripe('{{ config('cashier.key') }}');
    const elements = stripe.elements();
    
    // Store card elements for each form
    const cardElements = {};
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Stripe Elements for each payment form
        const paymentForms = document.querySelectorAll('.individual-payment-form');
        
        paymentForms.forEach(function(form) {
            const formId = form.querySelector('input[name="payment_method_selection"][value="new"]').id.split('_').pop();
            
            // Create card element for this form
            const cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#424770',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                    },
                },
            });
            
            cardElement.mount(`#card-element-${formId}`);
            cardElements[formId] = cardElement;
            
            // Handle real-time validation errors from the card Element
            cardElement.on('change', function(event) {
                const displayError = document.getElementById(`card-errors-${formId}`);
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });
            
            const paymentMethodRadios = form.querySelectorAll('input[name="payment_method_selection"]');
            const paymentMethodIdInput = form.querySelector('.payment-method-id');
            const submitButton = form.querySelector('button[type="submit"]');
            const newCardDetails = document.getElementById(`new_card_details_${formId}`);
            
            // Handle payment method selection
            paymentMethodRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        // Show/hide new card details
                        if (this.value === 'new') {
                            newCardDetails.style.display = 'block';
                        } else {
                            newCardDetails.style.display = 'none';
                            paymentMethodIdInput.value = this.value;
                        }
                        
                        // Add use_existing_method flag for existing payment methods
                        let useExistingInput = form.querySelector('input[name="use_existing_method"]');
                        if (useExistingInput) {
                            useExistingInput.remove();
                        }
                        
                        if (this.value !== 'new') {
                            useExistingInput = document.createElement('input');
                            useExistingInput.setAttribute('type', 'hidden');
                            useExistingInput.setAttribute('name', 'use_existing_method');
                            useExistingInput.setAttribute('value', '1');
                            form.appendChild(useExistingInput);
                        }
                    }
                });
            });
            
            // Handle form submission
            form.addEventListener('submit', async function(event) {
                event.preventDefault();
                
                const selectedPaymentMethod = form.querySelector('input[name="payment_method_selection"]:checked');
                
                if (!selectedPaymentMethod) {
                    alert('Please select a payment method');
                    return;
                }
                
                // Disable the submit button to prevent double-submission
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                
                if (selectedPaymentMethod.value === 'new') {
                    // Create payment method with new card
                    const {error, paymentMethod} = await stripe.createPaymentMethod({
                        type: 'card',
                        card: cardElements[formId],
                    });
                    
                    if (error) {
                        // Show error to customer
                        const errorElement = document.getElementById(`card-errors-${formId}`);
                        errorElement.textContent = error.message;
                        
                        // Re-enable submit button
                        submitButton.disabled = false;
                        submitButton.innerHTML = '<i class="fas fa-credit-card mr-2"></i>Pay ${{ number_format($winner["prize"]->cash_amount, 2) }}';
                        return;
                    }
                    
                    // Set the payment method ID
                    paymentMethodIdInput.value = paymentMethod.id;
                } else {
                    // Use existing payment method
                    paymentMethodIdInput.value = selectedPaymentMethod.value;
                }
                
                // Submit the form
                form.submit();
            });
        });
    });
</script>
@else
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle individual payment forms (no Stripe Elements needed)
    const paymentForms = document.querySelectorAll('.individual-payment-form');
    
    paymentForms.forEach(function(form) {
        const paymentMethodRadios = form.querySelectorAll('input[name="payment_method_selection"]');
        const paymentMethodIdInput = form.querySelector('.payment-method-id');
        const submitButton = form.querySelector('button[type="submit"]');
        
        // Handle payment method selection
        paymentMethodRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.checked) {
                    paymentMethodIdInput.value = this.value;
                    
                    // Add use_existing_method flag for existing payment methods
                    let useExistingInput = form.querySelector('input[name="use_existing_method"]');
                    if (useExistingInput) {
                        useExistingInput.remove();
                    }
                    
                    if (this.value !== 'new') {
                        useExistingInput = document.createElement('input');
                        useExistingInput.setAttribute('type', 'hidden');
                        useExistingInput.setAttribute('name', 'use_existing_method');
                        useExistingInput.setAttribute('value', '1');
                        form.appendChild(useExistingInput);
                    }
                }
            });
        });
        
        // Handle form submission
        form.addEventListener('submit', function(event) {
            const selectedPaymentMethod = form.querySelector('input[name="payment_method_selection"]:checked');
            
            if (!selectedPaymentMethod) {
                event.preventDefault();
                alert('Please select a payment method');
                return;
            }
            
            // Disable the submit button to prevent double-submission
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
            
            // Set the payment method ID
            paymentMethodIdInput.value = selectedPaymentMethod.value;
        });
    });
});
</script>
@endif

<script>
// Toggle winner card expand/collapse
function toggleWinnerCard(winnerId) {
    const details = document.getElementById('details-' + winnerId);
    const arrow = document.getElementById('arrow-' + winnerId);
    
    if (details.classList.contains('hidden')) {
        // Expand
        details.classList.remove('hidden');
        arrow.style.transform = 'rotate(180deg)';
    } else {
        // Collapse
        details.classList.add('hidden');
        arrow.style.transform = 'rotate(0deg)';
    }
}

// Auto-expand cards ready for payment on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-rotate arrows for pre-expanded cards
    const expandedCards = document.querySelectorAll('.winner-details:not(.hidden)');
    expandedCards.forEach(function(card) {
        const winnerId = card.id.replace('details-', '');
        const arrow = document.getElementById('arrow-' + winnerId);
        if (arrow) {
            arrow.style.transform = 'rotate(180deg)';
        }
    });
});
</script>
@endsection 