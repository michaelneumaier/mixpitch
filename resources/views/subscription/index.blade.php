<x-layouts.app-sidebar>

@php
    // Unified Color System - Subscription context (blue theme)
    $workflowColors = [
        'bg' => 'bg-blue-50 dark:bg-blue-950',
        'border' => 'border-blue-200 dark:border-blue-800', 
        'text_primary' => 'text-blue-900 dark:text-blue-100',
        'text_secondary' => 'text-blue-700 dark:text-blue-300',
        'text_muted' => 'text-blue-600 dark:text-blue-400',
        'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
        'accent_border' => 'border-blue-200 dark:border-blue-800',
        'icon' => 'text-blue-600 dark:text-blue-400'
    ];

    // Semantic colors (consistent across app)
    $semanticColors = [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'accent' => 'bg-green-600 dark:bg-green-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400', 
            'accent' => 'bg-amber-500'
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'accent' => 'bg-red-500'
        ]
    ];

    // Subscription data processing
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

    // Gradient classes for modern header
    $gradientClasses = 'bg-gradient-to-r from-blue-500/10 via-blue-600/10 to-purple-600/10 dark:from-blue-400/20 dark:via-blue-500/20 dark:to-purple-500/20';
@endphp

<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="container mx-auto px-2 py-2">
        <div class="mx-auto">
            <div class="space-y-6">

                <!-- Compact Subscription Header -->
                <flux:card class="mb-2 bg-white/50">
                    <!-- Top Row: Title + Primary Actions -->
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <flux:heading size="lg" class="bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 dark:from-gray-100 dark:via-blue-300 dark:to-purple-300 bg-clip-text text-transparent">
                            Subscription Management
                        </flux:heading>
                        
                        <!-- Primary Actions -->
                        <div class="flex items-center gap-2">
                            @if($user->isFreePlan())
                                <flux:button href="{{ route('pricing') }}" icon="arrow-up" variant="filled" size="xs" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 !text-white">
                                    Upgrade
                                </flux:button>
                            @else
                                <flux:button href="{{ route('billing.portal') }}" icon="cog-6-tooth" variant="outline" size="xs">
                                    Billing
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <!-- Mobile: Stack layout -->
                    <div class="lg:hidden space-y-3">
                        <!-- Plan + Critical Stats Row -->
                        <div class="flex items-center justify-between">
                            <flux:badge :color="$user->isProPlan() ? 'emerald' : 'zinc'" size="sm" :icon="$user->isProPlan() ? 'sparkles' : 'user'">
                                {{ $planName }}
                            </flux:badge>
                            
                            <div class="flex items-center gap-2">
                                @if($totalEarnings > 0)
                                    <flux:badge color="green" size="sm" icon="currency-dollar">
                                        <span class="font-mono text-xs">${{ number_format($totalEarnings, 0) }}</span>
                                    </flux:badge>
                                @endif
                                <flux:badge color="blue" size="sm" icon="percent-badge">
                                    <span class="font-mono text-xs">{{ $user->getPlatformCommissionRate() }}%</span>
                                </flux:badge>
                                @if($user->isProPlan() && $user->getReputationMultiplier() > 1.0)
                                    <flux:badge color="purple" size="sm" icon="star">
                                        <span class="font-mono text-xs">{{ $user->getReputationMultiplier() }}×</span>
                                    </flux:badge>
                                @endif
                            </div>
                        </div>
                        
                        <!-- Action Buttons Row -->
                        <div class="flex items-center justify-center gap-2">
                            @if($user->isFreePlan())
                                <flux:button href="{{ route('pricing') }}" icon="arrow-up" variant="filled" size="xs" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 flex-1 !text-white">
                                    Upgrade to Pro
                                </flux:button>
                            @else
                                <flux:button href="{{ route('billing.portal') }}" icon="credit-card" variant="outline" size="xs" class="flex-1">
                                    Manage Billing
                                </flux:button>
                            @endif
                            
                            @if($shouldShowBillingInfo)
                                <flux:badge color="zinc" size="sm">
                                    {{ $formattedPrice }}
                                </flux:badge>
                            @endif
                        </div>
                    </div>

                    <!-- Desktop: Horizontal layout -->
                    <div class="hidden lg:flex lg:items-center lg:gap-3">
                        <!-- Plan Badge -->
                        <flux:badge :color="$user->isProPlan() ? 'emerald' : 'zinc'" size="sm" :icon="$user->isProPlan() ? 'sparkles' : 'user'" class="flex-shrink-0">
                            {{ $planName }}
                        </flux:badge>

                        <!-- Key Stats -->
                        <div class="flex items-center gap-2">
                            @if($totalEarnings > 0)
                                <flux:badge color="green" size="sm" icon="currency-dollar">
                                    <span class="font-mono">${{ number_format($totalEarnings, 0) }} earned</span>
                                </flux:badge>
                            @endif
                            
                            <flux:badge color="blue" size="sm" icon="percent-badge">
                                <span class="font-mono">{{ $user->getPlatformCommissionRate() }}% commission</span>
                            </flux:badge>

                            @if($user->isProPlan() && $user->getReputationMultiplier() > 1.0)
                                <flux:badge color="purple" size="sm" icon="star">
                                    <span class="font-mono">{{ $user->getReputationMultiplier() }}× reputation</span>
                                </flux:badge>
                            @endif

                            @if($shouldShowBillingInfo)
                                <flux:badge color="zinc" size="sm" icon="calendar">
                                    <span class="font-mono">{{ $formattedPrice }} {{ strtolower($billingPeriod) }}</span>
                                </flux:badge>
                            @endif
                        </div>

                        <!-- Spacer -->
                        <div class="flex-1"></div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            @if($user->isFreePlan())
                                <flux:button href="{{ route('pricing') }}" icon="arrow-up" variant="filled" size="xs" class="bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 !text-white">
                                    Upgrade to Pro
                                </flux:button>
                            @else
                                <flux:button href="{{ route('billing.portal') }}" icon="credit-card" variant="outline" size="xs">
                                    Manage Billing
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    <!-- Status Alerts -->
                    @if($onGracePeriod)
                        <flux:callout color="amber" icon="exclamation-triangle" class="mt-4">
                            <div class="flex items-center justify-between">
                                <flux:callout.text>
                                    <strong>Subscription Ending:</strong> Your subscription ends on {{ $subscription->ends_at->format('M d, Y') }}. You'll keep Pro features until then.
                                </flux:callout.text>
                                <form action="{{ route('subscription.resume') }}" method="POST">
                                    @csrf
                                    <flux:button type="submit" variant="primary" size="xs" icon="arrow-path">
                                        Resume
                                    </flux:button>
                                </form>
                            </div>
                        </flux:callout>
                    @endif
                </flux:card>

                <!-- Key Metrics Dashboard -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Total Earnings -->
                    <flux:card class="{{ $semanticColors['success']['bg'] }} {{ $semanticColors['success']['border'] }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text size="sm" class="{{ $semanticColors['success']['text'] }} font-medium mb-1">Total Earnings</flux:text>
                                <flux:heading size="xl" class="{{ $semanticColors['success']['text'] }}">${{ number_format($totalEarnings, 2) }}</flux:heading>
                            </div>
                            <div class="{{ $semanticColors['success']['accent'] }} p-3 rounded-xl">
                                <flux:icon name="currency-dollar" class="text-white text-xl" />
                            </div>
                        </div>
                    </flux:card>

                    <!-- Commission Rate & Savings -->
                    <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] }} font-medium mb-1">Commission Rate</flux:text>
                                <flux:heading size="xl" class="{{ $workflowColors['text_primary'] }}">{{ $user->getPlatformCommissionRate() }}%</flux:heading>
                                @if($commissionSavings > 0)
                                    <flux:text size="sm" class="{{ $semanticColors['success']['text'] }} font-medium">Saved ${{ number_format($commissionSavings, 2) }}</flux:text>
                                @endif
                            </div>
                            <div class="{{ $workflowColors['accent_bg'] }} p-3 rounded-xl border {{ $workflowColors['accent_border'] }}">
                                <flux:icon name="percent-badge" class="{{ $workflowColors['icon'] }} text-xl" />
                            </div>
                        </div>
                    </flux:card>

                    <!-- Reputation Score -->
                    <flux:card class="bg-yellow-50 dark:bg-yellow-950 border-yellow-200 dark:border-yellow-800">
                        <div class="flex items-center justify-between">
                            <div>
                                <flux:text size="sm" class="text-yellow-600 dark:text-yellow-400 font-medium mb-1">Reputation</flux:text>
                                <flux:heading size="xl" class="text-yellow-900 dark:text-yellow-100">{{ number_format($reputationData['final_reputation'], 1) }}</flux:heading>
                                @if($user->getReputationMultiplier() > 1.0)
                                    <flux:text size="sm" class="text-purple-600 dark:text-purple-400 font-medium">{{ $user->getReputationMultiplier() }}× Multiplier</flux:text>
                                @endif
                            </div>
                            <div class="bg-yellow-100 dark:bg-yellow-900 p-3 rounded-xl">
                                <span class="text-2xl">{{ $reputationData['tier']['badge'] }}</span>
                            </div>
                        </div>
                    </flux:card>
                </div>

                <!-- Quick Actions Panel -->
                @if($user->isProPlan())
                    <flux:card>
                        <flux:heading size="lg" class="mb-4">Quick Actions</flux:heading>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Visibility Boost -->
                            @if($user->getRemainingVisibilityBoosts() > 0)
                                <flux:button 
                                    x-data
                                    @click="$dispatch('open-modal', 'boost-modal')"
                                    variant="outline" 
                                    icon="rocket-launch"
                                    class="flex items-center justify-center p-4 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800">
                                    <div class="text-left ml-3">
                                        <div class="font-medium text-blue-900 dark:text-blue-100">Boost Visibility</div>
                                        <div class="text-sm text-blue-600 dark:text-blue-400">{{ $user->getRemainingVisibilityBoosts() }} remaining</div>
                                    </div>
                                </flux:button>
                            @endif

                            <!-- Create Private Project -->
                            @if($user->getRemainingPrivateProjects() === null || $user->getRemainingPrivateProjects() > 0)
                                <flux:button 
                                    href="{{ route('projects.create') }}?private=1" 
                                    variant="outline"
                                    icon="lock-closed"
                                    class="flex items-center justify-center p-4 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 border-red-200 dark:border-red-800">
                                    <div class="text-left ml-3">
                                        <div class="font-medium text-red-900 dark:text-red-100">Create Private Project</div>
                                        <div class="text-sm text-red-600 dark:text-red-400">
                                            @if($user->getRemainingPrivateProjects() === null)
                                                Unlimited
                                            @else
                                                {{ $user->getRemainingPrivateProjects() }} remaining
                                            @endif
                                        </div>
                                    </div>
                                </flux:button>
                            @endif

                            <!-- Manage Billing -->
                            <flux:button 
                                href="{{ route('billing.portal') }}" 
                                variant="outline"
                                icon="credit-card"
                                class="flex items-center justify-center p-4 bg-gray-50 hover:bg-gray-100 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700">
                                <div class="text-left ml-3">
                                    <div class="font-medium text-gray-900 dark:text-gray-100">Manage Billing</div>
                                    <div class="text-sm text-gray-600 dark:text-gray-400">Payment & invoices</div>
                                </div>
                            </flux:button>
                        </div>
                    </flux:card>
                @endif

                <!-- Tabbed Usage Overview -->
                <flux:card x-data="{ activeTab: 'usage' }">
                    <!-- Tab Navigation -->
                    <div class="border-b border-gray-200 dark:border-gray-700">
                        <nav class="flex space-x-8 px-6" role="tablist">
                            <button 
                                @click="activeTab = 'usage'"
                                class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                :class="activeTab === 'usage' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'">
                                Usage Overview
                            </button>
                            <button 
                                @click="activeTab = 'features'"
                                class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                :class="activeTab === 'features' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'">
                                Plan Features
                            </button>
                            @if($user->isProPlan())
                                <button 
                                    @click="activeTab = 'analytics'"
                                    class="py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                                    :class="activeTab === 'analytics' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'">
                                    Detailed Analytics
                                </button>
                            @endif
                        </nav>
                    </div>

                    <!-- Tab Contents -->
                    <div class="p-6">
                        <!-- Usage Tab -->
                        <div x-show="activeTab === 'usage'" x-transition>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                                <!-- Projects Usage -->
                                <flux:card class="bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300">Projects</flux:text>
                                        <flux:icon name="folder" class="text-blue-500" />
                                    </div>
                                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $usage['projects_count'] }}</flux:heading>
                                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                        / {{ $limits && $limits->max_projects_owned ? $limits->max_projects_owned : 'Unlimited' }}
                                    </flux:text>
                                    @if($limits && $limits->max_projects_owned)
                                        <div class="mt-2">
                                            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ min(($usage['projects_count'] / $limits->max_projects_owned) * 100, 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </flux:card>

                                <!-- Active Pitches -->
                                <flux:card class="bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300">Active Pitches</flux:text>
                                        <flux:icon name="paper-airplane" class="text-green-500" />
                                    </div>
                                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $usage['active_pitches_count'] }}</flux:heading>
                                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                                        / {{ $limits && $limits->max_active_pitches ? $limits->max_active_pitches : 'Unlimited' }}
                                    </flux:text>
                                    @if($limits && $limits->max_active_pitches)
                                        <div class="mt-2">
                                            <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ min(($usage['active_pitches_count'] / $limits->max_active_pitches) * 100, 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </flux:card>

                                <!-- Visibility Boosts -->
                                @if($user->getMonthlyVisibilityBoosts() > 0)
                                <flux:card class="bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300">Visibility Boosts</flux:text>
                                        <flux:icon name="rocket-launch" class="text-purple-500" />
                                    </div>
                                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $user->getMonthlyVisibilityBoosts() - $user->getRemainingVisibilityBoosts() }}</flux:heading>
                                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">/ {{ $user->getMonthlyVisibilityBoosts() }} this month</flux:text>
                                    <div class="mt-2">
                                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-purple-500 h-2 rounded-full" style="width: {{ (($user->getMonthlyVisibilityBoosts() - $user->getRemainingVisibilityBoosts()) / $user->getMonthlyVisibilityBoosts()) * 100 }}%"></div>
                                        </div>
                                    </div>
                                </flux:card>
                                @endif

                                <!-- Storage -->
                                <flux:card class="bg-gray-50 dark:bg-gray-800">
                                    <div class="flex items-center justify-between mb-2">
                                        <flux:text size="sm" class="font-medium text-gray-700 dark:text-gray-300">Storage</flux:text>
                                        <flux:icon name="circle-stack" class="text-orange-500" />
                                    </div>
                                    <flux:heading size="lg" class="text-gray-900 dark:text-gray-100">{{ $storage['used_gb'] }}GB</flux:heading>
                                    <flux:text size="sm" class="text-gray-600 dark:text-gray-400">/ {{ $storage['total_gb'] }}GB total</flux:text>
                                    <div class="mt-2">
                                        <div class="bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                            <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $storage['percentage'] }}%"></div>
                                        </div>
                                    </div>
                                </flux:card>
                            </div>
                        </div>

                        <!-- Features Tab -->
                        <div x-show="activeTab === 'features'" x-transition>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <flux:heading size="lg">Core Features</flux:heading>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">
                                                {{ ($limits && $limits->max_projects_owned) ? $limits->max_projects_owned . ' Project' . ($limits->max_projects_owned > 1 ? 's' : '') : 'Unlimited Projects' }}
                                            </flux:text>
                                        </div>
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">
                                                {{ ($limits && $limits->max_active_pitches) ? $limits->max_active_pitches . ' Active Pitches' : 'Unlimited Active Pitches' }}
                                            </flux:text>
                                        </div>
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">{{ $storage['total_gb'] }}GB Total Storage</flux:text>
                                        </div>
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">{{ $user->getPlatformCommissionRate() }}% Commission Rate</flux:text>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="space-y-4">
                                    <flux:heading size="lg">Pro Features</flux:heading>
                                    <div class="space-y-3">
                                        @if($user->getReputationMultiplier() > 1.0)
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">{{ $user->getReputationMultiplier() }}× Reputation Multiplier</flux:text>
                                        </div>
                                        @endif
                                        @if($user->getMonthlyVisibilityBoosts() > 0)
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">{{ $user->getMonthlyVisibilityBoosts() }} Visibility Boosts/Month</flux:text>
                                        </div>
                                        @endif
                                        @if($user->getMaxPrivateProjectsMonthly() !== 0)
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">
                                                @if($user->getMaxPrivateProjectsMonthly() === null)
                                                    Unlimited Private Projects
                                                @else
                                                    {{ $user->getMaxPrivateProjectsMonthly() }} Private Projects/Month
                                                @endif
                                            </flux:text>
                                        </div>
                                        @endif
                                        @if($user->hasClientPortalAccess())
                                        <div class="flex items-center">
                                            <flux:icon name="check" class="text-green-500 mr-3" />
                                            <flux:text class="text-gray-700 dark:text-gray-300">Client Portal Access</flux:text>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Analytics Tab -->
                        @if($user->isProPlan())
                        <div x-show="activeTab === 'analytics'" x-transition>
                            <livewire:subscription-dashboard />
                        </div>
                        @endif
                    </div>
                </flux:card>

                <!-- Account Management Panel -->
                <flux:card>
                    <flux:heading size="lg" class="mb-6">Account Management</flux:heading>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Billing & Payment -->
                        <div class="space-y-4">
                            <flux:heading size="base">Billing & Payment</flux:heading>
                            <div class="space-y-3">
                                @if($isSubscribed)
                                <flux:button 
                                    href="{{ route('billing.portal') }}" 
                                    variant="outline" 
                                    icon="credit-card"
                                    class="w-full justify-center">
                                    Update Payment Method
                                </flux:button>
                                <flux:button 
                                    href="{{ route('billing.invoices') }}" 
                                    variant="outline" 
                                    icon="document-text"
                                    class="w-full justify-center">
                                    View Invoices
                                </flux:button>
                                @endif
                            </div>
                        </div>

                        <!-- Plan Changes -->
                        <div class="space-y-4">
                            <flux:heading size="base">Plan Changes</flux:heading>
                            <div class="space-y-3">
                                @if($user->isFreePlan())
                                <flux:button 
                                    href="{{ route('pricing') }}" 
                                    variant="primary" 
                                    icon="arrow-up"
                                    class="w-full justify-center">
                                    Upgrade to Pro
                                </flux:button>
                                @elseif(!$onGracePeriod)
                                <form action="{{ route('subscription.downgrade') }}" method="POST" 
                                      onsubmit="return confirm('Are you sure you want to cancel your subscription? You will lose access to Pro features at the end of your billing period.')">
                                    @csrf
                                    <flux:button 
                                        type="submit" 
                                        variant="danger" 
                                        icon="x-mark"
                                        class="w-full justify-center">
                                        Cancel Subscription
                                    </flux:button>
                                </form>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($isSubscribed && $subscription)
                    <flux:separator class="my-6" />
                    <flux:heading size="base" class="mb-4">Subscription Details</flux:heading>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Plan:</flux:text>
                            <flux:text class="font-medium">{{ $planName }}</flux:text>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Status:</flux:text>
                            <flux:badge :color="$onGracePeriod ? 'amber' : 'green'" size="sm">
                                {{ $onGracePeriod ? 'Cancelling' : 'Active' }}
                            </flux:badge>
                        </div>
                        <div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Started:</flux:text>
                            <flux:text class="font-medium">{{ $subscription->created_at->format('M d, Y') }}</flux:text>
                        </div>
                        @if(!$onGracePeriod && $subscription->asStripeSubscription()->current_period_end)
                        <div>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Next Billing:</flux:text>
                            <flux:text class="font-medium">{{ \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') }}</flux:text>
                        </div>
                        @endif
                    </div>
                    @endif
                </flux:card>

                <!-- Upgrade Options for Free Users -->
                @if($user->isFreePlan())
                <flux:card>
                    <div x-data="{ selectedBillingPeriod: 'monthly' }">
                        <flux:heading size="xl" class="mb-6 text-center">Upgrade Your Plan</flux:heading>
                        
                        <!-- Billing Period Toggle -->
                        <div class="flex justify-center mb-8">
                        <div class="bg-gray-100 dark:bg-gray-800 rounded-xl p-2">
                            <div class="flex relative">
                                <button 
                                    @click="selectedBillingPeriod = 'monthly'"
                                    type="button"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200"
                                    :class="selectedBillingPeriod === 'monthly' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'">
                                    Monthly Billing
                                </button>
                                <button 
                                    @click="selectedBillingPeriod = 'yearly'"
                                    type="button"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 flex items-center"
                                    :class="selectedBillingPeriod === 'yearly' ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200'">
                                    <span>Yearly Billing</span>
                                    <span class="ml-2 text-xs bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 px-2 py-1 rounded-full">Save 17%</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Pro Artist Plan -->
                        <flux:card class="border-2 border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <flux:heading size="lg" class="flex items-center justify-center">
                                    Pro Artist
                                    <span class="ml-2">🔷</span>
                                </flux:heading>
                                <div class="mt-4">
                                    <!-- Monthly Pricing -->
                                    <div x-show="selectedBillingPeriod === 'monthly'" x-transition>
                                        <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">$6.99</flux:heading>
                                        <flux:text class="text-gray-500 dark:text-gray-400">/month</flux:text>
                                    </div>
                                    <!-- Yearly Pricing -->
                                    <div x-show="selectedBillingPeriod === 'yearly'" x-transition>
                                        <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">$69.99</flux:heading>
                                        <flux:text class="text-gray-500 dark:text-gray-400">/year</flux:text>
                                        <div class="mt-2">
                                            <flux:badge color="green" size="sm">
                                                Save $13.89/year
                                            </flux:badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 space-y-4">
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">Unlimited Projects & Pitches</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">50GB Total Storage</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">8% Commission Rate</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">Custom License Templates</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">4 Visibility Boosts/Month</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">2 Private Projects/Month</flux:text>
                                </div>
                            </div>
                            <div class="mt-6">
                                <form action="{{ route('subscription.upgrade') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="pro">
                                    <input type="hidden" name="tier" value="artist">
                                    <input type="hidden" name="billing_period" x-model="selectedBillingPeriod">
                                    <flux:button type="submit" variant="primary" class="w-full justify-center">
                                        Upgrade to Pro Artist
                                    </flux:button>
                                </form>
                            </div>
                        </flux:card>

                        <!-- Pro Engineer Plan -->
                        <flux:card class="border-2 border-gray-200 dark:border-gray-700">
                            <div class="text-center">
                                <flux:heading size="lg" class="flex items-center justify-center">
                                    Pro Engineer
                                    <span class="ml-2">🔶</span>
                                </flux:heading>
                                <div class="mt-4">
                                    <!-- Monthly Pricing -->
                                    <div x-show="selectedBillingPeriod === 'monthly'" x-transition>
                                        <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">$9.99</flux:heading>
                                        <flux:text class="text-gray-500 dark:text-gray-400">/month</flux:text>
                                    </div>
                                    <!-- Yearly Pricing -->
                                    <div x-show="selectedBillingPeriod === 'yearly'" x-transition>
                                        <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">$99.99</flux:heading>
                                        <flux:text class="text-gray-500 dark:text-gray-400">/year</flux:text>
                                        <div class="mt-2">
                                            <flux:badge color="green" size="sm">
                                                Save $19.89/year
                                            </flux:badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-6 space-y-4">
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">Unlimited Projects & Pitches</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">200GB Total Storage</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">6% Commission Rate</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">1.25× Reputation Multiplier</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">Unlimited Private Projects</flux:text>
                                </div>
                                <div class="flex items-start">
                                    <flux:icon name="check" class="text-green-500 mt-0.5 mr-3 flex-shrink-0" />
                                    <flux:text size="sm" class="text-gray-700 dark:text-gray-300">Client Portal Access</flux:text>
                                </div>
                            </div>
                            <div class="mt-6">
                                <form action="{{ route('subscription.upgrade') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="pro">
                                    <input type="hidden" name="tier" value="engineer">
                                    <input type="hidden" name="billing_period" x-model="selectedBillingPeriod">
                                    <flux:button type="submit" variant="primary" class="w-full justify-center bg-purple-600 hover:bg-purple-700">
                                        Upgrade to Pro Engineer
                                    </flux:button>
                                </form>
                            </div>
                        </flux:card>
                    </div>
                    
                        <!-- Feature Comparison Link -->
                        <div class="mt-6 text-center">
                            <flux:button href="{{ route('pricing') }}" variant="ghost" icon="arrow-right">
                                View complete feature comparison
                            </flux:button>
                        </div>
                    </div>
                </flux:card>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Visibility Boost Modal -->
<flux:modal name="boost-modal" class="md:max-w-md">
    <div class="p-6">
        <flux:heading size="lg" class="mb-4">Boost Your Visibility</flux:heading>
        <flux:text class="text-gray-600 dark:text-gray-400 mb-6">Choose what you'd like to boost for the next 72 hours.</flux:text>
        
        <div class="space-y-3">
            <flux:button 
                @click="createBoost('profile')"
                variant="outline" 
                icon="user"
                class="w-full justify-center p-4 bg-blue-50 hover:bg-blue-100 dark:bg-blue-900/30 border-blue-200 dark:border-blue-800">
                <span class="font-medium text-blue-900 dark:text-blue-100">Boost Profile</span>
            </flux:button>
            <flux:button 
                @click="createBoost('project')"
                variant="outline" 
                icon="folder"
                class="w-full justify-center p-4 bg-purple-50 hover:bg-purple-100 dark:bg-purple-900/30 border-purple-200 dark:border-purple-800">
                <span class="font-medium text-purple-900 dark:text-purple-100">Boost Latest Project</span>
            </flux:button>
        </div>
        
        <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
            <flux:modal.close>
                <flux:button variant="ghost" class="w-full justify-center">
                    Cancel
                </flux:button>
            </flux:modal.close>
        </div>
    </div>
</flux:modal>

<script>
    // Boost modal functions
    function createBoost(type) {
        // Close modal
        document.querySelector('[x-data]').__x.$dispatch('close-modal', 'boost-modal');
        
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
</script>

</x-layouts.app-sidebar>