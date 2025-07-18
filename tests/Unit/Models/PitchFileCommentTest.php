<?php

namespace Tests\Unit\Models;

use App\Models\PitchFile;
use App\Models\PitchFileComment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PitchFileCommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected PitchFile $pitchFile;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'client_management',
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
        ]);
        
        $this->pitchFile = PitchFile::factory()->create([
            'pitch_id' => $this->project->pitches()->first()->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function can_create_regular_user_comment()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'This is a regular comment',
            'timestamp' => 45.5,
            'resolved' => false,
        ]);

        $this->assertFalse($comment->isClientComment());
        $this->assertEquals($this->user->name, $comment->getAuthorName());
        $this->assertNull($comment->client_email);
    }

    /** @test */
    public function can_create_client_comment()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'This is a client comment',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        $this->assertTrue($comment->isClientComment());
        $this->assertEquals('client@example.com', $comment->getAuthorName());
        $this->assertEquals('client@example.com', $comment->client_email);
    }

    /** @test */
    public function client_comment_returns_correct_author_name()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'test@client.com',
            'is_client_comment' => true,
            'comment' => 'Client feedback',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $this->assertEquals('test@client.com', $comment->getAuthorName());
    }

    /** @test */
    public function user_comment_returns_user_name()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Producer feedback',
            'timestamp' => 120.0,
            'resolved' => false,
        ]);

        $this->assertEquals($this->user->name, $comment->getAuthorName());
    }

    /** @test */
    public function can_scope_client_comments()
    {
        // Create mixed comments
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Producer comment',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client comment',
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        $clientComments = PitchFileComment::where('is_client_comment', true)->get();
        $userComments = PitchFileComment::where('is_client_comment', false)->get();

        $this->assertEquals(1, $clientComments->count());
        $this->assertEquals(1, $userComments->count());
        $this->assertTrue($clientComments->first()->isClientComment());
        $this->assertFalse($userComments->first()->isClientComment());
    }

    /** @test */
    public function client_comments_can_have_replies_from_producers()
    {
        $clientComment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client needs clarification',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $reply = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'parent_id' => $clientComment->id,
            'comment' => 'Producer response',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $this->assertTrue($clientComment->has_replies);
        $this->assertEquals(1, $clientComment->replies->count());
        $this->assertEquals($reply->id, $clientComment->replies->first()->id);
        $this->assertFalse($reply->isClientComment());
    }

    /** @test */
    public function can_resolve_client_comments()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Please change this section',
            'timestamp' => 90.0,
            'resolved' => false,
        ]);

        $this->assertFalse($comment->resolved);

        $comment->update(['resolved' => true]);
        $comment->refresh();

        $this->assertTrue($comment->resolved);
    }

    /** @test */
    public function timestamp_formatting_works_correctly()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Test comment',
            'timestamp' => 125.5, // 2:05
            'resolved' => false,
        ]);

        $this->assertEquals('02:05', $comment->formatted_timestamp);
    }

    /** @test */
    public function can_get_comments_by_pitch_file_and_client_status()
    {
        // Create comments for the same pitch file
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Producer comment 1',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client comment 1',
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client comment 2',
            'timestamp' => 60.0,
            'resolved' => true,
        ]);

        // Test filtering
        $allComments = PitchFileComment::where('pitch_file_id', $this->pitchFile->id)->get();
        $clientComments = PitchFileComment::where('pitch_file_id', $this->pitchFile->id)
            ->where('is_client_comment', true)->get();
        $unresolvedClientComments = PitchFileComment::where('pitch_file_id', $this->pitchFile->id)
            ->where('is_client_comment', true)
            ->where('resolved', false)->get();

        $this->assertEquals(3, $allComments->count());
        $this->assertEquals(2, $clientComments->count());
        $this->assertEquals(1, $unresolvedClientComments->count());
    }

    /** @test */
    public function client_comments_are_included_in_fillable_attributes()
    {
        $comment = new PitchFileComment();
        $fillable = $comment->getFillable();

        $this->assertContains('client_email', $fillable);
        $this->assertContains('is_client_comment', $fillable);
    }

    /** @test */
    public function client_comment_casts_work_correctly()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => 1, // Integer input
            'comment' => 'Test comment',
            'timestamp' => 45.0,
            'resolved' => 0, // Integer input
        ]);

        $this->assertIsBool($comment->is_client_comment);
        $this->assertIsBool($comment->resolved);
        $this->assertTrue($comment->is_client_comment);
        $this->assertFalse($comment->resolved);
    }
}