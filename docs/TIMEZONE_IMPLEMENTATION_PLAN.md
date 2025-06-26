# Timezone Implementation Plan for MixPitch

## Overview
This document outlines a comprehensive plan to implement timezone functionality in the MixPitch application, allowing users to select their timezone in Account Settings while setting EST as the default site timezone.

## Current State Analysis

### Configuration
- **Current App Timezone**: UTC (in `config/app.php`)
- **Target Site Default**: EST (Eastern Standard Time)
- **Database Timestamps**: Currently stored in UTC

### Date/Time Usage Patterns
- **Project Deadlines**: Extensive use in project cards, filters, and displays
- **Contest Deadlines**: Submission and judging deadlines with countdown timers
- **Payout Schedules**: Hold periods and processing times (already has timezone considerations)
- **User Profile**: Profile completion dates, subscription dates
- **File Uploads**: Created timestamps for project files and pitch files

## Implementation Strategy

### Phase 1: Foundation Setup ✅ TODO
- [ ] Update application configuration for EST default
- [ ] Add timezone field to users table
- [ ] Create timezone helper service
- [ ] Implement timezone middleware
- [ ] Update User model with timezone functionality

### Phase 2: User Interface ✅ TODO
- [ ] Add timezone selector to Account Settings
- [ ] Create timezone selection component
- [ ] Update profile editing forms
- [ ] Add timezone validation rules

### Phase 3: DateTime Display Updates ✅ TODO
- [ ] Create timezone-aware date formatting helpers
- [ ] Update all date displays throughout the application
- [ ] Implement automatic timezone conversion in Blade components
- [ ] Update dashboard date displays

### Phase 4: Backend Logic Updates ✅ TODO
- [ ] Update project deadline handling
- [ ] Update contest timing logic
- [ ] Update payout processing schedules
- [ ] Update notification scheduling
- [ ] Update email timestamp displays

### Phase 5: Testing & Validation ✅ TODO
- [ ] Unit tests for timezone service
- [ ] Feature tests for user timezone selection
- [ ] Integration tests for date display consistency
- [ ] Browser tests for timezone functionality
- [ ] Performance testing for timezone conversions

---

## Technical Implementation Details

### 1. Database Schema Changes

#### Users Table Migration
```php
// database/migrations/YYYY_MM_DD_add_timezone_to_users_table.php
Schema::table('users', function (Blueprint $table) {
    $table->string('timezone')->default('America/New_York')->after('location');
});
```

### 2. Configuration Updates

#### App Configuration
```php
// config/app.php
'timezone' => 'America/New_York', // EST
```

#### New Timezone Configuration
```php
// config/timezone.php
return [
    'default' => 'America/New_York',
    'user_selectable' => [
        'America/New_York' => 'Eastern Time (EST/EDT)',
        'America/Chicago' => 'Central Time (CST/CDT)',
        'America/Denver' => 'Mountain Time (MST/MDT)',
        'America/Los_Angeles' => 'Pacific Time (PST/PDT)',
        'America/Anchorage' => 'Alaska Time (AKST/AKDT)',
        'Pacific/Honolulu' => 'Hawaii Time (HST)',
        'UTC' => 'UTC (Coordinated Universal Time)',
        'Europe/London' => 'GMT/BST (London)',
        'Europe/Paris' => 'CET/CEST (Paris)',
        'Europe/Berlin' => 'CET/CEST (Berlin)',
        'Asia/Tokyo' => 'JST (Tokyo)',
        'Australia/Sydney' => 'AEDT/AEST (Sydney)',
    ],
    'display_format' => 'M j, Y g:i A T',
    'date_format' => 'M j, Y',
    'time_format' => 'g:i A',
];
```

### 3. Core Services

#### Timezone Service
```php
// app/Services/TimezoneService.php
class TimezoneService
{
    public function convertToUserTimezone(Carbon $date, ?User $user = null): Carbon
    public function formatForUser(Carbon $date, ?User $user = null, string $format = null): string
    public function getUserTimezone(?User $user = null): string
    public function getAvailableTimezones(): array
    public function validateTimezone(string $timezone): bool
}
```

#### Timezone Middleware
```php
// app/Http/Middleware/SetUserTimezone.php
class SetUserTimezone
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            config(['app.timezone' => $user->timezone ?? config('timezone.default')]);
        }
        return $next($request);
    }
}
```

### 4. User Model Updates

#### Model Changes
```php
// app/Models/User.php additions
protected $fillable = [
    // existing fields...
    'timezone',
];

protected $casts = [
    // existing casts...
    'timezone' => 'string',
];

public function getTimezone(): string
{
    return $this->timezone ?? config('timezone.default');
}

public function formatDate(Carbon $date, string $format = null): string
{
    return app(TimezoneService::class)->formatForUser($date, $this, $format);
}
```

### 5. Blade Components

#### Timezone-Aware Date Component
```php
// app/View/Components/DateTime.php
class DateTime extends Component
{
    public function __construct(
        public Carbon $date,
        public ?string $format = null,
        public ?User $user = null,
        public bool $relative = false
    ) {}
}
```

#### Timezone Selector Component
```php
// app/Livewire/Components/TimezoneSelector.php
class TimezoneSelector extends Component
{
    public string $selectedTimezone;
    public array $timezones;

    public function mount()
    {
        $this->selectedTimezone = auth()->user()->timezone ?? config('timezone.default');
        $this->timezones = config('timezone.user_selectable');
    }

    public function updatedSelectedTimezone()
    {
        auth()->user()->update(['timezone' => $this->selectedTimezone]);
        $this->dispatch('timezone-updated');
    }
}
```

### 6. UI Updates

#### Account Settings Integration
- Add timezone selector to existing profile edit components
- Update `resources/views/livewire/user-profile-edit.blade.php`
- Update `app/Livewire/UserProfileEdit.php` component

#### Dashboard Updates
- Update all date displays in dashboard cards
- Update project deadline displays
- Update contest countdown timers

---

## File Structure

### New Files to Create
```
app/
├── Services/
│   └── TimezoneService.php
├── Http/
│   └── Middleware/
│       └── SetUserTimezone.php
├── View/
│   └── Components/
│       └── DateTime.php
└── Livewire/
    └── Components/
        └── TimezoneSelector.php

config/
└── timezone.php

database/
└── migrations/
    └── YYYY_MM_DD_add_timezone_to_users_table.php

resources/
└── views/
    └── components/
        ├── datetime.blade.php
        └── timezone-selector.blade.php

tests/
├── Unit/
│   ├── TimezoneServiceTest.php
│   └── UserTimezoneTest.php
├── Feature/
│   ├── TimezoneSelectionTest.php
│   └── TimezoneDisplayTest.php
└── Browser/
    └── TimezoneIntegrationTest.php
```

### Files to Update
```
config/app.php                                     # Change default timezone
app/Models/User.php                                # Add timezone functionality
app/Http/Kernel.php                                # Add timezone middleware
resources/views/livewire/user-profile-edit.blade.php # Add timezone selector
app/Livewire/UserProfileEdit.php                   # Handle timezone updates
```

---

## Testing Strategy

### Unit Tests
1. **TimezoneService Tests**
   - Test timezone conversion accuracy
   - Test date formatting with different timezones
   - Test validation of timezone strings
   - Test edge cases (DST transitions)

2. **User Model Tests**
   - Test timezone getter/setter methods
   - Test date formatting methods
   - Test default timezone fallback

### Feature Tests
1. **Profile Update Tests**
   - Test timezone selection and saving
   - Test validation of timezone values
   - Test profile completion with timezone

2. **Display Tests**
   - Test date displays in different timezones
   - Test relative time displays
   - Test consistency across components

### Integration Tests
1. **Middleware Tests**
   - Test timezone setting from user preferences
   - Test fallback to default timezone
   - Test performance impact

2. **Component Tests**
   - Test timezone selector component
   - Test datetime display component
   - Test timezone change propagation

### Browser Tests (Dusk)
1. **User Journey Tests**
   - Complete timezone selection flow
   - Verify date display updates
   - Test timezone persistence across sessions

2. **Cross-Component Tests**
   - Test timezone consistency across dashboard
   - Test project deadline displays
   - Test contest countdown accuracy

---

## Performance Considerations

### Optimization Strategies
1. **Caching**
   - Cache timezone conversions for frequently accessed dates
   - Cache user timezone preferences
   - Use Redis for timezone conversion cache

2. **Database Queries**
   - Avoid N+1 queries when converting multiple dates
   - Bulk convert dates when possible
   - Index timezone field in users table

3. **Frontend Performance**
   - Use JavaScript for real-time countdown timers
   - Minimize server-side timezone conversions for dynamic content
   - Cache timezone lists in browser storage

### Memory Management
- Limit timezone conversion cache size
- Use weak references for user timezone caching
- Regular cleanup of expired conversion cache

---

## Security Considerations

### Input Validation
- Validate timezone strings against allowed list
- Sanitize timezone input to prevent injection
- Rate limit timezone updates to prevent abuse

### Data Privacy
- Timezone is personal data - handle according to privacy policy
- Allow users to reset to default timezone
- Include timezone in data export functionality

---

## Migration Strategy

### Rollout Plan
1. **Phase 1**: Infrastructure (no user-facing changes)
2. **Phase 2**: Soft launch with select users
3. **Phase 3**: Full rollout with announcement
4. **Phase 4**: Cleanup and optimization

### Backward Compatibility
- Default timezone for existing users
- Graceful fallback for missing timezone data
- Maintain UTC storage in database

### Data Migration
```php
// Database seeder to set default timezone for existing users
User::whereNull('timezone')->update(['timezone' => 'America/New_York']);
```

---

## Progress Tracking

### Implementation Checklist

#### ✅ Foundation (Phase 1)
- [ ] Create timezone configuration file
- [ ] Update app.php with EST default
- [ ] Create timezone service class
- [ ] Create timezone middleware
- [ ] Add timezone field to users table
- [ ] Update User model with timezone methods
- [ ] Register middleware in Kernel
- [ ] Write unit tests for service

#### ✅ User Interface (Phase 2)
- [ ] Create timezone selector Livewire component
- [ ] Create timezone selector Blade template
- [ ] Update UserProfileEdit component
- [ ] Update profile edit template
- [ ] Add timezone validation rules
- [ ] Style timezone selector to match design
- [ ] Test timezone selection flow

#### ✅ Display Updates (Phase 3)
- [ ] Create DateTime Blade component
- [ ] Update dashboard card displays
- [ ] Update project deadline displays
- [ ] Update contest countdown timers
- [ ] Update payout schedule displays
- [ ] Update notification timestamps
- [ ] Update email timestamp displays

#### ✅ Backend Logic (Phase 4)
- [ ] Update project creation with timezone awareness
- [ ] Update contest deadline calculations
- [ ] Update payout processing with timezone
- [ ] Update notification scheduling
- [ ] Update search and filter logic
- [ ] Update API responses with timezone data

#### ✅ Testing & QA (Phase 5)
- [ ] Write comprehensive unit tests
- [ ] Write feature tests for timezone selection
- [ ] Write browser tests for user flow
- [ ] Performance testing
- [ ] Security testing
- [ ] Cross-browser testing
- [ ] Mobile responsive testing

---

## Success Metrics

### User Experience
- User timezone selection adoption rate
- Reduction in timezone-related support tickets
- User satisfaction with date/time displays

### Technical Metrics
- Page load time impact (target: <50ms increase)
- Database query performance
- Cache hit rates for timezone conversions

### Business Metrics
- Improved user engagement with time-sensitive features
- Better contest participation timing
- More accurate project deadline adherence

---

## Risk Mitigation

### Technical Risks
- **Performance Impact**: Extensive testing and caching strategy
- **Data Consistency**: Comprehensive testing across all components
- **Timezone Data Updates**: Regular updates to timezone data

### User Experience Risks
- **Confusion**: Clear UI and help documentation
- **Migration Issues**: Careful rollout and fallback strategies
- **Mobile Experience**: Responsive design testing

### Business Risks
- **Contest Timing**: Extra validation for contest deadlines
- **Payment Processing**: Coordinate with payout hold system
- **Support Load**: Prepare documentation and training

---

## Documentation & Training

### User Documentation
- Help article on timezone selection
- FAQ about timezone handling
- Contest timing explanation

### Developer Documentation
- Timezone service API documentation
- Best practices for timezone-aware code
- Migration guide for existing features

### Support Training
- Common timezone-related issues
- How to help users with timezone problems
- Escalation procedures for timezone bugs

---

## Future Enhancements

### Planned Features
- Automatic timezone detection from IP/browser
- Timezone-aware email scheduling
- Regional contest timing optimization
- Timezone analytics and insights

### Technical Improvements
- GraphQL timezone support
- Real-time timezone update propagation
- Advanced caching strategies
- Timezone-aware search optimization

---

*This plan will be updated as implementation progresses. Each completed item should be checked off and any issues or changes documented.*