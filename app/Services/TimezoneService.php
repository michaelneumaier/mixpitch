<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TimezoneService
{
    /**
     * Convert UTC date to user's timezone
     */
    public function convertToUserTimezone(Carbon $date, ?User $user = null): Carbon
    {
        $user = $user ?? auth()->user();
        $timezone = $this->getUserTimezone($user);
        
        // If the date timezone is not UTC but should be (due to middleware effects),
        // we need to treat the date/time values as UTC
        if ($date->timezone->getName() !== 'UTC' && $this->shouldTreatAsUtc($date)) {
            $utcDate = Carbon::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d H:i:s'), 'UTC');
            return $utcDate->setTimezone($timezone);
        }
        
        return $date->copy()->setTimezone($timezone);
    }
    
    /**
     * Format date for user's timezone with specified format
     */
    public function formatForUser(Carbon $date, ?User $user = null, ?string $format = null): string
    {
        $userDate = $this->convertToUserTimezone($date, $user);
        $format = $format ?? config('timezone.display_formats.datetime');
        
        return $userDate->format($format);
    }
    
    /**
     * Get user's timezone preference
     */
    public function getUserTimezone(?User $user = null): string
    {
        if (!$user) {
            return config('timezone.default');
        }
        
        return $user->timezone ?? config('timezone.default');
    }
    
    /**
     * Get available timezones for selection
     */
    public function getAvailableTimezones(): array
    {
        return config('timezone.user_selectable');
    }
    
    /**
     * Validate if timezone string is valid and selectable
     */
    public function validateTimezone(string $timezone): bool
    {
        return array_key_exists($timezone, $this->getAvailableTimezones());
    }
    
    /**
     * Convert user input time to UTC for database storage
     */
    public function convertToUtc(string $dateTime, ?User $user = null): Carbon
    {
        $userTimezone = $this->getUserTimezone($user);
        return Carbon::createFromFormat('Y-m-d H:i:s', $dateTime, $userTimezone)->utc();
    }
    
    /**
     * Convert datetime-local input (which is in browser timezone) to UTC
     * This method expects the datetime to already be in UTC format from JavaScript
     */
    public function convertDatetimeLocalToUtc(string $dateTime): Carbon
    {
        // datetime-local format: "2025-06-28T02:00"
        // Convert to "2025-06-28 02:00:00" and parse as UTC
        $formattedDateTime = str_replace('T', ' ', $dateTime) . ':00';
        return Carbon::createFromFormat('Y-m-d H:i:s', $formattedDateTime, 'UTC');
    }
    
    /**
     * Get current time in user's timezone
     */
    public function now(?User $user = null): Carbon
    {
        return $this->convertToUserTimezone(now(), $user);
    }
    
    /**
     * Determine if a Carbon object should be treated as UTC despite having a different timezone
     * This handles cases where the SetUserTimezone middleware has affected database timestamps
     */
    private function shouldTreatAsUtc(Carbon $date): bool
    {
        // This is a heuristic - we assume that dates that come from database
        // timestamps (deadline, submission_deadline, judging_deadline) should be UTC
        // but may have been affected by middleware
        
        // For now, we'll be conservative and only apply this to dates that:
        // 1. Are not in UTC timezone
        // 2. Have the same timezone as the current user (suggesting middleware effect)
        if (auth()->check()) {
            $userTimezone = $this->getUserTimezone(auth()->user());
            return $date->timezone->getName() === $userTimezone;
        }
        
        return false;
    }
} 