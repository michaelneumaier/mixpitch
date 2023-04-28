@extends('layouts.app')

@section('content')
<div class="container">
    <h2 class="text-light">{{ $track->title }}</h2>
    <p class="text-light">Genre: {{ $track->genre }}</p>
    <p class="text-light">Uploaded by: {{ $track->user->name }}</p>
    <audio controls>
        <source src="{{ asset('storage/' . $track->file_path) }}" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>
</div>
@endsection
