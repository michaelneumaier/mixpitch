<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPaymentConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $servicePackage = $this->order->servicePackage;
        $producer = $this->order->producer;

        return (new MailMessage)
            ->subject('Payment Confirmed for Order #' . $this->order->id)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line('Your payment for order #' . $this->order->id . ' has been confirmed.')
            ->line('Service: ' . $servicePackage->title)
            ->line('Producer: ' . $producer->name)
            ->line('Amount: ' . $this->order->price . ' ' . $this->order->currency)
            ->action('View Order', route('orders.show', $this->order))
            ->line('Thank you for using MixPitch!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'title' => 'Payment Confirmed',
            'message' => 'Your payment for order #' . $this->order->id . ' has been confirmed.',
            'link' => route('orders.show', $this->order),
        ];
    }
}
