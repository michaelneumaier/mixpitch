<?php

namespace Tests\Feature\ClientManagement;

use App\Mail\Client\ProducerResubmitted;
use App\Mail\Client\RevisionRequestConfirmation;
use App\Mail\Payment\ClientPaymentReceipt;
use App\Mail\Producer\ClientCommented;
use App\Mail\Producer\ClientRevisionsRequested;
use App\Mail\Producer\PaymentReceived;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailPreferenceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected Project $project;

    protected Pitch $pitch;

    protected EmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        $this->producer = User::factory()->create(['email' => 'producer@example.com']);
        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
        ]);

        $this->emailService = app(EmailService::class);
    }

    /** @test */
    public function client_revision_confirmation_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);
        $this->project->client_email_preferences = ['revision_confirmation' => true];
        $this->project->save();

        $this->emailService->sendClientRevisionRequestConfirmation(
            'client@example.com',
            'Test Client',
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal'
        );

        Mail::assertQueued(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function client_revision_confirmation_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);
        $this->project->client_email_preferences = ['revision_confirmation' => false];
        $this->project->save();

        $this->emailService->sendClientRevisionRequestConfirmation(
            'client@example.com',
            'Test Client',
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal'
        );

        Mail::assertNotQueued(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function client_producer_resubmitted_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_resubmitted', true);
        $this->project->client_email_preferences = ['producer_resubmitted' => true];
        $this->project->save();

        $this->emailService->sendClientProducerResubmitted(
            'client@example.com',
            'Test Client',
            $this->project,
            $this->pitch,
            'https://example.com/portal'
        );

        Mail::assertQueued(ProducerResubmitted::class);
    }

    /** @test */
    public function client_producer_resubmitted_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_resubmitted', true);
        $this->project->client_email_preferences = ['producer_resubmitted' => false];
        $this->project->save();

        $this->emailService->sendClientProducerResubmitted(
            'client@example.com',
            'Test Client',
            $this->project,
            $this->pitch,
            'https://example.com/portal'
        );

        Mail::assertNotQueued(ProducerResubmitted::class);
    }

    /** @test */
    public function producer_revisions_requested_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);
        $this->project->producer_email_preferences = ['producer_revisions_requested' => true];
        $this->project->save();

        $this->emailService->sendProducerClientRevisionsRequested(
            $this->producer,
            $this->project,
            $this->pitch,
            'Please add more bass'
        );

        Mail::assertQueued(ClientRevisionsRequested::class);
    }

    /** @test */
    public function producer_revisions_requested_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);
        $this->project->producer_email_preferences = ['producer_revisions_requested' => false];
        $this->project->save();

        $this->emailService->sendProducerClientRevisionsRequested(
            $this->producer,
            $this->project,
            $this->pitch,
            'Please add more bass'
        );

        Mail::assertNotQueued(ClientRevisionsRequested::class);
    }

    /** @test */
    public function producer_client_commented_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_client_commented', true);
        $this->project->producer_email_preferences = ['producer_client_commented' => true];
        $this->project->save();

        $this->emailService->sendProducerClientCommented(
            $this->producer,
            $this->project,
            $this->pitch,
            'This sounds great!'
        );

        Mail::assertQueued(ClientCommented::class);
    }

    /** @test */
    public function producer_client_commented_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_client_commented', true);
        $this->project->producer_email_preferences = ['producer_client_commented' => false];
        $this->project->save();

        $this->emailService->sendProducerClientCommented(
            $this->producer,
            $this->project,
            $this->pitch,
            'This sounds great!'
        );

        Mail::assertNotQueued(ClientCommented::class);
    }

    /** @test */
    public function client_payment_receipt_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.payment_receipt', true);
        $this->project->client_email_preferences = ['payment_receipt' => true];
        $this->project->save();

        $this->emailService->sendClientPaymentReceipt(
            $this->project,
            'Test Client',
            100.00,
            'usd',
            'inv_123456',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        Mail::assertQueued(ClientPaymentReceipt::class);
    }

    /** @test */
    public function client_payment_receipt_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.payment_receipt', true);
        $this->project->client_email_preferences = ['payment_receipt' => false];
        $this->project->save();

        $this->emailService->sendClientPaymentReceipt(
            $this->project,
            'Test Client',
            100.00,
            'usd',
            'inv_123456',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        Mail::assertNotQueued(ClientPaymentReceipt::class);
    }

    /** @test */
    public function producer_payment_received_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_payment_received', true);
        $this->project->producer_email_preferences = ['payment_received' => true];
        $this->project->save();

        $this->emailService->sendProducerPaymentReceived(
            $this->producer,
            $this->project,
            $this->pitch,
            100.00,
            10.00,
            90.00,
            'usd',
            now()->addDays(7)
        );

        Mail::assertQueued(PaymentReceived::class);
    }

    /** @test */
    public function producer_payment_received_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_payment_received', true);
        $this->project->producer_email_preferences = ['payment_received' => false];
        $this->project->save();

        $this->emailService->sendProducerPaymentReceived(
            $this->producer,
            $this->project,
            $this->pitch,
            100.00,
            10.00,
            90.00,
            'usd',
            now()->addDays(7)
        );

        Mail::assertNotQueued(PaymentReceived::class);
    }

    /** @test */
    public function global_config_disabled_blocks_email_even_when_project_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', false);
        $this->project->client_email_preferences = ['revision_confirmation' => true];
        $this->project->save();

        $this->emailService->sendClientRevisionRequestConfirmation(
            'client@example.com',
            'Test Client',
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal'
        );

        Mail::assertNotQueued(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function emails_are_sent_when_no_project_preference_set()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);
        $this->project->client_email_preferences = null; // No preferences set
        $this->project->save();

        $this->emailService->sendClientRevisionRequestConfirmation(
            'client@example.com',
            'Test Client',
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal'
        );

        Mail::assertQueued(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function all_producer_emails_respect_preferences()
    {
        Config::set('business.email_notifications.client_management', [
            'enabled' => true,
            'producer_revisions_requested' => true,
            'producer_client_commented' => true,
            'producer_payment_received' => true,
        ]);

        // Disable all producer preferences
        $this->project->producer_email_preferences = [
            'producer_revisions_requested' => false,
            'producer_client_commented' => false,
            'payment_received' => false,
        ];
        $this->project->save();

        // Try to send all producer emails
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->producer,
            $this->project,
            $this->pitch,
            'Revisions needed'
        );

        $this->emailService->sendProducerClientCommented(
            $this->producer,
            $this->project,
            $this->pitch,
            'Great work!'
        );

        $this->emailService->sendProducerPaymentReceived(
            $this->producer,
            $this->project,
            $this->pitch,
            100.00,
            10.00,
            90.00,
            'usd',
            now()->addDays(7)
        );

        // None should be sent
        Mail::assertNotQueued(ClientRevisionsRequested::class);
        Mail::assertNotQueued(ClientCommented::class);
        Mail::assertNotQueued(PaymentReceived::class);
    }

    /** @test */
    public function all_client_emails_respect_preferences()
    {
        Config::set('business.email_notifications.client_management', [
            'enabled' => true,
            'revision_confirmation' => true,
            'producer_resubmitted' => true,
            'payment_receipt' => true,
        ]);

        // Disable all client preferences
        $this->project->client_email_preferences = [
            'revision_confirmation' => false,
            'producer_resubmitted' => false,
            'payment_receipt' => false,
        ];
        $this->project->save();

        // Try to send all client emails
        $this->emailService->sendClientRevisionRequestConfirmation(
            'client@example.com',
            'Client',
            $this->project,
            $this->pitch,
            'Feedback',
            'https://example.com'
        );

        $this->emailService->sendClientProducerResubmitted(
            'client@example.com',
            'Client',
            $this->project,
            $this->pitch,
            'https://example.com'
        );

        $this->emailService->sendClientPaymentReceipt(
            $this->project,
            'Client',
            100.00,
            'usd',
            'inv_123',
            'https://example.com/invoice',
            'https://example.com/portal'
        );

        // None should be sent
        Mail::assertNotQueued(RevisionRequestConfirmation::class);
        Mail::assertNotQueued(ProducerResubmitted::class);
        Mail::assertNotQueued(ClientPaymentReceipt::class);
    }

    /** @test */
    public function partial_preferences_work_correctly()
    {
        Config::set('business.email_notifications.client_management', [
            'enabled' => true,
            'producer_revisions_requested' => true,
            'producer_client_commented' => true,
            'producer_payment_received' => true,
        ]);

        // Only disable one preference
        $this->project->producer_email_preferences = [
            'producer_client_commented' => false,
            // Other preferences not set, should default to enabled
        ];
        $this->project->save();

        // This should be sent (preference not explicitly set)
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->producer,
            $this->project,
            $this->pitch,
            'Revisions'
        );
        Mail::assertQueued(ClientRevisionsRequested::class);

        // This should be blocked (explicitly disabled)
        $this->emailService->sendProducerClientCommented(
            $this->producer,
            $this->project,
            $this->pitch,
            'Comment'
        );
        Mail::assertNotQueued(ClientCommented::class);

        // This should be sent (preference not explicitly set)
        $this->emailService->sendProducerPaymentReceived(
            $this->producer,
            $this->project,
            $this->pitch,
            100.00,
            10.00,
            90.00,
            'usd',
            now()->addDays(7)
        );
        Mail::assertQueued(PaymentReceived::class);
    }

    /** @test */
    public function preferences_only_affect_specific_project()
    {
        // Create another project with different preferences
        $otherProject = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'other@example.com',
        ]);
        $otherPitch = Pitch::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->producer->id,
        ]);

        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);

        // Disable for first project
        $this->project->producer_email_preferences = ['producer_revisions_requested' => false];
        $this->project->save();

        // Leave second project with defaults (enabled)
        $otherProject->producer_email_preferences = null;
        $otherProject->save();

        // First project email should be blocked
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->producer,
            $this->project,
            $this->pitch,
            'Revisions'
        );

        // Second project email should be sent
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->producer,
            $otherProject,
            $otherPitch,
            'Revisions'
        );

        Mail::assertQueued(ClientRevisionsRequested::class, 1); // Only one sent
    }
}
