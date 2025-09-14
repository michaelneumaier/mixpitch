<x-layouts.app-sidebar>

<div class="bg-gradient-to-br from-blue-50 via-purple-50 to-pink-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 min-h-screen">
    <div class="mx-auto p-2">
        <div class="mx-auto">
            <!-- Enhanced Dashboard Header -->
            <div class="relative mb-2">
                <!-- Subscription Alerts (if any) -->
                @if(isset($subscription) && !empty($subscription['alerts']))
                    @foreach($subscription['alerts'] as $alert)
                        <flux:callout 
                            :color="$alert['level'] === 'error' ? 'red' : 'amber'" 
                            :icon="$alert['level'] === 'error' ? 'exclamation-triangle' : 'exclamation-circle'"
                            class="mb-4"
                        >
                            <div class="flex items-center justify-between">
                                <flux:callout.text>{{ $alert['message'] }}</flux:callout.text>
                                @if($alert['level'] === 'error')
                                    <flux:callout.link href="{{ route('subscription.index') }}" wire:navigate>
                                        Upgrade Now
                                    </flux:callout.link>
                                @endif
                            </div>
                        </flux:callout>
                    @endforeach
                @endif

                <!-- Compact Dashboard Header -->
                <flux:card class="p-4 lg:p-6 xl:p-8 mb-2 bg-white/50">
                    <!-- Top Row: Title + Primary Actions -->
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 to-purple-800 dark:from-blue-200 dark:to-purple-200 bg-clip-text text-transparent">
                            Dashboard
                        </flux:heading>
                        
                        <!-- Primary Actions -->
                        <div class="flex items-center gap-2">
                            <flux:button href="{{ route('projects.create') }}" wire:navigate icon="plus" variant="primary" color="violet" size="xs" >
                                Create
                            </flux:button>
                        </div>
                    </div>

                    @if(isset($subscription))
                    <!-- Mobile: Stack layout -->
                    <div class="lg:hidden space-y-3">
                        <!-- Plan + Critical Stats Row -->
                        <div class="flex items-center justify-between">
                            <flux:badge :color="$subscription['plan'] !== 'free' ? 'emerald' : 'zinc'" size="sm" :icon="$subscription['plan'] !== 'free' ? 'sparkles' : 'user'">
                                {{ $subscription['display_name'] ?? ucfirst($subscription['plan']) }}
                            </flux:badge>
                            
                            @if(isset($subscription['usage']))
                            <div class="flex items-center gap-2">
                                <flux:badge color="blue" size="sm" icon="folder">
                                    <span class="font-mono text-xs">{{ $subscription['usage']['active_projects'] }}{{ isset($subscription['limits']) && $subscription['limits']->max_projects_owned ? '/' . $subscription['limits']->max_projects_owned : '' }}</span>
                                </flux:badge>
                                <flux:badge color="green" size="sm" icon="paper-airplane">
                                    <span class="font-mono text-xs">{{ $subscription['usage']['active_pitches_count'] }}{{ isset($subscription['limits']) && $subscription['limits']->max_active_pitches ? '/' . $subscription['limits']->max_active_pitches : '' }}</span>
                                </flux:badge>
                            </div>
                            @endif
                        </div>
                        
                        <!-- Action Buttons Row -->
                        <div class="flex items-center justify-center gap-2">
                            @if($subscription['plan'] === 'free')
                                <flux:button href="{{ route('subscription.index') }}" wire:navigate icon="arrow-up" variant="filled" size="xs" class="!bg-gradient-to-r !from-blue-600 !to-purple-600 !hover:from-blue-700 !hover:to-purple-700 !text-white flex-1">
                                    Upgrade to Pro
                                </flux:button>
                            @else
                                <flux:button href="{{ route('subscription.index') }}" wire:navigate icon="cog-6-tooth" variant="outline" size="xs" class="flex-1">
                                    Manage Plan
                                </flux:button>
                            @endif
                            
                            <flux:button href="{{ route('subscription.index') }}" wire:navigate variant="ghost" size="xs">
                                Details
                            </flux:button>
                        </div>
                    </div>

                    <!-- Desktop: Horizontal layout -->
                    <div class="hidden lg:flex lg:items-center lg:gap-3">
                        <!-- Plan Badge -->
                        <flux:badge :color="$subscription['plan'] !== 'free' ? 'emerald' : 'zinc'" size="sm" :icon="$subscription['plan'] !== 'free' ? 'sparkles' : 'user'" class="flex-shrink-0">
                            {{ $subscription['display_name'] ?? ucfirst($subscription['plan']) }}
                        </flux:badge>

                        @if(isset($subscription['usage']))
                        <!-- Usage Stats -->
                        <div class="flex items-center gap-2">
                            <flux:badge color="blue" size="sm" icon="folder">
                                <span class="font-mono">{{ $subscription['usage']['active_projects'] }}{{ isset($subscription['limits']) && $subscription['limits']->max_projects_owned ? '/' . $subscription['limits']->max_projects_owned : '' }}</span>
                            </flux:badge>
                            
                            <flux:badge color="green" size="sm" icon="paper-airplane">
                                <span class="font-mono">{{ $subscription['usage']['active_pitches_count'] }}{{ isset($subscription['limits']) && $subscription['limits']->max_active_pitches ? '/' . $subscription['limits']->max_active_pitches : '' }}</span>
                            </flux:badge>

                            @if(isset($subscription['limits']) && $subscription['limits']->max_monthly_pitches)
                            <flux:badge color="purple" size="sm" icon="calendar">
                                <span class="font-mono">{{ $subscription['usage']['monthly_pitches_used'] }}/{{ $subscription['limits']->max_monthly_pitches }}</span>
                            </flux:badge>
                            @endif

                            <flux:badge color="orange" size="sm" icon="circle-stack">
                                <span class="font-mono">
                                    @if(isset($storage_info))
                                        {{ $storage_info['percentage'] }}%
                                    @else
                                        --
                                    @endif
                                </span>
                            </flux:badge>
                        </div>
                        @endif

                        <!-- Spacer -->
                        <div class="flex-1"></div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            @if($subscription['plan'] === 'free')
                                <flux:button href="{{ route('subscription.index') }}" wire:navigate icon="arrow-up" variant="filled" size="xs" class="!bg-gradient-to-r !from-blue-600 !to-purple-600 !hover:from-blue-700 !hover:to-purple-700 !text-white">
                                    Upgrade to Pro
                                </flux:button>
                            @else
                                <flux:button href="{{ route('subscription.index') }}" wire:navigate icon="cog-6-tooth" variant="outline" size="xs">
                                    Manage Plan
                                </flux:button>
                            @endif
                            
                            <flux:button href="{{ route('subscription.index') }}" wire:navigate variant="ghost" size="xs">
                                Details
                            </flux:button>
                        </div>
                    </div>
                    @endif
                </flux:card>

            <!-- Profile Setup Banner -->
            <x-profile-setup-banner :user="auth()->user()" />

            <!-- Payout Status Banner -->
            <x-payout-status-banner :user="auth()->user()" />

            {{-- 
            <!-- Client Management Promotion Banner (hidden for now) -->
            @if(isset($producerData) && $producerData['client_management']['total_projects'] == 0)
            <div class="relative mb-4 lg:mb-8">
                <div class="relative bg-gradient-to-r from-blue-500 via-indigo-600 to-purple-600 rounded-2xl shadow-xl overflow-hidden">
                    <!-- Background Pattern -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/20 via-indigo-600/20 to-purple-600/20"></div>
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full"></div>
                    <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-32 h-32 bg-white/5 rounded-full"></div>
                    
                    <div class="relative p-4 lg:p-6">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Content -->
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 lg:w-16 lg:h-16 bg-white/20 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-lg">
                                        <i class="fas fa-users text-white text-xl lg:text-2xl"></i>
                                    </div>
                                </div>
                                <div class="flex-1 text-white">
                                    <h3 class="text-lg lg:text-xl font-bold mb-1">🚀 Unlock Client Management Features</h3>
                                    <p class="text-blue-100 text-sm lg:text-base leading-relaxed">
                                        Take your business to the next level with professional client project management, 
                                        custom branding, advanced analytics, and direct client approval workflows.
                                    </p>
                                    <div class="flex flex-wrap items-center gap-4 mt-3 text-xs lg:text-sm">
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle mr-1.5 text-emerald-300"></i>
                                            Professional branding
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle mr-1.5 text-emerald-300"></i>
                                            Client portal access
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle mr-1.5 text-emerald-300"></i>
                                            Advanced analytics
                                        </div>
                                        <div class="flex items-center">
                                            <i class="fas fa-check-circle mr-1.5 text-emerald-300"></i>
                                            Higher earnings
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="flex flex-col sm:flex-row gap-2 lg:gap-3 flex-shrink-0">
                                <a href="{{ route('producer.client-management') }}" 
                                   class="inline-flex items-center px-4 lg:px-5 py-2.5 lg:py-3 bg-white hover:bg-gray-50 text-blue-600 font-semibold rounded-lg shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 text-sm lg:text-base">
                                    <i class="fas fa-chart-line mr-2"></i>
                                    Explore Dashboard
                                </a>
                                <a href="{{ route('projects.create') }}?workflow_type=client_management" 
                                   class="inline-flex items-center px-4 lg:px-5 py-2.5 lg:py-3 bg-white/20 backdrop-blur-sm hover:bg-white/30 text-white font-semibold rounded-lg border border-white/30 hover:border-white/40 shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-105 text-sm lg:text-base">
                                    <i class="fas fa-plus mr-2"></i>
                                    Create Client Project
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            --}}

            <!-- Note: Producer analytics moved to compact sidebar widget for better space utilization -->
            
            <!-- Recent Payouts Section -->
            @if(isset($producerData))
            <div class="mb-2">
                @if($producerData['recent_payouts']->isNotEmpty())
                <flux:card class="bg-gradient-to-br from-slate-50 to-gray-50 dark:from-slate-900/20 dark:to-gray-900/20 border-slate-200 dark:border-slate-700">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-md">
                                <flux:icon name="clock" class="text-white" size="lg" />
                            </div>
                            <div>
                                <flux:heading size="lg" class="text-slate-800 dark:text-slate-200">Recent Payouts</flux:heading>
                                <flux:subheading class="text-slate-600 dark:text-slate-400">Your recent payment history</flux:subheading>
                            </div>
                        </div>
                        <flux:button href="{{ route('payouts.index') }}" wire:navigate icon="arrow-top-right-on-square" variant="ghost" size="sm" class="text-slate-600 dark:text-slate-400 hover:text-slate-800 dark:hover:text-slate-200" />
                    </div>

                    <div class="space-y-3">
                        @foreach($producerData['recent_payouts'] as $payout)
                        <div class="flex items-center justify-between p-4 bg-white/70 dark:bg-white/10 rounded-lg border border-slate-100 dark:border-slate-700 hover:bg-white dark:hover:bg-white/20 hover:shadow-md transition-all duration-200">
                            <div class="flex items-center gap-4">
                                <div class="flex-shrink-0">
                                    @if($payout->status === 'completed')
                                        <div class="p-2 bg-gradient-to-r from-emerald-500 to-green-600 rounded-full shadow-sm">
                                            <flux:icon name="check" class="text-white" size="sm" />
                                        </div>
                                    @elseif($payout->status === 'processing')
                                        <div class="p-2 bg-gradient-to-r from-blue-500 to-blue-600 rounded-full shadow-sm">
                                            <flux:icon name="clock" class="text-white" size="sm" />
                                        </div>
                                    @else
                                        <div class="p-2 bg-gradient-to-r from-amber-500 to-yellow-600 rounded-full shadow-sm">
                                            <flux:icon name="clock" class="text-white" size="sm" />
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-slate-900 dark:text-slate-100 truncate">{{ $payout->project->name ?? 'Unknown Project' }}</div>
                                    <div class="flex items-center gap-1 text-sm text-slate-600 dark:text-slate-400">
                                        <flux:icon name="calendar" size="xs" />
                                        <span>{{ $payout->created_at->format('M j, Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <div class="font-bold text-slate-900 dark:text-slate-100">${{ number_format($payout->net_amount, 2) }}</div>
                                <flux:badge 
                                    :color="$payout->status === 'completed' ? 'emerald' : ($payout->status === 'processing' ? 'blue' : 'amber')" 
                                    size="sm"
                                    class="mt-1"
                                >
                                    {{ ucfirst(str_replace('_', ' ', $payout->status)) }}
                                </flux:badge>
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <flux:separator class="my-4" />
                    
                    <div class="flex items-center justify-center">
                        <flux:button href="{{ route('payouts.index') }}" wire:navigate icon="arrow-right" variant="outline" size="sm" class="border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800">
                            View All Payouts
                        </flux:button>
                    </div>
                </flux:card>
                @endif
            </div>
            @endif

            <!-- Work Section -->
            <livewire:work-section />

            <!-- License Templates Section -->
            <livewire:license-templates-section />
        </div>
    </div>
</div>

</x-layouts.app-sidebar>