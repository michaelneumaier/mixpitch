<?php

namespace Tests\Feature;

use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\SubscriptionLimit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PresignedUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a subscription limit for testing
        SubscriptionLimit::create([
            'plan_name' => 'basic',
            'plan_tier' => 'monthly',
            'total_user_storage_gb' => 10,
            'max_projects_owned' => 10,
            'max_active_pitches' => 10,
            'max_monthly_pitches' => 10,
            'storage_per_project_gb' => 2,
            'platform_commission_rate' => 10.0,
            'reputation_multiplier' => 1.0,
        ]);

        $this->user = User::factory()->create([
            'subscription_plan' => 'basic',
            'subscription_tier' => 'monthly',
        ]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'in_progress', // Set a valid status for upload
        ]);

        // Mock S3 storage
        Storage::fake('s3');
    }

    /** @test */
    public function it_generates_presigned_url_for_project_upload()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            'filename' => 'test-audio.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024, // 1MB
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'presigned_url',
                    's3_key',
                    'expires_at',
                    'upload_method',
                    'headers',
                    'context',
                    'filename',
                    'file_size',
                    'mime_type',
                    'metadata',
                ],
            ]);

        $data = $response->json('data');
        $this->assertEquals('PUT', $data['upload_method']);
        $this->assertEquals(FileUploadSetting::CONTEXT_PROJECTS, $data['context']);
        $this->assertEquals('test-audio.mp3', $data['filename']);
        $this->assertStringContainsString('projects/'.$this->project->id, $data['s3_key']);
    }

    /** @test */
    public function it_generates_presigned_url_for_pitch_upload()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PITCHES,
            'filename' => 'pitch-audio.wav',
            'mime_type' => 'audio/wav',
            'file_size' => 2 * 1024 * 1024, // 2MB
            'metadata' => [
                'pitch_id' => $this->pitch->id,
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(FileUploadSetting::CONTEXT_PITCHES, $data['context']);
        $this->assertStringContainsString('pitches/'.$this->pitch->id, $data['s3_key']);
    }

    /** @test */
    public function it_validates_file_size_against_context_settings()
    {
        Sanctum::actingAs($this->user);

        // Set project file size limit to 50MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 50,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        // Try to upload a 100MB file
        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            'filename' => 'large-file.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 100 * 1024 * 1024, // 100MB
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'error' => 'Upload validation failed',
            ]);
    }

    /** @test */
    public function it_validates_file_type()
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            'filename' => 'malicious.exe',
            'mime_type' => 'application/x-executable',
            'file_size' => 1024 * 1024,
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'error',
                'errors' => [
                    'mime_type',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_authenticated_contexts()
    {
        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            'filename' => 'test.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024,
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_project_access_permissions()
    {
        $otherUser = User::factory()->create();
        $otherProject = Project::factory()->create(['user_id' => $otherUser->id]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            'filename' => 'test.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024,
            'metadata' => [
                'project_id' => $otherProject->id,
            ],
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_completes_upload_and_creates_project_file_record()
    {
        Sanctum::actingAs($this->user);

        $s3Key = 'projects/'.$this->project->id.'/01HXXX123456789/test-audio.mp3';

        // Mock that the file exists in S3
        Storage::disk('s3')->put($s3Key, 'fake audio content');

        $response = $this->postJson('/api/presigned-upload/complete', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            's3_key' => $s3Key,
            'filename' => 'test-audio.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024,
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'file_id',
                    'filename',
                    'size',
                    'mime_type',
                    'context',
                    'created_at',
                ],
            ]);

        // Verify file record was created
        $this->assertDatabaseHas('project_files', [
            'project_id' => $this->project->id,
            'storage_path' => $s3Key,
            'file_name' => 'test-audio.mp3',
            'mime_type' => 'audio/mpeg',
            'size' => 1024 * 1024,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_completes_upload_and_creates_pitch_file_record()
    {
        Sanctum::actingAs($this->user);

        $s3Key = 'pitches/'.$this->pitch->id.'/01HXXX123456789/pitch-audio.wav';

        // Mock that the file exists in S3
        Storage::disk('s3')->put($s3Key, 'fake audio content');

        $response = $this->postJson('/api/presigned-upload/complete', [
            'context' => FileUploadSetting::CONTEXT_PITCHES,
            's3_key' => $s3Key,
            'filename' => 'pitch-audio.wav',
            'mime_type' => 'audio/wav',
            'file_size' => 2 * 1024 * 1024,
            'metadata' => [
                'pitch_id' => $this->pitch->id,
            ],
        ]);

        $response->assertStatus(200);

        // Verify file record was created
        $this->assertDatabaseHas('pitch_files', [
            'pitch_id' => $this->pitch->id,
            'storage_path' => $s3Key,
            'file_name' => 'pitch-audio.wav',
            'mime_type' => 'audio/wav',
            'size' => 2 * 1024 * 1024,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_fails_completion_when_file_does_not_exist_in_s3()
    {
        Sanctum::actingAs($this->user);

        $s3Key = 'projects/'.$this->project->id.'/01HXXX123456789/nonexistent.mp3';

        $response = $this->postJson('/api/presigned-upload/complete', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            's3_key' => $s3Key,
            'filename' => 'nonexistent.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024,
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error', 'File not found in storage');
    }

    /** @test */
    public function it_validates_required_metadata_for_context()
    {
        Sanctum::actingAs($this->user);

        // Try to complete project upload without project_id
        $response = $this->postJson('/api/presigned-upload/complete', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            's3_key' => 'projects/test.mp3',
            'filename' => 'test.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024,
            'metadata' => [],
        ]);

        $response->assertStatus(404)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function it_updates_user_storage_usage_on_completion()
    {
        Sanctum::actingAs($this->user);

        $s3Key = 'projects/'.$this->project->id.'/01HXXX123456789/test-audio.mp3';
        $fileSize = 5 * 1024 * 1024; // 5MB

        // Mock that the file exists in S3
        Storage::disk('s3')->put($s3Key, 'fake audio content');

        // Get initial storage usage
        $initialUsage = $this->user->total_storage_used ?? 0;

        $response = $this->postJson('/api/presigned-upload/complete', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            's3_key' => $s3Key,
            'filename' => 'test-audio.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => $fileSize,
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(200);

        // Verify storage usage was updated
        $this->user->refresh();
        $this->assertEquals($initialUsage + $fileSize, $this->user->total_storage_used);
    }

    /** @test */
    public function it_applies_upload_settings_validation_middleware()
    {
        Sanctum::actingAs($this->user);

        // This should trigger the upload validation middleware
        $response = $this->postJson('/api/presigned-upload/generate', [
            'context' => FileUploadSetting::CONTEXT_PROJECTS,
            'filename' => 'test.mp3',
            'mime_type' => 'audio/mpeg',
            'file_size' => 1024 * 1024,
            'metadata' => [
                'project_id' => $this->project->id,
            ],
        ]);

        $response->assertStatus(200);

        // The middleware should have added settings to the request
        // This is implicitly tested by the successful response
    }
}
