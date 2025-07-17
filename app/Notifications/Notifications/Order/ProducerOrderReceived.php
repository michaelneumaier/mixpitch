<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProducerOrderReceived extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

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
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $servicePackage = $this->order->servicePackage;
        $client = $this->order->client;

        return (new MailMessage)
            ->subject('New Order Received #'.$this->order->id)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('You have received a new order for your service package.')
            ->line('Order #: '.$this->order->id)
            ->line('Service: '.$servicePackage->title)
            ->line('Client: '.$client->name)
            ->line('Amount: '.$this->order->price.' '.$this->order->currency)
            ->action('View Order', route('orders.show', $this->order))
            ->line('The client will submit their requirements soon. You will be notified when they do.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'title' => 'New Order Received',
            'message' => 'You have received a new order #'.$this->order->id.' for your service package.',
            'link' => route('orders.show', $this->order),
        ];
    }
}
