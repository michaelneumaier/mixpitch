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
            
            // Instead of using charge(), create a payment intent with a return URL
            $stripeCharge = $user->pay(
                $amount, 
                [
                    'payment_method' => $paymentMethod,
                    'description' => $description,
                    'return_url' => route('billing.index'),
                    'confirm' => true, // Auto-confirm the payment
                ]
            );
            
            // Explicitly create an invoice for this payment
            $this->createInvoiceForPayment($user, $amount, $description, $stripeCharge);
            
            return redirect()->back()->with('success', 'Payment processed successfully!');
        } catch (IncompletePayment $exception) {
            // Redirect to the payment confirmation page if additional authentication is needed
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('billing.index')]
            );
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
    
    /**
     * Create an invoice record for a processed payment
     *
     * @param \App\Models\User $user
     * @param int $amount
     * @param string $description
     * @param mixed $paymentIntent
     * @return void
     */
    protected function createInvoiceForPayment($user, $amount, $description, $paymentIntent)
    {
        try {
            // Use the Stripe API to create an invoice item
            $user->stripeClient()->invoiceItems->create([
                'customer' => $user->stripe_id,
                'amount' => $amount,
                'currency' => 'usd',
                'description' => $description,
            ]);
            
            // Create and finalize the invoice
            $invoice = $user->stripeClient()->invoices->create([
                'customer' => $user->stripe_id,
                'auto_advance' => true, // Auto-finalize the invoice
            ]);
            
            // Mark the invoice as paid with the payment intent
            if ($paymentIntent && isset($paymentIntent->id)) {
                $user->stripeClient()->invoices->pay($invoice->id, [
                    'paid_out_of_band' => false,
                    'payment_method' => $paymentIntent->payment_method,
                ]);
            }
            
            // Log the successful invoice creation
            \Log::info('Invoice created for payment', [
                'user_id' => $user->id,
                'amount' => $amount / 100,
                'invoice_id' => $invoice->id,
            ]);
            
        } catch (\Exception $e) {
            // Log any errors but don't interrupt the payment flow
            \Log::error('Failed to create invoice for payment: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'amount' => $amount / 100,
            ]);
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
            
            // First, sync any charges made to the customer
            $this->syncStripeCharges($user);
            
            // Then get all invoices
            $stripeInvoices = $user->stripeClient()->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 100, // Get a reasonable number of invoices
            ]);
            
            // Force cashier to refresh invoice data
            $invoices = $user->invoices(true); // Pass true to force refresh from Stripe
            
            \Log::info('Synced invoices from Stripe', [
                'user_id' => $user->id,
                'invoice_count' => count($invoices),
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync invoices from Stripe: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id,
            ]);
        }
    }
    
    /**
     * Sync charges from Stripe that might not be associated with invoices.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    protected function syncStripeCharges($user)
    {
        try {
            // Get all charges for this customer
            $charges = $user->stripeClient()->charges->all([
                'customer' => $user->stripe_id,
                'limit' => 100,
            ]);
            
            // For each charge that doesn't have an invoice, create one
            foreach ($charges->data as $charge) {
                // Skip charges that already have an invoice
                if (!empty($charge->invoice)) {
                    continue;
                }
                
                // Skip charges that aren't successful
                if ($charge->status !== 'succeeded') {
                    continue;
                }
                
                try {
                    // Create invoice item
                    $user->stripeClient()->invoiceItems->create([
                        'customer' => $user->stripe_id,
                        'amount' => $charge->amount,
                        'currency' => $charge->currency,
                        'description' => $charge->description ?? 'Charge ' . $charge->id,
                    ]);
                    
                    // Create and finalize the invoice
                    $invoice = $user->stripeClient()->invoices->create([
                        'customer' => $user->stripe_id,
                        'auto_advance' => true,
                    ]);
                    
                    // Mark it as paid from this charge
                    $user->stripeClient()->invoices->pay($invoice->id, [
                        'paid_out_of_band' => true,
                    ]);
                    
                    \Log::info('Created invoice for orphaned charge', [
                        'user_id' => $user->id,
                        'charge_id' => $charge->id,
                        'invoice_id' => $invoice->id,
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to create invoice for charge: ' . $e->getMessage(), [
                        'user_id' => $user->id,
                        'charge_id' => $charge->id,
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to sync charges from Stripe: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id,
            ]);
        }
    }
}
