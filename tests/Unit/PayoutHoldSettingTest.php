<?php

namespace Tests\Unit;

use App\Models\PayoutHoldSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase as BaseTestCase;

class PayoutHoldSettingTest extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // Clear cache before each test
    }

    /** @test */
    public function it_can_create_default_settings()
    {
        $settings = PayoutHoldSetting::factory()->create();

        $this->assertDatabaseHas('payout_hold_settings', [
            'enabled' => true,
            'default_days' => 1,
            'business_days_only' => true,
            'processing_time' => '09:00:00',
            'minimum_hold_hours' => 0,
            'allow_admin_bypass' => true,
            'require_bypass_reason' => true,
            'log_bypasses' => true,
        ]);

        $this->assertEquals([
            'standard' => 1,
            'contest' => 0,
            'client_management' => 0,
        ], $settings->workflow_days);
    }

    /** @test */
    public function it_returns_current_settings_with_caching()
    {
        // Create settings
        $settings = PayoutHoldSetting::factory()->create();

        // First call should hit database and cache result
        $current1 = PayoutHoldSetting::current();
        $this->assertEquals($settings->id, $current1->id);

        // Second call should use cache
        $current2 = PayoutHoldSetting::current();
        $this->assertEquals($settings->id, $current2->id);
        $this->assertSame($current1, $current2); // Same object from cache
    }

    /** @test */
    public function it_creates_default_settings_if_none_exist()
    {
        $this->assertDatabaseEmpty('payout_hold_settings');

        $current = PayoutHoldSetting::current();

        $this->assertDatabaseCount('payout_hold_settings', 1);
        $this->assertTrue($current->enabled);
        $this->assertEquals(1, $current->default_days);
    }

    /** @test */
    public function it_clears_cache_when_settings_are_updated()
    {
        $settings = PayoutHoldSetting::factory()->create();

        // Load into cache
        PayoutHoldSetting::current();
        $this->assertTrue(Cache::has('payout_hold_settings'));

        // Update settings should clear cache
        $settings->update(['enabled' => false]);
        $this->assertFalse(Cache::has('payout_hold_settings'));
    }

    /** @test */
    public function it_clears_cache_when_settings_are_deleted()
    {
        $settings = PayoutHoldSetting::factory()->create();

        // Load into cache
        PayoutHoldSetting::current();
        $this->assertTrue(Cache::has('payout_hold_settings'));

        // Delete settings should clear cache
        $settings->delete();
        $this->assertFalse(Cache::has('payout_hold_settings'));
    }

    /** @test */
    public function it_returns_correct_hold_days_for_workflow_types()
    {
        $settings = PayoutHoldSetting::factory()->create([
            'enabled' => true,
            'default_days' => 5,
            'workflow_days' => [
                'standard' => 1,
                'contest' => 0,
                'client_management' => 0,
            ],
        ]);

        $this->assertEquals(1, $settings->getHoldDaysForWorkflow('standard'));
        $this->assertEquals(0, $settings->getHoldDaysForWorkflow('contest'));
        $this->assertEquals(0, $settings->getHoldDaysForWorkflow('client_management'));
        $this->assertEquals(5, $settings->getHoldDaysForWorkflow('unknown_workflow')); // Falls back to default
    }

    /** @test */
    public function it_returns_zero_hold_days_when_disabled()
    {
        $settings = PayoutHoldSetting::factory()->disabled()->create([
            'workflow_days' => [
                'standard' => 5,
                'contest' => 3,
                'client_management' => 7,
            ],
        ]);

        $this->assertEquals(0, $settings->getHoldDaysForWorkflow('standard'));
        $this->assertEquals(0, $settings->getHoldDaysForWorkflow('contest'));
        $this->assertEquals(0, $settings->getHoldDaysForWorkflow('client_management'));
    }

    /** @test */
    public function it_casts_attributes_correctly()
    {
        $settings = PayoutHoldSetting::factory()->create([
            'enabled' => '1',
            'default_days' => '5',
            'business_days_only' => '0',
            'minimum_hold_hours' => '24',
            'allow_admin_bypass' => '1',
        ]);

        $settings->refresh();

        $this->assertIsBool($settings->enabled);
        $this->assertIsInt($settings->default_days);
        $this->assertIsBool($settings->business_days_only);
        $this->assertIsInt($settings->minimum_hold_hours);
        $this->assertIsBool($settings->allow_admin_bypass);
        $this->assertIsArray($settings->workflow_days);

        $this->assertTrue($settings->enabled);
        $this->assertEquals(5, $settings->default_days);
        $this->assertFalse($settings->business_days_only);
        $this->assertEquals(24, $settings->minimum_hold_hours);
        $this->assertTrue($settings->allow_admin_bypass);
    }

    /** @test */
    public function it_provides_validation_rules()
    {
        $rules = PayoutHoldSetting::validationRules();

        $this->assertArrayHasKey('enabled', $rules);
        $this->assertArrayHasKey('default_days', $rules);
        $this->assertArrayHasKey('workflow_days', $rules);
        $this->assertArrayHasKey('workflow_days.standard', $rules);
        $this->assertArrayHasKey('workflow_days.contest', $rules);
        $this->assertArrayHasKey('workflow_days.client_management', $rules);
        $this->assertArrayHasKey('business_days_only', $rules);
        $this->assertArrayHasKey('processing_time', $rules);
        $this->assertArrayHasKey('minimum_hold_hours', $rules);
    }

    /** @test */
    public function it_gets_default_settings_from_config()
    {
        config(['business.payout_hold_settings.enabled' => false]);
        config(['business.payout_hold_settings.default_days' => 10]);
        config(['business.admin_overrides.allow_bypass' => false]);

        $defaults = PayoutHoldSetting::getDefaultSettings();

        $this->assertFalse($defaults['enabled']);
        $this->assertEquals(10, $defaults['default_days']);
        $this->assertFalse($defaults['allow_admin_bypass']);
    }
}
