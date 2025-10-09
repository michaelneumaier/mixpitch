<?php

namespace App\Http\Controllers\Billing;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderEvent;
use App\Models\Pitch;
use App\Models\User;
use App\Services\InvoiceService;
use App\Services\NotificationService;
use App\Services\PitchWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
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
     *
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
            Log::error('Error handling invoice.created webhook: '.$e->getMessage(), [
                'invoice_id' => $payload['data']['object']['id'] ?? 'N/A',
                'exception' => $e,
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        $invoicePayload = $payload['data']['object'] ?? null;
        $invoiceId = $invoicePayload['id'] ?? null;
        Log::info('Webhook received: invoice.payment_succeeded', ['invoice_id' => $invoiceId]);

        if (! $invoicePayload || ! $invoiceId) {
            Log::error('Invalid invoice.payment_succeeded payload received.', ['payload_id' => $payload['id'] ?? 'N/A']);

            return $this->missingInvoiceId(); // Respond appropriately
        }

        try {
            // Resolve service from container
            $pitchWorkflowService = app(PitchWorkflowService::class);

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
                        'pitch_id' => $pitchId,
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
            Log::error('Error handling invoice.payment_succeeded webhook: '.$e->getMessage(), [
                'invoice_id' => $invoiceId,
                'payload_id' => $payload['id'] ?? 'N/A',
                'exception' => $e, // Consider limiting exception detail in production logs
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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentFailed($payload)
    {
        $invoicePayload = $payload['data']['object'] ?? null;
        $invoiceId = $invoicePayload['id'] ?? null;
        Log::info('Webhook received: invoice.payment_failed', ['invoice_id' => $invoiceId]);

        if (! $invoicePayload || ! $invoiceId) {
            Log::error('Invalid invoice.payment_failed payload received.', ['payload_id' => $payload['id'] ?? 'N/A']);

            return $this->missingInvoiceId();
        }

        try {
            // Resolve service from container
            $pitchWorkflowService = app(PitchWorkflowService::class);

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
                        'pitch_id' => $pitchId,
                    ]);
                }
            } else {
                Log::info('No pitch_id metadata found in invoice.payment_failed, skipping pitch processing.', ['invoice_id' => $invoiceId]);
            }

            // --- Optional: Existing User Invoice Sync Logic ---
            $this->syncUserInvoicesFromPayload($payload);

        } catch (\Exception $e) {
            Log::error('Error handling invoice.payment_failed webhook: '.$e->getMessage(), [
                'invoice_id' => $invoiceId,
                'payload_id' => $payload['id'] ?? 'N/A',
                'exception' => $e,
            ]);
            // Return success to prevent retries
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription created event.
     */
    public function handleCustomerSubscriptionCreated($payload)
    {
        Log::info('Webhook received: customer.subscription.created', ['payload_id' => $payload['id'] ?? 'N/A']);

        try {
            $subscription = $payload['data']['object'] ?? null;
            $customerId = $subscription['customer'] ?? null;
            $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

            if ($customerId && $priceId) {
                $user = User::where('stripe_id', $customerId)->first();
                if ($user) {
                    // Create the subscription record if it doesn't exist
                    $existingSubscription = $user->subscriptions()
                        ->where('stripe_id', $subscription['id'])
                        ->first();

                    if (! $existingSubscription) {
                        $user->subscriptions()->create([
                            'name' => 'default',
                            'stripe_id' => $subscription['id'],
                            'stripe_status' => $subscription['status'],
                            'stripe_price' => $priceId,
                            'quantity' => $subscription['items']['data'][0]['quantity'] ?? 1,
                            'trial_ends_at' => $subscription['trial_end'] ? \Carbon\Carbon::createFromTimestamp($subscription['trial_end']) : null,
                            'ends_at' => null,
                        ]);

                        Log::info('Created subscription record from webhook', [
                            'user_id' => $user->id,
                            'subscription_id' => $subscription['id'],
                            'price_id' => $priceId,
                        ]);
                    }

                    $this->updateUserSubscriptionStatus($user, 'active', $priceId);

                    // Send upgrade notification
                    $priceMapping = [
                        config('subscription.stripe_prices.pro_artist_monthly') => ['plan' => 'pro', 'tier' => 'artist'],
                        config('subscription.stripe_prices.pro_engineer_monthly') => ['plan' => 'pro', 'tier' => 'engineer'],
                        config('subscription.stripe_prices.pro_artist_yearly') => ['plan' => 'pro', 'tier' => 'artist'],
                        config('subscription.stripe_prices.pro_engineer_yearly') => ['plan' => 'pro', 'tier' => 'engineer'],
                    ];

                    if (isset($priceMapping[$priceId])) {
                        $mapping = $priceMapping[$priceId];
                        $user->notify(new \App\Notifications\SubscriptionUpgraded($mapping['plan'], $mapping['tier']));
                    }

                    Log::info('Updated user subscription from webhook', [
                        'user_id' => $user->id,
                        'price_id' => $priceId,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling customer.subscription.created: '.$e->getMessage());
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription updated event.
     */
    public function handleCustomerSubscriptionUpdated($payload)
    {
        Log::info('Webhook received: customer.subscription.updated', ['payload_id' => $payload['id'] ?? 'N/A']);

        try {
            $subscription = $payload['data']['object'] ?? null;
            $customerId = $subscription['customer'] ?? null;
            $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;
            $status = $subscription['status'] ?? null;

            if ($customerId && $priceId) {
                $user = User::where('stripe_id', $customerId)->first();
                if ($user) {
                    if ($status === 'active') {
                        $this->updateUserSubscriptionStatus($user, 'active', $priceId);
                    } elseif (in_array($status, ['canceled', 'unpaid', 'past_due'])) {
                        $this->updateUserSubscriptionStatus($user, 'inactive');
                    }
                    Log::info('Updated user subscription from webhook', [
                        'user_id' => $user->id,
                        'price_id' => $priceId,
                        'status' => $status,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling customer.subscription.updated: '.$e->getMessage());
        }

        return $this->successMethod();
    }

    /**
     * Handle customer subscription deleted event.
     */
    public function handleCustomerSubscriptionDeleted($payload)
    {
        Log::info('Webhook received: customer.subscription.deleted', ['payload_id' => $payload['id'] ?? 'N/A']);

        try {
            $subscription = $payload['data']['object'] ?? null;
            $customerId = $subscription['customer'] ?? null;

            if ($customerId) {
                $user = User::where('stripe_id', $customerId)->first();
                if ($user) {
                    // Get current plan name before downgrading
                    $currentPlan = ucfirst($user->subscription_plan).' '.ucfirst($user->subscription_tier);

                    $this->updateUserSubscriptionStatus($user, 'canceled');

                    // Send cancellation notification
                    $endsAt = isset($subscription['canceled_at']) ?
                        \Carbon\Carbon::createFromTimestamp($subscription['canceled_at']) :
                        now();

                    $user->notify(new \App\Notifications\SubscriptionCancelled($currentPlan, $endsAt));

                    Log::info('Canceled user subscription from webhook', [
                        'user_id' => $user->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error handling customer.subscription.deleted: '.$e->getMessage());
        }

        return $this->successMethod();
    }

    public function handleCustomerUpdated($payload)
    {
        Log::info('Webhook received: customer.updated', ['payload_id' => $payload['id'] ?? 'N/A']);

        return $this->successMethod();
    }

    public function handleCustomerDeleted($payload)
    {
        Log::info('Webhook received: customer.deleted', ['payload_id' => $payload['id'] ?? 'N/A']);

        return $this->successMethod();
    }

    /**
     * Handle checkout session completed event.
     * Handles payments completed via Stripe Checkout for:
     * - Client Management Pitches
     * - Service Package Orders
     */
    public function handleCheckoutSessionCompleted(
        array $payload,
        InvoiceService $invoiceService,
        NotificationService $notificationService
    ): Response {
        $session = $payload['data']['object'] ?? null;
        $sessionId = $session['id'] ?? null;
        Log::info('Webhook received: checkout.session.completed', ['session_id' => $sessionId]);

        if (! $session || ! $sessionId) {
            Log::error('Invalid checkout.session.completed payload received.', ['payload_id' => $payload['id'] ?? 'N/A']);

            return new Response('Invalid payload', 400);
        }

        // Check payment status - should be 'paid' for this event
        if ($session['payment_status'] !== 'paid') {
            Log::info('Checkout session completed but payment not marked as paid.', ['session_id' => $sessionId, 'payment_status' => $session['payment_status']]);

            return $this->successMethod(); // Acknowledge webhook
        }

        // Extract metadata and payment intent ID
        $metadata = $session['metadata'] ?? [];
        $paymentIntentId = $session['payment_intent'] ?? null;
        $pitchId = $metadata['pitch_id'] ?? null;
        $orderId = $metadata['order_id'] ?? null;
        $invoiceId = $metadata['invoice_id'] ?? null; // Local Invoice Model ID

        // --- Service Package Order Payment Processing ---
        if ($orderId && $invoiceId) {
            Log::info('Processing checkout.session.completed for Service Order.', [
                'session_id' => $sessionId,
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            try {
                DB::transaction(function () use ($orderId, $invoiceId, $sessionId, $paymentIntentId, $notificationService, $session) {
                    $order = Order::with('servicePackage')->find($orderId);
                    $invoice = Invoice::find($invoiceId);

                    if (! $order || ! $invoice) {
                        Log::error('Order or Invoice not found for checkout session.', [
                            'session_id' => $sessionId,
                            'order_id' => $orderId,
                            'invoice_id' => $invoiceId,
                        ]);
                        // Throw exception to rollback transaction and log error
                        throw new \Exception('Order or Invoice not found');
                    }

                    // Idempotency check: ensure we haven't already processed this
                    if ($order->payment_status === Order::PAYMENT_STATUS_PAID || $invoice->status === Invoice::STATUS_PAID) {
                        Log::info('Order/Invoice already marked as paid, skipping duplicate processing.', [
                            'session_id' => $sessionId,
                            'order_id' => $orderId,
                            'invoice_id' => $invoiceId,
                        ]);

                        return; // Exit transaction successfully
                    }

                    // Update Order Status
                    $order->payment_status = Order::PAYMENT_STATUS_PAID;
                    // Move to next logical step - usually pending requirements unless package has none
                    $order->status = Order::STATUS_PENDING_REQUIREMENTS; // Or check if requirements_prompt exists
                    if (empty($order->servicePackage->requirements_prompt)) {
                        $order->status = Order::STATUS_IN_PROGRESS;
                    }
                    $order->save();

                    // Update Invoice Status
                    $invoice->status = Invoice::STATUS_PAID;
                    $invoice->paid_at = now();
                    $invoice->stripe_checkout_session_id = $sessionId;
                    $invoice->stripe_payment_intent_id = $paymentIntentId;
                    // Store relevant session data if needed
                    $invoice->metadata = array_merge($invoice->metadata ?? [], ['checkout_session' => $session]);
                    $invoice->save();

                    // Create Order Event
                    $order->events()->create([
                        'event_type' => OrderEvent::EVENT_PAYMENT_RECEIVED,
                        'comment' => 'Payment successfully received via Stripe Checkout.',
                        'status_to' => $order->status,
                        'metadata' => ['stripe_checkout_session_id' => $sessionId, 'payment_intent_id' => $paymentIntentId],
                    ]);

                    // Send notifications to client and producer
                    $notificationService->notify($order->client, new \App\Notifications\Notifications\Order\OrderPaymentConfirmed($order));
                    $notificationService->notify($order->producer, new \App\Notifications\Notifications\Order\ProducerOrderReceived($order));

                    Log::info('Successfully processed Service Order payment via webhook.', ['order_id' => $orderId, 'invoice_id' => $invoiceId]);
                });

            } catch (\Exception $e) {
                Log::error('Error processing checkout.session.completed for Service Order: '.$e->getMessage(), [
                    'session_id' => $sessionId,
                    'order_id' => $orderId,
                    'invoice_id' => $invoiceId,
                    'exception' => $e,
                ]);
                // Don't throw error back to Stripe, return success to prevent retries
            }
        }
        // --- End Service Package Order Payment Processing ---

        // --- Client Management Pitch Payment Processing ---
        elseif ($pitchId && ($metadata['type'] ?? null) === 'client_pitch_payment') {
            Log::info('Processing checkout.session.completed for Client Pitch Payment.', [
                'session_id' => $sessionId,
                'pitch_id' => $pitchId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            try {
                DB::transaction(function () use ($pitchId, $sessionId, $paymentIntentId, $session) {
                    // Resolve service from container where needed
                    $pitchWorkflowService = app(PitchWorkflowService::class);

                    $pitch = Pitch::find($pitchId);
                    if (! $pitch) {
                        Log::error('Pitch not found for client payment checkout session.', [
                            'session_id' => $sessionId,
                            'pitch_id' => $pitchId,
                        ]);
                        throw new \Exception('Pitch not found');
                    }

                    // Idempotency check
                    if ($pitch->payment_status === Pitch::PAYMENT_STATUS_PAID) {
                        Log::info('Pitch already marked as paid, skipping duplicate processing.', [
                            'session_id' => $sessionId,
                            'pitch_id' => $pitchId,
                        ]);

                        return; // Exit transaction successfully
                    }

                    // Mark pitch as paid using workflow service (includes payout scheduling)
                    $pitchWorkflowService->markPitchAsPaid($pitch, $sessionId, $paymentIntentId);

                    // Call workflow service to update pitch status, create event, notify
                    // The service method should be idempotent regarding status updates
                    $pitchWorkflowService->clientApprovePitch($pitch, $pitch->project->client_email ?? 'webhook'); // Removed extra params no longer needed?

                    // Update or Create Invoice (using InvoiceService might be cleaner)
                    // Find existing or create new Invoice model based on pitch_id?
                    // This depends on whether an Invoice model was created *before* checkout
                    $invoice = Invoice::firstOrCreate(
                        ['pitch_id' => $pitch->id], // Assuming only one invoice per pitch for this flow
                        [
                            'user_id' => $pitch->project->user_id, // Or client_user_id if applicable
                            'amount' => $pitch->payment_amount, // Assuming payment_amount is on Pitch
                            'currency' => $pitch->project->prize_currency ?? 'USD', // Adjust as needed
                            'description' => 'Invoice for Client Pitch Payment #'.$pitch->id,
                            'metadata' => ['client_email' => $pitch->project->client_email],
                        ]
                    );

                    $invoice->status = Invoice::STATUS_PAID;
                    $invoice->paid_at = now();
                    $invoice->stripe_checkout_session_id = $sessionId;
                    $invoice->stripe_payment_intent_id = $paymentIntentId;
                    $invoice->metadata = array_merge($invoice->metadata ?? [], ['checkout_session' => $session]);
                    $invoice->save();

                    Log::info('Successfully processed Client Pitch Payment via webhook.', ['pitch_id' => $pitchId, 'invoice_id' => $invoice->id]);
                });

                // Send payment confirmation emails after successful transaction
                try {
                    $pitch = Pitch::find($pitchId); // Reload pitch after transaction
                    if ($pitch) {
                        $emailService = app(EmailService::class);
                        $project = $pitch->project;
                        $invoice = Invoice::where('pitch_id', $pitch->id)->first();

                        // Send client payment receipt
                        if ($project->client_email && $invoice) {
                            $invoiceUrl = route('billing.invoice.show', $invoice);
                            $portalUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                                'client.portal.view',
                                now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)),
                                ['project' => $project->id]
                            );

                            $emailService->sendClientPaymentReceipt(
                                $project->client_email,
                                $project->client_name,
                                $project,
                                $invoice->amount,
                                $invoice->currency,
                                $invoice->stripe_payment_intent_id ?? 'N/A',
                                $invoiceUrl,
                                $portalUrl
                            );

                            Log::info('Sent client payment receipt email', [
                                'pitch_id' => $pitch->id,
                                'invoice_id' => $invoice->id,
                            ]);
                        }

                        // Send producer payment received email
                        $producer = $pitch->user;
                        if ($producer && $invoice) {
                            // Calculate platform fee and net amount
                            $platformFeePercentage = config('business.platform_fee_percentage', 10);
                            $grossAmount = $invoice->amount;
                            $platformFee = ($grossAmount * $platformFeePercentage) / 100;
                            $netAmount = $grossAmount - $platformFee;

                            // Get payout date (immediate for client management)
                            $payoutDate = \Carbon\Carbon::now();

                            $emailService->sendProducerPaymentReceived(
                                $producer,
                                $project,
                                $pitch,
                                $grossAmount,
                                $platformFee,
                                $netAmount,
                                $invoice->currency,
                                $payoutDate
                            );

                            Log::info('Sent producer payment received email', [
                                'pitch_id' => $pitch->id,
                                'producer_id' => $producer->id,
                            ]);
                        }
                    }
                } catch (\Exception $emailException) {
                    // Log but don't fail the webhook processing
                    Log::error('Failed to send payment confirmation emails', [
                        'pitch_id' => $pitchId,
                        'error' => $emailException->getMessage(),
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('Error processing checkout.session.completed for Client Pitch: '.$e->getMessage(), [
                    'session_id' => $sessionId,
                    'pitch_id' => $pitchId,
                    'exception' => $e,
                ]);
                // Return success to prevent retries
            }
        }
        // --- End Client Management Pitch Payment Processing ---

        // --- Client Management Milestone Payment Processing ---
        elseif (($metadata['type'] ?? null) === 'client_milestone_payment' && ($metadata['milestone_id'] ?? null)) {
            $milestoneId = $metadata['milestone_id'];
            Log::info('Processing checkout.session.completed for Client Milestone Payment.', [
                'session_id' => $sessionId,
                'milestone_id' => $milestoneId,
                'payment_intent_id' => $paymentIntentId,
            ]);

            try {
                DB::transaction(function () use ($milestoneId, $sessionId, $paymentIntentId) {
                    $milestone = \App\Models\PitchMilestone::with('pitch')->find($milestoneId);
                    if (! $milestone) {
                        Log::error('Milestone not found for checkout session.', [
                            'session_id' => $sessionId,
                            'milestone_id' => $milestoneId,
                        ]);
                        throw new \Exception('Milestone not found');
                    }

                    // Idempotency check
                    if ($milestone->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID) {
                        Log::info('Milestone already marked as paid, skipping duplicate processing.', [
                            'session_id' => $sessionId,
                            'milestone_id' => $milestoneId,
                        ]);

                        return;
                    }

                    // Update milestone payment fields
                    $milestone->update([
                        'payment_status' => \App\Models\Pitch::PAYMENT_STATUS_PAID,
                        'payment_completed_at' => now(),
                        'stripe_invoice_id' => $sessionId, // store checkout session id; can be replaced with invoice id if created
                        'status' => $milestone->status === 'approved' ? 'approved' : 'approved',
                    ]);

                    // If this is a revision milestone with a linked snapshot, approve it automatically
                    if ($milestone->is_revision_milestone && $milestone->pitch_snapshot_id) {
                        $snapshot = \App\Models\PitchSnapshot::find($milestone->pitch_snapshot_id);

                        if ($snapshot && $snapshot->status === \App\Models\PitchSnapshot::STATUS_PENDING) {
                            $snapshot->update(['status' => \App\Models\PitchSnapshot::STATUS_APPROVED]);

                            Log::info('Auto-approved revision snapshot after milestone payment', [
                                'milestone_id' => $milestone->id,
                                'snapshot_id' => $snapshot->id,
                                'revision_round' => $milestone->revision_round_number,
                            ]);

                            // Create event for snapshot approval
                            $milestone->pitch?->events()->create([
                                'event_type' => 'snapshot_approved',
                                'comment' => "Revision snapshot V{$snapshot->version} auto-approved after milestone payment.",
                                'status' => $milestone->pitch?->status,
                                'metadata' => [
                                    'milestone_id' => $milestone->id,
                                    'snapshot_id' => $snapshot->id,
                                    'revision_round' => $milestone->revision_round_number,
                                    'auto_approved_via' => 'milestone_payment',
                                    'amount_paid' => (string) $milestone->amount,
                                ],
                            ]);
                        }
                    }

                    // Schedule payout for the producer
                    $payoutService = app(\App\Services\PayoutProcessingService::class);
                    $payoutSchedule = $payoutService->schedulePayoutForMilestone($milestone, $sessionId, false);

                    Log::info('Payout scheduled for milestone payment', [
                        'milestone_id' => $milestone->id,
                        'payout_schedule_id' => $payoutSchedule->id,
                        'net_amount' => $payoutSchedule->net_amount,
                    ]);

                    // Optionally, add an event to the pitch timeline (with idempotency check)
                    try {
                        // Check if event already exists for this session to prevent duplicates on webhook retries
                        $existingEvent = $milestone->pitch?->events()
                            ->where('event_type', 'milestone_paid')
                            ->where(function ($query) use ($sessionId) {
                                $query->whereJsonContains('metadata->stripe_checkout_session_id', $sessionId)
                                    ->orWhereJsonContains('metadata->stripe_checkout_session_id', (string) $sessionId);
                            })
                            ->first();

                        if (! $existingEvent) {
                            $milestone->pitch?->events()->create([
                                'event_type' => 'milestone_paid',
                                'comment' => 'Milestone "'.$milestone->name.'" payment received.',
                                'status' => $milestone->pitch?->status,
                                'metadata' => [
                                    'milestone_id' => $milestone->id,
                                    'amount' => (string) $milestone->amount,
                                    'stripe_checkout_session_id' => $sessionId,
                                    'payment_intent_id' => $paymentIntentId,
                                ],
                            ]);
                        } else {
                            Log::info('Milestone payment event already exists, skipping duplicate creation', [
                                'milestone_id' => $milestoneId,
                                'session_id' => $sessionId,
                            ]);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to create pitch event for milestone payment.', [
                            'milestone_id' => $milestoneId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                });

            } catch (\Exception $e) {
                Log::error('Error processing checkout.session.completed for Milestone: '.$e->getMessage(), [
                    'session_id' => $sessionId,
                    'milestone_id' => $milestoneId ?? null,
                    'exception' => $e,
                ]);
                // Return success to prevent Stripe retries
            }
        }
        // --- End Client Management Milestone Payment Processing ---

        else {
            Log::info('Checkout session completed did not match expected metadata for Order or Client Pitch.', [
                'session_id' => $sessionId,
                'metadata' => $metadata,
            ]);
        }

        return $this->successMethod(); // Always return success to Stripe
    }

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
            Log::error('Error during user invoice sync from webhook: '.$e->getMessage(), [
                'customer_id' => $customer ?? 'N/A',
                'payload_id' => $payload['id'] ?? 'N/A',
                'event_type' => $payload['type'] ?? 'N/A',
                'exception' => $e,
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

    /**
     * Update the user's subscription status
     */
    private function updateUserSubscriptionStatus($user, $status, $priceId = null)
    {
        $priceMapping = [
            config('subscription.stripe_prices.pro_artist_monthly') => [
                'plan' => 'pro',
                'tier' => 'artist',
                'billing_period' => 'monthly',
                'price' => config('subscription.plans.pro_artist.monthly_price'),
            ],
            config('subscription.stripe_prices.pro_artist_yearly') => [
                'plan' => 'pro',
                'tier' => 'artist',
                'billing_period' => 'yearly',
                'price' => config('subscription.plans.pro_artist.yearly_price'),
            ],
            config('subscription.stripe_prices.pro_engineer_monthly') => [
                'plan' => 'pro',
                'tier' => 'engineer',
                'billing_period' => 'monthly',
                'price' => config('subscription.plans.pro_engineer.monthly_price'),
            ],
            config('subscription.stripe_prices.pro_engineer_yearly') => [
                'plan' => 'pro',
                'tier' => 'engineer',
                'billing_period' => 'yearly',
                'price' => config('subscription.plans.pro_engineer.yearly_price'),
            ],
        ];

        if ($priceId && isset($priceMapping[$priceId])) {
            $mapping = $priceMapping[$priceId];
            $user->update([
                'subscription_plan' => $mapping['plan'],
                'subscription_tier' => $mapping['tier'],
                'billing_period' => $mapping['billing_period'],
                'subscription_price' => $mapping['price'],
                'subscription_currency' => 'USD',
                'plan_started_at' => $status === 'active' ? now() : $user->plan_started_at,
            ]);
        } elseif ($status === 'canceled' || $status === 'inactive') {
            // Downgrade to free plan
            $user->update([
                'subscription_plan' => 'free',
                'subscription_tier' => 'basic',
                'billing_period' => 'monthly',
                'subscription_price' => null,
                'subscription_currency' => 'USD',
                'plan_started_at' => null,
                'monthly_pitch_count' => 0,
                'monthly_pitch_reset_date' => null,
            ]);
        }
    }
}
