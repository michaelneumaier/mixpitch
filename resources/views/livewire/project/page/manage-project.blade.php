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
                                class="absolute top-4 right-4 text-white">Close</button>
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative pb-0 flex flex-grow flex-col items-center lg:ml-1/3">

                        <!-- Content here will be vertically and horizontally centered within the parent div -->

                        <!-- First Row -->
                        <div class="w-full flex px-4 py-2 flex-col justify-center flex-1">
                            <a href="{{ route('projects.show', $project) }}" class="text-3xl py-1 leading-8">

                                {{ $project->name }}
                            </a>
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
                            <a href="{{ route('projects.edit', $project)}}" class="block bg-warning/80 hover:bg-warning tracking-tight text-xl text-center font-bold
                                                    grow py-2 px-4 shadow-accent hover:shadow-accent-focus
                                                    whitespace-nowrap">
                                <i class="fas fa-edit"></i>
                                Edit Project Details</a>
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

                <div class="p-2 md:p-4 grid md:grid-cols-2 gap-4">
                    <div class="flex w-full flex-col md:col-span-2 bg-base-200 rounded-lg overflow-hidden">
                        <div class="p-4 pl-6 text-xl font-bold bg-base-300 rounded-t-lg flex items-center">
                            <i class="fas fa-paper-plane w-5 text-center mr-3"></i>Pitches
                        </div>
                        <div class="flex flex-col">
                            @forelse($project->pitches as $pitch)
                            <div wire:key="pitch-{{$pitch->id}}" wire:ignore
                                class="flex flex-row w-full justify-between items-stretch rounded {{ $loop->even ? 'bg-base-300/30' : 'bg-base-100/50' }}">
                                <a href="{{ route('pitches.show', $pitch->id) }}"
                                    class="flex flex-row items-center flex-grow p-3">
                                    <div class="flex flex-col">
                                        <div class="flex items-center w-full text-xl">
                                            <img class="h-8 w-8 rounded-full object-cover mr-3"
                                                src="{{ $pitch->user->profile_photo_url }}"
                                                alt="{{ $pitch->user->name }}" />
                                            <span class="text-base max-w-xs truncate">{{ $pitch->user->name }}</span>
                                        </div>
                                    </div>
                                </a>
                                <div
                                    class="flex items-center h-full p-3 bg-base-300 {{ $loop->even ? 'bg-base-300' : 'bg-base-300/50' }} {{ $loop->last ? 'rounded-br-lg' : '' }} min-w-[170px]">
                                    <livewire:pitch.component.update-pitch-status :key="'pitch-'.$pitch->id" :$pitch />
                                </div>
                                <span>
                                    <!-- for($i = 1; $i <= 10; $i++) 
                                            if($i <= $mix->rating)
                                            ★
                                            else
                                            ☆
                                            endif
                                        endfor -->
                                </span>
                            </div>
                            @empty
                            <div class="p-4">You have no pitches, yet.</div>
                            @endforelse
                        </div>
                    </div>
                    <div class="flex w-full flex-col md:col-span-2 bg-base-200 rounded-lg overflow-hidden">
                        <div class="p-4 pl-6 text-xl font-bold bg-base-300 rounded-t-lg flex items-center">
                            <i class="fas fa-music w-5 text-center mr-3"></i>Tracks
                        </div>
                        @if($isUploading)
                        <!-- File Upload Form -->
                        <div x-data="{ isUploading: false }"
                            x-on:drop.prevent="isUploading = false; $refs.fileInput.files = $event.dataTransfer.files"
                            x-on:dragover.prevent="isUploading = true" x-on:dragleave.prevent="isUploading = false"
                            class="border-2 border-base-300 border-dashed" :class="{'bg-gray-100': isUploading}">

                            <input type="file" wire:model="uploadedFiles" multiple class="hidden" x-ref="fileInput">

                            <div @click="$refs.fileInput.click()" class="cursor-pointer text-center p-4">
                                Drag files here or click to upload
                            </div>

                            @if ($uploadedFiles)
                            <div class="mt-2 p-4">
                                @foreach ($uploadedFiles as $uploadedFile)
                                <div class="truncate">{{ $uploadedFile->getClientOriginalName() }}</div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @else
                        @if($project->files->isEmpty())
                        <div class="p-4">There are no files uploaded.</div>
                        @else
                        <div class="flex flex-col p-2">
                            @if(!$project->preview_track)
                            <div class="p-4 font-bold hidden">Preview Track not selected</div>
                            @endif
                            <div class="border-4 border-base-300/40 rounded-lg">
                                @foreach($project->files as $file)
                                <div x-data="{ showTooltip: false }" wire:key="file-{{$file->id}}"
                                    class="flex flex-row items-center justify-between p-2 {{ $loop->even ? 'bg-base-300/30' : 'bg-base-100/50' }} hover:bg-base-100">
                                    <button wire:click="togglePreviewTrack({{ $file->id }})" class="mr-2">
                                        <i
                                            class="fas fa-play {{ $file->id == $project->preview_track ? 'text-green-400' : 'text-gray-400' }} hover:text-green-400 cursor-pointer"></i>
                                    </button>
                                    <div class="relative flex-1 truncate items-center">
                                        <span @click="showTooltip = !showTooltip" class="truncate">{{ $file->file_name
                                            }}</span>
                                    </div>
                                    <div x-show="showTooltip" @click.away="showTooltip = false"
                                        class="absolute z-10 break-all w-auto p-2 mr-2 bg-black text-white text-sm rounded-md shadow-lg"
                                        x-text="'{{ $file->file_name }}'">
                                    </div>
                                    <div class="flex items-center space-x-2">

                                        <span>{{ $file->formatted_size }}</span>

                                        <button
                                            x-on:click="if (confirm('Are you sure you want to delete this file?')) @this.call('deleteFile', {{ $file->id }})"
                                            class="ml-2">
                                            <i
                                                class="fas fa-trash-alt text-red-500 hover:text-red-600 cursor-pointer"></i>
                                        </button>
                                    </div>
                                </div>
                                @endforeach
                            </div>

                        </div>
                        @endif
                        @endif
                        <div class="flex">
                            <button wire:click="uploadFiles"
                                class="btn grow flex-row rounded-t-none border-0 bg-success/90 hover:bg-success text-white {{ $isUploading ? 'rounded-br-none' : 'hidden' }}">
                                Upload
                            </button>
                            <button wire:click="$toggle('isUploading')"
                                class="btn grow flex-row rounded-t-none border-0 bg-primary hover:bg-primary-focus text-white text-center {{ $isUploading ? 'grow-0 rounded-bl-none' : 'bg-primary hover:bg-primary-focus' }}">
                                {{ $isUploading ? 'Finish' : 'Upload Files' }}
                            </button>
                        </div>
                    </div>




                    <div class="flex flex-col bg-base-200 rounded-lg">
                        @if($project->status == 'unpublished')
                        <span class="flex-row grow text-xl font-bold p-4">You are ready to publish your project!</span>
                        <div class="btn flex-row rounded-t-none bg-primary hover:bg-primary-focus text-white text-center"
                            wire:click="publish()">
                            Publish</div>
                        @elseif($project->status == 'published' || $project->status == 'open')
                        <span class="flex-row grow text-xl font-bold p-4">You may unpublish your project at any
                            time.</span>
                        <div class="btn flex-row rounded-t-none bg-primary hover:bg-primary-focus text-white text-center"
                            wire:click="unpublish()">
                            Unpublish</div>
                        @endif
                    </div>

                    <div class="flex flex-col bg-base-200 rounded-lg" x-data="{ open: false }">

                        <span class="flex-row grow text-xl font-bold p-4">Permanently delete project and all
                            files</span>
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

                        <div class="btn bg-error/80 hover:bg-error flex-row rounded-t-none text-white text-center"
                            @click="open = true" onclick="event.stopPropagation(); /* handle button click */">
                            Delete Project</div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>