@props(['storageUsedPercentage', 'storageLimitMessage', 'storageRemaining'])

<div class="mb-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 p-4 rounded-lg">
    <div class="flex justify-between items-center mb-3">
        <flux:text weight="medium" size="sm">Storage Used: {{ $storageLimitMessage }}</flux:text>
        <flux:text size="xs" class="text-gray-500">{{ $storageRemaining }} remaining</flux:text>
    </div>
    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
        <div class="h-2 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-red-500' : ($storageUsedPercentage > 70 ? 'bg-amber-500' : 'bg-blue-600') }}"
            style="width: {{ $storageUsedPercentage }}%"></div>
    </div>
    <div class="mt-3">
        <flux:text size="xs" class="text-gray-500 flex items-center">
            <flux:icon name="information-circle" size="xs" class="mr-1 text-blue-500" />
            Storage is calculated across all your projects and pitches.
        </flux:text>
    </div>
</div> 