<?php

namespace Tests\Unit\Mail\Producer;

use App\Mail\Producer\ClientCommented;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientCommentedTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_renders_with_correct_subject_line()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'title' => 'Funky Beat',
            'client_name' => 'Sarah Client',
            'client_email' => 'sarah@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $comment = 'This is sounding great! Just a quick question...';

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            $comment
        );

        $mailable->assertHasSubject('New Message from Sarah Client - Funky Beat');
    }

    /** @test */
    public function it_includes_short_comment_in_full()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_name' => 'John',
            'client_email' => 'john@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);
        $comment = 'Sounds perfect! Thank you.';

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            $comment
        );

        $mailable->assertSeeInHtml($comment);
        $mailable->assertDontSeeInHtml('[View full message in project]');
    }

    /** @test */
    public function it_truncates_long_comments_with_indicator()
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
        $comment = str_repeat('This is a very long comment that exceeds the character limit. ', 20);

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            $comment
        );

        $mailable->assertSeeInHtml('[View full message in project]');
        $mailable->assertDontSeeInHtml($comment); // Should not see full comment
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

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            'test comment'
        );

        $this->assertEquals(
            'emails.producer.client_commented',
            $mailable->content()->markdown
        );
    }

    /** @test */
    public function it_includes_reply_button_with_project_link()
    {
        $producer = User::factory()->create();
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_name' => 'Jane',
            'client_email' => 'jane@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            'test comment'
        );

        $projectUrl = route('projects.manage-client', $project);
        $mailable->assertSeeInHtml($projectUrl, false);
        $mailable->assertSeeInHtml('Reply to Jane');
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

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            'comment'
        );

        // Should use fallback "Client" in subject (includes project title)
        $mailable->assertHasSubject('New Message from Client - ' . $project->title);
        // Should use "Your client" in body (use assertSeeInText for plain text match)
        $mailable->assertSeeInText('Your client');
    }

    /** @test */
    public function it_addresses_producer_by_name()
    {
        $producer = User::factory()->create(['name' => 'Producer Mike']);
        $project = Project::factory()->create([
            'user_id' => $producer->id,
            'client_email' => 'client@example.com',
        ]);
        $pitch = Pitch::factory()->create([
            'project_id' => $project->id,
            'user_id' => $producer->id,
        ]);

        $mailable = new ClientCommented(
            $producer,
            $project,
            $pitch,
            'comment'
        );

        $mailable->assertSeeInHtml('Hello Producer Mike');
    }
}
