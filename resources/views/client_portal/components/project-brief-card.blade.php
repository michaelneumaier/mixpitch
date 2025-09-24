@if ($project->description)
    <flux:card class="mb-6">
        <div class="mb-4 flex items-center gap-3">
            <flux:icon.document-text class="text-blue-500" />
            <flux:heading size="lg">Project Brief</flux:heading>
        </div>
        <div class="rounded-xl bg-gray-50 p-2 md:p-6 dark:bg-gray-800">
            <flux:text class="whitespace-pre-wrap">{{ $project->description }}</flux:text>
        </div>
    </flux:card>
@endif

