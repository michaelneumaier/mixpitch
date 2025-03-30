<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use App\Models\User;
use App\Models\Pitch;
use App\Services\PitchWorkflowService;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends CashierWebhookController
{
    /**
     * Handle payment succeeded event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleChargeSucceeded($payload)
    {
        // We likely care more about invoice.payment_succeeded for pitch payments
        Log::info('Webhook received: charge.succeeded', ['payload_id' => $payload['id'] ?? 'N/A']);
        return $this->successMethod();
    }

    /**
     * Handle payment failed event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleChargeFailed($payload)
    {
         // We likely care more about invoice.payment_failed for pitch payments
        Log::info('Webhook received: charge.failed', ['payload_id' => $payload['id'] ?? 'N/A']);
        return $this->successMethod();
    }

    /**
     * Handle invoice created event.
     * (Keep existing logic if needed for general user invoice sync)
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoiceCreated($payload)
    {
        Log::info('Webhook received: invoice.created', ['invoice_id' => $payload['data']['object']['id'] ?? 'N/A']);
        try {
            // Existing user sync logic can remain if necessary
            $customer = $payload['data']['object']['customer'] ?? null;
            if ($customer) {
                $user = User::where('stripe_id', $customer)->first();
                if ($user) {
                     Log::info('Syncing invoices for user via invoice.created', ['user_id' => $user->id, 'stripe_customer' => $customer]);
                     $user->createOrGetStripeCustomer(); // Syncs invoices
                } else {
                     Log::warning('User not found for stripe customer via invoice.created', ['stripe_customer' => $customer]);
                 }
            } else {
                 Log::warning('No customer ID found in invoice.created payload', ['invoice_id' => $payload['data']['object']['id'] ?? 'N/A']);
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice.created webhook: ' . $e->getMessage(), [
                'invoice_id' => $payload['data']['object']['id'] ?? 'N/A',
                'exception' => $e
            ]);
        }

        // Return success regardless of user sync outcome to acknowledge webhook
        return $this->successMethod();
    }

    /**
     * Handle invoice payment succeeded event.
     * This is the primary webhook for confirming pitch payments.
     *
     * @param  array  $payload
     * @param  PitchWorkflowService $pitchWorkflowService Injected service
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload, PitchWorkflowService $pitchWorkflowService)
    {
        $invoicePayload = $payload['data']['object'] ?? null;
        $invoiceId = $invoicePayload['id'] ?? null;
        Log::info('Webhook received: invoice.payment_succeeded', ['invoice_id' => $invoiceId]);

        if (!$invoicePayload || !$invoiceId) {
             Log::error('Invalid invoice.payment_succeeded payload received.', ['payload_id' => $payload['id'] ?? 'N/A']);
             return $this->missingInvoiceId(); // Respond appropriately
        }

        try {
            // --- Pitch Payment Logic ---
            $pitchId = $invoicePayload['metadata']['pitch_id'] ?? null;

            if ($pitchId) {
                 Log::info('Processing invoice.payment_succeeded for pitch.', ['invoice_id' => $invoiceId, 'pitch_id' => $pitchId]);
                $pitch = Pitch::find($pitchId);

                if ($pitch) {
                    // Call the service to mark the pitch as paid
                    // Pass invoice ID and potentially charge/payment_intent ID if needed/available
                    $chargeId = $invoicePayload['charge'] ?? null; // Or 'payment_intent'
                    $pitchWorkflowService->markPitchAsPaid($pitch, $invoiceId, $chargeId);
                    Log::info('Successfully marked pitch as paid via webhook.', ['invoice_id' => $invoiceId, 'pitch_id' => $pitchId]);
                } else {
                    Log::warning('Pitch not found for invoice.payment_succeeded webhook.', [
                        'invoice_id' => $invoiceId,
                        'pitch_id' => $pitchId
                    ]);
                    // Decide if this is an error state - perhaps the pitch was deleted?
                }
            } else {
                 Log::info('No pitch_id metadata found in invoice.payment_succeeded, skipping pitch processing.', ['invoice_id' => $invoiceId]);
                 // This might be a regular subscription invoice, etc.
            }

             // --- Optional: Existing User Invoice Sync Logic ---
             $this->syncUserInvoicesFromPayload($payload);


        } catch (\Exception $e) {
            Log::error('Error handling invoice.payment_succeeded webhook: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'payload_id' => $payload['id'] ?? 'N/A',
                'exception' => $e // Consider limiting exception detail in production logs
            ]);
             // Don't throw error back to Stripe, return success to prevent retries
             // Logged error needs monitoring.
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed event.
     *
     * @param  array  $payload
     * @param  PitchWorkflowService $pitchWorkflowService Injected service
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentFailed($payload, PitchWorkflowService $pitchWorkflowService)
    {
         $invoicePayload = $payload['data']['object'] ?? null;
         $invoiceId = $invoicePayload['id'] ?? null;
         Log::info('Webhook received: invoice.payment_failed', ['invoice_id' => $invoiceId]);

        if (!$invoicePayload || !$invoiceId) {
             Log::error('Invalid invoice.payment_failed payload received.', ['payload_id' => $payload['id'] ?? 'N/A']);
             return $this->missingInvoiceId();
        }

        try {
             // --- Pitch Payment Logic ---
            $pitchId = $invoicePayload['metadata']['pitch_id'] ?? null;

            if ($pitchId) {
                 Log::info('Processing invoice.payment_failed for pitch.', ['invoice_id' => $invoiceId, 'pitch_id' => $pitchId]);
                 $pitch = Pitch::find($pitchId);

                if ($pitch) {
                     // Extract failure reason if available
                    $failureReason = $invoicePayload['last_payment_error']['message'] ?? // Attempt 1
                                    $invoicePayload['attempt_failure_reason'] ?? // Attempt 2 (older APIs?)
                                    'Unknown failure reason from webhook.';

                    // Call the service to mark the pitch payment as failed
                    $pitchWorkflowService->markPitchPaymentFailed($pitch, $invoiceId, $failureReason);
                     Log::info('Successfully marked pitch payment as failed via webhook.', ['invoice_id' => $invoiceId, 'pitch_id' => $pitchId]);
                } else {
                     Log::warning('Pitch not found for invoice.payment_failed webhook.', [
                        'invoice_id' => $invoiceId,
                        'pitch_id' => $pitchId
                    ]);
                }
            } else {
                 Log::info('No pitch_id metadata found in invoice.payment_failed, skipping pitch processing.', ['invoice_id' => $invoiceId]);
            }

             // --- Optional: Existing User Invoice Sync Logic ---
             $this->syncUserInvoicesFromPayload($payload);

        } catch (\Exception $e) {
            Log::error('Error handling invoice.payment_failed webhook: ' . $e->getMessage(), [
                 'invoice_id' => $invoiceId,
                 'payload_id' => $payload['id'] ?? 'N/A',
                 'exception' => $e
            ]);
             // Return success to prevent retries
        }

        return $this->successMethod();
    }

    // --- Existing Subscription/Customer Handlers (keep as is unless they need changes) ---

    public function handleCustomerSubscriptionCreated($payload) { Log::info('Webhook received: customer.subscription.created', ['payload_id' => $payload['id'] ?? 'N/A']); return $this->successMethod(); }
    public function handleCustomerSubscriptionUpdated($payload) { Log::info('Webhook received: customer.subscription.updated', ['payload_id' => $payload['id'] ?? 'N/A']); return $this->successMethod(); }
    public function handleCustomerSubscriptionDeleted($payload) { Log::info('Webhook received: customer.subscription.deleted', ['payload_id' => $payload['id'] ?? 'N/A']); return $this->successMethod(); }
    public function handleCustomerUpdated($payload) { Log::info('Webhook received: customer.updated', ['payload_id' => $payload['id'] ?? 'N/A']); return $this->successMethod(); }


    // --- Helper Methods ---

    /**
     * Helper to sync user invoices based on customer ID in payload.
     * Extracted from original handlers.
     */
    protected function syncUserInvoicesFromPayload(array $payload): void
    {
         try {
            $customer = $payload['data']['object']['customer'] ?? null;
            if ($customer) {
                $user = User::where('stripe_id', $customer)->first();
                if ($user) {
                     Log::info('Syncing invoices for user via webhook.', ['user_id' => $user->id, 'stripe_customer' => $customer, 'event_type' => $payload['type'] ?? 'N/A']);
                     // Ensure customer exists locally before syncing invoices
                     $user->createOrGetStripeCustomer();
                     // Optionally force download invoices if needed: $user->downloadInvoices();
                } else {
                     Log::warning('User not found for stripe customer via webhook sync.', ['stripe_customer' => $customer, 'event_type' => $payload['type'] ?? 'N/A']);
                 }
            } else {
                 Log::warning('No customer ID found in webhook payload for user sync.', ['payload_id' => $payload['id'] ?? 'N/A', 'event_type' => $payload['type'] ?? 'N/A']);
            }
        } catch (\Exception $e) {
            // Log error but don't let it prevent webhook success response
            Log::error('Error during user invoice sync from webhook: ' . $e->getMessage(), [
                'customer_id' => $customer ?? 'N/A',
                'payload_id' => $payload['id'] ?? 'N/A',
                'event_type' => $payload['type'] ?? 'N/A',
                'exception' => $e
            ]);
        }
    }

    /**
     * Return a response for missing invoice ID.
     */
     protected function missingInvoiceId(): Response
     {
         return new Response('Webhook Handled: Invoice ID missing in payload', 400); // Use 400 Bad Request
     }

     /**
      * Handle calls to missing methods.
      *
      * @param  array  $parameters
      * @return \Symfony\Component\HttpFoundation\Response
      */
     public function missingMethod($parameters = [])
     {
         Log::warning('Webhook type not handled.', ['parameters' => $parameters]);
         return new Response('Webhook type not handled', 400);
     }
}
