<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Models\Pitch;
use App\Models\ProjectFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ClientCommentNotification;

class ClientPortalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $project;
    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_name' => 'Jane Smith',
            'client_email' => 'jane@example.com',
            'title' => 'Music Production Project'
        ]);
        
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => Pitch::STATUS_READY_FOR_REVIEW,
            'payment_amount' => 200.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);
    }

    /** @test */
    public function client_can_view_enhanced_portal_with_progress_dashboard()
    {
        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Music Production Project');
        $response->assertSee('Jane Smith');
        $response->assertSee('jane@example.com');
        $response->assertSee('Project Progress');
        
        // Check for progress indicators (the test shows "In Progress" status)
        $response->assertSee('animate-pulse'); // Animated elements
        $response->assertSee('Project Files'); // File management section
        $response->assertSee('Project Communication'); // Communication section
        
        // Check for modern styling elements
        $response->assertSee('backdrop-blur'); // Glass morphism
        $response->assertSee('gradient'); // Gradient styling
    }

    /** @test */
    public function client_sees_different_progress_states()
    {
        $testCases = [
            [
                'status' => Pitch::STATUS_PENDING,
                'expected_progress' => '0%',
                'expected_message' => 'preparing your deliverables'
            ],
            [
                'status' => Pitch::STATUS_IN_PROGRESS,
                'expected_progress' => '25%',
                'expected_message' => 'actively working'
            ],
            [
                'status' => Pitch::STATUS_COMPLETED,
                'expected_progress' => '100%',
                'expected_message' => 'completed successfully'
            ]
        ];

        foreach ($testCases as $testCase) {
            $this->pitch->update(['status' => $testCase['status']]);
            
            $signedUrl = URL::temporarySignedRoute(
                'client.portal.view',
                now()->addHours(24),
                ['project' => $this->project->id]
            );

            $response = $this->get($signedUrl);
            
            $response->assertStatus(200);
            $response->assertSee($testCase['expected_progress']);
            $response->assertSee($testCase['expected_message']);
        }
    }

    /** @test */
    public function client_can_upload_reference_files()
    {
        Storage::fake('local');
        
        $file = UploadedFile::fake()->create('reference-track.mp3', 2048);
        
        $uploadUrl = URL::temporarySignedRoute(
            'client.portal.upload',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->post($uploadUrl, [
            'file' => $file
        ]);

        // Assert
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'filename' => 'reference-track.mp3'
        ]);
        
        // Verify file was stored in database
        $this->assertDatabaseHas('project_files', [
            'project_id' => $this->project->id,
            'filename' => 'reference-track.mp3',
            'file_type' => 'client_reference'
        ]);
    }

    /** @test */
    public function client_can_delete_uploaded_files()
    {
        Storage::fake('local');
        
        $projectFile = ProjectFile::factory()->create([
            'project_id' => $this->project->id,
            'file_type' => 'client_reference',
            'filename' => 'old-reference.mp3'
        ]);

        $deleteUrl = URL::temporarySignedRoute(
            'client.portal.delete-file',
            now()->addHours(24),
            ['project' => $this->project->id, 'projectFile' => $projectFile->id]
        );

        // Act
        $response = $this->delete($deleteUrl);

        // Assert
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Verify file was removed from database
        $this->assertDatabaseMissing('project_files', [
            'id' => $projectFile->id
        ]);
    }

    /** @test */
    public function client_can_add_comments()
    {
        Notification::fake();
        
        $commentUrl = URL::temporarySignedRoute(
            'client.portal.comments.store',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        $comment = 'This is looking great! Can we adjust the bass levels?';

        // Act
        $response = $this->post($commentUrl, [
            'comment' => $comment
        ]);

        // Assert
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('success');
        
        // Verify comment was stored
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => 'client_comment',
            'comment' => $comment
        ]);
        
        // Verify producer was notified
        Notification::assertSentTo(
            $this->user,
            ClientCommentNotification::class
        );
    }

    /** @test */
    public function client_can_request_revisions()
    {
        $revisionsUrl = URL::temporarySignedRoute(
            'client.portal.revisions',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        $feedback = 'Please increase the tempo and add more reverb to the vocals';

        // Act
        $response = $this->post($revisionsUrl, [
            'feedback' => $feedback
        ]);

        // Assert
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('success');
        
        // Verify pitch status changed to revisions requested
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_REVISIONS_REQUESTED, $this->pitch->status);
        
        // Verify revision event was created
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => 'client_revisions_requested',
            'comment' => $feedback
        ]);
    }

    /** @test */
    public function client_approval_redirects_to_stripe_for_paid_projects()
    {
        // Ensure producer has Stripe setup
        $this->user->createOrGetStripeCustomer();
        
        $approveUrl = URL::temporarySignedRoute(
            'client.portal.approve',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->post($approveUrl);

        // Assert
        $response->assertStatus(302); // Redirect to Stripe
        $this->assertStringContainsString('checkout.stripe.com', $response->headers->get('Location'));
        
        // Verify pitch status hasn't changed yet (payment pending)
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_READY_FOR_REVIEW, $this->pitch->status);
        $this->assertEquals(Pitch::PAYMENT_STATUS_PENDING, $this->pitch->payment_status);
    }

    /** @test */
    public function client_approval_works_immediately_for_free_projects()
    {
        // Update pitch to be free
        $this->pitch->update(['payment_amount' => 0.00]);
        
        $approveUrl = URL::temporarySignedRoute(
            'client.portal.approve',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->post($approveUrl);

        // Assert
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHas('success');
        
        // Verify pitch was approved
        $this->pitch->refresh();
        $this->assertEquals(Pitch::STATUS_APPROVED, $this->pitch->status);
    }

    /** @test */
    public function client_sees_post_approval_success_section()
    {
        // Set pitch to approved status
        $this->pitch->update([
            'status' => Pitch::STATUS_APPROVED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);
        
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Project Approved!');
        $response->assertSee('Payment Confirmed');
        $response->assertSee('$200.00');
        $response->assertSee('Download Invoice');
    }

    /** @test */
    public function client_sees_completion_celebration_for_completed_projects()
    {
        // Set pitch to completed status
        $this->pitch->update([
            'status' => Pitch::STATUS_COMPLETED,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);
        
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('🎉 Project Completed!');
        $response->assertSee('successfully completed');
        $response->assertSee('All deliverables are ready');
        $response->assertSee('We\'d love your feedback');
    }

    /** @test */
    public function client_portal_rejects_invalid_signatures()
    {
        // Create URL without signature
        $invalidUrl = route('client.portal.view', ['project' => $this->project->id]);

        // Act
        $response = $this->get($invalidUrl);

        // Assert
        $response->assertStatus(403);
    }

    /** @test */
    public function client_portal_rejects_non_client_management_projects()
    {
        // Create a standard project
        $standardProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD
        ]);
        
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $standardProject->id]
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert
        $response->assertStatus(404);
    }

    /** @test */
    public function client_portal_shows_mobile_responsive_design()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert mobile-friendly elements
        $response->assertStatus(200);
        $response->assertSee('responsive');
        $response->assertSee('mobile-first');
        $response->assertSee('touch-friendly');
        $response->assertSee('grid-cols-1 lg:grid-cols-2'); // Responsive grid
    }

    /** @test */
    public function client_portal_handles_checkout_success_callback()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id, 'checkout_status' => 'success']
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Payment successful!');
        $response->assertSee('project has been approved');
        $response->assertSee('producer has been notified');
    }

    /** @test */
    public function client_portal_handles_checkout_cancel_callback()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id, 'checkout_status' => 'cancel']
        );

        // Act
        $response = $this->get($signedUrl);

        // Assert
        $response->assertStatus(200);
        $response->assertSee('Payment was cancelled');
        $response->assertSee('try approving again');
    }

    /** @test */
    public function client_portal_validates_file_uploads()
    {
        Storage::fake('local');
        
        $uploadUrl = URL::temporarySignedRoute(
            'client.portal.upload',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Test with invalid file type
        $invalidFile = UploadedFile::fake()->create('virus.exe', 1024);

        $response = $this->post($uploadUrl, [
            'file' => $invalidFile
        ]);

        // Should reject invalid file types
        $response->assertStatus(422);
        
        // Test with oversized file
        $oversizedFile = UploadedFile::fake()->create('huge.mp3', 50000); // 50MB

        $response = $this->post($uploadUrl, [
            'file' => $oversizedFile
        ]);

        // Should reject oversized files
        $response->assertStatus(422);
    }
} 