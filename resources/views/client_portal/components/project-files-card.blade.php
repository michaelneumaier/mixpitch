@php
    $branding = $branding ?? [];
    $milestones = $milestones ?? collect();
    $snapshotHistory = $snapshotHistory ?? collect();
    $currentSnapshot = $currentSnapshot ?? null;
    $isPreview = $isPreview ?? false;
@endphp

<flux:card class="mb-2">
    <div class="mb-6 flex items-center gap-2">
        <flux:icon.folder-open class="text-purple-500" />
        <div>
            <flux:heading size="lg">Project Files</flux:heading>
            <flux:subheading>Manage your project files and deliverables</flux:subheading>
        </div>
    </div>

    @include('client_portal.components.project-files-client-list', ['project' => $project])

    @livewire('client-portal.producer-deliverables', [
        'project' => $project,
        'pitch' => $pitch,
        'milestones' => $milestones,
        'branding' => $branding,
        'isPreview' => $isPreview,
    ])
</flux:card>

