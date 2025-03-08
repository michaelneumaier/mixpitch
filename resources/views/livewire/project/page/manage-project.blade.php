<div class="container mx-auto px-0 sm:px-4">
    <style>
        /* Custom breakpoint for extra small screens */
        @media (min-width: 475px) {
            .xs\:inline {
                display: inline;
            }

            .xs\:block {
                display: block;
            }

            .xs\:hidden {
                display: none;
            }
        }

        /* Ensure content doesn't overflow on small screens */
        .overflow-x-auto {
            overflow-x: auto;
        }

        /* Ensure text doesn't overflow */
        .break-words {
            word-break: break-word;
        }

        /* Fix for file input on small screens */
        .file-input {
            max-width: 100%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Fix for track filenames */
        .track-filename {
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            word-break: break-all;
        }

        /* Ensure track list items don't expand beyond container */
        .track-item {
            width: 100%;
            min-width: 0;
        }

        /* Force track container to respect parent width */
        .tracks-container {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        /* Responsive track items on very small screens */
        @media (max-width: 380px) {
            .track-item {
                flex-direction: column;
                align-items: flex-start;
            }

            .track-item .track-actions {
                margin-top: 0.5rem;
                margin-left: 2.5rem;
                width: 100%;
                display: flex;
                justify-content: flex-start;
            }
        }

        /* Ensure consistent button sizing in pitch actions */
        .pitch-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .pitch-actions>* {
            flex: 0 0 auto;
        }

        /* Fix layout issues at medium screen sizes */
        @media (min-width: 640px) and (max-width: 800px) {
            .pitch-status-wrapper {
                flex-direction: column;
                align-items: flex-end;
            }

            .pitch-status-wrapper>div {
                margin-bottom: 0.5rem;
            }
        }

        @media (max-width: 640px) {
            .pitch-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .pitch-actions>* {
                width: 100%;
            }
        }
    </style>
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12 overflow-hidden flex flex-col">
                <div class="flex flex-col md:flex-row shadow-lightGlow shadow-base-300 h-full">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative shrink-0 md:w-72">

                        <!-- Image that triggers the lightbox -->
                        @if($project->image_path)
                        <img @click="lightbox.isOpen = true" src="{{ asset('storage/' . $project->image_path) }}"
                            alt="{{ $project->name }}"
                            class="w-full md:aspect-square h-72 object-cover md:rounded-tl-lg cursor-pointer shadow-sm" />
                        @else
                        <div
                            class="w-full md:aspect-square md:w-72 h-72 object-cover lg:rounded-tl-lg bg-base-200 flex items-center justify-center">
                            <i class="fas fa-music text-6xl text-base-300"></i>
                        </div>
                        @endif
                        @if($this->hasPreviewTrack)
                        <div
                            class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm">
                            <livewire:audio-player audioUrl="{{$this->audioUrl}}" isInCard=true />
                        </div>
                        @endif

                        <div class="md:hidden absolute top-0 right-0">
                            <x-project-status-button :status="$project->status" type="top-right" />
                        </div>

                        <!-- The actual lightbox overlay -->
                        @if($project->image_path)
                        <div x-cloak x-show="lightbox.isOpen"
                            class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-75 flex justify-center items-center z-50">
                            <img @click="lightbox.isOpen = false" src="{{ asset('storage/' . $project->image_path) }}"
                                alt="Lightbox image" class="max-w-full max-h-full">

                            <!-- Close button -->
                            <button @click="lightbox.isOpen = false"
                                class="absolute top-4 right-4 text-white bg-black/50 p-2 rounded-full hover:bg-black/70 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="flex-1 overflow-x-auto flex flex-col md:h-72 justify-between">
                        <div class="flex flex-col p-4 md:p-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <a href="{{ route('projects.show', $project) }}"
                                    class="text-2xl md:text-3xl font-bold text-gray-800 break-words hover:text-primary transition-colors">
                                    {{ $project->name }}
                                </a>
                            </div>

                            @if($project->artist_name)
                            <div class="py-1 flex items-center">
                                <span class="font-semibold text-gray-700 mr-2">Artist:</span>
                                <span class="text-gray-900">{{ $project->artist_name }}</span>
                            </div>
                            @endif

                            <!-- User info -->
                            <div class="flex items-center w-full mt-2">
                                <img class="h-10 w-10 rounded-full object-cover mr-3 border-2 border-base-300"
                                    src="{{ $project->user->profile_photo_url }}" alt="{{ $project->user->name }}" />
                                <div class="flex flex-col">
                                    <span class="text-base max-w-xs truncate font-medium">{{ $project->user->name
                                        }}</span>
                                    <span class="text-xs text-gray-600">Project Owner</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <!-- Edit button -->
                            <div class="flex w-full mt-4">
                                <a href="{{ route('projects.edit', $project)}}" class="block bg-warning/80 hover:bg-warning tracking-tight text-xl text-center font-bold
                                                               grow py-2 px-4 shadow-accent hover:shadow-accent-focus
                                                               whitespace-nowrap transition-colors">
                                    <i class="fas fa-edit mr-2"></i> Edit Project Details
                                </a>
                            </div>

                            <!-- Project metadata -->
                            <div class="font-sans w-full border-t border-b border-base-200 overflow-x-auto">
                                <div class="flex flex-row min-w-full">
                                    <div
                                        class="px-2 py-1 md:px-4 flex-1 bg-base-200/70 text-center sm:text-right border-r border-base-200">
                                        <div class="label-text whitespace-nowrap text-gray-600">Project Type</div>
                                        <div class="font-bold">{{ Str::title($project->project_type) }}</div>
                                    </div>
                                    <div
                                        class="py-1 pb-0 px-2 md:px-4 flex-1 bg-base-200/30 border-r border-base-200 text-center">
                                        <div class="label-text text-gray-600">Budget</div>
                                        <div class="font-bold">{{ $project->budget == 0 ? 'Free' :
                                            '$'.number_format($project->budget, 0) }}</div>
                                    </div>
                                    <div class="py-1 pb-0 px-2 md:px-4 flex-1 bg-base-200/70 text-center sm:text-left">
                                        <div class="label-text text-gray-600">Deadline</div>
                                        <div class="whitespace-nowrap font-bold">{{
                                            \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-0 sm:p-2 md:p-4 grid md:grid-cols-2 gap-4">

                    <div
                        class="flex w-full flex-col md:col-span-2 bg-base-100 rounded-lg shadow-md border border-base-300">
                        <div class="p-4 flex flex-col">
                            <span class="text-xl font-bold mb-2 flex items-center">
                                <i
                                    class="fas {{ $project->status == 'completed' ? 'fa-check-circle text-success' : 'fa-toggle-off text-gray-500' }} mr-2"></i>
                                Project Status
                            </span>

                            @if($project->status == 'completed')
                            <div class="mb-4">
                                <div class="p-3 bg-success/20 border-l-4 border-success rounded-r-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-trophy mr-1"></i>
                                        <span class="font-medium">This project has been completed!</span>
                                    </div>
                                    <p class="text-sm mt-1 text-gray-700">
                                        A pitch has been selected and marked as completed
                                        @if($project->completed_at)
                                        on {{ $project->completed_at->format('M d, Y') }}.
                                        @endif
                                    </p>
                                </div>
                            </div>
                            @endif

                            @if(!$project->is_published)
                            <p class="text-gray-700 mb-4">Your project is currently unpublished and not visible to
                                potential collaborators.</p>
                            <div class="btn flex-row bg-primary hover:bg-primary-focus text-white text-center transition-colors"
                                wire:click="publish()">
                                <i class="fas fa-globe mr-2"></i> Publish Project
                            </div>
                            @else
                            <p class="text-gray-700 mb-4">Your project is currently published and visible to potential
                                collaborators.</p>
                            <div class="btn flex-row bg-warning hover:bg-warning/80 text-black text-center transition-colors"
                                wire:click="unpublish()">
                                <i class="fas fa-eye-slash mr-2"></i> Unpublish Project
                            </div>
                            @endif
                        </div>
                    </div>
                    <div
                        class="flex w-full flex-col md:col-span-2 bg-base-100 rounded-lg shadow-md border border-base-300">
                        <div class="p-4 flex flex-col">
                            <span class="text-xl font-bold mb-2 flex items-center">
                                <i class="fas fa-paper-plane w-5 text-center mr-3 text-blue-500"></i>Pitches
                            </span>
                            <div class="flex flex-col divide-y divide-base-300/50">
                                @php
                                // Sort pitches to show completed first, then approved, then others
                                $sortedPitches = $project->pitches->sortBy(function($pitch) {
                                if ($pitch->status === 'completed') return 1;
                                if ($pitch->status === 'approved') return 2;
                                if ($pitch->status === 'closed') return 4;
                                return 3; // All other statuses
                                });
                                @endphp
                                @forelse($sortedPitches as $pitch)
                                <div wire:key="pitch-{{$pitch->id}}" class="flex flex-col w-full {{ $loop->even ? 'bg-base-200/30' : 'bg-base-100' }} hover:bg-base-100 transition-colors relative
                                    {{ $pitch->status === 'completed' ? 'border-l-4 border-l-success' : '' }}
                                    {{ $pitch->status === 'approved' ? 'border-l-4 border-l-blue-500' : '' }}
                                    {{ $pitch->status === 'closed' ? 'border-l-4 border-l-gray-400' : '' }}
                                    {{ $pitch->status === 'denied' ? 'border-l-4 border-l-error' : '' }}
                                    {{ $pitch->status === 'revisions_requested' ? 'border-l-4 border-l-amber-500' : '' }}
                                    {{ $pitch->status === 'pending_review' ? 'border-l-4 border-l-warning' : '' }}
                                    {{ $pitch->status === 'ready_for_review' ? 'border-l-4 border-l-info' : '' }}
                                    {{ $pitch->status === 'in_progress' ? 'border-l-4 border-l-info' : '' }}
                                    {{ $pitch->status === 'pending' ? 'border-l-4 border-l-gray-300' : '' }}">

                                    @if($pitch->status === 'completed')
                                    <div
                                        class="absolute top-0 right-0 bg-success text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-trophy mr-1"></i> <span
                                            class="hidden xs:inline">COMPLETED</span>
                                        <span class="hidden sm:inline text-xs font-normal ml-1">
                                            ({{ \Carbon\Carbon::parse($pitch->completion_date)->format('M d, Y') }})
                                        </span>
                                    </div>
                                    @elseif($pitch->status === 'approved')
                                    <div
                                        class="absolute top-0 left-0 bg-blue-500 text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-thumbs-up mr-1"></i> <span
                                            class="hidden xs:inline">APPROVED</span>
                                    </div>
                                    @elseif($pitch->status === 'closed')
                                    <div
                                        class="absolute top-0 left-0 bg-gray-500 text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-lock mr-1"></i> <span class="hidden xs:inline">CLOSED</span>
                                    </div>
                                    @elseif($pitch->status === 'denied')
                                    <div
                                        class="absolute top-0 left-0 bg-error text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-thumbs-down mr-1"></i> <span
                                            class="hidden xs:inline">DENIED</span>
                                    </div>
                                    @elseif($pitch->status === 'revisions_requested')
                                    <div
                                        class="absolute top-0 left-0 bg-amber-500 text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-edit mr-1"></i> <span class="hidden xs:inline">REVISIONS
                                            REQUESTED</span>
                                    </div>
                                    @elseif($pitch->status === 'pending_review')
                                    <div
                                        class="absolute top-0 left-0 bg-warning text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-hourglass-half mr-1"></i> <span
                                            class="hidden xs:inline">PENDING REVIEW</span>
                                    </div>
                                    @elseif($pitch->status === 'ready_for_review')
                                    <div
                                        class="absolute top-0 left-0 bg-info text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-clipboard-check mr-1"></i> <span class="hidden xs:inline">READY
                                            FOR REVIEW</span>
                                    </div>
                                    @elseif($pitch->status === 'in_progress')
                                    <div
                                        class="absolute top-0 left-0 bg-info text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-spinner mr-1"></i> <span class="hidden xs:inline">IN
                                            PROGRESS</span>
                                    </div>
                                    @elseif($pitch->status === 'pending')
                                    <div
                                        class="absolute top-0 left-0 bg-gray-400 text-white px-2 sm:px-3 py-1 text-xs sm:text-sm font-bold rounded-bl shadow-sm">
                                        <i class="fas fa-clock mr-1"></i> <span class="hidden xs:inline">PENDING</span>
                                    </div>
                                    @endif

                                    <div
                                        class="flex flex-col sm:flex-row justify-between items-start pt-8 p-4 {{ in_array($pitch->status, ['completed', 'approved', 'closed']) ? 'mt-6' : '' }}">
                                        <div class="flex items-center w-full sm:w-auto mb-3 sm:mb-0">
                                            <img class="h-10 w-10 rounded-full object-cover mr-3 border-2 border-base-300"
                                                src="{{ $pitch->user->profile_photo_url }}"
                                                alt="{{ $pitch->user->name }}" />
                                            <div class="min-w-0">
                                                <div class="font-bold truncate">{{ $pitch->user->name }}</div>
                                                <div class="text-sm text-gray-600 truncate">
                                                    Submitted {{ $pitch->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="flex flex-col sm:flex-row items-end sm:items-center w-full sm:w-auto mt-3 sm:mt-0 gap-3 pitch-status-wrapper">
                                            <div class="flex gap-2 w-full sm:w-auto pitch-actions">
                                                @if($pitch->status === \App\Models\Pitch::STATUS_APPROVED &&
                                                !$hasCompletedPitch)
                                                <div>
                                                    <livewire:pitch.component.complete-pitch
                                                        :key="'complete-pitch-'.$pitch->id" :pitch="$pitch" />
                                                </div>
                                                @endif

                                                @if(auth()->id() === $project->user_id && in_array($pitch->status, [
                                                \App\Models\Pitch::STATUS_PENDING,
                                                \App\Models\Pitch::STATUS_IN_PROGRESS,
                                                \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                                \App\Models\Pitch::STATUS_APPROVED,
                                                \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                                \App\Models\Pitch::STATUS_DENIED,
                                                \App\Models\Pitch::STATUS_COMPLETED
                                                ]))
                                                <div>
                                                    <x-update-pitch-status :pitch="$pitch"
                                                        :has-completed-pitch="$hasCompletedPitch" />
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @if($pitch->snapshots->count() > 0)
                                    <div class="p-4 pt-0">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-medium text-gray-700 mb-2 flex items-center">
                                                <i class="fas fa-history mr-2 text-blue-500"></i> Snapshots
                                                <span
                                                    class="ml-2 text-xs bg-base-200 text-gray-600 px-2 py-0.5 rounded-full">{{
                                                    $pitch->snapshots->count() }}</span>

                                                @if($pitch->snapshots->where('status', 'pending')->count() > 0 &&
                                                auth()->id() === $project->user_id)


                                                <div
                                                    class="ml-auto text-xs bg-warning text-white px-2 py-0.5 rounded-full animate-pulse">
                                                    {{ $pitch->snapshots->where('status', 'pending')->count() }} pending
                                                    review
                                                </div>
                                                @endif
                                            </div>
                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-2 gap-2 bg-base-200/30 p-2 rounded-lg">
                                                @foreach($pitch->snapshots->sortByDesc('created_at') as $snapshot)
                                                <a href="{{ route('pitches.showSnapshot', [$pitch->id, $snapshot->id]) }}"
                                                    class="flex items-center p-2 hover:bg-base-200 rounded-md transition-colors">
                                                    <div
                                                        class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center bg-blue-100 text-blue-700 mr-2">
                                                        <i class="fas fa-camera"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="font-medium truncate">Version {{
                                                            $snapshot->snapshot_data['version']
                                                            }}</div>
                                                        <div class="text-xs text-gray-600 truncate">
                                                            {{ $snapshot->created_at->format('M d, Y H:i') }}</div>
                                                    </div>
                                                    @if($snapshot->status === 'accepted')
                                                    <span
                                                        class="ml-auto bg-green-100 text-green-800 text-xs px-2 py-1 rounded flex-shrink-0">Accepted</span>
                                                    @elseif($snapshot->status === 'declined')
                                                    <span
                                                        class="ml-auto bg-red-100 text-red-800 text-xs px-2 py-1 rounded flex-shrink-0">Declined</span>
                                                    @elseif($snapshot->status === 'revisions_requested')
                                                    <span
                                                        class="ml-auto bg-amber-100 text-amber-800 text-xs px-2 py-1 rounded flex-shrink-0">Revisions
                                                        Requested</span>
                                                    @elseif($snapshot->status === 'revision_addressed')
                                                    <span
                                                        class="ml-auto bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded flex-shrink-0">Revision
                                                        Addressed</span>
                                                    @elseif($snapshot->status === 'pending')
                                                    <span
                                                        class="ml-auto bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded flex-shrink-0">Pending</span>
                                                    @else
                                                    <span
                                                        class="ml-auto bg-amber-100 text-amber-800 text-xs px-2 py-1 rounded flex-shrink-0">{{
                                                        $snapshot->status }}</span>
                                                    @endif
                                                </a>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @endif

                                    @if($pitch->completion_feedback && $pitch->status === 'completed')
                                    <div class="px-4 pb-4">
                                        <div class="p-3 bg-success/10 border border-success/20 rounded-lg">
                                            <div class="text-sm font-semibold text-gray-700 mb-1 flex items-center">
                                                <i class="fas fa-comment-alt text-success mr-2"></i>Completion Feedback:
                                            </div>
                                            <div class="text-gray-800">{{ $pitch->completion_feedback }}</div>
                                        </div>
                                    </div>
                                    @endif

                                </div>
                                @empty
                                <div class="p-10 text-center text-gray-500 italic">
                                    <i class="fas fa-paper-plane text-5xl text-gray-300 mb-3"></i>
                                    <p class="text-lg">No pitches have been submitted yet</p>
                                    <p class="text-sm mt-2">When users submit pitches for your project, they will appear
                                        here</p>
                                </div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Tracks Section -->
                    <div class="flex w-full flex-col md:col-span-2 bg-base-100 rounded-lg shadow-md border border-base-300 max-w-full tracks-container"
                        x-data="{ 
                            isUploading: false, 
                            progress: 0,
                            deleteModal: {
                                isOpen: false,
                                fileId: null,
                                fileName: ''
                            },
                            openDeleteModal(fileId, fileName) {
                                this.deleteModal.isOpen = true;
                                this.deleteModal.fileId = fileId;
                                this.deleteModal.fileName = fileName;
                            },
                            closeDeleteModal() {
                                this.deleteModal.isOpen = false;
                            },
                            confirmDelete() {
                                $wire.deleteFile(this.deleteModal.fileId);
                                this.closeDeleteModal();
                            }
                        }" x-on:livewire-upload-start="isUploading = true"
                        x-on:livewire-upload-finish="isUploading = false"
                        x-on:livewire-upload-error="isUploading = false"
                        x-on:livewire-upload-progress="progress = $event.detail.progress"
                        x-on:new-files-added.window="setTimeout(() => { $wire.clearHighlights() }, 2000)"
                        x-on:new-uploads-completed.window="setTimeout(() => { $wire.clearUploadHighlights() }, 2000)">
                        <div class="p-4 flex flex-col">
                            <span class="text-xl font-bold mb-2 flex items-center">
                                <i class="fas fa-music w-5 text-center mr-3 text-purple-500"></i>Tracks
                            </span>

                            <div class="mb-4">
                                <div class="flex flex-col">
                                    <label class="mb-2 text-gray-700">Upload new tracks</label>
                                    <div class="flex flex-col">
                                        <div class="flex flex-col sm:flex-row gap-2 mb-2">
                                            <div class="flex-grow min-w-0 overflow-hidden">
                                                <label for="newUploadedFiles"
                                                    class="flex flex-col items-center justify-center w-full h-16 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors">
                                                    <div class="flex items-center justify-center">
                                                        <i class="fas fa-cloud-upload-alt text-gray-400 mr-2"></i>
                                                        <span class="text-sm text-gray-500">
                                                            Click to add audio files
                                                        </span>
                                                    </div>
                                                    <input type="file" wire:model.live="newUploadedFiles"
                                                        id="newUploadedFiles" class="hidden"
                                                        accept="audio/mpeg,audio/wav,audio/mp3,audio/aac,audio/ogg"
                                                        multiple />
                                                </label>
                                                @error('uploadedFiles.*') <span class="text-red-500 text-sm">{{ $message
                                                    }}</span>
                                                @enderror
                                            </div>
                                            <div class="flex-shrink-0">
                                                <button wire:click="uploadFiles" wire:loading.attr="disabled"
                                                    class="btn bg-primary hover:bg-primary-focus text-white w-full h-16 sm:h-full"
                                                    @if(empty($uploadedFiles)) disabled @endif>
                                                    <i class="fas fa-upload mr-2"></i> Upload
                                                </button>
                                            </div>
                                        </div>

                                        @if(count($uploadedFiles) > 0)
                                        <div class="bg-base-200/50 p-3 rounded-lg mb-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="font-medium">Files to upload ({{ count($uploadedFiles) }})
                                                </h4>
                                                <button wire:click="$set('uploadedFiles', []); $set('fileSizes', []);"
                                                    class="text-red-500 hover:text-red-700 transition-colors text-sm">
                                                    Clear All
                                                </button>
                                            </div>
                                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                                @foreach($uploadedFiles as $key => $file)
                                                <div class="flex items-center justify-between bg-white p-2 rounded-md transition-all duration-500
                                                    @if(in_array($key, $newlyAddedFileKeys)) animate-fade-in @endif">
                                                    <div class="flex items-center flex-1 min-w-0">
                                                        <i class="fas fa-music text-purple-500 mr-2"></i>
                                                        <div class="truncate flex-1">
                                                            {{ $file->getClientOriginalName() }}
                                                            <span class="text-xs text-gray-500 ml-1">{{ $fileSizes[$key]
                                                                ?? '' }}</span>
                                                        </div>
                                                    </div>
                                                    <button wire:click="removeUploadedFile({{ $key }})"
                                                        class="text-red-500 hover:text-red-700 transition-colors ml-2">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @endif

                                        <div x-show="isUploading" class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                                            <div class="bg-primary h-2.5 rounded-full"
                                                x-bind:style="'width: ' + progress + '%'">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col divide-y divide-base-300/50 overflow-hidden">
                                @forelse($project->files as $file)
                                <div class="flex items-center justify-between py-3 px-2 hover:bg-base-200/30 transition-all duration-500 rounded-lg track-item
                                    @if(in_array($file->id, $newlyUploadedFileIds)) animate-fade-in @endif">
                                    <div class="flex items-center overflow-hidden flex-1 pr-2">
                                        <div
                                            class="w-8 h-8 sm:w-10 sm:h-10 rounded-full flex-shrink-0 flex items-center justify-center {{ $file->id == $project->preview_track ? 'bg-primary text-white' : 'bg-base-200 text-gray-500' }} mr-2 sm:mr-3">
                                            <i class="fas fa-music"></i>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-medium truncate track-filename">{{ $file->file_name }}
                                            </div>
                                            <div class="text-xs text-gray-600 truncate">{{ $file->created_at->format('M
                                                d, Y') }}
                                                •
                                                {{ $file->formatted_size }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-center flex-shrink-0 track-actions">
                                        @if($file->id == $project->preview_track)
                                        <span
                                            class="mr-1 sm:mr-3 text-xs bg-primary/20 text-primary px-1 sm:px-2 py-1 rounded whitespace-nowrap">
                                            <i class="fas fa-star"></i><span
                                                class="hidden sm:inline ml-1">Preview</span>
                                        </span>
                                        @else
                                        <button wire:click="togglePreviewTrack({{ $file->id }})"
                                            class="mr-3 text-xs bg-base-200 hover:bg-base-300 text-gray-700 px-2 py-1 rounded transition-colors hidden sm:block">
                                            Set as preview
                                        </button>
                                        <button wire:click="togglePreviewTrack({{ $file->id }})"
                                            class="mr-1 text-xs bg-base-200 hover:bg-base-300 text-gray-700 px-1 py-1 rounded transition-colors sm:hidden">
                                            <i class="fas fa-star"></i>
                                        </button>
                                        @endif
                                        <a href="{{ Storage::url($file->path) }}" target="_blank"
                                            class="text-gray-500 hover:text-gray-700 transition-colors mr-2">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button @click="openDeleteModal({{ $file->id }}, '{{ $file->file_name }}')"
                                            class="text-red-500 hover:text-red-700 transition-colors">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                @empty
                                <div class="p-10 text-center text-gray-500 italic">
                                    <i class="fas fa-music text-5xl text-gray-300 mb-3"></i>
                                    <p class="text-lg">No tracks uploaded yet</p>
                                    <p class="text-sm mt-2">Upload tracks to share with potential collaborators</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Delete Confirmation Modal -->
                        <div x-show="deleteModal.isOpen" class="fixed inset-0 z-50 overflow-y-auto"
                            x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200"
                            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
                            <div
                                class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div class="fixed inset-0 transition-opacity" aria-hidden="true"
                                    @click="closeDeleteModal()">
                                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                                </div>

                                <!-- Modal panel -->
                                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                        <div class="sm:flex sm:items-start">
                                            <div
                                                class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                                            </div>
                                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                                <h3 class="text-lg leading-6 font-medium text-gray-900"
                                                    id="modal-title">
                                                    Delete Track
                                                </h3>
                                                <div class="mt-2">
                                                    <p class="text-sm text-gray-500">
                                                        Are you sure you want to delete <span class="font-medium"
                                                            x-text="deleteModal.fileName"></span>? This action cannot be
                                                        undone.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <button @click="confirmDelete()" type="button"
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                                            Delete
                                        </button>
                                        <button @click="closeDeleteModal()" type="button"
                                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex w-full flex-col md:col-span-2 bg-base-100 rounded-lg shadow-md border border-base-300"
                        x-data="{ open: false }">
                        <div class="p-4 flex flex-col">
                            <span class="text-xl font-bold mb-2 flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>Danger Zone
                            </span>
                            <p class="text-gray-700 mb-4">Permanently delete this project and all associated files. This
                                action cannot be undone.</p>
                            <div class="btn bg-error/80 hover:bg-error flex-row text-white text-center transition-colors"
                                @click="open = true" onclick="event.stopPropagation();">
                                <i class="fas fa-trash-alt mr-2"></i> Delete Project
                            </div>
                        </div>
                        <!-- Modal -->
                        <div x-show="open" x-cloak class="fixed z-10 inset-0 overflow-y-auto"
                            aria-labelledby="modal-title" role="dialog" aria-modal="true"
                            @click="$event.stopPropagation()">
                            <div class="flex items-center justify-center min-h-screen">
                                <!-- Background overlay -->
                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                    aria-hidden="true"></div>

                                <!-- Modal -->
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"
                                    aria-hidden="true">&#8203;</span>
                                <div
                                    class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                    <div
                                        class="bg-white rounded-lg text-left overflow-hidden shadow-xl p-4 transform transition-all sm:align-middle sm:max-w-lg sm:w-full">
                                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                            Confirm Deletion
                                        </h3>
                                        <div class="mt-2">
                                            <p class="text-sm text-gray-500">
                                                Are you sure you want to delete this project?
                                            </p>
                                        </div>
                                    </div>
                                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                        <!-- Confirm Button -->
                                        <form action="{{ route('projects.destroy', $project) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-sm bg-red-400 border">
                                                Confirm
                                            </button>
                                        </form>
                                        <!-- Cancel Button -->
                                        <button @click="open = false" class="btn-sm border">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>