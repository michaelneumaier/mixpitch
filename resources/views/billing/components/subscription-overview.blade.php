<!-- Subscription Overview Section -->
<div class="mb-8">
    <div class="bg-gradient-to-br from-white/90 to-blue-50/90 backdrop-blur-sm border border-white/50 rounded-2xl shadow-xl p-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Plan Information -->
            <div class="mb-6 lg:mb-0">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4">
                        <i class="fas fa-crown text-white text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">
                            Current Subscription
                        </h2>
                        <p class="text-gray-600">Manage your plan and billing settings</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <!-- Plan Badge -->
                    <div class="flex items-center space-x-3">
                        <div class="text-2xl">
                            @if($user->subscription_tier === 'artist')
                                🔷
                            @elseif($user->subscription_tier === 'engineer')
                                🔶
                            @else
                                🆓
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Plan</div>
                            <div class="text-lg font-bold text-gray-900">{{ $billingSummary['plan_name'] }}</div>
                        </div>
                    </div>
                    
                    <!-- Billing Period -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-100 to-emerald-100 rounded-lg">
                            <i class="fas fa-calendar-alt text-green-600"></i>
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-500">Billing</div>
                            <div class="text-lg font-bold text-gray-900">{{ $billingSummary['billing_period'] }}</div>
                            @if($billingSummary['yearly_savings'])
                                <div class="text-xs text-green-600 font-medium">
                                    Saving ${{ number_format($billingSummary['yearly_savings'], 2) }}/year
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Next Billing / Status -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-lg">
                            @if($onGracePeriod)
                                <i class="fas fa-hourglass-half text-yellow-600"></i>
                            @elseif($isSubscribed)
                                <i class="fas fa-check-circle text-green-600"></i>
                            @else
                                <i class="fas fa-exclamation-circle text-gray-600"></i>
                            @endif
                        </div>
                        <div>
                            @if($onGracePeriod)
                                <div class="text-sm font-medium text-yellow-600">Cancelling</div>
                                <div class="text-lg font-bold text-yellow-700">
                                    {{ $subscription->ends_at->format('M d, Y') }}
                                </div>
                            @elseif($isSubscribed && $billingSummary['next_billing_date'])
                                <div class="text-sm font-medium text-gray-500">Next Billing</div>
                                <div class="text-lg font-bold text-gray-900">
                                    {{ $billingSummary['next_billing_date']->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $billingSummary['next_billing_date']->diffForHumans() }}
                                </div>
                            @else
                                <div class="text-sm font-medium text-gray-500">Status</div>
                                <div class="text-lg font-bold text-gray-900">Free Plan</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex flex-col sm:flex-row lg:flex-col gap-3">
                @if($user->isFreePlan())
                    <a href="{{ route('pricing') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 font-medium">
                        <i class="fas fa-rocket mr-2"></i>
                        Upgrade to Pro
                    </a>
                    <a href="{{ route('subscription.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 font-medium">
                        <i class="fas fa-cog mr-2"></i>
                        View Plans
                    </a>
                @elseif($onGracePeriod)
                    <form action="{{ route('subscription.resume') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 font-medium">
                            <i class="fas fa-play mr-2"></i>
                            Resume Subscription
                        </button>
                    </form>
                    <a href="{{ route('subscription.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 font-medium">
                        <i class="fas fa-cog mr-2"></i>
                        Manage Plan
                    </a>
                @else
                    <a href="{{ route('subscription.index') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 font-medium">
                        <i class="fas fa-cog mr-2"></i>
                        Manage Subscription
                    </a>
                    <a href="{{ route('billing.portal') }}" class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-100 to-gray-200 hover:from-gray-200 hover:to-gray-300 text-gray-700 rounded-xl border border-gray-300/50 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 font-medium">
                        <i class="fas fa-external-link-alt mr-2"></i>
                        Billing Portal
                    </a>
                @endif
            </div>
        </div>
        
        <!-- Subscription Status Alert -->
        @if($onGracePeriod)
            <div class="mt-6 bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-yellow-100 rounded-lg mr-3">
                        <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-yellow-800">Subscription Ending</h4>
                        <p class="text-sm text-yellow-700 mt-1">
                            Your subscription will end on {{ $subscription->ends_at->format('F j, Y') }}. 
                            You'll continue to have access to Pro features until then.
                        </p>
                    </div>
                </div>
            </div>
        @elseif($isSubscribed && $billingSummary['next_billing_date'] && $billingSummary['next_billing_date']->diffInDays() <= 7)
            <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-lg mr-3">
                        <i class="fas fa-info-circle text-blue-600"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-blue-800">Upcoming Billing</h4>
                        <p class="text-sm text-blue-700 mt-1">
                            Your next payment of {{ $billingSummary['formatted_price'] }} will be processed on {{ $billingSummary['next_billing_date']->format('F j, Y') }}.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div> 