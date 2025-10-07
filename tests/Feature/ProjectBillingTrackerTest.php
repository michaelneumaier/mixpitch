<?php

namespace Tests\Feature;

use App\Livewire\Project\Component\ProjectBillingTracker;
use App\Models\Pitch;
use App\Models\PitchMilestone;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\StripeConnectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProjectBillingTrackerTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected Project $project;

    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock NotificationService to avoid notification sending
        $notificationMock = Mockery::mock(NotificationService::class);
        $notificationMock->shouldReceive('notifyPayoutScheduled')->andReturn(null);
        $notificationMock->shouldReceive('notifyClientProjectInvite')->andReturn(null);
        $notificationMock->shouldIgnoreMissing();
        $this->app->instance(NotificationService::class, $notificationMock);

        // Mock StripeConnectService to avoid Stripe API calls
        $stripeMock = Mockery::mock(StripeConnectService::class);
        $this->app->instance(StripeConnectService::class, $stripeMock);

        // Create a producer (pitch owner)
        $this->producer = User::factory()->create([
            'stripe_account_id' => 'acct_test_'.uniqid(),
        ]);

        // Create a client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'budget' => 5000,
        ]);

        // Create a pitch for the project
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
            'payment_amount' => 5000,
        ]);

        // Act as the producer
        $this->actingAs($this->producer);
    }

    protected function getWorkflowColors(): array
    {
        return [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-gray-600',
        ];
    }

    public function test_can_mount_the_billing_tracker_component(): void
    {
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertStatus(200);
    }

    public function test_displays_payment_summary_correctly(): void
    {
        // Create milestones
        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Milestone 1',
            'amount' => 2000,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'sort_order' => 1,
        ]);

        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Milestone 2',
            'amount' => 1500,
            'payment_status' => Pitch::PAYMENT_STATUS_PROCESSING,
            'sort_order' => 2,
        ]);

        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Milestone 3',
            'amount' => 1500,
            'payment_status' => null,
            'sort_order' => 3,
        ]);

        $component = Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ]);

        $paymentSummary = $component->viewData('paymentSummary');

        $this->assertEquals(5000.0, $paymentSummary['total_budget']);
        $this->assertEquals(2000.0, $paymentSummary['paid_amount']);
        $this->assertEquals(3000.0, $paymentSummary['outstanding_amount']);
        $this->assertEquals(1, $paymentSummary['paid_count']);
        $this->assertEquals(3, $paymentSummary['total_milestones']);
    }

    public function test_shows_all_milestones_with_correct_status_badges(): void
    {
        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Initial Deposit',
            'amount' => 2000,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'sort_order' => 1,
        ]);

        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Progress Payment',
            'amount' => 1500,
            'payment_status' => Pitch::PAYMENT_STATUS_PROCESSING,
            'sort_order' => 2,
        ]);

        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Final Payment',
            'amount' => 1500,
            'payment_status' => null,
            'sort_order' => 3,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Initial Deposit')
            ->assertSee('Progress Payment')
            ->assertSee('Final Payment')
            ->assertSee('Paid')
            ->assertSee('Processing')
            ->assertSee('Pending');
    }

    public function test_allows_producer_to_open_manual_payment_modal(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->call('openManualPaymentModal', $milestone->id)
            ->assertSet('showManualPaymentModal', true)
            ->assertSet('selectedMilestoneId', $milestone->id);
    }

    public function test_prevents_opening_manual_payment_modal_for_already_paid_milestone(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->call('openManualPaymentModal', $milestone->id)
            ->assertSet('showManualPaymentModal', false);
    }

    public function test_can_mark_milestone_as_paid_manually_with_note(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', 'Payment received via wire transfer')
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually')
            ->assertSet('showManualPaymentModal', false);

        $milestone->refresh();

        $this->assertEquals(Pitch::PAYMENT_STATUS_PAID, $milestone->payment_status);
        $this->assertNotNull($milestone->payment_completed_at);
        $this->assertStringStartsWith('MANUAL_', $milestone->stripe_invoice_id);
    }

    public function test_creates_audit_event_when_marking_milestone_as_paid_manually(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', 'Payment received via wire transfer')
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually');

        $event = $this->pitch->events()->where('event_type', 'milestone_manually_marked_paid')->first();

        $this->assertNotNull($event);
        $this->assertTrue($event->metadata['manual_payment']);
        $this->assertEquals('Payment received via wire transfer', $event->metadata['note']);
        $this->assertEquals('2000', $event->metadata['amount']);
        $this->assertEquals($this->producer->id, $event->created_by);
    }

    public function test_requires_confirmation_checkbox_to_mark_milestone_as_paid_manually(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', 'Payment received')
            ->set('confirmManualPayment', false)
            ->call('markMilestoneAsPaidManually')
            ->assertHasErrors(['confirmManualPayment']);

        $milestone->refresh();
        $this->assertNotEquals(Pitch::PAYMENT_STATUS_PAID, $milestone->payment_status);
    }

    public function test_prevents_unauthorized_users_from_marking_milestones_as_paid(): void
    {
        $otherUser = User::factory()->create();
        $this->actingAs($otherUser);

        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually')
            ->assertForbidden();
    }

    public function test_displays_payment_timeline_with_recent_events(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'sort_order' => 1,
        ]);

        // Create a payment event
        $this->pitch->events()->create([
            'event_type' => 'milestone_paid',
            'comment' => 'Milestone "Initial Deposit" payment received.',
            'status' => $this->pitch->status,
            'created_by' => $this->producer->id,
            'metadata' => [
                'milestone_id' => $milestone->id,
                'amount' => '2000',
            ],
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Recent Activity')
            ->assertSee('Milestone "Initial Deposit" payment received.');
    }

    public function test_shows_revision_milestone_badge_correctly(): void
    {
        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Additional Revision',
            'amount' => 500,
            'payment_status' => null,
            'is_revision_milestone' => true,
            'revision_round_number' => 3,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Revision');
    }

    public function test_shows_manual_payment_badge_for_manually_paid_milestones(): void
    {
        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Manual Payment',
            'amount' => 2000,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'MANUAL_'.time(),
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Manual');
    }

    public function test_displays_empty_state_when_no_milestones_exist(): void
    {
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('No milestones configured yet');
    }

    public function test_shows_all_paid_success_message_when_all_milestones_are_paid(): void
    {
        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Milestone 1',
            'amount' => 2500,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'sort_order' => 1,
        ]);

        PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Milestone 2',
            'amount' => 2500,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'sort_order' => 2,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('All milestones paid!');
    }

    public function test_closes_manual_payment_modal_when_cancel_is_clicked(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('showManualPaymentModal', true)
            ->call('closeManualPaymentModal')
            ->assertSet('showManualPaymentModal', false)
            ->assertSet('selectedMilestoneId', null)
            ->assertSet('manualPaymentNote', null)
            ->assertSet('confirmManualPayment', false);
    }

    public function test_dispatches_milestones_updated_event_after_marking_milestone_as_paid(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually')
            ->assertDispatched('milestonesUpdated');
    }

    public function test_displays_manual_payment_note_for_manually_paid_milestones(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        // Mark as paid with a note
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', 'Payment received via wire transfer on January 15, 2024')
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually');

        // Verify note is displayed
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Payment Note:')
            ->assertSee('Payment received via wire transfer');
    }

    public function test_can_toggle_note_expansion_for_long_notes(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        $longNote = 'This is a very long payment note that exceeds the 60 character limit and should be truncated initially, requiring the user to click to expand it.';

        // Mark as paid with long note
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', $longNote)
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually');

        // Test expansion toggle
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('(click to expand)')
            ->call('toggleNoteExpansion', $milestone->id)
            ->assertSet('expandedNotes.'.$milestone->id, true)
            ->assertSee('(click to collapse)')
            ->call('toggleNoteExpansion', $milestone->id)
            ->assertSet('expandedNotes.'.$milestone->id, null);
    }

    public function test_does_not_display_note_section_when_no_note_provided(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        // Mark as paid without a note
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', null)
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually');

        // Verify note section is not displayed
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertDontSee('Payment Note:');
    }

    public function test_get_manual_payment_note_returns_null_for_stripe_payments(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => \App\Models\Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
            'stripe_invoice_id' => 'in_1234567890', // Stripe invoice, not manual
            'sort_order' => 1,
        ]);

        $component = Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ]);

        $note = $component->instance()->getManualPaymentNote($milestone);
        $this->assertNull($note);
    }

    public function test_get_manual_payment_note_retrieves_note_from_event(): void
    {
        $milestone = PitchMilestone::create([
            'pitch_id' => $this->pitch->id,
            'name' => 'Test Milestone',
            'amount' => 2000,
            'payment_status' => null,
            'sort_order' => 1,
        ]);

        $testNote = 'Payment received via check #12345';

        // Mark as paid with note
        Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->set('selectedMilestoneId', $milestone->id)
            ->set('manualPaymentNote', $testNote)
            ->set('confirmManualPayment', true)
            ->call('markMilestoneAsPaidManually');

        $milestone->refresh();

        // Test that getManualPaymentNote retrieves the correct note
        $component = Livewire::test(ProjectBillingTracker::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => $this->getWorkflowColors(),
        ]);

        $retrievedNote = $component->instance()->getManualPaymentNote($milestone);
        $this->assertEquals($testNote, $retrievedNote);
    }
}
