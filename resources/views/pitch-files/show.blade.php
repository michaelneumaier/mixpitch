<!-- resources/views/pitch-files/show.blade.php -->

@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1 py-6">
    <div class="flex justify-center">
        <div class="w-full lg:!w-3/4 2xl:!w-2/3">
            <div class="bg-white border-transparent rounded-lg mb-12">
                <!-- Replace the old audio player with our Livewire component -->
                <livewire:pitch-file-player :file="$file" />
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* Hide wavesurfer initially until loaded to prevent FOUC */
    #waveform {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    #waveform.loaded {
        opacity: 1;
    }

    /* Custom timeline styles */
    #waveform-timeline {
        position: relative;
        height: 20px;
        margin-top: 8px;
    }

    .timeline-mark {
        position: absolute;
        top: 0;
        font-size: 10px;
        color: #6b7280;
        transform: translateX(-50%);
    }

    .timeline-container {
        border-top: 1px solid #e5e7eb;
        padding-top: 4px;
    }
</style>
@endpush

@push('scripts')
<!-- WaveSurfer.js library -->
<script src="https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js"></script>
@endpush