<?php

namespace Tests\Feature;

use App\Models\Notification as NotificationModel;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use App\Notifications\UserNotification;
use App\Services\InvoiceService;
use App\Services\PitchCompletionService;
use App\Services\PitchWorkflowService;
use App\Services\Project\ProjectManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class StandardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $projectOwner;

    protected $producer;

    protected $pitchWorkflowService;

    protected $pitchCompletionService;

    protected $project;

    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local'); // Use fake storage
        NotificationFacade::fake(); // Fake notifications

        $this->projectOwner = User::factory()->create();
        $this->producer = User::factory()->create();

        // Mock ProjectManagementService dependency for PitchCompletionService
        $projectManagementServiceMock = Mockery::mock(ProjectManagementService::class);
        // Update mock to simulate status change
        $projectManagementServiceMock->shouldReceive('completeProject')
            ->zeroOrMoreTimes()
            ->withArgs(function (Project $project) {
                return true; // Basic check, ensures a Project object is passed
            })
            ->andReturnUsing(function (Project $project) {
                $project->status = Project::STATUS_COMPLETED; // Simulate setting the status
                $project->save(); // Simulate saving the change

                return $project; // Return the modified project
            });
        $this->app->instance(ProjectManagementService::class, $projectManagementServiceMock);

        // Mock InvoiceService dependency (used later by payment flow, but good practice)
        $invoiceServiceMock = Mockery::mock(InvoiceService::class);
        $invoiceServiceMock->shouldReceive('createInvoiceForPitchCompletion')->zeroOrMoreTimes();
        $this->app->instance(InvoiceService::class, $invoiceServiceMock);

        // Get actual service instances
        $this->pitchWorkflowService = $this->app->make(PitchWorkflowService::class);
        $this->pitchCompletionService = $this->app->make(PitchCompletionService::class);

        // Create Project
        $this->project = Project::factory()->for($this->projectOwner, 'user')->create([
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'status' => Project::STATUS_OPEN,
            'budget' => 500, // Add a budget for payment tests
        ]);
    }

    /** @test */
    public function test_standard_project_full_lifecycle_with_revisions()
    {
        // 1. Producer Submits Initial Pitch
        $this->actingAs($this->producer);
        $initialPitchData = ['title' => 'My Initial Pitch', 'description' => 'Description'];
        $this->pitch = $this->pitchWorkflowService->createPitch($this->project, $this->producer, $initialPitchData);
        $this->assertEquals(Pitch::STATUS_PENDING, $this->pitch->status);
        NotificationFacade::assertSentTo(
            $this->projectOwner,
            UserNotification::class,
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_PITCH_SUBMITTED && $notification->relatedId === $this->pitch->id
        );

        // 2. Owner Approves Initial Pitch
        $this->actingAs($this->projectOwner);
        $this->pitchWorkflowService->approveInitialPitch($this->pitch, $this->projectOwner);
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $this->pitch->status);
        NotificationFacade::assertSentTo(
            $this->producer,
            UserNotification::class,
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_PITCH_APPROVED && $notification->relatedId === $this->pitch->id
        );

        // 3. Producer Uploads File & Submits V1 for Review
        $this->actingAs($this->producer);
        $fileV1 = UploadedFile::fake()->create('version1.mp3', 1024);
        $filePathV1 = Storage::disk('local')->putFile('pitch_files/'.$this->pitch->id, $fileV1);
        $this->assertTrue($filePathV1 !== false, 'File V1 failed to store.'); // Ensure storage succeeded
        $pitchFileV1 = PitchFile::create([
            'pitch_id' => $this->pitch->id,
            'user_id' => $this->producer->id,
            'file_path' => $filePathV1,
            'file_name' => basename($filePathV1), // <-- Use basename of stored path for file_name
            'original_name' => $fileV1->getClientOriginalName(), // Keep original name too if needed
            'mime_type' => $fileV1->getMimeType(),
            'size' => $fileV1->getSize(),
        ]);
        $this->assertDatabaseHas('pitch_files', ['id' => $pitchFileV1->id, 'file_name' => basename($filePathV1)]); // Verify file_name in DB
        Storage::disk('local')->assertExists($filePathV1);

        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer);
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $this->pitch->status);
        $snapshotV1 = $this->pitch->currentSnapshot;
        $this->assertNotNull($snapshotV1);
        $this->assertEquals(1, $snapshotV1->snapshot_data['version']);
        NotificationFacade::assertSentTo(
            $this->projectOwner,
            UserNotification::class,
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_PITCH_READY_FOR_REVIEW && $notification->relatedId === $this->pitch->id
        );

        // 4. Owner Requests Revisions
        $this->actingAs($this->projectOwner);
        $revisionFeedback = 'Needs more cowbell!';
        $this->pitchWorkflowService->requestPitchRevisions($this->pitch, $snapshotV1->id, $this->projectOwner, $revisionFeedback);
        $this->pitch->refresh();
        $snapshotV1->refresh();
        $this->assertEquals(Pitch::STATUS_REVISIONS_REQUESTED, $this->pitch->status);
        $this->assertEquals(PitchSnapshot::STATUS_REVISIONS_REQUESTED, $snapshotV1->status);
        NotificationFacade::assertSentTo(
            $this->producer,
            UserNotification::class,
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_SNAPSHOT_REVISIONS_REQUESTED && $notification->relatedId === $snapshotV1->id
        );

        // 5. Producer Uploads File & Submits V2 for Review
        $this->actingAs($this->producer);
        $fileV2 = UploadedFile::fake()->create('version2_more_cowbell.mp3', 1024);
        $filePathV2 = Storage::disk('local')->putFile('pitch_files/'.$this->pitch->id, $fileV2);
        $this->assertTrue($filePathV2 !== false, 'File V2 failed to store.'); // Ensure storage succeeded
        $pitchFileV2 = PitchFile::create([
            'pitch_id' => $this->pitch->id,
            'user_id' => $this->producer->id,
            'file_path' => $filePathV2,
            'file_name' => basename($filePathV2), // <-- Use basename of stored path for file_name
            'original_name' => $fileV2->getClientOriginalName(), // Keep original name too if needed
            'mime_type' => $fileV2->getMimeType(),
            'size' => $fileV2->getSize(),
        ]);
        $this->assertDatabaseHas('pitch_files', ['id' => $pitchFileV2->id, 'file_name' => basename($filePathV2)]); // Verify file_name in DB
        Storage::disk('local')->assertExists($filePathV2);

        $responseFeedback = 'Cowbell added as requested.';
        $this->pitchWorkflowService->submitPitchForReview($this->pitch, $this->producer, $responseFeedback);
        $this->pitch->refresh();
        $snapshotV1->refresh(); // Refresh previous snapshot
        $snapshotV2 = $this->pitch->currentSnapshot;
        $this->assertNotNull($snapshotV2);
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $this->pitch->status);
        $this->assertEquals(2, $snapshotV2->snapshot_data['version']);
        $this->assertEquals($responseFeedback, $snapshotV2->snapshot_data['response_to_feedback']);
        $this->assertEquals(PitchSnapshot::STATUS_REVISION_ADDRESSED, $snapshotV1->status); // Check V1 snapshot status
        NotificationFacade::assertSentTo(
            $this->projectOwner,
            UserNotification::class,
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_PITCH_READY_FOR_REVIEW && $notification->relatedId === $this->pitch->id
        );

        // 6. Owner Approves Submission (V2)
        $this->actingAs($this->projectOwner);
        $this->pitchWorkflowService->approveSubmittedPitch($this->pitch, $snapshotV2->id, $this->projectOwner);
        $this->pitch->refresh();
        $snapshotV2->refresh();
        $this->assertEquals(Pitch::STATUS_APPROVED, $this->pitch->status);
        $this->assertEquals(PitchSnapshot::STATUS_ACCEPTED, $snapshotV2->status);
        NotificationFacade::assertSentTo(
            $this->producer,
            UserNotification::class,
            // Expect TYPE_SNAPSHOT_APPROVED with snapshot as related model
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_SNAPSHOT_APPROVED && $notification->relatedId === $snapshotV2->id
        );

        // 7. Owner Completes Pitch
        $completionFeedback = 'Great work!';
        $rating = 5;
        $this->pitchCompletionService->completePitch($this->pitch, $this->projectOwner, $completionFeedback, $rating);
        $this->pitch->refresh();
        $this->project->refresh();
        $snapshotV2->refresh();
        $this->assertEquals(Pitch::STATUS_COMPLETED, $this->pitch->status);
        $this->assertEquals(Project::STATUS_COMPLETED, $this->project->status);
        $this->assertEquals(PitchSnapshot::STATUS_COMPLETED, $snapshotV2->status);
        $this->assertEquals(Pitch::PAYMENT_STATUS_PENDING, $this->pitch->payment_status); // Check payment status
        $this->assertNotNull($this->pitch->completed_at);
        $this->assertDatabaseHas('pitch_events', [ // Check for completion event with rating
            'pitch_id' => $this->pitch->id,
            'event_type' => 'status_change',
            'status' => Pitch::STATUS_COMPLETED,
            'rating' => $rating,
        ]);
        NotificationFacade::assertSentTo(
            $this->producer,
            UserNotification::class,
            fn ($notification) => $notification->eventType === NotificationModel::TYPE_PITCH_COMPLETED && $notification->relatedId === $this->pitch->id
        );
    }

    /** @test */
    public function test_file_size_limits_are_enforced()
    {
        // Disable CSRF protection for this test
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);

        // Arrange: Get original size limit from config (what FileManagementService actually uses)
        $originalMaxSize = config('files.max_pitch_file_size', 100 * 1024 * 1024); // 100MB default

        // Create a pitch in progress
        $this->actingAs($this->projectOwner);
        $pitch = Pitch::factory()->for($this->project)->for($this->producer, 'user')->create([
            'status' => Pitch::STATUS_IN_PROGRESS,
            'total_storage_limit_bytes' => 200 * 1024 * 1024, // Set 200MB limit for testing
        ]);

        // Debug: Ensure pitch was created and check storage limits
        $this->assertDatabaseHas('pitches', ['id' => $pitch->id]);

        // Mock for testing
        Storage::fake('s3');

        // Test valid file size (just under the limit)
        $this->actingAs($this->producer);
        $validFile = UploadedFile::fake()->create('valid_file.mp3', ($originalMaxSize / 1024) - 1024); // -1MB from limit in KB

        // Debug: Check the route URL being generated
        $routeUrl = route('pitch.files.store', ['pitch' => $pitch->slug]);
        $this->assertStringContainsString("/pitches/{$pitch->slug}/files", $routeUrl);

        // Act: Upload valid file (expecting JSON response from AJAX endpoint)
        $validUploadResponse = $this->post(route('pitch.files.store', ['pitch' => $pitch->slug]), [
            'file' => $validFile,
        ]);

        // Assert: Valid upload succeeded (JSON response)
        $validUploadResponse->assertStatus(200); // JSON success
        $validUploadResponse->assertJson(['success' => true]);

        // Debug: Check what file path was actually stored
        $storedFile = \App\Models\PitchFile::where('pitch_id', $pitch->id)->latest()->first();
        $this->assertNotNull($storedFile, 'No pitch file was created in database');

        // Use the actual stored path instead of guessing
        Storage::disk('s3')->assertExists($storedFile->file_path);
        $this->assertDatabaseHas('pitch_files', [
            'pitch_id' => $pitch->id,
            'original_file_name' => 'valid_file.mp3',
        ]);

        // Reset for next test
        Pitch::where('id', $pitch->id)->update(['total_storage_used' => 0]);

        // Test oversized file (over the limit)
        $oversizedFile = UploadedFile::fake()->create('oversized_file.mp3', ($originalMaxSize / 1024) + 1024); // +1MB over limit in KB

        // Act: Try to upload oversized file
        $oversizedUploadResponse = $this->post(route('pitch.files.store', ['pitch' => $pitch->slug]), [
            'file' => $oversizedFile,
        ]);

        // Assert: Oversized upload was rejected (JSON error response)
        $oversizedUploadResponse->assertStatus(400); // JSON validation failed
        $oversizedUploadResponse->assertJson(['success' => false]);
        Storage::disk('s3')->assertMissing('pitches/'.$pitch->id.'/'.$oversizedFile->hashName());
        $this->assertDatabaseMissing('pitch_files', [
            'pitch_id' => $pitch->id,
            'original_file_name' => 'oversized_file.mp3',
        ]);

        // Test multiple files cumulative limit
        // Upload several smaller files that would exceed the total storage limit together
        $maxPitchStorage = $pitch->total_storage_limit_bytes; // Use the actual pitch limit (200MB)
        $individualSize = 60 * 1024; // 60MB in KB - small enough to upload 3 files but not 4

        // Reset storage used counter
        Pitch::where('id', $pitch->id)->update(['total_storage_used' => 0]);

        // Upload first file (should succeed)
        $file1 = UploadedFile::fake()->create('file1.mp3', $individualSize);
        $response1 = $this->post(route('pitch.files.store', ['pitch' => $pitch->slug]), ['file' => $file1]);
        $response1->assertStatus(200);

        // Upload second file (should succeed)
        $file2 = UploadedFile::fake()->create('file2.mp3', $individualSize);
        $response2 = $this->post(route('pitch.files.store', ['pitch' => $pitch->slug]), ['file' => $file2]);
        $response2->assertStatus(200);

        // Upload third file (should succeed)
        $file3 = UploadedFile::fake()->create('file3.mp3', $individualSize);
        $response3 = $this->post(route('pitch.files.store', ['pitch' => $pitch->slug]), ['file' => $file3]);
        $response3->assertStatus(200);

        // Update storage used to near max to test limit
        $pitchToUpdate = Pitch::find($pitch->id);
        $pitchToUpdate->update([
            'total_storage_used' => $maxPitchStorage - 100, // Just under the limit
        ]);

        // Upload fourth file (should fail - exceeds total storage)
        $file4 = UploadedFile::fake()->create('file4.mp3', $individualSize);
        $response4 = $this->post(route('pitch.files.store', ['pitch' => $pitch->slug]), ['file' => $file4]);

        // Assert final file was rejected due to cumulative limit (JSON error response)
        $response4->assertStatus(400);
        $response4->assertJson(['success' => false]);
        Storage::disk('s3')->assertMissing('pitches/'.$pitch->id.'/'.$file4->hashName());
    }
}
