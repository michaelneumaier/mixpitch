<?php

namespace App\Livewire\User;

use App\Models\Notification;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class NotificationPreferences extends Component
{
    public array $preferences = [];
    public array $notificationTypes = [];

    public function mount(): void
    {
        $this->loadPreferences();
    }

    public function loadPreferences(): void
    {
        $user = Auth::user();
        if (!$user) {
            // Handle guest user or redirect
            Log::warning('Attempted to load notification preferences for guest user.');
            $this->notificationTypes = [];
            $this->preferences = [];
            return;
        }

        $this->notificationTypes = Notification::getManageableTypes();

        // Fetch existing preferences for this user
        $existingPreferences = NotificationPreference::where('user_id', $user->id)
            ->whereIn('notification_type', array_keys($this->notificationTypes))
            ->pluck('is_enabled', 'notification_type') // Get as [type => is_enabled]
            ->all(); // Convert collection to array

        // Initialize preferences array, defaulting to true if no preference exists
        $this->preferences = [];
        foreach ($this->notificationTypes as $type => $label) {
            // Default to true (enabled) if no specific preference is found
            $this->preferences[$type] = $existingPreferences[$type] ?? true;
        }
    }

    /**
     * This method is triggered when a preference toggle changes.
     * Livewire handles the direct binding, but we need to persist the change.
     */
    public function updatedPreferences($value, $type): void
    {
        $user = Auth::user();
        if (!$user || !array_key_exists($type, $this->notificationTypes)) {
            Log::warning('Invalid attempt to update notification preference.', [
                'user_id' => $user?->id,
                'type' => $type,
                'value' => $value
            ]);
            // Optionally: Add a user-facing error message
            // $this->dispatch('toast', type: 'error', message: 'Failed to update preference.');
            return;
        }

        try {
            NotificationPreference::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_type' => $type,
                ],
                [
                    'is_enabled' => (bool) $value,
                ]
            );
            // Optionally: Add a success message
            // $this->dispatch('toast', type: 'success', message: 'Preference updated.');
        } catch (\Exception $e) {
            Log::error('Failed to update notification preference.', [
                'user_id' => $user->id,
                'type' => $type,
                'value' => $value,
                'error' => $e->getMessage()
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
