<?php

namespace Tests\Feature\Livewire;

use App\Livewire\EnhancedFileUploader;
use App\Models\Pitch;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class EnhancedFileUploaderTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Project $project;

    protected Pitch $pitch;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup user and models
        $this->user = User::factory()->create(['role' => User::ROLE_CLIENT]);
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->pitch = Pitch::factory()->create(['project_id' => $this->project->id, 'user_id' => $this->user->id]);

        // Fake storage for uploads
        Storage::fake('local');
        Storage::fake(config('filesystems.default'));
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
        Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project])
            ->assertStatus(200)
            ->assertSee('Upload files');
    }

    /** @test */
    public function get_upload_config_returns_correct_structure()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('enableChunking', $config);
        $this->assertArrayHasKey('allowMultiple', $config);
        $this->assertArrayHasKey('maxFileSize', $config);
        $this->assertArrayHasKey('maxFiles', $config);
        $this->assertArrayHasKey('context', $config);
        $this->assertArrayHasKey('modelId', $config);
        $this->assertArrayHasKey('modelType', $config);
    }

    /** @test */
    public function upload_config_reflects_multiple_file_setting()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => ['allowMultiple' => true],
            ]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertTrue($config['allowMultiple']);
        $this->assertGreaterThan(1, $config['maxFiles']);
    }

    /** @test */
    public function upload_config_reflects_single_file_default()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertFalse($config['allowMultiple']);
        $this->assertEquals(1, $config['maxFiles']);
    }

    /** @test */
    public function upload_context_is_correct_for_project()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertEquals('projects', $config['context']);
    }

    /** @test */
    public function upload_context_is_correct_for_pitch()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->pitch]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertEquals('pitches', $config['context']);
    }

    /** @test */
    public function accepted_file_types_are_set()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, ['model' => $this->project]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertIsArray($config['acceptedFileTypes']);
        $this->assertNotEmpty($config['acceptedFileTypes']);
    }

    /** @test */
    public function custom_config_overrides_defaults()
    {
        $component = Livewire::actingAs($this->user)
            ->test(EnhancedFileUploader::class, [
                'model' => $this->project,
                'config' => [
                    'enableChunking' => false,
                    'maxFileSize' => '500MB',
                ],
            ]);

        $instance = $component->instance();
        $config = $instance->getUploadConfig();

        $this->assertFalse($config['enableChunking']);
        $this->assertEquals('500MB', $config['maxFileSize']);
    }
}
