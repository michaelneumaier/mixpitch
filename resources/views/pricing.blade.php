<x-layouts.marketing title="Pricing Plans - Choose Your MixPitch Experience">
<div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    <div class="container mx-auto px-2 md:px-4 py-8 md:py-16">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-8 md:mb-16">
                <h1 class="text-5xl lg:text-6xl font-bold bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent mb-6">
                    Choose Your Plan
                </h1>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto leading-relaxed">
                    Unlock your creative potential with MixPitch. From beginner-friendly free tools to professional-grade features, 
                    we have the perfect plan to elevate your music career.
                </p>
            </div>

            <!-- Current Plan Alert (for authenticated users) -->
            @auth
                @php
                    $user = auth()->user();
                    $isSubscribed = $user->subscribed('default');
                    $onGracePeriod = $isSubscribed && $user->subscription('default')->onGracePeriod();
                @endphp
                @if($user->isProPlan())
                <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-8 text-center">
                    <div class="flex items-center justify-center">
                        <i class="fas fa-crown text-green-600 mr-2"></i>
                        <span class="font-medium text-green-800">
                            You're currently on the {{ ucfirst($user->subscription_plan) }} {{ ucfirst($user->subscription_tier) }} plan
                        </span>
                        @if($onGracePeriod)
                            <span class="ml-2 text-sm text-yellow-600">(Cancelling {{ $user->subscription('default')->ends_at->format('M d, Y') }})</span>
                        @endif
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('subscription.index') }}" class="text-sm text-green-600 hover:text-green-800 underline">
                            Manage your subscription
                        </a>
                    </div>
                </div>
                @endif
            @endauth

            <!-- Pricing Toggle -->
            <div class="flex justify-center mb-12">
                <div class="bg-white rounded-xl shadow-lg p-2">
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
                            <span class="inline-block ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Save {{ $yearlyDiscount }}%</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-{{ count($plans) }} gap-8 mb-16">
                @foreach($plans as $plan)
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 relative {{ $plan['is_most_popular'] ? 'transform hover:scale-105 transition-all duration-300 hover:shadow-2xl' : '' }}">
                    @if($plan['is_most_popular'])
                    <!-- Popular Badge -->
                    <div class="absolute top-6 right-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-star mr-1"></i>
                            Most Popular
                        </span>
                    </div>
                    @endif
                    
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2 flex items-center justify-center">
                            {{ $plan['display_name'] }}
                            @if($plan['badge'])
                                <span class="ml-2 text-2xl">{{ $plan['badge'] }}</span>
                            @endif
                        </h3>
                        <p class="mt-2 text-gray-600">{{ $plan['description'] }}</p>
                        
                        <!-- Pricing Display -->
                        <div class="mt-6">
                            <div class="monthly-pricing">
                                <span class="text-4xl font-bold text-gray-900">${{ $plan['monthly_price'] == 0 ? '0' : number_format($plan['monthly_price'], 2) }}</span>
                                <span class="text-lg text-gray-600">/month</span>
                            </div>
                            <div class="yearly-pricing hidden">
                                <span class="text-4xl font-bold text-gray-900">${{ $plan['yearly_price'] == 0 ? '0' : number_format($plan['yearly_price'], 2) }}</span>
                                <span class="text-lg text-gray-600">/year</span>
                                @if($plan['yearly_savings'] > 0)
                                <div class="mt-2">
                                    <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full font-medium">
                                        Save ${{ number_format($plan['yearly_savings'], 2) }}/year
                                    </span>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Features List -->
                    <ul class="mt-8 space-y-4">
                        @foreach($plan['features'] as $feature)
                        <li class="flex items-start">
                            <i class="fas fa-check {{ $plan['plan_name'] === 'free' ? 'text-green-500' : ($plan['plan_tier'] === 'artist' ? 'text-blue-500' : 'text-orange-500') }} mt-1 mr-3"></i>
                            <span class="text-gray-700">{{ $feature }}</span>
                        </li>
                        @endforeach
                    </ul>
                    
                    <!-- CTA Button -->
                    <div class="mt-8">
                        @auth
                            @if($user->subscription_plan === $plan['plan_name'] && $user->subscription_tier === $plan['plan_tier'])
                                <button class="w-full bg-gray-100 text-gray-500 py-3 px-6 rounded-lg font-semibold cursor-not-allowed">
                                    Current Plan
                                </button>
                            @elseif($user->isFreePlan() && $plan['plan_name'] === 'pro')
                                <form action="{{ route('subscription.upgrade') }}" method="POST" class="subscription-form">
                                    @csrf
                                    <input type="hidden" name="plan" value="{{ $plan['plan_name'] }}">
                                    <input type="hidden" name="tier" value="{{ $plan['plan_tier'] }}">
                                    <input type="hidden" name="billing_period" class="billing-period-input" value="monthly">
                                    <button type="submit" class="w-full {{ $plan['plan_tier'] === 'artist' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white py-3 px-6 rounded-lg font-semibold transition-colors duration-200">
                                        <span class="monthly-text">Start Free Trial</span>
                                        <span class="yearly-text hidden">Start Free Trial</span>
                                    </button>
                                </form>
                            @elseif($plan['plan_name'] === 'free')
                                <div class="text-center">
                                    <div class="bg-gray-50 text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200">
                                        Manage in Subscription Settings
                                    </div>
                                </div>
                            @else
                                <a href="{{ route('subscription.index') }}" class="block w-full {{ $plan['plan_tier'] === 'artist' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700' }} text-white py-3 px-6 rounded-lg font-semibold text-center transition-colors duration-200">
                                    Manage Subscription
                                </a>
                            @endif
                        @else
                            <a href="{{ route('register') }}" class="block w-full {{ $plan['plan_name'] === 'free' ? 'bg-gray-600 hover:bg-gray-700' : ($plan['plan_tier'] === 'artist' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-orange-600 hover:bg-orange-700') }} text-white py-3 px-6 rounded-lg font-semibold text-center transition-colors duration-200">
                                Get Started
                            </a>
                        @endauth
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Feature Comparison Table -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-16">
                <div class="bg-gray-50 px-8 py-6 border-b border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 text-center">Complete Feature Comparison</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                                @foreach($plans as $plan)
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">
                                    {{ $plan['display_name'] }} {{ $plan['badge'] ?? '' }}
                                </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php
                                $features = [
                                    'Projects' => function($plan) {
                                        return $plan['limits']->max_projects_owned ?? 'Unlimited';
                                    },
                                    'Active Pitches' => function($plan) {
                                        return $plan['limits']->max_active_pitches ?? 'Unlimited';
                                    },
                                    'Total Storage' => function($plan) {
                                        return intval($plan['limits']->total_user_storage_gb) . 'GB';
                                    },
                                    'Commission Rate' => function($plan) {
                                        return intval($plan['limits']->platform_commission_rate) . '%';
                                    },
                                    'License Templates' => function($plan) {
                                        if ($plan['limits']->max_license_templates === null) return 'Unlimited custom';
                                        if ($plan['limits']->max_license_templates === 0) return '3 presets';
                                        return $plan['limits']->max_license_templates . ' custom';
                                    },
                                    'Reputation Multiplier' => function($plan) {
                                        return $plan['limits']->reputation_multiplier . '×';
                                    },
                                    'Analytics' => function($plan) {
                                        return match($plan['limits']->analytics_level) {
                                            'track' => 'Track Analytics',
                                            'client_earnings' => 'Client & Earnings',
                                            default => 'Basic',
                                        };
                                    },
                                    'Early Access' => function($plan) {
                                        if ($plan['limits']->challenge_early_access_hours === 0) return '-';
                                        $text = $plan['limits']->challenge_early_access_hours . 'h';
                                        if ($plan['limits']->has_judge_access) $text .= ' + Judge Access';
                                        return $text;
                                    },
                                    'Support' => function($plan) {
                                        $channels = $plan['limits']->support_channels ?? [];
                                        $parts = [];
                                        if (in_array('forum', $channels)) $parts[] = 'Forum';
                                        if (in_array('email', $channels)) $parts[] = 'Email';
                                        if (in_array('chat', $channels)) $parts[] = 'Chat';
                                        $text = implode(' & ', $parts);
                                        if ($plan['limits']->support_sla_hours) {
                                            $text .= ' (' . $plan['limits']->support_sla_hours . 'h SLA)';
                                        }
                                        return $text ?: '-';
                                    },
                                    'Client Portal' => function($plan) {
                                        return $plan['limits']->has_client_portal ? '✓' : '-';
                                    },
                                ];
                            @endphp
                            
                            @foreach($features as $featureName => $getValue)
                            <tr class="{{ $loop->even ? 'bg-gray-50' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $featureName }}</td>
                                @foreach($plans as $plan)
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center {{ $getValue($plan) === '-' ? 'text-gray-500' : 'text-green-600 font-medium' }}">
                                    {{ $getValue($plan) }}
                                </td>
                                @endforeach
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="bg-white rounded-2xl shadow-xl p-8">
                <h3 class="text-2xl font-bold text-gray-900 text-center mb-8">Frequently Asked Questions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Can I change plans anytime?</h4>
                        <p class="text-gray-600">Yes! You can upgrade or downgrade your plan at any time through your subscription settings.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">What happens to my data if I downgrade?</h4>
                        <p class="text-gray-600">Your data is preserved, but you may lose access to certain features until you upgrade again.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">Is there a free trial?</h4>
                        <p class="text-gray-600">Yes! New users get full access to all features for 14 days to explore what MixPitch can offer.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">How secure is my payment information?</h4>
                        <p class="text-gray-600">We use Stripe for payment processing, which is PCI DSS Level 1 compliant - the highest level of security.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">How does the reputation multiplier work?</h4>
                        <p class="text-gray-600">Pro Engineer users get a 1.25× reputation boost, helping them build credibility faster on the platform.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">How do commission savings work?</h4>
                        <p class="text-gray-600">Pro plans have reduced commission rates ({{ intval($plans->where('plan_tier', 'artist')->first()['limits']->platform_commission_rate ?? 8) }}% for Artist, {{ intval($plans->where('plan_tier', 'engineer')->first()['limits']->platform_commission_rate ?? 6) }}% for Engineer vs. {{ intval($plans->where('plan_tier', 'basic')->first()['limits']->platform_commission_rate ?? 10) }}% for Free), saving you money on each transaction.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Billing Toggle -->
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const monthlyToggle = document.getElementById('monthly-toggle');
        const yearlyToggle = document.getElementById('yearly-toggle');
        const monthlyPricings = document.querySelectorAll('.monthly-pricing');
        const yearlyPricings = document.querySelectorAll('.yearly-pricing');
        const billingPeriodInputs = document.querySelectorAll('.billing-period-input');
        const monthlyTexts = document.querySelectorAll('.monthly-text');
        const yearlyTexts = document.querySelectorAll('.yearly-text');

        function showMonthly() {
            // Update toggle buttons
            monthlyToggle.classList.add('active', 'bg-blue-600', 'text-white');
            monthlyToggle.classList.remove('text-gray-600');
            yearlyToggle.classList.remove('active', 'bg-blue-600', 'text-white');
            yearlyToggle.classList.add('text-gray-600');

            // Show/hide pricing
            monthlyPricings.forEach(pricing => pricing.classList.remove('hidden'));
            yearlyPricings.forEach(pricing => pricing.classList.add('hidden'));

            // Update form inputs
            billingPeriodInputs.forEach(input => input.value = 'monthly');

            // Update button text
            monthlyTexts.forEach(text => text.classList.remove('hidden'));
            yearlyTexts.forEach(text => text.classList.add('hidden'));
        }

        function showYearly() {
            // Update toggle buttons
            yearlyToggle.classList.add('active', 'bg-blue-600', 'text-white');
            yearlyToggle.classList.remove('text-gray-600');
            monthlyToggle.classList.remove('active', 'bg-blue-600', 'text-white');
            monthlyToggle.classList.add('text-gray-600');

            // Show/hide pricing
            yearlyPricings.forEach(pricing => pricing.classList.remove('hidden'));
            monthlyPricings.forEach(pricing => pricing.classList.add('hidden'));

            // Update form inputs
            billingPeriodInputs.forEach(input => input.value = 'yearly');

            // Update button text
            yearlyTexts.forEach(text => text.classList.remove('hidden'));
            monthlyTexts.forEach(text => text.classList.add('hidden'));
        }

        // Set initial state
        showMonthly();

        // Add event listeners
        monthlyToggle.addEventListener('click', function(e) {
            e.preventDefault();
            showMonthly();
        });

        yearlyToggle.addEventListener('click', function(e) {
            e.preventDefault();
            showYearly();
        });
    });
</script>

<style>
    .billing-toggle {
        @apply relative transition-all duration-200;
    }
    
    .billing-toggle.active {
        @apply bg-blue-600 text-white shadow-md;
    }
    
    .billing-toggle:not(.active) {
        @apply text-gray-600 hover:text-gray-800;
    }
</style>
@endpush
</x-layouts.marketing>