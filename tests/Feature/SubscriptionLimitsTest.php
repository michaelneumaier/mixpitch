<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Database\Seeders\CompleteSubscriptionLimitsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

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
            'subscription_tier' => 'basic',
        ]);

        // User should be able to create a project initially
        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(0, $user->getActiveProjectsCount());
        $this->assertEquals(0, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function free_user_can_create_project_when_below_active_limit()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Create two active projects — still one slot remaining under the limit of 3
        Project::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
        ]);

        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(2, $user->getActiveProjectsCount());
    }

    /** @test */
    public function free_user_cannot_create_project_when_at_active_limit()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Fill all three active project slots
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
        ]);

        $this->assertFalse($user->canCreateProject());
        $this->assertEquals(3, $user->getActiveProjectsCount());
        $this->assertEquals(0, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function free_user_at_project_limit_is_redirected_by_middleware()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
        ]);

        $response = $this->actingAs($user)->post(route('projects.store'), []);

        $response->assertRedirect(route('subscription.index'));
        $response->assertSessionHas('error');
    }

    /** @test */
    public function free_user_can_create_project_when_has_completed_projects()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Three completed projects should not count against the active limit
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED,
        ]);

        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(0, $user->getActiveProjectsCount());
        $this->assertEquals(3, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function free_user_at_limit_with_completed_projects_still_blocked()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Three active projects fills the limit; completed projects should
        // not free up slots — the check is on active count only.
        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED,
        ]);

        $this->assertFalse($user->canCreateProject());
        $this->assertEquals(3, $user->getActiveProjectsCount());
        $this->assertEquals(1, $user->getCompletedProjectsCount());
    }

    /** @test */
    public function client_management_projects_do_not_count_against_active_limit()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'free',
            'subscription_tier' => 'basic',
        ]);

        // Client management workflow projects have their own lifecycle
        // and should not consume free-plan project slots.
        Project::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        ]);

        $this->assertTrue($user->canCreateProject());
        $this->assertEquals(0, $user->getActiveProjectsCount());
    }

    /** @test */
    public function pro_user_can_create_unlimited_projects()
    {
        $user = User::factory()->create([
            'subscription_plan' => 'pro',
            'subscription_tier' => 'artist',
        ]);

        // Create multiple projects (both active and completed)
        Project::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
        ]);

        Project::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED,
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
            'subscription_tier' => 'basic',
        ]);

        // Create projects with different statuses
        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_UNPUBLISHED,
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_OPEN,
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_IN_PROGRESS,
        ]);

        Project::factory()->create([
            'user_id' => $user->id,
            'status' => Project::STATUS_COMPLETED,
        ]);

        // Should count 3 active projects (unpublished, open, in_progress)
        $this->assertEquals(3, $user->getActiveProjectsCount());

        // Should count 1 completed project
        $this->assertEquals(1, $user->getCompletedProjectsCount());

        // Total should be 4
        $this->assertEquals(4, $user->projects()->count());
    }
}
