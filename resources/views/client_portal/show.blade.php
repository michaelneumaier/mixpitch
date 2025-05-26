<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Portal - {{ $project->title }}</title>
    {{-- Assuming Tailwind is included via a layout or globally --}}
    {{-- If using Vite/Mix, include the relevant CSS/JS assets --}}
    @vite(['resources/css/app.css', 'resources/js/app.js']) {{-- Example using Vite --}}
    <style>
        /* Add any specific styles needed for this page if not covered by Tailwind/app.css */
    </style>
</head>
<body class="bg-gray-100 font-sans antialiased">

    <div class="container mx-auto p-4 sm:p-6 lg:p-8 max-w-4xl">

        {{-- Header --}}
        <div class="bg-white shadow rounded-lg p-6 mb-6">
            <h1 class="text-2xl font-semibold text-gray-800 mb-2">Client Portal</h1>
            <h2 class="text-xl font-medium text-gray-700 mb-1">{{ $project->title }}</h2>
            <p class="text-sm text-gray-500">Managed by: {{ $pitch->user->name }}</p>
            {{-- Display Client Name if available --}}
            @if($project->client_name)
            <p class="text-sm text-gray-500">Client: {{ $project->client_name }} ({{ $project->client_email }})</p>
            @else
            <p class="text-sm text-gray-500">Client Email: {{ $project->client_email }}</p>
            @endif
        </div>

        {{-- Flash Messages (Success/Error) --}}
        {{-- Checkout Status Feedback --}}
        @if(request()->query('checkout_status') === 'success')
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Payment successful! The project has been approved. The producer has been notified.</span>
            </div>
        @elseif(request()->query('checkout_status') === 'cancel')
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">Payment was cancelled. The project has not been approved yet. You can try approving again when ready.</span>
            </div>
        @endif

        {{-- Standard Session Flash Messages --}}
        @if (session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Main Content Area --}}
        <div class="bg-white shadow rounded-lg p-6 mb-6">

            {{-- Pitch Status --}}
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Current Status</h3>
                <span class="inline-flex items-center px-3 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                    {{ $pitch->readable_status }}
                </span>
                <p class="text-sm text-gray-600 mt-1">{{ $pitch->status_description }}</p>
            </div>

            {{-- Project Description --}}
            @if($project->description)
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Project Brief</h3>
                <p class="text-gray-700 whitespace-pre-wrap">{{ $project->description }}</p>
            </div>
            @endif

            {{-- Files Section (Placeholder) --}}
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Files</h3>
                {{-- TODO: Implement file listing logic --}}
                {{-- Iterate through $pitch->files and display download links? --}}
                {{-- Need to consider permissions (e.g., using signed URLs for downloads?) --}}
                @if($pitch->files->count() > 0)
                     <ul class="list-disc list-inside text-gray-700 space-y-1">
                        @foreach($pitch->files as $file)
                            <li>
                                {{ $file->file_name }} ({{ number_format($file->size / 1024, 1) }} KB)
                                <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.download_file', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}" class="ml-2 inline-block text-blue-600 hover:text-blue-800 hover:underline text-sm">
                                    Download
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-gray-500">No files have been uploaded yet.</p>
                @endif
                 {{-- TODO: Add file upload capability for client if needed? --}}
            </div>

            {{-- Action Forms (Conditional) --}}
            @if ($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                <div class="mb-6 pb-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 mb-3">Actions Required</h3>
                    <div class="space-y-4">
                        {{-- Approve Form --}}
                        <form action="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.approve', now()->addHours(24), ['project' => $project->id]) }}" method="POST" class="inline-block">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Approve Submission
                            </button>
                        </form>

                        {{-- Request Revisions Form --}}
                        <form action="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.revisions', now()->addHours(24), ['project' => $project->id]) }}" method="POST">
                            @csrf
                            <div>
                                <label for="feedback" class="block text-sm font-medium text-gray-700 mb-1">Request Revisions (Feedback Required):</label>
                                <textarea name="feedback" id="feedback" rows="4" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md p-2" placeholder="Please provide detailed feedback on the revisions needed...">{{ old('feedback') }}</textarea>
                                @error('feedback')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="mt-2 px-4 py-2 bg-yellow-500 text-white font-semibold rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2">
                                Request Revisions
                            </button>
                        </form>
                    </div>
                </div>
            @endif

             {{-- Comment History & Form --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Communication Log</h3>
                {{-- Add Comment Form --}}
                <form action="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.comments.store', now()->addHours(24), ['project' => $project->id]) }}" method="POST" class="mb-6 bg-gray-50 p-4 rounded-md border border-gray-200">
                    @csrf
                    <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Add a Comment:</label>
                    <textarea name="comment" id="comment" rows="3" required class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border border-gray-300 rounded-md p-2" placeholder="Type your message here..."></textarea>
                    @error('comment')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="mt-2 px-4 py-2 bg-blue-600 text-white font-semibold rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Submit Comment
                    </button>
                </form>

                {{-- Comment History (Placeholder) --}}
                {{-- TODO: Implement comment listing --}}
                {{-- Iterate through $pitch->events, filter for comments, display nicely --}}
                <div class="space-y-4">
                    @forelse ($pitch->events->whereIn('event_type', ['client_comment', 'producer_comment', 'status_change', 'client_approved', 'client_revisions_requested']) as $event)
                        {{-- Example structure for displaying an event/comment --}}
                        <div class="p-3 rounded-md border {{ $event->event_type === 'client_comment' ? 'bg-blue-50 border-blue-200' : 'bg-gray-50 border-gray-200' }}">
                            <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $event->comment }}</p>
                            <p class="text-xs text-gray-500 mt-1">
                                @if($event->event_type === 'client_comment' && isset($event->metadata['client_email']))
                                    Client ({{ $event->metadata['client_email'] }})
                                @elseif($event->user)
                                    {{ $event->user->name }} (Producer)
                                @else
                                    {{-- System Event or Unknown --}}
                                    {{-- Might want to refine how events are displayed --}}
                                    System Event [{{ $event->event_type }}]
                                @endif
                                - {{ $event->created_at->diffForHumans() }} ({{ $event->created_at->format('M d, Y H:i') }})
                                @if($event->status)
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                        Status: {{ Str::title(str_replace('_', ' ', $event->status)) }}
                                    </span>
                                @endif
                            </p>
                        </div>
                    @empty
                        <p class="text-gray-500">No comments or activity yet.</p>
                    @endforelse
                </div>

            </div>

        </div> {{-- End Main Content --}}

        <footer class="text-center text-sm text-gray-500 mt-8">
            Powered by MixPitch
            {{-- Optional: Add link back to main site? --}}
        </footer>

    </div> {{-- End Container --}}

</body>
</html> 