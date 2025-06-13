<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use App\Models\ProjectFile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Livewire\Livewire;

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
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Project Workflow Status');
        $response->assertSee('Open for Pitches');
        $response->assertSee('10%'); // Initial progress
    }

    /** @test */
    public function workflow_status_shows_correct_stage_for_unpublished_project()
    {
        $this->project->update(['is_published' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Project is not yet published');
        $response->assertSee('Publish your project to start receiving pitches');
    }

    /** @test */
    public function workflow_status_shows_reviewing_stage_when_pitches_exist()
    {
        $producer = User::factory()->create();
        Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Reviewing 1 pitch');
        $response->assertSee('Review submitted pitches and approve one');
        $response->assertSee('30%'); // Reviewing progress
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
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Pitch approved - waiting for work to begin');
        $response->assertSee('50%'); // Approved progress
    }

    /** @test */
    public function workflow_status_shows_in_progress_when_files_uploaded()
    {
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'approved',
        ]);

        // Add a file to the pitch to simulate work in progress
        ProjectFile::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Work in Progress');
        $response->assertSee('50%'); // Pitch approved stage
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
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('3'); // Pitch count
        $response->assertSee('Pitches');
        $response->assertSee('2'); // File count
        $response->assertSee('Files');
    }

    /** @test */
    public function workflow_status_shows_warning_for_long_review_time()
    {
        $producer = User::factory()->create();
        $pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'submitted',
            'created_at' => now()->subDays(8), // 8 days ago
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Attention Needed');
        $response->assertSee('8 days');
    }

    /** @test */
    public function manage_project_page_has_two_column_layout()
    {
        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        // Check for the grid layout classes
        $response->assertSee('lg:grid-cols-3');
        $response->assertSee('lg:col-span-2');
        // Check for actual content that exists in the layout
        $response->assertSee('Project Workflow Status');
        $response->assertSee('Pitches');
    }

    /** @test */
    public function standard_project_shows_mobile_optimized_layout()
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $project));

        $response->assertStatus(200);
        
        // Check for mobile-specific Quick Actions (shown first on mobile)
        $response->assertSee('lg:hidden'); // Mobile-only elements
        $response->assertSee('View Public');
        $response->assertSee('Edit Project');
        
        // Check for mobile Quick Stats (new component)
        $response->assertSee('Project Overview');
        $response->assertSee('grid grid-cols-2 sm:grid-cols-4'); // Mobile stats grid
        
        // Check for mobile Tips section
        $response->assertSee('Tips for Success');
        
        // Check for desktop-only sidebar elements
        $response->assertSee('hidden lg:block'); // Desktop-only elements
    }

    /** @test */
    public function project_status_component_removed_and_publish_actions_in_quick_actions()
    {
        // Test with unpublished project
        $unpublishedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $unpublishedProject));

        $response->assertStatus(200);
        
        // Should NOT see the old Project Status section header
        $response->assertDontSee('<i class="fas fa-toggle-off text-gray-500"></i>Project Status');
        
        // Should see publish action in Quick Actions instead
        $response->assertSee('Publish Project');
        $response->assertSee('Quick Actions');
        
        // Test with published project
        $publishedProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $publishedProject));

        $response->assertStatus(200);
        
        // Should see unpublish action in Quick Actions
        $response->assertSee('Unpublish Project');
        $response->assertSee('Quick Actions');
    }

    /** @test */
    public function manage_project_page_shows_no_duplicate_pitch_sections()
    {
        $producer = User::factory()->create();
        Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        
        // Count occurrences of the specific Pitches section header
        $content = $response->getContent();
        $pitchHeaderCount = substr_count($content, 'Submitted Pitches');
        
        // Should only see the Pitches header once in the unified section
        $this->assertEquals(1, $pitchHeaderCount);
    }

    /** @test */
    public function workflow_status_provides_contextual_actions()
    {
        // Test unpublished project actions
        $this->project->update(['is_published' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Publish Project');
        $response->assertSee('Edit Details');

        // Test reviewing stage actions
        $this->project->update(['is_published' => true]);
        $producer = User::factory()->create();
        Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $producer->id,
            'status' => 'submitted',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $this->project));

        $response->assertStatus(200);
        $response->assertSee('Review Pitches');
        $response->assertSee('View Public Page');
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
            ->get(route('projects.manage', $contestProject));

        $response->assertStatus(200);
        $response->assertSee('Contest Workflow Status');
        $response->assertSee('Total Entries');
    }

    /** @test */
    public function contest_project_shows_quick_actions_and_danger_zone()
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
            ->get(route('projects.manage', $contestProject));

        $response->assertStatus(200);
        
        // Check for Quick Actions component (both mobile and desktop)
        $response->assertSee('Quick Actions');
        $response->assertSee('Manage your contest efficiently');
        $response->assertSee('View Public Page');
        $response->assertSee('Edit Contest');
        $response->assertSee('Unpublish Contest');
        $response->assertSee('Post to r/MixPitch');
        
        // Check for Danger Zone component (both mobile and desktop)
        $response->assertSee('Danger Zone');
        $response->assertSee('Irreversible actions');
        $response->assertSee('Delete Contest');
        $response->assertSee('Permanently delete this contest and all associated files, entries, and judging data');
        
        // Check for Contest Prizes component (replaced Contest Details)
        $response->assertSee('Contest Prizes');
        $response->assertSee('Rewards and incentives for winners');
        
        // Check for Project Files section (should be available for contests now)
        $response->assertSee('Contest Files');
        $response->assertSee('Upload and manage contest');
        $response->assertSee('contest participants');
    }

    /** @test */
    public function direct_hire_project_shows_appropriate_workflow_status()
    {
        $producer = User::factory()->create();
        $directHireProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'direct_hire',
            'target_producer_id' => $producer->id,
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $directHireProject));

        $response->assertStatus(200);
        $response->assertSee('Project Workflow Status');
        $response->assertSee('Direct Hire Details');
    }

    /** @test */
    public function workflow_status_tracks_time_in_status()
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        // Create a pitch that was created 8 days ago (should trigger warning)
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'created_at' => now()->subDays(8),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $project));

        $response->assertStatus(200);
        $response->assertSee('Attention Needed');
        $response->assertSee('8 days');
    }

    /** @test */
    public function standard_project_shows_sidebar_content()
    {
        $project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
            'is_published' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('projects.manage', $project));

        $response->assertStatus(200);
        
        // Check for Standard Project sidebar content (desktop only)
        // Note: These elements are hidden on mobile with 'hidden lg:block' classes
        $response->assertSee('Standard Project');
        $response->assertSee('Open Collaboration');
        
        // Check for Quick Stats section (new component)
        $response->assertSee('Quick Stats');
        $response->assertSee('Pitches');
        $response->assertSee('Files');
        $response->assertSee('Days Active');
        
        // Check for Quick Actions section
        $response->assertSee('Quick Actions');
        $response->assertSee('View Public Page');
        $response->assertSee('Edit Project');
        
        // Check for Tips section
        $response->assertSee('Tips for Success');
        $response->assertSee('Share your project');
    }
} 