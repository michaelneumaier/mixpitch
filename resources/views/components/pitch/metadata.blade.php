@props(['pitch'])

@php
    $project = $pitch->project;
    $user = $pitch->user;
@endphp

<div class="bg-gradient-to-br from-white/95 to-indigo-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
    <div class="flex items-center mb-4">
        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl mr-3">
            <i class="fas fa-info-circle text-white"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-800">Pitch Details</h3>
            <p class="text-sm text-gray-600">Producer and project info</p>
        </div>
    </div>

    <div class="space-y-4">
        <!-- Producer Information -->
        <div class="bg-white/60 backdrop-blur-sm border border-indigo-200/30 rounded-xl p-4">
            <div class="flex items-center mb-3">
                <img class="h-10 w-10 rounded-full object-cover mr-3 border-2 border-indigo-200"
                     src="{{ $user->profile_photo_url }}"
                     alt="{{ $user->name }}" />
                <div class="flex-1">
                    <h4 class="text-sm font-bold text-indigo-900">{{ $user->name }}</h4>
                    <p class="text-xs text-indigo-600">Producer</p>
                </div>
            </div>
            @if($user->username)
                <div class="text-xs text-indigo-700">
                    <i class="fas fa-at mr-1"></i>{{ $user->username }}
                </div>
            @endif
        </div>

        <!-- Project Information -->
        @if($project->artist_name)
            <div class="bg-blue-50 border border-blue-200/50 rounded-xl p-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-microphone text-blue-600 mr-2"></i>
                    <span class="text-xs font-medium text-blue-700 uppercase tracking-wide">Artist</span>
                </div>
                <div class="text-sm font-bold text-blue-900">{{ $project->artist_name }}</div>
            </div>
        @endif

        @if($project->genre)
            <div class="bg-purple-50 border border-purple-200/50 rounded-xl p-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-music text-purple-600 mr-2"></i>
                    <span class="text-xs font-medium text-purple-700 uppercase tracking-wide">Genre</span>
                </div>
                <div class="text-sm font-bold text-purple-900">{{ $project->genre }}</div>
            </div>
        @endif

        <!-- Project Type -->
        <div class="bg-green-50 border border-green-200/50 rounded-xl p-4">
            <div class="flex items-center mb-2">
                <i class="fas fa-tag text-green-600 mr-2"></i>
                <span class="text-xs font-medium text-green-700 uppercase tracking-wide">Project Type</span>
            </div>
            <div class="text-sm font-bold text-green-900">{{ ucwords(str_replace('_', ' ', $project->project_type)) }}</div>
        </div>

        <!-- Budget -->
        <div class="bg-emerald-50 border border-emerald-200/50 rounded-xl p-4">
            <div class="flex items-center mb-2">
                <i class="fas fa-money-bill-wave text-emerald-600 mr-2"></i>
                <span class="text-xs font-medium text-emerald-700 uppercase tracking-wide">Budget</span>
            </div>
            <div class="text-sm font-bold text-emerald-900">
                {{ $project->budget == 0 ? 'Free Project' : '$'.number_format($project->budget, 0) }}
                @if($project->budget > 0)
                    <span class="text-xs font-normal text-emerald-700 ml-1">USD</span>
                @endif
            </div>
            @if($project->budget == 0)
                <div class="text-xs text-emerald-600 mt-1">No payment expected</div>
            @endif
        </div>

        <!-- Deadline (if applicable) -->
        @if($project->deadline)
            <div class="bg-amber-50 border border-amber-200/50 rounded-xl p-4">
                <div class="flex items-center mb-2">
                    <i class="fas fa-calendar-alt text-amber-600 mr-2"></i>
                    <span class="text-xs font-medium text-amber-700 uppercase tracking-wide">Deadline</span>
                </div>
                <div class="text-sm font-bold text-amber-900">
                    {{ \Carbon\Carbon::parse($project->deadline)->format('M d, Y') }}
                </div>
                @if(\Carbon\Carbon::parse($project->deadline)->isPast())
                    <div class="text-xs text-red-600 mt-1">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Overdue
                    </div>
                @else
                    <div class="text-xs text-amber-600 mt-1">
                        {{ \Carbon\Carbon::parse($project->deadline)->diffForHumans() }}
                    </div>
                @endif
            </div>
        @endif

        <!-- Pitch Created Date -->
        <div class="bg-gray-50 border border-gray-200/50 rounded-xl p-4">
            <div class="flex items-center mb-2">
                <i class="fas fa-clock text-gray-600 mr-2"></i>
                <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">Pitch Created</span>
            </div>
            <div class="text-sm font-bold text-gray-900">
                {{ $pitch->created_at->format('M d, Y') }}
            </div>
            <div class="text-xs text-gray-600 mt-1">
                {{ $pitch->created_at->diffForHumans() }}
            </div>
        </div>

        <!-- Collaboration Types (if available) -->
        @if($project->collaboration_type && count(array_filter($project->collaboration_type)) > 0)
            <div class="bg-indigo-50 border border-indigo-200/50 rounded-xl p-4">
                <div class="flex items-center mb-3">
                    <i class="fas fa-handshake text-indigo-600 mr-2"></i>
                    <span class="text-xs font-medium text-indigo-700 uppercase tracking-wide">Services Needed</span>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($project->collaboration_type as $key => $value)
                        @php
                            $collaborationType = '';
                            if (is_string($key) && $value && $value !== false) {
                                $collaborationType = $key;
                            } elseif (is_string($value) && !empty($value)) {
                                $collaborationType = $value;
                            } elseif (is_numeric($key) && is_string($value) && !empty($value)) {
                                $collaborationType = $value;
                            }
                            
                            if ($collaborationType) {
                                $collaborationType = str_replace('_', ' ', $collaborationType);
                                $collaborationType = ucwords(strtolower($collaborationType));
                            }
                        @endphp
                        
                        @if($collaborationType)
                            <span class="inline-flex items-center px-2 py-1 bg-indigo-100 text-indigo-800 rounded-lg text-xs font-medium">
                                {{ $collaborationType }}
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div> 