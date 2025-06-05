<div class="space-y-6">
    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-2"></i>
                <span class="text-green-800">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @error('boost')
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                <span class="text-red-800">{{ $message }}</span>
            </div>
        </div>
    @enderror

    <!-- Main Subscription Widget -->
    <x-subscription-dashboard :user="$user" />

    <!-- Toggle Details Button -->
    <div class="text-center">
        <button wire:click="toggleDetails" 
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
            <i class="fas fa-{{ $showFullDetails ? 'chevron-up' : 'chevron-down' }} mr-2"></i>
            {{ $showFullDetails ? 'Hide' : 'Show' }} Detailed Analytics
        </button>
    </div>

    @if($showFullDetails)
        <!-- Detailed Analytics Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" wire:transition>
            <!-- Reputation Analytics -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-gray-900 flex items-center">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>
                        Reputation Analytics
                    </h4>
                    <button wire:click="refreshReputationCache" 
                            class="text-sm text-blue-600 hover:text-blue-800 transition-colors duration-200">
                        <i class="fas fa-sync-alt mr-1"></i>
                        Refresh
                    </button>
                </div>

                <x-reputation-display :user="$user" detailed="true" showBreakdown="true" />

                @if($subscriptionData['reputation']['subscription_benefit'])
                    <div class="mt-4 p-3 bg-blue-50 rounded-lg border border-blue-200">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-rocket text-blue-600"></i>
                            <span class="text-sm font-medium text-blue-800">
                                Pro Benefit: +{{ number_format($subscriptionData['reputation']['multiplier_bonus'], 1) }} bonus reputation
                            </span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Earnings Breakdown -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-chart-line text-green-500 mr-2"></i>
                    Earnings Breakdown
                </h4>

                <div class="space-y-4">
                    <div class="flex justify-between items-center p-3 bg-green-50 rounded-lg">
                        <span class="text-green-700 font-medium">Total Earnings</span>
                        <span class="text-green-900 font-bold text-lg">
                            ${{ number_format($subscriptionData['earnings']['total'], 2) }}
                        </span>
                    </div>

                    <div class="flex justify-between items-center p-3 bg-red-50 rounded-lg">
                        <span class="text-red-700 font-medium">Commission Paid</span>
                        <span class="text-red-900 font-semibold">
                            ${{ number_format($subscriptionData['earnings']['commission_paid'], 2) }}
                        </span>
                    </div>

                    @if($subscriptionData['earnings']['commission_savings'] > 0)
                        <div class="flex justify-between items-center p-3 bg-purple-50 rounded-lg">
                            <span class="text-purple-700 font-medium">Commission Savings</span>
                            <span class="text-purple-900 font-semibold">
                                ${{ number_format($subscriptionData['earnings']['commission_savings'], 2) }}
                            </span>
                        </div>
                    @endif

                    <div class="border-t pt-3">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Current Rate</span>
                            <span class="font-semibold text-gray-900">
                                {{ $user->getPlatformCommissionRate() }}%
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Activity -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                    Monthly Activity
                </h4>

                <div class="space-y-4">
                    <!-- Visibility Boosts -->
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-rocket text-blue-600"></i>
                            <span class="font-medium text-gray-900">Visibility Boosts</span>
                        </div>
                        <div class="text-right">
                            <span class="font-semibold text-gray-900">
                                {{ $subscriptionData['usage_summary']['visibility_boosts']['used'] }} used
                            </span>
                            <br>
                            <span class="text-sm text-gray-600">
                                {{ $subscriptionData['remaining']['visibility_boosts'] }} remaining
                            </span>
                        </div>
                    </div>

                    <!-- Quick Boost Actions -->
                    @if($subscriptionData['remaining']['visibility_boosts'] > 0)
                        <div class="grid grid-cols-2 gap-2">
                            <button wire:click="createVisibilityBoost('profile', 72)" 
                                    class="px-3 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                <i class="fas fa-user mr-1"></i>
                                Boost Profile
                            </button>
                            <button wire:click="createVisibilityBoost('project', 72)" 
                                    class="px-3 py-2 bg-purple-600 text-white text-sm rounded-lg hover:bg-purple-700 transition-colors duration-200">
                                <i class="fas fa-folder mr-1"></i>
                                Boost Project
                            </button>
                        </div>
                    @endif

                    <!-- Private Projects -->
                    @if($user->getMaxPrivateProjectsMonthly() !== 0)
                        <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-lock text-red-600"></i>
                                <span class="font-medium text-gray-900">Private Projects</span>
                            </div>
                            <div class="text-right">
                                <span class="font-semibold text-gray-900">
                                    {{ $subscriptionData['usage_summary']['private_projects']['created'] }} created
                                </span>
                                <br>
                                <span class="text-sm text-gray-600">
                                    @if($subscriptionData['remaining']['private_projects'] === null)
                                        Unlimited
                                    @else
                                        {{ $subscriptionData['remaining']['private_projects'] }} remaining
                                    @endif
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-history text-gray-500 mr-2"></i>
                    Recent Activity
                </h4>

                <div class="space-y-4">
                    <!-- Recent Transactions -->
                    @if($recentActivity['transactions']->isNotEmpty())
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Recent Transactions</h5>
                            <div class="space-y-2">
                                @foreach($recentActivity['transactions']->take(3) as $transaction)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">
                                            {{ $transaction->type }} • {{ $transaction->created_at->format('M j') }}
                                        </span>
                                        <span class="font-medium text-gray-900">
                                            ${{ number_format($transaction->net_amount, 2) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Recent Boosts -->
                    @if($recentActivity['visibility_boosts']->isNotEmpty())
                        <div>
                            <h5 class="text-sm font-medium text-gray-700 mb-2">Recent Boosts</h5>
                            <div class="space-y-2">
                                @foreach($recentActivity['visibility_boosts']->take(3) as $boost)
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-600">
                                            {{ ucfirst($boost->boost_type) }} boost • {{ $boost->created_at->format('M j') }}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 bg-{{ $boost->status === 'active' ? 'green' : 'gray' }}-100 text-{{ $boost->status === 'active' ? 'green' : 'gray' }}-800 text-xs rounded-full">
                                            {{ ucfirst($boost->status) }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Subscription Insights -->
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6 border border-indigo-200">
            <h4 class="font-semibold text-indigo-900 mb-4 flex items-center">
                <i class="fas fa-lightbulb text-indigo-600 mr-2"></i>
                Subscription Insights
            </h4>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
                @if($user->getReputationMultiplier() > 1.0)
                    <div class="bg-white/60 rounded-lg p-3">
                        <div class="flex items-center text-indigo-700 font-medium mb-1">
                            <i class="fas fa-rocket mr-2"></i>
                            Reputation Boost
                        </div>
                        <p class="text-indigo-600">
                            Your {{ $subscriptionData['plan_name'] }} plan gives you a {{ $user->getReputationMultiplier() }}× reputation multiplier.
                        </p>
                    </div>
                @endif

                @if($subscriptionData['earnings']['commission_savings'] > 0)
                    <div class="bg-white/60 rounded-lg p-3">
                        <div class="flex items-center text-indigo-700 font-medium mb-1">
                            <i class="fas fa-piggy-bank mr-2"></i>
                            Commission Savings
                        </div>
                        <p class="text-indigo-600">
                            You've saved ${{ number_format($subscriptionData['earnings']['commission_savings'], 2) }} in reduced commission fees.
                        </p>
                    </div>
                @endif

                @if($user->hasClientPortalAccess())
                    <div class="bg-white/60 rounded-lg p-3">
                        <div class="flex items-center text-indigo-700 font-medium mb-1">
                            <i class="fas fa-users mr-2"></i>
                            Client Portal
                        </div>
                        <p class="text-indigo-600">
                            Access advanced client management tools and analytics.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
