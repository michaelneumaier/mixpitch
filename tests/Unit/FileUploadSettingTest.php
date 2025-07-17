<?php

namespace Tests\Unit;

use App\Models\FileUploadSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FileUploadSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear any existing settings and cache
        FileUploadSetting::query()->delete();
        Cache::flush();
    }

    /** @test */
    public function it_has_correct_default_values()
    {
        $defaults = FileUploadSetting::DEFAULT_VALUES;

        $this->assertEquals(500, $defaults[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(5, $defaults[FileUploadSetting::CHUNK_SIZE_MB]);
        $this->assertEquals(3, $defaults[FileUploadSetting::MAX_CONCURRENT_UPLOADS]);
        $this->assertEquals(3, $defaults[FileUploadSetting::MAX_RETRY_ATTEMPTS]);
        $this->assertTrue($defaults[FileUploadSetting::ENABLE_CHUNKING]);
        $this->assertEquals(24, $defaults[FileUploadSetting::SESSION_TIMEOUT_HOURS]);
    }

    /** @test */
    public function it_has_correct_validation_rules()
    {
        $rules = FileUploadSetting::VALIDATION_RULES;

        $this->assertEquals('integer|min:1|max:2048', $rules[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals('integer|min:1|max:50', $rules[FileUploadSetting::CHUNK_SIZE_MB]);
        $this->assertEquals('integer|min:1|max:10', $rules[FileUploadSetting::MAX_CONCURRENT_UPLOADS]);
        $this->assertEquals('integer|min:1|max:5', $rules[FileUploadSetting::MAX_RETRY_ATTEMPTS]);
        $this->assertEquals('boolean', $rules[FileUploadSetting::ENABLE_CHUNKING]);
        $this->assertEquals('integer|min:1|max:168', $rules[FileUploadSetting::SESSION_TIMEOUT_HOURS]);
    }

    /** @test */
    public function it_validates_context_correctly()
    {
        $this->assertTrue(FileUploadSetting::validateContext(FileUploadSetting::CONTEXT_GLOBAL));
        $this->assertTrue(FileUploadSetting::validateContext(FileUploadSetting::CONTEXT_PROJECTS));
        $this->assertTrue(FileUploadSetting::validateContext(FileUploadSetting::CONTEXT_PITCHES));
        $this->assertTrue(FileUploadSetting::validateContext(FileUploadSetting::CONTEXT_CLIENT_PORTALS));

        $this->assertFalse(FileUploadSetting::validateContext('invalid_context'));
    }

    /** @test */
    public function it_validates_settings_correctly()
    {
        // Valid settings should pass
        FileUploadSetting::validateSetting(FileUploadSetting::MAX_FILE_SIZE_MB, 1000);
        FileUploadSetting::validateSetting(FileUploadSetting::CHUNK_SIZE_MB, 10);
        FileUploadSetting::validateSetting(FileUploadSetting::ENABLE_CHUNKING, true);

        // This assertion passes if no exception is thrown
        $this->assertTrue(true);
    }

    /** @test */
    public function it_rejects_invalid_settings()
    {
        $this->expectException(ValidationException::class);
        FileUploadSetting::validateSetting(FileUploadSetting::MAX_FILE_SIZE_MB, 3000); // Too large
    }

    /** @test */
    public function it_rejects_invalid_setting_keys()
    {
        $this->expectException(\InvalidArgumentException::class);
        FileUploadSetting::validateSetting('invalid_key', 100);
    }

    /** @test */
    public function it_can_get_context_specific_defaults()
    {
        $projectDefaults = FileUploadSetting::getContextDefaults(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals(1000, $projectDefaults[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(10, $projectDefaults[FileUploadSetting::CHUNK_SIZE_MB]);

        $pitchDefaults = FileUploadSetting::getContextDefaults(FileUploadSetting::CONTEXT_PITCHES);
        $this->assertEquals(200, $pitchDefaults[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(5, $pitchDefaults[FileUploadSetting::CHUNK_SIZE_MB]);

        $clientDefaults = FileUploadSetting::getContextDefaults(FileUploadSetting::CONTEXT_CLIENT_PORTALS);
        $this->assertEquals(100, $clientDefaults[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(2, $clientDefaults[FileUploadSetting::MAX_CONCURRENT_UPLOADS]);
    }

    /** @test */
    public function it_can_get_context_specific_validation_rules()
    {
        $projectRules = FileUploadSetting::getContextValidationRules(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals('integer|min:1|max:2048', $projectRules[FileUploadSetting::MAX_FILE_SIZE_MB]);

        $pitchRules = FileUploadSetting::getContextValidationRules(FileUploadSetting::CONTEXT_PITCHES);
        $this->assertEquals('integer|min:1|max:1024', $pitchRules[FileUploadSetting::MAX_FILE_SIZE_MB]);

        $clientRules = FileUploadSetting::getContextValidationRules(FileUploadSetting::CONTEXT_CLIENT_PORTALS);
        $this->assertEquals('integer|min:1|max:500', $clientRules[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals('integer|min:1|max:5', $clientRules[FileUploadSetting::MAX_CONCURRENT_UPLOADS]);
    }

    /** @test */
    public function it_returns_default_values_when_no_settings_exist()
    {
        $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals(FileUploadSetting::DEFAULT_VALUES, $settings);
    }

    /** @test */
    public function it_can_update_single_setting()
    {
        $result = FileUploadSetting::updateSetting(
            FileUploadSetting::MAX_FILE_SIZE_MB,
            800,
            FileUploadSetting::CONTEXT_GLOBAL
        );

        $this->assertTrue($result);

        $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals(800, $settings[FileUploadSetting::MAX_FILE_SIZE_MB]);
    }

    /** @test */
    public function it_can_update_multiple_settings()
    {
        $newSettings = [
            FileUploadSetting::MAX_FILE_SIZE_MB => 1200,
            FileUploadSetting::CHUNK_SIZE_MB => 8,
            FileUploadSetting::ENABLE_CHUNKING => false,
        ];

        $result = FileUploadSetting::updateSettings($newSettings, FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertTrue($result);

        $settings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals(1200, $settings[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(8, $settings[FileUploadSetting::CHUNK_SIZE_MB]);
        $this->assertFalse($settings[FileUploadSetting::ENABLE_CHUNKING]);

        // Other settings should remain at defaults
        $this->assertEquals(
            FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_CONCURRENT_UPLOADS],
            $settings[FileUploadSetting::MAX_CONCURRENT_UPLOADS]
        );
    }

    /** @test */
    public function it_implements_settings_inheritance_correctly()
    {
        // Set global settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 400,
            FileUploadSetting::CHUNK_SIZE_MB => 6,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        // Set project-specific setting (only max file size)
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1500,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        $projectSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);

        // Should get project-specific max file size
        $this->assertEquals(1500, $projectSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);

        // Should inherit global chunk size
        $this->assertEquals(6, $projectSettings[FileUploadSetting::CHUNK_SIZE_MB]);

        // Should use default for unset values
        $this->assertEquals(
            FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_CONCURRENT_UPLOADS],
            $projectSettings[FileUploadSetting::MAX_CONCURRENT_UPLOADS]
        );
    }

    /** @test */
    public function it_caches_settings_correctly()
    {
        // First call should hit the database
        $settings1 = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);

        // Second call should hit cache (we can't easily test this directly, but we can verify consistent results)
        $settings2 = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);

        $this->assertEquals($settings1, $settings2);
    }

    /** @test */
    public function it_clears_cache_when_settings_updated()
    {
        // Get initial settings (this caches them)
        $initial = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals(500, $initial[FileUploadSetting::MAX_FILE_SIZE_MB]);

        // Update settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1000,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        // Get settings again - should not be cached
        $updated = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $this->assertEquals(1000, $updated[FileUploadSetting::MAX_FILE_SIZE_MB]);
    }

    /** @test */
    public function it_can_clear_specific_context_cache()
    {
        // Set settings for multiple contexts
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 600,
        ], FileUploadSetting::CONTEXT_GLOBAL);

        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 900,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        // Clear only project cache
        FileUploadSetting::clearSettingsCache(FileUploadSetting::CONTEXT_PROJECTS);

        // Both contexts should still work
        $globalSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_GLOBAL);
        $projectSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);

        $this->assertEquals(600, $globalSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertEquals(900, $projectSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);
    }

    /** @test */
    public function it_can_reset_context_to_defaults()
    {
        // Set custom settings
        FileUploadSetting::updateSettings([
            FileUploadSetting::MAX_FILE_SIZE_MB => 1800,
            FileUploadSetting::CHUNK_SIZE_MB => 15,
        ], FileUploadSetting::CONTEXT_PROJECTS);

        // Verify custom settings exist
        $customSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals(1800, $customSettings[FileUploadSetting::MAX_FILE_SIZE_MB]);

        // Reset to defaults
        $result = FileUploadSetting::resetToDefaults(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertTrue($result);

        // Should now return defaults
        $resetSettings = FileUploadSetting::getSettings(FileUploadSetting::CONTEXT_PROJECTS);
        $this->assertEquals(
            FileUploadSetting::DEFAULT_VALUES[FileUploadSetting::MAX_FILE_SIZE_MB],
            $resetSettings[FileUploadSetting::MAX_FILE_SIZE_MB]
        );
    }

    /** @test */
    public function it_provides_settings_schema()
    {
        $schema = FileUploadSetting::getSettingsSchema();

        $this->assertArrayHasKey(FileUploadSetting::MAX_FILE_SIZE_MB, $schema);
        $this->assertArrayHasKey('description', $schema[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertArrayHasKey('validation', $schema[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertArrayHasKey('default', $schema[FileUploadSetting::MAX_FILE_SIZE_MB]);
        $this->assertArrayHasKey('type', $schema[FileUploadSetting::MAX_FILE_SIZE_MB]);

        $this->assertEquals('integer', $schema[FileUploadSetting::MAX_FILE_SIZE_MB]['type']);
        $this->assertEquals('boolean', $schema[FileUploadSetting::ENABLE_CHUNKING]['type']);
    }

    /** @test */
    public function it_has_correct_valid_contexts()
    {
        $contexts = FileUploadSetting::getValidContexts();

        $this->assertCount(4, $contexts);
        $this->assertContains(FileUploadSetting::CONTEXT_GLOBAL, $contexts);
        $this->assertContains(FileUploadSetting::CONTEXT_PROJECTS, $contexts);
        $this->assertContains(FileUploadSetting::CONTEXT_PITCHES, $contexts);
        $this->assertContains(FileUploadSetting::CONTEXT_CLIENT_PORTALS, $contexts);
    }

    /** @test */
    public function it_has_correct_valid_keys()
    {
        $keys = FileUploadSetting::getValidKeys();

        $this->assertCount(6, $keys);
        $this->assertContains(FileUploadSetting::MAX_FILE_SIZE_MB, $keys);
        $this->assertContains(FileUploadSetting::CHUNK_SIZE_MB, $keys);
        $this->assertContains(FileUploadSetting::MAX_CONCURRENT_UPLOADS, $keys);
        $this->assertContains(FileUploadSetting::MAX_RETRY_ATTEMPTS, $keys);
        $this->assertContains(FileUploadSetting::ENABLE_CHUNKING, $keys);
        $this->assertContains(FileUploadSetting::SESSION_TIMEOUT_HOURS, $keys);
    }

    /** @test */
    public function it_validates_invalid_settings_correctly()
    {
        $invalidSettings = [
            // Test each validation rule
            [FileUploadSetting::MAX_FILE_SIZE_MB, 0], // Below minimum
            [FileUploadSetting::MAX_FILE_SIZE_MB, 3000], // Above maximum
            [FileUploadSetting::CHUNK_SIZE_MB, 0], // Below minimum
            [FileUploadSetting::CHUNK_SIZE_MB, 100], // Above maximum
            [FileUploadSetting::MAX_CONCURRENT_UPLOADS, 0], // Below minimum
            [FileUploadSetting::MAX_CONCURRENT_UPLOADS, 20], // Above maximum
            [FileUploadSetting::MAX_RETRY_ATTEMPTS, 0], // Below minimum
            [FileUploadSetting::MAX_RETRY_ATTEMPTS, 10], // Above maximum
            [FileUploadSetting::ENABLE_CHUNKING, 'not_boolean'], // Invalid type
            [FileUploadSetting::SESSION_TIMEOUT_HOURS, 0], // Below minimum
            [FileUploadSetting::SESSION_TIMEOUT_HOURS, 200], // Above maximum
        ];

        foreach ($invalidSettings as [$key, $value]) {
            try {
                FileUploadSetting::validateSetting($key, $value);
                $this->fail("Expected validation to fail for {$key} = {$value}");
            } catch (ValidationException $e) {
                // Expected - validation should fail
                $this->assertTrue(true);
            }
        }
    }
}
