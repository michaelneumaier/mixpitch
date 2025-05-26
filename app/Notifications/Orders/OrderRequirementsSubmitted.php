<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderRequirementsSubmitted extends Notification
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

        return (new MailMessage)
                    ->subject("Requirements Submitted for Order #{$this->order->id}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("{$clientName} has submitted the requirements for order #{$this->order->id} - '{$this->order->servicePackage->title}'.")
                    ->line('You can now review the requirements and begin working on the order.')
                    ->action('View Order', $orderUrl)
                    ->line('Thank you for using our application!');
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
            'message' => "Requirements submitted for order #{$this->order->id}.",
            'action_url' => route('orders.show', $this->order->id),
        ];
    }
}
