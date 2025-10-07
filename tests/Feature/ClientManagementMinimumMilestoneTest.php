<?php

namespace Tests\Feature;

use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientManagementMinimumMilestoneTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->producer = User::factory()->create();
        $this->actingAs($this->producer);
    }

    /**
     * Test that a milestone is automatically created when creating a client management project with a budget.
     */
    public function test_creates_milestone_when_creating_project_with_budget(): void
    {
        // Create a client management project with a budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'payment_amount' => 5000,
        ]);

        // Get the automatically created pitch
        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch, 'Pitch should be auto-created for client management project');

        // Assert payment amount was set
        $this->assertEquals(5000, $pitch->payment_amount);

        // Assert a milestone was automatically created
        $milestones = $pitch->milestones;
        $this->assertCount(1, $milestones, 'Exactly one milestone should be created');

        $milestone = $milestones->first();
        $this->assertEquals('Project Payment', $milestone->name);
        $this->assertEquals(5000, $milestone->amount);
        $this->assertEquals(1, $milestone->sort_order);
        $this->assertEquals('pending', $milestone->status);
        $this->assertNull($milestone->payment_status);
    }

    /**
     * Test that no milestone is created when budget is $0.
     */
    public function test_does_not_create_milestone_when_budget_is_zero(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'payment_amount' => 0,
        ]);

        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);
        $this->assertEquals(0, $pitch->payment_amount);

        // Assert no milestones were created
        $milestones = $pitch->milestones;
        $this->assertCount(0, $milestones, 'No milestones should be created when budget is $0');
    }

    /**
     * Test that no milestone is created when payment_amount is null.
     */
    public function test_does_not_create_milestone_when_budget_is_null(): void
    {
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'payment_amount' => null,
        ]);

        $pitch = $project->pitches()->first();
        $this->assertNotNull($pitch);

        // Assert no milestones were created
        $milestones = $pitch->milestones;
        $this->assertCount(0, $milestones, 'No milestones should be created when payment_amount is null');
    }

    /**
     * Test that the single milestone amount updates when budget changes.
     */
    public function test_updates_single_milestone_when_budget_changes(): void
    {
        // Create project with initial budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 5000,
        ]);

        $pitch = $project->pitches()->first();
        $milestone = $pitch->milestones()->first();

        $this->assertEquals(5000, $milestone->amount);

        // Update the budget via the MilestoneManager component
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        $component->call('toggleBudgetEdit')
            ->set('editableBudget', 7500)
            ->call('saveBudget');

        // Refresh and check milestone was updated
        $milestone->refresh();
        $this->assertEquals(7500, $milestone->amount, 'Milestone amount should update when budget changes');
    }

    /**
     * Test that a milestone is created when budget changes from $0 to positive amount.
     */
    public function test_creates_milestone_when_budget_changes_from_zero_to_positive(): void
    {
        // Create project with $0 budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 0,
        ]);

        $pitch = $project->pitches()->first();
        $this->assertCount(0, $pitch->milestones, 'Should start with no milestones');

        // Update budget to positive amount
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        $component->call('toggleBudgetEdit')
            ->set('editableBudget', 3000)
            ->call('saveBudget');

        // Refresh and check milestone was created
        $pitch->refresh();
        $this->assertCount(1, $pitch->milestones, 'Milestone should be created when budget goes from $0 to positive');
        $this->assertEquals(3000, $pitch->milestones->first()->amount);
    }

    /**
     * Test that the milestone is deleted when budget changes to $0.
     */
    public function test_deletes_milestone_when_budget_changes_to_zero(): void
    {
        // Create project with budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 5000,
        ]);

        $pitch = $project->pitches()->first();
        $this->assertCount(1, $pitch->milestones, 'Should start with one milestone');

        // Update budget to $0
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        $component->call('toggleBudgetEdit')
            ->set('editableBudget', 0)
            ->call('saveBudget');

        // Refresh and check milestone was deleted
        $pitch->refresh();
        $this->assertCount(0, $pitch->milestones, 'Milestone should be deleted when budget changes to $0');
    }

    /**
     * Test that you cannot delete the last milestone when budget is set.
     */
    public function test_cannot_delete_last_milestone_when_budget_is_set(): void
    {
        // Create project with budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 5000,
        ]);

        $pitch = $project->pitches()->first();
        $milestone = $pitch->milestones()->first();

        // Try to delete the milestone
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        $component->call('deleteMilestone', $milestone->id);

        // Assert milestone still exists
        $pitch->refresh();
        $this->assertCount(1, $pitch->milestones, 'Milestone should not be deleted');
    }

    /**
     * Test that revision milestones are not created when additional_revision_price is $0.
     */
    public function test_does_not_create_revision_milestone_when_additional_cost_is_zero(): void
    {
        // Create project with budget but $0 additional revision cost
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 5000,
        ]);

        $pitch = $project->pitches()->first();
        $pitch->update([
            'included_revisions' => 2,
            'additional_revision_price' => 0, // $0 additional revision cost
            'revisions_used' => 2, // Already used included revisions
        ]);

        // Request a revision (which would normally create a paid milestone)
        $pitchWorkflowService = app(\App\Services\PitchWorkflowService::class);

        // Update pitch to status that allows client revisions
        $pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        $pitchWorkflowService->clientRequestRevisions($pitch, 'Please make changes', $project->client_email);

        // Assert no new milestone was created
        $pitch->refresh();
        $this->assertCount(1, $pitch->milestones, 'No revision milestone should be created when cost is $0');
    }

    /**
     * Test that multiple milestones can be managed independently (not synced with budget).
     */
    public function test_allows_multiple_milestones_to_be_managed_independently(): void
    {
        // Create project with budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 10000,
        ]);

        $pitch = $project->pitches()->first();

        // Add a second milestone manually
        $pitch->milestones()->create([
            'name' => 'Milestone 2',
            'description' => 'Second milestone',
            'amount' => 5000,
            'sort_order' => 2,
            'status' => 'pending',
            'payment_status' => null,
        ]);

        $this->assertCount(2, $pitch->milestones, 'Should have 2 milestones');

        // Update budget - should NOT sync milestone amounts
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        $component->call('toggleBudgetEdit')
            ->set('editableBudget', 15000)
            ->call('saveBudget');

        // Refresh and check milestones were NOT updated
        $pitch->refresh();
        $this->assertCount(2, $pitch->milestones, 'Should still have 2 milestones');
        $this->assertEquals(10000, $pitch->milestones->first()->amount, 'First milestone amount should not change');
        $this->assertEquals(5000, $pitch->milestones->skip(1)->first()->amount, 'Second milestone amount should not change');
    }

    /**
     * Test that cannot change budget to $0 when milestone is already paid.
     */
    public function test_cannot_change_budget_to_zero_when_milestone_is_paid(): void
    {
        // Create project with budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 5000,
        ]);

        $pitch = $project->pitches()->first();
        $milestone = $pitch->milestones()->first();

        // Mark milestone as paid
        $milestone->update([
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
            'payment_completed_at' => now(),
        ]);

        // Try to change budget to $0
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        $component->call('toggleBudgetEdit')
            ->set('editableBudget', 0)
            ->call('saveBudget');

        // Assert milestone still exists and error was shown
        $pitch->refresh();
        $this->assertCount(1, $pitch->milestones, 'Paid milestone should not be deleted');
        $this->assertEquals(5000, $pitch->milestones->first()->amount, 'Milestone amount should not change');
    }

    /**
     * Test that unpaid revision milestones are cleaned up when price changes to $0.
     */
    public function test_deletes_unpaid_revision_milestones_when_price_changes_to_zero(): void
    {
        // Create project with budget
        $project = Project::factory()->create([
            'user_id' => $this->producer->id,
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'payment_amount' => 5000,
        ]);

        $pitch = $project->pitches()->first();
        $pitch->update([
            'included_revisions' => 2,
            'additional_revision_price' => 500,
        ]);

        // Manually create an unpaid revision milestone
        $revisionMilestone = $pitch->milestones()->create([
            'name' => 'Revision Round 1',
            'description' => 'Additional revision',
            'amount' => 500,
            'sort_order' => 2,
            'status' => 'pending',
            'payment_status' => null,
            'is_revision_milestone' => true,
            'revision_round_number' => 1,
        ]);

        $this->assertCount(2, $pitch->milestones, 'Should have 2 milestones');

        // Change additional revision price to $0
        $component = \Livewire\Livewire::test(\App\Livewire\Project\Component\MilestoneManager::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => [
                'icon' => 'text-purple-600',
                'text_primary' => 'text-purple-900',
                'text_secondary' => 'text-purple-700',
                'text_muted' => 'text-gray-600',
                'accent_bg' => 'bg-purple-50',
                'accent_text' => 'text-purple-700',
            ],
        ]);

        // The component needs to be properly initialized first
        $component->call('toggleRevisionSettings'); // This loads current values

        // Verify it loaded the correct old value
        $component->assertSet('editableAdditionalRevisionPrice', 500);

        // Now change to 0 and save
        $component->set('editableAdditionalRevisionPrice', 0)
            ->call('saveRevisionSettings');

        // Assert revision milestone was deleted
        $pitch->refresh();
        $this->assertCount(1, $pitch->milestones, 'Unpaid revision milestone should be deleted');
        $this->assertFalse($pitch->milestones->contains('id', $revisionMilestone->id), 'Revision milestone should not exist');
    }
}
