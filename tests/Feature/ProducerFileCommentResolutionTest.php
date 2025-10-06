<?php

use App\Livewire\Project\ManageClientProject;
use App\Models\FileComment;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create producer (project owner)
    $this->producer = User::factory()->create([
        'role' => 'producer',
    ]);

    // Create client management project
    $this->project = Project::factory()->create([
        'user_id' => $this->producer->id,
        'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
        'client_email' => 'client@example.com',
        'client_name' => 'Test Client',
    ]);

    // Create pitch for client management workflow
    $this->pitch = Pitch::factory()->create([
        'project_id' => $this->project->id,
        'user_id' => $this->producer->id,
        'status' => Pitch::STATUS_IN_PROGRESS,
    ]);

    // Create a pitch file for testing
    $this->pitchFile = PitchFile::factory()->create([
        'pitch_id' => $this->pitch->id,
        'file_name' => 'test-mix.mp3',
        'file_path' => 'pitch-files/test-mix.mp3',
        'mime_type' => 'audio/mpeg',
        'size' => 5000000,
    ]);
});

it('allows producer to resolve client comments', function () {
    // Create a client comment
    $clientComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => null,
        'comment' => 'The bass needs more punch',
        'timestamp' => 45.0,
        'resolved' => false,
        'is_client_comment' => true,
        'client_email' => 'client@example.com',
    ]);

    $this->actingAs($this->producer);

    // Producer marks client comment as resolved
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $clientComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify comment is marked as resolved
    expect($clientComment->fresh()->resolved)->toBeTrue();
});

it('allows producer to resolve their own comments', function () {
    // Create a producer comment
    $producerComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'TODO: Add reverb to vocals',
        'timestamp' => 60.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Producer marks their own comment as resolved
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $producerComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify comment is marked as resolved
    expect($producerComment->fresh()->resolved)->toBeTrue();
});

it('allows producer to respond to their own comments', function () {
    // Create a producer comment
    $producerComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Need to fix timing issue at chorus',
        'timestamp' => 120.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Producer responds to their own comment
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'respondToFileComment',
            'commentId' => $producerComment->id,
            'response' => 'Fixed the timing - adjusted drum track',
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify a reply was created
    $reply = FileComment::where('parent_id', $producerComment->id)->first();
    expect($reply)->not->toBeNull();
    expect($reply->comment)->toBe('Fixed the timing - adjusted drum track');
    expect($reply->is_client_comment)->toBeFalse();
    expect($reply->user_id)->toBe($this->producer->id);

    // Verify original comment is marked as resolved
    expect($producerComment->fresh()->resolved)->toBeTrue();
});

it('shows mark as addressed button for producer comments in main app', function () {
    // Create a producer comment
    $producerComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Producer note: Check mix levels',
        'timestamp' => 30.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Render the component and check for the button
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->assertSee('Mark as Addressed');
});

it('maintains resolution status after page refresh', function () {
    // Create a producer comment
    $producerComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Mixing note',
        'timestamp' => 90.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Mark as resolved
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $producerComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify it persisted in database
    $this->assertDatabaseHas('file_comments', [
        'id' => $producerComment->id,
        'resolved' => true,
    ]);

    // Fresh component instance should show resolved status
    expect($producerComment->fresh()->resolved)->toBeTrue();
});

it('does not show mark as addressed button for already resolved comments', function () {
    // Create an already-resolved producer comment
    $resolvedComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Already handled',
        'timestamp' => 15.0,
        'resolved' => true,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // The component should show resolved badge instead of action buttons
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->assertSee('Resolved');
});

it('allows producer to mark multiple comments as resolved independently', function () {
    // Create multiple producer comments
    $comment1 = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'First task',
        'timestamp' => 10.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $comment2 = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Second task',
        'timestamp' => 20.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Resolve first comment
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $comment1->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify first is resolved, second is not
    expect($comment1->fresh()->resolved)->toBeTrue();
    expect($comment2->fresh()->resolved)->toBeFalse();

    // Resolve second comment
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $comment2->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify both are now resolved
    expect($comment1->fresh()->resolved)->toBeTrue();
    expect($comment2->fresh()->resolved)->toBeTrue();
});

it('handles mixed client and producer comments correctly', function () {
    // Create a client comment
    $clientComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => null,
        'comment' => 'Client feedback',
        'timestamp' => 45.0,
        'resolved' => false,
        'is_client_comment' => true,
        'client_email' => 'client@example.com',
    ]);

    // Create a producer comment
    $producerComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Producer note',
        'timestamp' => 60.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Producer can resolve both
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $clientComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ])
        ->call('handleCommentAction', [
            'action' => 'markFileCommentResolved',
            'commentId' => $producerComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify both are resolved
    expect($clientComment->fresh()->resolved)->toBeTrue();
    expect($producerComment->fresh()->resolved)->toBeTrue();
});

it('allows producer to unresolve their own resolved comment', function () {
    // Create and resolve a producer comment
    $producerComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Fixed the EQ issue',
        'timestamp' => 75.0,
        'resolved' => true,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Producer unresolves their own comment
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'unresolveFileComment',
            'commentId' => $producerComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify comment is now unresolved
    expect($producerComment->fresh()->resolved)->toBeFalse();
});

it('allows producer to unresolve client resolved comment', function () {
    // Create and resolve a client comment
    $clientComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => null,
        'comment' => 'This sounds perfect now',
        'timestamp' => 90.0,
        'resolved' => true,
        'is_client_comment' => true,
        'client_email' => 'client@example.com',
    ]);

    $this->actingAs($this->producer);

    // Producer unresolves client comment
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'unresolveFileComment',
            'commentId' => $clientComment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify comment is now unresolved
    expect($clientComment->fresh()->resolved)->toBeFalse();
});

it('shows clickable resolved badge for authorized users', function () {
    // Create a resolved producer comment
    $resolvedComment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Already fixed',
        'timestamp' => 50.0,
        'resolved' => true,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // The component should show resolved badge
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->assertSee('Resolved');
});

it('persists unresolve status in database', function () {
    // Create a resolved comment
    $comment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Task complete',
        'timestamp' => 100.0,
        'resolved' => true,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    // Unresolve the comment
    Livewire::test(ManageClientProject::class, ['project' => $this->project])
        ->call('handleCommentAction', [
            'action' => 'unresolveFileComment',
            'commentId' => $comment->id,
            'modelType' => 'pitch',
            'modelId' => $this->pitch->id,
        ]);

    // Verify it persisted in database
    $this->assertDatabaseHas('file_comments', [
        'id' => $comment->id,
        'resolved' => false,
    ]);
});

it('allows multiple resolve and unresolve cycles', function () {
    // Create a comment
    $comment = FileComment::create([
        'commentable_type' => PitchFile::class,
        'commentable_id' => $this->pitchFile->id,
        'user_id' => $this->producer->id,
        'comment' => 'Iterative task',
        'timestamp' => 110.0,
        'resolved' => false,
        'is_client_comment' => false,
        'client_email' => null,
    ]);

    $this->actingAs($this->producer);

    $component = Livewire::test(ManageClientProject::class, ['project' => $this->project]);

    // First cycle: resolve
    $component->call('handleCommentAction', [
        'action' => 'markFileCommentResolved',
        'commentId' => $comment->id,
        'modelType' => 'pitch',
        'modelId' => $this->pitch->id,
    ]);
    expect($comment->fresh()->resolved)->toBeTrue();

    // First unresolve
    $component->call('handleCommentAction', [
        'action' => 'unresolveFileComment',
        'commentId' => $comment->id,
        'modelType' => 'pitch',
        'modelId' => $this->pitch->id,
    ]);
    expect($comment->fresh()->resolved)->toBeFalse();

    // Second cycle: resolve again
    $component->call('handleCommentAction', [
        'action' => 'markFileCommentResolved',
        'commentId' => $comment->id,
        'modelType' => 'pitch',
        'modelId' => $this->pitch->id,
    ]);
    expect($comment->fresh()->resolved)->toBeTrue();

    // Second unresolve
    $component->call('handleCommentAction', [
        'action' => 'unresolveFileComment',
        'commentId' => $comment->id,
        'modelType' => 'pitch',
        'modelId' => $this->pitch->id,
    ]);
    expect($comment->fresh()->resolved)->toBeFalse();
});
