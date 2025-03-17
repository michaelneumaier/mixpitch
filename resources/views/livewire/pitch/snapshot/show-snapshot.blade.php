<div class="container mx-auto px-1 sm:px-4">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12 overflow-hidden">
                <div class="flex flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative shrink-0 w-1/5 md:w-48">
                        <!-- Image that triggers the lightbox -->
                        @if($pitch->project->image_path)
                        <img @click="lightbox.isOpen = true" src="{{ $pitch->project->imageUrl }}"
                            alt="{{ $pitch->project->name }}"
                            class="md:aspect-square h-48 object-cover md:rounded-tl-lg cursor-pointer" />
                        @else
                        <div
                            class="flex items-center justify-center w-full md:aspect-square h-48 object-cover md:rounded-tl-lg bg-base-200">
                            <i class="fas fa-music text-5xl text-gray-400"></i>
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
                        <div class="w-full flex px-3 py-2 flex-col justify-center flex-1">
                            <div class="p-2">
                                <div class="flex flex-col mb-2">
                                    <div class="flex pb-1">
                                        <img class="h-8 w-8 md:h-10 md:w-10 rounded-full object-cover mr-2 border-2 border-base-200"
                                            src="{{ $pitch->user->profile_photo_url }}"
                                            alt="{{ $pitch->user->name }}" />
                                        <h2 class="md:pl-2 text-2xl md:text-3xl font-bold break-words">
                                            <x-user-link :user="$pitch->user" />'s Pitch
                                        </h2>
                                    </div>

                                    <div class="text-lg md:text-xl font-medium text-gray-700">
                                        for <a href="{{ route('projects.show', $pitch->project) }}"
                                            class="text-primary hover:text-primary-focus transition-colors">"{{
                                            $pitch->project->name }}"</a>
                                    </div>
                                    <div class=""><span class="text-sm text-gray-600">Submitted</span>
                                        <span class="text-base font-medium">{{ $pitchSnapshot->created_at->format('M j,
                                            Y')
                                            }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>




                        <!-- Status Bar -->
                        @if (Auth::check() && Auth::id() === $pitch->project->user_id)
                        <div class="flex w-full">
                            @if ($pitchSnapshot->status === 'pending')
                            <button
                                onclick="openApproveModal({{ $pitchSnapshot->id }}, '{{ route('pitch.approveSnapshot', ['pitch' => $pitch->id, 'snapshot' => $pitchSnapshot->id]) }}')"
                                class="block basis-1/3 bg-success hover:bg-success/80 text-white tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap">
                                <i class="fas fa-check mr-2"></i> Approve
                            </button>
                            <button
                                onclick="openRevisionsModal({{ $pitchSnapshot->id }}, '{{ route('pitch.requestChanges', ['pitch' => $pitch->id, 'snapshot' => $pitchSnapshot->id]) }}')"
                                class="block basis-1/3 bg-primary hover:bg-primary/80 text-white tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap">
                                <i class="fas fa-edit mr-2"></i> Request Revisions
                            </button>
                            <button
                                onclick="openDenyModal({{ $pitchSnapshot->id }}, '{{ route('pitch.denySnapshot', ['pitch' => $pitch->id, 'snapshot' => $pitchSnapshot->id]) }}')"
                                class="block basis-1/3 bg-decline hover:bg-decline/80 tracking-tight text-lg text-center text-gray-100 font-bold grow py-3 px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap">
                                <i class="fas fa-times mr-2"></i> Deny
                            </button>
                            @elseif ($pitchSnapshot->status === 'accepted')
                            <div
                                class="block bg-accent tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap">
                                <i class="fas fa-check-circle mr-2"></i> Pitch Accepted
                            </div>
                            @elseif ($pitchSnapshot->status === 'revisions_requested')
                            <div class="flex w-full">
                                <div
                                    class="block bg-amber-500 tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap text-white">
                                    <i class="fas fa-edit mr-2"></i> Revisions Requested
                                </div>
                                @if(auth()->id() === $pitch->user_id)
                                <a href="{{ route('pitches.edit', $pitch->id) }}"
                                    class="block bg-amber-600 hover:bg-amber-700 tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm hover:shadow-md transition-all whitespace-nowrap text-white">
                                    <i class="fas fa-reply mr-2"></i> Submit Revisions
                                </a>
                                @endif
                            </div>
                            @elseif ($pitchSnapshot->status === 'revision_addressed')
                            <div class="flex w-full">
                                <div
                                    class="block bg-info/80 tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap text-white">
                                    <i class="fas fa-check-circle mr-2"></i> Revision Addressed
                                </div>
                            </div>
                            @elseif ($pitchSnapshot->status === 'denied')
                            <div
                                class="block bg-decline tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap text-gray-100">
                                <i class="fas fa-times-circle mr-2"></i> Pitch Denied
                            </div>
                            @endif
                        </div>
                        @else
                        @if ($pitchSnapshot->status === 'pending')
                        <div class="flex w-full">
                            <div
                                class="block bg-warning/80 tracking-tight text-lg text-center font-bold grow py-3 px-4 whitespace-nowrap text-white">
                                <i class="fas fa-hourglass-half mr-2"></i> Pending Review
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'accepted')
                        <div class="flex w-full">
                            <div
                                class="block bg-accent tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap">
                                <i class="fas fa-check-circle mr-2"></i> Pitch Accepted
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'revisions_requested')
                        <div class="flex w-full">
                            <div
                                class="block bg-amber-500 tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap text-white">
                                <i class="fas fa-edit mr-2"></i> Revisions Requested
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'revision_addressed')
                        <div class="flex w-full">
                            <div
                                class="block bg-info/80 tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap text-white">
                                <i class="fas fa-check-circle mr-2"></i> Revision Addressed
                            </div>
                        </div>
                        @elseif ($pitchSnapshot->status === 'denied')
                        <div class="flex w-full">
                            <div
                                class="block bg-decline tracking-tight text-lg text-center font-bold grow py-3 px-4 shadow-sm whitespace-nowrap text-gray-100">
                                <i class="fas fa-times-circle mr-2"></i> Pitch Denied
                            </div>
                        </div>
                        @endif
                        @endif

                    </div>
                </div>

                <!-- Navigation and Back Button -->
                <div class="">
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
                        <div class="flex flex-wrap gap-3 items-center bg-base-200/30 p-3 rounded-lg">
                            <div class="flex-grow">
                                <span class="text-gray-600 text-sm font-medium">
                                    <span class="font-bold text-base">Version {{ $currentPosition + 1 }}</span> of {{
                                    $totalSnapshots }}
                                </span>
                            </div>

                            <div class="flex gap-2">
                                <!-- Always show Previous when not on the first snapshot -->
                                @if($previousSnapshot)
                                <a href="{{ route('pitches.showSnapshot', ['pitch' => $pitch->id, 'pitchSnapshot' => $previousSnapshot->id]) }}"
                                    class="btn btn-sm bg-base-200 hover:bg-base-300 text-gray-700">
                                    <i class="fas fa-arrow-left mr-1"></i> Previous
                                </a>
                                @endif

                                <!-- Always show Next when not on the last snapshot -->
                                @if($nextSnapshot)
                                <a href="{{ route('pitches.showSnapshot', ['pitch' => $pitch->id, 'pitchSnapshot' => $nextSnapshot->id]) }}"
                                    class="btn btn-sm bg-base-200 hover:bg-base-300 text-gray-700">
                                    Next <i class="fas fa-arrow-right ml-1"></i>
                                </a>
                                @endif

                                <!-- Always show Latest unless we're already on the latest -->
                                @if(!$isLatestSnapshot && $latestSnapshot)
                                <a href="{{ route('pitches.showSnapshot', ['pitch' => $pitch->id, 'pitchSnapshot' => $latestSnapshot->id]) }}"
                                    class="btn btn-sm bg-blue-100 hover:bg-blue-200 text-blue-800">
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
                <div class="px-2 md:px-6 py-4">
                    <h3 class="text-lg font-semibold mb-3 flex items-center">
                        <i class="fas fa-comments mr-2 text-blue-600"></i>
                        Feedback & Response
                    </h3>

                    <div class="space-y-4">
                        @forelse($conversationThread as $item)
                        <div
                            class="w-full rounded-lg shadow-sm {{ $item['type'] === 'feedback' ? 'bg-amber-50 border border-amber-200' : 'bg-blue-50 border border-blue-200' }}">
                            <div
                                class="flex items-center justify-between px-3 py-2 border-b {{ $item['type'] === 'feedback' ? 'border-amber-200 bg-amber-100/50' : 'border-blue-200 bg-blue-100/50' }}">
                                <div class="flex items-center">
                                    @if($item['user'])
                                    <img class="h-5 w-5 rounded-full object-cover mr-2 border border-gray-200"
                                        src="{{ $item['user']->profile_photo_url }}" alt="{{ $item['user']->name }}" />
                                    <span class="font-medium text-gray-900 text-sm">{{ $item['user']->name }}</span>
                                    @else
                                    <span class="font-medium text-gray-900 text-sm">{{ $item['type'] === 'feedback' ?
                                        'Project Owner' : 'Pitch Creator' }}</span>
                                    @endif

                                    <span class="mx-2 text-xs text-gray-500">•</span>

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
                                            <a href="{{ route('pitches.showSnapshot', [$pitch->id, $item['previous_snapshot_id']]) }}"
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

                            <div class="p-4 text-gray-700 text-sm">
                                @if($item['message'])
                                {{ $item['message'] }}
                                @else
                                <span class="italic text-gray-500">No specific response was provided with this
                                    revision.</span>
                                @endif
                            </div>
                        </div>
                        @empty
                        <div class="text-center p-4 bg-gray-50 rounded-lg border border-gray-200">
                            <i class="fas fa-comments text-gray-300 text-3xl mb-1"></i>
                            <p class="text-gray-500">No feedback or revision messages for this snapshot.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
                @endif



                <!-- Pitch Files Section -->
                <div class="px-2 md:px-6 py-4 shadow-lightGlow shadow-base-300">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-file-audio mr-3 text-purple-500"></i>Pitch Files
                    </h3>
                    <div class="bg-base-200/30 rounded-lg p-4 shadow-inner">
                        <div class="space-y-3">
                            @foreach($snapshotData['file_ids'] as $fileId)
                            @php
                            $file = \App\Models\PitchFile::find($fileId);
                            @endphp
                            @if($file)
                            <div
                                class="flex flex-col p-3 bg-white rounded-lg shadow-sm border border-base-200 hover:shadow-md transition-shadow">
                                @if($file->note)
                                <div
                                    class="mb-3 text-sm text-gray-700 bg-gray-50 p-2 rounded-md border-l-2 border-blue-500">
                                    <strong>Note:</strong> {{ $file->note }}
                                </div>
                                @endif

                                <!-- Embed our WaveSurfer component -->
                                @livewire('snapshot-file-player', [
                                'file' => $file,
                                'showDownloadButton' => $pitchSnapshot->isApproved()
                                ], key('file-player-' . $file->id))
                            </div>
                            @endif
                            @endforeach

                            @if(count($snapshotData['file_ids']) === 0)
                            <div class="p-8 text-center text-gray-500 italic">
                                <i class="fas fa-music text-5xl text-gray-300 mb-3"></i>
                                <p class="text-lg">No audio files in this pitch</p>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Back Buttons -->
                    <div class="flex justify-between pt-3">
                        @if(Auth::id() === $pitch->user_id)
                        <a href="{{ route('pitches.show', $pitch) }}"
                            class="btn bg-base-200 hover:bg-base-300 text-gray-700">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Pitch
                        </a>
                        @endif

                        @if(Auth::id() === $pitch->project->user_id)
                        <a href="{{ route('projects.manage', $pitch->project) }}"
                            class="btn bg-base-200 hover:bg-base-300 text-gray-700">
                            <i class="fas fa-project-diagram mr-2"></i> Back to Project
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