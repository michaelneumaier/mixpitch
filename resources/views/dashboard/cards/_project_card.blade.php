{{-- resources/views/dashboard/cards/_project_card.blade.php --}}
@php
    $projectUrl = route('projects.manage', $project);
    $needsAttentionCount = $project->pitches->whereIn('status', [
        \App\Models\Pitch::STATUS_PENDING,
        \App\Models\Pitch::STATUS_READY_FOR_REVIEW
    ])->count();
@endphp

<div class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
    <a href="{{ $projectUrl }}" class="block">
        <div class="flex flex-col md:flex-row">
            {{-- Project Image --}}
            <div class="w-full md:w-40 h-40 bg-center bg-cover bg-no-repeat"
                style="background-image: url('{{ $project->image_path ? $project->imageUrl : asset('images/default-project.jpg') }}');">
            </div>

            {{-- Project Info --}}
            <div class="p-4 flex-grow">
                <div class="flex flex-col md:flex-row md:items-start justify-between">
                    <div>
                        <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-blue-200 dark:text-blue-800">Project</span>
                        <h4 class="text-lg font-semibold text-gray-800 inline">{{ $project->name }}</h4>
                        <div class="text-sm text-gray-500 mt-1">{{ $project->readableWorkflowTypeAttribute }}</div>
                    </div>
                    <span class="inline-flex mt-2 md:mt-0 px-3 py-1 rounded-full text-sm font-medium {{ $project->getStatusColorClass() }}">
                        {{ Str::title(str_replace('_', ' ', $project->status)) }}
                    </span>
                </div>
                
                {{-- Key Details --}}
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                    @if($project->isContest() && $project->prize_amount > 0)
                    <div class="flex items-center">
                        <i class="fas fa-trophy mr-1.5 text-yellow-500"></i>
                        <span class="font-medium">{{ Number::currency($project->prize_amount, $project->prize_currency) }} Prize</span>
                    </div>
                    @elseif($project->budget > 0)
                    <div class="flex items-center">
                        <i class="fas fa-dollar-sign mr-1.5 text-green-500"></i>
                        <span class="font-medium">{{ Number::currency($project->budget, 'USD') }} Budget</span>
                    </div>
                    @endif
                    
                    @if($project->deadline)
                    <div class="flex items-center">
                        <i class="fas fa-calendar-alt mr-1.5"></i>
                        <span>Deadline: {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}</span>
                    </div>
                     @endif

                    @if($project->targetProducer)
                    <div class="flex items-center">
                        <i class="fas fa-user-check mr-1.5 text-indigo-500"></i>
                        <span>Assigned: <x-user-link :user="$project->targetProducer" /></span>
                    </div>
                    @endif
                    @if($project->client_email)
                    <div class="flex items-center">
                        <i class="fas fa-user-tie mr-1.5 text-purple-500"></i>
                        <span>Client: {{ $project->client_name ?? $project->client_email }}</span>
                    </div>
                    @endif
                </div>

                {{-- Stats / Needs Attention --}}
                <div class="mt-4 flex flex-wrap items-center justify-between">
                    <div class="text-xs text-gray-500">
                        <span>Updated: {{ $project->updated_at->diffForHumans() }}</span>
                    </div>
                    <div class="mt-2 md:mt-0 flex gap-2">
                        @if($project->pitches->count() > 0)
                        <div class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-100 flex items-center">
                            <i class="fas fa-user-edit text-indigo-400 mr-1"></i>
                            <span>{{ $project->pitches->count() }} {{ Str::plural('Pitch', $project->pitches->count()) }}</span>
                        </div>
                        @endif
                        @if($needsAttentionCount > 0)
                        <div class="bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center animate-pulse">
                            <i class="fas fa-bell text-red-400 mr-1"></i>
                            <span>{{ $needsAttentionCount }} {{ Str::plural('Pitch', $needsAttentionCount) }} need{{ $needsAttentionCount === 1 ? 's' : '' }} attention</span>
                        </div>
                        @endif
                        {{-- Add Payment attention badge if needed --}}
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 