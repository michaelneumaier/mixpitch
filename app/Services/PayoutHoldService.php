<?php

namespace App\Services;

use App\Models\PayoutHoldSetting;
use App\Models\PayoutSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PayoutHoldService
{
    /**
     * Calculate the hold release date for a given workflow type
     */
    public function calculateHoldReleaseDate(string $workflowType = 'standard'): Carbon
    {
        $settings = PayoutHoldSetting::current();

        // If hold periods are disabled, return immediate processing with minimum hold
        if (! $settings->enabled) {
            return now()->addHours($settings->minimum_hold_hours);
        }

        $holdDays = $settings->getHoldDaysForWorkflow($workflowType);

        // If workflow has 0 days, return immediate processing with minimum hold
        if ($holdDays === 0) {
            return now()->addHours($settings->minimum_hold_hours);
        }

        $date = now();

        if ($settings->business_days_only) {
            return $this->addBusinessDays($date, $holdDays, $settings->processing_time);
        }

        // Add calendar days and set processing time
        return $date->addDays($holdDays)->setTimeFromTimeString($settings->processing_time->format('H:i'));
    }

    /**
     * Add business days to a date, skipping weekends
     */
    private function addBusinessDays(Carbon $date, int $days, $processingTime): Carbon
    {
        $businessDaysAdded = 0;
        $date = $date->copy(); // Don't modify original date

        while ($businessDaysAdded < $days) {
            $date->addDay();

            // Skip weekends (Saturday = 6, Sunday = 0)
            if (! in_array($date->dayOfWeek, [0, 6])) {
                $businessDaysAdded++;
            }
        }

        // Set processing time
        $timeString = $processingTime instanceof Carbon ?
            $processingTime->format('H:i') :
            $processingTime;

        return $date->setTimeFromTimeString($timeString);
    }

    /**
     * Check if current user can bypass hold periods
     */
    public function canBypassHold(?User $user = null): bool
    {
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        $settings = PayoutHoldSetting::current();

        return $settings->allow_admin_bypass && $this->userHasAdminRole($user);
    }

    /**
     * Check if user has admin role (adjust based on your role system)
     */
    private function userHasAdminRole(User $user): bool
    {
        // Check for is_admin boolean field first
        if (isset($user->is_admin)) {
            return $user->is_admin;
        }

        // Fallback to role-based check if available
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin');
        }

        // Final fallback: check role field directly
        return $user->role === 'admin';
    }

    /**
     * Bypass hold period for a payout with proper authorization and logging
     */
    public function bypassHoldPeriod(PayoutSchedule $payout, string $reason, ?User $admin = null): void
    {
        $admin = $admin ?? Auth::user();

        if (! $this->canBypassHold($admin)) {
            throw new \Exception('Unauthorized: Admin bypass not allowed or insufficient permissions');
        }

        $settings = PayoutHoldSetting::current();

        if ($settings->require_bypass_reason && empty(trim($reason))) {
            throw new \Exception('Bypass reason is required');
        }

        $originalReleaseDate = $payout->hold_release_date;

        // Set new release date to minimum hold hours from now
        $newReleaseDate = now()->addHours($settings->minimum_hold_hours);

        $payout->update([
            'hold_release_date' => $newReleaseDate,
            'hold_bypassed' => true,
            'bypass_reason' => $reason,
            'bypass_admin_id' => $admin->id,
            'bypassed_at' => now(),
        ]);

        // Log the bypass action if enabled
        if ($settings->log_bypasses) {
            Log::info('Payout hold bypassed', [
                'payout_id' => $payout->id,
                'admin_id' => $admin->id,
                'admin_name' => $admin->name,
                'reason' => $reason,
                'original_release_date' => $originalReleaseDate,
                'new_release_date' => $newReleaseDate,
                'workflow_type' => $payout->workflow_type,
                'net_amount' => $payout->net_amount,
            ]);
        }
    }

    /**
     * Get hold period information for display purposes
     */
    public function getHoldPeriodInfo(string $workflowType = 'standard'): array
    {
        $settings = PayoutHoldSetting::current();
        $holdDays = $settings->getHoldDaysForWorkflow($workflowType);
        $releaseDate = $this->calculateHoldReleaseDate($workflowType);

        $info = [
            'enabled' => $settings->enabled,
            'workflow_type' => $workflowType,
            'hold_days' => $holdDays,
            'business_days_only' => $settings->business_days_only,
            'processing_time' => $settings->processing_time->format('H:i'),
            'release_date' => $releaseDate,
            'is_immediate' => $holdDays === 0 && $settings->minimum_hold_hours === 0,
        ];

        // Generate human-readable description
        if (! $settings->enabled) {
            $info['description'] = 'Hold periods are disabled - payouts processed immediately';
        } elseif ($holdDays === 0) {
            if ($settings->minimum_hold_hours > 0) {
                $info['description'] = "Immediate payout after {$settings->minimum_hold_hours} hour(s)";
            } else {
                $info['description'] = 'Immediate payout processing';
            }
        } else {
            $dayType = $settings->business_days_only ? 'business day' : 'day';
            $dayText = $holdDays === 1 ? $dayType : $dayType.'s';
            $info['description'] = "Payout released after {$holdDays} {$dayText}";
        }

        return $info;
    }

    /**
     * Get current hold period settings
     */
    public function getSettings(): PayoutHoldSetting
    {
        return PayoutHoldSetting::current();
    }

    /**
     * Update hold period settings (admin only)
     */
    public function updateSettings(array $data, ?User $admin = null): PayoutHoldSetting
    {
        $admin = $admin ?? Auth::user();

        if (! $this->canBypassHold($admin)) {
            throw new \Exception('Unauthorized: Admin access required');
        }

        $settings = PayoutHoldSetting::current();
        $settings->update($data);

        Log::info('Payout hold settings updated', [
            'admin_id' => $admin->id,
            'admin_name' => $admin->name,
            'changes' => $data,
        ]);

        return $settings;
    }
}
