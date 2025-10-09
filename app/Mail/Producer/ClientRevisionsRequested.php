<?php

namespace App\Mail\Producer;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientRevisionsRequested extends Mailable
{
    use Queueable, SerializesModels;

    public User $producer;

    public Project $project;

    public Pitch $pitch;

    public string $feedback;

    public string $projectUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $producer,
        Project $project,
        Pitch $pitch,
        string $feedback
    ) {
        $this->producer = $producer;
        $this->project = $project;
        $this->pitch = $pitch;
        $this->feedback = $feedback;
        $this->projectUrl = route('projects.manage-client', $project);

        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $clientName = $this->project->client_name ?? 'Client';

        return new Envelope(
            subject: '[Action Required] '.$clientName.' Requested Revisions - '.$this->project->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.producer.client_revisions_requested',
            with: [
                'producerName' => $this->producer->name,
                'projectTitle' => $this->project->title,
                'clientName' => $this->project->client_name ?? 'Your client',
                'feedback' => $this->feedback,
                'projectUrl' => $this->projectUrl,
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
            Log::info('📧 PRODUCER CLIENT REVISIONS REQUESTED EMAIL', [
                'to' => $this->producer->email,
                'subject' => '[Action Required] Client Requested Revisions - '.$this->project->title,
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->project->client_name,
                'feedback_length' => strlen($this->feedback),
                'project_url' => $this->projectUrl,
                'email_data' => [
                    'producerName' => $this->producer->name,
                    'projectTitle' => $this->project->title,
                    'clientName' => $this->project->client_name,
                    'projectUrl' => $this->projectUrl,
                ],
                'template' => 'emails.producer.client_revisions_requested',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ClientRevisionsRequested email content', [
                'error' => $e->getMessage(),
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
            ]);
        }
    }
}
