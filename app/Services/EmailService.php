<?php

namespace App\Services;

use App\Mail\ClientProjectCompleted;
use App\Models\EmailAudit;
use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class EmailService
{
    /**
     * Send an email if the recipient is not in the suppression list
     *
     * @param  string|array  $recipient
     */
    public function send(Mailable $mailable, $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Handle array of recipients
        if (is_array($recipient)) {
            $results = [];
            foreach ($recipient as $email) {
                $results[] = $this->sendToSingleRecipient($mailable, $email, $type, $metadata);
            }

            return ! in_array(false, $results, true);
        }

        // Handle single recipient
        return $this->sendToSingleRecipient($mailable, $recipient, $type, $metadata);
    }

    /**
     * Send email to a single recipient
     */
    protected function sendToSingleRecipient(Mailable $mailable, string $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Check if email is suppressed
        if ($this->isEmailSuppressed($recipient)) {
            Log::info('Email sending skipped - recipient is in suppression list', [
                'email' => $recipient,
                'type' => $type,
            ]);

            // Log the audit with suppressed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'suppressed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'reason' => 'Email is in suppression list',
                ])
            );

            return false;
        }

        try {
            // Extract the subject from the mailable
            $subject = $mailable->subject ?? '[No Subject]';

            // Create comprehensive metadata
            $auditMetadata = array_merge($metadata ?? [], [
                'mailable_class' => get_class($mailable),
                'email_type' => $type,
                'sent_at' => now()->toIso8601String(),
            ]);

            // Attempt to capture email content before sending
            $messageId = null;
            $headers = null;
            $content = null;
            $recipientName = null;

            // Capture email content if possible by rendering it
            if (method_exists($mailable, 'render')) {
                try {
                    $content = $mailable->render();
                } catch (\Exception $e) {
                    Log::warning('Could not capture email content: '.$e->getMessage());
                }
            }

            // Try to extract the recipient name if available
            if (method_exists($mailable, 'build') && isset($mailable->to)) {
                if (is_array($mailable->to) && ! empty($mailable->to[0]) && isset($mailable->to[0]['name'])) {
                    $recipientName = $mailable->to[0]['name'];
                }
            }

            // Send the email
            Mail::to($recipient)->send($mailable);

            // Log the event
            EmailEvent::logEvent(
                $recipient,
                'sent',
                $type,
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                ])
            );

            // Log the audit
            EmailAudit::log(
                $recipient,
                $subject,
                'sent',
                $auditMetadata,
                $headers,
                $content,
                $messageId,
                $recipientName
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'email' => $recipient,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            // Log the audit with failed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'failed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'error' => $e->getMessage(),
                ])
            );

            return false;
        }
    }

    /**
     * Queue an email if the recipient is not in the suppression list
     *
     * @param  string|array  $recipient
     */
    public function queue(Mailable $mailable, $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Handle array of recipients
        if (is_array($recipient)) {
            $results = [];
            foreach ($recipient as $email) {
                $results[] = $this->queueForSingleRecipient($mailable, $email, $type, $metadata);
            }

            return ! in_array(false, $results, true);
        }

        // Handle single recipient
        return $this->queueForSingleRecipient($mailable, $recipient, $type, $metadata);
    }

    /**
     * Queue email for a single recipient
     */
    protected function queueForSingleRecipient(Mailable $mailable, string $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Check if email is suppressed
        if ($this->isEmailSuppressed($recipient)) {
            Log::info('Email queuing skipped - recipient is in suppression list', [
                'email' => $recipient,
                'type' => $type,
            ]);

            // Log the audit with suppressed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'suppressed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'reason' => 'Email is in suppression list',
                ])
            );

            return false;
        }

        try {
            // Extract the subject from the mailable
            $subject = $mailable->subject ?? '[No Subject]';

            // Create comprehensive metadata
            $auditMetadata = array_merge($metadata ?? [], [
                'mailable_class' => get_class($mailable),
                'email_type' => $type,
                'queued_at' => now()->toIso8601String(),
            ]);

            // Attempt to capture email content before queueing
            $messageId = null;
            $headers = null;
            $content = null;
            $recipientName = null;

            // Capture email content if possible by rendering it
            if (method_exists($mailable, 'render')) {
                try {
                    $content = $mailable->render();
                } catch (\Exception $e) {
                    Log::warning('Could not capture email content: '.$e->getMessage());
                }
            }

            // Try to extract the recipient name if available
            if (method_exists($mailable, 'build') && isset($mailable->to)) {
                if (is_array($mailable->to) && ! empty($mailable->to[0]) && isset($mailable->to[0]['name'])) {
                    $recipientName = $mailable->to[0]['name'];
                }
            }

            // Queue the email
            Mail::to($recipient)->queue($mailable);

            // Log the event
            EmailEvent::logEvent(
                $recipient,
                'queued',
                $type,
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                ])
            );

            // Log the audit
            EmailAudit::log(
                $recipient,
                $subject,
                'queued',
                $auditMetadata,
                $headers,
                $content,
                $messageId,
                $recipientName
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue email', [
                'email' => $recipient,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            // Log the audit with failed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'failed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'error' => $e->getMessage(),
                ])
            );

            return false;
        }
    }

    /**
     * Check if an email is in the suppression list
     */
    protected function isEmailSuppressed(string $email): bool
    {
        return EmailSuppression::isEmailSuppressed($email);
    }

    /**
     * Send a test email and log the results
     */
    public function sendTestEmail(string $recipient, ?string $subject = null, string $template = 'emails.test', array $variables = []): array
    {
        $subject = $subject ?: 'Test Email from MixPitch';
        $messageId = Str::uuid()->toString();

        try {
            // Render the email content
            $content = null;
            try {
                $content = View::make($template, $variables)->render();
            } catch (\Exception $e) {
                Log::warning('Could not render test email template: '.$e->getMessage());
                throw new \Exception('Could not render email template: '.$e->getMessage());
            }

            // Send the email directly
            Mail::send($template, $variables, function ($message) use ($recipient, $subject, $messageId) {
                $message->to($recipient)
                    ->subject($subject)
                    ->getHeaders()->addTextHeader('X-Message-ID', $messageId);
            });

            // Log the event
            EmailEvent::logEvent(
                $recipient,
                'sent',
                'test',
                [
                    'subject' => $subject,
                    'template' => $template,
                    'messageId' => $messageId,
                ]
            );

            // Log the audit
            EmailAudit::log(
                $recipient,
                $subject,
                'sent',
                [
                    'email_type' => 'test',
                    'template' => $template,
                    'sent_at' => now()->toIso8601String(),
                ],
                null, // headers
                $content,
                $messageId
            );

            return [
                'status' => 'sent',
                'message_id' => $messageId,
                'recipient' => $recipient,
                'subject' => $subject,
                'template' => $template,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'email' => $recipient,
                'error' => $e->getMessage(),
            ]);

            // Log the audit with failed status
            EmailAudit::log(
                $recipient,
                $subject,
                'failed',
                [
                    'email_type' => 'test',
                    'template' => $template,
                    'error' => $e->getMessage(),
                ]
            );

            throw $e;
        }
    }

    // --- Client Management Specific Emails ---

    /**
     * Sends the initial project invitation email to the client.
     */
    public function sendClientInviteEmail(string $clientEmail, ?string $clientName, \App\Models\Project $project, string $signedUrl): void
    {
        try {
            $mailable = new \App\Mail\ClientProjectInvite($project, $signedUrl);

            // Create metadata including the signed URL
            $metadata = [
                'project_id' => $project->id,
                'client_portal_url' => $signedUrl, // Include URL in metadata for audit records
            ];

            $this->queue($mailable, $clientEmail, 'client_project_invite', $metadata);

            // Log the invite with the URL
            Log::info('Client project invite email queued', [
                'email' => $clientEmail,
                'project_id' => $project->id,
                'client_portal_url' => $signedUrl,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to queue client project invite email', [
                'email' => $clientEmail,
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sends the review ready notification email to the client.
     */
    public function sendClientReviewReadyEmail(string $clientEmail, ?string $clientName, \App\Models\Project $project, \App\Models\Pitch $pitch, string $signedUrl): void
    {
        try {
            Mail::to($clientEmail)->send(new \App\Mail\ClientReviewReady(
                $project,
                $pitch,
                $signedUrl,
                $clientName
            ));

            Log::info('Client review ready email sent', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'client_email' => $clientEmail,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send client review ready email', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'client_email' => $clientEmail,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send email to client when producer adds a comment
     */
    public function sendClientProducerCommentEmail(
        string $clientEmail,
        ?string $clientName,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        string $comment,
        string $signedUrl
    ): void {
        try {
            Mail::to($clientEmail)->send(new \App\Mail\ClientProducerComment(
                $project,
                $pitch,
                $comment,
                $signedUrl,
                $clientName
            ));

            Log::info('Producer comment email sent to client', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'client_email' => $clientEmail,
                'comment_length' => strlen($comment),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send producer comment email', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'client_email' => $clientEmail,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sends the project completed notification email to the client.
     *
     * @param  string|null  $feedback  Producer feedback
     * @param  int|null  $rating  Producer rating
     */
    public function sendClientProjectCompletedEmail(
        string $clientEmail,
        ?string $clientName,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        string $signedUrl,
        ?string $feedback,
        ?int $rating
    ): void {
        try {
            $mailable = new ClientProjectCompleted(
                $project,
                $pitch,
                $signedUrl,
                $clientName,
                $feedback,
                $rating
            );
            $this->queue($mailable, $clientEmail, 'client_project_completed', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
            ]);
            Log::info('Client project completed email queued', ['email' => $clientEmail, 'project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('Failed to queue client project completed email', [
                'email' => $clientEmail,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email to producer when client approves and completes the project.
     */
    public function sendProducerClientApprovedAndCompletedEmail(
        \App\Models\User $producer,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        bool $hasPayment
    ): void {
        try {
            Mail::to($producer->email)->send(new \App\Mail\ProducerClientApprovedAndCompleted(
                $producer,
                $project,
                $pitch,
                $hasPayment
            ));

            Log::info('Producer client approved and completed email sent', [
                'producer_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'has_payment' => $hasPayment,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send producer client approved and completed email', [
                'producer_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Send email to producer when their payout is scheduled.
     */
    public function sendProducerPayoutScheduledEmail(
        \App\Models\User $producer,
        float $netAmount,
        \App\Models\PayoutSchedule $payoutSchedule
    ): void {
        try {
            Mail::to($producer->email)->send(new \App\Mail\ProducerPayoutScheduled(
                $producer,
                $netAmount,
                $payoutSchedule
            ));

            Log::info('Producer payout scheduled email sent', [
                'producer_id' => $producer->id,
                'payout_schedule_id' => $payoutSchedule->id,
                'net_amount' => $netAmount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send producer payout scheduled email', [
                'producer_id' => $producer->id,
                'payout_schedule_id' => $payoutSchedule->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    // --- Client Management Workflow Emails ---

    /**
     * Send revision request confirmation email to client.
     */
    public function sendClientRevisionRequestConfirmation(
        string $clientEmail,
        ?string $clientName,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        string $feedback,
        string $signedUrl
    ): void {
        // Check if email notifications are enabled
        if (! config('business.email_notifications.client_management.enabled')) {
            Log::info('Client management emails disabled, skipping revision confirmation');

            return;
        }

        if (! config('business.email_notifications.client_management.revision_confirmation')) {
            Log::info('Revision confirmation emails disabled, skipping');

            return;
        }

        // Check per-project client email preference
        if (! $project->shouldSendClientEmail('revision_confirmation')) {
            Log::info('Revision confirmation email disabled by client preference', [
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            $mailable = new \App\Mail\Client\RevisionRequestConfirmation(
                $project,
                $pitch,
                $feedback,
                $signedUrl,
                $clientName
            );

            $metadata = [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'client_email' => $clientEmail,
                'client_name' => $clientName,
            ];

            $this->queue($mailable, $clientEmail, 'client_revision_confirmation', $metadata);

            Log::info('Client revision confirmation email queued', $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to send client revision confirmation email', [
                'error' => $e->getMessage(),
                'client_email' => $clientEmail,
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Send notification to client when producer resubmits updated work.
     */
    public function sendClientProducerResubmitted(
        string $clientEmail,
        ?string $clientName,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        string $signedUrl,
        int $fileCount = 0,
        ?string $producerNote = null
    ): void {
        if (! config('business.email_notifications.client_management.enabled')) {
            Log::info('Client management emails disabled, skipping producer resubmission');

            return;
        }

        if (! config('business.email_notifications.client_management.producer_resubmitted')) {
            Log::info('Producer resubmission emails disabled, skipping');

            return;
        }

        // Check per-project client email preference
        if (! $project->shouldSendClientEmail('producer_resubmitted')) {
            Log::info('Producer resubmission email disabled by client preference', [
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            $mailable = new \App\Mail\Client\ProducerResubmitted(
                $project,
                $pitch,
                $signedUrl,
                $clientName,
                $fileCount,
                $producerNote
            );

            $metadata = [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'client_email' => $clientEmail,
                'client_name' => $clientName,
                'file_count' => $fileCount,
            ];

            $this->queue($mailable, $clientEmail, 'client_producer_resubmitted', $metadata);

            Log::info('Client producer resubmitted email queued', $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to send client producer resubmitted email', [
                'error' => $e->getMessage(),
                'client_email' => $clientEmail,
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Send notification to producer when client requests revisions.
     */
    public function sendProducerClientRevisionsRequested(
        \App\Models\User $producer,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        string $feedback
    ): void {
        if (! config('business.email_notifications.client_management.enabled')) {
            Log::info('Client management emails disabled, skipping producer revisions requested');

            return;
        }

        if (! config('business.email_notifications.client_management.producer_revisions_requested')) {
            Log::info('Producer revisions requested emails disabled, skipping');

            return;
        }

        // Check per-project producer email preference
        if (! $project->shouldSendProducerEmail('producer_revisions_requested')) {
            Log::info('Producer revisions requested email disabled by producer preference', [
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            $mailable = new \App\Mail\Producer\ClientRevisionsRequested(
                $producer,
                $project,
                $pitch,
                $feedback
            );

            $metadata = [
                'producer_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'feedback_length' => strlen($feedback),
            ];

            $this->queue($mailable, $producer->email, 'producer_client_revisions_requested', $metadata);

            Log::info('Producer client revisions requested email queued', $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to send producer client revisions requested email', [
                'error' => $e->getMessage(),
                'producer_id' => $producer->id,
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Send notification to producer when client adds a comment.
     */
    public function sendProducerClientCommented(
        \App\Models\User $producer,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        string $comment
    ): void {
        if (! config('business.email_notifications.client_management.enabled')) {
            Log::info('Client management emails disabled, skipping producer client commented');

            return;
        }

        if (! config('business.email_notifications.client_management.producer_client_commented')) {
            Log::info('Producer client commented emails disabled, skipping');

            return;
        }

        // Check per-project producer email preference
        if (! $project->shouldSendProducerEmail('producer_client_commented')) {
            Log::info('Producer client commented email disabled by producer preference', [
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            $mailable = new \App\Mail\Producer\ClientCommented(
                $producer,
                $project,
                $pitch,
                $comment
            );

            $metadata = [
                'producer_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'comment_length' => strlen($comment),
            ];

            $this->queue($mailable, $producer->email, 'producer_client_commented', $metadata);

            Log::info('Producer client commented email queued', $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to send producer client commented email', [
                'error' => $e->getMessage(),
                'producer_id' => $producer->id,
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Send payment receipt email to client.
     */
    public function sendClientPaymentReceipt(
        \App\Models\Project $project,
        ?string $clientName,
        float $amount,
        string $currency,
        string $transactionId,
        string $invoiceUrl,
        string $portalUrl
    ): void {
        if (! config('business.email_notifications.client_management.enabled')) {
            Log::info('Client management emails disabled, skipping client payment receipt');

            return;
        }

        if (! config('business.email_notifications.client_management.payment_receipt')) {
            Log::info('Client payment receipt emails disabled, skipping');

            return;
        }

        // Check per-project client email preference
        if (! $project->shouldSendClientEmail('payment_receipt')) {
            Log::info('Client payment receipt email disabled by client preference', [
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            $mailable = new \App\Mail\Payment\ClientPaymentReceipt(
                $project,
                $clientName,
                $amount,
                $currency,
                $transactionId,
                $invoiceUrl,
                $portalUrl
            );

            $metadata = [
                'project_id' => $project->id,
                'client_email' => $project->client_email,
                'client_name' => $clientName,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_id' => $transactionId,
            ];

            $this->queue($mailable, $project->client_email, 'client_payment_receipt', $metadata);

            Log::info('Client payment receipt email queued', $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to send client payment receipt email', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);
        }
    }

    /**
     * Send payment received notification to producer.
     */
    public function sendProducerPaymentReceived(
        \App\Models\User $producer,
        \App\Models\Project $project,
        \App\Models\Pitch $pitch,
        float $grossAmount,
        float $platformFee,
        float $netAmount,
        string $currency,
        \Carbon\Carbon $payoutDate
    ): void {
        if (! config('business.email_notifications.client_management.enabled')) {
            Log::info('Client management emails disabled, skipping producer payment received');

            return;
        }

        if (! config('business.email_notifications.client_management.producer_payment_received')) {
            Log::info('Producer payment received emails disabled, skipping');

            return;
        }

        // Check per-project producer email preference
        if (! $project->shouldSendProducerEmail('payment_received')) {
            Log::info('Producer payment received email disabled by producer preference', [
                'project_id' => $project->id,
            ]);

            return;
        }

        try {
            $mailable = new \App\Mail\Producer\PaymentReceived(
                $producer,
                $project,
                $pitch,
                $grossAmount,
                $platformFee,
                $netAmount,
                $currency,
                $payoutDate
            );

            $metadata = [
                'producer_id' => $producer->id,
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'gross_amount' => $grossAmount,
                'platform_fee' => $platformFee,
                'net_amount' => $netAmount,
                'currency' => $currency,
                'payout_date' => $payoutDate->toDateString(),
            ];

            $this->queue($mailable, $producer->email, 'producer_payment_received', $metadata);

            Log::info('Producer payment received email queued', $metadata);

        } catch (\Exception $e) {
            Log::error('Failed to send producer payment received email', [
                'error' => $e->getMessage(),
                'producer_id' => $producer->id,
                'project_id' => $project->id,
            ]);
        }
    }

    // --- Generic/Other Emails ---
}
