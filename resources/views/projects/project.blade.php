@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12">
                <div class="flex flex-col md:flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative shrink-0 md:w-72">

                        <!-- Image that triggers the lightbox -->
                        @if($project->image_path)

                        <img @click="lightbox.isOpen = true" src="{{ $project->imageUrl }}" alt="{{ $project->name }}"
                            class="w-full md:aspect-square h-72 object-cover md:rounded-tl-lg cursor-pointer" />
                        @else
                        <div class="w-full md:aspect-square md:w-72 h-72 object-cover lg:rounded-tl-lg bg-base-200">
                        </div>
                        @endif
                        @if($project->hasPreviewTrack())
                        <div
                            class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm">
                            @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
                        </div>
                        @endif



                        <div class="md:hidden absolute top-0 right-0">
                            <x-project-status-button :status="$project->status" type="top-right" />
                        </div>


                        <!-- The actual lightbox overlay -->
                        @if($project->image_path)
                        <div x-cloak x-show="lightbox.isOpen"
                            class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-75 flex justify-center items-center z-50">
                            <img @click="lightbox.isOpen = false" src="{{ $project->imageUrl }}" alt="Lightbox image"
                                class="max-w-full max-h-full">

                            <!-- Close button -->
                            <button @click="lightbox.isOpen = false"
                                class="absolute top-4 right-4 text-white">Close</button>
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative pb-0 flex flex-grow flex-col items-center lg:ml-1/3">

                        <!-- Content here will be vertically and horizontally centered within the parent div -->

                        <!-- First Row -->
                        <div class="w-full flex px-4 py-2 flex-col justify-center flex-1">
                            <h3 class="text-3xl py-1 leading-8">
                                {{ $project->name }}
                            </h3>
                            @if($project->artist_name)
                            <div class="py-1">
                                <b>Artist</b>: {{ $project->artist_name }}
                            </div>
                            @endif
                            <!-- Second Row -->
                            <div class="flex items-center w-full text-xl">
                                <img class="h-8 w-8 rounded-full object-cover mr-3"
                                    src="{{ $project->user->profile_photo_url }}" alt="{{ $project->user->name }}" />
                                <span class="text-base max-w-xs truncate">
                                    <x-user-link :user="$project->user" />
                                </span>
                            </div>



                            <!-- Additional Information -->
                            <!-- <div class="flex flex-row justify-center w-full place-self-end">
                                <div class="flex flex-row w-full">
                                    <div class="py-2 grow">
                                        <div class="label-text">Project Type</div>
                                        <div class="text-xl font-bold">{{ Str::title($project->project_type) }}</div>
                                    </div>
                                    <div class="py-2 grow">
                                        <div class="lebel-text">Budget</div>
                                        <div class="text-xl font-bold">{{ $project->budget == 0 ? 'Free' :
                                            '$'.number_format($project->budget, 2) }}</div>
                                    </div>
                                    <div class="py-2 grow">
                                        <div class="label-text">Deadline</div>
                                        <div class="text-xl font-bold">{{
                                            \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            </div> -->



                            <!-- Status Button for larger screens -->
                            <div class="hidden md:block">
                                <div class="absolute top-0 right-0">
                                    <x-project-status-button :status="$project->status" type="top-right" />
                                </div>
                            </div>
                        </div>
                        <div class="flex w-full">
                            @if($userPitch)
                            <a href="{{ route('pitches.show', $userPitch->id) }}" class="block bg-accent hover:bg-accent-focus tracking-tight text-xl text-center font-bold
                                                        grow py-2 px-4 shadow-glow shadow-accent hover:shadow-accent-focus
                                                        whitespace-nowrap">Manage
                                Your Pitch</a>
                            @else
                            <button onclick="openPitchTermsModal()" class="block bg-accent hover:bg-accent-focus tracking-tight text-xl text-center font-bold
                                                        grow py-2 px-4 shadow-glow shadow-accent hover:shadow-accent-focus
                                                        whitespace-nowrap">Start
                                Your Pitch</button>
                            @endif

                            @if(auth()->check() && $project->isOwnedByUser(auth()->user()))
                            <a href="{{ route('projects.manage', $project)}}" class="block bg-primary hover:bg-primary-focus text-white tracking-tight text-xl text-center font-bold
                            grow py-2 px-4 shadow-accent hover:shadow-accent-focus
                            whitespace-nowrap">
                                Manage Project</a>
                            @endif
                        </div>
                        <div class="font-sans w-full border-t border-b border-base-200">
                            <div class="flex flex-row">
                                <div class="px-2 py-1 md:px-4 grow bg-base-200/70 text-right border-r border-base-200">
                                    <div class="label-text whitespace-nowrap">Project Type</div>
                                    <div class="font-bold">{{ Str::title($project->project_type) }}</div>

                                </div>
                                <div class="py-1 pb-0 px-4 bg-base-200/30 border-r border-base-200 hidden">
                                    <div class="label-text">Budget</div>
                                    <div class="font-bold">{{ $project->budget == 0 ? 'Free' :
                                        '$'.number_format($project->budget, 0) }}</div>

                                </div>
                                <div class="py-1 pb-0 px-2 md:px-4 grow bg-base-200/70">
                                    <div class="label-text">Deadline</div>
                                    <div class="whitespace-nowrap font-bold">{{
                                        \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}</div>

                                </div>
                            </div>
                        </div>

                    </div>


                </div>
                @if($project->collaboration_type && count(array_filter($project->collaboration_type)) > 0)
                <div class="m-4">
                    <div class="bg-base-200/30 p-4 rounded-lg">
                        <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                            <i class="fas fa-handshake text-indigo-500 mr-2"></i>Looking For Collaboration In
                        </h3>
                        <div class="flex flex-wrap gap-2 mt-3">
                            @foreach($project->collaboration_type as $type => $value)
                            @if($value)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary/10 text-primary">
                                <i class="fas {{ 
                                                            $type == 'mixing' ? 'fa-sliders-h' : 
                                                            ($type == 'mastering' ? 'fa-compact-disc' : 
                                                            ($type == 'production' ? 'fa-music' : 
                                                            ($type == 'vocals' ? 'fa-microphone' : 
                                                            ($type == 'instruments' ? 'fa-guitar' : 'fa-tasks')))) 
                                                        }} mr-1.5"></i>
                                {{ Str::title(str_replace('_', ' ', $type)) }}
                            </span>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                <div>
                    <!-- Budget Section -->
                    <div class="px-6 py-2">
                        <div class="bg-base-200/30 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Budget
                            </h3>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-gray-800">
                                    {{ $project->budget == 0 ? 'Free Project' : '$'.number_format($project->budget, 0)
                                    }}
                                </span>
                                @if($project->budget > 0)
                                <span class="ml-2 text-gray-600">USD</span>
                                @endif
                            </div>
                            <p class="text-gray-600 mt-2">
                                @if($project->budget == 0)
                                This is a free collaboration project. No payment is expected.
                                @else
                                This is the budget allocated for this project. The final payment may vary based on
                                project requirements and
                                agreement with the collaborator.
                                @endif
                            </p>
                        </div>
                    </div>
                    <!-- Description Section -->
                    <div class="px-6 py-2">
                        <div class="bg-base-200/30 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>Description
                            </h3>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $project->description }}</p>
                        </div>
                    </div>

                    <div class="w-full hidden">
                        <label class="block label-text -m-8 ml-12">{{ pathinfo($project->previewTrackPath(),
                            PATHINFO_FILENAME) }}</label>
                        <div class="flex-grow justify-between items-start text-xl mb-4 p-8">

                            <span>

                                @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isPreviewTrack'
                                =>
                                true])

                            </span>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    @if ($project->notes)
                    <div class="px-6 py-2">
                        <div class="bg-base-200/30 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>Additional Notes
                            </h3>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $project->notes }}</p>
                        </div>
                    </div>
                    @endif

                    <!-- Files Section -->
                    <div class="px-6 py-2">
                        <div class="bg-base-200/30 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-music text-purple-500 mr-2"></i>Project Files
                            </h3>

                            @if($project->files->isEmpty())
                            <div class="p-4 text-center text-gray-500 italic">
                                <p>No files have been uploaded for this project</p>
                            </div>
                            @else
                            <div class="divide-y divide-base-300/50">
                                @foreach($project->files as $file)
                                <div
                                    class="flex items-center justify-between py-3 px-2 {{ $loop->even ? 'bg-base-200/30' : '' }}">
                                    <div class="flex items-center truncate">
                                        <i class="fas fa-file-audio text-gray-500 mr-3"></i>
                                        <span class="font-medium text-gray-800 truncate" title="{{ $file->file_name }}">
                                            {{ $file->file_name }}
                                        </span>
                                    </div>
                                    <div class="flex items-center ml-4">
                                        <span class="text-sm text-gray-500">{{ $file->formatted_size }}</span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include the pitch terms modal component -->
<x-pitch-terms-modal :project="$project" />

@endsection