@php
    $branding = $branding ?? [];
    $milestones = $milestones ?? collect();
    $snapshotHistory = $snapshotHistory ?? collect();
    $currentSnapshot = $currentSnapshot ?? null;
    $isPreview = $isPreview ?? false;

    // Determine default tab based on whether producer deliverables exist
    $hasDeliverables = $snapshotHistory->count() > 0;
    $defaultTab = $hasDeliverables ? 'deliverables' : 'reference-files';

    // Get file counts for badges
    $clientFileCount = $project->files->count() ?? 0;
@endphp

<div x-data="{ activeTab: '{{ $defaultTab }}' }" class="mb-2">
    <!-- Tab Navigation -->
    <div class="mb-2 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-1 md:space-x-8">
            <button @click="activeTab = 'reference-files'"
                :class="activeTab === 'reference-files' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                class="border-b-2 px-1 py-2 text-sm font-medium transition-colors duration-200">
                <flux:icon.cloud-arrow-up class="mr-1 inline h-4 w-4" />
                Your <span class="hidden md:inline">Reference</span> Files
                <flux:badge variant="outline" size="sm" class="ml-1">
                    {{ $clientFileCount }} files
                </flux:badge>
            </button>
            <button @click="activeTab = 'deliverables'"
                :class="activeTab === 'deliverables' ? 'border-green-500 text-green-600 dark:text-green-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                class="border-b-2 px-1 py-2 text-sm font-medium transition-colors duration-200">
                <flux:icon.clock class="mr-1 inline h-4 w-4" />
                Producer <span class="hidden md:inline">Deliverables</span>
                @if($hasDeliverables)
                    <flux:badge variant="outline" size="sm" class="ml-1">
                        {{ $snapshotHistory->count() }} {{ $snapshotHistory->count() === 1 ? 'version' : 'versions' }}
                    </flux:badge>
                @endif
            </button>
        </nav>
    </div>

    <!-- Tab Content -->
    <!-- Your Reference Files Tab -->
    <div x-show="activeTab === 'reference-files'" x-transition>
        @include('client_portal.components.project-files-client-list', ['project' => $project])
    </div>

    <!-- Producer Deliverables Tab -->
    <div x-show="activeTab === 'deliverables'" x-transition>
        @livewire('client-portal.producer-deliverables', [
            'project' => $project,
            'pitch' => $pitch,
            'milestones' => $milestones,
            'branding' => $branding,
            'isPreview' => $isPreview,
        ])
    </div>
</div>

