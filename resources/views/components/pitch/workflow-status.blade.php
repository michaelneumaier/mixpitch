@props(['pitch', 'project'])

@php
    // Calculate workflow metrics
    $isClientManagement = $project->isClientManagement();
    $currentStatus = $pitch->status;
    $lastUpdated = $pitch->updated_at;
    
    // Get revision history and metrics
    $revisionEvents = $pitch->events()
        ->where('event_type', 'client_revisions_requested')
        ->orderBy('created_at', 'desc')
        ->get();
    $revisionCount = $revisionEvents->count();
    
    // Get latest feedback event
    $latestFeedbackEvent = $pitch->events()
        ->whereIn('event_type', ['client_revisions_requested', 'status_change'])
        ->where(function($query) {
            $query->where('event_type', 'client_revisions_requested')
                  ->orWhere(function($q) {
                      $q->where('event_type', 'status_change')
                        ->where('status', \App\Models\Pitch::STATUS_DENIED);
                  });
        })
        ->latest()
        ->first();
    
    // Calculate time in current status
    $timeInStatus = $lastUpdated->diffForHumans();
    $timeInStatusDays = $lastUpdated->diffInDays(now());
    
    // Define workflow stages for client management
    $workflowStages = [
        'in_progress' => ['label' => 'Working', 'icon' => 'fas fa-cog', 'color' => 'blue'],
        'ready_for_review' => ['label' => 'Review', 'icon' => 'fas fa-eye', 'color' => 'purple'],
        'revisions_requested' => ['label' => 'Revisions', 'icon' => 'fas fa-edit', 'color' => 'amber'],
        'approved' => ['label' => 'Approved', 'icon' => 'fas fa-check', 'color' => 'green'],
        'completed' => ['label' => 'Complete', 'icon' => 'fas fa-trophy', 'color' => 'emerald']
    ];
    
    // Determine current stage
    $currentStage = match($currentStatus) {
        \App\Models\Pitch::STATUS_IN_PROGRESS => 'in_progress',
        \App\Models\Pitch::STATUS_READY_FOR_REVIEW => 'ready_for_review',
        \App\Models\Pitch::STATUS_REVISIONS_REQUESTED => 'revisions_requested',
        \App\Models\Pitch::STATUS_APPROVED => 'approved',
        \App\Models\Pitch::STATUS_COMPLETED => 'completed',
        \App\Models\Pitch::STATUS_DENIED => 'revisions_requested', // Treat as revision opportunity
        default => 'in_progress'
    };
    
    // Calculate progress percentage
    $stageOrder = ['in_progress', 'ready_for_review', 'revisions_requested', 'approved', 'completed'];
    $currentStageIndex = array_search($currentStage, $stageOrder);
    $progressPercentage = $currentStageIndex !== false ? (($currentStageIndex + 1) / count($stageOrder)) * 100 : 20;
    
    // Get file metrics
    $fileCount = $pitch->files->count();
    $hasFiles = $fileCount > 0;
    $recentFileUploads = $pitch->files()->where('created_at', '>=', now()->subDays(7))->count();
@endphp

<div class="bg-white rounded-lg border border-base-300 shadow-sm overflow-hidden">
    <!-- Header with Progress Bar -->
    <div class="bg-gradient-to-r from-blue-50 to-purple-50 p-4 border-b border-gray-200">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-lg font-semibold flex items-center">
                <i class="fas fa-tasks text-blue-500 mr-2"></i>
                Workflow Status
                @if($revisionCount > 0)
                <span class="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                    <i class="fas fa-redo mr-1"></i>
                    {{ $revisionCount }} {{ $revisionCount === 1 ? 'revision' : 'revisions' }}
                </span>
                @endif
            </h4>
            <div class="text-right">
                <x-pitch-status-badge :status="$pitch->status" />
                <div class="text-xs text-gray-500 mt-1">
                    {{ $timeInStatus }}
                    @if($timeInStatusDays > 3)
                    <span class="text-amber-600">
                        <i class="fas fa-clock ml-1"></i>
                    </span>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Progress Visualization -->
        @if($isClientManagement)
        <div class="space-y-3">
            <!-- Progress Bar -->
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 h-2 rounded-full transition-all duration-500" 
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
            
            <!-- Stage Indicators -->
            <div class="flex justify-between text-xs">
                @foreach($workflowStages as $stageKey => $stage)
                @php
                    $isActive = $stageKey === $currentStage;
                    $isPassed = array_search($stageKey, $stageOrder) < $currentStageIndex;
                    $stageClass = $isActive ? "text-{$stage['color']}-600 font-semibold" : 
                                 ($isPassed ? 'text-green-600' : 'text-gray-400');
                @endphp
                <div class="flex flex-col items-center {{ $stageClass }}">
                    <i class="{{ $stage['icon'] }} mb-1"></i>
                    <span>{{ $stage['label'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <!-- Status-Specific Content -->
    <div class="p-4 space-y-4">
        
        @if($currentStatus === \App\Models\Pitch::STATUS_IN_PROGRESS)
        <!-- In Progress Status -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-cog text-blue-600 text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h5 class="font-medium text-blue-800 mb-2">Work in Progress</h5>
                    <p class="text-sm text-blue-700 mb-3">
                        You're actively working on this project. Upload your files and submit for client review when ready.
                    </p>
                    
                    <!-- File Status -->
                    <div class="bg-white border border-blue-200 rounded-md p-3">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-blue-800">Project Files</span>
                            <span class="text-xs text-blue-600">
                                {{ $fileCount }} {{ $fileCount === 1 ? 'file' : 'files' }}
                                @if($recentFileUploads > 0)
                                <span class="text-green-600">
                                    <i class="fas fa-arrow-up ml-1"></i>
                                    {{ $recentFileUploads }} recent
                                </span>
                                @endif
                            </span>
                        </div>
                        @if(!$hasFiles)
                        <div class="text-xs text-amber-600 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-1"></i>
                            Upload at least one file before submitting for review
                        </div>
                        @else
                        <div class="text-xs text-green-600 flex items-center">
                            <i class="fas fa-check mr-1"></i>
                            Ready to submit for client review
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($currentStatus === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
        <!-- Ready for Review Status -->
        <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-eye text-purple-600 text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h5 class="font-medium text-purple-800 mb-2">Submitted for Client Review</h5>
                    <p class="text-sm text-purple-700 mb-3">
                        Your work has been submitted and is awaiting client review. You can recall this submission if you need to make changes.
                    </p>
                    
                    <!-- Submission Details -->
                    <div class="bg-white border border-purple-200 rounded-md p-3">
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="font-medium text-purple-800">Submitted:</span>
                                <div class="text-purple-600">{{ $lastUpdated->format('M d, Y \a\t g:i A') }}</div>
                            </div>
                            <div>
                                <span class="font-medium text-purple-800">Files Included:</span>
                                <div class="text-purple-600">{{ $fileCount }} {{ $fileCount === 1 ? 'file' : 'files' }}</div>
                            </div>
                        </div>
                        
                        @if($timeInStatusDays > 2)
                        <div class="mt-3 pt-3 border-t border-purple-200">
                            <div class="text-xs text-amber-600 flex items-center">
                                <i class="fas fa-clock mr-1"></i>
                                Pending review for {{ $timeInStatusDays }} {{ $timeInStatusDays === 1 ? 'day' : 'days' }}
                                @if($timeInStatusDays > 5)
                                - Consider following up with client
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($currentStatus === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED && $latestFeedbackEvent)
        <!-- Revisions Requested Status -->
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-edit text-amber-600 text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h5 class="font-medium text-amber-800 mb-2">Client Requested Revisions</h5>
                    <p class="text-sm text-amber-700 mb-3">
                        The client has provided feedback and requested changes. Review their comments and make the necessary adjustments.
                    </p>
                    
                    <!-- Feedback Display -->
                    <div class="bg-white border border-amber-200 rounded-md p-4 mb-3">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-amber-800 flex items-center">
                                <i class="fas fa-comment-dots mr-2"></i>
                                Client Feedback
                            </span>
                            <span class="text-xs text-amber-600">
                                {{ $latestFeedbackEvent->created_at->format('M d, Y \a\t g:i A') }}
                                <span class="ml-2 text-amber-500">
                                    ({{ $latestFeedbackEvent->created_at->diffForHumans() }})
                                </span>
                            </span>
                        </div>
                        
                        <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-3">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $latestFeedbackEvent->comment }}</p>
                        </div>
                        
                        <!-- Revision Context -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div class="bg-gray-50 rounded-md p-2">
                                <span class="font-medium text-gray-700">Revision #:</span>
                                <div class="text-gray-600">{{ $revisionCount }}</div>
                            </div>
                            <div class="bg-gray-50 rounded-md p-2">
                                <span class="font-medium text-gray-700">Time to Address:</span>
                                <div class="text-gray-600">{{ $timeInStatus }}</div>
                            </div>
                            <div class="bg-gray-50 rounded-md p-2">
                                <span class="font-medium text-gray-700">Current Files:</span>
                                <div class="text-gray-600">{{ $fileCount }} {{ $fileCount === 1 ? 'file' : 'files' }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Action Guidance -->
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <h6 class="font-medium text-blue-800 mb-2 flex items-center">
                            <i class="fas fa-lightbulb mr-2"></i>
                            Next Steps:
                        </h6>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Review the client's feedback carefully
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Make the requested changes to your files
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Add a response explaining your changes
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Resubmit for client review
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($currentStatus === \App\Models\Pitch::STATUS_DENIED && $latestFeedbackEvent)
        <!-- Denied Status -->
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-times-circle text-red-600 text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h5 class="font-medium text-red-800 mb-2">Submission Declined</h5>
                    <p class="text-sm text-red-700 mb-3">
                        The client has declined this submission. Review their feedback to understand their concerns.
                    </p>
                    
                    <!-- Feedback Display -->
                    <div class="bg-white border border-red-200 rounded-md p-4 mb-3">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-red-800 flex items-center">
                                <i class="fas fa-comment-dots mr-2"></i>
                                Reason for Decline
                            </span>
                            <span class="text-xs text-red-600">
                                {{ $latestFeedbackEvent->created_at->format('M d, Y \a\t g:i A') }}
                            </span>
                        </div>
                        
                        <div class="bg-red-50 border border-red-200 rounded-md p-3">
                            <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $latestFeedbackEvent->comment }}</p>
                        </div>
                    </div>
                    
                    <!-- Recovery Options -->
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <h6 class="font-medium text-blue-800 mb-2 flex items-center">
                            <i class="fas fa-redo mr-2"></i>
                            Recovery Options:
                        </h6>
                        <ul class="text-sm text-blue-700 space-y-1">
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Address the client's concerns
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Upload revised files
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-arrow-right text-blue-500 mr-2 mt-0.5 text-xs"></i>
                                Resubmit with improvements
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($currentStatus === \App\Models\Pitch::STATUS_APPROVED)
        <!-- Approved Status -->
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600 text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h5 class="font-medium text-green-800 mb-2">Client Approved!</h5>
                    <p class="text-sm text-green-700 mb-3">
                        Excellent work! The client has approved your submission. You can now mark the project as complete.
                    </p>
                    
                    <!-- Approval Details -->
                    <div class="bg-white border border-green-200 rounded-md p-3">
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="font-medium text-green-800">Approved:</span>
                                <div class="text-green-600">{{ $lastUpdated->format('M d, Y \a\t g:i A') }}</div>
                            </div>
                            <div>
                                <span class="font-medium text-green-800">Total Revisions:</span>
                                <div class="text-green-600">{{ $revisionCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($currentStatus === \App\Models\Pitch::STATUS_COMPLETED)
        <!-- Completed Status -->
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-trophy text-emerald-600 text-lg"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h5 class="font-medium text-emerald-800 mb-2">Project Completed!</h5>
                    <p class="text-sm text-emerald-700 mb-3">
                        Congratulations! This project has been successfully completed.
                    </p>
                    
                    <!-- Completion Summary -->
                    <div class="bg-white border border-emerald-200 rounded-md p-3">
                        <div class="grid grid-cols-3 gap-4 text-xs">
                            <div>
                                <span class="font-medium text-emerald-800">Completed:</span>
                                <div class="text-emerald-600">{{ $lastUpdated->format('M d, Y') }}</div>
                            </div>
                            <div>
                                <span class="font-medium text-emerald-800">Revisions:</span>
                                <div class="text-emerald-600">{{ $revisionCount }}</div>
                            </div>
                            <div>
                                <span class="font-medium text-emerald-800">Final Files:</span>
                                <div class="text-emerald-600">{{ $fileCount }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Submit for Review Button (only show if not already submitted) -->
        @if(in_array($currentStatus, [
            \App\Models\Pitch::STATUS_IN_PROGRESS, 
            \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, 
            \App\Models\Pitch::STATUS_DENIED
        ]))
        <div class="border-t pt-4">
            <button wire:click="submitForReview" 
                    class="btn btn-primary w-full {{ !$hasFiles ? 'opacity-50 cursor-not-allowed' : '' }}"
                    @if(!$hasFiles) disabled @endif>
                <i class="fas fa-paper-plane mr-2"></i>
                @if($currentStatus === \App\Models\Pitch::STATUS_REVISIONS_REQUESTED)
                    Resubmit with Revisions
                @elseif($currentStatus === \App\Models\Pitch::STATUS_DENIED)
                    Submit Improved Version
                @else
                    Submit for Client Review
                @endif
            </button>
            @if(!$hasFiles)
            <p class="text-xs text-gray-500 mt-2 text-center flex items-center justify-center">
                <i class="fas fa-info-circle mr-1"></i>
                Upload at least one file before submitting
            </p>
            @endif
        </div>
        @endif
    </div>
</div> 