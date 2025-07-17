<?php

namespace App\Mail;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientReviewReady extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;

    public Pitch $pitch;

    public string $signedUrl;

    public ?string $clientName;

    /**
     * Create a new message instance.
     */
    public function __construct(Project $project, Pitch $pitch, string $signedUrl, ?string $clientName = null)
    {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->signedUrl = $signedUrl;
        $this->clientName = $clientName;

        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Project "'.$this->project->title.'" is Ready for Review',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.review_ready',
            with: [
                'projectTitle' => $this->project->title,
                'producerName' => $this->project->user->name,
                'clientName' => $this->clientName,
                'portalUrl' => $this->signedUrl,
                'fileCount' => $this->pitch->files->count(),
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

    /**
     * Log the email content for development purposes
     */
    protected function logEmailContent(): void
    {
        try {
            // Log the email details without rendering the view (to avoid mail hint issues)
            Log::info('📧 CLIENT REVIEW READY EMAIL', [
                'to' => $this->project->client_email,
                'subject' => 'Your Project "'.$this->project->title.'" is Ready for Review',
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->clientName,
                'producer_name' => $this->project->user->name,
                'file_count' => $this->pitch->files->count(),
                'portal_url' => $this->signedUrl,
                'email_data' => [
                    'projectTitle' => $this->project->title,
                    'producerName' => $this->project->user->name,
                    'clientName' => $this->clientName,
                    'portalUrl' => $this->signedUrl,
                    'fileCount' => $this->pitch->files->count(),
                ],
                'template' => 'emails.client.review_ready',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ClientReviewReady email content', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
            ]);
        }
    }
}
