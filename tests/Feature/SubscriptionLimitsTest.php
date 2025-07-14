<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\CompleteSubscriptionLimitsSeeder;

class SubscriptionLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed subscription limits
        $this->seed(CompleteSubscriptionLimitsSeeder::class);
    }

    /** @test */
    public function free_user_can_create_project_when_no_active_projects()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic'
        ]);

        // User should be able to create a project initially
        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(0, $user->getActiveProjectsCount());
        $this->assertEquals(0, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function free_user_cannot_create_project_when_has_one_active_project()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic'
        ]);

        // Create one active project
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN
        ]);

        // User should not be able to create another project
        $this->assertFalse($user->canCreateProject());
        $this->assertEquals(1, $user->getActiveProjectsCount());
        $this->assertEquals(0, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function free_user_can_create_project_when_has_one_completed_project()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic'
        ]);

        // Create one completed project
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED
        ]);

        // User should be able to create another project
        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(0, $user->getActiveProjectsCount());
        $this->assertEquals(1, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function free_user_cannot_create_project_when_has_one_active_and_one_completed()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic'
        ]);

        // Create one active project
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN
        ]);

        // Create one completed project
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED
        ]);

        // User should not be able to create another project (active limit reached)
        $this->assertFalse($user->canCreateProject());
        $this->assertEquals(1, $user->getActiveProjectsCount());
        $this->assertEquals(1, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function pro_user_can_create_unlimited_projects()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'artist'
        ]);

        // Create multiple projects (both active and completed)
        Project::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN
        ]);

        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED
        ]);

        // Pro user should always be able to create projects
        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(5, $user->getActiveProjectsCount());
        $this->assertEquals(3, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function project_counts_are_correct_for_different_statuses()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic'
        ]);

        // Create projects with different statuses
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_UNPUBLISHED
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_IN_PROGRESS
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED
        ]);

        // Should count 3 active projects (unpublished, open, in_progress)
        $this->assertEquals(3, $user->getActiveProjectsCount());
        
        // Should count 1 completed project
        $this->assertEquals(1, $user->getCompletedProjectsCount());
        
        // Total should be 4
        $this->assertEquals(4, $user->projects()->count());
    }
} 