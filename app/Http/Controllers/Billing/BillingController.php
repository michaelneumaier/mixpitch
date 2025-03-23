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
            $stripeInvoices = $user->stripeClient()->invoices->all([
                'customer' => $user->stripe_id,
                'limit' => 100, // Get a reasonable number of invoices
            ]);
            
            // The invoices will be automatically synced by Laravel Cashier
            // when the user->invoices() method is called
            
        } catch (\Exception $e) {
            \Log::error('Failed to sync invoices from Stripe: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'stripe_id' => $user->stripe_id,
            ]);
        }
    }
}
