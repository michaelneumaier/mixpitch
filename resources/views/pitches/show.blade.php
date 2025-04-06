@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1 sm:px-4">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-6 sm:mb-12">
                <div class="flex flex-col sm:flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative w-full sm:w-1/5 sm:shrink-0 md:w-48">
                        <!-- Mobile image wrapper -->
                        <div class="flex justify-center sm:block">
                            <!-- Image that triggers the lightbox -->
                            @if ($pitch->project->image_path)
                            <img @click="lightbox.isOpen = true" src="{{ $pitch->project->imageUrl }}"
                                class="object-cover rounded-t-lg md:rounded-tl-lg shadow-xl cursor-pointer transition-all h-48 w-full sm:w-auto sm:h-auto"
                                width="600" height="400" alt="{{ $pitch->project->name }}">
                            @else
                            <div
                                class="flex items-center justify-center w-full sm:aspect-square h-40 sm:h-48 object-cover rounded-lg sm:rounded-tl-lg bg-base-200">
                                <i class="fas fa-music text-4xl sm:text-5xl text-base-300"></i>
                            </div>
                            @endif
                        </div>

                        @if ($pitch->project->hasPreviewTrack())
                        <div
                            class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm">
                            @livewire('audio-player', ['audioUrl' => $pitch->project->previewTrackPath(), 'isInCard' =>
                            true])
                        </div>
                        @endif

                        <!-- The actual lightbox overlay -->
                        @if ($pitch->project->image_path)
                        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 transition-all"
                            x-show="lightbox.isOpen" x-transition>
                            <img class="max-h-[80vh] max-w-[90vw] object-contain shadow-2xl rounded"
                                src="{{ $pitch->project->imageUrl }}" alt="Lightbox image"
                                @click.away="lightbox.isOpen = false">
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative pb-0 flex flex-grow flex-col items-center mt-3 sm:mt-0">
                        <div class="w-full flex px-3 flex-col justify-center flex-1">
                            <div class="p-2">
                                <div class="flex flex-col mb-2">
                                    <h2
                                        class="text-xl sm:text-2xl md:text-3xl font-bold break-words text-center sm:text-left">
                                        {{ $pitch->user->name }}'s Pitch
                                    </h2>
                                    <div
                                        class="text-base sm:text-lg md:text-xl font-medium text-gray-700 text-center sm:text-left">
                                        for <a href="{{ route('projects.show', $pitch->project) }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors">"{{
                                            $pitch->project->name }}"</a>
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col md:flex-row md:items-center gap-2 md:gap-6 mt-3 items-center sm:items-start">
                                    @if ($pitch->project->artist_name)
                                    <div class="flex items-center">
                                        <span class="font-semibold mr-1">Artist:</span> {{ $pitch->project->artist_name
                                        }}
                                    </div>
                                    @endif
                                    <div class="flex items-center">
                                        <img class="h-8 w-8 rounded-full object-cover mr-2 border-2 border-base-300"
                                            src="{{ $pitch->project->user->profile_photo_url }}"
                                            alt="{{ $pitch->project->user->name }}" />
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-600">Project Owner</span>
                                            <span class="text-base font-medium">{{ $pitch->project->user->name }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Replace the standard pitch status component with an enhanced version -->
                        <div class="flex w-full mt-2 sm:mt-0">
                            <div
                                class="block tracking-tight text-lg sm:text-xl text-center font-bold grow py-2 px-4
                                {{ $pitch->status === 'completed' ? 'bg-success text-white' : 
                                   ($pitch->status === 'approved' ? 'bg-blue-500 text-white' : 
                                   ($pitch->status === 'closed' ? 'bg-gray-500 text-white' : 
                                   ($pitch->status === 'denied' ? 'bg-red-500 text-white' : 
                                   ($pitch->status === 'pending' ? 'bg-yellow-500 text-black' : 
                                   ($pitch->status === 'in_progress' ? 'bg-blue-400 text-white' : 
                                   ($pitch->status === 'pending_review' ? 'bg-purple-500 text-white' : 
                                   ($pitch->status === 'ready_for_review' ? 'bg-indigo-500 text-white' : 'bg-base-300 text-base-content'))))))) }}">
                                <div class="flex items-center justify-center">
                                    <i
                                        class="fas {{ $pitch->status === 'completed' ? 'fa-trophy' : 
                                                   ($pitch->status === 'approved' ? 'fa-thumbs-up' : 
                                                   ($pitch->status === 'closed' ? 'fa-lock' : 
                                                   ($pitch->status === 'denied' ? 'fa-times-circle' : 
                                                   ($pitch->status === 'pending' ? 'fa-clock' : 
                                                   ($pitch->status === 'in_progress' ? 'fa-spinner' : 
                                                   ($pitch->status === 'pending_review' ? 'fa-search' : 
                                                   ($pitch->status === 'ready_for_review' ? 'fa-clipboard-check' : 'fa-info-circle'))))))) }} mr-2"></i>
                                    {{ ucwords(str_replace('_', ' ', $pitch->status)) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="shadow-lightGlow shadow-base-300 rounded-lg">
                    <!-- Project Management -->
                    <div class="px-3 sm:px-6 py-3 sm:py-4">
                        @if (auth()->check() && auth()->id() === $pitch->user_id)
                        <livewire:pitch.component.manage-pitch :pitch="$pitch" />
                        @endif
                    </div>

                    <!-- Feedback & Revision History -->
                    <div class="px-3 sm:px-6 py-3 sm:py-4 border-t border-base-200">
                        <livewire:pitch.component.feedback-conversation :pitch="$pitch" />
                    </div>

                    <div class="px-3 sm:px-6 py-3 sm:py-4">
                        <!-- Pitch History Timeline -->
                        <livewire:pitch.component.pitch-history :pitch="$pitch" />
                    </div>

                    <div class="px-3 sm:px-6 py-3 sm:py-4">
                        <!-- Rating Card - Only shown for completed pitches with ratings -->
                        @if($pitch->status === 'completed' && $pitch->getCompletionRating())
                        <div class="bg-base-200/30 p-3 sm:p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center text-base sm:text-lg">
                                <i class="fas fa-star text-orange-500 mr-2"></i>Rating
                            </h3>
                            <div class="flex items-center">
                                <span class="text-xl sm:text-2xl font-bold text-gray-800 flex items-center">
                                    {{ number_format($pitch->getCompletionRating(), 1) }}
                                    <span class="text-orange-500 ml-1">★</span>
                                </span>
                                <span class="ml-2 text-gray-600">/ 5</span>
                            </div>
                            <p class="text-sm sm:text-base text-gray-600 mt-2">
                                This pitch received a rating of {{ number_format($pitch->getCompletionRating(), 1) }} out of 5 stars upon completion.
                            </p>
                        </div>
                        @endif
                        
                        <!-- File Downloads -->
                        <div class="bg-base-200/30 p-3 sm:p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 sm:mb-3 flex items-center text-base sm:text-lg">
                                <i class="fas fa-music text-purple-500 mr-2"></i>Project Files

                                @if ($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                            </h3>
                            <div
                                class="p-3 sm:p-4 text-center text-yellow-600 italic bg-yellow-50/50 rounded-md border border-yellow-200">
                                <i class="fas fa-lock mr-2"></i>You don't have access to the project files yet.
                            </div>
                            @elseif($pitch->project->files->isEmpty())
                            </h3>
                            <div class="p-3 sm:p-4 text-center text-gray-500 italic">
                                <p>No files have been uploaded for this project</p>
                            </div>
                            @else
                            <a href="{{ route('projects.download', $pitch->project) }}"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-semibold ml-2 py-1 px-2 sm:px-3 rounded text-xs sm:text-sm shadow-sm transition-colors flex items-center">
                                <i class="fas fa-download mr-1"></i> Download All
                            </a>
                            </h3>
                            <!-- Main Download All Button -->
                            <div class="divide-y divide-base-300/50">
                                @foreach ($pitch->project->files as $file)
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between py-2 sm:py-3 px-2 {{ $loop->even ? 'bg-base-200/30' : '' }}">
                                    <div class="flex items-center truncate mb-2 sm:mb-0">
                                        <i class="fas fa-file-audio text-gray-500 mr-2 sm:mr-3"></i>
                                        <span class="font-medium text-gray-800 truncate text-sm sm:text-base"
                                            title="{{ $file->file_name }}">
                                            {{ $file->file_name }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between sm:justify-end gap-2 sm:gap-4 pl-6 sm:pl-0">
                                        <span class="text-xs sm:text-sm text-gray-500">{{ $file->formatted_size
                                            }}</span>
                                        <a href="{{ asset('storage/' . $file->file_path) }}"
                                            download="{{ $file->file_name }}"
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1.5 sm:py-1 px-3 rounded text-xs sm:text-sm shadow-sm transition-colors inline-flex items-center">
                                            <i class="fas fa-download mr-1"></i> Get
                                        </a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <!-- Budget Section -->

                        <div class="bg-base-200/30 p-3 sm:p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center text-base sm:text-lg">
                                <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Budget
                            </h3>
                            <div class="flex items-center">
                                <span class="text-xl sm:text-2xl font-bold text-gray-800">
                                    {{ $pitch->project->budget == 0 ? 'Free Project' :
                                    '$'.number_format($pitch->project->budget,
                                    0) }}
                                </span>
                                @if($pitch->project->budget > 0)
                                <span class="ml-2 text-gray-600">USD</span>
                                @endif
                            </div>
                            <p class="text-sm sm:text-base text-gray-600 mt-2">
                                @if($pitch->project->budget == 0)
                                This is a free collaboration project. No payment is expected.
                                @else
                                This is the budget allocated for this project. The final payment may vary based on
                                project requirements and
                                agreement with the collaborator.
                                @endif
                            </p>
                        </div>
                        <!-- Project Details Section -->
                        <div class="bg-base-200/30 p-3 sm:p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center text-base sm:text-lg">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>Project Description
                            </h3>
                            <p class="text-sm sm:text-base text-gray-700 whitespace-pre-wrap">{{
                                $pitch->project->description }}</p>
                        </div>

                        <!-- Collaboration Types if needed -->
                        @if ($pitch->project->collaboration_type &&
                        count(array_filter($pitch->project->collaboration_type)) > 0)
                        <div class="bg-base-200/30 p-3 sm:p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 sm:mb-3 flex items-center text-base sm:text-lg">
                                <i class="fas fa-handshake text-indigo-500 mr-2"></i>Looking For Collaboration In
                            </h3>
                            <div class="flex flex-wrap gap-2 mt-2 sm:mt-3">
                                @foreach($pitch->project->collaboration_type as $type => $value)
                                @if($value)
                                <span
                                    class="inline-flex items-center px-2 sm:px-3 py-1 rounded-full text-xs sm:text-sm font-medium bg-primary/10 text-primary">
                                    <i class="fas {{ 
                                                                $type == 'mixing' ? 'fa-sliders-h' : 
                                                                ($type == 'mastering' ? 'fa-compact-disc' : 
                                                                ($type == 'production' ? 'fa-music' : 
                                                                ($type == 'vocals' ? 'fa-microphone' : 
                                                                ($type == 'instruments' ? 'fa-guitar' : 'fa-tasks')))) 
                                                            }} mr-1.5"></i>
                                    {{ Str::title(str_replace('_', ' ', $type)) }}
                                </span>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if ($pitch->project->notes)
                        <div class="bg-base-200/30 p-3 sm:p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center text-base sm:text-lg">
                                <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>Additional Notes
                            </h3>
                            <p class="text-sm sm:text-base text-gray-700 whitespace-pre-wrap">{{ $pitch->project->notes
                                }}</p>
                        </div>
                        @endif
                        <!-- Pitch Delete Component - Only visible to pitch owner -->
                        @if(Auth::check() && Auth::id() === $pitch->user_id)
                        <livewire:pitch.component.delete-pitch :pitch="$pitch" />
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
        <!-- Admin Actions -->
    @if(Auth::check() && Auth::user()->is_admin)
    <div class="mt-4 sm:mt-6 bg-slate-100 p-3 sm:p-4 rounded-lg mx-2 sm:mx-auto max-w-screen-lg">
        <h3 class="text-base sm:text-lg font-semibold mb-2">Admin Actions</h3>
        <div class="flex flex-wrap gap-2">
            <a href="#" class="btn btn-sm text-xs sm:text-sm py-1.5 sm:py-1">View All Snapshots</a>
            <a href="#" class="btn btn-sm btn-info text-xs sm:text-sm py-1.5 sm:py-1">Edit Pitch Details</a>
        </div>
    </div>
    @endif

    @endsection
</div>

