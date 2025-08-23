@props(['project', 'workflowColors' => [], 'semanticColors' => []])

@php
    // Contest-specific workflow stages with finalized state
    $workflowStages = [
        'contest_setup' => ['label' => 'Contest Setup', 'icon' => 'fa-cog', 'progress' => 10],
        'contest_open' => ['label' => 'Accepting Entries', 'icon' => 'fa-trophy', 'progress' => 25],
        'contest_submissions_closed' => ['label' => 'Submissions Closed', 'icon' => 'fa-door-closed', 'progress' => 50],
        'contest_judging' => ['label' => 'Judging Phase', 'icon' => 'fa-gavel', 'progress' => 75],
        'contest_finalized' => ['label' => 'Judging Finalized', 'icon' => 'fa-flag-checkered', 'progress' => 90],
        'contest_results' => ['label' => 'Results Announced', 'icon' => 'fa-crown', 'progress' => 100],
    ];
    
    // Determine current contest stage
    $currentStage = 'contest_setup';
    $progressPercentage = 10;
    $statusMessage = '';
    $contextualGuidance = '';
    $timeInStatus = null;
    $showWarning = false;
    
    // Get contest data
    $contestEntries = $project->getContestEntries();
    $hasEntries = $contestEntries->isNotEmpty();
    $isFinalized = $project->isJudgingFinalized();
    $winnerExists = $contestEntries->whereIn('status', [\App\Models\Pitch::STATUS_CONTEST_WINNER])->isNotEmpty();
    $placedEntries = $contestEntries->whereIn('status', [
        \App\Models\Pitch::STATUS_CONTEST_WINNER,
        \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP
    ])->count();
    $notSelectedCount = $contestEntries->where('status', \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED)->count();
    
    // Determine contest lifecycle stage
    if (!$project->is_published) {
        $currentStage = 'contest_setup';
        $statusMessage = 'Contest setup in progress';
        $contextualGuidance = 'Complete contest configuration and publish to start accepting entries.';
        $timeInStatus = $project->created_at;
    } elseif (!$hasEntries) {
        $currentStage = 'contest_open';
        $statusMessage = 'Contest is open for entries';
        $contextualGuidance = 'Promote your contest to attract entries from talented producers.';
        $timeInStatus = $project->created_at;
        
        // Check if deadline passed but no entries
        if ($project->submission_deadline && now()->isAfter($project->submission_deadline)) {
            $currentStage = 'contest_submissions_closed';
            $statusMessage = 'Contest closed - No entries received';
            $contextualGuidance = 'Consider extending the deadline or promoting the contest more widely.';
            $showWarning = true;
        }
    } elseif ($hasEntries && !$project->isSubmissionPeriodClosed()) {
        $currentStage = 'contest_open';
        $statusMessage = "Contest open - {$contestEntries->count()} entries received";
        $contextualGuidance = 'Contest is still accepting entries until the deadline.';
        $timeInStatus = $project->created_at;
    } elseif ($hasEntries && $project->isSubmissionPeriodClosed() && !$isFinalized) {
        $currentStage = 'contest_judging';
        $statusMessage = 'Judging in progress';
        $contextualGuidance = 'Review the entries and make placement decisions. Finalize judging when complete.';
        $timeInStatus = $project->getEffectiveSubmissionDeadline();
        
        // Warning if judging taking too long
        if ($project->submission_deadline && $project->submission_deadline->diffInDays(now()) > 14) {
            $showWarning = true;
        }
    } elseif ($isFinalized && ($winnerExists || $placedEntries > 0)) {
        $currentStage = 'contest_results';
        $statusMessage = 'Contest completed - Winners announced';
        $contextualGuidance = 'Contest has concluded successfully with winners selected and announced.';
        $timeInStatus = $project->judging_finalized_at;
    } elseif ($isFinalized && !$winnerExists && $notSelectedCount === 0) {
        // Special case: Judging finalized but no entries processed yet
        $currentStage = 'contest_finalized';
        $statusMessage = 'Contest judging finalized - Ready for announcement';
        $contextualGuidance = 'Judging is complete. You can now select winners or formally announce results.';
        $timeInStatus = $project->judging_finalized_at;
        $showWarning = true;
    } elseif ($isFinalized && ($winnerExists || $placedEntries > 0) && $project->results_announced_at) {
        $currentStage = 'contest_results';
        $statusMessage = 'Contest completed - Results announced';
        $contextualGuidance = 'Contest has concluded successfully with winners selected and results formally announced.';
        $timeInStatus = $project->results_announced_at;
    } elseif ($isFinalized && ($winnerExists || $placedEntries > 0)) {
        $currentStage = 'contest_finalized';
        $statusMessage = 'Contest judging complete - Ready to announce';
        $contextualGuidance = 'Winners have been selected. Click "Announce Results" to formally complete the contest at 100%.';
        $timeInStatus = $project->judging_finalized_at;
    } elseif ($isFinalized && !$winnerExists && $notSelectedCount > 0 && $project->results_announced_at) {
        $currentStage = 'contest_results';
        $statusMessage = 'Contest completed - Results announced';
        $contextualGuidance = 'Contest has concluded. Results have been formally announced to all participants.';
        $timeInStatus = $project->results_announced_at;
    } elseif ($isFinalized && !$winnerExists && $notSelectedCount > 0) {
        $currentStage = 'contest_finalized';
        $statusMessage = 'Judging finalized - No winners selected';
        $contextualGuidance = 'Contest judging has been completed. You can formally announce the results to reach 100% completion.';
        $timeInStatus = $project->judging_finalized_at;
    } else {
        // Fallback for edge cases
        $currentStage = 'contest_open';
        $statusMessage = "Contest status - {$contestEntries->count()} entries";
        $contextualGuidance = 'Contest workflow status needs review.';
        $timeInStatus = $project->created_at;
    }
    
    $progressPercentage = $workflowStages[$currentStage]['progress'];
    
    // Contest metrics
    $totalFiles = $contestEntries->flatMap(fn($entry) => $entry->files)->count();
    $submittedEntries = $contestEntries->whereNotNull('submitted_at')->count();
    $draftEntries = $contestEntries->whereNull('submitted_at')->count();
@endphp

<div class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-amber-50/95 to-yellow-50/90 dark:from-amber-950/95 dark:to-yellow-950/90' }} backdrop-blur-sm border {{ $workflowColors['border'] ?? 'border-amber-200/50 dark:border-amber-700/50' }} rounded-2xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="bg-gradient-to-r {{ $workflowColors['accent_bg'] ?? 'from-amber-100/80 to-yellow-100/80 dark:from-amber-900/80 dark:to-yellow-900/80' }} border-b {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} p-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold {{ $workflowColors['text_primary'] ?? 'text-amber-900 dark:text-amber-100' }} flex items-center">
                    <i class="fas fa-trophy {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }} mr-3"></i>
                    Contest Workflow Status
                </h3>
                <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }} mt-1">{{ $statusMessage }}</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}">{{ $progressPercentage }}%</div>
                <div class="text-xs {{ $workflowColors['text_muted'] ?? 'text-amber-500 dark:text-amber-400' }}">Complete</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="flex justify-between text-xs {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }} mb-2">
                <span>Progress</span>
                <span>{{ $workflowStages[$currentStage]['label'] }}</span>
            </div>
            <div class="w-full bg-amber-200/50 dark:bg-amber-800/50 rounded-full h-2">
                <div class="bg-gradient-to-r {{ $workflowColors['icon'] ?? 'from-amber-500 to-yellow-600' }} h-2 rounded-full transition-all duration-500"
                     style="width: {{ $progressPercentage }}%"></div>
            </div>
        </div>

        <!-- Stage Indicators -->
        <div class="flex justify-between mt-4 text-xs">
            @foreach(['contest_open', 'contest_judging', 'contest_finalized', 'contest_results'] as $stage)
                @php
                    $isActive = $currentStage === $stage;
                    $isCompleted = $progressPercentage >= ($workflowStages[$stage]['progress'] ?? 0);
                    $stageIcon = $workflowStages[$stage]['icon'] ?? 'fa-circle';
                @endphp
                <div class="flex flex-col items-center {{ $isActive ? ($workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400') : ($workflowColors['text_muted'] ?? 'text-amber-400 dark:text-amber-500') }}">
                    <div class="w-8 h-8 rounded-full border-2 flex items-center justify-center mb-1 transition-all duration-300
                        {{ $isCompleted ? ($workflowColors['icon'] ?? 'bg-amber-600 border-amber-600') . ' text-white' : ($workflowColors['accent_border'] ?? 'border-amber-300 dark:border-amber-600') . ' ' . ($workflowColors['text_muted'] ?? 'text-amber-400 dark:text-amber-500') }}
                        {{ $isActive ? 'ring-2 ring-amber-300 dark:ring-amber-600 ring-offset-2 dark:ring-offset-gray-900' : '' }}">
                        <i class="fas {{ $stageIcon }} text-xs"></i>
                    </div>
                    <span class="text-center leading-tight max-w-16">{{ $workflowStages[$stage]['label'] ?? ucfirst($stage) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Status-Specific Content -->
    <div class="p-6">
        <!-- Warning Banner -->
        @if($showWarning)
            <div class="mb-6 p-4 {{ $semanticColors['danger']['bg'] ?? 'bg-red-50/80 dark:bg-red-950/80' }} border {{ $semanticColors['danger']['border'] ?? 'border-red-200/50 dark:border-red-700/50' }} rounded-xl">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle {{ $semanticColors['danger']['icon'] ?? 'text-red-500 dark:text-red-400' }} mr-3"></i>
                    <div>
                        <h4 class="font-medium {{ $semanticColors['danger']['text'] ?? 'text-red-800 dark:text-red-200' }}">Attention Required</h4>
                        <p class="text-sm {{ $semanticColors['danger']['icon'] ?? 'text-red-700 dark:text-red-300' }} mt-1">
                            @if($currentStage === 'contest_judging')
                                Contest judging has been in progress for over 2 weeks. Consider finalizing the results soon.
                            @elseif($currentStage === 'contest_submissions_closed')
                                Contest deadline has passed but no entries were received.
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <!-- Contextual Guidance -->
        <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4 mb-6">
            <h4 class="font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-800 dark:text-amber-200' }} mb-2 flex items-center">
                <i class="fas fa-lightbulb mr-2 {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}"></i>
                What's Next?
            </h4>
            <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">{{ $contextualGuidance }}</p>
        </div>

        <!-- Contest Metrics Grid -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4 text-center">
                <div class="text-2xl font-bold {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">{{ $contestEntries->count() }}</div>
                <div class="text-xs {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}">Total Entries</div>
            </div>
            
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4 text-center">
                <div class="text-2xl font-bold {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">{{ $submittedEntries }}</div>
                <div class="text-xs {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}">Submitted</div>
            </div>
            
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4 text-center">
                <div class="text-2xl font-bold {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">{{ $placedEntries }}</div>
                <div class="text-xs {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}">Placed</div>
            </div>
            
            <div class="bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4 text-center">
                <div class="text-2xl font-bold {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">{{ $totalFiles }}</div>
                <div class="text-xs {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}">Files Uploaded</div>
            </div>
        </div>

        <!-- Timeline Information -->
        @if($timeInStatus)
            <div class="mt-6 bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4">
                <h4 class="font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-800 dark:text-amber-200' }} mb-3 flex items-center">
                    <i class="fas fa-clock mr-2 {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}"></i>
                    Timeline
                </h4>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="{{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">Current Stage:</span>
                        <span class="font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-900 dark:text-amber-100' }}">{{ $workflowStages[$currentStage]['label'] }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="{{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">Time in Stage:</span>
                        <span class="font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-900 dark:text-amber-100' }}"><x-datetime :date="$timeInStatus" relative="true" /></span>
                    </div>
                    @if($project->submission_deadline)
                        <div class="flex items-center justify-between">
                            <span class="{{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">Submission Deadline:</span>
                            <span class="font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-900 dark:text-amber-100' }}"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y g:i A" /></span>
                        </div>
                    @endif
                    @if($project->judging_finalized_at)
                        <div class="flex items-center justify-between">
                            <span class="{{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">Judging Finalized:</span>
                            <span class="font-medium {{ $semanticColors['success']['text'] ?? 'text-green-700 dark:text-green-300' }}"><x-datetime :date="$project->judging_finalized_at" format="M d, Y g:i A" /></span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
    
    <!-- Contest Management Actions -->
    @if(($currentStage === 'contest_finalized' || $currentStage === 'contest_results') && ($project->user_id === auth()->id() || auth()->user()?->hasRole('admin')))
        <div class="mt-6 bg-white/60 dark:bg-gray-800/60 border {{ $workflowColors['accent_border'] ?? 'border-amber-200/30 dark:border-amber-700/30' }} rounded-xl p-4">
            <div class="flex items-center justify-between mb-3">
                <h4 class="font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-800 dark:text-amber-200' }} flex items-center">
                    <i class="fas fa-bullhorn mr-2 {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}"></i>
                    Contest Management
                </h4>
                <x-contest.payment-status-badge :project="$project" compact="true" />
            </div>
            
            @php
                $paymentStatus = $project->getContestPaymentStatus();
                $hasCashPrizes = $paymentStatus['has_cash_prizes'];
                $prizesPaid = $paymentStatus['payment_status'] === 'all_paid';
            @endphp
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <!-- Select Winners Button -->
                @if($contestEntries->count() > 0)
                    <a href="{{ route('projects.contest.judging', $project) }}" 
                       class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-yellow-600 to-amber-600 hover:from-yellow-700 hover:to-amber-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                        <i class="fas fa-crown mr-2"></i>
                        Select Winners
                    </a>
                @endif
                
                <!-- Contest Prize Payment Button -->
                @if($hasCashPrizes)
                    @if($paymentStatus['payment_status'] === 'all_paid')
                        <a href="{{ route('contest.prizes.receipt', $project) }}" 
                           class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                            <i class="fas fa-receipt mr-2"></i>
                            View Receipt
                        </a>
                    @elseif($paymentStatus['payment_status'] === 'partially_paid')
                        <a href="{{ route('contest.prizes.overview', $project) }}" 
                           class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                            <i class="fas fa-clock mr-2"></i>
                            Continue Payments
                        </a>
                    @else
                        <a href="{{ route('contest.prizes.overview', $project) }}" 
                           class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                            <i class="fas fa-dollar-sign mr-2"></i>
                            Pay Prizes
                        </a>
                    @endif
                @endif
                
                <!-- Formal Announcement Button -->
                <button onclick="announceResults({{ $project->id }})" 
                        class="inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                    <i class="fas fa-bullhorn mr-2"></i>
                    Announce Results
                </button>
            </div>
            
            @if($notSelectedCount > 0)
                <div class="mt-3 p-3 {{ $workflowColors['accent_bg'] ?? 'bg-amber-50/80 dark:bg-amber-950/80' }} border {{ $workflowColors['accent_border'] ?? 'border-amber-200 dark:border-amber-700' }} rounded-lg">
                    <p class="text-xs {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ $notSelectedCount }} {{ Str::plural('entry', $notSelectedCount) }} marked as not selected. 
                        You can still select winners or formally announce the results as-is.
                    </p>
                </div>
            @endif
            
            @if($hasCashPrizes)
                <div class="mt-3 p-3 bg-purple-50/80 dark:bg-purple-950/80 border border-purple-200 dark:border-purple-700 rounded-lg">
                    <p class="text-xs text-purple-700 dark:text-purple-300">
                        <i class="fas fa-info-circle mr-1"></i>
                        This contest has cash prizes totaling ${{ number_format($paymentStatus['total_prize_amount'], 2) }}. 
                        @if($prizesPaid)
                            All prizes have been paid.
                        @else
                            Pay prizes after selecting winners.
                        @endif
                    </p>
                </div>
            @endif
        </div>
    @endif

    <!-- Announcement Success -->
    @if($currentStage === 'contest_results')
        <div class="mt-6 bg-gradient-to-r {{ $semanticColors['success']['bg'] ?? 'from-green-50/80 to-emerald-50/80 dark:from-green-950/80 dark:to-emerald-950/80' }} border {{ $semanticColors['success']['border'] ?? 'border-green-200/50 dark:border-green-700/50' }} rounded-xl p-4">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-3">
                    <i class="fas fa-trophy text-white"></i>
                </div>
                <div>
                    <h4 class="font-bold {{ $semanticColors['success']['text'] ?? 'text-green-800 dark:text-green-200' }}">Contest Successfully Completed</h4>
                    <p class="text-sm {{ $semanticColors['success']['icon'] ?? 'text-green-700 dark:text-green-300' }}">Results have been announced and all participants notified.</p>
                </div>
            </div>
            
            <div class="mt-3 flex gap-2">
                <a href="{{ route('projects.contest.results', $project) }}" 
                   class="inline-flex items-center px-3 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium text-xs transition-all duration-200">
                    <i class="fas fa-trophy mr-1"></i>
                    View Results
                </a>
                
                @if($project->contestResult)
                    <a href="{{ route('projects.contest.analytics', $project) }}" 
                       class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium text-xs transition-all duration-200">
                        <i class="fas fa-chart-bar mr-1"></i>
                        Analytics
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>

<script>
function announceResults(projectId) {
    if (confirm('Are you sure you want to formally announce the contest results? This will set the contest to 100% completion and notify all participants that results are final.')) {
        fetch(`/projects/${projectId}/contest/announce-results`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Failed to announce results: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while announcing results.');
        });
    }
}
</script> 