@props(['project', 'workflowColors' => null, 'semanticColors' => null])

@php
    // Provide default workflow colors if not passed from parent
    $workflowColors =
        $workflowColors ??
        match ($project->workflow_type) {
            'standard' => [
                'bg' => 'bg-blue-50 dark:bg-blue-950',
                'border' => 'border-blue-200 dark:border-blue-800',
                'text_primary' => 'text-blue-900 dark:text-blue-100',
                'text_secondary' => 'text-blue-700 dark:text-blue-300',
                'text_muted' => 'text-blue-600 dark:text-blue-400',
                'accent_bg' => 'bg-blue-100 dark:bg-blue-900',
                'icon' => 'text-blue-600 dark:text-blue-400',
            ],
            'contest' => [
                'bg' => 'bg-orange-50 dark:bg-orange-950',
                'border' => 'border-orange-200 dark:border-orange-800',
                'text_primary' => 'text-orange-900 dark:text-orange-100',
                'text_secondary' => 'text-orange-700 dark:text-orange-300',
                'text_muted' => 'text-orange-600 dark:text-orange-400',
                'accent_bg' => 'bg-orange-100 dark:bg-orange-900',
                'icon' => 'text-orange-600 dark:text-orange-400',
            ],
            'direct_hire' => [
                'bg' => 'bg-green-50 dark:bg-green-950',
                'border' => 'border-green-200 dark:border-green-800',
                'text_primary' => 'text-green-900 dark:text-green-100',
                'text_secondary' => 'text-green-700 dark:text-green-300',
                'text_muted' => 'text-green-600 dark:text-green-400',
                'accent_bg' => 'bg-green-100 dark:bg-green-900',
                'icon' => 'text-green-600 dark:text-green-400',
            ],
            'client_management' => [
                'bg' => 'bg-purple-50 dark:bg-purple-950',
                'border' => 'border-purple-200 dark:border-purple-800',
                'text_primary' => 'text-purple-900 dark:text-purple-100',
                'text_secondary' => 'text-purple-700 dark:text-purple-300',
                'text_muted' => 'text-purple-600 dark:text-purple-400',
                'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
                'icon' => 'text-purple-600 dark:text-purple-400',
            ],
            default => [
                'bg' => 'bg-gray-50 dark:bg-gray-950',
                'border' => 'border-gray-200 dark:border-gray-800',
                'text_primary' => 'text-gray-900 dark:text-gray-100',
                'text_secondary' => 'text-gray-700 dark:text-gray-300',
                'text_muted' => 'text-gray-600 dark:text-gray-400',
                'accent_bg' => 'bg-gray-100 dark:bg-gray-900',
                'icon' => 'text-gray-600 dark:text-gray-400',
            ],
        };

    // Semantic colors for status-based theming (consistent across workflows)
    $semanticColors = $semanticColors ?? [
        'success' => [
            'bg' => 'bg-green-50 dark:bg-green-950',
            'border' => 'border-green-200 dark:border-green-800',
            'text' => 'text-green-800 dark:text-green-200',
            'icon' => 'text-green-600 dark:text-green-400',
            'accent' => 'bg-green-500',
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950',
            'border' => 'border-amber-200 dark:border-amber-800',
            'text' => 'text-amber-800 dark:text-amber-200',
            'icon' => 'text-amber-600 dark:text-amber-400',
            'accent' => 'bg-amber-500',
        ],
        'danger' => [
            'bg' => 'bg-red-50 dark:bg-red-950',
            'border' => 'border-red-200 dark:border-red-800',
            'text' => 'text-red-800 dark:text-red-200',
            'icon' => 'text-red-600 dark:text-red-400',
            'accent' => 'bg-red-500',
        ],
    ];

    // Sort pitches to show completed first, then approved, then others
    $sortedPitches = $project->pitches->sortBy(function ($pitch) {
        return match ($pitch->status) {
            'completed' => 1,
            'approved' => 2,
            'revisions_requested' => 3,
            'closed' => 5,
            default => 4,
        };
    });

    // Check for multiple approved pitches
    $hasMultipleApprovedPitches = $project->pitches->where('status', 'approved')->count() > 1;
    $hasCompletedPitch = $project->pitches->where('status', 'completed')->count() > 0;
@endphp

@php
    // Create workflow-aware gradient classes similar to project-workflow-status
    $gradientClasses = match ($project->workflow_type) {
        'standard' => [
            'outer' =>
                'bg-gradient-to-br from-blue-50/95 to-indigo-50/90 dark:from-blue-950/95 dark:to-indigo-950/90 backdrop-blur-sm border border-blue-200/50 dark:border-blue-700/50',
            'header' =>
                'bg-gradient-to-r from-blue-100/80 to-indigo-100/80 dark:from-blue-900/80 dark:to-indigo-900/80 border-b border-blue-200/30 dark:border-blue-700/30',
            'text_primary' => 'text-blue-900 dark:text-blue-100',
            'text_secondary' => 'text-blue-700 dark:text-blue-300',
            'text_muted' => 'text-blue-600 dark:text-blue-400',
            'icon' => 'text-blue-600 dark:text-blue-400',
        ],
        'contest' => [
            'outer' =>
                'bg-gradient-to-br from-amber-50/95 to-yellow-50/90 dark:from-amber-950/95 dark:to-yellow-950/90 backdrop-blur-sm border border-amber-200/50 dark:border-amber-700/50',
            'header' =>
                'bg-gradient-to-r from-amber-100/80 to-yellow-100/80 dark:from-amber-900/80 dark:to-yellow-900/80 border-b border-amber-200/30 dark:border-amber-700/30',
            'text_primary' => 'text-amber-900 dark:text-amber-100',
            'text_secondary' => 'text-amber-700 dark:text-amber-300',
            'text_muted' => 'text-amber-600 dark:text-amber-400',
            'icon' => 'text-amber-600 dark:text-amber-400',
        ],
        'direct_hire' => [
            'outer' =>
                'bg-gradient-to-br from-green-50/95 to-emerald-50/90 dark:from-green-950/95 dark:to-emerald-950/90 backdrop-blur-sm border border-green-200/50 dark:border-green-700/50',
            'header' =>
                'bg-gradient-to-r from-green-100/80 to-emerald-100/80 dark:from-green-900/80 dark:to-emerald-900/80 border-b border-green-200/30 dark:border-green-700/30',
            'text_primary' => 'text-green-900 dark:text-green-100',
            'text_secondary' => 'text-green-700 dark:text-green-300',
            'text_muted' => 'text-green-600 dark:text-green-400',
            'icon' => 'text-green-600 dark:text-green-400',
        ],
        'client_management' => [
            'outer' =>
                'bg-gradient-to-br from-purple-50/95 to-indigo-50/90 dark:from-purple-950/95 dark:to-indigo-950/90 backdrop-blur-sm border border-purple-200/50 dark:border-purple-700/50',
            'header' =>
                'bg-gradient-to-r from-purple-100/80 to-indigo-100/80 dark:from-purple-900/80 dark:to-indigo-900/80 border-b border-purple-200/30 dark:border-purple-700/30',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'icon' => 'text-purple-600 dark:text-purple-400',
        ],
        default => [
            'outer' =>
                'bg-gradient-to-br from-gray-50/95 to-slate-50/90 dark:from-gray-950/95 dark:to-slate-950/90 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50',
            'header' =>
                'bg-gradient-to-r from-gray-100/80 to-slate-100/80 dark:from-gray-900/80 dark:to-slate-900/80 border-b border-gray-200/30 dark:border-gray-700/30',
            'text_primary' => 'text-gray-900 dark:text-gray-100',
            'text_secondary' => 'text-gray-700 dark:text-gray-300',
            'text_muted' => 'text-gray-600 dark:text-gray-400',
            'icon' => 'text-gray-600 dark:text-gray-400',
        ],
    };
@endphp

<flux:card class="{{ $gradientClasses['outer'] }} overflow-hidden">
    <!-- Professional Header matching workflow-status style -->
    <div class="">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h3 class="{{ $gradientClasses['text_primary'] }} flex items-center text-lg font-bold">
                    <flux:icon.paper-airplane
                        class="{{ $gradientClasses['icon'] }} mr-2 h-4 w-4 flex-shrink-0 sm:mr-3 sm:h-5 sm:w-5" />
                    <span class="truncate">Submitted Pitches</span>
                </h3>
                <p class="{{ $gradientClasses['text_secondary'] }} mt-1 text-sm">
                    @if ($project->pitches->count() > 0)
                        {{ $project->pitches->count() }} {{ Str::plural('submission', $project->pitches->count()) }}
                        received
                    @else
                        Ready to receive pitch submissions
                    @endif
                </p>
            </div>
            <div class="flex-shrink-0">
                <!-- Auto-allow toggle - allows label to wrap -->
                <div
                    class="border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 max-w-[120px] rounded-lg border bg-white/60 px-2 py-1.5 dark:bg-gray-800/60">
                    <div class="flex items-center">
                        <flux:label
                            class="{{ $gradientClasses['text_secondary'] }} text-center text-xs font-medium leading-tight">
                            Auto-allow access
                        </flux:label>
                        <flux:switch wire:model.live="autoAllowAccess" wire:loading.attr="disabled"
                            wire:target="autoAllowAccess" size="sm" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Area -->
    <div class="mt-2">
        <div class="space-y-4">
            @forelse($sortedPitches as $pitch)
                @php
                    // Clean semantic status theming
                    $statusColors = match ($pitch->status) {
                        'completed' => [
                            'bg' => $semanticColors['success']['bg'],
                            'border' => $semanticColors['success']['border'],
                            'accent' => $semanticColors['success']['accent'],
                            'badge' => 'green',
                        ],
                        'approved' => [
                            'bg' => $hasMultipleApprovedPitches
                                ? $semanticColors['warning']['bg']
                                : 'bg-blue-50 dark:bg-blue-950',
                            'border' => $hasMultipleApprovedPitches
                                ? $semanticColors['warning']['border']
                                : 'border-blue-200 dark:border-blue-800',
                            'accent' => $hasMultipleApprovedPitches
                                ? $semanticColors['warning']['accent']
                                : 'bg-blue-500',
                            'badge' => $hasMultipleApprovedPitches ? 'amber' : 'blue',
                        ],
                        'denied' => [
                            'bg' => $semanticColors['danger']['bg'],
                            'border' => $semanticColors['danger']['border'],
                            'accent' => $semanticColors['danger']['accent'],
                            'badge' => 'red',
                        ],
                        'revisions_requested' => [
                            'bg' => $semanticColors['warning']['bg'],
                            'border' => $semanticColors['warning']['border'],
                            'accent' => $semanticColors['warning']['accent'],
                            'badge' => 'amber',
                        ],
                        'closed' => [
                            'bg' => 'bg-gray-50 dark:bg-gray-950',
                            'border' => 'border-gray-200 dark:border-gray-800',
                            'accent' => 'bg-gray-500',
                            'badge' => 'gray',
                        ],
                        default => [
                            'bg' => 'bg-white dark:bg-gray-800',
                            'border' => 'border-gray-200 dark:border-gray-700',
                            'accent' => $gradientClasses['icon'],
                            'badge' => 'gray',
                        ],
                    };
                @endphp

                <div wire:key="pitch-{{ $pitch->id }}" class="group relative">
                    <!-- Minimal Status Accent Bar -->
                    <div class="{{ $statusColors['accent'] }} absolute bottom-6 left-0 top-6 z-10 w-1 rounded-r-full">
                    </div>

                    <!-- Main Card -->
                    <div
                        class="border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 {{ $statusColors['bg'] }} {{ $statusColors['border'] }} relative overflow-hidden rounded-xl border bg-white/60 transition-all duration-200 dark:bg-gray-800/60">
                        <!-- Enhanced User Profile Section -->
                        <div class="p-4 pb-3">
                            <!-- Mobile-optimized layout -->
                            <div class="sm:hidden">
                                <!-- Top row: Avatar, Name, Status -->
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="relative flex-shrink-0">
                                        <flux:avatar size="sm" src="{{ $pitch->user->profile_photo_url }}"
                                            alt="{{ $pitch->user->name }}" />
                                        <div
                                            class="{{ $statusColors['accent'] }} absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-gray-800">
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <a href="{{ route('profile.show', $pitch->user->id) }}" wire:navigate
                                            class="hover:{{ $gradientClasses['text_muted'] }} truncate text-base font-bold text-gray-900 transition-colors dark:text-gray-100 block">{{ $pitch->user->name }}</a>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if ($pitch->status === 'completed')
                                            <flux:badge color="green" size="sm">
                                                <flux:icon.trophy class="mr-1 h-3 w-3" />Completed
                                            </flux:badge>
                                        @else
                                            <flux:badge color="{{ $statusColors['badge'] }}" size="sm">
                                                {{ $pitch->readable_status }}
                                            </flux:badge>
                                        @endif
                                    </div>
                                </div>
                                <!-- Bottom section: Full-width meta info -->
                                <div class="space-y-2">
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-gray-700 dark:text-gray-300">
                                        <div class="flex items-center gap-1.5">
                                            <flux:icon.calendar class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                            {{ $pitch->created_at->format('M j, Y') }}
                                        </div>
                                        {{-- License Agreement Status --}}
                                        @php
                                            $hasLicenseAgreement =
                                                $project->requiresLicenseAgreement() &&
                                                $project
                                                    ->licenseSignatures()
                                                    ->where('user_id', $pitch->user_id)
                                                    ->where('status', 'active')
                                                    ->exists();
                                        @endphp
                                        @if ($project->requiresLicenseAgreement())
                                            @if ($hasLicenseAgreement)
                                                <flux:badge color="green" size="sm">
                                                    <flux:icon.shield-check class="mr-1.5 h-3 w-3" />
                                                    License Agreed
                                                </flux:badge>
                                            @else
                                                <flux:badge color="amber" size="sm">
                                                    <flux:icon.shield-exclamation class="mr-1.5 h-3 w-3" />
                                                    License Pending
                                                </flux:badge>
                                            @endif
                                        @endif
                                    </div>
                                    {{-- Enhanced Rating Display --}}
                                    @if ($pitch->status === 'completed' && $pitch->getCompletionRating())
                                        <div class="flex items-center">
                                            <div class="flex items-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-1 dark:border-orange-800 dark:bg-orange-950">
                                                <div class="mr-2 flex items-center">
                                                    @for ($i = 1; $i <= 5; $i++)
                                                        <flux:icon.star
                                                            class="{{ $i <= $pitch->getCompletionRating() ? 'text-orange-500' : 'text-gray-300 dark:text-gray-600' }} h-3 w-3" />
                                                    @endfor
                                                </div>
                                                <span class="text-sm font-bold text-orange-800 dark:text-orange-200">{{ number_format($pitch->getCompletionRating(), 1) }}</span>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <!-- Desktop layout (unchanged) -->
                            <div class="hidden sm:block">
                                <div class="flex items-start gap-3">
                                    <div class="relative flex-shrink-0">
                                        <flux:avatar size="md" src="{{ $pitch->user->profile_photo_url }}"
                                            alt="{{ $pitch->user->name }}" />
                                        <div
                                            class="{{ $statusColors['accent'] }} absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-gray-800">
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="min-w-0 flex-1">
                                            <div class="mb-1 flex items-center justify-between gap-4">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <a href="{{ route('profile.show', $pitch->user->id) }}" wire:navigate
                                                        class="hover:{{ $gradientClasses['text_muted'] }} truncate text-base font-bold text-gray-900 transition-colors dark:text-gray-100">{{ $pitch->user->name }}</a>
                                                    @if ($pitch->status === 'completed')
                                                        <flux:badge color="green" size="sm">
                                                            <flux:icon.trophy class="mr-1 h-3 w-3" />Completed
                                                        </flux:badge>
                                                    @endif
                                                </div>
                                                <!-- Desktop Action Buttons inline with name -->
                                                <div class="flex items-center gap-2 flex-shrink-0">
                                                    @if (
                                                        ($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch) ||
                                                            (auth()->id() === $project->user_id && $pitch->status === \App\Models\Pitch::STATUS_COMPLETED) ||
                                                            (auth()->id() === $project->user_id &&
                                                                in_array($pitch->status, [
                                                                    \App\Models\Pitch::STATUS_PENDING,
                                                                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                                                                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                                                    \App\Models\Pitch::STATUS_APPROVED,
                                                                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                                                    \App\Models\Pitch::STATUS_DENIED,
                                                                    \App\Models\Pitch::STATUS_COMPLETED,
                                                                ])) ||
                                                            ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                                                $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED))
                                                        
                                                        @if ($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch)
                                                            <livewire:pitch.component.complete-pitch :key="'complete-pitch-desktop-' . $pitch->id"
                                                                :pitch="$pitch" />
                                                        @endif

                                                        @if (auth()->id() === $project->user_id &&
                                                                $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                                                (empty($pitch->payment_status) ||
                                                                    $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ||
                                                                    $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED))
                                                            <flux:button
                                                                href="{{ route('projects.pitches.payment.overview', ['project' => $pitch->project, 'pitch' => $pitch]) }}"
                                                                wire:navigate variant="primary" size="sm"
                                                                icon="credit-card">
                                                                Process Payment
                                                            </flux:button>
                                                        @elseif(auth()->id() === $project->user_id &&
                                                                $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                                                in_array($pitch->payment_status, [
                                                                    \App\Models\Pitch::PAYMENT_STATUS_PAID,
                                                                    \App\Models\Pitch::PAYMENT_STATUS_PROCESSING,
                                                                ]))
                                                            <flux:button
                                                                href="{{ route('projects.pitches.payment.receipt', ['project' => $pitch->project, 'pitch' => $pitch]) }}"
                                                                wire:navigate variant="filled" size="sm"
                                                                icon="document">
                                                                View Receipt
                                                            </flux:button>
                                                        @endif

                                                        @if (auth()->id() === $project->user_id &&
                                                                in_array($pitch->status, [
                                                                    \App\Models\Pitch::STATUS_PENDING,
                                                                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                                                                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                                                    \App\Models\Pitch::STATUS_APPROVED,
                                                                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                                                    \App\Models\Pitch::STATUS_DENIED,
                                                                    \App\Models\Pitch::STATUS_COMPLETED,
                                                                ]))
                                                            <x-update-pitch-status :pitch="$pitch" :status="$pitch->status" />
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                                <div
                                                    class="flex flex-col gap-1 text-sm text-gray-700 sm:flex-row sm:items-center sm:gap-3 dark:text-gray-300">
                                                    <div class="flex items-center gap-1.5">
                                                        <flux:icon.calendar
                                                            class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                                        Pitched {{ $pitch->created_at->format('M j, Y') }}
                                                    </div>
                                                    <div class="flex items-center gap-1.5">
                                                        <flux:icon.clock class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                                        {{ $pitch->created_at->diffForHumans() }}
                                                    </div>
                                                    {{-- License Agreement Status --}}
                                                    @php
                                                        $hasLicenseAgreement =
                                                            $project->requiresLicenseAgreement() &&
                                                            $project
                                                                ->licenseSignatures()
                                                                ->where('user_id', $pitch->user_id)
                                                                ->where('status', 'active')
                                                                ->exists();
                                                    @endphp
                                                    @if ($project->requiresLicenseAgreement())
                                                        <div class="flex items-center">
                                                            @if ($hasLicenseAgreement)
                                                                <flux:badge color="green" size="sm">
                                                                    <flux:icon.shield-check class="mr-1.5 h-3 w-3" />
                                                                    License Agreed
                                                                </flux:badge>
                                                            @else
                                                                <flux:badge color="amber" size="sm">
                                                                    <flux:icon.shield-exclamation class="mr-1.5 h-3 w-3" />
                                                                    License Pending
                                                                </flux:badge>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <div class="flex items-center">
                                                        <flux:badge color="{{ $statusColors['badge'] }}" size="sm">
                                                            {{ $pitch->readable_status }}
                                                        </flux:badge>
                                                    </div>
                                                </div>
                                                {{-- Enhanced Rating Display --}}
                                                @if ($pitch->status === 'completed' && $pitch->getCompletionRating())
                                                    <div class="mt-2 flex items-center">
                                                        <div
                                                            class="flex items-center rounded-lg border border-orange-200 bg-orange-50 px-3 py-1 dark:border-orange-800 dark:bg-orange-950">
                                                            <div class="mr-2 flex items-center">
                                                                @for ($i = 1; $i <= 5; $i++)
                                                                    <flux:icon.star
                                                                        class="{{ $i <= $pitch->getCompletionRating() ? 'text-orange-500' : 'text-gray-300 dark:text-gray-600' }} h-3 w-3" />
                                                                @endfor
                                                            </div>
                                                            <span
                                                                class="text-sm font-bold text-orange-800 dark:text-orange-200">{{ number_format($pitch->getCompletionRating(), 1) }}</span>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile Actions & Payment Section -->
                        <div class="sm:hidden">
                            @if (
                                ($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch) ||
                                    (auth()->id() === $project->user_id && $pitch->status === \App\Models\Pitch::STATUS_COMPLETED) ||
                                    (auth()->id() === $project->user_id &&
                                        in_array($pitch->status, [
                                            \App\Models\Pitch::STATUS_PENDING,
                                            \App\Models\Pitch::STATUS_IN_PROGRESS,
                                            \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                            \App\Models\Pitch::STATUS_APPROVED,
                                            \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                            \App\Models\Pitch::STATUS_DENIED,
                                            \App\Models\Pitch::STATUS_COMPLETED,
                                        ])) ||
                                    ($pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                        $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED))
                                <div class="px-4 pb-4">
                                <div class="flex flex-col gap-3">
                                    <!-- Action Buttons Row -->
                                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end sm:gap-2">
                                        @if ($pitch->status === \App\Models\Pitch::STATUS_APPROVED && !$hasCompletedPitch)
                                            <livewire:pitch.component.complete-pitch :key="'complete-pitch-' . $pitch->id"
                                                :pitch="$pitch" />
                                        @endif

                                        <!-- Enhanced Payment Component for Completed Pitches -->
                                        @if (auth()->id() === $project->user_id &&
                                                $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                                (empty($pitch->payment_status) ||
                                                    $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ||
                                                    $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED))
                                            <flux:button
                                                href="{{ route('projects.pitches.payment.overview', ['project' => $pitch->project, 'pitch' => $pitch]) }}"
                                                wire:navigate variant="primary" size="sm"
                                                class="min-h-[44px] w-full sm:min-h-[32px] sm:w-auto"
                                                icon="credit-card">
                                                <span class="hidden sm:inline">Process Payment</span>
                                                <span class="sm:hidden">Payment</span>
                                            </flux:button>
                                        @elseif(auth()->id() === $project->user_id &&
                                                $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                                in_array($pitch->payment_status, [
                                                    \App\Models\Pitch::PAYMENT_STATUS_PAID,
                                                    \App\Models\Pitch::PAYMENT_STATUS_PROCESSING,
                                                ]))
                                            <flux:button
                                                href="{{ route('projects.pitches.payment.receipt', ['project' => $pitch->project, 'pitch' => $pitch]) }}"
                                                wire:navigate variant="filled" size="sm"
                                                class="min-h-[44px] w-full sm:min-h-[32px] sm:w-auto" icon="document">
                                                <span class="hidden sm:inline">View Receipt</span>
                                                <span class="sm:hidden">Receipt</span>
                                            </flux:button>
                                        @endif

                                        @if (auth()->id() === $project->user_id &&
                                                in_array($pitch->status, [
                                                    \App\Models\Pitch::STATUS_PENDING,
                                                    \App\Models\Pitch::STATUS_IN_PROGRESS,
                                                    \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
                                                    \App\Models\Pitch::STATUS_APPROVED,
                                                    \App\Models\Pitch::STATUS_REVISIONS_REQUESTED,
                                                    \App\Models\Pitch::STATUS_DENIED,
                                                    \App\Models\Pitch::STATUS_COMPLETED,
                                                ]))
                                            <x-update-pitch-status :pitch="$pitch" :status="$pitch->status" />
                                        @endif
                                    </div>

                                    <!-- Payment Status Row (if applicable) -->
                                    @if (
                                        $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                            $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED)
                                        <div
                                            class="w-fit rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                                            <div class="flex items-center gap-2 text-xs">
                                                <flux:icon.credit-card
                                                    class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                                                <span class="font-medium text-gray-600 dark:text-gray-300">Payment
                                                    Status:</span>
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
                        </div>

                        <!-- Desktop Payment Status (if applicable) -->
                        <div class="hidden sm:block">
                            @if (
                                $pitch->status === \App\Models\Pitch::STATUS_COMPLETED &&
                                    $pitch->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_NOT_REQUIRED)
                                <div class="px-4 pb-4">
                                    <div
                                        class="w-fit rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                                        <div class="flex items-center gap-2 text-xs">
                                            <flux:icon.credit-card
                                                class="h-4 w-4 text-gray-600 dark:text-gray-300" />
                                            <span class="font-medium text-gray-600 dark:text-gray-300">Payment
                                                Status:</span>
                                            <flux:badge
                                                color="{{ $pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID ? 'green' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PENDING ? 'amber' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PROCESSING ? 'blue' : ($pitch->payment_status === \App\Models\Pitch::PAYMENT_STATUS_FAILED ? 'red' : 'gray'))) }}"
                                                size="sm">
                                                {{ Str::title(str_replace('_', ' ', $pitch->payment_status)) }}
                                            </flux:badge>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        @if ($pitch->snapshots->count() > 0)
                            <!-- Enhanced Snapshots Section -->
                            <div x-data="{ expanded: false }"
                                class="border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 border-t">
                                <div class="px-4 py-2">
                                    <button @click="expanded = !expanded"
                                        class="group flex w-full items-center justify-between text-left">
                                        <div class="flex items-center gap-2">
                                            <flux:icon.clock class="{{ $gradientClasses['icon'] }} h-4 w-4" />
                                            <flux:text size="sm" class="{{ $gradientClasses['text_primary'] }}">
                                                Snapshots ({{ $pitch->snapshots->count() }})</flux:text>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            @if ($pitch->snapshots->count() >= 2)
                                                <flux:button variant="ghost" size="xs"
                                                    flux:modal="version-comparison-{{ $pitch->id }}">
                                                    <flux:icon.squares-2x2 class="h-3 w-3" />
                                                </flux:button>
                                            @endif
                                            <flux:icon.chevron-down x-show="!expanded"
                                                class="{{ $gradientClasses['text_muted'] }} h-3 w-3" />
                                            <flux:icon.chevron-up x-show="expanded"
                                                class="{{ $gradientClasses['text_muted'] }} h-3 w-3" />
                                        </div>
                                    </button>
                                </div>

                                <div x-show="expanded" x-collapse class="px-4 pb-3">
                                    <div
                                        class="border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 rounded-lg border bg-white/60 p-3">

                                        <!-- Version Comparison Modal -->
                                        <flux:modal name="version-comparison-{{ $pitch->id }}" class="max-w-6xl">
                                            <div class="p-6">
                                                <div class="mb-6 flex items-center gap-3">
                                                    <flux:icon.squares-2x2
                                                        class="{{ $gradientClasses['icon'] }} h-6 w-6" />
                                                    <div>
                                                        <flux:heading size="lg">Version Comparison</flux:heading>
                                                        <flux:subheading>Compare snapshots from
                                                            {{ $pitch->user->name }}</flux:subheading>
                                                    </div>
                                                </div>

                                                @if ($pitch->snapshots->count() >= 2)
                                                    @livewire('file-comparison-player', [
                                                        'snapshots' => $pitch->snapshots->sortByDesc('created_at'),
                                                        'pitchId' => $pitch->id,
                                                        'allowAnnotations' => true,
                                                    ])
                                                @endif
                                            </div>
                                        </flux:modal>
                                    </div>
                                </div>
                                <!-- Compact Snapshots Grid -->
                                <div class="space-y-2">
                                    @foreach ($pitch->snapshots->sortByDesc('created_at') as $snapshot)
                                        <a href="{{ route('projects.pitches.snapshots.show', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug, 'snapshot' => $snapshot->id]) }}"
                                            wire:navigate
                                            class="group flex items-center rounded-lg border border-gray-200 bg-white p-2 transition-all duration-200 hover:bg-white/80 dark:border-gray-700 dark:bg-gray-800">

                                            <!-- Snapshot Icon -->
                                            <div
                                                class="{{ $gradientClasses['icon'] }} mr-2 flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg bg-white/60">
                                                <flux:icon.camera class="h-3 w-3" />
                                            </div>

                                            <!-- Snapshot Info -->
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center justify-between">
                                                    <div
                                                        class="group-hover:{{ $gradientClasses['text_primary'] }} truncate text-xs font-medium text-gray-800 dark:text-gray-200">
                                                        Version {{ $snapshot->snapshot_data['version'] ?? 'N/A' }}
                                                    </div>
                                                    <div class="ml-2 text-xs text-gray-600 dark:text-gray-300">
                                                        {{ $snapshot->created_at->format('M j') }}
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Indicator -->
                                            <div class="ml-1 flex-shrink-0">
                                                @if ($snapshot->status === 'accepted')
                                                    <div class="h-2 w-2 rounded-full bg-green-500"></div>
                                                @elseif($snapshot->status === 'declined')
                                                    <div class="h-2 w-2 rounded-full bg-red-500"></div>
                                                @elseif($snapshot->status === 'revisions_requested')
                                                    <div class="h-2 w-2 rounded-full bg-amber-500"></div>
                                                @elseif($snapshot->status === 'pending')
                                                    <div class="h-2 w-2 animate-pulse rounded-full bg-yellow-500">
                                                    </div>
                                                @else
                                                    <div class="h-2 w-2 rounded-full bg-gray-400"></div>
                                                @endif
                                            </div>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                    </div>
                </div>
            @endif

            @if ($pitch->status === 'completed' && !empty($pitch->completion_feedback))
                <!-- Compact Completion Feedback -->
                <div
                    class="{{ $semanticColors['success']['border'] }} {{ $semanticColors['success']['bg'] }} border-t px-4 py-3">
                    <div class="flex items-start gap-2">
                        <flux:icon.chat-bubble-left-ellipsis
                            class="{{ $semanticColors['success']['icon'] }} mt-0.5 h-4 w-4 flex-shrink-0" />
                        <div class="min-w-0">
                            <flux:text size="sm"
                                class="{{ $semanticColors['success']['text'] }} mb-1 font-medium">Completion Feedback
                            </flux:text>
                            <flux:text size="xs"
                                class="{{ $semanticColors['success']['text'] }} leading-relaxed">
                                {{ $pitch->completion_feedback }}</flux:text>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@empty
    <!-- Clean Empty State -->
    <div
        class="border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-200/30 dark:border-{{ $project->workflow_type === 'contest' ? 'amber' : ($project->workflow_type === 'direct_hire' ? 'green' : ($project->workflow_type === 'client_management' ? 'purple' : 'blue')) }}-700/30 rounded-xl border bg-white/60 p-6 text-center dark:bg-gray-800/60">
        <div class="flex items-center justify-center gap-2 text-gray-700 dark:text-gray-300">
            <flux:icon.paper-airplane class="h-5 w-5" />
            <span class="text-sm font-medium">No pitches submitted yet</span>
        </div>
        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">
            Pitch submissions will appear here for review once producers respond to your project.
        </p>
    </div>
    @endforelse
</flux:card>
