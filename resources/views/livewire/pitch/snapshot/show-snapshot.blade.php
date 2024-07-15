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
                                <h2 class="text-3xl font-bold">{{ $pitch->user->name }}'s Pitch for <a
                                        href="{{ route('projects.show', $pitch->project) }}">"{{
                                        $pitch->project->name }}"</a></h2>
                                <div class="text-center">
                                    <p class="text-2xl">Version: <span class="font-semibold">{{ $snapshotData['version']
                                            }}</span></p>
                                </div>
                            </div>
                        </div>
                        @if (Auth::check() && Auth::id() === $pitch->project->user_id)
                        <div class="flex w-full">
                            @if ($pitchSnapshot->status === 'pending')
                            <button wire:click="accept" wire:confirm="Are you sure you want to accept this Pitch?"
                                class="block basis-1/3 bg-accent hover:bg-accent-focus tracking-tight text-xl text-center font-bold grow py-2 px-4 shadow-glow shadow-accent/50 hover:shadow-accent-focus/50 whitespace-nowrap">
                                Accept
                            </button>
                            <button wire:click="revise"
                                wire:confirm="Are you sure you want the user to revise this Pitch?"
                                class="block basis-1/3 bg-primary hover:bg-primary/80 text-white tracking-tight text-xl text-center font-bold grow py-2 px-4 shadow-glow shadow-primary hover:shadow-primary whitespace-nowrap">
                                Revise
                            </button>
                            <button wire:click="decline" wire:confirm="Are you sure you want to decline this Pitch?"
                                class="block basis-1/3 bg-decline hover:bg-decline/80 tracking-tight text-xl text-center text-gray-100 font-bold grow py-2 px-4 shadow-glow shadow-decline/30 hover:shadow-decline/30 whitespace-nowrap">
                                Decline
                            </button>
                            @elseif ($pitchSnapshot->status === 'accepted')
                            <div
                                class="block bg-accent tracking-tight text-xl text-center font-bold grow py-2 px-4 shadow-glow shadow-accent/50 whitespace-nowrap">
                                Pitch Accepted
                            </div>
                            @elseif ($pitchSnapshot->status === 'revise')
                            <div
                                class="block bg-primary tracking-tight text-xl text-center font-bold grow py-2 px-4 shadow-glow shadow-primary whitespace-nowrap text-white">
                                Pitch Needs Revised
                            </div>
                            @elseif ($pitchSnapshot->status === 'declined')
                            <div
                                class="block bg-decline tracking-tight text-xl text-center font-bold grow py-2 px-4 shadow-glow shadow-decline/30 whitespace-nowrap text-gray-100">
                                Pitch Declined
                            </div>
                            @endif
                        </div>
                        @else
                        @if ($pitchSnapshot->status === 'pending')
                        <div class="flex w-full">
                            <div
                                class="block bg-gray-200 tracking-tight text-xl text-center font-bold grow py-2 px-4 whitespace-nowrap text-gray-900">
                                Pending Review
                            </div>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
                <div class="shadow-lightGlow shadow-base-300 rounded-lg">
                    <!-- Project Details -->
                    <div class="">
                        <div class="px-2 md:px-6 py-4">
                            <div class="flex items-center w-full text-xl py-1">
                                <img class="h-8 w-8 rounded-full object-cover mr-3"
                                    src="{{ $pitch->user->profile_photo_url }}" alt="{{ $pitch->user->name }}" />
                                <span class="text-base max-w-xs truncate">{{ $pitch->user->name }}</span>
                            </div>
                            <div class="text-xl ml-1 font-semibold">Pitch Files</div>
                            <div class="bg-base-200/50 rounded-lg p-3">
                                <div class="space-y-1">
                                    @foreach($snapshotData['file_ids'] as $fileId)
                                    @php
                                    $file = \App\Models\PitchFile::find($fileId);
                                    @endphp
                                    @if($file)
                                    <div class="flex flex-col p-2 bg-gray-50 rounded shadow">
                                        <div class="flex flex-col md:flex-row justify-between items-end">
                                            <a href="{{ route('pitch-files.show', $file) }}"
                                                class="flex-1 place-self-start truncate text-base ml-2">
                                                <span class="font-bold">{{ $file->name() }}</span><span>.{{
                                                    $file->extension() }}</span>
                                            </a>

                                            <div class="flex items-center space-x-2">
                                                <a href="{{ route('pitch-files.show', $file) }}"
                                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                                                    View
                                                </a>
                                                @if($pitchSnapshot->isApproved())
                                                <a href="{{ asset('storage/' . $file->file_path) }}"
                                                    download="{{ $file->file_name }}"
                                                    class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-2 rounded text-sm">
                                                    Download
                                                </a>
                                                @endif
                                            </div>
                                        </div>
                                        @if($file->note)
                                        <div class="text-sm text-gray-700 ml-4">
                                            <strong>Note:</strong> {{ $file->note }}
                                        </div>
                                        @endif
                                        <div class="px-4 py-2">
                                            <audio controls class="w-full">
                                                <source src="{{ asset('storage/' . $file->file_path) }}">
                                                Your browser does not support the audio element.
                                            </audio>
                                        </div>
                                    </div>
                                    @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>