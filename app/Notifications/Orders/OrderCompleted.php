<?php

namespace App\Notifications\Orders;

use App\Events\NotificationCreated;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCompleted extends Notification implements ShouldQueue
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
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $client = $this->order->client;
        $servicePackage = $this->order->servicePackage;

        return (new MailMessage)
            ->subject('Order Completed: '.$servicePackage->title)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Great news! Your order has been completed.')
            ->line('Order #: '.$this->order->id)
            ->line('Service: '.$servicePackage->title)
            ->line('Client: '.$client->name)
            ->action('View Order', route('orders.show', $this->order))
            ->line('Thank you for using MixPitch!');
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
            'title' => 'Order Completed',
            'message' => 'Order #'.$this->order->id.' has been completed.',
            'link' => route('orders.show', $this->order),
        ];
    }

    /**
     * The event that should be broadcast when the notification is sent.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function toBroadcast($notifiable)
    {
        event(new NotificationCreated($notifiable, $this));

        return [];
    }
}
