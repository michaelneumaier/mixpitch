<?php

namespace Tests\Unit\Services;

use App\Models\Pitch;
use App\Models\PitchFile;
use App\Models\Project;
use App\Models\User;
use App\Services\FileManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileVersioningServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $producer;

    protected Pitch $pitch;

    protected FileManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');

        $this->producer = User::factory()->create();
        $project = Project::factory()->create();
        $this->pitch = Pitch::factory()
            ->recycle($this->producer)
            ->recycle($project)
            ->create(['status' => Pitch::STATUS_IN_PROGRESS]);

        $this->service = app(FileManagementService::class);
    }

    /** @test */
    public function normalizeFilename_removes_extension_and_lowercases()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFilename');
        $method->setAccessible(true);

        $this->assertEquals('kick', $method->invoke($this->service, 'Kick.wav'));
        $this->assertEquals('kick', $method->invoke($this->service, 'KICK.mp3'));
        $this->assertEquals('snare drum', $method->invoke($this->service, 'Snare Drum.wav'));
        $this->assertEquals('hihat', $method->invoke($this->service, '  HiHat  .flac'));
    }

    /** @test */
    public function normalizeFilename_handles_special_characters()
    {
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('normalizeFilename');
        $method->setAccessible(true);

        $this->assertEquals('bass-line_v2', $method->invoke($this->service, 'Bass-Line_v2.wav'));
        $this->assertEquals('808 kick', $method->invoke($this->service, '808 Kick.mp3'));
    }

    /** @test */
    public function matchFilesByName_matches_identical_names()
    {
        $kick = PitchFile::factory()->recycle($this->pitch)->create();
        $kick->update(['original_file_name' => 'Kick.wav']);

        $snare = PitchFile::factory()->recycle($this->pitch)->create();
        $snare->update(['original_file_name' => 'Snare.wav']);

        $hihat = PitchFile::factory()->recycle($this->pitch)->create();
        $hihat->update(['original_file_name' => 'HiHat.wav']);

        $existingFiles = collect([$kick, $snare, $hihat]);

        $uploadedFiles = [
            ['name' => 'kick.wav', 's3_key' => 'test1', 'size' => 1024, 'type' => 'audio/wav'],
            ['name' => 'snare.wav', 's3_key' => 'test2', 'size' => 2048, 'type' => 'audio/wav'],
        ];

        $result = $this->service->matchFilesByName($existingFiles, $uploadedFiles);

        $this->assertCount(2, $result['matched']);
        $this->assertCount(0, $result['unmatched']);
    }

    /** @test */
    public function matchFilesByName_ignores_extensions()
    {
        $kickFile = PitchFile::factory()->recycle($this->pitch)->create();
        $kickFile->update(['original_file_name' => 'Kick.wav']);

        $existingFiles = collect([$kickFile]);

        $uploadedFiles = [
            ['name' => 'Kick.mp3', 's3_key' => 'test1', 'size' => 1024, 'type' => 'audio/mpeg'],
        ];

        $result = $this->service->matchFilesByName($existingFiles, $uploadedFiles);

        $this->assertCount(1, $result['matched']);
        $this->assertArrayHasKey($kickFile->id, $result['matched']);
        $this->assertCount(0, $result['unmatched']);
    }

    /** @test */
    public function matchFilesByName_is_case_insensitive()
    {
        $kickFile = PitchFile::factory()->recycle($this->pitch)->create();
        $kickFile->update(['original_file_name' => 'Kick.wav']);

        $existingFiles = collect([$kickFile]);

        $uploadedFiles = [
            ['name' => 'KICK.wav', 's3_key' => 'test1', 'size' => 1024, 'type' => 'audio/wav'],
        ];

        $result = $this->service->matchFilesByName($existingFiles, $uploadedFiles);

        $this->assertCount(1, $result['matched']);
        $this->assertArrayHasKey($kickFile->id, $result['matched']);
    }

    /** @test */
    public function matchFilesByName_returns_unmatched_files()
    {
        $kickFile = PitchFile::factory()->recycle($this->pitch)->create();
        $kickFile->update(['original_file_name' => 'Kick.wav']);

        $snareFile = PitchFile::factory()->recycle($this->pitch)->create();
        $snareFile->update(['original_file_name' => 'Snare.wav']);

        $existingFiles = collect([$kickFile, $snareFile]);

        $uploadedFiles = [
            ['name' => 'Kick.wav', 's3_key' => 'test1', 'size' => 1024, 'type' => 'audio/wav'],
            ['name' => 'Bass.wav', 's3_key' => 'test2', 'size' => 2048, 'type' => 'audio/wav'],
            ['name' => 'Synth.wav', 's3_key' => 'test3', 'size' => 3072, 'type' => 'audio/wav'],
        ];

        $result = $this->service->matchFilesByName($existingFiles, $uploadedFiles);

        $this->assertCount(1, $result['matched']);
        $this->assertArrayHasKey($kickFile->id, $result['matched']);
        $this->assertCount(2, $result['unmatched']);
        $this->assertEquals('Bass.wav', $result['unmatched'][0]['name']);
        $this->assertEquals('Synth.wav', $result['unmatched'][1]['name']);
    }

    /** @test */
    public function matchFilesByName_handles_empty_existing_files()
    {
        $existingFiles = collect([]);

        $uploadedFiles = [
            ['name' => 'Kick.wav', 's3_key' => 'test1', 'size' => 1024, 'type' => 'audio/wav'],
            ['name' => 'Snare.wav', 's3_key' => 'test2', 'size' => 2048, 'type' => 'audio/wav'],
        ];

        $result = $this->service->matchFilesByName($existingFiles, $uploadedFiles);

        $this->assertCount(0, $result['matched']);
        $this->assertCount(2, $result['unmatched']);
    }

    /** @test */
    public function matchFilesByName_handles_empty_uploaded_files()
    {
        $kick = PitchFile::factory()->recycle($this->pitch)->create();
        $kick->update(['original_file_name' => 'Kick.wav']);

        $snare = PitchFile::factory()->recycle($this->pitch)->create();
        $snare->update(['original_file_name' => 'Snare.wav']);

        $existingFiles = collect([$kick, $snare]);

        $uploadedFiles = [];

        $result = $this->service->matchFilesByName($existingFiles, $uploadedFiles);

        $this->assertCount(0, $result['matched']);
        $this->assertCount(0, $result['unmatched']);
    }
}
