<?php

namespace Tests\Feature\Livewire\User;

use App\Livewire\User\NotificationPreferences;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function component_renders_successfully()
    {
        Livewire::test(NotificationPreferences::class)
            ->assertOk();
    }

    /** @test */
    public function it_loads_manageable_notification_types()
    {
        $manageableTypes = Notification::getManageableTypes();

        Livewire::test(NotificationPreferences::class)
            ->assertSet('notificationTypes', $manageableTypes)
            ->assertSee(array_values($manageableTypes)[0]); // Check if at least one label is rendered
    }

    /** @test */
    public function it_loads_existing_preferences_correctly()
    {
        // Get a type to test with
        $typeToDisable = Notification::TYPE_PITCH_COMMENT;
        $typeToKeepEnabled = Notification::TYPE_PITCH_APPROVED;

        // Create a preference to disable one type
        NotificationPreference::create([
            'user_id' => $this->user->id,
            'notification_type' => $typeToDisable,
            'is_enabled' => false,
        ]);
        // No preference needed for typeToKeepEnabled, should default to true

        Livewire::test(NotificationPreferences::class)
            ->assertSet("preferences.{$typeToDisable}", false)
            ->assertSet("preferences.{$typeToKeepEnabled}", true); // Assert default is true
    }

    /** @test */
    public function updating_a_preference_saves_it_to_database()
    {
        $typeToToggle = Notification::TYPE_PITCH_SUBMITTED;

        // Initial state should be enabled (default)
        Livewire::test(NotificationPreferences::class)
            ->assertSet("preferences.{$typeToToggle}", true)
            // Toggle it off
            ->set("preferences.{$typeToToggle}", false)
            ->assertSet("preferences.{$typeToToggle}", false); // Livewire updates property immediately

        // Assert the change was persisted in the database
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => $typeToToggle,
            'is_enabled' => false,
        ]);

        // Toggle it back on
        Livewire::test(NotificationPreferences::class)
             // Ensure it starts reflecting the saved state (false)
            ->assertSet("preferences.{$typeToToggle}", false)
            ->set("preferences.{$typeToToggle}", true)
            ->assertSet("preferences.{$typeToToggle}", true);

        // Assert the change was persisted
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $this->user->id,
            'notification_type' => $typeToToggle,
            'is_enabled' => true,
        ]);
    }
} 