<?php

namespace Tests\Feature;

use App\Models\FeedbackTemplate;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchFileComment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StructuredFeedbackFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $producer;

    protected Project $project;

    protected Pitch $pitch;

    protected PitchFile $pitchFile;

    protected FeedbackTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->producer = User::factory()->producer()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
        ]);
        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
        ]);
        $this->pitchFile = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'created_at' => now(),
        ]);
        $this->template = FeedbackTemplate::factory()->create([
            'user_id' => $this->producer->id,
            'usage_count' => 0, // Start with 0 for predictable testing
            'questions' => [
                [
                    'id' => 'overall_rating',
                    'type' => FeedbackTemplate::TYPE_RATING,
                    'label' => 'Overall Rating',
                    'required' => true,
                    'max_rating' => 5,
                ],
                [
                    'id' => 'comments',
                    'type' => FeedbackTemplate::TYPE_TEXTAREA,
                    'label' => 'Comments',
                    'required' => false,
                    'rows' => 3,
                ],
            ],
        ]);
    }

    public function test_component_mounts_with_authenticated_user()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
        ]);

        $component->assertSet('isClientUser', false)
            ->assertSet('pitch', $this->pitch)
            ->assertSet('pitchFile', $this->pitchFile);
    }

    public function test_component_mounts_with_client_email()
    {
        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
            'clientEmail' => 'client@example.com',
        ]);

        $component->assertSet('isClientUser', true)
            ->assertSet('clientEmail', 'client@example.com');
    }

    public function test_authenticated_user_sees_available_templates()
    {
        $this->actingAs($this->producer);

        $defaultTemplate = FeedbackTemplate::factory()->default()->create();

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $availableTemplates = $component->get('availableTemplates');
        $this->assertCount(2, $availableTemplates); // User's template + default template
        $this->assertTrue(collect($availableTemplates)->pluck('id')->contains($this->template->id));
        $this->assertTrue(collect($availableTemplates)->pluck('id')->contains($defaultTemplate->id));
    }

    public function test_client_user_sees_only_default_templates()
    {
        $defaultTemplate = FeedbackTemplate::factory()->default()->create();

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'clientEmail' => 'client@example.com',
        ]);

        $availableTemplates = $component->get('availableTemplates');
        $this->assertCount(1, $availableTemplates); // Only default template
        $this->assertTrue(collect($availableTemplates)->pluck('id')->contains($defaultTemplate->id));
        $this->assertFalse(collect($availableTemplates)->pluck('id')->contains($this->template->id));
    }

    public function test_user_can_select_template()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->assertSet('selectedTemplateId', $this->template->id)
            ->assertSet('showTemplateSelector', false);

        // Check that responses are initialized
        $responses = $component->get('responses');
        $this->assertArrayHasKey('overall_rating', $responses);
        $this->assertArrayHasKey('comments', $responses);
    }

    public function test_user_can_go_back_to_template_selector()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->call('backToTemplateSelector')
            ->assertSet('showTemplateSelector', true)
            ->assertSet('template', null)
            ->assertSet('selectedTemplateId', null);
    }

    public function test_user_can_submit_feedback_with_valid_responses()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', 4)
            ->set('responses.comments', 'Great work on this track!')
            ->call('submitFeedback');

        // Check that feedback was created
        $this->assertDatabaseHas('pitch_file_comments', [
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->producer->id,
            'is_client_comment' => false,
        ]);

        // Check template usage was incremented
        $this->assertEquals(1, $this->template->fresh()->usage_count);
    }

    public function test_client_can_submit_feedback()
    {
        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
            'clientEmail' => 'client@example.com',
        ]);

        $defaultTemplate = FeedbackTemplate::factory()->default()->create([
            'questions' => [
                [
                    'id' => 'rating',
                    'type' => FeedbackTemplate::TYPE_RATING,
                    'label' => 'Rating',
                    'required' => true,
                    'max_rating' => 5,
                ],
            ],
        ]);

        $component->call('selectTemplate', $defaultTemplate->id)
            ->set('responses.rating', 5)
            ->call('submitFeedback');

        // Check that client feedback was created
        $this->assertDatabaseHas('pitch_file_comments', [
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'is_client_comment' => true,
            'client_email' => 'client@example.com',
        ]);
    }

    public function test_validation_errors_for_required_fields()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', null) // Required field
            ->call('submitFeedback');

        $validationErrors = $component->get('validationErrors');
        $this->assertArrayHasKey('overall_rating', $validationErrors);
        $this->assertEquals('This field is required.', $validationErrors['overall_rating']);
    }

    public function test_validation_for_rating_values()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', 10) // Max is 5
            ->call('submitFeedback');

        $validationErrors = $component->get('validationErrors');
        $this->assertArrayHasKey('overall_rating', $validationErrors);
        $this->assertStringContainsString('Rating must be between 1 and 5', $validationErrors['overall_rating']);
    }

    public function test_validation_for_select_options()
    {
        $template = FeedbackTemplate::factory()->create([
            'user_id' => $this->producer->id,
            'questions' => [
                [
                    'id' => 'quality',
                    'type' => FeedbackTemplate::TYPE_SELECT,
                    'label' => 'Quality',
                    'required' => true,
                    'options' => ['Poor', 'Good', 'Excellent'],
                ],
            ],
        ]);

        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $template->id)
            ->set('responses.quality', 'Invalid Option')
            ->call('submitFeedback');

        $validationErrors = $component->get('validationErrors');
        $this->assertArrayHasKey('quality', $validationErrors);
        $this->assertEquals('Invalid option selected.', $validationErrors['quality']);
    }

    public function test_validation_for_range_values()
    {
        $template = FeedbackTemplate::factory()->create([
            'user_id' => $this->producer->id,
            'questions' => [
                [
                    'id' => 'loudness',
                    'type' => FeedbackTemplate::TYPE_RANGE,
                    'label' => 'Loudness',
                    'required' => true,
                    'min' => 0,
                    'max' => 100,
                ],
            ],
        ]);

        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $template->id)
            ->set('responses.loudness', 150) // Max is 100
            ->call('submitFeedback');

        $validationErrors = $component->get('validationErrors');
        $this->assertArrayHasKey('loudness', $validationErrors);
        $this->assertStringContainsString('Value must be between 0 and 100', $validationErrors['loudness']);
    }

    public function test_checkbox_validation()
    {
        $template = FeedbackTemplate::factory()->create([
            'user_id' => $this->producer->id,
            'questions' => [
                [
                    'id' => 'aspects',
                    'type' => FeedbackTemplate::TYPE_CHECKBOX,
                    'label' => 'Good Aspects',
                    'required' => false,
                    'options' => ['Vocals', 'Mixing', 'Arrangement'],
                ],
            ],
        ]);

        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $template->id)
            ->set('responses.aspects', ['Vocals', 'Invalid Option'])
            ->call('submitFeedback');

        $validationErrors = $component->get('validationErrors');
        $this->assertArrayHasKey('aspects', $validationErrors);
        $this->assertEquals('Invalid option selected.', $validationErrors['aspects']);
    }

    public function test_feedback_content_formatting()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', 4)
            ->set('responses.comments', 'Great work!')
            ->call('submitFeedback');

        $comment = PitchFileComment::where('pitch_file_id', $this->pitchFile->id)->first();

        $this->assertStringContainsString('**Structured Feedback -', $comment->comment);
        $this->assertStringContainsString('**Overall Rating**', $comment->comment);
        $this->assertStringContainsString('★★★★☆ (4/5)', $comment->comment);
        $this->assertStringContainsString('**Comments**', $comment->comment);
        $this->assertStringContainsString('Great work!', $comment->comment);
    }

    public function test_default_values_are_set_correctly()
    {
        $template = FeedbackTemplate::factory()->create([
            'user_id' => $this->producer->id,
            'questions' => [
                [
                    'id' => 'volume',
                    'type' => FeedbackTemplate::TYPE_RANGE,
                    'label' => 'Volume',
                    'min' => 10,
                    'max' => 90,
                ],
                [
                    'id' => 'aspects',
                    'type' => FeedbackTemplate::TYPE_CHECKBOX,
                    'label' => 'Aspects',
                    'options' => ['Option1', 'Option2'],
                ],
            ],
        ]);

        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
        ]);

        $component->call('selectTemplate', $template->id);

        $responses = $component->get('responses');
        $this->assertEquals(10, $responses['volume']); // Min value for range
        $this->assertEquals([], $responses['aspects']); // Empty array for checkbox
    }

    public function test_component_handles_pitch_without_specific_file()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile, // Provide pitch file to ensure success
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', 3)
            ->call('submitFeedback');

        // Should create feedback successfully
        $this->assertDatabaseHas('pitch_file_comments', [
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->producer->id,
            'is_client_comment' => false,
        ]);
    }

    public function test_component_emits_success_event()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', 5)
            ->call('submitFeedback')
            ->assertDispatched('feedbackSubmitted');
    }

    public function test_form_resets_after_successful_submission()
    {
        $this->actingAs($this->producer);

        $component = Livewire::test('structured-feedback-form', [
            'pitch' => $this->pitch,
            'pitchFile' => $this->pitchFile,
        ]);

        $component->call('selectTemplate', $this->template->id)
            ->set('responses.overall_rating', 5)
            ->call('submitFeedback')
            ->assertSet('showTemplateSelector', true)
            ->assertSet('template', null)
            ->assertSet('selectedTemplateId', null)
            ->assertSet('responses', []);
    }
}
