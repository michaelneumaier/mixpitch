@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg overflow-hidden">
                <div class="flex flex-col lg:flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }"
                        class="relative w-full lg:aspect-square lg:w-fit lg:float-left md:mb-4 lg:mb-0">

                        <!-- Image that triggers the lightbox -->
                        @if($project->image_path)
                        <img @click="lightbox.isOpen = true" src="{{ asset('storage/' . $project->image_path) }}"
                            alt="{{ $project->name }}"
                            class="w-full aspect-square lg:w-64 h-64 object-cover lg:rounded-tl-lg cursor-pointer">
                        @else
                        <div class="w-full aspect-square lg:w-64 h-64 object-cover lg:rounded-tl-lg bg-base-200">
                        </div>
                        @endif

                        <div class="lg:hidden">
                            <livewire:status-button :status="$project->status" type="top-right" />
                        </div>
                        <!-- Edit/Delete if User's Project-->
                        @if(auth()->check() && $project->isOwnedByUser(auth()->user()))
                        <div class="absolute bottom-0 right-2 flex items-start py-2">
                            <form action="{{ route('projects.edit', $project) }}" method="GET" class="mr-2">
                                @csrf
                                <button type="submit" onclick="event.stopPropagation(); /* handle button click */"
                                    class="btn-sm btn-warning btn-icon flex text-black items-center space-x-1 px-2 py-2 shadow-md transition-shadow hover:shadow-lg rounded-lg">
                                    <i class="fas fa-edit"></i>
                                    <span>Edit</span>
                                </button>
                            </form>
                            <div x-data="{ open: false }">
                                <!-- Trigger -->
                                <button @click="open = true"
                                    onclick="event.stopPropagation(); /* handle button click */"
                                    class="btn-sm btn-error btn-icon text-white font-bold flex items-center space-x-1 px-2 py-2 shadow-md transition-shadow hover:shadow-lg rounded-lg">
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
                                                <h3 class="text-lg leading-6 font-medium text-gray-900"
                                                    id="modal-title">
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

                        <!-- The actual lightbox overlay -->
                        @if($project->image_path)
                        <div x-cloak x-show="lightbox.isOpen"
                            class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-75 flex justify-center items-center z-50">
                            <img @click="lightbox.isOpen = false" src="{{ asset('storage/' . $project->image_path) }}"
                                alt="Lightbox image" class="max-w-full max-h-full">

                            <!-- Close button -->
                            <button @click="lightbox.isOpen = false"
                                class="absolute top-4 right-4 text-white">Close</button>
                        </div>
                        @endif

                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative p-4 flex flex-grow flex-row items-center lg:ml-1/3">

                        <!-- Content here will be vertically and horizontally centered within the parent div -->

                        <!-- First Row -->
                        <div class="w-full">
                            <h3 class="text-4xl">
                                {{ $project->name }}
                            </h3>
                            @if($project->artist_name)
                            <div class="py-2">
                                <b>Artist</b>: {{ $project->artist_name }}
                            </div>
                            @endif
                            <!-- Second Row -->
                            <div class="flex items-center w-full text-xl pb-2">
                                <img class="h-10 w-10 rounded-full object-cover mr-3"
                                    src="{{ $project->user->profile_photo_url }}" alt="{{ $project->user->name }}" />
                                <span>{{ $project->user->name }}</span>
                            </div>


                            <!-- Additional Information -->
                            <div class="flex justify-center w-full">
                                <div
                                    class="stats bg-base-200 shadow-lightGlow shadow-base-200 border-2 border-base-300">
                                    <div class="stat py-2">
                                        <div class="stat-title ">Project Type</div>
                                        <div class="stat-value text-xl">{{ Str::title($project->project_type) }}</div>
                                    </div>
                                    <div class="stat py-2">
                                        <div class="stat-title">Budget</div>
                                        <div class="stat-value text-xl">{{ $project->budget == 0 ? 'Free' :
                                            '$'.number_format($project->budget, 2) }}</div>
                                    </div>
                                    <div class="stat py-2">
                                        <div class="stat-title">Deadline</div>
                                        <div class="stat-value text-xl">{{
                                            \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            </div>



                            <!-- Status Button for larger screens -->
                            <div class="hidden lg:block">
                                <livewire:status-button :status="$project->status" type="top-right" />
                            </div>
                        </div>
                    </div>

                </div>
                <div>
                    <div class="flex justify-between items-start text-xl mb-4 p-8">
                        <span> {{ $project->description }}</span>
                    </div>
                    @if($project->hasPreviewTrack())
                    <div class="w-1/2">
                        <label class="block label-text -m-8 ml-12">Preview Track</label>
                        <div class="flex-grow justify-between items-start text-xl mb-4 p-8">

                            <span>

                                @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isPreviewTrack'
                                =>
                                true])

                            </span>
                        </div>
                    </div>
                    @endif

                    <label class="block label-text -m-8 ml-12">Notes:</label>
                    <div class="flex justify-between items-start m-8 p-4 border-2 border-base-300 bg-base-200">

                        <span>{{ $project->notes }}</span>
                    </div>
                    <div class="space-y-2">
                        @if($project->collaboration_type)
                        <label class="block label-text ml-12">Looking for collaboration with:</label>
                        @foreach($project->collaboration_type as $type => $value)
                        @if($value)
                        <div class="flex items-center p-2 bg-gray-200">
                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                            <span class="font-medium text-gray-700">{{ Str::title(str_replace('_', ' ', $type))
                                }}</span>
                        </div>
                        @endif
                        @endforeach
                        @endif
                    </div>

                    <!-- Content Below the Image and Details -->
                    <div class="clear-left bg-dark bg-opacity-50 p-4">
                        <div class="space-y-4">
                            @livewire('project-tracks', ['project' => $project])

                            <div class="flex space-x-4 mt-3">
                                @if(!auth()->check()) <!-- Check if user is not logged in -->
                                <div class="tooltip tooltip-top" data-tip="Please login in to download files.">
                                    <button class="btn btn-primary">
                                        Download All Files
                                    </button>
                                </div>
                                <div class="tooltip tooltip-top" data-tip="Please login in to download files.">
                                    <button class="btn btn-primary">
                                        Submit Mix
                                    </button>
                                </div>


                                @else
                                <a href="{{ route('projects.download', $project) }}" class="btn btn-primary">
                                    Download All Files
                                </a>
                                <a href="{{ route('mixes.create', $project) }}" class="btn btn-primary">
                                    Submit Mix
                                </a>
                                @endif
                            </div>



                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>

@endsection