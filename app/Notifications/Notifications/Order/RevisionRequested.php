<?php

namespace App\Notifications\Notifications\Order;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class RevisionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    protected $feedback;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order, string $feedback)
    {
        $this->order = $order->withoutRelations();
        $this->feedback = $feedback;
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
            ->subject('Revision Requested for Order #'.$this->order->id)
            ->greeting('Hello '.($notifiable->name ?? 'Producer').',')
            ->line("{$clientName} has requested revisions for Order #{$this->order->id} ('{$this->order->servicePackage->title}').")
            ->line('Please review the feedback below and submit an updated delivery.')
            ->line(new HtmlString('<strong>Client Feedback:</strong><br><pre style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 4px;">'.e($this->feedback).'</pre>'))
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
            'message' => 'Revisions requested for Order #'.$this->order->id,
            'feedback' => $this->feedback,
            'url' => route('orders.show', $this->order),
        ];
    }
}
