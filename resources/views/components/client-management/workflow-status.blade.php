@props(['pitch', 'project', 'component' => null, 'showActions' => true, 'compact' => false])

@php
    // Ensure this is only used for client management projects
    if (!$project->isClientManagement()) {
        throw new InvalidArgumentException('Client management workflow status component can only be used with client management projects.');
    }
    
    // Define workflow stages specific to client management
    $workflowStages = [
        'project_setup' => ['label' => 'Setup', 'icon' => 'fa-cog', 'progress' => 10],
        'working' => ['label' => 'Working', 'icon' => 'fa-palette', 'progress' => 40],
        'client_review' => ['label' => 'Review', 'icon' => 'fa-eye', 'progress' => 70],
        'approved' => ['label' => 'Approved', 'icon' => 'fa-check-circle', 'progress' => 100],
    ];

    // Determine current stage and guidance based on pitch status
    $currentStage = 'project_setup';
    $progressPercentage = 10;
    $contextualGuidance = '';
    $showWarning = false;
    $showDecision = false;
    $decisionType = '';
    $daysInStatus = 0;

    // Map pitch status to workflow stage for client management
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
            if ($pitch->files->count() === 0) {
                $currentStage = 'project_setup';
                $contextualGuidance = 'Start by downloading any client reference files, then upload your work as you create it.';
            } else {
                $currentStage = 'working';
                $contextualGuidance = 'Continue working on deliverables. Submit for client review when ready.';
            }
            break;
            
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
            $currentStage = 'client_review';
            $contextualGuidance = 'Your work is submitted. The client will review and either approve or request revisions.';
            $showDecision = true;
            $decisionType = 'review_submitted';
            break;
            
        case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
            $currentStage = 'working';
            $contextualGuidance = 'Review the client\'s feedback below and make the requested revisions before resubmitting.';
            $showDecision = true;
            $decisionType = 'revisions';
            break;
            
        case \App\Models\Pitch::STATUS_COMPLETED:
            $currentStage = 'approved';
            $contextualGuidance = 'Project completed! The client approved your work and payment has been processed.';
            break;
    }

    // Set progress percentage based on current stage
    if (isset($workflowStages[$currentStage])) {
        $progressPercentage = $workflowStages[$currentStage]['progress'];
    }

    // Check for workflow warnings
    if ($pitch->updated_at) {
        $daysInStatus = $pitch->updated_at->diffInDays(now());
        if ($currentStage === 'client_review' && $daysInStatus > 7) {
            $showWarning = true;
        } elseif ($currentStage === 'working' && $daysInStatus > 10) {
            $showWarning = true;
        }
    }
@endphp

<!-- Slim Client Workflow Status -->
<div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl shadow-md overflow-hidden relative z-0">
    
    <!-- Compact Header -->
    <div class="p-4 pb-2">
        <div class="flex items-center justify-between mb-3">
            <h3 class="text-base font-bold text-blue-900 flex items-center">
                <i class="fas fa-route text-blue-600 mr-2 text-sm"></i>
                Workflow Progress
            </h3>
            <div class="text-right">
                <div class="text-lg font-bold text-blue-600">{{ $progressPercentage }}%</div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="w-full bg-blue-200/50 rounded-full h-1.5 mb-3">
            <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full transition-all duration-500"
                 style="width: {{ $progressPercentage }}%"></div>
        </div>
        
        <!-- Stage Indicators -->
        <div class="flex justify-between text-xs">
            @foreach(['project_setup', 'working', 'client_review', 'approved'] as $stage)
                @php
                    $isActive = $currentStage === $stage;
                    $isCompleted = $progressPercentage >= ($workflowStages[$stage]['progress'] ?? 0);
                    $stageIcon = $workflowStages[$stage]['icon'] ?? 'fa-circle';
                @endphp
                <div class="flex flex-col items-center {{ $isActive ? 'text-blue-600' : ($isCompleted ? 'text-blue-500' : 'text-blue-400') }}">
                    <div class="w-6 h-6 rounded-full border flex items-center justify-center mb-1 transition-all duration-300
                        {{ $isCompleted ? 'bg-blue-600 border-blue-600 text-white' : 'border-blue-300' }}
                        {{ $isActive ? 'ring-1 ring-blue-300' : '' }}">
                        <i class="fas {{ $stageIcon }} text-[10px]"></i>
                    </div>
                    <span class="text-center leading-tight max-w-12 text-[10px]">{{ $workflowStages[$stage]['label'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Status-Specific Alerts -->
    <div class="px-4 pb-4">
        @if($showWarning)
            <div class="bg-amber-50/90 border border-amber-200/50 rounded-lg p-3 mb-3">
                <div class="flex items-start">
                    <i class="fas fa-clock text-amber-600 mr-2 mt-0.5 text-sm"></i>
                    <div>
                        <p class="text-xs text-amber-800 font-medium">
                            {{ $daysInStatus }} days in {{ strtolower($workflowStages[$currentStage]['label']) }}
                        </p>
                        <p class="text-xs text-amber-700 mt-1">
                            @if($currentStage === 'client_review')
                                Consider sending a follow-up message to the client.
                            @elseif($currentStage === 'working')
                                Consider submitting your work or reaching out for clarification.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @if($showDecision && $decisionType === 'revisions')
            <div class="bg-amber-50/90 border border-amber-200/50 rounded-lg p-3 mb-3">
                <div class="flex items-start">
                    <i class="fas fa-edit text-amber-600 mr-2 mt-0.5 text-sm"></i>
                    <div>
                        <p class="text-xs text-amber-800 font-medium">Client requested revisions</p>
                        <p class="text-xs text-amber-700 mt-1">Check the feedback panel below for details.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- Get file comments from the pitch_file_comments table --}}
        @php
            // Get pitch file IDs
            $pitchFileIds = $pitch->files->pluck('id');
            
            // Get all file comments for this pitch's files
            $allFileComments = DB::table('pitch_file_comments')
                ->whereIn('pitch_file_id', $pitchFileIds)
                ->get();
            
            // Filter for unresolved client comments
            $unresolvedComments = $allFileComments->filter(function($comment) {
                return !($comment->resolved ?? false) && ($comment->is_client_comment ?? false);
            });
                
            // Group by file for better display
            $commentsByFile = $unresolvedComments->groupBy('pitch_file_id');
        @endphp
        

        @if($showDecision && $decisionType === 'review_submitted' && $showActions && $component)
            @php
                $canResubmit = method_exists($component, 'canResubmit') ? $component->canResubmit : false;
            @endphp
            
            @if($commentsByFile->count() > 0)
                        <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-3 overflow-hidden">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-triangle text-amber-600 mr-2 text-xs flex-shrink-0"></i>
                                <p class="text-xs text-amber-800 font-medium">
                                    {{ $unresolvedComments->count() }} unresolved comment{{ $unresolvedComments->count() > 1 ? 's' : '' }}
                                    on {{ $commentsByFile->count() }} file{{ $commentsByFile->count() > 1 ? 's' : '' }}
                                </p>
                            </div>
                            
                            @if($commentsByFile->count() <= 3)
                                <div class="space-y-2">
                                    @foreach($commentsByFile as $pitchFileId => $comments)
                                        @php
                                            $file = $pitch->files->firstWhere('id', $pitchFileId);
                                        @endphp
                                        @if($file)
                                        <div class="bg-white border border-amber-200 rounded-md p-2 overflow-hidden">
                                            <div class="flex items-start mb-2">
                                                <i class="fas fa-file text-amber-600 mr-2 mt-0.5 text-xs flex-shrink-0"></i>
                                                <div class="flex-1 min-w-0 overflow-hidden">
                                                    <div class="font-medium text-amber-800 text-xs truncate w-full" title="{{ $file->file_name }}">
                                                        {{ Str::limit($file->file_name, 35) }}
                                                    </div>
                                                    <div class="text-xs text-amber-600 whitespace-nowrap">
                                                        {{ $comments->count() }} comment{{ $comments->count() > 1 ? 's' : '' }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="space-y-1">
                                                @foreach($comments->take(2) as $comment)
                                                    <div class="text-xs text-amber-700 bg-amber-50 rounded p-2 border-l-2 border-amber-300">
                                                        <span class="break-words">"{{ Str::limit($comment->comment ?? '', 50) }}"</span>
                                                    </div>
                                                @endforeach
                                                @if($comments->count() > 2)
                                                    <div class="text-xs text-amber-600 italic">
                                                        +{{ $comments->count() - 2 }} more comment{{ ($comments->count() - 2) > 1 ? 's' : '' }}
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-amber-700">
                                    Check the File Management section below for details.
                                </p>
                            @endif
                        </div>
                        @endif
            <div class="bg-blue-50/90 border border-blue-200/50 rounded-lg p-3 mb-3">
                <div class="flex items-start">
                    <i class="fas fa-eye text-blue-600 mr-2 mt-0.5 text-sm"></i>
                    <div class="flex-1">
                        <p class="text-xs text-blue-800 font-medium">Submitted for review</p>
                        <p class="text-xs text-blue-700 mt-1 mb-3">You can recall this submission if you need to make changes.</p>
                        
                        
                        
                        
                        @if($canResubmit)
                        <div class="bg-amber-50 border border-amber-200 rounded-md p-2 mb-3">
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle text-amber-600 mr-2 text-xs"></i>
                                <span class="text-xs text-amber-700">
                                    <strong>Files Updated:</strong> You can resubmit with changes.
                                </span>
                            </div>
                        </div>
                        @endif

                        <div class="flex gap-2">
                            <button wire:click="recallSubmission" 
                                    class="inline-flex items-center px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-700 rounded-md font-medium text-xs transition-all duration-200"
                                    wire:confirm="Are you sure you want to recall this submission? The client will no longer be able to review it until you resubmit.">
                                <i class="fas fa-undo mr-1 text-xs"></i>Recall Submission
                            </button>
                            
                            @if($canResubmit)
                            <button wire:click="submitForReview" 
                                    class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-md font-medium text-xs transition-all duration-200">
                                <i class="fas fa-paper-plane mr-1 text-xs"></i>Resubmit
                            </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if($currentStage === 'approved')
            <div class="bg-green-50/90 border border-green-200/50 rounded-lg p-3 mb-3">
                <div class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mr-2 mt-0.5 text-sm"></i>
                    <div>
                        <p class="text-xs text-green-800 font-medium">Project completed successfully!</p>
                        <p class="text-xs text-green-700 mt-1">The client approved your work.</p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Next Steps Guidance -->
        <div class="bg-blue-50/60 border border-blue-200/30 rounded-lg p-3">
            <div class="flex items-start">
                <i class="fas fa-lightbulb text-blue-600 mr-2 mt-0.5 text-sm"></i>
                <div>
                    <p class="text-xs text-blue-800 font-medium">Next Steps</p>
                    <p class="text-xs text-blue-700 mt-1">{{ $contextualGuidance }}</p>
                </div>
            </div>
        </div>
    </div>
</div>