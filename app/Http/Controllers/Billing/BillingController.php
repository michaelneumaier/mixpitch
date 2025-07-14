<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use App\Filament\Plugins\Billing\Pages\BillingDashboard;
use Illuminate\View\View;

class BillingController extends Controller
{
    /**
     * Display the billing portal.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $user = Auth::user();
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        // Get subscription information
        $isSubscribed = $user->hasActiveSubscription();
        $subscription = $isSubscribed ? $user->subscription('default') : null;
        $onGracePeriod = $isSubscribed && $subscription && $subscription->onGracePeriod();
        
        // Get subscription limits and usage
        $limits = $user->getSubscriptionLimits();
        $usage = [
            'projects_count' => $user->projects()->count(),
            'active_pitches_count' => $user->getActivePitchesCount(),
            'monthly_pitches_used' => $user->getMonthlyPitchCount(),
            'visibility_boosts_used' => $user->getRemainingVisibilityBoosts() ?? 0,
            'private_projects_used' => $user->getRemainingPrivateProjects() ?? 0,
            'license_templates_count' => $user->licenseTemplates()->count() ?? 0,
        ];
        
        // Get billing summary
        $billingSummary = [
            'plan_name' => $user->getSubscriptionDisplayName(),
            'billing_period' => $user->getBillingPeriodDisplayName(),
            'formatted_price' => $user->getFormattedSubscriptionPrice(),
            'yearly_savings' => $user->getYearlySavings(),
            'next_billing_date' => $user->getNextBillingDate(),
            'total_earnings' => $user->getTotalEarnings(),
            'commission_savings' => $user->getCommissionSavings(),
            'commission_rate' => $user->getPlatformCommissionRate(),
        ];
        
        // Get payment method and setup intent
        $hasPaymentMethod = $user->hasPaymentMethod();
        $intent = $user->createSetupIntent();
        
        // Get Stripe Connect status using enhanced method
        $stripeConnectService = app(\App\Services\StripeConnectService::class);
        $accountStatus = $stripeConnectService->getDetailedAccountStatus($user);
        
        // Fetch invoices directly from Stripe for more accurate data
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $stripeInvoices = $stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 10,
            ]);
            
            // Also get Cashier invoices for data we might need from there
            $cashierInvoices = $user->invoices()->take(10);
            
            // Map Stripe invoices to a format we can use in the view
            $invoices = collect($stripeInvoices->data)->map(function($stripeInvoice) use ($cashierInvoices) {
                // Find matching Cashier invoice
                $cashierInvoice = $cashierInvoices->first(function($invoice) use ($stripeInvoice) {
                    return $invoice->id === $stripeInvoice->id;
                });
                
                return (object)[
                    'id' => $stripeInvoice->id,
                    'number' => $stripeInvoice->number,
                    'date' => \Carbon\Carbon::createFromTimestamp($stripeInvoice->created),
                    'total' => $stripeInvoice->total,
                    'amount_paid' => $stripeInvoice->amount_paid,
                    'status' => $stripeInvoice->status,
                    'paid' => $stripeInvoice->status === 'paid',
                    'description' => $stripeInvoice->description,
                    'stripe_invoice' => $stripeInvoice,
                    'cashier_invoice' => $cashierInvoice
                ];
            });
        } catch (\Exception $e) {
            \Log::error('Error retrieving invoices for billing index', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            // Fall back to Cashier invoices if there's an error
            $invoices = $user->invoices()->take(10);
        }
        
        // Check if user has payment method
        $hasDefaultPaymentMethod = $user->hasDefaultPaymentMethod();
        $paymentMethod = null;
        
        if ($hasDefaultPaymentMethod) {
            try {
                $paymentMethod = $user->defaultPaymentMethod();
            } catch (\Exception $e) {
                \Log::warning('Error retrieving default payment method', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                $hasDefaultPaymentMethod = false;
            }
        }
        
        // Ensure hasPaymentMethod is consistent with the actual payment method availability
        $hasPaymentMethod = $hasDefaultPaymentMethod && $paymentMethod !== null;
        
        return view('billing.index', [
            'user' => $user,
            'invoices' => $invoices,
            'hasPaymentMethod' => $hasPaymentMethod,
            'hasDefaultPaymentMethod' => $hasDefaultPaymentMethod,
            'paymentMethod' => $paymentMethod,
            'intent' => $intent,
            'accountStatus' => $accountStatus,
            'isSubscribed' => $isSubscribed,
            'subscription' => $subscription,
            'onGracePeriod' => $onGracePeriod,
            'limits' => $limits,
            'usage' => $usage,
            'billingSummary' => $billingSummary,
        ]);
    }

    /**
     * Update the user's payment method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePaymentMethod(Request $request)
    {
        $user = Auth::user();

        try {
            // Create Stripe customer if one doesn't exist yet
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            $user->updateDefaultPaymentMethod($request->payment_method);
            
            return redirect()->route('billing')->with('success', 'Payment method updated successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Remove the user's payment method.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function removePaymentMethod(Request $request)
    {
        $user = Auth::user();
        $paymentMethodId = $request->input('payment_method_id');

        try {
            if ($paymentMethodId) {
                // Delete a specific payment method
                $paymentMethod = $user->findPaymentMethod($paymentMethodId);
                if ($paymentMethod) {
                    $paymentMethod->delete();
                }
            } else {
                // Default behavior - delete the default payment method
                $paymentMethod = $user->defaultPaymentMethod();
                if ($paymentMethod) {
                    $paymentMethod->delete();
                }
            }
            
            return back()->with('success', 'Payment method removed successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Process a single payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function processPayment(Request $request)
    {
        $user = Auth::user();
        $amount = floatval($request->input('amount')) * 100; // Convert to cents and ensure it's a number
        $description = $request->input('description', 'Payment for services');
        $paymentMethod = $request->input('payment_method');

        // Validate amount
        if ($amount <= 0) {
            return redirect()->back()->withErrors(['error' => 'Please enter a valid amount greater than zero.']);
        }

        // Validate payment method
        if (empty($paymentMethod)) {
            return redirect()->back()->withErrors(['error' => 'Payment method is required.']);
        }

        try {
            // Create Stripe customer if one doesn't exist yet
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            // First create a Stripe Invoice
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            
            // Log the amount we're about to charge
            \Log::info('Creating invoice with amount', [
                'amount' => $amount,
                'amount_dollars' => $amount / 100,
                'user_id' => $user->id,
                'payment_method' => $paymentMethod
            ]);
            
            // First create the invoice
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => false, // Don't auto-finalize yet
                'description' => $description,
                'collection_method' => 'charge_automatically',
                'metadata' => [
                    'source' => 'one_time_payment',
                    'user_id' => $user->id,
                    'amount' => $amount
                ]
            ]);
            
            // Then create an invoice item attached to the invoice
            $invoiceItem = $stripe->invoiceItems->create([
                'customer' => $user->stripe_id,
                'amount' => (int)$amount, // Ensure amount is an integer
                'currency' => 'usd',
                'description' => $description,
                'invoice' => $invoice->id, // Attach to the invoice we just created
            ]);
            
            // Finalize the invoice
            $invoice = $stripe->invoices->finalizeInvoice($invoice->id);
            
            // Log the invoice before payment
            \Log::info('Invoice finalized, about to pay', [
                'invoice_id' => $invoice->id,
                'invoice_total' => $invoice->total,
                'invoice_amount_due' => $invoice->amount_due
            ]);
            
            // Pay the invoice using the specified payment method
            $payResult = $stripe->invoices->pay($invoice->id, [
                'payment_method' => $paymentMethod,
                'off_session' => true,
            ]);
            
            // Log the successful payment
            \Log::info('Payment processed successfully', [
                'user_id' => $user->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'description' => $description,
                'payment_result' => [
                    'status' => $payResult->status,
                    'total' => $payResult->total,
                    'amount_paid' => $payResult->amount_paid
                ]
            ]);
            
            // Refresh the user's invoices from Stripe
            $this->syncInvoicesFromStripe($user);
            
            return redirect()->back()->with('success', 'Payment of $' . number_format($amount / 100, 2) . ' processed successfully!');
        } catch (\Stripe\Exception\CardException $e) {
            \Log::error('Card error during payment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'amount' => $amount
            ]);
            return redirect()->back()->withErrors(['error' => 'Card error: ' . $e->getMessage()]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            \Log::error('Invalid request during payment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'amount' => $amount
            ]);
            return redirect()->back()->withErrors(['error' => 'Invalid request: ' . $e->getMessage()]);
        } catch (\Laravel\Cashier\Exceptions\IncompletePayment $exception) {
            \Log::info('Incomplete payment requiring authentication', [
                'user_id' => $user->id,
                'payment_id' => $exception->payment->id,
                'amount' => $amount
            ]);
            // Redirect to the payment confirmation page if additional authentication is needed
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('billing')])
            ;
        } catch (\Exception $e) {
            \Log::error('Error processing payment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'amount' => $amount
            ]);
            return redirect()->back()->withErrors(['error' => 'Error processing payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the list of invoices
     *
     * @return \Illuminate\View\View
     */
    public function invoices()
    {
        $invoiceService = app(InvoiceService::class);
        $invoices = $invoiceService->getUserInvoices(50); // Get up to 50 invoices
        
        return view('billing.invoices', [
            'invoices' => $invoices
        ]);
    }
    
    /**
     * Show invoice details
     *
     * @param string $invoiceId
     * @return \Illuminate\View\View
     */
    public function showInvoice($invoiceId)
    {
        $invoiceService = app(InvoiceService::class);
        $invoice = $invoiceService->getInvoice($invoiceId);
        
        if (!$invoice) {
            return redirect()->route('billing.invoices')
                ->with('error', 'Invoice not found.');
        }
        
        return view('billing.invoice-show', [
            'invoice' => $invoice
        ]);
    }
    
    /**
     * Download invoice PDF
     *
     * @param string $invoiceId
     * @return \Symfony\Component\HttpFoundation\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadInvoice($invoiceId)
    {
        $user = Auth::user();
        
        try {
            $invoice = $user->findInvoice($invoiceId);
            return $invoice->download([
                'vendor' => config('app.name'),
                'product' => 'Subscription and Services',
            ]);
        } catch (\Exception $e) {
            return redirect()->route('billing.invoices')
                ->with('error', 'Error downloading invoice: ' . $e->getMessage());
        }
    }

    /**
     * Create the customer portal session for billing management.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function customerPortal(Request $request)
    {
        $user = Auth::user();
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        try {
            return $user->redirectToBillingPortal(route('billing'));
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Stripe Portal Error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id
            ]);
            
            // Provide a helpful error message
            $errorMessage = 'Unable to access the Stripe Customer Portal. ';
            
            // Check for specific error about portal configuration
            if (strpos($e->getMessage(), 'No configuration provided') !== false) {
                $errorMessage .= 'The Stripe Customer Portal has not been configured yet. Please set up your Customer Portal in the Stripe Dashboard.';
            } else {
                $errorMessage .= $e->getMessage();
            }
            
            return redirect()->route('billing')->withErrors(['error' => $errorMessage]);
        }
    }

    /**
     * Display the checkout page for a specific product/price.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $priceId = $request->price_id;
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        return view('billing.checkout', [
            'intent' => $user->createSetupIntent(),
            'priceId' => $priceId,
        ]);
    }

    /**
     * Display payment method management page.
     *
     * @return \Illuminate\View\View
     */
    public function managePaymentMethods()
    {
        $user = Auth::user();
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        // Get all payment methods for the user
        $paymentMethods = $user->paymentMethods();
        $defaultPaymentMethod = $user->defaultPaymentMethod();
        
        return view('billing.payment-methods', [
            'intent' => $user->createSetupIntent(),
            'paymentMethods' => $paymentMethods,
            'defaultPaymentMethod' => $defaultPaymentMethod,
        ]);
    }

    /**
     * Sync the user's invoices with Stripe to ensure we have the latest data.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function syncInvoicesFromStripe($user)
    {
        try {
            // Make sure we have a Stripe customer
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
                return; // New customer won't have invoices yet
            }
            
            // Use Stripe API to get the latest invoice data
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $stripeInvoices = $stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 100, // Get a reasonable number of invoices
                'expand' => ['data.charge', 'data.payment_intent']
            ]);
            
            // Force a refresh by calling the invoices() method
            // We'll count the invoices to ensure the method is actually executed
            $invoiceCount = $user->invoices()->count();
            
            \Log::info("Synced {$invoiceCount} invoices for user {$user->id}");
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync invoices from Stripe: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id
            ]);
        }
    }

    /**
     * Diagnostic method to show raw invoice data from Stripe.
     * This can help debug invoice syncing issues.
     *
     * @return \Illuminate\Http\Response
     */
    public function diagnosticInvoices()
    {
        $user = Auth::user();
        
        try {
            // Make sure we have a Stripe customer
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            // Get raw invoice data from Stripe
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $stripeInvoices = $stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 10, // Limit to most recent invoices
                'expand' => ['data.lines.data', 'data.payment_intent']
            ]);
            
            // Get Cashier invoice data for comparison
            $cashierInvoices = $user->invoices();
            
            $diagnosticData = [
                'stripe_customer_id' => $user->stripe_id,
                'stripe_invoices' => [],
                'cashier_invoices' => []
            ];
            
            // Process Stripe invoice data
            foreach ($stripeInvoices->data as $invoice) {
                $invoiceData = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'created' => date('Y-m-d H:i:s', $invoice->created),
                    'due_date' => isset($invoice->due_date) ? date('Y-m-d', $invoice->due_date) : null,
                    'status' => $invoice->status,
                    'amount_due' => $invoice->amount_due,
                    'amount_due_dollars' => number_format($invoice->amount_due / 100, 2),
                    'amount_paid' => $invoice->amount_paid,
                    'amount_paid_dollars' => number_format($invoice->amount_paid / 100, 2),
                    'total' => $invoice->total,
                    'total_dollars' => number_format($invoice->total / 100, 2),
                    'subtotal' => $invoice->subtotal,
                    'subtotal_dollars' => number_format($invoice->subtotal / 100, 2),
                    'line_items' => []
                ];
                
                // Add line items if available
                if (isset($invoice->lines) && isset($invoice->lines->data)) {
                    foreach ($invoice->lines->data as $lineItem) {
                        $invoiceData['line_items'][] = [
                            'id' => $lineItem->id,
                            'description' => $lineItem->description,
                            'amount' => $lineItem->amount,
                            'amount_dollars' => number_format($lineItem->amount / 100, 2),
                            'currency' => $lineItem->currency,
                            'quantity' => $lineItem->quantity,
                            'type' => $lineItem->type
                        ];
                    }
                }
                
                $diagnosticData['stripe_invoices'][] = $invoiceData;
            }
            
            // Process Cashier invoice data
            foreach ($cashierInvoices as $invoice) {
                $invoiceData = [
                    'id' => $invoice->id,
                    'number' => $invoice->number,
                    'date' => $invoice->date()->format('Y-m-d H:i:s'),
                    'status' => $invoice->status(), // "paid", "open", etc
                    'subtotal' => $invoice->subtotal(),
                    'subtotal_dollars' => number_format($invoice->subtotal() / 100, 2),
                    'tax' => $invoice->tax(),
                    'tax_dollars' => number_format($invoice->tax() / 100, 2),
                    'total' => $invoice->total(),
                    'total_dollars' => number_format($invoice->total() / 100, 2),
                    'raw_invoice' => json_encode($invoice),
                    'invoice_items' => []
                ];
                
                try {
                    // Try to get invoice items if available
                    foreach ($invoice->invoiceItems() as $item) {
                        $invoiceData['invoice_items'][] = [
                            'id' => $item->id,
                            'description' => $item->description,
                            'amount' => $item->amount,
                            'amount_dollars' => number_format($item->amount / 100, 2),
                            'currency' => $item->currency,
                            'quantity' => $item->quantity ?? 1,
                        ];
                    }
                } catch (\Exception $e) {
                    $invoiceData['invoice_items_error'] = $e->getMessage();
                }
                
                $diagnosticData['cashier_invoices'][] = $invoiceData;
            }
            
            return response()->json($diagnosticData);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Debug method to test invoice creation directly
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function testInvoiceCreation(Request $request)
    {
        $user = Auth::user();
        $amount = 1000; // $10.00
        
        try {
            // Create Stripe customer if one doesn't exist yet
            if (!$user->stripe_id) {
                $user->createAsStripeCustomer();
            }
            
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            
            // Create test invoice first
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => false, // Don't auto-finalize yet
                'collection_method' => 'send_invoice',
                'days_until_due' => 30,
                'description' => 'Test Invoice',
            ]);
            
            // Create test invoice item attached to the invoice
            $invoiceItem = $stripe->invoiceItems->create([
                'customer' => $user->stripe_id,
                'amount' => $amount,
                'currency' => 'usd',
                'description' => 'Test Invoice Item',
                'invoice' => $invoice->id, // Attach to the invoice
            ]);
            
            // Now finalize the invoice
            $finalizedInvoice = $stripe->invoices->finalizeInvoice($invoice->id);
            
            // Get the Stripe invoice directly to check
            $retrievedInvoice = $stripe->invoices->retrieve($invoice->id, [
                'expand' => ['lines.data']
            ]);
            
            return response()->json([
                'success' => true,
                'invoice_item' => $invoiceItem,
                'invoice' => $invoice,
                'finalized_invoice' => $finalizedInvoice,
                'retrieved_invoice' => $retrievedInvoice,
                'message' => 'Test invoice created successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
