<?php

namespace App\Jobs;

use App\Mail\GenericNotificationEmail; // We'll create this Mailable next
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNotificationEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public string $notificationType;
    public array $data;
    public ?int $originalNotificationId; // Nullable in case it's ever dispatched without one

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, string $notificationType, array $data, ?int $originalNotificationId = null)
    {
        $this->user = $user;
        $this->notificationType = $notificationType;
        $this->data = $data;
        $this->originalNotificationId = $originalNotificationId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Here you could potentially have logic to choose different Mailables
            // based on $this->notificationType if needed in the future.
            // For now, we use a generic one.
            
            $mailable = new GenericNotificationEmail(
                $this->user,
                $this->notificationType,
                $this->data,
                $this->originalNotificationId
            );

            Mail::to($this->user->email)->send($mailable);

            Log::info('Successfully sent notification email.', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'notification_type' => $this->notificationType,
                'original_notification_id' => $this->originalNotificationId
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send notification email.', [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
                'notification_type' => $this->notificationType,
                'original_notification_id' => $this->originalNotificationId,
                'error' => $e->getMessage(),
                // Optionally include trace if needed for debugging
                // 'trace' => $e->getTraceAsString()
            ]);

            // Optional: Decide if the job should be released back onto the queue
            // $this->release(60); // Release back for 60 seconds
            
            // Or fail the job permanently
            $this->fail($e);
        }
    }
}
