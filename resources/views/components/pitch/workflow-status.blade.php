@props(['pitch', 'showActions' => true, 'compact' => false])

@php
    $project = $pitch->project;
    $user = $pitch->user;
    
    // Define workflow stages for pitch lifecycle (corrected based on actual system)
    $workflowStages = [
        'access_approved' => ['label' => 'Access Approved', 'icon' => 'fa-check-circle', 'progress' => 20],
        'working' => ['label' => 'Work in Progress', 'icon' => 'fa-cogs', 'progress' => 40],
        'submitted' => ['label' => 'Pitch Submitted', 'icon' => 'fa-paper-plane', 'progress' => 60],
        'under_review' => ['label' => 'Under Review', 'icon' => 'fa-eye', 'progress' => 80],
        'completed' => ['label' => 'Pitch Completed', 'icon' => 'fa-trophy', 'progress' => 100],
    ];

    // Determine current stage based on pitch status
    $currentStage = 'access_approved';
    $progressPercentage = 20;
    $statusMessage = '';
    $contextualGuidance = '';
    $timeInStatus = null;
    $showWarning = false;
    $showSuccess = false;
    $showDecision = false;
    $decisionType = '';
    $decisionMessage = '';

    // Map pitch status to workflow stage
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_PENDING:
            $currentStage = 'access_approved';
            $progressPercentage = 10;
            $statusMessage = 'Pitch submitted - awaiting approval';
            $contextualGuidance = 'Your pitch has been submitted and is waiting for the project owner to review and approve it.';
            $timeInStatus = $pitch->created_at;
            break;
            
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
            // Check if this is just starting work or continuing
            if ($pitch->files->count() === 0) {
                $currentStage = 'access_approved';
                $progressPercentage = 25;
                $statusMessage = 'Ready to start work';
                $contextualGuidance = 'Your pitch has been approved! You can now download the project files and start working.';
            } else {
                $currentStage = 'working';
                $progressPercentage = 40;
                $statusMessage = 'Work in progress';
                $contextualGuidance = 'Keep working on your pitch. Upload your progress files and submit for review when ready.';
            }
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_ENTRY:
            // Contest entries should show as working since they have immediate access
            if ($pitch->files->count() === 0) {
                $currentStage = 'working';
                $progressPercentage = 25;
                $statusMessage = 'Contest entry - ready to start';
                $contextualGuidance = 'You can download project files and upload your contest entry. Submit when ready!';
            } else {
                $currentStage = 'working';
                $progressPercentage = 70;
                $statusMessage = 'Contest entry - work in progress';
                $contextualGuidance = 'Continue working on your contest entry. Upload files as you progress.';
            }
            $timeInStatus = $pitch->created_at;
            break;
            
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
            $currentStage = 'submitted';
            $progressPercentage = 60;
            $statusMessage = 'Submitted for review';
            $contextualGuidance = 'Your pitch has been submitted and is waiting for the project owner to review it.';
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_PENDING_REVIEW:
            $currentStage = 'under_review';
            $progressPercentage = 75;
            $statusMessage = 'Under review';
            $contextualGuidance = 'The project owner is currently reviewing your pitch submission.';
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_APPROVED:
            // Check if this is final approval (has files) or just initial approval  
            if ($pitch->files->count() > 0) {
                $currentStage = 'completed';
                $progressPercentage = 95; // Almost done, just waiting for final completion
                $statusMessage = 'Work approved - awaiting final completion';
                $contextualGuidance = 'Great! Your work has been approved. The project owner will mark it as completed soon.';
                $showSuccess = true;
                $showDecision = true;
                $decisionType = 'approved';
                $decisionMessage = 'Your submission was approved by the project owner!';
            } else {
                $currentStage = 'access_approved';
                $statusMessage = 'Access approved - ready to start work';
                $contextualGuidance = 'Great! Your pitch has been approved. You can now begin working on the project and upload your files.';
                $showSuccess = true;
                $showDecision = true;
                $decisionType = 'approved';
                $decisionMessage = 'Your pitch was approved! You can now start working.';
            }
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_REVISIONS_REQUESTED:
            $currentStage = 'submitted';
            $progressPercentage = 55; // Back from review but with feedback
            $statusMessage = 'Revisions requested';
            $contextualGuidance = 'The project owner has requested changes. Review their feedback and make the necessary revisions before resubmitting.';
            $showDecision = true;
            $decisionType = 'revisions';
            $decisionMessage = 'The project owner has requested revisions to your submission.';
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_DENIED:
            $currentStage = 'submitted';
            $progressPercentage = 30; // Set back significantly
            $statusMessage = 'Pitch denied';
            $contextualGuidance = 'Your pitch was not approved. Review the feedback and consider resubmitting with improvements.';
            $showDecision = true;
            $decisionType = 'denied';
            $decisionMessage = 'Your pitch was not approved by the project owner.';
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_COMPLETED:
            $currentStage = 'completed';
            $progressPercentage = 100;
            $statusMessage = 'Project completed successfully';
            $contextualGuidance = 'Congratulations! Your pitch has been completed and the project is finished.';
            $showSuccess = true;
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_WINNER:
            $currentStage = 'completed';
            $progressPercentage = 100;
            $statusMessage = 'Contest winner!';
            $contextualGuidance = 'Congratulations! You won the contest. Your entry was selected as the best submission.';
            $showSuccess = true;
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP:
            $currentStage = 'completed';
            $progressPercentage = 100;
            $statusMessage = 'Contest runner-up';
            $contextualGuidance = 'Great work! You were selected as a runner-up in this contest.';
            $showSuccess = true;
            $timeInStatus = $pitch->updated_at;
            break;
            
        case \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED:
            $currentStage = 'completed';
            $progressPercentage = 100;
            $statusMessage = 'Contest completed';
            $contextualGuidance = 'Thank you for participating in this contest. While your entry wasn\'t selected this time, keep creating!';
            $timeInStatus = $pitch->updated_at;
            break;
            
        default:
            $currentStage = 'access_approved';
            $progressPercentage = 20;
            $statusMessage = 'Status: ' . $pitch->readable_status;
            $contextualGuidance = 'Please check with the project owner for next steps.';
            $timeInStatus = $pitch->updated_at;
    }

    if (isset($workflowStages[$currentStage])) {
        $progressPercentage = $workflowStages[$currentStage]['progress'];
    }

    // Check for warnings (time in status)
    if ($timeInStatus) {
        $daysInStatus = $timeInStatus->diffInDays(now());
        if ($currentStage === 'access_approved' && $pitch->status === \App\Models\Pitch::STATUS_PENDING && $daysInStatus > 3) {
            $showWarning = true;
        } elseif ($currentStage === 'under_review' && $daysInStatus > 5) {
            $showWarning = true;
        } elseif ($currentStage === 'working' && $daysInStatus > 14) {
            $showWarning = true;
        }
    }

    // Get pitch metrics
    $totalFiles = $pitch->files->count();
    $recentFiles = $pitch->files->where('created_at', '>=', now()->subDays(7))->count();
    $storageUsed = $pitch->files->sum('size');
    $revisionCount = $pitch->events()->where('event_type', 'revision_request')->count();
    
    // Get snapshot information
    $totalSnapshots = $pitch->snapshots->count();
    $currentSnapshot = $pitch->currentSnapshot;

    // Special handling for contest and direct hire
    $isContest = $project->isContest();
    $isDirectHire = $project->isDirectHire();
    $isClientManagement = $project->isClientManagement();
@endphp

<div class="bg-gradient-to-br from-white/95 to-purple-50/90 backdrop-blur-sm border border-white/50 rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r from-purple-50/80 to-indigo-50/80 border-b border-purple-200/30 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-purple-900 flex items-center">
                    <i class="fas fa-route text-purple-600 mr-3"></i>
                    Pitch Workflow Status
                </h3>
                <p class="text-sm text-purple-700 mt-1">{{ $statusMessage }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-purple-600">{{ $progressPercentage }}%</div>
                <div class="text-xs text-purple-500">Complete</div>
            </div>
        </div>
        
            <!-- Progress Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-xs text-purple-600 mb-2">
                <span>Progress</span>
                <span>{{ $workflowStages[$currentStage]['label'] ?? 'Unknown' }}</span>
            </div>
            <div class="w-full bg-purple-200/50 rounded-full h-2">
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
            </div>
            
            <!-- Stage Indicators -->
        <div class="flex justify-between mt-4 text-xs">
            @foreach(['access_approved', 'working', 'submitted', 'under_review', 'completed'] as $stage)
                @php
                    $isActive = $currentStage === $stage;
                    $isCompleted = $progressPercentage >= ($workflowStages[$stage]['progress'] ?? 0);
                    $stageIcon = $workflowStages[$stage]['icon'] ?? 'fa-circle';
                @endphp
                <div class="flex flex-col items-center {{ $isActive ? 'text-purple-600' : 'text-purple-400' }}">
                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-1 transition-all duration-300
                        {{ $isCompleted ? 'bg-purple-600 border-purple-600 text-white' : 'border-purple-300 text-purple-400' }}
                        {{ $isActive ? 'ring-2 ring-purple-300 ring-offset-2' : '' }}">
                        <i class="fas {{ $stageIcon }} text-xs"></i>
                    </div>
                    <span class="text-center leading-tight max-w-16">{{ $workflowStages[$stage]['label'] ?? ucfirst($stage) }}</span>
                </div>
                @endforeach
        </div>
    </div>

    <!-- Status-Specific Content -->
    <div class="p-6">
        @if($showWarning)
            <div class="bg-amber-50/90 border border-amber-200/50 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle text-amber-600 mr-3"></i>
                    <div>
                        <h4 class="font-medium text-amber-800">Attention Needed</h4>
                        <p class="text-sm text-amber-700 mt-1">
                            Your pitch has been in "{{ $workflowStages[$currentStage]['label'] ?? $currentStage }}" status for {{ $daysInStatus }} days.
                            @if($currentStage === 'access_approved' && $pitch->status === \App\Models\Pitch::STATUS_PENDING)
                                Consider following up with the project owner.
                            @elseif($currentStage === 'working')
                                Remember to submit your work for review when ready.
                            @endif
                        </p>
                </div>
            </div>
        </div>
        @endif

        @if($showDecision)
            @if($decisionType === 'approved')
                <div class="bg-green-50/90 border border-green-200/50 rounded-xl p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                            <div>
                            <h4 class="font-medium text-green-800">Approved!</h4>
                            <p class="text-sm text-green-700 mt-1">{{ $decisionMessage }}</p>
                        </div>
                    </div>
                </div>
            @elseif($decisionType === 'denied')
                <div class="bg-red-50/90 border border-red-200/50 rounded-xl p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-times-circle text-red-600 mr-3"></i>
                        <div>
                            <h4 class="font-medium text-red-800">Not Approved</h4>
                            <p class="text-sm text-red-700 mt-1">{{ $decisionMessage }}</p>
            </div>
        </div>
                </div>
            @elseif($decisionType === 'revisions')
                <div class="bg-amber-50/90 border border-amber-200/50 rounded-xl p-4 mb-6">
                    <div class="flex items-center">
                        <i class="fas fa-edit text-amber-600 mr-3"></i>
                        <div>
                            <h4 class="font-medium text-amber-800">Revisions Requested</h4>
                            <p class="text-sm text-amber-700 mt-1">{{ $decisionMessage }}</p>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        @if($showSuccess && !$showDecision)
            <div class="bg-green-50/90 border border-green-200/50 rounded-xl p-4 mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-3"></i>
                    <div>
                        <h4 class="font-medium text-green-800">Great Progress!</h4>
                        <p class="text-sm text-green-700 mt-1">
                            @if($currentStage === 'completed')
                                Congratulations on completing this project successfully!
                            @endif
                        </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Contextual Guidance -->
        <div class="bg-purple-50/90 border border-purple-200/50 rounded-xl p-4 mb-6">
            <h4 class="font-medium text-purple-900 mb-2 flex items-center">
                <i class="fas fa-lightbulb text-purple-600 mr-2"></i>
                Next Steps
            </h4>
            <p class="text-sm text-purple-800">{{ $contextualGuidance }}</p>
        </div>

        <!-- Pitch Metrics -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="text-center p-3 bg-purple-50/60 backdrop-blur-sm border border-purple-200/30 rounded-xl">
                <div class="text-lg font-bold text-purple-900">{{ $totalFiles }}</div>
                <div class="text-xs text-purple-600">File{{ $totalFiles !== 1 ? 's' : '' }} Uploaded</div>
            </div>
            <div class="text-center p-3 bg-purple-50/60 backdrop-blur-sm border border-purple-200/30 rounded-xl">
                <div class="text-lg font-bold text-purple-900">{{ $totalSnapshots }}</div>
                <div class="text-xs text-purple-600">Submission{{ $totalSnapshots !== 1 ? 's' : '' }}</div>
            </div>
            @if($revisionCount > 0)
                <div class="text-center p-3 bg-purple-50/60 backdrop-blur-sm border border-purple-200/30 rounded-xl">
                    <div class="text-lg font-bold text-purple-900">{{ $revisionCount }}</div>
                    <div class="text-xs text-purple-600">Revision{{ $revisionCount !== 1 ? 's' : '' }}</div>
                </div>
            @endif
            @if($storageUsed > 0)
                <div class="text-center p-3 bg-purple-50/60 backdrop-blur-sm border border-purple-200/30 rounded-xl">
                    <div class="text-lg font-bold text-purple-900">{{ number_format($storageUsed / 1024 / 1024, 1) }}MB</div>
                    <div class="text-xs text-purple-600">Storage Used</div>
                        </div>
            @endif
                    </div>
                    
        <!-- Action Buttons -->
        @if($showActions && !$compact && auth()->check() && auth()->id() === $pitch->user_id)
            @if($currentStage === 'access_approved' && $pitch->status === \App\Models\Pitch::STATUS_APPROVED && $pitch->files->count() === 0)
                <div class="text-center">
                    <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-medium">
                        <i class="fas fa-rocket mr-2"></i>Ready to Start Working!
                    </div>
                </div>
            @elseif($currentStage === 'working')
                <div class="text-center">
                    @if($pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                        <p class="text-sm text-purple-700 mb-3">Upload your contest entry files below</p>
                        <div class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-800 rounded-xl font-medium">
                            <i class="fas fa-trophy mr-2"></i>Working on Contest Entry
            </div>
                    @else
                        <p class="text-sm text-purple-700 mb-3">Upload your work files in the management section below</p>
                        <div class="inline-flex items-center px-4 py-2 bg-purple-100 text-purple-800 rounded-xl font-medium">
                            <i class="fas fa-upload mr-2"></i>Keep Working & Upload Files
        </div>
        @endif
                </div>
            @elseif($currentStage === 'completed')
                <div class="text-center">
                    @if($pitch->status === \App\Models\Pitch::STATUS_CONTEST_WINNER)
                        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 rounded-xl font-medium border border-yellow-300">
                            <i class="fas fa-crown mr-2"></i>Contest Winner! 🏆
                            </div>
                    @elseif($pitch->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP)
                        <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 rounded-xl font-medium border border-blue-300">
                            <i class="fas fa-medal mr-2"></i>Contest Runner-up!
                            </div>
                    @elseif($pitch->status === \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED)
                        <div class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-800 rounded-xl font-medium border border-gray-300">
                            <i class="fas fa-handshake mr-2"></i>Thanks for Participating
                        </div>
                    @else
                        <div class="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 rounded-xl font-medium">
                            <i class="fas fa-trophy mr-2"></i>Project Successfully Completed!
                    </div>
                    @endif
                </div>
            @endif
        @endif

        <!-- Special Status Messages -->
        @if($isContest)
            <div class="mt-4 p-3 bg-gradient-to-r from-purple-100/80 to-indigo-100/80 rounded-xl border border-purple-200/50">
                <div class="flex items-center text-sm">
                    <i class="fas fa-trophy text-purple-600 mr-2"></i>
                    <span class="font-medium text-purple-800">Contest Entry:</span>
                    <span class="text-purple-700 ml-1">
                        @if($pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                            You have immediate access to download project files and upload your entry
                        @else
                            This pitch is competing in a contest format
                        @endif
                    </span>
                </div>
                @if($project->submission_deadline && $pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                    <div class="flex items-center text-sm mt-2">
                        <i class="fas fa-clock text-amber-600 mr-2"></i>
                        <span class="font-medium text-amber-800">Deadline:</span>
                        <span class="text-amber-700 ml-1"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y \a\t H:i T" /></span>
                    </div>
                @endif
            </div>
        @elseif($isDirectHire)
            <div class="mt-4 p-3 bg-gradient-to-r from-green-100/80 to-emerald-100/80 rounded-xl border border-green-200/50">
                <div class="flex items-center text-sm">
                    <i class="fas fa-user-check text-green-600 mr-2"></i>
                    <span class="font-medium text-green-800">Direct Hire:</span>
                    <span class="text-green-700 ml-1">You were specifically chosen for this project</span>
                </div>
            </div>
        @elseif($isClientManagement)
            <div class="mt-4 p-3 bg-gradient-to-r from-orange-100/80 to-amber-100/80 rounded-xl border border-orange-200/50">
                <div class="flex items-center text-sm">
                    <i class="fas fa-briefcase text-orange-600 mr-2"></i>
                    <span class="font-medium text-orange-800">Client Project:</span>
                    <span class="text-orange-700 ml-1">Working directly with the project owner's client</span>
            </div>
        </div>
        @endif

        <!-- Time in Status -->
        @if($timeInStatus)
            <div class="mt-4 pt-4 border-t border-purple-200/30">
                <div class="flex items-center justify-between text-sm text-purple-600">
                    <span>Time in current status:</span>
                    <span class="font-medium">{{ $timeInStatus->diffForHumans() }}</span>
                </div>
        </div>
        @endif
    </div>
</div> 