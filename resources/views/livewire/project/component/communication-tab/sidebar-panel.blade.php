{{-- Desktop Sidebar Panel --}}

{{-- Pending Actions Card (Collapsible) --}}
<flux:card class="{{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }}">
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:icon name="bell-alert" class="{{ $workflowColors['icon'] ?? 'text-purple-600 dark:text-purple-400' }} h-5 w-5" />
            <flux:heading size="sm" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                Pending Actions
            </flux:heading>
            @if($this->pendingActions->count() > 0)
                <flux:badge color="red" size="sm">{{ $this->pendingActions->count() }}</flux:badge>
            @endif
        </div>
    </div>

    @if($this->pendingActions->count() > 0)
        <div class="space-y-2" x-data="{ expanded: null }">
            @foreach($this->pendingActions as $index => $action)
                <div class="overflow-hidden rounded-lg {{ $workflowColors['accent_bg'] ?? 'bg-purple-50 dark:bg-purple-900/30' }} border {{ $workflowColors['accent_border'] ?? 'border-purple-200 dark:border-purple-800' }}">
                    <button
                        @click="expanded = expanded === {{ $index }} ? null : {{ $index }}"
                        class="flex w-full items-center justify-between p-3 text-left transition-colors hover:bg-black/5 dark:hover:bg-white/5"
                    >
                        <div class="flex items-center gap-2">
                            {{-- Priority dot --}}
                            <span class="h-2 w-2 flex-shrink-0 rounded-full {{ match($action['priority']) {
                                'high' => 'bg-red-500',
                                'medium' => 'bg-amber-500',
                                default => 'bg-blue-500'
                            } }}"></span>
                            <span class="text-sm font-medium {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                                {{ $action['title'] }}
                            </span>
                        </div>
                        <flux:icon
                            name="chevron-down"
                            class="h-4 w-4 transition-transform {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}"
                            x-bind:class="expanded === {{ $index }} ? 'rotate-180' : ''"
                        />
                    </button>
                    <div x-show="expanded === {{ $index }}" x-collapse>
                        <div class="border-t {{ $workflowColors['accent_border'] ?? 'border-purple-200 dark:border-purple-800' }} px-3 pb-3 pt-2">
                            <p class="mb-2 text-xs {{ $workflowColors['text_muted'] ?? 'text-gray-600 dark:text-gray-400' }}">
                                {{ $action['description'] }}
                            </p>
                            @if(isset($action['action_url']))
                                @if ($action['type'] === 'revision_pending')
                                    <flux:button
                                        wire:click="navigateToTab('submission')"
                                        variant="outline"
                                        size="xs"
                                    >
                                        Respond to Feedback
                                    </flux:button>
                                @elseif ($action['type'] === 'unresolved_comments')
                                    <flux:button
                                        wire:click="navigateToTab('your-files')"
                                        variant="outline"
                                        size="xs"
                                    >
                                        View Comments
                                    </flux:button>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="py-4 text-center">
            <flux:icon name="check-circle" class="mx-auto mb-2 h-8 w-8 text-green-500" />
            <p class="text-sm {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">All caught up!</p>
        </div>
    @endif
</flux:card>

{{-- Activity Summary Card --}}
<flux:card class="{{ $workflowColors['border'] ?? 'border-gray-200 dark:border-gray-700' }}">
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <flux:icon name="clock" class="{{ $workflowColors['icon'] ?? 'text-purple-600 dark:text-purple-400' }} h-5 w-5" />
            <flux:heading size="sm" class="{{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                Recent Activity
            </flux:heading>
        </div>
        @if($this->activityTimeline->count() > 5)
            <flux:button
                wire:click="toggleActivityExpansion"
                variant="ghost"
                size="xs"
            >
                {{ $showAllActivity ? 'Show Less' : 'View All' }}
            </flux:button>
        @endif
    </div>

    @php
        $displayActivity = $showAllActivity ? $this->activityTimeline : $this->sidebarActivity;
    @endphp

    @if($displayActivity->count() > 0)
        <div class="space-y-3 {{ $showAllActivity ? 'max-h-[400px] overflow-y-auto pr-1' : '' }}">
            @foreach($displayActivity as $event)
                @php
                    $displayType = $this->getEventDisplayType($event);
                    $colorClass = $this->getEventColorClass($displayType);
                    $icon = $this->getEventIcon($displayType);
                @endphp

                <div class="flex items-start gap-2">
                    {{-- Icon --}}
                    <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full {{ $colorClass }}">
                        <flux:icon :name="$icon" class="h-3 w-3" />
                    </div>

                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-medium {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-white' }}">
                            @switch($displayType)
                                @case('client_message')
                                    {{ $event->metadata['client_name'] ?? 'Client' }} messaged
                                    @break
                                @case('producer_message')
                                    You sent a message
                                    @break
                                @case('approval')
                                    Client approved
                                    @break
                                @case('revision_request')
                                    Revisions requested
                                    @break
                                @case('status_change')
                                    Status: {{ ucfirst(str_replace('_', ' ', $event->status)) }}
                                    @break
                                @case('file_uploaded')
                                    File uploaded
                                    @break
                                @case('work_session_completed')
                                    Work session completed
                                    @break
                                @default
                                    Activity
                            @endswitch
                        </p>
                        <p class="text-xs {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">
                            {{ $event->created_at->diffForHumans() }}
                        </p>
                    </div>

                    {{-- Urgent indicator --}}
                    @if ($event->is_urgent)
                        <span class="h-2 w-2 flex-shrink-0 rounded-full bg-red-500" title="Urgent"></span>
                    @endif
                </div>
            @endforeach
        </div>

        @if(!$showAllActivity && $this->activityTimeline->count() > 5)
            <div class="mt-3 border-t {{ $workflowColors['accent_border'] ?? 'border-gray-200 dark:border-gray-700' }} pt-3 text-center">
                <p class="text-xs {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">
                    +{{ $this->activityTimeline->count() - 5 }} more events
                </p>
            </div>
        @endif
    @else
        <div class="py-4 text-center">
            <flux:icon name="clock" class="mx-auto mb-2 h-8 w-8 text-gray-400" />
            <p class="text-sm {{ $workflowColors['text_muted'] ?? 'text-gray-500 dark:text-gray-400' }}">No activity yet</p>
        </div>
    @endif
</flux:card>
