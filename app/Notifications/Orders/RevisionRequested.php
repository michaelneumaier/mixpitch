<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RevisionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;
    public string $feedback;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $feedback)
    {
        $this->order = $order;
        $this->feedback = $feedback;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $clientName = $this->order->client->name ?? 'The client';
        $orderUrl = route('orders.show', $this->order->id);

        return (new MailMessage)
                    ->subject("Revisions Requested for Order #{$this->order->id}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("{$clientName} has requested revisions for order #{$this->order->id} ('{$this->order->servicePackage->title}').")
                    ->line("\nFeedback:")
                    ->line($this->feedback)
                    ->action('View Order', $orderUrl)
                    ->line('Please review the feedback and submit a revised delivery.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_title' => $this->order->servicePackage->title,
            'client_id' => $this->order->client_user_id,
            'client_name' => $this->order->client->name ?? 'N/A',
            'message' => "Revisions requested for order #{$this->order->id}.",
            'feedback_preview' => \Illuminate\Support\Str::limit($this->feedback, 100),
            'action_url' => route('orders.show', $this->order->id),
        ];
    }
}
