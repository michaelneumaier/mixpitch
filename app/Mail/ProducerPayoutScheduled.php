<?php

namespace App\Mail;

use App\Models\User;
use App\Models\PayoutSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProducerPayoutScheduled extends Mailable
{
    use Queueable, SerializesModels;

    public User $producer;
    public float $netAmount;
    public PayoutSchedule $payoutSchedule;

    /**
     * Create a new message instance.
     */
    public function __construct(User $producer, float $netAmount, PayoutSchedule $payoutSchedule)
    {
        $this->producer = $producer;
        $this->netAmount = $netAmount;
        $this->payoutSchedule = $payoutSchedule;
        
        // Log the email details for development
        $this->logEmailContent();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payout Scheduled: $' . number_format($this->netAmount, 2) . ' for "' . ($this->payoutSchedule->project->title ?? 'Project') . '"',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.producer.payout_scheduled',
            with: [
                'producerName' => $this->producer->name,
                'netAmount' => $this->netAmount,
                'grossAmount' => $this->payoutSchedule->gross_amount,
                'commissionRate' => $this->payoutSchedule->commission_rate,
                'commissionAmount' => $this->payoutSchedule->commission_amount,
                'projectTitle' => $this->payoutSchedule->project->title ?? 'Project',
                'holdReleaseDate' => $this->payoutSchedule->hold_release_date,
                'payoutsUrl' => route('payouts.index'),
                'dashboardUrl' => route('dashboard'),
                'payoutScheduleId' => $this->payoutSchedule->id,
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
            Log::info('📧 PRODUCER PAYOUT SCHEDULED EMAIL', [
                'to' => $this->producer->email,
                'subject' => 'Payout Scheduled: $' . number_format($this->netAmount, 2) . ' for "' . ($this->payoutSchedule->project->title ?? 'Project') . '"',
                'producer_id' => $this->producer->id,
                'payout_schedule_id' => $this->payoutSchedule->id,
                'net_amount' => $this->netAmount,
                'gross_amount' => $this->payoutSchedule->gross_amount,
                'commission_rate' => $this->payoutSchedule->commission_rate,
                'hold_release_date' => $this->payoutSchedule->hold_release_date,
                'project_title' => $this->payoutSchedule->project->title ?? 'Project',
                'email_data' => [
                    'producerName' => $this->producer->name,
                    'netAmount' => $this->netAmount,
                    'grossAmount' => $this->payoutSchedule->gross_amount,
                    'commissionRate' => $this->payoutSchedule->commission_rate,
                    'commissionAmount' => $this->payoutSchedule->commission_amount,
                    'projectTitle' => $this->payoutSchedule->project->title ?? 'Project',
                    'holdReleaseDate' => $this->payoutSchedule->hold_release_date,
                    'payoutsUrl' => route('payouts.index'),
                    'dashboardUrl' => route('dashboard'),
                    'payoutScheduleId' => $this->payoutSchedule->id,
                ],
                'template' => 'emails.producer.payout_scheduled'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log ProducerPayoutScheduled email content', [
                'error' => $e->getMessage(),
                'producer_id' => $this->producer->id,
                'payout_schedule_id' => $this->payoutSchedule->id
            ]);
        }
    }
} 