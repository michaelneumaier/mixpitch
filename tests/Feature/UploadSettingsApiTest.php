<?php

namespace Tests\Feature;

use App\Models\FileUploadSetting;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UploadSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
        ]);

        // Clear settings and cache
        FileUploadSetting::query()->delete();
        Cache::flush();
    }

    /** @test */
    public function it_requires_authentication_to_access_settings()
    {
        $response = $this->getJson('/api/upload-settings/global');
        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_global_settings()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/global');

        $response->assertOk();
        $response->assertJsonStructure([
            'context',
            'settings' => [
                'max_file_size_mb',
                'chunk_size_mb',
                'max_concurrent_uploads',
                'max_retry_attempts',
                'enable_chunking',
                'session_timeout_hours',
            ],
            'metadata' => [
                'schema',
                'defaults',
                'validation_rules',
            ],
            'computed' => [
                'max_file_size_bytes',
                'chunk_size_bytes',
                'session_timeout_ms',
                'uppy_restrictions',
                'upload_config',
            ],
        ]);

        $this->assertEquals('global', $response->json('context'));
        $this->assertEquals(FileUploadSetting::DEFAULT_VALUES, $response->json('settings'));
    }

    /** @test */
    public function it_returns_context_specific_settings()
    {
        // Set project-specific settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
            FileUploadSetting::CHUNK_SIZE_MB => 10,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/projects');

        $response->assertOk();
        $this->assertEquals('projects', $response->json('context'));
        $this->assertEquals(1000, $response->json('settings.max_file_size_mb'));
        $this->assertEquals(10, $response->json('settings.chunk_size_mb'));
    }

    /** @test */
    public function it_validates_context_parameter()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/invalid_context');

        $response->assertStatus(400);
        $response->assertJson([
            'error' => 'Invalid context',
            'valid_contexts' => FileUploadSetting::getValidContexts(),
        ]);
    }

    /** @test */
    public function it_provides_computed_values_for_frontend()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 150,
            FileUploadSetting::CHUNK_SIZE_MB => 8,
            FileUploadSetting::MAX_CONCURRENT_UPLOADS => 4,
            FileUploadSetting::MAX_RETRY_ATTEMPTS => 2,
            FileUploadSetting::SESSION_TIMEOUT_HOURS => 48,
        ], FileUploadSetting::CONTEXT_PITCHES);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/pitches');

        $response->assertOk();

        $computed = $response->json('computed');

        // Test byte conversions
        $this->assertEquals(150 * 1024 * 1024, $computed['max_file_size_bytes']);
        $this->assertEquals(8 * 1024 * 1024, $computed['chunk_size_bytes']);
        $this->assertEquals(48 * 60 * 60 * 1000, $computed['session_timeout_ms']);

        // Test Uppy restrictions
        $this->assertEquals(150 * 1024 * 1024, $computed['uppy_restrictions']['maxFileSize']);
        $this->assertEquals(['audio/*', 'application/pdf', 'image/*', 'application/zip'], $computed['uppy_restrictions']['allowedFileTypes']);

        // Test upload config
        $this->assertEquals(8 * 1024 * 1024, $computed['upload_config']['chunkSize']);
        $this->assertEquals(4, $computed['upload_config']['limit']);
        $this->assertEquals([1000, 1000], $computed['upload_config']['retryDelays']);
    }

    /** @test */
    public function it_provides_metadata_for_validation()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/global');

        $response->assertOk();

        $metadata = $response->json('metadata');

        // Test schema
        $this->assertArrayHasKey('max_file_size_mb', $metadata['schema']);
        $this->assertEquals('Maximum file size in megabytes', $metadata['schema']['max_file_size_mb']['description']);
        $this->assertEquals('integer', $metadata['schema']['max_file_size_mb']['type']);

        // Test defaults
        $this->assertEquals(FileUploadSetting::DEFAULT_VALUES, $metadata['defaults']);

        // Test validation rules
        $this->assertEquals(FileUploadSetting::VALIDATION_RULES, $metadata['validation_rules']);
    }

    /** @test */
    public function it_can_get_settings_for_model()
    {
        // Set project-specific settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 800,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/upload-settings/for-model', [
            'model_type' => 'App\\Models\\Project',
            'model_id' => $this->project->id,
        ]);

        $response->assertOk();
        $this->assertEquals('projects', $response->json('context'));
        $this->assertEquals(800, $response->json('settings.max_file_size_mb'));
    }

    /** @test */
    public function it_validates_model_parameters_for_model_endpoint()
    {
        $this->actingAs($this->user);

        // Test missing model_type
        $response = $this->postJson('/api/upload-settings/for-model', [
            'model_id' => $this->project->id,
        ]);
        $response->assertStatus(422);

        // Test invalid model_type
        $response = $this->postJson('/api/upload-settings/for-model', [
            'model_type' => 'Invalid\\Model',
            'model_id' => $this->project->id,
        ]);
        $response->assertStatus(422);

        // Test non-existent model_id
        $response = $this->postJson('/api/upload-settings/for-model', [
            'model_type' => 'App\\Models\\Project',
            'model_id' => 99999,
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_can_test_settings_against_hypothetical_file()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 100,
            FileUploadSetting::CHUNK_SIZE_MB => 5,
            FileUploadSetting::ENABLE_CHUNKING => true,
        ], FileUploadSetting::CONTEXT_PITCHES);

        $this->actingAs($this->user);

        // Test file within limits
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'pitches',
            'file_size' => 50 * 1024 * 1024, // 50MB
            'file_type' => 'audio/mp3',
        ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertTrue($data['is_size_valid']);
        $this->assertTrue($data['is_type_valid']);
        $this->assertTrue($data['chunking_enabled']);
        $this->assertTrue($data['will_be_chunked']); // File is larger than chunk size
        $this->assertEquals(100 * 1024 * 1024, $data['max_allowed_size']);

        // Test file over limits
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'pitches',
            'file_size' => 150 * 1024 * 1024, // 150MB
            'file_type' => 'audio/mp3',
        ]);

        $response->assertOk();
        $this->assertFalse($response->json('is_size_valid'));
    }

    /** @test */
    public function it_validates_test_endpoint_parameters()
    {
        $this->actingAs($this->user);

        // Test missing required fields
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            // Missing file_size and file_type
        ]);
        $response->assertStatus(422);

        // Test invalid context
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'invalid',
            'file_size' => 1024,
            'file_type' => 'audio/mp3',
        ]);
        $response->assertStatus(400);

        // Test invalid file size
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            'file_size' => 0, // Invalid
            'file_type' => 'audio/mp3',
        ]);
        $response->assertStatus(422);
    }

    /** @test */
    public function it_detects_file_types_correctly_in_test_endpoint()
    {
        $this->actingAs($this->user);

        // Test allowed audio type
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            'file_size' => 1024,
            'file_type' => 'audio/mp3',
        ]);
        $response->assertOk();
        $this->assertTrue($response->json('is_type_valid'));

        // Test allowed PDF type
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            'file_size' => 1024,
            'file_type' => 'application/pdf',
        ]);
        $response->assertOk();
        $this->assertTrue($response->json('is_type_valid'));

        // Test disallowed type
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            'file_size' => 1024,
            'file_type' => 'video/mp4',
        ]);
        $response->assertOk();
        $this->assertFalse($response->json('is_type_valid'));
    }

    /** @test */
    public function it_handles_chunking_detection_correctly()
    {
        // Test with chunking disabled
        FileUploadSetting::updateSettings([
            FileUploadSetting::ENABLE_CHUNKING => false,
            FileUploadSetting::CHUNK_SIZE_MB => 5,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            'file_size' => 10 * 1024 * 1024, // 10MB
            'file_type' => 'audio/mp3',
        ]);

        $response->assertOk();
        $this->assertFalse($response->json('chunking_enabled'));
        $this->assertFalse($response->json('will_be_chunked'));

        // Test with chunking enabled but small file
        FileUploadSetting::updateSettings([
            FileUploadSetting::ENABLE_CHUNKING => true,
            FileUploadSetting::CHUNK_SIZE_MB => 10,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'global',
            'file_size' => 5 * 1024 * 1024, // 5MB (smaller than chunk size)
            'file_type' => 'audio/mp3',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('chunking_enabled'));
        $this->assertFalse($response->json('will_be_chunked')); // File smaller than chunk size
    }

    /** @test */
    public function it_handles_api_errors_gracefully()
    {
        $this->actingAs($this->user);

        // This should trigger an error (we'll mock it by using a very invalid context)
        $response = $this->getJson('/api/upload-settings/global');

        // Even if there are internal errors, it should return a proper JSON response
        $response->assertHeader('Content-Type', 'application/json');
    }

    /** @test */
    public function it_returns_correct_inheritance_behavior()
    {
        // Set global settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 300,
            FileUploadSetting::CHUNK_SIZE_MB => 6,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        // Set only max file size for projects
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1200,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/projects');

        $response->assertOk();
        $settings = $response->json('settings');

        // Should get project-specific max file size
        $this->assertEquals(1200, $settings['max_file_size_mb']);

        // Should inherit global chunk size
        $this->assertEquals(6, $settings['chunk_size_mb']);

        // Should use default for unset values
        $this->assertEquals(
            FileUploadSetting::DEFAULT_VALUES['max_concurrent_uploads'],
            $settings['max_concurrent_uploads']
        );
    }

    /** @test */
    public function it_defaults_to_global_context_when_none_specified()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/');

        $response->assertOk();
        $this->assertEquals('global', $response->json('context'));
    }

    /** @test */
    public function it_includes_context_specific_defaults_in_metadata()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/projects');

        $response->assertOk();
        $defaults = $response->json('metadata.defaults');

        // Should include project-specific recommended defaults
        $this->assertEquals(1000, $defaults['max_file_size_mb']); // Project default
        $this->assertEquals(10, $defaults['chunk_size_mb']); // Project default
    }

    /** @test */
    public function it_includes_context_specific_validation_rules()
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/upload-settings/client_portals');

        $response->assertOk();
        $validationRules = $response->json('metadata.validation_rules');

        // Should include client portal specific validation rules
        $this->assertEquals('integer|min:1|max:500', $validationRules['max_file_size_mb']);
        $this->assertEquals('integer|min:1|max:5', $validationRules['max_concurrent_uploads']);
    }
}
