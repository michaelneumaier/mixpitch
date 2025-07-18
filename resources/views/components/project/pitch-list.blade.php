@props(['project'])

@php
// Sort pitches to show completed first, then approved, then others
$sortedPitches = $project->pitches->sortBy(function($pitch) {
    if ($pitch->status === 'completed') return 1;
    if ($pitch->status === 'approved') return 2;
    if ($pitch->status === 'closed') return 4;
    return 3; // All other statuses
});

// Check for multiple approved pitches
$hasMultipleApprovedPitches = $project->pitches->where('status', 'approved')->count() > 1;
$hasCompletedPitch = $project->pitches->where('status', 'completed')->count() > 0;
@endphp

<!-- Background Effects -->
<div class="absolute inset-0 overflow-hidden pointer-events-none">
    <div class="absolute top-4 right-4 w-32 h-32 bg-gradient-to-br from-purple-400/10 to-pink-400/10 rounded-full blur-2xl"></div>
    <div class="absolute bottom-8 left-8 w-24 h-24 bg-gradient-to-tr from-blue-400/10 to-purple-400/10 rounded-full blur-xl"></div>
</div>

<div class="relative bg-gradient-to-br from-white/95 to-purple-50/90 backdrop-blur-md border border-white/50 rounded-2xl shadow-xl overflow-hidden">
    <!-- Modern Header -->
    <div class="bg-gradient-to-r from-purple-50/80 to-blue-50/80 backdrop-blur-sm border-b border-purple-200/30 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <div class="flex items-center justify-center w-12 h-12 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl mr-4 shadow-lg">
                    <i class="fas fa-paper-plane text-white text-lg"></i>
                </div>
                <div>
                    <h3 class="text-xl font-bold text-purple-900">Submitted Pitches</h3>
                    <p class="text-sm text-purple-700 mt-1">Review and manage pitch submissions</p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <div class="bg-gradient-to-br from-white/80 to-purple-50/80 backdrop-blur-sm border border-purple-200/50 rounded-xl px-4 py-2 shadow-sm">
                    <div class="text-lg font-bold text-purple-900">{{ $project->pitches->count() }}</div>
                    <div class="text-xs text-purple-600">Total</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-lg font-semibold text-purple-800">Review Pitches</h4>
            <div class="flex items-center">
                <label for="auto-allow-access" class="flex items-center cursor-pointer">
                    <span class="text-sm font-medium text-gray-700 mr-3">Automatically Allow Access</span>
                    <div class="relative">
                        <input type="checkbox" id="auto-allow-access" class="sr-only" wire:model.live="autoAllowAccess">
                        <div class="block bg-gray-200 w-14 h-8 rounded-full"></div>
                        <div class="dot absolute left-1 top-1 bg-white w-6 h-6 rounded-full transition"></div>
                    </div>
                </label>
            </div>
        </div>
        <div class="flex flex-col space-y-4">
        <div class="flex flex-col divide-y divide-white/20">
            @forelse($sortedPitches as $pitch)
                    @php
                        // Determine status-based theming
                        $statusTheme = match($pitch->status) {
                            'completed' => [
                                'bg' => 'bg-gradient-to-br from-green-50/90 to-emerald-50/80',
                                'border' => 'border-green-200/50',
                                'accent' => 'bg-gradient-to-br from-green-500 to-emerald-600',
                                'ring' => 'ring-green-200/50'
                            ],
                            'approved' => $hasMultipleApprovedPitches ? [
                                'bg' => 'bg-gradient-to-br from-amber-50/90 to-orange-50/80',
                                'border' => 'border-amber-200/50',
                                'accent' => 'bg-gradient-to-br from-amber-500 to-orange-600',
                                'ring' => 'ring-amber-200/50'
                            ] : [
                                'bg' => 'bg-gradient-to-br from-blue-50/90 to-indigo-50/80',
                                'border' => 'border-blue-200/50',
                                'accent' => 'bg-gradient-to-br from-blue-500 to-indigo-600',
                                'ring' => 'ring-blue-200/50'
                            ],
                            'denied' => [
                                'bg' => 'bg-gradient-to-br from-red-50/90 to-pink-50/80',
                                'border' => 'border-red-200/50',
                                'accent' => 'bg-gradient-to-br from-red-500 to-pink-600',
                                'ring' => 'ring-red-200/50'
                            ],
                            'revisions_requested' => [
                                'bg' => 'bg-gradient-to-br from-amber-50/90 to-yellow-50/80',
                                'border' => 'border-amber-200/50',
                                'accent' => 'bg-gradient-to-br from-amber-500 to-yellow-600',
                                'ring' => 'ring-amber-200/50'
                            ],
                            'pending_review', 'ready_for_review', 'in_progress' => [
                                'bg' => 'bg-gradient-to-br from-blue-50/90 to-cyan-50/80',
                                'border' => 'border-blue-200/50',
                                'accent' => 'bg-gradient-to-br from-blue-500 to-cyan-600',
                                'ring' => 'ring-blue-200/50'
                            ],
                            'closed' => [
                                'bg' => 'bg-gradient-to-br from-gray-50/90 to-slate-50/80',
                                'border' => 'border-gray-200/50',
                                'accent' => 'bg-gradient-to-br from-gray-500 to-slate-600',
                                'ring' => 'ring-gray-200/50'
                            ],
                            default => [
                                'bg' => 'bg-gradient-to-br from-white/95 to-gray-50/80',
                                'border' => 'border-gray-200/50',
                                'accent' => 'bg-gradient-to-br from-gray-500 to-gray-600',
                                'ring' => 'ring-gray-200/50'
                            ]
                        };
                    @endphp

                    <div wire:key="pitch-{{$pitch->id}}" class="relative group">
                        <!-- Status Accent Bar -->
                        <div class="absolute left-0 top-6 bottom-6 w-1 {{ $statusTheme['accent'] }} rounded-r-full z-10"></div>
                        
                        <!-- Main Card -->
                        <div class="relative {{ $statusTheme['bg'] }} backdrop-blur-md border {{ $statusTheme['border'] }} rounded-2xl shadow-lg hover:shadow-xl transition-all duration-300 hover:{{ $statusTheme['ring'] }} hover:ring-2 overflow-hidden">
                            <!-- Enhanced User Profile Section -->
                            <div class="p-6 pb-4">
                                <div class="flex items-start space-x-4">
                                    <div class="relative flex-shrink-0">
                                        <img class="h-12 w-12 rounded-xl object-cover border-2 border-white shadow-lg ring-2 ring-white/20"
                                    src="{{ $pitch->user->profile_photo_url }}" alt="{{ $pitch->user->name }}" />
                                        <div class="absolute -bottom-1 -right-1 w-4 h-4 {{ $statusTheme['accent'] }} rounded-full border-2 border-white shadow-sm"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center space-x-2 mb-1">
                                                    <a href="{{ route('profile.show', $pitch->user->id) }}" class="font-bold text-gray-900 hover:text-purple-600 transition-colors text-base truncate">{{ $pitch->user->name }}</a>
                                    @if($pitch->status === 'completed')
                                                        <div class="bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 text-xs px-2 py-0.5 rounded-lg font-medium border border-green-200 flex-shrink-0">
                                                            <i class="fas fa-trophy mr-1"></i>Completed
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col sm:flex-row sm:items-center text-sm text-gray-600 gap-1 sm:gap-3">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-calendar-alt mr-1.5 text-gray-400"></i>
                                                        Pitched {{ $pitch->created_at->format('M j, Y') }}
                                                    </div>
                                                    <div class="flex items-center">
                                                        <i class="fas fa-clock mr-1.5 text-gray-400"></i>
                                                        {{ $pitch->created_at->diffForHumans() }}
                                                    </div>
                                                    {{-- License Agreement Status --}}
                                                    @php
                                                        $hasLicenseAgreement = $project->requiresLicenseAgreement() && 
                                                            $project->licenseSignatures()
                                                                ->where('user_id', $pitch->user_id)
                                                                ->where('status', 'active')
                                                                ->exists();
                                                    @endphp
                                                    @if($project->requiresLicenseAgreement())
                                                        <div class="flex items-center">
                                                            @if($hasLicenseAgreement)
                                                                <div class="inline-flex items-center px-2 py-1 bg-green-100 text-green-700 rounded-lg text-xs font-medium border border-green-200">
                                                                    <i class="fas fa-shield-check mr-1.5"></i>
                                                                    License Agreed
                                                                </div>
                                                            @else
                                                                <div class="inline-flex items-center px-2 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-medium border border-amber-200">
                                                                    <i class="fas fa-shield-exclamation mr-1.5"></i>
                                                                    License Pending
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                {{-- Enhanced Rating Display --}}
                                                @if($pitch->status === 'completed' && $pitch->getCompletionRating())
                                                    <div class="mt-2 flex items-center">
                                                        <div class="bg-gradient-to-r from-orange-100 to-amber-100 border border-orange-200 rounded-lg px-3 py-1 flex items-center">
                                                            <div class="flex items-center mr-2">
                                                                @for($i = 1; $i <= 5; $i++)
                                                                    <i class="fas fa-star text-xs {{ $i <= $pitch->getCompletionRating() ? 'text-orange-500' : 'text-gray-300' }}"></i>
                                                                @endfor
                                                            </div>
                                                            <span class="text-sm font-bold text-orange-800">{{ number_format($pitch->getCompletionRating(), 1) }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Status Badge - Moved to top right -->
                                            <div class="flex-shrink-0 sm:ml-4">
                                                <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-bold {{ $pitch->getStatusColorClass() }} border-2 border-white/50 shadow-lg backdrop-blur-sm">
                                                    <div class="w-2 h-2 rounded-full mr-2 {{ $statusTheme['accent'] }}"></div>
                                                    {{ $pitch->readable_status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Enhanced Actions & Payment Section -->
                            @if(
                                ($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch) ||
                                (auth()->id() === $project->user_id && $pitch->status === \App\Models\Pitch::STATUS_COMPLETED) ||
                                (auth()->id() === $project->user_id && in_array($pitch->status, [
                                    \App\Models\Pitch::STATUS_PENDING,
                                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                    \App\Models\Pitch::STATUS_APPROVED,
                                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                    \App\Models\Pitch::STATUS_DENIED,
                                    \App\Models\Pitch::STATUS_COMPLETED
                                ])) ||
                                ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED && $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED)
                            )
                                <div class="px-6 pb-4">
                                    <div class="flex flex-col gap-3">
                                        <!-- Action Buttons Row -->
                                        <div class="flex flex-wrap gap-2">
                                @if($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch)
                                        <livewire:pitch.component.complete-pitch
                                            :key="'complete-pitch-'.$pitch->id" :pitch="$pitch" />
                                @endif

                                            <!-- Enhanced Payment Component for Completed Pitches -->
                                @if(auth()->id() === $project->user_id && 
                                    $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                    (empty($pitch->payment_status) || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED))
                                        <a href="{{ route('projects.pitches.payment.overview', ['project' => $pitch->project, 'pitch' => $pitch]) }}" 
                                                   class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                            <i class="fas fa-credit-card mr-2"></i> Process Payment
                                        </a>
                                @elseif(auth()->id() === $project->user_id && 
                                    $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                    in_array($pitch->payment_status, [\App\Models\Pitch::PAYMENT_STATUS_PAID, \App\Models\Pitch::PAYMENT_STATUS_PROCESSING]))
                                        <a href="{{ route('projects.pitches.payment.receipt', ['project' => $pitch->project, 'pitch' => $pitch]) }}" 
                                                   class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600 text-white rounded-xl font-medium transition-all duration-200 hover:shadow-lg shadow-sm">
                                                    <i class="fas fa-file-invoice-dollar mr-2"></i> View Receipt
                                        </a>
                                @endif

                                @if(auth()->id() === $project->user_id && in_array($pitch->status, [
                                    \App\Models\Pitch::STATUS_PENDING,
                                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                    \App\Models\Pitch::STATUS_APPROVED,
                                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                    \App\Models\Pitch::STATUS_DENIED,
                                    \App\Models\Pitch::STATUS_COMPLETED
                                ]))
                                        <x-update-pitch-status :pitch="$pitch" :status="$pitch->status" />
                                @endif
                            </div>
                                        
                                        <!-- Payment Status Row (if applicable) -->
                                @if ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED && $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED)
                                            <div class="bg-gradient-to-r from-white/80 to-gray-50/80 backdrop-blur-sm border border-gray-200/50 rounded-lg px-3 py-2 shadow-sm w-fit">
                                                <div class="flex items-center text-xs">
                                                    <i class="fas fa-credit-card mr-2 text-gray-500"></i>
                                                    <span class="text-gray-600 font-medium">Payment Status:</span>
                                                    <span class="ml-2 font-bold {{
                                            $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID ? 'text-green-600' :
                                                        ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ? 'text-amber-600' :
                                            ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING ? 'text-blue-600' :
                                            ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED ? 'text-red-600' : 'text-gray-600')))
                                        }}">
                                            {{ Str::title(str_replace('_', ' ', $pitch->payment_status)) }}
                                        </span>
                                                </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                            @endif

                    @if($pitch->snapshots->count() > 0)
                                <!-- Enhanced Snapshots Section -->
                                <div class="mx-6 mb-4 p-4 bg-gradient-to-br from-white/60 to-gray-50/40 backdrop-blur-sm border border-white/40 rounded-xl shadow-inner">
                                    <div class="flex items-center justify-between mb-3">
                                        <div class="flex items-center">
                                            <div class="flex items-center justify-center w-8 h-8 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-lg mr-3">
                                                <i class="fas fa-history text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <h5 class="font-bold text-gray-800 text-sm">Snapshots</h5>
                                                <p class="text-xs text-gray-600">Version history</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <div class="bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 text-xs px-3 py-1 rounded-lg font-bold border border-blue-200 shadow-sm">
                                                {{ $pitch->snapshots->count() }} versions
                                            </div>
                                            @if($pitch->snapshots->count() >= 2)
                                                <button onclick="showVersionComparison({{ $pitch->id }})" 
                                                        class="bg-gradient-to-r from-purple-100 to-pink-100 text-purple-800 text-xs px-3 py-1 rounded-lg font-bold border border-purple-200 shadow-sm hover:from-purple-200 hover:to-pink-200 transition-colors">
                                                    <i class="fas fa-columns mr-1"></i>Compare
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- Version Comparison Modal -->
                                    <div id="versionComparisonModal-{{ $pitch->id }}" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
                                        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                                            <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-6 rounded-t-2xl">
                                                <div class="flex items-center justify-between">
                                                    <div class="flex items-center">
                                                        <i class="fas fa-columns text-2xl mr-3"></i>
                                                        <div>
                                                            <h3 class="text-xl font-bold">Version Comparison</h3>
                                                            <p class="text-purple-100">Compare snapshots from {{ $pitch->user->name }}</p>
                                                        </div>
                                                    </div>
                                                    <button onclick="hideVersionComparison({{ $pitch->id }})" 
                                                            class="text-white hover:text-purple-200 transition-colors">
                                                        <i class="fas fa-times text-2xl"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            
                                            <div class="p-6">
                                                @if($pitch->snapshots->count() >= 2)
                                                    @livewire('file-comparison-player', [
                                                        'snapshots' => $pitch->snapshots->sortByDesc('created_at'),
                                                        'pitchId' => $pitch->id,
                                                        'allowAnnotations' => true
                                                    ])
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                                            @if($pitch->snapshots->where('status', 'pending')->count() > 0 && auth()->id() === $project->user_id)
                                                <div class="bg-gradient-to-r from-amber-500 to-orange-500 text-white text-xs px-3 py-1 rounded-lg font-bold animate-pulse shadow-lg border border-amber-300">
                                                    {{ $pitch->snapshots->where('status', 'pending')->count() }} pending
                                        </div>
                                    @endif
                                </div>
                                    </div>
                                    
                                    <!-- Enhanced Snapshots Grid -->
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @foreach($pitch->snapshots->sortByDesc('created_at') as $snapshot)
                                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
                                                class="group flex items-center p-3 bg-gradient-to-br from-white/80 to-gray-50/60 hover:from-white/90 hover:to-blue-50/60 backdrop-blur-sm border border-white/50 hover:border-blue-200/50 rounded-xl transition-all duration-200 hover:shadow-md hover:scale-[1.02]">
                                                
                                                <!-- Snapshot Icon -->
                                                <div class="w-10 h-10 rounded-xl flex-shrink-0 flex items-center justify-center bg-gradient-to-br from-blue-100 to-indigo-200 text-blue-700 mr-3 shadow-sm group-hover:shadow-md transition-shadow">
                                                <i class="fas fa-camera text-sm"></i>
                                            </div>
                                                
                                                <!-- Snapshot Info -->
                                            <div class="min-w-0 flex-1">
                                                    <div class="font-bold truncate text-sm text-gray-800 group-hover:text-blue-800">
                                                        Version {{ $snapshot->snapshot_data['version'] ?? 'N/A' }}
                                                    </div>
                                                    <div class="text-xs text-gray-500 truncate flex items-center">
                                                        <i class="fas fa-clock mr-1"></i>
                                                        {{ $snapshot->created_at->format('M j, Y g:i A') }}
                                                    </div>
                                            </div>
                                                
                                                <!-- Enhanced Status Badge -->
                                                <div class="ml-2 flex-shrink-0">
                                            @if($snapshot->status === 'accepted')
                                                        <span class="bg-gradient-to-r from-green-100 to-emerald-100 text-green-800 text-xs px-2.5 py-1 rounded-lg font-bold border border-green-200 shadow-sm">
                                                            <i class="fas fa-check mr-1"></i>Accepted
                                                        </span>
                                            @elseif($snapshot->status === 'declined')
                                                        <span class="bg-gradient-to-r from-red-100 to-pink-100 text-red-800 text-xs px-2.5 py-1 rounded-lg font-bold border border-red-200 shadow-sm">
                                                            <i class="fas fa-times mr-1"></i>Declined
                                                        </span>
                                            @elseif($snapshot->status === 'revisions_requested')
                                                        <span class="bg-gradient-to-r from-amber-100 to-orange-100 text-amber-800 text-xs px-2.5 py-1 rounded-lg font-bold border border-amber-200 shadow-sm">
                                                            <i class="fas fa-edit mr-1"></i>Revisions
                                                        </span>
                                            @elseif($snapshot->status === 'revision_addressed')
                                                        <span class="bg-gradient-to-r from-blue-100 to-indigo-100 text-blue-800 text-xs px-2.5 py-1 rounded-lg font-bold border border-blue-200 shadow-sm">
                                                            <i class="fas fa-undo mr-1"></i>Addressed
                                                        </span>
                                            @elseif($snapshot->status === 'pending')
                                                        <span class="bg-gradient-to-r from-yellow-100 to-amber-100 text-yellow-800 text-xs px-2.5 py-1 rounded-lg font-bold border border-yellow-200 shadow-sm animate-pulse">
                                                            <i class="fas fa-clock mr-1"></i>Pending
                                                        </span>
                                            @else
                                                        <span class="bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 text-xs px-2.5 py-1 rounded-lg font-bold border border-gray-200 shadow-sm">
                                                            {{ ucfirst($snapshot->status) }}
                                                        </span>
                                            @endif
                                                </div>
                                        </a>
                                    @endforeach
                            </div>
                        </div>
                    @endif

                    @if($pitch->status === 'completed' && !empty($pitch->completion_feedback))
                                <!-- Enhanced Completion Feedback -->
                                <div class="mx-6 mb-4 p-4 bg-gradient-to-br from-green-50/80 to-emerald-50/70 backdrop-blur-sm border border-green-200/50 rounded-xl shadow-lg">
                                    <div class="flex items-center mb-3">
                                        <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl mr-3 shadow-lg">
                                            <i class="fas fa-comment-alt text-white"></i>
                                        </div>
                                        <div>
                                            <h5 class="font-bold text-green-900">Project Completed</h5>
                                            <p class="text-sm text-green-700">Feedback from project owner</p>
                                        </div>
                                    </div>
                                    <div class="bg-gradient-to-br from-white/80 to-green-50/60 backdrop-blur-sm border border-green-200/30 rounded-lg p-4">
                                        <p class="text-green-800 leading-relaxed">{{ $pitch->completion_feedback }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <!-- Enhanced Empty State -->
                    <div class="text-center py-8 lg:py-12">
                        <!-- Enhanced Icon Container -->
                        <div class="mb-4 lg:mb-6">
                            <div class="flex items-center justify-center w-16 lg:w-20 h-16 lg:h-20 bg-gradient-to-br from-purple-100 to-blue-100 backdrop-blur-sm border border-purple-200/50 rounded-2xl mx-auto shadow-lg">
                                <i class="fas fa-paper-plane text-2xl lg:text-3xl text-purple-500"></i>
                            </div>
                        </div>
                        
                        <!-- Enhanced Text Content -->
                        <div class="mb-4 lg:mb-6">
                            <h4 class="text-lg lg:text-xl font-bold text-gray-800 mb-2">No pitches submitted yet</h4>
                            <p class="text-gray-600 max-w-md mx-auto leading-relaxed">
                                Your project is ready to receive pitches from talented producers. 
                                Once users submit their proposals, they'll appear here for your review.
                            </p>
                    </div>
                    
                    @if($project->isStandard() && $project->status === \App\Models\Project::STATUS_OPEN)
                            <!-- Enhanced Tips Card -->
                            <div class="bg-gradient-to-br from-purple-50/90 to-blue-50/80 backdrop-blur-md border border-purple-200/50 rounded-2xl p-6 max-w-lg mx-auto shadow-lg">
                                <div class="flex items-center mb-4">
                                    <div class="flex items-center justify-center w-10 h-10 bg-gradient-to-br from-purple-500 to-blue-600 rounded-xl mr-3 shadow-lg">
                                        <i class="fas fa-lightbulb text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="font-bold text-purple-900">Boost Your Project</h5>
                                        <p class="text-sm text-purple-700">Tips to attract quality pitches</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left">
                                    <div class="flex items-center p-3 bg-gradient-to-br from-white/60 to-purple-50/40 rounded-lg border border-white/50">
                                        <i class="fas fa-share-alt text-purple-500 mr-3"></i>
                                        <span class="text-sm font-medium text-purple-800">Share on social media</span>
                                    </div>
                                    <div class="flex items-center p-3 bg-gradient-to-br from-white/60 to-blue-50/40 rounded-lg border border-white/50">
                                        <i class="fas fa-file-alt text-blue-500 mr-3"></i>
                                        <span class="text-sm font-medium text-blue-800">Add detailed description</span>
                                    </div>
                                    <div class="flex items-center p-3 bg-gradient-to-br from-white/60 to-purple-50/40 rounded-lg border border-white/50">
                                        <i class="fas fa-music text-purple-500 mr-3"></i>
                                        <span class="text-sm font-medium text-purple-800">Upload reference tracks</span>
                                    </div>
                                    <div class="flex items-center p-3 bg-gradient-to-br from-white/60 to-blue-50/40 rounded-lg border border-white/50">
                                        <i class="fas fa-dollar-sign text-blue-500 mr-3"></i>
                                        <span class="text-sm font-medium text-blue-800">Set clear budget & deadline</span>
                                    </div>
                                </div>
                        </div>
                    @endif
                </div>
            @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    /* Toggle B */
    input:checked ~ .dot {
        transform: translateX(100%);
        background-color: #4f46e5;
    }
    input:checked ~ .block {
        background-color: #c7d2fe;
    }
</style>

<script>
    function showVersionComparison(pitchId) {
        const modal = document.getElementById(`versionComparisonModal-${pitchId}`);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
    }

    function hideVersionComparison(pitchId) {
        const modal = document.getElementById(`versionComparisonModal-${pitchId}`);
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.style.overflow = 'auto';
        }
    }

    // Close modal when clicking outside
    document.addEventListener('click', function(event) {
        if (event.target.id && event.target.id.startsWith('versionComparisonModal-')) {
            const pitchId = event.target.id.replace('versionComparisonModal-', '');
            hideVersionComparison(pitchId);
        }
    });

    // Close modal with escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const openModals = document.querySelectorAll('[id^="versionComparisonModal-"]:not(.hidden)');
            openModals.forEach(modal => {
                const pitchId = modal.id.replace('versionComparisonModal-', '');
                hideVersionComparison(pitchId);
            });
        }
    });
</script> 