@props([
    'project', 
    'hasPreviewTrack' => false, 
    'showEditButton' => true,
    'context' => 'view', // 'view', 'manage', 'client'
    'showActions' => true,
    'userPitch' => null,
    'canPitch' => false,
    'autoAllowAccess' => null
])

@php
    // Determine project status color and messaging based on UX guidelines
    $statusConfig = match($project->status) {
        'open' => [
            'color' => 'blue',
            'message' => 'Accepting Pitches',
            'icon' => 'folder-open'
        ],
        'in_progress' => [
            'color' => 'amber', 
            'message' => 'In Progress',
            'icon' => 'clock'
        ],
        'completed' => [
            'color' => 'green',
            'message' => 'Completed',
            'icon' => 'check-circle'
        ],
        'unpublished' => [
            'color' => 'gray',
            'message' => 'Draft',
            'icon' => 'document'
        ],
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
        // Project owner actions
        if ($project->status === 'unpublished' && !$project->isClientManagement()) {
            $primaryAction = [
                'action' => 'wire:click="publish"',
                'label' => 'Publish Project',
                'variant' => 'primary'
            ];
        } elseif ($project->status === 'open' && $project->pitches()->count() > 0) {
            $primaryAction = [
                'url' => '#pitch-review',
                'label' => 'Review Pitches',
                'variant' => 'primary'
            ];
        } elseif ($project->status === 'in_progress') {
            $primaryAction = [
                'url' => '#current-work',
                'label' => 'View Progress',
                'variant' => 'primary'
            ];
        } elseif ($project->status === 'open') {
            $primaryAction = [
                'type' => 'modal',
                'modal' => 'shareProject',
                'label' => 'Share Project',
                'variant' => 'outline'
            ];
        }
    } else {
        // Producer/collaborator actions
        if (!$userPitch && $project->status === 'open' && $canPitch) {
            $primaryAction = [
                'url' => "javascript:openPitchTermsModal()",
                'label' => 'Submit Pitch',
                'variant' => 'primary'
            ];
        } elseif ($userPitch) {
            $primaryAction = [
                'url' => route('projects.pitches.show', [$userPitch->project, $userPitch]),
                'navigate' => true,
                'label' => 'View My Pitch',
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
@endphp

<!-- Simplified Project Header -->
<flux:card class="mb-2">
    <!-- Main Header Content -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <!-- Project Identity -->
        <div class="flex items-start gap-3 min-w-0 flex-1">
            <!-- Project Type Icon -->
            <div class="flex-shrink-0 mt-1">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $statusConfig['color'] === 'blue' ? 'from-blue-500 to-indigo-600' : ($statusConfig['color'] === 'green' ? 'from-emerald-500 to-green-600' : ($statusConfig['color'] === 'amber' ? 'from-amber-500 to-yellow-600' : 'from-slate-400 to-slate-500')) }} flex items-center justify-center shadow-sm">
                    <flux:icon name="{{ $statusConfig['icon'] }}" class="w-5 h-5 text-white" />
                </div>
            </div>
            
            <!-- Project Info -->
            <div class="min-w-0 flex-1">
                <flux:heading size="lg" class="text-slate-900 dark:text-slate-100 mb-2">
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
                
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <!-- Status Badge -->
                    <flux:badge color="{{ $statusConfig['color'] }}" size="sm" icon="{{ $statusConfig['icon'] }}">
                        {{ $statusConfig['message'] }}
                    </flux:badge>
                    
                    <!-- Workflow Type -->
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {{ $project->readable_workflow_type }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Actions Dropdown -->
        @if($showActions)
            <div class="flex-shrink-0">
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="primary" size="base" icon="chevron-down" class="w-full sm:w-auto font-semibold">
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
                                <flux:menu.item wire:click="$toggle('autoAllowAccess')" icon="{{ $autoAllowAccess ?? $project->auto_allow_access ? 'check-circle' : 'x-circle' }}">
                                    {{ ($autoAllowAccess ?? $project->auto_allow_access) ? 'Disable' : 'Enable' }} Auto-Approve
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
    </div>
    
    <!-- Quick Stats Row -->
    <div class="border-t border-slate-200 dark:border-slate-700 mt-4 pt-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Stats -->
            <div class="flex flex-wrap items-center gap-4 text-sm">
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
    </div>
</flux:card>

<!-- Legacy Image Modal Support for Existing Features -->
@if($project->image_path && $context === 'manage')
    <!-- Simplified image display option -->
    <div class="mb-4" x-data="{ showImage: false }">
        <flux:button @click="showImage = !showImage" variant="ghost" size="sm" class="mb-2">
            <flux:icon name="photo" class="w-4 h-4 mr-2" />
            {{ $project->image_path ? 'Show Project Image' : 'Add Project Image' }}
        </flux:button>
        
        <div x-show="showImage" x-transition class="rounded-lg overflow-hidden">
            <img src="{{ $project->imageUrl }}" alt="{{ $project->name }}" class="w-full max-h-64 object-cover">
        </div>
    </div>
@endif 