<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
        // Handle successful payment logic here
        // e.g., Store payment information, update order status, etc.
        
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
        // Handle failed payment logic here
        // e.g., Notify user, update order status, etc.
        
        return $this->successMethod();
    }

    /**
     * Handle invoice created event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoiceCreated($payload)
    {
        try {
            $customer = $payload['data']['object']['customer'];
            $user = User::where('stripe_id', $customer)->first();
            
            if ($user) {
                // Force sync the user's invoices with Stripe
                $user->createOrGetStripeCustomer();
                // The invoice will be available in the invoices collection
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice.created webhook: ' . $e->getMessage(), [
                'payload' => $payload,
                'exception' => $e
            ]);
        }
        
        return $this->successMethod();
    }

    /**
     * Handle invoice payment succeeded event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleInvoicePaymentSucceeded($payload)
    {
        try {
            $customer = $payload['data']['object']['customer'];
            $user = User::where('stripe_id', $customer)->first();
            
            if ($user) {
                // Force sync the user's invoices with Stripe
                $user->createOrGetStripeCustomer();
                // The invoice will be available in the invoices collection
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice.payment_succeeded webhook: ' . $e->getMessage(), [
                'payload' => $payload,
                'exception' => $e
            ]);
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
        try {
            $customer = $payload['data']['object']['customer'];
            $user = User::where('stripe_id', $customer)->first();
            
            if ($user) {
                // Force sync the user's invoices with Stripe
                $user->createOrGetStripeCustomer();
                // The invoice will be available in the invoices collection
            }
        } catch (\Exception $e) {
            Log::error('Error handling invoice.payment_failed webhook: ' . $e->getMessage(), [
                'payload' => $payload,
                'exception' => $e
            ]);
        }
        
        return $this->successMethod();
    }

    /**
     * Handle subscription created event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionCreated($payload)
    {
        // Handle subscription created logic here
        
        return $this->successMethod();
    }

    /**
     * Handle subscription updated event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionUpdated($payload)
    {
        // Handle subscription updated logic here
        
        return $this->successMethod();
    }

    /**
     * Handle subscription deleted event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerSubscriptionDeleted($payload)
    {
        // Handle subscription deleted logic here
        
        return $this->successMethod();
    }

    /**
     * Handle customer updated event.
     *
     * @param  array  $payload
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handleCustomerUpdated($payload)
    {
        // Handle customer updated logic here
        
        return $this->successMethod();
    }
}
