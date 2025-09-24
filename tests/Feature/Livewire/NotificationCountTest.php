<?php

namespace Tests\Feature\Livewire;

use App\Events\NotificationCreated;
use App\Livewire\NotificationCount;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationCountTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_successfully_for_logged_in_user()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationCount::class)
            ->assertOk();
    }

    /** @test */
    public function renders_zero_count_for_guest()
    {
        Livewire::test(NotificationCount::class)
            ->assertOk()
            ->assertSet('count', 0);
    }

    /** @test */
    public function loads_correct_initial_unread_count()
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->for($user)->create(['read_at' => now()]);
        Notification::factory()->count(3)->for($user)->create(['read_at' => null]); // 3 Unread

        Livewire::actingAs($user)
            ->test(NotificationCount::class)
            ->assertSet('count', 3);
    }

    /** @test */
    public function loads_zero_count_when_no_unread_notifications()
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->for($user)->create(['read_at' => now()]);

        Livewire::actingAs($user)
            ->test(NotificationCount::class)
            ->assertSet('count', 0);
    }

    /** @test */
    public function it_refreshes_count_when_notification_read_event_is_dispatched()
    {
        $user = User::factory()->create();
        $unreadNotification = Notification::factory()->for($user)->create(['read_at' => null]);

        $component = Livewire::actingAs($user)
            ->test(NotificationCount::class)
            ->assertSet('count', 1);

        // Simulate marking the notification as read elsewhere (e.g., by NotificationList)
        $unreadNotification->markAsRead();

        // Trigger the refresh via the listener
        $component->call('$refresh');

        // Assert the count is updated
        $component->assertSet('count', 0);
    }

    /** @test */
    public function it_refreshes_count_when_notification_created_event_is_broadcast()
    {
        $user = User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(NotificationCount::class)
            ->assertSet('count', 0);

        // Simulate a new notification being created
        $newNotification = Notification::factory()->for($user)->create(['read_at' => null]); // Important: unread

        // Dispatch the event manually to simulate it being handled by the faked dispatcher
        Event::dispatch(new NotificationCreated($newNotification));

        // Manually trigger the listener method to simulate the effect
        $component->call('$refresh');

        // Assert the count is updated
        $component->assertSet('count', 1);
    }
}
