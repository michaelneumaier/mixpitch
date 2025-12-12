@php
    $contestPhase = $this->contestPhase;
    $metrics = $this->contestMetrics;
    $deadlineInfo = $this->deadlineInfo;

    $colorClasses = match($contestPhase['color']) {
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'button' => 'bg-green-600 hover:bg-green-700 text-white',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'button' => 'bg-amber-600 hover:bg-amber-700 text-white',
        ],
        'info' => [
            'bg' => 'bg-orange-50 dark:bg-orange-950',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text' => 'text-orange-800 dark:text-orange-200',
            'icon' => 'text-orange-600 dark:text-orange-400',
            'button' => 'bg-orange-600 hover:bg-orange-700 text-white',
        ],
        default => [
            'bg' => 'bg-gray-50 dark:bg-gray-950',
            'border' => 'border-gray-200 dark:border-gray-800',
            'text' => 'text-gray-800 dark:text-gray-200',
            'icon' => 'text-gray-600 dark:text-gray-400',
            'button' => 'bg-gray-600 hover:bg-gray-700 text-white',
        ],
    };
@endphp

<div class="space-y-4">
    {{-- Contest Phase Status Card --}}
    <flux:card class="{{ $colorClasses['bg'] }} {{ $colorClasses['border'] }} border">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                @switch($contestPhase['icon'])
                    @case('document-text')
                        <flux:icon.document-text variant="solid" class="w-12 h-12 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('trophy')
                        <flux:icon.trophy variant="solid" class="w-12 h-12 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('scale')
                        <flux:icon.scale variant="solid" class="w-12 h-12 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('users')
                        <flux:icon.users variant="solid" class="w-12 h-12 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('rocket-launch')
                        <flux:icon.rocket-launch variant="solid" class="w-12 h-12 {{ $colorClasses['icon'] }}" />
                        @break
                    @default
                        <flux:icon.information-circle variant="solid" class="w-12 h-12 {{ $colorClasses['icon'] }}" />
                @endswitch
            </div>

            <div class="flex-1">
                <flux:heading size="lg" class="{{ $colorClasses['text'] }}">
                    {{ $contestPhase['heading'] }}
                </flux:heading>
                <flux:subheading class="{{ $colorClasses['text'] }} opacity-80 mt-1">
                    {{ $contestPhase['message'] }}
                </flux:subheading>

                <div class="mt-4 flex flex-wrap gap-2">
                    @if($contestPhase['phase'] === 'draft')
                        <flux:button
                            wire:click="handleAction('publish-project')"
                            size="sm"
                            class="{{ $colorClasses['button'] }}"
                        >
                            Publish Contest
                        </flux:button>
                    @elseif($contestPhase['phase'] === 'judging')
                        <flux:button
                            wire:click="handleAction('switch-to-judging')"
                            size="sm"
                            class="{{ $colorClasses['button'] }}"
                        >
                            Start Judging
                        </flux:button>
                    @elseif($contestPhase['phase'] === 'submission')
                        <flux:button
                            wire:click="handleAction('switch-to-entries')"
                            size="sm"
                            variant="ghost"
                        >
                            View Entries
                        </flux:button>
                    @elseif($contestPhase['phase'] === 'completed')
                        <flux:button
                            wire:click="handleAction('switch-to-entries')"
                            size="sm"
                            variant="ghost"
                        >
                            View Results
                        </flux:button>
                    @endif
                </div>
            </div>
        </div>
    </flux:card>

    {{-- Deadline Countdown Cards --}}
    @if($deadlineInfo['submission_deadline'] || $deadlineInfo['judging_deadline'])
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @if($deadlineInfo['submission_deadline'])
                <flux:card class="{{ $deadlineInfo['submission_open'] ? 'bg-orange-50 dark:bg-orange-950 border-orange-200 dark:border-orange-800' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }} border">
                    <div class="flex items-center gap-3">
                        <flux:icon.clock variant="solid" class="w-8 h-8 {{ $deadlineInfo['submission_open'] ? 'text-orange-600 dark:text-orange-400' : 'text-gray-500 dark:text-gray-400' }}" />
                        <div>
                            <div class="text-sm font-medium {{ $deadlineInfo['submission_open'] ? 'text-orange-800 dark:text-orange-200' : 'text-gray-600 dark:text-gray-400' }}">
                                Submission Deadline
                            </div>
                            <div class="text-lg font-bold {{ $deadlineInfo['submission_open'] ? 'text-orange-900 dark:text-orange-100' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $deadlineInfo['submission_deadline']->format('M j, Y') }}
                            </div>
                            <div class="text-sm {{ $deadlineInfo['submission_open'] ? 'text-orange-700 dark:text-orange-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $deadlineInfo['submission_deadline']->format('g:i A') }}
                                @if(!$deadlineInfo['submission_open'])
                                    <span class="ml-1">(Closed)</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($deadlineInfo['submission_open'] && $deadlineInfo['submission_days_left'] !== null)
                        <div class="mt-3 pt-3 border-t border-orange-200 dark:border-orange-700">
                            @if($deadlineInfo['submission_days_left'] <= 0 && $deadlineInfo['submission_hours_left'] > 0)
                                <div class="text-center">
                                    <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $deadlineInfo['submission_hours_left'] }}</span>
                                    <span class="text-sm text-orange-700 dark:text-orange-300 ml-1">hours left</span>
                                </div>
                            @elseif($deadlineInfo['submission_days_left'] > 0)
                                <div class="text-center">
                                    <span class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ $deadlineInfo['submission_days_left'] }}</span>
                                    <span class="text-sm text-orange-700 dark:text-orange-300 ml-1">{{ $deadlineInfo['submission_days_left'] === 1 ? 'day' : 'days' }} left</span>
                                </div>
                            @else
                                <div class="text-center text-sm text-orange-700 dark:text-orange-300">
                                    Deadline ending soon
                                </div>
                            @endif
                        </div>
                    @endif
                </flux:card>
            @endif

            @if($deadlineInfo['judging_deadline'])
                <flux:card class="{{ $deadlineInfo['judging_phase'] ? 'bg-purple-50 dark:bg-purple-950 border-purple-200 dark:border-purple-800' : 'bg-gray-100 dark:bg-gray-800 border-gray-200 dark:border-gray-700' }} border">
                    <div class="flex items-center gap-3">
                        <flux:icon.scale variant="solid" class="w-8 h-8 {{ $deadlineInfo['judging_phase'] ? 'text-purple-600 dark:text-purple-400' : 'text-gray-500 dark:text-gray-400' }}" />
                        <div>
                            <div class="text-sm font-medium {{ $deadlineInfo['judging_phase'] ? 'text-purple-800 dark:text-purple-200' : 'text-gray-600 dark:text-gray-400' }}">
                                Judging Deadline
                            </div>
                            <div class="text-lg font-bold {{ $deadlineInfo['judging_phase'] ? 'text-purple-900 dark:text-purple-100' : 'text-gray-700 dark:text-gray-300' }}">
                                {{ $deadlineInfo['judging_deadline']->format('M j, Y') }}
                            </div>
                            <div class="text-sm {{ $deadlineInfo['judging_phase'] ? 'text-purple-700 dark:text-purple-300' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $deadlineInfo['judging_deadline']->format('g:i A') }}
                            </div>
                        </div>
                    </div>

                    @if($deadlineInfo['judging_phase'] && $deadlineInfo['judging_days_left'] !== null)
                        <div class="mt-3 pt-3 border-t border-purple-200 dark:border-purple-700">
                            <div class="text-center">
                                <span class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ max(0, $deadlineInfo['judging_days_left']) }}</span>
                                <span class="text-sm text-purple-700 dark:text-purple-300 ml-1">{{ $deadlineInfo['judging_days_left'] === 1 ? 'day' : 'days' }} to judge</span>
                            </div>
                        </div>
                    @endif
                </flux:card>
            @endif
        </div>
    @endif

    {{-- Contest Metrics Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Entry Count --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold {{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                {{ $metrics['total_entries'] }}
            </div>
            <flux:subheading class="text-sm">
                {{ $metrics['total_entries'] === 1 ? 'Entry' : 'Entries' }}
            </flux:subheading>
        </flux:card>

        {{-- Prize Pool --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                ${{ number_format($metrics['prize_pool'], 0) }}
            </div>
            <flux:subheading class="text-sm">
                Prize Pool
            </flux:subheading>
        </flux:card>

        {{-- Files Count --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold {{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                {{ $metrics['total_files'] }}
            </div>
            <flux:subheading class="text-sm">
                Contest {{ $metrics['total_files'] === 1 ? 'File' : 'Files' }}
            </flux:subheading>
        </flux:card>

        {{-- Days Active --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold {{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                {{ $metrics['days_active'] }}
            </div>
            <flux:subheading class="text-sm">
                {{ $metrics['days_active'] === 1 ? 'Day' : 'Days' }} Active
            </flux:subheading>
        </flux:card>
    </div>

    {{-- Winner Display (if completed) --}}
    @if($metrics['has_winner'] && $metrics['winner_entry'])
        <flux:card class="bg-gradient-to-r from-yellow-50 to-amber-50 dark:from-yellow-950 dark:to-amber-950 border-yellow-300 dark:border-yellow-700 border">
            <div class="flex items-center gap-4">
                <div class="flex-shrink-0">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-br from-yellow-400 to-amber-500 flex items-center justify-center">
                        <flux:icon.trophy variant="solid" class="w-10 h-10 text-white" />
                    </div>
                </div>
                <div class="flex-1">
                    <div class="text-sm font-medium text-yellow-700 dark:text-yellow-300 uppercase tracking-wide">Winner</div>
                    <div class="text-xl font-bold text-yellow-900 dark:text-yellow-100">
                        {{ $metrics['winner_entry']->user->name }}
                    </div>
                    @if($metrics['runner_up_entries']->count() > 0)
                        <div class="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                            Runner{{ $metrics['runner_up_entries']->count() > 1 ? 's' : '' }}-up:
                            {{ $metrics['runner_up_entries']->pluck('user.name')->join(', ') }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:card>
    @endif

    {{-- Quick Actions --}}
    <flux:card>
        <flux:heading size="base" class="mb-4">Quick Actions</flux:heading>

        <div class="flex flex-wrap gap-2">
            <flux:button wire:click="handleAction('switch-to-entries')" variant="ghost" size="sm" icon="users">
                View Entries
            </flux:button>

            <flux:button wire:click="handleAction('switch-to-prizes')" variant="ghost" size="sm" icon="trophy">
                Manage Prizes
            </flux:button>

            <flux:button wire:click="$parent.dispatch('switchTab', { tab: 'files' })" variant="ghost" size="sm" icon="folder-open">
                Contest Files
            </flux:button>

            <flux:button wire:click="$parent.dispatch('switchTab', { tab: 'settings' })" variant="ghost" size="sm" icon="cog-6-tooth">
                Settings
            </flux:button>

            @if($project->is_published)
                <flux:button href="{{ route('projects.show', $project) }}" wire:navigate variant="ghost" size="sm" icon="eye">
                    View Public Page
                </flux:button>
            @endif
        </div>
    </flux:card>
</div>
