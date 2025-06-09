<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription Management') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @php
                $isSubscribed = $user->hasActiveSubscription();
                $onGracePeriod = $isSubscribed && $user->subscription('default') && $user->subscription('default')->onGracePeriod();
                $subscription = $user->subscription('default');
                $planName = $user->getSubscriptionDisplayName();
                $billingPeriod = $user->getBillingPeriodDisplayName();
                $formattedPrice = $user->getFormattedSubscriptionPrice();
                $yearlySavings = $user->getYearlySavings();
                $totalEarnings = $user->getTotalEarnings();
                $commissionSavings = $user->getCommissionSavings();
                $reputationData = $user->getReputationWithMultiplier();
                
                // Check if we have complete billing data
                $hasValidBillingData = $user->subscription_price && $user->billing_period;
                $shouldShowBillingInfo = $isSubscribed && !$onGracePeriod && $user->isProPlan() && $hasValidBillingData;
            @endphp

            <!-- Hero Section -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-8 py-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <div class="bg-white/20 p-3 rounded-xl">
                                @if($user->getUserBadge())
                                    <span class="text-3xl">{{ $user->getUserBadge() }}</span>
                                @else
                                    <i class="fas fa-crown text-white text-2xl"></i>
                                @endif
                            </div>
                            <div>
                                <h1 class="text-2xl font-bold text-white">{{ $planName }}</h1>
                                <p class="text-blue-100 text-lg">
                                    @if($user->isProPlan())
                                        Lower commission rates saving you money
                                    @else
                                        Get started with professional features
                                    @endif
                                </p>
                                @if($shouldShowBillingInfo)
                                    <p class="text-blue-200 text-sm mt-1">
                                        {{ $formattedPrice }} ({{ $billingPeriod }})
                                        @if($yearlySavings)
                                            • Saving ${{ number_format($yearlySavings, 2) }}/year
                                        @endif
                                    </p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="text-right">
                            @if($totalEarnings > 0)
                                <div class="text-3xl font-bold text-white">${{ number_format($totalEarnings, 2) }}</div>
                                <div class="text-blue-200">Total Earned</div>
                            @endif
                        </div>
                    </div>

                    <!-- Status Alerts -->
                    @if($onGracePeriod)
                        <div class="mt-4 bg-yellow-500/20 border border-yellow-300/30 rounded-lg p-4">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-yellow-300 mr-3"></i>
                                <div>
                                    <h4 class="font-medium text-yellow-100">Subscription Ending</h4>
                                    <p class="text-yellow-200 text-sm">
                                        Your subscription ends on {{ $subscription->ends_at->format('M d, Y') }}. You'll keep Pro features until then.
                                    </p>
                                </div>
                                <form action="{{ route('subscription.resume') }}" method="POST" class="ml-auto">
                                    @csrf
                                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded-lg transition-colors duration-200">
                                        Resume
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Key Metrics Dashboard (3-Card Focus) -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Total Earnings -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-600 text-sm font-medium mb-1">Total Earnings</p>
                            <p class="text-green-900 text-3xl font-bold">${{ number_format($totalEarnings, 2) }}</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-xl">
                            <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Commission Rate & Savings -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-600 text-sm font-medium mb-1">Commission Rate</p>
                            <p class="text-blue-900 text-3xl font-bold">{{ $user->getPlatformCommissionRate() }}%</p>
                            @if($commissionSavings > 0)
                                <p class="text-green-600 text-sm font-medium">Saved ${{ number_format($commissionSavings, 2) }}</p>
                            @endif
                        </div>
                        <div class="bg-blue-100 p-3 rounded-xl">
                            <i class="fas fa-percentage text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Reputation Score -->
                <div class="bg-white rounded-xl shadow-lg p-6 border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-600 text-sm font-medium mb-1">Reputation</p>
                            <p class="text-yellow-900 text-3xl font-bold">{{ number_format($reputationData['final_reputation'], 1) }}</p>
                            @if($user->getReputationMultiplier() > 1.0)
                                <p class="text-purple-600 text-sm font-medium">{{ $user->getReputationMultiplier() }}× Multiplier</p>
                            @endif
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-xl">
                            <span class="text-2xl">{{ $reputationData['tier']['badge'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Panel -->
            @if($user->isProPlan())
                <div class="bg-white rounded-xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Visibility Boost -->
                        @if($user->getRemainingVisibilityBoosts() > 0)
                            <button onclick="showBoostModal()" class="flex items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl border border-blue-200 transition-colors duration-200">
                                <i class="fas fa-rocket text-blue-600 mr-3"></i>
                                <div class="text-left">
                                    <div class="font-medium text-blue-900">Boost Visibility</div>
                                    <div class="text-sm text-blue-600">{{ $user->getRemainingVisibilityBoosts() }} remaining</div>
                                </div>
                            </button>
                        @endif

                        <!-- Create Private Project -->
                        @if($user->getRemainingPrivateProjects() === null || $user->getRemainingPrivateProjects() > 0)
                            <a href="{{ route('projects.create') }}?private=1" class="flex items-center justify-center p-4 bg-red-50 hover:bg-red-100 rounded-xl border border-red-200 transition-colors duration-200">
                                <i class="fas fa-lock text-red-600 mr-3"></i>
                                <div class="text-left">
                                    <div class="font-medium text-red-900">Create Private Project</div>
                                    <div class="text-sm text-red-600">
                                        @if($user->getRemainingPrivateProjects() === null)
                                            Unlimited
                                        @else
                                            {{ $user->getRemainingPrivateProjects() }} remaining
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @endif

                        <!-- Manage Billing -->
                        <a href="{{ route('billing.portal') }}" class="flex items-center justify-center p-4 bg-gray-50 hover:bg-gray-100 rounded-xl border border-gray-200 transition-colors duration-200">
                            <i class="fas fa-credit-card text-gray-600 mr-3"></i>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">Manage Billing</div>
                                <div class="text-sm text-gray-600">Payment & invoices</div>
                            </div>
                        </a>
                    </div>
                </div>
            @endif

            <!-- Tabbed Usage Overview -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex space-x-8 px-6" role="tablist">
                        <button class="tab-button active py-4 px-1 border-b-2 border-blue-500 font-medium text-sm text-blue-600" data-tab="usage">
                            Usage Overview
                        </button>
                        <button class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="features">
                            Plan Features
                        </button>
                        @if($user->isProPlan())
                            <button class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700" data-tab="analytics">
                                Detailed Analytics
                            </button>
                        @endif
                    </nav>
                </div>

                <!-- Tab Contents -->
                <div class="p-6">
                    <!-- Usage Tab -->
                    <div id="usage-tab" class="tab-content">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Projects Usage -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Projects</span>
                                    <i class="fas fa-folder text-blue-500"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $usage['projects_count'] }}</div>
                                <div class="text-sm text-gray-600">
                                    / {{ $limits && $limits->max_projects_owned ? $limits->max_projects_owned : 'Unlimited' }}
                                </div>
                                @if($limits && $limits->max_projects_owned)
                                    <div class="mt-2">
                                        <div class="bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(($usage['projects_count'] / $limits->max_projects_owned) * 100, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Active Pitches -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Active Pitches</span>
                                    <i class="fas fa-paper-plane text-green-500"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $usage['active_pitches_count'] }}</div>
                                <div class="text-sm text-gray-600">
                                    / {{ $limits && $limits->max_active_pitches ? $limits->max_active_pitches : 'Unlimited' }}
                                </div>
                                @if($limits && $limits->max_active_pitches)
                                    <div class="mt-2">
                                        <div class="bg-gray-200 rounded-full h-2">
                                            <div class="bg-green-500 h-2 rounded-full" style="width: {{ min(($usage['active_pitches_count'] / $limits->max_active_pitches) * 100, 100) }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Visibility Boosts -->
                            @if($user->getMonthlyVisibilityBoosts() > 0)
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Visibility Boosts</span>
                                    <i class="fas fa-rocket text-purple-500"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $user->getMonthlyVisibilityBoosts() - $user->getRemainingVisibilityBoosts() }}</div>
                                <div class="text-sm text-gray-600">/ {{ $user->getMonthlyVisibilityBoosts() }} this month</div>
                                <div class="mt-2">
                                    <div class="bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-500 h-2 rounded-full" style="width: {{ (($user->getMonthlyVisibilityBoosts() - $user->getRemainingVisibilityBoosts()) / $user->getMonthlyVisibilityBoosts()) * 100 }}%"></div>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Storage -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm font-medium text-gray-700">Storage</span>
                                    <i class="fas fa-hdd text-orange-500"></i>
                                </div>
                                <div class="text-2xl font-bold text-gray-900">{{ $user->getStoragePerProjectGB() }}GB</div>
                                <div class="text-sm text-gray-600">per project</div>
                            </div>
                        </div>
                    </div>

                    <!-- Features Tab -->
                    <div id="features-tab" class="tab-content hidden">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-4">
                                <h4 class="font-semibold text-gray-900">Core Features</h4>
                                <div class="space-y-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">
                                            {{ $limits->max_projects_owned ? $limits->max_projects_owned . ' Project' . ($limits->max_projects_owned > 1 ? 's' : '') : 'Unlimited Projects' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">
                                            {{ $limits->max_active_pitches ? $limits->max_active_pitches . ' Active Pitches' : 'Unlimited Active Pitches' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">{{ $user->getStoragePerProjectGB() }}GB Storage per Project</span>
                                    </div>
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">{{ $user->getPlatformCommissionRate() }}% Commission Rate</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="space-y-4">
                                <h4 class="font-semibold text-gray-900">Pro Features</h4>
                                <div class="space-y-3">
                                    @if($user->getReputationMultiplier() > 1.0)
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">{{ $user->getReputationMultiplier() }}× Reputation Multiplier</span>
                                    </div>
                                    @endif
                                    @if($user->getMonthlyVisibilityBoosts() > 0)
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">{{ $user->getMonthlyVisibilityBoosts() }} Visibility Boosts/Month</span>
                                    </div>
                                    @endif
                                    @if($user->getMaxPrivateProjectsMonthly() !== 0)
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">
                                            @if($user->getMaxPrivateProjectsMonthly() === null)
                                                Unlimited Private Projects
                                            @else
                                                {{ $user->getMaxPrivateProjectsMonthly() }} Private Projects/Month
                                            @endif
                                        </span>
                                    </div>
                                    @endif
                                    @if($user->hasClientPortalAccess())
                                    <div class="flex items-center">
                                        <i class="fas fa-check text-green-500 mr-3"></i>
                                        <span class="text-gray-700">Client Portal Access</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Tab -->
                    @if($user->isProPlan())
                    <div id="analytics-tab" class="tab-content hidden">
                        <livewire:subscription-dashboard />
                    </div>
                    @endif
                </div>
            </div>

            <!-- Account Management Panel -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-6">Account Management</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Billing & Payment -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900">Billing & Payment</h4>
                        <div class="space-y-3">
                            @if($isSubscribed)
                            <a href="{{ route('billing.portal') }}" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg text-center transition-colors duration-200">
                                <i class="fas fa-credit-card mr-2"></i>
                                Update Payment Method
                            </a>
                            <a href="{{ route('billing.invoices') }}" class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg text-center transition-colors duration-200">
                                <i class="fas fa-file-invoice mr-2"></i>
                                View Invoices
                            </a>
                            @endif
                        </div>
                    </div>

                    <!-- Plan Changes -->
                    <div class="space-y-4">
                        <h4 class="font-semibold text-gray-900">Plan Changes</h4>
                        <div class="space-y-3">
                            @if($user->isFreePlan())
                            <a href="{{ route('pricing') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg text-center transition-colors duration-200">
                                <i class="fas fa-arrow-up mr-2"></i>
                                Upgrade to Pro
                            </a>
                            @elseif(!$onGracePeriod)
                            <form action="{{ route('subscription.downgrade') }}" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to cancel your subscription? You will lose access to Pro features at the end of your billing period.')">
                                @csrf
                                <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-medium py-3 px-4 rounded-lg transition-colors duration-200">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel Subscription
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>

                @if($isSubscribed && $subscription)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="font-semibold text-gray-900 mb-4">Subscription Details</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600">Plan:</span>
                            <div class="font-medium">{{ $planName }}</div>
                        </div>
                        <div>
                            <span class="text-gray-600">Status:</span>
                            <div class="font-medium {{ $onGracePeriod ? 'text-yellow-600' : 'text-green-600' }}">
                                {{ $onGracePeriod ? 'Cancelling' : 'Active' }}
                            </div>
                        </div>
                        <div>
                            <span class="text-gray-600">Started:</span>
                            <div class="font-medium">{{ $subscription->created_at->format('M d, Y') }}</div>
                        </div>
                        @if(!$onGracePeriod && $subscription->asStripeSubscription()->current_period_end)
                        <div>
                            <span class="text-gray-600">Next Billing:</span>
                            <div class="font-medium">{{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') }}</div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            <!-- Upgrade Options for Free Users -->
            @if($user->isFreePlan())
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-xl font-semibold text-gray-900 mb-6 text-center">Upgrade Your Plan</h3>
                
                <!-- Billing Period Toggle -->
                <div class="flex justify-center mb-8">
                    <div class="bg-gray-100 rounded-xl shadow-lg p-2">
                        <div class="flex relative">
                            <button 
                                id="monthly-toggle" 
                                class="billing-toggle active px-6 py-2 rounded-lg font-medium transition-all duration-200"
                                data-period="monthly"
                            >
                                Monthly Billing
                            </button>
                            <button 
                                id="yearly-toggle" 
                                class="billing-toggle px-6 py-2 rounded-lg font-medium transition-all duration-200"
                                data-period="yearly"
                            >
                                <span>Yearly Billing</span>
                                <span class="inline-block ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Save 17%</span>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pro Artist Plan -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="text-center">
                            <h4 class="text-xl font-semibold text-gray-900 flex items-center justify-center">
                                Pro Artist
                                <span class="ml-2">🔷</span>
                            </h4>
                            <div class="mt-4">
                                <!-- Monthly Pricing -->
                                <div class="monthly-pricing">
                                    <span class="text-3xl font-bold text-gray-900">$6.99</span>
                                    <span class="text-base font-medium text-gray-500">/month</span>
                                </div>
                                <!-- Yearly Pricing -->
                                <div class="yearly-pricing hidden">
                                    <span class="text-3xl font-bold text-gray-900">$69.99</span>
                                    <span class="text-base font-medium text-gray-500">/year</span>
                                    <div class="mt-2">
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full font-medium">
                                            Save $13.89/year
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Unlimited Projects & Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">5GB Storage per Project</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">8% Commission Rate</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Custom License Templates</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">4 Visibility Boosts/Month</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">2 Private Projects/Month</span>
                            </li>
                        </ul>
                        <div class="mt-6">
                            <form action="{{ route('subscription.upgrade') }}" method="POST" class="subscription-form">
                                @csrf
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="tier" value="artist">
                                <input type="hidden" name="billing_period" class="billing-period-input" value="monthly">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                                    <span class="monthly-text">Upgrade to Pro Artist</span>
                                    <span class="yearly-text hidden">Upgrade to Pro Artist</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Pro Engineer Plan -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="text-center">
                            <h4 class="text-xl font-semibold text-gray-900 flex items-center justify-center">
                                Pro Engineer
                                <span class="ml-2">🔶</span>
                            </h4>
                            <div class="mt-4">
                                <!-- Monthly Pricing -->
                                <div class="monthly-pricing">
                                    <span class="text-3xl font-bold text-gray-900">$9.99</span>
                                    <span class="text-base font-medium text-gray-500">/month</span>
                                </div>
                                <!-- Yearly Pricing -->
                                <div class="yearly-pricing hidden">
                                    <span class="text-3xl font-bold text-gray-900">$99.99</span>
                                    <span class="text-base font-medium text-gray-500">/year</span>
                                    <div class="mt-2">
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full font-medium">
                                            Save $19.89/year
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Unlimited Projects & Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">10GB Storage per Project</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">6% Commission Rate</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">1.25× Reputation Multiplier</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Unlimited Private Projects</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Client Portal Access</span>
                            </li>
                        </ul>
                        <div class="mt-6">
                            <form action="{{ route('subscription.upgrade') }}" method="POST" class="subscription-form">
                                @csrf
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="tier" value="engineer">
                                <input type="hidden" name="billing_period" class="billing-period-input" value="monthly">
                                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded transition-colors duration-200">
                                    <span class="monthly-text">Upgrade to Pro Engineer</span>
                                    <span class="yearly-text hidden">Upgrade to Pro Engineer</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Feature Comparison Link -->
                <div class="mt-6 text-center">
                    <a href="{{ route('pricing') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        View complete feature comparison →
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Visibility Boost Modal -->
    <div id="boost-modal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <h3 class="text-xl font-bold text-gray-900 mb-4">Boost Your Visibility</h3>
            <p class="text-gray-600 mb-6">Choose what you'd like to boost for the next 72 hours.</p>
            
            <div class="space-y-3">
                <button onclick="createBoost('profile')" class="w-full flex items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 rounded-xl border border-blue-200 transition-colors duration-200">
                    <i class="fas fa-user text-blue-600 mr-3"></i>
                    <span class="font-medium text-blue-900">Boost Profile</span>
                </button>
                <button onclick="createBoost('project')" class="w-full flex items-center justify-center p-4 bg-purple-50 hover:bg-purple-100 rounded-xl border border-purple-200 transition-colors duration-200">
                    <i class="fas fa-folder text-purple-600 mr-3"></i>
                    <span class="font-medium text-purple-900">Boost Latest Project</span>
                </button>
            </div>
            
            <div class="mt-6 pt-4 border-t border-gray-200">
                <button onclick="hideBoostModal()" class="w-full bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                    Cancel
                </button>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetTab = button.dataset.tab;
                    
                    // Update button states
                    tabButtons.forEach(btn => {
                        btn.classList.remove('active', 'border-blue-500', 'text-blue-600');
                        btn.classList.add('border-transparent', 'text-gray-500');
                    });
                    button.classList.add('active', 'border-blue-500', 'text-blue-600');
                    button.classList.remove('border-transparent', 'text-gray-500');
                    
                    // Update content visibility
                    tabContents.forEach(content => {
                        content.classList.add('hidden');
                    });
                    document.getElementById(`${targetTab}-tab`).classList.remove('hidden');
                });
            });

            // Billing period toggle for upgrade section
            const monthlyToggle = document.getElementById('monthly-toggle');
            const yearlyToggle = document.getElementById('yearly-toggle');
            
            if (monthlyToggle && yearlyToggle) {
                function showMonthly() {
                    monthlyToggle.classList.add('active', 'bg-blue-600', 'text-white');
                    monthlyToggle.classList.remove('text-gray-600');
                    yearlyToggle.classList.remove('active', 'bg-blue-600', 'text-white');
                    yearlyToggle.classList.add('text-gray-600');

                    document.querySelectorAll('.monthly-pricing').forEach(el => el.classList.remove('hidden'));
                    document.querySelectorAll('.yearly-pricing').forEach(el => el.classList.add('hidden'));
                    document.querySelectorAll('.billing-period-input').forEach(input => input.value = 'monthly');
                    document.querySelectorAll('.monthly-text').forEach(text => text.classList.remove('hidden'));
                    document.querySelectorAll('.yearly-text').forEach(text => text.classList.add('hidden'));
                }

                function showYearly() {
                    yearlyToggle.classList.add('active', 'bg-blue-600', 'text-white');
                    yearlyToggle.classList.remove('text-gray-600');
                    monthlyToggle.classList.remove('active', 'bg-blue-600', 'text-white');
                    monthlyToggle.classList.add('text-gray-600');

                    document.querySelectorAll('.yearly-pricing').forEach(el => el.classList.remove('hidden'));
                    document.querySelectorAll('.monthly-pricing').forEach(el => el.classList.add('hidden'));
                    document.querySelectorAll('.billing-period-input').forEach(input => input.value = 'yearly');
                    document.querySelectorAll('.yearly-text').forEach(text => text.classList.remove('hidden'));
                    document.querySelectorAll('.monthly-text').forEach(text => text.classList.add('hidden'));
                }

                showMonthly(); // Set initial state
                monthlyToggle.addEventListener('click', showMonthly);
                yearlyToggle.addEventListener('click', showYearly);
            }
        });

        // Boost modal functions
        function showBoostModal() {
            document.getElementById('boost-modal').classList.remove('hidden');
        }

        function hideBoostModal() {
            document.getElementById('boost-modal').classList.add('hidden');
        }

        function createBoost(type) {
            // You can implement the actual boost creation logic here
            // For now, just simulate success
            hideBoostModal();
            
            // Show success message (you can customize this)
            const message = document.createElement('div');
            message.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50';
            message.textContent = `${type.charAt(0).toUpperCase() + type.slice(1)} boost activated!`;
            document.body.appendChild(message);
            
            setTimeout(() => {
                message.remove();
                location.reload(); // Refresh to show updated usage
            }, 2000);
        }

        // Close modal when clicking outside
        document.getElementById('boost-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                hideBoostModal();
            }
        });
    </script>
    @endpush

    @push('styles')
    <style>
        .billing-toggle.active {
            @apply bg-blue-600 text-white shadow-md;
        }
        .tab-button.active {
            @apply border-blue-500 text-blue-600;
        }
    </style>
    @endpush
</x-app-layout> 