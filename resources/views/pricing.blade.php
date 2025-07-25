@extends('components.layouts.app')

@section('title', 'Pricing Plans - Choose Your MixPitch Experience')

@section('content')
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
                            <span class="inline-block ml-2 px-2 py-1 bg-green-100 text-green-700 text-xs rounded-full">Save 17%</span>
                        </button>
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
                            <span class="text-gray-700">10GB Total Storage</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Unlimited File Retention</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">10% Commission Rate</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">3 License Presets</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Basic Analytics</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-check text-green-500 mr-3"></i>
                            <span class="text-gray-700">Forum Support</span>
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

                <!-- Pro Artist -->
                <div class="relative bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
                    <!-- Popular Badge -->
                    <div class="absolute top-6 right-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-star mr-1"></i>
                            Most Popular
                        </span>
                    </div>
                    
                    <div class="p-8">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900 flex items-center justify-center">
                                Pro Artist
                                <span class="ml-2 text-2xl">🔷</span>
                            </h3>
                            <p class="mt-2 text-gray-600">For professional music creators</p>
                            
                            <!-- Pricing Display -->
                            <div class="mt-6">
                                <div class="monthly-pricing">
                                    <span class="text-4xl font-bold text-gray-900">$6.99</span>
                                    <span class="text-lg text-gray-600">/month</span>
                                </div>
                                <div class="yearly-pricing hidden">
                                    <span class="text-4xl font-bold text-gray-900">$69.99</span>
                                    <span class="text-lg text-gray-600">/year</span>
                                    <div class="mt-2">
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full font-medium">
                                            Save $13.89/year
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Features List -->
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Unlimited Projects & Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">50GB Total Storage</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">8% Commission Rate</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Custom License Templates</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">4 Visibility Boosts/month</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">2 Private Projects/month</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Unlimited File Retention</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Track-level Analytics</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">24h Early Challenge Access</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-blue-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Priority Support</span>
                            </li>
                        </ul>
                        
                        <!-- CTA Button -->
                        <div class="mt-8">
                            @auth
                                @if($user->subscription_plan === 'pro' && $user->subscription_tier === 'artist')
                                    <button class="w-full bg-gray-100 text-gray-500 py-3 px-6 rounded-lg font-semibold cursor-not-allowed">
                                        Current Plan
                                    </button>
                                @elseif($user->isFreePlan())
                                    <form action="{{ route('subscription.upgrade') }}" method="POST" class="subscription-form">
                                        @csrf
                                        <input type="hidden" name="plan" value="pro">
                                        <input type="hidden" name="tier" value="artist">
                                        <input type="hidden" name="billing_period" class="billing-period-input" value="monthly">
                                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold transition-colors duration-200">
                                            <span class="monthly-text">Start Free Trial</span>
                                            <span class="yearly-text hidden">Start Free Trial</span>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('subscription.index') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold text-center transition-colors duration-200">
                                        Manage Subscription
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('register') }}" class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-semibold text-center transition-colors duration-200">
                                    Get Started
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- Pro Engineer -->
                <div class="relative bg-white rounded-2xl shadow-xl border border-gray-200 overflow-hidden transform hover:scale-105 transition-all duration-300 hover:shadow-2xl">
                    <div class="p-8">
                        <div class="text-center">
                            <h3 class="text-2xl font-bold text-gray-900 flex items-center justify-center">
                                Pro Engineer
                                <span class="ml-2 text-2xl">🔶</span>
                            </h3>
                            <p class="mt-2 text-gray-600">Advanced tools for audio engineers</p>
                            
                            <!-- Pricing Display -->
                            <div class="mt-6">
                                <div class="monthly-pricing">
                                    <span class="text-4xl font-bold text-gray-900">$9.99</span>
                                    <span class="text-lg text-gray-600">/month</span>
                                </div>
                                <div class="yearly-pricing hidden">
                                    <span class="text-4xl font-bold text-gray-900">$99.99</span>
                                    <span class="text-lg text-gray-600">/year</span>
                                    <div class="mt-2">
                                        <span class="inline-block px-3 py-1 bg-green-100 text-green-800 text-sm rounded-full font-medium">
                                            Save $19.89/year
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Features List -->
                        <ul class="mt-8 space-y-4">
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Unlimited Projects & Pitches</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">200GB Total Storage</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">6% Commission Rate</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">1.25× Reputation Multiplier</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Unlimited License Templates</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Unlimited Private Projects</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Client Portal Access</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Unlimited File Retention</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Client & Earnings Analytics</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">24h Early Challenge Access + Judge</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check text-orange-500 mt-1 mr-3"></i>
                                <span class="text-gray-700">Email & Chat Support (24h SLA)</span>
                            </li>
                        </ul>
                        
                        <!-- CTA Button -->
                        <div class="mt-8">
                            @auth
                                @if($user->subscription_plan === 'pro' && $user->subscription_tier === 'engineer')
                                    <button class="w-full bg-gray-100 text-gray-500 py-3 px-6 rounded-lg font-semibold cursor-not-allowed">
                                        Current Plan
                                    </button>
                                @elseif($user->isFreePlan())
                                    <form action="{{ route('subscription.upgrade') }}" method="POST" class="subscription-form">
                                        @csrf
                                        <input type="hidden" name="plan" value="pro">
                                        <input type="hidden" name="tier" value="engineer">
                                        <input type="hidden" name="billing_period" class="billing-period-input" value="monthly">
                                        <button type="submit" class="w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-6 rounded-lg font-semibold transition-colors duration-200">
                                            <span class="monthly-text">Start Free Trial</span>
                                            <span class="yearly-text hidden">Start Free Trial</span>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('subscription.index') }}" class="block w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-6 rounded-lg font-semibold text-center transition-colors duration-200">
                                        Manage Subscription
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('register') }}" class="block w-full bg-orange-600 hover:bg-orange-700 text-white py-3 px-6 rounded-lg font-semibold text-center transition-colors duration-200">
                                    Get Started
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
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
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Free</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Pro Artist 🔷</th>
                                <th class="px-6 py-4 text-center text-sm font-medium text-gray-500 uppercase tracking-wider">Pro Engineer 🔶</th>
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
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Total Storage</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">10GB</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">50GB</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">200GB</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Commission Rate</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">10%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">8%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">6%</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">License Templates</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">3 presets</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited custom</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited custom</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Visibility Boosts per Month</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">0</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">4</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">1</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Reputation Multiplier</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">1×</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">1×</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">1.25×</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Private Projects per Month</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">0</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">2</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Unlimited</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Analytics</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Basic</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Track Analytics</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Client & Earnings</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Early Access</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">-</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">24h</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">24h + Judge Access</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Support</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">Forum</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Email (48h SLA)</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 text-center font-medium">Email & Chat (24h SLA)</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Client Portal</td>
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
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">What are visibility boosts?</h4>
                        <p class="text-gray-600">Visibility boosts give your projects higher ranking in search results and featured placement for 72 hours.</p>
                    </div>
                    <div>
                        <h4 class="font-semibold text-gray-900 mb-2">How do commission savings work?</h4>
                        <p class="text-gray-600">Pro plans have reduced commission rates (8% for Artist, 6% for Engineer vs. 10% for Free), saving you money on each transaction.</p>
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
@endsection