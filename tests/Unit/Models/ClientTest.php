<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = User::factory()->create();
        $this->client = Client::factory()->create([
            'user_id' => $this->producer->id,
            'email' => 'client@example.com',
            'name' => 'Test Client',
            'company' => 'Test Company',
            'status' => Client::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function can_create_client_with_valid_data()
    {
        $client = Client::factory()->create([
            'user_id' => $this->producer->id,
            'email' => 'new@example.com',
            'name' => 'New Client',
            'status' => Client::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('clients', [
            'user_id' => $this->producer->id,
            'email' => 'new@example.com',
            'name' => 'New Client',
            'status' => Client::STATUS_ACTIVE,
        ]);
    }

    /** @test */
    public function belongs_to_producer()
    {
        $this->assertInstanceOf(User::class, $this->client->producer);
        $this->assertEquals($this->producer->id, $this->client->producer->id);
    }

    /** @test */
    public function has_many_projects_through_email()
    {
        // Create projects with matching client_email
        $project1 = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'workflow_type' => 'client_management',
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'workflow_type' => 'client_management',
        ]);

        // Create project with different email (should not be included)
        Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => 'other@example.com',
            'workflow_type' => 'client_management',
        ]);

        $projects = $this->client->projects;

        $this->assertCount(2, $projects);
        $this->assertTrue($projects->contains($project1));
        $this->assertTrue($projects->contains($project2));
    }

    /** @test */
    public function can_get_active_projects()
    {
        $activeProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'status' => Project::STATUS_OPEN,
        ]);

        $completedProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'status' => Project::STATUS_COMPLETED,
        ]);

        $activeProjects = $this->client->activeProjects;

        $this->assertCount(1, $activeProjects);
        $this->assertTrue($activeProjects->contains($activeProject));
        $this->assertFalse($activeProjects->contains($completedProject));
    }

    /** @test */
    public function can_get_completed_projects()
    {
        $activeProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'status' => Project::STATUS_OPEN,
        ]);

        $completedProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'status' => Project::STATUS_COMPLETED,
        ]);

        $completedProjects = $this->client->completedProjects;

        $this->assertCount(1, $completedProjects);
        $this->assertFalse($completedProjects->contains($activeProject));
        $this->assertTrue($completedProjects->contains($completedProject));
    }

    /** @test */
    public function can_get_latest_project()
    {
        $olderProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'created_at' => now()->subDays(5),
        ]);

        $newerProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
            'created_at' => now()->subDay(),
        ]);

        $latestProject = $this->client->latestProject();

        $this->assertEquals($newerProject->id, $latestProject->id);
    }

    /** @test */
    public function can_update_client_statistics()
    {
        // Create projects with pitches and payments
        $project1 = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
        ]);

        $project2 = Project::factory()->create([
            'user_id' => $this->producer->id,
            'client_email' => $this->client->email,
        ]);

        $pitch1 = \App\Models\Pitch::factory()->create([
            'project_id' => $project1->id,
            'user_id' => $this->producer->id,
            'payment_status' => 'paid',
            'payment_amount' => 500.00,
        ]);

        $pitch2 = \App\Models\Pitch::factory()->create([
            'project_id' => $project2->id,
            'user_id' => $this->producer->id,
            'payment_status' => 'paid',
            'payment_amount' => 750.00,
        ]);

        $this->client->updateStats();

        $this->assertEquals(2, $this->client->fresh()->total_projects);
        $this->assertEquals(1250.00, $this->client->fresh()->total_spent);
    }

    /** @test */
    public function can_mark_as_contacted()
    {
        $originalTimestamp = $this->client->last_contacted_at;

        $this->client->markAsContacted();

        $this->assertNotEquals($originalTimestamp, $this->client->fresh()->last_contacted_at);
        $this->assertTrue($this->client->fresh()->last_contacted_at->isToday());
    }

    /** @test */
    public function status_scopes_work_correctly()
    {
        $activeClient = Client::factory()->active()->create(['user_id' => $this->producer->id]);
        $inactiveClient = Client::factory()->inactive()->create(['user_id' => $this->producer->id]);
        $blockedClient = Client::factory()->blocked()->create(['user_id' => $this->producer->id]);

        $this->assertCount(2, Client::active()->get()); // includes $this->client
        $this->assertCount(1, Client::inactive()->get());
        $this->assertCount(1, Client::blocked()->get());
    }

    /** @test */
    public function recently_contacted_scope_works()
    {
        $recentClient = Client::factory()->create([
            'user_id' => $this->producer->id,
            'last_contacted_at' => now()->subDays(5),
        ]);

        $oldClient = Client::factory()->create([
            'user_id' => $this->producer->id,
            'last_contacted_at' => now()->subDays(40),
        ]);

        $recentClients = Client::recentlyContacted(30)->get();

        $this->assertTrue($recentClients->contains($recentClient));
        $this->assertFalse($recentClients->contains($oldClient));
    }

    /** @test */
    public function needs_follow_up_scope_works()
    {
        $needsFollowUpClient = Client::factory()->create([
            'user_id' => $this->producer->id,
            'last_contacted_at' => now()->subDays(20),
        ]);

        $recentClient = Client::factory()->create([
            'user_id' => $this->producer->id,
            'last_contacted_at' => now()->subDays(5),
        ]);

        $neverContactedClient = Client::factory()->create([
            'user_id' => $this->producer->id,
            'last_contacted_at' => null,
        ]);

        $needsFollowUp = Client::needsFollowUp(14)->get();

        $this->assertTrue($needsFollowUp->contains($needsFollowUpClient));
        $this->assertTrue($needsFollowUp->contains($neverContactedClient));
        $this->assertFalse($needsFollowUp->contains($recentClient));
    }

    /** @test */
    public function display_name_attribute_returns_name_or_email()
    {
        $clientWithName = Client::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $clientWithoutName = Client::factory()->create([
            'name' => null,
            'email' => 'anonymous@example.com',
        ]);

        $this->assertEquals('John Doe', $clientWithName->display_name);
        $this->assertEquals('anonymous@example.com', $clientWithoutName->display_name);
    }

    /** @test */
    public function initials_attribute_generates_correctly()
    {
        $clientWithFullName = Client::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $clientWithSingleName = Client::factory()->create([
            'name' => 'Madonna',
            'email' => 'madonna@example.com',
        ]);

        $clientWithoutName = Client::factory()->create([
            'name' => null,
            'email' => 'test@example.com',
        ]);

        $this->assertEquals('JD', $clientWithFullName->initials);
        $this->assertEquals('MA', $clientWithSingleName->initials);
        $this->assertEquals('TE', $clientWithoutName->initials);
    }

    /** @test */
    public function status_check_methods_work()
    {
        $activeClient = Client::factory()->active()->create();
        $inactiveClient = Client::factory()->inactive()->create();
        $blockedClient = Client::factory()->blocked()->create();

        $this->assertTrue($activeClient->isActive());
        $this->assertFalse($activeClient->isInactive());
        $this->assertFalse($activeClient->isBlocked());

        $this->assertFalse($inactiveClient->isActive());
        $this->assertTrue($inactiveClient->isInactive());
        $this->assertFalse($inactiveClient->isBlocked());

        $this->assertFalse($blockedClient->isActive());
        $this->assertFalse($blockedClient->isInactive());
        $this->assertTrue($blockedClient->isBlocked());
    }

    /** @test */
    public function status_label_attribute_returns_human_readable_status()
    {
        $activeClient = Client::factory()->active()->create();
        $inactiveClient = Client::factory()->inactive()->create();
        $blockedClient = Client::factory()->blocked()->create();

        $this->assertEquals('Active', $activeClient->status_label);
        $this->assertEquals('Inactive', $inactiveClient->status_label);
        $this->assertEquals('Blocked', $blockedClient->status_label);
    }

    /** @test */
    public function status_badge_class_returns_correct_css_classes()
    {
        $activeClient = Client::factory()->active()->create();
        $inactiveClient = Client::factory()->inactive()->create();
        $blockedClient = Client::factory()->blocked()->create();

        $this->assertEquals('bg-green-100 text-green-800', $activeClient->status_badge_class);
        $this->assertEquals('bg-gray-100 text-gray-800', $inactiveClient->status_badge_class);
        $this->assertEquals('bg-red-100 text-red-800', $blockedClient->status_badge_class);
    }

    /** @test */
    public function casts_preferences_and_tags_as_arrays()
    {
        $client = Client::factory()->create([
            'preferences' => ['format' => 'mp3', 'quality' => 'high'],
            'tags' => ['VIP', 'Rush'],
        ]);

        $this->assertIsArray($client->preferences);
        $this->assertIsArray($client->tags);
        $this->assertEquals(['format' => 'mp3', 'quality' => 'high'], $client->preferences);
        $this->assertEquals(['VIP', 'Rush'], $client->tags);
    }

    /** @test */
    public function casts_timestamps_correctly()
    {
        $client = Client::factory()->create([
            'last_contacted_at' => '2025-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $client->last_contacted_at);
        $this->assertEquals('2025-01-15', $client->last_contacted_at->format('Y-m-d'));
    }

    /** @test */
    public function enforces_unique_email_per_producer()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Try to create another client with same email for same producer
        Client::factory()->create([
            'user_id' => $this->producer->id,
            'email' => $this->client->email, // Same email as existing client
        ]);
    }

    /** @test */
    public function allows_same_email_for_different_producers()
    {
        $otherProducer = User::factory()->create();

        // Should be able to create client with same email for different producer
        $otherClient = Client::factory()->create([
            'user_id' => $otherProducer->id,
            'email' => $this->client->email, // Same email, different producer
        ]);

        $this->assertDatabaseHas('clients', [
            'user_id' => $otherProducer->id,
            'email' => $this->client->email,
        ]);
    }
}
