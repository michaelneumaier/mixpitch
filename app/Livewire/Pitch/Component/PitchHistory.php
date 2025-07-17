<?php

namespace App\Livewire\Pitch\Component;

use App\Models\Pitch;
use Livewire\Component;

/**
 * PitchHistory Component
 *
 * Displays the history of status changes, comments, and snapshot events for a pitch
 */
class PitchHistory extends Component
{
    public Pitch $pitch;

    public $events;

    public $showHistory = false;

    public function mount(Pitch $pitch)
    {
        $this->pitch = $pitch;
        $this->loadEvents();
    }

    public function loadEvents()
    {
        // Load events with relations
        $this->events = $this->pitch->events()
            ->with(['user', 'snapshot'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function toggleHistory()
    {
        $this->showHistory = ! $this->showHistory;
        if ($this->showHistory) {
            // Refresh events when showing history
            $this->loadEvents();
        }
    }

    public function getEventIcon($eventType)
    {
        $icons = [
            'status_change' => 'arrow-path',
            'comment' => 'chat-bubble-left-ellipsis',
            'snapshot_created' => 'camera',
            'snapshot_approved' => 'check-circle',
            'snapshot_denied' => 'x-circle',
            'snapshot_status_change' => 'photo',
            'file_added' => 'paper-clip',
            'file_removed' => 'trash',
        ];

        return $icons[$eventType] ?? 'information-circle';
    }

    public function getIconPath($eventType)
    {
        $paths = [
            'status_change' => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
            'comment' => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
            'snapshot_created' => 'M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM18.75 10.5h.008v.008h-.008V10.5z',
            'snapshot_approved' => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'snapshot_denied' => 'M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            'snapshot_status_change' => 'M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
            'file_added' => 'M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13',
            'file_removed' => 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0',
            'information-circle' => 'M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z',
        ];

        return $paths[$eventType] ?? $paths['information-circle'];
    }

    public function getEventClass($eventType, $status = null)
    {
        // Default class is blue (informational)
        $class = 'text-blue-500';

        if ($eventType === 'status_change') {
            switch ($status) {
                case Pitch::STATUS_APPROVED:
                    $class = 'text-green-500';
                    break;
                case Pitch::STATUS_DENIED:
                    $class = 'text-red-500';
                    break;
                case Pitch::STATUS_COMPLETED:
                    $class = 'text-purple-500';
                    break;
                default:
                    $class = 'text-blue-500';
            }
        } elseif ($eventType === 'snapshot_approved') {
            $class = 'text-green-500';
        } elseif ($eventType === 'snapshot_denied') {
            $class = 'text-red-500';
        } elseif ($eventType === 'comment') {
            $class = 'text-gray-500';
        }

        return $class;
    }

    public function render()
    {
        return view('livewire.pitch.component.pitch-history');
    }
}
