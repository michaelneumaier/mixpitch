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

                        <img @click="lightbox.isOpen = true" src="{{ asset('storage/' . $project->image_path) }}"
                            alt="{{ $project->name }}"
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
                            <img @click="lightbox.isOpen = false" src="{{ asset('storage/' . $project->image_path) }}"
                                alt="Lightbox image" class="max-w-full max-h-full">

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
                                <span class="text-base max-w-xs truncate">{{ $project->user->name
                                    }}</span>
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
                            <a href="{{ route('pitches.create', $project) }}" class="block bg-accent hover:bg-accent-focus tracking-tight text-xl text-center font-bold
                                                        grow py-2 px-4 shadow-glow shadow-accent hover:shadow-accent-focus
                                                        whitespace-nowrap">Start
                                Your Pitch</a>
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
                                <div class="py-1 pb-0 px-4 bg-base-200/30 border-r border-base-200">
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
                <div class="m-4 flex justify-center">
                    @if($project->collaboration_type)
                        <div class="flex flex-wrap justify-center gap-2 w-full">
                            @foreach($project->collaboration_type as $type => $value)
                                @if($value)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-base-200 text-base-content cursor-default select-none border border-base-300 hover:border-base-300 hover:bg-base-300 transition-colors duration-200">
                                        <i class="fas fa-check-circle text-success mr-2"></i>
                                        {{ Str::title(str_replace('_', ' ', $type)) }}
                                    </span>
                                @endif
                            @endforeach
                        </div>
                    @endif
                </div>
                <div>
                    <div class="flex justify-between items-start text-xl mb-4 px-6 py-2 ">
                        <span class="whitespace-pre-wrap">{{ $project->description }}</span>
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


                    
                    @if ($project->notes)
                    <label class="block label-text mt-8 ml-12">Notes:</label>
                    <div class="flex justify-between items-start m-8 mt-1 p-4 border border-base-200 bg-base-200/50">

                        <span class="whitespace-pre-wrap">{{ $project->notes }}</span>
                    </div>
                    @endif

                    <!-- Content Below the Image and Details -->
                    <div class="clear-left bg-dark bg-opacity-50 p-4">
                        <div class="space-y-4">
                            <div>
                                <label class="block label-text ml-8">Files:</label>
                                <div class="border-4 border-base-300/40 rounded-lg">
                                    @if($project->files->isEmpty())
                                    <div class="p-4">There are no files uploaded.</div>
                                    @else
                                    @foreach($project->files as $file)
                                    <div x-data="{ showTooltip: false }"
                                        class="flex flex-row items-center justify-between p-2 {{ $loop->even ? 'bg-base-200/10' : 'bg-base-200/60' }} {{ $loop->first ? 'rounded-t-md' : '' }} {{ $loop->last ? 'rounded-b-md' : '' }}">
                                        <div class="flex flex-1 items-center truncate">
                                            <span @click="showTooltip = !showTooltip" class="truncate">{{
                                                $file->file_name
                                                }}</span>
                                        </div>
                                        <div x-show="showTooltip" @click.away="showTooltip = false"
                                            class="absolute z-10 break-all w-auto p-2 mr-2 bg-black text-white text-sm rounded-md shadow-lg"
                                            x-text="'{{ $file->file_name }}'">
                                        </div>
                                        <div class="flex items-center">
                                            <span>{{ $file->formatted_size }}</span>


                                        </div>
                                    </div>
                                    @endforeach
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
    </div>
</div>

@endsection