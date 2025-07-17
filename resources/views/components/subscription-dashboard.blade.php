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

<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="bg-white/20 p-2 rounded-lg">
                    <i class="fas fa-crown text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-white font-semibold text-lg">{{ $planName }}</h3>
                    <p class="text-blue-100 text-sm">Your subscription plan</p>
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
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-green-600 text-sm font-medium">Total Earnings</p>
                        <p class="text-green-900 text-xl font-bold">${{ number_format($totalEarnings, 2) }}</p>
                    </div>
                    <i class="fas fa-dollar-sign text-green-500 text-lg"></i>
                </div>
            </div>

            <!-- Commission Rate -->
            <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-blue-600 text-sm font-medium">Commission Rate</p>
                        <p class="text-blue-900 text-xl font-bold">{{ $user->getPlatformCommissionRate() }}%</p>
                    </div>
                    <i class="fas fa-percentage text-blue-500 text-lg"></i>
                </div>
            </div>

            <!-- Reputation Score -->
            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-yellow-600 text-sm font-medium">Reputation</p>
                        <p class="text-yellow-900 text-xl font-bold">{{ number_format($reputationData['final_reputation'], 1) }}</p>
                    </div>
                    <span class="text-lg">{{ $reputationData['tier']['badge'] }}</span>
                </div>
            </div>

            <!-- Commission Savings -->
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-purple-600 text-sm font-medium">Savings</p>
                        <p class="text-purple-900 text-xl font-bold">${{ number_format($commissionSavings, 2) }}</p>
                    </div>
                    <i class="fas fa-piggy-bank text-purple-500 text-lg"></i>
                </div>
            </div>
        </div>

        <!-- Monthly Usage -->
        <div class="border-t pt-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-chart-bar text-blue-500 mr-2"></i>
                Monthly Usage - {{ $usageSummary['month_year'] }}
            </h4>
            
            <div class="space-y-4">
                <!-- Visibility Boosts -->
                @if($user->getMonthlyVisibilityBoosts() > 0)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-100 p-2 rounded-lg">
                            <i class="fas fa-rocket text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Visibility Boosts</p>
                            <p class="text-sm text-gray-600">Boost your content visibility</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">
                            {{ $usageSummary['visibility_boosts']['used'] }} / {{ $usageSummary['visibility_boosts']['limit'] }}
                        </p>
                        <p class="text-sm text-gray-600">{{ $remainingVisibilityBoosts }} remaining</p>
                    </div>
                </div>
                @endif

                <!-- Private Projects -->
                @if($user->getMaxPrivateProjectsMonthly() !== 0)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="bg-red-100 p-2 rounded-lg">
                            <i class="fas fa-lock text-red-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">Private Projects</p>
                            <p class="text-sm text-gray-600">Create private projects</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">
                            {{ $usageSummary['private_projects']['created'] }}
                            @if($user->getMaxPrivateProjectsMonthly() !== null)
                                / {{ $user->getMaxPrivateProjectsMonthly() }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-600">
                            @if($remainingPrivateProjects === null)
                                Unlimited
                            @else
                                {{ $remainingPrivateProjects }} remaining
                            @endif
                        </p>
                    </div>
                </div>
                @endif

                <!-- License Templates -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-100 p-2 rounded-lg">
                            <i class="fas fa-file-contract text-green-600"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900">License Templates</p>
                            <p class="text-sm text-gray-600">Custom licensing options</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900">
                            {{ $usageSummary['license_templates']['total_templates'] }}
                            @if($user->getMaxLicenseTemplates() !== null)
                                / {{ $user->getMaxLicenseTemplates() }}
                            @endif
                        </p>
                        <p class="text-sm text-gray-600">
                            @if($user->getMaxLicenseTemplates() === null)
                                Unlimited
                            @else
                                {{ max(0, $user->getMaxLicenseTemplates() - $usageSummary['license_templates']['total_templates']) }} slots available
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subscription Benefits -->
        <div class="border-t pt-6">
            <h4 class="font-semibold text-gray-900 mb-4 flex items-center">
                <i class="fas fa-star text-yellow-500 mr-2"></i>
                Your Benefits
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Storage -->
                <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg">
                    <i class="fas fa-hdd text-blue-600"></i>
                    <div>
                        <p class="font-medium text-blue-900">{{ $user->getStorageLimitGB() }}GB Storage</p>
                        <p class="text-sm text-blue-700">Total storage</p>
                    </div>
                </div>

                <!-- Reputation Multiplier -->
                @if($user->getReputationMultiplier() > 1.0)
                <div class="flex items-center space-x-3 p-3 bg-yellow-50 rounded-lg">
                    <i class="fas fa-chart-line text-yellow-600"></i>
                    <div>
                        <p class="font-medium text-yellow-900">{{ $user->getReputationMultiplier() }}× Reputation</p>
                        <p class="text-sm text-yellow-700">Boost multiplier</p>
                    </div>
                </div>
                @endif

                <!-- Client Portal -->
                @if($user->hasClientPortalAccess())
                <div class="flex items-center space-x-3 p-3 bg-purple-50 rounded-lg">
                    <i class="fas fa-users text-purple-600"></i>
                    <div>
                        <p class="font-medium text-purple-900">Client Portal</p>
                        <p class="text-sm text-purple-700">Advanced client tools</p>
                    </div>
                </div>
                @endif

                <!-- Early Access -->
                @if($user->getChallengeEarlyAccessHours() > 0)
                <div class="flex items-center space-x-3 p-3 bg-indigo-50 rounded-lg">
                    <i class="fas fa-clock text-indigo-600"></i>
                    <div>
                        <p class="font-medium text-indigo-900">{{ $user->getChallengeEarlyAccessHours() }}h Early Access</p>
                        <p class="text-sm text-indigo-700">To challenges</p>
                    </div>
                </div>
                @endif

                <!-- Judge Access -->
                @if($user->hasJudgeAccess())
                <div class="flex items-center space-x-3 p-3 bg-red-50 rounded-lg">
                    <i class="fas fa-gavel text-red-600"></i>
                    <div>
                        <p class="font-medium text-red-900">Judge Access</p>
                        <p class="text-sm text-red-700">Contest judging privileges</p>
                    </div>
                </div>
                @endif

                <!-- Support Level -->
                @if($user->getSupportSlaHours())
                <div class="flex items-center space-x-3 p-3 bg-green-50 rounded-lg">
                    <i class="fas fa-headset text-green-600"></i>
                    <div>
                        <p class="font-medium text-green-900">{{ $user->getSupportSlaHours() }}h Support SLA</p>
                        <p class="text-sm text-green-700">Priority assistance</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="border-t pt-6">
            <div class="flex flex-col sm:flex-row gap-3">
                @if(!$user->isProPlan())
                    <a href="{{ route('subscription.index') }}" 
                       class="flex-1 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-center py-3 px-4 rounded-lg font-medium transition-all duration-200 hover:scale-105 shadow-md hover:shadow-lg">
                        <i class="fas fa-arrow-up mr-2"></i>
                        Upgrade to Pro
                    </a>
                @else
                    <a href="{{ route('subscription.index') }}" 
                       class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 text-center py-3 px-4 rounded-lg font-medium transition-colors duration-200">
                        <i class="fas fa-cog mr-2"></i>
                        Manage Subscription
                    </a>
                @endif
                
                <a href="{{ route('profile.username', ['username' => auth()->user()->username]) }}" 
                   class="flex-1 border border-gray-300 hover:border-gray-400 text-gray-700 text-center py-3 px-4 rounded-lg font-medium transition-colors duration-200">
                    <i class="fas fa-user mr-2"></i>
                    View Profile
                </a>
            </div>
        </div>
    </div>
</div> 