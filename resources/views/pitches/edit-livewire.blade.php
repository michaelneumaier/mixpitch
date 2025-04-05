@extends('components.layouts.app')

@section('content')
    {{-- Load the ManagePitch Livewire component, passing the Pitch object --}}
    {{-- The component's mount method will handle loading related data --}}
    <livewire:pitch.component.manage-pitch :pitch="$pitch" />
@endsection 