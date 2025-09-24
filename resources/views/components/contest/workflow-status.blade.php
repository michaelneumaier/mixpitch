@props(['pitch', 'showActions' => true, 'compact' => false, 'workflowColors' => [], 'semanticColors' => []])

@php
    use Illuminate\Support\Str;
    
    $project = $pitch->project;
    $user = $pitch->user;
    
    // Define workflow stages for contest entry lifecycle
    $workflowStages = [
        'entry_created' => ['label' => 'Entry Created', 'icon' => 'fa-plus-circle', 'progress' => 25],
        'working' => ['label' => 'Working on Entry', 'icon' => 'fa-palette', 'progress' => 50],
        'submitted' => ['label' => 'Entry Submitted', 'icon' => 'fa-paper-plane', 'progress' => 75],
        'judging' => ['label' => 'Contest Judging', 'icon' => 'fa-gavel', 'progress' => 90],
        'results' => ['label' => 'Results Announced', 'icon' => 'fa-trophy', 'progress' => 100],
    ];

    // Determine current stage based on pitch state and project status
    $currentStage = 'entry_created';
    $progressPercentage = 25;
    $statusMessage = '';
    $contextualGuidance = '';
    $timeInStatus = null;
    $showWarning = false;
    $showSuccess = false;
    $showDecision = false;
    $decisionType = '';
    $decisionMessage = '';

    // Check if contest has ended (deadline passed or closed early)
    $contestEnded = $project->isSubmissionPeriodClosed();
    
    // Check if results have been announced
    $hasWinner = $project->pitches()->whereIn('status', [
        \App\Models\Pitch::STATUS_CONTEST_WINNER,
        \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP,
        \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED
    ])->exists();

    // Determine current stage based on contest entry status
    if (in_array($pitch->status, [
        \App\Models\Pitch::STATUS_CONTEST_WINNER,
        \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP,
        \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED
    ])) {
        // Results announced
        $currentStage = 'results';
        $progressPercentage = 100;
        $timeInStatus = $pitch->updated_at;
        
        switch ($pitch->status) {
            case \App\Models\Pitch::STATUS_CONTEST_WINNER:
                $statusMessage = '🏆 Contest Winner!';
                $contextualGuidance = 'Congratulations! Your entry was selected as the winner of this contest.';
                $showSuccess = true;
                $showDecision = true;
                $decisionType = 'winner';
                $decisionMessage = 'Your entry won the contest!';
                break;
                
            case \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP:
                $statusMessage = '🥈 Contest Runner-Up';
                $contextualGuidance = 'Great work! Your entry was selected as a runner-up in this contest.';
                $showSuccess = true;
                $showDecision = true;
                $decisionType = 'runner_up';
                $decisionMessage = 'Your entry was selected as a runner-up!';
                break;
                
            case \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED:
                $statusMessage = 'Contest Completed';
                $contextualGuidance = 'Thank you for participating! While your entry wasn\'t selected this time, keep creating amazing work.';
                $showDecision = true;
                $decisionType = 'not_selected';
                $decisionMessage = 'Contest results have been announced.';
                break;
        }
    } elseif ($contestEnded || $hasWinner) {
        // Contest has ended, judging phase
        $currentStage = 'judging';
        $progressPercentage = 90;
        $statusMessage = 'Contest Judging in Progress';
        $contextualGuidance = 'The contest has closed and entries are being judged. Results will be announced soon.';
        $timeInStatus = $project->submission_deadline;
        
        // Check if judging is taking too long
        if ($project->submission_deadline && $project->submission_deadline->diffInDays(now()) > 14) {
            $showWarning = true;
        }
    } elseif ($pitch->submitted_at) {
        // Entry has been submitted
        $currentStage = 'submitted';
        $progressPercentage = 75;
        $statusMessage = 'Entry Submitted Successfully';
        $contextualGuidance = 'Your contest entry has been submitted and is awaiting the judging phase. Files can no longer be modified.';
        $timeInStatus = $pitch->submitted_at;
        $showSuccess = true;
        
        // Show deadline info if available
        if ($project->submission_deadline) {
            $daysUntilDeadline = now()->diffInDays($project->submission_deadline, false);
            if ($daysUntilDeadline > 0) {
                $contextualGuidance .= " Contest closes in {$daysUntilDeadline} " . Str::plural('day', $daysUntilDeadline) . ".";
            }
        }
    } elseif ($pitch->files->count() > 0) {
        // Has files uploaded, working on entry
        $currentStage = 'working';
        $progressPercentage = 50;
        $statusMessage = 'Working on Contest Entry';
        $contextualGuidance = 'Continue uploading and refining your entry files. Remember to submit your entry before the deadline.';
        $timeInStatus = $pitch->files->first()->created_at;
        
        // Show warning if deadline is approaching
        if ($project->submission_deadline) {
            $daysUntilDeadline = now()->diffInDays($project->submission_deadline, false);
            if ($daysUntilDeadline <= 3 && $daysUntilDeadline > 0) {
                $showWarning = true;
                $contextualGuidance = "⚠️ Contest deadline is in {$daysUntilDeadline} " . Str::plural('day', $daysUntilDeadline) . "! Make sure to submit your entry.";
            } elseif ($daysUntilDeadline <= 0) {
                $showWarning = true;
                $contextualGuidance = "❌ Contest deadline has passed. You can no longer submit entries.";
            }
        }
    } else {
        // Just created, no files yet
        $currentStage = 'entry_created';
        $progressPercentage = 25;
        $statusMessage = 'Contest Entry Created';
        $contextualGuidance = 'Download the project files and start working on your contest entry. Upload your files when ready.';
        $timeInStatus = $pitch->created_at;
        
        // Show deadline warning
        if ($project->submission_deadline) {
            $daysUntilDeadline = now()->diffInDays($project->submission_deadline, false);
            if ($daysUntilDeadline > 0) {
                $contextualGuidance .= " You have {$daysUntilDeadline} " . Str::plural('day', $daysUntilDeadline) . " to complete and submit your entry.";
            }
        }
    }

    // Get contest metrics
    $totalFiles = $pitch->files->count();
    $recentFiles = $pitch->files->where('created_at', '>=', now()->subDays(7))->count();
    $storageUsed = $pitch->files->sum('size');
    
    // Contest-specific metrics
    $totalEntries = $project->pitches()->where('status', 'like', '%contest%')->count();
    $submittedEntries = $project->pitches()->where('status', 'contest_entry')->whereNotNull('submitted_at')->count();
    
    // Check if winner needs Stripe Connect setup for prize payouts
    $needsStripeConnect = false;
    $hasWonPrize = false;
    $prizeAmount = 0;
    
    if (in_array($pitch->status, [\App\Models\Pitch::STATUS_CONTEST_WINNER, \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP])) {
        $hasWonPrize = true;
        
        // Check if this contest has cash prizes
        if ($project->hasCashPrizes()) {
            // Find the prize for this winner's placement
            $placement = $pitch->status === \App\Models\Pitch::STATUS_CONTEST_WINNER ? '1st' : 'runner_up';
            $prize = $project->contestPrizes()->where('placement', $placement)->where('prize_type', 'cash')->first();
            
            if ($prize && $prize->cash_amount > 0) {
                $prizeAmount = $prize->cash_amount;
                // Check if user needs Stripe Connect setup
                $needsStripeConnect = !$user->stripe_account_id || !$user->hasValidStripeConnectAccount();
            }
        }
    }
@endphp

<div class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-yellow-50/95 to-amber-50/90 dark:from-yellow-950/95 dark:to-amber-950/90' }} backdrop-blur-sm border {{ $workflowColors['border'] ?? 'border-yellow-200/50 dark:border-yellow-700/50' }} rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r {{ $workflowColors['accent_bg'] ?? 'from-yellow-100/80 to-amber-100/80 dark:from-yellow-900/80 dark:to-amber-900/80' }} border-b {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold {{ $workflowColors['text_primary'] ?? 'text-yellow-900 dark:text-yellow-100' }} flex items-center">
                    <i class="fas fa-trophy {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }} mr-3"></i>
                    Contest Entry Progress
                </h3>
                <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-yellow-700 dark:text-yellow-300' }} mt-1">{{ $statusMessage }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}">{{ $progressPercentage }}%</div>
                <div class="text-xs {{ $workflowColors['text_muted'] ?? 'text-yellow-500 dark:text-yellow-400' }}">Complete</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-xs {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }} mb-2">
                <span>Progress</span>
                <span>{{ $workflowStages[$currentStage]['label'] ?? 'Unknown' }}</span>
            </div>
            <div class="w-full bg-yellow-200/50 dark:bg-yellow-800/50 rounded-full h-2">
                <div class="bg-gradient-to-r {{ $workflowColors['icon'] ?? 'from-yellow-500 to-amber-600' }} h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>
        
        <!-- Payout Status for Winners -->
        @if(auth()->check() && auth()->id() === $pitch->user_id)
            <x-pitch.payout-status-compact :pitch="$pitch" />
        @endif

        <!-- Stage Indicators -->
        <div class="flex justify-between mt-4 text-xs">
            @foreach(['entry_created', 'working', 'submitted', 'judging', 'results'] as $stage)
                @php
                    $isActive = $currentStage === $stage;
                    $isCompleted = $progressPercentage >= ($workflowStages[$stage]['progress'] ?? 0);
                    $stageIcon = $workflowStages[$stage]['icon'] ?? 'fa-circle';
                @endphp
                <div class="flex flex-col items-center {{ $isActive ? $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' : $workflowColors['text_muted'] ?? 'text-yellow-400 dark:text-yellow-500' }}">
                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-1 transition-all duration-300
                        {{ $isCompleted ? ($workflowColors['icon'] ?? 'bg-yellow-600 border-yellow-600') . ' text-white' : ($workflowColors['accent_border'] ?? 'border-yellow-300 dark:border-yellow-600') . ' ' . ($workflowColors['text_muted'] ?? 'text-yellow-400 dark:text-yellow-500') }}
                        {{ $isActive ? 'ring-2 ring-yellow-300 dark:ring-yellow-600 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
                        <i class="fas {{ $stageIcon }} text-xs"></i>
                    </div>
                    <span class="text-center leading-tight max-w-16">{{ $workflowStages[$stage]['label'] ?? ucfirst($stage) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Content Body -->
    <div class="p-6 space-y-6">
        <!-- Status-Specific Messages -->
        @if($showDecision)
            <div class="p-4 rounded-xl border 
                {{ $decisionType === 'winner' ? 'bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 border-green-200 dark:border-green-700' : 
                   ($decisionType === 'runner_up' ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 border-blue-200 dark:border-blue-700' : 
                    'bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-900 dark:to-slate-900 border-gray-200 dark:border-gray-700') }}">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        @if($decisionType === 'winner')
                            <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-trophy text-green-600 dark:text-green-400"></i>
                            </div>
                        @elseif($decisionType === 'runner_up')
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center">
                                <i class="fas fa-medal text-blue-600 dark:text-blue-400"></i>
                            </div>
                        @else
                            <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center">
                                <i class="fas fa-flag-checkered text-gray-600 dark:text-gray-400"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <p class="font-medium {{ $decisionType === 'winner' ? 'text-green-800 dark:text-green-200' : 
                                                 ($decisionType === 'runner_up' ? 'text-blue-800 dark:text-blue-200' : 'text-gray-800 dark:text-gray-200') }}">
                            {{ $decisionMessage }}
                        </p>
                        @if($pitch->rank)
                            <p class="text-sm mt-1 {{ $decisionType === 'winner' ? 'text-green-600 dark:text-green-400' : 
                                                     ($decisionType === 'runner_up' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-gray-400') }}">
                                Final Ranking: #{{ $pitch->rank }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Stripe Connect Setup Notification for Winners -->
        @if($needsStripeConnect && $hasWonPrize && $prizeAmount > 0)
            <div class="bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-purple-950 dark:to-indigo-950 border border-purple-200 dark:border-purple-700 rounded-xl p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <div class="w-8 h-8 bg-purple-100 dark:bg-purple-900 rounded-full flex items-center justify-center">
                            <i class="fas fa-dollar-sign text-purple-600 dark:text-purple-400"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-purple-800 dark:text-purple-200 mb-2">Prize Payout Setup Required</h4>
                        <p class="text-sm text-purple-700 dark:text-purple-300 mb-3">
                            Congratulations on winning ${{ number_format($prizeAmount, 2) }}! To receive your prize payout, you need to set up your Stripe Connect account for receiving payments.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('payouts.setup.index') }}" 
                               class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 shadow-lg">
                                <i class="fas fa-cog mr-2"></i>
                                Set Up Prize Payouts
                            </a>
                            <div class="text-xs text-purple-600 dark:text-purple-400 flex items-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                This is different from your billing payment methods
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @elseif($hasWonPrize && $prizeAmount > 0 && !$needsStripeConnect)
            <div class="bg-gradient-to-r from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950 border border-green-200 dark:border-green-700 rounded-xl p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <div class="w-8 h-8 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-green-800 dark:text-green-200 mb-2">Prize Payout Ready</h4>
                        <p class="text-sm text-green-700 dark:text-green-300">
                            Your Stripe Connect account is set up and ready to receive your ${{ number_format($prizeAmount, 2) }} prize payout. You'll receive the funds after the contest owner processes the payment.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contextual Guidance -->
        <div class="{{ $workflowColors['accent_bg'] ?? 'bg-yellow-50/50 dark:bg-yellow-950/50' }} border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} rounded-xl p-4">
            <h4 class="font-medium {{ $workflowColors['text_primary'] ?? 'text-yellow-800 dark:text-yellow-200' }} mb-2 flex items-center">
                <i class="fas fa-lightbulb mr-2 {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}"></i>
                What's Next?
            </h4>
            <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-yellow-700 dark:text-yellow-300' }}">{{ $contextualGuidance }}</p>
        </div>

        <!-- Contest Timeline -->
        @if($project->submission_deadline)
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} rounded-xl p-4">
                <h4 class="font-medium {{ $workflowColors['text_primary'] ?? 'text-yellow-800 dark:text-yellow-200' }} mb-3 flex items-center">
                    <i class="fas fa-calendar-alt mr-2 {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}"></i>
                    Contest Timeline
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="{{ $workflowColors['text_secondary'] ?? 'text-yellow-700 dark:text-yellow-300' }}">Contest Deadline:</span>
                        <span class="font-medium {{ $workflowColors['text_primary'] ?? 'text-yellow-900 dark:text-yellow-100' }}"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y g:i A" /></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="{{ $workflowColors['text_secondary'] ?? 'text-yellow-700 dark:text-yellow-300' }}">Time Remaining:</span>
                        <span class="font-medium {{ $contestEnded ? ($semanticColors['danger']['text'] ?? 'text-red-600 dark:text-red-400') : ($workflowColors['text_primary'] ?? 'text-yellow-900 dark:text-yellow-100') }}">
                            @if($contestEnded)
                                Contest Ended
                            @else
                                <x-datetime :date="$project->submission_deadline" relative="true" />
                            @endif
                        </span>
                    </div>
                    @if($pitch->submitted_at)
                        <div class="flex items-center justify-between">
                            <span class="{{ $workflowColors['text_secondary'] ?? 'text-yellow-700 dark:text-yellow-300' }}">Your Submission:</span>
                            <span class="font-medium {{ $semanticColors['success']['text'] ?? 'text-green-600 dark:text-green-400' }}"><x-datetime :date="$pitch->submitted_at" format="M d, Y g:i A" /></span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Entry Metrics -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} rounded-xl p-3 text-center">
                <div class="text-lg font-bold {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}">{{ $totalFiles }}</div>
                <div class="text-xs {{ $workflowColors['text_muted'] ?? 'text-yellow-500 dark:text-yellow-400' }}">Files Uploaded</div>
            </div>
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} rounded-xl p-3 text-center">
                <div class="text-lg font-bold {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}">{{ \App\Models\Pitch::formatBytes($storageUsed) }}</div>
                <div class="text-xs {{ $workflowColors['text_muted'] ?? 'text-yellow-500 dark:text-yellow-400' }}">Storage Used</div>
            </div>
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} rounded-xl p-3 text-center">
                <div class="text-lg font-bold {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}">{{ $totalEntries }}</div>
                <div class="text-xs {{ $workflowColors['text_muted'] ?? 'text-yellow-500 dark:text-yellow-400' }}">Total Entries</div>
            </div>
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-yellow-200/30 dark:border-yellow-700/30' }} rounded-xl p-3 text-center">
                <div class="text-lg font-bold {{ $workflowColors['icon'] ?? 'text-yellow-600 dark:text-yellow-400' }}">
                    @if($pitch->submitted_at)
                        <i class="fas fa-check-circle {{ $semanticColors['success']['icon'] ?? 'text-green-500 dark:text-green-400' }}"></i>
                    @else
                        <i class="fas fa-clock {{ $semanticColors['warning']['icon'] ?? 'text-amber-500 dark:text-amber-400' }}"></i>
                    @endif
                </div>
                <div class="text-xs {{ $workflowColors['text_muted'] ?? 'text-yellow-500 dark:text-yellow-400' }}">
                    {{ $pitch->submitted_at ? 'Submitted' : 'Draft' }}
                </div>
            </div>
        </div>

        <!-- Status-Specific Actions -->
        @if($showActions && !$compact && auth()->check() && auth()->id() === $pitch->user_id)
            @if($currentStage === 'entry_created')
                <div class="text-center">
                    <div class="inline-flex items-center px-4 py-2 bg-gradient-to-r {{ $workflowColors['icon'] ?? 'from-yellow-600 to-amber-600' }} text-white rounded-xl font-medium">
                        <i class="fas fa-download mr-2"></i>Download Project Files & Start Creating
                    </div>
                </div>
            @elseif($currentStage === 'working' && !$pitch->submitted_at)
                <div class="text-center">
                    <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-yellow-700 dark:text-yellow-300' }} mb-3">Upload your contest entry files and submit when ready</p>
                    <div class="inline-flex items-center px-4 py-2 {{ $workflowColors['accent_bg'] ?? 'bg-yellow-100 dark:bg-yellow-900' }} {{ $workflowColors['text_primary'] ?? 'text-yellow-800 dark:text-yellow-200' }} rounded-xl font-medium">
                        <i class="fas fa-upload mr-2"></i>Continue Working on Entry
                    </div>
                </div>
            @elseif($currentStage === 'submitted')
                <div class="text-center">
                    <div class="inline-flex items-center px-4 py-2 {{ $semanticColors['success']['bg'] ?? 'bg-green-100 dark:bg-green-900' }} {{ $semanticColors['success']['text'] ?? 'text-green-800 dark:text-green-200' }} rounded-xl font-medium">
                        <i class="fas fa-check-circle mr-2"></i>Entry Successfully Submitted
                    </div>
                </div>
            @endif
        @endif

        <!-- Time in Status Warning -->
        @if($showWarning && $timeInStatus)
            <div class="{{ $semanticColors['danger']['bg'] ?? 'bg-red-50 dark:bg-red-950' }} border {{ $semanticColors['danger']['border'] ?? 'border-red-200 dark:border-red-700' }} rounded-xl p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0 mr-3">
                        <div class="w-8 h-8 {{ $semanticColors['danger']['bg'] ?? 'bg-red-100 dark:bg-red-900' }} rounded-full flex items-center justify-center">
                            <i class="fas fa-exclamation-triangle {{ $semanticColors['danger']['icon'] ?? 'text-red-600 dark:text-red-400' }}"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium {{ $semanticColors['danger']['text'] ?? 'text-red-800 dark:text-red-200' }}">Action Needed</p>
                        <p class="text-sm {{ $semanticColors['danger']['icon'] ?? 'text-red-600 dark:text-red-400' }} mt-1">
                            @if($currentStage === 'working')
                                Contest deadline is approaching! Make sure to submit your entry soon.
                            @elseif($currentStage === 'judging')
                                Contest judging has been in progress for {{ $timeInStatus->diffInDays(now()) }} days.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div> 