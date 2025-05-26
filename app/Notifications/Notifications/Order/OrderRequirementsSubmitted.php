<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OrderRequirementsSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order->withoutRelations(); // Avoid serializing large relations
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // TODO: Add database channel later if needed for in-app notifications
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $orderUrl = route('orders.show', $this->order);
        $clientName = $this->order->client->name ?? 'The Client';

        return (new MailMessage)
                    ->subject('Order Requirements Submitted: Order #' . $this->order->id)
                    ->greeting('Hello ' . ($notifiable->name ?? 'Producer') . ',')
                    ->line("The client, {$clientName}, has submitted the requirements for Order #{$this->order->id} ('{$this->order->servicePackage->title}').")
                    ->line('You can now review the requirements and begin working on the order.')
                    ->line(new HtmlString("<strong>Submitted Requirements:</strong><br><pre style=\"white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px;\">" . e($this->order->requirements_submitted) . "</pre>"))
                    ->action('View Order', $orderUrl)
                    ->line('Thank you for using our platform!');
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
            'message' => 'Requirements submitted for Order #' . $this->order->id,
            'url' => route('orders.show', $this->order),
        ];
    }
}
