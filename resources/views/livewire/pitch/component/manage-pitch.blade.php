@php 
    use Illuminate\Support\Str; 
@endphp

<!-- Remove container constraint and modernize with glass morphism -->
<div class="w-full">
    {{-- Load necessary Font Awesome icons --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">



    {{-- Contest View --}}
    @if($project->isContest())
        <div class="bg-gradient-to-br from-yellow-50/90 to-amber-50/90 backdrop-blur-sm border border-yellow-200/50 rounded-2xl shadow-lg overflow-hidden mb-6">
            <div class="p-6 bg-gradient-to-r from-yellow-100/80 to-amber-100/80 backdrop-blur-sm border-b border-yellow-200/50">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-yellow-500 to-amber-600 rounded-xl mr-3">
                        <i class="fas fa-medal text-white"></i>
                    </div>
                    <h3 class="text-lg font-bold text-yellow-800">Contest Entry Status</h3>
                </div>
            </div>
            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                        <dt class="text-sm font-medium text-yellow-700 mb-2">Current Status</dt>
                        <dd class="flex items-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $pitch->status === \App\Models\Pitch::STATUS_CONTEST_WINNER ? 'bg-green-100 text-green-800 border border-green-200' : ($pitch->status === \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : ($pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY ? 'bg-blue-100 text-blue-800 border border-blue-200' : 'bg-gray-100 text-gray-800 border border-gray-200')) }}">
                                {{ $pitch->readable_status }}
                            </span>
                        </dd>
                    </div>
                    
                    @if($pitch->rank && $pitch->rank > 0)
                        <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                            <dt class="text-sm font-medium text-yellow-700 mb-2">Rank</dt>
                            <dd class="text-lg font-bold text-yellow-900">{{ $pitch->rank }}</dd>
                        </div>
                    @endif
                </div>
                
                @if($pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                    <!-- Contest Entry Instructions -->
                    <div class="bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4">
                        <div class="flex items-start">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3 flex-shrink-0">
                                <i class="fas fa-info-circle text-white text-sm"></i>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-blue-800 mb-2">Contest Entry Instructions</h4>
                                <ul class="text-sm text-blue-700 space-y-1">
                                    <li class="flex items-start">
                                        <i class="fas fa-check mr-2 text-blue-600 mt-0.5 text-xs"></i>
                                        You have immediate access to download project files and upload your contest entry
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check mr-2 text-blue-600 mt-0.5 text-xs"></i>
                                        Upload your best work - you can update files anytime before the deadline
                                    </li>
                                    @if($project->submission_deadline)
                                        <li class="flex items-start">
                                            <i class="fas fa-clock mr-2 text-amber-600 mt-0.5 text-xs"></i>
                                            <span>Contest deadline: <strong><x-datetime :date="$project->submission_deadline" :user="$project->user" :convertToViewer="true" format="M d, Y \a\t H:i T" /></strong></span>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Project Files for Contest Entry -->
                    @if($project->files->count() > 0)
                        <div class="bg-gradient-to-br from-green-50/80 to-emerald-50/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center">
                                    <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg mr-3">
                                        <i class="fas fa-download text-white text-sm"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-green-800">Project Files</h4>
                                        <p class="text-xs text-green-600">Download these files to create your contest entry</p>
                                    </div>
                                </div>
                                <a href="{{ route('projects.download', $project) }}"
                                   class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg text-sm">
                                    <i class="fas fa-download mr-2"></i>Download All
                                </a>
                            </div>
                            <div class="space-y-2">
                                @foreach($project->files as $file)
                                    <div class="flex items-center justify-between p-2 bg-green-50/50 rounded-lg border border-green-200/30">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-alt mr-3 text-green-600"></i>
                                            <span class="text-green-900 font-medium text-sm">{{ $file->file_name }}</span>
                                        </div>
                                        <span class="text-xs text-green-600">{{ \App\Models\Pitch::formatBytes($file->size) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <!-- Final Contest Results -->
                    <div class="bg-white/60 backdrop-blur-sm border border-yellow-200/30 rounded-xl p-4">
                        <dt class="text-sm font-medium text-yellow-700 mb-3">Final Entry Files</dt>
                        <dd>
                            @if($pitch->files->count() > 0)
                                <div class="space-y-2">
                                    @foreach($pitch->files as $file)
                                        <div class="flex items-center justify-between p-3 bg-yellow-50/50 rounded-lg border border-yellow-200/30">
                                            <div class="flex items-center">
                                                <i class="fas fa-file-alt mr-3 text-yellow-600"></i>
                                                <span class="text-yellow-900 font-medium">{{ $file->file_name }}</span>
                                            </div>
                                            <button wire:click="downloadFile({{ $file->id }})"
                                                class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-600 rounded-lg transition-all duration-200 hover:scale-105">
                                                <i class="fas fa-download text-sm"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-folder-open text-yellow-400 text-2xl mb-2"></i>
                                    <p class="text-yellow-700">No files were submitted with this entry.</p>
                                </div>
                            @endif
                        </dd>
                    </div>
                @endif
            </div>
        </div>

    {{-- Standard/Other Project Type View --}}
    @elseif($project->isDirectHire())
        {{-- Direct Hire Specific Header --}}
        <div class="bg-gradient-to-br from-purple-50/90 to-indigo-50/90 backdrop-blur-sm border border-purple-200/50 rounded-2xl p-6 mb-6 shadow-lg">
            <div class="flex items-center mb-4">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-3">
                    <i class="fas fa-user-check text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-purple-800">Direct Hire Project</h2>
                    <p class="text-sm text-purple-600">Status: {{ $pitch->readable_status }}</p>
                </div>
            </div>
        </div>

    @elseif($project->isClientManagement())
        {{-- Client Management Specific Header --}}
        <div class="bg-gradient-to-br from-indigo-50/90 to-purple-50/90 backdrop-blur-sm border border-indigo-200/50 rounded-2xl p-6 mb-6 shadow-lg">
            <div class="flex items-center mb-4">
                <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-3">
                    <i class="fas fa-briefcase text-white"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-indigo-800">Client Management Project</h2>
                    <div class="mt-2 space-y-1">
                        @if($project->pitches->first())
                            <p class="text-sm text-indigo-600">Status: {{ $project->pitches->first()->readable_status }}</p>
                        @else
                            <p class="text-sm text-indigo-600">Status: No pitch initiated</p>
                        @endif
                        <p class="text-sm text-indigo-600">Client: {{ $project->client_name ?: 'N/A' }} ({{ $project->client_email }})</p>
                        
                        {{-- Payment Details for Producer --}}
                        @if($pitch->payment_amount > 0)
                            <p class="text-sm text-indigo-600">
                                Payment Amount: ${{ number_format($pitch->payment_amount, 2) }} {{ $pitch->currency ?? 'USD' }}
                                <span class="ml-2 font-medium {{
                                    $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID ? 'text-green-700' :
                                    ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ? 'text-yellow-700' :
                                    ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING ? 'text-blue-700' :
                                    ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED ? 'text-red-700' : 'text-gray-700')))
                                }}">
                                    ({{ Str::title(str_replace('_', ' ', $pitch->payment_status)) }})
                                </span>
                            </p>
                        @else
                            <p class="text-sm text-indigo-600">Payment: Not applicable (Amount is $0)</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Payout Status for Client Management (Producer View) --}}
        @if(auth()->check() && auth()->id() === $pitch->user_id)
            <x-pitch.payout-status :pitch="$pitch" />
        @endif

        {{-- Client Management Pitch Details --}}
        <div class="bg-gradient-to-br from-gray-50/90 to-slate-50/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-6 mb-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-lg font-bold text-gray-800">Pitch Status: {{ $pitch->readable_status }}</h3>
                </div>
                <a href="{{ route('projects.pitches.show', ['project' => $project, 'pitch' => $pitch]) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-cog mr-2"></i>Manage Pitch Details & Files
                </a>
            </div>

            {{-- Display Client Comments/Events --}}
            <div class="bg-white/60 backdrop-blur-sm border border-gray-200/30 rounded-xl p-4">
                <h4 class="font-bold text-gray-700 mb-3 flex items-center">
                    <i class="fas fa-clock mr-2 text-blue-500"></i>Recent Client Activity
                </h4>
                <div class="space-y-2">
                    @forelse($pitch->events->whereIn('event_type', ['client_comment', 'client_revisions_requested', 'client_approved'])->sortByDesc('created_at')->take(5) as $event)
                        <div class="flex items-start p-3 bg-gray-50/50 rounded-lg border border-gray-200/30">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 rounded-lg mr-3 flex-shrink-0">
                                @if($event->event_type === 'client_comment')
                                    <i class="fas fa-comment text-blue-600 text-sm"></i>
                                @elseif($event->event_type === 'client_revisions_requested')
                                    <i class="fas fa-edit text-amber-600 text-sm"></i>
                                @elseif($event->event_type === 'client_approved')
                                    <i class="fas fa-check text-green-600 text-sm"></i>
                                @endif
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-800">
                                    @if($event->event_type === 'client_comment')
                                        Client Comment: "{{ Str::limit($event->comment, 50) }}"
                                    @elseif($event->event_type === 'client_revisions_requested')
                                        Client Requested Revisions: "{{ Str::limit($event->comment, 50) }}"
                                    @elseif($event->event_type === 'client_approved')
                                        Client Approved Submission
                                    @endif
                                </div>
                                <div class="text-xs text-gray-500 mt-1">{{ $event->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="fas fa-inbox text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500">No recent client activity.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

    @else {{-- This now applies only if NOT Contest, NOT Direct Hire, and NOT Client Management --}}

        <!-- Main Pitch Management Card -->
        <div class="bg-gradient-to-br from-white/95 to-blue-50/90 backdrop-blur-md border border-white/30 rounded-2xl shadow-xl overflow-hidden mb-6">
            <div class="p-6">
                <div class="flex items-center mb-6">
                    <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mr-4">
                        <i class="fas fa-tasks text-white text-lg"></i>
                    </div>
                    <h3 class="text-xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        Pitch Management
                    </h3>
                </div>

                <!-- Success Message -->
                <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 5000)">
                    @if ($message = session('message'))
                        <div x-show="show" class="mb-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 rounded-xl text-green-800" x-transition>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-green-600"></i>
                                {{ $message }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Status Messages -->
                <div class="mb-6">
                    <div class="rounded-xl overflow-hidden shadow-sm">
                        @if($pitch->is_inactive || $pitch->status == 'closed')
                            <div class="p-4 bg-gradient-to-r from-gray-50 to-slate-50 border-l-4 border-gray-500 rounded-xl">
                                <div class="flex items-center">
                                    <i class="fas fa-lock mr-3 text-gray-600"></i>
                                    <span class="text-gray-800 font-medium">
                                        {{ $pitch->is_inactive ? 'This pitch is now inactive' : 'This pitch has been closed' }}
                                    </span>
                                </div>
                            </div>
                        @else
                        @endif
                    </div>
                </div>


                <!-- Denied Pitch Alert Section -->
                @if($pitch->status == 'denied')
                <div class="mb-6 bg-gradient-to-br from-red-50/90 to-rose-50/90 backdrop-blur-sm border border-red-200/50 rounded-2xl p-6 shadow-lg">
                    <div class="flex items-start mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-red-500 to-rose-600 rounded-xl mr-4 flex-shrink-0">
                            <i class="fas fa-times-circle text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-red-800 mb-2">Your Pitch Has Been Denied</h4>
                            <p class="text-sm text-red-700 mb-4">
                                The project owner has reviewed your pitch and has decided not to proceed with it at this time. You
                                can view their feedback below, make changes to your files, and resubmit if appropriate.
                            </p>
                        </div>
                    </div>

                    @if($snapshots->isNotEmpty())
                    <div class="bg-white/60 backdrop-blur-sm border border-red-200/30 rounded-xl p-4 mb-4">
                        <h5 class="font-bold text-red-800 mb-3 flex items-center">
                            <i class="fas fa-comment-alt mr-2 text-red-600"></i>Feedback from Project Owner
                        </h5>
                        <div class="text-sm text-red-900 bg-red-50/50 rounded-lg p-3 border border-red-200/30">
                            @if (!empty($statusFeedbackMessage))
                                {!! nl2br(e($statusFeedbackMessage)) !!}
                            @else
                                <span class="italic text-red-600">No specific feedback was provided. Please review your pitch and consider making improvements before resubmitting.</span>
                            @endif
                        </div>
                        <div class="mt-4">
                            @if($pitch->currentSnapshot)
                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitch->currentSnapshot->id]) }}"
                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                <i class="fas fa-eye mr-2"></i>View Denied Snapshot
                            </a>
                            @else
                            <span class="text-red-500 text-sm italic">No snapshot available</span>
                            @endif
                        </div>
                    </div>
                    <div class="bg-red-100/50 backdrop-blur-sm border border-red-200/30 rounded-xl p-4">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle mr-3 text-red-600 mt-0.5"></i>
                            <p class="text-sm text-red-800">
                                To resubmit your pitch, make any necessary changes to your files above, then click the "Resubmit Pitch" button at the bottom of the page.
                            </p>
                        </div>
                    </div>
                    @endif
                </div>
                @endif

                <!-- Revisions Requested Alert Section -->
                @if($pitch->status == 'revisions_requested')
                <div class="mb-6 bg-gradient-to-br from-amber-50/90 to-yellow-50/90 backdrop-blur-sm border border-amber-200/50 rounded-2xl p-6 shadow-lg">
                    <div class="flex items-start mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-amber-500 to-yellow-600 rounded-xl mr-4 flex-shrink-0">
                            <i class="fas fa-pencil-alt text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-amber-800 mb-2">Revisions Have Been Requested</h4>
                            <p class="text-sm text-amber-700 mb-4">
                                The project owner has reviewed your pitch and requested some changes. Please review the latest
                                snapshot and their feedback, then make the necessary revisions and submit your updated pitch for review.
                            </p>
                        </div>
                    </div>

                    @if($snapshots->isNotEmpty())
                    <div class="bg-white/60 backdrop-blur-sm border border-amber-200/30 rounded-xl p-4 mb-4">
                        <h5 class="font-bold text-amber-800 mb-3 flex items-center">
                            <i class="fas fa-comment-alt mr-2 text-amber-600"></i>Feedback from Project Owner
                        </h5>
                        <div class="text-sm text-amber-900 bg-amber-50/50 rounded-lg p-3 border border-amber-200/30">
                            @if (!empty($statusFeedbackMessage))
                                {!! nl2br(e($statusFeedbackMessage)) !!}
                            @else
                                <span class="italic text-amber-600">No specific feedback was provided. Please review the latest snapshot for details.</span>
                            @endif
                        </div>
                        <div class="mt-4">
                            @if($latestSnapshot)
                                <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $latestSnapshot->id]) }}"
                                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-eye mr-2"></i>View Snapshot Details
                                </a>
                            @else
                                <span class="text-amber-500 text-sm italic">No snapshot available</span>
                            @endif
                        </div>
                    </div>
                    @endif
                    
                    {{-- Revision Response Form --}}
                    @if($pitch->status === 'revisions_requested')
                        <div class="bg-white/60 backdrop-blur-sm border border-amber-200/30 rounded-xl p-4">
                            <h5 class="font-bold text-amber-800 mb-4 flex items-center">
                                <i class="fas fa-reply mr-2 text-amber-600"></i>Respond to Feedback & Resubmit
                            </h5>
                            <div class="mb-4">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-sm font-medium text-amber-800">Your Response</label>
                                    <span class="text-xs text-amber-600 flex items-center">
                                        <i class="fas fa-info-circle mr-1"></i>This message will be visible to the project owner
                                    </span>
                                </div>
                                <div class="bg-amber-50/50 border border-amber-200/30 p-3 rounded-t-xl">
                                    <p class="text-amber-800 text-xs flex items-center">
                                        <i class="fas fa-comment-dots mr-2"></i>Your response will appear in the feedback conversation history.
                                    </p>
                                </div>
                                <textarea wire:model.lazy="responseToFeedback" rows="5"
                                    class="w-full p-3 border border-amber-200/50 rounded-b-xl bg-white/80 backdrop-blur-sm text-sm text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-amber-500/50 focus:border-amber-500/50 transition-all duration-200"
                                    placeholder="Explain what changes you've made in response to the feedback..."></textarea>
                            </div>

                            <div class="bg-amber-100/50 backdrop-blur-sm border border-amber-200/30 rounded-xl p-4 mb-4">
                                <h6 class="text-sm font-bold text-amber-800 mb-2 flex items-center">
                                    <i class="fas fa-checklist mr-2"></i>Before Resubmitting:
                                </h6>
                                <ul class="space-y-1 text-amber-700 text-sm">
                                    <li class="flex items-start">
                                        <i class="fas fa-check mr-2 text-amber-600 mt-0.5 text-xs"></i>
                                        Ensure you have uploaded any necessary new files above.
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check mr-2 text-amber-600 mt-0.5 text-xs"></i>
                                        Explain the changes you made in the response field.
                                    </li>
                                </ul>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-3">
                                <button wire:click="submitForReview" wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-600 to-yellow-600 hover:from-amber-700 hover:to-yellow-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg disabled:opacity-50">
                                    <span wire:loading wire:target="submitForReview" class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
                                    <i wire:loading.remove wire:target="submitForReview" class="fas fa-paper-plane mr-2"></i>
                                    Submit Revisions
                                </button>
                                <button
                                    onclick="window.scrollTo({top: document.querySelector('.tracks-container').offsetTop - 100, behavior: 'smooth'})"
                                    class="inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-amber-100 to-yellow-100 hover:from-amber-200 hover:to-yellow-200 text-amber-800 border border-amber-300 rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-md">
                                    <i class="fas fa-upload mr-2"></i>Upload New Files
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
                @endif

                <!-- Completed Pitch Feedback Section -->
                @if($pitch->status == 'completed' && !empty($pitch->completion_feedback))
                <div class="mb-6 bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-2xl p-6 shadow-lg">
                    <div class="flex items-start mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-4 flex-shrink-0">
                            <i class="fas fa-trophy text-white"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-lg font-bold text-green-800 mb-2">Completion Feedback</h4>
                            <p class="text-sm text-green-700 mb-4">
                                The project owner provided the following feedback when completing your pitch:
                            </p>
                        </div>
                    </div>

                    <div class="bg-white/60 backdrop-blur-sm border border-green-200/30 rounded-xl p-4">
                        <div class="text-sm text-green-900 whitespace-pre-wrap leading-relaxed">
                            {{ $pitch->completion_feedback }}
                        </div>
                    </div>
                </div>
                @endif

                {{-- Payout Status for Standard Workflow (Producer View) --}}
                @if(auth()->check() && auth()->id() === $pitch->user_id)
                    <x-pitch.payout-status :pitch="$pitch" />
                @endif

                <!-- Submitted Pitches -->
                @if($snapshots->isNotEmpty())
                <div class="mb-6">
                    <div class="flex items-center mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-3">
                            <i class="fas fa-history text-white"></i>
                        </div>
                        <h4 class="text-lg font-bold text-purple-800">Submission History</h4>
                    </div>
                    <div class="space-y-3">
                        @foreach($snapshots as $snapshot)
                        <div class="bg-gradient-to-br from-white/90 to-purple-50/50 backdrop-blur-sm border border-purple-200/30 rounded-xl p-4 shadow-sm hover:shadow-md transition-all duration-200">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex-1 min-w-0 mb-3 sm:mb-0">
                                    <div class="flex flex-wrap items-center mb-2">
                                        <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-lg mr-3">
                                            <i class="fas fa-code-branch text-purple-600 text-sm"></i>
                                        </div>
                                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
                                            class="font-bold text-purple-800 hover:text-purple-600 transition-colors">
                                            Version {{ $snapshot->snapshot_data['version'] ?? 1 }}
                                        </a>
                                        <span class="text-xs text-purple-600 ml-3 bg-purple-100/50 px-2 py-1 rounded-lg">
                                            {{ $snapshot->created_at->format('M d, Y H:i') }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{
                                            $snapshot->status === 'accepted' ? 'bg-green-100 text-green-800 border-green-200' :
                                            ($snapshot->status === 'denied' ? 'bg-red-100 text-red-800 border-red-200' :
                                            ($snapshot->status === 'revisions_requested' ? 'bg-amber-100 text-amber-800 border-amber-200' :
                                            ($snapshot->status === 'revision_addressed' ? 'bg-blue-100 text-blue-800 border-blue-200' :
                                            'bg-blue-100 text-blue-800 border-blue-200')))
                                        }}">
                                            {{ match($snapshot->status) {
                                            'accepted' => 'Accepted',
                                            'denied' => 'Denied',
                                            'revisions_requested' => 'Revisions Requested',
                                            'revision_addressed' => 'Revision Addressed',
                                            default => ucfirst($snapshot->status)
                                            } }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
                                        class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 hover:shadow-md text-xs">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                    <button wire:click="deleteSnapshot({{ $snapshot->id }})"
                                        wire:confirm="Are you sure you want to delete this version?"
                                        class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-red-500 to-rose-600 hover:from-red-600 hover:to-rose-700 text-white rounded-lg font-medium transition-all duration-200 hover:scale-105 hover:shadow-md text-xs">
                                        <i class="fas fa-trash mr-1"></i>Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- File Management Section -->
                @if($pitch->status !== \App\Models\Pitch::STATUS_PENDING || $pitch->status === \App\Models\Pitch::STATUS_CONTEST_ENTRY)
                <div class="mb-6 tracks-container">
                    <div class="flex items-center mb-4">
                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-3">
                            <i class="fas fa-file-upload text-white"></i>
                        </div>
                        <h4 class="text-lg font-bold text-purple-800">
                            @if($project->isContest())
                                Upload Contest Entry Files
                            @else
                                Upload Files
                            @endif
                        </h4>
                    </div>

                    <!-- Storage usage display -->
                    <div class="mb-6 bg-gradient-to-br from-blue-50/90 to-indigo-50/90 backdrop-blur-sm border border-blue-200/50 rounded-xl p-4 shadow-sm">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm font-bold text-blue-800">Storage Used: {{ $storageLimitMessage }}</span>
                            <span class="text-xs text-blue-600 bg-blue-100/50 px-2 py-1 rounded-lg">{{ $this->formatFileSize($storageRemaining) }} remaining</span>
                        </div>
                        <div class="w-full bg-blue-200/50 rounded-full h-3 overflow-hidden">
                            <div class="h-3 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-gradient-to-r from-red-500 to-red-600' : ($storageUsedPercentage > 70 ? 'bg-gradient-to-r from-amber-500 to-yellow-500' : 'bg-gradient-to-r from-blue-500 to-indigo-600') }}"
                                style="width: {{ $storageUsedPercentage }}%"></div>
                        </div>
                        <div class="mt-3 flex items-center text-xs text-blue-700">
                            <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                            Maximum file size: 200MB. Storage based on your subscription plan.
                        </div>
                    </div>

                    <!-- File Upload Section -->
                    <div class="bg-gradient-to-br from-white/95 to-purple-50/50 backdrop-blur-sm border border-purple-200/30 rounded-xl shadow-sm overflow-hidden mb-6">
                        <div class="p-4 bg-gradient-to-r from-purple-100/60 to-indigo-100/60 backdrop-blur-sm border-b border-purple-200/30">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3">
                                    <i class="fas fa-cloud-upload-alt text-white text-sm"></i>
                                </div>
                                <div>
                                    <h5 class="font-bold text-purple-800">Upload New Files</h5>
                                    <p class="text-xs text-purple-600 mt-1">
                                        @if($project->isContest())
                                            Upload your contest entry files - audio, PDFs, or images
                                        @else
                                            Upload audio, PDFs, or images to include with your pitch
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            @if($this->canUploadFiles)
                                <livewire:uppy-file-uploader :model="$pitch" wire:key="'enhanced-pitch-uploader-' . $pitch->id" />
                            @else
                                <div class="text-center py-8">
                                    <p class="text-gray-500 mb-2">File uploads are not available for this pitch.</p>
                                    @if(in_array($pitch->status, ['completed', 'closed', 'denied', 'contest_winner', 'contest_runner_up', 'contest_not_selected']))
                                        <p class="text-sm text-gray-400">Pitch is in a final state - no additional files can be uploaded.</p>
                                    @elseif($pitch->isAcceptedCompletedAndPaid())
                                        <p class="text-sm text-gray-400">Pitch is completed and paid - no additional files can be uploaded.</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Existing Files List -->
                    <div class="bg-gradient-to-br from-white/95 to-purple-50/50 backdrop-blur-sm border border-purple-200/30 rounded-xl shadow-sm overflow-hidden">
                        <div class="p-4 bg-gradient-to-r from-purple-100/60 to-indigo-100/60 backdrop-blur-sm border-b border-purple-200/30 flex justify-between items-center">
                            <div class="flex items-center">
                                <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-lg mr-3">
                                    <i class="fas fa-folder text-white text-sm"></i>
                                </div>
                                <h5 class="font-bold text-purple-800">Pitch Files ({{ $pitch->files->count() }})</h5>
                            </div>
                            @if($pitch->files->count() > 0)
                            <span class="text-xs text-purple-600 bg-purple-100/50 px-2 py-1 rounded-lg">Total: {{ $this->formatFileSize($pitch->files->sum('size')) }}</span>
                            @endif
                        </div>
                        
                        <div class="divide-y divide-purple-200/30">
                            @forelse($pitch->files as $file)
                            <div class="flex items-center justify-between py-4 px-4 hover:bg-purple-50/30 transition-all duration-300 track-item
                                @if(in_array($file->id, $newlyUploadedFileIds ?? [])) animate-fade-in @endif">
                                <div class="flex items-center overflow-hidden flex-1 pr-3">
                                    <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center bg-gradient-to-br from-purple-100 to-indigo-100 text-purple-600 mr-4">
                                        @if (Str::startsWith($file->mime_type, 'audio/'))
                                            <i class="fas fa-music"></i>
                                        @elseif ($file->mime_type == 'application/pdf')
                                            <i class="fas fa-file-pdf text-red-500"></i>
                                        @elseif (Str::startsWith($file->mime_type, 'image/'))
                                            <i class="fas fa-file-image text-blue-500"></i>
                                        @else
                                            <i class="fas fa-file-alt"></i>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <a href="{{ route('pitch-files.show', $file) }}" 
                                           class="font-bold truncate text-purple-900 hover:text-purple-600 transition-colors duration-200 block" 
                                           title="{{ $file->file_name }}">{{ $file->file_name }}</a>
                                        <div class="flex items-center text-xs text-purple-600 mt-1">
                                            <span>{{ $file->created_at->format('M d, Y') }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ $this->formatFileSize($file->size) }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="{{ route('pitch-files.show', $file) }}"
                                       class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-green-100 to-emerald-100 hover:from-green-200 hover:to-emerald-200 text-green-600 rounded-lg transition-all duration-200 hover:scale-105"
                                       title="View file details">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <button wire:click="downloadFile({{ $file->id }})"
                                        class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-100 to-indigo-100 hover:from-blue-200 hover:to-indigo-200 text-blue-600 rounded-lg transition-all duration-200 hover:scale-105"
                                        title="Download file">
                                        <i class="fas fa-download text-sm"></i>
                                    </button>
                                    <button wire:click="confirmDeleteFile({{ $file->id }})" 
                                            class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-red-100 to-rose-100 hover:from-red-200 hover:to-rose-200 text-red-600 rounded-lg transition-all duration-200 hover:scale-105"
                                            title="Delete file">
                                        <i class="fas fa-trash text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            @empty
                            <div class="p-10 text-center">
                                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-100 to-indigo-100 rounded-2xl mx-auto mb-4">
                                    <i class="fas fa-folder-open text-purple-400 text-2xl"></i>
                                </div>
                                <h6 class="text-lg font-bold text-purple-800 mb-2">No files uploaded yet</h6>
                                <p class="text-sm text-purple-600">Upload files to include with your pitch</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif
                {{-- Internal Notes Section --}}
                @can('update', $pitch)
                <div x-data="{ open: false }" class="mb-6 bg-gradient-to-br from-blue-50/50 to-indigo-50/50 backdrop-blur-sm border border-blue-200/30 rounded-xl overflow-hidden">
                    <button @click="open = !open" class="flex justify-between items-center w-full p-4 bg-gradient-to-r from-blue-100/60 to-indigo-100/60 hover:from-blue-100/80 hover:to-indigo-100/80 transition-all duration-200">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                                <i class="fas fa-sticky-note text-white text-sm"></i>
                            </div>
                            <span class="font-bold text-blue-800">Internal Notes (Visible only to you)</span>
                        </div>
                        <i class="fas text-blue-600 transition-transform duration-200" :class="{ 'fa-chevron-down': !open, 'fa-chevron-up': open }"></i>
                    </button>
                    <div x-show="open" x-collapse class="p-4 bg-white/60 backdrop-blur-sm border-t border-blue-200/30">
                        <textarea wire:model.debounce.1000ms="internalNotes" rows="4"
                            class="w-full p-3 border border-blue-200/50 rounded-xl bg-white/80 backdrop-blur-sm text-sm text-gray-800 placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500/50 transition-all duration-200"
                            placeholder="Add private notes about this pitch..."></textarea>
                        @error('internalNotes') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        <div class="mt-3 text-right">
                            <button wire:click="saveInternalNotes" wire:loading.attr="disabled"
                                    class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg disabled:opacity-50">
                                <span wire:loading wire:target="saveInternalNotes" class="inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin mr-2"></span>
                                <i wire:loading.remove wire:target="saveInternalNotes" class="fas fa-save mr-2"></i>
                                Save Notes
                            </button>
                        </div>
                    </div>
                </div>
                @endcan
                <!-- Submit for review button section -->
                @if(in_array($pitch->status, [
                    \App\Models\Pitch::STATUS_IN_PROGRESS, 
                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED, 
                    \App\Models\Pitch::STATUS_DENIED
                ]))
                <div class="mt-6 bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-xl p-6 shadow-sm">
                    @error('acceptedTerms')
                    <div class="mb-4 p-3 bg-red-100/50 border border-red-200 rounded-lg">
                        <span class="text-red-600 text-sm flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>{{ $message }}
                        </span>
                    </div>
                    @enderror
                    
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="terms" class="w-5 h-5 text-green-600 bg-white border-green-300 rounded focus:ring-green-500 focus:ring-2"
                                wire:model.defer="acceptedTerms">
                            <label for="terms" class="ml-3 text-sm text-green-800">
                                I accept the <a href="/terms" target="_blank" class="text-green-600 hover:text-green-800 underline font-medium">terms and conditions</a>
                            </label>
                        </div>

                        <button wire:click="submitForReview" wire:confirm="Are you sure you want to Submit your Pitch?"
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-bold transition-all duration-200 hover:scale-105 hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="!acceptedTerms">
                            <i class="fas fa-check mr-2"></i>
                            {{ $pitch->status == 'denied' || $pitch->status == 'revisions_requested' ? 'Resubmit Pitch' : 'Ready To Submit' }}
                        </button>
                    </div>
                </div>
                @endif

                <!-- Cancel submission button section -->
                @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW && auth()->id() === $pitch->user_id)
                <div class="mt-6 bg-gradient-to-br from-red-50/90 to-rose-50/90 backdrop-blur-sm border border-red-200/50 rounded-xl p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <h5 class="font-bold text-red-800 mb-1">Cancel Submission</h5>
                            <p class="text-sm text-red-600">This will return your pitch to 'In Progress' status</p>
                        </div>
                        <button wire:click="cancelPitchSubmission"
                            wire:confirm="Are you sure you want to cancel your submission? This will return your pitch to 'In Progress' status and delete the current pending snapshot."
                            class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl font-bold transition-all duration-200 hover:scale-105 hover:shadow-lg">
                            <i class="fas fa-times mr-2"></i>
                            Cancel Submission
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Delete File Modal --}}
    @if($showDeleteModal)
    <div class="fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen p-4 text-center">
            {{-- Background overlay --}}
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" wire:click="cancelDeleteFile"></div>
            
            {{-- Modal panel --}}
            <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-2xl transform transition-all max-w-lg w-full">
                <div class="p-6">
                    <div class="flex items-start">
                        <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-red-100 to-rose-100 rounded-xl mr-4 flex-shrink-0">
                            <i class="fas fa-exclamation-triangle text-red-600 text-lg"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">Delete File</h3>
                            <p class="text-sm text-gray-600">
                                Are you sure you want to delete this file? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50/80 backdrop-blur-sm px-6 py-4 flex flex-col sm:flex-row gap-3 sm:justify-end rounded-b-2xl">
                    <button wire:click="cancelDeleteFile" type="button" 
                            class="inline-flex items-center justify-center px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-300 rounded-xl font-medium transition-all duration-200 hover:scale-105">
                        Cancel
                    </button>
                    <button wire:click="deleteSelectedFile" type="button" 
                            class="inline-flex items-center justify-center px-4 py-2 bg-gradient-to-r from-red-600 to-rose-600 hover:from-red-700 hover:to-rose-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

