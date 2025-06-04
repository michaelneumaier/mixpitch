@extends('layouts.app')

@section('title', 'Manage Contest Entry - ' . $pitch->project->name)

@section('content')
<div class="container mx-auto px-2 sm:px-4 py-6">
    <!-- Breadcrumb -->
    <nav class="flex mb-6" aria-label="Breadcrumb">
        <ol class="flex items-center space-x-2">
            <li>
                <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                    <i class="fas fa-home"></i>
                    <span class="sr-only">Home</span>
                </a>
            </li>
            <li>
                <span class="text-gray-400">/</span>
            </li>
            <li>
                <a href="{{ route('projects.show', $pitch->project) }}" class="text-gray-500 hover:text-gray-700 transition-colors">
                    {{ $pitch->project->name }}
                </a>
            </li>
            <li>
                <span class="text-gray-400">/</span>
            </li>
            <li class="text-gray-900 font-medium" aria-current="page">
                Contest Entry Management
            </li>
        </ol>
    </nav>

    <!-- Contest Entry Management Component -->
    <livewire:pitch.component.manage-contest-pitch :pitch="$pitch" />
</div>
@endsection 