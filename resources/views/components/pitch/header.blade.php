@props([
    'pitch',
    'project' => null,
    'context' => 'view', // 'view', 'manage'
    'showActions' => true
])

@php
    $project = $project ?: $pitch->project;
    
    // Determine pitch status color and messaging
    $statusConfig = match($pitch->status) {
        \App\Models\Pitch::STATUS_PENDING => [
            'color' => 'amber',
            'message' => 'Awaiting Access',
            'icon' => 'clock'
        ],
        \App\Models\Pitch::STATUS_IN_PROGRESS => [
            'color' => 'blue',
            'message' => 'In Progress',
            'icon' => 'cog-6-tooth'
        ],
        \App\Models\Pitch::STATUS_CONTEST_ENTRY => [
            'color' => 'orange',
            'message' => 'Contest Entry',
            'icon' => 'trophy'
        ],
        \App\Models\Pitch::STATUS_READY_FOR_REVIEW => [
            'color' => 'purple',
            'message' => 'Ready for Review',
            'icon' => 'paper-airplane'
        ],
        \App\Models\Pitch::STATUS_PENDING_REVIEW => [
            'color' => 'purple',
            'message' => 'Under Review',
            'icon' => 'eye'
        ],
        \App\Models\Pitch::STATUS_APPROVED => [
            'color' => 'green',
            'message' => 'Approved',
            'icon' => 'check-circle'
        ],
        \App\Models\Pitch::STATUS_REVISIONS_REQUESTED => [
            'color' => 'amber',
            'message' => 'Revisions Requested',
            'icon' => 'arrow-path'
        ],
        \App\Models\Pitch::STATUS_DENIED => [
            'color' => 'red',
            'message' => 'Not Approved',
            'icon' => 'x-circle'
        ],
        \App\Models\Pitch::STATUS_COMPLETED => [
            'color' => 'green',
            'message' => 'Completed',
            'icon' => 'check-circle'
        ],
        \App\Models\Pitch::STATUS_CONTEST_WINNER => [
            'color' => 'yellow',
            'message' => 'Contest Winner',
            'icon' => 'trophy'
        ],
        \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP => [
            'color' => 'blue',
            'message' => 'Runner-Up',
            'icon' => 'star'
        ],
        \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED => [
            'color' => 'gray',
            'message' => 'Contest Complete',
            'icon' => 'user'
        ],
        default => [
            'color' => 'gray',
            'message' => ucfirst(str_replace('_', ' ', $pitch->status)),
            'icon' => 'information-circle'
        ]
    };

    // Determine primary action based on pitch state and user role
    $primaryAction = null;
    $user = auth()->user();
    
    if ($pitch->user_id === $user->id) {
        // Pitch owner actions
        switch ($pitch->status) {
            case \App\Models\Pitch::STATUS_IN_PROGRESS:
                if ($pitch->files->count() > 0) {
                    $primaryAction = [
                        'action' => 'wire:click="submitForReview"',
                        'label' => 'Submit for Review',
                        'variant' => 'primary'
                    ];
                } else {
                    $primaryAction = [
                        'url' => '#project-files',
                        'label' => 'Download Project Files',
                        'variant' => 'primary'
                    ];
                }
                break;
                
            case \App\Models\Pitch::STATUS_REVISIONS_REQUESTED:
                $primaryAction = [
                    'url' => '#feedback',
                    'label' => 'Review Feedback',
                    'variant' => 'primary'
                ];
                break;
                
            case \App\Models\Pitch::STATUS_CONTEST_ENTRY:
                if ($pitch->files->count() === 0) {
                    $primaryAction = [
                        'url' => '#project-files',
                        'label' => 'Start Contest Entry',
                        'variant' => 'primary'
                    ];
                }
                break;
                
            case \App\Models\Pitch::STATUS_APPROVED:
                if ($pitch->files->count() === 0) {
                    $primaryAction = [
                        'url' => '#project-files',
                        'label' => 'Download Files',
                        'variant' => 'primary'
                    ];
                }
                break;
        }
    }

    // Calculate time status
    $timeStatus = '';
    if ($project->deadline && !in_array($pitch->status, [\App\Models\Pitch::STATUS_COMPLETED, \App\Models\Pitch::STATUS_CONTEST_WINNER, \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP, \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED])) {
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
    } elseif (in_array($pitch->status, [\App\Models\Pitch::STATUS_COMPLETED, \App\Models\Pitch::STATUS_CONTEST_WINNER, \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP, \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED])) {
        $timeStatus = 'Completed ' . $pitch->updated_at->diffForHumans();
    } else {
        $timeStatus = 'Created ' . $pitch->created_at->diffForHumans();
    }
@endphp

<!-- Pitch Header Component -->
<flux:card class="mb-2">
    <!-- Main Header Content -->
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <!-- Pitch Identity -->
        <div class="flex items-start gap-3 min-w-0 flex-1">
            <!-- Pitch Status Icon -->
            <div class="flex-shrink-0 mt-1">
                <div class="w-10 h-10 rounded-lg bg-gradient-to-br {{ $statusConfig['color'] === 'blue' ? 'from-blue-500 to-indigo-600' : ($statusConfig['color'] === 'green' ? 'from-emerald-500 to-green-600' : ($statusConfig['color'] === 'amber' ? 'from-amber-500 to-yellow-600' : ($statusConfig['color'] === 'purple' ? 'from-purple-500 to-indigo-600' : ($statusConfig['color'] === 'orange' ? 'from-orange-500 to-amber-600' : ($statusConfig['color'] === 'red' ? 'from-red-500 to-red-600' : ($statusConfig['color'] === 'yellow' ? 'from-yellow-400 to-amber-500' : 'from-slate-400 to-slate-500')))))) }} flex items-center justify-center shadow-sm">
                    <flux:icon name="{{ $statusConfig['icon'] }}" class="w-5 h-5 text-white" />
                </div>
            </div>
            
            <!-- Pitch Info -->
            <div class="min-w-0 flex-1">
                <flux:heading size="lg" class="text-slate-900 dark:text-slate-100 mb-2">
                    Your Pitch for "{{ $project->name }}"
                </flux:heading>
                
                <div class="flex flex-wrap items-center gap-2 mb-1">
                    <!-- Status Badge -->
                    <flux:badge color="{{ $statusConfig['color'] }}" size="sm" icon="{{ $statusConfig['icon'] }}">
                        {{ $statusConfig['message'] }}
                    </flux:badge>
                    
                    <!-- Project Type -->
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                        {{ $project->readable_workflow_type }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Actions Section -->
        @if($showActions && auth()->check() && auth()->id() === $pitch->user_id)
            <div class="flex-shrink-0">
                <!-- Pitch Owner: Show Manage Dropdown -->
                <flux:dropdown position="bottom" align="end">
                    <flux:button variant="primary" size="base" icon="chevron-down" class="w-full sm:w-auto font-semibold">
                        Manage
                    </flux:button>
                    
                    <flux:menu>
                        <!-- Primary Action (if exists) -->
                        @if($primaryAction)
                            @if(isset($primaryAction['action']))
                                <flux:menu.item wire:click="submitForReview" icon="{{ $statusConfig['icon'] }}">
                                    {{ $primaryAction['label'] }}
                                </flux:menu.item>
                            @else
                                <flux:menu.item href="{{ $primaryAction['url'] }}" icon="{{ $statusConfig['icon'] }}">
                                    {{ $primaryAction['label'] }}
                                </flux:menu.item>
                            @endif
                            <flux:menu.separator />
                        @endif
                        
                        <!-- Pitch Management Actions -->
                        @if($pitch->status === \App\Models\Pitch::STATUS_IN_PROGRESS || $pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                            <flux:menu.item href="#pitch-files" icon="arrow-up-tray">
                                Upload Files
                            </flux:menu.item>
                        @endif
                        
                        @if(in_array($pitch->status, [\App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_PENDING_REVIEW]))
                            <flux:menu.item wire:click="withdrawSubmission" icon="arrow-path">
                                Withdraw Submission
                            </flux:menu.item>
                        @endif
                        
                        <!-- Communication -->
                        <flux:menu.item href="#feedback" icon="chat-bubble-left">
                            View Feedback
                        </flux:menu.item>
                        
                        <!-- Project Link -->
                        <flux:menu.item href="{{ route('projects.show', $project) }}" wire:navigate icon="eye">
                            View Project
                        </flux:menu.item>
                        
                        <flux:menu.separator />
                        
                        <!-- Settings -->
                        <flux:menu.item href="#" wire:click="editPitchDetails" icon="cog-6-tooth">
                            Pitch Settings
                        </flux:menu.item>
                        
                        <!-- Danger Zone -->
                        <flux:menu.separator />
                        <flux:menu.item onclick="Livewire.dispatch('confirmDeletePitch')" icon="trash" class="text-red-600 hover:text-red-700 hover:bg-red-50">
                            Delete Pitch
                        </flux:menu.item>
                    </flux:menu>
                </flux:dropdown>
            </div>
        @elseif($showActions && $primaryAction)
            <!-- Non-Owner: Show Single Action Button -->
            @if(isset($primaryAction['action']))
                <flux:button 
                    wire:click="submitForReview" 
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
    </div>
    
    <!-- Quick Stats Row -->
    <div class="border-t border-slate-200 dark:border-slate-700 mt-4 pt-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <!-- Stats -->
            <div class="flex flex-wrap items-center gap-4 text-sm">
                <!-- File Count -->
                <div class="flex items-center gap-1.5">
                    <flux:icon name="document" class="w-4 h-4 text-slate-500" />
                    <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ $pitch->files->count() }}
                    </span>
                    <span class="text-slate-500">
                        {{ $pitch->files->count() === 1 ? 'file' : 'files' }}
                    </span>
                </div>
                
                <!-- Submission Count -->
                @if($pitch->snapshots->count() > 0)
                    <div class="flex items-center gap-1.5">
                        <flux:icon name="paper-airplane" class="w-4 h-4 text-slate-500" />
                        <span class="font-medium text-slate-900 dark:text-slate-100">
                            {{ $pitch->snapshots->count() }}
                        </span>
                        <span class="text-slate-500">
                            {{ $pitch->snapshots->count() === 1 ? 'submission' : 'submissions' }}
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
                
                <!-- Producer Name -->
                <div class="flex items-center gap-1.5">
                    <flux:icon name="user" class="w-4 h-4 text-slate-500" />
                    <span class="font-medium text-slate-900 dark:text-slate-100">
                        {{ $pitch->user->name }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</flux:card>