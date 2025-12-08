<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Project\Component\OverviewCard;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OverviewCardTest extends TestCase
{
    use RefreshDatabase;

    protected function getWorkflowColors(): array
    {
        return [
            'bg' => '!bg-purple-50 dark:!bg-purple-950',
            'border' => 'border-purple-200 dark:border-purple-800',
            'text_primary' => 'text-purple-900 dark:text-purple-100',
            'text_secondary' => 'text-purple-700 dark:text-purple-300',
            'text_muted' => 'text-purple-600 dark:text-purple-400',
            'accent_bg' => 'bg-purple-100 dark:bg-purple-900',
            'accent_border' => 'border-purple-200 dark:border-purple-800',
            'icon' => 'text-purple-600 dark:text-purple-400',
            'accent' => 'rgb(147 51 234)',
        ];
    }

    protected function createClientManagementProject(): array
    {
        $user = User::factory()->create();
        $project = Project::factory()
            ->configureWorkflow(Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT, [
                'client_email' => 'client@example.com',
                'client_name' => 'Test Client',
            ])
            ->create([
                'user_id' => $user->id,
            ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        return [$user, $project, $pitch];
    }

    /** @test */
    public function it_renders_successfully()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertStatus(200)
            ->assertSee('Getting Started'); // IN_PROGRESS status title
    }

    /** @test */
    public function it_shows_correct_state_for_in_progress_status()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Getting Started')
            ->assertSee('Upload your audio files in the "Your Files" tab');
    }

    /** @test */
    public function it_shows_correct_state_for_ready_for_review_status()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();
        $pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Awaiting Client Review');
    }

    /** @test */
    public function it_shows_correct_state_for_completed_status()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();
        $pitch->update([
            'status' => Pitch::STATUS_COMPLETED,
            'approved_at' => now(),
        ]);

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Project Successfully Completed!');
    }

    /** @test */
    public function it_displays_project_metrics_correctly()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();

        $this->actingAs($user);

        $component = Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ]);

        $component->assertSee('Project Metrics')
            ->assertSee('Total Files')
            ->assertSee('Days Active')
            ->assertSee('Submissions')
            ->assertSee('Revisions');
    }

    /** @test */
    public function it_displays_client_engagement_info()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();

        $this->actingAs($user);

        $component = Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ]);

        $component->assertSee('Client Engagement')
            ->assertSee('Portal Status');
    }

    /** @test */
    public function it_displays_client_feedback_when_revisions_requested()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();
        $pitch->update(['status' => Pitch::STATUS_CLIENT_REVISIONS_REQUESTED]);

        // Create a revision request event with feedback
        $pitch->events()->create([
            'event_type' => \App\Models\PitchEvent::TYPE_CLIENT_REVISIONS_REQUESTED,
            'comment' => 'Please adjust the bass levels',
            'created_by' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Client Feedback')
            ->assertSee('Please adjust the bass levels');
    }

    /** @test */
    public function it_does_not_display_client_feedback_for_other_statuses()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();
        // Pitch is IN_PROGRESS (no feedback should be shown)

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertDontSee('Client Feedback');
    }

    /** @test */
    public function it_displays_recent_milestones()
    {
        [$user, $project, $pitch] = $this->createClientManagementProject();

        $this->actingAs($user);

        Livewire::test(OverviewCard::class, [
            'pitch' => $pitch,
            'project' => $project,
            'workflowColors' => $this->getWorkflowColors(),
        ])
            ->assertSee('Recent Milestones');
    }
}
