<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class NewOrderMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    public User $sender;

    public string $messageContent;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, User $sender, string $messageContent)
    {
        $this->order = $order;
        $this->sender = $sender;
        $this->messageContent = $messageContent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Avoid sending notification back to the sender
        if ($notifiable->id === $this->sender->id) {
            return [];
        }

        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->sender->name ?? 'A user';
        $senderRole = ($this->sender->id === $this->order->client_user_id) ? 'Client' : 'Producer';
        $orderUrl = route('orders.show', $this->order->id);

        return (new MailMessage)
            ->subject("New Message on Order #{$this->order->id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have received a new message from {$senderName} ({$senderRole}) regarding order #{$this->order->id} ('{$this->order->servicePackage->title}').")
            ->line("\nMessage:")
            ->line(nl2br(e($this->messageContent))) // Display message content safely
            ->action('Reply in Order', $orderUrl)
            ->line('Thank you!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $senderRole = ($this->sender->id === $this->order->client_user_id) ? 'Client' : 'Producer';

        return [
            'order_id' => $this->order->id,
            'order_title' => $this->order->servicePackage->title,
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name ?? 'N/A',
            'sender_role' => $senderRole,
            'message' => "New message from {$senderName} ({$senderRole}) on order #{$this->order->id}.",
            'message_preview' => Str::limit($this->messageContent, 100),
            'action_url' => route('orders.show', $this->order->id),
        ];
    }
}
