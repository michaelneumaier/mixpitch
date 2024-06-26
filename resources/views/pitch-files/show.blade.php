<!-- resources/views/pitch-files/show.blade.php -->

@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12">
                <div class="flex flex-col shadow-lightGlow shadow-base-300 rounded-t-lg">
                    <div class="px-4 py-2">
                        <a href="{{ route('pitches.show', $file->pitch_id) }}"
                            class="text-blue-500 hover:text-blue-700">&larr; Back to
                            Pitch</a>
                    </div>
                    <h1 class="text-2xl font-semibold px-4 py-2">{{ $file->file_name }}</h1>

                    <div class="px-4 py-2">
                        <audio controls class="w-full">
                            <source src="{{ asset('storage/' . $file->file_path) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>

                    <div class="px-4 py-2 flex flex-row">
                        <a href="{{ asset('storage/' . $file->file_path) }}" download="{{ $file->file_name }}"
                            class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-2 px-4 m-1 rounded inline-flex items-center">
                            <i class="fas fa-download mr-2"></i>
                            Download
                        </a>
                        <form action="{{ route('pitch-files.delete', $file) }}" method="POST"
                            onsubmit="return confirm('Are you sure you want to delete this file?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="bg-red-500 hover:bg-red-700 text-white font-semibold py-2 px-4 m-1 rounded inline-flex items-center">
                                <i class="fas fa-trash mr-2"></i>
                                Delete
                            </button>
                        </form>
                    </div>
                    <!-- Placeholder for Rating System -->
                    <div class="px-4 py-2">
                        <h2 class="text-xl font-semibold mb-2">Rate this File</h2>
                        <p class="text-gray-500">Rating system placeholder</p>
                    </div>

                    <!-- Placeholder for Comments Section -->
                    <div class="px-4 py-2">
                        <h2 class="text-xl font-semibold mb-2">Comments</h2>
                        <p class="text-gray-500">Comments section placeholder</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection