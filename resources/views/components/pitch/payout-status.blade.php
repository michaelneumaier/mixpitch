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
    
    if ($payouts->isEmpty()) return;
    
    $latestPayout = $payouts->first();
    $totalEarnings = $payouts->where('status', 'completed')->sum('net_amount');
    $pendingAmount = $payouts->whereIn('status', ['scheduled', 'processing'])->sum('net_amount');
    
    // Determine the primary status to display
    $hasCompleted = $payouts->where('status', 'completed')->count() > 0;
    $hasPending = $payouts->whereIn('status', ['scheduled', 'processing'])->count() > 0;
    $hasFailed = $payouts->where('status', 'failed')->count() > 0;
@endphp

@if($payouts->isNotEmpty())
<div class="bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-green-100/80 to-emerald-100/80 border-b border-green-200/50">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg">
                    <i class="fas fa-money-bill-wave text-white"></i>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-green-800">Payout Status</h3>
                    @if($latestPayout->workflow_type === 'standard')
                        <p class="text-sm text-green-600">Your earnings from this project</p>
                    @elseif($latestPayout->workflow_type === 'contest')
                        <p class="text-sm text-green-600">Your contest prize earnings</p>
                    @else
                        <p class="text-sm text-green-600">Your earnings from this pitch</p>
                    @endif
                </div>
            </div>
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
        </div>
    </div>

    <!-- Content -->
    <div class="p-6">
        <!-- Primary Status Display -->
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
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full mb-4 shadow-lg animate-pulse">
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
                    Releases {{ $latestPayout->hold_release_date?->format('M j, Y') }}
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
                <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-red-400 to-red-500 rounded-full mb-4 shadow-lg">
                    <i class="fas fa-exclamation-triangle text-white text-2xl"></i>
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
                @if($latestPayout->failure_reason)
                    <p class="text-sm text-red-600 mt-1">
                        {{ $latestPayout->failure_reason }}
                    </p>
                @endif
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
                        <h5 class="text-sm font-medium text-blue-800 mb-1">What's Next?</h5>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li>• Your payment is scheduled and will be automatically released</li>
                            <li>• Funds typically arrive 1-2 business days after release</li>
                            <li>• You'll receive notifications when the transfer completes</li>
                        </ul>
                    </div>
                </div>
            </div>
        @elseif($latestPayout->status === 'completed')
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex items-start space-x-3">
                    <i class="fas fa-check-circle text-green-500 mt-0.5"></i>
                    <div class="flex-1">
                        <h5 class="text-sm font-medium text-green-800 mb-1">Payment Complete!</h5>
                        <p class="text-sm text-green-700">
                            Your payment has been successfully transferred to your Stripe account. 
                            Funds should appear in your bank account within 1-2 business days.
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

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3 mt-6">
            <a href="{{ route('payouts.index') }}" 
               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-chart-line mr-2"></i>
                View Payout Dashboard
            </a>
            @if($latestPayout->status === 'completed' && $latestPayout->stripe_transfer_id)
                <a href="{{ route('stripe.connect.dashboard') }}" 
                   class="inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-gray-50 text-green-700 font-medium rounded-lg border border-green-200 hover:border-green-300 transition-all duration-200">
                    <i class="fas fa-external-link-alt mr-2"></i>
                    Stripe Dashboard
                </a>
            @endif
        </div>
    </div>
</div>
@endif 