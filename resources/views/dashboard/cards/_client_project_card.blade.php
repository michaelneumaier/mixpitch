{{-- resources/views/dashboard/cards/_client_project_card.blade.php --}}
@php
    // Generate signed URL for client portal access
    $clientPortalUrl = URL::temporarySignedRoute('client.portal.view', now()->addDays(7), ['project' => $project->id]);
    $pitch = $project->pitches->first();
    $producer = $pitch ? $pitch->user : null;
@endphp

<div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl border border-white/20 shadow-xl hover:shadow-2xl transition-all duration-300 overflow-hidden">
    <!-- Client Project Gradient Border Effect -->
    <div class="absolute inset-0 bg-gradient-to-r from-teal-500/20 via-cyan-500/20 to-blue-500/20 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    
    <a href="{{ $clientPortalUrl }}" class="relative block m-0.5 bg-white/95 backdrop-blur-sm rounded-2xl overflow-hidden">
        <div class="flex flex-col lg:flex-row">
            {{-- Enhanced Project Image --}}
            <div class="relative lg:w-64 h-48 lg:h-auto bg-gradient-to-br from-gray-100 to-gray-200 overflow-hidden">
                @if($project->image_path)
                    <img src="{{ $project->imageUrl }}" 
                         alt="{{ $project->name }}"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                @else
                    <div class="w-full h-full bg-gradient-to-br from-teal-100 via-cyan-100 to-blue-100 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-headphones-alt text-4xl text-teal-400/60 mb-2"></i>
                            <p class="text-sm text-gray-500 font-medium">{{ $project->name }}</p>
                        </div>
                    </div>
                @endif
                
                <!-- Client Project Badge -->
                <div class="absolute bottom-4 left-4">
                    <span class="inline-flex items-center px-3 py-2 rounded-full text-sm font-semibold bg-teal-100/90 text-teal-800 border border-white/20 backdrop-blur-sm shadow-lg">
                        <i class="fas fa-user-circle mr-2"></i>Your Project
                    </span>
                </div>

                <!-- Status Badge -->
                @if($project->status)
                <div class="absolute bottom-4 right-4">
                    @php
                        $statusConfig = [
                            'open' => ['bg' => 'bg-blue-100/90', 'text' => 'text-blue-800', 'label' => 'In Progress'],
                            'in_progress' => ['bg' => 'bg-yellow-100/90', 'text' => 'text-yellow-800', 'label' => 'Active'],
                            'completed' => ['bg' => 'bg-green-100/90', 'text' => 'text-green-800', 'label' => 'Completed'],
                            'unpublished' => ['bg' => 'bg-gray-100/90', 'text' => 'text-gray-800', 'label' => 'Draft'],
                        ];
                        $statusStyle = $statusConfig[$project->status] ?? ['bg' => 'bg-gray-100/90', 'text' => 'text-gray-800', 'label' => ucfirst($project->status)];
                    @endphp
                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold {{ $statusStyle['bg'] }} {{ $statusStyle['text'] }} border border-white/20 backdrop-blur-sm shadow-lg">
                        {{ $statusStyle['label'] }}
                    </span>
                </div>
                @endif
            </div>

            {{-- Project Details --}}
            <div class="flex-1 p-6 lg:p-8">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl lg:text-2xl font-bold text-gray-900 mb-2 group-hover:text-teal-700 transition-colors">
                            {{ $project->name }}
                        </h3>
                        
                        @if($project->description)
                            <p class="text-gray-600 text-sm lg:text-base leading-relaxed mb-4">
                                {{ Str::limit($project->description, 120) }}
                            </p>
                        @endif
                    </div>
                </div>

                {{-- Producer & Project Info --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    @if($producer)
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-teal-400 to-cyan-500 rounded-full flex items-center justify-center mr-3 shadow-md">
                            <i class="fas fa-user text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-medium tracking-wide">Producer</p>
                            <p class="text-gray-900 font-semibold">{{ $producer->name }}</p>
                        </div>
                    </div>
                    @endif

                    @if($project->created_at)
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-gradient-to-br from-blue-400 to-indigo-500 rounded-full flex items-center justify-center mr-3 shadow-md">
                            <i class="fas fa-calendar text-white text-sm"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase font-medium tracking-wide">Created</p>
                            <p class="text-gray-900 font-semibold">{{ $project->created_at->format('M j, Y') }}</p>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1 flex items-center justify-between bg-gradient-to-r from-teal-50 to-cyan-50 rounded-xl p-4 border border-teal-200/50">
                        <div>
                            <p class="text-teal-800 font-semibold text-sm">Client Portal Access</p>
                            <p class="text-teal-600 text-xs">View progress & provide feedback</p>
                        </div>
                        <i class="fas fa-external-link-alt text-teal-500"></i>
                    </div>

                    @if($project->status === 'completed' && $pitch && $pitch->payment_status === 'paid')
                    <div class="flex gap-2">
                        <a href="{{ URL::temporarySignedRoute('client.portal.invoice', now()->addDays(7), ['project' => $project->id]) }}" 
                           class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors text-sm font-medium border border-gray-300">
                            Invoice
                        </a>
                        <a href="{{ URL::temporarySignedRoute('client.portal.deliverables', now()->addDays(7), ['project' => $project->id]) }}" 
                           class="px-4 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg transition-colors text-sm font-medium border border-green-300">
                            Files
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </a>
</div>