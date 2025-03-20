<?php

namespace App\Http\Controllers;

use App\Models\EmailEvent;
use App\Models\EmailSuppression;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SesWebhookController extends Controller
{
    /**
     * Handle the SES webhook notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Verify if the request is from AWS
        if (!$this->verifyRequest($request)) {
            Log::warning('Invalid SES notification request');
            return response()->json(['message' => 'Invalid request'], 400);
        }
        
        $payload = $request->getContent();
        $message = json_decode($payload, true);
        
        // Handle SNS subscription confirmation
        if (isset($message['Type']) && $message['Type'] === 'SubscriptionConfirmation') {
            // Call the SubscribeURL to confirm subscription
            file_get_contents($message['SubscribeURL']);
            Log::info('SNS subscription confirmed');
            return response()->json(['message' => 'Subscription confirmed']);
        }
        
        // Handle bounce or complaint notification
        if (isset($message['Message'])) {
            $messageData = json_decode($message['Message'], true);
            
            if (isset($messageData['notificationType'])) {
                Log::info('Processing SES notification', ['type' => $messageData['notificationType']]);
                
                switch ($messageData['notificationType']) {
                    case 'Bounce':
                        return $this->handleBounce($messageData['bounce']);
                    case 'Complaint':
                        return $this->handleComplaint($messageData['complaint']);
                }
            }
        }
        
        return response()->json(['message' => 'Notification processed']);
    }
    
    /**
     * Verify if the request is from AWS
     *
     * @param Request $request
     * @return bool
     */
    protected function verifyRequest(Request $request)
    {
        // For production, implement validation using AWS SNS signature verification
        // For now, we're accepting all requests for simplicity in development
        return true;
    }
    
    /**
     * Handle bounce notifications
     *
     * @param array $bounceData
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
                    'metadata' => $bounceData
                ]
            );
            
            // Log the bounce event
            EmailEvent::logEvent(
                $email, 
                'bounced', 
                null, 
                [
                    'bounce_type' => $bounceData['bounceType'] ?? 'unknown',
                    'bounce_subtype' => $bounceData['bounceSubType'] ?? 'unknown'
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
     * @param array $complaintData
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
                    'metadata' => $complaintData
                ]
            );
            
            // Log the complaint event
            EmailEvent::logEvent(
                $email,
                'complained',
                null,
                ['complaint_feedback_type' => $complaintData['complaintFeedbackType'] ?? 'unknown']
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
