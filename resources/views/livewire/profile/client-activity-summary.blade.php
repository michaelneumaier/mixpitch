<div class="bg-white shadow-sm rounded-lg p-6">
    <h2 class="text-xl font-semibold text-gray-900 mb-4">
        Client Activity
    </h2>

    <!-- Stats Section -->
    <div class="mb-6 text-sm text-gray-600">
        <p><span class="font-semibold text-gray-700">Total Projects Posted:</span> {{ $totalProjects }}</p>
        <p class="mt-1"><span class="font-semibold text-gray-700">Projects Hired:</span> {{ $hiredProjectsCount }}</p>
    </div>

    @if(($totalProjects > 0 || $hiredProjectsCount > 0) && ($recentProjects->isNotEmpty() || $completedProjects->isNotEmpty()))
    <hr class="my-6 border-gray-200">
    @endif

    @if ($recentProjects->isNotEmpty())
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Recent Projects</h3>
            <ul class="divide-y divide-gray-200">
                @foreach ($recentProjects as $project)
                    <li class="py-3">
                        <div>
                            <a href="{{ route('projects.show', $project->slug) }}" class="text-indigo-600 hover:underline font-medium">
                                {{ $project->name }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">
                                Status: <span class="font-semibold">{{ Str::title(str_replace('_', ' ', $project->status)) }}</span> -
                                Posted: {{ $project->created_at->diffForHumans() }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($completedProjects->isNotEmpty())
        <div>
            <h3 class="text-lg font-medium text-gray-800 mb-3">Project History (Completed)</h3>
            <ul class="divide-y divide-gray-200">
                @foreach ($completedProjects as $project)
                    <li class="py-3">
                        <div>
                            <a href="{{ route('projects.show', $project->slug) }}" class="text-indigo-600 hover:underline font-medium">
                                {{ $project->name }}
                            </a>
                            <p class="text-xs text-gray-500 mt-1">
                                Status: <span class="font-semibold">{{ Str::title(str_replace('_', ' ', $project->status)) }}</span> -
                                Completed: {{ $project->completed_at ? $project->completed_at->diffForHumans() : 'N/A' }}
                            </p>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Show empty state only if there are no stats AND no projects lists --}}
    @if ($totalProjects == 0 && $hiredProjectsCount == 0 && $recentProjects->isEmpty() && $completedProjects->isEmpty())
        <p class="text-sm text-gray-500 italic">No project activity to display yet.</p>
    @endif

</div>
