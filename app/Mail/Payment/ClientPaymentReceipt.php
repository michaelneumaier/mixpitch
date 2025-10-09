<?php

namespace App\Mail\Payment;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ClientPaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public Project $project;

    public ?string $clientName;

    public float $amount;

    public string $currency;

    public string $transactionId;

    public string $invoiceUrl;

    public string $portalUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Project $project,
        ?string $clientName,
        float $amount,
        string $currency,
        string $transactionId,
        string $invoiceUrl,
        string $portalUrl
    ) {
        $this->project = $project;
        $this->clientName = $clientName;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->transactionId = $transactionId;
        $this->invoiceUrl = $invoiceUrl;
        $this->portalUrl = $portalUrl;

        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $formattedAmount = $this->currency.' '.number_format($this->amount, 2);

        return new Envelope(
            subject: 'Payment Confirmation - '.$this->project->title.' - '.$formattedAmount,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment.client_receipt',
            with: [
                'projectTitle' => $this->project->title,
                'producerName' => $this->project->user->name,
                'clientName' => $this->clientName,
                'amount' => $this->amount,
                'formattedAmount' => number_format($this->amount, 2),
                'currency' => $this->currency,
                'transactionId' => $this->transactionId,
                'invoiceUrl' => $this->invoiceUrl,
                'portalUrl' => $this->portalUrl,
                'paymentDate' => now()->format('F j, Y'),
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
            Log::info('📧 CLIENT PAYMENT RECEIPT EMAIL', [
                'to' => $this->project->client_email,
                'subject' => 'Payment Confirmation - '.$this->project->title,
                'project_id' => $this->project->id,
                'client_name' => $this->clientName,
                'producer_name' => $this->project->user->name,
                'amount' => $this->amount,
                'currency' => $this->currency,
                'transaction_id' => $this->transactionId,
                'invoice_url' => $this->invoiceUrl,
                'portal_url' => $this->portalUrl,
                'email_data' => [
                    'projectTitle' => $this->project->title,
                    'producerName' => $this->project->user->name,
                    'clientName' => $this->clientName,
                    'amount' => $this->amount,
                    'currency' => $this->currency,
                    'transactionId' => $this->transactionId,
                ],
                'template' => 'emails.payment.client_receipt',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ClientPaymentReceipt email content', [
                'error' => $e->getMessage(),
                'project_id' => $this->project->id,
            ]);
        }
    }
}
