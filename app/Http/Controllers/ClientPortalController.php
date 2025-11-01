<?php

namespace App\Http\Controllers;

use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Models\FileUploadSetting; // Assuming one pitch per client project
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use App\Services\FileManagementService;
use App\Services\NotificationService; // Added for notifications
use App\Services\PitchWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Already used, keep
use Illuminate\Support\Facades\Hash; // Needed for Cashier
use Illuminate\Support\Facades\Log; // Needed for Cashier
use Illuminate\Support\Facades\Storage; // Needed for Cashier
use Illuminate\Support\Facades\URL; // Add FileManagementService
use Laravel\Cashier\Exceptions\IncompletePayment;

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
        // Access: allow via signed URL or authenticated registered client (middleware ensures this)
        if (! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Retrieve the single pitch associated with this project
        // Enhanced: Eager load snapshots and their associated files
        $pitch = $project->pitches()
            ->with([
                'user',
                'files',
                'snapshots' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'events' => function ($query) {
                    // Order events, newest first
                    $query->orderBy('created_at', 'desc');
                },
                'events.user',
            ])
            ->first();

        if (! $pitch) {
            Log::error('Client portal accessed but no pitch found for project.', ['project_id' => $project->id]);
            abort(404, 'Project details could not be loaded.'); // Or show an error view
        }

        // Enhanced: Prepare snapshot history and current snapshot
        $snapshotHistory = $this->prepareSnapshotHistory($pitch);
        $currentSnapshot = $this->getCurrentSnapshot($pitch, $request);

        // Debug logging for client portal file display
        if ($currentSnapshot) {
            Log::info('Client portal current snapshot prepared', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $currentSnapshot->id ?? 'virtual',
                'snapshot_files_count' => $currentSnapshot->files ? $currentSnapshot->files->count() : 0,
                'snapshot_has_files' => method_exists($currentSnapshot, 'hasFiles') ? $currentSnapshot->hasFiles() : false,
                'files_data' => $currentSnapshot->files ? $currentSnapshot->files->map(function ($file) {
                    return [
                        'id' => $file->id ?? 'missing',
                        'name' => $file->file_name ?? 'missing',
                        'pitch_id' => $file->pitch_id ?? 'missing',
                    ];
                })->toArray() : [],
            ]);
        }

        // Pass enhanced data to view
        $branding = app(\App\Services\BrandingResolver::class)->forProducer($pitch->user);

        // Detect if user is authenticated (vs. accessing via signed URL)
        $isAuthenticated = auth()->check() &&
            auth()->user() &&
            (
                $project->client_user_id === auth()->id() ||
                $project->client_email === auth()->user()->email
            );

        // Authenticated clients should use the app-sidebar layout for consistent UX
        $useAppSidebar = $isAuthenticated;

        return view('client_portal.show', [
            'project' => $project,
            'pitch' => $pitch,
            'snapshotHistory' => $snapshotHistory,
            'currentSnapshot' => $currentSnapshot,
            'branding' => $branding,
            'milestones' => $pitch->milestones()->get(),
            'isAuthenticated' => $isAuthenticated,
            'useAppSidebar' => $useAppSidebar,
        ]);
    }

    /**
     * Show a specific snapshot in the client portal.
     */
    public function showSnapshot(Project $project, \App\Models\PitchSnapshot $snapshot, Request $request)
    {
        // Access: allow via signed URL or authenticated registered client (middleware ensures this)
        if (! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Ensure snapshot belongs to this project
        $pitch = $project->pitches()->first();
        if (! $pitch || $snapshot->pitch_id !== $pitch->id) {
            abort(404, 'Snapshot not found for this project.');
        }

        // Load the pitch with all necessary relationships
        $pitch = $project->pitches()
            ->with([
                'user',
                'files',
                'snapshots' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'events' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'events.user',
            ])
            ->first();

        // Prepare data for view
        $snapshotHistory = $this->prepareSnapshotHistory($pitch);

        // Use the specific snapshot as current
        $currentSnapshot = $snapshot;

        $branding = app(\App\Services\BrandingResolver::class)->forProducer($pitch->user);

        return view('client_portal.show', [
            'project' => $project,
            'pitch' => $pitch,
            'snapshotHistory' => $snapshotHistory,
            'currentSnapshot' => $currentSnapshot,
            'branding' => $branding,
            'milestones' => $pitch->milestones()->get(),
        ]);
    }

    /**
     * Prepare snapshot history data for client view.
     */
    private function prepareSnapshotHistory($pitch)
    {
        $snapshots = $pitch->snapshots;

        // If we have real snapshots, use them
        if ($snapshots->count() > 0) {
            return $snapshots->map(function ($snapshot, $index) use ($pitch) {
                // Get files for this snapshot (including soft-deleted files for history transparency)
                $fileIds = $snapshot->snapshot_data['file_ids'] ?? [];
                $files = $pitch->files()->withTrashed()->whereIn('id', $fileIds)->get()->map(function ($file) {
                    return [
                        'id' => $file->id,
                        'file_name' => $file->file_name,
                        'original_file_name' => $file->original_file_name,
                        'size' => $file->size,
                        'duration' => $file->duration,
                        'mime_type' => $file->mime_type,
                        'created_at' => $file->created_at,
                        'waveform_peaks' => $file->waveform_peaks,
                        'note' => $file->note,
                        'uuid' => $file->uuid,
                        'deleted_at' => $file->deleted_at,
                        'trashed' => $file->trashed(),
                    ];
                });

                return [
                    'id' => $snapshot->id,
                    'version' => $snapshot->snapshot_data['version'] ?? ($index + 1),
                    'submitted_at' => $snapshot->created_at,
                    'status' => $snapshot->status,
                    'file_count' => count($fileIds),
                    'response_to_feedback' => $snapshot->snapshot_data['response_to_feedback'] ?? null,
                    'files' => $files,
                ];
            });
        }

        // Fallback: If no snapshots but files exist AND pitch is in client-viewable status,
        // create virtual snapshot history for backward compatibility
        $clientViewableStatuses = [
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            Pitch::STATUS_COMPLETED,
        ];

        if ($pitch->files->count() > 0 && in_array($pitch->status, $clientViewableStatuses)) {
            $files = $pitch->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'file_name' => $file->file_name,
                    'original_file_name' => $file->original_file_name,
                    'size' => $file->size,
                    'duration' => $file->duration,
                    'mime_type' => $file->mime_type,
                    'created_at' => $file->created_at,
                    'waveform_peaks' => $file->waveform_peaks,
                    'note' => $file->note,
                    'uuid' => $file->uuid,
                ];
            });

            return collect([[
                'id' => 'current',
                'version' => 1,
                'submitted_at' => $pitch->updated_at,
                'status' => 'pending',
                'file_count' => $pitch->files->count(),
                'response_to_feedback' => null,
                'files' => $files,
            ]]);
        }

        // No snapshots, or pitch not in client-viewable status
        return collect();
    }

    /**
     * Get the current snapshot to display.
     */
    private function getCurrentSnapshot($pitch, $request)
    {
        $snapshotId = $request->get('snapshot');

        if ($snapshotId) {
            // Use eager-loaded snapshots instead of querying
            return $pitch->snapshots->find($snapshotId);
        }

        // Try to get latest snapshot first (use eager-loaded collection)
        $latestSnapshot = $pitch->snapshots->sortByDesc('created_at')->first();

        if ($latestSnapshot) {
            return $latestSnapshot;
        }

        // Fallback: Create a virtual snapshot from current pitch files for backward compatibility
        // ONLY if pitch is in a client-viewable status
        $clientViewableStatuses = [
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            Pitch::STATUS_COMPLETED,
        ];

        if ($pitch->files->count() > 0 && in_array($pitch->status, $clientViewableStatuses)) {
            // Create a dynamic class that mimics PitchSnapshot behavior
            $virtualSnapshot = new class
            {
                public $id;

                public $pitch_id;

                public $created_at;

                public $created_at_for_user;

                public $snapshot_data;

                public $status;

                public $files;

                public $version;

                public $response_to_feedback;

                public function hasFiles()
                {
                    return $this->files && $this->files->count() > 0;
                }
            };

            $virtualSnapshot->id = 'current';
            $virtualSnapshot->pitch_id = $pitch->id;
            $virtualSnapshot->created_at = $pitch->updated_at;
            $virtualSnapshot->created_at_for_user = app(\App\Services\TimezoneService::class)
                ->convertToUserTimezone($pitch->updated_at);
            $virtualSnapshot->snapshot_data = [
                'version' => 1,
                'file_ids' => $pitch->files->pluck('id')->toArray(),
                'response_to_feedback' => null,
            ];
            $virtualSnapshot->status = 'pending';
            $virtualSnapshot->files = $pitch->files;
            $virtualSnapshot->version = 1;
            $virtualSnapshot->response_to_feedback = null;

            return $virtualSnapshot;
        }

        return null;
    }

    /**
     * Store a comment from the client.
     */
    public function storeComment(Project $project, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }
        $pitch = $project->pitches()->firstOrFail(); // Assuming one pitch

        $request->validate(['comment' => 'required|string|max:5000']);

        try {
            // Add comment via PitchEvent
            $event = $pitch->events()->create([
                'event_type' => 'client_comment',
                'comment' => $request->input('comment'),
                'status' => $pitch->status,
                'created_by' => null, // Indicate client origin
                'metadata' => ['client_email' => auth()->check() ? auth()->user()->email : $project->client_email], // Store identifier
            ]);

            // Notify producer
            $this->notificationService->notifyProducerClientCommented($pitch, $request->input('comment'));

            return back()->with('success', 'Comment added successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to store client comment', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['comment' => 'Could not add comment at this time.']);
        }
    }

    /**
     * Client approves the pitch submission.
     */
    public function approvePitch(Project $project, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }
        $pitch = $project->pitches()->with('user')->firstOrFail(); // Eager load producer
        $producer = $pitch->user; // Get the producer user model

        if (! $producer) {
            Log::error('Producer not found for pitch during client approval', ['pitch_id' => $pitch->id, 'project_id' => $project->id]);

            return back()->withErrors(['approval' => 'Could not approve pitch due to an internal error (Producer not found).']);
        }

        try {
            // Check if milestones exist
            $hasMilestones = $pitch->milestones()->exists();

            if ($hasMilestones) {
                // When milestones exist: approval is separate from payment
                // Payment happens through individual milestone payments
                Log::info('Client approving pitch with milestones. Payment handled separately.', [
                    'pitch_id' => $pitch->id,
                    'project_id' => $project->id,
                ]);

                $clientIdentifier = auth()->check() ? auth()->user()->email : $project->client_email;
                $this->pitchWorkflowService->clientApprovePitch($pitch, $clientIdentifier);

                return back()->with('success', 'Project approved successfully! Please complete milestone payments below.');
            }

            // No milestones - check if payment is required
            $needsPayment = $pitch->payment_amount > 0 && $pitch->payment_status !== Pitch::PAYMENT_STATUS_PAID;

            if ($needsPayment) {
                Log::info('Client approval requires payment. Initiating Stripe Checkout.', ['pitch_id' => $pitch->id, 'amount' => $pitch->payment_amount]);

                // --- Initiate Stripe Checkout ---
                $successUrl = URL::signedRoute('client.portal.view', ['project' => $project->id, 'checkout_status' => 'success']);
                $cancelUrl = URL::signedRoute('client.portal.view', ['project' => $project->id, 'checkout_status' => 'cancel']);

                // Guard: ensure producer has Stripe Connect ready if payouts required
                if (! $producer->stripe_account_id || ! $producer->hasValidStripeConnectAccount()) {
                    return back()->withErrors(['payment' => 'Producer is not ready to receive payouts. Please contact them to complete setup.']);
                }

                $checkoutSession = $producer->checkout([
                    // Define line items
                    'price_data' => [
                        'currency' => config('cashier.currency'), // Assumes USD or configured default
                        'product_data' => [
                            'name' => 'Payment for Project: '.$project->title,
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
                $clientIdentifier = auth()->check() ? auth()->user()->email : $project->client_email;
                $this->pitchWorkflowService->clientApprovePitch($pitch, $clientIdentifier);

                return back()->with('success', 'Pitch approved successfully.');
            }

        } catch (InvalidStatusTransitionException $e) {
            Log::warning('Client attempted to approve pitch with invalid status.', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'current_status' => $pitch->status]);

            return back()->withErrors(['approval' => $e->getMessage()]); // Show specific error
        } catch (IncompletePayment $e) {
            // Handle specific Cashier exceptions related to SCA or failed payments if needed
            // This might happen if checkout() is used differently, less likely with direct redirect
            Log::error('Cashier payment exception during client approval checkout initiation.', ['pitch_id' => $pitch->id, 'error' => $e->getMessage()]);

            return back()->withErrors(['approval' => 'Payment processing failed: '.$e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Client failed to approve pitch or initiate payment', ['project_id' => $project->id, 'pitch_id' => $pitch->id, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return back()->withErrors(['approval' => 'Could not approve pitch at this time. Please try again later.']);
        }
    }

    /**
     * Approve a specific milestone and trigger payment if required.
     */
    public function approveMilestone(Project $project, \App\Models\PitchMilestone $milestone, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }

        $pitch = $project->pitches()->with('user')->firstOrFail();
        if ($milestone->pitch_id !== $pitch->id) {
            abort(404, 'Milestone not found for this project.');
        }

        // Validation: Check if this is the next payable milestone (sequential payment enforcement)
        $allMilestones = $pitch->milestones()->orderBy('sort_order')->get();
        $nextPayableMilestone = $allMilestones
            ->where('payment_status', '!=', \App\Models\Pitch::PAYMENT_STATUS_PAID)
            ->where('payment_status', '!=', \App\Models\Pitch::PAYMENT_STATUS_PROCESSING)
            ->first();

        if ($nextPayableMilestone && $nextPayableMilestone->id !== $milestone->id) {
            Log::warning('Client attempted to pay out-of-order milestone.', [
                'project_id' => $project->id,
                'attempted_milestone_id' => $milestone->id,
                'expected_milestone_id' => $nextPayableMilestone->id,
            ]);

            return back()->withErrors([
                'payment' => 'Please complete milestone payments in order. Next milestone: "'.$nextPayableMilestone->name.'"',
            ]);
        }

        // Check if already paid
        if ($milestone->payment_status === \App\Models\Pitch::PAYMENT_STATUS_PAID) {
            return back()->with('info', 'This milestone has already been paid.');
        }

        // Approve milestone
        $milestone->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        // Handle payment if amount > 0 and not paid
        if ($milestone->amount > 0 && $milestone->payment_status !== \App\Models\Pitch::PAYMENT_STATUS_PAID) {
            $producer = $pitch->user;
            // Create a Stripe Checkout session via Cashier for payment
            $successUrl = URL::signedRoute('client.portal.view', ['project' => $project->id, 'checkout_status' => 'success']);
            $cancelUrl = URL::signedRoute('client.portal.view', ['project' => $project->id, 'checkout_status' => 'cancel']);

            // Guard: ensure producer has Stripe Connect ready if payouts required
            if (! $producer->stripe_account_id || ! $producer->hasValidStripeConnectAccount()) {
                return back()->withErrors(['payment' => 'Producer is not ready to receive payouts. Please contact them to complete setup.']);
            }

            $checkoutSession = $producer->checkout([
                'price_data' => [
                    'currency' => config('cashier.currency'),
                    'product_data' => [
                        'name' => 'Milestone Payment: '.$milestone->name,
                        'description' => 'Payment for milestone on project: '.$project->title,
                    ],
                    'unit_amount' => (int) round($milestone->amount * 100),
                ],
                'quantity' => 1,
            ], [
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'milestone_id' => $milestone->id,
                    'pitch_id' => $pitch->id,
                    'project_id' => $project->id,
                    'type' => 'client_milestone_payment',
                ],
            ]);

            // Mark as processing until webhook confirms
            $milestone->update([
                'payment_status' => \App\Models\Pitch::PAYMENT_STATUS_PROCESSING,
            ]);

            return redirect($checkoutSession->url);
        }

        return back()->with('success', 'Milestone approved.');
    }

    /**
     * Client approves a specific file in the portal (per-file approval).
     */
    public function approveFile(Project $project, \App\Models\PitchFile $pitchFile, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }

        $pitch = $project->pitches()->firstOrFail();
        if ($pitchFile->pitch_id !== $pitch->id) {
            abort(404, 'File not found for this project.');
        }

        $pitchFile->update([
            'client_approval_status' => 'approved',
            'client_approved_at' => now(),
        ]);

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'file_id' => $pitchFile->id,
                'approved_at' => optional($pitchFile->client_approved_at)->toIso8601String(),
                'approved_at_human' => optional($pitchFile->client_approved_at)->diffForHumans(),
                'status' => 'approved',
            ]);
        }

        return back()->with('success', 'File approved.');
    }

    /**
     * Client approves all files for the current visible snapshot/pitch.
     */
    public function approveAllFiles(Project $project, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }

        $pitch = $project->pitches()->firstOrFail();
        // Approve all files on the pitch
        $updated = $pitch->files()
            ->where(function ($query) {
                $query
                    ->whereNull('client_approval_status')
                    ->orWhere('client_approval_status', '!=', 'approved');
            })
            ->update([
                'client_approval_status' => 'approved',
                'client_approved_at' => now(),
            ]);

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'updated_count' => $updated,
            ]);
        }

        return back()->with('success', 'All files approved.');
    }

    /**
     * Client unapproves a specific file in the portal.
     */
    public function unapproveFile(Project $project, \App\Models\PitchFile $pitchFile, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }

        $pitch = $project->pitches()->firstOrFail();
        if ($pitchFile->pitch_id !== $pitch->id) {
            abort(404, 'File not found for this project.');
        }

        $pitchFile->update([
            'client_approval_status' => null,
            'client_approved_at' => null,
        ]);

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'file_id' => $pitchFile->id,
                'status' => 'unapproved',
            ]);
        }

        return back()->with('success', 'File unapproved.');
    }

    /**
     * Client unapproves all files for the current visible snapshot/pitch.
     */
    public function unapproveAllFiles(Project $project, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }

        $pitch = $project->pitches()->firstOrFail();
        // Unapprove all approved files on the pitch
        $updated = $pitch->files()
            ->where('client_approval_status', 'approved')
            ->update([
                'client_approval_status' => null,
                'client_approved_at' => null,
            ]);

        if ($request->expectsJson() || $request->boolean('ajax')) {
            return response()->json([
                'success' => true,
                'updated_count' => $updated,
            ]);
        }

        return back()->with('success', 'All files unapproved.');
    }

    /**
     * Client requests revisions.
     */
    public function requestRevisions(Project $project, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(403);
        }
        $pitch = $project->pitches()->firstOrFail();

        $request->validate(['feedback' => 'required|string|max:5000']);

        try {
            // Service method handles authorization (status check)
            $clientIdentifier = auth()->check() ? auth()->user()->email : $project->client_email;
            $this->pitchWorkflowService->clientRequestRevisions($pitch, $request->input('feedback'), $clientIdentifier);

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
     * Phase 2: Show client account upgrade form.
     */
    public function showUpgrade(Project $project, Request $request)
    {
        // Validate signed URL access
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        if (! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Check if user already exists with this email
        $existingUser = User::where('email', $project->client_email)->first();
        if ($existingUser) {
            return redirect()->route('login')->with('info', 'Please log in to access your projects.');
        }

        return view('client_portal.upgrade', compact('project'));
    }

    /**
     * Get MIME type for processed audio format
     */
    private function getMimeTypeForFormat(?string $format): string
    {
        return match ($format) {
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'aac' => 'audio/aac',
            'm4a' => 'audio/mp4',
            'flac' => 'audio/flac',
            default => 'audio/mpeg'
        };
    }

    /**
     * Phase 2: Create client account from guest access.
     */
    public function createAccount(Request $request, Project $project)
    {
        // Validate signed URL access
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link.');
        }

        if (! $project->isClientManagement()) {
            abort(403, 'Invalid project type.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Check if user already exists
            $existingUser = User::where('email', $project->client_email)->first();
            if ($existingUser) {
                return back()->withErrors(['email' => 'An account with this email already exists. Please log in instead.']);
            }

            // Create new client user
            $user = User::create([
                'name' => $request->name,
                'email' => $project->client_email,
                'password' => Hash::make($request->password),
                'role' => User::ROLE_CLIENT,
                'email_verified_at' => now(), // Auto-verify since they accessed via signed URL
            ]);

            // Link existing projects to new user account
            Project::where('client_email', $user->email)->update(['client_user_id' => $user->id]);

            // Link existing license signatures to new user account
            $updatedSignatures = \App\Models\LicenseSignature::where('client_email', $user->email)
                ->whereNull('user_id')
                ->update(['user_id' => $user->id]);

            // Log the user in
            Auth::login($user);

            Log::info('Client account created and linked to projects and license signatures', [
                'user_id' => $user->id,
                'email' => $user->email,
                'linked_projects' => Project::where('client_user_id', $user->id)->count(),
                'linked_signatures' => $updatedSignatures,
            ]);

            return redirect()->route('dashboard')->with('success', 'Account created successfully! Welcome to MIXPITCH.');

        } catch (\Exception $e) {
            Log::error('Failed to create client account', [
                'project_id' => $project->id,
                'email' => $project->client_email,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['account' => 'Could not create account at this time. Please try again later.']);
        }
    }

    /**
     * Phase 2: Show invoice for completed project.
     */
    public function invoice(Project $project, Request $request)
    {
        // Validate access (signed URL or authenticated client)
        if (! $this->validateClientAccess($project, $request)) {
            abort(403, 'Access denied.');
        }

        $pitch = $project->pitches()
            ->where('payment_status', Pitch::PAYMENT_STATUS_PAID)
            ->with(['user', 'events'])
            ->firstOrFail();

        // Get payment information from events or metadata
        $paymentEvent = $pitch->events()
            ->where('event_type', 'payment_completed')
            ->first();

        $invoiceData = [
            'project' => $project,
            'pitch' => $pitch,
            'payment_event' => $paymentEvent,
            'invoice_number' => 'INV-'.$project->id.'-'.$pitch->id,
            'payment_date' => $paymentEvent ? $paymentEvent->created_at : $pitch->updated_at,
            'amount' => $pitch->payment_amount,
        ];

        return view('client_portal.invoice', $invoiceData);
    }

    /**
     * Phase 2: Show deliverables for completed project.
     */
    public function deliverables(Project $project, Request $request)
    {
        // Validate access (signed URL or authenticated client)
        if (! $this->validateClientAccess($project, $request)) {
            abort(403, 'Access denied.');
        }

        $pitch = $project->pitches()
            ->where('status', Pitch::STATUS_COMPLETED)
            ->with(['user', 'files'])
            ->firstOrFail();

        // Get deliverable files (files marked as deliverables in note field or all files for completed pitch)
        $deliverables = $pitch->files()
            ->where(function ($query) {
                $query->where('note', 'LIKE', '%deliverable%')
                    ->orWhere('note', 'LIKE', '%final%')
                    ->orWhereNull('note'); // Include all files if no specific deliverable marking
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('client_portal.deliverables', compact('project', 'pitch', 'deliverables'));
    }

    /**
     * Phase 2: Validate client access to project (signed URL or authenticated user).
     */
    private function validateClientAccess(Project $project, ?Request $request = null): bool
    {
        if (! $project->isClientManagement()) {
            return false;
        }

        // Check if user is authenticated and owns the project
        if (Auth::check()) {
            $user = Auth::user();

            $isClientRole = ($user->role ?? null) === User::ROLE_CLIENT;

            return $isClientRole &&
                   ($project->client_user_id === $user->id || $project->client_email === $user->email);
        }

        // Check signed URL access
        if ($request && $request->hasValidSignature()) {
            return true;
        }

        return false;
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
        if (! $project->isClientManagement()) {
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
                'signed_url' => $signedUrl,
            ]);

            // Re-trigger the notification
            $this->notificationService->notifyClientProjectInvite($project, $signedUrl);

            // Add an event to track the resend?
            // optional: $project->pitches()->first()?->events()->create([...]);

            return back()->with('success', 'Client invitation resent successfully.');

        } catch (\Exception $e) {
            Log::error('Failed to resend client invite', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return back()->withErrors(['resend' => 'Could not resend invitation at this time.']);
        }
    }

    /**
     * Handle secure file download request from the client portal.
     */
    public function downloadFile(Project $project, PitchFile $pitchFile, Request $request)
    {
        // Ensure it's a client management project (access enforcement is handled by middleware)
        if (! $project->isClientManagement()) {
            Log::warning('Client portal download attempt for non-client project.', ['project_id' => $project->id, 'file_id' => $pitchFile->id]);
            abort(404); // Or 403
        }

        // Get the associated pitch
        $pitch = $project->pitches()->first();
        if (! $pitch) {
            Log::error('Client portal download failed: Pitch not found for project.', ['project_id' => $project->id, 'file_id' => $pitchFile->id]);
            abort(404);
        }

        // Authorization: Check if the requested file belongs to the correct pitch
        if ($pitchFile->pitch_id !== $pitch->id) {
            Log::warning('Client portal download attempt for file not belonging to project\'s pitch.', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id,
                'file_actual_pitch_id' => $pitchFile->pitch_id,
            ]);
            abort(403, 'Access denied to this file.');
        }

        // Get current snapshot for revision-based access control
        $currentSnapshot = $this->getCurrentSnapshot($pitch, $request);

        try {
            // Determine which file to download based on watermarking logic and payment status
            // Pass project context and snapshot for revision-based access control
            $shouldServeWatermarked = $pitchFile->shouldServeWatermarked(auth()->user(), $project, $currentSnapshot);

            if ($shouldServeWatermarked && $pitchFile->processed_file_path && $pitchFile->is_watermarked) {
                // Download the processed (watermarked) version
                $filePath = $pitchFile->processed_file_path;
                $fileName = pathinfo($pitchFile->file_name, PATHINFO_FILENAME).'_watermarked.'.pathinfo($pitchFile->file_name, PATHINFO_EXTENSION);

                Log::info('Client portal downloading watermarked version', [
                    'project_id' => $project->id,
                    'file_id' => $pitchFile->id,
                    'processed_path' => $filePath,
                    'original_path' => $pitchFile->file_path,
                ]);
            } else {
                // Download the original version (either not watermarked or payment allows access)
                $filePath = $pitchFile->file_path;
                $fileName = $pitchFile->file_name;

                Log::info('Client portal downloading original version', [
                    'project_id' => $project->id,
                    'file_id' => $pitchFile->id,
                    'should_watermark' => $shouldServeWatermarked,
                    'has_processed' => ! empty($pitchFile->processed_file_path),
                    'is_watermarked' => $pitchFile->is_watermarked,
                    'payment_allows_access' => ! $shouldServeWatermarked,
                ]);
            }

            // Stream the download for broad driver compatibility
            $stream = Storage::disk($pitchFile->disk)->readStream($filePath);
            if (! $stream) {
                abort(404, 'File not found.');
            }

            return response()->streamDownload(function () use ($stream) {
                fpassthru($stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }, $fileName);
        } catch (\Exception $e) {
            Log::error('Client portal download failed: Generic error.', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Could not download file at this time.');
        }
    }

    /**
     * Upload a file from the client (without user account) as a project file.
     */
    public function uploadFile(Project $project, Request $request, FileManagementService $fileService)
    {
        // Debug logging to understand the 403 issue
        Log::info('Client portal upload request started', [
            'project_id' => $project->id,
            'request_url' => $request->fullUrl(),
            'request_method' => $request->method(),
            'has_file' => $request->hasFile('file'),
            'user_agent' => $request->userAgent(),
            'csrf_token' => $request->header('X-CSRF-TOKEN'),
            'signature_valid' => $request->hasValidSignature(),
            'query_params' => $request->query(),
        ]);

        // Validate client management project
        if (! $project->isClientManagement()) {
            Log::warning('Upload rejected: Not a client management project', ['project_id' => $project->id]);

            return response()->json([
                'success' => false,
                'message' => 'File upload is only available for client management projects.',
            ], 403);
        }

        // Note: Signed URL validation is handled by the signed middleware
        // Removing redundant check that was causing 403 errors

        // Validate file upload using client portal context settings
        try {
            $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_CLIENT_PORTALS);
            $maxFileSizeKB = $settings[FileUploadSetting::MAX_FILE_SIZE_MB] * 1024; // Convert MB to KB for Laravel validation

            $request->validate([
                'file' => "required|file|max:{$maxFileSizeKB}",
            ]);
            Log::info('File validation passed', ['project_id' => $project->id]);
        } catch (\Exception $e) {
            Log::error('File validation failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File validation failed: '.$e->getMessage(),
            ], 422);
        }

        try {
            Log::info('Starting file upload process', ['project_id' => $project->id]);

            // Upload as PROJECT file (not pitch file) with no user (client upload)
            $projectFile = $fileService->uploadProjectFile(
                $project,
                $request->file('file'),
                null, // No user - this is a client upload
                [
                    'uploaded_by_client' => true,
                    'client_email' => $project->client_email,
                    'upload_context' => 'client_portal',
                ]
            );

            Log::info('Client uploaded project file successfully.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'file_name' => $projectFile->file_name,
                'client_email' => $project->client_email,
            ]);

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $projectFile->id,
                    'name' => $projectFile->file_name,
                    'size' => $projectFile->size,
                    'type' => $projectFile->mime_type,
                ],
                'message' => 'File uploaded successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Client file upload failed.', [
                'project_id' => $project->id,
                'client_email' => $project->client_email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'File upload failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Download a project file (client-uploaded file).
     */
    public function downloadProjectFile(Project $project, \App\Models\ProjectFile $projectFile, FileManagementService $fileService)
    {
        // Validate client management project
        if (! $project->isClientManagement()) {
            abort(403, 'Access denied.');
        }

        // Ensure the file belongs to this project
        if ($projectFile->project_id !== $project->id) {
            abort(404, 'File not found.');
        }

        try {
            // Generate temporary download URL
            $downloadUrl = $fileService->getTemporaryDownloadUrl($projectFile);

            Log::info('Client downloaded project file.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'client_email' => $project->client_email,
            ]);

            return redirect($downloadUrl);

        } catch (\Exception $e) {
            Log::error('Client project file download failed.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'error' => $e->getMessage(),
            ]);

            abort(500, 'Unable to download file.');
        }
    }

    /**
     * Delete a project file (client-uploaded file).
     */
    public function deleteProjectFile(Project $project, \App\Models\ProjectFile $projectFile, FileManagementService $fileService)
    {
        // Validate client management project
        if (! $project->isClientManagement()) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }

        // Ensure the file belongs to this project
        if ($projectFile->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'File not found.',
            ], 404);
        }

        try {
            // Use the FileManagementService to delete the file
            $fileService->deleteProjectFile($projectFile);

            Log::info('Client deleted project file.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'file_name' => $projectFile->file_name,
                'client_email' => $project->client_email,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully.',
            ]);

        } catch (\Exception $e) {
            Log::error('Client project file deletion failed.', [
                'project_id' => $project->id,
                'file_id' => $projectFile->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to delete file. Please try again.',
            ], 500);
        }
    }

    /**
     * Stream an audio file for the client portal.
     */
    public function streamAudioFile(Project $project, PitchFile $pitchFile, Request $request)
    {
        // Validate client management project
        if (! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Get the associated pitch
        $pitch = $project->pitches()->first();
        if (! $pitch) {
            abort(404, 'Project details could not be loaded.');
        }

        // Ensure the file belongs to this project's pitch
        if ($pitchFile->pitch_id !== $pitch->id) {
            abort(404, 'File not found for this project.');
        }

        // Check if pitch is in appropriate status for file streaming
        $allowedStatuses = [
            \App\Models\Pitch::STATUS_READY_FOR_REVIEW,
            \App\Models\Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            \App\Models\Pitch::STATUS_COMPLETED,
        ];

        if (! in_array($pitch->status, $allowedStatuses)) {
            abort(403, 'Files are not available for streaming at this time.');
        }

        // Get current snapshot for revision-based access control
        $currentSnapshot = $this->getCurrentSnapshot($pitch, $request);

        try {
            // Determine which file to stream based on watermarking logic
            // Pass project context and snapshot for revision-based access control
            $shouldServeWatermarked = $pitchFile->shouldServeWatermarked(auth()->user(), $project, $currentSnapshot);

            if ($shouldServeWatermarked && $pitchFile->processed_file_path && $pitchFile->is_watermarked) {
                // Stream the processed (watermarked) version
                $filePath = $pitchFile->processed_file_path;
                $mimeType = $pitchFile->processed_format ? $this->getMimeTypeForFormat($pitchFile->processed_format) : ($pitchFile->mime_type ?? 'audio/mpeg');

                // Get file size from processed file
                $fileSize = Storage::disk($pitchFile->disk)->size($filePath);

                Log::info('Client portal streaming watermarked version', [
                    'project_id' => $project->id,
                    'file_id' => $pitchFile->id,
                    'processed_path' => $filePath,
                    'original_path' => $pitchFile->file_path,
                ]);
            } else {
                // Stream the original version
                $filePath = $pitchFile->file_path;
                $mimeType = $pitchFile->mime_type ?? 'audio/mpeg';
                $fileSize = $pitchFile->size;

                Log::info('Client portal streaming original version', [
                    'project_id' => $project->id,
                    'file_id' => $pitchFile->id,
                ]);
            }

            // Check if the file exists
            if (! Storage::disk($pitchFile->disk)->exists($filePath)) {
                Log::error('Client portal audio file not found', [
                    'project_id' => $project->id,
                    'file_id' => $pitchFile->id,
                    'file_path' => $filePath,
                    'disk' => $pitchFile->disk,
                ]);
                abort(404, 'Audio file not found.');
            }

            $headers = [
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'no-cache, must-revalidate',
            ];

            return response()->stream(function () use ($pitchFile, $filePath) {
                $stream = Storage::disk($pitchFile->disk)->readStream($filePath);
                if ($stream) {
                    fpassthru($stream);
                    fclose($stream);
                }
            }, 200, $headers);

        } catch (\Exception $e) {
            Log::error('Client portal audio streaming failed.', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'file_id' => $pitchFile->id,
                'error' => $e->getMessage(),
            ]);
            abort(500, 'Could not stream audio file at this time.');
        }
    }

    /**
     * Show the audio player interface for the client portal.
     */
    public function showAudioPlayer(Project $project, PitchFile $pitchFile, Request $request)
    {
        // Validate client management project
        if (! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Get the associated pitch
        $pitch = $project->pitches()->first();
        if (! $pitch) {
            abort(404, 'Project details could not be loaded.');
        }

        // Ensure the file belongs to this project's pitch
        if ($pitchFile->pitch_id !== $pitch->id) {
            abort(404, 'File not found for this project.');
        }

        // Load necessary relationships
        $pitchFile->load(['pitch.project', 'pitch.user']);

        return view('audio.show', [
            'file' => $pitchFile,
            'fileType' => 'pitch_file',
            'isClientPortal' => true,
            'breadcrumbs' => [
                'title' => $pitch->title ?? 'Producer Deliverable',
                'url' => route('client.portal.view', $project->id),
                'icon' => 'fas fa-music',
            ],
        ]);
    }

    /**
     * Preview the client portal (for project owners)
     */
    public function preview($projectId, Request $request)
    {
        Log::info('🔍 CLIENT PORTAL PREVIEW METHOD CALLED', [
            'project_id' => $projectId,
            'user_id' => auth()->id(),
            'user_role' => auth()->user()->role ?? 'no_role',
        ]);

        // Manually find the project to bypass implicit authorization
        $project = Project::findOrFail($projectId);

        // Ensure only the project owner can preview
        if (auth()->id() !== $project->user_id) {
            Log::error('Client portal preview authorization failed', [
                'project_id' => $project->id,
                'project_owner_id' => $project->user_id,
                'current_user_id' => auth()->id(),
                'current_user_role' => auth()->user()->role ?? 'no_role',
            ]);
            abort(403, 'You are not authorized to preview this client portal.');
        }

        // Basic validation: Ensure it's a client management project
        if (! $project->isClientManagement()) {
            abort(404, 'Project not found or not accessible via client portal.');
        }

        // Retrieve the single pitch associated with this project
        // Enhanced: Eager load snapshots and their associated files
        $pitch = $project->pitches()
            ->with([
                'user',
                'files',
                'snapshots' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'events' => function ($query) {
                    // Order events, newest first
                    $query->orderBy('created_at', 'desc');
                },
                'events.user',
            ])
            ->first();

        if (! $pitch) {
            Log::error('Client portal preview accessed but no pitch found for project.', ['project_id' => $project->id]);
            abort(404, 'Project details could not be loaded.'); // Or show an error view
        }

        // Enhanced: Prepare snapshot history and current snapshot
        $snapshotHistory = $this->prepareSnapshotHistory($pitch);
        $currentSnapshot = $this->getCurrentSnapshot($pitch, $request);

        // Debug logging for client portal file display (preview)
        if ($currentSnapshot) {
            Log::info('Client portal PREVIEW current snapshot prepared', [
                'project_id' => $project->id,
                'pitch_id' => $pitch->id,
                'snapshot_id' => $currentSnapshot->id ?? 'virtual',
                'snapshot_files_count' => $currentSnapshot->files ? $currentSnapshot->files->count() : 0,
                'snapshot_has_files' => method_exists($currentSnapshot, 'hasFiles') ? $currentSnapshot->hasFiles() : false,
                'files_data' => $currentSnapshot->files ? $currentSnapshot->files->map(function ($file) {
                    return [
                        'id' => $file->id ?? 'missing',
                        'name' => $file->file_name ?? 'missing',
                        'pitch_id' => $file->pitch_id ?? 'missing',
                    ];
                })->toArray() : [],
            ]);
        }

        // Add preview banner context
        $isPreview = true;

        Log::info('🔍 CLIENT PORTAL PREVIEW ACCESS', [
            'project_id' => $project->id,
            'accessed_by' => auth()->user()->name.' (Project Owner)',
            'pitch_id' => $pitch->id,
            'pitch_status' => $pitch->status,
        ]);

        $branding = app(\App\Services\BrandingResolver::class)->forProducer($pitch->user);

        return view('client_portal.show', [
            'project' => $project,
            'pitch' => $pitch,
            'snapshotHistory' => $snapshotHistory,
            'currentSnapshot' => $currentSnapshot,
            'isPreview' => $isPreview,
            'branding' => $branding,
            'milestones' => $pitch->milestones()->get(),
        ]);
    }

    /**
     * Update client email preferences for this project.
     */
    public function updateEmailPreferences(Project $project, Request $request)
    {
        if (! $project->isClientManagement()) {
            abort(404);
        }

        $validated = $request->validate([
            'type' => 'required|string|in:revision_confirmation,producer_resubmitted,payment_receipt',
            'enabled' => 'required|boolean',
        ]);

        try {
            $project->updateClientEmailPreference($validated['type'], $validated['enabled']);

            return response()->json(['success' => true, 'message' => 'Email preference updated']);
        } catch (\Exception $e) {
            Log::error('Failed to update client email preference', [
                'error' => $e->getMessage(),
                'project_id' => $project->id,
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to update preference'], 500);
        }
    }
}
