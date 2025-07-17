<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionUpgraded extends Notification implements ShouldQueue
{
    use Queueable;

    protected $plan;

    protected $tier;

    /**
     * Create a new notification instance.
     */
    public function __construct($plan, $tier)
    {
        $this->plan = $plan;
        $this->tier = $tier;
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
        $planName = ucfirst($this->plan).' '.ucfirst($this->tier);

        return (new MailMessage)
            ->subject('Welcome to MixPitch '.$planName.'! 🎉')
            ->line('Congratulations! Your subscription has been upgraded to '.$planName.'.')
            ->line('You now have access to all the powerful features that will help take your music collaboration to the next level.')
            ->line('**Your new benefits include:**')
            ->line('• Unlimited projects and pitches')
            ->line('• ' . ($this->tier === 'artist' ? '50GB' : '200GB') . ' total storage space')
            ->line('• Priority support')
            ->line($this->tier === 'artist' ? '• Custom portfolio layouts' : '• Advanced analytics')
            ->action('Explore Your New Features', route('dashboard'))
            ->line('Thank you for upgrading! We can\'t wait to see what amazing music you\'ll create.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'plan' => $this->plan,
            'tier' => $this->tier,
            'message' => 'Upgraded to '.ucfirst($this->plan).' '.ucfirst($this->tier),
        ];
    }
}
