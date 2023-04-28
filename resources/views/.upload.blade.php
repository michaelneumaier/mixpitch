@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Upload a track</h2>

    @if(session('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <form action="{{ route('tracks.upload') }}" method="post" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="title">Title:</label>
            <input type="text" name="title" id="title" class="form-control">
        </div>

        <div class="form-group">
            <label for="genre">Genre:</label>
            <input type="text" name="genre" id="genre" class="form-control">
        </div>

        <div class="form-group">
            <label for="track">Upload your track:</label>
            <input type="file" name="track" id="track" class="form-control">
            @error('track')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Upload</button>
    </form>
</div>
@endsection
