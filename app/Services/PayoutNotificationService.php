<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PayoutSchedule;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class PayoutNotificationService
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Notify user when a payout is scheduled
     */
    public function notifyPayoutScheduled(PayoutSchedule $payout): ?Notification
    {
        $user = User::find($payout->producer_user_id);
        if (!$user) {
            Log::warning('User not found for payout notification', ['payout_id' => $payout->id]);
            return null;
        }

        $data = [
            'payout_id' => $payout->id,
            'net_amount' => $payout->net_amount,
            'gross_amount' => $payout->gross_amount,
            'commission_rate' => $payout->commission_rate,
            'hold_release_date' => $payout->hold_release_date?->format('Y-m-d'),
            'workflow_type' => $payout->workflow_type,
            'project_id' => $payout->project_id,
            'project_name' => $payout->project->name ?? 'Unknown Project',
        ];

        // Add contest-specific data
        if ($payout->workflow_type === 'contest' && $payout->contestPrize) {
            $data['contest_placement'] = $payout->contestPrize->placement;
            $data['contest_prize_amount'] = $payout->contestPrize->amount;
        }

        try {
            return $this->notificationService->createNotification(
                $user,
                Notification::TYPE_CONTEST_PAYOUT_SCHEDULED,
                $payout,
                $data
            );
        } catch (\Exception $e) {
            Log::error('Failed to create payout scheduled notification', [
                'payout_id' => $payout->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Notify user when a payout is completed
     */
    public function notifyPayoutCompleted(PayoutSchedule $payout): ?Notification
    {
        $user = User::find($payout->producer_user_id);
        if (!$user) {
            Log::warning('User not found for payout completion notification', ['payout_id' => $payout->id]);
            return null;
        }

        $data = [
            'payout_id' => $payout->id,
            'net_amount' => $payout->net_amount,
            'gross_amount' => $payout->gross_amount,
            'commission_rate' => $payout->commission_rate,
            'completed_at' => $payout->completed_at?->format('Y-m-d H:i:s'),
            'stripe_transfer_id' => $payout->stripe_transfer_id,
            'workflow_type' => $payout->workflow_type,
            'project_id' => $payout->project_id,
            'project_name' => $payout->project->name ?? 'Unknown Project',
        ];

        // Add contest-specific data
        if ($payout->workflow_type === 'contest' && $payout->contestPrize) {
            $data['contest_placement'] = $payout->contestPrize->placement;
            $data['contest_prize_amount'] = $payout->contestPrize->amount;
        }

        try {
            return $this->notificationService->createNotification(
                $user,
                Notification::TYPE_PAYOUT_COMPLETED,
                $payout,
                $data
            );
        } catch (\Exception $e) {
            Log::error('Failed to create payout completed notification', [
                'payout_id' => $payout->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Notify user when a payout fails
     */
    public function notifyPayoutFailed(PayoutSchedule $payout): ?Notification
    {
        $user = User::find($payout->producer_user_id);
        if (!$user) {
            Log::warning('User not found for payout failure notification', ['payout_id' => $payout->id]);
            return null;
        }

        $data = [
            'payout_id' => $payout->id,
            'net_amount' => $payout->net_amount,
            'gross_amount' => $payout->gross_amount,
            'failure_reason' => $payout->failure_reason,
            'workflow_type' => $payout->workflow_type,
            'project_id' => $payout->project_id,
            'project_name' => $payout->project->name ?? 'Unknown Project',
        ];

        // Add contest-specific data
        if ($payout->workflow_type === 'contest' && $payout->contestPrize) {
            $data['contest_placement'] = $payout->contestPrize->placement;
            $data['contest_prize_amount'] = $payout->contestPrize->amount;
        }

        try {
            return $this->notificationService->createNotification(
                $user,
                Notification::TYPE_PAYOUT_FAILED,
                $payout,
                $data
            );
        } catch (\Exception $e) {
            Log::error('Failed to create payout failed notification', [
                'payout_id' => $payout->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Notify user when a payout is cancelled
     */
    public function notifyPayoutCancelled(PayoutSchedule $payout, string $reason = ''): ?Notification
    {
        $user = User::find($payout->producer_user_id);
        if (!$user) {
            Log::warning('User not found for payout cancellation notification', ['payout_id' => $payout->id]);
            return null;
        }

        $data = [
            'payout_id' => $payout->id,
            'net_amount' => $payout->net_amount,
            'gross_amount' => $payout->gross_amount,
            'cancellation_reason' => $reason,
            'workflow_type' => $payout->workflow_type,
            'project_id' => $payout->project_id,
            'project_name' => $payout->project->name ?? 'Unknown Project',
        ];

        // Add contest-specific data
        if ($payout->workflow_type === 'contest' && $payout->contestPrize) {
            $data['contest_placement'] = $payout->contestPrize->placement;
            $data['contest_prize_amount'] = $payout->contestPrize->amount;
        }

        try {
            return $this->notificationService->createNotification(
                $user,
                Notification::TYPE_PAYOUT_CANCELLED,
                $payout,
                $data
            );
        } catch (\Exception $e) {
            Log::error('Failed to create payout cancelled notification', [
                'payout_id' => $payout->id,
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Send comprehensive payout status update to user
     */
    public function sendPayoutStatusUpdate(PayoutSchedule $payout): void
    {
        switch ($payout->status) {
            case 'scheduled':
                $this->notifyPayoutScheduled($payout);
                break;
            case 'completed':
                $this->notifyPayoutCompleted($payout);
                break;
            case 'failed':
                $this->notifyPayoutFailed($payout);
                break;
            case 'cancelled':
                $this->notifyPayoutCancelled($payout);
                break;
            default:
                Log::info('No notification sent for payout status', [
                    'payout_id' => $payout->id,
                    'status' => $payout->status
                ]);
        }
    }
} 