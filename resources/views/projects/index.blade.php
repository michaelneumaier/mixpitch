@extends('layouts.app')

@section('content')
<link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">

    <div class="container container-projects">
        <div class="projects-text">Projects</div>
        <div class="card-columns">
            @foreach($projects as $project)
                <div class="card">
                    <a href="{{ route('projects.show', $project->id) }}" class="text-white">
                        <div class="card-body">
                            <h5 class="card-title">{{ $project->name }}</h5>
                            <p class="card-text">{{ $project->genre }}</p>
                            <p class="card-text">Uploaded by: {{ $project->user->name }}</p>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
