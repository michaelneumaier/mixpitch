<div class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="mx-auto px-2 py-2">
        <div class="mx-auto">
            <!-- Client Management Header -->
            <flux:card class="mb-2">
                <!-- Main Header -->
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <flux:icon.users variant="solid" class="w-8 h-8 text-blue-600 dark:text-blue-400" />
                            <flux:heading size="xl" class="text-gray-900 dark:text-gray-100">
                                <span class="lg:hidden">Client Management</span>
                                <span class="hidden lg:inline">Client Management Dashboard</span>
                            </flux:heading>
                        </div>
                        <flux:subheading class="text-gray-600 dark:text-gray-400">
                            <span class="">Analytics and insights for your client relationships</span>
                        </flux:subheading>
                    </div>

                    <!-- Primary Action Button -->
                    <div class="flex-shrink-0">
                        <flux:button
                            wire:click="$dispatch('openClientSelectionModal')"
                            icon="plus" variant="primary" color="violet" size="xs">
                            <span class="hidden">Create</span>
                            <span class="md:inline">New Client Project</span>
                        </flux:button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <flux:separator />

                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 pt-6">
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:button
                            href="{{ route('clients.import.index') }}"
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            icon="arrow-up-tray">
                            Import Clients
                        </flux:button>

                        <flux:button
                            href="{{ route('settings.branding.edit') }}"
                            wire:navigate
                            variant="ghost"
                            size="sm"
                            icon="paint-brush">
                            Branding Settings
                        </flux:button>
                    </div>

                </div>
            </flux:card>

            <!-- Delivery Kanban + Client Management Dashboard -->
            <div class="space-y-2">
                <flux:card>
                    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
                    @livewire('delivery-pipeline-board')
                </flux:card>

                <flux:card>
                    @livewire('client-management-dashboard', [
                        'userId' => auth()->id(),
                        'expanded' => true
                    ])
                </flux:card>
            </div>
        </div>
    </div>

    <!-- Client Selection Modal -->
    @livewire('client-selection-modal')
</div>