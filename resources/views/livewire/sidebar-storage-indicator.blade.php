<div wire:poll.60s="updateStorageInfo">
    <x-file-management.storage-indicator-minimal 
        :storageUsedPercentage="$storageUsedPercentage"
        :storageLimitMessage="$storageLimitMessage"
        :storageRemaining="$this->formatFileSize($storageRemaining)" />
</div>