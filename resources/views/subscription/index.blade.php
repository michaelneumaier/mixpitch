<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Enhanced Subscription Dashboard -->
            <div class="mb-8">
                <livewire:subscription-dashboard />
            </div>

            <!-- Current Plan Section -->
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Current Plan</h3>
                        <div class="mt-2 flex items-center">
                            @php
                                $isSubscribed = $user->subscribed('default');
                                $onGracePeriod = $isSubscribed && $user->subscription('default')->onGracePeriod();
                                $subscription = $user->subscription('default');
                                $planName = $user->getSubscriptionDisplayName();
                                $billingPeriod = $user->getBillingPeriodDisplayName();
                                $formattedPrice = $user->getFormattedSubscriptionPrice();
                                $yearlySavings = $user->getYearlySavings();
                            @endphp
                            
                            <x-user-badge :user="$user" showPlan="true" size="lg" />
                            
                            @if($onGracePeriod)
                                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Cancelling {{ $subscription->ends_at->format('M d, Y') }}
                                </span>
                            @endif
                            
                            @if($user->plan_started_at)
                                <span class="ml-2 text-sm text-gray-500">
                                    Since {{ $user->plan_started_at->format('M d, Y') }}
                                </span>
                            @endif

                            @if($yearlySavings)
                                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <i class="fas fa-piggy-bank mr-1"></i>
                                    Saving ${{ number_format($yearlySavings, 2) }}/year
                                </span>
                            @endif
                        </div>
                        
                        @if($isSubscribed && $subscription)
                            <div class="mt-2 text-sm text-gray-600">
                                <div class="flex items-center space-x-4">
                                    <span>{{ $formattedPrice }} ({{ $billingPeriod }})</span>
                                    @if($onGracePeriod)
                                        <span class="text-yellow-600">• Subscription set to cancel on {{ $subscription->ends_at->format('M d, Y') }}</span>
                                    @else
                                        @php
                                            $nextBilling = $user->getNextBillingDate();
                                        @endphp
                                        @if($nextBilling)
                                            <span>• Next billing: {{ $nextBilling->format('M d, Y') }}</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                    
                    <div class="flex items-center space-x-3">
                        @if($user->isFreePlan())
                            <a href="{{ route('pricing') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Upgrade to Pro
                            </a>
                        @elseif($onGracePeriod)
                            <form action="{{ route('subscription.resume') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Resume Subscription
                                </button>
                            </form>
                        @elseif($isSubscribed)
                            <form action="{{ route('subscription.downgrade') }}" method="POST" class="inline" 
                                  onsubmit="return confirm('Are you sure you want to cancel your subscription? You will lose access to Pro features at the end of your billing period.')">
                                @csrf
                                <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Cancel Subscription
                                </button>
                            </form>
                        @endif
                        
                        @if($isSubscribed)
                            <a href="{{ route('billing.portal') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                                Manage Billing
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Usage Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <!-- Projects Usage -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-folder text-blue-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Projects</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ $usage['projects_count'] }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <span class="text-gray-500">
                                            / {{ $limits && $limits->max_projects_owned ? $limits->max_projects_owned : 'Unlimited' }}
                                        </span>
                                    </div>
                                </dd>
                            </dl>
                            @if($limits && $limits->max_projects_owned)
                                <div class="mt-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="text-gray-500">Usage</div>
                                        <div class="text-gray-900">{{ number_format(($usage['projects_count'] / $limits->max_projects_owned) * 100, 1) }}%</div>
                                    </div>
                                    <div class="mt-1 relative">
                                        <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                            <div style="width:{{ min(($usage['projects_count'] / $limits->max_projects_owned) * 100, 100) }}%"
                                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $usage['projects_count'] >= $limits->max_projects_owned ? 'bg-red-500' : 'bg-blue-500' }}"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Active Pitches Usage -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-paper-plane text-green-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Pitches</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ $usage['active_pitches_count'] }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <span class="text-gray-500">
                                            / {{ $limits && $limits->max_active_pitches ? $limits->max_active_pitches : 'Unlimited' }}
                                        </span>
                                    </div>
                                </dd>
                            </dl>
                            @if($limits && $limits->max_active_pitches)
                                <div class="mt-3">
                                    <div class="flex items-center justify-between text-sm">
                                        <div class="text-gray-500">Usage</div>
                                        <div class="text-gray-900">{{ number_format(($usage['active_pitches_count'] / $limits->max_active_pitches) * 100, 1) }}%</div>
                                    </div>
                                    <div class="mt-1 relative">
                                        <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                            <div style="width:{{ min(($usage['active_pitches_count'] / $limits->max_active_pitches) * 100, 100) }}%"
                                                 class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $usage['active_pitches_count'] >= $limits->max_active_pitches ? 'bg-red-500' : 'bg-green-500' }}"></div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Storage Usage -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-hdd text-purple-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Storage per Project</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ $user->getStoragePerProjectGB() }}GB
                                    </div>
                                </dd>
                            </dl>
                            <div class="mt-3 text-sm text-gray-600">
                                File retention: {{ $user->getFileRetentionDays() }} days
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commission Rate -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-percentage text-orange-500 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Commission Rate</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ $user->getPlatformCommissionRate() }}%
                                    </div>
                                </dd>
                            </dl>
                            @if($user->isProPlan())
                                <div class="mt-2 text-sm text-green-600 font-medium">
                                    Saved: ${{ number_format($user->getCommissionSavings(), 2) }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Features Usage -->
            @if($user->isProPlan())
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Features Usage</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Visibility Boosts -->
                        @if($user->getMonthlyVisibilityBoosts() > 0)
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-rocket text-blue-600 text-xl"></i>
                                    <div>
                                        <p class="font-medium text-gray-900">Visibility Boosts</p>
                                        <p class="text-sm text-gray-600">{{ $user->getRemainingVisibilityBoosts() }} / {{ $user->getMonthlyVisibilityBoosts() }} remaining</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Private Projects -->
                        @if($user->getMaxPrivateProjectsMonthly() !== 0)
                            <div class="flex items-center justify-between p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex items-center space-x-3">
                                    <i class="fas fa-lock text-red-600 text-xl"></i>
                                    <div>
                                        <p class="font-medium text-gray-900">Private Projects</p>
                                        <p class="text-sm text-gray-600">
                                            @if($user->getRemainingPrivateProjects() === null)
                                                Unlimited
                                            @else
                                                {{ $user->getRemainingPrivateProjects() }} remaining this month
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- License Templates -->
                        <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg border border-green-200">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-file-contract text-green-600 text-xl"></i>
                                <div>
                                    <p class="font-medium text-gray-900">License Templates</p>
                                    <p class="text-sm text-gray-600">
                                        @if($user->getMaxLicenseTemplates() === null)
                                            Unlimited custom templates
                                        @else
                                            {{ $user->getMaxLicenseTemplates() }} templates allowed
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Plan Features -->
            @if($limits)
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Your Plan Features</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">
                            {{ $limits->max_projects_owned ? $limits->max_projects_owned . ' Project' . ($limits->max_projects_owned > 1 ? 's' : '') : 'Unlimited Projects' }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">
                            {{ $limits->max_active_pitches ? $limits->max_active_pitches . ' Active Pitches' : 'Unlimited Active Pitches' }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">{{ $user->getStoragePerProjectGB() }}GB Storage per Project</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">{{ $user->getFileRetentionDays() }} Days File Retention</span>
                    </div>
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">{{ $user->getPlatformCommissionRate() }}% Commission Rate</span>
                    </div>
                    @if($user->getReputationMultiplier() > 1.0)
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">{{ $user->getReputationMultiplier() }}× Reputation Multiplier</span>
                    </div>
                    @endif
                    @if($user->hasClientPortalAccess())
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">Client Portal Access</span>
                    </div>
                    @endif
                    @if($user->getChallengeEarlyAccessHours() > 0)
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">{{ $user->getChallengeEarlyAccessHours() }}h Early Access</span>
                    </div>
                    @endif
                    @if($user->hasJudgeAccess())
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">Judge Access</span>
                    </div>
                    @endif
                    @if($user->getSupportSlaHours())
                    <div class="flex items-center">
                        <i class="fas fa-check text-green-500 mr-2"></i>
                        <span class="text-sm text-gray-700">{{ $user->getSupportSlaHours() }}h Support SLA</span>
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Upgrade Options -->
            @if($user->isFreePlan())
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Upgrade Your Plan</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Pro Artist Plan -->
                    <div class="border border-gray-200 rounded-lg p-6">
                        <div class="text-center">
                            <h4 class="text-xl font-semibold text-gray-900 flex items-center justify-center">
                                Pro Artist
                                <span class="ml-2">🔷</span>
                            </h4>
                            <div class="mt-4">
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-3xl font-bold text-gray-900">$6.99</span>
                                        <span class="text-base font-medium text-gray-500">/month</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">$69.99/year</span>
                                        <span class="ml-2 inline-block px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Save $13.89</span>
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
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Email Support (48h SLA)</span>
                            </li>
                        </ul>
                        <div class="mt-6">
                            <form action="{{ route('subscription.upgrade') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="tier" value="artist">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Upgrade to Pro Artist
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
                                <div class="space-y-2">
                                    <div>
                                        <span class="text-3xl font-bold text-gray-900">$9.99</span>
                                        <span class="text-base font-medium text-gray-500">/month</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">$99.99/year</span>
                                        <span class="ml-2 inline-block px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Save $19.89</span>
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
                            <li class="flex items-start">
                                <i class="fas fa-check text-green-500 mt-0.5 mr-3"></i>
                                <span class="text-sm text-gray-700">Email & Chat Support (24h SLA)</span>
                            </li>
                        </ul>
                        <div class="mt-6">
                            <form action="{{ route('subscription.upgrade') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="tier" value="engineer">
                                <button type="submit" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-4 rounded">
                                    Upgrade to Pro Engineer
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

            <!-- Current Subscription Management -->
            @if($isSubscribed)
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Subscription Details</h3>
                
                @if($onGracePeriod)
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                            <div>
                                <h4 class="font-medium text-yellow-800">Subscription Ending</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Your subscription will end on {{ $subscription->ends_at->format('M d, Y') }}. 
                                    You'll continue to have access to Pro features until then.
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <form action="{{ route('subscription.resume') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded">
                                    Resume Subscription
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Plan Information</h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Plan:</span>
                                <span class="font-medium">{{ $planName }}</span>
                            </div>
                            @if($subscription)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-medium {{ $onGracePeriod ? 'text-yellow-600' : 'text-green-600' }}">
                                    {{ $onGracePeriod ? 'Cancelling' : 'Active' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Started:</span>
                                <span class="font-medium">{{ $subscription->created_at->format('M d, Y') }}</span>
                            </div>
                            @if(!$onGracePeriod)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Next Billing:</span>
                                <span class="font-medium">
                                    {{ $subscription->asStripeSubscription()->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') : 'Unknown' }}
                                </span>
                            </div>
                            @endif
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <h4 class="font-medium text-gray-900 mb-2">Billing Management</h4>
                        <div class="space-y-3">
                            <a href="{{ route('billing.portal') }}" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded">
                                <i class="fas fa-credit-card mr-2"></i>
                                Update Payment Method
                            </a>
                            <a href="{{ route('billing.invoices') }}" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded">
                                <i class="fas fa-file-invoice mr-2"></i>
                                View Invoices
                            </a>
                            @if(!$onGracePeriod)
                            <form action="{{ route('subscription.downgrade') }}" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to cancel your subscription? You will lose access to Pro features at the end of your billing period.')">
                                @csrf
                                <button type="submit" class="w-full bg-red-100 hover:bg-red-200 text-red-700 font-medium py-2 px-4 rounded">
                                    <i class="fas fa-times mr-2"></i>
                                    Cancel Subscription
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout> 