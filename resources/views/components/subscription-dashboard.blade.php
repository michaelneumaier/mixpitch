@props(['user'])

@php
    $subscription = $user->getSubscriptionLimits();
    $monthlyLimits = $user->currentMonthLimits();
    $usageSummary = $monthlyLimits->getUsageSummary();
    $reputationData = $user->getReputationWithMultiplier();
    $totalEarnings = $user->getTotalEarnings();
    $commissionSavings = $user->getCommissionSavings();
    $remainingPrivateProjects = $user->getRemainingPrivateProjects();
    $remainingVisibilityBoosts = $user->getRemainingVisibilityBoosts();
    
    $planName = ucfirst($user->subscription_plan);
    if ($user->subscription_tier !== 'basic') {
        $planName .= ' ' . ucfirst($user->subscription_tier);
    }
@endphp

<flux:card class="overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-white/20 p-2 rounded-lg">
                    <flux:icon name="sparkles" class="w-6 h-6 text-white" />
                </div>
                <div>
                    <flux:heading size="lg" class="text-white">{{ $planName }}</flux:heading>
                    <flux:text size="sm" class="text-blue-100">Your subscription plan</flux:text>
                </div>
            </div>
            @if($user->getUserBadge())
                <span class="text-2xl">{{ $user->getUserBadge() }}</span>
            @endif
        </div>
    </div>

    <!-- Content -->
    <div class="p-6 space-y-6">
        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Earnings -->
            <div class="bg-green-50 dark:bg-green-950/50 rounded-lg p-4 border border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-green-600 dark:text-green-400 font-medium">Total Earnings</flux:text>
                        <flux:text class="text-green-900 dark:text-green-100 text-xl font-bold">${{ number_format($totalEarnings, 2) }}</flux:text>
                    </div>
                    <flux:icon name="currency-dollar" class="w-6 h-6 text-green-500" />
                </div>
            </div>

            <!-- Commission Rate -->
            <div class="bg-blue-50 dark:bg-blue-950/50 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-blue-600 dark:text-blue-400 font-medium">Commission Rate</flux:text>
                        <flux:text class="text-blue-900 dark:text-blue-100 text-xl font-bold">{{ $user->getPlatformCommissionRate() }}%</flux:text>
                    </div>
                    <flux:icon name="percent-badge" class="w-6 h-6 text-blue-500" />
                </div>
            </div>

            <!-- Reputation Score -->
            <div class="bg-yellow-50 dark:bg-yellow-950/50 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-yellow-600 dark:text-yellow-400 font-medium">Reputation</flux:text>
                        <flux:text class="text-yellow-900 dark:text-yellow-100 text-xl font-bold">{{ number_format($reputationData['final_reputation'], 1) }}</flux:text>
                    </div>
                    <span class="text-lg">{{ $reputationData['tier']['badge'] }}</span>
                </div>
            </div>

            <!-- Commission Savings -->
            <div class="bg-purple-50 dark:bg-purple-950/50 rounded-lg p-4 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center justify-between">
                    <div>
                        <flux:text size="sm" class="text-purple-600 dark:text-purple-400 font-medium">Savings</flux:text>
                        <flux:text class="text-purple-900 dark:text-purple-100 text-xl font-bold">${{ number_format($commissionSavings, 2) }}</flux:text>
                    </div>
                    <flux:icon name="banknotes" class="w-6 h-6 text-purple-500" />
                </div>
            </div>
        </div>

        <!-- Monthly Usage -->
        <div class="border-t pt-6">
            <flux:heading size="base" class="mb-4 flex items-center">
                <flux:icon name="chart-bar" class="w-5 h-5 text-blue-500 mr-2" />
                Monthly Usage - {{ $usageSummary['month_year'] }}
            </flux:heading>
            
            <div class="space-y-4">
                <!-- Visibility Boosts -->
                @if($user->getMonthlyVisibilityBoosts() > 0)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 dark:bg-blue-900/30 p-2 rounded-lg">
                            <flux:icon name="rocket-launch" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium text-gray-900 dark:text-gray-100">Visibility Boosts</flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Boost your content visibility</flux:text>
                        </div>
                    </div>
                    <div class="text-right">
                        <flux:text class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $usageSummary['visibility_boosts']['used'] }} / {{ $usageSummary['visibility_boosts']['limit'] }}
                        </flux:text>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">{{ $remainingVisibilityBoosts }} remaining</flux:text>
                    </div>
                </div>
                @endif

                <!-- Private Projects -->
                @if($user->getMaxPrivateProjectsMonthly() !== 0)
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="bg-red-100 dark:bg-red-900/30 p-2 rounded-lg">
                            <flux:icon name="lock-closed" class="w-5 h-5 text-red-600 dark:text-red-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium text-gray-900 dark:text-gray-100">Private Projects</flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Create private projects</flux:text>
                        </div>
                    </div>
                    <div class="text-right">
                        <flux:text class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $usageSummary['private_projects']['created'] }}
                            @if($user->getMaxPrivateProjectsMonthly() !== null)
                                / {{ $user->getMaxPrivateProjectsMonthly() }}
                            @endif
                        </flux:text>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            @if($remainingPrivateProjects === null)
                                Unlimited
                            @else
                                {{ $remainingPrivateProjects }} remaining
                            @endif
                        </flux:text>
                    </div>
                </div>
                @endif

                <!-- License Templates -->
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded-lg">
                            <flux:icon name="document-text" class="w-5 h-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <flux:text class="font-medium text-gray-900 dark:text-gray-100">License Templates</flux:text>
                            <flux:text size="sm" class="text-gray-600 dark:text-gray-400">Custom licensing options</flux:text>
                        </div>
                    </div>
                    <div class="text-right">
                        <flux:text class="font-semibold text-gray-900 dark:text-gray-100">
                            {{ $usageSummary['license_templates']['total_templates'] }}
                            @if($user->getMaxLicenseTemplates() !== null)
                                / {{ $user->getMaxLicenseTemplates() }}
                            @endif
                        </flux:text>
                        <flux:text size="sm" class="text-gray-600 dark:text-gray-400">
                            @if($user->getMaxLicenseTemplates() === null)
                                Unlimited
                            @else
                                {{ max(0, $user->getMaxLicenseTemplates() - $usageSummary['license_templates']['total_templates']) }} slots available
                            @endif
                        </flux:text>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Benefits -->
        <div class="border-t pt-6">
            <flux:heading size="base" class="mb-4 flex items-center">
                <flux:icon name="star" class="w-5 h-5 text-yellow-500 mr-2" />
                Your Benefits
            </flux:heading>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Storage -->
                <div class="flex items-center space-x-3 p-3 bg-blue-50 dark:bg-blue-950/50 rounded-lg">
                    <flux:icon name="circle-stack" class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    <div>
                        <flux:text class="font-medium text-blue-900 dark:text-blue-100">{{ $user->getStorageLimitGB() }}GB Storage</flux:text>
                        <flux:text size="sm" class="text-blue-700 dark:text-blue-300">Total storage</flux:text>
                    </div>
                </div>

                <!-- Reputation Multiplier -->
                @if($user->getReputationMultiplier() > 1.0)
                <div class="flex items-center space-x-3 p-3 bg-yellow-50 dark:bg-yellow-950/50 rounded-lg">
                    <flux:icon name="chart-bar" class="w-5 h-5 text-yellow-600 dark:text-yellow-400" />
                    <div>
                        <flux:text class="font-medium text-yellow-900 dark:text-yellow-100">{{ $user->getReputationMultiplier() }}× Reputation</flux:text>
                        <flux:text size="sm" class="text-yellow-700 dark:text-yellow-300">Boost multiplier</flux:text>
                    </div>
                </div>
                @endif

                <!-- Client Portal -->
                @if($user->hasClientPortalAccess())
                <div class="flex items-center space-x-3 p-3 bg-purple-50 dark:bg-purple-950/50 rounded-lg">
                    <flux:icon name="users" class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    <div>
                        <flux:text class="font-medium text-purple-900 dark:text-purple-100">Client Portal</flux:text>
                        <flux:text size="sm" class="text-purple-700 dark:text-purple-300">Advanced client tools</flux:text>
                    </div>
                </div>
                @endif

                <!-- Early Access -->
                @if($user->getChallengeEarlyAccessHours() > 0)
                <div class="flex items-center space-x-3 p-3 bg-indigo-50 dark:bg-indigo-950/50 rounded-lg">
                    <flux:icon name="clock" class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                    <div>
                        <flux:text class="font-medium text-indigo-900 dark:text-indigo-100">{{ $user->getChallengeEarlyAccessHours() }}h Early Access</flux:text>
                        <flux:text size="sm" class="text-indigo-700 dark:text-indigo-300">To challenges</flux:text>
                    </div>
                </div>
                @endif

                <!-- Judge Access -->
                @if($user->hasJudgeAccess())
                <div class="flex items-center space-x-3 p-3 bg-red-50 dark:bg-red-950/50 rounded-lg">
                    <flux:icon name="scale" class="w-5 h-5 text-red-600 dark:text-red-400" />
                    <div>
                        <flux:text class="font-medium text-red-900 dark:text-red-100">Judge Access</flux:text>
                        <flux:text size="sm" class="text-red-700 dark:text-red-300">Contest judging privileges</flux:text>
                    </div>
                </div>
                @endif

                <!-- Support Level -->
                @if($user->getSupportSlaHours())
                <div class="flex items-center space-x-3 p-3 bg-green-50 dark:bg-green-950/50 rounded-lg">
                    <flux:icon name="chat-bubble-left-ellipsis" class="w-5 h-5 text-green-600 dark:text-green-400" />
                    <div>
                        <flux:text class="font-medium text-green-900 dark:text-green-100">{{ $user->getSupportSlaHours() }}h Support SLA</flux:text>
                        <flux:text size="sm" class="text-green-700 dark:text-green-300">Priority assistance</flux:text>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="border-t pt-6">
            <div class="flex flex-col sm:flex-row gap-3">
                @if(!$user->isProPlan())
                    <flux:button 
                        href="{{ route('subscription.index') }}" 
                        wire:navigate
                        variant="filled"
                        icon="arrow-up"
                        class="flex-1 justify-center bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 !text-white">
                        Upgrade to Pro
                    </flux:button>
                @else
                    <flux:button 
                        href="{{ route('subscription.index') }}" 
                        wire:navigate
                        variant="outline"
                        icon="cog-6-tooth"
                        class="flex-1 justify-center">
                        Manage Subscription
                    </flux:button>
                @endif
                
                <flux:button 
                    href="{{ route('profile.username', ['username' => auth()->user()->username]) }}" 
                    wire:navigate
                    variant="outline"
                    icon="user"
                    class="flex-1 justify-center">
                    View Profile
                </flux:button>
            </div>
        </div>
    </div>
</flux:card> 