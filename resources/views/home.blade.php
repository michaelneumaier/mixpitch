@extends('layouts.app')

@section('content')
<div class="container-home">
    <h1 class="display-1 text-white align-items-center text-center">Welcome to Mix Pitch</h1>
    <p class="welcome-text">A platform for <span class="text-muted mark">musicians</span> and <span
            class="text-muted mark">audio
            engineers</span> to <i class="text-white">collaborate,
            learn, </i>and <i class="text-white">improve</i> their
        mixing
        skills.</p>
    <div class="row justify-content-center">
        <div class="col-md-6">
            @auth
            <a href="{{ route('projects.upload') }}" class="upload-btn">Share Your Project</a>
            @else
            <a href="{{ route('login') }}" class="upload-btn">Share Your Project</a>
            @endauth
        </div>
    </div>
</div>
@endsection