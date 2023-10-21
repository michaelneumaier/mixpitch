@extends('layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg overflow-hidden">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:ml-1/3">
                    <div class="lg:w-1/3 flex-shrink-0 mb-4 lg:mb-0">
                        <!-- added mb-4 to add space between image and form on mobile -->
                        <!-- Image label (clicking this will trigger the hidden file input) -->
                        <label for="image-input">
                            <img src="{{ asset('storage/' . $project->image_path) }}" alt="{{ $project->name }}"
                                class="w-full h-56 object-cover lg:rounded-tl-lg cursor-pointer" id="preview">
                        </label>
                    </div>
                    <div class="flex-grow lg:ml-4 p-4">

                        <form method="POST" action="{{ route('projects.update', $project) }}"
                            enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <!--  -->

                            <!-- First Row -->
                            <div class="mb-2">
                                <label for="name" class="block text-sm font-medium text-gray-600">Project Name</label>
                                <input type="text"
                                    class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-500"
                                    id="name" name="name" value="{{ $project->name }}" required>
                            </div>

                            <!-- Second Row -->
                            <div class="flex justify-between items-start w-full">
                                <span>{{ $project->user->name }}</span>
                            </div>
                            <input type="file" id="image-input" name="image" class="hidden" onchange="loadFile(event)">
                            <div class="form-group mb-4">
                                <label for="description" class="sr-only">Description</label> <!-- hidden label -->
                                <textarea type="text" wire:model="projectDescription"
                                    class="form-control bg-dark text-light input-lg-custom" id="description"
                                    name="description" placeholder="Description">{{$project->description}}</textarea>

                            </div>

                            <!-- Project Image Upload -->
                            <!-- <div class="mb-4">
                            <label for="image" class="block text-sm font-medium text-gray-700">Project Image</label>
                            <input type="file" class="mt-1 rounded-md form-input block w-full" id="image" name="image">
                        </div> -->

                            <!-- Genre Selection -->
                            <div class="mb-4">
                                <label for="genre" class="block text-sm font-medium text-gray-700">Genre</label>
                                <select class="form-select rounded-md mt-1 block w-full text-gray-700" id="genre"
                                    name="genre" required>
                                    <option value="">Select genre...</option>
                                    <option value="Pop" {{ $project->genre == 'Pop' ? 'selected' : '' }}>Pop</option>
                                    <option value="Rock" {{ $project->genre == 'Rock' ? 'selected' : '' }}>Rock</option>
                                    <option value="Country" {{ $project->genre == 'Country' ? 'selected' : '' }}>Country
                                    </option>
                                    <option value="Hip Hop" {{ $project->genre == 'Hip Hop' ? 'selected' : '' }}>Hip Hop
                                    </option>
                                    <option value="Jazz" {{ $project->genre == 'Jazz' ? 'selected' : '' }}>Jazz</option>
                                </select>
                            </div>

                            <!-- Status Selection -->
                            <div class="mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <select class="form-select rounded-md mt-1 block w-full text-gray-700" id="status"
                                    name="status" required>
                                    <option value="unpublished" {{ $project->status == 'unpublished' ? 'selected' : ''
                                        }}>
                                        Unpublished
                                    </option>
                                    <option value="open" {{ $project->status == 'open' ? 'selected' : '' }}>Open
                                    </option>
                                    <option value="review" {{ $project->status == 'review' ? 'selected' : '' }}>In
                                        Review
                                    </option>
                                    <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>
                                        Completed
                                    </option>
                                    <option value="closed" {{ $project->status == 'closed' ? 'selected' : '' }}>Closed
                                    </option>
                                </select>
                            </div>

                            <!-- Update Button -->
                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Update Project</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Content Below the Image and Details -->
                <div class="clear-left bg-dark bg-opacity-50">
                    <div class="space-y-4">

                        <!-- Project Tracks and File Upload -->
                        <div class="mt-4 p-6 bg-white shadow-md rounded-lg">
                            <h5 class="text-xl font-semibold mb-4">Project Files</h5>

                            <div>
                                @if($project->files->count() == 0)
                                <p class="text-gray-600">There are no files in this project</p>
                                @else
                                <ul class="divide-y divide-gray-200">
                                    @foreach($project->files as $file)
                                    <li class="py-3 flex justify-between items-center">
                                        <div class="flex items-center space-x-4">
                                            <span class="text-gray-600">{{ basename($file->file_path) }}</span>
                                            <span class="text-sm text-gray-500">{{ $file->formatted_size }}</span>
                                        </div>
                                        <form
                                            action="{{ route('projects.deleteFile', ['project' => $project, 'file' => $file->id]) }}"
                                            method="POST" class="inline delete-file-form">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-danger">Delete</button>
                                        </form>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>

                            <a href="{{ route('projects.createStep2', $project) }}" class="btn btn-primary mt-3">Upload
                                More Files</a>
                        </div>



                        <!-- Other Actions -->
                        <!-- <div class="flex space-x-4 mt-3">
                            <a href="{{ route('projects.download', $project) }}" class="btn btn-primary">Download All
                                Files</a> -->
                        <!-- ... your other action buttons ... -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>


@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteFileButtons = document.querySelectorAll('.delete-file-form button');
        const form = document.querySelector('form');
        form.addEventListener('submit', function () {
            console.log('Form submitted');
        });
        deleteFileButtons.forEach(button => {
            button.addEventListener('click', function () {
                event.preventDefault();
                button.closest('form').submit();
            });
        });
    });
    function loadFile(event) {
        var output = document.getElementById('preview');
        output.src = URL.createObjectURL(event.target.files[0]);
        output.onload = function () {
            URL.revokeObjectURL(output.src); // free memory
        }
    };
</script>
@endsection