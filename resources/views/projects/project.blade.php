@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg overflow-hidden">

                <!-- Project Image on the Left -->
                <div x-data="{ lightbox: { isOpen: false } }" class="relative lg:w-1/3 lg:float-left md:mb-4 lg:mb-0">

                    <!-- Image that triggers the lightbox -->
                    <img @click="lightbox.isOpen = true" src="{{ asset('storage/' . $project->image_path) }}"
                        alt="{{ $project->name }}" class="w-full h-56 object-cover lg:rounded-tl-lg cursor-pointer">

                    <div class="lg:hidden">
                        <livewire:status-button :status="$project->status" type="top-right" />
                    </div>

                    <!-- The actual lightbox overlay -->
                    <div x-cloak x-show="lightbox.isOpen"
                        class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-75 flex justify-center items-center z-50">
                        <img @click="lightbox.isOpen = false" src="{{ asset('storage/' . $project->image_path) }}"
                            alt="Lightbox image" class="max-w-full max-h-full">

                        <!-- Close button -->
                        <button @click="lightbox.isOpen = false"
                            class="absolute top-4 right-4 text-white">Close</button>
                    </div>

                </div>


                <!-- Project Details on the Right -->
                <div class="relative p-4 flex flex-col justify-between items-start lg:ml-1/3">

                    <!-- First Row -->
                    <div class="flex justify-between items-start w-full mb-2">
                        <h3 class="text-4xl">
                            {{ $project->name }}
                            @if(auth()->check() && $project->isOwnedByUser(auth()->user()))
                            <a href="{{ route('projects.edit', $project) }}"
                                class="btn btn-info btn-sm bg-sky-400 ml-3">Edit</a>
                            @endif
                        </h3>
                        <div class="hidden lg:block">
                            <livewire:status-button :status="$project->status" type="top-right" />
                        </div>

                    </div>

                    <!-- Second Row -->
                    <div class="flex justify-between items-start w-full">
                        <span> {{ $project->user->name }}</span>
                    </div>
                </div>

                <div class="flex justify-between items-start w-full p-4">
                    <span> {{ $project->description }}</span>
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

@endsection