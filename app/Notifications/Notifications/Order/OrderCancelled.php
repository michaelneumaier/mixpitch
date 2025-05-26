<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $canceller;
    protected $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, User $canceller, string $reason)
    {
        $this->order = $order->withoutRelations();
        $this->canceller = $canceller; // Don't need withoutRelations if just using name/id
        $this->reason = $reason;
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
        $cancellerName = $this->canceller->name ?? 'Unknown User';
        $cancellerRole = ($this->canceller->id === $this->order->client_user_id) ? 'Client' : 'Producer';

        return (new MailMessage)
                    ->subject('Order Cancelled: Order #' . $this->order->id)
                    ->greeting('Hello ' . ($notifiable->name ?? 'User') . ',')
                    ->line("Order #{$this->order->id} ('{$this->order->servicePackage->title}') has been cancelled by the {$cancellerRole} ({$cancellerName}).")
                    ->line("Reason provided:")
                    ->line("> " . nl2br(e($this->reason))) // Blockquote style
                    ->line('If you have any questions, please contact support or the other party involved.')
                    ->action('View Cancelled Order', $orderUrl);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $cancellerRole = ($this->canceller->id === $this->order->client_user_id) ? 'Client' : 'Producer';
        return [
            'order_id' => $this->order->id,
            'order_title' => $this->order->servicePackage->title ?? 'N/A',
            'canceller_id' => $this->canceller->id,
            'canceller_name' => $this->canceller->name ?? 'N/A',
            'canceller_role' => $cancellerRole,
            'reason' => $this->reason,
            'message' => "Order #{$this->order->id} was cancelled by the {$cancellerRole}.".",
            'url' => route('orders.show', $this->order),
        ];
    }
}
