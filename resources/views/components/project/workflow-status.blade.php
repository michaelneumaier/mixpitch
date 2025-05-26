@props(['project'])

@php
    // Determine current workflow stage and progress
    $workflowStages = [
        'open' => ['label' => 'Open for Pitches', 'icon' => 'fa-bullhorn', 'progress' => 10],
        'reviewing' => ['label' => 'Reviewing Pitches', 'icon' => 'fa-search', 'progress' => 30],
        'approved' => ['label' => 'Pitch Approved', 'icon' => 'fa-check-circle', 'progress' => 50],
        'in_progress' => ['label' => 'Work in Progress', 'icon' => 'fa-cogs', 'progress' => 70],
        'under_review' => ['label' => 'Under Review', 'icon' => 'fa-eye', 'progress' => 85],
        'revisions' => ['label' => 'Revisions Requested', 'icon' => 'fa-edit', 'progress' => 75],
        'approved_final' => ['label' => 'Approved', 'icon' => 'fa-thumbs-up', 'progress' => 95],
        'completed' => ['label' => 'Completed', 'icon' => 'fa-trophy', 'progress' => 100],
    ];

    // Determine current stage based on project and pitch status
    $currentStage = 'open';
    $progressPercentage = 10;
    $statusMessage = '';
    $contextualGuidance = '';
    $timeInStatus = null;
    $showWarning = false;

    // Load pitches if not already loaded
    if (!$project->relationLoaded('pitches')) {
        $project->load(['pitches' => function($q) {
            $q->with('user')->orderBy('created_at', 'desc');
        }]);
    }

    $pitchCount = $project->pitches->count();
    $approvedPitch = $project->pitches->where('status', 'approved')->first();
    $hasApprovedPitch = $approvedPitch !== null;

    // Determine current stage
    if (!$project->is_published) {
        $currentStage = 'open';
        $statusMessage = 'Project is not yet published';
        $contextualGuidance = 'Publish your project to start receiving pitches from producers.';
    } elseif ($pitchCount === 0) {
        $currentStage = 'open';
        $statusMessage = 'Waiting for pitches';
        $contextualGuidance = 'Your project is live and accepting pitches. Share it to attract more producers.';
        $timeInStatus = $project->created_at;
    } elseif (!$hasApprovedPitch && $pitchCount > 0) {
        $currentStage = 'reviewing';
        $statusMessage = "Reviewing {$pitchCount} pitch" . ($pitchCount > 1 ? 'es' : '');
        $contextualGuidance = 'Review submitted pitches and approve one to move forward with the project.';
        $timeInStatus = $project->pitches->first()->created_at;
    } elseif ($hasApprovedPitch) {
        // Check the approved pitch status for more granular stages
        switch ($approvedPitch->status) {
            case 'approved':
                if ($approvedPitch->files->count() === 0) {
                    $currentStage = 'approved';
                    $statusMessage = 'Pitch approved - waiting for work to begin';
                    $contextualGuidance = 'The producer has been notified and should begin working on your project soon.';
                } else {
                    $currentStage = 'in_progress';
                    $statusMessage = 'Work in progress';
                    $contextualGuidance = 'The producer is actively working on your project. Files have been uploaded.';
                }
                break;
            case 'submitted_for_review':
                $currentStage = 'under_review';
                $statusMessage = 'Work submitted for review';
                $contextualGuidance = 'Review the submitted work and provide feedback or approve the final deliverable.';
                break;
            case 'revision_requested':
                $currentStage = 'revisions';
                $statusMessage = 'Revisions requested';
                $contextualGuidance = 'Waiting for the producer to implement your requested changes.';
                break;
            case 'approved_final':
                $currentStage = 'approved_final';
                $statusMessage = 'Final work approved';
                $contextualGuidance = 'Great! The work has been approved. Payment processing will begin shortly.';
                break;
            case 'completed':
                $currentStage = 'completed';
                $statusMessage = 'Project completed';
                $contextualGuidance = 'Project successfully completed. Thank you for using our platform!';
                break;
        }
        $timeInStatus = $approvedPitch->updated_at;
    }

    $progressPercentage = $workflowStages[$currentStage]['progress'];

    // Check for warnings (time in status)
    if ($timeInStatus) {
        $daysInStatus = $timeInStatus->diffInDays(now());
        if ($currentStage === 'reviewing' && $daysInStatus > 7) {
            $showWarning = true;
        } elseif ($currentStage === 'under_review' && $daysInStatus > 5) {
            $showWarning = true;
        }
    }

    // Get file metrics
    $totalFiles = $project->files->count();
    $recentFiles = $project->files->where('created_at', '>=', now()->subDays(7))->count();
    $storageUsed = $project->files->sum('size');

    // Get activity metrics
    $recentActivity = $project->pitches->where('updated_at', '>=', now()->subDays(7))->count();
@endphp

<div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                    <i class="fas fa-project-diagram text-blue-600 mr-3"></i>
                    Project Workflow Status
                </h3>
                <p class="text-sm text-gray-600 mt-1">{{ $statusMessage }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-blue-600">{{ $progressPercentage }}%</div>
                <div class="text-xs text-gray-500">Complete</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-xs text-gray-600 mb-2">
                <span>Progress</span>
                <span>{{ $workflowStages[$currentStage]['label'] }}</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>

        <!-- Stage Indicators -->
        <div class="flex justify-between mt-4 text-xs">
            @foreach(['open', 'reviewing', 'approved', 'in_progress', 'completed'] as $stage)
                <div class="flex flex-col items-center {{ $currentStage === $stage ? 'text-blue-600' : 'text-gray-400' }}">
                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-1
                        {{ $progressPercentage >= $workflowStages[$stage]['progress'] ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300' }}">
                        <i class="fas {{ $workflowStages[$stage]['icon'] }} text-xs"></i>
                    </div>
                    <span class="text-center leading-tight">{{ $workflowStages[$stage]['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Status-Specific Content -->
    <div class="p-6">
        @if($showWarning)
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-amber-600 mr-3"></i>
                    <div>
                        <h4 class="font-medium text-amber-800">Attention Needed</h4>
                        <p class="text-sm text-amber-700 mt-1">
                            This project has been in "{{ $workflowStages[$currentStage]['label'] }}" status for {{ $daysInStatus }} days.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contextual Guidance -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h4 class="font-medium text-blue-900 mb-2 flex items-center">
                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                Next Steps
            </h4>
            <p class="text-sm text-blue-800">{{ $contextualGuidance }}</p>
        </div>

        <!-- Project Metrics -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-semibold text-gray-900">{{ $pitchCount }}</div>
                <div class="text-xs text-gray-600">Pitch{{ $pitchCount !== 1 ? 'es' : '' }}</div>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-semibold text-gray-900">{{ $totalFiles }}</div>
                <div class="text-xs text-gray-600">Files</div>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-semibold text-gray-900">{{ number_format($storageUsed / 1024 / 1024, 1) }}MB</div>
                <div class="text-xs text-gray-600">Storage</div>
            </div>
            <div class="text-center p-3 bg-gray-50 rounded-lg">
                <div class="text-lg font-semibold text-gray-900">{{ $recentActivity }}</div>
                <div class="text-xs text-gray-600">Recent Activity</div>
            </div>
        </div>

        <!-- Status-Specific Actions -->
        @if($currentStage === 'open' && !$project->is_published)
            <div class="flex flex-col sm:flex-row gap-3">
                <button wire:click="publishProject" class="btn btn-primary flex-1">
                    <i class="fas fa-bullhorn mr-2"></i>
                    Publish Project
                </button>
                <a href="{{ route('projects.edit', $project) }}" class="btn btn-outline flex-1">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Details
                </a>
            </div>
        @elseif($currentStage === 'reviewing' && $pitchCount > 0)
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="#pitches-section" class="btn btn-primary flex-1">
                    <i class="fas fa-search mr-2"></i>
                    Review Pitches
                </a>
                <a href="{{ route('projects.show', $project) }}" class="btn btn-outline flex-1">
                    <i class="fas fa-eye mr-2"></i>
                    View Public Page
                </a>
            </div>
        @elseif($currentStage === 'under_review')
            <div class="flex flex-col sm:flex-row gap-3">
                <button wire:click="approveWork" class="btn btn-success flex-1">
                    <i class="fas fa-check mr-2"></i>
                    Approve Work
                </button>
                <button wire:click="requestRevisions" class="btn btn-warning flex-1">
                    <i class="fas fa-edit mr-2"></i>
                    Request Revisions
                </button>
            </div>
        @elseif($currentStage === 'completed')
            <div class="text-center">
                <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-lg">
                    <i class="fas fa-trophy mr-2"></i>
                    Project Successfully Completed!
                </div>
            </div>
        @endif

        <!-- Time in Status -->
        @if($timeInStatus)
            <div class="mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center justify-between text-sm text-gray-600">
                    <span>Time in current status:</span>
                    <span class="font-medium">{{ $timeInStatus->diffForHumans() }}</span>
                </div>
            </div>
        @endif
    </div>
</div> 