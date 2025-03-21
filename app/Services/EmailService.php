<?php

namespace App\Services;

use App\Models\EmailAudit;
use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use App\Models\EmailTest;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

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
            
            // Log the audit with suppressed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'suppressed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'reason' => 'Email is in suppression list'
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
                'sent_at' => now()->toIso8601String()
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
                    Log::warning('Could not capture email content: ' . $e->getMessage());
                }
            }
            
            // Try to extract the recipient name if available
            if (method_exists($mailable, 'build') && isset($mailable->to)) {
                if (is_array($mailable->to) && !empty($mailable->to[0]) && isset($mailable->to[0]['name'])) {
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
                    'mailable_class' => get_class($mailable)
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
                'error' => $e->getMessage()
            ]);
            
            // Log the audit with failed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'failed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'error' => $e->getMessage()
                ])
            );
            
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
            
            // Log the audit with suppressed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'suppressed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'reason' => 'Email is in suppression list'
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
                'queued_at' => now()->toIso8601String()
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
                    Log::warning('Could not capture email content: ' . $e->getMessage());
                }
            }
            
            // Try to extract the recipient name if available
            if (method_exists($mailable, 'build') && isset($mailable->to)) {
                if (is_array($mailable->to) && !empty($mailable->to[0]) && isset($mailable->to[0]['name'])) {
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
                    'mailable_class' => get_class($mailable)
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
                'error' => $e->getMessage()
            ]);
            
            // Log the audit with failed status
            EmailAudit::log(
                $recipient,
                $mailable->subject ?? '[No Subject]',
                'failed',
                array_merge($metadata ?? [], [
                    'mailable_class' => get_class($mailable),
                    'email_type' => $type,
                    'error' => $e->getMessage()
                ])
            );
            
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

    /**
     * Send a test email and log the results
     *
     * @param string $recipient
     * @param string|null $subject
     * @param string $template
     * @param array $variables
     * @return array
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
                Log::warning('Could not render test email template: ' . $e->getMessage());
                throw new \Exception('Could not render email template: ' . $e->getMessage());
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
                    'messageId' => $messageId
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
                    'sent_at' => now()->toIso8601String()
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
                'template' => $template
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'email' => $recipient,
                'error' => $e->getMessage()
            ]);
            
            // Log the audit with failed status
            EmailAudit::log(
                $recipient,
                $subject,
                'failed',
                [
                    'email_type' => 'test',
                    'template' => $template,
                    'error' => $e->getMessage()
                ]
            );
            
            throw $e;
        }
    }
} 