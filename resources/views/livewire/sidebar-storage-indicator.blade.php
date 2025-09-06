@if(Auth::check())
<div wire:poll.30s="updateStorageInfo">
    <x-file-management.storage-indicator-minimal 
        :storageUsedPercentage="$storageUsedPercentage"
        :storageLimitMessage="$storageLimitMessage"
        :storageRemaining="$this->formatFileSize($storageRemaining)" />
</div>
@endif