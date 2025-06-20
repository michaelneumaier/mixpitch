<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\User;
use App\Models\PitchFile;
use App\Models\PitchEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

class Phase2ClientExperienceTest extends TestCase
{
    use RefreshDatabase;

    protected $producer;
    protected $project;
    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = User::factory()->create([
            'role' => User::ROLE_PRODUCER,
            'name' => 'Test Producer'
        ]);

        $this->project = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'title' => 'Test Project',
            'description' => 'Test project description'
        ]);

        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_amount' => 500.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);
    }

    /** @test */
    public function guest_can_access_account_upgrade_page_with_signed_url()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.upgrade',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertViewIs('client_portal.upgrade');
        $response->assertViewHas('project', $this->project);
        $response->assertSee('Create Your MIXPITCH Account');
        $response->assertSee($this->project->client_email);
        $response->assertSee($this->project->title);
    }

    /** @test */
    public function guest_cannot_access_upgrade_page_without_signed_url()
    {
        $response = $this->get(route('client.portal.upgrade', ['project' => $this->project->id]));

        $response->assertStatus(403);
    }

    /** @test */
    public function existing_user_is_redirected_to_login_from_upgrade_page()
    {
        User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.upgrade',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('info', 'Please log in to access your projects.');
    }

    /** @test */
    public function guest_can_create_account_successfully()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.create_account',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $response = $this->post($signedUrl, [
            'name' => 'New Client User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        // Verify user was created
        $user = User::where('email', $this->project->client_email)->first();
        $this->assertNotNull($user);
        $this->assertEquals('New Client User', $user->name);
        $this->assertEquals(User::ROLE_CLIENT, $user->role);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotNull($user->email_verified_at);

        // Verify project was linked
        $this->project->refresh();
        $this->assertEquals($user->id, $this->project->client_user_id);

        // Verify user is logged in and redirected
        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success', 'Account created successfully! Welcome to MIXPITCH.');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function account_creation_validates_required_fields()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.create_account',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $response = $this->post($signedUrl, [
            'name' => '',
            'password' => 'short',
            'password_confirmation' => 'different'
        ]);

        $response->assertSessionHasErrors(['name', 'password']);
        $this->assertGuest();
    }

    /** @test */
    public function duplicate_account_creation_is_prevented()
    {
        User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.create_account',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $response = $this->post($signedUrl, [
            'name' => 'New Client User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    /** @test */
    public function client_can_access_dashboard_after_account_creation()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $this->project->update(['client_user_id' => $client->id]);

        $response = $this->actingAs($client)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard.client');
        $response->assertViewHas('projects');
        $response->assertViewHas('stats');
        $response->assertViewHas('recentActivity');
        $response->assertSee('Client Dashboard');
        $response->assertSee($client->name);
    }

    /** @test */
    public function client_dashboard_shows_correct_statistics()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $this->project->update(['client_user_id' => $client->id]);

        // Create additional test data
        $project2 = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_user_id' => $client->id,
            'status' => Project::STATUS_IN_PROGRESS
        ]);

        $pitch2 = Pitch::factory()->create([
            'project_id' => $project2->id,
            'user_id' => $this->producer->id,
            'payment_amount' => 300.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID
        ]);

        $response = $this->actingAs($client)->get(route('dashboard'));

        $response->assertStatus(200);
        $stats = $response->viewData('stats');
        
        $this->assertEquals(2, $stats['total_projects']);
        $this->assertEquals(1, $stats['active_projects']);
        $this->assertEquals(1, $stats['completed_projects']);
        $this->assertEquals(800.00, $stats['total_spent']); // 500 + 300
    }

    /** @test */
    public function client_can_access_invoice_with_signed_url()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertViewIs('client_portal.invoice');
        $response->assertViewHas('project', $this->project);
        $response->assertViewHas('pitch', $this->pitch);
        $response->assertViewHas('amount', 500.00);
        $response->assertSee('Invoice');
        $response->assertSee('INV-' . $this->project->id);
        $response->assertSee('$500.00');
    }

    /** @test */
    public function authenticated_client_can_access_invoice()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $this->project->update(['client_user_id' => $client->id]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->actingAs($client)->get($signedUrl);

        $response->assertStatus(200);
        $response->assertViewIs('client_portal.invoice');
    }

    /** @test */
    public function invoice_requires_paid_status()
    {
        $this->pitch->update(['payment_status' => Pitch::PAYMENT_STATUS_PENDING]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.invoice',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(404);
    }

    /** @test */
    public function client_can_access_deliverables_page()
    {
        // Create test deliverable files
        $deliverable1 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'final_mix.wav',
            'is_deliverable' => true,
            'file_size' => 1024000
        ]);

        $deliverable2 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'master.wav',
            'file_category' => 'deliverable',
            'file_size' => 2048000
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.deliverables',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertViewIs('client_portal.deliverables');
        $response->assertViewHas('project', $this->project);
        $response->assertViewHas('pitch', $this->pitch);
        $response->assertViewHas('deliverables');
        $response->assertSee('Project Deliverables');
        $response->assertSee('final_mix.wav');
        $response->assertSee('master.wav');
        $response->assertSee('1.00 MB'); // File size display
        $response->assertSee('2.00 MB');
    }

    /** @test */
    public function deliverables_requires_completed_status()
    {
        $this->pitch->update(['status' => Pitch::STATUS_IN_PROGRESS]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.deliverables',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(404);
    }

    /** @test */
    public function client_portal_shows_account_upgrade_section_for_guests()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Create Your MIXPITCH Account');
        $response->assertSee('Account Benefits');
        $response->assertSee('Create Free Account');
        $response->assertSee($this->project->client_email);
    }

    /** @test */
    public function client_portal_hides_upgrade_section_for_authenticated_users()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->actingAs($client)->get($signedUrl);

        $response->assertStatus(200);
        $response->assertDontSee('Create Your MIXPITCH Account');
        $response->assertDontSee('Create Free Account');
    }

    /** @test */
    public function completed_project_shows_enhanced_deliverables_section()
    {
        $signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(200);
        $response->assertSee('Your Project Deliverables');
        $response->assertSee('Download Files');
        $response->assertSee('View Invoice');
        $response->assertSee('Get your final deliverables');
    }

    /** @test */
    public function client_dashboard_shows_invoice_and_deliverable_links()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $this->project->update([
            'client_user_id' => $client->id,
            'status' => Project::STATUS_COMPLETED
        ]);

        $response = $this->actingAs($client)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('View Invoice');
        $response->assertSee('Deliverables');
        $response->assertSee('View Project');
    }

    /** @test */
    public function client_dashboard_shows_recent_activity()
    {
        $client = User::factory()->create([
            'email' => $this->project->client_email,
            'role' => User::ROLE_CLIENT
        ]);

        $this->project->update(['client_user_id' => $client->id]);

        // Create test events
        PitchEvent::factory()->create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'pitch_submitted',
            'created_at' => now()->subHours(2)
        ]);

        PitchEvent::factory()->create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'payment_completed',
            'created_at' => now()->subHour()
        ]);

        $response = $this->actingAs($client)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Recent Activity');
        $response->assertSee('Pitch submitted');
        $response->assertSee('Payment completed');
    }

    /** @test */
    public function non_client_management_projects_are_rejected()
    {
        $standardProject = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
            'client_email' => 'client@example.com'
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.upgrade',
            now()->addHours(1),
            ['project' => $standardProject->id]
        );

        $response = $this->get($signedUrl);

        $response->assertStatus(404);
    }

    /** @test */
    public function client_can_link_multiple_projects_to_account()
    {
        // Create multiple projects with same email
        $project2 = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => $this->project->client_email,
            'title' => 'Second Project'
        ]);

        $project3 = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => $this->project->client_email,
            'title' => 'Third Project'
        ]);

        $signedUrl = URL::temporarySignedRoute(
            'client.portal.create_account',
            now()->addHours(1),
            ['project' => $this->project->id]
        );

        $response = $this->post($signedUrl, [
            'name' => 'New Client User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);

        $user = User::where('email', $this->project->client_email)->first();

        // Verify all projects are linked
        $linkedProjects = Project::where('client_user_id', $user->id)->count();
        $this->assertEquals(3, $linkedProjects);

        $response->assertRedirect(route('dashboard'));
    }

    /** @test */
    public function producer_role_cannot_access_client_dashboard()
    {
        $response = $this->actingAs($this->producer)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard'); // Regular producer dashboard
        $response->assertDontSee('Client Dashboard');
    }
} 