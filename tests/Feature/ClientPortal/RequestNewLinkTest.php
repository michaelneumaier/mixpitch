<?php

namespace Tests\Feature\ClientPortal;

use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RequestNewLinkTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset throttle counters between tests (throttle key is IP based here)
        Cache::flush();

        // The ProjectObserver auto-creates a pitch for CLIENT_MANAGEMENT projects
        // and calls notifyClientProjectInvite once during that flow. Allow it.
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->andReturnNull()->byDefault();
        });

        $this->producer = User::factory()->create([
            'role' => User::ROLE_PRODUCER,
        ]);

        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'title' => 'Test Project',
        ]);
    }

    /** @test */
    public function it_sends_a_new_link_when_email_matches(): void
    {
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')
                ->once()
                ->with(
                    \Mockery::on(fn ($project) => $project->id === $this->project->id),
                    \Mockery::type('string')
                )
                ->andReturnNull();

            // Allow any additional invocations from observers etc.
            $mock->shouldReceive('notifyClientProjectInvite')->andReturnNull()->byDefault();
        });

        $response = $this->post(
            route('client.portal.request_new_link', ['project' => $this->project->id]),
            ['email' => 'client@example.com']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
    }

    /** @test */
    public function it_matches_client_email_case_insensitively(): void
    {
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->once()->andReturnNull();
            $mock->shouldReceive('notifyClientProjectInvite')->andReturnNull()->byDefault();
        });

        $response = $this->post(
            route('client.portal.request_new_link', ['project' => $this->project->id]),
            ['email' => 'CLIENT@Example.COM']
        );

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function it_rejects_a_mismatched_email(): void
    {
        $this->mock(NotificationService::class, function ($mock) {
            // Should NOT be called for the request-new-link endpoint when email mismatches.
            // The initial project-creation invite is allowed.
            $mock->shouldReceive('notifyClientProjectInvite')->andReturnNull()->byDefault();
        });

        $response = $this->post(
            route('client.portal.request_new_link', ['project' => $this->project->id]),
            ['email' => 'someone-else@example.com']
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $response->assertSessionMissing('success');
    }

    /** @test */
    public function it_validates_that_email_is_present_and_valid(): void
    {
        $response = $this->post(
            route('client.portal.request_new_link', ['project' => $this->project->id]),
            ['email' => 'not-an-email']
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
    }

    /** @test */
    public function it_returns_404_for_non_client_management_projects(): void
    {
        $standardProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'client_email' => null,
        ]);

        $response = $this->post(
            route('client.portal.request_new_link', ['project' => $standardProject->id]),
            ['email' => 'anyone@example.com']
        );

        $response->assertNotFound();
    }

    /** @test */
    public function it_throttles_after_three_requests_within_ten_minutes(): void
    {
        $this->mock(NotificationService::class, function ($mock) {
            $mock->shouldReceive('notifyClientProjectInvite')->andReturnNull()->byDefault();
        });

        for ($i = 0; $i < 3; $i++) {
            $response = $this->post(
                route('client.portal.request_new_link', ['project' => $this->project->id]),
                ['email' => 'client@example.com']
            );
            $response->assertStatus(302); // Redirect back with success — not throttled yet
        }

        $fourth = $this->post(
            route('client.portal.request_new_link', ['project' => $this->project->id]),
            ['email' => 'client@example.com']
        );

        $fourth->assertStatus(429);
    }
}
