<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $planName;

    protected $endsAt;

    /**
     * Create a new notification instance.
     */
    public function __construct($planName, $endsAt)
    {
        $this->planName = $planName;
        $this->endsAt = $endsAt;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your MixPitch Subscription Has Been Cancelled')
            ->line('We\'re sorry to see you go! Your '.$this->planName.' subscription has been cancelled.')
            ->line('You\'ll continue to have access to all Pro features until '.$this->endsAt->format('F j, Y').'.')
            ->line('After that date, your account will automatically switch to our Free plan.')
            ->line('**What happens next:**')
            ->line('• Your projects and data will be preserved')
            ->line('• You\'ll have access to Free plan features')
            ->line('• You can upgrade again anytime')
            ->action('Resume Subscription', route('subscription.index'))
            ->line('We\'d love to have you back anytime. Thank you for being part of the MixPitch community!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plan_name' => $this->planName,
            'ends_at' => $this->endsAt,
            'message' => 'Subscription cancelled - access until '.$this->endsAt->format('M j, Y'),
        ];
    }
}
