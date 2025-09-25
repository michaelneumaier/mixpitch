<?php

namespace Tests\Feature;

use App\Livewire\Project\Component\ClientSubmitSection;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientSubmitSectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Create client management project
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.com',
        ]);

        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => Pitch::STATUS_IN_PROGRESS,
        ]);

        $this->actingAs($this->user);
    }

    public function test_component_renders_submit_section_when_in_progress()
    {
        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        Livewire::test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->assertSee('Ready to Submit')
            ->assertSee('No deliverables uploaded')
            ->assertDontSee('Submission Under Review');
    }

    public function test_component_renders_recall_section_when_ready_for_review()
    {
        $this->pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        Livewire::test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->assertSee('Submission Under Review')
            ->assertSee('Awaiting Client Review')
            ->assertSee('Recall Submission')
            ->assertDontSee('Ready to Submit for Review?');
    }

    public function test_watermarking_toggle_works()
    {
        // Add a file to the pitch so watermarking section shows
        PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test.mp3',
        ]);

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        Livewire::test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ])
            ->assertSee('Audio Protection')
            ->assertSet('watermarkingEnabled', false)
            ->set('watermarkingEnabled', true)
            ->assertSet('watermarkingEnabled', true);
    }

    public function test_recall_submission_ui_displays_correctly()
    {
        $this->pitch->update(['status' => Pitch::STATUS_READY_FOR_REVIEW]);

        // First verify the policy works correctly
        $this->assertTrue($this->user->can('recallSubmission', $this->pitch));

        $workflowColors = [
            'icon' => 'text-purple-600',
            'text_primary' => 'text-purple-900',
            'text_muted' => 'text-purple-600',
        ];

        // Test that the UI displays correctly
        $component = Livewire::actingAs($this->user)->test(ClientSubmitSection::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'workflowColors' => $workflowColors,
        ]);

        $component->assertSee('Recall Submission')
            ->assertSee('Awaiting Client Review')
            ->assertSee('Need to make changes?');
    }
}
