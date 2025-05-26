<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Models\PitchEvent;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ClientManagementCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user
        $this->user = User::factory()->create();
        
        // Create a client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);
        
        // Create associated pitch
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);
    }

    /** @test */
    public function producer_can_add_comment_to_client_project()
    {
        Mail::fake();
        
        $this->actingAs($this->user);
        
        Livewire::test(\App\Livewire\Project\ManageClientProject::class, ['project' => $this->project])
            ->set('newComment', 'This is a test message for the client')
            ->call('addProducerComment')
            ->assertHasNoErrors()
            ->assertSet('newComment', ''); // Should be cleared after successful submission
        
        // Assert event was created
        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => 'producer_comment',
            'comment' => 'This is a test message for the client',
            'created_by' => $this->user->id,
        ]);
        
        // Assert email was sent
        Mail::assertSent(\App\Mail\ClientProducerComment::class);
    }

    /** @test */
    public function conversation_items_include_producer_and_client_comments()
    {
        $this->actingAs($this->user);
        
        // Create some events (client comment first, then producer comment)
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'client_comment',
            'comment' => 'Client message',
            'status' => $this->pitch->status,
            'metadata' => ['client_email' => 'client@example.com'],
            'created_at' => now()->subMinutes(5), // Older
        ]);
        
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'producer_comment',
            'comment' => 'Producer message',
            'status' => $this->pitch->status,
            'created_by' => $this->user->id,
            'created_at' => now(), // More recent
        ]);
        
        $component = Livewire::test(\App\Livewire\Project\ManageClientProject::class, ['project' => $this->project]);
        
        $conversationItems = $component->instance()->conversationItems;
        
        $this->assertCount(2, $conversationItems);
        $this->assertEquals('client_message', $conversationItems[0]['type']);
        $this->assertEquals('producer_message', $conversationItems[1]['type']);
    }

    /** @test */
    public function activity_dashboard_shows_correct_message_count()
    {
        $this->actingAs($this->user);
        
        // Create some events
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'producer_comment',
            'comment' => 'Producer message 1',
            'status' => $this->pitch->status,
            'created_by' => $this->user->id,
        ]);
        
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'client_comment',
            'comment' => 'Client message 1',
            'status' => $this->pitch->status,
        ]);
        
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'status_change',
            'comment' => 'Status changed',
            'status' => $this->pitch->status,
            'created_by' => $this->user->id,
        ]);
        
        Livewire::test(\App\Livewire\Project\ManageClientProject::class, ['project' => $this->project])
            ->assertSee('Messages')
            ->assertSee('2'); // Should show 2 in the messages count
    }

    /** @test */
    public function feedback_panel_shows_when_revisions_requested()
    {
        $this->actingAs($this->user);
        
        // Update pitch status to revisions requested
        $this->pitch->update(['status' => Pitch::STATUS_REVISIONS_REQUESTED]);
        
        // Create revision request event
        PitchEvent::create([
            'pitch_id' => $this->pitch->id,
            'event_type' => 'client_revisions_requested',
            'comment' => 'Please make these changes...',
            'status' => $this->pitch->status,
        ]);
        
        Livewire::test(\App\Livewire\Project\ManageClientProject::class, ['project' => $this->project])
            ->assertSee('Client Requested Revisions')
            ->assertSee('Please make these changes...');
    }

    /** @test */
    public function producer_comment_validation_works()
    {
        $this->actingAs($this->user);
        
        Livewire::test(\App\Livewire\Project\ManageClientProject::class, ['project' => $this->project])
            ->set('newComment', '') // Empty comment
            ->call('addProducerComment')
            ->assertHasErrors(['newComment']);
        
        // Test max length
        Livewire::test(\App\Livewire\Project\ManageClientProject::class, ['project' => $this->project])
            ->set('newComment', str_repeat('a', 2001)) // Too long
            ->call('addProducerComment')
            ->assertHasErrors(['newComment']);
    }
} 