@extends('layouts.app')

@section('content')
    <div class="container mx-auto py-4">
        <div class="flex justify-center">
            <div class="w-full md:w-9/10 lg:w-4/5">
                <div class="bg-white shadow mb-4">
                    <div class="bg-gray-200 py-3 px-4">{{ __('Dashboard') }}</div>

                    <div class="p-4">
                        <h3 class="text-2xl font-semibold mb-3">Your Projects</h3>
                        @if ($projects->isEmpty())
                            <p class="mb-3">You haven't shared any projects yet.</p>
                        @else
                            <ul class="divide-y divide-gray-200 mb-3">
                                @foreach ($projects as $project)
                                    <div class="bg-cover bg-center"
                                         style="background: {{ $project->image_path ? 'url(' . asset('storage/'.$project->image_path) . ')' : 'transparent' }}">
                                        <li class="p-3 bg-gray-800 bg-opacity-70">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center justify-between">
                                                    <div class="mr-2">
                                                        <livewire:status-button :status="$project->status"/>
                                                    </div>
                                                    <h5 class="text-lg font-semibold mb-0">
                                                        <a href="{{ route('projects.show', $project) }}"
                                                           class="no-underline text-blue-500">
                                                            {{ $project->name }}
                                                        </a>
                                                    </h5>
                                                </div>
                                                <div class="flex space-x-2 float-right">
                                                    <form action="{{ route('projects.edit', $project) }}" method="GET"
                                                          class="mr-2">
                                                        @csrf
                                                        <button type="submit"
                                                                class="text-sm bg-blue-500 text-white px-2 py-1 rounded">
                                                            Edit
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('projects.destroy', $project) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit"
                                                                class="text-sm bg-red-500 text-white px-2 py-1 rounded">
                                                            Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="flex justify-between items-center">
                                                <div class="flex space-x-3 text-white">
                                                    <div>
                                                        Files Uploaded: <span
                                                            class="font-semibold">{{ $project->files->count() }}</span>
                                                    </div>
                                                    <div>
                                                        Mixes: <span
                                                            class="font-semibold">{{ $project->mixes->count() }}</span>
                                                    </div>
                                                    <div>
                                                        Last Updated: <span
                                                            class="font-semibold">{{ $project->updated_at->format('F j, Y') }}</span>
                                                    </div>
                                                </div>

                                            </div>
                                        </li>
                                    </div>
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('projects.upload') }}"
                           class="block bg-blue-500 hover:bg-blue-400 text-2xl text-white text-center font-bold w-full py-2 px-4 border-b-4 border-blue-700 hover:border-blue-500 rounded whitespace-nowrap">Share
                            Your Project</a>
                    </div>

                    <div class="p-4">
                        <h3 class="text-lg font-semibold mb-3">Your Uploaded Mixes</h3>
                        <div>
                            <ul class="divide-y divide-gray-200">
                                @forelse($mixes as $mix)
                                    <li class="py-3 flex justify-between items-center">
                                        <a href="{{ route('projects.show', $project) }}"
                                           class="text-blue-500 hover:underline">
                                            {{ $mix->project->name }}
                                        </a>
                                        <span>
                                        @for($i = 1; $i <= 10; $i++)
                                                @if($i <=$mix->rating)
                                                    ★
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                    </span>
                                    </li>
                                @empty
                                    <li class="py-3">You haven't uploaded any mixes yet.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <div class="container py-4"> <!-- padding added -->
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card mb-4"> <!-- margin added -->
                    <div class="card-header py-3">{{ __('Dashboard') }}</div> <!-- padding added -->

                    <div class="card-body">
                        <h3 class="h3 mb-3">Your Projects</h3> <!-- margin added -->
                        @if ($projects->isEmpty())
                            <p class="mb-3">You haven't shared any projects yet.</p> <!-- margin added -->
                        @else
                            <ul class="list-group mb-3"> <!-- margin added -->
                                @foreach ($projects as $project)
                                    <div
                                        style="background: {{ $project->image_path ? 'url(\'' . asset('storage/'.$project->image_path) . '\') no-repeat center center / cover;' : 'transparent;' }}">
                                        <li class="list-group-item py-3 bg-dark bg-opacity-75">
                                            <!-- Project name and status -->
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="me-2">
                                                    <livewire:status-button :status="$project->status"/>
                                                </div>
                                                <h5 class="mb-0">
                                                    <a href="{{ route('projects.show', $project) }}"
                                                       class="text-decoration-none text-primary font-weight-bold"
                                                       style="font-size: 2rem;">
                                                        {{ $project->name }}
                                                    </a>
                                                </h5>
                                            </div>

                                            <!-- Additional details -->
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex">
                                                    <div class="text-secondary me-3">
                                                        Files Uploaded: <span class="font-weight-bold">{{ $project->files->count()
                                            }}</span>
                                                    </div>
                                                    <div class="text-secondary me-3">
                                                        Mixes: <span
                                                            class="font-weight-bold">{{ $project->mixes->count() }}</span>
                                                    </div>
                                                    <div class="text-secondary me-3">
                                                        Last Updated: <span class="font-weight-bold">{{ $project->updated_at->format('F
                                            j, Y') }}</span>
                                                    </div>
                                                </div>
                                                <div class="d-flex">
                                                    <form action="{{ route('projects.edit', $project) }}"
                                                          method="GET"
                                                          style="display:inline-block" class="me-2">
                                                        @csrf
                                                        <button type="submit" class="btn btn-info btn-sm">Edit</button>
                                                    </form>
                                                    <form action="{{ route('projects.destroy', $project) }}"
                                                          method="POST"
                                                          style="display:inline-block">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger btn-sm">Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>


                                            <!-- Edit and delete buttons -->
                                            <!-- <div class="d-flex justify-content-end">
                                <form action="{{ route('projects.edit', $project->id) }}" method="GET"
                                    style="display:inline-block" class="me-2">
                                    @csrf
                                            <button type="submit" class="btn btn-info btn-sm">Edit</button>
                                        </form>
                                        <form action="{{ route('projects.destroy', $project->id) }}" method="POST"
                                    style="display:inline-block">
                                    @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                        </form>
                                    </div> -->
                                        </li>
                                    </div>

                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('projects.upload') }}" class="upload-btn">Share Your Project</a>
                    </div>
                    <div class="card-body">
                        <h3 class="h3 mb-3">Your Uploaded Mixes</h3> <!-- margin added -->
                        <div class="">
                            <ul class="list-group">
                                @forelse($mixes as $mix)
                                    <li class="list-group-item py-3"> <!-- padding added -->
                                        <a href="{{ route('projects.show', $project) }}">
                                            {{ $mix->project->name }}
                                        </a>
                                        <span class="float-right">
                                    @for($i = 1; $i <= 10; $i++)
                                                @if($i <=$mix->rating)
                                                    ★
                                                @else
                                                    ☆
                                                @endif
                                            @endfor
                                </span>
                                    </li>
                                @empty
                                    <li class="list-group-item py-3"> <!-- padding added -->
                                        You haven't uploaded any mixes yet.
                                    </li>
                                @endforelse
                            </ul>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
