@php
    $statusVariant = 'ghost';
    switch ($pitch->status) {
        case \App\Models\Pitch::STATUS_PENDING:
        case \App\Models\Pitch::STATUS_AWAITING_ACCEPTANCE:
            $statusVariant = 'ghost';
            break;
        case \App\Models\Pitch::STATUS_IN_PROGRESS:
        case \App\Models\Pitch::STATUS_CONTEST_ENTRY:
            $statusVariant = 'info';
            break;
        case \App\Models\Pitch::STATUS_READY_FOR_REVIEW:
        case \App\Models\Pitch::STATUS_REVISIONS_REQUESTED:
        case \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED:
            $statusVariant = 'warning';
            break;
        case \App\Models\Pitch::STATUS_APPROVED:
            $statusVariant = 'info';
            break;
        case \App\Models\Pitch::STATUS_COMPLETED:
        case \App\Models\Pitch::STATUS_CONTEST_WINNER:
        case \App\Models\Pitch::STATUS_CONTEST_RUNNER_UP:
            $statusVariant = 'success';
            break;
        case \App\Models\Pitch::STATUS_DENIED:
        case \App\Models\Pitch::STATUS_CLOSED:
        case \App\Models\Pitch::STATUS_CONTEST_NOT_SELECTED:
            $statusVariant = 'danger';
            break;
    }
@endphp

<flux:card class="mb-2">
    <div class="mb-6 flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
        <div class="flex items-center gap-4">
            @if (!empty($branding['logo_url']))
                <img src="{{ $branding['logo_url'] }}" alt="Brand Logo"
                    class="h-12 w-12 rounded-xl bg-white object-contain p-1 shadow-lg sm:h-16 sm:w-16">
            @else
                <div
                    class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg sm:h-16 sm:w-16">
                    <flux:icon.briefcase class="text-lg text-white sm:text-2xl" />
                </div>
            @endif
            <div class="min-w-0 flex-1">
                <flux:heading size="xl" class="break-words">{{ $project->title }}</flux:heading>
                <flux:subheading>Managed by {{ $branding['brand_display'] ?? $pitch->user->name }}</flux:subheading>
            </div>
        </div>

        <flux:badge size="lg" :variant="$statusVariant" class="self-start sm:self-auto">
            <div class="mr-2 h-2 w-2 animate-pulse rounded-full bg-current"></div>
            {{ $pitch->readable_status }}
        </flux:badge>
    </div>

    @include('client_portal.components.project-progress-card', [
        'project' => $project,
        'pitch' => $pitch,
        'branding' => $branding,
    ])
</flux:card>

