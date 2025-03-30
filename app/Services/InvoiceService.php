<?php

namespace App\Services;

use App\Models\Pitch;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment;

class InvoiceService 
{
    /**
     * Create a unified invoice for a pitch payment
     * 
     * @param Pitch $pitch
     * @param float $amount
     * @param string $paymentMethod
     * @return array [invoice, invoiceId, success]
     */
    public function createPitchInvoice(Pitch $pitch, float $amount, string $paymentMethod)
    {
        $user = $pitch->project->user;
        $invoiceId = 'INV-' . strtoupper(substr(md5(uniqid()), 0, 10));
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        try {
            // Create Stripe client using the protected method for better testability
            $stripe = $this->newStripeClient();
            
            // Add pitch and project metadata to the invoice
            $metadata = [
                'pitch_id' => $pitch->id,
                'project_id' => $pitch->project->id,
                'invoice_id' => $invoiceId,
                'source' => 'pitch_payment'
            ];
            
            // Generate description for the payment
            $description = "Payment for Pitch: {$pitch->title} (Project: {$pitch->project->name})";
            
            // First create the invoice
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => false,
                'description' => $description,
                'collection_method' => 'charge_automatically',
                'metadata' => $metadata
            ]);
            
            // Create an invoice item attached to the invoice
            $invoiceItem = $stripe->invoiceItems->create([
                'customer' => $user->stripe_id,
                'amount' => (int)($amount * 100), // Convert to cents
                'currency' => 'usd',
                'description' => "Payment for '{$pitch->title}' pitch",
                'invoice' => $invoice->id,
                'metadata' => [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project->id,
                ]
            ]);
            
            // Return the created invoice
            return [
                'invoice' => $invoice,
                'invoiceId' => $invoiceId,
                'success' => true
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to create pitch invoice', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'invoice' => null,
                'invoiceId' => $invoiceId,
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process payment for an existing invoice
     * 
     * @param object $invoice
     * @param string $paymentMethod
     * @return array [success, paymentResult]
     */
    public function processInvoicePayment($invoice, $paymentMethod)
    {
        try {
            // Use the protected method for better testability
            $stripe = $this->newStripeClient();
            
            // Finalize the invoice
            $finalizedInvoice = $stripe->invoices->finalizeInvoice($invoice->id);
            
            // Pay the invoice using the specified payment method
            $payResult = $stripe->invoices->pay($invoice->id, [
                'payment_method' => $paymentMethod,
                'off_session' => true,
            ]);
            
            return [
                'success' => true,
                'paymentResult' => $payResult
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to process invoice payment', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get invoice details by invoice ID
     * 
     * @param string $invoiceId
     * @return object|null
     */
    public function getInvoice($invoiceId)
    {
        try {
            // Use the protected method for better testability
            $stripe = $this->newStripeClient();
            $invoice = $stripe->invoices->retrieve($invoiceId, [
                'expand' => ['lines', 'customer', 'payment_intent']
            ]);
            
            // Format the invoice data to ensure consistent structure
            return (object)[
                'id' => $invoice->id,
                'number' => $invoice->number,
                'date' => \Carbon\Carbon::createFromTimestamp($invoice->created),
                'total' => $invoice->total,
                'paid' => $invoice->status === 'paid',
                'metadata' => $invoice->metadata->toArray(),
                'description' => $invoice->description,
                'lines' => $invoice->lines,
                'stripe_invoice' => $invoice
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to retrieve invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Get all invoices for the user with pitch metadata
     * 
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getUserInvoices($limit = 10)
    {
        $user = Auth::user();
        
        if (!$user->stripe_id) {
            return collect([]);
        }
        
        try {
            // Use the protected method for better testability
            $stripe = $this->newStripeClient();
            $stripeInvoices = $stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => $limit,
                'expand' => ['data.lines', 'data.payment_intent']
            ]);
            
            // Map Stripe invoices to a format we can use in the view
            return collect($stripeInvoices->data)->map(function($stripeInvoice) {
                // Ensure metadata is an array even if empty
                $metadata = [];
                if (isset($stripeInvoice->metadata) && is_object($stripeInvoice->metadata)) {
                    $metadata = $stripeInvoice->metadata->toArray();
                }
                
                return (object)[
                    'id' => $stripeInvoice->id,
                    'number' => $stripeInvoice->number,
                    'date' => \Carbon\Carbon::createFromTimestamp($stripeInvoice->created),
                    'total' => $stripeInvoice->total,
                    'paid' => $stripeInvoice->status === 'paid',
                    'metadata' => $metadata,
                    'description' => $stripeInvoice->description ?? '',
                    'lines' => $stripeInvoice->lines ?? null,
                    'stripe_invoice' => $stripeInvoice
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error retrieving user invoices', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            return collect([]);
        }
    }

    /**
     * Create a new Stripe client instance.
     * Protected method to allow mocking in tests.
     * 
     * @return \Stripe\StripeClient
     */
    protected function newStripeClient()
    {
        return new \Stripe\StripeClient(config('cashier.secret'));
    }
}
