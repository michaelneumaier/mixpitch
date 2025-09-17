<?php

namespace App\Livewire;

use App\Services\UserStorageService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class SidebarStorageIndicator extends Component
{
    public $storageUsedPercentage = 0;

    public $storageLimitMessage = '';

    public $storageRemaining = 0;

    protected $listeners = [
        'storageChanged' => 'updateStorageInfo',
        'fileDeleted' => 'updateStorageInfo',
        'storageUpdated' => 'updateStorageInfo',
    ];

    public function mount(): void
    {
        $this->updateStorageInfo();
    }

    public function updateStorageInfo(): void
    {
        if (! Auth::check()) {
            return;
        }

        try {
            $user = Auth::user();
            $userStorageService = app(UserStorageService::class);

            $this->storageUsedPercentage = $userStorageService->getUserStoragePercentage($user);
            $this->storageLimitMessage = $userStorageService->getStorageLimitMessage($user);
            $this->storageRemaining = $userStorageService->getUserStorageRemaining($user);
        } catch (\Throwable $e) {
            // Silently handle errors to avoid breaking the UI
            // Log the error for debugging but don't propagate it
            \Log::error('Storage indicator update failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.sidebar-storage-indicator');
    }

    /**
     * Format file size for display
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) {
            return round($bytes / (1024 * 1024 * 1024), 1).'GB';
        } elseif ($bytes >= 1024 * 1024) {
            return round($bytes / (1024 * 1024), 1).'MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 1).'KB';
        }

        return $bytes.'B';
    }
}
