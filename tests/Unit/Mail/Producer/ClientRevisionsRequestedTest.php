<?php

namespace Tests\Unit\Mail\Producer;

use App\Mail\Producer\ClientRevisionsRequested;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientRevisionsRequestedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_with_correct_subject_line()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'title' => 'Epic Track',
            'client_name' => 'John Client',
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $feedback = 'Please add more bass';

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            $feedback
        );

        $mailable->assertHasSubject('[Action Required] John Client Requested Revisions - Epic Track');
    }

    /** @test */
    public function it_includes_client_feedback_prominently()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_name' => 'Sarah',
            'client_email' => 'sarah@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $feedback = 'The tempo needs to be faster and add more energy to the drop.';

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            $feedback
        );

        $mailable->assertSeeInHtml($feedback);
        $mailable->assertSeeInHtml('Client Feedback');
    }

    /** @test */
    public function it_uses_correct_template()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            'test feedback'
        );

        $this->assertEquals(
            'emails.producer.client_revisions_requested',
            $mailable->content()->markdown
        );
    }

    /** @test */
    public function it_includes_action_steps_for_producer()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            'feedback'
        );

        $mailable->assertSeeInText('What you need to do:');
        $mailable->assertSeeInText('Review the client\'s feedback');
        $mailable->assertSeeInText('Upload the updated files');
    }

    /** @test */
    public function it_includes_project_link_button()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            'test feedback'
        );

        $projectUrl = route('projects.manage-client', $project);
        $mailable->assertSeeInHtml($projectUrl, false);
        $mailable->assertSeeInHtml('View Project & Respond');
    }

    /** @test */
    public function it_handles_null_client_name_with_fallback()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_name' => null,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            'feedback'
        );

        // Should use fallback "Client" in subject (includes project title)
        $mailable->assertHasSubject('[Action Required] Client Requested Revisions - '.$project->title);
        // Should use "Your client" in body (use assertSeeInText for plain text match)
        $mailable->assertSeeInText('Your client');
    }

    /** @test */
    public function it_addresses_producer_by_name()
    {
        $producer = User::factory()->create(['name' => 'DJ Awesome']);
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientRevisionsRequested(
            $producer,
            $project,
            $pitch,
            'feedback'
        );

        $mailable->assertSeeInHtml('Hello DJ Awesome');
    }
}
