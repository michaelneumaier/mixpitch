<div class="container mx-auto px-1 sm:px-4">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-6 sm:mb-12 overflow-hidden">
                <div class="flex flex-col sm:flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative w-full sm:w-1/5 sm:shrink-0 md:w-48">
                        <!-- Image that triggers the lightbox -->
                        @if($pitch->project->image_path)
                        <img @click="lightbox.isOpen = true" src="{{ $pitch->project->imageUrl }}"
                            alt="{{ $pitch->project->name }}"
                            class="w-full h-40 sm:h-48 object-cover rounded-t-lg sm:rounded-none sm:rounded-tl-lg cursor-pointer" />
                        @else
                        <div
                            class="flex items-center justify-center w-full h-40 sm:h-48 object-cover rounded-t-lg sm:rounded-none sm:rounded-tl-lg bg-base-200">
                            <i class="fas fa-music text-4xl sm:text-5xl text-gray-400"></i>
                        </div>
                        @endif

                        <!-- The actual lightbox overlay -->
                        @if($pitch->project->image_path)
                        <div x-cloak x-show="lightbox.isOpen" 
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 transition-all"
                            x-transition>
                            <img class="max-h-[80vh] max-w-[90vw] object-contain shadow-2xl rounded"
                                src="{{ $pitch->project->imageUrl }}" 
                                alt="{{ $pitch->project->name }}"
                                @click.away="lightbox.isOpen = false">
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative pb-0 flex flex-grow flex-col">
                        <div class="w-full flex px-2 sm:px-3 py-2 flex-col justify-center flex-1">
                            <div class="p-1 sm:p-2">
                                <div class="flex flex-col mb-1 sm:mb-2">
                                    <div class="flex pb-1">
                                        <img class="h-7 w-7 sm:h-8 sm:w-8 md:h-10 md:w-10 rounded-full object-cover mr-2 border-2 border-base-200"
                                            src="{{ $pitch->user->profile_photo_url }}"
                                            alt="{{ $pitch->user->name }}" />
                                        <h2 class="md:pl-2 text-xl sm:text-2xl md:text-3xl font-bold break-words">
                                            <x-user-link :user="$pitch->user" />'s Pitch
                                        </h2>
                                    </div>

                                    <div class="text-base sm:text-lg md:text-xl font-medium text-gray-700">
                                        for <a href="{{ route('projects.show', $pitch->project) }}"
                                            class="text-primary hover:text-primary-focus transition-colors">"{{
                                            $pitch->project->name }}"</a>
                                    </div>
                                    <div class=""><span class="text-xs sm:text-sm text-gray-600">Submitted</span>
                                        <span class="text-sm sm:text-base font-medium">{{ $pitchSnapshot->created_at->format('M j,
                                            Y')
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Status Bar -->
                        @if (Auth::check() && Auth::id() === $pitch->project->user_id)
                        <div class="flex flex-col sm:flex-row w-full">
                            @if ($pitchSnapshot->status === 'pending')
                            @php
                                $approveUrl = route('projects.pitches.approve-snapshot', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitchSnapshot->id]);
                                $revisionsUrl = route('projects.pitches.request-changes', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitchSnapshot->id]);
                                $denyUrl = route('projects.pitches.deny-snapshot', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $pitchSnapshot->id]);
                            @endphp
                            
                            <!-- JavaScript-based buttons -->
                            <button
                                onclick="openApproveModal({{ $pitchSnapshot->id }}, @js($approveUrl))"
                                class="block sm:basis-1/3 bg-success hover:bg-success/80 text-white tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap">
                                <i class="fas fa-check mr-1.5 sm:mr-2"></i> Approve
                            </button>
                            <button
                                onclick="openRevisionsModal({{ $pitchSnapshot->id }}, @js($revisionsUrl))"
                                class="block sm:basis-1/3 bg-primary hover:bg-primary/80 text-white tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap">
                                <i class="fas fa-edit mr-1.5 sm:mr-2"></i> Request Revisions
                            </button>
                            <button
                                onclick="openDenyModal({{ $pitchSnapshot->id }}, @js($denyUrl))"
                                class="block sm:basis-1/3 bg-decline hover:bg-decline/80 tracking-tight text-base sm:text-lg text-center text-gray-100 font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap">
                                <i class="fas fa-times mr-1.5 sm:mr-2"></i> Deny
                            </button>
                            
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
                            <div
                                class="block bg-accent tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap">
                                <i class="fas fa-check-circle mr-1.5 sm:mr-2"></i> Pitch Accepted
                            </div>
                            @elseif ($pitchSnapshot->status === 'revisions_requested')
                            <div class="flex flex-col sm:flex-row w-full">
                                <div
                                    class="block bg-amber-500 tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap text-white">
                                    <i class="fas fa-edit mr-1.5 sm:mr-2"></i> Revisions Requested
                                </div>
                                @if(auth()->id() === $pitch->user_id)
                                <a href="{{ route('projects.pitches.edit', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}"
                                    class="block bg-amber-600 hover:bg-amber-700 tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap text-white">
                                    <i class="fas fa-reply mr-1.5 sm:mr-2"></i> Submit Revisions
                                </a>
                                @endif
                            </div>
                            @elseif ($pitchSnapshot->status === 'revision_addressed')
                            <div class="flex w-full">
                                <div
                                    class="block bg-info/80 tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap text-white">
                                    <i class="fas fa-check-circle mr-1.5 sm:mr-2"></i> Revision Addressed
                                </div>
                            </div>
                            @elseif ($pitchSnapshot->status === 'denied')
                            <div
                                class="block bg-decline tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap text-gray-100">
                                <i class="fas fa-times-circle mr-1.5 sm:mr-2"></i> Pitch Denied
                            </div>
                            @endif
                        </div>
                        @else
                        @if ($pitchSnapshot->status === 'pending')
                        <div class="flex w-full">
                            <div
                                class="block bg-warning/80 tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 whitespace-nowrap text-white">
                                <i class="fas fa-hourglass-half mr-1.5 sm:mr-2"></i> Pending Review
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'accepted')
                        <div class="flex w-full">
                            <div
                                class="block bg-accent tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap">
                                <i class="fas fa-check-circle mr-1.5 sm:mr-2"></i> Pitch Accepted
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'revisions_requested')
                        <div class="flex w-full">
                            <div
                                class="block bg-amber-500 tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap text-white">
                                <i class="fas fa-edit mr-1.5 sm:mr-2"></i> Revisions Requested
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'revision_addressed')
                        <div class="flex w-full">
                            <div
                                class="block bg-info/80 tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap text-white">
                                <i class="fas fa-check-circle mr-1.5 sm:mr-2"></i> Revision Addressed
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'denied')
                        <div class="flex w-full">
                            <div
                                class="block bg-decline tracking-tight text-base sm:text-lg text-center font-bold grow py-2.5 sm:py-3 px-3 sm:px-4 shadow-sm whitespace-nowrap text-gray-100">
                                <i class="fas fa-times-circle mr-1.5 sm:mr-2"></i> Pitch Denied
                            </div>
                        </div>
                        @endif
                        @endif

                    </div>
                </div>

                <!-- Navigation and Back Button -->
                <div class="p-2 sm:p-3">
                    <!-- Snapshot Navigation -->
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

                        <!-- Show Navigation Controls if there are multiple snapshots -->
                        @if($totalSnapshots > 1)
                        <div class="flex flex-wrap gap-2 sm:gap-3 items-center bg-base-200/30 p-2 sm:p-3 rounded-lg">
                            <div class="w-full sm:w-auto sm:flex-grow order-1">
                                <span class="text-gray-600 text-xs sm:text-sm font-medium block sm:inline text-center sm:text-left">
                                    <span class="font-bold text-sm sm:text-base">Version {{ $currentPosition + 1 }}</span> of {{
                                    $totalSnapshots }}
                                </span>
                            </div>

                            <div class="flex justify-center gap-1.5 sm:gap-2 w-full sm:w-auto order-3 sm:order-2 mt-2 sm:mt-0">
                                <!-- Always show Previous when not on the first snapshot -->
                                @if($previousSnapshot)
                                <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $previousSnapshot->id]) }}"
                                    class="btn btn-xs sm:btn-sm bg-base-200 hover:bg-base-300 text-gray-700 text-xs sm:text-sm">
                                    <i class="fas fa-arrow-left mr-1"></i> Previous
                                </a>
                                @endif

                                <!-- Always show Next when not on the last snapshot -->
                                @if($nextSnapshot)
                                <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $nextSnapshot->id]) }}"
                                    class="btn btn-xs sm:btn-sm bg-base-200 hover:bg-base-300 text-gray-700 text-xs sm:text-sm">
                                    Next <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                                @endif

                                <!-- Always show Latest unless we're already on the latest -->
                                @if(!$isLatestSnapshot && $latestSnapshot)
                                <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $latestSnapshot->id]) }}"
                                    class="btn btn-xs sm:btn-sm bg-blue-100 hover:bg-blue-200 text-blue-800 text-xs sm:text-sm">
                                    <i class="fas fa-fast-forward mr-1"></i> Latest
                                </a>
                                @endif
                            </div>
                        </div>
                        @endif


                </div>
                <!-- Main Content Area -->

                @if ($pitchSnapshot->status === 'revisions_requested' || $pitchSnapshot->status === 'denied' ||
                $pitchSnapshot->status === 'revision_addressed' || $pitchSnapshot->status === 'pending' ||
                $pitchSnapshot->status === 'accepted')
                <!-- Feedback Section for All Snapshot Types -->
                <div class="px-2 sm:px-4 md:px-6 py-3 sm:py-4">
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 flex items-center">
                        <i class="fas fa-comments mr-1.5 sm:mr-2 text-blue-600"></i>
                        Feedback & Response
                    </h3>

                    <div class="space-y-3 sm:space-y-4">
                        @forelse($conversationThread as $item)
                        <div
                            class="w-full rounded-lg shadow-sm {{ $item['type'] === 'feedback' ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200' }}">
                            <div
                                class="flex items-center justify-between px-2 sm:px-3 py-1.5 sm:py-2 border-b {{ $item['type'] === 'feedback' ? 'border-amber-200 bg-amber-100/50' : 'border-blue-200 bg-blue-100/50' }}">
                                <div class="flex items-center">
                                    @if($item['user'])
                                    <img class="h-4 w-4 sm:h-5 sm:w-5 rounded-full object-cover mr-1.5 sm:mr-2 border border-gray-200"
                                        src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}" />
                                    <span class="font-medium text-gray-900 text-xs sm:text-sm">{{ $item['user']->name }}</span>
                                    @else
                                    <span class="font-medium text-gray-900 text-xs sm:text-sm">{{ $item['type'] === 'feedback' ?
                                        'Project Owner' : 'Pitch Creator' }}</span>
                                    @endif

                                    <span class="mx-1.5 sm:mx-2 text-xs text-gray-500">•</span>

                                    <span class="inline-flex items-center text-xs">
                                        @if($item['type'] === 'feedback')
                                        @if($item['feedback_type'] === 'revision')
                                        <i class="fas fa-comment-dots mr-1 text-amber-600"></i>
                                        Revision Request
                                        @else
                                        <i class="fas fa-times-circle mr-1 text-red-600"></i>
                                        Denial Reason
                                        @endif
                                        @elseif($item['type'] === 'response')
                                        <i class="fas fa-reply mr-1 text-blue-600"></i>
                                        @if(isset($item['previous_snapshot_id']))
                                        <span>
                                            Response to
                                            <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $item['previous_snapshot_id']]) }}"
                                                class="text-blue-600 hover:text-blue-800 hover:underline">previous
                                                version</a>
                                        </span>
                                        @else
                                        Response to feedback
                                        @endif
                                        @endif
                                    </span>
                                </div>

                                <div class="flex items-center">
                                    @if($item['date'])
                                    <span class="text-xs text-gray-500">{{ $item['date']->format('M j, g:i a') }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="p-2 sm:p-4 text-gray-700 text-xs sm:text-sm">
                                @if($item['message'])
                                {{ $item['message'] }}
                                @else
                                <span class="italic text-gray-500">No specific response was provided with this
                                    revision.</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center p-3 sm:p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <i class="fas fa-comments text-gray-300 text-2xl sm:text-3xl mb-2 sm:mb-3"></i>
                            <p class="text-gray-500 text-xs sm:text-sm">No feedback or revision messages for this snapshot.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                @endif

                <!-- Completion Feedback Section -->
                @if($pitch->status === 'completed' && !empty($pitch->completion_feedback) && $pitchSnapshot->id === $pitch->current_snapshot_id)
                <div class="px-2 sm:px-4 md:px-6 py-3 sm:py-4 border-t border-base-200">
                    <h3 class="text-base sm:text-lg font-semibold mb-2 sm:mb-3 flex items-center">
                        <i class="fas fa-trophy mr-1.5 sm:mr-2 text-success"></i>
                        Completion Feedback
                    </h3>
                    
                    <div class="bg-success/10 border border-success/30 rounded-lg p-3 sm:p-4">
                        <div class="flex items-start">
                            <div class="flex-1">
                                <div class="text-sm text-gray-700 mb-2">
                                    The project owner provided the following feedback when completing this pitch:
                                </div>
                                <div class="bg-white border border-success/20 rounded-lg p-2.5 sm:p-3">
                                    <div class="text-gray-800 text-sm whitespace-pre-wrap">{{ $pitch->completion_feedback }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Pitch Files Section -->
                <div class="px-2 sm:px-4 md:px-6 py-3 sm:py-4 shadow-lightGlow shadow-base-300">
                    <h3 class="text-lg sm:text-xl font-semibold mb-3 sm:mb-4 flex items-center">
                        <i class="fas fa-file-audio mr-2 sm:mr-3 text-purple-500"></i>Pitch Files
                    </h3>
                    <div class="bg-base-200/30 rounded-lg p-3 sm:p-4 shadow-inner">
                        <div class="space-y-2 sm:space-y-3">
                            @foreach($snapshotData['file_ids'] as $fileId)
                            @php
                            $file = \App\Models\PitchFile::find($fileId);
                            @endphp
                            @if($file)
                            <div
                                class="flex flex-col p-2 sm:p-3 bg-white rounded-lg shadow-sm border border-base-200 hover:shadow-md transition-shadow">
                                @if($file->note)
                                <div
                                    class="mb-2 sm:mb-3 text-xs sm:text-sm text-gray-700 bg-gray-50 p-1.5 sm:p-2 rounded-md border-l-2 border-blue-500">
                                    <strong>Note:</strong> {{ $file->note }}
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
                            <div class="p-6 sm:p-8 text-center text-gray-500 italic">
                                <i class="fas fa-music text-4xl sm:text-5xl text-gray-300 mb-2 sm:mb-3"></i>
                                <p class="text-base sm:text-lg">No audio files in this pitch</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Back Buttons -->
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-0 pt-3">
                        @if(Auth::id() === $pitch->user_id)
                        <div class="flex-grow text-center md:text-left mb-4 md:mb-0">
                            <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}"
                               class="btn btn-sm bg-base-200 hover:bg-base-300 transition-colors text-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Back to Pitch Overview
                            </a>
                        </div>
                        @endif

                        @if(Auth::id() === $pitch->project->user_id)
                        <a href="{{ route('projects.manage', $pitch->project) }}"
                            class="btn btn-sm w-full sm:w-auto bg-base-200 hover:bg-base-300 text-gray-700 text-xs sm:text-sm">
                            <i class="fas fa-project-diagram mr-1.5 sm:mr-2"></i> Back to Project
                        </a>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Include the shared modals component -->
    <x-pitch-action-modals />
</div>