<!-- Subscription Overview Section -->
<flux:card class="mb-2">
    <div class="">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Plan Information -->
            <div class="mb-6 lg:mb-0">
                <div class="flex items-center mb-4">
                    <div class="flex items-center justify-center w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl mr-4">
                        <flux:icon name="star" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <flux:heading size="lg" class="mb-1">Current Subscription</flux:heading>
                        <flux:subheading>Manage your plan and billing settings</flux:subheading>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Plan Badge -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-lg">
                            @if($user->subscription_tier === 'artist')
                                <flux:icon name="musical-note" class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                            @elseif($user->subscription_tier === 'engineer')
                                <flux:icon name="cog-6-tooth" class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                            @else
                                <flux:icon name="user" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            @endif
                        </div>
                        <div>
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Plan</flux:text>
                            <flux:text class="font-semibold text-gray-900 dark:text-white">{{ $billingSummary['plan_name'] }}</flux:text>
                        </div>
                    </div>
                    
                    <!-- Billing Period -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg">
                            <flux:icon name="calendar" class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Billing</flux:text>
                            <flux:text class="font-semibold text-gray-900 dark:text-white">{{ $billingSummary['billing_period'] }}</flux:text>
                            @if($billingSummary['yearly_savings'])
                                <flux:badge color="green" size="sm" class="mt-1">
                                    Saving ${{ number_format($billingSummary['yearly_savings'], 2) }}/year
                                </flux:badge>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Next Billing / Status -->
                    <div class="flex items-center space-x-3">
                        <div class="flex items-center justify-center w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                            @if($onGracePeriod)
                                <flux:icon name="clock" class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                            @elseif($isSubscribed)
                                <flux:icon name="check-circle" class="w-6 h-6 text-green-600 dark:text-green-400" />
                            @else
                                <flux:icon name="exclamation-circle" class="w-6 h-6 text-gray-600 dark:text-gray-400" />
                            @endif
                        </div>
                        <div>
                            @if($onGracePeriod)
                                <flux:text size="sm" class="text-yellow-600 dark:text-yellow-400">Cancelling</flux:text>
                                <flux:text class="font-semibold text-yellow-700 dark:text-yellow-300">
                                    {{ $subscription->ends_at->format('M d, Y') }}
                                </flux:text>
                            @elseif($isSubscribed && $billingSummary['next_billing_date'])
                                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Next Billing</flux:text>
                                <flux:text class="font-semibold text-gray-900 dark:text-white">
                                    {{ $billingSummary['next_billing_date']->format('M d, Y') }}
                                </flux:text>
                                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">
                                    {{ $billingSummary['next_billing_date']->diffForHumans() }}
                                </flux:text>
                            @else
                                <flux:text size="sm" class="text-gray-500 dark:text-gray-400">Status</flux:text>
                                <flux:text class="font-semibold text-gray-900 dark:text-white">Free Plan</flux:text>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="flex flex-col sm:flex-row lg:flex-col gap-3">
                @if($user->isFreePlan())
                    <flux:button href="{{ route('pricing') }}" wire:navigate variant="primary" icon="rocket-launch">
                        Upgrade to Pro
                    </flux:button>
                    <flux:button href="{{ route('subscription.index') }}" wire:navigate variant="outline" icon="cog-6-tooth">
                        View Plans
                    </flux:button>
                @elseif($onGracePeriod)
                    <form action="{{ route('subscription.resume') }}" method="POST" class="inline">
                        @csrf
                        <flux:button type="submit" variant="primary" icon="play" class="w-full">
                            Resume Subscription
                        </flux:button>
                    </form>
                    <flux:button href="{{ route('subscription.index') }}" wire:navigate variant="outline" icon="cog-6-tooth">
                        Manage Plan
                    </flux:button>
                @else
                    <flux:button href="{{ route('subscription.index') }}" wire:navigate variant="primary" icon="cog-6-tooth">
                        Manage Subscription
                    </flux:button>
                    <flux:button href="{{ route('billing.portal') }}" wire:navigate variant="outline" icon="arrow-top-right-on-square">
                        Billing Portal
                    </flux:button>
                @endif
            </div>
        </div>
        
        <!-- Subscription Status Alert -->
        @if($onGracePeriod)
            <div class="mt-6">
                <flux:callout variant="warning">
                    <flux:heading size="sm">Subscription Ending</flux:heading>
                    <flux:text class="mt-1">
                        Your subscription will end on {{ $subscription->ends_at->format('F j, Y') }}. 
                        You'll continue to have access to Pro features until then.
                    </flux:text>
                </flux:callout>
            </div>
        @elseif($isSubscribed && $billingSummary['next_billing_date'] && $billingSummary['next_billing_date']->diffInDays() <= 7)
            <div class="mt-6">
                <flux:callout variant="info">
                    <flux:heading size="sm">Upcoming Billing</flux:heading>
                    <flux:text class="mt-1">
                        Your next payment of {{ $billingSummary['formatted_price'] }} will be processed on {{ $billingSummary['next_billing_date']->format('F j, Y') }}.
                    </flux:text>
                </flux:callout>
            </div>
        @endif
    </div>
</flux:card> 