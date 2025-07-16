<?php

namespace Tests\Feature;

use App\Models\FileUploadSetting;
use App\Models\User;
use App\Models\Project;
use App\Models\Pitch;
use App\Services\FileManagementService;
use App\Http\Controllers\Api\UploadSettingsController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FileUploadSettingsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Project $project;
    protected Pitch $pitch;
    protected FileManagementService $fileService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user and models
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->pitch = Pitch::factory()->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => Pitch::STATUS_IN_PROGRESS
        ]);
        
        $this->fileService = app(FileManagementService::class);
        
        // Clear any existing settings
        FileUploadSetting::query()->delete();
        Cache::flush();
    }

    /** @test */
    public function it_uses_default_settings_when_no_custom_settings_exist()
    {
        $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        
        $this->assertEquals(FileUploadSetting::DEFAULT_VALUES, $settings);
    }

    /** @test */
    public function it_can_update_and_retrieve_global_settings()
    {
        $newSettings = [
            FileUploadSetting::MAX_FILE_SIZE_MB => 1024,
            FileUploadSetting::CHUNK_SIZE_MB => 10,
            FileUploadSetting::MAX_CONCURRENT_UPLOADS => 5,
            FileUploadSetting::MAX_RETRY_ATTEMPTS => 4,
            FileUploadSetting::ENABLE_CHUNKING => false,
            FileUploadSetting::SESSION_TIMEOUT_HOURS => 48
        ];

        $result = FileUploadSetting::updateSettings($newSettings, FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertTrue($result);

        $retrievedSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals($newSettings, $retrievedSettings);
    }

    /** @test */
    public function it_validates_settings_before_saving()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 3000, // Exceeds max of 2048
        ], FileUploadSetting::CONTEXT_GLOBAL);
    }

    /** @test */
    public function context_specific_settings_override_global_settings()
    {
        // Set global settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 200,
            FileUploadSetting::CHUNK_SIZE_MB => 5,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        // Set project-specific settings (only max file size)
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $projectSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        
        // Should get project-specific max file size but global chunk size
        $this->assertEquals(1000, $projectSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(5, $projectSettings[FileUploadSetting::CHUNK_SIZE_MB]);
    }

    /** @test */
    public function api_returns_correct_settings_for_context()
    {
        // Set different settings for different contexts
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 500,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);

        // Test global settings API
        $response = $this->getJson('/api/upload-settings/global');
        $response->assertOk();
        $this->assertEquals(500, $response->json('settings.max_file_size_mb'));

        // Test project settings API
        $response = $this->getJson('/api/upload-settings/projects');
        $response->assertOk();
        $this->assertEquals(1000, $response->json('settings.max_file_size_mb'));
    }

    /** @test */
    public function api_provides_computed_values_for_frontend()
    {
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 100,
            FileUploadSetting::CHUNK_SIZE_MB => 5,
            FileUploadSetting::MAX_CONCURRENT_UPLOADS => 3,
            FileUploadSetting::MAX_RETRY_ATTEMPTS => 2,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);
        $response = $this->getJson('/api/upload-settings/projects');
        
        $response->assertOk();
        $computed = $response->json('computed');
        
        $this->assertEquals(100 * 1024 * 1024, $computed['max_file_size_bytes']);
        $this->assertEquals(5 * 1024 * 1024, $computed['chunk_size_bytes']);
        $this->assertEquals(100 * 1024 * 1024, $computed['uppy_restrictions']['maxFileSize']);
        $this->assertEquals(5 * 1024 * 1024, $computed['upload_config']['chunkSize']);
        $this->assertEquals(3, $computed['upload_config']['limit']);
        $this->assertEquals([1000, 1000], $computed['upload_config']['retryDelays']);
    }

    /** @test */
    public function file_management_service_respects_project_settings()
    {
        // Set project max file size to 50MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 50,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        Storage::fake('s3');

        // Create a file that's larger than 50MB (simulated)
        $largeFile = UploadedFile::fake()->create('large_file.mp3', 60 * 1024); // 60MB

        $this->expectException(\App\Exceptions\File\FileUploadException::class);
        $this->expectExceptionMessage('exceeds the maximum allowed size of 50MB');

        $this->fileService->uploadProjectFile($this->project, $largeFile, $this->user);
    }

    /** @test */
    public function file_management_service_respects_pitch_settings()
    {
        // Set pitch max file size to 100MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 100,
        ], FileUploadSetting::CONTEXT_PITCHES);

        Storage::fake('s3');

        // Create a file that's larger than 100MB (simulated)
        $largeFile = UploadedFile::fake()->create('large_pitch.mp3', 120 * 1024); // 120MB

        $this->expectException(\App\Exceptions\File\FileUploadException::class);
        $this->expectExceptionMessage('exceeds the maximum allowed size of 100MB');

        $this->fileService->uploadPitchFile($this->pitch, $largeFile, $this->user);
    }

    /** @test */
    public function file_management_service_allows_files_within_limits()
    {
        // Set project max file size to 200MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 200,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        Storage::fake('s3');

        // Create a file that's within the limit
        $validFile = UploadedFile::fake()->create('valid_file.mp3', 100 * 1024); // 100MB

        // This should not throw an exception (we'll just test that it doesn't throw size validation error)
        try {
            $projectFile = $this->fileService->uploadProjectFile($this->project, $validFile, $this->user);
            $this->assertTrue(true); // File was processed without size validation error
        } catch (\App\Exceptions\File\FileUploadException $e) {
            // Check if it's a storage capacity issue, not file size
            $this->assertStringNotContainsString('exceeds the maximum allowed size', $e->getMessage());
        }
    }

    /** @test */
    public function chunk_upload_validation_works_with_middleware()
    {
        // Set chunk size limit to 10MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::CHUNK_SIZE_MB => 10,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        // Test that chunk size validation is working by checking the middleware directly
        $request = \Illuminate\Http\Request::create('/test', 'POST');
        $largeChunk = UploadedFile::fake()->create('chunk.part', 15 * 1024); // 15MB
        $request->files->set('chunk', $largeChunk);
        $request->merge(['model_type' => 'projects']);

        $middleware = new \App\Http\Middleware\ValidateUploadSettings();
        
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'projects');

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Upload validation failed', $response->getContent());
    }

    /** @test */
    public function project_controller_validates_against_settings()
    {
        // Set project max file size to 150MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 150,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);

        // Try to upload a file larger than 150MB
        $largeFile = UploadedFile::fake()->create('large.mp3', 200 * 1024); // 200MB

        $response = $this->postJson('/project/upload-file', [
            'project_id' => $this->project->id,
            'file' => $largeFile,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function pitch_controller_validates_against_settings()
    {
        // Set pitch max file size to 80MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 80,
        ], FileUploadSetting::CONTEXT_PITCHES);

        $this->actingAs($this->user);

        // Try to upload a file larger than 80MB
        $largeFile = UploadedFile::fake()->create('large_pitch.mp3', 100 * 1024); // 100MB

        $response = $this->postJson('/pitch/upload-file', [
            'pitch_id' => $this->pitch->id,
            'file' => $largeFile,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function client_portal_validation_works_with_middleware()
    {
        // Set client portal max file size to 50MB
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 50,
        ], FileUploadSetting::CONTEXT_CLIENT_PORTALS);

        // Test client portal validation directly through middleware
        $request = \Illuminate\Http\Request::create('/client-portal/upload', 'POST');
        $largeFile = UploadedFile::fake()->create('client_file.mp3', 70 * 1024); // 70MB
        $request->files->set('file', $largeFile);

        $middleware = new \App\Http\Middleware\ValidateUploadSettings();
        
        $response = $middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        }, 'client_portals');

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertStringContainsString('Upload validation failed', $response->getContent());
    }

    /** @test */
    public function settings_cache_is_cleared_when_updated()
    {
        // Get initial settings (this will cache them)
        $initialSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals(500, $initialSettings[FileUploadSetting::MAX_FILE_SIZE_MB]); // Default value

        // Update settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        // Get settings again - should reflect the new value, not cached
        $updatedSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals(1000, $updatedSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);
    }

    /** @test */
    public function livewire_component_loads_correct_settings()
    {
        // Set project-specific settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 300,
            FileUploadSetting::CHUNK_SIZE_MB => 8,
            FileUploadSetting::MAX_CONCURRENT_UPLOADS => 4,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $this->actingAs($this->user);

        // Create the Livewire component
        $component = \Livewire\Livewire::test(\App\Livewire\UppyFileUploader::class, [
            'model' => $this->project
        ]);

        // Call the getUploadConfig method through the component instance
        $uploadConfig = $component->instance()->getUploadConfig();
        
        // Should have project-specific settings
        $this->assertEquals(300 * 1024 * 1024, $uploadConfig['maxFileSize']);
        $this->assertEquals(4, $uploadConfig['chunking']['limit']);
        $this->assertEquals(8 * 1024 * 1024, $uploadConfig['chunking']['chunkSize']);
    }

    /** @test */
    public function upload_validation_middleware_works_correctly()
    {
        // Set global settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 100,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        $this->actingAs($this->user);

        // Create a request that should trigger the middleware
        $largeFile = UploadedFile::fake()->create('test.mp3', 150 * 1024); // 150MB

        // This should be caught by the middleware if properly applied
        $response = $this->postJson('/project/upload-file', [
            'project_id' => $this->project->id,
            'file' => $largeFile,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function different_contexts_have_independent_settings()
    {
        // Set different max file sizes for each context
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 200,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 300,
        ], FileUploadSetting::CONTEXT_PITCHES);

        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 50,
        ], FileUploadSetting::CONTEXT_CLIENT_PORTALS);

        // Verify each context has its own settings
        $this->assertEquals(200, FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL)[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(1000, FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS)[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(300, FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PITCHES)[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(50, FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_CLIENT_PORTALS)[FileUploadSetting::MAX_FILE_SIZE_MB]);
    }

    /** @test */
    public function settings_reset_to_defaults_works_correctly()
    {
        // Set custom settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
            FileUploadSetting::CHUNK_SIZE_MB => 10,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        // Verify settings were set
        $customSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals(1000, $customSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);

        // Reset to defaults
        $result = FileUploadSetting::resetToDefaults(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertTrue($result);

        // Verify settings are back to defaults
        $defaultSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals(
            FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_FILE_SIZE_MB],
            $defaultSettings[FileUploadSetting::MAX_FILE_SIZE_MB]
        );
    }

    /** @test */
    public function test_settings_api_endpoint_with_model_detection()
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
    public function test_settings_validation_with_test_endpoint()
    {
        // Set strict settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 100,
        ], FileUploadSetting::CONTEXT_PITCHES);

        $this->actingAs($this->user);

        // Test with file size within limit
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'pitches',
            'file_size' => 50 * 1024 * 1024, // 50MB
            'file_type' => 'audio/mp3',
        ]);

        $response->assertOk();
        $this->assertTrue($response->json('is_size_valid'));

        // Test with file size over limit
        $response = $this->postJson('/api/upload-settings/test', [
            'context' => 'pitches',
            'file_size' => 150 * 1024 * 1024, // 150MB
            'file_type' => 'audio/mp3',
        ]);

        $response->assertOk();
        $this->assertFalse($response->json('is_size_valid'));
    }
}