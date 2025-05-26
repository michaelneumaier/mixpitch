<?php

namespace App\Mail;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientProjectCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;
    public Pitch $pitch;
    public string $signedUrl;
    public ?string $clientName;
    public ?string $feedback;
    public ?int $rating;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Project $project,
        Pitch $pitch,
        string $signedUrl,
        ?string $clientName,
        ?string $feedback,
        ?int $rating
    ) {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->signedUrl = $signedUrl;
        $this->clientName = $clientName;
        $this->feedback = $feedback;
        $this->rating = $rating;
        
        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Project '.$this->project->title.' is Complete',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.project_completed',
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

    /**
     * Log the email content for development purposes
     */
    protected function logEmailContent(): void
    {
        try {
            // Log the email details without rendering the view (to avoid mail hint issues)
            Log::info('📧 CLIENT PROJECT COMPLETED EMAIL', [
                'to' => $this->project->client_email,
                'subject' => 'Your Project '.$this->project->title.' is Complete',
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->clientName,
                'producer_name' => $this->project->user->name,
                'feedback' => $this->feedback,
                'rating' => $this->rating,
                'portal_url' => $this->signedUrl,
                'email_data' => [
                    'project' => [
                        'id' => $this->project->id,
                        'title' => $this->project->title,
                        'client_email' => $this->project->client_email,
                    ],
                    'pitch' => [
                        'id' => $this->pitch->id,
                        'status' => $this->pitch->status,
                    ],
                    'signedUrl' => $this->signedUrl,
                    'clientName' => $this->clientName,
                    'feedback' => $this->feedback,
                    'rating' => $this->rating,
                ],
                'template' => 'emails.client.project_completed'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ClientProjectCompleted email content', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id
            ]);
        }
    }
}
