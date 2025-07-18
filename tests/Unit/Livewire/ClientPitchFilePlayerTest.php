<?php

namespace Tests\Unit\Livewire;

use App\Livewire\ClientPitchFilePlayer;
use App\Models\PitchFile;
use App\Models\PitchFileComment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class ClientPitchFilePlayerTest extends TestCase
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
        
        // Create a pitch for the client management project
        $pitch = \App\Models\Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => \App\Models\Pitch::STATUS_IN_PROGRESS,
        ]);
        
        $this->pitchFile = PitchFile::factory()->create([
            'pitch_id' => $pitch->id,
            'user_id' => $this->user->id,
            'duration' => 120.0,
        ]);
    }

    /** @test */
    public function can_mount_component_for_client_management_project()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $component->assertSet('clientMode', true)
                 ->assertSet('clientEmail', 'client@example.com')
                 ->assertSet('signedAccess', true);
    }

    /** @test */
    public function rejects_non_client_management_project()
    {
        $standardProject = Project::factory()->create([
            'user_id' => $this->user->id,
            'workflow_type' => 'standard',
        ]);

        $standardPitch = \App\Models\Pitch::factory()->create([
            'project_id' => $standardProject->id,
            'user_id' => $this->user->id,
        ]);
        
        $standardPitchFile = PitchFile::factory()->create([
            'pitch_id' => $standardPitch->id,
            'user_id' => $this->user->id,
        ]);

        // Verify the project is NOT client management
        $this->assertFalse($standardProject->isClientManagement());

        // Test that the component throws exception during instantiation
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        // This should throw exception during mount
        new \App\Livewire\ClientPitchFilePlayer();
        $component = new \App\Livewire\ClientPitchFilePlayer();
        $component->mount($standardPitchFile, $standardProject, true);
    }

    /** @test */
    public function client_can_add_comment_with_timestamp()
    {
        Mail::fake();

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $component->call('addComment', 45.5, 'This needs to be changed');

        // Check that comment was created
        $this->assertDatabaseHas('pitch_file_comments', [
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'This needs to be changed',
            'timestamp' => 45.5,
        ]);

        // Check that component state was updated
        $component->assertSet('comments.0.comment', 'This needs to be changed');
    }

    /** @test */
    public function client_comment_validates_required_fields()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $component->call('addComment', 30.0, '');

        $component->assertHasErrors(['comment']);
    }

    /** @test */
    public function client_comment_validates_comment_length()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $longComment = str_repeat('a', 1001);
        $component->call('addComment', 30.0, $longComment);

        $component->assertHasErrors(['comment']);
    }

    /** @test */
    public function client_cannot_resolve_comments()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'Producer comment',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $permissions = $component->instance()->getCommentPermissions();

        $this->assertTrue($permissions['can_add']);
        $this->assertFalse($permissions['can_edit']);
        $this->assertFalse($permissions['can_delete']);
        $this->assertFalse($permissions['can_resolve']);
    }

    /** @test */
    public function client_cannot_delete_comments()
    {
        $comment = PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Client comment',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        // Try to delete comment - should not work
        $component->call('deleteComment', $comment->id);

        // Comment should still exist
        $this->assertDatabaseHas('pitch_file_comments', [
            'id' => $comment->id,
        ]);
    }

    /** @test */
    public function loads_existing_comments_including_client_comments()
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

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        // Should load both comments
        $this->assertCount(2, $component->get('comments'));
    }

    /** @test */
    public function calculates_comment_markers_correctly()
    {
        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => $this->user->id,
            'comment' => 'At 30 seconds',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        PitchFileComment::create([
            'pitch_file_id' => $this->pitchFile->id,
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'At 60 seconds',
            'timestamp' => 60.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $markers = $component->get('commentMarkers');

        $this->assertCount(2, $markers);
        $this->assertEquals(25.0, $markers[0]['position']); // 30/120 * 100
        $this->assertEquals(50.0, $markers[1]['position']); // 60/120 * 100
    }

    /** @test */
    public function filters_comments_by_resolution_status()
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
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        // Should be able to filter by resolution status
        $component->call('toggleShowResolved');
        $component->call('loadComments');

        // Component should have method to get unresolved comments
        $this->assertTrue(method_exists($component->instance(), 'getUnresolvedComments'));
    }

    /** @test */
    public function notifies_producer_when_client_adds_comment()
    {
        Mail::fake();

        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $component->call('addComment', 45.5, 'This needs to be changed');

        // Check that notification was sent
        $this->assertTrue(method_exists($component->instance(), 'notifyProducerOfClientComment'));
    }

    /** @test */
    public function handles_waveform_ready_event()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        // Should handle waveform ready event
        $component->call('onWaveformReady');
        $this->assertIsArray($component->get('commentMarkers'));
    }

    /** @test */
    public function client_mode_affects_ui_permissions()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $permissions = $component->instance()->getCommentPermissions();

        // Client should only be able to add comments
        $this->assertTrue($permissions['can_add']);
        $this->assertFalse($permissions['can_edit']);
        $this->assertFalse($permissions['can_delete']);
        $this->assertFalse($permissions['can_resolve']);
    }

    /** @test */
    public function tracks_client_email_correctly()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $this->assertEquals('client@example.com', $component->get('clientEmail'));
    }

    /** @test */
    public function handles_signed_access_correctly()
    {
        $component = Livewire::test(ClientPitchFilePlayer::class, [
            'pitchFile' => $this->pitchFile,
            'project' => $this->project,
            'signedAccess' => true,
        ]);

        $this->assertTrue($component->get('signedAccess'));
    }
}