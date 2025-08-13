<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientProjectInvite extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;

    public string $signedUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Project $project, string $signedUrl)
    {
        $this->project = $project;
        $this->signedUrl = $signedUrl;

        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $producer = $this->project->user;
        $subject = $producer->invite_email_subject ?: ('Invitation to Collaborate on Project: '.$this->project->title);

        return new Envelope(subject: $subject);
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $producer = $this->project->user;
        $branding = app(\App\Services\BrandingResolver::class)->forProducer($producer);

        return new Content(
            markdown: 'emails.client.project_invite',
            with: [
                'projectTitle' => $this->project->title,
                'producerName' => $producer->name,
                'clientName' => $this->project->client_name,
                'portalUrl' => $this->signedUrl,
                'customBody' => $branding['invite_body'] ?? null,
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
            Log::info('📧 CLIENT PROJECT INVITE EMAIL', [
                'to' => $this->project->client_email,
                'subject' => 'Invitation to Collaborate on Project: '.$this->project->title,
                'project_id' => $this->project->id,
                'client_name' => $this->project->client_name,
                'producer_name' => $this->project->user->name,
                'portal_url' => $this->signedUrl,
                'email_data' => [
                    'projectTitle' => $this->project->title,
                    'producerName' => $this->project->user->name,
                    'clientName' => $this->project->client_name,
                    'portalUrl' => $this->signedUrl,
                ],
                'template' => 'emails.client.project_invite',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ClientProjectInvite email content', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
            ]);
        }
    }
}
