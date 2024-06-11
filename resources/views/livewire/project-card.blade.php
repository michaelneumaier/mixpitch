<div class="w-full md:w-1/3 lg:w-1/4 mb-4 px-1">
    <div class="transition-all shadow-base-200 shadow-glow hover:shadow-lg hover:shadow-base-300 rounded-lg">
        <div class="relative flex aspect-w-1 aspect-h-1 rounded-lg  cursor-pointer" wire:click="cardClickRoute()">
            <div class="absolute inset-0 bg-center bg-cover bg-no-repeat rounded-t-lg"
                style="background-image: url('{{ $project->image_path ? asset('storage/' . $project->image_path) : '' }}');">
            </div>

            <div class="absolute top-0 right-0">
                <x-project-status-button :status="$project->status" type="top-right" />
            </div>
            @if($project->hasPreviewTrack())
            <div class="flex absolute h-auto w-auto top-auto -bottom-1 -left-1 right-auto z-50 aspect-auto text-sm"
                onclick="event.stopPropagation(); /* handle button click */">
                @livewire('audio-player', ['audioUrl' => $project->previewTrackPath(), 'isInCard' => true])
            </div>
            @endif

        </div>
        <div class="rounded-t-xl shadow-innerGlow shadow-base-200 text-primary bg-cover bg-no-repeat rotate-180 bg-bottom -scale-x-100 "
            style="background-image: url('{{ $project->image_path ? asset('storage/' . $project->image_path) : '' }}');">
            <div
                class="rounded-b-md p-3 shadow-innerGlow shadow-base-200 backdrop-blur-sm rotate-180 -scale-x-100 bg-gradient-to-b from-base-200/60 to-base-200">
                <div class="flex w-full items-center text-xl mb-1">
                    <img class="h-7 w-7 rounded-full object-cover mr-1" src="{{ $project->user->profile_photo_url }}"
                        alt="{{ $project->user->name }}" />
                    <span class="text-base max-w-xs truncate">{{ $project->user->name
                        }}</span>
                </div>
                <h5 class="font-bold truncate"><a href="{{ route('projects.show', $project) }}" class="no-underline">
                        {{ $project->name }}
                    </a></h5>

                <h6 class="text-sm">{{ $project->genre }}</h6>
                @if($isDashboardView)
                <div class="text-sm space-y-1 border-t border-gray-200">
                    <div class="flex justify-between">
                        <span>Files Uploaded:</span>
                        <span class="font-semibold text-primary">{{ $project->files->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Pitches:</span>
                        <span class="font-semibold text-primary">{{ $project->pitches->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Last Updated:</span>
                        <span class="font-semibold text-primary">{{ $project->updated_at->format('F j, Y') }}</span>
                    </div>
                </div>


                @else

                @endif
                @if($isDashboardView)

                <a href="{{ route('projects.manage', $project)}}"
                    class="btn w-full bg-primary hover:bg-primary-focus text-white text-center">
                    Manage Project</a>
                @endif
            </div>
        </div>

    </div>
</div>