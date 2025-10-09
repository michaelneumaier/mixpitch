<?php

namespace Tests\Unit\Mail\Client;

use App\Mail\Client\RevisionRequestConfirmation;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisionRequestConfirmationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_with_correct_subject_line()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'title' => 'My Awesome Track',
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $feedback = 'Please add more bass';
        $signedUrl = 'https://example.com/portal/signed';

        $mailable = new RevisionRequestConfirmation(
            $project,
            $pitch,
            $feedback,
            $signedUrl,
            'John Client'
        );

        $mailable->assertHasSubject('Revision Request Received for My Awesome Track');
    }

    /** @test */
    public function it_includes_client_feedback_in_body()
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
        $feedback = 'Please add more bass and reduce treble';

        $mailable = new RevisionRequestConfirmation(
            $project,
            $pitch,
            $feedback,
            'https://example.com/portal',
            'Jane'
        );

        $mailable->assertSeeInHtml($feedback);
        $mailable->assertSeeInHtml('View Your Project');
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

        $mailable = new RevisionRequestConfirmation(
            $project,
            $pitch,
            'test feedback',
            'https://example.com',
            'Client'
        );

        $this->assertEquals(
            'emails.client.revision_request_confirmation',
            $mailable->content()->markdown
        );
    }

    /** @test */
    public function it_handles_null_client_name_gracefully()
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

        $mailable = new RevisionRequestConfirmation(
            $project,
            $pitch,
            'feedback',
            'https://example.com',
            null // No client name
        );

        // Should use fallback greeting
        $mailable->assertSeeInHtml('Hello there');
    }

    /** @test */
    public function it_includes_producer_name_in_content()
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

        $mailable = new RevisionRequestConfirmation(
            $project,
            $pitch,
            'test feedback',
            'https://example.com',
            'Client Name'
        );

        $mailable->assertSeeInHtml('DJ Awesome');
    }

    /** @test */
    public function it_includes_portal_link_button()
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
        $signedUrl = 'https://example.com/portal/abc123';

        $mailable = new RevisionRequestConfirmation(
            $project,
            $pitch,
            'test feedback',
            $signedUrl,
            'Client'
        );

        $mailable->assertSeeInHtml($signedUrl, false);
    }
}
