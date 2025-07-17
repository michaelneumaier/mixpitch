<?php

namespace App\Listeners;

use App\Events\NotificationCreated;
use App\Jobs\SendNotificationEmailJob;
use App\Models\NotificationChannelPreference;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class NotificationCreatedListener implements ShouldQueue // Implement ShouldQueue to handle asynchronously
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(NotificationCreated $event): void
    {
        $notification = $event->notification;
        $user = $notification->user;

        if (! $user) {
            Log::warning('NotificationCreatedListener: User not found for notification.', ['notification_id' => $notification->id]);

            return;
        }

        // Check user's preference for the 'email' channel for this notification type
        $preference = NotificationChannelPreference::where('user_id', $user->id)
            ->where('notification_type', $notification->type)
            ->where('channel', 'email')
            ->first();

        // Send email only if specifically enabled OR if no preference exists (default is enabled)
        $emailEnabled = $preference ? $preference->is_enabled : true;

        if ($emailEnabled) {
            Log::info('Dispatching SendNotificationEmailJob for user and notification type.', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
            ]);
            // Dispatch the job to send the email
            SendNotificationEmailJob::dispatch($user, $notification->type, $notification->data, $notification->id);
        } else {
            Log::info('Skipping email notification due to user preference.', [
                'user_id' => $user->id,
                'notification_id' => $notification->id,
                'notification_type' => $notification->type,
            ]);
        }
    }
}
