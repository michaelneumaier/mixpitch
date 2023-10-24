@extends('components.layouts.app')

@section('content')
<link href="{{ asset('css/project-detail.css') }}" rel="stylesheet">

<div class="container container-project-detail">
    <div class="project-detail-text">{{ $track->name }}</div>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="project-info">
                <h2>{{ $track->title}}</h2>
                <p>Uploaded by: {{ $track->user->name }}</p>
                <p>Created at: {{ $track->created_at }}</p>
                <p>Audio:</p>
                <audio controls>
                    <source src="{{ asset('storage/' . $track->file_path) }}" type="audio/mpeg">
                    Your browser does not support the audio element.
                </audio>
                <a href="{{ route('projects.download', ['project' => $track->id]) }}"
                    class="btn btn-primary">Download</a>



                @if (auth()->check() && auth()->user()->id === $track->user_id)
                <a href="#" class="delete-btn"
                    onclick="event.preventDefault(); document.getElementById('delete-project-form').submit();">Delete
                    Project</a>
                <form id="delete-project-form" action="{{ route('tracks.destroy', $track->id) }}" method="POST"
                    style="display: none;">
                    @csrf
                    @method('DELETE')
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection