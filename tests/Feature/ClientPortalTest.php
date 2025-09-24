<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\PitchWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function valid_signed_url_grants_access_to_client_portal()
    {
        // Arrange: Mock NotificationService
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        });

        // Create a producer and a client management project
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'testclient@example.com',
            'title' => 'Client Project Alpha',
        ]);

        // The ProjectObserver should have created a pitch automatically
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch, 'Pitch was not automatically created for client management project.');

        // Generate a valid signed URL
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Access the signed URL
        $response = $this->get($signedUrl);

        // Assert: Check for successful access and project content
        $response->assertStatus(200);
        $response->assertSee($project->title); // Check if the project title is visible (using the placeholder view)
    }

    /** @test */
    public function invalid_signed_url_is_rejected()
    {
        // Arrange: Mock NotificationService
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        });

        // Create project
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
        ]);

        // Generate a URL
        $validUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Tamper with the URL (invalidate the signature)
        $invalidUrl = str_replace('signature=', 'signature=invalid', $validUrl);

        // Act: Access the invalid URL
        $response = $this->get($invalidUrl);

        // Assert: Check for forbidden status (middleware should reject)
        $response->assertStatus(403);
    }

    /** @test */
    public function expired_signed_url_is_rejected()
    {
        // Arrange: Mock NotificationService
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        });

        // Create project
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client2@example.com',
        ]);

        // Generate an expired signed URL
        $expiredUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->subMinute(), // Expiry time in the past
            ['project' => $project->id]
        );

        // Act: Access the expired URL
        $response = $this->get($expiredUrl);

        // Assert: Check for forbidden status (middleware should reject)
        $response->assertStatus(403);
    }

    /** @test */
    public function non_client_management_project_cannot_access_portal()
    {
        // Arrange: Mock NotificationService (though it shouldn't be called for non-client projects)
        // No expectation needed here as the observer condition won't match
        $this->mock(NotificationService::class);

        // Create a producer and a *regular* project (not client management)
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD, // Use an actual non-client type
        ]);

        // Generate a signed URL (even though access should be denied based on type)
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Access the signed URL
        $response = $this->get($signedUrl);

        // Assert: Check for Not Found status (controller should abort with 404)
        $response->assertStatus(404);
    }

    /** @test */
    public function client_management_project_without_pitch_cannot_access_portal()
    {
        // Arrange: Mock NotificationService for initial project creation
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        });

        // Create a producer and a client management project
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'anotherclient@example.com',
        ]);

        // Manually delete the auto-created pitch to simulate the error condition
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        $pitch->delete();
        $project->refresh(); // Refresh project state
        $this->assertNull($project->pitches()->first(), 'Pitch was not deleted for test setup.');

        // Generate a signed URL
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Access the signed URL
        $response = $this->get($signedUrl);

        // Assert: Check for Not Found status (controller should abort with 404 due to missing pitch)
        $response->assertStatus(404);
    }

    // --- Client Action Tests ---

    /** @test */
    public function client_can_store_comment_via_portal()
    {
        // Arrange: Mock NotificationService
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull(); // For project creation
        $notificationMock->shouldReceive('notifyProducerClientCommented')->once(); // Expect comment notification

        // Create project and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'commenter@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Generate a valid signed VIEW URL (context for POST)
        $signedViewUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Generate a SIGNED POST action URL
        $postCommentUrl = URL::temporarySignedRoute(
            'client.portal.comments.store',
            now()->addMinutes(15), // Use same expiry as view for consistency
            ['project' => $project->id]
        );
        $commentText = 'This is a test comment from the client.';

        // Act: Make POST request to the signed action URL
        $response = $this->from($signedViewUrl) // Keep referer for good practice
            ->post($postCommentUrl, [
                'comment' => $commentText,
            ]);

        // Assert
        $response->assertStatus(302); // Should redirect back
        $response->assertSessionHas('success', 'Comment added successfully.'); // Check session on redirect response

        // Assert comment exists in database (as PitchEvent)
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'client_comment',
            'comment' => $commentText,
            'created_by' => null, // Client comments have null user
            // 'metadata->client_email' => $project->client_email // Need to check how metadata is stored/queried
        ]);

        // Check metadata separately if direct query is complex
        $event = $pitch->events()->where('comment', $commentText)->first();
        $this->assertNotNull($event);
        $this->assertEquals($project->client_email, $event->metadata['client_email'] ?? null);
    }

    /** @test */
    public function client_cannot_store_empty_comment()
    {
        // Arrange: Mock NotificationService (no comment notification expected)
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull(); // For project creation
        $notificationMock->shouldNotReceive('notifyProducerClientCommented'); // Ensure no notification is sent

        // Create project and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'emptycommenter@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Generate URLs
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $postCommentUrl = URL::temporarySignedRoute(
            'client.portal.comments.store',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Make POST request with empty comment to signed URL
        $response = $this->from($signedViewUrl)->post($postCommentUrl, [
            'comment' => '',
        ]);

        // Assert
        $response->assertStatus(302); // Should redirect back due to validation error
        $response->assertSessionHasErrors('comment'); // Check for validation error on the 'comment' field
        $response->assertSessionDoesntHaveErrors(['_token']); // Ensure it's not just a CSRF issue

        // Assert comment was NOT created
        $this->assertDatabaseMissing('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'client_comment',
        ]);
    }

    /** @test */
    public function client_can_approve_pitch_via_portal()
    {
        // Arrange
        // Mock NotificationService (only for project creation)
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        // No other notifications expected for simple approval

        // Mock PitchWorkflowService
        $workflowMock = $this->mock(PitchWorkflowService::class);

        // Create project and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'approver@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Set pitch status to ready for review
        $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
        $pitch->save();

        // Expect the workflow service method to be called
        $workflowMock->shouldReceive('clientApprovePitch')
            ->once()
            ->withArgs(function ($argPitch, $argEmail) use ($pitch, $project) {
                return $argPitch->id === $pitch->id && $argEmail === $project->client_email;
            })
            ->andReturnUsing(function ($argPitch) {
                // Simulate the status change that the real service would do
                $argPitch->status = Pitch::STATUS_APPROVED;
                $argPitch->save();

                return $argPitch; // Return the modified pitch
            });

        // Generate URLs (Sign the POST action URL)
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $approveUrl = URL::temporarySignedRoute(
            'client.portal.approve',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Make POST request to the signed action URL
        $response = $this->from($signedViewUrl)
            ->post($approveUrl);

        // Assert
        $response->assertStatus(302); // Should redirect back
        $response->assertSessionHas('success', 'Pitch approved successfully.'); // Check session on redirect response

        // Assert pitch status changed (simulated by mock)
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_APPROVED, $pitch->status);

        // Optionally: Assert a PitchEvent was created for approval if the service does that
        // $this->assertDatabaseHas('pitch_events', ['pitch_id' => $pitch->id, 'event_type' => 'client_approved']);
    }

    /** @test */
    public function client_cannot_approve_pitch_in_invalid_status()
    {
        // Arrange
        $this->mock(NotificationService::class, fn ($mock) => $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull());

        // Mock PitchWorkflowService to throw exception
        $workflowMock = $this->mock(PitchWorkflowService::class);

        // Create project and pitch (default status is draft, which is invalid for approval)
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'invalidapprover@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        $initialStatus = $pitch->status;
        $this->assertNotEquals(Pitch::STATUS_READY_FOR_REVIEW, $initialStatus); // Ensure it's not ready

        // Expect the workflow service method to be called and throw an exception
        $exceptionMessage = 'Pitch cannot be approved from its current state.';
        $workflowMock->shouldReceive('clientApprovePitch')
            ->once()
            ->withArgs(fn ($p, $e) => $p->id === $pitch->id && $e === $project->client_email)
            ->andThrow(new \App\Exceptions\Pitch\InvalidStatusTransitionException(
                $initialStatus, // Current status
                Pitch::STATUS_APPROVED, // Target status
                $exceptionMessage
            ));

        // Generate URLs (Sign the POST action URL)
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $approveUrl = URL::temporarySignedRoute(
            'client.portal.approve',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Make POST request to signed URL
        $response = $this->from($signedViewUrl)->post($approveUrl);

        // Assert
        $response->assertStatus(302); // Should redirect back
        $response->assertSessionHasErrors('approval'); // Check for the specific error key used in controller

        // Construct the expected full error message from the exception
        $expectedFullErrorMessage = $exceptionMessage.": Cannot transition from '{$initialStatus}' to '".Pitch::STATUS_APPROVED."'";
        $this->assertEquals($expectedFullErrorMessage, session('errors')->first('approval')); // Check full error message

        // Assert pitch status did NOT change
        $pitch->refresh();
        $this->assertEquals($initialStatus, $pitch->status);
    }

    /** @test */
    public function client_can_request_revisions_via_portal()
    {
        // Arrange
        $this->mock(NotificationService::class, fn ($mock) => $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull());
        $workflowMock = $this->mock(PitchWorkflowService::class);

        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'reviser@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
        $pitch->save();

        $feedbackText = 'Please revise the intro section.';

        // Expect workflow service call
        $workflowMock->shouldReceive('clientRequestRevisions')
            ->once()
            ->withArgs(function ($argPitch, $argFeedback, $argEmail) use ($pitch, $feedbackText, $project) {
                return $argPitch->id === $pitch->id && $argFeedback === $feedbackText && $argEmail === $project->client_email;
            })
            ->andReturnUsing(function ($argPitch) {
                // Simulate status change
                $argPitch->status = Pitch::STATUS_REVISIONS_REQUESTED;
                $argPitch->save();

                return $argPitch; // Return the modified pitch
            });

        // Generate URLs (Sign the POST action URL)
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $revisionsUrl = URL::temporarySignedRoute(
            'client.portal.revisions',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act
        $response = $this->from($signedViewUrl)
            ->post($revisionsUrl, ['feedback' => $feedbackText]);

        // Assert
        $response->assertStatus(302); // Expect redirect back
        $response->assertSessionHas('success', 'Revision request submitted successfully.'); // Check session on redirect response

        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_REVISIONS_REQUESTED, $pitch->status);

        // Optionally: Assert PitchEvent for revision request
        // $this->assertDatabaseHas('pitch_events', ['pitch_id' => $pitch->id, 'event_type' => 'client_revision_request']);
    }

    /** @test */
    public function client_cannot_request_revisions_with_empty_feedback()
    {
        // Arrange
        $this->mock(NotificationService::class, fn ($mock) => $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull());
        $workflowMock = $this->mock(PitchWorkflowService::class);
        $workflowMock->shouldNotReceive('clientRequestRevisions'); // Ensure service method is NOT called

        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'emptyreviser@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
        $pitch->save();
        $initialStatus = $pitch->status;

        // Generate URLs (Sign the POST action URL)
        $signedViewUrl = URL::temporarySignedRoute('client.portal.view', now()->addMinutes(15), ['project' => $project->id]);
        $revisionsUrl = URL::temporarySignedRoute(
            'client.portal.revisions',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        // Act: Post with empty feedback to signed URL
        $response = $this->from($signedViewUrl)->post($revisionsUrl, ['feedback' => '']);

        // Assert
        $response->assertStatus(302); // Redirect back on validation error
        $response->assertSessionHasErrors('feedback');

        // Assert status did not change
        $pitch->refresh();
        $this->assertEquals($initialStatus, $pitch->status);
    }

    /** @test */
    public function client_cannot_request_revisions_in_invalid_status()
    {
        // Arrange: Mock NotificationService
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull(); // For project creation
        // No revision notification should be sent
        $notificationMock->shouldNotReceive('notifyProducerClientRevisionsRequested');

        // Create project and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'reviser-invalid@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Set an invalid status (e.g., IN_PROGRESS)
        $initialStatus = Pitch::STATUS_IN_PROGRESS;
        $pitch->status = $initialStatus;
        $pitch->save();

        // Generate signed URL for the action
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.revisions', // Action route
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        $postData = [
            'feedback' => 'This feedback should not be processed.',
        ];

        // Act: Attempt to request revisions via POST
        $response = $this->post($signedUrl, $postData);

        // Assert: Check for error, unchanged status, and no event
        // Expecting a back redirect with errors due to InvalidStatusTransitionException
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHasErrors(['revisions']);

        $pitch->refresh();
        $this->assertEquals($initialStatus, $pitch->status, "Pitch status should not have changed from {$initialStatus}.");

        // Verify no revision event was created
        $this->assertDatabaseMissing('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'client_revisions_requested',
        ]);

        // --- Optional: Test other invalid statuses ---
        // Repeat for APPROVED status
        $pitch->status = Pitch::STATUS_APPROVED;
        $pitch->save();
        $response = $this->post($signedUrl, $postData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['revisions']);
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_APPROVED, $pitch->status);
        $this->assertDatabaseMissing('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'client_revisions_requested',
        ]);

        // Repeat for COMPLETED status
        $pitch->status = Pitch::STATUS_COMPLETED;
        $pitch->save();
        $response = $this->post($signedUrl, $postData);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['revisions']);
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_COMPLETED, $pitch->status);
        $this->assertDatabaseMissing('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'client_revisions_requested',
        ]);
    }

    /** @test */
    public function producer_submit_triggers_client_review_notification()
    {
        // Arrange: Mock NotificationService
        $notificationMock = $this->mock(NotificationService::class);
        // Expect invite notification on creation
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        // Crucial expectation: Client review notification
        $notificationMock->shouldReceive('notifyClientReviewReady')
            ->once()
            ->withArgs(function (Pitch $notifiedPitch, string $signedUrl) {
                // Check if URL is a valid signed URL for the client portal view
                // We can't know the exact signature, but we can parse and check structure/expiry
                try {
                    $route = app('url')->route('client.portal.view', ['project' => $notifiedPitch->project_id], false);
                    // Basic check: Does the generated URL start with the expected route path?
                    if (strpos($signedUrl, $route) === false) {
                        return false;
                    }

                    // Further checks could parse query params for 'expires' and 'signature'
                    // if needed, but checking the base path is a good start.
                    return true;
                } catch (\Exception $e) {
                    return false;
                }
            });

        // Create producer, project, and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client-to-notify@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        // Ensure pitch is in a state where it can be submitted (IN_PROGRESS or CLIENT_REVISIONS_REQUESTED)
        $pitch->status = Pitch::STATUS_IN_PROGRESS;
        $pitch->save();

        // Simulate file upload before submission
        \App\Models\PitchFile::factory()->create([
            'pitch_id' => $pitch->id,
            'user_id' => $producer->id,
            'file_path' => 'dummy/path/to/file.zip', // Path doesn't need to exist for this test
            'file_name' => 'file.zip',
        ]);
        $pitch->refresh(); // Refresh to ensure the relationship count is updated

        // Get the workflow service
        $workflowService = app(PitchWorkflowService::class);

        // Act: Producer submits the pitch for review
        // Authenticate as the producer
        $this->actingAs($producer);
        $updatedPitch = $workflowService->submitPitchForReview($pitch, $producer);

        // Assert
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $updatedPitch->status);
        // Notification mock expectation is checked automatically by PHPUnit

        // Optional: Assert snapshot was created
        $this->assertDatabaseHas('pitch_snapshots', [
            'pitch_id' => $pitch->id,
            'status' => \App\Models\PitchSnapshot::STATUS_PENDING, // Assuming snapshot is created in pending state
        ]);
        $this->assertNotNull($updatedPitch->current_snapshot_id);
    }

    /** @test */
    public function producer_can_complete_client_management_pitch_after_client_approval()
    {
        // Arrange
        // Mock NotificationService (expect invite and completion notifications)
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once();
        $notificationMock->shouldReceive('notifyPitchCompleted')->once(); // Called for producer
        $notificationMock->shouldReceive('notifyClientProjectCompleted')->once(); // Called for client

        // Mock ProjectManagementService to avoid side effects on Project model
        $projectManagementMock = $this->mock(\App\Services\Project\ProjectManagementService::class);
        $projectManagementMock->shouldReceive('completeProject')->once();

        // Create producer, project, and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'completed-client@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Set pitch status to APPROVED (simulating client approval)
        $pitch->status = Pitch::STATUS_APPROVED;
        $pitch->save();

        // Get the completion service
        $completionService = app(\App\Services\PitchCompletionService::class);

        // Act: Producer completes the pitch
        // Authenticate as the producer (who is the project owner in client management)
        $this->actingAs($producer);
        $completedPitch = $completionService->completePitch($pitch, $producer);

        // Assert
        // 1. Pitch status is COMPLETED
        $this->assertEquals(Pitch::STATUS_COMPLETED, $completedPitch->status);
        $this->assertNotNull($completedPitch->completed_at);

        // 2. Completion event created
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'status_change',
            'created_by' => $producer->id,
        ]);
    }

    /** @test */
    public function producer_cannot_complete_client_management_pitch_without_approval()
    {
        // Arrange
        // Mock NotificationService (only for project creation)
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once();

        // Create producer, project, and pitch
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'incomplete-client@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Keep pitch status as READY_FOR_REVIEW (not yet approved by client)
        $pitch->status = Pitch::STATUS_READY_FOR_REVIEW;
        $pitch->save();

        // Get the completion service
        $completionService = app(\App\Services\PitchCompletionService::class);

        // Act & Assert: Attempting to complete should throw exception
        $this->actingAs($producer);
        $this->expectException(\App\Exceptions\Pitch\CompletionValidationException::class);
        $completionService->completePitch($pitch, $producer);
    }

    /** @test */
    public function producer_can_resend_client_invite()
    {
        // Arrange
        Carbon::setTestNow(now()); // Freeze time initially
        $firstSignedUrl = null;
        $secondSignedUrl = null;

        // Mock NotificationService - expect invite TWICE
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')
            ->twice() // Expect it twice (creation + resend)
            ->andReturnUsing(function (Project $notifiedProject, string $signedUrl) use (&$firstSignedUrl, &$secondSignedUrl) {
                // Capture the URLs to compare later
                if ($firstSignedUrl === null) {
                    $firstSignedUrl = $signedUrl;
                } else {
                    $secondSignedUrl = $signedUrl;
                }

                return null;
            });

        // Create producer, project, and pitch (triggers first notification with frozen time)
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'resend-target@example.com',
        ]);
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        $this->assertNotNull($firstSignedUrl, 'First notification did not capture URL.');

        // Advance time before resending
        Carbon::setTestNow(now()->addMinute());

        // Act: Producer resends the invite via the new route (triggers second notification with advanced time)
        // Authenticate as the producer
        $response = $this->actingAs($producer)
            ->post(route('client.portal.resend_invite', ['project' => $project->id]));

        // Assert
        // 1. Response indicates success (redirect back with success flash)
        $response->assertStatus(302); // Redirect
        $response->assertSessionHas('success', 'Client invitation resent successfully.');

        // 2. Notification mock expectation (twice) checked automatically by PHPUnit

        // 3. Verify two different URLs were captured (ensures a new one was generated)
        $this->assertNotNull($secondSignedUrl, 'Second notification did not capture URL.');
        $this->assertNotEquals($firstSignedUrl, $secondSignedUrl, 'Resend notification did not use a new signed URL.');

        // Optional: Check structure of the second URL again
        try {
            $routePath = app('url')->route('client.portal.view', ['project' => $project->id], false);
            $this->assertStringContainsString($routePath, $secondSignedUrl);
        } catch (\Exception $e) {
            $this->fail('Could not verify structure of the second signed URL: '.$e->getMessage());
        }

        // Clean up: Reset time
        Carbon::setTestNow();
    }

    /** @test */
    public function client_portal_file_download_security_is_enforced()
    {
        // Arrange: Set up test dependencies & environment
        Storage::fake('local');

        // Create producer and client management project
        $producer = User::factory()->create();
        $otherProducer = User::factory()->create(); // For testing access control
        $otherClient = User::factory()->create();   // For testing access control

        // Mock NotificationService (not testing notifications here)
        $notificationMock = $this->mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
        $notificationMock->shouldReceive('notifyClientReviewReady')->once()->andReturnNull();

        // Create project and pitch
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);

        // Get auto-created pitch and prepare a test file
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Set up a test file (owned by producer)
        $testFile = UploadedFile::fake()->create('test-delivery.mp3', 500);
        $filePath = Storage::disk('local')->putFile('pitch_files/'.$pitch->id, $testFile);
        $pitchFile = \App\Models\PitchFile::create([
            'pitch_id' => $pitch->id,
            'user_id' => $producer->id,
            'file_path' => $filePath,
            'file_name' => basename($filePath),
            'original_name' => $testFile->getClientOriginalName(),
            'mime_type' => $testFile->getMimeType(),
            'size' => $testFile->getSize(),
        ]);

        // Set pitch status for submission
        $pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        // Generate signed URLs
        $clientViewUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addMinutes(15),
            ['project' => $project->id]
        );

        $clientDownloadUrl = URL::temporarySignedRoute(
            'client.portal.download_file',
            now()->addMinutes(15),
            ['project' => $project->id, 'file' => $pitchFile->id]
        );

        // Test Cases:

        // 1. Client with signed URL can download the file
        $clientResponse = $this->get($clientDownloadUrl);
        $clientResponse->assertStatus(200);
        $clientResponse->assertHeader('Content-Type', $pitchFile->mime_type);

        // 2. Tampering with file ID results in 404/403
        $tamperedFileUrl = URL::temporarySignedRoute(
            'client.portal.download_file',
            now()->addMinutes(15),
            ['project' => $project->id, 'file' => 99999] // Non-existent file ID
        );
        $tamperedResponse = $this->get($tamperedFileUrl);
        $tamperedResponse->assertStatus(404); // File not found

        // 3. Missing/invalid signature is rejected
        $unsignedUrl = route('client.portal.download_file', ['project' => $project->id, 'file' => $pitchFile->id]);
        $unsignedResponse = $this->get($unsignedUrl);
        $unsignedResponse->assertStatus(403); // Forbidden without signature

        // 4. Client from different project cannot access (wrong project ID in URL)
        $otherProject = Project::factory()->create([
            'user_id' => $otherProducer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'other@example.com',
        ]);

        $wrongProjectUrl = URL::temporarySignedRoute(
            'client.portal.download_file',
            now()->addMinutes(15),
            ['project' => $otherProject->id, 'file' => $pitchFile->id] // Wrong project
        );

        $wrongProjectResponse = $this->get($wrongProjectUrl);
        $wrongProjectResponse->assertStatus(403); // Should be forbidden

        // 5. Even logged-in users must have correct signature
        $this->actingAs($otherClient); // Log in as another user
        $loggedInResponse = $this->get($unsignedUrl); // Try without signature
        $loggedInResponse->assertStatus(403); // Should still be forbidden

        // 6. Only producer and client can download (producer via normal auth)
        $this->actingAs($producer); // Log in as the producer
        $producerResponse = $this->get(route('pitch.files.download', ['file' => $pitchFile->id]));
        $producerResponse->assertStatus(200); // Producer can access via normal route

        // 7. Other producer cannot access
        $this->actingAs($otherProducer);
        $otherProducerResponse = $this->get(route('pitch.files.download', ['file' => $pitchFile->id]));
        $otherProducerResponse->assertStatus(403); // Other producer cannot access
    }
}
