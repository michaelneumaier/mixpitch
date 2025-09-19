@props(['pitch', 'showActions' => true, 'compact' => false])

@php
    $project = $pitch->project;
    
    // Current focus determination - single actionable item
    $currentFocus = [
        'title' => '',
        'description' => '',
        'action' => null,
        'urgency' => 'normal', // normal, warning, urgent
        'icon' => 'check-circle',
        'progress' => null
    ];

    // Get pitch metrics for context
    $totalFiles = $pitch->files->count();
    $totalSnapshots = $pitch->snapshots->count();
    $daysInStatus = $pitch->updated_at->diffInDays(now());
    
    // Map pitch status to current focus
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_PENDING:
            $currentFocus = [
                'title' => 'Awaiting Approval',
                'description' => 'Your pitch is waiting for the project owner to review and approve it',
                'action' => null,
                'urgency' => $daysInStatus > 3 ? 'warning' : 'normal',
                'icon' => 'clock',
                'progress' => 10
            ];
            break;
            
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
            if ($totalFiles === 0) {
                $currentFocus = [
                    'title' => 'Ready to Start Working',
                    'description' => 'Your pitch has been approved! Download project files and begin work',
                    'action' => ['type' => 'anchor', 'href' => '#project-files', 'label' => 'Download Project Files'],
                    'urgency' => 'normal',
                    'icon' => 'bolt',
                    'progress' => 25
                ];
            } else {
                $currentFocus = [
                    'title' => 'Work in Progress',
                    'description' => "{$totalFiles} files uploaded • Continue working and submit when ready",
                    'action' => ['type' => 'anchor', 'href' => '#pitch-files', 'label' => 'Upload More Files'],
                    'urgency' => $daysInStatus > 14 ? 'warning' : 'normal',
                    'icon' => 'cog-6-tooth',
                    'progress' => 60
                ];
            }
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_ENTRY:
            if ($totalFiles === 0) {
                $currentFocus = [
                    'title' => 'Contest Entry Ready',
                    'description' => 'Download project files and create your contest entry',
                    'action' => ['type' => 'anchor', 'href' => '#project-files', 'label' => 'Start Contest Entry'],
                    'urgency' => 'normal',
                    'icon' => 'trophy',
                    'progress' => 25
                ];
            } else {
                $deadline = $project->deadline;
                $daysLeft = $deadline ? now()->diffInDays($deadline, false) : null;
                $urgency = $daysLeft !== null && $daysLeft <= 3 ? 'urgent' : 'normal';
                
                $currentFocus = [
                    'title' => 'Contest Entry in Progress',
                    'description' => $deadline ? "{$totalFiles} files uploaded • {$daysLeft} days remaining" : "{$totalFiles} files uploaded • Continue working",
                    'action' => null,
                    'urgency' => $urgency,
                    'icon' => 'trophy',
                    'progress' => 70
                ];
            }
            break;
            
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
            $currentFocus = [
                'title' => 'Submitted for Review',
                'description' => 'Your work has been submitted and is waiting for project owner review',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'paper-airplane',
                'progress' => 80
            ];
            break;
            
        case \App\Models\Pitch::STATUS_PENDING_REVIEW:
            $currentFocus = [
                'title' => 'Under Review',
                'description' => 'The project owner is currently reviewing your submission',
                'action' => null,
                'urgency' => $daysInStatus > 5 ? 'warning' : 'normal',
                'icon' => 'eye',
                'progress' => 85
            ];
            break;
            
        case \App\Models\Pitch::STATUS_APPROVED:
            if ($totalFiles > 0) {
                $currentFocus = [
                    'title' => 'Work Approved',
                    'description' => 'Excellent! Your submission was approved and is being finalized',
                    'action' => null,
                    'urgency' => 'normal',
                    'icon' => 'check-circle',
                    'progress' => 95
                ];
            } else {
                $currentFocus = [
                    'title' => 'Pitch Approved',
                    'description' => 'Great! Your pitch was approved. Download files and start working',
                    'action' => ['type' => 'anchor', 'href' => '#project-files', 'label' => 'Download Files'],
                    'urgency' => 'normal',
                    'icon' => 'check-circle',
                    'progress' => 25
                ];
            }
            break;
            
        case \App\Models\Pitch::STATUS_REVISIONS_REQUESTED:
            $currentFocus = [
                'title' => 'Revisions Requested',
                'description' => 'The project owner has requested changes to your submission',
                'action' => ['type' => 'anchor', 'href' => '#feedback', 'label' => 'Review Feedback'],
                'urgency' => 'urgent',
                'icon' => 'arrow-path',
                'progress' => 55
            ];
            break;
            
        case \App\Models\Pitch::STATUS_DENIED:
            $currentFocus = [
                'title' => 'Pitch Not Approved',
                'description' => 'Your pitch was not approved. Review feedback for future opportunities',
                'action' => ['type' => 'anchor', 'href' => '#feedback', 'label' => 'View Feedback'],
                'urgency' => 'normal',
                'icon' => 'x-circle',
                'progress' => 30
            ];
            break;
            
        case \App\Models\Pitch::STATUS_COMPLETED:
            $currentFocus = [
                'title' => 'Project Complete',
                'description' => 'Congratulations! Your work has been completed successfully',
                'action' => ['type' => 'anchor', 'href' => '#final-files', 'label' => 'View Final Work'],
                'urgency' => 'normal',
                'icon' => 'check-circle',
                'progress' => 100
            ];
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_WINNER:
            $currentFocus = [
                'title' => 'Contest Winner! 🏆',
                'description' => 'Congratulations! Your entry was selected as the winning submission',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'trophy',
                'progress' => 100
            ];
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP:
            $currentFocus = [
                'title' => 'Contest Runner-Up',
                'description' => 'Great work! You were selected as a runner-up in this contest',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'star',
                'progress' => 100
            ];
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED:
            $currentFocus = [
                'title' => 'Contest Complete',
                'description' => 'Thank you for participating! Keep creating and entering future contests',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'user',
                'progress' => 100
            ];
            break;
            
        default:
            $currentFocus = [
                'title' => 'Status: ' . $pitch->readable_status,
                'description' => 'Check with the project owner for next steps',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'information-circle',
                'progress' => 50
            ];
    }

    // Color scheme based on urgency
    $colorScheme = match($currentFocus['urgency']) {
        'urgent' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'icon' => 'text-red-600 dark:text-red-400',
            'title' => 'text-red-900 dark:text-red-100',
            'desc' => 'text-red-700 dark:text-red-300',
            'progress' => 'bg-red-500'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'title' => 'text-amber-900 dark:text-amber-100',
            'desc' => 'text-amber-700 dark:text-amber-300',
            'progress' => 'bg-amber-500'
        ],
        default => [
            'bg' => 'bg-blue-50 dark:bg-blue-950',
            'border' => 'border-blue-200 dark:border-blue-800',
            'icon' => 'text-blue-600 dark:text-blue-400',
            'title' => 'text-blue-900 dark:text-blue-100',
            'desc' => 'text-blue-700 dark:text-blue-300',
            'progress' => 'bg-blue-500'
        ]
    };
@endphp

<!-- Current Focus Component -->
<flux:card class="{{ $colorScheme['bg'] }} {{ $colorScheme['border'] }} overflow-hidden">
    <div class="flex items-start gap-4">
        <!-- Icon -->
        <div class="flex-shrink-0">
            <div class="w-12 h-12 rounded-xl {{ $colorScheme['bg'] }} {{ $colorScheme['border'] }} flex items-center justify-center">
                <flux:icon name="{{ $currentFocus['icon'] }}" class="w-6 h-6 {{ $colorScheme['icon'] }}" />
            </div>
        </div>
        
        <!-- Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <flux:heading size="lg" class="{{ $colorScheme['title'] }} mb-1">
                        {{ $currentFocus['title'] }}
                    </flux:heading>
                    <p class="text-sm {{ $colorScheme['desc'] }} mb-3">
                        {{ $currentFocus['description'] }}
                    </p>
                    
                    <!-- Progress Bar (if applicable) -->
                    @if($currentFocus['progress'])
                        <div class="flex items-center gap-3 mb-3">
                            <div class="flex-1 bg-white dark:bg-gray-800 rounded-full h-2 {{ $colorScheme['border'] }} border">
                                <div class="{{ $colorScheme['progress'] }} h-full rounded-full transition-all duration-500" 
                                     style="width: {{ $currentFocus['progress'] }}%"></div>
                            </div>
                            <span class="text-xs font-medium {{ $colorScheme['desc'] }} min-w-fit">
                                {{ $currentFocus['progress'] }}%
                            </span>
                        </div>
                    @endif
                    
                    <!-- Action Button -->
                    @if($currentFocus['action'])
                        @php $action = $currentFocus['action']; @endphp
                        
                        @if($action['type'] === 'wire')
                            <flux:button 
                                wire:click="{{ $action['method'] }}" 
                                variant="primary" 
                                size="sm"
                                class="inline-flex items-center">
                                {{ $action['label'] }}
                            </flux:button>
                        @elseif($action['type'] === 'route')
                            <flux:button 
                                href="{{ route($action['name'], $action['params']) }}" 
                                variant="primary" 
                                size="sm"
                                class="inline-flex items-center">
                                {{ $action['label'] }}
                            </flux:button>
                        @elseif($action['type'] === 'anchor')
                            <flux:button 
                                href="{{ $action['href'] }}" 
                                variant="primary" 
                                size="sm"
                                class="inline-flex items-center">
                                {{ $action['label'] }}
                            </flux:button>
                        @elseif($action['type'] === 'modal')
                            <flux:modal.trigger name="{{ $action['modal'] }}">
                                <flux:button 
                                    variant="primary" 
                                    size="sm"
                                    icon="share"
                                    class="inline-flex items-center">
                                    {{ $action['label'] }}
                                </flux:button>
                            </flux:modal.trigger>
                        @endif
                    @endif
                </div>
                
                <!-- Quick Stats -->
                <div class="flex flex-col gap-2 text-right min-w-fit">
                    <div class="text-xs {{ $colorScheme['desc'] }}">
                        {{ $totalFiles }} {{ $totalFiles === 1 ? 'file' : 'files' }}
                    </div>
                    @if($totalSnapshots > 0)
                        <div class="text-xs {{ $colorScheme['desc'] }}">
                            {{ $totalSnapshots }} {{ $totalSnapshots === 1 ? 'submission' : 'submissions' }}
                        </div>
                    @endif
                    @if($project->isContest() && $project->deadline)
                        @php 
                            $daysToDeadline = now()->diffInDays($project->deadline, false);
                        @endphp
                        <div class="text-xs {{ $daysToDeadline < 0 ? 'text-red-600 dark:text-red-400' : $colorScheme['desc'] }}">
                            @if($daysToDeadline < 0)
                                {{ abs($daysToDeadline) }} days overdue
                            @elseif($daysToDeadline === 0)
                                Due today
                            @else
                                {{ $daysToDeadline }} days left
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</flux:card> 