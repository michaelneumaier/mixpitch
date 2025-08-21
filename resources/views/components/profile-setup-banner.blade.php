@props(['user'])

@if(!$user->hasCompletedProfile())
@php
    $completionStatus = $user->getProfileCompletionStatus();
    $missingFields = $user->getMissingProfileFields();
@endphp

<flux:card class="mb-2 bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border-amber-200 dark:border-amber-800">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- Header with Progress Circle -->
        <div class="flex items-start gap-4 lg:w-80 lg:flex-shrink-0">
            <!-- Circular Progress Indicator -->
            <div class="relative flex-shrink-0">
                <div class="w-16 h-16 rounded-full border-4 border-amber-200 dark:border-amber-700 flex items-center justify-center bg-white dark:bg-gray-800 shadow-sm">
                    <span class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $completionStatus['percentage'] }}%</span>
                </div>
                <!-- Progress Ring -->
                <svg class="absolute inset-0 w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" class="text-amber-200 dark:text-amber-700"/>
                    <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" 
                            class="text-amber-500 dark:text-amber-400" stroke-linecap="round"
                            stroke-dasharray="{{ 2 * pi() * 28 }}"
                            stroke-dashoffset="{{ 2 * pi() * 28 * (1 - $completionStatus['percentage'] / 100) }}"
                            style="transition: stroke-dashoffset 0.5s ease-in-out"/>
                </svg>
            </div>
            
            <!-- Title & Description -->
            <div class="flex-1 min-w-0">
                <flux:heading size="lg" class="text-amber-800 dark:text-amber-200 mb-1">Complete Your Profile</flux:heading>
                <flux:subheading class="text-amber-700 dark:text-amber-300">Set up your profile to get discovered and build your reputation</flux:subheading>
            </div>
        </div>

        <!-- Profile Completion Tasks -->
        <div class="flex-1 space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <!-- Username -->
                @if(empty($user->username))
                    <flux:badge color="amber" size="md" icon="at-symbol" class="justify-between">
                        <span>Username</span>
                        <span class="text-xs">Missing</span>
                    </flux:badge>
                @else
                    <flux:badge color="emerald" size="md" icon="check" class="justify-between">
                        <span>Username</span>
                        <span class="text-xs">✓</span>
                    </flux:badge>
                @endif

                <!-- Bio -->
                @if(empty($user->bio))
                    <flux:badge color="amber" size="md" icon="document-text" class="justify-between">
                        <span>Bio</span>
                        <span class="text-xs">Missing</span>
                    </flux:badge>
                @else
                    <flux:badge color="emerald" size="md" icon="check" class="justify-between">
                        <span>Bio</span>
                        <span class="text-xs">✓</span>
                    </flux:badge>
                @endif

                <!-- Location -->
                @if(empty($user->location))
                    <flux:badge color="amber" size="md" icon="map-pin" class="justify-between">
                        <span>Location</span>
                        <span class="text-xs">Missing</span>
                    </flux:badge>
                @else
                    <flux:badge color="emerald" size="md" icon="check" class="justify-between">
                        <span>Location</span>
                        <span class="text-xs">✓</span>
                    </flux:badge>
                @endif

                <!-- Website -->
                @if(empty($user->website))
                    <flux:badge color="zinc" size="md" icon="globe-alt" class="justify-between">
                        <span>Website</span>
                        <span class="text-xs">Optional</span>
                    </flux:badge>
                @else
                    <flux:badge color="emerald" size="md" icon="check" class="justify-between">
                        <span>Website</span>
                        <span class="text-xs">✓</span>
                    </flux:badge>
                @endif
            </div>

            <!-- Benefits -->
            <flux:separator class="my-4" />
            
            <div class="grid grid-cols-2 gap-2 text-sm text-amber-700 dark:text-amber-300">
                <div class="flex items-center gap-2">
                    <flux:icon name="magnifying-glass" size="sm" class="text-amber-600 dark:text-amber-400" />
                    <span>Get discovered</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="star" size="sm" class="text-amber-600 dark:text-amber-400" />
                    <span>Build reputation</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="link" size="sm" class="text-amber-600 dark:text-amber-400" />
                    <span>Share profile</span>
                </div>
                <div class="flex items-center gap-2">
                    <flux:icon name="chart-bar" size="sm" class="text-amber-600 dark:text-amber-400" />
                    <span>Track success</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row lg:flex-col gap-3 lg:w-48">
            <flux:button href="{{ route('profile.edit') }}" icon="pencil" variant="filled" size="sm" 
                         class="bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 !text-white shadow-lg">
                Set Up Profile
            </flux:button>
            
            @if($user->username)
                <flux:button href="{{ route('profile.username', ['username' => '@' . $user->username]) }}" 
                             icon="eye" variant="outline" size="sm" 
                             class="border-amber-300 dark:border-amber-600 text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                    Preview Profile
                </flux:button>
            @endif
        </div>
    </div>
</flux:card>
@endif 