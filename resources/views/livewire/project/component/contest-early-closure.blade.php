@props(['workflowColors' => [], 'semanticColors' => []])

<div class="bg-white dark:bg-gray-900 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-gradient-to-br {{ $workflowColors['icon'] ?? 'from-amber-500 to-orange-600' }} rounded-xl flex items-center justify-center mr-4">
                    <i class="fas fa-clock text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">Contest Timeline Management</h3>
                    <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">Manage contest submission deadlines and early closure</p>
                </div>
            </div>
        </div>

        <!-- Current Status -->
        <div class="mb-6">
            @if($project->wasClosedEarly())
                <!-- Early Closure Status -->
                <div class="bg-gradient-to-r {{ $workflowColors['accent_bg'] ?? 'from-amber-50 to-orange-50 dark:from-amber-950 dark:to-orange-950' }} border {{ $workflowColors['accent_border'] ?? 'border-amber-200 dark:border-amber-700' }} rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 {{ $workflowColors['accent_bg'] ?? 'bg-amber-100 dark:bg-amber-900' }} rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-exclamation-triangle {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold {{ $workflowColors['text_primary'] ?? 'text-amber-800 dark:text-amber-200' }} mb-2">Contest Closed Early</h4>
                            <div class="space-y-2 text-sm {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }}">
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
                        </div>
                    </div>
                </div>

                <!-- Reopen Option -->
                @if($project->canCloseEarly() || (!$project->isJudgingFinalized() && $project->submission_deadline && !$project->submission_deadline->isPast()))
                    <div class="mt-4">
                        <form action="{{ route('projects.contest.reopen-submissions', $project) }}" method="POST" 
                              onsubmit="return confirm('Are you sure you want to reopen contest submissions? This will allow new entries until the original deadline.')">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-unlock mr-2"></i>
                                Reopen Submissions
                            </button>
                        </form>
                    </div>
                @endif

            @elseif($project->isSubmissionPeriodClosed())
                <!-- Natural Deadline Passed -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-calendar-times text-gray-600 dark:text-gray-400"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Submission Deadline Passed</h4>
                            <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <div class="flex items-center justify-between">
                                    <span>Deadline was:</span>
                                    <span class="font-medium"><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y \a\t g:i A" /></span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Closed:</span>
                                    <span class="font-medium">{{ $project->submission_deadline->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif($project->canCloseEarly())
                <!-- Can Close Early -->
                <div class="bg-gradient-to-r {{ $semanticColors['success']['bg'] ?? 'from-green-50 to-emerald-50 dark:from-green-950 dark:to-emerald-950' }} border {{ $semanticColors['success']['border'] ?? 'border-green-200 dark:border-green-800' }} rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 {{ $semanticColors['success']['bg'] ?? 'bg-green-100 dark:bg-green-900' }} rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-check-circle {{ $semanticColors['success']['icon'] ?? 'text-green-600 dark:text-green-400' }}"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold {{ $semanticColors['success']['text'] ?? 'text-green-800 dark:text-green-200' }} mb-2">Contest Active</h4>
                            <div class="space-y-2 text-sm {{ $semanticColors['success']['text'] ?? 'text-green-700 dark:text-green-300' }}">
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
                        </div>
                    </div>
                </div>

                <!-- Early Closure Form -->
                <div class="mt-6 p-4 {{ $workflowColors['accent_bg'] ?? 'bg-amber-50 dark:bg-amber-950' }} border {{ $workflowColors['accent_border'] ?? 'border-amber-200 dark:border-amber-700' }} rounded-xl">
                    <h4 class="font-semibold {{ $workflowColors['text_primary'] ?? 'text-amber-800 dark:text-amber-200' }} mb-3 flex items-center">
                        <i class="fas fa-fast-forward mr-2"></i>
                        Close Contest Early
                    </h4>
                    <p class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-amber-700 dark:text-amber-300' }} mb-4">
                        You can close contest submissions early to begin judging immediately. This action will notify all participants and prevent new submissions.
                    </p>
                    
                    <form action="{{ route('projects.contest.close-early', $project) }}" method="POST" 
                          onsubmit="return confirm('Are you sure you want to close contest submissions early? This action cannot be undone and will notify all participants.')">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="reason" class="block text-sm font-medium {{ $workflowColors['text_primary'] ?? 'text-amber-800 dark:text-amber-200' }} mb-2">
                                Reason for early closure (optional)
                            </label>
                            <textarea name="reason" id="reason" rows="3" 
                                      class="w-full px-3 py-2 border {{ $workflowColors['accent_border'] ?? 'border-amber-300 dark:border-amber-600' }} bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 text-sm"
                                      placeholder="e.g., Received enough high-quality entries, want to announce results sooner..."></textarea>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="text-xs {{ $workflowColors['text_secondary'] ?? 'text-amber-600 dark:text-amber-400' }} text-center">
                                <i class="fas fa-info-circle mr-1"></i>
                                All participants will be notified immediately
                            </div>
                            <button type="submit" 
                                    class="w-full inline-flex items-center justify-center px-4 py-2 bg-amber-600 hover:bg-amber-700 dark:bg-amber-500 dark:hover:bg-amber-600 text-white font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-stop-circle mr-2"></i>
                                Close Submissions Early
                            </button>
                        </div>
                    </form>
                </div>

            @else
                <!-- Cannot Close Early -->
                <div class="bg-gradient-to-r from-gray-50 to-slate-50 dark:from-gray-800 dark:to-slate-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
                    <div class="flex items-start">
                        <div class="w-8 h-8 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mr-3 flex-shrink-0">
                            <i class="fas fa-info-circle text-gray-600 dark:text-gray-400"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-800 dark:text-gray-200 mb-2">Early Closure Not Available</h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                @if($project->getContestEntries()->isEmpty())
                                    Contest must have at least one entry before it can be closed early.
                                @elseif($project->isJudgingFinalized())
                                    Contest judging has already been finalized.
                                @else
                                    Contest cannot be closed early at this time.
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Contest Statistics -->
        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
            <h4 class="font-semibold {{ $workflowColors['text_primary'] ?? 'text-gray-800 dark:text-gray-200' }} mb-4 flex items-center">
                <i class="fas fa-chart-bar mr-2 {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}"></i>
                Contest Statistics
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">{{ $project->getContestEntries()->count() }}</div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">Total Entries</div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                        {{ $project->created_at->diffInDays($project->getEffectiveSubmissionDeadline() ?? now()) }}
                    </div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">
                        {{ $project->wasClosedEarly() ? 'Actual Duration (Days)' : 'Contest Duration (Days)' }}
                    </div>
                </div>
                
                <div class="text-center p-4 bg-gray-50 dark:bg-gray-800 rounded-xl">
                    <div class="text-2xl font-bold {{ $workflowColors['text_primary'] ?? 'text-gray-900 dark:text-gray-100' }}">
                        @if($project->isJudgingFinalized())
                            <i class="fas fa-check {{ $semanticColors['success']['icon'] ?? 'text-green-600 dark:text-green-400' }}"></i>
                        @elseif($project->isSubmissionPeriodClosed())
                            <i class="fas fa-gavel {{ $workflowColors['icon'] ?? 'text-amber-600 dark:text-amber-400' }}"></i>
                        @else
                            <i class="fas fa-clock text-blue-600 dark:text-blue-400"></i>
                        @endif
                    </div>
                    <div class="text-sm {{ $workflowColors['text_secondary'] ?? 'text-gray-600 dark:text-gray-400' }}">
                        @if($project->isJudgingFinalized())
                            Judging Complete
                        @elseif($project->isSubmissionPeriodClosed())
                            Ready for Judging
                        @else
                            Accepting Entries
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 