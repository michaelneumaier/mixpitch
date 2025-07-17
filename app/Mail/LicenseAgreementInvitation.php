<?php

namespace App\Mail;

use App\Models\LicenseSignature;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LicenseAgreementInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public LicenseSignature $signature;

    /**
     * Create a new message instance.
     */
    public function __construct(LicenseSignature $signature)
    {
        $this->signature = $signature;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'License Agreement Required - '.$this->signature->project->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.license-agreement-invitation',
            with: [
                'signature' => $this->signature,
                'project' => $this->signature->project,
                'user' => $this->signature->user,
                'invitedBy' => $this->signature->invitedBy,
                'licenseTemplate' => $this->signature->licenseTemplate,
                'signUrl' => route('license.sign', $this->signature->id),
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
}
