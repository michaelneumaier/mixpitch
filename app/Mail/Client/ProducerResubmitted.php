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

class ProducerResubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;

    public Pitch $pitch;

    public string $signedUrl;

    public ?string $clientName;

    public int $fileCount;

    public ?string $producerNote;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Project $project,
        Pitch $pitch,
        string $signedUrl,
        ?string $clientName = null,
        int $fileCount = 0,
        ?string $producerNote = null
    ) {
        $this->project = $project;
        $this->pitch = $pitch;
        $this->signedUrl = $signedUrl;
        $this->clientName = $clientName;
        $this->fileCount = $fileCount;
        $this->producerNote = $producerNote;

        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Updated Work Ready for Review - '.$this->project->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.client.producer_resubmitted',
            with: [
                'projectTitle' => $this->project->title,
                'producerName' => $this->project->user->name,
                'clientName' => $this->clientName,
                'fileCount' => $this->fileCount,
                'producerNote' => $this->producerNote,
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
            Log::info('📧 CLIENT PRODUCER RESUBMITTED EMAIL', [
                'to' => $this->project->client_email,
                'subject' => 'Updated Work Ready for Review - '.$this->project->title,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->clientName,
                'producer_name' => $this->project->user->name,
                'file_count' => $this->fileCount,
                'has_producer_note' => ! empty($this->producerNote),
                'portal_url' => $this->signedUrl,
                'email_data' => [
                    'projectTitle' => $this->project->title,
                    'producerName' => $this->project->user->name,
                    'clientName' => $this->clientName,
                    'fileCount' => $this->fileCount,
                    'portalUrl' => $this->signedUrl,
                ],
                'template' => 'emails.client.producer_resubmitted',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ProducerResubmitted email content', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
            ]);
        }
    }
}
