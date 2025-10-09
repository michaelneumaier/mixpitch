<?php

namespace App\Mail\Client;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RevisionRequestConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;

    public Pitch $pitch;

    public string $feedback;

    public string $signedUrl;

    public ?string $clientName;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Project $project,
        Pitch $pitch,
        string $feedback,
        string $signedUrl,
        ?string $clientName = null
    ) {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->feedback = $feedback;
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
            subject: 'Revision Request Received for '.$this->project->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.revision_request_confirmation',
            with: [
                'projectTitle' => $this->project->title,
                'producerName' => $this->project->user->name,
                'clientName' => $this->clientName,
                'feedback' => $this->feedback,
                'portalUrl' => $this->signedUrl,
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
            Log::info('📧 CLIENT REVISION REQUEST CONFIRMATION EMAIL', [
                'to' => $this->project->client_email,
                'subject' => 'Revision Request Received for '.$this->project->title,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->clientName,
                'producer_name' => $this->project->user->name,
                'feedback_length' => strlen($this->feedback),
                'portal_url' => $this->signedUrl,
                'email_data' => [
                    'projectTitle' => $this->project->title,
                    'producerName' => $this->project->user->name,
                    'clientName' => $this->clientName,
                    'portalUrl' => $this->signedUrl,
                ],
                'template' => 'emails.client.revision_request_confirmation',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log RevisionRequestConfirmation email content', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
            ]);
        }
    }
}
