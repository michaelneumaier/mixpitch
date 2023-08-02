@extends('layouts.app')

@section('content')
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
                        <li class="list-group-item py-3">
                            <!-- Project name and status -->
                            <div class="d-flex align-items-center mb-2">
                                <div class="me-2">
                                    <livewire:status-button :status="$project->status" />
                                </div>
                                <h5 class="mb-0">
                                    <a href="{{ route('projects.show', $project->id) }}"
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
                                        Mixes: <span class="font-weight-bold">{{ $project->mixes->count() }}</span>
                                    </div>
                                    <div class="text-secondary me-3">
                                        Last Updated: <span class="font-weight-bold">{{ $project->updated_at->format('F
                                            j, Y') }}</span>
                                    </div>
                                </div>
                                <div class="d-flex">
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
                                <a href="{{ route('projects.show', $mix->project_id) }}">
                                    {{ $mix->project->name }}
                                </a>
                                <span class="float-right">
                                    @for($i = 1; $i <= 10; $i++) @if($i <=$mix->rating)
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