@props(['storageUsedPercentage', 'storageLimitMessage', 'storageRemaining'])

<div class="mb-4 bg-base-200/50 p-3 rounded-lg">
    <div class="flex justify-between items-center mb-2">
        <span class="text-sm font-medium">Storage Used: {{ $storageLimitMessage }}</span>
        <span class="text-xs text-gray-500">{{ $storageRemaining }} remaining</span>
    </div>
    <div class="w-full bg-gray-200 rounded-full h-2.5">
        <div class="bg-primary h-2.5 rounded-full transition-all duration-500 {{ $storageUsedPercentage > 90 ? 'bg-red-500' : ($storageUsedPercentage > 70 ? 'bg-amber-500' : 'bg-primary') }}"
            style="width: {{ $storageUsedPercentage }}%"></div>
    </div>
    <div class="mt-2 text-xs text-gray-500">
        <i class="fas fa-info-circle text-blue-500 mr-1"></i>
        Maximum file size: 200MB. Total storage limit: 1GB.
    </div>
</div> 