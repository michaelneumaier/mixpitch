<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DeliveryAccepted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->withoutRelations();
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
        $clientName = $this->order->client->name ?? 'The Client';

        return (new MailMessage)
                    ->subject('Delivery Accepted: Order #' . $this->order->id . ' Completed!')
                    ->greeting('Hello ' . ($notifiable->name ?? 'Producer') . ',')
                    ->line("Good news! {$clientName} has accepted the delivery for Order #{$this->order->id} ('{$this->order->servicePackage->title}').")
                    ->line('The order is now marked as completed.')
                    ->line('Payment release will be processed according to the platform schedule. (Placeholder - Payout logic TBD)')
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
            'order_title' => $this->order->servicePackage->title ?? 'N/A',
            'client_name' => $this->order->client->name ?? 'N/A',
            'message' => 'Delivery accepted and order #' . $this->order->id . ' completed.',
            'url' => route('orders.show', $this->order),
        ];
    }
}
