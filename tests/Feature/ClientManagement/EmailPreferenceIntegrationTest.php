<?php

namespace Tests\Feature\ClientManagement;

use App\Mail\Client\ProducerResubmitted;
use App\Mail\Client\RevisionRequestConfirmation;
use App\Mail\Payment\ClientReceipt;
use App\Mail\Producer\ClientCommented;
use App\Mail\Producer\ClientRevisionsRequested;
use App\Mail\Producer\PaymentReceived;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
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
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal',
            'Test Client'
        );

        Mail::assertSent(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function client_revision_confirmation_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);
        $this->project->client_email_preferences = ['revision_confirmation' => false];
        $this->project->save();

        Log::shouldReceive('info')
            ->once()
            ->with('Revision confirmation email disabled by client preference', [
                'project_id' => $this->project->id,
            ]);

        $this->emailService->sendClientRevisionRequestConfirmation(
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal',
            'Test Client'
        );

        Mail::assertNotSent(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function client_producer_resubmitted_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_resubmitted', true);
        $this->project->client_email_preferences = ['producer_resubmitted' => true];
        $this->project->save();

        $this->emailService->sendClientProducerResubmitted(
            $this->project,
            $this->pitch,
            'https://example.com/portal',
            'Test Client'
        );

        Mail::assertSent(ProducerResubmitted::class);
    }

    /** @test */
    public function client_producer_resubmitted_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_resubmitted', true);
        $this->project->client_email_preferences = ['producer_resubmitted' => false];
        $this->project->save();

        Log::shouldReceive('info')
            ->once()
            ->with('Producer resubmitted email disabled by client preference', [
                'project_id' => $this->project->id,
            ]);

        $this->emailService->sendClientProducerResubmitted(
            $this->project,
            $this->pitch,
            'https://example.com/portal',
            'Test Client'
        );

        Mail::assertNotSent(ProducerResubmitted::class);
    }

    /** @test */
    public function producer_revisions_requested_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);
        $this->project->producer_email_preferences = ['producer_revisions_requested' => true];
        $this->project->save();

        $this->emailService->sendProducerClientRevisionsRequested(
            $this->project,
            $this->pitch,
            'Please add more bass',
            'Test Client'
        );

        Mail::assertSent(ClientRevisionsRequested::class);
    }

    /** @test */
    public function producer_revisions_requested_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_revisions_requested', true);
        $this->project->producer_email_preferences = ['producer_revisions_requested' => false];
        $this->project->save();

        Log::shouldReceive('info')
            ->once()
            ->with('Client revisions requested email disabled by producer preference', [
                'project_id' => $this->project->id,
            ]);

        $this->emailService->sendProducerClientRevisionsRequested(
            $this->project,
            $this->pitch,
            'Please add more bass',
            'Test Client'
        );

        Mail::assertNotSent(ClientRevisionsRequested::class);
    }

    /** @test */
    public function producer_client_commented_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.producer_client_commented', true);
        $this->project->producer_email_preferences = ['producer_client_commented' => true];
        $this->project->save();

        $this->emailService->sendProducerClientCommented(
            $this->project,
            $this->pitch,
            'This sounds great!',
            'Test Client'
        );

        Mail::assertSent(ClientCommented::class);
    }

    /** @test */
    public function producer_client_commented_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.producer_client_commented', true);
        $this->project->producer_email_preferences = ['producer_client_commented' => false];
        $this->project->save();

        Log::shouldReceive('info')
            ->once()
            ->with('Client commented email disabled by producer preference', [
                'project_id' => $this->project->id,
            ]);

        $this->emailService->sendProducerClientCommented(
            $this->project,
            $this->pitch,
            'This sounds great!',
            'Test Client'
        );

        Mail::assertNotSent(ClientCommented::class);
    }

    /** @test */
    public function client_payment_receipt_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.payment_receipt', true);
        $this->project->client_email_preferences = ['payment_receipt' => true];
        $this->project->save();

        $this->emailService->sendClientPaymentReceipt(
            $this->project,
            100.00,
            'inv_123456',
            'Test Client'
        );

        Mail::assertSent(ClientReceipt::class);
    }

    /** @test */
    public function client_payment_receipt_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.payment_receipt', true);
        $this->project->client_email_preferences = ['payment_receipt' => false];
        $this->project->save();

        Log::shouldReceive('info')
            ->once()
            ->with('Payment receipt email disabled by client preference', [
                'project_id' => $this->project->id,
            ]);

        $this->emailService->sendClientPaymentReceipt(
            $this->project,
            100.00,
            'inv_123456',
            'Test Client'
        );

        Mail::assertNotSent(ClientReceipt::class);
    }

    /** @test */
    public function producer_payment_received_email_is_sent_when_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.payment_received', true);
        $this->project->producer_email_preferences = ['payment_received' => true];
        $this->project->save();

        $this->emailService->sendProducerPaymentReceived(
            $this->project,
            $this->pitch,
            100.00
        );

        Mail::assertSent(PaymentReceived::class);
    }

    /** @test */
    public function producer_payment_received_email_is_blocked_when_preference_disabled()
    {
        Config::set('business.email_notifications.client_management.payment_received', true);
        $this->project->producer_email_preferences = ['payment_received' => false];
        $this->project->save();

        Log::shouldReceive('info')
            ->once()
            ->with('Payment received email disabled by producer preference', [
                'project_id' => $this->project->id,
            ]);

        $this->emailService->sendProducerPaymentReceived(
            $this->project,
            $this->pitch,
            100.00
        );

        Mail::assertNotSent(PaymentReceived::class);
    }

    /** @test */
    public function global_config_disabled_blocks_email_even_when_project_preference_enabled()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', false);
        $this->project->client_email_preferences = ['revision_confirmation' => true];
        $this->project->save();

        $this->emailService->sendClientRevisionRequestConfirmation(
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal',
            'Test Client'
        );

        Mail::assertNotSent(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function emails_are_sent_when_no_project_preference_set()
    {
        Config::set('business.email_notifications.client_management.revision_confirmation', true);
        $this->project->client_email_preferences = null; // No preferences set
        $this->project->save();

        $this->emailService->sendClientRevisionRequestConfirmation(
            $this->project,
            $this->pitch,
            'Please add more bass',
            'https://example.com/portal',
            'Test Client'
        );

        Mail::assertSent(RevisionRequestConfirmation::class);
    }

    /** @test */
    public function all_producer_emails_respect_preferences()
    {
        Config::set('business.email_notifications.client_management', [
            'producer_revisions_requested' => true,
            'producer_client_commented' => true,
            'payment_received' => true,
        ]);

        // Disable all producer preferences
        $this->project->producer_email_preferences = [
            'producer_revisions_requested' => false,
            'producer_client_commented' => false,
            'payment_received' => false,
        ];
        $this->project->save();

        Log::shouldReceive('info')->times(3);

        // Try to send all producer emails
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->project,
            $this->pitch,
            'Revisions needed',
            'Client'
        );

        $this->emailService->sendProducerClientCommented(
            $this->project,
            $this->pitch,
            'Great work!',
            'Client'
        );

        $this->emailService->sendProducerPaymentReceived(
            $this->project,
            $this->pitch,
            100.00
        );

        // None should be sent
        Mail::assertNotSent(ClientRevisionsRequested::class);
        Mail::assertNotSent(ClientCommented::class);
        Mail::assertNotSent(PaymentReceived::class);
    }

    /** @test */
    public function all_client_emails_respect_preferences()
    {
        Config::set('business.email_notifications.client_management', [
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

        Log::shouldReceive('info')->times(3);

        // Try to send all client emails
        $this->emailService->sendClientRevisionRequestConfirmation(
            $this->project,
            $this->pitch,
            'Feedback',
            'https://example.com',
            'Client'
        );

        $this->emailService->sendClientProducerResubmitted(
            $this->project,
            $this->pitch,
            'https://example.com',
            'Client'
        );

        $this->emailService->sendClientPaymentReceipt(
            $this->project,
            100.00,
            'inv_123',
            'Client'
        );

        // None should be sent
        Mail::assertNotSent(RevisionRequestConfirmation::class);
        Mail::assertNotSent(ProducerResubmitted::class);
        Mail::assertNotSent(ClientReceipt::class);
    }

    /** @test */
    public function partial_preferences_work_correctly()
    {
        Config::set('business.email_notifications.client_management', [
            'producer_revisions_requested' => true,
            'producer_client_commented' => true,
            'payment_received' => true,
        ]);

        // Only disable one preference
        $this->project->producer_email_preferences = [
            'producer_client_commented' => false,
            // Other preferences not set, should default to enabled
        ];
        $this->project->save();

        Log::shouldReceive('info')->once();

        // This should be sent (preference not explicitly set)
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->project,
            $this->pitch,
            'Revisions',
            'Client'
        );
        Mail::assertSent(ClientRevisionsRequested::class);

        // This should be blocked (explicitly disabled)
        $this->emailService->sendProducerClientCommented(
            $this->project,
            $this->pitch,
            'Comment',
            'Client'
        );
        Mail::assertNotSent(ClientCommented::class);

        // This should be sent (preference not explicitly set)
        $this->emailService->sendProducerPaymentReceived(
            $this->project,
            $this->pitch,
            100.00
        );
        Mail::assertSent(PaymentReceived::class);
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

        Log::shouldReceive('info')->once();

        // First project email should be blocked
        $this->emailService->sendProducerClientRevisionsRequested(
            $this->project,
            $this->pitch,
            'Revisions',
            'Client'
        );

        // Second project email should be sent
        $this->emailService->sendProducerClientRevisionsRequested(
            $otherProject,
            $otherPitch,
            'Revisions',
            'Client 2'
        );

        Mail::assertSent(ClientRevisionsRequested::class, 1); // Only one sent
    }
}
