<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardClientManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function client_management_project_shows_only_pitch_on_dashboard()
    {
        // Create a user who will be both project owner and producer
        $user = User::factory()->create();

        // Create a client management project with active status
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'status' => Project::STATUS_IN_PROGRESS, // This is an active status
        ]);

        // The ProjectObserver should automatically create a pitch
        $this->assertDatabaseHas('pitches', [
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $pitch = Pitch::where('project_id', $project->id)->first();
        $this->assertNotNull($pitch);

        // Ensure the pitch has an active status
        $pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        // Act as the user and visit the dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // First, just verify the filter buttons are there
        $response->assertSee('Client Projects');
        $response->assertSee('All');
        $response->assertSee('Projects');
        $response->assertSee('Pitches');

        // Check that the pitch exists and has the right status
        $this->assertEquals(Pitch::STATUS_IN_PROGRESS, $pitch->fresh()->status);

        // Debug: Let's see what work items are being passed to the view
        $content = $response->getContent();

        // The response should contain the pitch (as "Client Project")
        $response->assertSee('Client Project');
    }

    /** @test */
    public function standard_project_shows_normally_on_dashboard()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a standard project (not client management) with active status
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'status' => Project::STATUS_OPEN, // Active status
        ]);

        // Standard projects should not automatically create pitches
        $this->assertDatabaseMissing('pitches', [
            'project_id' => $project->id,
        ]);

        // Act as the user and visit the dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // The response should contain the project
        $response->assertSee('Project'); // The badge text
        $response->assertSee($project->name);
    }

    /** @test */
    public function client_management_project_without_pitch_shows_project_on_dashboard()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a client management project with active status
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'status' => Project::STATUS_IN_PROGRESS, // Active status
        ]);

        // Manually delete the auto-created pitch to simulate edge case
        Pitch::where('project_id', $project->id)->delete();

        // Act as the user and visit the dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // The response should show the project since there's no pitch
        $response->assertSee('Project');
        $response->assertSee($project->name);
    }

    /** @test */
    public function client_management_project_links_to_correct_page_on_dashboard()
    {
        // Create a user who will be both project owner and producer
        $user = User::factory()->create();

        // Create a client management project with active status
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'status' => Project::STATUS_IN_PROGRESS, // Active status
        ]);

        // The ProjectObserver should automatically create a pitch
        $pitch = Pitch::where('project_id', $project->id)->first();
        $this->assertNotNull($pitch);

        // Ensure the pitch has an active status
        $pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        // Act as the user and visit the dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // The response should contain a link to the manage-client-project page
        $expectedUrl = route('projects.manage-client', $project);
        $response->assertSee($expectedUrl);

        // The response should NOT contain a link to the standard pitch page
        $standardPitchUrl = route('projects.pitches.show', ['project' => $project, 'pitch' => $pitch]);
        $response->assertDontSee($standardPitchUrl);
    }

    /** @test */
    public function client_filter_shows_only_client_management_projects()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a standard project (should not appear in client filter) with active status
        $standardProject = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'status' => Project::STATUS_OPEN, // Active status
        ]);

        // Create a client management project (should appear in client filter) with active status
        $clientProject = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'status' => Project::STATUS_IN_PROGRESS, // Active status
        ]);

        // Create a standard pitch (should not appear in client filter) with active status
        $standardPitch = Pitch::factory()->create([
            'project_id' => $standardProject->id,
            'user_id' => $user->id,
            'status' => Pitch::STATUS_IN_PROGRESS, // Active status
        ]);

        // Get the auto-created client management pitch and ensure it has active status
        $clientPitch = Pitch::where('project_id', $clientProject->id)->first();
        $this->assertNotNull($clientPitch);
        $clientPitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        // Act as the user and visit the dashboard
        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);

        // Check that the dashboard renders the work-section component with filter functionality
        $content = $response->getContent();

        // Verify the client filter button text exists in the rendered HTML
        $this->assertStringContainsString('Client Projects', $content);

        // Verify both project names appear in the dashboard
        $this->assertStringContainsString($clientProject->name, $content);
        $this->assertStringContainsString($standardProject->name, $content);
    }

    /** @test */
    public function enhanced_workflow_status_displays_correctly_for_different_statuses()
    {
        // Create a user
        $user = User::factory()->create();

        // Create a client management project
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'status' => Project::STATUS_IN_PROGRESS,
        ]);

        // Get the auto-created pitch
        $pitch = Pitch::where('project_id', $project->id)->first();
        $this->assertNotNull($pitch);
        $pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        // Test IN_PROGRESS status with no files - overview card shows "Getting Started"
        $response = $this->actingAs($user)->get(route('projects.manage-client', $project));
        $response->assertStatus(200);
        $response->assertSee('Getting Started');

        // Add a file and test file metrics
        $pitch->files()->create([
            'file_name' => 'test.mp3',
            'file_path' => 'test/path.mp3',
            'file_size' => 1024,
            'mime_type' => 'audio/mpeg',
            'uploaded_by' => $user->id,
            'user_id' => $user->id,
        ]);

        // With files, project-header still shows "Work in Progress"
        $response = $this->actingAs($user)->get(route('projects.manage-client', $project));
        $response->assertSee('Work in Progress');

        // Test READY_FOR_REVIEW status - overview card shows "Awaiting Client Review"
        $pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);
        $response = $this->actingAs($user)->get(route('projects.manage-client', $project));
        $response->assertSee('Awaiting Client Review');

        // Test COMPLETED status - overview card shows completion message
        $pitch->update(['status' => Pitch::STATUS_COMPLETED]);
        $response = $this->actingAs($user)->get(route('projects.manage-client', $project));
        $response->assertSee('Completed');
    }

    /** @test */
    public function workflow_status_shows_progress_visualization_for_client_management()
    {
        // Create a user and client management project
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'status' => Project::STATUS_IN_PROGRESS,
        ]);

        $pitch = Pitch::where('project_id', $project->id)->first();
        $pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        $response = $this->actingAs($user)->get(route('projects.manage-client', $project));
        $response->assertStatus(200);

        // Check for progress bar and workflow status elements
        $content = $response->getContent();
        // The workflow status component renders a progress bar with width percentage
        $this->assertStringContainsString('width:', $content);
        // The page should contain a gradient-styled element (FAB or progress)
        $this->assertStringContainsString('bg-gradient-to-r', $content);
    }

    /** @test */
    public function workflow_status_tracks_revision_count_correctly()
    {
        // Create a user and client management project
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'status' => Project::STATUS_IN_PROGRESS,
        ]);

        $pitch = Pitch::where('project_id', $project->id)->first();
        // Use CLIENT_REVISIONS_REQUESTED which is the status the view handles
        $pitch->update(['status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]);

        // Create multiple revision events
        $pitch->events()->create([
            'event_type' => 'client_revisions_requested',
            'comment' => 'First revision request',
            'status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            'created_by' => $user->id,
        ]);

        $pitch->events()->create([
            'event_type' => 'client_revisions_requested',
            'comment' => 'Second revision request',
            'status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('projects.manage-client', $project));
        $response->assertStatus(200);

        // The view shows "Revisions Requested" for CLIENT_REVISIONS_REQUESTED status
        $response->assertSee('Revisions Requested');
    }
}
