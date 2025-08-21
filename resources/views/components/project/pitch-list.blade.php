@props(['project', 'workflowColors' => null, 'semanticColors' => null])

@php
// Provide default workflow colors if not passed from parent
$workflowColors = $workflowColors ?? match($project->workflow_type) {
    'standard' => [
        'bg' => 'bg-blue-50 dark:bg-blue-950',
        'border' => 'border-blue-200 dark:border-blue-800',
        'text_primary' => 'text-blue-900 dark:text-blue-100',
        'text_secondary' => 'text-blue-700 dark:text-blue-300',
        'text_muted' => 'text-blue-600 dark:text-blue-400',
        'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
        'icon' => 'text-blue-600 dark:text-blue-400'
    ],
    'contest' => [
        'bg' => 'bg-orange-50 dark:bg-orange-950',
        'border' => 'border-orange-200 dark:border-orange-800',
        'text_primary' => 'text-orange-900 dark:text-orange-100',
        'text_secondary' => 'text-orange-700 dark:text-orange-300',
        'text_muted' => 'text-orange-600 dark:text-orange-400',
        'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
        'icon' => 'text-orange-600 dark:text-orange-400'
    ],
    'direct_hire' => [
        'bg' => 'bg-green-50 dark:bg-green-950',
        'border' => 'border-green-200 dark:border-green-800',
        'text_primary' => 'text-green-900 dark:text-green-100',
        'text_secondary' => 'text-green-700 dark:text-green-300',
        'text_muted' => 'text-green-600 dark:text-green-400',
        'accent_bg' => 'bg-green-100 dark:bg-green-900',
        'icon' => 'text-green-600 dark:text-green-400'
    ],
    'client_management' => [
        'bg' => 'bg-purple-50 dark:bg-purple-950',
        'border' => 'border-purple-200 dark:border-purple-800',
        'text_primary' => 'text-purple-900 dark:text-purple-100',
        'text_secondary' => 'text-purple-700 dark:text-purple-300',
        'text_muted' => 'text-purple-600 dark:text-purple-400',
        'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
        'icon' => 'text-purple-600 dark:text-purple-400'
    ],
    default => [
        'bg' => 'bg-gray-50 dark:bg-gray-950',
        'border' => 'border-gray-200 dark:border-gray-800',
        'text_primary' => 'text-gray-900 dark:text-gray-100',
        'text_secondary' => 'text-gray-700 dark:text-gray-300',
        'text_muted' => 'text-gray-600 dark:text-gray-400',
        'accent_bg' => 'bg-gray-100 dark:bg-gray-900',
        'icon' => 'text-gray-600 dark:text-gray-400'
    ]
};

// Semantic colors for status-based theming (consistent across workflows)
$semanticColors = $semanticColors ?? [
    'success' => [
        'bg' => 'bg-green-50 dark:bg-green-950',
        'border' => 'border-green-200 dark:border-green-800',
        'text' => 'text-green-800 dark:text-green-200',
        'icon' => 'text-green-600 dark:text-green-400',
        'accent' => 'bg-green-500'
    ],
    'warning' => [
        'bg' => 'bg-amber-50 dark:bg-amber-950',
        'border' => 'border-amber-200 dark:border-amber-800',
        'text' => 'text-amber-800 dark:text-amber-200',
        'icon' => 'text-amber-600 dark:text-amber-400',
        'accent' => 'bg-amber-500'
    ],
    'danger' => [
        'bg' => 'bg-red-50 dark:bg-red-950',
        'border' => 'border-red-200 dark:border-red-800',
        'text' => 'text-red-800 dark:text-red-200',
        'icon' => 'text-red-600 dark:text-red-400',
        'accent' => 'bg-red-500'
    ]
];

// Sort pitches to show completed first, then approved, then others
$sortedPitches = $project->pitches->sortBy(function($pitch) {
    return match($pitch->status) {
        'completed' => 1,
        'approved' => 2,
        'revisions_requested' => 3,
        'closed' => 5,
        default => 4
    };
});

// Check for multiple approved pitches
$hasMultipleApprovedPitches = $project->pitches->where('status', 'approved')->count() > 1;
$hasCompletedPitch = $project->pitches->where('status', 'completed')->count() > 0;
@endphp

@php
// Create workflow-aware gradient classes similar to project-workflow-status
$gradientClasses = match($project->workflow_type) {
    'standard' => [
        'outer' => 'bg-gradient-to-br from-blue-50/95 to-indigo-50/90 backdrop-blur-sm border border-blue-200/50',
        'header' => 'bg-gradient-to-r from-blue-100/80 to-indigo-100/80 border-b border-blue-200/30',
        'text_primary' => 'text-blue-900',
        'text_secondary' => 'text-blue-700',
        'text_muted' => 'text-blue-600',
        'icon' => 'text-blue-600'
    ],
    'contest' => [
        'outer' => 'bg-gradient-to-br from-amber-50/95 to-yellow-50/90 backdrop-blur-sm border border-amber-200/50',
        'header' => 'bg-gradient-to-r from-amber-100/80 to-yellow-100/80 border-b border-amber-200/30',
        'text_primary' => 'text-amber-900',
        'text_secondary' => 'text-amber-700',
        'text_muted' => 'text-amber-600',
        'icon' => 'text-amber-600'
    ],
    'direct_hire' => [
        'outer' => 'bg-gradient-to-br from-green-50/95 to-emerald-50/90 backdrop-blur-sm border border-green-200/50',
        'header' => 'bg-gradient-to-r from-green-100/80 to-emerald-100/80 border-b border-green-200/30',
        'text_primary' => 'text-green-900',
        'text_secondary' => 'text-green-700',
        'text_muted' => 'text-green-600',
        'icon' => 'text-green-600'
    ],
    'client_management' => [
        'outer' => 'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 backdrop-blur-sm border border-purple-200/50',
        'header' => 'bg-gradient-to-r from-purple-100/80 to-indigo-100/80 border-b border-purple-200/30',
        'text_primary' => 'text-purple-900',
        'text_secondary' => 'text-purple-700',
        'text_muted' => 'text-purple-600',
        'icon' => 'text-purple-600'
    ],
    default => [
        'outer' => 'bg-gradient-to-br from-gray-50/95 to-slate-50/90 backdrop-blur-sm border border-gray-200/50',
        'header' => 'bg-gradient-to-r from-gray-100/80 to-slate-100/80 border-b border-gray-200/30',
        'text_primary' => 'text-gray-900',
        'text_secondary' => 'text-gray-700',
        'text_muted' => 'text-gray-600',
        'icon' => 'text-gray-600'
    ]
};
@endphp

<div class="{{ $gradientClasses['outer'] }} rounded-2xl shadow-lg overflow-hidden">
    <!-- Professional Header matching workflow-status style -->
    <div class="{{ $gradientClasses['header'] }} p-6">
        <div class="flex items-center justify-between">
                <div>
                <h3 class="text-lg font-bold {{ $gradientClasses['text_primary'] }} flex items-center">
                    <flux:icon.paper-airplane class="w-5 h-5 {{ $gradientClasses['icon'] }} mr-3" />
                    Submitted Pitches
                </h3>
                <p class="text-sm {{ $gradientClasses['text_secondary'] }} mt-1">
                    @if($project->pitches->count() > 0)
                        {{ $project->pitches->count() }} {{ Str::plural('submission', $project->pitches->count()) }} received
                    @else
                        Ready to receive pitch submissions
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-4">
                <!-- Metrics display -->
                <div class="text-right">
                    <div class="text-2xl font-bold {{ $gradientClasses['icon'] }}">{{ $project->pitches->count() }}</div>
                    <div class="text-xs {{ $gradientClasses['text_muted'] }}">Pitches</div>
                </div>
                <!-- Auto-allow toggle -->
                <div class="bg-white/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 rounded-xl px-3 py-2">
                    <div class="flex items-center gap-2">
                        <flux:label class="text-xs {{ $gradientClasses['text_secondary'] }} font-medium">Auto-allow access</flux:label>
                        <flux:switch wire:model.live="autoAllowAccess" wire:loading.attr="disabled" wire:target="autoAllowAccess" size="sm" />
                        <div wire:loading wire:target="autoAllowAccess" class="text-xs {{ $gradientClasses['text_muted'] }}">...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="p-6">
        <div class="space-y-4">
            @forelse($sortedPitches as $pitch)
                    @php
                        // Clean semantic status theming
                        $statusColors = match($pitch->status) {
                            'completed' => [
                                'bg' => $semanticColors['success']['bg'],
                                'border' => $semanticColors['success']['border'],
                                'accent' => $semanticColors['success']['accent'],
                                'badge' => 'green'
                            ],
                            'approved' => [
                                'bg' => $hasMultipleApprovedPitches ? $semanticColors['warning']['bg'] : 'bg-blue-50 dark:bg-blue-950',
                                'border' => $hasMultipleApprovedPitches ? $semanticColors['warning']['border'] : 'border-blue-200 dark:border-blue-800',
                                'accent' => $hasMultipleApprovedPitches ? $semanticColors['warning']['accent'] : 'bg-blue-500',
                                'badge' => $hasMultipleApprovedPitches ? 'amber' : 'blue'
                            ],
                            'denied' => [
                                'bg' => $semanticColors['danger']['bg'],
                                'border' => $semanticColors['danger']['border'],
                                'accent' => $semanticColors['danger']['accent'],
                                'badge' => 'red'
                            ],
                            'revisions_requested' => [
                                'bg' => $semanticColors['warning']['bg'],
                                'border' => $semanticColors['warning']['border'],
                                'accent' => $semanticColors['warning']['accent'],
                                'badge' => 'amber'
                            ],
                            'closed' => [
                                'bg' => 'bg-gray-50 dark:bg-gray-950',
                                'border' => 'border-gray-200 dark:border-gray-800',
                                'accent' => 'bg-gray-500',
                                'badge' => 'gray'
                            ],
                            default => [
                                'bg' => 'bg-white dark:bg-gray-800',
                                'border' => 'border-gray-200 dark:border-gray-700',
                                'accent' => $gradientClasses['icon'],
                                'badge' => 'gray'
                            ]
                        };
                    @endphp

                    <div wire:key="pitch-{{$pitch->id}}" class="relative group">
                        <!-- Minimal Status Accent Bar -->
                        <div class="absolute left-0 top-6 bottom-6 w-1 {{ $statusColors['accent'] }} rounded-r-full z-10"></div>
                        
                        <!-- Main Card -->
                        <div class="relative transition-all duration-200 hover:shadow-lg bg-white/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 rounded-xl overflow-hidden {{ $statusColors['bg'] }} {{ $statusColors['border'] }}">
                            <!-- Enhanced User Profile Section -->
                            <div class="p-4 pb-3">
                                <div class="flex items-start gap-3">
                                    <div class="relative flex-shrink-0">
                                        <flux:avatar size="md" src="{{ $pitch->user->profile_photo_url }}" alt="{{ $pitch->user->name }}" />
                                        <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 {{ $statusColors['accent'] }} rounded-full border-2 border-white dark:border-gray-800"></div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <a href="{{ route('profile.show', $pitch->user->id) }}" class="font-bold text-gray-900 dark:text-gray-100 hover:{{ $gradientClasses['text_muted'] }} transition-colors text-base truncate">{{ $pitch->user->name }}</a>
                                    @if($pitch->status === 'completed')
                                                        <flux:badge color="green" size="sm">
                                                            <flux:icon.trophy class="w-3 h-3 mr-1" />Completed
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                                <div class="flex flex-col sm:flex-row sm:items-center text-sm text-gray-600 dark:text-gray-400 gap-1 sm:gap-3">
                                                    <div class="flex items-center gap-1.5">
                                                        <flux:icon.calendar class="w-4 h-4 text-gray-400 dark:text-gray-500" />
                                                        Pitched {{ $pitch->created_at->format('M j, Y') }}
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <flux:icon.clock class="w-4 h-4 text-gray-400 dark:text-gray-500" />
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
                                                                <flux:badge color="green" size="sm">
                                                                    <flux:icon.shield-check class="w-3 h-3 mr-1.5" />
                                                                    License Agreed
                                                                </flux:badge>
                                                            @else
                                                                <flux:badge color="amber" size="sm">
                                                                    <flux:icon.shield-exclamation class="w-3 h-3 mr-1.5" />
                                                                    License Pending
                                                                </flux:badge>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                                {{-- Enhanced Rating Display --}}
                                                @if($pitch->status === 'completed' && $pitch->getCompletionRating())
                                                    <div class="mt-2 flex items-center">
                                                        <div class="bg-orange-50 dark:bg-orange-950 border border-orange-200 dark:border-orange-800 rounded-lg px-3 py-1 flex items-center">
                                                            <div class="flex items-center mr-2">
                                                                @for($i = 1; $i <= 5; $i++)
                                                                    <flux:icon.star class="w-3 h-3 {{ $i <= $pitch->getCompletionRating() ? 'text-orange-500' : 'text-gray-300 dark:text-gray-600' }}" />
                                                                @endfor
                                                            </div>
                                                            <span class="text-sm font-bold text-orange-800 dark:text-orange-200">{{ number_format($pitch->getCompletionRating(), 1) }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                            
                                            <!-- Status Badge - Moved to top right -->
                                            <div class="flex-shrink-0 sm:ml-4">
                                                <flux:badge color="{{ $statusColors['badge'] }}" size="lg">
                                                    {{ $pitch->readable_status }}
                                                </flux:badge>
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
                                <div class="px-4 pb-4">
                                    <div class="flex flex-col gap-3">
                                        <!-- Action Buttons Row -->
                                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-2">
                                @if($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch)
                                        <livewire:pitch.component.complete-pitch
                                            :key="'complete-pitch-'.$pitch->id" :pitch="$pitch" />
                                @endif

                                            <!-- Enhanced Payment Component for Completed Pitches -->
                                @if(auth()->id() === $project->user_id && 
                                    $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                    (empty($pitch->payment_status) || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING || $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED))
                                        <flux:button 
                                            href="{{ route('projects.pitches.payment.overview', ['project' => $pitch->project, 'pitch' => $pitch]) }}"
                                            variant="primary"
                                            size="sm"
                                            class="w-full sm:w-auto">
                                            <flux:icon.credit-card class="w-4 h-4 sm:mr-2" />
                                            <span class="hidden sm:inline ml-1">Process Payment</span>
                                            <span class="sm:hidden">Payment</span>
                                        </flux:button>
                                @elseif(auth()->id() === $project->user_id && 
                                    $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                    in_array($pitch->payment_status, [\App\Models\Pitch::PAYMENT_STATUS_PAID, \App\Models\Pitch::PAYMENT_STATUS_PROCESSING]))
                                        <flux:button 
                                            href="{{ route('projects.pitches.payment.receipt', ['project' => $pitch->project, 'pitch' => $pitch]) }}"
                                            variant="filled"
                                            size="sm"
                                            class="w-full sm:w-auto">
                                            <flux:icon.document class="w-4 h-4 sm:mr-2" />
                                            <span class="hidden sm:inline ml-1">View Receipt</span>
                                            <span class="sm:hidden">Receipt</span>
                                        </flux:button>
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
                                            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 w-fit">
                                                <div class="flex items-center text-xs gap-2">
                                                    <flux:icon.credit-card class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                                                    <span class="text-gray-600 dark:text-gray-300 font-medium">Payment Status:</span>
                                                    <flux:badge 
                                                        color="{{ $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID ? 'green' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ? 'amber' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING ? 'blue' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED ? 'red' : 'gray'))) }}"
                                                        size="sm">
                                                        {{ Str::title(str_replace('_', ' ', $pitch->payment_status)) }}
                                                    </flux:badge>
                                                </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                            @endif

                    @if($pitch->snapshots->count() > 0)
                                <!-- Enhanced Snapshots Section -->
                                <div x-data="{ expanded: false }" class="border-t border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30">
                                    <div class="px-4 py-2">
                                        <button @click="expanded = !expanded" class="w-full flex items-center justify-between text-left group">
                                            <div class="flex items-center gap-2">
                                                <flux:icon.clock class="w-4 h-4 {{ $gradientClasses['icon'] }}" />
                                                <flux:text size="sm" class="{{ $gradientClasses['text_primary'] }}">Snapshots ({{ $pitch->snapshots->count() }})</flux:text>
                                            </div>
                                            <div class="flex items-center gap-1">
                                            @if($pitch->snapshots->count() >= 2)
                                                <flux:button 
                                                    variant="ghost" 
                                                        size="xs"
                                                    flux:modal="version-comparison-{{ $pitch->id }}">
                                                        <flux:icon.squares-2x2 class="w-3 h-3" />
                                                </flux:button>
                                            @endif
                                                <flux:icon.chevron-down x-show="!expanded" class="w-3 h-3 {{ $gradientClasses['text_muted'] }}" />
                                                <flux:icon.chevron-up x-show="expanded" class="w-3 h-3 {{ $gradientClasses['text_muted'] }}" />
                                        </div>
                                        </button>
                                    </div>
                                    
                                    <div x-show="expanded" x-collapse class="px-4 pb-3">
                                        <div class="bg-white/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 rounded-lg p-3">
                                    
                                    <!-- Version Comparison Modal -->
                                    <flux:modal name="version-comparison-{{ $pitch->id }}" class="max-w-6xl">
                                        <div class="p-6">
                                            <div class="flex items-center gap-3 mb-6">
                                                <flux:icon.squares-2x2 class="w-6 h-6 {{ $gradientClasses['icon'] }}" />
                                                <div>
                                                    <flux:heading size="lg">Version Comparison</flux:heading>
                                                    <flux:subheading>Compare snapshots from {{ $pitch->user->name }}</flux:subheading>
                                                </div>
                                            </div>
                                            
                                            @if($pitch->snapshots->count() >= 2)
                                                @livewire('file-comparison-player', [
                                                    'snapshots' => $pitch->snapshots->sortByDesc('created_at'),
                                                    'pitchId' => $pitch->id,
                                                    'allowAnnotations' => true
                                                ])
                                            @endif
                                        </div>
                                    </flux:modal>
                                </div>
                            </div>
                                            <!-- Compact Snapshots Grid -->
                                            <div class="space-y-2">
                                    @foreach($pitch->snapshots->sortByDesc('created_at') as $snapshot)
                                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
                                                        class="group flex items-center p-2 bg-white dark:bg-gray-800 hover:bg-white/80 border border-gray-200 dark:border-gray-700 rounded-lg transition-all duration-200">
                                                
                                                <!-- Snapshot Icon -->
                                                        <div class="w-6 h-6 rounded-lg flex-shrink-0 flex items-center justify-center bg-white/60 {{ $gradientClasses['icon'] }} mr-2">
                                                            <flux:icon.camera class="w-3 h-3" />
                                                </div>
                                                
                                                <!-- Snapshot Info -->
                                                <div class="min-w-0 flex-1">
                                                            <div class="flex items-center justify-between">
                                                                <div class="font-medium truncate text-xs text-gray-800 dark:text-gray-200 group-hover:{{ $gradientClasses['text_primary'] }}">
                                                        Version {{ $snapshot->snapshot_data['version'] ?? 'N/A' }}
                                                    </div>
                                                                <div class="text-xs text-gray-500 dark:text-gray-400 ml-2">
                                                                    {{ $snapshot->created_at->format('M j') }}
                                                                </div>
                                                    </div>
                                                </div>
                                                
                                                        <!-- Status Indicator -->
                                                        <div class="ml-1 flex-shrink-0">
                                                    @if($snapshot->status === 'accepted')
                                                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                                    @elseif($snapshot->status === 'declined')
                                                                <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                                    @elseif($snapshot->status === 'revisions_requested')
                                                                <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                                                    @elseif($snapshot->status === 'pending')
                                                                <div class="w-2 h-2 bg-yellow-500 rounded-full animate-pulse"></div>
                                                    @else
                                                                <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                                    @endif
                                                </div>
                                        </a>
                                    @endforeach
                                            </div>
                                        </div>
                                    </div>
                        </div>
                    @endif

                    @if($pitch->status === 'completed' && !empty($pitch->completion_feedback))
                                <!-- Compact Completion Feedback -->
                                <div class="border-t {{ $semanticColors['success']['border'] }} {{ $semanticColors['success']['bg'] }} px-4 py-3">
                                    <div class="flex items-start gap-2">
                                        <flux:icon.chat-bubble-left-ellipsis class="w-4 h-4 {{ $semanticColors['success']['icon'] }} mt-0.5 flex-shrink-0" />
                                        <div class="min-w-0">
                                            <flux:text size="sm" class="{{ $semanticColors['success']['text'] }} font-medium mb-1">Completion Feedback</flux:text>
                                            <flux:text size="xs" class="{{ $semanticColors['success']['text'] }} leading-relaxed">{{ $pitch->completion_feedback }}</flux:text>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <!-- Clean Empty State -->
                    <div class="bg-white/60 border border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 rounded-xl p-6 text-center">
                        <div class="flex items-center justify-center gap-2 {{ $gradientClasses['text_muted'] }}">
                            <flux:icon.paper-airplane class="w-5 h-5" />
                            <span class="text-sm font-medium">No pitches submitted yet</span>
                        </div>
                        <p class="text-xs {{ $gradientClasses['text_muted'] }} mt-2">
                            Pitch submissions will appear here for review once producers respond to your project.
                        </p>
                </div>
            @endforelse
        </div>
    </div>
</div>



 