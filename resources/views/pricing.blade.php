@extends('components.layouts.app')

@section('title', 'Pricing Plans - Choose Your MixPitch Experience')

@section('content')
<div class="bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 min-h-screen">
    <div class="container mx-auto px-4 py-16">
        <div class="max-w-7xl mx-auto">
            <!-- Header Section -->
            <div class="text-center mb-16">
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

            <!-- Pricing Toggle (Monthly Only for Now) -->
            <div class="flex justify-center mb-12">
                <div class="bg-white rounded-xl shadow-lg p-2">
                    <div class="bg-blue-600 text-white px-6 py-2 rounded-lg font-medium">
                        Monthly Billing
                    </div>
                </div>
            </div>

            <!-- Pricing Cards -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
                <!-- Free Plan -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 relative">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Free</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-gray-900">$0</span>
                            <span class="text-gray-500">/month</span>
                        </div>
                        <p class="text-gray-600 mb-8">Perfect for getting started with music collaboration</p>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">1 Project</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">3 Active Pitches</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">100MB Storage per Project</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Basic Portfolio</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Community Support</span>
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    @auth
                        @if($user->isFreePlan())
                            <div class="text-center">
                                <div class="bg-gray-100 text-gray-700 font-bold py-3 px-6 rounded-xl">
                                    Current Plan
                                </div>
                            </div>
                        @else
                            <div class="text-center">
                                <div class="bg-gray-50 text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200">
                                    Manage in Subscription Settings
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center">
                            <a href="{{ route('register') }}" class="block bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 hover:scale-105">
                                Get Started Free
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Pro Artist Plan -->
                <div class="bg-white rounded-2xl shadow-xl border-2 border-blue-500 p-8 relative transform scale-105">
                    <!-- Popular Badge -->
                    <div class="absolute -top-4 left-1/2 transform -translate-x-1/2">
                        <span class="bg-blue-500 text-white px-6 py-2 rounded-full text-sm font-bold">
                            Most Popular
                        </span>
                    </div>

                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Pro Artist</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-blue-600">$29</span>
                            <span class="text-gray-500">/month</span>
                        </div>
                        <p class="text-gray-600 mb-8">For artists who want unlimited creative freedom</p>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700"><strong>Unlimited Projects</strong></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700"><strong>Unlimited Active Pitches</strong></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">500MB Storage per Project</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Custom Portfolio Layouts</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Priority Support</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Project Prioritization</span>
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    @auth
                        @if($user->subscription_plan === 'pro' && $user->subscription_tier === 'artist')
                            <div class="text-center">
                                <div class="bg-blue-100 text-blue-700 font-bold py-3 px-6 rounded-xl">
                                    Current Plan
                                </div>
                            </div>
                        @elseif($user->isFreePlan())
                            <div class="text-center">
                                <form action="{{ route('subscription.upgrade') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="pro">
                                    <input type="hidden" name="tier" value="artist">
                                    <button type="submit" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 hover:scale-105">
                                        Upgrade to Pro Artist
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="text-center">
                                <div class="bg-gray-50 text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200">
                                    Contact Support to Switch Plans
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center">
                            <a href="{{ route('register') }}" class="block bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 hover:scale-105">
                                Start Pro Artist Trial
                            </a>
                        </div>
                    @endauth
                </div>

                <!-- Pro Engineer Plan -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-200 p-8 relative">
                    <div class="text-center">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Pro Engineer</h3>
                        <div class="mb-6">
                            <span class="text-5xl font-bold text-purple-600">$19</span>
                            <span class="text-gray-500">/month</span>
                        </div>
                        <p class="text-gray-600 mb-8">For engineers who want to receive high-quality work</p>
                    </div>

                    <!-- Features -->
                    <ul class="space-y-4 mb-8">
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700"><strong>Unlimited Projects</strong></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700"><strong>Unlimited Active Pitches</strong></span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">5 Monthly Pitches Received</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">500MB Storage per Project</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Priority Support</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Advanced Analytics</span>
                        </li>
                    </ul>

                    <!-- CTA Button -->
                    @auth
                        @if($user->subscription_plan === 'pro' && $user->subscription_tier === 'engineer')
                            <div class="text-center">
                                <div class="bg-purple-100 text-purple-700 font-bold py-3 px-6 rounded-xl">
                                    Current Plan
                                </div>
                            </div>
                        @elseif($user->isFreePlan())
                            <div class="text-center">
                                <form action="{{ route('subscription.upgrade') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="plan" value="pro">
                                    <input type="hidden" name="tier" value="engineer">
                                    <button type="submit" class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 hover:scale-105">
                                        Upgrade to Pro Engineer
                                    </button>
                                </form>
                            </div>
                        @else
                            <div class="text-center">
                                <div class="bg-gray-50 text-gray-500 font-medium py-3 px-6 rounded-xl border border-gray-200">
                                    Contact Support to Switch Plans
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center">
                            <a href="{{ route('register') }}" class="block bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-6 rounded-xl transition-all duration-200 hover:scale-105">
                                Start Pro Engineer Trial
                            </a>
                        </div>
                    @endauth
                </div>
            </div>

            <!-- Feature Comparison Table -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden mb-16">
                <div class="bg-gray-50 px-8 py-6 border-b border-gray-200">
                    <h3 class="text-2xl font-bold text-gray-900 text-center">Feature Comparison</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-sm font-medium text-gray-500 uppercase tracking-wider">Feature</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Free</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Pro Artist</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Pro Engineer</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Projects</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">1</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Active Pitches</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">3</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Monthly Pitches Received</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">5</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Storage per Project</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">100MB</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">500MB</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">500MB</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Portfolio</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Basic</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Custom Layouts</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Basic</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Support</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Community</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Priority</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Priority</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Project Prioritization</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">✓</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Advanced Analytics</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">✓</td>
                            </tr>
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
                </div>
            </div>
        </div>
    </div>
</div>
@endsection