<?php

namespace Tests\Feature;

use App\Livewire\Project\ManageClientProject;
use App\Models\FileComment;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FileCommentsSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Pitch $pitch;
    protected PitchFile $pitchFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
        ]);

        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED,
        ]);

        $this->pitchFile = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-track.mp3',
        ]);

        $this->actingAs($this->user);
    }

    /** @test */
    public function file_comments_summary_shows_when_comments_exist()
    {
        // Create some file comments
        FileComment::create([
            'commentable_type' => \App\Models\PitchFile::class,
            'commentable_id' => $this->pitchFile->id,
            'comment' => 'The drums need more punch in the chorus',
            'is_client_comment' => true,
            'client_email' => 'client@example.com',
            'resolved' => false,
            'timestamp' => 60.5, // 1 minute 0.5 seconds
        ]);

        FileComment::create([
            'commentable_type' => \App\Models\PitchFile::class,
            'commentable_id' => $this->pitchFile->id,
            'comment' => 'Love the melody in the verse',
            'is_client_comment' => true,
            'client_email' => 'client@example.com',
            'resolved' => true,
            'timestamp' => 125.0, // 2 minutes 5 seconds
        ]);

        // Test the ResponseToFeedback component directly
        $component = Livewire::test(\App\Livewire\Project\Component\ResponseToFeedback::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => [],
        ]);

        $component->assertSee('File Comments Overview')
            ->assertSee('1')
            ->assertSee('unresolved')
            ->assertSee('2')
            ->assertSee('total')
            ->assertSee('need attention')
            ->assertSee('The drums need more punch in the chorus');
    }

    /** @test */
    public function file_comments_summary_not_shown_when_no_comments()
    {
        $component = Livewire::test(\App\Livewire\Project\Component\ResponseToFeedback::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => [],
        ]);

        $component->assertDontSee('File Comments Overview');
    }

    /** @test */
    public function send_feedback_response_creates_producer_comment()
    {
        $component = Livewire::test(\App\Livewire\Project\Component\ResponseToFeedback::class, [
            'pitch' => $this->pitch,
            'project' => $this->project,
            'workflowColors' => [],
        ])
            ->set('responseToFeedback', 'I have addressed all the feedback')
            ->call('sendFeedbackResponse');

        $component->assertHasNoErrors();

        $this->assertDatabaseHas('pitch_events', [
            'pitch_id' => $this->pitch->id,
            'event_type' => 'producer_comment',
            'comment' => 'I have addressed all the feedback',
            'created_by' => $this->user->id,
        ]);
    }
}