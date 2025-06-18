@props(['project', 'user' => null])

@php
    $user = $user ?? auth()->user();
    if (!$user) return;
    
    // Get user's payouts for this project
    $payouts = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
        ->where('project_id', $project->id)
        ->with(['pitch', 'contestPrize'])
        ->orderBy('created_at', 'desc')
        ->get();
    
    if ($payouts->isEmpty()) return;
    
    $totalEarnings = $payouts->where('status', 'completed')->sum('net_amount');
    $pendingAmount = $payouts->whereIn('status', ['scheduled', 'processing'])->sum('net_amount');
    $latestPayout = $payouts->first();
@endphp

@if($payouts->isNotEmpty())
<div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl p-6 shadow-sm">
    <div class="flex items-start justify-between mb-4">
        <div class="flex items-center space-x-3">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg">
                <i class="fas fa-money-bill-wave text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold text-green-800">Your Earnings</h3>
                <p class="text-sm text-green-600">From this project</p>
            </div>
        </div>
        <a href="{{ route('payouts.index') }}" 
           class="inline-flex items-center px-3 py-1 text-xs font-medium text-green-700 bg-green-100 hover:bg-green-200 rounded-lg transition-colors duration-200">
            <i class="fas fa-external-link-alt mr-1"></i>
            View All
        </a>
    </div>

    <!-- Earnings Summary -->
    <div class="grid grid-cols-2 gap-4 mb-4">
        @if($totalEarnings > 0)
        <div class="bg-white/70 rounded-lg p-3 border border-green-200/50">
            <div class="text-xs text-green-600 font-medium mb-1">Total Earned</div>
            <div class="text-lg font-bold text-green-800">${{ number_format($totalEarnings, 2) }}</div>
        </div>
        @endif
        
        @if($pendingAmount > 0)
        <div class="bg-white/70 rounded-lg p-3 border border-yellow-200/50">
            <div class="text-xs text-yellow-600 font-medium mb-1">Pending</div>
            <div class="text-lg font-bold text-yellow-800">${{ number_format($pendingAmount, 2) }}</div>
        </div>
        @endif
    </div>

    <!-- Latest Payout Status -->
    <div class="bg-white/70 rounded-lg p-4 border border-green-200/50">
        <div class="flex items-center justify-between mb-2">
            <h4 class="text-sm font-medium text-green-800">Latest Payout</h4>
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                @if($latestPayout->status === 'completed') bg-green-100 text-green-800
                @elseif($latestPayout->status === 'scheduled') bg-blue-100 text-blue-800
                @elseif($latestPayout->status === 'processing') bg-yellow-100 text-yellow-800
                @elseif($latestPayout->status === 'failed') bg-red-100 text-red-800
                @else bg-gray-100 text-gray-800
                @endif">
                @if($latestPayout->status === 'completed')
                    <i class="fas fa-check-circle mr-1"></i>
                @elseif($latestPayout->status === 'scheduled')
                    <i class="fas fa-clock mr-1"></i>
                @elseif($latestPayout->status === 'processing')
                    <i class="fas fa-spinner fa-spin mr-1"></i>
                @elseif($latestPayout->status === 'failed')
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                @endif
                {{ ucfirst($latestPayout->status) }}
            </span>
        </div>
        
        <div class="flex items-center justify-between text-sm">
            <div class="text-green-700">
                <span class="font-medium">${{ number_format($latestPayout->net_amount, 2) }}</span>
                @if($latestPayout->workflow_type === 'contest' && $latestPayout->contestPrize)
                    • {{ $latestPayout->contestPrize->placement }} Place Prize
                @endif
            </div>
            <div class="text-green-600">
                {{ $latestPayout->created_at->format('M j, Y') }}
            </div>
        </div>
        
        @if($latestPayout->status === 'scheduled')
        <div class="mt-2 text-xs text-blue-600 bg-blue-50 rounded p-2">
            <i class="fas fa-info-circle mr-1"></i>
            Funds will be released on {{ $latestPayout->hold_release_date->format('M j, Y') }}
        </div>
        @endif
        
        @if($latestPayout->status === 'failed' && $latestPayout->failure_reason)
        <div class="mt-2 text-xs text-red-600 bg-red-50 rounded p-2">
            <i class="fas fa-exclamation-triangle mr-1"></i>
            {{ $latestPayout->failure_reason }}
        </div>
        @endif
    </div>

    @if($payouts->count() > 1)
    <div class="mt-4 pt-3 border-t border-green-200">
        <div class="text-xs text-green-600">
            <i class="fas fa-history mr-1"></i>
            {{ $payouts->count() }} total {{ Str::plural('payout', $payouts->count()) }} for this project
        </div>
    </div>
    @endif
</div>
@endif 