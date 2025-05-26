<?php

namespace App\Notifications\Orders;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderDelivered extends Notification implements ShouldQueue
{
    use Queueable;

    public Order $order;
    public ?string $deliveryMessage;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, ?string $deliveryMessage = null)
    {
        $this->order = $order;
        $this->deliveryMessage = $deliveryMessage;
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
        $producerName = $this->order->producer->name ?? 'The producer';
        $orderUrl = route('orders.show', $this->order->id);
        $mail = (new MailMessage)
                    ->subject("Order Delivered: #{$this->order->id} - {$this->order->servicePackage->title}")
                    ->greeting("Hello {$notifiable->name},")
                    ->line("{$producerName} has delivered your order #{$this->order->id} ('{$this->order->servicePackage->title}').")
                    ->line('Please review the delivery and accept it or request revisions.');

        if ($this->deliveryMessage) {
            $mail->line("\nDelivery Message:")
                 ->line($this->deliveryMessage);
        }

        $mail->action('View Order', $orderUrl)
             ->line('Thank you for using our application!');

        return $mail;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $message = "Your order #{$this->order->id} has been delivered.";
        if ($this->deliveryMessage) {
            $message .= " Delivery message included."; // Keep it concise for DB notification
        }

        return [
            'order_id' => $this->order->id,
            'order_title' => $this->order->servicePackage->title,
            'producer_id' => $this->order->producer_user_id,
            'producer_name' => $this->order->producer->name ?? 'N/A',
            'message' => $message,
            'action_url' => route('orders.show', $this->order->id),
            'has_delivery_message' => !empty($this->deliveryMessage), // Useful flag
        ];
    }
}
