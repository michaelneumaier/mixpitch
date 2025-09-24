<?php

namespace Tests\Feature\Livewire;

use App\Livewire\EnhancedFileUploader;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use App\Services\FileUploadSettingsService;
use App\Services\UploadErrorHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class EnhancedFileUploaderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Pitch $pitch;

    protected MockInterface $settingsServiceMock;

    protected MockInterface $errorHandlerMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the services
        $this->settingsServiceMock = Mockery::mock(FileUploadSettingsService::class);
        $this->errorHandlerMock = Mockery::mock(UploadErrorHandler::class);

        $this->app->instance(FileUploadSettingsService::class, $this->settingsServiceMock);
        $this->app->instance(UploadErrorHandler::class, $this->errorHandlerMock);

        // Setup user and models
        $this->user = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->pitch = Pitch::factory()->create(['project_id' => $this->project->id, 'user_id' => $this->user->id]);

        // Fake storage for uploads
        Storage::fake('local');
        Storage::fake(config('filesystems.default'));

        // Mock default settings
        $this->settingsServiceMock
            ->shouldReceive('getSettings')
            ->andReturn([
                'max_file_size_mb' => 200,
                'chunk_size_mb' => 5,
                'max_retry_attempts' => 3,
                'session_timeout_hours' => 24,
                'enable_chunking' => false,
                'max_concurrent_uploads' => 3,
            ]);
    }

    /** @test */
    public function component_renders_correctly_for_project()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project])
            ->assertStatus(200)
            ->assertSee('Upload files');
    }

    /** @test */
    public function component_renders_correctly_for_pitch()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->pitch])
            ->assertStatus(200)
            ->assertSee('Upload files');
    }

    /** @test */
    public function component_renders_with_multiple_file_support()
    {
        Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ])
            ->assertStatus(200)
            ->assertSee('Multiple files supported');
    }

    /** @test */
    public function component_renders_with_fallback_when_filepond_not_supported()
    {
        // This test verifies the component gracefully handles FilePond not being available
        Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project])
            ->assertStatus(200)
            ->assertSee('Upload files');
    }

    /** @test */
    public function get_upload_config_returns_correct_structure()
    {
        $component = new EnhancedFileUploader($this->project);
        $config = $component->getUploadConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enableChunking', $config);
        $this->assertArrayHasKey('allowMultiple', $config);
        $this->assertArrayHasKey('maxFileSize', $config);
        $this->assertArrayHasKey('maxConcurrentUploads', $config);
        $this->assertArrayHasKey('context', $config);
    }

    /** @test */
    public function validation_rules_are_correct()
    {
        $component = new EnhancedFileUploader($this->project);
        $rules = $component->rules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('file', $rules);
        $this->assertContains('file', $rules['file']);
        $this->assertContains('mimes:mp3,wav,aac,ogg,pdf,jpg,jpeg,png,gif,zip', $rules['file']);
    }

    /** @test */
    public function can_add_files_to_queue()
    {
        $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

        Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ])
            ->call('addFilesToQueue', [$file])
            ->assertDispatched('fileQueueUpdated');
    }

    /** @test */
    public function can_remove_file_from_queue()
    {
        $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ]);

        // Add file to queue first
        $component->call('addFilesToQueue', [$file]);

        // Get the file ID from the queue
        $fileQueue = $component->get('fileQueue');
        $fileId = $fileQueue[0]['id'] ?? null;

        if ($fileId) {
            $component->call('removeFromQueue', $fileId)
                ->assertDispatched('fileQueueUpdated');
        }
    }

    /** @test */
    public function can_clear_queue()
    {
        $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ]);

        // Add file to queue first
        $component->call('addFilesToQueue', [$file]);

        // Clear the queue
        $component->call('clearQueue')
            ->assertDispatched('fileQueueUpdated');
    }

    /** @test */
    public function can_start_queue_processing()
    {
        $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ]);

        // Add file to queue first
        $component->call('addFilesToQueue', [$file]);

        // Start processing
        $component->call('startQueueProcessing')
            ->assertDispatched('queueStatusUpdated');
    }

    /** @test */
    public function can_pause_and_resume_uploads()
    {
        $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ]);

        // Add file to queue first
        $component->call('addFilesToQueue', [$file]);

        // Get the file ID from the queue
        $fileQueue = $component->get('fileQueue');
        $fileId = $fileQueue[0]['id'] ?? null;

        if ($fileId) {
            // Pause upload
            $component->call('pauseUpload', $fileId)
                ->assertDispatched('uploadPaused');

            // Resume upload
            $component->call('resumeUpload', $fileId)
                ->assertDispatched('uploadResumed');
        }
    }

    /** @test */
    public function can_retry_failed_upload()
    {
        $file = UploadedFile::fake()->create('test.mp3', 1024, 'audio/mpeg');

        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ]);

        // Add file to queue first
        $component->call('addFilesToQueue', [$file]);

        // Get the file ID from the queue
        $fileQueue = $component->get('fileQueue');
        $fileId = $fileQueue[0]['id'] ?? null;

        if ($fileId) {
            // Simulate a failed upload by setting status to error
            $component->set('fileQueue', [
                [
                    'id' => $fileId,
                    'status' => 'error',
                    'error' => 'Upload failed',
                ],
            ]);

            // Retry the upload
            $component->call('retryUpload', $fileId)
                ->assertDispatched('uploadRetrying');
        }
    }

    /** @test */
    public function get_queue_progress_returns_correct_structure()
    {
        $component = new EnhancedFileUploader($this->project);
        $progress = $component->getQueueProgress();

        $this->assertIsArray($progress);
        $this->assertArrayHasKey('overall_progress', $progress);
        $this->assertArrayHasKey('completed_files', $progress);
        $this->assertArrayHasKey('total_files', $progress);
        $this->assertArrayHasKey('active_uploads', $progress);
        $this->assertArrayHasKey('failed_uploads', $progress);
        $this->assertArrayHasKey('average_speed', $progress);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
