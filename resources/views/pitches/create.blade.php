@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12 p-4">
                <h2>Start Your Pitch for "{{ $project->name }}"</h2>

                <form action="{{ route('pitches.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">

                    <div class="form-group mb-4">
                        <input type="checkbox" name="agree_terms" id="agree_terms" required>
                        <label for="agree_terms">I agree to the <a href="#" target="_blank" class="underline">Terms and
                                Conditions</a>.</label>
                    </div>

                    <button type="submit" class="btn btn-primary">Start Your Pitch</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection