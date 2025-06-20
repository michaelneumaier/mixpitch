<?php

namespace App\Mail;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProducerClientApprovedAndCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public User $producer;
    public Project $project;
    public Pitch $pitch;
    public bool $hasPayment;

    /**
     * Create a new message instance.
     */
    public function __construct(User $producer, Project $project, Pitch $pitch, bool $hasPayment)
    {
        $this->producer = $producer;
        $this->project = $project;
        $this->pitch = $pitch;
        $this->hasPayment = $hasPayment;
        
        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->hasPayment 
            ? 'Great News! Client Approved & Paid for "' . $this->project->title . '"'
            : 'Great News! Client Approved "' . $this->project->title . '"';
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.producer.client_approved_and_completed',
            with: [
                'producerName' => $this->producer->name,
                'projectTitle' => $this->project->title,
                'clientName' => $this->project->client_name ?? 'Your client',
                'hasPayment' => $this->hasPayment,
                'paymentAmount' => $this->pitch->payment_amount,
                'projectUrl' => route('projects.manage-client', $this->project),
                'dashboardUrl' => route('dashboard'),
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
            Log::info('📧 PRODUCER CLIENT APPROVED AND COMPLETED EMAIL', [
                'to' => $this->producer->email,
                'subject' => $this->hasPayment 
                    ? 'Great News! Client Approved & Paid for "' . $this->project->title . '"'
                    : 'Great News! Client Approved "' . $this->project->title . '"',
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->project->client_name,
                'has_payment' => $this->hasPayment,
                'payment_amount' => $this->pitch->payment_amount,
                'email_data' => [
                    'producerName' => $this->producer->name,
                    'projectTitle' => $this->project->title,
                    'clientName' => $this->project->client_name ?? 'Your client',
                    'hasPayment' => $this->hasPayment,
                    'paymentAmount' => $this->pitch->payment_amount,
                    'projectUrl' => route('projects.manage-client', $this->project),
                    'dashboardUrl' => route('dashboard'),
                ],
                'template' => 'emails.producer.client_approved_and_completed'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ProducerClientApprovedAndCompleted email content', [
                'error' => $e->getMessage(),
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id
            ]);
        }
    }
} 