@extends('layouts.app')

@section('content')
@php
$backgroundStyle = $project->image_path ? 'style="background: url(\'' . asset('storage' . $project->image_path) .
'\') no-repeat center center / cover;"' :
'';
@endphp
<link href="{{ asset('css/upload-page.css') }}" rel="stylesheet">
<div class="flex justify-center items-center">
    <div class="w-full max-w-xl mx-auto overflow-hidden bg-white rounded-lg shadow-md">
        <div class="px-6 py-4 bg-gray-500 text-white">
            <h2 class="text-lg font-semibold">Edit Project</h2>
        </div>

        <div class="px-6 py-4 bg-opacity-50">
            <form method="POST" action="{{ route('projects.update', $project) }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mt-4">
                    <label for="name" class="block text-sm font-medium text-gray-600">Project Name</label>
                    <input type="text"
                        class="mt-1 w-full rounded-md border-gray-300 focus:border-indigo-500 focus:ring focus:ring-indigo-500"
                        id="name" name="name" value="{{ $project->name }}" required>
                </div>

                <div class="mb-4">
                    <label for="genre" class="block text-sm font-medium text-gray-700">Genre</label>
                    <select class="form-select rounded-md mt-1 block w-full text-gray-700" id="genre" name="genre"
                        required>
                        <option value="">Select genre...</option>
                        <option value="Pop" {{ $project->genre == 'Pop' ? 'selected' : '' }}>Pop</option>
                        <option value="Rock" {{ $project->genre == 'Rock' ? 'selected' : '' }}>Rock</option>
                        <option value="Country" {{ $project->genre == 'Country' ? 'selected' : '' }}>Country
                        </option>
                        <option value="Hip Hop" {{ $project->genre == 'Hip Hop' ? 'selected' : '' }}>Hip Hop
                        </option>
                        <option value="Jazz" {{ $project->genre == 'Jazz' ? 'selected' : '' }}>Jazz</option>
                        <!-- add more genres as needed -->
                    </select>
                </div>

                <div class="mb-4">
                    <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                    <select class="form-select rounded-md mt-1 block w-full text-gray-700" id="status" name="status"
                        required>
                        <option value="">Status</option>
                        <option value="unpublished" {{ $project->status == 'unpublished' ? 'selected' : '' }}>
                            Unpublished
                        </option>
                        <option value="open" {{ $project->status == 'open' ? 'selected' : '' }}>Open</option>
                        <option value="review" {{ $project->status == 'review' ? 'selected' : '' }}>In Review
                        </option>
                        <option value="completed" {{ $project->status == 'completed' ? 'selected' : '' }}>
                            Completed
                        </option>
                        <option value="closed" {{ $project->status == 'closed' ? 'selected' : '' }}>Closed</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="image" class="block text-sm font-medium text-gray-700">Project Image</label>
                    <input type="file" class="mt-1 rounded-md form-input block w-full" id="image" name="image">
                </div>


                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">Update Project</button>
                </div>
            </form>

            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">Project Files</h5>

                    <ul class="list-group">
                        @if($project->files->count() == 0)
                        There are no files in this project
                        @endif
                        @foreach($project->files as $file)
                        <li class="list-group-item flex justify-between">
                            {{ basename($file->file_path) }}
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

                    <a href="{{ route('projects.createStep2', $project) }}" class="btn btn-primary mt-3">Upload More
                        Files</a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- <div class="container">--}}
    {{-- <div class="row justify-content-center">--}}
        {{-- <div class="col-md-8">--}}
            {{-- <div class="card text-white border-0" {!! $backgroundStyle !!}>--}}
                {{-- <div class="card-header bg-dark bg-opacity-75">Edit Project</div>--}}

                {{-- <div class="card-body bg-dark bg-opacity-50">--}}
                    {{-- <form method="POST" action="{{ route('projects.update', $project) }}" --}} {{--
                        enctype="multipart/form-data">--}}
                        {{-- @csrf--}}
                        {{-- @method('PUT')--}}

                        {{-- <div class="form-group">--}}
                            {{-- <label for="name">Project Name</label>--}}
                            {{-- <input type="text" class="form-control bg-dark text-light input-lg-custom" id="name"
                                --}} {{-- name="name" value="{{ $project->name }}" required>--}}
                            {{-- </div>--}}
                        {{-- <div class="form-group">--}}
                            {{-- <label for="genre">Genre</label>--}}
                            {{-- <select class="form-control form-control-lg text-white" id="genre" name="genre" --}}
                                {{-- required>--}}
                                {{-- <option value="">Select genre...</option>--}}
                                {{-- <option value="Pop" {{ $project->genre == 'Pop' ? 'selected' : '' }}>Pop</option>
                                --}}
                                {{-- <option value="Rock" {{ $project->genre == 'Rock' ? 'selected' : '' }}>Rock
                                </option>--}}
                                {{-- <option value="Country" {{ $project->genre == 'Country' ? 'selected' : ''
                                    }}>Country--}}
                                    {{-- </option>--}}
                                {{-- <option value="Hip Hop" {{ $project->genre == 'Hip Hop' ? 'selected' : '' }}>Hip
                                    Hop--}}
                                    {{-- </option>--}}
                                {{-- <option value="Jazz" {{ $project->genre == 'Jazz' ? 'selected' : '' }}>Jazz
                                </option>--}}
                                {{-- <!-- add more genres as needed -->--}}
                                {{-- </select>--}}
                            {{-- </div>--}}

                        {{-- <div class="form-group">--}}
                            {{-- <label for="status">Status</label>--}}
                            {{-- <select class="form-control form-control-lg text-white" id="status" name="status" --}}
                                {{-- required>--}}
                                {{-- <option value="">Status</option>--}}
                                {{-- <option--}} {{-- value="unpublished" {{ $project->status == 'unpublished' ?
                                    'selected' : '' }}>--}}
                                    {{-- Unpublished--}}
                                    {{-- </option>--}}
                                    {{-- <option value="open" {{ $project->status == 'open' ? 'selected' : ''
                                        }}>Open--}}
                                        {{-- </option>--}}
                                    {{-- <option value="review" {{ $project->status == 'review' ? 'selected' : ''
                                        }}>In--}}
                                        {{-- Review--}}
                                        {{-- </option>--}}
                                    {{-- <option value="completed" {{ $project->status == 'completed' ? 'selected' : ''
                                        }}>--}}
                                        {{-- Completed--}}
                                        {{-- </option>--}}
                                    {{-- <option value="closed" {{ $project->status == 'closed' ? 'selected' : ''
                                        }}>Closed--}}
                                        {{-- </option>--}}
                                    {{-- </select>--}}
                            {{-- </div>--}}

                        {{-- <div class="form-group mt-4">--}}
                            {{-- <label for="image">Project Image</label>--}}
                            {{-- <input type="file" class="form-control-file" id="image" name="image">--}}
                            {{-- </div>--}}

                        {{-- <button type="submit" class="btn bg-blue-500 btn-primary mt-3">Update Project</button>--}}
                        {{-- </form>--}}

                    {{-- <div class="card mt-4 text-white bg-dark bg-opacity-50">--}}
                        {{-- <div class="card-header">--}}
                            {{-- <h5>Project Files</h5>--}}
                            {{-- </div>--}}
                        {{-- <div class="card-body">--}}
                            {{-- <ul class="list-group">--}}
                                {{-- @if($project->files->count() == 0)--}}
                                {{-- There are no files in this project--}}
                                {{-- @endif--}}
                                {{-- @foreach($project->files as $file)--}}
                                {{-- <li class="list-group-item">--}}
                                    {{-- {{ basename($file->file_path) }}--}}
                                    {{-- <form--}} {{--
                                        action="{{ route('projects.deleteFile', ['project' => $project, 'file' => $file->id]) }}"
                                        --}} {{-- method="POST" class="d-inline delete-file-form">--}}
                                        {{-- @csrf--}}
                                        {{-- @method('DELETE')--}}
                                        {{-- <button type="button" --}} {{--
                                            class="btn btn-sm bg-red-500 btn-danger float-right">Delete--}}
                                            {{-- </button>--}}
                                        {{-- </form>--}}
                                        {{-- </li>--}}
                                {{-- @endforeach--}}
                                {{-- </ul>--}}


                            {{-- <a href="{{ route('projects.createStep2', $project) }}" --}} {{--
                                class="btn btn-primary mt-3">Upload--}}
                                {{-- More--}}
                                {{-- Files</a>--}}
                            {{-- </div>--}}
                        {{-- </div>--}}
                    {{-- </div>--}}
                {{-- </div>--}}
            {{-- </div>--}}
        {{-- </div>--}}
    {{-- </div>--}}
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
</script>
@endsection