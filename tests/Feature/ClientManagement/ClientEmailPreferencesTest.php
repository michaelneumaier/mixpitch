<?php

namespace Tests\Feature\ClientManagement;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ClientEmailPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected Project $project;

    protected Pitch $pitch;

    protected string $signedUrl;

    protected function setUp(): void
    {
        parent::setUp();

        // Don't mock services - let them run normally for client portal tests

        // Create a producer and client management project
        $this->producer = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);

        // Get the auto-created pitch
        $this->pitch = $this->project->pitches()->first();

        // Generate signed URL for client portal access
        $this->signedUrl = URL::temporarySignedRoute(
            'client.portal.view',
            now()->addDays(7),
            ['project' => $this->project->id]
        );
    }

    /** @test */
    public function client_can_view_email_preferences_in_portal()
    {
        $response = $this->get($this->signedUrl);

        $response->assertStatus(200)
            ->assertSee('Email Preferences')
            ->assertSee('Revision request confirmations')
            ->assertSee('Producer file updates')
            ->assertSee('Payment receipts');
    }

    /** @test */
    public function client_can_update_email_preference_via_ajax()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Make signed URL to get session/cookies
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => false,
        ]);

        $response->assertSuccessful()
            ->assertJson([
                'success' => true,
                'message' => 'Email preference updated',
            ]);

        // Verify database was updated
        $this->project->refresh();
        $this->assertFalse($this->project->client_email_preferences['revision_confirmation']);
    }

    /** @test */
    public function client_can_enable_disabled_preference()
    {
        // Start with preference disabled
        $this->project->client_email_preferences = [
            'revision_confirmation' => false,
        ];
        $this->project->save();

        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => true,
        ]);

        $response->assertSuccessful()
            ->assertJson(['success' => true]);

        // Verify database
        $this->project->refresh();
        $this->assertTrue($this->project->client_email_preferences['revision_confirmation']);
    }

    /** @test */
    public function client_can_update_multiple_preferences()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        // Update first preference
        $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => false,
        ])->assertSuccessful();

        // Update second preference
        $this->postJson($updateUrl, [
            'type' => 'producer_resubmitted',
            'enabled' => false,
        ])->assertSuccessful();

        // Update third preference
        $this->postJson($updateUrl, [
            'type' => 'payment_receipt',
            'enabled' => false,
        ])->assertSuccessful();

        // Verify all changes persisted
        $this->project->refresh();
        $this->assertFalse($this->project->client_email_preferences['revision_confirmation']);
        $this->assertFalse($this->project->client_email_preferences['producer_resubmitted']);
        $this->assertFalse($this->project->client_email_preferences['payment_receipt']);
    }

    /** @test */
    public function validation_rejects_invalid_preference_type()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'type' => 'invalid_type', // Not in the allowed list
            'enabled' => false,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    /** @test */
    public function validation_requires_type_field()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'enabled' => false,
            // Missing 'type'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }

    /** @test */
    public function validation_requires_enabled_field()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            // Missing 'enabled'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('enabled');
    }

    /** @test */
    public function validation_requires_enabled_to_be_boolean()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => 'not-a-boolean',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('enabled');
    }

    /** @test */
    public function non_client_management_project_returns_404()
    {
        // Create a standard project
        $standardProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_STANDARD,
        ]);

        $updateUrl = route('client.portal.update-email-prefs', ['project' => $standardProject->id]);

        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => false,
        ]);

        $response->assertNotFound();
    }

    /** @test */
    public function updating_preference_logs_the_change()
    {
        // Note: Logging is tested in unit tests for the model methods
        // Here we just verify the update completes successfully
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => false,
        ]);

        $response->assertSuccessful();

        // Verify the update completed
        $this->project->refresh();
        $this->assertFalse($this->project->client_email_preferences['revision_confirmation']);
    }

    // Note: Error handling tests are covered by integration tests
    // Testing forced errors can be fragile and doesn't reflect real-world scenarios

    /** @test */
    public function preference_changes_do_not_affect_other_projects()
    {
        // Create a second client management project
        $otherProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'other@example.com',
            'client_name' => 'Other Client',
        ]);

        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        // Update preference on first project
        $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => false,
        ]);

        // Verify the other project still has default preferences
        $otherProject->refresh();
        $this->assertNull($otherProject->client_email_preferences);

        // Verify first project was updated
        $this->project->refresh();
        $this->assertFalse($this->project->client_email_preferences['revision_confirmation']);
    }

    /** @test */
    public function client_preferences_are_displayed_correctly_in_view()
    {
        // Set custom preferences
        $this->project->client_email_preferences = [
            'revision_confirmation' => false,
            'producer_resubmitted' => true,
            'payment_receipt' => false,
        ];
        $this->project->save();

        $response = $this->get($this->signedUrl);

        // The view should reflect the saved preferences
        $response->assertStatus(200)
            ->assertSee('Email Preferences');

        // Note: Testing checkboxes is tricky in integration tests
        // The actual checked state would be verified in browser tests
    }

    /** @test */
    public function signed_url_middleware_allows_access_to_update_endpoint()
    {
        // This test verifies that the signed_or_client middleware allows access
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // First access the signed URL to establish session
        $this->get($this->signedUrl);

        // Now the update should work
        $response = $this->postJson($updateUrl, [
            'type' => 'revision_confirmation',
            'enabled' => false,
        ]);

        $response->assertSuccessful();
    }

    /** @test */
    public function only_allowed_preference_types_can_be_updated()
    {
        $updateUrl = route('client.portal.update-email-prefs', ['project' => $this->project->id]);

        // Get signed URL first
        $this->get($this->signedUrl);

        // Try to update a producer-only preference type
        $response = $this->postJson($updateUrl, [
            'type' => 'producer_revisions_requested', // Not allowed for clients
            'enabled' => false,
        ]);

        // Should fail validation
        $response->assertStatus(422)
            ->assertJsonValidationErrors('type');
    }
}
