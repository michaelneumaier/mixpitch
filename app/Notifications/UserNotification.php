<?php

namespace App\Notifications;

use App\Models\Notification as NotificationModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification; // Alias to avoid name conflict

class UserNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public string $eventType;

    public int $relatedId;

    public string $relatedType;

    public array $eventData;

    /**
     * Create a new notification instance.
     *
     * @param  string  $eventType  Type of the event (e.g., 'contest_winner_selected')
     * @param  int  $relatedId  ID of the related model (e.g., Pitch ID)
     * @param  string  $relatedType  Class name of the related model (e.g., App\Models\Pitch)
     * @param  array  $eventData  Additional data specific to the notification
     */
    public function __construct(string $eventType, int $relatedId, string $relatedType, array $eventData)
    {
        $this->eventType = $eventType;
        $this->relatedId = $relatedId;
        $this->relatedType = $relatedType;
        $this->eventData = $eventData;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     */
    public function via($notifiable): array
    {
        // Default to database, can be expanded based on user preferences or type
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     * (Optional - Add if email notifications are needed for this type)
     *
     * @param  mixed  $notifiable
     */
    public function toMail($notifiable): ?MailMessage
    {
        // Example structure - needs proper implementation based on eventType
        // if ($this->eventType === NotificationModel::TYPE_CONTEST_WINNER_SELECTED) {
        //     return (new MailMessage)
        //                 ->subject('Congratulations! You won the contest!')
        //                 ->line('You have been selected as the winner for the contest: ' . $this->eventData['project_name'])
        //                 ->action('View Project', url('/projects/...')); // Add relevant URL
        // }

        // Return null if no email representation for this specific eventType
        return null;
    }

    /**
     * Get the array representation of the notification (for database channel).
     *
     * @param  mixed  $notifiable
     */
    public function toArray($notifiable): array
    {
        // This structure mirrors the data saved by NotificationService::createNotification
        // The database channel essentially uses the data already prepared.
        return [
            'event_type' => $this->eventType,
            'related_id' => $this->relatedId,
            'related_type' => $this->relatedType,
            'data' => $this->eventData,
        ];
    }

    /**
     * Get the array representation for broadcasting (optional).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    // public function toBroadcast($notifiable): array
    // {
    //     return [
    //         // Data for broadcasting via Pusher, etc.
    //     ];
    // }
}
