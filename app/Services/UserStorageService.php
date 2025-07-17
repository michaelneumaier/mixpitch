<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserStorageService
{
    /**
     * Get user's total storage limit in bytes
     */
    public function getUserStorageLimit(User $user): int
    {
        // Check for user-specific override first
        if ($user->storage_limit_override_gb) {
            return (int) ($user->storage_limit_override_gb * 1024 * 1024 * 1024);
        }

        // Use subscription-based limit
        $subscriptionLimits = $user->getSubscriptionLimits();
        if (!$subscriptionLimits) {
            // Fallback for tests or users without subscription limits
            return 10 * 1024 * 1024 * 1024; // 10GB default
        }
        return (int) ($subscriptionLimits->total_user_storage_gb * 1024 * 1024 * 1024);
    }

    /**
     * Get user's current storage usage in bytes
     */
    public function getUserStorageUsed(User $user): int
    {
        return $user->total_storage_used ?? 0;
    }

    /**
     * Get user's remaining storage in bytes
     */
    public function getUserStorageRemaining(User $user): int
    {
        return max(0, $this->getUserStorageLimit($user) - $this->getUserStorageUsed($user));
    }

    /**
     * Get user's storage usage as percentage (0-100)
     */
    public function getUserStoragePercentage(User $user): float
    {
        $limit = $this->getUserStorageLimit($user);
        if ($limit === 0) return 0;
        
        $used = $this->getUserStorageUsed($user);
        return min(100, ($used / $limit) * 100);
    }

    /**
     * Check if user has capacity for additional bytes
     */
    public function hasUserStorageCapacity(User $user, int $additionalBytes): bool
    {
        return $this->getUserStorageRemaining($user) >= $additionalBytes;
    }

    /**
     * Increment user's storage usage atomically
     */
    public function incrementUserStorage(User $user, int $bytes): void
    {
        DB::transaction(function () use ($user, $bytes) {
            $user->increment('total_storage_used', $bytes);
        });
    }

    /**
     * Decrement user's storage usage atomically
     */
    public function decrementUserStorage(User $user, int $bytes): void
    {
        DB::transaction(function () use ($user, $bytes) {
            $user->decrement('total_storage_used', $bytes);
        });
    }

    /**
     * Get formatted storage limit message
     */
    public function getStorageLimitMessage(User $user): string
    {
        $used = $this->getUserStorageUsed($user);
        $limit = $this->getUserStorageLimit($user);
        
        $usedMB = round($used / (1024 * 1024), 1);
        $limitGB = round($limit / (1024 * 1024 * 1024), 1);
        
        return "{$usedMB}MB of {$limitGB}GB used";
    }

    /**
     * Get storage data array for frontend components
     */
    public function getStorageData(User $user): array
    {
        return [
            'storageUsedPercentage' => $this->getUserStoragePercentage($user),
            'storageLimitMessage' => $this->getStorageLimitMessage($user),
            'storageRemaining' => $this->getUserStorageRemaining($user),
            'storageUsed' => $this->getUserStorageUsed($user),
            'storageLimit' => $this->getUserStorageLimit($user),
        ];
    }

    /**
     * Recalculate user's total storage from all their files
     */
    public function recalculateUserStorage(User $user): int
    {
        $projectStorage = $user->projects()->sum('total_storage_used');
        $pitchStorage = $user->pitches()->sum('total_storage_used');
        $totalStorage = $projectStorage + $pitchStorage;

        DB::transaction(function () use ($user, $totalStorage) {
            $user->update(['total_storage_used' => $totalStorage]);
        });

        return $totalStorage;
    }

    /**
     * Get formatted storage remaining message
     */
    public function getStorageRemainingMessage(User $user): string
    {
        $remaining = $this->getUserStorageRemaining($user);
        $remainingGB = round($remaining / (1024 * 1024 * 1024), 1);
        
        return "{$remainingGB}GB remaining";
    }
}