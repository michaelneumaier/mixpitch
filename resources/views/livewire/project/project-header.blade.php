<div>
@php
    // Determine project status color and messaging based on workflow type and status
    $statusConfig = match([$project->workflow_type, $project->status]) {
        // Standard Workflow
        ['standard', 'open'] => [
            'color' => 'blue',
            'message' => 'Accepting Pitches',
            'icon' => 'folder-open'
        ],
        ['standard', 'in_progress'] => [
            'color' => 'amber',
            'message' => 'Collaboration Active',
            'icon' => 'users'
        ],
        ['standard', 'completed'] => [
            'color' => 'green',
            'message' => 'Project Complete',
            'icon' => 'check-circle'
        ],
        ['standard', 'unpublished'] => [
            'color' => 'gray',
            'message' => 'Draft',
            'icon' => 'document'
        ],

        // Contest Workflow
        ['contest', 'open'] => [
            'color' => 'orange',
            'message' => 'Accepting Entries',
            'icon' => 'trophy'
        ],
        ['contest', 'in_progress'] => [
            'color' => 'amber',
            'message' => 'Contest Active',
            'icon' => 'clock'
        ],
        ['contest', 'completed'] => [
            'color' => 'green',
            'message' => 'Contest Complete',
            'icon' => 'trophy'
        ],
        ['contest', 'unpublished'] => [
            'color' => 'gray',
            'message' => 'Contest Draft',
            'icon' => 'document'
        ],

        // Direct Hire Workflow
        ['direct_hire', 'open'] => [
            'color' => 'green',
            'message' => 'Producer Assigned',
            'icon' => 'user-check'
        ],
        ['direct_hire', 'in_progress'] => [
            'color' => 'amber',
            'message' => 'Work in Progress',
            'icon' => 'cog-6-tooth'
        ],
        ['direct_hire', 'completed'] => [
            'color' => 'green',
            'message' => 'Work Complete',
            'icon' => 'check-circle'
        ],
        ['direct_hire', 'unpublished'] => [
            'color' => 'gray',
            'message' => 'Setup Draft',
            'icon' => 'document'
        ],

        // Client Management Workflow
        ['client_management', 'open'] => [
            'color' => 'purple',
            'message' => 'Setup Complete',
            'icon' => 'user-group'
        ],
        ['client_management', 'in_progress'] => [
            'color' => 'amber',
            'message' => 'Producer Working',
            'icon' => 'cog-6-tooth'
        ],
        ['client_management', 'completed'] => [
            'color' => 'green',
            'message' => 'Delivered to Client',
            'icon' => 'check-circle'
        ],
        ['client_management', 'unpublished'] => [
            'color' => 'gray',
            'message' => 'Client Setup Draft',
            'icon' => 'document'
        ],

        // Fallback for any unknown combinations
        default => [
            'color' => 'gray',
            'message' => ucfirst(str_replace('_', ' ', $project->status)),
            'icon' => 'information-circle'
        ]
    };

    // Determine primary action based on project state and user role
    $primaryAction = null;
    $user = auth()->user();

    if ($project->user_id === $user->id) {
        // Project owner actions - workflow-aware
        if ($project->status === 'unpublished' && !$project->isClientManagement()) {
            $labelMap = [
                'standard' => 'Publish Project',
                'contest' => 'Launch Contest',
                'direct_hire' => 'Start Collaboration',
                'client_management' => 'Setup Client Portal'
            ];
            $primaryAction = [
                'action' => 'wire:click="publish"',
                'label' => $labelMap[$project->workflow_type] ?? 'Publish Project',
                'variant' => 'primary'
            ];
        } elseif ($project->status === 'open' && $project->pitches()->count() > 0) {
            // Workflow-specific review labels
            $reviewLabels = [
                'standard' => 'Review Pitches',
                'contest' => 'Review Entries',
                'direct_hire' => 'Check Progress',
                'client_management' => 'Monitor Work'
            ];
            $primaryAction = [
                'url' => '#pitch-review',
                'label' => $reviewLabels[$project->workflow_type] ?? 'Review Pitches',
                'variant' => 'primary'
            ];
        } elseif ($project->status === 'in_progress') {
            // Workflow-specific progress labels
            $progressLabels = [
                'standard' => 'View Progress',
                'contest' => 'Monitor Contest',
                'direct_hire' => 'Check Work',
                'client_management' => 'View Deliverables'
            ];
            $primaryAction = [
                'url' => '#current-work',
                'label' => $progressLabels[$project->workflow_type] ?? 'View Progress',
                'variant' => 'primary'
            ];
        } elseif ($project->status === 'open') {
            // Different sharing behavior for different workflows
            if ($project->isClientManagement()) {
                $primaryAction = [
                    'type' => 'modal',
                    'modal' => 'clientPortalLink',
                    'label' => 'Share Client Portal',
                    'variant' => 'outline'
                ];
            } elseif ($project->isDirectHire()) {
                // Direct hire shouldn't show sharing options as it's private
                $primaryAction = [
                    'url' => '#collaboration',
                    'label' => 'View Collaboration',
                    'variant' => 'outline'
                ];
            } else {
                // Standard and Contest can be shared publicly
                $shareLabels = [
                    'standard' => 'Share Project',
                    'contest' => 'Share Contest'
                ];
                $primaryAction = [
                    'type' => 'modal',
                    'modal' => 'shareProject',
                    'label' => $shareLabels[$project->workflow_type] ?? 'Share Project',
                    'variant' => 'outline'
                ];
            }
        }
    } else {
        // Producer/collaborator actions - workflow-aware
        if (!$userPitch && $project->status === 'open' && $canPitch) {
            // Different action labels based on workflow type
            $actionLabels = [
                'standard' => 'Start Pitch',
                'contest' => 'Submit Entry',
                'direct_hire' => 'Accept Project',
                'client_management' => 'View Details' // Client management shouldn't allow public pitching
            ];

            // Client management and direct hire have different behaviors
            if ($project->isClientManagement()) {
                // Client management projects shouldn't show pitch options to non-collaborators
                $primaryAction = null;
            } elseif ($project->isDirectHire()) {
                // Direct hire should only show for the target producer
                if ($project->target_producer_id === $user->id) {
                    $primaryAction = [
                        'url' => "javascript:openPitchTermsModal()",
                        'label' => 'Accept Project',
                        'variant' => 'primary'
                    ];
                }
            } else {
                // Standard and Contest workflows allow public participation
                $primaryAction = [
                    'url' => "javascript:openPitchTermsModal()",
                    'label' => $actionLabels[$project->workflow_type] ?? 'Start Pitch',
                    'variant' => 'primary'
                ];
            }
        } elseif ($userPitch) {
            // Workflow-specific management labels
            $manageLabels = [
                'standard' => 'Manage Pitch',
                'contest' => 'Manage Entry',
                'direct_hire' => 'Manage Work',
                'client_management' => 'Manage Deliverables'
            ];
            $primaryAction = [
                'url' => route('projects.pitches.show', [$userPitch->project, $userPitch]),
                'navigate' => true,
                'label' => $manageLabels[$project->workflow_type] ?? 'Manage Pitch',
                'variant' => 'outline'
            ];
        }
    }

    // Calculate time status
    $timeStatus = '';
    if ($project->deadline && $project->status !== 'completed') {
        $daysRemaining = now()->diffInDays($project->deadline, false);
        if ($daysRemaining < 0) {
            $overdueDays = abs($daysRemaining);
            $timeStatus = 'Overdue by ' . $overdueDays . ' ' . ($overdueDays === 1 ? 'day' : 'days');
        } elseif ($daysRemaining === 0) {
            $timeStatus = 'Due today';
        } elseif ($daysRemaining <= 3) {
            $timeStatus = 'Due in ' . $daysRemaining . ' ' . ($daysRemaining === 1 ? 'day' : 'days');
        } else {
            $timeStatus = 'Due ' . $project->deadline->format('M j');
        }
    } elseif ($project->status === 'completed') {
        $timeStatus = 'Completed ' . $project->updated_at->diffForHumans();
    } else {
        $timeStatus = 'Created ' . $project->created_at->diffForHumans();
    }

    // Workflow Status Detection (only when showWorkflowStatus is true and context is 'manage')
    $workflowStatusData = null;
    if ($showWorkflowStatus && $context === 'manage' && $user) {
        // Determine the relevant pitch for status display
        $relevantPitch = null;

        if ($project->user_id === $user->id) {
            // Project owner - show the active pitch or most recent pitch
            if ($project->isClientManagement()) {
                $relevantPitch = $project->pitches()->first(); // Client management has only one pitch
            } elseif ($project->isDirectHire()) {
                $relevantPitch = $project->pitches()->first(); // Direct hire has only one pitch
            } elseif ($project->isContest()) {
                // For contests, we might want to show overall contest status rather than individual pitches
                $relevantPitch = null; // Will handle contest status separately
            } else {
                // Standard project - could show active pitch or summary
                $activePitch = $project->pitches()->whereIn('status', [
                    'in_progress', 'ready_for_review', 'approved'
                ])->first();
                $relevantPitch = $activePitch ?: $project->pitches()->latest()->first();
            }
        } else {
            // Producer/collaborator - show their pitch
            $relevantPitch = $project->pitches()->where('user_id', $user->id)->first();
        }

        // Prepare workflow status data
        if ($relevantPitch) {
            $workflowStatusData = [
                'pitch' => $relevantPitch,
                'type' => $project->workflow_type,
                'userRole' => $project->user_id === $user->id ? 'owner' : 'collaborator'
            ];
        } elseif ($project->user_id === $user->id) {
            // Project owner without pitches - show project-level status
            if ($project->isContest()) {
                $workflowStatusData = [
                    'type' => 'contest_overview',
                    'project' => $project,
                    'userRole' => 'owner'
                ];
            } else {
                // For other project types, show project status
                $workflowStatusData = [
                    'type' => 'project_status',
                    'project' => $project,
                    'userRole' => 'owner'
                ];
            }
        }
    }

    // Define workflow colors for status components
    $workflowColors = match($project->workflow_type) {
        'standard' => [
            'bg' => '!bg-blue-50 dark:!bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800',
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
            'accent_border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'color' => 'blue'
        ],
        'contest' => [
            'bg' => '!bg-orange-50 dark:!bg-orange-950',
            'border' => 'border-orange-200 dark:border-orange-800',
            'text_primary' => 'text-orange-900 dark:text-orange-100',
            'text_secondary' => 'text-orange-700 dark:text-orange-300',
            'text_muted' => 'text-orange-600 dark:text-orange-400',
            'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
            'accent_border' => 'border-orange-200 dark:border-orange-800',
            'icon' => 'text-orange-600 dark:text-orange-400',
            'color' => 'orange'
        ],
        'direct_hire' => [
            'bg' => '!bg-green-50 dark:!bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300',
            'text_muted' => 'text-green-600 dark:text-green-400',
            'accent_bg' => 'bg-green-100 dark:bg-green-900',
            'accent_border' => 'border-green-200 dark:border-green-800',
            'icon' => 'text-green-600 dark:text-green-400',
            'color' => 'green'
        ],
        'client_management' => [
            'bg' => '!bg-purple-50 dark:!bg-purple-950',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
            'accent_border' => 'border-purple-200 dark:border-purple-800',
            'icon' => 'text-purple-600 dark:text-purple-400',
            'color' => 'purple'
        ],
        default => [
            'bg' => '!bg-gray-50 dark:!bg-gray-950',
            'border' => 'border-gray-200 dark:border-gray-800',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'accent_bg' => 'bg-gray-100 dark:bg-gray-900',
            'accent_border' => 'border-gray-200 dark:border-gray-800',
            'icon' => 'text-gray-600 dark:text-gray-400',
            'color' => 'gray'
        ]
    };

    // Define semantic colors for status components
    $semanticColors = [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'accent' => 'bg-green-600 dark:bg-green-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'accent' => 'bg-amber-500'
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'accent' => 'bg-red-500'
        ]
    ];
@endphp

<!-- Enhanced Project Header with Image Support -->
<div class="-mx-2 -mt-2 mb-2 {{ $workflowColors['bg'] }} border-b-4 {{ $workflowColors['accent_border'] }} shadow-md px-6 py-6">
    <!-- Project Image Section (only show if image exists OR if user owns project and it's not client management) -->
    @if($project->image_path || ($context === 'manage' && $project->user_id === auth()->id() && !$project->isClientManagement()))
        <div class="mb-6">
            @if($project->image_path)
                <!-- Project has image - show with edit overlay for owners -->
                <div class="relative group">
                    <div class="relative w-full h-48 lg:h-64 rounded-xl overflow-hidden bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-700">
                        <img src="{{ $project->imageUrl }}"
                             alt="{{ $project->name }}"
                             class="w-full h-full object-cover">

                        <!-- Edit Overlay for project owners -->
                        @if($context === 'manage' && $project->user_id === auth()->id())
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-200 flex items-center justify-center">
                                <div class="flex gap-3">
                                    <flux:button
                                        wire:click="showImageUpload"
                                        variant="filled"
                                        size="sm"
                                        icon="camera"
                                        class="bg-white/20 backdrop-blur-sm hover:bg-white/30 border-white/30"
                                    >
                                        Change Image
                                    </flux:button>
                                    <flux:button
                                        wire:click="removeProjectImage"
                                        variant="outline"
                                        size="sm"
                                        icon="trash"
                                        class="bg-white/20 backdrop-blur-sm hover:bg-red-500/30 text-white border-white/30 hover:border-red-300"
                                    >
                                        Remove
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @else
                <!-- No image but user owns project - show compact outlined add image option -->
                @if($context === 'manage' && $project->user_id === auth()->id())
                    <div class="mb-4">
                        <div class="relative w-full h-12 rounded-lg bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 border-2 border-dashed border-slate-300 dark:border-slate-600 hover:border-blue-400 dark:hover:border-blue-500 transition-colors duration-200 group cursor-pointer"
                             wire:click="showImageUpload">
                            <div class="absolute inset-0 flex items-center justify-center text-slate-500 dark:text-slate-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors duration-200">
                                <flux:icon name="photo" class="w-4 h-4 mr-2" />
                                <span class="text-sm font-medium">Add Project Image</span>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    @endif

    <!-- Main Header Content -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 -m-4 -mb-2 md:m-0">
        <!-- Project Identity -->
        <div class="flex items-center gap-3 min-w-0 flex-1">
            <!-- Project Type Icon -->
            <div class="flex-shrink-0 mt-1">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $statusConfig['color'] === 'blue' ? 'from-blue-500 to-indigo-600' : ($statusConfig['color'] === 'green' ? 'from-emerald-500 to-green-600' : ($statusConfig['color'] === 'amber' ? 'from-amber-500 to-yellow-600' : 'from-slate-400 to-slate-500')) }} flex items-center justify-center shadow-sm">
                    <flux:icon name="{{ $statusConfig['icon'] }}" class="w-5 h-5 text-white" />
                </div>
            </div>

            <!-- Project Info -->
            <div class="min-w-0 flex-1">
                @if($context === 'manage' && $project->user_id === auth()->id())
                    {{-- Inline Title Editing for Project Owner --}}
                    <div x-data="{
                        editingTitle: false,
                        tempTitle: '{{ addslashes($project->name) }}',
                        originalTitle: '{{ addslashes($project->name) }}'
                    }" >
                        {{-- Display Mode --}}
                        <div x-show="!editingTitle" class="flex items-center gap-2 group">
                            <flux:heading size="lg" class="text-slate-900 dark:text-slate-100 !text-xl">
                                <span x-text="originalTitle"></span>
                            </flux:heading>
                            <button
                                @click="editingTitle = true; $nextTick(() => $refs.titleInput.focus())"
                                class="flex items-center justify-center opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity p-1 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-md touch-manipulation"
                                type="button"
                                aria-label="Edit project title">
                                <flux:icon.pencil class="w-4 h-4 text-slate-500" />
                            </button>
                        </div>

                        {{-- Edit Mode --}}
                        <div x-show="editingTitle" class="flex items-center gap-1.5 sm:gap-2 w-full max-w-full">
                            <input
                                x-ref="titleInput"
                                type="text"
                                x-model="tempTitle"
                                @keydown.enter="$wire.updateProjectTitle(tempTitle).then(() => { editingTitle = false; originalTitle = tempTitle; })"
                                @keydown.escape="tempTitle = originalTitle; editingTitle = false"
                                class="flex-1 min-w-0 px-2 sm:px-3 py-1 text-base sm:text-lg font-bold border border-slate-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                            <button
                                @click="$wire.updateProjectTitle(tempTitle).then(() => { editingTitle = false; originalTitle = tempTitle; })"
                                class="shrink-0 p-1.5 text-green-600 hover:bg-green-50 dark:hover:bg-green-950 rounded-lg transition-colors touch-manipulation"
                                type="button"
                                aria-label="Save title">
                                <flux:icon.check class="w-4 h-4" />
                            </button>
                            <button
                                @click="tempTitle = originalTitle; editingTitle = false"
                                class="shrink-0 p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-950 rounded-lg transition-colors touch-manipulation"
                                type="button"
                                aria-label="Cancel editing">
                                <flux:icon.x-mark class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Regular Display for View Context or Non-Owners --}}
                    <flux:heading level="1" class="text-slate-900 dark:text-slate-100 !text-xl">
                        @if($context === 'view')
                            {{ $project->name }}
                        @else
                            <a href="{{ route('projects.show', $project) }}"
                               wire:navigate
                               class="hover:text-blue-600 transition-colors duration-200">
                                {{ $project->name }}
                            </a>
                        @endif
                    </flux:heading>
                @endif

                <div class="hidden flex flex-wrap items-center gap-2 mb-1">
                    <!-- Status Badge -->
                    <flux:badge color="{{ $statusConfig['color'] }}" size="sm" icon="{{ $statusConfig['icon'] }}">
                        {{ $statusConfig['message'] }}
                    </flux:badge>

                    <!-- Workflow Type -->
                    <span class="hidden block text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {{ $project->readable_workflow_type }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Actions Section -->
        @if($showActions)
            <div class="flex-shrink-0 flex gap-2 items-center w-full sm:w-auto">
                @if($project->user_id === auth()->id())
                    {{-- Work Session Control (Client Management only) --}}
                    @if($project->isClientManagement() && $context === 'manage')
                        @php
                            $headerPitch = $project->pitches()->where('user_id', $project->user_id)->first();
                        @endphp
                        @if($headerPitch)
                            <div class="shrink-0">
                                @livewire('project.component.work-session-control', [
                                    'project' => $project,
                                    'pitch' => $headerPitch,
                                    'variant' => 'header'
                                ], key('work-session-header-actions-' . $project->id))
                            </div>
                        @endif
                    @endif

                    <!-- Project Owner: Show Manage Dropdown (hidden for client management in manage context) -->
                    @if(!($context === 'manage' && $project->isClientManagement()))
                    <div class="flex-1 min-w-0">
                        <flux:dropdown position="bottom" align="end">
                            <flux:button variant="primary" color="{{ $workflowColors['color'] }}" size="sm" icon="chevron-down" class="w-full font-semibold">
                                Manage
                            </flux:button>

                        <flux:menu>
                            <!-- Primary Action (if exists) -->
                            @if($primaryAction)
                                @if(isset($primaryAction['action']))
                                    <flux:menu.item wire:click="publish" icon="{{ $statusConfig['icon'] }}">
                                        {{ $primaryAction['label'] }}
                                    </flux:menu.item>
                                @elseif(isset($primaryAction['type']) && $primaryAction['type'] === 'modal')
                                    <flux:modal.trigger name="{{ $primaryAction['modal'] }}">
                                        <flux:menu.item icon="share">
                                            {{ $primaryAction['label'] }}
                                        </flux:menu.item>
                                    </flux:modal.trigger>
                                @else
                                    @if(isset($primaryAction['navigate']) && $primaryAction['navigate'])
                                        <flux:menu.item href="{{ $primaryAction['url'] }}" wire:navigate icon="{{ $statusConfig['icon'] }}">
                                            {{ $primaryAction['label'] }}
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item href="{{ $primaryAction['url'] }}" icon="{{ $statusConfig['icon'] }}">
                                            {{ $primaryAction['label'] }}
                                        </flux:menu.item>
                                    @endif
                                @endif
                                <flux:menu.separator />
                            @endif

                            <!-- Standard Actions -->
                            @if($context === 'manage')
                                @if($project->isClientManagement())
                                    <!-- Client Management Specific Actions -->
                                    <flux:menu.item wire:click="previewClientPortal" icon="eye">
                                        Preview Client Portal
                                    </flux:menu.item>

                                    <flux:menu.item wire:click="resendClientInvite" icon="envelope">
                                        Resend Client Invite
                                    </flux:menu.item>
                                @else
                                    <!-- Standard Project Actions -->
                                    <flux:menu.item href="{{ route('projects.show', $project) }}" wire:navigate icon="eye">
                                        View Public
                                    </flux:menu.item>
                                @endif

                                <!-- Edit/Settings -->
                                @if($showEditButton)
                                    <flux:menu.item href="{{ route('projects.edit', $project) }}" wire:navigate icon="cog-6-tooth">
                                        Project Settings
                                    </flux:menu.item>
                                @endif


                                <!-- Reddit Integration (Not for Client Management) -->
                                @if(!$project->isClientManagement())
                                    @if($project->hasBeenPostedToReddit())
                                        <flux:menu.item href="{{ $project->getRedditUrl() }}" target="_blank" icon="arrow-top-right-on-square">
                                            View on Reddit
                                        </flux:menu.item>
                                    @elseif($project->is_published)
                                        <flux:menu.item wire:click="postToReddit" icon="globe-alt">
                                            Post to r/MixPitch
                                        </flux:menu.item>
                                    @endif
                                @endif

                                <!-- Auto-Allow Access Toggle (for Standard/Contest projects) -->
                                @if(!$project->isClientManagement() && !$project->isDirectHire())
                                    <flux:menu.item wire:click="toggleAutoAllowAccess" icon="{{ $autoAllowAccess ? 'check-circle' : 'x-circle' }}">
                                        {{ $autoAllowAccess ? 'Disable' : 'Enable' }} Auto-Approve
                                    </flux:menu.item>
                                @endif

                                <!-- Sync Options -->
                                <flux:modal.trigger name="syncOptions">
                                    <flux:menu.item icon="cloud">
                                        Sync Options
                                    </flux:menu.item>
                                </flux:modal.trigger>

                                <!-- Backup History -->
                                <flux:modal.trigger name="backupHistory">
                                    <flux:menu.item icon="clock">
                                        Backup History
                                    </flux:menu.item>
                                </flux:modal.trigger>


                                <flux:menu.separator />

                                <!-- Publish/Unpublish Toggle (Not for Client Management) -->
                                @if($project->user_id === auth()->id() && !$project->isClientManagement())
                                    @if($project->is_published)
                                        <flux:menu.item wire:click="unpublish" icon="eye-slash">
                                            Unpublish Project
                                        </flux:menu.item>
                                    @elseif($project->status !== 'unpublished')
                                        <flux:menu.item wire:click="publish" icon="globe-alt">
                                            Publish Project
                                        </flux:menu.item>
                                    @endif
                                @endif

                                <!-- Additional Management Actions -->
                                @if($project->isContest() && auth()->check() && auth()->user()->can('judgeContest', $project))
                                    <flux:menu.separator />
                                    @if($project->isJudgingFinalized())
                                        <flux:menu.item href="{{ route('projects.contest.results', $project) }}" wire:navigate icon="trophy">
                                            View Results
                                        </flux:menu.item>
                                        <flux:menu.item href="{{ route('projects.contest.judging', $project) }}" wire:navigate icon="scale">
                                            Judging Dashboard
                                        </flux:menu.item>
                                    @else
                                        <flux:menu.item href="{{ route('projects.contest.judging', $project) }}" wire:navigate icon="scale">
                                            Judge Contest
                                        </flux:menu.item>
                                    @endif
                                @endif

                                <!-- Danger Zone -->
                                <flux:menu.separator />
                                <flux:menu.item wire:click="confirmDeleteProject" icon="trash" class="text-red-600 hover:text-red-700 hover:bg-red-50">
                                    Delete Project
                                </flux:menu.item>
                            @else
                                <!-- Non-manage context actions -->
                                <flux:menu.item href="{{ route('projects.manage', $project) }}" wire:navigate icon="cog-6-tooth">
                                    Manage Project
                                </flux:menu.item>

                                @if($showEditButton)
                                    <flux:menu.item href="{{ route('projects.edit', $project) }}" wire:navigate icon="pencil">
                                        Edit Details
                                    </flux:menu.item>
                                @endif
                            @endif
                        </flux:menu>
                    </flux:dropdown>
                    </div>
                    @endif

                    {{-- Client dropdown for client management projects --}}
                    @if($context === 'manage' && $project->isClientManagement() && $project->client)
                        <div class="flex-1 min-w-0">
                            <flux:dropdown position="bottom" align="end">
                                <flux:button variant="primary" color="{{ $workflowColors['color'] }}" size="sm" icon="user" icon-trailing="chevron-down" class="w-full font-semibold">
                                    Client
                                </flux:button>

                                <flux:menu class="min-w-64">
                                    {{-- Client Info Header --}}
                                    <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                        <div class="font-medium text-sm">{{ $project->client->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $project->client->email }}</div>
                                        @if($project->client->company)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ $project->client->company }}</div>
                                        @endif
                                    </div>

                                    {{-- Client Management Link --}}
                                    <flux:menu.item href="{{ route('producer.client-detail', $project->client) }}" wire:navigate icon="user-circle">
                                        View Client Profile
                                    </flux:menu.item>

                                    {{-- Other Projects with this Client --}}
                                    @if($this->clientProjects->count() > 0)
                                        <flux:menu.separator />
                                        <div class="px-3 py-1.5 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Other Projects
                                        </div>
                                        @foreach($this->clientProjects as $clientProject)
                                            <flux:menu.item href="{{ route('projects.manage-client', $clientProject) }}" wire:navigate icon="folder">
                                                {{ Str::limit($clientProject->name, 30) }}
                                            </flux:menu.item>
                                        @endforeach
                                    @endif
                                </flux:menu>
                            </flux:dropdown>
                        </div>
                    @endif
                @else
                    <!-- Non-Owner: Show Single Action Button -->
                    @if($primaryAction)
                        @if(isset($primaryAction['action']))
                            <flux:button
                                wire:click="publish"
                                variant="{{ $primaryAction['variant'] }}"
                                size="base"
                                class="w-full sm:w-auto font-semibold"
                            >
                                {{ $primaryAction['label'] }}
                            </flux:button>
                        @elseif(isset($primaryAction['type']) && $primaryAction['type'] === 'modal')
                            <flux:modal.trigger name="{{ $primaryAction['modal'] }}">
                                <flux:button
                                    variant="{{ $primaryAction['variant'] }}"
                                    size="base"
                                    class="w-full sm:w-auto font-semibold"
                                >
                                    {{ $primaryAction['label'] }}
                                </flux:button>
                            </flux:modal.trigger>
                        @else
                            @if(isset($primaryAction['navigate']) && $primaryAction['navigate'])
                                <flux:button
                                    href="{{ $primaryAction['url'] }}"
                                    wire:navigate
                                    variant="{{ $primaryAction['variant'] }}"
                                    size="base"
                                    class="w-full sm:w-auto font-semibold"
                                >
                                    {{ $primaryAction['label'] }}
                                </flux:button>
                            @else
                                <flux:button
                                    href="{{ $primaryAction['url'] }}"
                                    variant="{{ $primaryAction['variant'] }}"
                                    size="base"
                                    class="w-full sm:w-auto font-semibold"
                                >
                                    {{ $primaryAction['label'] }}
                                </flux:button>
                            @endif
                        @endif
                    @endif
                @endif
            </div>
        @endif
    </div>

    <!-- Workflow Status Section -->
    @if($workflowStatusData)
        @php
            // Create a compact status display
            $statusDisplay = null;

            if (isset($workflowStatusData['pitch'])) {
                $pitch = $workflowStatusData['pitch'];
                $workflowType = $workflowStatusData['type'];

                // Get status info based on workflow type and payment/milestone status
                $statusInfo = match($pitch->status) {
                    'pending' => ['text' => 'Awaiting Approval', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'clock'],
                    'awaiting_acceptance' => ['text' => 'Awaiting Your Response', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'bell'],
                    'in_progress' => ['text' => 'Work in Progress', 'color' => 'text-blue-600 dark:text-blue-400', 'icon' => 'cog-6-tooth'],
                    'ready_for_review' => ['text' => 'Submitted for Review', 'color' => 'text-purple-600 dark:text-purple-400', 'icon' => 'eye'],
                    'pending_review' => ['text' => 'Under Review', 'color' => 'text-purple-600 dark:text-purple-400', 'icon' => 'magnifying-glass'],
                    'approved' => (function() use ($pitch) {
                        // Check payment status for approved work
                        if ($pitch->payment_amount > 0 && !in_array($pitch->payment_status, ['paid', 'payment_not_required'])) {
                            return ['text' => 'Approved - Payment Pending', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'credit-card'];
                        }
                        return ['text' => 'Work Approved', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'check-circle'];
                    })(),
                    'completed' => (function() use ($pitch) {
                        // Check milestone and payment status for completed work
                        $hasMilestones = $pitch->milestones()->count() > 0;

                        if ($hasMilestones) {
                            $allMilestonesPaid = $pitch->milestones()
                                ->where('payment_status', '!=', 'paid')
                                ->count() === 0;

                            if (!$allMilestonesPaid) {
                                return ['text' => 'Completed - Milestones Pending', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'flag'];
                            }
                            return ['text' => 'Completed - Deliverables Available', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'check-circle'];
                        }

                        if ($pitch->payment_amount > 0 && !in_array($pitch->payment_status, ['paid', 'payment_not_required'])) {
                            return ['text' => 'Completed - Payment Pending', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'credit-card'];
                        }

                        return ['text' => 'Completed - Deliverables Available', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'check-circle'];
                    })(),
                    'revisions_requested' => ['text' => 'Revisions Requested', 'color' => 'text-orange-600 dark:text-orange-400', 'icon' => 'arrow-path'],
                    'client_revisions_requested' => ['text' => 'Client Revisions Requested', 'color' => 'text-orange-600 dark:text-orange-400', 'icon' => 'pencil'],
                    'denied' => ['text' => 'Not Approved', 'color' => 'text-red-600 dark:text-red-400', 'icon' => 'x-circle'],
                    'closed' => ['text' => 'Closed', 'color' => 'text-gray-600 dark:text-gray-400', 'icon' => 'lock-closed'],
                    'contest_entry' => ['text' => 'Contest Entry', 'color' => 'text-orange-600 dark:text-orange-400', 'icon' => 'trophy'],
                    'contest_winner' => ['text' => 'Contest Winner!', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'trophy'],
                    'contest_runner_up' => ['text' => 'Runner-Up', 'color' => 'text-blue-600 dark:text-blue-400', 'icon' => 'star'],
                    'contest_not_selected' => ['text' => 'Contest Complete', 'color' => 'text-gray-600 dark:text-gray-400', 'icon' => 'check'],
                    default => ['text' => ucfirst(str_replace('_', ' ', $pitch->status)), 'color' => 'text-gray-600 dark:text-gray-400', 'icon' => 'information-circle']
                };

                $statusDisplay = [
                    'text' => $statusInfo['text'],
                    'color' => $statusInfo['color'],
                    'icon' => $statusInfo['icon'],
                    'files' => $pitch->files->count()
                ];
            } elseif (isset($workflowStatusData['type']) && $workflowStatusData['type'] === 'contest_overview') {
                $entriesCount = $project->pitches()->count();
                $statusDisplay = [
                    'text' => $entriesCount . ' ' . ($entriesCount === 1 ? 'Entry' : 'Entries'),
                    'color' => 'text-orange-600 dark:text-orange-400',
                    'icon' => 'trophy',
                    'files' => null
                ];
            } elseif (isset($workflowStatusData['type']) && $workflowStatusData['type'] === 'project_status') {
                // Use sophisticated project workflow logic
                $currentProject = $workflowStatusData['project'];
                $pitchCount = $currentProject->pitches()->count();
                $approvedPitch = $currentProject->pitches()->where('status', 'approved')->first();
                $completedPitch = $currentProject->pitches()->where('status', 'completed')->first();

                // Determine current focus based on project status and workflow type (from project workflow status component)
                $currentFocus = ['text' => '', 'color' => '', 'icon' => '', 'urgency' => 'normal'];

                if ($currentProject->isContest()) {
                    if (!$currentProject->is_published) {
                        $currentFocus = ['text' => 'Ready to Launch Contest', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'megaphone', 'urgency' => 'warning'];
                    } elseif ($currentProject->deadline && now()->gt($currentProject->deadline) && !$currentProject->isJudgingFinalized()) {
                        $currentFocus = ['text' => 'Ready for Judging', 'color' => 'text-red-600 dark:text-red-400', 'icon' => 'scale', 'urgency' => 'urgent'];
                    } elseif ($currentProject->isJudgingFinalized()) {
                        $currentFocus = ['text' => 'Contest Complete', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'trophy', 'urgency' => 'normal'];
                    } elseif ($currentProject->deadline) {
                        $daysLeft = now()->diffInDays($currentProject->deadline, false);
                        if ($daysLeft > 0) {
                            $urgency = $daysLeft <= 3 ? 'warning' : 'normal';
                            $color = $urgency === 'warning' ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400';
                            $currentFocus = ['text' => "Contest Active ({$daysLeft} days left)", 'color' => $color, 'icon' => 'clock', 'urgency' => $urgency];
                        }
                    }
                } elseif ($currentProject->isClientManagement()) {
                    if ($completedPitch) {
                        $currentFocus = ['text' => 'Project Delivered', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'check-circle', 'urgency' => 'normal'];
                    } elseif ($approvedPitch) {
                        $workSubmitted = $approvedPitch->status === 'ready_for_review';
                        $revisionsRequested = $approvedPitch->status === 'client_revisions_requested';

                        if ($revisionsRequested) {
                            $currentFocus = ['text' => 'Addressing Client Revisions', 'color' => 'text-orange-600 dark:text-orange-400', 'icon' => 'pencil', 'urgency' => 'normal'];
                        } elseif ($workSubmitted) {
                            $currentFocus = ['text' => 'Client Review Pending', 'color' => 'text-purple-600 dark:text-purple-400', 'icon' => 'eye', 'urgency' => 'normal'];
                        } else {
                            $currentFocus = ['text' => 'Work in Progress', 'color' => 'text-blue-600 dark:text-blue-400', 'icon' => 'cog-6-tooth', 'urgency' => 'normal'];
                        }
                    } else {
                        $currentFocus = ['text' => 'Setup Client Review', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'user-group', 'urgency' => 'warning'];
                    }
                } else {
                    // Standard and Direct Hire workflows
                    if (!$currentProject->is_published) {
                        $currentFocus = ['text' => 'Ready to Publish', 'color' => 'text-amber-600 dark:text-amber-400', 'icon' => 'globe-alt', 'urgency' => 'warning'];
                    } elseif ($completedPitch) {
                        $requiresPayment = $currentProject->budget > 0;
                        $paymentStatus = $completedPitch->payment_status;

                        if ($requiresPayment && in_array($paymentStatus, ['pending', 'failed', null])) {
                            $currentFocus = ['text' => 'Payment Required', 'color' => 'text-red-600 dark:text-red-400', 'icon' => 'credit-card', 'urgency' => 'urgent'];
                        } else {
                            $currentFocus = ['text' => 'Project Complete', 'color' => 'text-green-600 dark:text-green-400', 'icon' => 'check-circle', 'urgency' => 'normal'];
                        }
                    } elseif ($approvedPitch) {
                        $workSubmitted = $approvedPitch->status === 'ready_for_review';
                        $revisionRequested = $approvedPitch->status === 'revisions_requested';

                        if ($workSubmitted) {
                            $currentFocus = ['text' => 'Review Submitted Work', 'color' => 'text-red-600 dark:text-red-400', 'icon' => 'eye', 'urgency' => 'urgent'];
                        } elseif ($revisionRequested) {
                            $currentFocus = ['text' => 'Revisions in Progress', 'color' => 'text-blue-600 dark:text-blue-400', 'icon' => 'arrow-path', 'urgency' => 'normal'];
                        } else {
                            $currentFocus = ['text' => 'Work in Progress', 'color' => 'text-blue-600 dark:text-blue-400', 'icon' => 'cog-6-tooth', 'urgency' => 'normal'];
                        }
                    } elseif ($pitchCount > 0) {
                        $pendingPitches = $currentProject->pitches()->where('status', 'pending')->count();
                        $currentFocus = ['text' => "Review {$pendingPitches} Pitch" . ($pendingPitches !== 1 ? 'es' : ''), 'color' => 'text-red-600 dark:text-red-400', 'icon' => 'clipboard-document-list', 'urgency' => 'urgent'];
                    } else {
                        $daysSincePublished = $currentProject->created_at->diffInDays(now());
                        $urgency = $daysSincePublished > 7 ? 'warning' : 'normal';
                        $color = $urgency === 'warning' ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400';
                        $currentFocus = ['text' => 'Waiting for Pitches', 'color' => $color, 'icon' => 'share', 'urgency' => $urgency];
                    }
                }

                $statusDisplay = [
                    'text' => $currentFocus['text'],
                    'color' => $currentFocus['color'],
                    'icon' => $currentFocus['icon'],
                    'files' => null
                ];
            }
        @endphp

        @if($statusDisplay)
            <div class="hidden flex items-center gap-4 text-sm mt-2">
                <div class="flex items-center gap-1.5">
                    <flux:icon name="{{ $statusDisplay['icon'] }}" class="w-4 h-4 {{ $statusDisplay['color'] }}" />
                    <span class="font-medium {{ $statusDisplay['color'] }}">
                        {{ $statusDisplay['text'] }}
                    </span>
                </div>
                @if($statusDisplay['files'] !== null && $statusDisplay['files'] > 0)
                    <div class="flex items-center gap-1 text-slate-500 dark:text-slate-400">
                        <flux:icon name="document" class="w-3 h-3" />
                        <span class="text-xs">{{ $statusDisplay['files'] }} {{ $statusDisplay['files'] === 1 ? 'file' : 'files' }}</span>
                    </div>
                @endif
            </div>
        @endif
    @endif

    <!-- Quick Stats Row - Scrollable -->
     @if($context === 'view')
    <div class="border-t {{ $workflowColors['border'] }} mt-4 -mx-6 -mb-6 py-3 bg-white/70 dark:bg-black/20 inset-shadow-sm">
        <div class="relative px-2 md:px-6">
            <!-- Scrollable container with Alpine.js scroll tracking -->
            <div
                x-data="{ scrollPercentage: 0, hasOverflow: false }"
                x-init="
                    $watch('scrollPercentage', value => {
                        $el.style.setProperty('--scroll-percentage', value + '%');
                    });

                    const updateScroll = () => {
                        // Check if there's overflow content
                        hasOverflow = $el.scrollWidth > $el.clientWidth;

                        // Calculate scroll percentage
                        const percentage = Math.abs($el.scrollLeft) / ($el.scrollWidth - $el.clientWidth) * 100;
                        scrollPercentage = percentage || 0;
                    };

                    $el.addEventListener('scroll', updateScroll);
                    new ResizeObserver(updateScroll).observe($el);
                    updateScroll();
                "
                class="flex items-center gap-4 text-sm overflow-x-auto overflow-y-hidden flux-no-scrollbar [--scroll-percentage:0%]"
                :class="hasOverflow ? 'mask-r-from-[max(calc(100%-6rem),var(--scroll-percentage))]' : ''"
            >
                <!-- Status Badge -->
                <div class="flex-shrink-0">
                    <flux:badge color="{{ $statusConfig['color'] }}" size="sm" icon="{{ $statusConfig['icon'] }}">
                        {{ $statusConfig['message'] }}
                    </flux:badge>
                </div>

                <!-- Setup Checklist (rendered based on context) -->
                @if($context === 'manage' && auth()->check())
                    <div class="flex-shrink-0">
                        @if($project->isClientManagement())
                            @php
                                $pitch = $project->pitches()->where('user_id', $project->user_id)->first();
                            @endphp
                            @if($pitch)
                                @livewire('client-project-setup-checklist', ['project' => $project, 'pitch' => $pitch, 'variant' => 'badge'], key('client-setup-checklist-' . $project->id))
                            @endif
                        @else
                            @livewire('project-setup-checklist', ['project' => $project, 'variant' => 'badge'], key('project-setup-checklist-' . $project->id))
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Hidden Stats (kept for future use) -->
        <div class="hidden">

                <!-- Pitch Count (Not for Client Management) -->
                @if(!$project->isClientManagement())
                    <div class="flex items-center gap-1.5">
                        <flux:icon name="paper-airplane" class="w-4 h-4 text-slate-500" />
                        <span class="font-medium text-slate-900 dark:text-slate-100">
                            {{ $project->pitches()->count() }}
                        </span>
                        <span class="text-slate-500">
                            {{ $project->pitches()->count() === 1 ? 'pitch' : 'pitches' }}
                        </span>
                    </div>
                @endif

                <!-- Budget -->
                @if($project->budget)
                    <div class="flex items-center gap-1.5">
                        <flux:icon name="currency-dollar" class="w-4 h-4 text-slate-500" />
                        <span class="font-medium text-slate-900 dark:text-slate-100">
                            ${{ number_format($project->budget) }}
                        </span>
                    </div>
                @endif

                <!-- Time Status -->
                @if($timeStatus)
                    <div class="flex items-center gap-1.5">
                        <flux:icon name="clock" class="w-4 h-4 {{ str_contains($timeStatus, 'Overdue') ? 'text-red-500' : (str_contains($timeStatus, 'Due') ? 'text-amber-500' : 'text-slate-500') }}" />
                        <span class="font-medium {{ str_contains($timeStatus, 'Overdue') ? 'text-red-600' : (str_contains($timeStatus, 'Due') ? 'text-amber-600' : 'text-slate-900 dark:text-slate-100') }}">
                            {{ $timeStatus }}
                        </span>
                    </div>
                @endif

                <!-- Artist Name -->
                @if($project->artist_name)
                    <div class="flex items-center gap-1.5">
                        <flux:icon name="microphone" class="w-4 h-4 text-slate-500" />
                        <span class="font-medium text-slate-900 dark:text-slate-100">
                            {{ $project->artist_name }}
                        </span>
                    </div>
                @endif
        </div>
    </div>
    @endif
</div>
</div>
