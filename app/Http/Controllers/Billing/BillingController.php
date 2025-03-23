<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\CardException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BillingController extends Controller
{
    /**
     * Display the billing portal.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = Auth::user();
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        // Ensure we get the latest invoices from Stripe
        $this->syncInvoicesFromStripe($user);
        
        return view('billing.index', [
            'intent' => $user->createSetupIntent(),
            'hasPaymentMethod' => $user->hasDefaultPaymentMethod(),
            'paymentMethod' => $user->hasDefaultPaymentMethod() ? $user->defaultPaymentMethod() : null,
            'invoices' => $user->invoices()->take(5), // Show only 5 most recent invoices
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
            
            return redirect()->route('billing.index')->with('success', 'Payment method updated successfully!');
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
        $amount = $request->input('amount') * 100; // Convert to cents
        $description = $request->input('description', 'Payment for services');
        $paymentMethod = $request->input('payment_method');

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
            
            // First create an invoice item
            $invoiceItem = $stripe->invoiceItems->create([
                'customer' => $user->stripe_id,
                'amount' => $amount,
                'currency' => 'usd',
                'description' => $description,
            ]);
            
            // Create the invoice
            $invoice = $stripe->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => true, // auto-finalize the invoice
                'description' => $description,
                'collection_method' => 'charge_automatically',
                'metadata' => [
                    'source' => 'one_time_payment',
                    'user_id' => $user->id
                ]
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
                'description' => $description
            ]);
            
            // Refresh the user's invoices from Stripe
            $this->syncInvoicesFromStripe($user);
            
            return redirect()->back()->with('success', 'Payment processed successfully!');
        } catch (\Stripe\Exception\CardException $e) {
            \Log::error('Card error during payment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return redirect()->back()->withErrors(['error' => 'Card error: ' . $e->getMessage()]);
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            \Log::error('Invalid request during payment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return redirect()->back()->withErrors(['error' => 'Invalid request: ' . $e->getMessage()]);
        } catch (\Laravel\Cashier\Exceptions\IncompletePayment $exception) {
            \Log::info('Incomplete payment requiring authentication', [
                'user_id' => $user->id,
                'payment_id' => $exception->payment->id
            ]);
            // Redirect to the payment confirmation page if additional authentication is needed
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('billing.index')]
            );
        } catch (\Exception $e) {
            \Log::error('Error processing payment', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withErrors(['error' => 'Error processing payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Display a specific invoice.
     *
     * @param  string  $invoiceId
     * @return \Illuminate\Http\Response
     */
    public function downloadInvoice($invoiceId)
    {
        return Auth::user()->downloadInvoice($invoiceId, [
            'vendor' => config('app.name'),
            'product' => 'Service Payment',
        ]);
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
            return $user->redirectToBillingPortal(route('billing.index'));
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
            
            return redirect()->route('billing.index')->withErrors(['error' => $errorMessage]);
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
     * Display all invoices for the user.
     *
     * @return \Illuminate\View\View
     */
    public function invoices()
    {
        $user = Auth::user();
        
        // Create Stripe customer if one doesn't exist yet
        if (!$user->stripe_id) {
            $user->createAsStripeCustomer();
        }
        
        // Ensure we get the latest invoices from Stripe
        $this->syncInvoicesFromStripe($user);
        
        return view('billing.invoices', [
            'invoices' => $user->invoices(),
        ]);
    }
    
    /**
     * Display a specific invoice details.
     *
     * @param  string  $invoiceId
     * @return \Illuminate\View\View
     */
    public function showInvoice($invoiceId)
    {
        $user = Auth::user();
        
        try {
            // Retrieve the specific invoice from Stripe
            $invoice = $user->findInvoice($invoiceId);
            
            if (!$invoice) {
                return redirect()->route('billing.invoices')
                    ->withErrors(['error' => 'Invoice not found.']);
            }
            
            return view('billing.invoice-details', [
                'invoice' => $invoice,
            ]);
        } catch (\Exception $e) {
            return redirect()->route('billing.invoices')
                ->withErrors(['error' => 'Unable to retrieve invoice details: ' . $e->getMessage()]);
        }
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
                'stripe_id' => $user->stripe_id,
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
            $rawInvoices = $stripe->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
                'expand' => ['data.charge', 'data.payment_intent']
            ]);
            
            // Get Cashier-processed invoices
            $cashierInvoices = $user->invoices();
            
            return view('billing.diagnostic', [
                'rawInvoiceData' => $rawInvoices->data,
                'cashierInvoiceCount' => count($cashierInvoices),
                'stripeId' => $user->stripe_id
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id
            ], 500);
        }
    }
}
