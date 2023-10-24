@extends('components.layouts.app')

@section('content')
<div class="container">
    <h2 class="text-light">Tracks</h2>
    <div class="row">
        @foreach($tracks as $track)
        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-body text-white">
                    <h5 class="card-title">{{ $track->title }}</h5>
                    <p class="card-text">{{ $track->genre }}</p>
                    <a href="{{ route('tracks.show', $track->id) }}" class="btn btn-primary">View Track</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection