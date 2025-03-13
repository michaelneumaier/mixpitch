<div class="w-full mb-4 project-list-item">
    <div class="bg-white rounded-xl shadow-md transition-all hover:shadow-lg border border-gray-100">
        <div class="flex flex-col md:flex-row">
            <!-- Project Image Section -->
            <div class="relative md:w-1/4 lg:w-1/5">
                <!-- Project Image with Hover Effect -->
                <div class="relative h-48 md:h-full w-full cursor-pointer image-container" wire:click="viewProject">
                    <div class="absolute inset-0 rounded-l-xl bg-center bg-cover bg-no-repeat"
                        style="background-image: url('{{ $project->image_path ? asset('storage/' . $project->image_path) : asset('images/default-project.jpg') }}');">
                    </div>
                    <div class="absolute inset-0 rounded-l-xl bg-gradient-to-t from-black/10 to-transparent"></div>

                    <!-- Status Badge and Preview -->
                    <div class="absolute top-0 right-0">
                        <x-project-status-button :status="$project->status" type="top-right" />
                    </div>
                    @if($project->hasPreviewTrack())
                    <div class="absolute -left-1 -bottom-1" onclick="event.stopPropagation();">
                        @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true],
                        key('preview-'.$project->id))
                    </div>
                    @endif
                </div>
            </div>

            <!-- Project Details Section -->
            <div class="md:w-3/4 lg:w-4/5 p-4 md:p-6 flex flex-col">
                <div class="flex flex-wrap items-start justify-between mb-2 metadata">
                    <!-- Project Title and Creator -->
                    <div class="mb-2 md:mb-0 flex-1 pr-4">
                        <h3 class="text-xl font-bold text-gray-800 hover:text-primary transition-colors truncate">
                            <a href="{{ route('projects.show', $project) }}" class="hover:underline">{{ $project->name
                                }}</a>
                        </h3>
                        <div class="flex items-center text-gray-600 text-sm mt-1">
                            <img class="h-6 w-6 rounded-full object-cover mr-2"
                                src="{{ $project->user->profile_photo_url }}" alt="{{ $project->user->name }}" />
                            <span>{{ $project->user->name }}</span>
                        </div>
                    </div>

                    <!-- Project Budget and Deadline -->
                    <div class="flex flex-col items-end min-w-[140px] budget-deadline">
                        <div
                            class="bg-accent/10 text-accent font-semibold px-3 py-1 rounded-lg text-sm whitespace-nowrap">
                            ${{ number_format($project->budget) }}
                        </div>
                        @if($project->deadline)
                        <div class="text-sm text-gray-500 mt-1 whitespace-nowrap">
                            Deadline: {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Project Metadata -->
                <div class="flex flex-wrap gap-2 mb-3">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ $formattedProjectType }}
                    </span>
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ $formattedGenre }}
                    </span>
                    @foreach($formattedCollaborationTypes as $type)
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ $type }}
                    </span>
                    @endforeach
                </div>

                <!-- Project Description -->
                <div
                    class="text-gray-600 mb-4 project-description {{ !$showFullDescription && strlen($project->description) > 150 ? 'collapsed' : '' }}">
                    <p>{{ $project->description }}</p>

                    @if(strlen($project->description) > 150)
                    <button wire:click="toggleDescription"
                        class="text-primary text-sm font-medium hover:underline mt-1">
                        {{ $showFullDescription ? 'Show Less' : 'Read More' }}
                    </button>
                    @endif
                </div>

                <!-- Project Stats -->
                <div class="mt-auto flex flex-wrap items-center justify-between">
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            <span>{{ $project->pitches->count() }} {{ Str::plural('pitch', $project->pitches->count())
                                }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            <span>{{ $project->files->count() }} {{ Str::plural('file', $project->files->count())
                                }}</span>
                        </div>
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ $project->created_at->diffForHumans() }}</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <a href="{{ route('projects.show', $project) }}"
                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-focus transition-colors">
                        View Details
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>