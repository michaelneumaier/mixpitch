<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDelivered extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    protected $deliveryMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, ?string $deliveryMessage)
    {
        $this->order = $order->withoutRelations();
        $this->deliveryMessage = $deliveryMessage;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail']; // Add 'database' later if needed
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $orderUrl = route('orders.show', $this->order);
        $producerName = $this->order->producer->name ?? 'The Producer';

        $mail = (new MailMessage)
            ->subject('Your Order Has Been Delivered: Order #'.$this->order->id)
            ->greeting('Hello '.($notifiable->name ?? 'Client').',')
            ->line("{$producerName} has delivered your Order #{$this->order->id} ('{$this->order->servicePackage->title}').")
            ->line('Please review the delivery and either accept it or request revisions if needed.');

        if ($this->deliveryMessage) {
            $mail->line("Producer's Message:")
                ->line('> '.nl2br(e($this->deliveryMessage))); // Blockquote style for message
        }

        $mail->action('View Order & Delivery', $orderUrl)
            ->line('Thank you for using our platform!');

        return $mail;
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
            'producer_name' => $this->order->producer->name ?? 'N/A',
            'message' => 'Order #'.$this->order->id.' has been delivered.',
            'delivery_message' => $this->deliveryMessage,
            'url' => route('orders.show', $this->order),
        ];
    }
}
