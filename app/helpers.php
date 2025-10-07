<?php

if (! function_exists('toUserTimezone')) {
    /**
     * Convert a UTC timestamp to the authenticated user's timezone
     *
     * Usage in Blade: {{ toUserTimezone($date)->diffForHumans() }}
     *
     * @param  \Carbon\Carbon|null  $date
     * @param  \App\Models\User|null  $user
     * @return \Carbon\Carbon|null
     */
    function toUserTimezone($date, $user = null)
    {
        if (! $date) {
            return null;
        }

        return app(\App\Services\TimezoneService::class)->convertToUserTimezone($date, $user);
    }
}
