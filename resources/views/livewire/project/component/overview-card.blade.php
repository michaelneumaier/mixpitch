<div class="space-y-4">
    {{-- Section 1: Current State Card --}}
    <flux:card class="{{ $workflowColors['bg'] }} {{ $workflowColors['border'] }}">
        <div class="flex items-start gap-4">
            {{-- Status Icon --}}
            <div class="w-12 h-12 rounded-xl flex items-center justify-center {{ $workflowColors['accent_bg'] }} border {{ $workflowColors['accent_border'] }}">
                <flux:icon :name="$this->currentStateContext['icon']" class="w-6 h-6 {{ $workflowColors['icon'] }}" />
            </div>

            {{-- Content --}}
            <div class="flex-1">
                <flux:heading size="lg" class="{{ $workflowColors['text_primary'] }}">
                    {{ $this->currentStateContext['title'] }}
                </flux:heading>
                <p class="text-sm {{ $workflowColors['text_muted'] }} mt-1">
                    {{ $this->currentStateContext['description'] }}
                </p>

                {{-- Progress Bar --}}
                @if($this->currentStateContext['progress'])
                    <div class="flex items-center gap-3 mt-3">
                        <div class="flex-1 bg-white/50 dark:bg-gray-800/50 rounded-full h-2 border {{ $workflowColors['accent_border'] }}">
                            <div class="bg-purple-600 dark:bg-purple-500 h-full rounded-full transition-all duration-500"
                                 style="width: {{ $this->currentStateContext['progress'] }}%"></div>
                        </div>
                        <span class="text-xs font-medium {{ $workflowColors['text_primary'] }} min-w-[3rem] text-right">
                            {{ $this->currentStateContext['progress'] }}%
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Client Feedback (CLIENT_REVISIONS_REQUESTED only) --}}
        @if($this->clientFeedback)
            <div class="mt-6 pt-6 border-t {{ $workflowColors['accent_border'] }}">
                <div class="flex items-start gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-amber-100 dark:bg-amber-900 border border-amber-200 dark:border-amber-800">
                        <flux:icon name="chat-bubble-left-right" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <flux:heading size="sm" class="text-amber-900 dark:text-amber-100">
                                Client Feedback
                            </flux:heading>
                            <flux:badge color="amber" size="sm">
                                Round {{ $this->clientFeedback['revision_round'] }}
                            </flux:badge>
                            <span class="text-xs text-amber-600 dark:text-amber-400">
                                {{ $this->clientFeedback['timestamp']->diffForHumans() }}
                            </span>
                        </div>
                        <div class="p-4 bg-amber-50 dark:bg-amber-950 rounded-lg border border-amber-200 dark:border-amber-800">
                            <p class="text-sm text-amber-900 dark:text-amber-100 whitespace-pre-wrap">{{ $this->clientFeedback['feedback'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- What's Next Section --}}
        @if(count($this->currentStateContext['next_steps']) > 0)
            <div class="mt-6 pt-6 border-t {{ $workflowColors['accent_border'] }}">
                <flux:heading size="sm" class="{{ $workflowColors['text_primary'] }} mb-3">
                    What's Next
                </flux:heading>
                <ul class="space-y-2">
                    @foreach($this->currentStateContext['next_steps'] as $step)
                        <li class="flex items-start gap-2 text-sm {{ $workflowColors['text_muted'] }}">
                            <flux:icon name="check-circle" class="w-4 h-4 mt-0.5 {{ $workflowColors['icon'] }} flex-shrink-0" />
                            <span>{{ $step }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Quick Actions --}}
        @if(count($this->quickActions) > 0)
            <div class="mt-6 pt-6 border-t {{ $workflowColors['accent_border'] }}">
                <div class="flex flex-wrap gap-2">
                    @foreach($this->quickActions as $action)
                        <flux:button
                            wire:click="{{ $action['action'] }}"
                            variant="{{ $action['variant'] }}"
                            size="sm"
                            :icon="$action['icon']"
                        >
                            {{ $action['label'] }}
                        </flux:button>
                    @endforeach
                </div>
            </div>
        @endif
    </flux:card>

    {{-- Section 2 & 3: Project Metrics and Client Engagement Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Project Metrics Card --}}
        <flux:card class="{{ $workflowColors['border'] }}">
            <div class="mb-4 flex items-center gap-2">
                <flux:icon name="chart-bar" class="{{ $workflowColors['icon'] }} h-5 w-5" />
                <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">
                    Project Metrics
                </flux:heading>
            </div>

            <div class="grid grid-cols-2 gap-3">
                {{-- Total Files --}}
                <div class="p-3 rounded-lg {{ $workflowColors['accent_bg'] }} border {{ $workflowColors['accent_border'] }}">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:icon name="document" class="{{ $workflowColors['icon'] }} h-4 w-4" />
                        <span class="text-xs {{ $workflowColors['text_muted'] }}">Total Files</span>
                    </div>
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] }}">
                        {{ $this->projectMetrics['total_files'] }}
                    </div>
                </div>

                {{-- Days Active --}}
                <div class="p-3 rounded-lg {{ $workflowColors['accent_bg'] }} border {{ $workflowColors['accent_border'] }}">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:icon name="calendar" class="{{ $workflowColors['icon'] }} h-4 w-4" />
                        <span class="text-xs {{ $workflowColors['text_muted'] }}">Days Active</span>
                    </div>
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] }}">
                        {{ $this->projectMetrics['days_active'] }}
                    </div>
                </div>

                {{-- Submissions --}}
                <div class="p-3 rounded-lg {{ $workflowColors['accent_bg'] }} border {{ $workflowColors['accent_border'] }}">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:icon name="paper-airplane" class="{{ $workflowColors['icon'] }} h-4 w-4" />
                        <span class="text-xs {{ $workflowColors['text_muted'] }}">Submissions</span>
                    </div>
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] }}">
                        {{ $this->projectMetrics['submission_count'] }}
                    </div>
                </div>

                {{-- Revisions --}}
                <div class="p-3 rounded-lg {{ $workflowColors['accent_bg'] }} border {{ $workflowColors['accent_border'] }}">
                    <div class="flex items-center gap-2 mb-1">
                        <flux:icon name="arrow-path" class="{{ $workflowColors['icon'] }} h-4 w-4" />
                        <span class="text-xs {{ $workflowColors['text_muted'] }}">Revisions</span>
                    </div>
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] }}">
                        {{ $this->projectMetrics['revision_round'] }}
                        @if($this->projectMetrics['included_revisions'] > 0)
                            <span class="text-sm font-normal {{ $workflowColors['text_muted'] }}">
                                of {{ $this->projectMetrics['included_revisions'] }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </flux:card>

        {{-- Client Engagement Card --}}
        <flux:card class="{{ $workflowColors['border'] }}">
            <div class="mb-4 flex items-center gap-2">
                <flux:icon name="user-circle" class="{{ $workflowColors['icon'] }} h-5 w-5" />
                <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">
                    Client Engagement
                </flux:heading>
            </div>

            {{-- Client Info --}}
            <div class="space-y-3">
                <div>
                    <div class="text-xs {{ $workflowColors['text_muted'] }} mb-1">Client Name</div>
                    <div class="text-sm font-medium {{ $workflowColors['text_primary'] }}">
                        {{ $this->clientEngagement['client_name'] ?: 'Not set' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs {{ $workflowColors['text_muted'] }} mb-1">Client Email</div>
                    <div class="text-sm font-medium {{ $workflowColors['text_primary'] }}">
                        {{ $this->clientEngagement['client_email'] ?: 'Not set' }}
                    </div>
                </div>

                <div class="pt-3 border-t {{ $workflowColors['accent_border'] }}">
                    <div class="text-xs {{ $workflowColors['text_muted'] }} mb-1">Portal Status</div>
                    <div class="text-sm font-medium {{ $workflowColors['text_primary'] }} mb-2">
                        {{ $this->clientEngagement['portal_status'] }}
                    </div>
                </div>

                <div>
                    <div class="text-xs {{ $workflowColors['text_muted'] }} mb-1">Last Client Action</div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] }}">
                        {{ $this->clientEngagement['last_client_action'] }}
                    </div>
                </div>

                {{-- Resend Invite Button --}}
                <div class="pt-3 border-t {{ $workflowColors['accent_border'] }}">
                    <flux:button
                        wire:click="$dispatch('resend-client-invite')"
                        variant="outline"
                        size="sm"
                        icon="envelope"
                        class="w-full"
                    >
                        Resend Client Invite
                    </flux:button>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- NEW Row 3: Communication Summary and Work Session Grid (v2) --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Section 5: Communication Summary Card --}}
        <flux:card class="{{ $workflowColors['border'] }}">
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <flux:icon name="chat-bubble-left-right" class="{{ $workflowColors['icon'] }} h-5 w-5" />
                    <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">
                        Communication
                    </flux:heading>
                    @if($this->communicationSummary['unread_count'] > 0)
                        <flux:badge color="purple" size="sm">
                            {{ $this->communicationSummary['unread_count'] }} new
                        </flux:badge>
                    @endif
                </div>
                <flux:button
                    wire:click="openCommunicationHub"
                    variant="ghost"
                    size="xs"
                    icon="arrow-top-right-on-square"
                >
                    Open Hub
                </flux:button>
            </div>

            @if($this->communicationSummary['has_pending_communication'])
                <div class="space-y-3">
                    {{-- Pending Actions List --}}
                    @foreach($this->communicationSummary['pending_actions'] as $action)
                        <div class="p-3 rounded-lg {{ $workflowColors['accent_bg'] }} border {{ $workflowColors['accent_border'] }}">
                            <div class="flex items-start gap-3">
                                {{-- Priority Icon --}}
                                <div class="flex-shrink-0">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full
                                        @if($action['priority'] === 'high') bg-red-100 dark:bg-red-900/50
                                        @elseif($action['priority'] === 'medium') bg-amber-100 dark:bg-amber-900/50
                                        @else bg-blue-100 dark:bg-blue-900/50
                                        @endif">
                                        <flux:icon
                                            :name="match($action['type']) {
                                                'revision_pending' => 'pencil',
                                                'unread_messages' => 'envelope',
                                                'unresolved_comments' => 'chat-bubble-left-ellipsis',
                                                default => 'information-circle'
                                            }"
                                            class="h-4 w-4
                                            @if($action['priority'] === 'high') text-red-600 dark:text-red-400
                                            @elseif($action['priority'] === 'medium') text-amber-600 dark:text-amber-400
                                            @else text-blue-600 dark:text-blue-400
                                            @endif"
                                        />
                                    </div>
                                </div>

                                {{-- Content --}}
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium {{ $workflowColors['text_primary'] }}">
                                        {{ $action['title'] }}
                                    </div>
                                    <div class="text-xs {{ $workflowColors['text_muted'] }} mt-1">
                                        {{ Str::limit($action['description'], 80) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Latest Message Preview (if unread messages exist) --}}
                    @if($this->communicationSummary['latest_message'])
                        @php
                            $message = $this->communicationSummary['latest_message'];
                            $isClientMessage = $message->event_type === App\Models\PitchEvent::TYPE_CLIENT_MESSAGE;
                        @endphp
                        <div class="pt-3 border-t {{ $workflowColors['accent_border'] }}">
                            <div class="text-xs {{ $workflowColors['text_muted'] }} mb-2">Latest Message</div>
                            <div class="flex items-start gap-2">
                                <flux:icon
                                    :name="$isClientMessage ? 'user-circle' : 'user'"
                                    class="{{ $workflowColors['icon'] }} h-4 w-4 mt-0.5"
                                />
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium {{ $workflowColors['text_secondary'] }}">
                                        {{ $isClientMessage ?
                                           ($message->metadata['client_name'] ?? 'Client') :
                                           ($message->user->name ?? 'Producer') }}
                                        <span class="{{ $workflowColors['text_muted'] }}">• {{ $message->created_at->diffForHumans() }}</span>
                                    </div>
                                    <div class="text-sm {{ $workflowColors['text_primary'] }} mt-1">
                                        {{ Str::limit($message->comment, 100) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @else
                {{-- Empty State --}}
                <div class="text-center py-8">
                    <flux:icon name="check-circle" class="{{ $workflowColors['icon'] }} h-12 w-12 mx-auto mb-3 opacity-50" />
                    <p class="text-sm {{ $workflowColors['text_muted'] }}">
                        All caught up! No pending communication.
                    </p>
                </div>
            @endif
        </flux:card>

        {{-- Section 6: Work Session Card --}}
        <flux:card class="{{ $workflowColors['border'] }}">
            <div class="mb-4 flex items-center gap-2">
                <flux:icon name="clock" class="{{ $workflowColors['icon'] }} h-5 w-5" />
                <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">
                    Work Session
                </flux:heading>
                @if($this->workSessionData['active_session'])
                    @php
                        $session = $this->workSessionData['active_session'];
                    @endphp
                    <flux:badge
                        :color="$session->status === 'active' ? 'green' : 'amber'"
                        size="sm"
                    >
                        {{ $session->status === 'active' ? 'Working' : 'Paused' }}
                    </flux:badge>
                @endif
            </div>

            {{-- Embedded Work Session Controls --}}
            <div class="mb-4">
                @persist('overview-work-session-' . $pitch->id)
                    @livewire('project.component.work-session-control', [
                        'project' => $project,
                        'pitch' => $pitch,
                        'variant' => 'embedded'
                    ], key('overview-work-session-'.$pitch->id))
                @endpersist
            </div>

            {{-- Session History --}}
            @if($this->workSessionData['has_sessions'])
                <div class="pt-4 border-t {{ $workflowColors['accent_border'] }}">
                    <div class="flex items-center justify-between mb-3">
                        <div class="text-xs font-medium {{ $workflowColors['text_secondary'] }}">
                            Recent Sessions
                        </div>
                        @if($this->workSessionData['total_work_time_formatted'] !== '0m')
                            <div class="text-xs {{ $workflowColors['text_muted'] }}">
                                Total: <span class="font-medium">{{ $this->workSessionData['total_work_time_formatted'] }}</span>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-2">
                        @php
                            $sessionsToShow = $this->showAllSessions
                                ? $this->workSessionData['recent_sessions']
                                : $this->workSessionData['recent_sessions']->take(3);
                        @endphp

                        @foreach($sessionsToShow as $session)
                            @if($session->status === 'ended')
                                <div class="flex items-center justify-between p-2 rounded {{ $workflowColors['accent_bg'] }}">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <flux:icon
                                            name="check-circle"
                                            class="h-3.5 w-3.5 text-green-600 dark:text-green-400 flex-shrink-0"
                                        />
                                        <div class="flex-1 min-w-0">
                                            <div class="text-xs {{ $workflowColors['text_primary'] }} truncate">
                                                {{ $session->notes ?: 'Work session' }}
                                            </div>
                                            <div class="text-xs {{ $workflowColors['text_muted'] }}">
                                                {{ $session->started_at->format('M j, g:i A') }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-xs font-medium {{ $workflowColors['text_secondary'] }} ml-2">
                                        {{ $session->getFormattedDuration() }}
                                    </div>
                                </div>
                            @endif
                        @endforeach

                        {{-- Show More/Less Button --}}
                        @if($this->workSessionData['recent_sessions']->count() > 3)
                            <div class="pt-2">
                                <flux:button
                                    wire:click="toggleSessionHistory"
                                    variant="ghost"
                                    size="xs"
                                    class="w-full"
                                >
                                    {{ $this->showAllSessions ? 'Show Less' : 'Show More' }}
                                    <flux:icon
                                        :name="$this->showAllSessions ? 'chevron-up' : 'chevron-down'"
                                        class="ml-1 h-3 w-3"
                                    />
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                {{-- No Sessions Empty State (only if no active session either) --}}
                @if(!$this->workSessionData['active_session'])
                    <div class="pt-4 border-t {{ $workflowColors['accent_border'] }} text-center py-4">
                        <p class="text-xs {{ $workflowColors['text_muted'] }}">
                            No sessions yet. Start tracking your work above.
                        </p>
                    </div>
                @endif
            @endif
        </flux:card>
    </div>

    {{-- Section 4: Recent Milestones --}}
    <flux:card class="{{ $workflowColors['border'] }}">
        <div class="mb-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <flux:icon name="clock" class="{{ $workflowColors['icon'] }} h-5 w-5" />
                <flux:heading size="base" class="{{ $workflowColors['text_primary'] }}">
                    Recent Milestones
                </flux:heading>
                @if($this->recentMilestones->count() > 0)
                    <flux:badge variant="outline" size="sm" class="{{ $workflowColors['text_secondary'] }}">
                        {{ $this->recentMilestones->count() }}
                    </flux:badge>
                @endif
            </div>
            <flux:button
                wire:click="switchToDeliveryTab"
                variant="ghost"
                size="xs"
            >
                View Full Timeline →
            </flux:button>
        </div>

        @if($this->recentMilestones->count() > 0)
            <div class="space-y-3">
                @foreach($this->recentMilestones as $milestone)
                    <div class="flex gap-3 pb-3 @if(!$loop->last) border-b {{ $workflowColors['accent_border'] }} @endif">
                        {{-- Icon --}}
                        <div class="flex-shrink-0">
                            <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $milestone['bg_color'] }}">
                                <flux:icon :name="$milestone['icon']" class="h-4 w-4 text-white" />
                            </div>
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-medium {{ $workflowColors['text_primary'] }}">
                                {{ $milestone['title'] }}
                            </div>
                            @if($milestone['description'])
                                <div class="text-xs {{ $workflowColors['text_muted'] }} mt-1">
                                    {{ Str::limit($milestone['description'], 100) }}
                                </div>
                            @endif
                            <div class="text-xs {{ $workflowColors['text_muted'] }} mt-1">
                                {{ $milestone['timestamp']->diffForHumans() }}
                                <span class="text-gray-400 dark:text-gray-600">•</span>
                                {{ $milestone['timestamp']->format('M j, Y g:i A') }}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8">
                <flux:icon name="clock" class="{{ $workflowColors['icon'] }} h-12 w-12 mx-auto mb-3 opacity-50" />
                <p class="text-sm {{ $workflowColors['text_muted'] }}">
                    No milestones yet. Submit your first version to get started.
                </p>
            </div>
        @endif
    </flux:card>
</div>
