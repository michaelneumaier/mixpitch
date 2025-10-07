<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientPortalFileAccessTest extends TestCase
{
    use RefreshDatabase;

    protected $producer;

    protected $project;

    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create producer user
        $this->producer = User::factory()->create();

        // Create client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_name' => 'Jane Client',
            'client_email' => 'jane@client.com',
            'title' => 'Test Project',
        ]);

        // Create pitch with IN_PROGRESS status
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
            'payment_amount' => 200.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);
    }

    /** @test */
    public function client_cannot_see_files_when_pitch_status_is_in_progress()
    {
        // Upload some files to the pitch (before V1 submission)
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-1.mp3',
        ]);

        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-2.mp3',
        ]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Client accesses the portal
        $response = $this->get($signedUrl);

        // Assert portal loads successfully
        $response->assertStatus(200);

        // Assert files are NOT shown
        $response->assertDontSee('test-audio-1.mp3');
        $response->assertDontSee('test-audio-2.mp3');

        // Assert the appropriate empty state is shown
        $response->assertSee('Producer is working on your project');
        $response->assertSee('Files will appear here when the producer submits them for your review');
    }

    /** @test */
    public function client_can_see_files_when_pitch_status_is_ready_for_review()
    {
        // Upload some files to the pitch
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-1.mp3',
        ]);

        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-2.mp3',
        ]);

        // Change pitch status to READY_FOR_REVIEW (simulating V1 submission)
        $this->pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Client accesses the portal
        $response = $this->get($signedUrl);

        // Assert portal loads successfully
        $response->assertStatus(200);

        // Assert files ARE shown
        $response->assertSee('test-audio-1.mp3');
        $response->assertSee('test-audio-2.mp3');

        // Assert the deliverables section is visible
        $response->assertSee('Producer Deliverables');
    }

    /** @test */
    public function client_can_see_files_when_pitch_status_is_client_revisions_requested()
    {
        // Upload some files to the pitch
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-1.mp3',
        ]);

        // Change pitch status to CLIENT_REVISIONS_REQUESTED
        $this->pitch->update(['status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Client accesses the portal
        $response = $this->get($signedUrl);

        // Assert portal loads successfully
        $response->assertStatus(200);

        // Assert files ARE shown
        $response->assertSee('test-audio-1.mp3');
    }

    /** @test */
    public function client_can_see_files_when_pitch_status_is_completed()
    {
        // Upload some files to the pitch
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-1.mp3',
        ]);

        // Change pitch status to COMPLETED
        $this->pitch->update(['status' => Pitch::STATUS_COMPLETED]);

        // Generate signed URL for client portal
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addHours(24),
            ['project' => $this->project->id]
        );

        // Client accesses the portal
        $response = $this->get($signedUrl);

        // Assert portal loads successfully
        $response->assertStatus(200);

        // Assert files ARE shown
        $response->assertSee('test-audio-1.mp3');
    }

    /** @test */
    public function authenticated_client_cannot_see_files_when_pitch_status_is_in_progress()
    {
        // Create authenticated client user
        $clientUser = User::factory()->create([
            'email' => 'jane@client.com',
            'role' => User::ROLE_CLIENT,
        ]);

        // Link project to client user
        $this->project->update(['client_user_id' => $clientUser->id]);

        // Upload some files to the pitch
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-1.mp3',
        ]);

        // Authenticate as client
        $this->actingAs($clientUser);

        // Client accesses the portal
        $response = $this->get(route('client.portal.view', $this->project));

        // Assert portal loads successfully
        $response->assertStatus(200);

        // Assert files are NOT shown
        $response->assertDontSee('test-audio-1.mp3');

        // Assert the appropriate empty state is shown
        $response->assertSee('Producer is working on your project');
    }

    /** @test */
    public function authenticated_client_can_see_files_when_pitch_status_is_ready_for_review()
    {
        // Create authenticated client user
        $clientUser = User::factory()->create([
            'email' => 'jane@client.com',
            'role' => User::ROLE_CLIENT,
        ]);

        // Link project to client user
        $this->project->update(['client_user_id' => $clientUser->id]);

        // Upload some files to the pitch
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-audio-1.mp3',
        ]);

        // Change pitch status to READY_FOR_REVIEW
        $this->pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        // Authenticate as client
        $this->actingAs($clientUser);

        // Client accesses the portal
        $response = $this->get(route('client.portal.view', $this->project));

        // Assert portal loads successfully
        $response->assertStatus(200);

        // Assert files ARE shown
        $response->assertSee('test-audio-1.mp3');

        // Assert the deliverables section is visible
        $response->assertSee('Producer Deliverables');
    }
}
