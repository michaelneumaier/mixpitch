@props(['pitch', 'project', 'component' => null, 'workflowColors' => null, 'semanticColors' => null])

@php
    // Ensure this is only used for client management projects
    if (!$project->isClientManagement()) {
        throw new InvalidArgumentException('Client management workflow status component can only be used with client management projects.');
    }

    // Provide fallback colors if not passed from parent
    $workflowColors = $workflowColors ?? [
        'bg' => 'bg-purple-50 dark:bg-purple-950',
        'border' => 'border-purple-200 dark:border-purple-800',
        'text_primary' => 'text-purple-900 dark:text-purple-100',
        'text_secondary' => 'text-purple-700 dark:text-purple-300',
        'text_muted' => 'text-purple-600 dark:text-purple-400',
        'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
        'accent_border' => 'border-purple-200 dark:border-purple-800',
        'icon' => 'text-purple-600 dark:text-purple-400'
    ];

    $semanticColors = $semanticColors ?? [
        'success' => ['bg' => 'bg-green-50 dark:bg-green-950', 'text' => 'text-green-800 dark:text-green-200', 'icon' => 'text-green-600 dark:text-green-400'],
        'warning' => ['bg' => 'bg-amber-50 dark:bg-amber-950', 'text' => 'text-amber-800 dark:text-amber-200', 'icon' => 'text-amber-600 dark:text-amber-400'],
        'danger' => ['bg' => 'bg-red-50 dark:bg-red-950', 'text' => 'text-red-800 dark:text-red-200', 'icon' => 'text-red-600 dark:text-red-400']
    ];

    // Determine current focus based on pitch status - following main workflow-status pattern
    $currentFocus = [
        'title' => '',
        'description' => '',
        'action' => null,
        'urgency' => 'normal', // normal, warning, urgent
        'icon' => 'chart-bar',
        'progress' => null
    ];

    // Get file comments for context
    $pitchFileIds = $pitch->files->pluck('id');
    $allFileComments = DB::table('pitch_file_comments')
        ->whereIn('pitch_file_id', $pitchFileIds)
        ->get();
    $unresolvedComments = $allFileComments->filter(function($comment) {
        return !($comment->resolved ?? false) && ($comment->is_client_comment ?? false);
    });

    // Calculate time in current status for urgency detection
    $daysInStatus = $pitch->updated_at ? $pitch->updated_at->diffInDays(now()) : 0;

    // Map pitch status to workflow focus (centralized logic)
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
            if ($pitch->files->count() === 0) {
                $currentFocus = [
                    'title' => 'Project Setup',
                    'description' => 'Download client reference files and begin working on deliverables',
                    'action' => null,
                    'urgency' => $daysInStatus > 3 ? 'warning' : 'normal',
                    'icon' => 'cog-6-tooth',
                    'progress' => 20
                ];
            } else {
                $currentFocus = [
                    'title' => 'Work in Progress',
                    'description' => "{$pitch->files->count()} files uploaded • Continue working on deliverables",
                    'action' => null,
                    'urgency' => $daysInStatus > 10 ? 'warning' : 'normal',
                    'icon' => 'paint-brush',
                    'progress' => 60
                ];
            }
            break;
            
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
            $canResubmit = method_exists($component, 'canResubmit') ? $component->canResubmit : false;
            $currentFocus = [
                'title' => 'Client Review Pending',
                'description' => $unresolvedComments->count() > 0 
                    ? "Submitted for review • {$unresolvedComments->count()} unresolved client comments"
                    : 'Work submitted and waiting for client feedback',
                'action' => $canResubmit 
                    ? ['type' => 'wire', 'method' => 'submitForReview', 'label' => 'Resubmit Changes']
                    : ['type' => 'wire', 'method' => 'recallSubmission', 'label' => 'Recall Submission'],
                'urgency' => $daysInStatus > 7 ? 'warning' : 'normal',
                'icon' => 'eye',
                'progress' => 85
            ];
            break;
            
        case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
            $currentFocus = [
                'title' => 'Revisions Requested',
                'description' => $unresolvedComments->count() > 0 
                    ? "Client requested changes • {$unresolvedComments->count()} comments to address"
                    : 'Review client feedback and implement requested changes',
                'action' => null,
                'urgency' => $daysInStatus > 5 ? 'urgent' : 'warning',
                'icon' => 'pencil',
                'progress' => 70
            ];
            break;
            
        case \App\Models\Pitch::STATUS_COMPLETED:
            $currentFocus = [
                'title' => 'Project Completed',
                'description' => 'Client approved your work and payment has been processed',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'check-circle',
                'progress' => 100
            ];
            break;
            
        default:
            $currentFocus = [
                'title' => 'Project Active',
                'description' => 'Working on client deliverables',
                'action' => null,
                'urgency' => 'normal',
                'icon' => 'chart-bar',
                'progress' => 30
            ];
    }

    // Color scheme based on urgency (matching main workflow-status pattern)
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
            'bg' => $workflowColors['bg'],
            'border' => $workflowColors['border'],
            'icon' => $workflowColors['icon'],
            'title' => $workflowColors['text_primary'],
            'desc' => $workflowColors['text_secondary'],
            'progress' => 'bg-purple-500'
        ]
    };
@endphp

<!-- Client Management Workflow Status (Modern Design) -->
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
                    @if($currentFocus['action'] && $component)
                        @php $action = $currentFocus['action']; @endphp
                        
                        @if($action['type'] === 'wire')
                            <flux:button 
                                wire:click="{{ $action['method'] }}" 
                                variant="primary" 
                                size="sm"
                                wire:confirm="{{ $action['method'] === 'recallSubmission' ? 'Are you sure you want to recall this submission? The client will no longer be able to review it until you resubmit.' : '' }}"
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
                        @endif
                    @endif

                    <!-- Unresolved Comments Alert (if any) -->
                    @if($unresolvedComments->count() > 0 && in_array($pitch->status, [\App\Models\Pitch::STATUS_READY_FOR_REVIEW, \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]))
                        <div class="mt-3 {{ $semanticColors['warning']['bg'] }} border border-amber-200 dark:border-amber-800 rounded-lg p-3">
                            <div class="flex items-center gap-2">
                                <flux:icon.exclamation-triangle class="w-4 h-4 {{ $semanticColors['warning']['icon'] }}" />
                                <span class="text-sm {{ $semanticColors['warning']['text'] }} font-medium">
                                    {{ $unresolvedComments->count() }} unresolved comment{{ $unresolvedComments->count() > 1 ? 's' : '' }}
                                </span>
                            </div>
                            <p class="text-xs {{ $semanticColors['warning']['text'] }} mt-1">
                                Check the File Management section below for detailed feedback.
                            </p>
                        </div>
                    @endif

                    <!-- Time-based Warnings -->
                    @if($daysInStatus > 0 && $currentFocus['urgency'] !== 'normal')
                        <div class="mt-3 text-xs {{ $colorScheme['desc'] }}">
                            {{ $daysInStatus }} {{ $daysInStatus === 1 ? 'day' : 'days' }} in {{ strtolower($currentFocus['title']) }}
                            @if($currentFocus['urgency'] === 'warning')
                                • Consider taking action soon
                            @elseif($currentFocus['urgency'] === 'urgent')
                                • Immediate attention recommended
                            @endif
                        </div>
                    @endif
                </div>
                
                <!-- Quick Stats -->
                <div class="flex flex-col gap-2 text-right min-w-fit">
                    <div class="text-xs {{ $colorScheme['desc'] }}">
                        {{ $pitch->files->count() }} {{ $pitch->files->count() === 1 ? 'file' : 'files' }}
                    </div>
                    @if($unresolvedComments->count() > 0)
                        <div class="text-xs {{ $semanticColors['warning']['text'] }}">
                            {{ $unresolvedComments->count() }} comment{{ $unresolvedComments->count() > 1 ? 's' : '' }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</flux:card>