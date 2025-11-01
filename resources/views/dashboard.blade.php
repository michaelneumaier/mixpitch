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
                <flux:card class="mb-2">
                    <!-- Top Row: Title + Primary Actions -->
                    <div class="flex items-center justify-between gap-3 mb-3">
                        <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 to-purple-800 dark:from-blue-200 dark:to-purple-200 bg-clip-text text-transparent">
                            Dashboard
                        </flux:heading>
                        
                        <!-- Primary Actions -->
                        <div class="flex items-center gap-2">
                            <flux:button href="{{ route('projects.create') }}" wire:navigate icon="plus" variant="primary" color="amber" size="sm" >
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

            <!-- Work Section -->
            <livewire:work-section />

            <!-- Unified Billing & Payments Section -->
            <div class="mb-2">
                <livewire:billing-payments-section />
            </div>

            <!-- License Templates Section -->
            <livewire:license-templates-section />
        </div>
    </div>
</div>

</x-layouts.app-sidebar>