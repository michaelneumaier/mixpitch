{{-- resources/views/dashboard/cards/_pitch_card.blade.php --}}
@php
    // Determine the correct link based on pitch/project type
    $pitchUrl = \App\Helpers\RouteHelpers::pitchUrl($pitch);
    $needsAttention = in_array($pitch->status, [
        \App\Models\Pitch::STATUS_PENDING,
        \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
        \App\Models\Pitch::STATUS_AWAITING_ACCEPTANCE,
        \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
    ]);
    $isOwnerPerspective = $pitch->project->user_id === auth()->id();
    $isClientManagement = $pitch->project->isClientManagement();
@endphp
<div class="mb-4 rounded-lg shadow-sm overflow-hidden border border-base-300 hover:shadow-md transition-all">
    <a href="{{ $pitchUrl }}" class="block">
        <div class="flex flex-col md:flex-row">
            {{-- Project Image --}}
            <div class="w-full md:w-40 h-40 bg-center bg-cover bg-no-repeat"
                 style="background-image: url('{{ $pitch->project->image_path ? $pitch->project->imageUrl : asset('images/default-project.jpg') }}');">
            </div>

            {{-- Pitch Info --}}
            <div class="p-4 flex-grow">
                <div class="flex flex-col md:flex-row md:items-start justify-between">
                    <div>
                        @if($isClientManagement)
                            <span class="inline-block bg-purple-100 text-purple-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-purple-200 dark:text-purple-800">Client Project</span>
                        @else
                            <span class="inline-block bg-purple-100 text-purple-800 text-xs font-semibold mr-2 px-2.5 py-0.5 rounded dark:bg-purple-200 dark:text-purple-800">Pitch</span>
                        @endif
                        <h4 class="text-lg font-semibold text-gray-800 inline">{{ $pitch->project->name }}</h4>
                         <div class="text-sm text-gray-500 mt-1">{{ $pitch->project->readableWorkflowTypeAttribute }}</div>
                    </div>
                    <span class="inline-flex mt-2 md:mt-0 px-3 py-1 rounded-full text-sm font-medium {{ $pitch->getStatusColorClass() }}">
                        {{ $pitch->readable_status }}
                    </span>
                </div>
                
                {{-- Key Details --}}
                <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-600">
                    @if($isClientManagement)
                        {{-- For client management, show client info instead of producer info --}}
                        @if($pitch->project->client_email)
                        <div class="flex items-center">
                            <i class="fas fa-user-tie mr-1.5 text-purple-500"></i>
                            <span>Client: {{ $pitch->project->client_name ?? $pitch->project->client_email }}</span>
                        </div>
                        @endif
                        
                        @if($pitch->payment_amount > 0)
                        <div class="flex items-center">
                            <i class="fas fa-dollar-sign mr-1.5 text-green-500"></i>
                            <span class="font-medium">${{ number_format($pitch->payment_amount, 2) }} Project</span>
                        </div>
                        @endif
                        
                        @if($pitch->project->deadline)
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-1.5"></i>
                            <span>Deadline: {{ \Carbon\Carbon::parse($pitch->project->deadline)->format('M d, Y') }}</span>
                        </div>
                        @endif
                    @else
                        {{-- Standard pitch display --}}
                        @if($isOwnerPerspective)
                            <div class="flex items-center">
                                <i class="fas fa-user mr-1.5 text-blue-500"></i>
                                <span>Pitch by: <x-user-link :user="$pitch->user" /></span>
                            </div>
                        @else
                             <div class="flex items-center">
                                <i class="fas fa-user-tie mr-1.5 text-gray-500"></i>
                                <span>Project Owner: <x-user-link :user="$pitch->project->user" /></span>
                            </div>
                        @endif
                        
                        @if($pitch->project->isContest())
                        <div class="flex items-center">
                            <i class="fas fa-trophy mr-1.5 text-yellow-500"></i>
                            <span class="font-medium">Contest Entry</span>
                             @if($pitch->rank)
                             <span class="ml-1">(Rank: {{ $pitch->rank }})</span>
                             @endif
                        </div>
                        @endif
                    @endif
                </div>

                {{-- Stats / Needs Attention --}}
                <div class="mt-4 flex flex-wrap items-center justify-between">
                    <div class="text-xs text-gray-500">
                        <span>Updated: {{ $pitch->updated_at->diffForHumans() }}</span>
                    </div>
                    <div class="mt-2 md:mt-0 flex gap-2">
                        @if($isClientManagement && $pitch->files->count() > 0)
                        <div class="bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full border border-blue-100 flex items-center">
                            <i class="fas fa-file text-blue-400 mr-1"></i>
                            <span>{{ $pitch->files->count() }} {{ Str::plural('File', $pitch->files->count()) }}</span>
                        </div>
                        @endif
                        
                        @if($needsAttention)
                        <div class="bg-red-50 text-red-700 text-xs px-2 py-1 rounded-full border border-red-100 flex items-center animate-pulse">
                            <i class="fas fa-bell text-red-400 mr-1"></i>
                            <span>Needs Attention</span>
                        </div>
                        @endif
                         {{-- Add Payment attention badge if needed based on perspective --}}
                    </div>
                </div>
            </div>
        </div>
    </a>
</div> 