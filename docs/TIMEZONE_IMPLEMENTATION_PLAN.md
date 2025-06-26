# Timezone Implementation Plan for MixPitch

## Status: ✅ FIXED - Server-Side Approach Now Fully Implemented

This document outlines the implementation of timezone support for the MixPitch platform using a **server-side conversion approach** that eliminates JavaScript timezone conversion complexity.

## Overview

The platform now supports user-specific timezones using the HTML5 `datetime-local` input specification with server-side timezone conversion. This approach is more reliable and eliminates the browser-specific timezone conversion issues that were causing incorrect offsets.

## ⚠️ Issues Found and Fixed (December 2024)

### Critical Problems Identified:
1. **Hybrid Implementation**: JavaScript conversion was still active alongside server-side conversion
2. **Double Conversion Risk**: JavaScript converted to UTC, then server treated it as user timezone
3. **Inconsistent Data Flow**: Some inputs used `wire:model`, others used JavaScript `onchange`
4. **Missing Contest Support**: ManageProject didn't handle contest deadline loading
5. **Documentation Inaccuracy**: Docs claimed JavaScript was removed but it was still active

### Fixes Applied:
1. ✅ Removed all JavaScript `onchange="convertDatetimeLocalToUtc()"` calls
2. ✅ Added `wire:model` bindings to all datetime-local inputs
3. ✅ Fixed ManageProject to properly load contest deadlines for editing
4. ✅ Removed the problematic JavaScript function entirely
5. ✅ Standardized timezone conversion across all components

## Core Components

### 1. User Timezone Storage
- **Migration**: `add_timezone_to_users_table.php`
- **Model**: Users have a `timezone` field (nullable, defaults to system timezone)
- **Default**: `America/New_York` (EST/EDT)

### 2. Services

#### TimezoneService (`app/Services/TimezoneService.php`)
- Handles conversion between timezones
- Provides user timezone preferences
- Methods:
  - `convertToUserTimezone()`
  - `formatForUser()`
  - `getUserTimezone()`
  - `convertToUtc()`

### 3. Middleware
- **SetUserTimezone**: Automatically sets the application timezone based on user preference

### 4. UI Components

#### DateTime Component (`app/View/Components/DateTime.php`)
- Displays dates/times in user's timezone
- Supports relative time display
- Configurable formats
- Usage: `<x-datetime :date="$date" :user="$user" />`

### 5. Configuration
- **File**: `config/timezone.php`
- **Available Timezones**: Curated list of major timezones
- **Display Formats**: Standardized datetime formats

## ✅ Server-Side Approach (Current Implementation)

### Problem Solved
The previous JavaScript-based timezone conversion was causing issues where:
1. Browser timezone differed from user's profile timezone
2. Double conversion was occurring (JavaScript + Backend)
3. Complex debugging and inconsistent behavior across browsers
4. Inconsistent implementation across different components

### Solution: HTML5 Specification Approach

Following the [MDN datetime-local specification](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local#setting_timezones), we now use:

#### 1. Frontend: Pure HTML5 datetime-local inputs
```html
<!-- ✅ CORRECT: Direct wire:model binding -->
<input type="datetime-local" wire:model="submission_deadline" />
<input type="datetime-local" wire:model="form.deadline" />

<!-- ❌ OLD: JavaScript conversion (removed) -->
<!-- <input type="datetime-local" onchange="convertDatetimeLocalToUtc(this, 'deadline')" /> -->
```

#### 2. Backend: Server-Side Conversion
```php
private function convertDateTimeToUtc(string $dateTime, ?User $user = null): Carbon
{
    $userTimezone = $user->getTimezone();
    
    // Handle datetime-local format: "2025-06-29T13:00"
    if (str_contains($dateTime, 'T')) {
        $formattedDateTime = str_replace('T', ' ', $dateTime);
        if (substr_count($formattedDateTime, ':') === 1) {
            $formattedDateTime .= ':00'; // Add seconds
        }
        
        // Create Carbon instance in user's timezone and convert to UTC
        return Carbon::createFromFormat('Y-m-d H:i:s', $formattedDateTime, $userTimezone)->utc();
    }
    
    return Carbon::parse($dateTime)->utc();
}
```

#### 3. Loading for Edit: Consistent Timezone Conversion
```php
// Both CreateProject and ManageProject now handle this consistently
if ($this->project->isContest()) {
    $this->submission_deadline = $project->submission_deadline ? 
        $timezoneService->convertToUserTimezone($project->submission_deadline, auth()->user())->format('Y-m-d\TH:i') : null;
    $this->judging_deadline = $project->judging_deadline ? 
        $timezoneService->convertToUserTimezone($project->judging_deadline, auth()->user())->format('Y-m-d\TH:i') : null;
}
```

### 3. Benefits of Server-Side Approach

#### ✅ Advantages:
- **Reliable**: No browser-specific JavaScript timezone handling
- **Simple**: Uses standard HTML5 datetime-local inputs as intended
- **Consistent**: Always uses user's profile timezone setting across all components
- **Debuggable**: Server-side logging and testing
- **Standards Compliant**: Follows HTML5 specification recommendations
- **No Double Conversion**: Eliminates the JavaScript → UTC → Server timezone issue

#### ❌ Previous Issues Now Eliminated:
- ~~Browser timezone detection conflicts~~
- ~~Double timezone conversion~~
- ~~JavaScript Date constructor ambiguity~~
- ~~Complex debugging across different browsers~~
- ~~Race conditions with Livewire updates~~
- ~~Inconsistent implementation between components~~

### 4. How It Works

1. **User Input**: User enters `1:00 PM` in datetime-local input
2. **HTML5 Behavior**: Browser sends `2025-06-29T13:00` (local time format)
3. **Livewire Transfer**: `wire:model` sends this directly to backend property
4. **Server Processing**: Backend treats this as user's timezone and converts to UTC
5. **Database Storage**: Stores correct UTC time (e.g., `2025-06-29 19:00:00` for MDT user)
6. **Display**: When editing, converts UTC back to user's timezone for display

### 5. Implementation Details

#### Files Modified:
- `resources/views/livewire/project/page/create-project.blade.php` - ✅ Removed JavaScript conversion, added wire:model
- `resources/views/components/wizard/deadline-selector.blade.php` - ✅ Removed JavaScript function
- `app/Livewire/CreateProject.php` - ✅ Already had correct `convertDateTimeToUtc()` method
- `app/Livewire/ManageProject.php` - ✅ Added contest deadline loading and properties

#### Key Changes Made:
- ✅ Removed all `onchange="convertDatetimeLocalToUtc()"` JavaScript calls
- ✅ Added `wire:model` directly to all datetime-local inputs
- ✅ Added contest deadline support to ManageProject component
- ✅ Removed the problematic JavaScript conversion function entirely
- ✅ Standardized timezone conversion logic across components

### 6. Testing Results

Server-side conversion test for MDT user:
```
User timezone: America/Denver
User enters: 2025-06-29T13:00 (1:00 PM)
Server converts: 2025-06-29 19:00:00 UTC (7:00 PM UTC)
Offset: 6 hours (correct for MDT = UTC-6)
```

## Technical Reference

### Timezone Conversion Examples
```php
// MDT User (UTC-6) enters 1:00 PM
Input: "2025-06-29T13:00"
Output: "2025-06-29 19:00:00" UTC

// EDT User (UTC-4) enters 1:00 PM  
Input: "2025-06-29T13:00"
Output: "2025-06-29 17:00:00" UTC

// PST User (UTC-8) enters 1:00 PM
Input: "2025-06-29T13:00"
Output: "2025-06-29 21:00:00" UTC
```

### Display Conversion
```php
// UTC time from database: "2025-06-29 19:00:00"
// For MDT user display: "2025-06-29T13:00" (1:00 PM)
```

### Component Support Matrix

| Component | Standard Deadline | Contest Deadlines | Status |
|-----------|------------------|------------------|---------|
| CreateProject | ✅ | ✅ | Working |
| ManageProject | ✅ | ✅ | Fixed |
| DateTime Display | ✅ | ✅ | Working |
| Project Views | ✅ | ✅ | Working |

## Future Improvements

1. **Testing**: Add comprehensive timezone tests for both components
2. **Validation**: Consider adding client-side timezone validation
3. **UX**: Add timezone indicator tooltips for better user understanding
4. **Performance**: Cache timezone conversions where appropriate

## References

- [MDN datetime-local Specification](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local)
- [HTML5 Timezone Handling Best Practices](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local#setting_timezones)
- [Carbon PHP Documentation](https://carbon.nesbot.com/docs/)