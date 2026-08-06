@extends('layouts.app')

@section('title', 'Deliverables - ' . $project->title)

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-green-50 dark:from-slate-900 dark:via-green-900 dark:to-slate-900">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden mb-8">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Project Deliverables</h1>
                        <p class="text-gray-600 dark:text-gray-300">{{ $project->title }}</p>
                    </div>
                    <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-2 rounded-lg font-semibold">
                        Completed
                    </div>
                </div>

                @if($pitch->user)
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Produced by <span class="font-medium text-gray-700 dark:text-gray-300">{{ $pitch->user->name }}</span>
                    </p>
                @endif
            </div>
        </div>

        <!-- Deliverables List -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl overflow-hidden">
            <div class="p-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Your Files</h2>

                @if($deliverables->count() > 0)
                    <div class="space-y-4">
                        @foreach($deliverables as $file)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-xl">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ $file->file_name }}</p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $file->size ? number_format($file->size / 1024 / 1024, 1) . ' MB' : 'Unknown size' }}
                                        </p>
                                    </div>
                                </div>
                                <a href="{{ URL::temporarySignedRoute('client.portal.download_file', now()->addHours(1), ['project' => $project->id, 'pitchFile' => $file->id]) }}"
                                    class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-green-700 transition-colors">
                                    Download
                                </a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-12">
                        <p class="text-gray-500 dark:text-gray-400">No deliverable files available yet.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
