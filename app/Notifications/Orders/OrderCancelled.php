<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    public User $canceller;

    public string $reason;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, User $canceller, string $reason)
    {
        $this->order = $order;
        $this->canceller = $canceller;
        $this->reason = $reason;
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
        $cancellerName = $this->canceller->name ?? 'A user';
        $cancellerRole = ($this->canceller->id === $this->order->client_user_id) ? 'Client' : 'Producer';
        $orderUrl = route('orders.show', $this->order->id);

        return (new MailMessage)
            ->subject("Order Cancelled: #{$this->order->id}")
            ->greeting("Hello {$notifiable->name},")
            ->line("Order #{$this->order->id} ('{$this->order->servicePackage->title}') has been cancelled by the {$cancellerRole} ({$cancellerName}).")
            ->line("\nReason provided:")
            ->line($this->reason)
                    // TODO: Add information about potential refunds or next steps if applicable
                    // ->line('If a payment was made, a refund will be processed according to our policy.')
            ->action('View Order Details', $orderUrl)
            ->line('Please contact support if you have any questions.');
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
            'order_title' => $this->order->servicePackage->title,
            'canceller_id' => $this->canceller->id,
            'canceller_name' => $this->canceller->name ?? 'N/A',
            'canceller_role' => $cancellerRole,
            'message' => "Order #{$this->order->id} was cancelled by the {$cancellerRole}.",
            'reason_preview' => \Illuminate\Support\Str::limit($this->reason, 100),
            'action_url' => route('orders.show', $this->order->id),
        ];
    }
}
