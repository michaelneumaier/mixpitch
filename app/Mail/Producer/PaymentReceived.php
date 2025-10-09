<?php

namespace App\Mail\Producer;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PaymentReceived extends Mailable
{
    use Queueable, SerializesModels;

    public User $producer;

    public Project $project;

    public Pitch $pitch;

    public float $grossAmount;

    public float $platformFee;

    public float $netAmount;

    public string $currency;

    public Carbon $payoutDate;

    public string $projectUrl;

    public string $earningsUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(
        User $producer,
        Project $project,
        Pitch $pitch,
        float $grossAmount,
        float $platformFee,
        float $netAmount,
        string $currency,
        Carbon $payoutDate
    ) {
        $this->producer = $producer;
        $this->project = $project;
        $this->pitch = $pitch;
        $this->grossAmount = $grossAmount;
        $this->platformFee = $platformFee;
        $this->netAmount = $netAmount;
        $this->currency = $currency;
        $this->payoutDate = $payoutDate;
        $this->projectUrl = route('projects.manage-client', $project);
        $this->earningsUrl = route('dashboard').'#earnings';

        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $formattedAmount = $this->currency.' '.number_format($this->netAmount, 2);

        return new Envelope(
            subject: 'Payment Received - '.$formattedAmount.' Payout Scheduled',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.producer.payment_received',
            with: [
                'producerName' => $this->producer->name,
                'projectTitle' => $this->project->title,
                'clientName' => $this->project->client_name ?? 'Your client',
                'grossAmount' => number_format($this->grossAmount, 2),
                'platformFee' => number_format($this->platformFee, 2),
                'platformFeePercentage' => ($this->grossAmount > 0) ? round(($this->platformFee / $this->grossAmount) * 100, 1) : 0,
                'netAmount' => number_format($this->netAmount, 2),
                'currency' => $this->currency,
                'payoutDate' => $this->payoutDate->format('F j, Y'),
                'payoutDateRelative' => $this->payoutDate->diffForHumans(),
                'projectUrl' => $this->projectUrl,
                'earningsUrl' => $this->earningsUrl,
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
            Log::info('📧 PRODUCER PAYMENT RECEIVED EMAIL', [
                'to' => $this->producer->email,
                'subject' => 'Payment Received - Payout Scheduled',
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
                'client_name' => $this->project->client_name,
                'gross_amount' => $this->grossAmount,
                'platform_fee' => $this->platformFee,
                'net_amount' => $this->netAmount,
                'currency' => $this->currency,
                'payout_date' => $this->payoutDate->toDateString(),
                'project_url' => $this->projectUrl,
                'earnings_url' => $this->earningsUrl,
                'email_data' => [
                    'producerName' => $this->producer->name,
                    'projectTitle' => $this->project->title,
                    'clientName' => $this->project->client_name,
                    'netAmount' => $this->netAmount,
                    'currency' => $this->currency,
                ],
                'template' => 'emails.producer.payment_received',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log PaymentReceived email content', [
                'error' => $e->getMessage(),
                'producer_id' => $this->producer->id,
                'project_id' => $this->project->id,
                'pitch_id' => $this->pitch->id,
            ]);
        }
    }
}
