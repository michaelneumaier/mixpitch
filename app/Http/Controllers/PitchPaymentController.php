<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\Project;
use App\Services\InvoiceService;
use App\Services\PitchWorkflowService;
use App\Http\Requests\Pitch\ProcessPitchPaymentRequest;
use App\Helpers\RouteHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Stripe\Exception\CardException;

class PitchPaymentController extends Controller
{
    protected $invoiceService;
    protected $pitchWorkflowService;

    public function __construct(InvoiceService $invoiceService, PitchWorkflowService $pitchWorkflowService)
    {
        $this->invoiceService = $invoiceService;
        $this->pitchWorkflowService = $pitchWorkflowService;
    }

    /**
     * Show the payment overview page.
     * Authorization and status checks are similar, but simplified as payment processing logic moves.
     *
     * @param Project $project
     * @param Pitch $pitch
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function projectPitchOverview(Project $project, Pitch $pitch)
    {
        // Basic Authorization: Ensure user is project owner
        if (Auth::id() !== $project->user_id) {
            abort(403, 'Only the project owner can view the payment page.');
        }

        // Check pitch status consistency
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch does not belong to this project.');
        }

        // Redirect if already paid/processing or not applicable
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_PAID || $pitch->payment_status === Pitch::PAYMENT_STATUS_PROCESSING) {
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchReceiptUrl($pitch));
        }
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_NOT_REQUIRED || $project->budget <= 0) {
            // Use RouteHelpers for URL generation
             return redirect(RouteHelpers::pitchUrl($pitch))
                ->with('info', 'This project does not require payment.');
        }
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchUrl($pitch))
                ->with('error', 'Payment can only be processed for completed pitches.');
        }

        // Fetch payment intent if needed for Stripe Elements
        // $intent = Auth::user()->createSetupIntent(); // Example, adjust as needed

        return view('pitches.payment.overview', [
            'pitch' => $pitch,
            'project' => $project,
            'paymentAmount' => $project->budget,
           // 'intent' => $intent // Pass intent to view
        ]);
    }

    /**
     * Process the payment for a pitch using the refactored services.
     *
     * @param ProcessPitchPaymentRequest $request
     * @param Project $project
     * @param Pitch $pitch
     * @return \Illuminate\Http\RedirectResponse
     */
    public function projectPitchProcess(ProcessPitchPaymentRequest $request, Project $project, Pitch $pitch)
    {
        // Authorization and validation are handled by ProcessPitchPaymentRequest
        $paymentMethodId = $request->validated('payment_method_id') ?? $request->validated('payment_method');
        $stripeInvoice = null; // To store the created/retrieved Stripe Invoice object
        $stripeInvoiceId = null; // To store the ID for logging/workflow
        
        // Log the request input for debugging
        Log::info('Processing pitch payment request', [
            'pitch_id' => $pitch->id,
            'project_id' => $project->id,
            'payment_method_id' => $paymentMethodId,
            'all_inputs' => $request->all()
        ]);
        
        // Ensure we have a payment method
        if (empty($paymentMethodId)) {
            Log::error('Missing payment method id', [
                'pitch_id' => $pitch->id,
                'request_data' => $request->validated()
            ]);
            
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchPaymentUrl($pitch))
                ->with('error', 'No payment method selected. Please try again.');
        }

        try {
            // Consider setting status to PROCESSING here if desired UI feedback is needed
             // $pitch->payment_status = Pitch::PAYMENT_STATUS_PROCESSING;
             // $pitch->save(); // Be careful about rollbacks on failure

            // Step 1: Create/Retrieve the Stripe Invoice via InvoiceService
            // Assuming createPitchInvoice returns the Stripe Invoice object on success
            // And includes pitch_id, project_id metadata
            Log::info('Attempting to create/retrieve invoice for pitch.', ['pitch_id' => $pitch->id]);
            $invoiceResult = $this->invoiceService->createPitchInvoice($pitch, $project->budget, $paymentMethodId);
            
            // Check if the service indicates success and returns the invoice object
             if (!isset($invoiceResult['success']) || !$invoiceResult['success'] || !isset($invoiceResult['invoice'])) {
                 throw new \Exception($invoiceResult['error'] ?? 'Failed to create or retrieve invoice from InvoiceService.');
             }
             $stripeInvoice = $invoiceResult['invoice'];
             $stripeInvoiceId = $stripeInvoice->id; // Get ID for later use
             Log::info('Invoice created/retrieved successfully.', ['pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId]);


            // Step 2: Process the payment for the created invoice via InvoiceService
             Log::info('Attempting to process payment for invoice.', ['pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId]);
            $paymentResult = $this->invoiceService->processInvoicePayment($stripeInvoice, $paymentMethodId);

            // Check if the service indicates success
            if (!isset($paymentResult['success']) || !$paymentResult['success']) {
                // Throw specific exceptions if InvoiceService provides them, otherwise generic
                 $errorMessage = $paymentResult['error'] ?? 'Payment processing failed via InvoiceService.';
                 if (isset($paymentResult['exception']) && $paymentResult['exception'] instanceof CardException) {
                     throw $paymentResult['exception']; // Re-throw CardException
                 }
                 throw new \Exception($errorMessage);
            }
             Log::info('Payment processed successfully via InvoiceService.', ['pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId]);


            // Step 3: Mark pitch as paid using PitchWorkflowService
            $this->pitchWorkflowService->markPitchAsPaid($pitch, $stripeInvoiceId /*, optional charge id */);

            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchReceiptUrl($pitch))
                             ->with('success', 'Payment processed successfully!');

        } catch (CardException $e) {
            Log::error('Stripe CardException during pitch payment processing.', [
                'pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId, 'error' => $e->getMessage()
            ]);
            // Mark payment as failed
            $this->pitchWorkflowService->markPitchPaymentFailed($pitch, $stripeInvoiceId, $e->getMessage());
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchPaymentUrl($pitch))
                             ->with('error', 'Card error: ' . $e->getMessage());

        } catch (IncompletePayment $exception) {
            Log::info('Incomplete payment requiring authentication.', [
                'pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId, 'payment_id' => $exception->payment->id
            ]);
            
             // Mark payment as failed for now, or keep as processing? Depends on flow.
            // $this->pitchWorkflowService->markPitchPaymentFailed($pitch, $stripeInvoiceId, 'Requires authentication');
            
            // Use RouteHelpers for URL generation for the redirect parameter
             return redirect()->route(
                 'cashier.payment',
                 [$exception->payment->id, 'redirect' => RouteHelpers::pitchReceiptUrl($pitch)]
             );

        } catch (\Exception $e) {
            Log::error('General Exception during pitch payment processing.', [
                'pitch_id' => $pitch->id, 'invoice_id' => $stripeInvoiceId, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString() // Limit trace in production
            ]);
            // Mark payment as failed
            $this->pitchWorkflowService->markPitchPaymentFailed($pitch, $stripeInvoiceId, $e->getMessage());
            // Use RouteHelpers for URL generation
            return redirect(RouteHelpers::pitchPaymentUrl($pitch))
                             ->with('error', 'An unexpected error occurred during payment processing. Please try again.');
        }
    }

    /**
     * Show the payment receipt.
     *
     * @param Project $project
     * @param Pitch $pitch
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function projectPitchReceipt(Project $project, Pitch $pitch)
    {
        // Authorization: Ensure user is project owner OR pitch creator
        if (Auth::id() !== $project->user_id && Auth::id() !== $pitch->user_id) {
            abort(403, 'You are not authorized to view this receipt.');
        }
        if ($pitch->project_id !== $project->id) {
             abort(404, 'Pitch does not belong to this project.');
        }

        // Check if payment was required and completed/failed
        if ($pitch->payment_status === Pitch::PAYMENT_STATUS_PENDING || $pitch->payment_status === Pitch::PAYMENT_STATUS_NOT_REQUIRED) {
            // Use RouteHelpers for URL generation
             return redirect(RouteHelpers::pitchUrl($pitch))
                ->with('info', 'Payment is not yet completed or is not required for this pitch.');
        }


        // Retrieve the Stripe Invoice object via InvoiceService if ID exists
        $stripeInvoice = null;
        if ($pitch->final_invoice_id) {
            try {
                $stripeInvoice = $this->invoiceService->getInvoice($pitch->final_invoice_id);
            } catch (\Exception $e) {
                Log::error('Failed to retrieve Stripe invoice for receipt view.', [
                    'pitch_id' => $pitch->id,
                    'invoice_id' => $pitch->final_invoice_id,
                    'error' => $e->getMessage()
                ]);
                // Don't prevent showing the receipt, just log the error
            }
        }

        // Add logging to debug missing payment amount
        Log::info('Showing payment receipt', [
            'pitch_id' => $pitch->id,
            'payment_status' => $pitch->payment_status,
            'payment_amount' => $pitch->payment_amount,
            'project_budget' => $project->budget,
            'has_invoice' => !is_null($stripeInvoice)
        ]);

        return view('pitches.payment.receipt', [
            'pitch' => $pitch,
            'project' => $project,
            'stripeInvoice' => $stripeInvoice, // Pass the retrieved Stripe invoice object (or null)
            'invoice' => $stripeInvoice, // Also pass as 'invoice' to match what the view expects
            'viewAllInvoicesUrl' => route('billing.invoices') // Use the correct route name with the 'billing.' prefix
        ]);
    }

    // Deprecated methods - map routes to new ones if necessary
    public function overview(Pitch $pitch) {
        return $this->projectPitchOverview($pitch->project, $pitch);
    }
    public function process(Request $request, Pitch $pitch) {
        // This needs more careful mapping if directly called, ideally update routes
         // For now, redirect or abort might be safest
         abort(410, 'This payment processing route is deprecated. Please use the new route.');
    }
     public function receipt(Pitch $pitch) {
        return $this->projectPitchReceipt($pitch->project, $pitch);
    }
}