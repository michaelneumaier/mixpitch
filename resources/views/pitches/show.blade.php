@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12">
                <div class="flex flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative shrink-0 w-1/5 md:w-48">

                        <!-- Image that triggers the lightbox -->
                        @if($pitch->project->image_path)

                        <img @click="lightbox.isOpen = true" src="{{ asset('storage/' . $pitch->project->image_path) }}"
                            alt="{{ $pitch->project->name }}"
                            class=" md:aspect-square h-48 object-cover md:rounded-tl-lg cursor-pointer" />
                        @else
                        <div class="w-full md:aspect-square md:w-72 h-72 object-cover lg:rounded-tl-lg bg-base-200">
                        </div>
                        @endif
                        @if($pitch->project->hasPreviewTrack())
                        <div
                            class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm">
                            @livewire('audio-player', ['audioUrl' => $pitch->project->previewTrackPath(), 'isInCard' =>
                            true])
                        </div>
                        @endif

                        <!-- The actual lightbox overlay -->
                        @if($pitch->project->image_path)
                        <div x-cloak x-show="lightbox.isOpen"
                            class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-75 flex justify-center items-center z-50">
                            <img @click="lightbox.isOpen = false"
                                src="{{ asset('storage/' . $pitch->project->image_path) }}" alt="Lightbox image"
                                class="max-w-full max-h-full">

                            <!-- Close button -->
                            <button @click="lightbox.isOpen = false"
                                class="absolute top-4 right-4 text-white">Close</button>
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative pb-0 flex flex-grow flex-col items-center">
                        <div class="w-full flex px-1 py-2 flex-col justify-center flex-1">
                            <div class="p-2 text-center">
                                <h2 class="text-3xl font-bold mb-2">{{ $pitch->user->name }}'s Pitch for <a
                                        href="{{ route('projects.show', $pitch->project) }}">"{{
                                        $pitch->project->name }}"</a></h2>

                                <p>Status: <span class="font-semibold">{{ $pitch->getReadableStatusAttribute() }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="font-sans w-full border-t border-b border-base-200">
                    <div class="flex flex-row">
                        <div class="px-2 py-1 md:px-4 grow bg-base-200 text-right border-r border-base-200">
                            <div class="label-text whitespace-nowrap">Project Type</div>
                            <div class="font-bold">{{ Str::title($pitch->project->project_type) }}</div>

                        </div>
                        <div class="py-1 pb-0 px-4 bg-base-200/30 border-r border-base-200">
                            <div class="label-text">Budget</div>
                            <div class="font-bold">{{ $pitch->project->budget == 0 ? 'Free' :
                                '$'.number_format($pitch->project->budget, 0) }}</div>

                        </div>
                        <div class="py-1 pb-0 px-2 md:px-4 grow bg-base-200">
                            <div class="label-text">Deadline</div>
                            <div class="whitespace-nowrap font-bold">{{
                                \Carbon\Carbon::parse($pitch->project->deadline)->format('M d, Y') }}</div>

                        </div>
                    </div>
                </div>
                <div class="shadow-lightGlow shadow-base-300 rounded-lg">
                    <!-- Project Details -->
                    <div class="">
                        <div class="px-6 py-4">
                            @if($pitch->project->artist_name)
                            <div class="py-1">
                                <b>Artist</b>: {{ $pitch->project->artist_name }}
                            </div>
                            @endif
                            <div class="flex items-center w-full text-xl py-1">
                                <img class="h-8 w-8 rounded-full object-cover mr-3"
                                    src="{{ $pitch->project->user->profile_photo_url }}"
                                    alt="{{ $pitch->project->user->name }}" />
                                <span class="text-base max-w-xs truncate">{{ $pitch->project->user->name
                                    }} (Project Owner)</span>
                            </div>
                            @if (auth()->check() && auth()->id() === $pitch->user_id)
                            <livewire:pitch.component.manage-pitch :pitch="$pitch" />
                            @elseif ($pitch->status != 'in_progress')
                            <livewire:pitch.component.manage-pitch :pitch="$pitch" />
                            @endif

                            <!-- Second Row -->

                            <h3 class="text-xl font-semibold mb-4">Project Details:</h3>

                            <p class="mb-4">{{ $pitch->project->description }}</p>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-base-200 p-4 rounded-lg">


                                <!-- Collaboration Types if needed -->
                                @if($pitch->project->collaboration_type)
                                <div>
                                    <strong>Collaboration Type:</strong>
                                    <div class="flex flex-wrap -mx-2">
                                        @foreach($pitch->project->collaboration_type as $type => $value)
                                        @if($value)
                                        {{-- Every 3 items, close and open a new flex container to simulate a "new
                                        column" --}}
                                        @if($loop->iteration % 3 == 1)
                                        @if(!$loop->first)
                                    </div>
                                    @endif
                                    <div class="w-1/2 px-2 flex flex-col">
                                        @endif
                                        <div class="mt-2 flex items-center">
                                            <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                            <span>{{ Str::title(str_replace('_', ' ', $type)) }}</span>
                                        </div>
                                        @endif
                                        @endforeach
                                    </div> {{-- Close the last opened flex container --}}
                                </div>
                                @endif
                            </div>


                        </div>
                    </div>
                </div>


                <!-- File Downloads -->
                <div class="p-6">
                    <h4 class="flex items-center text-xl font-semibold mb-2">Project Files

                        @if($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                    </h4>
                    <div class="p-4">You don't have access to the project files.</div>
                    @elseif($pitch->project->files->isEmpty())
                    </h4>
                    <div class="p-4">No files available for download.</div>
                    @else
                    <a href="{{ route('projects.download', $pitch->project) }}"
                        class="bg-blue-500 hover:bg-blue-700 text-white font-semibold ml-2 py-1 px-2 rounded text-sm">
                        Download All Files
                    </a>
                    </h4>
                    <!-- Main Download All Button -->
                    <div class="border-4 border-base-300/40 rounded-lg">
                        @foreach($pitch->project->files as $file)
                        <div
                            class="flex flex-row items-center justify-between p-2 {{ $loop->even ? 'bg-base-200/10' : 'bg-base-200/60' }}">
                            <span class="truncate">{{ $file->file_name }}</span>
                            <div class="flex items-center space-x-3">
                                <span>{{ $file->formatted_size }}</span>
                                <a href="{{ asset('storage/' . $file->file_path) }}" download="{{ $file->file_name }}"
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold ml-2 py-1 px-2 rounded text-sm">
                                    Download
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>


                <!-- Additional Notes or Project Info -->
                @if ($pitch->project->notes)
                <div class="p-6 border-t border-base-300">
                    <h4 class="text-xl font-semibold mb-2">Additional Notes</h4>
                    <p>{{ $pitch->project->notes }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>


@endsection