@props(['pitch', 'user' => null])

@php
    $user = $user ?? auth()->user();
    if (!$user || $pitch->user_id !== $user->id) return;
    
    // Get payouts for this specific pitch
    $payouts = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
        ->where('pitch_id', $pitch->id)
        ->with(['project', 'contestPrize'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    // Check if pitch is completed and awaiting payment
    $isAwaitingPayment = $pitch->status === \App\Models\Pitch::STATUS_COMPLETED && 
                        $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING &&
                        $pitch->payment_amount > 0;
    
    // Don't show component if no payouts exist AND not awaiting payment
    if ($payouts->isEmpty() && !$isAwaitingPayment) return;
    
    // Calculate values for existing payouts
    if ($payouts->isNotEmpty()) {
        $latestPayout = $payouts->first();
        $totalEarnings = $payouts->where('status', 'completed')->sum('net_amount');
        $pendingAmount = $payouts->whereIn('status', ['scheduled', 'processing'])->sum('net_amount');
        
        // Determine the primary status to display
        $hasCompleted = $payouts->where('status', 'completed')->count() > 0;
        $hasPending = $payouts->whereIn('status', ['scheduled', 'processing'])->count() > 0;
        $hasFailed = $payouts->where('status', 'failed')->count() > 0;
    }
    
    // Calculate estimated payout for awaiting payment state
    if ($isAwaitingPayment) {
        $estimatedGrossAmount = $pitch->payment_amount ?? $pitch->project->budget;
        $estimatedCommissionRate = $user->getPlatformCommissionRate();
        $estimatedCommissionAmount = $estimatedGrossAmount * ($estimatedCommissionRate / 100);
        $estimatedNetAmount = $estimatedGrossAmount - $estimatedCommissionAmount;
    }
@endphp

@if($payouts->isNotEmpty() || $isAwaitingPayment)
<div class="bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-green-100/80 to-emerald-100/80 border-b border-green-200/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg">
                    @if($isAwaitingPayment)
                        <i class="fas fa-clock text-white"></i>
                    @else
                        <i class="fas fa-money-bill-wave text-white"></i>
                    @endif
                </div>
                <div>
                    <h3 class="text-lg font-bold text-green-800">
                        @if($isAwaitingPayment)
                            Expected Payout
                        @else
                            Payout Status
                        @endif
                    </h3>
                    @if($isAwaitingPayment)
                        <p class="text-sm text-green-600">Your projected earnings from this project</p>
                    @elseif($payouts->isNotEmpty())
                        @if($latestPayout->workflow_type === 'standard')
                            <p class="text-sm text-green-600">Your earnings from this project</p>
                        @elseif($latestPayout->workflow_type === 'contest')
                            <p class="text-sm text-green-600">Your contest prize earnings</p>
                        @else
                            <p class="text-sm text-green-600">Your earnings from this pitch</p>
                        @endif
                    @endif
                </div>
            </div>
            @if($isAwaitingPayment)
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800 animate-pulse">
                    <i class="fas fa-hourglass-half mr-1"></i>
                    Awaiting Payment
                </span>
            @elseif($payouts->isNotEmpty())
                @if($latestPayout->status === 'completed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i>
                        Paid
                    </span>
                @elseif($latestPayout->status === 'scheduled')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800 animate-pulse">
                        <i class="fas fa-clock mr-1"></i>
                        Scheduled
                    </span>
                @elseif($latestPayout->status === 'processing')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800 animate-pulse">
                        <i class="fas fa-spinner fa-spin mr-1"></i>
                        Processing
                    </span>
                @elseif($latestPayout->status === 'failed')
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        Failed
                    </span>
                @endif
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="p-6">
        @if($isAwaitingPayment)
            <!-- Awaiting Payment State -->
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-amber-400 to-yellow-500 rounded-full mb-4 shadow-lg">
                    <i class="fas fa-hourglass-half text-white text-2xl"></i>
                </div>
                <div class="text-3xl font-bold text-green-800 mb-2">
                    ${{ number_format($estimatedNetAmount, 2) }}
                </div>
                <p class="text-green-700 font-medium">Expected Project Payment</p>
                <p class="text-sm text-green-600 mt-1">
                    Awaiting client payment processing
                </p>
            </div>

            <!-- Estimated Breakdown -->
            <div class="bg-white/70 rounded-lg p-4 mb-4 border border-green-200/50">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-green-600 font-medium">Project Amount:</span>
                        <div class="text-green-800 font-semibold">${{ number_format($estimatedGrossAmount, 2) }}</div>
                    </div>
                    <div>
                        <span class="text-green-600 font-medium">Platform Fee:</span>
                        <div class="text-green-800 font-semibold">{{ $estimatedCommissionRate }}%</div>
                    </div>
                    <div class="col-span-2">
                        <span class="text-green-600 font-medium">Project:</span>
                        <div class="text-green-800 font-semibold">{{ $pitch->project->name }}</div>
                    </div>
                </div>
            </div>

            <!-- Payment Process Information -->
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-info-circle text-amber-600 mt-0.5"></i>
                    <div class="flex-1">
                        <h5 class="text-sm font-medium text-amber-800 mb-1">Payment Process</h5>
                        <p class="text-sm text-amber-700">
                            Once the client processes payment, your payout will be scheduled for release within 3 business days. 
                            You'll receive an email notification when payment is processed.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stripe Connect Status Check -->
            @if(!$user->stripe_account_id || !$user->hasValidStripeConnectAccount())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-red-800 mb-1">Action Required</h5>
                            <p class="text-sm text-red-700 mb-2">
                                You need to complete your Stripe Connect account setup to receive payments.
                            </p>
                                                         <a href="{{ route('profile.show') }}#stripe-connect" 
                                class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-medium rounded-lg transition-colors duration-200">
                                 <i class="fas fa-link mr-1"></i>
                                 Setup Payout Account
                             </a>
                        </div>
                    </div>
                </div>
            @endif

        @elseif($payouts->isNotEmpty())
            <!-- Existing Payout Logic (unchanged) -->
            @if($latestPayout->status === 'completed')
                <!-- Completed Payout -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full mb-4 shadow-lg">
                        <i class="fas fa-check text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-green-800 mb-2">
                        ${{ number_format($latestPayout->net_amount, 2) }}
                    </div>
                    @if($latestPayout->workflow_type === 'standard')
                        <p class="text-green-700 font-medium">Project Payment Completed</p>
                    @elseif($latestPayout->workflow_type === 'contest')
                        <p class="text-green-700 font-medium">Contest Prize Paid</p>
                    @else
                        <p class="text-green-700 font-medium">Payment Completed</p>
                    @endif
                    <p class="text-sm text-green-600 mt-1">
                        Paid on {{ $latestPayout->completed_at?->format('M j, Y \a\t g:i A') }}
                    </p>
                </div>

            @elseif($latestPayout->status === 'scheduled')
                <!-- Scheduled Payout -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full mb-4 shadow-lg">
                        <i class="fas fa-clock text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-blue-800 mb-2">
                        ${{ number_format($latestPayout->net_amount, 2) }}
                    </div>
                    @if($latestPayout->workflow_type === 'standard')
                        <p class="text-blue-700 font-medium">Project Payment Scheduled</p>
                    @elseif($latestPayout->workflow_type === 'contest')
                        <p class="text-blue-700 font-medium">Contest Prize Scheduled</p>
                    @else
                        <p class="text-blue-700 font-medium">Payment Scheduled</p>
                    @endif
                    <p class="text-sm text-blue-600 mt-1">
                        Releases {{ $latestPayout->hold_release_date?->format('M j, Y \a\t g:i A') }}
                    </p>
                </div>

            @elseif($latestPayout->status === 'processing')
                <!-- Processing Payout -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full mb-4 shadow-lg">
                        <i class="fas fa-spinner fa-spin text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-yellow-800 mb-2">
                        ${{ number_format($latestPayout->net_amount, 2) }}
                    </div>
                    @if($latestPayout->workflow_type === 'standard')
                        <p class="text-yellow-700 font-medium">Project Payment Processing</p>
                    @elseif($latestPayout->workflow_type === 'contest')
                        <p class="text-yellow-700 font-medium">Contest Prize Processing</p>
                    @else
                        <p class="text-yellow-700 font-medium">Payment Processing</p>
                    @endif
                    <p class="text-sm text-yellow-600 mt-1">
                        Your payment is currently being processed
                    </p>
                </div>

            @elseif($latestPayout->status === 'failed')
                <!-- Failed Payout -->
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-400 to-rose-500 rounded-full mb-4 shadow-lg">
                        <i class="fas fa-times text-white text-2xl"></i>
                    </div>
                    <div class="text-3xl font-bold text-red-800 mb-2">
                        ${{ number_format($latestPayout->net_amount, 2) }}
                    </div>
                    @if($latestPayout->workflow_type === 'standard')
                        <p class="text-red-700 font-medium">Project Payment Failed</p>
                    @elseif($latestPayout->workflow_type === 'contest')
                        <p class="text-red-700 font-medium">Contest Prize Failed</p>
                    @else
                        <p class="text-red-700 font-medium">Payment Failed</p>
                    @endif
                    <p class="text-sm text-red-600 mt-1">
                        There was an issue processing your payout
                    </p>
                </div>
            @endif

            <!-- Payout Details -->
            <div class="bg-white/70 rounded-lg p-4 mb-4 border border-green-200/50">
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-green-600 font-medium">Gross Amount:</span>
                        <div class="text-green-800 font-semibold">${{ number_format($latestPayout->gross_amount, 2) }}</div>
                    </div>
                    <div>
                        <span class="text-green-600 font-medium">Commission:</span>
                        <div class="text-green-800 font-semibold">{{ $latestPayout->commission_rate }}%</div>
                    </div>
                    @if($latestPayout->workflow_type === 'standard' && $latestPayout->project)
                    <div class="col-span-2">
                        <span class="text-green-600 font-medium">Project:</span>
                        <div class="text-green-800 font-semibold">{{ $latestPayout->project->name }}</div>
                    </div>
                    @elseif($latestPayout->workflow_type === 'contest' && $latestPayout->contestPrize)
                    <div class="col-span-2">
                        <span class="text-green-600 font-medium">Prize:</span>
                        <div class="text-green-800 font-semibold">{{ $latestPayout->contestPrize->placement }} Place - ${{ number_format($latestPayout->contestPrize->amount, 2) }}</div>
                    </div>
                    @endif
                    @if($latestPayout->stripe_transfer_id)
                    <div class="col-span-2">
                        <span class="text-green-600 font-medium">Transfer ID:</span>
                        <div class="text-green-800 font-semibold text-xs">{{ $latestPayout->stripe_transfer_id }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Status-Specific Information -->
            @if($latestPayout->status === 'scheduled')
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-blue-800 mb-1">Payout Information</h5>
                            <p class="text-sm text-blue-700">
                                Your payout is scheduled and will be automatically transferred to your connected Stripe account on the release date. 
                                The hold period allows time for any potential disputes or refund requests.
                            </p>
                        </div>
                    </div>
                </div>
            @elseif($latestPayout->status === 'failed')
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start space-x-3">
                        <i class="fas fa-exclamation-triangle text-red-500 mt-0.5"></i>
                        <div class="flex-1">
                            <h5 class="text-sm font-medium text-red-800 mb-1">Payment Issue</h5>
                            <p class="text-sm text-red-700">
                                There was an issue processing your payment. Please contact support for assistance.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Multiple Payouts Summary -->
            @if($payouts->count() > 1)
                <div class="border-t border-green-200 pt-4">
                    <div class="text-sm text-green-700">
                        <div class="flex items-center justify-between">
                            <span>Total from this pitch:</span>
                            <span class="font-semibold">${{ number_format($payouts->sum('net_amount'), 2) }}</span>
                        </div>
                        <div class="flex items-center justify-between mt-1">
                            <span>Total payouts:</span>
                            <span class="font-semibold">{{ $payouts->count() }}</span>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-wrap gap-2 mt-4">
                         @if($isAwaitingPayment && (!$user->stripe_account_id || !$user->hasValidStripeConnectAccount()))
                 <a href="{{ route('profile.show') }}#stripe-connect" 
                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                     <i class="fas fa-link mr-2"></i>
                     Setup Payout Account
                 </a>
             @endif
            
            @if($payouts->isNotEmpty() && $latestPayout->status === 'completed')
                <a href="{{ route('projects.pitches.payment.receipt', ['project' => $pitch->project, 'pitch' => $pitch]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                    <i class="fas fa-receipt mr-2"></i>
                    View Receipt
                </a>
            @endif
            
                         <a href="{{ route('dashboard') }}#payouts" 
                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-slate-600 hover:from-gray-700 hover:to-slate-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                 <i class="fas fa-history mr-2"></i>
                 View Dashboard
             </a>
        </div>
    </div>
</div>
@endif 