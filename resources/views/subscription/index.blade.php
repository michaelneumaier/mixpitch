<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Subscription Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                            @endphp
                            
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $user->isProPlan() ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst($user->subscription_plan) }}
                                {{ $user->subscription_tier !== 'basic' ? ' - ' . ucfirst($user->subscription_tier) : '' }}
                            </span>
                            
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
                        </div>
                        
                        @if($isSubscribed && $subscription)
                            <div class="mt-2 text-sm text-gray-600">
                                @if($onGracePeriod)
                                    <p>Your subscription is set to cancel on {{ $subscription->ends_at->format('M d, Y') }}.</p>
                                @else
                                    <p>Next billing: {{ $subscription->asStripeSubscription()->current_period_end ? \Carbon\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M d, Y') : 'Unknown' }}</p>
                                @endif
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <!-- Projects Usage -->
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
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
                            <svg class="h-8 w-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
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

                <!-- Monthly Pitches (Pro Engineer only) -->
                @if($limits && $limits->max_monthly_pitches)
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Monthly Pitches</dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold text-gray-900">
                                        {{ $usage['monthly_pitches_used'] }}
                                    </div>
                                    <div class="ml-2 flex items-baseline text-sm font-semibold">
                                        <span class="text-gray-500">
                                            / {{ $limits->max_monthly_pitches }}
                                        </span>
                                    </div>
                                </dd>
                            </dl>
                            <div class="mt-3">
                                <div class="flex items-center justify-between text-sm">
                                    <div class="text-gray-500">Usage</div>
                                    <div class="text-gray-900">{{ number_format(($usage['monthly_pitches_used'] / $limits->max_monthly_pitches) * 100, 1) }}%</div>
                                </div>
                                <div class="mt-1 relative">
                                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                                        <div style="width:{{ min(($usage['monthly_pitches_used'] / $limits->max_monthly_pitches) * 100, 100) }}%"
                                             class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center {{ $usage['monthly_pitches_used'] >= $limits->max_monthly_pitches ? 'bg-red-500' : 'bg-purple-500' }}"></div>
                                    </div>
                                </div>
                                <div class="mt-2 text-xs text-gray-500">
                                    Resets {{ $user->monthly_pitch_reset_date ? $user->monthly_pitch_reset_date->format('M d, Y') : 'next month' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Plan Features -->
            @if($limits)
            <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Your Plan Features</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">
                            {{ $limits->max_projects_owned ? $limits->max_projects_owned . ' Project' . ($limits->max_projects_owned > 1 ? 's' : '') : 'Unlimited Projects' }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">
                            {{ $limits->max_active_pitches ? $limits->max_active_pitches . ' Active Pitches' : 'Unlimited Active Pitches' }}
                        </span>
                    </div>
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">{{ $limits->storage_per_project_mb }}MB Storage per Project</span>
                    </div>
                    @if($limits->priority_support)
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">Priority Support</span>
                    </div>
                    @endif
                    @if($limits->custom_portfolio)
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">Custom Portfolio</span>
                    </div>
                    @endif
                    @if($limits->max_monthly_pitches)
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span class="text-sm text-gray-700">{{ $limits->max_monthly_pitches }} Monthly Pitches</span>
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
                            <h4 class="text-xl font-semibold text-gray-900">Pro Artist</h4>
                            <div class="mt-4">
                                <span class="text-3xl font-bold text-gray-900">$29</span>
                                <span class="text-base font-medium text-gray-500">/month</span>
                            </div>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Unlimited Projects</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Unlimited Active Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">500MB Storage per Project</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Priority Support</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Custom Portfolio</span>
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
                            <h4 class="text-xl font-semibold text-gray-900">Pro Engineer</h4>
                            <div class="mt-4">
                                <span class="text-3xl font-bold text-gray-900">$19</span>
                                <span class="text-base font-medium text-gray-500">/month</span>
                            </div>
                        </div>
                        <ul class="mt-6 space-y-4">
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Unlimited Projects</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Unlimited Active Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">5 Monthly Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">500MB Storage per Project</span>
                            </li>
                            <li class="flex items-start">
                                <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                <span class="ml-3 text-sm text-gray-700">Priority Support</span>
                            </li>
                        </ul>
                        <div class="mt-6">
                            <form action="{{ route('subscription.upgrade') }}" method="POST">
                                @csrf
                                <input type="hidden" name="plan" value="pro">
                                <input type="hidden" name="tier" value="engineer">
                                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                    Upgrade to Pro Engineer
                                </button>
                            </form>
                        </div>
                    </div>
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
                                <span class="font-medium">{{ ucfirst($user->subscription_plan) }} {{ ucfirst($user->subscription_tier) }}</span>
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
                                <button type="submit" class="block w-full text-center bg-red-100 hover:bg-red-200 text-red-700 font-medium py-2 px-4 rounded">
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