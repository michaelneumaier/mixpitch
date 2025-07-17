<?php

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationList extends Component
{
    public $notifications = [];

    public $hasUnread = false;

    public $showDropdown = false;

    public $notificationLimit = 10;

    protected $listeners = [
        'notificationRead' => '$refresh',
        'echo:notifications,NotificationCreated' => '$refresh',
    ];

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (Auth::check()) {
            // Get the latest notifications for the user based on the current limit
            $this->notifications = Notification::where('user_id', Auth::id())
                ->orderByDesc('created_at')
                ->limit($this->notificationLimit)
                ->get();

            // Check if there are any unread notifications
            $this->hasUnread = $this->notifications->contains(function ($notification) {
                return $notification->read_at === null;
            });
        } else {
            $this->notifications = [];
            $this->hasUnread = false;
        }
    }

    public function updatedShowDropdown($value)
    {
        if ($value) {
            $this->loadNotifications();
        }
    }

    public function markAsRead($id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            $this->dispatch('notificationRead');
        }
    }

    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->loadNotifications();
        $this->dispatch('notificationRead');
    }

    public function deleteNotification(int $id)
    {
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->delete();
            $this->loadNotifications(); // Reload the list after deleting
            $this->dispatch('notificationRead'); // Dispatch event to update count etc.
        }
    }

    public function getListeners()
    {
        if (! Auth::check()) {
            return [
                'notificationRead' => '$refresh',
            ];
        }

        return [
            'notificationRead' => '$refresh',
            'echo-private:notifications.'.Auth::id().',NotificationCreated' => 'refreshNotifications',
        ];
    }

    /**
     * Refresh notifications when a new one is created
     */
    public function refreshNotifications()
    {
        $this->loadNotifications();
    }

    /**
     * Load more notifications when requested
     */
    public function loadMoreNotifications()
    {
        // Increase the limit by 10
        $this->notificationLimit += 10;

        // Reload notifications with the new limit
        $this->loadNotifications();
    }

    public function render()
    {
        return view('livewire.notification-list');
    }
}
