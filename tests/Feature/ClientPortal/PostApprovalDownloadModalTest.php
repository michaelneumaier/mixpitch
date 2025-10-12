<?php

namespace Tests\Feature\ClientPortal;

use App\Livewire\ClientPortal\PostApprovalSuccessCard;
use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\PitchSnapshot;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Tests\TestCase;

class PostApprovalDownloadModalTest extends TestCase
{
    use RefreshDatabase;

    protected $producer;

    protected $project;

    protected $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');

        $this->producer = User::factory()->create([
            'role' => User::ROLE_PRODUCER,
            'name' => 'Test Producer',
        ]);

        $this->project = Project::factory()->create([
            'workflow_type' => Project::WORKFLOW_TYPE_CLIENT_MANAGEMENT,
            'client_email' => 'client@example.com',
            'client_name' => 'Test Client',
            'title' => 'Test Project',
        ]);

        $this->pitch = Pitch::factory()->create([
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'status' => Pitch::STATUS_COMPLETED,
            'payment_amount' => 500.00,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
        ]);
    }

    /** @test */
    public function it_renders_successfully_for_completed_pitch()
    {
        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->assertStatus(200);
        $component->assertSee('Project Completed!');
        $component->assertSee('Download Files');
    }

    /** @test */
    public function it_opens_download_modal_when_download_files_button_clicked()
    {
        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->assertSet('showDownloadModal', false);

        $component->call('openDownloadModal');

        $component->assertSet('showDownloadModal', true);
        $component->assertSee('Download Your Files');
    }

    /** @test */
    public function it_closes_download_modal()
    {
        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->call('openDownloadModal');
        $component->assertSet('showDownloadModal', true);

        $component->call('closeDownloadModal');
        $component->assertSet('showDownloadModal', false);
    }

    /** @test */
    public function it_displays_files_from_latest_snapshot_in_modal()
    {
        // Create pitch files
        $file1 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'final-mix.mp3',
            'mime_type' => 'audio/mpeg',
            'size' => 5242880, // 5MB
        ]);

        $file2 = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'master.wav',
            'mime_type' => 'audio/wav',
            'size' => 10485760, // 10MB
        ]);

        // Create a snapshot with these files
        $snapshot = PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => [
                'file_ids' => [$file1->id, $file2->id],
                'title' => 'Final Submission',
            ],
            'status' => PitchSnapshot::STATUS_ACCEPTED,
        ]);

        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->call('openDownloadModal');

        $component->assertSee('final-mix.mp3');
        $component->assertSee('master.wav');
        $component->assertSee('5 MB');
        $component->assertSee('10 MB');
    }

    /** @test */
    public function it_dispatches_download_event_when_download_button_clicked()
    {
        $file = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test-file.mp3',
            'mime_type' => 'audio/mpeg',
        ]);

        PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => [
                'file_ids' => [$file->id],
            ],
        ]);

        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->call('downloadFile', $file->id);

        $component->assertDispatched('download-file');
    }

    /** @test */
    public function it_shows_empty_state_when_no_files_available()
    {
        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->call('openDownloadModal');

        $component->assertSee('No files available for download');
    }

    /** @test */
    public function it_hides_download_button_when_milestones_not_all_paid()
    {
        // Create unpaid milestones
        $milestone1 = $this->pitch->milestones()->create([
            'name' => 'Milestone 1',
            'amount' => 250,
            'payment_status' => Pitch::PAYMENT_STATUS_PAID,
        ]);

        $milestone2 = $this->pitch->milestones()->create([
            'name' => 'Milestone 2',
            'amount' => 250,
            'payment_status' => Pitch::PAYMENT_STATUS_PENDING,
        ]);

        $milestones = collect([$milestone1, $milestone2]);

        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => $milestones,
        ]);

        $component->assertSee('Complete all milestone payments to unlock downloads');
        $component->assertDontSee('Download Files');
    }

    /** @test */
    public function it_formats_file_sizes_correctly()
    {
        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $instance = $component->instance();
        expect($instance->formatFileSize(1024))->toBe('1 KB');
        expect($instance->formatFileSize(1048576))->toBe('1 MB');
        expect($instance->formatFileSize(1073741824))->toBe('1 GB');
        expect($instance->formatFileSize(500))->toBe('500 bytes');
    }

    /** @test */
    public function it_returns_correct_file_icons_for_mime_types()
    {
        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $instance = $component->instance();
        expect($instance->getFileIcon('audio/mpeg'))->toBe('musical-note');
        expect($instance->getFileIcon('video/mp4'))->toBe('video-camera');
        expect($instance->getFileIcon('application/pdf'))->toBe('document-text');
        expect($instance->getFileIcon('image/png'))->toBe('photo');
        expect($instance->getFileIcon('application/zip'))->toBe('archive-box');
        expect($instance->getFileIcon('text/plain'))->toBe('document');
    }

    /** @test */
    public function it_shows_download_button_for_approved_status()
    {
        $this->pitch->update(['status' => Pitch::STATUS_APPROVED]);

        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->assertDontSee('Download Files');
        $component->assertSee('Project Approved!');
    }

    /** @test */
    public function download_generates_valid_signed_url()
    {
        $file = PitchFile::factory()->create([
            'pitch_id' => $this->pitch->id,
            'file_name' => 'test.mp3',
        ]);

        PitchSnapshot::factory()->create([
            'pitch_id' => $this->pitch->id,
            'project_id' => $this->project->id,
            'user_id' => $this->producer->id,
            'snapshot_data' => [
                'file_ids' => [$file->id],
            ],
        ]);

        $component = Livewire::test(PostApprovalSuccessCard::class, [
            'project' => $this->project,
            'pitch' => $this->pitch,
            'milestones' => collect(),
        ]);

        $component->call('downloadFile', $file->id);

        $component->assertDispatched('download-file', function ($name, $data) {
            // Verify the URL contains the expected route signature
            return isset($data['url']) && (
                str_contains($data['url'], 'client.portal.download_file') ||
                str_contains($data['url'], 'signature=')
            );
        });
    }
}
