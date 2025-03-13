@extends('components.layouts.app')

@section('content')

<div class="w-full mx-auto">
    <div class="flex justify-center">
        <div class="w-full max-w-6xl mx-auto">
            <div class="shadow mb-4">
                <!-- Adjusted text color for visibility -->
                <div class="py-3 px-4 text-primary text-3xl font-semibold text-center">{{ __('Dashboard') }}</div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-4 pl-2 flex items-center">Your Projects
                        <a href="{{ route('projects.create') }}"
                            class="bg-primary hover:bg-primary-focus text-white text-sm text-center ml-2 py-2 px-4 rounded-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">Share
                            Your Project</a>
                    </h3>
                    @if ($projects->isEmpty())
                    <div class="bg-base-200/50 rounded-lg p-8 text-center">
                        <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">You haven't shared any projects yet.</p>
                        <p class="text-gray-500 text-sm mt-2">Create a project to start collaborating with other
                            creators.</p>
                    </div>
                    @else

                    @foreach ($projects as $project)
                    <div
                        class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
                        <a href="{{ route('projects.manage', $project) }}" class="block">
                            <div class="flex flex-col md:flex-row">
                                <!-- Project Image -->
                                <div class="w-full md:w-40 h-40 bg-center bg-cover bg-no-repeat"
                                    style="background-image: url('{{ $project->image_path ? asset('storage/' . $project->image_path) : asset('images/default-project.jpg') }}');">
                                </div>

                                <!-- Project Info -->
                                <div class="p-4 flex-grow">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                                        <h4 class="text-lg font-semibold text-gray-800 mb-2 md:mb-0">{{ $project->name
                                            }}</h4>

                                        <!-- Project Status Badge -->
                                        <div class="inline-flex px-3 py-1 rounded-full text-sm font-medium
                                            {{ $project->status === 'active' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                               ($project->status === 'closed' ? 'bg-gray-100 text-gray-800 border border-gray-200' : 
                                               'bg-blue-100 text-blue-800 border border-blue-200') }}">
                                            {{ Str::title($project->status) }}
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-4 mt-3">
                                        <!-- Project Type -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-music mr-2"></i>
                                            <span>{{ Str::title($project->project_type) }}</span>
                                        </div>

                                        <!-- Budget -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            <span>{{ $project->budget == 0 ? 'Free' :
                                                '$'.number_format($project->budget, 0) }}</span>
                                        </div>

                                        <!-- Deadline -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            <span>{{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y')
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Project Stats -->
                                    <div class="mt-4 flex flex-wrap items-center justify-between">
                                        <div class="text-xs text-gray-500">
                                            <span>Created on {{ \Carbon\Carbon::parse($project->created_at)->format('M
                                                d, Y') }}</span>
                                        </div>

                                        <!-- Pitch Count Badge -->
                                        <div class="mt-2 md:mt-0 flex gap-2">
                                            <div
                                                class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-100 flex items-center">
                                                <i class="fas fa-user-edit text-indigo-400 mr-1"></i>
                                                <span>{{ $project->pitches->count() }} {{ Str::plural('Pitch',
                                                    $project->pitches->count()) }}</span>
                                            </div>
                                            
                                            @php
                                                $pendingCount = $project->pitches->where('status', \App\Models\Pitch::STATUS_PENDING)->count();
                                                $reviewCount = $project->pitches->where('status', \App\Models\Pitch::STATUS_READY_FOR_REVIEW)->count();
                                                $needsAttentionCount = $pendingCount + $reviewCount;
                                            @endphp
                                            
                                            @if($needsAttentionCount > 0)
                                            <div
                                                class="bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center">
                                                <i class="fas fa-bell text-red-400 mr-1"></i>
                                                <span>{{ $needsAttentionCount }} {{ Str::plural('Pitch', $needsAttentionCount) }} need{{ $needsAttentionCount === 1 ? 's' : '' }} attention</span>
                                            </div>
                                            @endif
                                            
                                            {{-- Removed collaboration types badge --}}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @endforeach


                    @endif
                </div>

                <div class="p-4 md:p-6 lg:p-8">
                    <h3 class="text-2xl text-primary font-semibold mb-4 pl-2">Your Pitches</h3>

                    @forelse($pitches as $pitch)
                    <div
                        class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
                        <a href="{{ route('pitches.show', $pitch->id) }}" class="block">
                            <div class="flex flex-col md:flex-row">
                                <!-- Project Image -->
                                <div class="w-full md:w-40 h-40 bg-center bg-cover bg-no-repeat"
                                    style="background-image: url('{{ $pitch->project->image_path ? asset('storage/' . $pitch->project->image_path) : asset('images/default-project.jpg') }}');">
                                </div>

                                <!-- Project Info -->
                                <div class="p-4 flex-grow">
                                    <div class="flex flex-col md:flex-row md:items-center justify-between">
                                        <h4 class="text-lg font-semibold text-gray-800 mb-2 md:mb-0">{{
                                            $pitch->project->name }}</h4>
                                        <!-- Status Badge -->
                                        <div class="inline-flex px-3 py-1 rounded-full text-sm font-medium
                                            {{ $pitch->status === \App\Models\Pitch::STATUS_PENDING ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                               ($pitch->status === \App\Models\Pitch::STATUS_APPROVED ? 'bg-green-100 text-green-800 border border-green-200' : 
                                               ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED ? 'bg-blue-100 text-blue-800 border border-blue-200' : 
                                               'bg-red-100 text-red-800 border border-red-200')) }}">
                                            {{ $pitch->getReadableStatusAttribute() }}
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap gap-4 mt-3">
                                        <!-- Project Type -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-music mr-2"></i>
                                            <span>{{ Str::title($pitch->project->project_type) }}</span>
                                        </div>

                                        <!-- Budget -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-money-bill-wave mr-2"></i>
                                            <span>{{ $pitch->project->budget == 0 ? 'Free' :
                                                '$'.number_format($pitch->project->budget, 0) }}</span>
                                        </div>

                                        <!-- Deadline -->
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-calendar-alt mr-2"></i>
                                            <span>{{ \Carbon\Carbon::parse($pitch->project->deadline)->format('M d, Y')
                                                }}</span>
                                        </div>
                                    </div>

                                    <!-- Pitch Submission Date -->
                                    <div class="mt-4 flex flex-wrap items-center justify-between">
                                        <div class="text-xs text-gray-500">
                                            <span>Pitch submitted on {{
                                                \Carbon\Carbon::parse($pitch->created_at)->format('M d, Y') }}</span>

                                            @if($pitch->status === \App\Models\Pitch::STATUS_COMPLETED)
                                            <span class="mx-2">•</span>
                                            <span>Completed on {{ \Carbon\Carbon::parse($pitch->completed_at)->format('M
                                                d, Y') }}</span>
                                            @endif
                                        </div>

                                        <!-- Snapshot Count Badge -->
                                        <div class="mt-2 md:mt-0">
                                            <div
                                                class="bg-indigo-50 text-indigo-700 text-xs px-2 py-1 rounded-full border border-indigo-100 flex items-center">
                                                <i class="fas fa-history text-indigo-400 mr-1"></i>
                                                <span>{{ $pitch->snapshots->count() }} {{ Str::plural('Snapshot',
                                                    $pitch->snapshots->count()) }}</span>
                                            </div>
                                            
                                            @if($pitch->status === \App\Models\Pitch::STATUS_READY_FOR_REVIEW)
                                            <div class="mt-1 bg-warning/20 text-amber-700 text-xs px-2 py-1 rounded-full border border-amber-200 flex items-center">
                                                <i class="fas fa-hourglass-half text-amber-500 mr-1"></i>
                                                <span>Pending Review</span>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    @empty
                    <div class="bg-base-200/50 rounded-lg p-8 text-center">
                        <i class="fas fa-folder-open text-gray-400 text-4xl mb-3"></i>
                        <p class="text-gray-600">You haven't submitted any pitches yet.</p>
                        <p class="text-gray-500 text-sm mt-2">Browse projects and submit pitches to collaborate with
                            other creators.</p>
                    </div>
                    @endforelse
                </div>

            </div>
        </div>
    </div>
</div>

@endsection