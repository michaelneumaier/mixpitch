@extends('components.layouts.app')

@section('content')

<div class="w-full mx-auto">
    <div class="flex justify-center">
        <div class="w-full">
            <div class="shadow mb-4">
                <!-- Adjusted text color for visibility -->
                <div class="py-3 px-4 text-primary text-3xl font-semibold text-center">{{ __('Dashboard') }}</div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-3 pl-2 flex items-center">Your Projects
                        <a href="{{ route('projects.create') }}"
                            class="bg-primary hover:bg-primary-focus text-white text-sm text-center ml-2 py-2 px-4 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Share
                            Your Project</a>
                    </h3>
                    @if ($projects->isEmpty())
                    <p class="mb-3">You haven't shared any projects yet.</p>
                    @else
                    <div class="w-full flex-1">
                        <div class="flex flex-wrap">
                            @foreach ($projects as $project)
                            @livewire('project-card', ['project' => $project, 'isDashboardView' => true],
                            key($project->id))
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-3 pl-2">Your Pitches</h3>
                    <div class="flex">
                        @forelse($pitches as $pitch)
                        <div class="flex flex-row w-full justify-between items-center rounded bg-base-200 m-2">
                            <a href="{{ route('pitches.show', $pitch->id) }}"
                                class="flex flex-row items-center flex-grow">
                                <div class="w-24 h-24 bg-center bg-cover bg-no-repeat rounded-l-lg"
                                    style="background-image: url('{{ $pitch->project->image_path ? asset('storage/' . $pitch->project->image_path) : '' }}');">
                                </div>
                                <div class="p-3 flex flex-col">
                                    <div>{{ $pitch->project->name }}</div>
                                    <div>
                                        <div class="whitespace-nowrap"><span class="label-text">Deadline:
                                            </span>{{
                                            \Carbon\Carbon::parse($pitch->project->deadline)->format('M d, Y') }}</div>
                                    </div>
                                </div>
                            </a>
                            <div class="flex h-full items-center p-3 bg-base-300 rounded-r-lg">
                                {{ $pitch->getReadableStatusAttribute() }}
                            </div>
                            <span>
                                <!-- for($i = 1; $i <= 10; $i++) 
                                    if($i <=$mix->rating)
                                    ★
                                    else
                                    ☆
                                    endif
                                    endfor -->
                            </span>
                        </div>
                        @empty
                        <div class="py-3">You have no pitches started, yet.</div>
                        @endforelse
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection