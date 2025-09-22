<div class="space-y-6">
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <flux:callout variant="success" class="mb-6">
            <flux:callout.text>{{ session('success') }}</flux:callout.text>
        </flux:callout>
    @endif

    @error('boost')
        <flux:callout variant="danger" class="mb-6">
            <flux:callout.text>{{ $message }}</flux:callout.text>
        </flux:callout>
    @enderror

    <!-- Main Subscription Widget -->
    <x-subscription-dashboard :user="$user" />

    <!-- Toggle Details Button -->
    <div class="text-center">
        <flux:button 
            wire:click="toggleDetails" 
            variant="outline" 
            size="sm"
            :icon="$showFullDetails ? 'chevron-up' : 'chevron-down'">
            {{ $showFullDetails ? 'Hide' : 'Show' }} Detailed Analytics
        </flux:button>
    </div>

    @if($showFullDetails)
        <!-- Detailed Analytics Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:transition>
            <!-- Reputation Analytics -->
            <flux:card>
                <div class="flex items-center justify-between mb-4">
                    <flux:heading size="base" class="flex items-center">
                        <flux:icon name="star" class="w-5 h-5 text-yellow-500 mr-2" />
                        Reputation Analytics
                    </flux:heading>
                    <flux:button 
                        wire:click="refreshReputationCache" 
                        variant="ghost" 
                        size="xs"
                        icon="arrow-path">
                        Refresh
                    </flux:button>
                </div>

                <x-reputation-display :user="$user" detailed="true" showBreakdown="true" />

                @if($subscriptionData['reputation']['subscription_benefit'])
                    <flux:callout variant="info" class="mt-4">
                        <flux:callout.text class="flex items-center">
                            <flux:icon name="rocket-launch" class="w-4 h-4 mr-2" />
                            Pro Benefit: +{{ number_format($subscriptionData['reputation']['multiplier_bonus'], 1) }} bonus reputation
                        </flux:callout.text>
                    </flux:callout>
                @endif
            </flux:card>

            <!-- Earnings Breakdown -->
            <flux:card>
                <flux:heading size="base" class="mb-4 flex items-center">
                    <flux:icon name="chart-bar" class="w-5 h-5 text-green-500 mr-2" />
                    Earnings Breakdown
                </flux:heading>

                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 dark:bg-green-950/50 rounded-lg">
                        <flux:text class="text-green-700 dark:text-green-300 font-medium">Total Earnings</flux:text>
                        <flux:text class="text-green-900 dark:text-green-100 font-bold text-lg">
                            ${{ number_format($subscriptionData['earnings']['total'], 2) }}
                        </flux:text>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-red-50 dark:bg-red-950/50 rounded-lg">
                        <flux:text class="text-red-700 dark:text-red-300 font-medium">Commission Paid</flux:text>
                        <flux:text class="text-red-900 dark:text-red-100 font-semibold">
                            ${{ number_format($subscriptionData['earnings']['commission_paid'], 2) }}
                        </flux:text>
                    </div>

                    @if($subscriptionData['earnings']['commission_savings'] > 0)
                        <div class="flex justify-between items-center p-3 bg-purple-50 dark:bg-purple-950/50 rounded-lg">
                            <flux:text class="text-purple-700 dark:text-purple-300 font-medium">Commission Savings</flux:text>
                            <flux:text class="text-purple-900 dark:text-purple-100 font-semibold">
                                ${{ number_format($subscriptionData['earnings']['commission_savings'], 2) }}
                            </flux:text>
                        </div>
                    @endif

                    <div class="border-t pt-3">
                        <div class="flex justify-between items-center">
                            <flux:text class="text-gray-600 dark:text-gray-400">Current Rate</flux:text>
                            <flux:text class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $user->getPlatformCommissionRate() }}%
                            </flux:text>
                        </div>
                    </div>
                </div>
            </flux:card>

            <!-- Monthly Activity -->
            <flux:card>
                <flux:heading size="base" class="mb-4 flex items-center">
                    <flux:icon name="calendar" class="w-5 h-5 text-blue-500 mr-2" />
                    Monthly Activity
                </flux:heading>

                <div class="space-y-4">
                    <!-- Visibility Boosts -->
                    <div class="flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-950/50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <flux:icon name="rocket-launch" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                            <flux:text class="font-medium text-gray-900 dark:text-gray-100">Visibility Boosts</flux:text>
                        </div>
                        <div class="text-right">
                            <flux:text class="font-semibold text-gray-900 dark:text-gray-100">
                                {{ $subscriptionData['usage_summary']['visibility_boosts']['used'] }} used
                            </flux:text>
                            <br>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                {{ $subscriptionData['remaining']['visibility_boosts'] }} remaining
                            </flux:text>
                        </div>
                    </div>

                    <!-- Quick Boost Actions -->
                    @if($subscriptionData['remaining']['visibility_boosts'] > 0)
                        <div class="grid grid-cols-2 gap-2">
                            <flux:button 
                                wire:click="createVisibilityBoost('profile', 72)" 
                                variant="primary"
                                size="sm"
                                icon="user">
                                Boost Profile
                            </flux:button>
                            <flux:button 
                                wire:click="createVisibilityBoost('project', 72)" 
                                variant="filled"
                                size="sm"
                                icon="folder"
                                class="bg-purple-600 hover:bg-purple-700 !text-white">
                                Boost Project
                            </flux:button>
                        </div>
                    @endif

                    <!-- Private Projects -->
                    @if($user->getMaxPrivateProjectsMonthly() !== 0)
                        <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-950/50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <flux:icon name="lock-closed" class="w-5 h-5 text-red-600 dark:text-red-400" />
                                <flux:text class="font-medium text-gray-900 dark:text-gray-100">Private Projects</flux:text>
                            </div>
                            <div class="text-right">
                                <flux:text class="font-semibold text-gray-900 dark:text-gray-100">
                                    {{ $subscriptionData['usage_summary']['private_projects']['created'] }} created
                                </flux:text>
                                <br>
                                <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                    @if($subscriptionData['remaining']['private_projects'] === null)
                                        Unlimited
                                    @else
                                        {{ $subscriptionData['remaining']['private_projects'] }} remaining
                                    @endif
                                </flux:text>
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>

            <!-- Recent Activity -->
            <flux:card>
                <flux:heading size="base" class="mb-4 flex items-center">
                    <flux:icon name="clock" class="w-5 h-5 text-gray-500 mr-2" />
                    Recent Activity
                </flux:heading>

                <div class="space-y-4">
                    <!-- Recent Transactions -->
                    @if($recentActivity['transactions']->isNotEmpty())
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Transactions</flux:text>
                            <div class="space-y-2">
                                @foreach($recentActivity['transactions']->take(3) as $transaction)
                                    <div class="flex items-center justify-between">
                                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                            {{ $transaction->type }} • {{ $transaction->created_at->format('M j') }}
                                        </flux:text>
                                        <flux:text size="sm" class="font-medium text-gray-900 dark:text-gray-100">
                                            ${{ number_format($transaction->net_amount, 2) }}
                                        </flux:text>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Recent Boosts -->
                    @if($recentActivity['visibility_boosts']->isNotEmpty())
                        <div>
                            <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Boosts</flux:text>
                            <div class="space-y-2">
                                @foreach($recentActivity['visibility_boosts']->take(3) as $boost)
                                    <div class="flex items-center justify-between">
                                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                            {{ ucfirst($boost->boost_type) }} boost • {{ $boost->created_at->format('M j') }}
                                        </flux:text>
                                        <flux:badge :color="$boost->status === 'active' ? 'green' : 'gray'" size="sm">
                                            {{ ucfirst($boost->status) }}
                                        </flux:badge>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </flux:card>
        </div>

        <!-- Subscription Insights -->
        <flux:card class="bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-950/50 dark:to-purple-950/50 border-indigo-200 dark:border-indigo-800">
            <flux:heading size="base" class="text-indigo-900 dark:text-indigo-100 mb-4 flex items-center">
                <flux:icon name="light-bulb" class="w-5 h-5 text-indigo-600 dark:text-indigo-400 mr-2" />
                Subscription Insights
            </flux:heading>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @if($user->getReputationMultiplier() > 1.0)
                    <div class="bg-white/60 dark:bg-white/10 rounded-lg p-3">
                        <div class="flex items-center text-indigo-700 dark:text-indigo-300 font-medium mb-1">
                            <flux:icon name="rocket-launch" class="w-4 h-4 mr-2" />
                            <flux:text size="sm" class="font-medium">Reputation Boost</flux:text>
                        </div>
                        <flux:text size="sm" class="text-indigo-600 dark:text-indigo-400">
                            Your {{ $subscriptionData['plan_name'] }} plan gives you a {{ $user->getReputationMultiplier() }}× reputation multiplier.
                        </flux:text>
                    </div>
                @endif

                @if($subscriptionData['earnings']['commission_savings'] > 0)
                    <div class="bg-white/60 dark:bg-white/10 rounded-lg p-3">
                        <div class="flex items-center text-indigo-700 dark:text-indigo-300 font-medium mb-1">
                            <flux:icon name="banknotes" class="w-4 h-4 mr-2" />
                            <flux:text size="sm" class="font-medium">Commission Savings</flux:text>
                        </div>
                        <flux:text size="sm" class="text-indigo-600 dark:text-indigo-400">
                            You've saved ${{ number_format($subscriptionData['earnings']['commission_savings'], 2) }} in reduced commission fees.
                        </flux:text>
                    </div>
                @endif

                @if($user->hasClientPortalAccess())
                    <div class="bg-white/60 dark:bg-white/10 rounded-lg p-3">
                        <div class="flex items-center text-indigo-700 dark:text-indigo-300 font-medium mb-1">
                            <flux:icon name="users" class="w-4 h-4 mr-2" />
                            <flux:text size="sm" class="font-medium">Client Portal</flux:text>
                        </div>
                        <flux:text size="sm" class="text-indigo-600 dark:text-indigo-400">
                            Access advanced client management tools and analytics.
                        </flux:text>
                    </div>
                @endif
            </div>
        </flux:card>
    @endif
</div>
