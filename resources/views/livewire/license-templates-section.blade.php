<div class="relative">
    <flux:card>
        <!-- Section Header -->
        <div class="mb-4">
            <div class="flex items-center gap-3 mb-2">
                <div class="hidden p-2 bg-gradient-to-r from-purple-500 to-indigo-600 rounded-lg shadow-md">
                    <flux:icon name="document-text" class="text-white" size="lg" />
                </div>
                <flux:heading size="xl" class="bg-gradient-to-r from-gray-900 via-purple-800 to-indigo-800 dark:from-gray-100 dark:via-purple-300 dark:to-indigo-300 bg-clip-text text-transparent">
                    License Templates
                </flux:heading>
            </div>
            <flux:subheading>Manage your custom license agreement templates for projects</flux:subheading>
        </div>
        
        <!-- License Templates Component -->
        <livewire:user.manage-license-templates embedded-mode="true" />
    </flux:card>
</div>