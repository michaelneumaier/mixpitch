<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderMessage extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $sender;
    protected $messageContent;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, User $sender, string $messageContent)
    {
        $this->order = $order->withoutRelations();
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
        return ['mail']; // Add 'database' later
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $orderUrl = route('orders.show', $this->order);
        $senderName = $this->sender->name ?? 'Unknown User';
        $senderRole = ($this->sender->id === $this->order->client_user_id) ? 'Client' : 'Producer';

        // Truncate message for subject/preview line if too long
        $messagePreview = strlen($this->messageContent) > 50 ? substr($this->messageContent, 0, 47) . '...' : $this->messageContent;

        return (new MailMessage)
                    ->subject("New Message on Order #{$this->order->id} from {$senderName}")
                    ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
                    ->line("You have received a new message regarding Order #{$this->order->id} ('{$this->order->servicePackage->title}') from the {$senderRole} ({$senderName}).")
                    ->line("Message:")
                    ->line("> " . nl2br(e($this->messageContent))) // Blockquote style
                    ->action('View Order & Reply', $orderUrl);
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
            'order_title' => $this->order->servicePackage->title ?? 'N/A',
            'sender_id' => $this->sender->id,
            'sender_name' => $this->sender->name ?? 'N/A',
            'sender_role' => $senderRole,
            'message' => "New message from {$senderRole} on order #{$this->order->id}.",
            'message_content_preview' => strlen($this->messageContent) > 100 ? substr($this->messageContent, 0, 97) . '...' : $this->messageContent,
            'url' => route('orders.show', $this->order),
        ];
    }
}
