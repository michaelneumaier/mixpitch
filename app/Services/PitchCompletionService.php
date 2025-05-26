<?php
namespace App\Services;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Models\PitchSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Pitch\CompletionValidationException; // Assuming this exists or will be created
use App\Exceptions\Pitch\UnauthorizedActionException;
use App\Services\Project\ProjectManagementService;
use Illuminate\Support\Facades\URL;

class PitchCompletionService
{
    protected $projectManagementService;
    protected $notificationService;

    public function __construct(
        ProjectManagementService $projectManagementService,
        NotificationService $notificationService
    ) {
        $this->projectManagementService = $projectManagementService;
        $this->notificationService = $notificationService;
    }

    /**
     * Mark a pitch as completed, close others, and complete the project.
     *
     * @param Pitch $pitchToComplete The pitch being marked as complete.
     * @param User $completingUser (Project Owner)
     * @param string|null $feedback Optional feedback.
     * @param int|null $rating Optional rating (1-5).
     * @return Pitch The completed pitch.
     * @throws CompletionValidationException|UnauthorizedActionException|\RuntimeException
     */
    public function completePitch(Pitch $pitchToComplete, User $completingUser, ?string $feedback = null, ?int $rating = null): Pitch
    {
        $project = $pitchToComplete->project;

        // Authorization: Ensure user is the project owner
        if ($project->user_id !== $completingUser->id) {
            throw new UnauthorizedActionException('complete this pitch');
        }
        // Policy check (Ensure 'complete' ability exists in PitchPolicy)
        // if ($completingUser->cannot('complete', $pitchToComplete)) {
        //    throw new UnauthorizedActionException('You are not authorized to complete this pitch.');
        // }

        // Validation: Pitch must be approved
        if ($pitchToComplete->status !== Pitch::STATUS_APPROVED) {
            throw new CompletionValidationException('Pitch must be approved before it can be completed.');
        }
        // Add validation for rating
        if ($rating !== null && ($rating < 1 || $rating > 5)) {
            throw new CompletionValidationException('Invalid rating provided. Rating must be between 1 and 5.');
        }
        // Validation: Prevent re-completion if already paid/processing
        if ($pitchToComplete->payment_status === Pitch::PAYMENT_STATUS_PAID || $pitchToComplete->payment_status === Pitch::PAYMENT_STATUS_PROCESSING) {
            throw new CompletionValidationException('This pitch has already been completed and paid/is processing payment.');
        }

        try {
            DB::transaction(function() use ($pitchToComplete, $project, $completingUser, $feedback, $rating) {
                // 1. Mark the selected pitch as completed
                $pitchToComplete->status = Pitch::STATUS_COMPLETED;
                $pitchToComplete->completed_at = now();
                $pitchToComplete->completion_feedback = $feedback; // Or store in separate PitchFeedback model

                // Set initial payment status based on project budget
                if ($project->budget > 0) {
                    $pitchToComplete->payment_status = Pitch::PAYMENT_STATUS_PENDING;
                } else {
                    $pitchToComplete->payment_status = Pitch::PAYMENT_STATUS_NOT_REQUIRED;
                }
                $pitchToComplete->save();

                // 2. Update the final snapshot status to completed
                if ($pitchToComplete->currentSnapshot) {
                    $pitchToComplete->currentSnapshot->status = PitchSnapshot::STATUS_COMPLETED;
                    $pitchToComplete->currentSnapshot->save();
                }

                // 3. Close other active pitches for the same project (ONLY FOR STANDARD PROJECTS)
                if ($project->isStandard()) {
                    $otherPitches = $project->pitches()
                        ->where('id', '!=', $pitchToComplete->id)
                        ->whereNotIn('status', [
                            Pitch::STATUS_COMPLETED,
                            Pitch::STATUS_CLOSED,
                            Pitch::STATUS_DENIED // Keep denied as is?
                        ]) // Close pending, in_progress, approved, revisions_requested etc.
                        ->get();

                    foreach ($otherPitches as $otherPitch) {
                        $originalStatus = $otherPitch->status;
                        $otherPitch->status = Pitch::STATUS_CLOSED;
                        $otherPitch->save();

                        // Decline/cancel any pending snapshots for these closed pitches
                        if ($otherPitch->currentSnapshot && $otherPitch->currentSnapshot->status === PitchSnapshot::STATUS_PENDING) {
                            $otherPitch->currentSnapshot->status = PitchSnapshot::STATUS_DENIED; // Or maybe 'cancelled'?
                            $otherPitch->currentSnapshot->save();
                        }

                        Log::info('Pitch closed due to project completion', ['pitch_id' => $otherPitch->id, 'project_id' => $project->id, 'original_status' => $originalStatus]);
                        // Notify creator of the closed pitch
                        // Note: notifyPitchClosed() needs implementation in NotificationService
                        $this->notificationService->notifyPitchClosed($otherPitch);
                    }
                }

                // 4. Mark the project as completed (using the ProjectManagementService)
                $this->projectManagementService->completeProject($project);

                // 5. Create Event for the completed pitch, including the rating
                $pitchToComplete->events()->create([
                    'event_type' => 'status_change',
                    'comment' => 'Pitch marked as completed by project owner.' . ($feedback ? " Feedback: {$feedback}" : ''),
                    'status' => $pitchToComplete->status,
                    'created_by' => $completingUser->id,
                    'rating' => $rating, // Save the rating here
                ]);

                // Add explicit log for rating
                \Log::info('Created pitch completion event with rating', [
                    'pitch_id' => $pitchToComplete->id,
                    'project_id' => $project->id,
                    'rating' => $rating,
                    'producer_id' => $pitchToComplete->user_id,
                    'project_owner_id' => $completingUser->id,
                ]);

                // 6. Notify creator of the completed pitch
                // Note: notifyPitchCompleted() needs implementation in NotificationService
                $this->notificationService->notifyPitchCompleted($pitchToComplete);

            }); // End DB Transaction

            // 7. Notify client if it's a client management project (outside transaction)
            if ($project->isClientManagement()) {
                // Generate a (potentially non-actionable) link to the portal
                $signedUrl = URL::temporarySignedRoute(
                    'client.portal.view',
                    now()->addDays(config('mixpitch.client_portal_link_expiry_days', 7)), // Use config
                    ['project' => $project->id]
                );
                // Note: notifyClientProjectCompleted() needs implementation in NotificationService
                $this->notificationService->notifyClientProjectCompleted($pitchToComplete, $signedUrl, $feedback, $rating);
            }

            return $pitchToComplete->refresh(); // Return the updated pitch object

        } catch (\Exception $e) {
            Log::error('Error completing pitch', [
                'pitch_id' => $pitchToComplete->id,
                'user_id' => $completingUser->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Optional for detailed debugging
            ]);
            // Re-throw a runtime exception to indicate failure
            throw new \RuntimeException('An unexpected error occurred while completing the pitch.', 0, $e);
        }
    }
} 