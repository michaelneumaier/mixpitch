<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Cashier\Cashier;
use App\Models\Project;

class PitchPaymentController extends Controller
{
    /**
     * Show the payment overview page for a pitch
     *
     * @param Pitch $pitch
     * @return \Illuminate\View\View
     */
    public function overview(Pitch $pitch)
    {
        // Check if the authenticated user is the project owner
        if (Auth::id() !== $pitch->project->user_id) {
            abort(403, 'Only the project owner can process payments.');
        }

        // Check if the pitch is in completed status
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            return redirect()->route('projects.pitches.show', [
                'project' => $pitch->project,
                'pitch' => $pitch
            ])->with('error', 'Only completed pitches can be paid.');
        }

        // Check if payment is already processed or in processing
        if (in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING])) {
            return redirect()->route('projects.pitches.payment.receipt', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]);
        }

        // Free projects don't require payment
        $isFreeProject = ($pitch->project->budget == 0);
        if ($isFreeProject) {
            return redirect()->route('projects.pitches.show', [
                'project' => $pitch->project,
                'pitch' => $pitch
            ])->with('info', 'This is a free project and does not require payment.');
        }

        return view('pitches.payment.overview', [
            'pitch' => $pitch,
            'project' => $pitch->project,
            'paymentAmount' => $pitch->project->budget,
        ]);
    }

    /**
     * Process the payment for a pitch
     *
     * @param Request $request
     * @param Pitch $pitch
     * @return \Illuminate\Http\RedirectResponse
     */
    public function process(Request $request, Pitch $pitch)
    {
        // Check if the authenticated user is the project owner
        if (Auth::id() !== $pitch->project->user_id) {
            abort(403, 'Only the project owner can process payments.');
        }

        // Check if the pitch is in completed status
        if ($pitch->status !== Pitch::STATUS_COMPLETED) {
            return redirect()->route('projects.pitches.show', [
                'project' => $pitch->project,
                'pitch' => $pitch
            ])->with('error', 'Only completed pitches can be paid.');
        }

        // Check if payment is already processed or in processing
        if (in_array($pitch->payment_status, [Pitch::PAYMENT_STATUS_PAID, Pitch::PAYMENT_STATUS_PROCESSING])) {
            return redirect()->route('projects.pitches.payment.receipt', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug]);
        }

        // Validate the payment method
        if (!$request->has('payment_method')) {
            return redirect()->route('projects.pitches.payment.overview', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                ->with('error', 'No payment method was provided.');
        }

        try {
            // Start by updating the pitch status to processing
            $pitch->update([
                'payment_status' => Pitch::PAYMENT_STATUS_PROCESSING,
                'payment_amount' => $pitch->project->budget
            ]);

            // Use the centralized invoice service
            $invoiceService = app(InvoiceService::class);
            $result = $invoiceService->createPitchInvoice(
                $pitch, 
                $pitch->project->budget, 
                $request->input('payment_method')
            );
            
            if (!$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to create invoice');
            }
            
            // Process the payment
            $paymentResult = $invoiceService->processInvoicePayment(
                $result['invoice'], 
                $request->input('payment_method')
            );
            
            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['error'] ?? 'Failed to process payment');
            }

            $payResult = $paymentResult['paymentResult'];
            
            // Update pitch with the invoice ID
            $pitch->update([
                'payment_status' => Pitch::PAYMENT_STATUS_PAID,
                'payment_completed_at' => now(),
                'final_invoice_id' => $result['invoice']->id // Store actual Stripe invoice ID
            ]);
            
            // Add payment event to the audit trail
            $pitch->events()->create([
                'event_type' => 'payment_processed',
                'comment' => 'Payment of $' . number_format($pitch->project->budget, 2) . ' was processed successfully.',
                'created_by' => auth()->id(),
                'user_id' => auth()->id(),
                'metadata' => [
                    'invoice_id' => $result['invoice']->id,
                    'amount' => $pitch->project->budget
                ]
            ]);
            
            // Send final payment notification to the pitch creator
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->notifyPaymentProcessed(
                    $pitch, 
                    $pitch->project->budget,
                    $result['invoice']->id
                );
            } catch (\Exception $e) {
                \Log::error('Failed to send payment notification', [
                    'pitch_id' => $pitch->id,
                    'error' => $e->getMessage()
                ]);
                // Continue process even if notification fails
            }
            
            // Log the successful payment
            \Log::info('Pitch payment processed successfully', [
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id(),
                'invoice_id' => $result['invoice']->id,
                'amount' => $pitch->project->budget,
                'payment_result' => [
                    'status' => $payResult->status,
                    'total' => $payResult->total,
                    'amount_paid' => $payResult->amount_paid
                ]
            ]);

            return redirect()->route('projects.pitches.payment.receipt', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                ->with('success', 'Payment processed successfully!');
            
        } catch (\Stripe\Exception\CardException $e) {
            \Log::error('Card error during pitch payment', [
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);
            
            // Update payment status to failed
            $pitch->update([
                'payment_status' => Pitch::PAYMENT_STATUS_FAILED
            ]);
            
            return redirect()->route('projects.pitches.payment.overview', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                ->with('error', 'Card error: ' . $e->getMessage());
                
        } catch (\Laravel\Cashier\Exceptions\IncompletePayment $exception) {
            // Handle 3D Secure authentication needs
            \Log::info('Incomplete payment requiring authentication', [
                'pitch_id' => $pitch->id,
                'payment_id' => $exception->payment->id
            ]);
            
            return redirect()->route(
                'cashier.payment',
                [$exception->payment->id, 'redirect' => route('projects.pitches.payment.receipt', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])]
            );
            
        } catch (\Exception $e) {
            // Log the error
            \Log::error('Payment processing failed', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Update payment status to failed
            $pitch->update([
                'payment_status' => Pitch::PAYMENT_STATUS_FAILED
            ]);

            return redirect()->route('projects.pitches.payment.overview', ['project' => $pitch->project->slug, 'pitch' => $pitch->slug])
                ->with('error', 'Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Show the payment receipt
     *
     * @param Pitch $pitch
     * @return \Illuminate\View\View
     */
    public function receipt(Pitch $pitch)
    {
        // Check if the authenticated user is authorized
        if (Auth::id() !== $pitch->project->user_id && Auth::id() !== $pitch->user_id) {
            abort(403, 'You are not authorized to view this receipt.');
        }

        // Retrieve the actual invoice from Stripe using our invoice service
        $invoiceService = app(InvoiceService::class);
        $invoice = null;
        
        if ($pitch->final_invoice_id) {
            $invoice = $invoiceService->getInvoice($pitch->final_invoice_id);
        }

        return view('pitches.payment.receipt', [
            'pitch' => $pitch,
            'project' => $pitch->project,
            'invoice' => $invoice,
            // Include a link to the billing history
            'viewAllInvoicesUrl' => route('billing.invoices')
        ]);
    }

    /**
     * Show the payment overview page with the new URL pattern
     */
    public function projectPitchOverview(Project $project, Pitch $pitch)
    {
        // Verify the pitch belongs to the specified project
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }
        
        return $this->overview($pitch);
    }
    
    /**
     * Process the payment with the new URL pattern
     */
    public function projectPitchProcess(Request $request, Project $project, Pitch $pitch)
    {
        // Verify the pitch belongs to the specified project
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }
        
        return $this->process($request, $pitch);
    }
    
    /**
     * Show the receipt with the new URL pattern
     */
    public function projectPitchReceipt(Project $project, Pitch $pitch)
    {
        // Verify the pitch belongs to the specified project
        if ($pitch->project_id !== $project->id) {
            abort(404, 'Pitch not found for this project');
        }
        
        return $this->receipt($pitch);
    }
}