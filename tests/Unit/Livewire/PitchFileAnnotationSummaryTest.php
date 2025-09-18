<?php

namespace Tests\Unit\Livewire;

use App\Livewire\PitchFileAnnotationSummary;
use App\Models\PitchFile;
use App\Models\PitchFileComment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PitchFileAnnotationSummaryTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected User $client;

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

        // Create a pitch for the client management project
        $pitch = \App\Models\Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => \App\Models\Pitch::STATUS_IN_PROGRESS,
        ]);

        $this->pitchFile = PitchFile::factory()->create([
            'pitch_id' => $pitch->id,
            'user_id' => $this->user->id,
            'duration' => 180.0, // 3 minutes
        ]);
    }

    /** @test */
    public function can_mount_component_with_pitch_file()
    {
        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $component->assertSet('pitchFile.id', $this->pitchFile->id)
            ->assertSet('showResolved', false)
            ->assertSet('commentIds', []);
    }

    /** @test */
    public function loads_comments_grouped_by_time_intervals()
    {
        // Create comments at different timestamps
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment at 15 seconds',
            'timestamp' => 15.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client comment at 45 seconds',
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        // Comment at 75 seconds - should be in different interval
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment at 75 seconds',
            'timestamp' => 75.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $groupedComments = $component->instance()->getGroupedComments();

        // Should have multiple groups (intervals)
        $this->assertGreaterThan(1, count($groupedComments));

        // Check that comments are properly grouped
        $this->assertEquals(3, $groupedComments->flatten(1)->count());
    }

    /** @test */
    public function filters_resolved_comments_by_default()
    {
        // Create resolved and unresolved comments
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Resolved comment',
            'timestamp' => 30.0,
            'resolved' => true,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Unresolved comment',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        // Should only show unresolved comments by default
        $allComments = $component->instance()->getGroupedComments()->flatten(1);
        $this->assertEquals(1, $allComments->count());
        $this->assertEquals('Unresolved comment', $allComments->first()->comment);
    }

    /** @test */
    public function can_toggle_show_resolved_comments()
    {
        // Create resolved and unresolved comments
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Resolved comment',
            'timestamp' => 30.0,
            'resolved' => true,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Unresolved comment',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        // Initially should show only unresolved
        $this->assertEquals(1, $component->instance()->getGroupedComments()->flatten(1)->count());

        // Toggle to show resolved
        $component->call('toggleShowResolved');

        // Should now show both comments
        $this->assertEquals(2, $component->instance()->getGroupedComments()->flatten(1)->count());
        $component->assertSet('showResolved', true);
    }

    /** @test */
    public function groups_comments_by_30_second_intervals()
    {
        // Create comments in same 30-second interval (0-30 seconds)
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment at 10 seconds',
            'timestamp' => 10.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment at 25 seconds',
            'timestamp' => 25.0,
            'resolved' => false,
        ]);

        // Comment in next interval (30-60 seconds)
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Comment at 45 seconds',
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $groupedComments = $component->instance()->getGroupedComments();

        // Should have 2 groups
        $this->assertEquals(2, count($groupedComments));

        // First group (interval 0) should have 2 comments
        $firstGroup = $groupedComments->first();
        $this->assertEquals(2, $firstGroup->count());

        // Second group (interval 1) should have 1 comment
        $secondGroup = $groupedComments->values()->get(1);
        $this->assertEquals(1, $secondGroup->count());
    }

    /** @test */
    public function can_resolve_comment()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Test comment',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $component->call('resolveComment', $comment->id);

        // Comment should be resolved in database
        $this->assertTrue($comment->fresh()->resolved);

        // Should reload comments
        $groupedComments = $component->instance()->getGroupedComments();
        $this->assertEquals(0, $groupedComments->flatten(1)->count()); // Hidden because resolved by default
    }

    /** @test */
    public function includes_client_and_producer_comments()
    {
        // Producer comment
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Producer feedback',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        // Client comment
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client feedback',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $allComments = $component->instance()->getGroupedComments()->flatten(1);
        $this->assertEquals(2, $allComments->count());

        // Check that both types are included
        $commentTypes = $allComments->map(function ($comment) {
            return $comment->isClientComment();
        })->toArray();
        $this->assertContains(false, $commentTypes); // Producer comment
        $this->assertContains(true, $commentTypes);  // Client comment
    }

    /** @test */
    public function loads_nested_replies()
    {
        // Parent comment
        $parentComment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client needs clarification',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        // Reply to client comment
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'parent_id' => $parentComment->id,
            'comment' => 'Producer response',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $groupedComments = $component->instance()->getGroupedComments();
        $parentComments = $groupedComments->flatten(1);

        // Should load replies
        $this->assertEquals(1, $parentComments->count()); // Only parent comment in main list
        $firstComment = $parentComments->first();
        $this->assertTrue($firstComment->relationLoaded('replies'));
        $this->assertEquals(1, $firstComment->replies->count());
    }

    /** @test */
    public function orders_comments_by_timestamp()
    {
        // Create comments out of order
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Second comment (60s)',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'First comment (30s)',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Third comment (90s)',
            'timestamp' => 90.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        $allComments = $component->instance()->getGroupedComments()->flatten(1);

        // Should be ordered by timestamp
        $timestamps = $allComments->pluck('timestamp')->toArray();
        $this->assertEquals([30.0, 60.0, 90.0], $timestamps);
    }

    /** @test */
    public function provides_summary_statistics()
    {
        // Create mix of resolved and unresolved comments
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Resolved comment',
            'timestamp' => 30.0,
            'resolved' => true,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Unresolved comment',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        // Component should provide summary methods
        $this->assertTrue(method_exists($component->instance(), 'getTotalComments'));
        $this->assertTrue(method_exists($component->instance(), 'getUnresolvedCount'));
        $this->assertTrue(method_exists($component->instance(), 'getResolvedCount'));

        // Test the summary methods
        $this->assertEquals(2, $component->instance()->getTotalComments());
        $this->assertEquals(1, $component->instance()->getUnresolvedCount());
        $this->assertEquals(1, $component->instance()->getResolvedCount());
    }

    /** @test */
    public function can_jump_to_timestamp()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Test comment',
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(PitchFileAnnotationSummary::class, [
            'pitchFile' => $this->pitchFile,
        ]);

        // Should dispatch event to seek to timestamp
        $component->call('jumpToTimestamp', 45.0)
            ->assertDispatched('seekToPosition');
    }
}
