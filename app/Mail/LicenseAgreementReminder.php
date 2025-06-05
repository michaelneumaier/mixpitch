<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\LicenseSignature;

class LicenseAgreementReminder extends Mailable implements ShouldQueue
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
            subject: 'Reminder: License Agreement Pending - ' . $this->signature->project->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.license-agreement-reminder',
            with: [
                'signature' => $this->signature,
                'project' => $this->signature->project,
                'user' => $this->signature->user,
                'licenseTemplate' => $this->signature->licenseTemplate,
                'signUrl' => route('license.sign', $this->signature->id),
                'reminderCount' => $this->signature->reminder_count ?? 1,
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