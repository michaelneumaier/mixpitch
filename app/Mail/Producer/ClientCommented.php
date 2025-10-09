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
use Illuminate\Support\Str;

class ClientCommented extends Mailable
{
    use Queueable, SerializesModels;

    public User $producer;

    public Project $project;

    public Pitch $pitch;

    public string $comment;

    public string $projectUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $producer,
        Project $project,
        Pitch $pitch,
        string $comment
    ) {
        $this->producer = $producer;
        $this->project = $project;
        $this->pitch = $pitch;
        $this->comment = $comment;
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
            subject: 'New Message from '.$clientName.' - '.$this->project->title,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.producer.client_commented',
            with: [
                'producerName' => $this->producer->name,
                'projectTitle' => $this->project->title,
                'clientName' => $this->project->client_name ?? 'Your client',
                'comment' => $this->comment,
                'commentExcerpt' => Str::limit($this->comment, 300),
                'isLongComment' => strlen($this->comment) > 300,
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
            Log::info('📧 PRODUCER CLIENT COMMENTED EMAIL', [
                'to' => $this->producer->email,
                'subject' => 'New Message from Client - '.$this->project->title,
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->project->client_name,
                'comment_length' => strlen($this->comment),
                'project_url' => $this->projectUrl,
                'email_data' => [
                    'producerName' => $this->producer->name,
                    'projectTitle' => $this->project->title,
                    'clientName' => $this->project->client_name,
                    'projectUrl' => $this->projectUrl,
                ],
                'template' => 'emails.producer.client_commented',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ClientCommented email content', [
                'error' => $e->getMessage(),
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
            ]);
        }
    }
}
