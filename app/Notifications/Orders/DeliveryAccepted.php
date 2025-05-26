<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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

        // Potential TODO: Add payout information if applicable

        return (new MailMessage)
                    ->subject("Delivery Accepted for Order #{$this->order->id}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("Good news! {$clientName} has accepted the delivery for order #{$this->order->id} ('{$this->order->servicePackage->title}').")
                    ->line('The order is now marked as complete.')
                    // ->line('Payment is being processed and should arrive according to the standard schedule.') // Uncomment if payout is triggered
                    ->action('View Completed Order', $orderUrl)
                    ->line('Thank you for your work!');
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
            'message' => "Delivery accepted for order #{$this->order->id}. Order completed!",
            'action_url' => route('orders.show', $this->order->id),
        ];
    }
}
