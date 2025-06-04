@extends('components.layouts.app')

@section('content')
<!-- Background Effects -->
<div class="fixed inset-0 overflow-hidden pointer-events-none">
    <div class="absolute -top-40 -right-40 w-80 h-80 bg-gradient-to-br from-purple-400/20 to-indigo-600/20 rounded-full blur-3xl"></div>
    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-gradient-to-tr from-indigo-400/20 to-blue-600/20 rounded-full blur-3xl"></div>
    <div class="absolute top-1/3 left-1/4 w-64 h-64 bg-gradient-to-r from-blue-300/10 to-purple-300/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-1/3 right-1/4 w-48 h-48 bg-gradient-to-l from-indigo-300/15 to-purple-300/15 rounded-full blur-xl"></div>
</div>

<div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Enhanced Hero Section -->
        <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-3xl shadow-2xl overflow-hidden mb-16">
            <!-- Hero Background Effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/30 via-purple-50/20 to-blue-50/30"></div>
            <div class="absolute top-0 left-0 w-full h-full overflow-hidden">
                <div class="absolute -top-20 -right-20 w-40 h-40 bg-purple-400/10 rounded-full blur-2xl animate-pulse"></div>
                <div class="absolute -bottom-20 -left-20 w-32 h-32 bg-indigo-400/10 rounded-full blur-xl animate-pulse" style="animation-delay: 1s;"></div>
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-blue-300/5 to-purple-300/5 rounded-full blur-3xl"></div>
            </div>
            
            <!-- Animated Sound Waves -->
            <div class="absolute inset-0 flex items-center justify-center opacity-5 overflow-hidden">
                <div class="wave-container">
                    <div class="wave wave1"></div>
                    <div class="wave wave2"></div>
                    <div class="wave wave3"></div>
                </div>
            </div>

            <div class="relative z-10 px-8 sm:px-12 py-16 text-center">
                <div class="max-w-4xl mx-auto">
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold mb-6 bg-gradient-to-r from-gray-900 via-indigo-800 to-purple-800 bg-clip-text text-transparent">
                        Pricing & Plans
                    </h1>
                    <p class="text-xl sm:text-2xl text-gray-600 font-medium leading-relaxed max-w-3xl mx-auto">
                        Choose the perfect plan for your music collaboration needs
                    </p>
                    
                    <!-- Pricing Stats -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mt-12 max-w-2xl mx-auto">
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-105">
                            <div class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">Free</div>
                            <div class="text-sm text-gray-600 font-medium">Start Today</div>
                        </div>
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-105">
                            <div class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">14-Day</div>
                            <div class="text-sm text-gray-600 font-medium">Free Trial</div>
                        </div>
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl p-4 shadow-sm hover:shadow-lg transition-all duration-300 hover:scale-105">
                            <div class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-cyan-600 bg-clip-text text-transparent">$9</div>
                            <div class="text-sm text-gray-600 font-medium">Per Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Pricing Plans -->
        <div class="mb-16">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent mb-4">
                    Choose Your <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Plan</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-3xl mx-auto">
                    Flexible pricing options designed for every stage of your musical journey
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Basic Plan -->
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-[1.02] group">
                    <div class="absolute inset-0 bg-gradient-to-br from-gray-50/30 to-slate-50/20 group-hover:from-gray-50/50 group-hover:to-slate-50/30 transition-all duration-300"></div>
                    <div class="absolute top-4 right-4 w-12 h-12 bg-gray-400/10 rounded-full blur-lg group-hover:bg-gray-400/20 transition-all duration-300"></div>
                    
                    <div class="relative p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center shadow-lg">
                                <i class="fas fa-music text-white text-xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 text-gray-700 rounded-full text-sm font-medium">Free</span>
                        </div>

                        <h3 class="text-2xl font-bold text-gray-900 mb-2">Basic</h3>
                        <div class="mb-6">
                            <span class="text-4xl font-bold bg-gradient-to-r from-gray-700 to-slate-700 bg-clip-text text-transparent">$0</span>
                            <span class="text-lg text-gray-500 font-medium">/month</span>
                        </div>

                        <div class="border-t border-gray-200/50 my-6"></div>

                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700">Upload <strong class="text-gray-900">1 project</strong></span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700">Work on up to <strong class="text-gray-900">3 projects</strong></span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-gray-900">Basic collaboration</strong> tools</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-gray-500 to-slate-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-gray-900">Community support</strong></span>
                            </li>
                        </ul>

                        <a href="{{ route('register') }}"
                            class="group inline-flex items-center justify-center w-full px-6 py-3 bg-gray-100/80 backdrop-blur-sm border border-gray-200/50 text-gray-700 font-semibold rounded-xl shadow-sm hover:shadow-lg transition-all duration-200 hover:scale-105 hover:bg-gray-200/80">
                            <i class="fas fa-rocket mr-2 group-hover:scale-110 transition-transform"></i>
                            Get Started for Free
                        </a>
                    </div>
                </div>

                <!-- Pro Artist Plan - Featured -->
                <div class="relative bg-white/90 backdrop-blur-sm border border-indigo-200/50 rounded-2xl shadow-xl overflow-hidden hover:shadow-2xl transition-all duration-300 hover:scale-[1.05] group">
                    <!-- Featured badge -->
                    <div class="absolute top-4 right-4 z-20">
                        <span class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold px-3 py-1.5 rounded-full uppercase tracking-wide shadow-lg">Popular</span>
                    </div>

                    <!-- Top accent bar -->
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-600"></div>
                    
                    <div class="absolute inset-0 bg-gradient-to-br from-indigo-50/40 to-purple-50/30 group-hover:from-indigo-50/60 group-hover:to-purple-50/40 transition-all duration-300"></div>
                    <div class="absolute top-4 left-4 w-16 h-16 bg-indigo-400/10 rounded-full blur-lg group-hover:bg-indigo-400/20 transition-all duration-300"></div>
                    
                    <div class="relative p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                                <i class="fas fa-star text-white text-xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-indigo-100/80 backdrop-blur-sm border border-indigo-200/50 text-indigo-700 rounded-full text-sm font-medium">Best Value</span>
                        </div>

                        <h3 class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">Pro Artist</h3>
                        <div class="mb-6">
                            <span class="text-4xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">$9</span>
                            <span class="text-lg text-gray-500 font-medium">/month</span>
                        </div>

                        <div class="border-t border-indigo-200/50 my-6"></div>

                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-indigo-700">Unlimited</strong> project uploads</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-indigo-700">2 prioritized</strong> projects at a time</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-indigo-700">Advanced collaboration</strong> tools</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-indigo-700">Priority support</strong></span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-indigo-700">Custom portfolio</strong> page</span>
                            </li>
                        </ul>

                        <a href="{{ route('register') }}"
                            class="group inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-indigo-500 to-purple-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:from-indigo-600 hover:to-purple-700">
                            <i class="fas fa-crown mr-2 group-hover:scale-110 transition-transform"></i>
                            Go Pro Artist
                        </a>
                    </div>
                </div>

                <!-- Pro Engineer Plan -->
                <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:scale-[1.02] group">
                    <!-- Top accent bar -->
                    <div class="h-2 bg-gradient-to-r from-emerald-500 to-teal-600"></div>
                    
                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/30 to-teal-50/20 group-hover:from-emerald-50/50 group-hover:to-teal-50/30 transition-all duration-300"></div>
                    <div class="absolute bottom-4 left-4 w-12 h-12 bg-emerald-400/10 rounded-full blur-lg group-hover:bg-emerald-400/20 transition-all duration-300"></div>
                    
                    <div class="relative p-8">
                        <div class="flex items-center justify-between mb-6">
                            <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center shadow-lg">
                                <i class="fas fa-microphone text-white text-xl"></i>
                            </div>
                            <span class="px-3 py-1 bg-emerald-100/80 backdrop-blur-sm border border-emerald-200/50 text-emerald-700 rounded-full text-sm font-medium">Expert</span>
                        </div>

                        <h3 class="text-2xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent mb-2">Pro Engineer</h3>
                        <div class="mb-6">
                            <span class="text-4xl font-bold bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent">$9</span>
                            <span class="text-lg text-gray-500 font-medium">/month</span>
                        </div>

                        <div class="border-t border-emerald-200/50 my-6"></div>

                        <ul class="space-y-4 mb-8">
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700">Work on <strong class="text-emerald-700">unlimited</strong> projects</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-emerald-700">5 prioritized</strong> pitches per month</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-emerald-700">Advanced collaboration</strong> tools</span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-emerald-700">Priority support</strong></span>
                            </li>
                            <li class="flex items-start group">
                                <div class="bg-gradient-to-r from-emerald-500 to-teal-600 rounded-full p-1 w-6 h-6 flex items-center justify-center mr-3 mt-0.5 shadow-sm group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check text-white text-xs"></i>
                                </div>
                                <span class="text-gray-700"><strong class="text-emerald-700">Custom portfolio</strong> page</span>
                            </li>
                        </ul>

                        <a href="{{ route('register') }}"
                            class="group inline-flex items-center justify-center w-full px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:from-emerald-600 hover:to-teal-700">
                            <i class="fas fa-tools mr-2 group-hover:scale-110 transition-transform"></i>
                            Go Pro Engineer
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced FAQ Section -->
        <div class="mb-16">
            <div class="relative bg-white/80 backdrop-blur-sm border border-white/30 rounded-2xl shadow-lg overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50/20 to-indigo-50/20"></div>
                <div class="absolute top-4 right-4 w-20 h-20 bg-blue-400/10 rounded-full blur-xl"></div>
                
                <div class="relative p-8 sm:p-12">
                    <div class="flex items-center mb-8">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-3 w-14 h-14 flex items-center justify-center mr-4 shadow-lg">
                            <i class="fas fa-question-circle text-white text-xl"></i>
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-gray-900 to-indigo-800 bg-clip-text text-transparent">
                            Frequently Asked Questions
                        </h2>
                    </div>

                    <div class="space-y-4">
                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
                            <details class="group">
                                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-white/20 transition-colors">
                                    <h3 class="text-lg font-semibold text-gray-900">Can I upgrade or downgrade my plan at any time?</h3>
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-chevron-down text-white text-sm transition-transform group-open:rotate-180"></i>
                                    </div>
                                </summary>
                                <div class="px-6 pb-6 border-t border-white/30">
                                    <p class="text-gray-700 leading-relaxed mt-4">
                                        Yes, you can upgrade or downgrade your plan at any time. Changes will be reflected in your next billing cycle, and we'll prorate any differences.
                                    </p>
                                </div>
                            </details>
                        </div>

                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
                            <details class="group">
                                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-white/20 transition-colors">
                                    <h3 class="text-lg font-semibold text-gray-900">Is there a free trial for the Pro plan?</h3>
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-chevron-down text-white text-sm transition-transform group-open:rotate-180"></i>
                                    </div>
                                </summary>
                                <div class="px-6 pb-6 border-t border-white/30">
                                    <p class="text-gray-700 leading-relaxed mt-4">
                                        We offer a 14-day free trial for our Pro plans. You can cancel anytime during the trial period without being charged, and you'll have access to all Pro features.
                                    </p>
                                </div>
                            </details>
                        </div>

                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
                            <details class="group">
                                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-white/20 transition-colors">
                                    <h3 class="text-lg font-semibold text-gray-900">What payment methods do you accept?</h3>
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-chevron-down text-white text-sm transition-transform group-open:rotate-180"></i>
                                    </div>
                                </summary>
                                <div class="px-6 pb-6 border-t border-white/30">
                                    <p class="text-gray-700 leading-relaxed mt-4">
                                        We accept all major credit cards (Visa, MasterCard, American Express), PayPal, and bank transfers for Enterprise plans. All payments are processed securely through Stripe.
                                    </p>
                                </div>
                            </details>
                        </div>

                        <div class="bg-white/60 backdrop-blur-sm border border-white/40 rounded-xl overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
                            <details class="group">
                                <summary class="flex items-center justify-between p-6 cursor-pointer hover:bg-white/20 transition-colors">
                                    <h3 class="text-lg font-semibold text-gray-900">Can I cancel my subscription anytime?</h3>
                                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full p-2 w-8 h-8 flex items-center justify-center shadow-sm">
                                        <i class="fas fa-chevron-down text-white text-sm transition-transform group-open:rotate-180"></i>
                                    </div>
                                </summary>
                                <div class="px-6 pb-6 border-t border-white/30">
                                    <p class="text-gray-700 leading-relaxed mt-4">
                                        Absolutely! You can cancel your subscription at any time from your account settings. You'll continue to have access to Pro features until the end of your current billing period.
                                    </p>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Call to Action -->
        <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-3xl shadow-2xl overflow-hidden">
            <!-- CTA Background Effects -->
            <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600"></div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/20 via-transparent to-black/20"></div>
            <div class="absolute -top-20 -right-20 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-20 -left-20 w-32 h-32 bg-white/10 rounded-full blur-xl"></div>
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
            
            <!-- Pattern Overlay -->
            <div class="absolute inset-0 opacity-10">
                <div class="bg-pattern w-full h-full"></div>
            </div>

            <div class="relative z-10 px-8 sm:px-12 py-16 text-center">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold mb-6 text-white">
                    Ready to Elevate Your Music Collaboration?
                </h2>
                <p class="text-xl sm:text-2xl max-w-3xl mx-auto mb-12 text-white/90 leading-relaxed">
                    Join MixPitch today and start connecting with talented musicians and audio professionals worldwide.
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-6">
                    @auth
                    <a href="{{ route('projects.create') }}"
                        class="group inline-flex items-center px-8 py-4 bg-white text-indigo-600 text-lg font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:bg-gray-50">
                        <i class="fas fa-plus mr-3 group-hover:scale-110 transition-transform"></i>
                        Start Collaborating
                    </a>
                    @else
                    <a href="{{ route('register') }}"
                        class="group inline-flex items-center px-8 py-4 bg-white text-indigo-600 text-lg font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:bg-gray-50">
                        <i class="fas fa-user-plus mr-3 group-hover:scale-110 transition-transform"></i>
                        Join MixPitch
                    </a>
                    @endauth

                    <a href="{{ route('about') }}"
                        class="group inline-flex items-center px-8 py-4 bg-transparent text-white text-lg font-bold border-2 border-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 hover:bg-white/10">
                        <i class="fas fa-info-circle mr-3 group-hover:scale-110 transition-transform"></i>
                        Learn More
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced CSS for animations and effects -->
<style>
    /* Enhanced Audio visualization waves */
    .wave-container {
        position: relative;
        width: 100%;
        height: 400px;
    }

    .wave {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 120px;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23667eea" fill-opacity="0.3" d="M0,192L48,197.3C96,203,192,213,288,229.3C384,245,480,267,576,250.7C672,235,768,181,864,181.3C960,181,1056,235,1152,234.7C1248,235,1344,181,1392,154.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 120px;
        background-repeat: repeat-x;
        animation: wave-animation 25s linear infinite;
    }

    .wave1 {
        opacity: 0.4;
        animation-duration: 25s;
        animation-delay: 0s;
    }

    .wave2 {
        opacity: 0.3;
        animation-duration: 20s;
        animation-delay: -3s;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23764ba2" fill-opacity="0.3" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,149.3C960,160,1056,160,1152,138.7C1248,117,1344,75,1392,53.3L1440,32L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 120px;
    }

    .wave3 {
        opacity: 0.2;
        animation-duration: 30s;
        animation-delay: -5s;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23f093fb" fill-opacity="0.3" d="M0,288L48,272C96,256,192,224,288,197.3C384,171,480,149,576,165.3C672,181,768,235,864,250.7C960,267,1056,245,1152,224C1248,203,1344,181,1392,170.7L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
        background-size: 1440px 120px;
    }

    @keyframes wave-animation {
        0% {
            background-position-x: 0;
        }
        100% {
            background-position-x: 1440px;
        }
    }

    /* Enhanced background patterns */
    .bg-pattern {
        background-image: url('data:image/svg+xml,%3Csvg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"%3E%3Cpath d="M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z" fill="%23ffffff" fill-opacity="0.4" fill-rule="evenodd"%3E%3C/path%3E%3C/svg%3E');
    }

    /* Smooth scroll behavior */
    html {
        scroll-behavior: smooth;
    }

    /* Enhanced hover effects */
    .group:hover .group-hover\:scale-110 {
        transform: scale(1.1);
    }

    .group:hover .group-hover\:scale-105 {
        transform: scale(1.05);
    }

    /* Enhanced details/summary styling */
    details summary::-webkit-details-marker {
        display: none;
    }

    details summary {
        list-style: none;
    }
</style>
@endsection