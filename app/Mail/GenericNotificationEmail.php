<?php

namespace App\Mail;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenericNotificationEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $notificationType;
    public array $notificationData;
    public ?int $originalNotificationId;

    public string $subjectLine = 'You have a new notification';
    public string $description = 'You have received a new notification.';
    public ?string $actionUrl = null;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $notificationType, array $notificationData, ?int $originalNotificationId = null)
    {
        $this->user = $user;
        $this->notificationType = $notificationType;
        $this->notificationData = $notificationData;
        $this->originalNotificationId = $originalNotificationId;

        $this->resolveContent();
    }

    /**
     * Determine the subject, description, and action URL based on the notification.
     */
    protected function resolveContent(): void
    {
        // Attempt to fetch the original notification to use its helper methods
        // This adds a DB query but keeps logic consistent with Notification model
        $notification = $this->originalNotificationId ? Notification::find($this->originalNotificationId) : null;

        if ($notification) {
            // Use the Notification model's methods if possible
            try {
                $this->description = $notification->getReadableDescription();
                $this->actionUrl = $notification->getUrl();
                // Generate a subject line based on the description (or type)
                $this->subjectLine = strip_tags($this->description); // Basic subject from description
            } catch (\Exception $e) {
                Log::error('Error resolving content from Notification model in GenericNotificationEmail', [
                    'notification_id' => $this->originalNotificationId,
                    'error' => $e->getMessage(),
                ]);
                // Fallback to generic content
                $this->generateFallbackContent();
            }
        } else {
            // Fallback if original notification couldn't be found
            Log::warning('Could not find original notification for email generation.', ['original_id' => $this->originalNotificationId]);
            $this->generateFallbackContent();
        }
        
        // Ensure action URL is absolute
        if ($this->actionUrl && !filter_var($this->actionUrl, FILTER_VALIDATE_URL)) {
            try {
                $this->actionUrl = url($this->actionUrl);
            } catch (\Exception $e) {
                Log::error('Failed to generate absolute URL for email action.', ['url' => $this->actionUrl]);
                $this->actionUrl = url('/'); // Fallback to base URL
            }
        }
    }

    /**
     * Generate generic fallback content if resolution fails.
     */
    protected function generateFallbackContent(): void
    {
        $this->subjectLine = 'You have a new notification';
        $this->description = 'You received a notification of type: ' . $this->notificationType . '. Please log in to view details.';
        $this->actionUrl = route('dashboard'); // Default action URL
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('app.name') . ': ' . $this->subjectLine,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.notifications.generic', // Use the specified markdown view
            with: [
                'greeting' => 'Hello ' . $this->user->name . ',',
                'description' => $this->description,
                'actionText' => 'View Details', // Text for the button
                'actionUrl' => $this->actionUrl, // URL for the button
                'level' => 'info', // For markdown styling (info, success, error)
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
