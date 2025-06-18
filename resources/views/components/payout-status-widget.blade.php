@props(['user' => null, 'compact' => false])

@php
    $user = $user ?? auth()->user();
    if (!$user) return;
    
    // Get payout statistics
    $payoutStats = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
        ->selectRaw('
            status,
            COUNT(*) as count,
            SUM(net_amount) as total_amount,
            MAX(created_at) as latest_date
        ')
        ->groupBy('status')
        ->get()
        ->keyBy('status');
    
    $pendingCount = $payoutStats->get('scheduled')->count ?? 0;
    $processingCount = $payoutStats->get('processing')->count ?? 0;
    $completedCount = $payoutStats->get('completed')->count ?? 0;
    
    $pendingAmount = $payoutStats->get('scheduled')->total_amount ?? 0;
    $processingAmount = $payoutStats->get('processing')->total_amount ?? 0;
    $totalEarnings = $payoutStats->get('completed')->total_amount ?? 0;
    
    $hasActivity = ($pendingCount + $processingCount + $completedCount) > 0;
@endphp

@if($hasActivity)
<div class="bg-white rounded-xl shadow-lg overflow-hidden border border-gray-200">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-600 to-indigo-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i class="fas fa-wallet text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-lg">Payout Status</h3>
                    <p class="text-purple-100 text-sm">Your earnings overview</p>
                </div>
            </div>
            @if(($pendingCount + $processingCount) > 0)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 animate-pulse">
                    <i class="fas fa-clock mr-1"></i>
                    In Progress
                </span>
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="p-6 space-y-4">
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Total Earnings -->
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-600 text-sm font-medium">Total Earned</p>
                        <p class="text-green-900 text-xl font-bold">${{ number_format($totalEarnings, 2) }}</p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-lg"></i>
                </div>
            </div>

            <!-- Pending Amount -->
            @if($pendingAmount > 0)
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-600 text-sm font-medium">Pending</p>
                        <p class="text-blue-900 text-xl font-bold">${{ number_format($pendingAmount, 2) }}</p>
                    </div>
                    <i class="fas fa-hourglass-half text-blue-500 text-lg"></i>
                </div>
            </div>
            @endif

            <!-- Processing Amount -->
            @if($processingAmount > 0)
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-600 text-sm font-medium">Processing</p>
                        <p class="text-yellow-900 text-xl font-bold">${{ number_format($processingAmount, 2) }}</p>
                    </div>
                    <i class="fas fa-spinner fa-spin text-yellow-500 text-lg"></i>
                </div>
            </div>
            @endif
        </div>

        <!-- Status Summary -->
        @if(($pendingCount + $processingCount) > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <div class="flex-1">
                    <h5 class="text-sm font-medium text-blue-800 mb-1">Payout Status</h5>
                    <p class="text-sm text-blue-700">
                        @if($pendingCount > 0)
                            {{ $pendingCount }} {{ Str::plural('payout', $pendingCount) }} scheduled
                        @endif
                        @if($pendingCount > 0 && $processingCount > 0) and @endif
                        @if($processingCount > 0)
                            {{ $processingCount }} currently processing
                        @endif
                        . Funds typically arrive within 3-5 business days.
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row gap-3">
            <a href="{{ route('payouts.index') }}" 
               class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                <i class="fas fa-chart-line mr-2"></i>
                View Full Dashboard
            </a>
            <a href="{{ route('stripe.connect.setup') }}" 
               class="inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 font-medium rounded-lg border border-gray-300 hover:border-gray-400 transition-all duration-200">
                <i class="fas fa-cog mr-2"></i>
                Settings
            </a>
        </div>
    </div>
</div>
@endif 