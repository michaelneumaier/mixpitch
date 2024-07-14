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

                                <p>Version: <span class="font-semibold">{{ $snapshotData['version']
                                        }}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="shadow-lightGlow shadow-base-300 rounded-lg">
                    <!-- Project Details -->
                    <div class="">
                        <div class="px-2 md:px-6 py-4">
                            <div class="flex items-center w-full text-xl py-1">
                                <img class="h-8 w-8 rounded-full object-cover mr-3"
                                    src="{{ $pitch->user->profile_photo_url }}" alt="{{ $pitch->user->name }}" />
                                <span class="text-base max-w-xs truncate">{{ $pitch->user->name
                                    }}</span>
                            </div>
                            <div class="container">
                                <h1>Snapshot for Pitch: {{ $pitch->id }}</h1>
                                <h2>Snapshot Version: {{ $snapshotData['version'] }}</h2>
                                <h3>Files:</h3>
                                <div class="space-y-1">
                                    @foreach($snapshotData['file_ids'] as $fileId)
                                    @php
                                    $file = \App\Models\PitchFile::find($fileId);
                                    @endphp
                                    @if($file)
                                    <div class="flex flex-col p-2 bg-gray-100 rounded-lg shadow"
                                        x-data="{ showNotes: false, note: '{{ $file->note }}' }">
                                        <div class="flex flex-col md:flex-row justify-between items-end">
                                            <span class="flex-1 place-self-start truncate font-bold">{{ $file->file_name
                                                }}</span>

                                            <div class="flex items-center space-x-2">
                                                <template x-if="!showNotes">
                                                    <a href="#" @click.prevent="showNotes = true"
                                                        :class="{'border-green-500 text-green-500 hover:border-green-700 hover:text-green-700': !note, 'border-orange-500 text-orange-500 hover:border-orange-700 hover:text-orange-700': note}"
                                                        class="border py-1 px-2 rounded text-sm">
                                                        {{ $file->note ? 'Edit Note' : 'Add Note' }}
                                                    </a>
                                                </template>
                                                <template x-if="showNotes">
                                                    <div class="flex items-center space-x-2">
                                                        <button
                                                            @click.prevent="showNotes = false; note = '{{ $file->note }}'"
                                                            class="bg-red-500 hover:bg-red-700 text-white py-1 px-2 rounded text-sm">
                                                            Cancel
                                                        </button>
                                                        <button
                                                            @click.prevent="$wire.saveNote({{ $file->id }}, note); showNotes = false"
                                                            class="bg-green-500 hover:bg-green-700 text-white py-1 px-2 rounded text-sm">
                                                            Save
                                                        </button>
                                                    </div>
                                                </template>
                                                <a href="{{ route('pitch-files.show', $file) }}"
                                                    class="bg-gray-500 hover:bg-gray-700 text-white font-semibold py-1 px-2 rounded text-sm">
                                                    View
                                                </a>
                                                <a href="{{ asset('storage/' . $file->file_path) }}"
                                                    download="{{ $file->file_name }}"
                                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                        <template x-if="showNotes">
                                            <div class="flex">
                                                <textarea x-model="note"
                                                    class="mt-2 block w-full border border-gray-300 rounded-md shadow-sm"
                                                    rows="1"></textarea>
                                            </div>
                                        </template>
                                        <div x-show="!showNotes && note" class="text-sm text-gray-700 pl-2">
                                            <strong>Note:</strong> {{ $file->note }}
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>

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