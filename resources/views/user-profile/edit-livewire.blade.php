@extends('components.layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <livewire:user-profile-edit />
        </div>
    </div>
</div>

@endsection
