<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LimitReached extends Notification implements ShouldQueue
{
    use Queueable;

    protected $limitType;
    protected $currentCount;
    protected $limit;

    /**
     * Create a new notification instance.
     */
    public function __construct($limitType, $currentCount, $limit)
    {
        $this->limitType = $limitType;
        $this->currentCount = $currentCount;
        $this->limit = $limit;
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
        $limitTitle = $this->getLimitTitle();
        $subject = "You've reached your {$limitTitle} limit on MixPitch";
        
        return (new MailMessage)
            ->subject($subject)
            ->line("You've reached your {$limitTitle} limit ({$this->currentCount}/{$this->limit}) on your current plan.")
            ->line($this->getDetailedMessage())
            ->line('**Upgrade to Pro to unlock:**')
            ->line('• Unlimited projects and pitches')
            ->line('• 5x more storage space')
            ->line('• Priority support')
            ->line('• Advanced features')
            ->action('Upgrade to Pro', route('pricing'))
            ->line('Questions? Reply to this email and our team will help you find the perfect plan.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'limit_type' => $this->limitType,
            'current_count' => $this->currentCount,
            'limit' => $this->limit,
            'message' => $this->getLimitTitle() . ' limit reached'
        ];
    }

    private function getLimitTitle(): string
    {
        switch ($this->limitType) {
            case 'projects':
                return 'project';
            case 'pitches':
                return 'active pitch';
            case 'monthly_pitches':
                return 'monthly pitch';
            case 'storage':
                return 'storage';
            default:
                return 'usage';
        }
    }

    private function getDetailedMessage(): string
    {
        switch ($this->limitType) {
            case 'projects':
                return 'You can\'t create more projects until you upgrade or delete existing ones.';
            case 'pitches':
                return 'You can\'t create more pitches until some of your current pitches are completed.';
            case 'monthly_pitches':
                return 'You\'ve reached your monthly limit for receiving pitches. This limit resets next month.';
            case 'storage':
                return 'You can\'t upload more files until you free up space or upgrade your plan.';
            default:
                return 'You\'ve reached your current plan\'s limit for this feature.';
        }
    }
}
