<?php

namespace Tests\Unit\Livewire;

use App\Livewire\FileComparisonPlayer;
use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FileComparisonPlayerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected PitchFile $leftFile;

    protected PitchFile $rightFile;

    protected PitchSnapshot $leftSnapshot;

    protected PitchSnapshot $rightSnapshot;

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

        // Create pitch for the client management project
        $pitch = \App\Models\Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => \App\Models\Pitch::STATUS_IN_PROGRESS,
        ]);

        // Create two pitch files for comparison
        $this->leftFile = PitchFile::factory()->create([
            'pitch_id' => $pitch->id,
            'user_id' => $this->user->id,
            'file_name' => 'version_1.mp3',
            'duration' => 180.0,
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $this->rightFile = PitchFile::factory()->create([
            'pitch_id' => $pitch->id,
            'user_id' => $this->user->id,
            'file_name' => 'version_2.mp3',
            'duration' => 185.0,
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);

        // Create snapshots for version tracking
        $this->leftSnapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => PitchSnapshot::STATUS_PENDING,
            'snapshot_data' => [
                'file_ids' => [$this->leftFile->id],
                'comment' => 'Initial version',
                'version' => 1,
            ],
        ]);

        $this->rightSnapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->user->id,
            'status' => PitchSnapshot::STATUS_PENDING,
            'snapshot_data' => [
                'file_ids' => [$this->rightFile->id],
                'comment' => 'Revised version with changes',
                'version' => 2,
            ],
        ]);
    }

    /** @test */
    public function can_mount_component_with_two_files()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $component->assertSet('leftFile.id', $this->leftFile->id)
            ->assertSet('rightFile.id', $this->rightFile->id)
            ->assertSet('syncPlayback', true);
    }

    /** @test */
    public function loads_associated_snapshots_correctly()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $leftSnapshot = $component->get('leftSnapshot');
        $rightSnapshot = $component->get('rightSnapshot');

        $this->assertNotNull($leftSnapshot);
        $this->assertNotNull($rightSnapshot);
        $this->assertEquals(1, $leftSnapshot['snapshot_data']['version']);
        $this->assertEquals(2, $rightSnapshot['snapshot_data']['version']);
    }

    /** @test */
    public function can_toggle_sync_playback()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $component->assertSet('syncPlayback', true);

        $component->call('toggleSync');

        $component->assertSet('syncPlayback', false);

        $component->call('toggleSync');

        $component->assertSet('syncPlayback', true);
    }

    /** @test */
    public function handles_files_without_snapshots_gracefully()
    {
        // Create files without associated snapshots
        $fileWithoutSnapshot = PitchFile::factory()->create([
            'pitch_id' => $this->leftFile->pitch_id,
            'user_id' => $this->user->id,
            'file_name' => 'no_snapshot.mp3',
        ]);

        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $fileWithoutSnapshot,
            'rightFile' => $this->rightFile,
        ]);

        $leftSnapshot = $component->get('leftSnapshot');
        $rightSnapshot = $component->get('rightSnapshot');

        $this->assertNull($leftSnapshot);
        $this->assertNotNull($rightSnapshot);
    }

    /** @test */
    public function provides_file_metadata_for_comparison()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $this->assertTrue(method_exists($component->instance(), 'getFileMetadata'));

        $leftMetadata = $component->instance()->getFileMetadata($this->leftFile);
        $rightMetadata = $component->instance()->getFileMetadata($this->rightFile);

        $this->assertArrayHasKey('duration', $leftMetadata);
        $this->assertArrayHasKey('file_size', $leftMetadata);
        $this->assertArrayHasKey('created_at', $leftMetadata);

        $this->assertEquals(180.0, $leftMetadata['duration']);
        $this->assertEquals(185.0, $rightMetadata['duration']);
    }

    /** @test */
    public function calculates_file_differences()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $this->assertTrue(method_exists($component->instance(), 'getFileDifferences'));

        $differences = $component->instance()->getFileDifferences();

        $this->assertArrayHasKey('duration_diff', $differences);
        $this->assertArrayHasKey('size_diff', $differences);
        $this->assertArrayHasKey('version_diff', $differences);

        $this->assertEquals(5.0, $differences['duration_diff']); // 185 - 180
        $this->assertEquals(1, $differences['version_diff']); // version 2 - version 1
    }

    /** @test */
    public function handles_same_file_comparison()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->leftFile, // Same file
        ]);

        $differences = $component->instance()->getFileDifferences();

        $this->assertEquals(0, $differences['duration_diff']);
        $this->assertEquals(0, $differences['size_diff']);
        $this->assertEquals(0, $differences['version_diff']);
    }

    /** @test */
    public function loads_comments_for_both_files()
    {
        // Add comments to both files
        $this->leftFile->comments()->create([
            'user_id' => $this->user->id,
            'comment' => 'Left file comment',
            'timestamp' => 30.0,
            'resolved' => false,
        ]);

        $this->rightFile->comments()->create([
            'user_id' => null,
            'client_email' => 'client@example.com',
            'is_client_comment' => true,
            'comment' => 'Right file client comment',
            'timestamp' => 45.0,
            'resolved' => false,
        ]);

        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $this->assertTrue(method_exists($component->instance(), 'getLeftComments'));
        $this->assertTrue(method_exists($component->instance(), 'getRightComments'));

        $leftComments = $component->instance()->getLeftComments();
        $rightComments = $component->instance()->getRightComments();

        $this->assertCount(1, $leftComments);
        $this->assertCount(1, $rightComments);

        $this->assertEquals('Left file comment', $leftComments->first()->comment);
        $this->assertEquals('Right file client comment', $rightComments->first()->comment);
    }

    /** @test */
    public function can_jump_to_timestamp_on_both_players()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $component->call('jumpToTimestamp', 60.0, 'both');

        // Should dispatch seek events for both players
        $component->assertDispatched('seekToPosition');
    }

    /** @test */
    public function can_jump_to_timestamp_on_individual_player()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $component->call('jumpToTimestamp', 45.0, 'left');
        $component->assertDispatched('seekToPosition');

        $component->call('jumpToTimestamp', 75.0, 'right');
        $component->assertDispatched('seekToPosition');
    }

    /** @test */
    public function validates_file_compatibility_for_comparison()
    {
        // Create file from different pitch
        $otherProject = Project::factory()->create(['user_id' => $this->user->id]);
        $otherPitch = \App\Models\Pitch::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $this->user->id,
        ]);
        $otherFile = PitchFile::factory()->create([
            'pitch_id' => $otherPitch->id,
            'user_id' => $this->user->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);

        $component = new FileComparisonPlayer;
        $component->mount($this->leftFile, $otherFile);
    }

    /** @test */
    public function provides_comparison_summary_statistics()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $this->assertTrue(method_exists($component->instance(), 'getComparisonSummary'));

        $summary = $component->instance()->getComparisonSummary();

        $this->assertArrayHasKey('files_compared', $summary);
        $this->assertArrayHasKey('total_duration_change', $summary);
        $this->assertArrayHasKey('version_span', $summary);
        $this->assertArrayHasKey('comment_changes', $summary);

        $this->assertEquals(2, $summary['files_compared']);
        $this->assertEquals(5.0, $summary['total_duration_change']);
    }

    /** @test */
    public function supports_multiple_comparison_modes()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        // Test side-by-side mode (default)
        $component->assertSet('comparisonMode', 'side-by-side');

        // Test overlay mode
        $component->call('setComparisonMode', 'overlay');
        $component->assertSet('comparisonMode', 'overlay');

        // Test sequential mode
        $component->call('setComparisonMode', 'sequential');
        $component->assertSet('comparisonMode', 'sequential');
    }

    /** @test */
    public function tracks_playback_synchronization_events()
    {
        $component = Livewire::test(FileComparisonPlayer::class, [
            'leftFile' => $this->leftFile,
            'rightFile' => $this->rightFile,
        ]);

        $component->call('onPlayerEvent', 'left', 'play', 30.0);
        $component->call('onPlayerEvent', 'right', 'seek', 45.0);

        // Component should handle synchronization events
        $this->assertTrue(method_exists($component->instance(), 'onPlayerEvent'));
    }
}
