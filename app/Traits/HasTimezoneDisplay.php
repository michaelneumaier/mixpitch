<?php

namespace App\Traits;

use App\Services\TimezoneService;
use Carbon\Carbon;

/**
 * Trait HasTimezoneDisplay
 *
 * Provides timezone-aware accessors for model timestamps.
 * Automatically converts UTC timestamps to the authenticated user's timezone for display.
 *
 * Usage:
 *   In your model: use HasTimezoneDisplay;
 *   In views: {{ $model->created_at_for_user->diffForHumans() }}
 */
trait HasTimezoneDisplay
{
    /**
     * Get created_at timestamp in user's timezone
     */
    public function getCreatedAtForUserAttribute(): ?Carbon
    {
        if (! $this->created_at) {
            return null;
        }

        return app(TimezoneService::class)->convertToUserTimezone($this->created_at);
    }

    /**
     * Get updated_at timestamp in user's timezone
     */
    public function getUpdatedAtForUserAttribute(): ?Carbon
    {
        if (! $this->updated_at) {
            return null;
        }

        return app(TimezoneService::class)->convertToUserTimezone($this->updated_at);
    }

    /**
     * Format a timestamp for the current user's timezone
     */
    protected function formatTimestampForUser(?Carbon $date, ?string $format = null): ?string
    {
        if (! $date) {
            return null;
        }

        return app(TimezoneService::class)->formatForUser($date, auth()->user(), $format);
    }
}
