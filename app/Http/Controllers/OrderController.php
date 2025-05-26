<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\ServicePackage;
use App\Models\User;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Cashier\Exceptions\IncompletePayment;
use App\Models\OrderEvent;
use App\Services\OrderWorkflowService;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\OrderFile;

class OrderController extends Controller
{
    protected $invoiceService;
    protected $orderWorkflowService;

    public function __construct(InvoiceService $invoiceService, OrderWorkflowService $orderWorkflowService)
    {
        $this->invoiceService = $invoiceService;
        $this->orderWorkflowService = $orderWorkflowService;
    }

    /**
     * Store a newly created order and initiate payment.
     *
     * @param Request $request
     * @param ServicePackage $package
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Cashier\Checkout
     */
    public function store(Request $request, ServicePackage $package)
    {
        /** @var User $client */
        $client = Auth::user();

        // Authorization: Ensure package is published and client is not the producer
        if (!$package->is_published) {
            return back()->with('error', 'This service package is not available.');
        }
        if ($client->id === $package->user_id) {
            return back()->with('error', 'You cannot order your own service package.');
        }

        // Ensure producer can receive payments (Stripe connected?)
        if (!$package->user->hasDefaultPaymentMethod() && !$package->user->stripe_account_id) {
             // We might need a more robust check, e.g., checking account capabilities
            
             // Temporarily allow ordering even if producer Stripe isn't fully set up,
             // but ideally this should block or warn.
             
             // Log::warning("Attempted to order package from producer without Stripe setup.", ['producer_id' => $package->user_id]);
             // return back()->with('error', 'The provider cannot accept payments at this time.');
        }

        $order = null;
        try {
            DB::beginTransaction();

            // Create the Order
            $order = Order::create([
                'service_package_id' => $package->id,
                'client_user_id' => $client->id,
                'producer_user_id' => $package->user_id,
                'status' => Order::STATUS_PENDING_PAYMENT,
                'price' => $package->price,
                'currency' => $package->currency,
                'payment_status' => Order::PAYMENT_STATUS_PENDING,
                // revision_count defaults to 0
            ]);

            // Create Invoice (initially unpaid)
            $invoice = $this->invoiceService->createInvoiceForOrder($order);
            $order->invoice_id = $invoice->id;
            $order->save();

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            
            report($e);
            return redirect()->route('public.services.index')->with('error', 'Could not create the order. Please try again later.');
        }

        // Initiate Stripe Checkout
        try {
            $checkoutOptions = [
                'success_url' => route('dashboard') . '?order_success=1&order_id={CHECKOUT_SESSION_ID}', // Redirect to dashboard or order page
                'cancel_url' => route('public.services.index') . '?order_cancel=1', // Redirect back to services
                'metadata' => [
                    'order_id' => $order->id,
                    'invoice_id' => $order->invoice_id,
                    'service_package_id' => $package->id,
                    'client_user_id' => $client->id,
                    'producer_user_id' => $package->user_id,
                    'description' => 'Payment for Order #' . $order->id . ': ' . $package->title,
                ],
                // If using Stripe Connect direct charges:
                 'payment_intent_data' => [
                     'application_fee_amount' => $this->calculateApplicationFee($order->price),
                     'transfer_data' => [
                         'destination' => $package->user->stripe_account_id,
                     ],
                 ],
            ];

            // Use checkout builder for one-time payment
            return $client->checkout([$package->price * 100 => 1], // Price in cents
                $checkoutOptions
            );

        } catch (\Exception $e) {
             report($e);
             // Clean up the order if payment initiation fails?
             // $order->delete(); // Or mark as failed?
             return redirect()->route('public.services.index')->with('error', 'Could not initiate payment. Please try again or contact support.');
        }
    }
    
    /**
     * Calculate the application fee (platform commission).
     * Example: 10% fee
     * 
     * @param float $price
     * @return int Fee amount in the smallest currency unit (e.g., cents)
     */
    private function calculateApplicationFee(float $price): int
    {
        // TODO: Make fee configurable
        $feePercentage = 0.10; // 10%
        return (int) round(($price * $feePercentage) * 100);
    }

    /**
     * Display a listing of the user's orders (as client or producer).
     */
    public function index()
    {
        $user = Auth::user();

        $orders = Order::where(function ($query) use ($user) {
                $query->where('client_user_id', $user->id)
                      ->orWhere('producer_user_id', $user->id);
            })
            ->with(['servicePackage', 'client', 'producer']) // Eager load relationships
            ->latest()
            ->paginate(15);

        return view('orders.index', compact('orders'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        // Authorize that the user can view this order (client or producer)
        $this->authorize('view', $order);

        // Eager load necessary details for the view
        $order->load(['servicePackage', 'client', 'producer', 'files', 'events.user']);

        return view('orders.show', compact('order'));
    }

    /**
     * Handle submission of order requirements by the client.
     */
    public function submitRequirements(Request $request, Order $order)
    {
        // Authorization (Uses OrderPolicy::submitRequirements)
        $this->authorize('submitRequirements', $order);

        // Validation
        $validated = $request->validate([
            'requirements' => 'required|string|max:10000', // Adjust max length as needed
        ]);

        try {
            $this->orderWorkflowService->submitRequirements(
                $order,
                Auth::user(),
                $validated['requirements']
            );

            return redirect()->route('orders.show', $order)->with('success', 'Requirements submitted successfully.');

        } catch (InvalidStatusTransitionException | AuthorizationException $e) {
            // Redirect back with specific error if authorization/status fails
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('orders.show', $order)->with('error', 'An unexpected error occurred while submitting requirements.');
        }
    }

    /**
     * Handle delivery of the order by the producer.
     */
    public function deliverOrder(Request $request, Order $order)
    {
        // Authorization (Uses OrderPolicy::deliverOrder)
        $this->authorize('deliverOrder', $order);

        // Validation
        $validated = $request->validate([
            'delivery_files' => 'required|array|min:1', 
            'delivery_files.*' => 'required|file|mimes:jpg,jpeg,png,pdf,zip,mp3,wav,txt,doc,docx|max:20480', // Example validation: adjust as needed
            'delivery_message' => 'nullable|string|max:5000',
        ]);

        $uploadedFilesData = [];
        try {
            // Handle File Uploads
            if ($request->hasFile('delivery_files')) {
                foreach ($request->file('delivery_files') as $file) {
                    // Generate a unique path for the file
                    $filePath = $file->store('orders/' . $order->id . '/deliveries', 's3'); // Store in s3

                    $uploadedFilesData[] = [
                        'path' => $filePath,
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'size' => $file->getSize(),
                    ];
                }
            }
            
            if (empty($uploadedFilesData)) {
                 return back()->with('error', 'File upload failed or no files were provided.')->withInput();
            }

            // Call the workflow service
            $this->orderWorkflowService->deliverOrder(
                $order,
                Auth::user(),
                $uploadedFilesData,
                $validated['delivery_message'] ?? null
            );

            return redirect()->route('orders.show', $order)->with('success', 'Order delivered successfully.');

        } catch (InvalidStatusTransitionException | AuthorizationException | \InvalidArgumentException $e) {
            // Redirect back with specific error
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            report($e);
            // Attempt to clean up uploaded files if service fails unexpectedly
            foreach ($uploadedFilesData as $fileData) {
                Storage::disk('s3')->delete($fileData['path']);
            }
            return redirect()->route('orders.show', $order)->with('error', 'An unexpected error occurred during delivery. Please try again.')->withInput();
        }
    }

    /**
     * Handle the client requesting revisions for the order.
     */
    public function requestRevision(Request $request, Order $order)
    {
        // Authorization (Uses OrderPolicy::requestRevision)
        $this->authorize('requestRevision', $order);

        // Validation
        $validated = $request->validate([
            'revision_feedback' => 'required|string|max:10000', // Adjust max length as needed
        ]);

        try {
            $this->orderWorkflowService->requestRevision(
                $order,
                Auth::user(),
                $validated['revision_feedback']
            );

            return redirect()->route('orders.show', $order)->with('success', 'Revision requested successfully.');

        } catch (\LogicException $e) {
            // For revision limit exceptions specifically, return 403 Forbidden
            if (strpos($e->getMessage(), 'No more revisions allowed') !== false) {
                return response()->json(['error' => $e->getMessage()], 403);
            }
            
            // Other logic exceptions (business rule violations) redirect with error
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage())->withInput();
        } catch (InvalidStatusTransitionException | AuthorizationException | \InvalidArgumentException $e) {
            // Redirect back with specific error
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('orders.show', $order)->with('error', 'An unexpected error occurred while requesting revisions.')->withInput();
        }
    }

    /**
     * Handle the client accepting the order delivery.
     */
    public function acceptDelivery(Request $request, Order $order)
    {
        // Authorization (Uses OrderPolicy::acceptDelivery)
        $this->authorize('acceptDelivery', $order);

        // No validation needed for this action currently

        try {
            $this->orderWorkflowService->acceptDelivery(
                $order,
                Auth::user()
            );

            return redirect()->route('orders.show', $order)->with('success', 'Delivery accepted and order completed!');

        } catch (InvalidStatusTransitionException | AuthorizationException $e) {
            // Redirect back with specific error
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('orders.show', $order)->with('error', 'An unexpected error occurred while accepting the delivery.');
        }
    }

    /**
     * Handle the cancellation of an order.
     */
    public function cancelOrder(Request $request, Order $order)
    {
        // Authorization (Uses OrderPolicy::cancelOrder)
        $this->authorize('cancelOrder', $order);

        // Validation
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|max:2000', // Reason is required
        ]);

        try {
            $this->orderWorkflowService->cancelOrder(
                $order,
                Auth::user(),
                $validated['cancellation_reason']
            );

            $redirectRoute = route('orders.show', $order);
            // Optionally redirect to index if preferred after cancellation
            // $redirectRoute = route('orders.index');

            return redirect($redirectRoute)->with('success', 'Order cancelled successfully.');

        } catch (InvalidStatusTransitionException | AuthorizationException | \InvalidArgumentException $e) {
            // Redirect back with specific error
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('orders.show', $order)->with('error', 'An unexpected error occurred while cancelling the order.')->withInput();
        }
    }

    /**
     * Posts a message to the order's activity log.
     */
    public function postMessage(Request $request, Order $order)
    {
        // Authorization (Uses OrderPolicy::postMessage)
        $this->authorize('postMessage', $order);

        // Validation
        $validated = $request->validate([
            'message' => 'required|string|max:5000', // Adjust max length as needed
        ]);

        try {
            $this->orderWorkflowService->postMessage(
                $order,
                Auth::user(),
                $validated['message']
            );

            // No specific success message needed usually, just refresh/show the log
            return redirect()->route('orders.show', $order)->with('status', 'Message posted.'); // Use generic status

        } catch (InvalidStatusTransitionException | AuthorizationException | \InvalidArgumentException $e) {
            // Redirect back with specific error
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('orders.show', $order)->with('error', 'An unexpected error occurred while posting the message.')->withInput();
        }
    }

    /**
     * Handles downloading a file associated with an order.
     */
    public function downloadFile(Request $request, Order $order, OrderFile $file)
    {
        // Authorization: Ensure the user can view the order (implicitly allows file access for now)
        $this->authorize('view', $order);

        // Authorization: Double-check the file belongs to the order
        if ($file->order_id !== $order->id) {
            abort(404); // Or throw AuthorizationException
        }

        // Check if file exists in storage
        if (!Storage::disk('s3')->exists($file->file_path)) {
            Log::error('Order file not found in storage.', ['order_id' => $order->id, 'file_id' => $file->id, 'path' => $file->file_path]);
            return back()->with('error', 'File not found or access denied.'); // Or abort(404)
        }

        // Option 1: Stream the file directly
        // return Storage::disk('s3')->download($file->file_path, $file->file_name);

        // Option 2: Generate a temporary S3 URL (Preferred for potentially large files & browser handling)
        try {
            $temporaryUrl = Storage::disk('s3')->temporaryUrl(
                $file->file_path,
                now()->addMinutes(15), // Link expiry time
                [
                    'ResponseContentType' => $file->mime_type ?? 'application/octet-stream',
                    'ResponseContentDisposition' => 'attachment; filename="' . $file->file_name . '"',
                ]
            );

            return redirect($temporaryUrl);

        } catch (\Exception $e) {
            report($e);
            Log::error('Could not generate temporary URL for order file.', ['order_id' => $order->id, 'file_id' => $file->id, 'path' => $file->file_path]);
            return back()->with('error', 'Could not download the file. Please try again later.');
        }
    }
}
