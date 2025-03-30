<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Policies\PitchPolicy; // Import the policy
use Illuminate\Foundation\Testing\RefreshDatabase;

class PitchPolicyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function project_owner_can_complete_approved_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'payment_status' => Pitch::PAYMENT_STATUS_PENDING // Or NOT_REQUIRED
            ]);

        $this->assertTrue($projectOwner->can('complete', $pitch));
    }

    /** @test */
    public function project_owner_can_complete_approved_pitch_with_not_required_payment()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create(['budget' => 0]);
        $pitch = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'payment_status' => Pitch::PAYMENT_STATUS_NOT_REQUIRED
            ]);

        $this->assertTrue($projectOwner->can('complete', $pitch));
    }

    /** @test */
    public function project_owner_cannot_complete_non_approved_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $statusesToTest = [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_PENDING_REVIEW,
            Pitch::STATUS_DENIED,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CLOSED,
        ];

        foreach ($statusesToTest as $status) {
            $pitch = Pitch::factory()
                ->for($project)->for($pitchCreator, 'user')
                ->create(['status' => $status]);

            $this->assertFalse($projectOwner->can('complete', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function project_owner_cannot_complete_approved_pitch_if_payment_is_paid()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'payment_status' => Pitch::PAYMENT_STATUS_PAID
            ]);

        $this->assertFalse($projectOwner->can('complete', $pitch));
    }

    /** @test */
    public function project_owner_cannot_complete_approved_pitch_if_payment_is_processing()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create([
                'status' => Pitch::STATUS_APPROVED,
                'payment_status' => Pitch::PAYMENT_STATUS_PROCESSING
            ]);

        $this->assertFalse($projectOwner->can('complete', $pitch));
    }

    /** @test */
    public function pitch_creator_cannot_complete_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_APPROVED]);

        $this->assertFalse($pitchCreator->can('complete', $pitch));
    }

    /** @test */
    public function unrelated_user_cannot_complete_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()
            ->for($project)->for($pitchCreator, 'user')
            ->create(['status' => Pitch::STATUS_APPROVED]);

        $this->assertFalse($unrelatedUser->can('complete', $pitch));
    }

    // --- view Tests ---

    /** @test */
    public function project_owner_can_view_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create();

        $this->assertTrue($projectOwner->can('view', $pitch));
    }

    /** @test */
    public function pitch_creator_can_view_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create();

        $this->assertTrue($pitchCreator->can('view', $pitch));
    }

    /** @test */
    public function unrelated_user_cannot_view_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create();

        $this->assertFalse($unrelatedUser->can('view', $pitch));
    }

    // --- createPitch Tests ---

    /** @test */
    public function potential_producer_can_create_pitch_for_open_project()
    {
        $projectOwner = User::factory()->create();
        $potentialProducer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create(['status' => Project::STATUS_OPEN]);

        $this->assertTrue($potentialProducer->can('createPitch', $project));
    }

    /** @test */
    public function user_cannot_create_pitch_if_already_pitched()
    {
        // Create users and project first
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();
        
        // Create project using create()
        $project = Project::factory()->create([
            'user_id' => $projectOwner->id,
            'status' => Project::STATUS_OPEN
        ]);
        
        // Create pitch manually (not using for() relationship method)
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id
        ]);
        
        // Make a custom Gate check instead of using the can() helper
        $policy = new PitchPolicy();
        $result = $policy->createPitch($producer, $project);
        
        $this->assertFalse($result, "Producer should not be able to create a pitch for a project they've already pitched");
    }

    /** @test */
    public function user_cannot_create_pitch_for_closed_project()
    {
        $projectOwner = User::factory()->create();
        $potentialProducer = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create(['status' => Project::STATUS_COMPLETED]);

        $this->assertFalse($potentialProducer->can('createPitch', $project));
    }

    /** @test */
    public function project_owner_cannot_create_pitch_for_own_project()
    {
        $projectOwner = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create(['status' => Project::STATUS_OPEN]);

        $this->assertFalse($projectOwner->can('createPitch', $project));
    }

    // --- update Tests ---

    /** @test */
    public function pitch_creator_can_update_pitch_in_valid_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $validStatuses = [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_DENIED,
            Pitch::STATUS_REVISIONS_REQUESTED,
            Pitch::STATUS_PENDING_REVIEW // Note: PENDING_REVIEW might be internal, check if intended
        ];

        foreach ($validStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertTrue($pitchCreator->can('update', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function pitch_creator_cannot_update_pitch_in_invalid_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $invalidStatuses = [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CLOSED,
        ];

        foreach ($invalidStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertFalse($pitchCreator->can('update', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function project_owner_cannot_update_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($projectOwner->can('update', $pitch));
    }

    /** @test */
    public function unrelated_user_cannot_update_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($unrelatedUser->can('update', $pitch));
    }

    // --- delete Tests ---

    /** @test */
    public function pitch_creator_can_delete_pitch_in_valid_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $validStatuses = [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_DENIED,
            Pitch::STATUS_REVISIONS_REQUESTED
        ];

        foreach ($validStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertTrue($pitchCreator->can('delete', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function pitch_creator_cannot_delete_pitch_in_invalid_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $invalidStatuses = [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CLOSED,
            Pitch::STATUS_PENDING_REVIEW
        ];

        foreach ($invalidStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertFalse($pitchCreator->can('delete', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function project_owner_cannot_delete_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($projectOwner->can('delete', $pitch));
    }

    /** @test */
    public function unrelated_user_cannot_delete_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($unrelatedUser->can('delete', $pitch));
    }

    // --- approveInitial Tests ---

    /** @test */
    public function project_owner_can_approve_initial_pending_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_PENDING]);

        $this->assertTrue($projectOwner->can('approveInitial', $pitch));
    }

    /** @test */
    public function project_owner_cannot_approve_initial_non_pending_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($projectOwner->can('approveInitial', $pitch));
    }

    /** @test */
    public function pitch_creator_cannot_approve_initial_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_PENDING]);

        $this->assertFalse($pitchCreator->can('approveInitial', $pitch));
    }

    // --- approveSubmission / denySubmission / requestRevisions Tests ---

    /** @test */
    public function project_owner_can_review_pitch_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        $this->assertTrue($projectOwner->can('approveSubmission', $pitch));
        $this->assertTrue($projectOwner->can('denySubmission', $pitch));
        $this->assertTrue($projectOwner->can('requestRevisions', $pitch));
    }

    /** @test */
    public function project_owner_cannot_review_pitch_not_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $invalidStatuses = [
             Pitch::STATUS_PENDING,
             Pitch::STATUS_IN_PROGRESS,
             Pitch::STATUS_APPROVED,
             Pitch::STATUS_COMPLETED,
             Pitch::STATUS_CLOSED,
             Pitch::STATUS_DENIED,
             Pitch::STATUS_REVISIONS_REQUESTED,
             Pitch::STATUS_PENDING_REVIEW
        ];

        foreach ($invalidStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertFalse($projectOwner->can('approveSubmission', $pitch), "Approve failed for status: {$status}");
            $this->assertFalse($projectOwner->can('denySubmission', $pitch), "Deny failed for status: {$status}");
            $this->assertFalse($projectOwner->can('requestRevisions', $pitch), "Revisions failed for status: {$status}");
        }
    }

     /** @test */
    public function project_owner_cannot_review_paid_or_processing_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $paymentStatuses = [
            Pitch::PAYMENT_STATUS_PAID,
            Pitch::PAYMENT_STATUS_PROCESSING,
        ];

        foreach ($paymentStatuses as $paymentStatus) {
            // Create a pitch that is technically READY_FOR_REVIEW but already paid/processing (edge case)
             $pitch = Pitch::factory()
                 ->for($project)->for($pitchCreator, 'user')
                 ->create(['status' => Pitch::STATUS_READY_FOR_REVIEW, 'payment_status' => $paymentStatus]);

            $this->assertFalse($projectOwner->can('approveSubmission', $pitch), "Approve failed for payment status: {$paymentStatus}");
            $this->assertFalse($projectOwner->can('denySubmission', $pitch), "Deny failed for payment status: {$paymentStatus}");
            $this->assertFalse($projectOwner->can('requestRevisions', $pitch), "Revisions failed for payment status: {$paymentStatus}");
        }
    }

    /** @test */
    public function pitch_creator_cannot_review_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        $this->assertFalse($pitchCreator->can('approveSubmission', $pitch));
        $this->assertFalse($pitchCreator->can('denySubmission', $pitch));
        $this->assertFalse($pitchCreator->can('requestRevisions', $pitch));
    }

    // --- cancelSubmission Tests ---

    /** @test */
    public function pitch_creator_can_cancel_submission_if_ready_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        // Need a current snapshot that is pending
        $snapshot = \App\Models\PitchSnapshot::factory()->create(['status' => \App\Models\PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create([
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'current_snapshot_id' => $snapshot->id
        ]);
         // Associate snapshot manually if factory doesn't
         $snapshot->pitch_id = $pitch->id;
         $snapshot->save();

        $this->assertTrue($pitchCreator->can('cancelSubmission', $pitch));
    }

    /** @test */
    public function pitch_creator_cannot_cancel_submission_in_wrong_status()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = \App\Models\PitchSnapshot::factory()->create(['status' => \App\Models\PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create([
            'status' => Pitch::STATUS_IN_PROGRESS, // Wrong status
            'current_snapshot_id' => $snapshot->id
        ]);
         $snapshot->pitch_id = $pitch->id;
         $snapshot->save();

        $this->assertFalse($pitchCreator->can('cancelSubmission', $pitch));
    }

     /** @test */
    public function pitch_creator_cannot_cancel_submission_if_snapshot_not_pending()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = \App\Models\PitchSnapshot::factory()->create(['status' => \App\Models\PitchSnapshot::STATUS_ACCEPTED]); // Snapshot not pending
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create([
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'current_snapshot_id' => $snapshot->id
        ]);
         $snapshot->pitch_id = $pitch->id;
         $snapshot->save();

        $this->assertFalse($pitchCreator->can('cancelSubmission', $pitch));
    }

    /** @test */
    public function project_owner_cannot_cancel_submission()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $snapshot = \App\Models\PitchSnapshot::factory()->create(['status' => \App\Models\PitchSnapshot::STATUS_PENDING]);
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create([
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'current_snapshot_id' => $snapshot->id
        ]);
         $snapshot->pitch_id = $pitch->id;
         $snapshot->save();

        $this->assertFalse($projectOwner->can('cancelSubmission', $pitch));
    }

    // --- submitForReview Tests ---

    /** @test */
    public function pitch_creator_can_submit_for_review_in_valid_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $validStatuses = [
            Pitch::STATUS_IN_PROGRESS,
            Pitch::STATUS_REVISIONS_REQUESTED
            // Add DENIED if policy allows resubmission from DENIED
        ];

        foreach ($validStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertTrue($pitchCreator->can('submitForReview', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function pitch_creator_cannot_submit_for_review_in_invalid_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $invalidStatuses = [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CLOSED,
            Pitch::STATUS_PENDING_REVIEW,
            // Add DENIED here if it's not a valid status to submit from
            Pitch::STATUS_DENIED
        ];

        foreach ($invalidStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertFalse($pitchCreator->can('submitForReview', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function project_owner_cannot_submit_for_review()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->assertFalse($projectOwner->can('submitForReview', $pitch));
    }

    // --- uploadFile Tests (Moved from PitchFilePolicyTest) ---

    /** @test */
    public function pitch_creator_can_upload_file_to_pitch_in_progress()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        $this->assertTrue($pitchCreator->can('uploadFile', $pitch));
    }

    /** @test */
    public function pitch_creator_can_upload_file_to_pitch_with_revisions_requested()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);
        $this->assertTrue($pitchCreator->can('uploadFile', $pitch));
    }

    /** @test */
    public function pitch_creator_cannot_upload_file_to_pitch_in_other_statuses()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();

        $disallowedStatuses = [
            Pitch::STATUS_PENDING,
            Pitch::STATUS_APPROVED,
            Pitch::STATUS_READY_FOR_REVIEW,
            Pitch::STATUS_DENIED,
            Pitch::STATUS_COMPLETED,
            Pitch::STATUS_CLOSED,
        ];

        foreach ($disallowedStatuses as $status) {
            $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => $status]);
            $this->assertFalse($pitchCreator->can('uploadFile', $pitch), "Failed for status: {$status}");
        }
    }

    /** @test */
    public function project_owner_cannot_upload_file_to_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        $this->assertFalse($projectOwner->can('uploadFile', $pitch));
    }

    /** @test */
    public function unrelated_user_cannot_upload_file_to_pitch()
    {
        $projectOwner = User::factory()->create();
        $pitchCreator = User::factory()->create();
        $unrelatedUser = User::factory()->create();
        $project = Project::factory()->for($projectOwner, 'user')->create();
        $pitch = Pitch::factory()->for($project)->for($pitchCreator, 'user')->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        $this->assertFalse($unrelatedUser->can('uploadFile', $pitch));
    }

    // --- downloadFile / deleteFile tests remain in PitchFilePolicyTest ---
} 