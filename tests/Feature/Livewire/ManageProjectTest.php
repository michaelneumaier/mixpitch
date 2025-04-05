<?php

namespace Tests\Feature\Livewire;

use App\Livewire\ManageProject;
use App\Livewire\Forms\ProjectForm;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Carbon\Carbon;
use Mockery;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Gate;

class ManageProjectTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_successfully_for_project_owner()
    {
        // Create a user
        $user = User::factory()->create();
        
        // Create a project with all needed fields
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'description' => 'Test Project Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'collaboration_type' => ['Mixing'],
            'budget' => 0,
            'deadline' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'status' => 'draft',
            'is_published' => false,
            'preview_track' => null,
            'total_storage_used' => 0,
            'total_storage_limit_bytes' => 104857600, // 100MB
        ]);
        
        // Define authorization explicitly to avoid hanging in policy lookups
        Gate::define('update', function (User $gateUser, Project $gateProject) use ($user) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        // Define a simple publish/unpublish permission
        Gate::define('publish', function (User $gateUser, Project $gateProject) use ($user) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        Gate::define('unpublish', function (User $gateUser, Project $gateProject) use ($user) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        // Test with mocks for problematic methods
        $component = Livewire::actingAs($user)
            ->test(ManageProject::class, ['project' => $project])
            ->assertOk();
    }

    /** @test */
    public function fails_to_render_for_unauthorized_user()
    {
        // Create owner and project
        $projectOwner = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $projectOwner->id,
            'name' => 'Test Project',
            'description' => 'Test Project Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'collaboration_type' => ['Mixing'],
            'budget' => 0,
            'deadline' => Carbon::now()->addDays(10)->format('Y-m-d'),
            'status' => 'draft',
            'is_published' => false,
            'preview_track' => null,
        ]);
        
        // Create unauthorized user
        $unauthorizedUser = User::factory()->create();
        
        // Define authorization explicitly to avoid hanging in policy lookups
        Gate::define('update', function (User $gateUser, Project $gateProject) use ($projectOwner) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        // Test with unauthorized user
        Livewire::actingAs($unauthorizedUser)
            ->test(ManageProject::class, ['project' => $project])
            ->assertForbidden();
    }

    /** @test */
    public function can_update_project_details()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Name',
            'description' => 'Original Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'collaboration_type' => ['Mixing'],
            'budget' => 0,
            'deadline' => Carbon::now()->addDays(10)->format('Y-m-d'),
        ]);
        
        // Define gate permissions
        Gate::define('update', function (User $gateUser, Project $gateProject) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        // Test updating project details
        $component = Livewire::actingAs($user)
            ->test(ManageProject::class, ['project' => $project]);
        
        // Set form data
        $component->set('form.name', 'Updated Name')
            ->set('form.description', 'Updated Description')
            ->set('form.projectType', 'album')
            ->set('form.genre', 'Pop')
            ->set('form.collaborationTypeMixing', true)
            ->set('form.collaborationTypeMastering', true)
            ->set('form.budgetType', 'paid')
            ->set('form.budget', 500)
            ->call('updateProjectDetails')
            ->assertDispatched('project-details-updated');
        
        // Verify project was updated in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Name', 
            'description' => 'Updated Description',
            'project_type' => 'album',
            'genre' => 'Pop',
            'budget' => 500,
        ]);
    }

    /** @test */
    public function can_publish_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'description' => 'Test Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'status' => 'draft',
            'is_published' => false
        ]);
        
        // Set up gate permissions
        Gate::define('update', function (User $gateUser, Project $gateProject) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        Gate::define('publish', function (User $gateUser, Project $gateProject) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        // Test publish functionality
        Livewire::actingAs($user)
            ->test(ManageProject::class, ['project' => $project])
            ->call('publish')
            ->assertDispatched('project-updated');
        
        // Verify status updated in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_published' => true
        ]);
    }

    /** @test */
    public function can_unpublish_project()
    {
        $user = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Project',
            'description' => 'Test Description',
            'project_type' => 'single',
            'genre' => 'Rock',
            'status' => 'published',
            'is_published' => true
        ]);
        
        // Set up gate permissions
        Gate::define('update', function (User $gateUser, Project $gateProject) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        Gate::define('unpublish', function (User $gateUser, Project $gateProject) {
            return $gateUser->id === $gateProject->user_id;
        });
        
        // Test unpublish functionality
        Livewire::actingAs($user)
            ->test(ManageProject::class, ['project' => $project])
            ->call('unpublish')
            ->assertDispatched('project-updated');
        
        // Verify status updated in database
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'is_published' => false
        ]);
    }
} 