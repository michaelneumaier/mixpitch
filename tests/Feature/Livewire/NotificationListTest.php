<?php

namespace Tests\Feature\Livewire;

use App\Events\NotificationCreated;
use App\Livewire\NotificationList;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\BroadcastManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;
use Illuminate\Support\Facades\Broadcast;

class NotificationListTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function renders_successfully_for_logged_in_user()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertOk();
    }

    /** @test */
    public function renders_empty_for_guest()
    {
        Livewire::test(NotificationList::class)
            ->assertOk()
            ->assertSet('notifications', [])
            ->assertSet('hasUnread', false);
    }

    /** @test */
    public function loads_initial_notifications_for_user()
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->for($user)->create(['read_at' => now()]);
        Notification::factory()->count(3)->for($user)->create(['read_at' => null]); // Unread

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertCount('notifications', 8)
            ->assertSet('hasUnread', true);
    }

    /** @test */
    public function limits_initial_notifications_to_default_limit()
    {
        $user = User::factory()->create();
        // Default limit is 10
        Notification::factory()->count(15)->for($user)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertCount('notifications', 10);
    }

    /** @test */
    public function mark_as_read_marks_single_notification_read()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create(['read_at' => null]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertSet('hasUnread', true)
            ->call('markAsRead', $notification->id)
            ->assertDispatched('notificationRead');

        $this->assertNotNull($notification->fresh()->read_at);
    }

     /** @test */
    public function mark_all_as_read_marks_all_user_notifications_read()
    {
        $user = User::factory()->create();
        Notification::factory()->count(5)->for($user)->create(['read_at' => null]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertSet('hasUnread', true)
            ->call('markAllAsRead')
            ->assertDispatched('notificationRead')
            ->assertSet('hasUnread', false); // Should refresh and update status

        $this->assertEquals(0, Notification::where('user_id', $user->id)->unread()->count());
    }

    /** @test */
    public function loads_more_notifications_when_requested()
    {
        $user = User::factory()->create();
        // Create more than the initial limit (10)
        Notification::factory()->count(15)->for($user)->create();

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertCount('notifications', 10) // Initial load
            ->call('loadMoreNotifications')
            ->assertCount('notifications', 15); // Should load all 15 now
    }

    /** @test */
    public function it_refreshes_when_notification_created_event_is_broadcast()
    {
        $user = User::factory()->create();
        // Event::fake(); // Keep Event fake if asserting specific event data, otherwise remove

        $component = Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->assertCount('notifications', 0);

        // Simulate a new notification being created
        $newNotification = Notification::factory()->for($user)->create();
        // We don't need to call broadcast() here, Event::fake() handles it.
        // broadcast(new NotificationCreated($newNotification))->toOthers();

        // Dispatch the event manually to simulate it being handled by the faked dispatcher
        Event::dispatch(new NotificationCreated($newNotification));

        // Manually trigger the listener method to simulate the effect
        // Livewire testing of Echo listeners is tricky, this simulates the $refresh
        $component->call('refreshNotifications');

        $component->assertCount('notifications', 1);

        // Access the component's property after potential refresh
        $updatedNotifications = $component->get('notifications');
        $this->assertNotEmpty($updatedNotifications);
        $this->assertEquals($newNotification->id, $updatedNotifications->first()->id);
    }

    /** @test */
    public function user_can_delete_their_own_notification()
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->for($user)->create();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);

        Livewire::actingAs($user)
            ->test(NotificationList::class)
            ->call('deleteNotification', $notification->id)
            ->assertDispatched('notificationRead');

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function user_cannot_delete_another_users_notification()
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $notificationForUserB = Notification::factory()->for($userB)->create();

        $this->assertDatabaseHas('notifications', ['id' => $notificationForUserB->id]);

        Livewire::actingAs($userA)
            ->test(NotificationList::class)
            ->call('deleteNotification', $notificationForUserB->id)
            ->assertNotDispatched('notificationRead'); // Should not dispatch if no deletion occurs

        $this->assertDatabaseHas('notifications', ['id' => $notificationForUserB->id]); // Notification should still exist
    }
} 