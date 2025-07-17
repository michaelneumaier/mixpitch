<?php

namespace App\Mail;

use App\Models\Pitch;
use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceipt extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $pitch;

    public $project;

    public $amount;

    public $invoiceId;

    public $recipientType; // 'owner' or 'creator'

    /**
     * Create a new message instance.
     */
    public function __construct(Pitch $pitch, Project $project, $amount, $invoiceId, $recipientType)
    {
        $this->pitch = $pitch;
        $this->project = $project;
        $this->amount = $amount;
        $this->invoiceId = $invoiceId;
        $this->recipientType = $recipientType;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            subject: $this->getSubject(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment.receipt',
            with: [
                'pitch' => $this->pitch,
                'project' => $this->project,
                'amount' => $this->amount,
                'invoiceId' => $this->invoiceId,
                'recipientType' => $this->recipientType,
                'recipientName' => $this->getRecipientName(),
                'paymentDate' => $this->pitch->payment_completed_at ?
                    $this->pitch->payment_completed_at->format('F j, Y') :
                    now()->format('F j, Y'),
                'isFreeProject' => $this->amount == 0,
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
     * Get the appropriate subject line based on recipient type
     */
    protected function getSubject(): string
    {
        if ($this->amount == 0) {
            return "Free Project Completion Confirmation - {$this->project->name}";
        }

        if ($this->recipientType === 'owner') {
            return "Payment Receipt for {$this->project->name} - Pitch #{$this->pitch->id}";
        } else {
            return "Payment Received for {$this->project->name} - Pitch #{$this->pitch->id}";
        }
    }

    /**
     * Get the recipient's name
     */
    protected function getRecipientName(): string
    {
        if ($this->recipientType === 'owner') {
            return $this->project->user->name ?? 'Project Owner';
        } else {
            return $this->pitch->user->name ?? 'Pitch Creator';
        }
    }
}
