<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StandardProjectManagementTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;

    protected $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);
    }

    /** @test */
    public function standard_project_shows_workflow_status_component()
    {
        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // The StandardOverviewCard shows "Waiting for Pitches" for published projects with no pitches
        $response->assertSee('Waiting for Pitches');
        $response->assertSee('Your project is live');
    }

    /** @test */
    public function workflow_status_shows_correct_stage_for_unpublished_project()
    {
        $this->project->update(['is_published' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Project Not Published');
        $response->assertSee('Your project is currently in draft mode');
    }

    /** @test */
    public function workflow_status_shows_reviewing_stage_when_pitches_exist()
    {
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'ready_for_review',
        ]);

        // Create a snapshot so the view can generate the snapshot review link
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => PitchSnapshot::STATUS_PENDING,
        ]);
        $pitch->update(['current_snapshot_id' => $snapshot->id]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // StandardOverviewCard shows "Pitches Ready for Review" when pitches are in ready_for_review status
        $response->assertSee('Pitches Ready for Review');
        $response->assertSee('waiting for your review');
    }

    /** @test */
    public function workflow_status_shows_approved_stage_when_pitch_approved()
    {
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // StandardOverviewCard shows "Pitch Approved" when a pitch is approved
        $response->assertSee('Pitch Approved');
        $response->assertSee('producer is now working on the final deliverables');
    }

    /** @test */
    public function workflow_status_shows_in_progress_when_pitch_in_progress()
    {
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // StandardOverviewCard shows "Producers Working" when pitches are in_progress
        $response->assertSee('Producers Working');
        $response->assertSee('currently working on pitches');
    }

    /** @test */
    public function workflow_status_shows_project_metrics()
    {
        $producer = User::factory()->create();

        // Create multiple pitches
        Pitch::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
        ]);

        // Create project files
        ProjectFile::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // StandardOverviewCard shows metrics grid with "Total Pitches" and "Project Files"
        $response->assertSee('Total Pitches');
        $response->assertSee('Project File');
    }

    /** @test */
    public function workflow_status_shows_pending_requests_for_pending_pitches()
    {
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // StandardOverviewCard shows "Pending Requests" when pitches have pending status
        $response->assertSee('Pending Requests');
        $response->assertSee('requested to work on your project');
    }

    /** @test */
    public function manage_project_page_has_tabbed_layout()
    {
        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // The new layout uses tabs: Overview, Pitches, Files, Project
        $response->assertSee('Overview');
        $response->assertSee('Pitches');
        $response->assertSee('Files');
        $response->assertSee('Project');
    }

    /** @test */
    public function standard_project_shows_overview_tab_content()
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $project));

        $response->assertStatus(200);

        // The overview tab shows Quick Actions section from StandardOverviewCard
        $response->assertSee('Quick Actions');
        $response->assertSee('View Pitches');
        $response->assertSee('Manage Files');
        $response->assertSee('Project Settings');
    }

    /** @test */
    public function project_status_shows_publish_action_for_unpublished_project()
    {
        // Test with unpublished project
        $unpublishedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $unpublishedProject));

        $response->assertStatus(200);

        // StandardOverviewCard shows "Publish Project" action for unpublished projects
        $response->assertSee('Publish Project');
        $response->assertSee('Project Not Published');

        // Test with published project - header shows unpublish option in manage dropdown
        $publishedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $publishedProject));

        $response->assertStatus(200);

        // Published project shows "Unpublish Project" in the header dropdown
        $response->assertSee('Unpublish Project');
    }

    /** @test */
    public function manage_project_page_shows_pitch_status_breakdown()
    {
        $producer = User::factory()->create();
        Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);

        // StandardOverviewCard shows "Pitch Status Breakdown" when pitches exist
        $content = $response->getContent();
        $breakdownCount = substr_count($content, 'Pitch Status Breakdown');

        // Should see the Pitch Status Breakdown heading in the overview card
        $this->assertGreaterThanOrEqual(1, $breakdownCount);
    }

    /** @test */
    public function workflow_status_provides_contextual_actions()
    {
        // Test unpublished project actions
        $this->project->update(['is_published' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Publish Project');

        // Test with pitches ready for review
        $this->project->update(['is_published' => true]);
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'ready_for_review',
        ]);

        // Create a snapshot so the view can generate the snapshot review link
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => PitchSnapshot::STATUS_PENDING,
        ]);
        $pitch->update(['current_snapshot_id' => $snapshot->id]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $this->project));

        $response->assertStatus(200);
        // StandardOverviewCard shows "Review Pitches" action button when pitches need review
        $response->assertSee('Review Pitches');
        // Header dropdown shows "View Public" option
        $response->assertSee('View Public');
    }

    /** @test */
    public function contest_project_shows_appropriate_workflow_status()
    {
        $contestProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'contest',
            'is_published' => true,
            'prize_amount' => 500.00,
            'submission_deadline' => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-contest', $contestProject));

        $response->assertStatus(200);
        // ContestOverviewCard shows "Accepting Entries" for published contests with open submissions
        $response->assertSee('Accepting Entries');
        // Contest metrics grid shows entry count
        $response->assertSee('Entries');
    }

    /** @test */
    public function contest_project_shows_quick_actions_and_contest_elements()
    {
        $contestProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'contest',
            'is_published' => true,
            'prize_amount' => 500.00,
            'submission_deadline' => now()->addDays(7),
            'judging_deadline' => now()->addDays(14),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-contest', $contestProject));

        $response->assertStatus(200);

        // ContestOverviewCard has Quick Actions section
        $response->assertSee('Quick Actions');
        $response->assertSee('View Entries');
        $response->assertSee('Manage Prizes');
        $response->assertSee('Contest Files');
        $response->assertSee('Settings');

        // Contest page has tabs for Entries, Judging, Prizes, Files, Settings
        $response->assertSee('Entries');
        $response->assertSee('Judging');
        $response->assertSee('Prizes');
        $response->assertSee('Files');

        // Contest Files section exists in the Files tab
        $response->assertSee('Contest Files');
        $response->assertSee('contest resources for participants');

        // Contest Delete modal content exists
        $response->assertSee('Delete Contest');
    }

    /** @test */
    public function direct_hire_project_redirects_appropriately()
    {
        $this->markTestSkipped('Direct Hire workflow is disabled');
    }

    /** @test */
    public function workflow_status_tracks_pending_pitches()
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        // Create a pending pitch
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $project));

        $response->assertStatus(200);
        // StandardOverviewCard shows "Pending Requests" when there are pending pitches
        $response->assertSee('Pending Requests');
        $response->assertSee('Review Requests');
    }

    /** @test */
    public function standard_project_shows_overview_card_and_actions()
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage-standard', $project));

        $response->assertStatus(200);

        // StandardOverviewCard shows metrics: Total Pitches, Project Files, Days Active, Actions Needed
        $response->assertSee('Total Pitches');
        $response->assertSee('Days Active');
        $response->assertSee('Actions Needed');

        // Quick Actions section
        $response->assertSee('Quick Actions');
        $response->assertSee('View Pitches');
        $response->assertSee('Manage Files');
        $response->assertSee('Project Settings');

        // Project header has manage dropdown with View Public
        $response->assertSee('View Public');
        // Header shows project settings link
        $response->assertSee('Project Settings');
    }
}
