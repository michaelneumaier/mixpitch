<?php

namespace App\Http\Controllers;

use App\Models\EmailAudit;
use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use App\Models\User;
use Aws\Exception\InvalidSnsMessageException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SesWebhookController extends Controller
{
    /**
     * Handle incoming webhook requests from AWS SES/SNS.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        Log::info('SES Webhook Received', ['headers' => $request->headers->all(), 'body' => $request->getContent()]);

        // First, verify the request signature (unless in local/testing env)
        if (! app()->environment('local', 'testing') && ! $this->verifyRequest($request)) {
            Log::warning('SES Webhook: Invalid SNS signature.', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Invalid SNS signature'], 403);
        }

        // Try to decode JSON content
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            Log::error('SES Webhook: Failed to decode JSON payload.', ['error' => $e->getMessage(), 'body' => $request->getContent()]);

            return response()->json(['error' => 'Invalid JSON payload'], 400);
        }

        $messageType = $request->header('x-amz-sns-message-type');

        // Handle Subscription Confirmation
        if ($messageType === 'SubscriptionConfirmation') {
            Log::info('SES Webhook: Handling Subscription Confirmation.');
            // Simply visit the SubscribeURL to confirm
            // Consider adding error handling for the HTTP request
            try {
                $subscribeUrl = $payload['SubscribeURL'] ?? null;
                if ($subscribeUrl) {
                    // In a production scenario, you might use Guzzle or Laravel's HTTP client
                    // For simplicity here, we assume file_get_contents works, but it's not ideal
                    // file_get_contents($subscribeUrl); // Avoid direct file_get_contents for external URLs

                    // Use Laravel's HTTP Client for better error handling and security
                    $response = \Illuminate\Support\Facades\Http::get($subscribeUrl);
                    if ($response->successful()) {
                        Log::info('SES Webhook: Subscription confirmed successfully via SubscribeURL.', ['url' => $subscribeUrl]);
                    } else {
                        Log::error('SES Webhook: Failed to confirm subscription via SubscribeURL.', ['url' => $subscribeUrl, 'status' => $response->status()]);
                        // Potentially retry or alert admin
                    }
                } else {
                    Log::warning('SES Webhook: SubscribeURL not found in SubscriptionConfirmation payload.');
                }
            } catch (\Exception $e) {
                Log::error('SES Webhook: Error during subscription confirmation request.', ['error' => $e->getMessage()]);
            }

            return response()->json(['message' => 'Subscription Confirmation processed.']);
        }

        // Handle Notification messages
        if ($messageType === 'Notification') {
            Log::info('SES Webhook: Handling Notification.');
            // Decode the actual message content
            try {
                $messageData = json_decode($payload['Message'], true, 512, JSON_THROW_ON_ERROR);
                $notificationType = $messageData['notificationType'] ?? null;

                if ($notificationType === 'Bounce') {
                    return $this->handleBounce($messageData);
                } elseif ($notificationType === 'Complaint') {
                    return $this->handleComplaint($messageData);
                } else {
                    Log::info('SES Webhook: Received unhandled notification type.', ['type' => $notificationType]);

                    return response()->json(['message' => 'Notification type not handled']);
                }
            } catch (\JsonException $e) {
                Log::error('SES Webhook: Failed to decode inner Message JSON.', ['error' => $e->getMessage(), 'message' => $payload['Message'] ?? null]);

                return response()->json(['error' => 'Invalid inner Message JSON payload'], 400);
            }
        }

        Log::info('SES Webhook: Received unhandled message type.', ['type' => $messageType]);

        return response()->json(['message' => 'Message type not handled']);
    }

    /**
     * Verify if the request is from AWS SNS.
     *
     * @return bool
     */
    protected function verifyRequest(Request $request)
    {
        // Skip verification in local/testing environments for convenience
        if (app()->environment('local', 'testing')) {
            Log::debug('SES Webhook: Skipping SNS signature verification in local/testing environment.');

            return true;
        }

        try {
            // Parse the message body
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
            $message = Message::fromArray($payload);

            // Validate the message
            $validator = new MessageValidator;
            $validator->validate($message);

            Log::info('SES Webhook: SNS message validated successfully.');

            return true;

        } catch (\JsonException $e) {
            Log::error('SES Webhook Verify: Failed to decode JSON payload for verification.', ['error' => $e->getMessage(), 'body' => $request->getContent()]);

            return false;
        } catch (InvalidSnsMessageException $e) {
            // Log the failure
            Log::error('SES Webhook Verify: SNS Message Validation Failed.', [
                'error' => $e->getMessage(),
                'payload' => $payload ?? 'Payload not parsed', // Log payload if available
            ]);

            return false;
        } catch (\Exception $e) {
            // Catch any other unexpected exceptions during validation
            Log::error('SES Webhook Verify: Unexpected error during SNS message validation.', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Handle bounce notifications
     *
     * @param  array  $bounceData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleBounce($bounceData)
    {
        foreach ($bounceData['bouncedRecipients'] as $recipient) {
            $email = $recipient['emailAddress'];

            // Add to suppression list
            EmailSuppression::updateOrCreate(
                ['email' => $email],
                [
                    'reason' => 'bounce',
                    'type' => $bounceData['bounceType'] ?? 'unknown',
                    'metadata' => $bounceData,
                ]
            );

            // Log the bounce event
            EmailEvent::logEvent(
                $email,
                'bounced',
                null,
                [
                    'bounce_type' => $bounceData['bounceType'] ?? 'unknown',
                    'bounce_subtype' => $bounceData['bounceSubType'] ?? 'unknown',
                ]
            );

            // Log comprehensive audit information
            EmailAudit::log(
                $email,
                $bounceData['bouncedRecipients'][0]['diagnosticCode'] ?? 'Bounce Notification',
                'bounced',
                [
                    'bounce_type' => $bounceData['bounceType'] ?? 'unknown',
                    'bounce_subtype' => $bounceData['bounceSubType'] ?? 'unknown',
                    'diagnostic_code' => $recipient['diagnosticCode'] ?? null,
                    'action' => $recipient['action'] ?? null,
                    'status' => $recipient['status'] ?? null,
                    'message_id' => $bounceData['mail']['messageId'] ?? null,
                    'timestamp' => $bounceData['timestamp'] ?? now()->toIso8601String(),
                    'raw_data' => $bounceData,
                ]
            );

            // Update user record if exists
            $user = User::where('email', $email)->first();
            if ($user) {
                // Set a flag on the user model to indicate email is invalid
                // Note: You may need to add this column to your users table
                if (Schema::hasColumn('users', 'email_valid')) {
                    $user->email_valid = false;
                    $user->save();
                }

                Log::info('User email marked as invalid due to bounce', ['email' => $email]);
            }
        }

        return response()->json(['message' => 'Bounce processed']);
    }

    /**
     * Handle complaint notifications
     *
     * @param  array  $complaintData
     * @return \Illuminate\Http\JsonResponse
     */
    protected function handleComplaint($complaintData)
    {
        foreach ($complaintData['complainedRecipients'] as $recipient) {
            $email = $recipient['emailAddress'];

            // Add to suppression list
            EmailSuppression::updateOrCreate(
                ['email' => $email],
                [
                    'reason' => 'complaint',
                    'type' => 'complaint',
                    'metadata' => $complaintData,
                ]
            );

            // Log the complaint event
            EmailEvent::logEvent(
                $email,
                'complained',
                null,
                ['complaint_feedback_type' => $complaintData['complaintFeedbackType'] ?? 'unknown']
            );

            // Log comprehensive audit information
            EmailAudit::log(
                $email,
                'Complaint Notification',
                'complained',
                [
                    'complaint_feedback_type' => $complaintData['complaintFeedbackType'] ?? 'unknown',
                    'arrived_date' => $complaintData['arrivalDate'] ?? null,
                    'message_id' => $complaintData['mail']['messageId'] ?? null,
                    'timestamp' => $complaintData['timestamp'] ?? now()->toIso8601String(),
                    'raw_data' => $complaintData,
                ]
            );

            // Update user preferences if exists
            $user = User::where('email', $email)->first();
            if ($user) {
                // You could set user preferences to opt-out of marketing emails
                // This depends on your specific user preferences implementation

                Log::info('User marked as complained', ['email' => $email]);
            }
        }

        return response()->json(['message' => 'Complaint processed']);
    }
}
