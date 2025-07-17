<?php

namespace App\Livewire\User;

use App\Models\Notification;
use App\Models\NotificationChannelPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class NotificationPreferences extends Component
{
    public array $preferences = [];

    public array $notificationTypes = [];

    public array $channels = ['database', 'email'];

    public function mount(): void
    {
        $this->loadPreferences();
    }

    public function loadPreferences(): void
    {
        $user = Auth::user();
        if (! $user) {
            // Handle guest user or redirect
            Log::warning('Attempted to load notification preferences for guest user.');
            $this->notificationTypes = [];
            $this->preferences = [];

            return;
        }

        $this->notificationTypes = Notification::getManageableTypes();
        $types = array_keys($this->notificationTypes);

        // Fetch existing preferences for this user for the manageable types and defined channels
        $existingPreferences = NotificationChannelPreference::where('user_id', $user->id)
            ->whereIn('notification_type', $types)
            ->whereIn('channel', $this->channels)
            ->get()
            ->keyBy(fn ($pref) => $pref->notification_type.'_'.$pref->channel); // Key by type_channel for easy lookup

        // Initialize preferences array
        $this->preferences = [];
        foreach ($this->notificationTypes as $type => $label) {
            $this->preferences[$type] = [];
            foreach ($this->channels as $channel) {
                $key = $type.'_'.$channel;
                // Default to true (enabled) if no specific preference is found
                $this->preferences[$type][$channel] = $existingPreferences->has($key)
                    ? $existingPreferences[$key]->is_enabled
                    : true;
            }
        }
    }

    /**
     * This method is triggered when a preference toggle changes.
     * Livewire property binding format will be preferences.{type}.{channel}
     * The $value will be the new boolean state (true/false).
     * The $key will be the composite key like "pitch_submitted.email".
     */
    public function updatedPreferences($value, $key): void
    {
        $user = Auth::user();
        // Extract type and channel from the key
        $parts = explode('.', $key);
        if (count($parts) !== 2) {
            Log::warning('Invalid preference key format during update.', ['key' => $key, 'user_id' => $user?->id]);

            return; // Invalid key structure
        }
        $type = $parts[0];
        $channel = $parts[1];

        if (! $user || ! array_key_exists($type, $this->notificationTypes) || ! in_array($channel, $this->channels)) {
            Log::warning('Invalid attempt to update notification channel preference.', [
                'user_id' => $user?->id,
                'type' => $type,
                'channel' => $channel,
                'value' => $value,
            ]);

            // Optionally: Add a user-facing error message
            // $this->dispatch('toast', type: 'error', message: 'Failed to update preference.');
            return;
        }

        try {
            NotificationChannelPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                    'channel' => $channel,
                ],
                [
                    'is_enabled' => (bool) $value,
                ]
            );
            // Optionally: Add a success message
            // $this->dispatch('toast', type: 'success', message: 'Preference updated.');
        } catch (\Exception $e) {
            Log::error('Failed to update notification channel preference.', [
                'user_id' => $user->id,
                'type' => $type,
                'channel' => $channel,
                'value' => $value,
                'error' => $e->getMessage(),
            ]);
            // Optionally: Add a user-facing error message
            // $this->dispatch('toast', type: 'error', message: 'Failed to save preference. Please try again.');

            // Reload preferences to revert optimistic UI update if save failed
            $this->loadPreferences();
        }
    }

    public function render()
    {
        return view('livewire.user.notification-preferences');
    }
}
