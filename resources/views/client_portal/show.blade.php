<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

            {{-- Files Section with Separation --}}
            <div class="mb-6 pb-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Project Files</h3>
                
                {{-- Client Reference Files Section --}}
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                    <h4 class="font-semibold text-blue-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                        </svg>
                        Your Reference Files
                    </h4>
                    <p class="text-sm text-blue-700 mb-4">Upload briefs, references, or examples to help the producer understand your requirements.</p>
                    
                    {{-- File Upload Area --}}
                    <div class="border-2 border-dashed border-blue-300 rounded-lg p-6 text-center mb-4" id="client-upload-area">
                        <svg class="mx-auto h-12 w-12 text-blue-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                        <p class="text-sm text-blue-600 mb-2">
                            <label for="client-file-input" class="font-medium cursor-pointer hover:text-blue-500">
                                Click to upload
                            </label>
                            or drag and drop
                        </p>
                        <p class="text-xs text-blue-500">PDF, DOC, MP3, WAV, JPG, PNG (max 200MB)</p>
                        <input id="client-file-input" type="file" class="hidden" multiple 
                               accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.mp3,.wav,.m4a">
                    </div>
                    
                    {{-- Client Files List --}}
                    <div id="client-files-list">
                        @if($project->files->count() > 0)
                            <div class="space-y-2">
                                @foreach($project->files as $file)
                                    <div class="flex items-center justify-between py-2 px-3 bg-blue-100 rounded">
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="text-sm font-medium text-blue-800">{{ $file->file_name }}</span>
                                            <span class="text-xs text-blue-600 ml-2">({{ number_format($file->file_size / 1024, 1) }} KB)</span>
                                        </div>
                                        <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.download_project_file', now()->addHours(24), ['project' => $project->id, 'projectFile' => $file->id]) }}" 
                                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                            Download
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-blue-600 text-sm italic">No reference files uploaded yet. Upload files above to get started.</p>
                        @endif
                    </div>
                </div>
                
                {{-- Producer Deliverables Section --}}
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h4 class="font-semibold text-green-800 mb-3 flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                        Producer Deliverables
                    </h4>
                    <p class="text-sm text-green-700 mb-4">Files delivered by {{ $pitch->user->name }} for your review.</p>
                    
                    {{-- Producer Files List --}}
                    @if($pitch->files->count() > 0)
                        <div class="space-y-2">
                            @foreach($pitch->files as $file)
                                <div class="flex items-center justify-between py-2 px-3 bg-green-100 rounded">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-2 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M18 3a1 1 0 00-1.196-.98l-10 2A1 1 0 006 5v9.114A4.369 4.369 0 005 14c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V7.82l8-1.6v5.894A4.37 4.37 0 0015 12c-1.657 0-3 .895-3 2s1.343 2 3 2 3-.895 3-2V3z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800">{{ $file->file_name }}</span>
                                        <span class="text-xs text-green-600 ml-2">({{ number_format($file->file_size / 1024, 1) }} KB)</span>
                                    </div>
                                    <a href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('client.portal.download_file', now()->addHours(24), ['project' => $project->id, 'pitchFile' => $file->id]) }}" 
                                       class="text-green-600 hover:text-green-800 text-sm font-medium">
                                        Download
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-green-600 text-sm italic">No deliverables uploaded yet. The producer will upload files here as they work on your project.</p>
                    @endif
                </div>
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

    {{-- Client File Upload JavaScript --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('client-upload-area');
            const fileInput = document.getElementById('client-file-input');
            const filesList = document.getElementById('client-files-list');
            
            // Handle file input change
            fileInput.addEventListener('change', function(e) {
                handleFiles(e.target.files);
            });
            
            // Handle drag and drop
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('border-blue-500', 'bg-blue-100');
            });
            
            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-blue-500', 'bg-blue-100');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('border-blue-500', 'bg-blue-100');
                handleFiles(e.dataTransfer.files);
            });
            
            function handleFiles(files) {
                Array.from(files).forEach(uploadFile);
            }
            
            function uploadFile(file) {
                // Create progress indicator
                const progressDiv = createProgressIndicator(file.name);
                uploadArea.insertAdjacentElement('afterend', progressDiv);
                
                const formData = new FormData();
                formData.append('file', file);
                
                // Get CSRF token
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                if (token) {
                    formData.append('_token', token);
                }
                
                // Upload file
                fetch('{{ \Illuminate\Support\Facades\URL::temporarySignedRoute("client.portal.upload_file", now()->addHours(24), ["project" => $project->id]) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    progressDiv.remove();
                    
                    if (data.success) {
                        // Add file to the list
                        addFileToList(data.file);
                        showSuccessMessage('File uploaded successfully!');
                    } else {
                        showErrorMessage(data.message || 'Upload failed');
                    }
                })
                .catch(error => {
                    progressDiv.remove();
                    console.error('Upload error:', error);
                    showErrorMessage('Upload failed. Please try again.');
                });
            }
            
            function createProgressIndicator(fileName) {
                const div = document.createElement('div');
                div.className = 'bg-blue-100 border border-blue-300 rounded-lg p-3 mt-2';
                div.innerHTML = `
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-blue-800">Uploading: ${fileName}</span>
                        <div class="w-6 h-6">
                            <svg class="animate-spin text-blue-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    </div>
                `;
                return div;
            }
            
            function addFileToList(file) {
                const existingFiles = filesList.querySelector('.space-y-2');
                const noFilesMsg = filesList.querySelector('p.italic');
                
                if (noFilesMsg) {
                    noFilesMsg.remove();
                }
                
                if (!existingFiles) {
                    const container = document.createElement('div');
                    container.className = 'space-y-2';
                    filesList.appendChild(container);
                }
                
                const fileDiv = document.createElement('div');
                fileDiv.className = 'flex items-center justify-between py-2 px-3 bg-blue-100 rounded';
                fileDiv.innerHTML = `
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-2 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 0v12h8V4H6z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-sm font-medium text-blue-800">${file.name}</span>
                        <span class="text-xs text-blue-600 ml-2">(${(file.size / 1024).toFixed(1)} KB)</span>
                    </div>
                    <span class="text-blue-600 text-sm font-medium">Just uploaded</span>
                `;
                
                filesList.querySelector('.space-y-2').appendChild(fileDiv);
            }
            
            function showSuccessMessage(message) {
                showMessage(message, 'success');
            }
            
            function showErrorMessage(message) {
                showMessage(message, 'error');
            }
            
            function showMessage(message, type) {
                const existing = document.querySelector('.flash-message');
                if (existing) existing.remove();
                
                const div = document.createElement('div');
                div.className = `flash-message fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg z-50 ${
                    type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                }`;
                div.textContent = message;
                
                document.body.appendChild(div);
                
                setTimeout(() => {
                    div.remove();
                }, 5000);
            }
        });
    </script>

</body>
</html> 