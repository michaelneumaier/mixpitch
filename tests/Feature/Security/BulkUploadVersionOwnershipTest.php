<?php

namespace Tests\Feature\Security;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use App\Services\FileManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BulkUploadVersionOwnershipTest extends TestCase
{
    use RefreshDatabase;

    protected FileManagementService $service;

    protected User $producerA;

    protected User $producerB;

    protected Pitch $pitchA;

    protected Pitch $pitchB;

    protected PitchFile $fileA;

    protected PitchFile $fileB;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->service = app(FileManagementService::class);

        // Producer A and their pitch/file (the attacker acting on their own pitch)
        $this->producerA = User::factory()->create();
        $this->pitchA = Pitch::factory()
            ->recycle($this->producerA)
            ->recycle(Project::factory()->create())
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        $this->fileA = PitchFile::factory()->recycle($this->pitchA)->create([
            'file_name' => 'A-track.wav',
            'original_file_name' => 'A-track.wav',
        ]);

        // Producer B and their pitch/file (the victim, another tenant)
        $this->producerB = User::factory()->create();
        $this->pitchB = Pitch::factory()
            ->recycle($this->producerB)
            ->recycle(Project::factory()->create())
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);
        $this->fileB = PitchFile::factory()->recycle($this->pitchB)->create([
            'file_name' => 'B-track.wav',
            'original_file_name' => 'B-track.wav',
        ]);
    }

    /** @test */
    public function manual_match_referencing_another_pitchs_file_is_rejected()
    {
        $versionsBefore = $this->fileB->fresh()->versions()->count();

        Storage::disk('s3')->put('attacker_key', 'audio-content');

        $uploadData = [
            'name' => 'malicious.wav',
            's3_key' => 'attacker_key',
            'size' => 2000,
            'type' => 'audio/wav',
        ];

        // Producer A uploads to their own pitch (pitchA) but supplies producer B's
        // file id in the manual matches, attempting a cross-tenant version write.
        $result = $this->service->bulkUploadFileVersions(
            $this->pitchA,
            [$uploadData],
            $this->producerA,
            [$this->fileB->id => $uploadData]
        );

        // No version was created on producer B's file.
        $this->assertSame(
            $versionsBefore,
            $this->fileB->fresh()->versions()->count(),
            'Producer B file must not gain a new version.'
        );

        // No cross-tenant version was reported as created.
        $this->assertCount(0, $result['created_versions']);

        // Producer A's pitch is not corrupted: no new version was pushed onto its file
        // via the rejected match.
        $this->assertSame(0, $this->fileA->fresh()->versions()->count());

        // Producer B's file record is untouched (same s3 key, size).
        $freshB = $this->fileB->fresh();
        $this->assertNotEquals('attacker_key', $freshB->storage_path);
        $this->assertNotEquals(2000, $freshB->size);

        // The uploaded file's s3_key is in the manual matches, so it is not
        // created as a new file on the attacker's pitch either.
        $this->assertCount(0, $result['new_files']);
    }

    /** @test */
    public function legitimate_manual_match_on_own_pitch_still_works()
    {
        Storage::disk('s3')->put('legit_key', 'audio-content');

        $uploadData = [
            'name' => 'A-track-v2.wav',
            's3_key' => 'legit_key',
            'size' => 3000,
            'type' => 'audio/wav',
        ];

        $result = $this->service->bulkUploadFileVersions(
            $this->pitchA,
            [$uploadData],
            $this->producerA,
            [$this->fileA->id => $uploadData]
        );

        $this->assertCount(1, $result['created_versions']);
        $this->assertSame(1, $this->fileA->fresh()->versions()->count());
        // Producer B is entirely untouched.
        $this->assertSame(0, $this->fileB->fresh()->versions()->count());
    }
}
