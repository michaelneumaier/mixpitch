@extends('components.layouts.app')

@section('content')

<div class="w-full mx-auto">
    <div class="flex justify-center">
        <div class="w-full">
            <!-- removed the background color from the card -->
            <div class="shadow mb-4">
                <!-- Adjusted text color for visibility -->
                <div class="py-3 px-4 text-primary text-3xl font-semibold text-center">{{ __('Dashboard') }}</div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-3 pl-2">Your Projects</h3>
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
                    <a href="{{ route('projects.upload') }}"
                        class="block bg-primary hover:bg-accent text-white text-lg text-center font-bold w-full py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Share
                        Your Project</a>
                </div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-lg md:text-xl lg:text-2xl font-semibold mb-3">Your Uploaded Mixes</h3>
                    <ul class="divide-y divide-gray-700">
                        @forelse($mixes as $mix)
                        <li class="py-3 flex justify-between items-center">
                            <a href="{{ route('projects.show', $project) }}"
                                class="hover:underline hover:text-gray-300">
                                {{ $mix->project->name }}
                            </a>
                            <span>
                                @for($i = 1; $i <= 10; $i++) @if($i <=$mix->rating)
                                    ★
                                    @else
                                    ☆
                                    @endif
                                    @endfor
                            </span>
                        </li>
                        @empty
                        <li class="py-3">You haven't uploaded any mixes yet.</li>
                        @endforelse
                    </ul>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection