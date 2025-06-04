@props(['pitch'])

@php
    $user = auth()->user();
    $project = $pitch->project;
    $isPitchOwner = $user && $user->id === $pitch->user_id;
    $isProjectOwner = $user && $user->id === $project->user_id;
    $isAdmin = $user && $user->is_admin;
@endphp

<div class="bg-gradient-to-br from-white/95 to-slate-50/90 backdrop-blur-sm border border-white/50 rounded-2xl p-6 shadow-lg">
    <div class="flex items-center mb-4">
        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-slate-500 to-gray-600 rounded-xl mr-3">
            <i class="fas fa-cog text-white"></i>
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-800">Actions</h3>
            <p class="text-sm text-gray-600">Available options</p>
        </div>
    </div>

    <div class="space-y-3">
        @if($isPitchOwner)
            <!-- Pitch Owner Actions -->
            <a href="{{ route('projects.pitches.show', ['project' => $project, 'pitch' => $pitch]) }}" 
               class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                <i class="fas fa-edit mr-2"></i>Manage Pitch
            </a>
            
            @if(in_array($pitch->status, ['in_progress', 'denied', 'revisions_requested']))
                <button onclick="window.scrollTo({top: document.querySelector('.tracks-container')?.offsetTop - 100 || 0, behavior: 'smooth'})"
                        class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-upload mr-2"></i>Upload Files
                </button>
            @endif

        @elseif($isProjectOwner)
            <!-- Project Owner Actions -->
            <a href="{{ route('projects.manage', $project) }}" 
               class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                <i class="fas fa-tasks mr-2"></i>Manage Project
            </a>
            
            @if($pitch->status === 'ready_for_review')
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-3">
                    <div class="flex items-center text-amber-800 text-sm">
                        <i class="fas fa-clock mr-2 text-amber-600"></i>
                        <span class="font-medium">Pending your review</span>
                    </div>
                </div>
            @endif

        @else
            <!-- Public User Actions -->
            <a href="{{ route('projects.show', $project) }}" 
               class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                <i class="fas fa-eye mr-2"></i>View Project
            </a>
            
            @if($user)
                <a href="{{ route('profile.show', $pitch->user) }}" 
                   class="w-full inline-flex items-center justify-center px-4 py-3 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white rounded-xl font-medium transition-all duration-200 hover:scale-105 hover:shadow-lg">
                    <i class="fas fa-user mr-2"></i>View Producer
                </a>
            @endif
        @endif

        @if($isAdmin)
            <!-- Admin Actions -->
            <div class="border-t border-gray-200 pt-3 mt-3">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Admin Actions</div>
                <div class="space-y-2">
                    <button class="w-full inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-gray-600 to-slate-700 hover:from-gray-700 hover:to-slate-800 text-white rounded-lg text-sm font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-camera mr-2"></i>View Snapshots
                    </button>
                    <button class="w-full inline-flex items-center justify-center px-3 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg text-sm font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-shield-alt mr-2"></i>Admin Panel
                    </button>
                </div>
            </div>
        @endif

        @guest
            <!-- Guest User Actions -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-3">
                <div class="text-center">
                    <p class="text-sm text-blue-800 mb-3">Want to collaborate on projects like this?</p>
                    <a href="{{ route('register') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white rounded-lg text-sm font-medium transition-all duration-200 hover:scale-105">
                        <i class="fas fa-user-plus mr-2"></i>Join MixPitch
                    </a>
                </div>
            </div>
        @endguest
    </div>
</div> 