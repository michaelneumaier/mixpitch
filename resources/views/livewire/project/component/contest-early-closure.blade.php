@props(['workflowColors' => [], 'semanticColors' => []])

<flux:card class="bg-gradient-to-br {{ $workflowColors['bg'] ?? 'from-orange-50/30 to-amber-50/30 dark:from-orange-950/30 dark:to-amber-950/30' }} border {{ $workflowColors['border'] ?? 'border-orange-200/50 dark:border-orange-800/50' }}">
    <div class="flex items-center gap-3 mb-6">
        <flux:icon name="clock" variant="solid" class="{{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }} h-8 w-8" />
        <div>
            <flux:heading size="lg" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">Contest Timeline Management</flux:heading>
            <flux:subheading class="{{ $workflowColors['text_muted'] ?? 'text-orange-600 dark:text-orange-400' }}">Manage contest submission deadlines and early closure</flux:subheading>
        </div>
    </div>

        <!-- Current Status -->
        <div class="mb-6">
            @if($project->wasClosedEarly())
                <!-- Early Closure Status -->
                <flux:callout color="amber" icon="exclamation-triangle">
                    <flux:callout.heading>Contest Closed Early</flux:callout.heading>
                    <flux:callout.text>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span>Closed on:</span>
                                <span class="font-medium">{{ $project->submissions_closed_early_at->format('M d, Y \a\t g:i A') }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Closed by:</span>
                                <span class="font-medium">{{ $project->submissionsClosedEarlyBy->name }}</span>
                            </div>
                            @if($project->early_closure_reason)
                                <div class="mt-3 p-3 {{ $workflowColors['accent_bg'] ?? 'bg-amber-100/50 dark:bg-amber-900/50' }} rounded-lg">
                                    <span class="font-medium">Reason:</span>
                                    <p class="mt-1">{{ $project->early_closure_reason }}</p>
                                </div>
                            @endif
                            @if($project->submission_deadline)
                                <div class="flex items-center justify-between">
                                    <span>Original deadline:</span>
                                    <span class="font-medium"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y \a\t g:i A" /></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Time saved:</span>
                                    <span class="font-medium {{ $semanticColors['success']['text'] ?? 'text-green-600 dark:text-green-400' }}">
                                        <x-datetime :date="$project->submissions_closed_early_at" relative="true" /> early
                                    </span>
                                </div>
                            @endif
                        </div>
                    </flux:callout.text>
                </flux:callout>

                <!-- Reopen Option -->
                @if($project->canCloseEarly() || (!$project->isJudgingFinalized() && $project->submission_deadline && !$project->submission_deadline->isPast()))
                    <div class="mt-4">
                        <form action="{{ route('projects.contest.reopen-submissions', $project) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to reopen contest submissions? This will allow new entries until the original deadline.')">
                            @csrf
                            <flux:button type="submit" variant="primary" icon="lock-open">
                                Reopen Submissions
                            </flux:button>
                        </form>
                    </div>
                @endif

            @elseif($project->isSubmissionPeriodClosed())
                <!-- Natural Deadline Passed -->
                <flux:callout color="zinc" icon="calendar">
                    <flux:callout.heading>Submission Deadline Passed</flux:callout.heading>
                    <flux:callout.text>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span>Deadline was:</span>
                                <span class="font-medium"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y \a\t g:i A" /></span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span>Closed:</span>
                                <span class="font-medium">{{ $project->submission_deadline->diffForHumans() }}</span>
                            </div>
                        </div>
                    </flux:callout.text>
                </flux:callout>

            @elseif($project->canCloseEarly())
                <!-- Can Close Early -->
                <flux:callout color="green" icon="check-circle">
                    <flux:callout.heading>Contest Active</flux:callout.heading>
                    <flux:callout.text>
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <span>Entries received:</span>
                                <span class="font-medium">{{ $project->getContestEntries()->count() }}</span>
                            </div>
                            @if($project->submission_deadline)
                                <div class="flex items-center justify-between">
                                    <span>Deadline:</span>
                                    <span class="font-medium"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y \a\t g:i A" /></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Time remaining:</span>
                                    <span class="font-medium">{{ $project->submission_deadline->diffForHumans() }}</span>
                                </div>
                            @endif
                        </div>
                    </flux:callout.text>
                </flux:callout>

                <!-- Early Closure Form -->
                <div class="mt-6 p-4 {{ $workflowColors['accent_bg'] ?? 'bg-orange-50 dark:bg-orange-950' }} border {{ $workflowColors['accent_border'] ?? 'border-orange-200 dark:border-orange-700' }} rounded-xl">
                    <div class="flex items-center gap-3 mb-3">
                        <flux:icon name="forward" class="{{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }} h-5 w-5" />
                        <flux:heading size="base" class="{{ $workflowColors['text_primary'] ?? 'text-orange-800 dark:text-orange-200' }}">Close Contest Early</flux:heading>
                    </div>
                    <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-700 dark:text-orange-300' }} mb-4">
                        You can close contest submissions early to begin judging immediately. This action will notify all participants and prevent new submissions.
                    </flux:text>
                    
                    <form action="{{ route('projects.contest.close-early', $project) }}" method="POST" 
                          onsubmit="return confirm('Are you sure you want to close contest submissions early? This action cannot be undone and will notify all participants.')">
                        @csrf
                        
                        <flux:field class="mb-4">
                            <flux:label>Reason for early closure (optional)</flux:label>
                            <flux:textarea 
                                name="reason" 
                                rows="3" 
                                placeholder="e.g., Received enough high-quality entries, want to announce results sooner..."
                            />
                        </flux:field>
                        
                        <div class="space-y-3">
                            <div class="text-xs {{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }} text-center">
                                <flux:icon name="information-circle" class="mr-1 h-3 w-3" />
                                All participants will be notified immediately
                            </div>
                            <flux:button type="submit" variant="primary" class="w-full" icon="stop-circle">
                                Close Submissions Early
                            </flux:button>
                        </div>
                    </form>
                </div>

            @else
                <!-- Cannot Close Early -->
                <flux:callout color="zinc" icon="information-circle">
                    <flux:callout.heading>Early Closure Not Available</flux:callout.heading>
                    <flux:callout.text>
                        @if($project->getContestEntries()->isEmpty())
                            Contest must have at least one entry before it can be closed early.
                        @elseif($project->isJudgingFinalized())
                            Contest judging has already been finalized.
                        @else
                            Contest cannot be closed early at this time.
                        @endif
                    </flux:callout.text>
                </flux:callout>
            @endif
        </div>

        <!-- Contest Statistics -->
        <flux:separator class="my-6" />
        
        <div class="flex items-center gap-3 mb-4">
            <flux:icon name="chart-bar" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }} h-5 w-5" />
            <flux:heading size="base" class="{{ $workflowColors['text_primary'] ?? 'text-orange-800 dark:text-orange-200' }}">Contest Statistics</flux:heading>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <flux:heading size="xl" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">{{ $project->getContestEntries()->count() }}</flux:heading>
                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">Total Entries</flux:text>
            </div>
            
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <flux:heading size="xl" class="{{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                    {{ $project->created_at->diffInDays($project->getEffectiveSubmissionDeadline() ?? now()) }}
                </flux:heading>
                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">
                    {{ $project->wasClosedEarly() ? 'Actual Duration (Days)' : 'Contest Duration (Days)' }}
                </flux:text>
            </div>
            
            <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-orange-900 dark:text-orange-100' }}">
                    @if($project->isJudgingFinalized())
                        <flux:icon name="check" class="{{ $semanticColors['success']['icon'] ?? 'text-green-600 dark:text-green-400' }} h-8 w-8" />
                    @elseif($project->isSubmissionPeriodClosed())
                        <flux:icon name="scale" class="{{ $workflowColors['icon'] ?? 'text-orange-600 dark:text-orange-400' }} h-8 w-8" />
                    @else
                        <flux:icon name="clock" class="text-blue-600 dark:text-blue-400 h-8 w-8" />
                    @endif
                </div>
                <flux:text size="sm" class="{{ $workflowColors['text_secondary'] ?? 'text-orange-600 dark:text-orange-400' }}">
                    @if($project->isJudgingFinalized())
                        Judging Complete
                    @elseif($project->isSubmissionPeriodClosed())
                        Ready for Judging
                    @else
                        Accepting Entries
                    @endif
                </flux:text>
            </div>
        </div>
</flux:card> 