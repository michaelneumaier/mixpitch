<!-- Background Effects Container -->
<div class="relative min-h-screen bg-gradient-to-br from-blue-50/30 via-white to-purple-50/30">
    <!-- Floating Blur Circles -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-10 w-32 h-32 bg-blue-400/20 rounded-full blur-2xl"></div>
        <div class="absolute bottom-20 right-10 w-24 h-24 bg-purple-400/20 rounded-full blur-xl"></div>
        <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-pink-400/15 rounded-full blur-xl"></div>
    </div>

    <!-- Main Container -->
    <div class="relative container mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex justify-center">
            <div class="w-full lg:w-4/5 2xl:w-3/4">
                <!-- Enhanced Main Card with Glass Morphism -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-6 sm:mb-8">
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
                    
                    <!-- Main Content -->
                    <div class="relative z-10">
                        <div class="flex flex-col lg:flex-row">
                            <!-- Enhanced Project Image Section -->
                            <div x-data="{ lightbox: { isOpen: false } }" class="relative w-full lg:w-80 h-64 lg:h-80 bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                                <!-- Image that triggers the lightbox -->
                                @if($pitch->project->image_path)
                                <img @click="lightbox.isOpen = true" src="{{ $pitch->project->imageUrl }}"
                                    alt="{{ $pitch->project->name }}"
                                    class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-[transform,colors,shadow] duration-300" />
                                @else
                                <div class="w-full h-full bg-gradient-to-br from-indigo-100 via-blue-100 to-purple-100 flex items-center justify-center">
                                    <div class="text-center">
                                        <i class="fas fa-file-audio text-6xl text-indigo-400/60 mb-4"></i>
                                        <p class="text-lg font-medium text-gray-600">{{ $pitch->project->name }}</p>
                                        <p class="text-sm text-gray-500">Pitch Snapshot</p>
                                    </div>
                                </div>
                                @endif

                                <!-- Enhanced Lightbox with Backdrop Blur -->
                                @if($pitch->project->image_path)
                                <div x-cloak x-show="lightbox.isOpen" 
                                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0">
                                    <img class="max-h-[80vh] max-w-[90vw] object-contain shadow-2xl rounded-xl border border-white/20"
                                        src="{{ $pitch->project->imageUrl }}" 
                                        alt="{{ $pitch->project->name }}"
                                        @click.away="lightbox.isOpen = false">
                                </div>
                                @endif
                            </div>

                            <!-- Enhanced Project Details Section -->
                            <div class="relative flex-1 flex flex-col">
                                <div class="flex-1 p-6 lg:p-8">
                                    <!-- Enhanced Header with Gradient Text -->
                                    <div class="mb-6">
                                        <div class="flex items-center mb-4">
                                            <img class="h-10 w-10 sm:h-12 sm:w-12 rounded-full object-cover mr-4 border-2 border-purple-200 shadow-lg"
                                                src="{{ $pitch->user->profile_photo_url }}"
                                                alt="{{ $pitch->user->name }}" />
                                            <div>
                                                <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                                    <x-user-link :user="$pitch->user" />'s Pitch
                                                </h1>
                                                <div class="text-lg sm:text-xl font-medium text-gray-700 mt-1">
                                                    for <a href="{{ route('projects.show', $pitch->project) }}"
                                                        class="text-blue-600 hover:text-blue-800 transition-colors font-semibold">"{{
                                                        $pitch->project->name }}"</a>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Enhanced Submission Date -->
                                        <div class="bg-gradient-to-r from-gray-50/80 to-blue-50/80 backdrop-blur-sm border border-gray-200/50 rounded-xl p-4">
                                            <div class="flex items-center">
                                                <i class="fas fa-calendar-alt text-blue-600 mr-3"></i>
                                                <div>
                                                    <span class="text-sm font-medium text-gray-600">Submitted</span>
                                                    <span class="ml-2 text-base font-bold text-gray-900">{{ $pitchSnapshot->created_at->format('M j, Y') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Enhanced Status Bar with Gradient Buttons -->
                                @if (Auth::check() && Auth::id() === $pitch->project->user_id)
                                
                                {{-- Contest Snapshot Judging Component (replaces standard approve/deny for contests) --}}
                                @if(!$pitch->project->isContest())
                                
                                <div class="border-t border-white/20 bg-gradient-to-r from-gray-50/80 to-gray-100/80 backdrop-blur-sm">
                                    @if ($pitchSnapshot->status === 'pending')
                                    @php
                                        $approveUrl = route('projects.pitches.approve-snapshot', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitchSnapshot->id]);
                                        $revisionsUrl = route('projects.pitches.request-changes', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitchSnapshot->id]);
                                        $denyUrl = route('projects.pitches.deny-snapshot', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitchSnapshot->id]);
                                    @endphp
                                    
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-0">
                                        <!-- Enhanced Approve Button -->
                                        <button
                                            onclick="openApproveModal('{{ $pitchSnapshot->id }}', '{{ $approveUrl }}')"
                                            class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white font-bold text-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-xl relative overflow-hidden group">
                                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                            <i class="fas fa-check mr-3 relative z-10"></i>
                                            <span class="relative z-10">Approve</span>
                                        </button>
                                        
                                        <!-- Enhanced Request Revisions Button -->
                                        <button
                                            onclick="openRevisionsModal('{{ $pitchSnapshot->id }}', '{{ $revisionsUrl }}')"
                                            class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-xl relative overflow-hidden group">
                                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                            <i class="fas fa-edit mr-3 relative z-10"></i>
                                            <span class="relative z-10">Request Revisions</span>
                                        </button>
                                        
                                        <!-- Enhanced Deny Button -->
                                        <button
                                            onclick="openDenyModal('{{ $pitchSnapshot->id }}', '{{ $denyUrl }}')"
                                            class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white font-bold text-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-xl relative overflow-hidden group">
                                            <div class="absolute inset-0 bg-white/20 transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-700"></div>
                                            <i class="fas fa-times mr-3 relative z-10"></i>
                                            <span class="relative z-10">Deny</span>
                                        </button>
                                    </div>
                                    
                                    <!-- Direct POST forms as fallback -->
                                    <div class="hidden">
                                        <form id="direct-approve-form" method="POST" action="{{ $approveUrl }}">
                                            @csrf
                                        </form>
                                        
                                        <form id="direct-deny-form" method="POST" action="{{ $denyUrl }}">
                                            @csrf
                                            <textarea name="reason" id="direct-deny-reason"></textarea>
                                        </form>
                                        
                                        <form id="direct-revisions-form" method="POST" action="{{ $revisionsUrl }}">
                                            @csrf
                                            <textarea name="reason" id="direct-revisions-reason"></textarea>
                                        </form>
                                    </div>
                                    
                                    @elseif ($pitchSnapshot->status === 'accepted')
                                    <div class="flex">
                                        <div class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-green-500 to-emerald-600 text-white font-bold text-lg shadow-lg">
                                            <i class="fas fa-check-circle mr-3"></i>
                                            <span>Pitch Accepted</span>
                                        </div>
                                    </div>
                                    
                                    @elseif ($pitchSnapshot->status === 'revisions_requested')
                                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-0">
                                        <div class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-amber-500 to-orange-500 text-white font-bold text-lg shadow-lg">
                                            <i class="fas fa-edit mr-3"></i>
                                            <span>Revisions Requested</span>
                                        </div>
                                        @if(auth()->id() === $pitch->user_id)
                                        <a href="{{ route('projects.pitches.edit', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}"
                                            class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-amber-600 to-orange-600 hover:from-amber-700 hover:to-orange-700 text-white font-bold text-lg transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-xl">
                                            <i class="fas fa-reply mr-3"></i>
                                            <span>Submit Revisions</span>
                                        </a>
                                        @endif
                                    </div>
                                    
                                    @elseif ($pitchSnapshot->status === 'revision_addressed')
                                    <div class="flex">
                                        <div class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-blue-500 to-indigo-600 text-white font-bold text-lg shadow-lg">
                                            <i class="fas fa-check-circle mr-3"></i>
                                            <span>Revision Addressed</span>
                                        </div>
                                    </div>
                                    
                                    @elseif ($pitchSnapshot->status === 'denied')
                                    <div class="flex">
                                        <div class="flex-1 inline-flex items-center justify-center px-6 py-4 bg-gradient-to-r from-red-500 to-red-600 text-white font-bold text-lg shadow-lg">
                                            <i class="fas fa-times-circle mr-3"></i>
                                            <span>Pitch Denied</span>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                                @endif

                            </div>
                        </div>

                        <!-- Enhanced Navigation Controls -->
                        @php
                        // Get all snapshots for this pitch
                        $snapshots = $pitch->snapshots()->orderBy('created_at')->get();
                        $totalSnapshots = $snapshots->count();

                        // Find the current snapshot's position
                        $currentPosition = 0;
                        $latestSnapshot = null;
                        $previousSnapshot = null;
                        $nextSnapshot = null;

                        foreach ($snapshots as $index => $snapshot) {
                        if ($snapshot->id === $pitchSnapshot->id) {
                        $currentPosition = $index;

                        // Get previous snapshot if exists
                        if ($index > 0) {
                        $previousSnapshot = $snapshots[$index - 1];
                        }

                        // Get next snapshot if exists
                        if ($index < $totalSnapshots - 1) { $nextSnapshot=$snapshots[$index + 1]; } } } if ($totalSnapshots>
                            0) {
                            $latestSnapshot = $snapshots[$totalSnapshots - 1];
                            }

                            // Check if we're on the latest snapshot
                            $isLatestSnapshot = $latestSnapshot && $latestSnapshot->id === $pitchSnapshot->id;
                            @endphp

                            <!-- Enhanced Navigation Controls (only show if there are multiple snapshots) -->
                            @if($totalSnapshots > 1)
                            <div class="p-6">
                                <div class="bg-gradient-to-r from-blue-50/80 to-purple-50/80 backdrop-blur-sm border border-blue-200/50 rounded-2xl p-6 shadow-lg">
                                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                        <div class="flex items-center">
                                            <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl mr-4">
                                                <i class="fas fa-history text-white"></i>
                                            </div>
                                            <div>
                                                <h4 class="text-lg font-bold text-blue-800">Snapshot Navigation</h4>
                                                <p class="text-sm text-blue-600">
                                                    <span class="font-bold">Version {{ $currentPosition + 1 }}</span> of {{ $totalSnapshots }}
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap gap-3 justify-center sm:justify-end">
                                            <!-- Enhanced Previous Button -->
                                            @if($previousSnapshot)
                                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $previousSnapshot->id]) }}"
                                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium text-sm transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                                                <i class="fas fa-arrow-left mr-2"></i> Previous
                                            </a>
                                            @endif

                                            <!-- Enhanced Next Button -->
                                            @if($nextSnapshot)
                                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $nextSnapshot->id]) }}"
                                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium text-sm transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                                                Next <i class="fas fa-arrow-right ml-2"></i>
                                            </a>
                                            @endif

                                            <!-- Enhanced Latest Button -->
                                            @if(!$isLatestSnapshot && $latestSnapshot)
                                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $latestSnapshot->id]) }}"
                                                class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium text-sm transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                                                <i class="fas fa-fast-forward mr-2"></i> Latest
                                            </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                    </div>
                </div>

                <!-- Enhanced Content Sections -->
                @if (!$pitch->project->isContest() && ($pitchSnapshot->status === 'revisions_requested' || $pitchSnapshot->status === 'denied' ||
                $pitchSnapshot->status === 'revision_addressed' || $pitchSnapshot->status === 'pending' ||
                $pitchSnapshot->status === 'accepted'))
                <!-- Enhanced Feedback Section -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-6">
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 via-purple-600/5 to-pink-600/5"></div>
                    
                    <div class="relative z-10 p-6 lg:p-8">
                        <!-- Enhanced Section Header -->
                        <div class="flex items-center mb-6">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                                <i class="fas fa-comments text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                                    Feedback & Response
                                </h2>
                                <p class="text-gray-600 text-sm">Conversation thread for this snapshot</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @forelse($conversationThread as $item)
                            <!-- Enhanced Feedback/Response Card -->
                            <div class="bg-gradient-to-br {{ $item['type'] === 'feedback' ? 'from-amber-50/90 to-orange-50/90' : 'from-blue-50/90 to-indigo-50/90' }} backdrop-blur-sm border {{ $item['type'] === 'feedback' ? 'border-amber-200/50' : 'border-blue-200/50' }} rounded-2xl shadow-lg overflow-hidden">
                                <!-- Enhanced Header -->
                                <div class="bg-gradient-to-r {{ $item['type'] === 'feedback' ? 'from-amber-100/80 to-orange-100/80' : 'from-blue-100/80 to-indigo-100/80' }} backdrop-blur-sm border-b {{ $item['type'] === 'feedback' ? 'border-amber-200/50' : 'border-blue-200/50' }} p-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            @if($item['user'])
                                            <img class="h-8 w-8 rounded-full object-cover mr-3 border-2 border-white shadow-sm"
                                                src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}" />
                                            <div class="mr-4">
                                                <span class="font-bold text-gray-900 text-sm">{{ $item['user']->name }}</span>
                                                <div class="text-xs text-gray-600">
                                                    {{ $item['type'] === 'feedback' ? 'Project Owner' : 'Pitch Creator' }}
                                                </div>
                                            </div>
                                            @else
                                            <div class="flex items-center justify-center w-8 h-8 {{ $item['type'] === 'feedback' ? 'bg-amber-500' : 'bg-blue-500' }} rounded-full mr-3">
                                                <i class="fas {{ $item['type'] === 'feedback' ? 'fa-user-tie' : 'fa-user' }} text-white text-sm"></i>
                                            </div>
                                            <div class="mr-4">
                                                <span class="font-bold text-gray-900 text-sm">{{ $item['type'] === 'feedback' ? 'Project Owner' : 'Pitch Creator' }}</span>
                                                <div class="text-xs text-gray-600">Anonymous</div>
                                            </div>
                                            @endif

                                            <!-- Enhanced Type Badge -->
                                            <div class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $item['type'] === 'feedback' ? 'bg-amber-200/80 text-amber-800' : 'bg-blue-200/80 text-blue-800' }}">
                                                @if($item['type'] === 'feedback')
                                                    @if($item['feedback_type'] === 'revision')
                                                    <i class="fas fa-comment-dots mr-2 text-amber-600"></i>
                                                    Revision Request
                                                    @else
                                                    <i class="fas fa-times-circle mr-2 text-red-600"></i>
                                                    Denial Reason
                                                    @endif
                                                @elseif($item['type'] === 'response')
                                                <i class="fas fa-reply mr-2 text-blue-600"></i>
                                                @if(isset($item['previous_snapshot_id']))
                                                <span>
                                                    Response to
                                                    <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $item['previous_snapshot_id']]) }}"
                                                        class="text-blue-600 hover:text-blue-800 hover:underline font-semibold">previous version</a>
                                                </span>
                                                @else
                                                Response to feedback
                                                @endif
                                                @endif
                                            </div>
                                        </div>

                                        <div class="flex items-center">
                                            @if($item['date'])
                                            <span class="text-xs text-gray-500 bg-white/60 backdrop-blur-sm px-2 py-1 rounded-lg">{{ $item['date']->format('M j, g:i a') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Enhanced Message Content -->
                                <div class="p-4">
                                    @if($item['message'])
                                    <div class="prose prose-sm max-w-none text-gray-700 leading-relaxed">
                                        {{ $item['message'] }}
                                    </div>
                                    @else
                                    <div class="flex items-center text-gray-500 italic">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        <span>No specific response was provided with this revision.</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @empty
                            <!-- Enhanced Empty State -->
                            <div class="bg-gradient-to-br from-gray-50/90 to-gray-100/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-8 text-center shadow-lg">
                                <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-200 to-gray-300 rounded-2xl mx-auto mb-4">
                                    <i class="fas fa-comments text-gray-500 text-2xl"></i>
                                </div>
                                <h4 class="text-lg font-bold text-gray-700 mb-2">No Conversation Yet</h4>
                                <p class="text-gray-500">No feedback or revision messages for this snapshot.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                @endif

                <!-- Enhanced Completion Feedback Section -->
                @if($pitch->status === 'completed' && !empty($pitch->completion_feedback) && $pitchSnapshot->id === $pitch->current_snapshot_id)
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-6">
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r from-green-600/5 via-emerald-600/5 to-green-600/5"></div>
                    
                    <div class="relative z-10 p-6 lg:p-8">
                        <!-- Enhanced Section Header -->
                        <div class="flex items-center mb-6">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-4 shadow-lg">
                                <i class="fas fa-trophy text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent">
                                    Completion Feedback
                                </h2>
                                <p class="text-gray-600 text-sm">Final feedback from the project owner</p>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50/90 to-emerald-50/90 backdrop-blur-sm border border-green-200/50 rounded-2xl p-6 shadow-lg">
                            <div class="flex items-start">
                                <div class="flex-1">
                                    <div class="text-sm text-green-700 mb-4 font-medium">
                                        The project owner provided the following feedback when completing this pitch:
                                    </div>
                                    <div class="bg-white/80 backdrop-blur-sm border border-green-200/50 rounded-xl p-4 shadow-sm">
                                        <div class="text-gray-800 leading-relaxed whitespace-pre-wrap">{{ $pitch->completion_feedback }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                @if (Auth::check() && Auth::id() === $pitch->project->user_id)
                                
                                {{-- Contest Snapshot Judging Component (replaces standard approve/deny for contests) --}}
                                @if($pitch->project->isContest())
                                    @livewire('project.component.contest-snapshot-judging', [
                                        'project' => $pitch->project,
                                        'pitch' => $pitch,
                                        'snapshot' => $pitchSnapshot
                                    ], key('contest-snapshot-judging-'.$pitchSnapshot->id))
                                @endif
                @endif

                <!-- Enhanced Pitch Files Section -->
                <div class="relative bg-white/95 backdrop-blur-md border border-white/20 rounded-2xl shadow-xl overflow-hidden mb-6">
                    <!-- Gradient Overlay -->
                    <div class="absolute inset-0 bg-gradient-to-r from-purple-600/5 via-indigo-600/5 to-purple-600/5"></div>
                    
                    <div class="relative z-10 p-6 lg:p-8">
                        <!-- Enhanced Section Header -->
                        <div class="flex items-center mb-6">
                            <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-xl mr-4 shadow-lg">
                                <i class="fas fa-file-audio text-white text-xl"></i>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold bg-gradient-to-r from-purple-600 to-indigo-600 bg-clip-text text-transparent">
                                    Pitch Files
                                </h2>
                                <p class="text-gray-600 text-sm">Audio files included in this snapshot</p>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50/90 to-indigo-50/90 backdrop-blur-sm border border-purple-200/50 rounded-2xl p-6 shadow-lg">
                            <div class="space-y-4">
                                @foreach($snapshotData['file_ids'] as $fileId)
                                @php
                                $file = \App\Models\PitchFile::find($fileId);
                                @endphp
                                @if($file)
                                <div class="bg-white/80 backdrop-blur-sm border border-purple-200/50 rounded-xl p-4 shadow-sm hover:shadow-md transition-[transform,colors,shadow] duration-200 hover:scale-[1.02]">
                                    @if($file->note)
                                    <div class="mb-4 bg-gradient-to-r from-blue-50/80 to-indigo-50/80 backdrop-blur-sm border border-blue-200/50 rounded-lg p-3">
                                        <div class="flex items-center">
                                            <i class="fas fa-sticky-note text-blue-600 mr-2"></i>
                                            <strong class="text-blue-800 text-sm">Note:</strong>
                                        </div>
                                        <p class="text-blue-700 text-sm mt-1">{{ $file->note }}</p>
                                    </div>
                                    @endif

                                    <!-- Embed our WaveSurfer component -->
                                    @livewire('snapshot-file-player', [
                                    'file' => $file,
                                    'showDownloadButton' => $pitchSnapshot->isAccepted() && $pitch->project->status === 'completed'
                                    ], key('file-player-' . $file->id))
                                </div>
                                @endif
                                @endforeach

                                @if(count($snapshotData['file_ids']) === 0)
                                <!-- Enhanced Empty State -->
                                <div class="bg-gradient-to-br from-gray-50/90 to-purple-50/90 backdrop-blur-sm border border-gray-200/50 rounded-2xl p-8 text-center shadow-lg">
                                    <div class="flex items-center justify-center w-16 h-16 bg-gradient-to-br from-gray-200 to-purple-200 rounded-2xl mx-auto mb-4">
                                        <i class="fas fa-music text-gray-500 text-2xl"></i>
                                    </div>
                                    <h4 class="text-lg font-bold text-gray-700 mb-2">No Audio Files</h4>
                                    <p class="text-gray-500">No audio files are included in this pitch snapshot.</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        <!-- Enhanced Back Buttons -->
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 mt-6 pt-6 border-t border-purple-200/50">
                            @if(Auth::id() === $pitch->user_id)
                            <div class="text-center md:text-left">
                                <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}"
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 hover:from-gray-700 hover:to-gray-800 text-white rounded-xl font-medium transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                                    <i class="fas fa-arrow-left mr-2"></i> Back to Pitch Overview
                                </a>
                            </div>
                            @endif

                            @if(Auth::id() === $pitch->project->user_id)
                            <a href="{{ route('projects.manage', $pitch->project) }}"
                                class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-[transform,colors,shadow] duration-200 hover:scale-105 hover:shadow-lg">
                                <i class="fas fa-project-diagram mr-2"></i> Back to Project
                            </a>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Include the shared modals component -->
    <x-pitch-action-modals />
</div>