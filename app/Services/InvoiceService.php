<?php

namespace App\Services;

use App\Models\Pitch;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Models\Order;
use App\Models\Invoice;
use App\Models\Project;

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
            
            // Log before finalizing
            \Log::info('About to finalize invoice', [
                'invoice_id' => $invoice->id,
                'payment_method' => $paymentMethod,
                'invoice_status' => $invoice->status ?? 'unknown'
            ]);

            // Ensure the payment method is attached to the customer
            try {
                $paymentMethodObj = $stripe->paymentMethods->retrieve($paymentMethod);
                
                // If the payment method is not attached to the customer, attach it
                if (!$paymentMethodObj->customer) {
                    \Log::info('Attaching payment method to customer', [
                        'payment_method' => $paymentMethod,
                        'customer' => $invoice->customer
                    ]);
                    
                    $stripe->paymentMethods->attach($paymentMethod, [
                        'customer' => $invoice->customer,
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to attach payment method to customer', [
                    'payment_method' => $paymentMethod,
                    'customer' => $invoice->customer,
                    'error' => $e->getMessage()
                ]);
                throw new \Exception('Failed to attach payment method to customer: ' . $e->getMessage());
            }
            
            // Finalize the invoice if it's not already finalized
            $finalizedInvoice = null;
            try {
                // Try to finalize the invoice - if it's already finalized,
                // this will throw an exception that we can catch
                $finalizedInvoice = $stripe->invoices->finalizeInvoice($invoice->id);
                
                \Log::info('Invoice finalized successfully', [
                    'invoice_id' => $finalizedInvoice->id,
                    'invoice_status' => $finalizedInvoice->status,
                    'invoice_total' => $finalizedInvoice->total,
                    'invoice_amount_due' => $finalizedInvoice->amount_due
                ]);
            } catch (\Exception $e) {
                // If the error is because the invoice is already finalized, we can continue
                if (strpos($e->getMessage(), 'already finalized') !== false) {
                    \Log::info('Invoice is already finalized, retrieving it', [
                        'invoice_id' => $invoice->id
                    ]);
                    
                    // Retrieve the invoice instead
                    $finalizedInvoice = $stripe->invoices->retrieve($invoice->id);
                } else {
                    // If it's some other error, re-throw it
                    throw $e;
                }
            }
            
            // Pay the invoice using the specified payment method
            \Log::info('About to pay invoice', [
                'invoice_id' => $finalizedInvoice->id,
                'payment_method' => $paymentMethod,
                'finalized_invoice_status' => $finalizedInvoice->status
            ]);
            
            $payResult = $stripe->invoices->pay($finalizedInvoice->id, [
                'payment_method' => $paymentMethod,
                'off_session' => true,
            ]);
            
            // Log payment result
            \Log::info('Invoice payment processed', [
                'invoice_id' => $payResult->id,
                'status' => $payResult->status,
                'paid' => $payResult->paid,
                'payment_intent' => $payResult->payment_intent ?? null
            ]);
            
            return [
                'success' => true,
                'paymentResult' => $payResult
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to process invoice payment', [
                'invoice_id' => $invoice->id,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'exception' => $e
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
     * Create or update an invoice for a paid pitch via checkout session
     * 
     * @param \App\Models\Pitch $pitch The pitch that was paid for
     * @param string $sessionId The Stripe checkout session ID
     * @param float $amount The payment amount (in base unit/dollars)
     * @param string $currency The currency code (uppercase)
     * @return array [success, invoice|error]
     */
    public function createOrUpdateInvoiceForPaidPitch($pitch, $sessionId, $amount, $currency = 'USD')
    {
        try {
            // Use the protected method for better testability
            $stripe = $this->newStripeClient();
            
            \Log::info('Creating/updating invoice for paid pitch', [
                'pitch_id' => $pitch->id,
                'checkout_session_id' => $sessionId,
                'amount' => $amount,
                'currency' => $currency
            ]);
            
            // Check if the pitch already has a final invoice ID
            if ($pitch->final_invoice_id) {
                \Log::info('Pitch already has a final invoice, retrieving', [
                    'pitch_id' => $pitch->id,
                    'final_invoice_id' => $pitch->final_invoice_id
                ]);
                
                // Retrieve the existing invoice
                $invoice = $stripe->invoices->retrieve($pitch->final_invoice_id);
                
                return [
                    'success' => true,
                    'invoice' => $invoice,
                    'message' => 'Retrieved existing invoice'
                ];
            }
            
            // We need to create a new invoice
            // First, ensure the user/customer exists in Stripe
            $user = $pitch->project->user;
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            // Create a unique invoice ID for reference
            $invoiceId = 'pitch_' . $pitch->id . '_' . time();
            
            // Create the invoice with metadata
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'collection_method' => 'charge_automatically',
                'auto_advance' => false, // Don't finalize automatically
                'currency' => strtolower($currency),
                'description' => "Payment for Pitch #{$pitch->id}",
                'metadata' => [
                    'pitch_id' => $pitch->id,
                    'project_id' => $pitch->project_id,
                    'checkout_session_id' => $sessionId,
                    'source' => 'checkout_session'
                ]
            ]);
            
            // Create the invoice item
            $stripe->invoiceItems->create([
                'customer' => $user->stripe_id,
                'invoice' => $invoice->id,
                'currency' => strtolower($currency),
                'amount' => (int)($amount * 100), // Convert to cents for Stripe
                'description' => "Payment for Pitch #{$pitch->id}: {$pitch->title}"
            ]);
            
            // Mark the invoice as paid (since the checkout session already processed payment)
            $invoice = $stripe->invoices->markAsPaid($invoice->id, [
                'paid_out_of_band' => true
            ]);
            
            // Update the pitch with the invoice ID
            $pitch->final_invoice_id = $invoice->id;
            $pitch->save();
            
            \Log::info('Successfully created and marked invoice as paid for pitch', [
                'pitch_id' => $pitch->id,
                'invoice_id' => $invoice->id
            ]);
            
            return [
                'success' => true,
                'invoice' => $invoice,
                'message' => 'Created new invoice and marked as paid'
            ];
            
        } catch (\Exception $e) {
            \Log::error('Failed to create/update invoice for paid pitch', [
                'pitch_id' => $pitch->id ?? 'unknown',
                'checkout_session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
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

    /**
     * Create an Invoice model record for a new Order.
     *
     * @param Order $order
     * @return Invoice
     */
    public function createInvoiceForOrder(Order $order): Invoice
    {
        return Invoice::create([
            'user_id' => $order->client_user_id,
            'order_id' => $order->id,
            'pitch_id' => null, // Not related to a pitch
            'amount' => $order->price,
            'currency' => $order->currency,
            'status' => Invoice::STATUS_PENDING, // Initial status
            'description' => 'Invoice for Order #' . $order->id . ': ' . ($order->servicePackage->title ?? 'Service Package'),
            'metadata' => [
                'service_package_id' => $order->service_package_id,
                'producer_user_id' => $order->producer_user_id,
            ],
        ]);
    }

    /**
     * Create a contest prize invoice for multiple winners
     * 
     * @param Project $project
     * @param array $winners
     * @param float $totalAmount
     * @return object
     */
    public function createContestPrizeInvoice($project, array $winners, float $totalAmount)
    {
        $user = $project->user;
        $invoiceId = 'CONTEST-' . strtoupper(substr(md5(uniqid()), 0, 10));
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        try {
            $stripe = $this->newStripeClient();
            
            // Add contest metadata to the invoice
            $metadata = [
                'project_id' => $project->id,
                'invoice_id' => $invoiceId,
                'source' => 'contest_prizes',
                'winner_count' => count($winners)
            ];
            
            // Generate description for the payment
            $description = "Contest Prize Payment: {$project->name}";
            
            // Create the invoice
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => false,
                'description' => $description,
                'collection_method' => 'charge_automatically',
                'metadata' => $metadata
            ]);
            
            // Create invoice items for each winner
            foreach ($winners as $winner) {
                $prize = $winner['prize'];
                $winnerUser = $winner['user'];
                $pitch = $winner['pitch'];
                
                $stripe->invoiceItems->create([
                    'customer' => $user->stripe_id,
                    'amount' => (int)($prize->cash_amount * 100), // Convert to cents
                    'currency' => 'usd',
                    'description' => "Prize: {$prize->getPlacementDisplayName()} Place - {$winnerUser->name}",
                    'invoice' => $invoice->id,
                    'metadata' => [
                        'pitch_id' => $pitch->id,
                        'winner_user_id' => $winnerUser->id,
                        'prize_placement' => $prize->placement,
                        'prize_amount' => $prize->cash_amount,
                    ]
                ]);
            }
            
            return $invoice;
            
        } catch (\Exception $e) {
            \Log::error('Failed to create contest prize invoice', [
                'project_id' => $project->id,
                'total_amount' => $totalAmount,
                'winner_count' => count($winners),
                'error' => $e->getMessage()
            ]);
            
            throw new \Exception('Failed to create contest prize invoice: ' . $e->getMessage());
        }
    }
}
