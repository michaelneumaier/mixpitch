<?php

namespace App\Http\Controllers;

use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Services\NotificationService;
use App\Exceptions\Pitch\InvalidStatusTransitionException;
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Exceptions\Pitch\SnapshotException;
use App\Exceptions\Pitch\PitchException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Masmerise\Toaster\Toaster;

class PitchStatusController extends Controller
{
    /**
     * Change the status of a pitch
     *
     * @param Pitch $pitch
     * @param string $direction
     * @param string|null $newStatus
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changeStatus(Pitch $pitch, string $direction, ?string $newStatus = null)
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();

            $project = $pitch->project;

            // Ensure the authenticated user is the owner of the project
            if (!Auth::check() || $project->user_id !== Auth::id()) {
                throw new UnauthorizedActionException(
                    'change status',
                    'You are not authorized to change the status of this pitch'
                );
            }

            // Perform the status change
            // We'll update this to directly update the status and create the necessary events
            // instead of using the older changeStatus method which returns arrays
            if ($newStatus) {
                // Depending on the new status, validate the transition
                switch ($newStatus) {
                    case Pitch::STATUS_READY_FOR_REVIEW:
                        // Special handling for returning to review from approved, denied, or revisions_requested status
                        if (in_array($pitch->status, [Pitch::STATUS_APPROVED, Pitch::STATUS_DENIED, Pitch::STATUS_REVISIONS_REQUESTED])) {
                            // Find the current snapshot
                            if ($pitch->current_snapshot_id) {
                                // Update the snapshot status
                                $snapshot = PitchSnapshot::find($pitch->current_snapshot_id);

                                if ($snapshot) {
                                    // Log the snapshot status change
                                    Log::info('Updating snapshot status to pending', [
                                        'pitch_id' => $pitch->id,
                                        'snapshot_id' => $snapshot->id,
                                        'old_status' => $snapshot->status,
                                        'pitch_status' => $pitch->status
                                    ]);

                                    // Update the snapshot status
                                    $snapshot->status = 'pending';

                                    // If snapshot was previously denied or had revisions requested, remove feedback from snapshot_data
                                    if (($pitch->status === Pitch::STATUS_DENIED || $pitch->status === Pitch::STATUS_REVISIONS_REQUESTED)
                                        && isset($snapshot->snapshot_data['feedback'])
                                    ) {
                                        // Create a copy of snapshot_data without the feedback
                                        $snapshotData = $snapshot->snapshot_data;
                                        unset($snapshotData['feedback']);
                                        $snapshot->snapshot_data = $snapshotData;

                                        Log::info('Removed feedback from snapshot when returning to review', [
                                            'pitch_id' => $pitch->id,
                                            'snapshot_id' => $snapshot->id,
                                            'previous_status' => $pitch->status,
                                            'updated_snapshot_data' => $snapshot->snapshot_data,
                                            'has_feedback' => isset($snapshot->snapshot_data['feedback'])
                                        ]);
                                    }

                                    $snapshot->save();

                                    // Create an event for the snapshot status change
                                    $pitch->events()->create([
                                        'event_type' => 'snapshot_status_change',
                                        'comment' => "Snapshot status changed from " .
                                            ($pitch->status === Pitch::STATUS_APPROVED ? 'accepted' : ($pitch->status === Pitch::STATUS_DENIED ? 'denied' : 'revisions requested')) .
                                            " to pending for review",
                                        'snapshot_id' => $snapshot->id,
                                        'created_by' => Auth::id()
                                    ]);
                                }
                            }

                            // Validate the transition
                            $pitch->canSubmitForReview();
                        } else {
                            // For other transitions to ready_for_review
                            $pitch->canSubmitForReview();
                        }
                        break;
                    case Pitch::STATUS_IN_PROGRESS:
                        // Different validation depending on current status
                        if ($pitch->status === Pitch::STATUS_PENDING) {
                            // Use the new validation method for allowing access to a pending pitch
                            $pitch->canAllowAccess();
                        } else {
                            // For other transitions to in_progress (like canceling a submission)
                            $pitch->canCancelSubmission();
                        }
                        break;
                    case Pitch::STATUS_PENDING:
                        // Validate that the pitch can have its access removed
                        $pitch->canRemoveAccess();
                        break;
                    case Pitch::STATUS_APPROVED:
                        // Special handling for returning from completed to approved
                        if ($pitch->status === Pitch::STATUS_COMPLETED) {
                            // Validate the transition
                            $pitch->canReturnToApproved();

                            // Find the current snapshot
                            if (!$pitch->current_snapshot_id) {
                                throw new \Exception('Cannot return to approved status: No current snapshot found.');
                            }

                            $snapshot = PitchSnapshot::find($pitch->current_snapshot_id);
                            if (!$snapshot) {
                                throw new \Exception('Cannot return to approved status: Current snapshot not found.');
                            }

                            // Log the action
                            Log::info('Returning pitch from completed to approved', [
                                'pitch_id' => $pitch->id,
                                'snapshot_id' => $snapshot->id,
                                'old_status' => $pitch->status
                            ]);

                            // No need to change snapshot status as it should already be 'accepted'

                            // Create an event for the status change
                            $pitch->events()->create([
                                'event_type' => 'status_change',
                                'comment' => 'Pitch returned from completed to approved status',
                                'snapshot_id' => $snapshot->id,
                                'created_by' => Auth::id()
                            ]);

                            break;
                        }

                        // For other transitions to approved, require a snapshot ID
                        throw new InvalidStatusTransitionException(
                            $pitch->status,
                            $newStatus,
                            'Approving a pitch requires a snapshot ID. Please use the appropriate form.'
                        );
                        break;
                    case Pitch::STATUS_DENIED:
                        // Requires a snapshot ID, so should be handled by denySnapshot
                        throw new InvalidStatusTransitionException(
                            $pitch->status,
                            $newStatus,
                            'Denying a pitch requires a snapshot ID. Please use the appropriate form.'
                        );
                        break;
                    case Pitch::STATUS_REVISIONS_REQUESTED:
                        // Requires a snapshot ID, so should be handled by requestChanges
                        throw new InvalidStatusTransitionException(
                            $pitch->status,
                            $newStatus,
                            'Requesting revisions requires a snapshot ID. Please use the appropriate form.'
                        );
                        break;
                    case Pitch::STATUS_COMPLETED:
                        $pitch->canComplete();
                        break;
                    default:
                        throw new InvalidStatusTransitionException(
                            $pitch->status,
                            $newStatus,
                            'Invalid status transition requested.'
                        );
                }

                // If we get here, the transition is valid
                $oldStatus = $pitch->status;
                $pitch->status = $newStatus;
                $pitch->save();

                // Create an event for this status change
                $pitch->events()->create([
                    'event_type' => 'status_change',
                    'comment' => "Status changed from {$oldStatus} to {$newStatus}",
                    'status' => $newStatus,
                    'created_by' => Auth::id()
                ]);
            } else {
                // If no specific status is provided, we'll throw an exception as we
                // need explicit status transitions for better error handling
                throw new InvalidStatusTransitionException(
                    $pitch->status,
                    'unknown',
                    'A specific target status must be provided'
                );
            }

            // Commit the transaction
            DB::commit();

            // We no longer need to create notifications here - they are handled centrally
            // by the Pitch model events in the booted() method

            Toaster::success('Pitch status updated successfully.');
            return redirect()->route('projects.manage', $pitch->project);
        } catch (UnauthorizedActionException $e) {
            DB::rollBack();
            Log::error('Unauthorized attempt to change pitch status', [
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (InvalidStatusTransitionException | SnapshotException $e) {
            DB::rollBack();
            Log::error('Invalid pitch status change attempt', [
                'pitch_id' => $pitch->id,
                'current_status' => $pitch->status,
                'requested_status' => $newStatus,
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing pitch status', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Approve a pitch snapshot
     *
     * @param Request $request
     * @param Pitch $pitch
     * @param PitchSnapshot $snapshot
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approveSnapshot(Request $request, Pitch $pitch, PitchSnapshot $snapshot)
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();

            // Ensure the authenticated user is the owner of the project
            if (!Auth::check() || $pitch->project->user_id !== Auth::id()) {
                throw new UnauthorizedActionException(
                    'approve',
                    'You are not authorized to approve this pitch'
                );
            }

            // Validate that the pitch can be approved
            $pitch->canApprove($snapshot->id);

            // Update the snapshot status
            $snapshot->status = 'accepted';
            $snapshot->save();

            // Set as the current snapshot
            $pitch->current_snapshot_id = $snapshot->id;

            // Get comment text
            $comment = 'Pitch version ' . $snapshot->snapshot_data['version'] . ' has been approved';
            if ($request->has('reason') && !empty($request->reason)) {
                $comment .= ': ' . $request->reason;
            }

            // Update the pitch status
            $pitch->status = Pitch::STATUS_APPROVED;
            $pitch->save();

            // Create a status change event
            $pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => $comment,
                'status' => Pitch::STATUS_APPROVED,
                'created_by' => Auth::id(),
                'snapshot_id' => $snapshot->id
            ]);

            // Send notification
            try {
                $notificationService = new NotificationService();
                $notificationService->notifySnapshotApproved($snapshot);
            } catch (\Exception $e) {
                // Log notification error but don't fail the transaction
                Log::error('Failed to send notification: ' . $e->getMessage());
            }

            // Commit the transaction
            DB::commit();

            // Redirect back with success message
            Toaster::success('Pitch has been approved successfully.');
            return redirect()->route('projects.manage', $pitch->project);
        } catch (UnauthorizedActionException $e) {
            DB::rollBack();
            Log::error('Unauthorized attempt to approve pitch', [
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (InvalidStatusTransitionException | SnapshotException $e) {
            DB::rollBack();
            Log::error('Invalid pitch approval attempt', [
                'pitch_id' => $pitch->id,
                'status' => $pitch->status,
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving pitch', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Deny a pitch snapshot
     *
     * @param Request $request
     * @param Pitch $pitch
     * @param PitchSnapshot $snapshot
     * @return \Illuminate\Http\RedirectResponse
     */
    public function denySnapshot(Request $request, Pitch $pitch, PitchSnapshot $snapshot)
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();

            // Ensure the authenticated user is the owner of the project
            if (!Auth::check() || $pitch->project->user_id !== Auth::id()) {
                throw new UnauthorizedActionException(
                    'deny',
                    'You are not authorized to deny this pitch'
                );
            }

            // Validate that the pitch can be denied
            $pitch->canDeny($snapshot->id);

            // Validate reason is provided
            if (!$request->has('reason') || empty($request->reason)) {
                throw new \Exception('Please provide a reason for denying this pitch.');
            }

            // Update the snapshot status
            $snapshot->status = 'denied';
            $snapshot->snapshot_data = array_merge($snapshot->snapshot_data, ['feedback' => $request->reason]);
            $snapshot->save();

            // Get comment text
            $comment = 'Pitch version ' . $snapshot->snapshot_data['version'] . ' has been denied: ' . $request->reason;

            // Add a comment to the pitch
            $pitch->events()->create([
                'event_type' => 'comment',
                'comment' => $comment,
                'created_by' => Auth::id(),
                'snapshot_id' => $snapshot->id
            ]);

            // Update the pitch status to denied 
            $pitch->status = Pitch::STATUS_DENIED;
            $pitch->save();

            // Create a status change event
            $pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => $comment,
                'status' => Pitch::STATUS_DENIED,
                'created_by' => Auth::id(),
                'snapshot_id' => $snapshot->id
            ]);

            // Send notification
            try {
                $notificationService = new NotificationService();
                $notificationService->notifySnapshotDenied($snapshot, $request->reason);
            } catch (\Exception $e) {
                // Log notification error but don't fail the transaction
                Log::error('Failed to send notification: ' . $e->getMessage());
            }

            // Commit the transaction
            DB::commit();

            // Redirect back with success message
            Toaster::success('Pitch has been denied.');
            return redirect()->route('projects.manage', $pitch->project);
        } catch (UnauthorizedActionException $e) {
            DB::rollBack();
            Log::error('Unauthorized attempt to deny pitch', [
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (InvalidStatusTransitionException | SnapshotException $e) {
            DB::rollBack();
            Log::error('Invalid pitch deny attempt', [
                'pitch_id' => $pitch->id,
                'status' => $pitch->status,
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error denying pitch', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        }
    }

    /**
     * Request revisions for a pitch snapshot
     *
     * @param Request $request
     * @param Pitch $pitch
     * @param PitchSnapshot $snapshot
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requestChanges(Request $request, Pitch $pitch, PitchSnapshot $snapshot)
    {
        try {
            // Begin a database transaction
            DB::beginTransaction();

            // Ensure the authenticated user is the owner of the project
            if (!Auth::check() || $pitch->project->user_id !== Auth::id()) {
                throw new UnauthorizedActionException(
                    'request changes',
                    'You are not authorized to request changes for this pitch'
                );
            }

            // Validate that revisions can be requested for the pitch
            $pitch->canRequestRevisions($snapshot->id);

            // Validate reason is provided
            if (!$request->has('reason') || empty($request->reason)) {
                throw new \Exception('Please specify what revisions you would like to request.');
            }

            // Update the snapshot status
            $snapshot->status = 'revisions_requested';
            $snapshot->snapshot_data = array_merge($snapshot->snapshot_data, ['feedback' => $request->reason]);
            $snapshot->save();

            // Get comment text
            $comment = 'Revisions requested for pitch version ' . $snapshot->snapshot_data['version'] . ': ' . $request->reason;

            // Update the pitch status
            $pitch->status = Pitch::STATUS_REVISIONS_REQUESTED;
            $pitch->save();

            // Create a status change event
            $pitch->events()->create([
                'event_type' => 'status_change',
                'comment' => $comment,
                'status' => Pitch::STATUS_REVISIONS_REQUESTED,
                'created_by' => Auth::id(),
                'snapshot_id' => $snapshot->id
            ]);

            // Send notification
            try {
                $notificationService = new NotificationService();
                $notificationService->notifySnapshotRevisionsRequested($snapshot, $request->reason);
            } catch (\Exception $e) {
                // Log notification error but don't fail the transaction
                Log::error('Failed to send notification: ' . $e->getMessage());
            }

            // Commit the transaction
            DB::commit();

            // Redirect back with success message
            Toaster::success('Revisions have been requested.');
            return redirect()->route('projects.manage', $pitch->project);
        } catch (UnauthorizedActionException $e) {
            DB::rollBack();
            Log::error('Unauthorized attempt to request revisions', [
                'pitch_id' => $pitch->id,
                'user_id' => Auth::id() ?? 'unauthenticated',
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (InvalidStatusTransitionException | SnapshotException $e) {
            DB::rollBack();
            Log::error('Invalid revision request attempt', [
                'pitch_id' => $pitch->id,
                'status' => $pitch->status,
                'error' => $e->getMessage()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error requesting revisions', [
                'pitch_id' => $pitch->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            Toaster::error($e->getMessage());
            return redirect()->back();
        }
    }
}
