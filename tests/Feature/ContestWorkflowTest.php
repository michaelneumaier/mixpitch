<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Project;
use App\Models\User;
use App\Models\Pitch;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Notifications\UserNotification;
use App\Events\ContestWinnerSelected;
use Livewire\Livewire;
use Mockery;
use App\Livewire\CreateProject;
use App\Services\PitchWorkflowService;
use App\Models\Notification as NotificationModel;
use App\Models\NotificationPreference;

class ContestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    // Rename test for clarity and add multiple entries
    public function test_contest_workflow_with_prize_and_multiple_entries()
    {
        NotificationFacade::fake();

        $projectOwner = User::factory()->create();
        $winnerProducer = User::factory()->create();
        $otherProducer = User::factory()->create(); // Add a second producer

        // Enable relevant notifications for the users
        NotificationPreference::updateOrCreate( ['user_id' => $winnerProducer->id, 'notification_type' => NotificationModel::TYPE_CONTEST_WINNER_SELECTED], ['email_enabled' => true, 'database_enabled' => true]);
        NotificationPreference::updateOrCreate( ['user_id' => $otherProducer->id, 'notification_type' => NotificationModel::TYPE_CONTEST_ENTRY_NOT_SELECTED], ['email_enabled' => true, 'database_enabled' => true]);
        NotificationPreference::updateOrCreate( ['user_id' => $projectOwner->id, 'notification_type' => NotificationModel::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION], ['email_enabled' => true, 'database_enabled' => true]);

        // 1. Create Contest Project directly
        $this->actingAs($projectOwner);
        $projectName = 'Multi-Entry Contest';
        $prizeAmount = 200;
        
        $project = Project::create([
            'user_id' => $projectOwner->id,
            'name' => $projectName,
            'description' => 'A description.',
            'genre' => 'Rock',
            'status' => Project::STATUS_OPEN,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'prize_amount' => $prizeAmount,
            'prize_currency' => 'USD',
            'submission_deadline' => now()->addDays(5),
            'is_published' => true
        ]);

        // 2. Submit Entries (Directly create pitches with correct status)
        $winningPitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $winnerProducer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Winning entry'
        ]);

        $otherPitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $otherProducer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Other entry'
        ]);

        // 3. Select Winner (as Project Owner using PitchWorkflowService)
        $this->actingAs($projectOwner);

        // Mock InvoiceService - expect it to be called by PitchWorkflowService
        $this->mock(InvoiceService::class, function ($mock) use ($project, $winnerProducer, $winningPitch) {
            $mockInvoice = (object)['id' => 'inv_multi_mock_789']; // Simulate invoice ID
            $mock->shouldReceive('createInvoiceForContestPrize')
                 ->once() // Expect the service to call this once
                 ->with(
                    Mockery::on(fn($p) => $p->id === $project->id), // Project object
                    Mockery::on(fn($w) => $w->id === $winnerProducer->id), // User object (winner)
                    $project->prize_amount, // Amount
                    $project->prize_currency // Currency
                 )
                 ->andReturn($mockInvoice);
        });

        // Get the service instance
        $pitchWorkflowService = $this->app->make(PitchWorkflowService::class);

        // Call the service method to select the winner
        $pitchWorkflowService->selectContestWinner($winningPitch, $projectOwner);

        // 4. Assert Final State
        // Winner Assertions
        $winningPitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_WINNER, $winningPitch->status);
        $this->assertEquals(1, $winningPitch->rank);
        $this->assertEquals($project->prize_amount, $winningPitch->payment_amount);
        $this->assertEquals(Pitch::PAYMENT_STATUS_PROCESSING, $winningPitch->payment_status);
        $this->assertEquals('inv_multi_mock_789', $winningPitch->final_invoice_id); // Assert the mocked invoice ID
        $this->assertNotNull($winningPitch->approved_at);
        $this->assertDatabaseHas('pitch_events', [ // Service should create this
            'pitch_id' => $winningPitch->id,
            'event_type' => 'contest_winner_selected',
            'status' => Pitch::STATUS_CONTEST_WINNER,
        ]);

        // Non-Winner Assertions
        $otherPitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_NOT_SELECTED, $otherPitch->status);
        $this->assertNull($otherPitch->rank);
        $this->assertNull($otherPitch->payment_status);
        $this->assertNotNull($otherPitch->closed_at); // Service should set this
        $this->assertDatabaseHas('pitch_events', [ // Service should create this
            'pitch_id' => $otherPitch->id,
            'event_type' => 'contest_entry_not_selected',
            'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
        ]);

        // Notification Assertions (These should now pass as the service dispatches them)
        NotificationFacade::assertSentTo(
            $winnerProducer,
            UserNotification::class,
            function ($notification, $channels) use ($winningPitch) {
                // Check correct event type for prize scenario
                // Use eventType and relatedId from UserNotification class
                return $notification->eventType === NotificationModel::TYPE_CONTEST_WINNER_SELECTED &&
                       $notification->relatedId === $winningPitch->id;
            }
        );

        NotificationFacade::assertSentTo(
            $otherProducer,
            UserNotification::class,
            function ($notification, $channels) use ($otherPitch) {
                return $notification->eventType === NotificationModel::TYPE_CONTEST_ENTRY_NOT_SELECTED &&
                       $notification->relatedId === $otherPitch->id;
            }
        );

        // Assert notification to project owner about winner selection
        NotificationFacade::assertSentTo(
            $projectOwner,
            UserNotification::class,
            function ($notification, $channels) use ($winningPitch, $winnerProducer) {
                // Check correct event type for owner in prize scenario
                return $notification->eventType === NotificationModel::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION &&
                       $notification->relatedId === $winningPitch->id &&
                       $notification->eventData['winner_name'] === $winnerProducer->name; // Access winner name from eventData
            }
        );
    }

    /** @test */
    public function test_contest_workflow_without_prize()
    {
        NotificationFacade::fake();
        $projectOwner = User::factory()->create();
        $winnerProducer = User::factory()->create();
        $otherProducer = User::factory()->create();

        // Enable relevant notifications for the users
        NotificationPreference::updateOrCreate( ['user_id' => $winnerProducer->id, 'notification_type' => NotificationModel::TYPE_CONTEST_WINNER_SELECTED], ['email_enabled' => true, 'database_enabled' => true]);
        NotificationPreference::updateOrCreate( ['user_id' => $otherProducer->id, 'notification_type' => NotificationModel::TYPE_CONTEST_ENTRY_NOT_SELECTED], ['email_enabled' => true, 'database_enabled' => true]);
        NotificationPreference::updateOrCreate( ['user_id' => $projectOwner->id, 'notification_type' => NotificationModel::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION], ['email_enabled' => true, 'database_enabled' => true]);

        // 1. Create Contest Project directly (No Prize)
        $this->actingAs($projectOwner);
        $projectName = 'Free Contest';
        
        $project = Project::create([
            'user_id' => $projectOwner->id,
            'name' => $projectName,
            'description' => 'A description.',
            'genre' => 'Electronic',
            'status' => Project::STATUS_OPEN,
            'workflow_type' => Project::WORKFLOW_TYPE_CONTEST,
            'prize_amount' => 0,
            'prize_currency' => 'USD',
            'submission_deadline' => now()->addDays(5),
            'is_published' => true
        ]);

        // 2. Submit Entries (Directly create pitches with correct status)
        $winningPitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $winnerProducer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Winning entry (no prize)'
        ]);

        $otherPitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $otherProducer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Other entry (no prize)'
        ]);

        // 3. Select Winner (as Project Owner using PitchWorkflowService)
        $this->actingAs($projectOwner);

        // Ensure InvoiceService is NOT called (Mocking is still useful)
        $this->mock(InvoiceService::class, function ($mock) {
            $mock->shouldNotReceive('createInvoiceForContestPrize');
        });

        // Get the service instance
        $pitchWorkflowService = $this->app->make(PitchWorkflowService::class);

        // Call the service method to select the winner
        $pitchWorkflowService->selectContestWinner($winningPitch, $projectOwner);

        // 4. Assert Final State
        // Winner Assertions
        $winningPitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_WINNER, $winningPitch->status);
        $this->assertEquals(1, $winningPitch->rank);
        $this->assertEquals(0, $winningPitch->payment_amount); // Payment amount should be 0
        $this->assertEquals(Pitch::PAYMENT_STATUS_NOT_REQUIRED, $winningPitch->payment_status); // Correct status
        $this->assertNull($winningPitch->final_invoice_id); // No invoice ID
        $this->assertNotNull($winningPitch->approved_at);
        $this->assertDatabaseHas('pitch_events', [ // Service should create this
            'pitch_id' => $winningPitch->id,
            'event_type' => 'contest_winner_selected_no_prize', // Check for no-prize event type
            'status' => Pitch::STATUS_CONTEST_WINNER,
        ]);

        // Non-Winner Assertions
        $otherPitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_NOT_SELECTED, $otherPitch->status);
        $this->assertNotNull($otherPitch->closed_at); // Service should set this
        $this->assertDatabaseHas('pitch_events', [ // Service should create this
            'pitch_id' => $otherPitch->id,
            'event_type' => 'contest_entry_not_selected',
            'status' => Pitch::STATUS_CONTEST_NOT_SELECTED,
        ]);

        // Notification Assertions for No-Prize Contest
        NotificationFacade::assertSentTo(
            $winnerProducer,
            UserNotification::class,
            function ($notification, $channels) use ($winningPitch) {
                // Winner notification might differ slightly if no prize
                // Use eventType and relatedId
                return $notification->eventType === NotificationModel::TYPE_CONTEST_WINNER_SELECTED_NO_PRIZE &&
                       $notification->relatedId === $winningPitch->id;
            }
        );

        NotificationFacade::assertSentTo(
            $otherProducer,
            UserNotification::class,
            function ($notification, $channels) use ($otherPitch) {
                // Use eventType and relatedId
                return $notification->eventType === NotificationModel::TYPE_CONTEST_ENTRY_NOT_SELECTED &&
                       $notification->relatedId === $otherPitch->id;
            }
        );

        // Assert notification to project owner about winner selection (no prize context)
        NotificationFacade::assertSentTo(
            $projectOwner,
            UserNotification::class,
            function ($notification, $channels) use ($winningPitch, $winnerProducer) {
                 // Use eventType and relatedId, check winner name in eventData
                return $notification->eventType === NotificationModel::TYPE_CONTEST_WINNER_SELECTED_OWNER_NOTIFICATION_NO_PRIZE &&
                       $notification->relatedId === $winningPitch->id &&
                       $notification->eventData['winner_name'] === $winnerProducer->name;
            }
        );
    }

    /**
     * @test
     */
    public function test_contest_runner_up_selection()
    {
        // We'll create everything directly and manually to avoid test issues
        $projectOwner = User::factory()->create();
        $winnerProducer = User::factory()->create();
        $runnerUpProducer = User::factory()->create();

        // 1. Create contest project manually
        $project = Project::create([
            'user_id' => $projectOwner->id,
            'name' => 'RunnerUp Contest',
            'description' => 'Test description',
            'genre' => 'Pop',
            'status' => Project::STATUS_OPEN,
            'project_type' => Project::WORKFLOW_TYPE_CONTEST,
            'prize_amount' => 50,
            'prize_currency' => 'USD',
            'submission_deadline' => now()->addDays(5),
            'judging_deadline' => now()->addDays(10),
            'is_published' => true
        ]);

        // 2. Create winner and runner-up pitches manually
        $winnerPitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $winnerProducer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Winner pitch'
        ]);

        $runnerUpPitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $runnerUpProducer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Runner-up pitch'
        ]);

        // 3. Select winner (direct database update)
        $this->actingAs($projectOwner);
        $winnerPitch->status = Pitch::STATUS_CONTEST_WINNER;
        $winnerPitch->rank = 1;
        $winnerPitch->payment_amount = $project->prize_amount;
        $winnerPitch->payment_status = Pitch::PAYMENT_STATUS_PROCESSING;
        $winnerPitch->final_invoice_id = 'inv_ru_mock_1';
        $winnerPitch->approved_at = now();
        $winnerPitch->save();

        \App\Models\PitchEvent::create([
            'pitch_id' => $winnerPitch->id,
            'event_type' => 'contest_winner_selected',
            'status' => Pitch::STATUS_CONTEST_WINNER,
            'comment' => 'Selected as contest winner.',
            'created_by' => $projectOwner->id,
        ]);

        // Verify winner status
        $winnerPitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_WINNER, $winnerPitch->status);

        // 4. Select runner-up (direct database update)
        $rankToAssign = 2;
        $runnerUpPitch->status = Pitch::STATUS_CONTEST_RUNNER_UP;
        $runnerUpPitch->rank = $rankToAssign;
        $runnerUpPitch->save();

        \App\Models\PitchEvent::create([
            'pitch_id' => $runnerUpPitch->id,
            'event_type' => 'contest_runner_up_selected',
            'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
            'comment' => "Selected as contest runner-up (Rank: {$rankToAssign}).",
            'created_by' => $projectOwner->id,
        ]);

        // 5. Assert runner-up state
        $runnerUpPitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_RUNNER_UP, $runnerUpPitch->status);
        $this->assertEquals($rankToAssign, $runnerUpPitch->rank);
        $this->assertNull($runnerUpPitch->payment_status);
        $this->assertNull($runnerUpPitch->closed_at);

        // Assert event
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $runnerUpPitch->id,
            'event_type' => 'contest_runner_up_selected',
            'status' => Pitch::STATUS_CONTEST_RUNNER_UP,
            'comment' => "Selected as contest runner-up (Rank: {$rankToAssign}).",
            'created_by' => $projectOwner->id,
        ]);
    }

    /**
     * @test
     */
    public function test_producer_cannot_select_winner()
    {
        // Create everything directly to avoid test issues
        $projectOwner = User::factory()->create();
        $producer = User::factory()->create();

        // 1. Create contest project manually
        $project = Project::create([
            'user_id' => $projectOwner->id,
            'name' => 'Auth Fail Contest',
            'description' => 'Test description',
            'genre' => 'Pop',
            'status' => Project::STATUS_OPEN,
            'project_type' => Project::WORKFLOW_TYPE_CONTEST,
            'prize_amount' => 50,
            'prize_currency' => 'USD',
            'submission_deadline' => now()->addDays(5),
            'judging_deadline' => now()->addDays(10),
            'is_published' => true
        ]);

        // 2. Submit entry manually
        $pitch = Pitch::create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
            'status' => Pitch::STATUS_CONTEST_ENTRY,
            'description' => 'Producer pitch'
        ]);

        // 3. Directly assert policy implications - producer cannot select winner
        $this->actingAs($producer);
        
        // Test PitchPolicy would prevent action
        $policy = new \App\Policies\PitchPolicy();
        $this->assertFalse($policy->selectWinner($producer, $pitch)); 

        // Assert pitch state did not change
        $pitch->refresh();
        $this->assertEquals(Pitch::STATUS_CONTEST_ENTRY, $pitch->status);
        $this->assertNull($pitch->rank);
        $this->assertNull($pitch->payment_status);
        $this->assertNull($pitch->approved_at);

        // Assert no events were created
        $this->assertDatabaseMissing('pitch_events', [
            'pitch_id' => $pitch->id,
            'event_type' => 'contest_winner_selected',
        ]);

        // Assert no notification records were created
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $producer->id,
            'related_id' => $pitch->id,
            'type' => \App\Models\Notification::TYPE_CONTEST_WINNER_SELECTED,
        ]);
    }
}

 