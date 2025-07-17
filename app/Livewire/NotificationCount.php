<?php

namespace App\Livewire;

use App\Models\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationCount extends Component
{
    public $count = 0;

    protected $listeners = [
        'notificationRead' => '$refresh',
        'echo:notifications,NotificationCreated' => '$refresh',
    ];

    public function mount()
    {
        $this->updateCount();
    }

    public function updateCount()
    {
        if (Auth::check()) {
            $this->count = Notification::where('user_id', Auth::id())
                ->whereNull('read_at')
                ->count();
        } else {
            $this->count = 0;
        }
    }

    public function getListeners()
    {
        return [
            'notificationRead' => '$refresh',
            'echo:notifications,NotificationCreated' => '$refresh',
        ];
    }

    public function render()
    {
        $this->updateCount();

        return view('livewire.notification-count');
    }
}
