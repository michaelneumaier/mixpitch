<?php

namespace App\Services;

use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send an email if the recipient is not in the suppression list
     *
     * @param Mailable $mailable
     * @param string|array $recipient
     * @param string|null $type
     * @param array|null $metadata
     * @return bool
     */
    public function send(Mailable $mailable, $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Handle array of recipients
        if (is_array($recipient)) {
            $results = [];
            foreach ($recipient as $email) {
                $results[] = $this->sendToSingleRecipient($mailable, $email, $type, $metadata);
            }
            return !in_array(false, $results, true);
        }
        
        // Handle single recipient
        return $this->sendToSingleRecipient($mailable, $recipient, $type, $metadata);
    }
    
    /**
     * Send email to a single recipient
     *
     * @param Mailable $mailable
     * @param string $recipient
     * @param string|null $type
     * @param array|null $metadata
     * @return bool
     */
    protected function sendToSingleRecipient(Mailable $mailable, string $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Check if email is suppressed
        if ($this->isEmailSuppressed($recipient)) {
            Log::info('Email sending skipped - recipient is in suppression list', [
                'email' => $recipient,
                'type' => $type
            ]);
            return false;
        }
        
        try {
            // Send the email
            Mail::to($recipient)->send($mailable);
            
            // Log the event
            EmailEvent::logEvent(
                $recipient,
                'sent',
                $type,
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable)
                ])
            );
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'email' => $recipient,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Queue an email if the recipient is not in the suppression list
     *
     * @param Mailable $mailable
     * @param string|array $recipient
     * @param string|null $type
     * @param array|null $metadata
     * @return bool
     */
    public function queue(Mailable $mailable, $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Handle array of recipients
        if (is_array($recipient)) {
            $results = [];
            foreach ($recipient as $email) {
                $results[] = $this->queueForSingleRecipient($mailable, $email, $type, $metadata);
            }
            return !in_array(false, $results, true);
        }
        
        // Handle single recipient
        return $this->queueForSingleRecipient($mailable, $recipient, $type, $metadata);
    }
    
    /**
     * Queue email for a single recipient
     *
     * @param Mailable $mailable
     * @param string $recipient
     * @param string|null $type
     * @param array|null $metadata
     * @return bool
     */
    protected function queueForSingleRecipient(Mailable $mailable, string $recipient, ?string $type = null, ?array $metadata = null): bool
    {
        // Check if email is suppressed
        if ($this->isEmailSuppressed($recipient)) {
            Log::info('Email queuing skipped - recipient is in suppression list', [
                'email' => $recipient,
                'type' => $type
            ]);
            return false;
        }
        
        try {
            // Queue the email
            Mail::to($recipient)->queue($mailable);
            
            // Log the event
            EmailEvent::logEvent(
                $recipient,
                'queued',
                $type,
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable)
                ])
            );
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue email', [
                'email' => $recipient,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Check if an email is in the suppression list
     *
     * @param string $email
     * @return bool
     */
    protected function isEmailSuppressed(string $email): bool
    {
        return EmailSuppression::isEmailSuppressed($email);
    }
} 