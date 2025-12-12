@php
    $statusContext = $this->statusContext;
    $metrics = $this->projectMetrics;

    $colorClasses = match($statusContext['color']) {
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
            'bg' => 'bg-blue-50 dark:bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text' => 'text-blue-800 dark:text-blue-200',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'button' => 'bg-blue-600 hover:bg-blue-700 text-white',
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
    {{-- Status Context Card --}}
    <flux:card class="{{ $colorClasses['bg'] }} {{ $colorClasses['border'] }} border">
        <div class="flex items-start gap-4">
            <div class="flex-shrink-0">
                @switch($statusContext['icon'])
                    @case('document-text')
                        <flux:icon.document-text variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('check-circle')
                        <flux:icon.check-circle variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('check-badge')
                        <flux:icon.check-badge variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('eye')
                        <flux:icon.eye variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('arrow-path')
                        <flux:icon.arrow-path variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('user-plus')
                        <flux:icon.user-plus variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('clock')
                        <flux:icon.clock variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @case('rocket-launch')
                        <flux:icon.rocket-launch variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                        @break
                    @default
                        <flux:icon.information-circle variant="solid" class="w-10 h-10 {{ $colorClasses['icon'] }}" />
                @endswitch
            </div>

            <div class="flex-1">
                <flux:heading size="lg" class="{{ $colorClasses['text'] }}">
                    {{ $statusContext['heading'] }}
                </flux:heading>
                <flux:subheading class="{{ $colorClasses['text'] }} opacity-80 mt-1">
                    {{ $statusContext['message'] }}
                </flux:subheading>

                @if($statusContext['action'])
                    <div class="mt-4">
                        <flux:button
                            wire:click="handleAction('{{ $statusContext['action_event'] }}')"
                            size="sm"
                            class="{{ $colorClasses['button'] }}"
                        >
                            {{ $statusContext['action'] }}
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>
    </flux:card>

    {{-- Project Metrics Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Pitch Count --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                {{ $metrics['pitch_count'] }}
            </div>
            <flux:subheading class="text-sm">
                Total {{ $metrics['pitch_count'] === 1 ? 'Pitch' : 'Pitches' }}
            </flux:subheading>
        </flux:card>

        {{-- Files Count --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                {{ $metrics['total_files'] }}
            </div>
            <flux:subheading class="text-sm">
                Project {{ $metrics['total_files'] === 1 ? 'File' : 'Files' }}
            </flux:subheading>
        </flux:card>

        {{-- Days Active --}}
        <flux:card class="text-center">
            <div class="text-3xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                {{ $metrics['days_active'] }}
            </div>
            <flux:subheading class="text-sm">
                {{ $metrics['days_active'] === 1 ? 'Day' : 'Days' }} Active
            </flux:subheading>
        </flux:card>

        {{-- Action Needed Badge --}}
        <flux:card class="text-center">
            @php
                $actionCount = $metrics['pending_count'] + $metrics['ready_for_review_count'];
            @endphp
            <div class="text-3xl font-bold {{ $actionCount > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-green-600 dark:text-green-400' }}">
                {{ $actionCount }}
            </div>
            <flux:subheading class="text-sm">
                {{ $actionCount === 1 ? 'Action' : 'Actions' }} Needed
            </flux:subheading>
        </flux:card>
    </div>

    {{-- Pitch Status Breakdown (if pitches exist) --}}
    @if($metrics['pitch_count'] > 0)
        <flux:card>
            <flux:heading size="base" class="mb-4">Pitch Status Breakdown</flux:heading>

            <div class="space-y-3">
                @if($metrics['pending_count'] > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-yellow-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Pending Approval</span>
                        </div>
                        <flux:badge color="yellow">{{ $metrics['pending_count'] }}</flux:badge>
                    </div>
                @endif

                @if($metrics['in_progress_count'] > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-blue-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">In Progress</span>
                        </div>
                        <flux:badge color="blue">{{ $metrics['in_progress_count'] }}</flux:badge>
                    </div>
                @endif

                @if($metrics['ready_for_review_count'] > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-purple-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Ready for Review</span>
                        </div>
                        <flux:badge color="purple">{{ $metrics['ready_for_review_count'] }}</flux:badge>
                    </div>
                @endif

                @if($metrics['approved_count'] > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-green-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Approved</span>
                        </div>
                        <flux:badge color="green">{{ $metrics['approved_count'] }}</flux:badge>
                    </div>
                @endif

                @if($metrics['completed_count'] > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-600"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Completed</span>
                        </div>
                        <flux:badge color="green" class="bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200">{{ $metrics['completed_count'] }}</flux:badge>
                    </div>
                @endif

                @if($metrics['denied_count'] > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-red-500"></span>
                            <span class="text-sm text-gray-700 dark:text-gray-300">Denied</span>
                        </div>
                        <flux:badge color="red">{{ $metrics['denied_count'] }}</flux:badge>
                    </div>
                @endif
            </div>
        </flux:card>
    @endif

    {{-- Quick Actions --}}
    <flux:card>
        <flux:heading size="base" class="mb-4">Quick Actions</flux:heading>

        <div class="flex flex-wrap gap-2">
            <flux:button wire:click="handleAction('switch-to-pitches')" variant="ghost" size="sm" icon="paper-airplane">
                View Pitches
            </flux:button>

            <flux:button wire:click="$parent.dispatch('switchTab', { tab: 'files' })" variant="ghost" size="sm" icon="folder-open">
                Manage Files
            </flux:button>

            <flux:button wire:click="$parent.dispatch('switchTab', { tab: 'project' })" variant="ghost" size="sm" icon="cog-6-tooth">
                Project Settings
            </flux:button>

            @if($project->is_published)
                <flux:button href="{{ route('projects.show', $project) }}" wire:navigate variant="ghost" size="sm" icon="eye">
                    View Public Page
                </flux:button>
            @endif
        </div>
    </flux:card>
</div>
