@props(['user' => null])

@php
    $user = $user ?? auth()->user();
    if (!$user) return;
    
    // Get recent payout activity
    $recentPayouts = \App\Models\PayoutSchedule::where('producer_user_id', $user->id)
        ->whereIn('status', ['scheduled', 'processing', 'completed'])
        ->where('created_at', '>=', now()->subDays(30))
        ->with(['project', 'contestPrize'])
        ->orderBy('created_at', 'desc')
        ->limit(3)
        ->get();
    
    $pendingPayouts = $recentPayouts->whereIn('status', ['scheduled', 'processing']);
    $recentCompletedPayouts = $recentPayouts->where('status', 'completed')->where('completed_at', '>=', now()->subDays(7));
    
    $totalPendingAmount = $pendingPayouts->sum('net_amount');
    $totalRecentAmount = $recentCompletedPayouts->sum('net_amount');
@endphp

@if($pendingPayouts->count() > 0 || $recentCompletedPayouts->count() > 0)
<div class="mb-6">
    <!-- Pending Payouts Alert -->
    @if($pendingPayouts->count() > 0)
    <div class="relative overflow-hidden bg-gradient-to-r from-blue-50 via-indigo-50 to-purple-50 border border-blue-200 rounded-2xl p-6 mb-4 shadow-lg">
        <!-- Background Animation -->
        <div class="absolute inset-0 bg-gradient-to-r from-blue-400/5 to-purple-400/5 animate-pulse"></div>
        <div class="absolute top-4 right-4 w-20 h-20 bg-blue-400/10 rounded-full blur-xl"></div>
        
        <div class="relative flex items-start space-x-4">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl shadow-lg">
                    <i class="fas fa-clock text-white text-lg"></i>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold text-blue-900">
                        <i class="fas fa-hourglass-half mr-2 text-blue-600"></i>
                        Payouts in Progress
                    </h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        {{ $pendingPayouts->count() }} {{ Str::plural('payout', $pendingPayouts->count()) }}
                    </span>
                </div>
                <p class="text-blue-800 mb-4">
                    You have <strong>${{ number_format($totalPendingAmount, 2) }}</strong> in payouts currently being processed.
                    Funds typically arrive within 3-5 business days.
                </p>
                
                <!-- Pending Payout Items -->
                <div class="space-y-2 mb-4">
                    @foreach($pendingPayouts->take(2) as $payout)
                    <div class="flex items-center justify-between p-3 bg-white/60 rounded-lg border border-blue-200/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                            <div>
                                <p class="text-sm font-medium text-blue-900">
                                    @if($payout->workflow_type === 'contest' && $payout->contestPrize)
                                        Contest Prize: {{ $payout->contestPrize->placement }} Place
                                    @else
                                        {{ $payout->project->name ?? 'Project Payout' }}
                                    @endif
                                </p>
                                <p class="text-xs text-blue-700">
                                    {{ ucfirst($payout->status) }} • Expected {{ $payout->hold_release_date->format('M j') }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-blue-900">${{ number_format($payout->net_amount, 2) }}</div>
                        </div>
                    </div>
                    @endforeach
                    
                    @if($pendingPayouts->count() > 2)
                    <div class="text-center">
                        <p class="text-xs text-blue-700">+ {{ $pendingPayouts->count() - 2 }} more pending</p>
                    </div>
                    @endif
                </div>
                
                <div class="flex items-center space-x-3">
                    <a href="{{ route('payouts.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-chart-line mr-2"></i>
                        View Payout Dashboard
                    </a>
                    <a href="{{ route('stripe.connect.setup') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-blue-700 font-medium rounded-lg border border-blue-200 hover:border-blue-300 transition-all duration-200">
                        <i class="fas fa-cog mr-2"></i>
                        Account Settings
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Recent Completed Payouts Celebration -->
    @if($recentCompletedPayouts->count() > 0)
    <div class="relative overflow-hidden bg-gradient-to-r from-green-50 via-emerald-50 to-teal-50 border border-green-200 rounded-2xl p-6 shadow-lg">
        <!-- Background Animation -->
        <div class="absolute inset-0 bg-gradient-to-r from-green-400/5 to-emerald-400/5"></div>
        <div class="absolute top-4 right-4 w-20 h-20 bg-green-400/10 rounded-full blur-xl"></div>
        
        <div class="relative flex items-start space-x-4">
            <div class="flex-shrink-0">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl shadow-lg">
                    <i class="fas fa-check-circle text-white text-lg"></i>
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-lg font-bold text-green-900">
                        <i class="fas fa-party-horn mr-2 text-green-600"></i>
                        Recent Payouts Completed!
                    </h3>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        Last 7 days
                    </span>
                </div>
                <p class="text-green-800 mb-4">
                    🎉 Congratulations! You've received <strong>${{ number_format($totalRecentAmount, 2) }}</strong> 
                    from {{ $recentCompletedPayouts->count() }} completed {{ Str::plural('payout', $recentCompletedPayouts->count()) }}.
                </p>
                
                <!-- Recent Completed Items -->
                <div class="space-y-2 mb-4">
                    @foreach($recentCompletedPayouts->take(2) as $payout)
                    <div class="flex items-center justify-between p-3 bg-white/60 rounded-lg border border-green-200/50">
                        <div class="flex items-center space-x-3">
                            <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                            <div>
                                <p class="text-sm font-medium text-green-900">
                                    @if($payout->workflow_type === 'contest' && $payout->contestPrize)
                                        Contest Prize: {{ $payout->contestPrize->placement }} Place
                                    @else
                                        {{ $payout->project->name ?? 'Project Payout' }}
                                    @endif
                                </p>
                                <p class="text-xs text-green-700">
                                    Completed {{ $payout->completed_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-bold text-green-900">${{ number_format($payout->net_amount, 2) }}</div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="flex items-center space-x-3">
                    <a href="{{ route('payouts.index') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-receipt mr-2"></i>
                        View All Payouts
                    </a>
                    @if($recentCompletedPayouts->where('stripe_transfer_id')->count() > 0)
                    <a href="{{ route('stripe.connect.dashboard') }}" 
                       class="inline-flex items-center px-4 py-2 bg-white hover:bg-gray-50 text-green-700 font-medium rounded-lg border border-green-200 hover:border-green-300 transition-all duration-200">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Stripe Dashboard
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endif 