@props(['project'])

@php
    // Check if this is a contest - if so, use dedicated contest workflow component
    if ($project->isContest()) {
        // Use contest-specific workflow component
        $useContestComponent = true;
    } else {
        $useContestComponent = false;
        
        // Original workflow stages for non-contest projects
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

        // Original logic for non-contest projects
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
        $completedPitch = $project->pitches->where('status', 'completed')->first();
        $hasApprovedPitch = $approvedPitch !== null;
        $hasCompletedPitch = $completedPitch !== null;

        // Load user relationship for completed pitch if needed for tipjar functionality
        if ($hasCompletedPitch && !$completedPitch->relationLoaded('user')) {
            $completedPitch->load('user');
        }

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
        } elseif (!$hasApprovedPitch && !$hasCompletedPitch && $pitchCount > 0) {
            $currentStage = 'reviewing';
            $statusMessage = "Reviewing {$pitchCount} pitch" . ($pitchCount > 1 ? 'es' : '');
            $contextualGuidance = 'Review submitted pitches and approve one to move forward with the project.';
            $timeInStatus = $project->pitches->first()->created_at;
        } elseif ($hasCompletedPitch) {
            // Handle completed pitch with payment status consideration
            $paymentStatus = $completedPitch->payment_status;
            $requiresPayment = $project->budget > 0;
            
            if ($requiresPayment) {
                switch ($paymentStatus) {
                    case 'pending':
                        $currentStage = 'completed';
                        $statusMessage = 'Pitch completed - payment pending';
                        $contextualGuidance = 'The work has been completed successfully. Process payment to finalize the project.';
                        break;
                    case 'processing':
                        $currentStage = 'completed';
                        $statusMessage = 'Payment processing';
                        $contextualGuidance = 'Payment is being processed. You will receive confirmation once completed.';
                        break;
                    case 'paid':
                        $currentStage = 'completed';
                        $statusMessage = 'Project completed & paid';
                        $contextualGuidance = 'Project successfully completed and payment processed. Thank you for using our platform!';
                        break;
                    case 'failed':
                        $currentStage = 'completed';
                        $statusMessage = 'Payment failed - action required';
                        $contextualGuidance = 'The work is completed but payment failed. Please retry payment processing.';
                        break;
                    default:
                        $currentStage = 'completed';
                        $statusMessage = 'Pitch completed - payment pending';
                        $contextualGuidance = 'The work has been completed successfully. Process payment to finalize the project.';
                }
            } else {
                $currentStage = 'completed';
                $statusMessage = 'Project completed';
                $contextualGuidance = 'Project successfully completed. Thank you for using our platform!';
            }
            $timeInStatus = $completedPitch->updated_at;
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
        $pitchCount = $project->pitches->count();
    }
@endphp

@if($useContestComponent)
    <x-contest.project-workflow-status :project="$project" />
@else
    <div class="bg-gradient-to-br from-white/95 to-blue-50/90 backdrop-blur-sm border border-white/50 rounded-2xl shadow-lg overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 border-b border-blue-200/30 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-blue-900 flex items-center">
                        <i class="fas fa-project-diagram text-blue-600 mr-3"></i>
                        Project Workflow Status
                    </h3>
                    <p class="text-sm text-blue-700 mt-1">{{ $statusMessage }}</p>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-blue-600">{{ $progressPercentage }}%</div>
                    <div class="text-xs text-blue-500">Complete</div>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="mt-4">
                <div class="flex justify-between text-xs text-blue-600 mb-2">
                    <span>Progress</span>
                    <span>{{ $workflowStages[$currentStage]['label'] }}</span>
                </div>
                <div class="w-full bg-blue-200/50 rounded-full h-2">
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-2 rounded-full transition-all duration-500"
                         style="width: {{ $progressPercentage }}%"></div>
                </div>
            </div>

            <!-- Stage Indicators -->
            <div class="flex justify-between mt-4 text-xs">
                @foreach(['open', 'reviewing', 'approved', 'in_progress', 'completed'] as $stage)
                    @php
                        $isActive = $currentStage === $stage;
                        $isCompleted = $progressPercentage >= ($workflowStages[$stage]['progress'] ?? 0);
                        $stageIcon = $workflowStages[$stage]['icon'] ?? 'fa-circle';
                    @endphp
                    <div class="flex flex-col items-center {{ $isActive ? 'text-blue-600' : 'text-blue-400' }}">
                        <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-1 transition-all duration-300
                            {{ $isCompleted ? 'bg-blue-600 border-blue-600 text-white' : 'border-blue-300 text-blue-400' }}
                            {{ $isActive ? 'ring-2 ring-blue-300 ring-offset-2' : '' }}">
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
                <div class="bg-gradient-to-r from-amber-50/80 to-orange-50/80 backdrop-blur-sm border border-amber-200/50 rounded-xl p-4 mb-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl mr-3">
                            <i class="fas fa-exclamation-triangle text-white"></i>
                        </div>
                        <div>
                            <h4 class="font-bold text-amber-800">Attention Needed</h4>
                            <p class="text-sm text-amber-700 mt-1">
                                This project has been in "{{ $workflowStages[$currentStage]['label'] }}" status for {{ $daysInStatus }} days.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Contextual Guidance -->
            <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 mb-6">
                <h4 class="font-bold text-blue-900 mb-2 flex items-center">
                    <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                        <i class="fas fa-lightbulb text-white text-sm"></i>
                    </div>
                    Next Steps
                </h4>
                <p class="text-sm text-blue-800">{{ $contextualGuidance }}</p>
            </div>

            <!-- Project Metrics -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-gradient-to-br from-white/80 to-blue-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mx-auto mb-2">
                        <i class="fas fa-bullhorn text-white text-sm"></i>
                    </div>
                    <div class="text-lg font-bold text-blue-900">{{ $pitchCount }}</div>
                    <div class="text-xs text-blue-600">Pitch{{ $pitchCount !== 1 ? 'es' : '' }}</div>
                </div>
                <div class="bg-gradient-to-br from-white/80 to-blue-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mx-auto mb-2">
                        <i class="fas fa-folder text-white text-sm"></i>
                    </div>
                    <div class="text-lg font-bold text-blue-900">{{ $totalFiles }}</div>
                    <div class="text-xs text-blue-600">Files</div>
                </div>
                <div class="bg-gradient-to-br from-white/80 to-blue-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mx-auto mb-2">
                        <i class="fas fa-hdd text-white text-sm"></i>
                    </div>
                    <div class="text-lg font-bold text-blue-900">{{ number_format($storageUsed / 1024 / 1024, 1) }}MB</div>
                    <div class="text-xs text-blue-600">Storage</div>
                </div>
                <div class="bg-gradient-to-br from-white/80 to-blue-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 text-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mx-auto mb-2">
                        <i class="fas fa-chart-line text-white text-sm"></i>
                    </div>
                    <div class="text-lg font-bold text-blue-900">{{ $recentActivity }}</div>
                    <div class="text-xs text-blue-600">Recent Activity</div>
                </div>
            </div>

            <!-- Status-Specific Actions -->
            @if($currentStage === 'completed' && $hasCompletedPitch)
                @php
                    $paymentStatus = $completedPitch->payment_status;
                    $requiresPayment = $project->budget > 0;
                    $producerHasTipjar = !empty($completedPitch->user->tipjar_link);
                @endphp
                
                <div class="space-y-4">
                    <!-- Payment Actions -->
                    @if($requiresPayment)
                        @if($paymentStatus === 'pending' || $paymentStatus === 'failed' || empty($paymentStatus))
                            <div class="bg-gradient-to-r from-amber-50/80 to-orange-50/80 backdrop-blur-sm border border-amber-200/50 rounded-xl p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl mr-3">
                                            <i class="fas fa-credit-card text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-amber-800">Payment Required</h4>
                                            <p class="text-sm text-amber-700">
                                                @if($paymentStatus === 'failed')
                                                    Previous payment failed. Please retry payment processing.
                                                @else
                                                    Process payment of ${{ number_format($project->budget, 2) }} to complete the project.
                                                @endif
                                            </p>
                                        </div>
                                    </div>
                                    <a href="{{ route('projects.pitches.payment.overview', ['project' => $project, 'pitch' => $completedPitch]) }}" 
                                       class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                        <i class="fas fa-credit-card mr-2"></i> Process Payment
                                    </a>
                                </div>
                            </div>
                        @elseif($paymentStatus === 'processing')
                            <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-3">
                                        <i class="fas fa-spinner fa-spin text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-blue-800">Payment Processing</h4>
                                        <p class="text-sm text-blue-700">Your payment of ${{ number_format($project->budget, 2) }} is being processed. You will receive confirmation shortly.</p>
                                    </div>
                                </div>
                            </div>
                        @elseif($paymentStatus === 'paid')
                            <div class="bg-gradient-to-r from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                    <div class="flex items-center">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-3">
                                            <i class="fas fa-check-circle text-white"></i>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-green-800">Payment Completed</h4>
                                            <p class="text-sm text-green-700">Payment of ${{ number_format($project->budget, 2) }} processed successfully{{ $completedPitch->payment_completed_at && is_object($completedPitch->payment_completed_at) ? ' on ' . $completedPitch->payment_completed_at->format('M d, Y') : '' }}.</p>
                                        </div>
                                    </div>
                                    <a href="{{ route('projects.pitches.payment.receipt', ['project' => $project, 'pitch' => $completedPitch]) }}" 
                                       class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                        <i class="fas fa-receipt mr-2"></i> View Receipt
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Tipjar Section -->
                    @if($producerHasTipjar && ($paymentStatus === 'paid' || !$requiresPayment))
                        <div class="bg-gradient-to-r from-purple-50/80 to-pink-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl mr-3">
                                        <i class="fas fa-heart text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-purple-800">Show Your Appreciation</h4>
                                        <p class="text-sm text-purple-700">Love the work? Consider leaving a tip for <strong>{{ $completedPitch->user->name }}</strong>!</p>
                                    </div>
                                </div>
                                <a href="{{ $completedPitch->user->tipjar_link }}" target="_blank"
                                   class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                    <i class="fas fa-donate mr-2"></i> Leave a Tip
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Project Summary -->
                    <div class="bg-gradient-to-r from-gray-50/80 to-blue-50/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-gray-500 to-blue-600 rounded-xl mr-3">
                                    <i class="fas fa-trophy text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-800">Project Completed</h4>
                                    <p class="text-sm text-gray-700">Completed by <strong>{{ $completedPitch->user->name }}</strong>{{ $completedPitch->completed_at && is_object($completedPitch->completed_at) ? ' on ' . $completedPitch->completed_at->format('M d, Y') : '' }}</p>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row gap-2">
                                <a href="{{ route('projects.pitches.show', ['project' => $project, 'pitch' => $completedPitch]) }}" 
                                   class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                    <i class="fas fa-eye mr-2"></i> View Pitch
                                </a>
                                <a href="{{ route('profile.show', $completedPitch->user) }}" 
                                   class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                    <i class="fas fa-user mr-2"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @elseif($currentStage === 'open' && !$project->is_published)
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="publish" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-bullhorn mr-2"></i>
                        Publish Project
                    </button>
                    <a href="{{ route('projects.edit', $project) }}" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Details
                    </a>
                </div>
            @elseif($currentStage === 'reviewing')
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="#pitches-section" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-search mr-2"></i>
                        Review Pitches
                    </a>
                    <a href="{{ route('projects.show', $project) }}" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-eye mr-2"></i>
                        View Public Page
                    </a>
                </div>
            @elseif($currentStage === 'under_review')
                <div class="flex flex-col sm:flex-row gap-3">
                    <button wire:click="approveWork" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-check mr-2"></i>
                        Approve Work
                    </button>
                    <button wire:click="requestRevisions" class="flex-1 inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-edit mr-2"></i>
                        Request Revisions
                    </button>
                </div>
            @elseif($currentStage === 'completed')
                <div class="text-center">
                    <div class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-100/80 to-emerald-100/80 backdrop-blur-sm border border-green-200/50 text-green-800 rounded-xl font-medium">
                        <i class="fas fa-trophy mr-2"></i>
                        Project Successfully Completed!
                    </div>
                </div>
            @endif

            <!-- Time in Status -->
            @if($timeInStatus)
                <div class="mt-4 pt-4 border-t border-blue-200/50">
                    <div class="flex items-center justify-between text-sm text-blue-600">
                        <span>Time in current status:</span>
                        <span class="font-medium">{{ $timeInStatus->diffForHumans() }}</span>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif 