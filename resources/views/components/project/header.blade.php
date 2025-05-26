@props(['project', 'hasPreviewTrack' => false, 'showEditButton' => true])

<div class="shadow-base-300 mb-6 flex flex-col rounded-lg border-transparent shadow-2xl sm:mb-12">
    <div class="shadow-lightGlow shadow-base-300 flex h-full flex-col md:flex-row">
        <!-- Project Image -->
        <div x-data="{ lightbox: { isOpen: false } }" class="project-header-image relative shrink-0 md:w-72">
            @if ($project->image_path)
                <img @click="lightbox.isOpen = true" src="{{ $project->imageUrl }}"
                    class="mx-auto max-h-56 w-full cursor-pointer rounded-lg object-cover shadow-lg transition-all duration-200 hover:shadow-xl md:mx-0 md:max-h-none md:w-auto"
                    alt="{{ $project->name }}">
            @else
                <div class="bg-base-200 flex h-56 w-full items-center justify-center object-cover sm:h-72 md:aspect-square md:w-72 lg:rounded-tl-lg">
                    <i class="fas fa-music text-base-300 text-5xl sm:text-6xl"></i>
                </div>
            @endif
            
            @if ($hasPreviewTrack)
                <div class="absolute -bottom-1 -left-1 right-auto top-auto z-50 flex aspect-auto h-auto w-auto text-sm">
                    @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
                </div>
            @endif

            <!-- Lightbox for image -->
            @if ($project->image_path)
                <div x-cloak x-show="lightbox.isOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 transition-all duration-300">
                    <div class="relative mx-auto max-w-4xl">
                        <img class="max-h-[90vh] max-w-[90vw] rounded object-contain shadow-2xl" src="{{ $project->imageUrl }}" alt="{{ $project->name }}">
                        <button @click="lightbox.isOpen = false" class="absolute right-4 top-4 rounded-full bg-gray-900 bg-opacity-50 p-2 text-white transition-colors duration-200 hover:bg-opacity-75">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Project Details -->
        <div class="project-header-details flex flex-1 flex-col justify-between overflow-x-auto md:h-72">
            <div class="flex flex-col p-3 sm:p-4 md:p-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <a href="{{ route('projects.show', $project) }}"
                        class="hover:text-primary break-words text-center text-xl font-bold text-gray-800 transition-colors sm:text-2xl md:text-left md:text-3xl">
                        {{ $project->name }}
                    </a>
                </div>

                @if ($project->artist_name)
                    <div class="flex items-center justify-center py-1 md:justify-start">
                        <span class="mr-2 font-semibold text-gray-700">Artist:</span>
                        <span class="text-gray-900">{{ $project->artist_name }}</span>
                    </div>
                @endif

                <!-- User info -->
                <div class="mt-2 flex w-full items-center justify-center md:justify-start">
                    <img class="border-base-300 mr-3 h-10 w-10 rounded-full border-2 object-cover"
                        src="{{ $project->user->profile_photo_url }}" alt="{{ $project->user->name }}" />
                    <div class="flex flex-col">
                        <span class="max-w-xs truncate text-base font-medium">{{ $project->user->name }}</span>
                        <span class="text-xs text-gray-600">Project Owner</span>
                    </div>
                </div>
            </div>

            <div class="mt-auto">
                @if($showEditButton)
                <!-- Edit button -->
                <div class="mt-4 flex w-full">
                    <a href="{{ route('projects.edit', $project) }}"
                        class="bg-warning/80 hover:bg-warning shadow-accent hover:shadow-accent-focus block grow whitespace-nowrap px-4 py-2 text-center text-xl font-bold tracking-tight transition-colors">
                        <i class="fas fa-edit mr-2"></i> Edit Project Details
                    </a>
                </div>
                @endif

                <!-- Project metadata -->
                <div class="border-base-200 w-full overflow-x-auto border-b border-t font-sans">
                    <div class="project-metadata-row flex min-w-full flex-row">
                        <div class="bg-base-200/70 border-base-200 flex-1 border-r px-2 py-1 text-center sm:text-right md:px-4">
                            <div class="label-text whitespace-nowrap text-xs text-gray-600 sm:text-sm">Project Type</div>
                            <div class="text-sm font-bold sm:text-base">{{ Str::title($project->project_type) }}</div>
                        </div>
                        <div class="bg-base-200/30 border-base-200 flex-1 border-r px-2 py-1 pb-0 text-center md:px-4">
                            <div class="label-text text-xs text-gray-600 sm:text-sm">Budget</div>
                            <div class="text-sm font-bold sm:text-base">
                                @if (is_numeric($project->budget) && $project->budget > 0)
                                    ${{ number_format((float) $project->budget, 0) }}
                                @elseif(is_numeric($project->budget) && $project->budget == 0)
                                    Free
                                @else
                                    Price TBD
                                @endif
                            </div>
                        </div>
                        <div class="bg-base-200/70 flex-1 px-2 py-1 pb-0 text-center sm:text-left md:px-4">
                            <div class="label-text text-xs text-gray-600 sm:text-sm">Deadline</div>
                            <div class="whitespace-nowrap text-sm font-bold sm:text-base">
                                {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 