@props(['storageUsedPercentage', 'storageLimitMessage', 'storageRemaining'])

<div class="px-2 py-3 border-t border-gray-200/50 dark:border-gray-700/50">
    <div class="flex items-center gap-2 mb-2">
        <flux:icon name="server" variant="micro" class="text-gray-500 dark:text-gray-400 flex-shrink-0" />
        <flux:text size="xs" weight="medium" class="text-gray-700 dark:text-gray-300 truncate">
            {{ $storageLimitMessage }}
        </flux:text>
    </div>
    
    <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-1.5">
        <div class="h-1.5 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-red-500' : ($storageUsedPercentage > 70 ? 'bg-amber-500' : 'bg-blue-600') }}"
            style="width: {{ min(100, $storageUsedPercentage) }}%"></div>
    </div>
</div>