@extends('layouts.app')

@section('content')
<link href="{{ asset('css/projects-page.css') }}" rel="stylesheet">

<div class="container container-projects">
    <div class="projects-text">Projects</div>

    <div class="container">
        <div class="row">
            @foreach($projects as $project)
                @livewire('project-card', ['project' => $project], key($project->id))
            @endforeach
        </div>
    </div>


    @endsection