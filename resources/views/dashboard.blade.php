@extends('components.layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-2 py-4 lg:py-8">
        <div class="max-w-7xl mx-auto">
            <!-- Enhanced Dashboard Header -->
            <div class="relative mb-4 lg:mb-8">
                <!-- Subscription Alerts (if any) -->
                @if(isset($subscription) && !empty($subscription['alerts']))
                    @foreach($subscription['alerts'] as $alert)
                    <div class="mb-3 lg:mb-4 p-3 lg:p-4 rounded-lg border {{ $alert['level'] === 'error' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-yellow-50 border-yellow-200 text-yellow-800' }}">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas {{ $alert['level'] === 'error' ? 'fa-exclamation-triangle' : 'fa-exclamation-circle' }} mr-2"></i>
                                <span>{{ $alert['message'] }}</span>
                            </div>
                            @if($alert['level'] === 'error')
                            <a href="{{ route('subscription.index') }}" class="text-sm font-medium underline hover:no-underline">
                                Upgrade Now
                            </a>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @endif

                <!-- Unified Header Content -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl p-4 lg:p-6 xl:p-8">
                                         <!-- Main Header Row -->
                     <div class="mb-4 lg:mb-6">
                         <!-- Mobile: Compact Layout -->
                         <div class="flex flex-col lg:hidden gap-2 lg:gap-3">
                             <!-- Title + Button Row -->
                             <div class="flex items-center justify-between gap-4">
                                 <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent flex-1 min-w-0">
                                     Dashboard
                                 </h1>
                                 <a href="{{ route('projects.create') }}"
                                    class="group inline-flex items-center px-4 py-2 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 flex-shrink-0">
                                     <i class="fas fa-plus mr-1.5 group-hover:scale-110 transition-transform text-sm"></i>
                                     <span class="text-sm">Create</span>
                                 </a>
                             </div>
                             <!-- Subtitle -->
                             <p class="text-base text-gray-600 font-medium leading-snug">Manage your projects, pitches, and collaborations</p>
                         </div>

                         <!-- Desktop: Original Layout -->
                         <div class="hidden lg:flex lg:items-start lg:justify-between gap-6">
                             <!-- Title Section -->
                             <div class="flex-1">
                                 <h1 class="text-5xl font-bold bg-gradient-to-r from-gray-900 via-blue-800 to-purple-800 bg-clip-text text-transparent mb-2">
                                     Dashboard
                                 </h1>
                                 <p class="text-lg text-gray-600 font-medium">Manage your projects, pitches, and collaborations</p>
                             </div>
                                 
                             <!-- Action Button -->
                             <div class="flex-shrink-0">
                                 <a href="{{ route('projects.create') }}"
                                    class="group inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                     <i class="fas fa-plus mr-2 group-hover:scale-110 transition-transform"></i>
                                     Create Project
                                 </a>
                             </div>
                         </div>
                     </div>

                    <!-- Subscription Status Integration -->
                    @if(isset($subscription))
                    <div class="border-t border-gray-200/60 pt-4 lg:pt-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                                         <!-- Account Status -->
                             <div class="flex flex-wrap items-center gap-x-4 gap-y-3">
                                 <!-- Current Plan Badge -->
                                 <div class="flex items-center flex-shrink-0">
                                     <span class="inline-flex items-center px-3 py-2 rounded-xl text-sm font-semibold {{ ($subscription['plan'] !== 'free') ? 'bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 border border-green-200' : 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 border border-gray-300' }} shadow-sm">
                                         <i class="{{ ($subscription['plan'] !== 'free') ? 'fas fa-crown' : 'fas fa-user' }} mr-2 text-xs"></i>
                                    {{ $subscription['display_name'] ?? ucfirst($subscription['plan']) }}
                                </span>
                            </div>

                            <!-- Usage Stats -->
                            @if(isset($subscription['usage']))
                                 <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm min-w-0">
                                <!-- Projects -->
                                     <div class="flex items-center bg-blue-50/80 px-2.5 py-1.5 rounded-lg border border-blue-100 flex-shrink-0">
                                         <i class="fas fa-folder text-blue-500 mr-1.5 text-xs"></i>
                                         <span class="text-blue-700 font-medium">Projects:</span>
                                         <span class="font-semibold ml-1 {{ isset($subscription['limits']) && $subscription['limits']->max_projects_owned && $subscription['usage']['active_projects'] >= $subscription['limits']->max_projects_owned ? 'text-red-600' : 'text-blue-900' }}">
                                        {{ $subscription['usage']['active_projects'] }}{{ isset($subscription['limits']) && $subscription['limits']->max_projects_owned ? '/' . $subscription['limits']->max_projects_owned : '' }}
                                    </span>
                                    @if($subscription['usage']['completed_projects'] > 0)
                                        <span class="text-xs text-gray-500 ml-1">(+{{ $subscription['usage']['completed_projects'] }} completed)</span>
                                    @endif
                                </div>

                                <!-- Active Pitches -->
                                     <div class="flex items-center bg-green-50/80 px-2.5 py-1.5 rounded-lg border border-green-100 flex-shrink-0">
                                         <i class="fas fa-paper-plane text-green-500 mr-1.5 text-xs"></i>
                                         <span class="text-green-700 font-medium">Pitches:</span>
                                         <span class="font-semibold ml-1 {{ isset($subscription['limits']) && $subscription['limits']->max_active_pitches && $subscription['usage']['active_pitches_count'] >= $subscription['limits']->max_active_pitches ? 'text-red-600' : 'text-green-900' }}">
                                        {{ $subscription['usage']['active_pitches_count'] }}{{ isset($subscription['limits']) && $subscription['limits']->max_active_pitches ? '/' . $subscription['limits']->max_active_pitches : '' }}
                                    </span>
                                </div>

                                <!-- Monthly Pitches (Pro Engineer only) -->
                                @if(isset($subscription['limits']) && $subscription['limits']->max_monthly_pitches)
                                     <div class="flex items-center bg-purple-50/80 px-2.5 py-1.5 rounded-lg border border-purple-100 flex-shrink-0">
                                         <i class="fas fa-calendar text-purple-500 mr-1.5 text-xs"></i>
                                         <span class="text-purple-700 font-medium">Monthly:</span>
                                         <span class="font-semibold ml-1 {{ $subscription['usage']['monthly_pitches_used'] >= $subscription['limits']->max_monthly_pitches ? 'text-red-600' : 'text-purple-900' }}">
                                        {{ $subscription['usage']['monthly_pitches_used'] }}/{{ $subscription['limits']->max_monthly_pitches }}
                                    </span>
                                </div>
                                @endif

                                <!-- Storage Usage -->
                                <div class="flex items-center bg-orange-50/80 px-2.5 py-1.5 rounded-lg border border-orange-100 flex-shrink-0">
                                    <i class="fas fa-hdd text-orange-500 mr-1.5 text-xs"></i>
                                    <span class="text-orange-700 font-medium">Storage:</span>
                                    <span class="font-semibold ml-1 text-orange-900">
                                        @if(isset($storage_info))
                                            {{ $storage_info['percentage'] }}%
                                        @else
                                            --
                                        @endif
                                    </span>
                                    @if(isset($storage_info['remaining_gb']))
                                        <span class="text-xs text-gray-500 ml-1">({{ $storage_info['remaining_gb'] }}GB left)</span>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                            <div class="flex items-center gap-3">
                            @if($subscription['plan'] === 'free')
                                    <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white text-sm font-semibold rounded-xl shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                        <i class="fas fa-arrow-up mr-2"></i>
                                    Upgrade to Pro
                                </a>
                            @else
                                    <a href="{{ route('subscription.index') }}" class="inline-flex items-center px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-xl border border-gray-200 transition-all duration-200 hover:shadow-sm">
                                        <i class="fas fa-cog mr-2"></i>
                                    Manage Plan
                                </a>
                            @endif
                                <a href="{{ route('subscription.index') }}" class="text-sm text-gray-500 hover:text-gray-700 font-medium transition-colors duration-200 px-2">
                                    View Details →
                            </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Profile Setup Banner -->
            <x-profile-setup-banner :user="auth()->user()" />

            <!-- Payout Status Banner -->
            <x-payout-status-banner :user="auth()->user()" />

            <!-- Phase 3: Producer Analytics Section -->
            @if(isset($producerData))
            <div class="mb-4 lg:mb-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 lg:gap-6 mb-4 lg:mb-6">
                    <!-- Earnings Overview -->
                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 border border-green-200 rounded-xl p-4 lg:p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-3 lg:mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-2 rounded-lg shadow-md">
                                    <i class="fas fa-dollar-sign text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-green-800">Total Earnings</h3>
                                    <p class="text-sm text-green-600">Net after commission</p>
                                </div>
                            </div>
                            <a href="{{ route('payouts.index') }}" 
                               class="text-green-600 hover:text-green-800 transition-colors group">
                                <i class="fas fa-external-link-alt group-hover:scale-110 transition-transform"></i>
                            </a>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-green-700 font-medium">Total Earned:</span>
                                <span class="text-2xl font-bold text-green-900">${{ number_format($producerData['earnings']['total'], 2) }}</span>
                            </div>
                            
                            @if($producerData['earnings']['pending'] > 0)
                            <div class="flex justify-between items-center">
                                <span class="text-green-600 text-sm">Pending:</span>
                                <span class="text-green-800 font-medium">${{ number_format($producerData['earnings']['pending'], 2) }}</span>
                            </div>
                            @endif
                            
                            <div class="flex justify-between items-center">
                                <span class="text-green-600 text-sm">This Month:</span>
                                <span class="text-green-800 font-medium">${{ number_format($producerData['earnings']['this_month'], 2) }}</span>
                            </div>
                            
                            @if($producerData['earnings']['commission_savings'] > 0)
                            <div class="pt-2 border-t border-green-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-green-600 text-sm">Commission Saved:</span>
                                    <span class="text-green-800 font-medium">${{ number_format($producerData['earnings']['commission_savings'], 2) }}</span>
                                </div>
                                <p class="text-xs text-green-600 mt-1">{{ $producerData['earnings']['commission_rate'] }}% rate vs 10% free tier</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Client Management Stats -->
                    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-4 lg:p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-3 lg:mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 p-2 rounded-lg shadow-md">
                                    <i class="fas fa-users text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-blue-800">Client Projects</h3>
                                    <p class="text-sm text-blue-600">Your client management</p>
                                </div>
                            </div>
                            <a href="{{ route('projects.index') }}?workflow_type=client_management" 
                               class="text-blue-600 hover:text-blue-800 transition-colors group">
                                <i class="fas fa-external-link-alt group-hover:scale-110 transition-transform"></i>
                            </a>
                        </div>
                        
                        @if($producerData['client_management']['total_projects'] > 0)
                        <!-- Has Client Projects - Show Stats -->
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-3 bg-white/50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-900">{{ $producerData['client_management']['total_projects'] }}</div>
                                <div class="text-xs text-blue-600">Total Projects</div>
                            </div>
                            <div class="text-center p-3 bg-white/50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-900">{{ $producerData['client_management']['active_projects'] }}</div>
                                <div class="text-xs text-blue-600">Active</div>
                            </div>
                            <div class="text-center p-3 bg-white/50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-900">{{ $producerData['client_management']['completed_projects'] }}</div>
                                <div class="text-xs text-blue-600">Completed</div>
                            </div>
                            <div class="text-center p-3 bg-white/50 rounded-lg">
                                <div class="text-lg font-bold text-blue-900">${{ number_format($producerData['client_management']['total_revenue'], 0) }}</div>
                                <div class="text-xs text-blue-600">Revenue</div>
                            </div>
                        </div>
                        @else
                        <!-- No Client Projects - Show Empty State with CTA -->
                        <div class="text-center">
                            <div class="mb-4">

                                <h4 class="text-lg font-semibold text-blue-800 mb-2">No Client Projects Yet</h4>
                                <p class="text-sm text-blue-600 mb-4">Start managing client projects to earn more and build your portfolio</p>
                            </div>
                            <a href="{{ route('projects.create') }}?workflow_type=client_management" 
                               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white text-sm font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                <i class="fas fa-plus mr-2"></i>
                                Create Client Project
                            </a>
                        </div>
                        @endif
                    </div>

                    <!-- Stripe Connect Status -->
                    <div class="bg-gradient-to-br from-purple-50 to-pink-50 border border-purple-200 rounded-xl p-4 lg:p-6 shadow-lg">
                        <div class="flex items-center justify-between mb-3 lg:mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="bg-gradient-to-r from-purple-500 to-pink-600 p-2 rounded-lg shadow-md">
                                    <i class="fas fa-credit-card text-white text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-purple-800">Payout Account</h3>
                                    <p class="text-sm text-purple-600">Stripe Connect</p>
                                </div>
                            </div>
                            <a href="{{ route('stripe.connect.setup') }}" 
                               class="text-purple-600 hover:text-purple-800 transition-colors group">
                                <i class="fas fa-cog group-hover:rotate-90 transition-transform"></i>
                            </a>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="flex items-center space-x-2">
                                @if($producerData['stripe_connect']['can_receive_payouts'] ?? false)
                                    <div class="w-3 h-3 bg-green-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium text-green-800">Ready for Payouts</span>
                                @elseif($producerData['stripe_connect']['account_exists'] ?? false)
                                    <div class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium text-yellow-800">Setup in Progress</span>
                                @else
                                    <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
                                    <span class="text-sm font-medium text-red-800">Setup Required</span>
                                @endif
                            </div>
                            
                            <div class="text-sm text-purple-700 bg-white/50 rounded-lg p-2">
                                {{ $producerData['stripe_connect']['status_display'] ?? 'Setup Required' }}
                            </div>
                            
                            @if(!($producerData['stripe_connect']['can_receive_payouts'] ?? false))
                            <div class="pt-2">
                                <a href="{{ route('stripe.connect.setup') }}" 
                                   class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white text-sm font-medium rounded-lg shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-arrow-right mr-2"></i>
                                    Complete Setup
                                </a>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Recent Payouts -->
                @if($producerData['recent_payouts']->isNotEmpty())
                <div class="bg-white/95 backdrop-blur-sm border border-white/20 rounded-xl shadow-lg">
                    <div class="px-4 lg:px-6 py-3 lg:py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg p-2 w-8 h-8 flex items-center justify-center mr-3 shadow-md">
                                    <i class="fas fa-history text-white text-xs"></i>
                                </div>
                                Recent Payouts
                            </h3>
                            <a href="{{ route('payouts.index') }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors group">
                                <span class="group-hover:mr-2 transition-all">View All</span>
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </a>
                        </div>
                    </div>
                    <div class="p-4 lg:p-6">
                        <div class="space-y-4">
                            @foreach($producerData['recent_payouts'] as $payout)
                            <div class="flex items-center justify-between p-4 bg-gradient-to-r from-gray-50 to-gray-100 hover:from-gray-100 hover:to-gray-200 rounded-lg transition-all duration-200 hover:shadow-md">
                                <div class="flex items-center space-x-4">
                                    <div class="flex-shrink-0">
                                        @if($payout->status === 'completed')
                                            <div class="w-10 h-10 bg-gradient-to-r from-green-500 to-green-600 rounded-full flex items-center justify-center shadow-md">
                                                <i class="fas fa-check text-white"></i>
                                            </div>
                                        @elseif($payout->status === 'processing')
                                            <div class="w-10 h-10 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full flex items-center justify-center shadow-md">
                                                <i class="fas fa-clock text-white"></i>
                                            </div>
                                        @else
                                            <div class="w-10 h-10 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-full flex items-center justify-center shadow-md">
                                                <i class="fas fa-hourglass-half text-white"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ $payout->project->name ?? 'Unknown Project' }}</div>
                                        <div class="text-sm text-gray-600 flex items-center">
                                            <i class="fas fa-calendar mr-1 text-xs"></i>
                                            {{ $payout->created_at->format('M j, Y') }}
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-semibold text-gray-900">${{ number_format($payout->net_amount, 2) }}</div>
                                    <div class="text-sm text-gray-600 capitalize">{{ str_replace('_', ' ', $payout->status) }}</div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>
            @endif

            <!-- Work Section -->
            <div class="relative">
                <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-4 lg:p-6 xl:p-8" x-data="{ filter: 'all' }">
                    <!-- Section Header with Filters -->
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6 mb-6 lg:mb-8">
                        <div>
                            <h2 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2">
                                My Work
                            </h2>
                            <p class="text-gray-600">Track and manage all your active projects and collaborations</p>
                        </div>
                        
                        <!-- Enhanced Filter System -->
                        <div class="flex flex-wrap gap-1.5">
                            <button @click="filter = 'all'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-blue-600 to-purple-600 text-white shadow-md scale-105': filter === 'all', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'all' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-th-large mr-1.5 text-xs"></i>All
                            </button>
                            <button @click="filter = 'project'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow-md scale-105': filter === 'project', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'project' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-folder mr-1.5 text-xs"></i>Projects
                            </button>
                            <button @click="filter = 'contest'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-yellow-500 to-orange-600 text-white shadow-md scale-105': filter === 'contest', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'contest' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-trophy mr-1.5 text-xs"></i>Contests
                            </button>
                            <button @click="filter = 'client'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow-md scale-105': filter === 'client', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'client' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-briefcase mr-1.5 text-xs"></i>Client Projects
                            </button>
                            <button @click="filter = 'pitch'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-md scale-105': filter === 'pitch', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'pitch' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-paper-plane mr-1.5 text-xs"></i>Pitches
                            </button>
                            <button @click="filter = 'order'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-md scale-105': filter === 'order', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'order' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-shopping-cart mr-1.5 text-xs"></i>Orders
                            </button>
                            <button @click="filter = 'service'" 
                                    :class="{ 
                                        'bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md scale-105': filter === 'service', 
                                        'bg-white/80 text-gray-700 hover:bg-white hover:shadow-sm': filter !== 'service' 
                                    }" 
                                    class="px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-200 border border-gray-200/50 backdrop-blur-sm hover:scale-105">
                                <i class="fas fa-box mr-1.5 text-xs"></i>Services
                            </button>
                        </div>
                    </div>

                    @if ($workItems->isEmpty())
                        <!-- Enhanced Empty State -->
                        <div class="text-center py-12 lg:py-16">
                            <div class="mx-auto w-24 lg:w-32 h-24 lg:h-32 bg-gradient-to-br from-blue-100 to-purple-100 rounded-3xl flex items-center justify-center mb-6 lg:mb-8 shadow-lg">
                                <i class="fas fa-rocket text-4xl lg:text-5xl text-blue-500"></i>
                            </div>
                            <h3 class="text-xl lg:text-2xl font-bold text-gray-800 mb-3 lg:mb-4">Ready to Start Creating?</h3>
                            <p class="text-gray-600 mb-6 lg:mb-8 max-w-md mx-auto leading-relaxed">
                                You don't have any active work items yet. Create your first project or find exciting collaborations to get started on your musical journey.
                            </p>
                            <div class="flex flex-col sm:flex-row gap-3 lg:gap-4 justify-center">
                                <a href="{{ route('projects.create') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-600 hover:to-teal-700 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-plus mr-2"></i>Create Project
                                </a>
                                <a href="{{ route('projects.index') }}" 
                                   class="inline-flex items-center px-6 py-3 bg-white hover:bg-gray-50 text-gray-700 font-semibold rounded-xl border-2 border-gray-200 hover:border-gray-300 shadow-md hover:shadow-lg transition-all duration-200 hover:scale-105">
                                    <i class="fas fa-search mr-2"></i>Browse Projects
                                </a>
                            </div>
                        </div>
                    @else
                        <!-- Work Items Grid -->
                        <div class="grid gap-4 lg:gap-6">
                            @foreach ($workItems as $item)
                                @php
                                    $itemType = 'unknown';
                                    if ($item instanceof \App\Models\Project) { 
                                        // Check if this is a contest project
                                        if ($item->isContest()) {
                                            $itemType = 'contest';
                                        } else {
                                        $itemType = 'project'; 
                                        }
                                    }
                                    elseif ($item instanceof \App\Models\Pitch) { 
                                        // Check if this is a client management pitch
                                        if ($item->project && $item->project->isClientManagement()) {
                                            $itemType = 'client';
                                        } else {
                                            $itemType = 'pitch';
                                        }
                                    }
                                    elseif ($item instanceof \App\Models\Order) { 
                                        $itemType = 'order'; 
                                    }
                                    elseif ($item instanceof \App\Models\ServicePackage) { 
                                        $itemType = 'service'; 
                                    }
                                @endphp
                                
                                <div x-show="filter === 'all' || filter === '{{ $itemType }}'" 
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 transform scale-95"
                                     x-transition:enter-end="opacity-100 transform scale-100"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 transform scale-100"
                                     x-transition:leave-end="opacity-0 transform scale-95">
                                    {{-- Determine item type and include specific card --}}
                                    @if ($itemType === 'project')
                                        @include('dashboard.cards._project_card', ['project' => $item])
                                    @elseif ($itemType === 'contest')
                                        @include('dashboard.cards._project_card', ['project' => $item])
                                    @elseif ($itemType === 'pitch')
                                        @include('dashboard.cards._pitch_card', ['pitch' => $item])
                                    @elseif ($itemType === 'client')
                                        @include('dashboard.cards._pitch_card', ['pitch' => $item])
                                    @elseif ($itemType === 'order')
                                        @include('dashboard.cards._order_card', ['order' => $item])
                                    @elseif ($itemType === 'service')
                                        @include('dashboard.cards._service_package_card', ['package' => $item])
                                    @endif
                                 </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- License Templates Section -->
            <div class="relative mt-4 lg:mt-8">
                <div class="relative bg-white/95 backdrop-blur-sm border border-white/20 rounded-2xl shadow-xl p-4 lg:p-6 xl:p-8">
                    <!-- Section Header -->
                    <div class="mb-6 lg:mb-8">
                        <h2 class="text-2xl lg:text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-700 bg-clip-text text-transparent mb-2 flex items-center">
                            <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-xl p-2.5 w-10 h-10 flex items-center justify-center mr-3 shadow-lg">
                                <i class="fas fa-file-contract text-white text-sm"></i>
                            </div>
                            License Templates
                        </h2>
                        <p class="text-gray-600">Manage your custom license agreement templates for projects</p>
                    </div>
                    
                    <!-- License Templates Component -->
                    <livewire:user.manage-license-templates />
                </div>
            </div>
        </div>
    </div>
</div>

@endsection