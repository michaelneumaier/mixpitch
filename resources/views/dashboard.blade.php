@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    <h3 class="h3">Your Projects</h3>
                    @if ($projects->isEmpty())
                    <p>You haven't shared any projects yet.</p>
                    @else
                    <ul class="list-group">
                        @foreach ($projects as $project)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <livewire:status-button :status="$project->status" />

                                <a href="{{ route('projects.show', $project->id) }}">{{
                                    $project->name
                                    }}</a>
                            </div>

                            <div>
                                <form action="{{ route('projects.edit', $project->id) }}" method="GET"
                                    style="display:inline-block">
                                    @csrf

                                    <button type="submit" class="btn btn-info btn-sm bg-sky-400">Edit</button>
                                </form>
                                <form action="{{ route('projects.destroy', $project->id) }}" method="POST"
                                    style="display:inline-block">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm bg-red-500">Delete</button>
                                </form>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                    @endif
                    <a href="{{ route('projects.upload') }}" class="upload-btn">Share Your Project</a>
                </div>
                <div class="card-body">
                    <h3 class="h3">Your Uploaded Mixes</h3>
                    <div class="">
                        <ul class="list-group">
                            @foreach($mixes as $mix)
                            <li class="list-group-item">
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
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>


        </div>
    </div>
</div>
@endsection