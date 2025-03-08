@extends('components.layouts.app')

@section('content')
<div class="container mx-auto px-1">
    <div class="flex justify-center">
        <div class="w-full lg:w-3/4 2xl:w-2/3">
            <div class="border-transparent shadow-2xl shadow-base-300 rounded-lg mb-12">
                <div class="flex flex-row shadow-lightGlow shadow-base-300">
                    <!-- Project Image on the Left -->
                    <div x-data="{ lightbox: { isOpen: false } }" class="relative shrink-0 w-1/5 md:w-48">

                        <!-- Image that triggers the lightbox -->
                        @if ($pitch->project->image_path)
                        <img @click="lightbox.isOpen = true" src="{{ asset('storage/' . $pitch->project->image_path) }}"
                            alt="{{ $pitch->project->name }}"
                            class="md:aspect-square h-48 object-cover md:rounded-tl-lg cursor-pointer" />
                        @else
                        <div
                            class="flex items-center justify-center w-full md:aspect-square h-48 object-cover md:rounded-tl-lg bg-base-200">
                            <i class="fas fa-music text-5xl text-base-300"></i>
                        </div>
                        @endif
                        @if ($pitch->project->hasPreviewTrack())
                        <div
                            class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm">
                            @livewire('audio-player', ['audioUrl' => $pitch->project->previewTrackPath(), 'isInCard' =>
                            true])
                        </div>
                        @endif

                        <!-- The actual lightbox overlay -->
                        @if ($pitch->project->image_path)
                        <div x-cloak x-show="lightbox.isOpen"
                            class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-75 flex justify-center items-center z-50">
                            <img @click="lightbox.isOpen = false"
                                src="{{ asset('storage/' . $pitch->project->image_path) }}" alt="Lightbox image"
                                class="max-w-full max-h-full">

                            <!-- Close button -->
                            <button @click="lightbox.isOpen = false"
                                class="absolute top-4 right-4 text-white bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full p-2 transition-colors">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        @endif
                    </div>

                    <!-- Project Details on the Right -->
                    <div class="relative pb-0 flex flex-grow flex-col items-center">
                        <div class="w-full flex px-3 flex-col justify-center flex-1">
                            <div class="p-2">
                                <div class="flex flex-col mb-2">
                                    <h2 class="text-2xl md:text-3xl font-bold break-words">
                                        {{ $pitch->user->name }}'s Pitch
                                    </h2>
                                    <div class="text-lg md:text-xl font-medium text-gray-700">
                                        for <a href="{{ route('projects.show', $pitch->project) }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors">"{{
                                            $pitch->project->name }}"</a>
                                    </div>
                                </div>
                                <div class="flex flex-col md:flex-row md:items-center gap-2 md:gap-6 mt-3">
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
                        <div class="flex w-full">
                            <div
                                class="block tracking-tight text-xl text-center font-bold grow py-2 px-4
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
                    <div class="px-6 py-4">
                        @if (auth()->check() && auth()->id() === $pitch->user_id)
                        <livewire:pitch.component.manage-pitch :pitch="$pitch" />
                        @endif
                    </div>

                    <!-- Feedback & Revision History -->
                    <div class="px-6 py-4 border-t border-base-200">
                        <livewire:pitch.component.feedback-conversation :pitch="$pitch" />
                    </div>

                    <div class="px-6 py-4">
                        <!-- Pitch History Timeline -->
                        <livewire:pitch.component.pitch-history :pitch="$pitch" />
                    </div>

                    <div class="px-6 py-4">
                        <!-- File Downloads -->
                        <div class="bg-base-200/30 p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-music text-purple-500 mr-2"></i>Project Files

                                @if ($pitch->status === \App\Models\Pitch::STATUS_PENDING)
                            </h3>
                            <div
                                class="p-4 text-center text-yellow-600 italic bg-yellow-50/50 rounded-md border border-yellow-200">
                                <i class="fas fa-lock mr-2"></i>You don't have access to the project files yet.
                            </div>
                            @elseif($pitch->project->files->isEmpty())
                            </h3>
                            <div class="p-4 text-center text-gray-500 italic">
                                <p>No files have been uploaded for this project</p>
                            </div>
                            @else
                            <a href="{{ route('projects.download', $pitch->project) }}"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-semibold ml-2 py-1 px-3 rounded text-sm shadow-sm transition-colors flex items-center">
                                <i class="fas fa-download mr-1"></i> Download All
                            </a>
                            </h3>
                            <!-- Main Download All Button -->
                            <div class="divide-y divide-base-300/50">
                                @foreach ($pitch->project->files as $file)
                                <div
                                    class="flex items-center justify-between py-3 px-2 {{ $loop->even ? 'bg-base-200/30' : '' }}">
                                    <div class="flex items-center truncate">
                                        <i class="fas fa-file-audio text-gray-500 mr-3"></i>
                                        <span class="font-medium text-gray-800 truncate" title="{{ $file->file_name }}">
                                            {{ $file->file_name }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="text-sm text-gray-500">{{ $file->formatted_size }}</span>
                                        <a href="{{ asset('storage/' . $file->file_path) }}"
                                            download="{{ $file->file_name }}"
                                            class="bg-blue-500 hover:bg-blue-700 text-white font-semibold py-1 px-3 rounded text-sm shadow-sm transition-colors inline-flex items-center">
                                            <i class="fas fa-download mr-1"></i> Get
                                        </a>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        <!-- Budget Section -->

                        <div class="bg-base-200/30 p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>Budget
                            </h3>
                            <div class="flex items-center">
                                <span class="text-2xl font-bold text-gray-800">
                                    {{ $pitch->project->budget == 0 ? 'Free Project' :
                                    '$'.number_format($pitch->project->budget,
                                    0) }}
                                </span>
                                @if($pitch->project->budget > 0)
                                <span class="ml-2 text-gray-600">USD</span>
                                @endif
                            </div>
                            <p class="text-gray-600 mt-2">
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
                        <div class="bg-base-200/30 p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-align-left text-blue-500 mr-2"></i>Project Description
                            </h3>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $pitch->project->description }}</p>
                        </div>

                        <!-- Collaboration Types if needed -->
                        @if ($pitch->project->collaboration_type &&
                        count(array_filter($pitch->project->collaboration_type)) > 0)
                        <div class="bg-base-200/30 p-4 rounded-lg mb-4">
                            <h3 class="font-semibold text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-handshake text-indigo-500 mr-2"></i>Looking For Collaboration In
                            </h3>
                            <div class="flex flex-wrap gap-3">
                                @foreach ($pitch->project->collaboration_type as $type => $value)
                                @if ($value)
                                <div
                                    class="flex flex-col items-center bg-white rounded-lg p-3 shadow-sm border border-indigo-100 hover:shadow-md transition-all">
                                    <div class="text-indigo-500 text-xl mb-1">
                                        <i class="fas {{ 
                                            $type == 'mixing' ? 'fa-sliders-h' : 
                                            ($type == 'mastering' ? 'fa-compact-disc' : 
                                            ($type == 'production' ? 'fa-music' : 
                                            ($type == 'vocals' ? 'fa-microphone' : 
                                            ($type == 'instruments' ? 'fa-guitar' : 'fa-tasks')))) 
                                        }}"></i>
                                    </div>
                                    <span class="text-center font-medium text-gray-800">
                                        {{ Str::title(str_replace('_', ' ', $type)) }}
                                    </span>
                                </div>
                                @endif
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if ($pitch->project->notes)
                        <div class="bg-base-200/30 p-4 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>Additional Notes
                            </h3>
                            <p class="text-gray-700 whitespace-pre-wrap">{{ $pitch->project->notes }}</p>
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
</div>

<!-- Admin Actions -->
@if(Auth::check() && Auth::user()->is_admin)
<div class="mt-6 bg-slate-100 p-4 rounded-lg">
    <h3 class="text-lg font-semibold mb-2">Admin Actions</h3>
    <div class="flex flex-wrap gap-2">
        <a href="#" class="btn btn-sm">View All Snapshots</a>
        <a href="#" class="btn btn-sm btn-info">Edit Pitch Details</a>
    </div>
</div>
@endif




@endsection