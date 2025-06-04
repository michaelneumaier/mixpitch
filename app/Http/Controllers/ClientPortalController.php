<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Pitch; // Assuming one pitch per client project
use App\Services\PitchWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use App\Services\NotificationService; // Added for notifications
use Illuminate\Support\Facades\Storage;
use App\Models\PitchFile;
use App\Exceptions\Pitch\InvalidStatusTransitionException; // Already used, keep
use App\Models\User; // Needed for Cashier
use Laravel\Cashier\Exceptions\PaymentActionRequired; // Needed for Cashier
use Laravel\Cashier\Exceptions\IncompletePayment; // Needed for Cashier
use App\Services\FileManagementService; // Add FileManagementService

class ClientPortalController extends Controller
{
    protected PitchWorkflowService $pitchWorkflowService;
    protected NotificationService $notificationService;

    public function __construct(PitchWorkflowService $pitchWorkflowService, NotificationService $notificationService)
    {
        $this->pitchWorkflowService = $pitchWorkflowService;
        $this->notificationService = $notificationService;
    }

    /**
     * Show the client portal for a specific project.
     */
    public function show(Project $project, Request $request)
    {
        // Basic validation: Ensure it's a client management project
        if (!$project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Validate the signed URL (Laravel handles this via middleware, but double-check)
        if (!$request->hasValidSignature()) {
             abort(403, 'Invalid or expired link.');
        }

        // Retrieve the single pitch associated with this project
        // Eager load necessary relationships for the view
        $pitch = $project->pitches()
                         ->with(['user', 'files', 'events' => function ($query) {
                             // Order events, newest first
                             $query->orderBy('created_at', 'desc');
                         }, 'events.user']) // Load user relation for events if needed
                         ->first();

        if (!$pitch) {
            Log::error('Client portal accessed but no pitch found for project.', ['project_id' => $project->id]);
            abort(404, 'Project details could not be loaded.'); // Or show an error view
        }

        // Pass project, pitch, and maybe a way to regenerate the signed URL for actions
        return view('client_portal.show', [
            'project' => $project,
            'pitch' => $pitch,
        ]);
    }

    /**
     * Store a comment from the client.
     */
    public function storeComment(Project $project, Request $request)
    {
        if (!$project->isClientManagement()) abort(403);
        $pitch = $project->pitches()->firstOrFail(); // Assuming one pitch

        $request->validate(['comment' => 'required|string|max:5000']);

        try {
            // Add comment via PitchEvent
            $event = $pitch->events()->create([
                'event_type' => 'client_comment',
                'comment' => $request->input('comment'),
                'status' => $pitch->status,
                'created_by' => null, // Indicate client origin
                'metadata' => ['client_email' => $project->client_email] // Store identifier
            ]);

            // Notify producer
            $this->notificationService->notifyProducerClientCommented($pitch, $request->input('comment'));

            return back()->with('success', 'Comment added successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to store client comment', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['comment' => 'Could not add comment at this time.']);
        }
    }

    /**
     * Client approves the pitch submission.
     */
    public function approvePitch(Project $project, Request $request)
    {
        if (!$project->isClientManagement()) abort(403);
        $pitch = $project->pitches()->with('user')->firstOrFail(); // Eager load producer
        $producer = $pitch->user; // Get the producer user model

        if (!$producer) {
            Log::error('Producer not found for pitch during client approval', ['pitch_id' => $pitch->id, 'project_id' => $project->id]);
            return back()->withErrors(['approval' => 'Could not approve pitch due to an internal error (Producer not found).']);
        }

        try {
            // Check if payment is required
            $needsPayment = $pitch->payment_amount > 0 && $pitch->payment_status !== Pitch::PAYMENT_STATUS_PAID;

            if ($needsPayment) {
                Log::info('Client approval requires payment. Initiating Stripe Checkout.', ['pitch_id' => $pitch->id, 'amount' => $pitch->payment_amount]);
                
                // --- Initiate Stripe Checkout --- 
                $successUrl = URL::signedRoute('client.portal.view', ['project' => $project->id, 'checkout_status' => 'success']);
                $cancelUrl = URL::signedRoute('client.portal.view', ['project' => $project->id, 'checkout_status' => 'cancel']);

                $checkoutSession = $producer->checkout([
                    // Define line items
                    'price_data' => [
                        'currency' => config('cashier.currency'), // Assumes USD or configured default
                        'product_data' => [
                            'name' => 'Payment for Project: ' . $project->title,
                            'description' => 'Client payment for completed pitch deliverables.',
                        ],
                        // Amount should be in cents
                        'unit_amount' => (int) round($pitch->payment_amount * 100),
                    ],
                    'quantity' => 1,
                ], [
                    // Checkout Session options
                    'success_url' => $successUrl,
                    'cancel_url' => $cancelUrl,
                    // Pass pitch_id in metadata for webhook handler
                    'metadata' => [
                        'pitch_id' => $pitch->id,
                        'project_id' => $project->id,
                        'type' => 'client_pitch_payment', // Identify payment type
                    ],
                    // Consider payment_intent_data for application_fee_amount if platform takes a cut
                ]);

                // Redirect to Stripe Checkout page
                return redirect($checkoutSession->url);

            } else {
                // --- No Payment Required --- 
                Log::info('Client approval does not require payment. Proceeding with approval workflow.', ['pitch_id' => $pitch->id]);
                // The service method needs to handle authorization (status check)
                $this->pitchWorkflowService->clientApprovePitch($pitch, $project->client_email);
                return back()->with('success', 'Pitch approved successfully.');
            }

        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Client attempted to approve pitch with invalid status.', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'current_status' => $pitch->status]);
            return back()->withErrors(['approval' => $e->getMessage()]); // Show specific error
        } catch (PaymentActionRequired | IncompletePayment $e) {
            // Handle specific Cashier exceptions related to SCA or failed payments if needed
            // This might happen if checkout() is used differently, less likely with direct redirect
            Log::error('Cashier payment exception during client approval checkout initiation.', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['approval' => 'Payment processing failed: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Client failed to approve pitch or initiate payment', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withErrors(['approval' => 'Could not approve pitch at this time. Please try again later.']);
        }
    }

    /**
     * Client requests revisions.
     */
    public function requestRevisions(Project $project, Request $request)
    {
         if (!$project->isClientManagement()) abort(403);
         $pitch = $project->pitches()->firstOrFail();

         $request->validate(['feedback' => 'required|string|max:5000']);

         try {
             // Service method handles authorization (status check)
             $this->pitchWorkflowService->clientRequestRevisions($pitch, $request->input('feedback'), $project->client_email);
             return back()->with('success', 'Revision request submitted successfully.');
         } catch (\App\Exceptions\Pitch\InvalidStatusTransitionException $e) {
            Log::warning('Client attempted to request revisions on pitch with invalid status.', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'current_status' => $pitch->status]);
            return back()->withErrors(['revisions' => $e->getMessage()]); // Show specific error
         } catch (\Exception $e) {
            Log::error('Client failed to request revisions', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'error' => $e->getMessage()]);
            return back()->withErrors(['revisions' => 'Could not request revisions at this time. Please try again later.']);
         }
    }

    /**
     * Resend the client invitation email with a new signed URL.
     */
    public function resendInvite(Project $project, Request $request)
    {
        // Authorization: Ensure authenticated user is the project owner (producer)
        if ($request->user()->id !== $project->user_id) {
            abort(403, 'Unauthorized action.');
        }

        // DD: Debugging the project type before validation
        Log::debug('Resend Invite Check:', ['project_id' => $project->id, 'workflow_type' => $project->workflow_type, 'expected_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT]);

        // Validation: Ensure it's a client management project
        if (!$project->isClientManagement()) {
            Log::error('Resend Invite Failed: Project is NOT Client Management', ['project_id' => $project->id, 'actual_type' => $project->workflow_type]);
            abort(404, 'Project type does not support client invites.');
        }

        // Validation: Ensure client email exists
        if (empty($project->client_email)) {
             Log::warning('Attempted to resend client invite for project without client email', ['project_id' => $project->id]);
             return back()->withErrors(['resend' => 'Client email is not set for this project.']);
        }

        try {
            // Generate a NEW Signed URL for Client Portal
            $signedUrl = URL::temporarySignedRoute(
                'client.portal.view',
                now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)), // Use config
                ['project' => $project->id]
            );

            // Log the signed URL directly for admin access
            Log::info('Client invite URL generated for resend', [
                'project_id' => $project->id,
                'client_email' => $project->client_email,
                'signed_url' => $signedUrl
            ]);

            // Re-trigger the notification
            $this->notificationService->notifyClientProjectInvite($project, $signedUrl);

            // Add an event to track the resend?
            // optional: $project->pitches()->first()?->events()->create([...]);

            return back()->with('success', 'Client invitation resent successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to resend client invite', [
                'project_id' => $project->id,
                'error' => $e->getMessage()
            ]);
            return back()->withErrors(['resend' => 'Could not resend invitation at this time.']);
        }
    }

    /**
     * Handle secure file download request from the client portal.
     */
    public function downloadFile(Project $project, PitchFile $pitchFile, Request $request)
    {
        // Double-check signature validity (middleware should handle, but good practice)
        if (!$request->hasValidSignature()) {
            Log::warning('Client portal download attempt with invalid signature.', ['project_id' => $project->id, 'file_id' => $pitchFile->id]);
            abort(403, 'Invalid or expired link.');
        }

        // Ensure it's a client management project
        if (!$project->isClientManagement()) {
            Log::warning('Client portal download attempt for non-client project.', ['project_id' => $project->id, 'file_id' => $pitchFile->id]);
            abort(404); // Or 403
        }

        // Get the associated pitch
        $pitch = $project->pitches()->first();
        if (!$pitch) {
            Log::error('Client portal download failed: Pitch not found for project.', ['project_id' => $project->id, 'file_id' => $pitchFile->id]);
            abort(404);
        }

        // Authorization: Check if the requested file belongs to the correct pitch
        if ($pitchFile->pitch_id !== $pitch->id) {
            Log::warning('Client portal download attempt for file not belonging to project\'s pitch.', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id,
                'file_actual_pitch_id' => $pitchFile->pitch_id
            ]);
            abort(403, 'Access denied to this file.');
        }

        // TODO: Add more granular permissions? E.g., only allow download if pitch is in specific statuses?

        try {
            // Use Storage facade to stream the download securely
            return Storage::disk($pitchFile->disk)->download($pitchFile->file_path, $pitchFile->file_name);
        } catch (\League\Flysystem\FileNotFoundException $e) {
            Log::error('Client portal download failed: File not found in storage.', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id,
                'disk' => $pitchFile->disk,
                'path' => $pitchFile->file_path,
                'error' => $e->getMessage()
            ]);
            abort(404, 'File not found.');
        } catch (\Exception $e) {
            Log::error('Client portal download failed: Generic error.', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id,
                'error' => $e->getMessage()
            ]);
            abort(500, 'Could not download file at this time.');
        }
    }

    /**
     * Upload a file from the client (without user account) as a project file.
     */
    public function uploadFile(Project $project, Request $request, FileManagementService $fileService)
    {
        // Validate client management project
        if (!$project->isClientManagement()) {
            return response()->json([
                'success' => false,
                'message' => 'File upload is only available for client management projects.'
            ], 403);
        }
        
        // Validate signed URL (middleware handles this, but double-check)
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired link.'
            ], 403);
        }
        
        // Validate file upload
        $request->validate([
            'file' => 'required|file|max:204800', // 200MB max
        ]);
        
        try {
            // Upload as PROJECT file (not pitch file) with no user (client upload)
            $projectFile = $fileService->uploadProjectFile(
                $project,
                $request->file('file'),
                null, // No user - this is a client upload
                [
                    'uploaded_by_client' => true,
                    'client_email' => $project->client_email,
                    'upload_context' => 'client_portal'
                ]
            );
            
            Log::info('Client uploaded project file successfully.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'file_name' => $projectFile->file_name,
                'client_email' => $project->client_email
            ]);
            
            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $projectFile->id,
                    'name' => $projectFile->file_name,
                    'size' => $projectFile->file_size,
                    'type' => $projectFile->mime_type,
                ],
                'message' => 'File uploaded successfully.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Client file upload failed.', [
                'project_id' => $project->id,
                'client_email' => $project->client_email,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'File upload failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Download a project file (client-uploaded file).
     */
    public function downloadProjectFile(Project $project, \App\Models\ProjectFile $projectFile, FileManagementService $fileService)
    {
        // Validate client management project
        if (!$project->isClientManagement()) {
            abort(403, 'Access denied.');
        }
        
        // Ensure the file belongs to this project
        if ($projectFile->project_id !== $project->id) {
            abort(404, 'File not found.');
        }
        
        // Double-check signature validity (middleware should handle, but good practice)
        if (!request()->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }
        
        try {
            // Generate temporary download URL
            $downloadUrl = $fileService->getTemporaryDownloadUrl($projectFile);
            
            Log::info('Client downloaded project file.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'client_email' => $project->client_email
            ]);
            
            return redirect($downloadUrl);
            
        } catch (\Exception $e) {
            Log::error('Client project file download failed.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'error' => $e->getMessage()
            ]);
            
            abort(500, 'Unable to download file.');
        }
    }
} 