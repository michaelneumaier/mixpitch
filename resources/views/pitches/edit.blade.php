@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1 py-8">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="mb-6">
                <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}" class="btn btn-sm bg-base-200 hover:bg-base-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Pitch
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-xl border border-base-300 overflow-hidden">
                <div class="bg-gradient-to-r from-amber-50 to-amber-100 p-6 border-b border-amber-200">
                    <h1 class="text-2xl font-bold text-amber-800 flex items-center">
                        <i class="fas fa-pencil-alt mr-3 text-amber-600"></i>Revise Your Pitch
                    </h1>
                    <p class="text-amber-700 mt-2">
                        The project owner has requested revisions to your pitch. Please review the feedback below and make the necessary changes.
                    </p>
                </div>

                <div class="p-6">
                    @if (session('error'))
                        <div class="alert alert-error mb-4">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Current Feedback Section -->
                    <div class="mb-8 bg-amber-50 border border-amber-200 rounded-lg p-4">
                        <h2 class="text-lg font-semibold mb-3 text-amber-800">Feedback from Project Owner</h2>
                        
                        @if ($currentSnapshot && isset($currentSnapshot->snapshot_data['feedback']))
                            <div class="whitespace-pre-wrap text-gray-700">{{ $currentSnapshot->snapshot_data['feedback'] }}</div>
                        @elseif ($pitch->events()->where('event_type', 'snapshot_revisions_requested')->latest()->first())
                            @php
                                $event = $pitch->events()->where('event_type', 'snapshot_revisions_requested')->latest()->first();
                                $feedback = preg_replace('/^Revisions requested\. Reason: /i', '', $event->comment);
                                // If feedback is empty after stripping, show a default message
                                if (empty(trim($feedback))) {
                                    $feedback = 'No specific feedback was provided. Please review your pitch and make improvements.';
                                }
                            @endphp
                            <div class="p-4 mb-4 bg-amber-50 border border-amber-200 rounded-lg">
                                <div class="flex items-center mb-2">
                                    <i class="fas fa-exclamation-circle text-amber-500 mr-2"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Revisions Requested</h2>
                                </div>
                                <p class="text-gray-700">
                                    {{ $feedback }}
                                </p>
                            </div>
                        @else
                            <div class="italic text-gray-500">No specific feedback was provided. Please review your pitch and make improvements.</div>
                        @endif
                    </div>

                    <!-- Revision Form -->
                    <form action="{{ route('projects.pitches.update', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <input type="hidden" name="has_revisions" value="1">

                        <div class="form-control">
                            <label class="label">
                                <span class="label-text text-base font-medium">Your Response to Feedback</span>
                                <span class="label-text-alt text-amber-600"><i class="fas fa-info-circle mr-1"></i>This message will be visible to the project owner</span>
                            </label>
                            <div class="bg-amber-50 border border-amber-200 p-3 rounded-t-lg">
                                <p class="text-amber-800 text-sm mb-2"><i class="fas fa-comment-dots mr-1"></i>Your response will appear in the feedback conversation history.</p>
                            </div>
                            <textarea name="response_to_feedback" rows="5" 
                                class="textarea textarea-bordered w-full bg-white border-amber-200 rounded-t-none" 
                                placeholder="Explain what changes you've made in response to the feedback...">{{ old('response_to_feedback') }}</textarea>
                            @error('response_to_feedback')
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mt-6 bg-gray-50 p-4 rounded-lg">
                            <h3 class="text-lg font-medium mb-3">Before Submitting:</h3>
                            <ul class="list-disc pl-5 space-y-2 text-gray-700">
                                <li>Make sure you've uploaded any new files that address the feedback</li>
                                <li>Review your pitch details carefully</li>
                                <li>Explain the changes you've made in the response field above</li>
                            </ul>
                        </div>

                        <div class="flex items-center justify-between">
                            <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}" class="btn btn-outline">
                                Cancel
                            </a>
                            <div class="flex gap-3">
                                <a href="#" onclick="window.scrollTo({top: 0, behavior: 'smooth'}); return false;" 
                                   class="btn btn-outline-amber">
                                    <i class="fas fa-arrow-up mr-1"></i>Review Feedback
                                </a>
                                <button type="submit" class="btn bg-amber-500 hover:bg-amber-600 text-white">
                                    <i class="fas fa-paper-plane mr-2"></i>Submit Revisions
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- File Management Instructions -->
            <div class="mt-8 bg-white rounded-lg shadow-md border border-base-300 p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-file-upload text-blue-500 mr-2"></i>File Management
                </h2>
                <p class="text-gray-700 mb-4">
                    To upload new files or manage existing ones, please return to your pitch dashboard.
                </p>
                <a href="{{ \App\Helpers\RouteHelpers::pitchUrl($pitch) }}" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                    <i class="fas fa-folder-open mr-2"></i>Manage Files
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
