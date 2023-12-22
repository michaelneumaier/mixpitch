<div class="w-full md:w-1/2 lg:w-1/3  mb-4 px-1">
    <div class="transition-all shadow-base-200 shadow-glow hover:shadow-lg hover:shadow-base-300 rounded-lg">
        <div class="relative flex aspect-w-1 aspect-h-1 rounded-lg" wire:click="cardClickRoute()">
            <div class="absolute inset-0 bg-center bg-cover bg-no-repeat rounded-t-lg"
                style="background-image: url('{{ $project->image_path ? asset('storage/' . $project->image_path) : '' }}');">
                @if($isDashboardView)
                <div class="flex z-10 absolute bottom-2 right-2">
                    <form action="{{ route('projects.edit', $project) }}" method="GET" class="mr-2">
                        @csrf
                        <button type="submit" onclick="event.stopPropagation(); /* handle button click */"
                            class="btn-sm btn-warning btn-icon flex items-center space-x-1 px-2 py-2 shadow-md transition-shadow hover:shadow-lg rounded-lg">
                            <i class="fas fa-edit"></i>
                            <span>Edit</span>
                        </button>
                    </form>
                    <div x-data="{ open: false }">
                        <!-- Trigger -->
                        <button @click="open = true" onclick="event.stopPropagation(); /* handle button click */"
                            class="btn-sm btn-error btn-icon font-bold flex items-center space-x-1 px-2 py-2 shadow-md transition-shadow hover:shadow-lg rounded-lg">
                            <i class="fas fa-trash"></i>
                            <span>Delete</span>
                        </button>

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
                @endif
            </div>

            <div class="absolute top-0 right-0">
                <x-project-status-button :status="$project->status" type="top-right" />
            </div>
            @if($project->hasPreviewTrack())
            <div class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm"
                onclick="event.stopPropagation(); /* handle button click */">
                @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
            </div>
            @endif

        </div>
        <div class="rounded-t-xl shadow-innerGlow shadow-base-200 text-primary bg-cover bg-no-repeat rotate-180 bg-bottom -scale-x-100 "
            style="background-image: url('{{ $project->image_path ? asset('storage/' . $project->image_path) : '' }}');">
            <div
                class="rounded-b-md p-3 shadow-innerGlow shadow-base-200 backdrop-blur-sm rotate-180 -scale-x-100 bg-gradient-to-b from-base-200/60 to-base-200">
                <div class="flex w-full items-center text-xl mb-1">
                    <img class="h-7 w-7 rounded-full object-cover mr-1" src="{{ $project->user->profile_photo_url }}"
                        alt="{{ $project->user->name }}" />
                    <span class="text-base max-w-xs truncate">{{ $project->user->name
                        }}</span>
                </div>
                <h5 class="font-bold truncate"><a href="{{ route('projects.show', $project) }}" class="no-underline">
                        {{ $project->name }}
                    </a></h5>

                <h6 class="text-sm">{{ $project->genre }}</h6>
                @if($isDashboardView)
                <div class="text-sm space-y-1 border-t border-gray-200">
                    <div class="flex justify-between">
                        <span>Files Uploaded:</span>
                        <span class="font-semibold text-primary">{{ $project->files->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Mixes:</span>
                        <span class="font-semibold text-primary">{{ $project->mixes->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Last Updated:</span>
                        <span class="font-semibold text-primary">{{ $project->updated_at->format('F j, Y') }}</span>
                    </div>
                </div>


                @else

                @endif
                @if($isDashboardView)

                <a href="{{ route('projects.manage', $project)}}"
                    class="btn w-full bg-primary hover:bg-primary-focus text-white text-center">
                    Manage Project</a>
                @endif
            </div>
        </div>

    </div>
</div>