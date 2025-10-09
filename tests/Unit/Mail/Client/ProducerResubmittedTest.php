<?php

namespace Tests\Unit\Mail\Client;

use App\Mail\Client\ProducerResubmitted;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProducerResubmittedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_with_correct_subject_line()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'title' => 'Summer Vibes Mix',
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $signedUrl = 'https://example.com/portal/signed';

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            $signedUrl,
            'Jane Client',
            3
        );

        $mailable->assertHasSubject('Updated Work Ready for Review - Summer Vibes Mix');
    }

    /** @test */
    public function it_includes_file_count_in_body()
    {
        $producer = User::factory()->create(['name' => 'DJ Producer']);
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            'https://example.com/portal',
            'Client',
            5
        );

        $mailable->assertSeeInHtml('5 updated files');
        $mailable->assertSeeInHtml('DJ Producer');
    }

    /** @test */
    public function it_displays_producer_note_when_provided()
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
        $producerNote = 'I added more bass and adjusted the EQ as requested.';

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            'https://example.com/portal',
            'Client',
            2,
            $producerNote
        );

        $mailable->assertSeeInHtml($producerNote);
        $mailable->assertSeeInHtml('Message from');
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

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            'https://example.com',
            'Client',
            1
        );

        $this->assertEquals(
            'emails.client.producer_resubmitted',
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

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            'https://example.com',
            null, // No client name
            2
        );

        $mailable->assertSeeInHtml('Hello there');
    }

    /** @test */
    public function it_includes_review_button_with_portal_link()
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
        $signedUrl = 'https://example.com/portal/xyz789';

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            $signedUrl,
            'Client',
            1
        );

        $mailable->assertSeeInHtml($signedUrl, false);
        $mailable->assertSeeInHtml('Review Updated Work');
    }

    /** @test */
    public function it_handles_singular_file_count()
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

        $mailable = new ProducerResubmitted(
            $project,
            $pitch,
            'https://example.com',
            'Client',
            1 // Single file
        );

        $mailable->assertSeeInHtml('1 updated file');
    }
}
