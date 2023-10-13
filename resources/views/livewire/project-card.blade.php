<div class="w-full lg:w-1/4 md:w-1/2 mb-4 px-1" onclick="location.href='{{ route('projects.show', $project) }}';">
    <div class="bg-white shadow-xl shadow-neutral-700 border rounded-lg">
        <div class="relative aspect-w-1 aspect-h-1 rounded-lg">
            <div class="absolute inset-0 bg-center bg-cover bg-no-repeat rounded-lg"
                style="background-image: url('{{ $project->image_path ? asset('storage' . $project->image_path) : 'https://via.placeholder.com/150' }}');">
                @if($isDashboardView)
                <div class="flex z-10 absolute bottom-2 right-2">
                    <form action="{{ route('projects.edit', $project) }}" method="GET" class="mr-2">
                        @csrf
                        <button type="submit" onclick="event.stopPropagation(); /* handle button click */"
                            class="btn-sm bg-yellow-300 border">
                            Edit
                        </button>
                    </form>
                    <div x-data="{ open: false }">
                        <!-- Trigger -->
                        <button @click="open = true" onclick="event.stopPropagation(); /* handle button click */"
                            class="btn-sm bg-red-400 border">
                            Delete
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
                <livewire:status-button :status="$project->status" type="top-right" />
            </div>

        </div>
        <div class="p-3">

            <h5 class="font-bold mb-1"><a href="{{ route('projects.show', $project) }}" class="no-underline">
                    {{ $project->name }}
                </a></h5>
            <h6 class="text-sm text-gray-600">{{ $project->genre }}</h6>
            @if($isDashboardView)
            <div class="text-sm">
                <div>
                    Files Uploaded: <span class="font-semibold">{{ $project->files->count()
                        }}</span>
                </div>
                <div>
                    Mixes: <span class="font-semibold">{{ $project->mixes->count() }}</span>
                </div>
                <div>
                    Last Updated: <span class="font-semibold">{{ $project->updated_at->format('F
                        j, Y') }}</span>
                </div>
            </div>

            @else
            <p class="text-sm mt-2">Uploaded by: {{ $project->user->name }}</p>
            @endif

        </div>
    </div>
</div>