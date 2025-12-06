# Communication Hub Implementation Plan

## Overview

| Aspect | Detail |
|--------|--------|
| Scope | Client Management workflow (initially) |
| Priority | Unified communication view first |
| Real-time | Build ready, implement later |
| Mobile | First-class support, especially for clients |

---

## Phase 1: Foundation (Unified Communication Hub)

### Objective
Create the core hub component with FAB access, unified message view, and read receipts.

### 1.1 Database Schema Updates

**Migration: add_communication_fields_to_pitch_events**
```php
// Add to pitch_events table:
- read_at (timestamp, nullable)
- read_by (json, nullable)
- delivery_status (enum: pending/delivered/read, default: delivered)
- is_urgent (boolean, default: false)
- thread_id (foreign key to pitch_events, nullable)
```

**Files:**
- `database/migrations/xxxx_add_communication_fields_to_pitch_events.php` (NEW)

### 1.2 Model Updates

**PitchEvent Model Extensions**
- Add new casts for read_at, read_by, is_urgent
- Add thread relationship (self-referential)
- Add scopes: unread(), messages(), forCommunicationHub()
- Add methods: markAsRead(), isReadBy(), isUnread()

**Files:**
- `app/Models/PitchEvent.php` (MODIFY)

### 1.3 CommunicationService

Create service to handle all communication operations:
- sendProducerMessage()
- sendClientMessage()
- markMessagesAsRead()
- getUnreadCount()
- getCommunicationFeed()
- getPendingActions()

**Files:**
- `app/Services/CommunicationService.php` (NEW)

### 1.4 Livewire Components

**CommunicationHub (Main Coordinator)**
- Manages hub open/close state
- Coordinates tabs
- Handles message sending
- Tracks unread count

**CommunicationFab (FAB Button)**
- Badge display
- Presence indicator
- Hover preview (desktop)

**CommunicationPanel (Expandable Panel)**
- Tab navigation
- Messages/Activity/Todo views
- Message input area

**Files:**
- `app/Livewire/Components/CommunicationHub.php` (NEW)
- `app/Livewire/Components/CommunicationFab.php` (NEW)
- `app/Livewire/Components/CommunicationPanel.php` (NEW)
- `resources/views/livewire/components/communication-hub.blade.php` (NEW)
- `resources/views/livewire/components/communication-fab.blade.php` (NEW)
- `resources/views/livewire/components/communication-panel.blade.php` (NEW)

### 1.5 Integration with ManageClientProject

- Add CommunicationHub component to page
- Wire up existing communication to use new service
- Ensure backwards compatibility with existing functionality

**Files:**
- `app/Livewire/Project/ManageClientProject.php` (MODIFY)
- `resources/views/livewire/project/manage-client-project.blade.php` (MODIFY)

### 1.6 Client Portal Integration

- Add CommunicationHub to client portal
- Adapt panel for mobile-first experience
- Handle signed URL context

**Files:**
- `app/Http/Controllers/ClientPortalController.php` (MODIFY)
- `resources/views/client_portal/show.blade.php` (MODIFY)

### 1.7 Polling Infrastructure

Create JavaScript module for smart polling until WebSockets are implemented:
- Normal poll interval (30s)
- Fast poll when hub is open (5s)
- Pause when tab is not visible

**Files:**
- `resources/js/communication-hub.js` (NEW)

### 1.8 Tests

- CommunicationService unit tests
- CommunicationHub Livewire tests
- Read receipt functionality tests
- Integration tests with existing workflow

**Files:**
- `tests/Unit/Services/CommunicationServiceTest.php` (NEW)
- `tests/Feature/Livewire/CommunicationHubTest.php` (NEW)

### Phase 1 Success Criteria
- [ ] FAB visible on ManageClientProject and Client Portal
- [ ] Hub opens with tabbed interface
- [ ] Messages display with read receipts
- [ ] Sending messages works from hub
- [ ] Unread count updates correctly
- [ ] Mobile responsive
- [ ] Existing communication functionality unchanged

---

## Phase 2: Trust & Reliability

### Objective
Build user confidence through smart email integration, search, and export.

### 2.1 Message Options

Add to message sending:
- Send email copy option
- Urgent flag
- Read confirmation request

**Files:**
- `app/Services/CommunicationService.php` (MODIFY)
- `resources/views/livewire/components/communication-panel.blade.php` (MODIFY)

### 2.2 Quick Actions / Templates

Create quick response system:
- Context-aware suggestions
- Custom template management
- One-click responses

**Files:**
- `app/Models/CommunicationTemplate.php` (NEW, optional)
- `app/Livewire/Components/QuickActions.php` (NEW)
- `resources/views/livewire/components/quick-actions.blade.php` (NEW)

### 2.3 Email Reply Integration

Parse incoming email replies and create PitchEvents:
- Unique reply-to addresses per conversation
- Email parsing service
- Signature stripping

**Files:**
- `app/Services/EmailReplyService.php` (NEW)
- `app/Http/Controllers/EmailWebhookController.php` (NEW)
- `routes/web.php` (MODIFY - add webhook route)

### 2.4 Search & History

Add search capability to communication hub:
- Full-text search on messages
- Date filtering
- Sender filtering

**Files:**
- `app/Livewire/Components/CommunicationSearch.php` (NEW)
- `resources/views/livewire/components/communication-search.blade.php` (NEW)

### 2.5 Export Capability

Allow exporting conversation history:
- PDF format (formatted)
- JSON format (structured)

**Files:**
- `app/Services/CommunicationExportService.php` (NEW)
- `app/Http/Controllers/CommunicationExportController.php` (NEW)

### Phase 2 Success Criteria
- [ ] Email copy option works
- [ ] Urgent messages get immediate notification
- [ ] Quick actions appear contextually
- [ ] Email replies create messages in hub
- [ ] Search finds past messages
- [ ] Export generates PDF/JSON

---

## Phase 3: Presence & Sessions

### Objective
Add work session tracking and presence indicators for transparency.

### 3.1 WorkSession Model & Migration

Create work session tracking:
- Session start/pause/end
- Time tracking
- Notes visible to client
- Focus mode

**Files:**
- `database/migrations/xxxx_create_work_sessions_table.php` (NEW)
- `app/Models/WorkSession.php` (NEW)

### 3.2 WorkSessionService

Handle all session operations:
- startSession()
- pauseSession()
- resumeSession()
- endSession()
- updateNotes()
- getActiveSession()

**Files:**
- `app/Services/WorkSessionService.php` (NEW)

### 3.3 Presence System

Cache-based presence tracking:
- Online/away/offline status
- Current project context
- Active session reference

**Files:**
- `app/Services/WorkSessionService.php` (MODIFY - add presence methods)

### 3.4 Work Session UI

Producer interface for sessions:
- Start/pause/end controls
- Session timer
- Notes input
- Visibility settings

**Files:**
- `app/Livewire/Components/WorkSessionControl.php` (NEW)
- `resources/views/livewire/components/work-session-control.blade.php` (NEW)

### 3.5 Focus Mode

Implement focus mode functionality:
- Hold messages during session
- Client notification of focus status
- Urgent message override

**Files:**
- `app/Services/CommunicationService.php` (MODIFY)
- `app/Livewire/Components/CommunicationHub.php` (MODIFY)

### 3.6 Activity Timeline

Display work history to clients:
- Session history
- Time worked per day
- Activity log

**Files:**
- `app/Livewire/Components/ActivityTimeline.php` (NEW)
- `resources/views/livewire/components/activity-timeline.blade.php` (NEW)

### 3.7 Presence Indicators

Show presence in hub and portal:
- Producer status on client portal
- Client online status (if logged in)

**Files:**
- `resources/views/livewire/components/communication-fab.blade.php` (MODIFY)
- `resources/views/livewire/components/communication-panel.blade.php` (MODIFY)

### 3.8 Presence Settings

Allow producers to control visibility:
- Full presence
- Activity summary
- Minimal presence

**Files:**
- `app/Models/User.php` (MODIFY - add presence_settings)
- `database/migrations/xxxx_add_presence_settings_to_users.php` (NEW)

### Phase 3 Success Criteria
- [ ] Producers can start/pause/end work sessions
- [ ] Session time is tracked accurately
- [ ] Clients see producer working status
- [ ] Focus mode holds messages
- [ ] Activity timeline shows work history
- [ ] Presence settings respected

---

## Phase 4: Real-Time

### Objective
Add WebSocket support for live updates.

### 4.1 Event Broadcasting Setup

Configure Laravel Reverb channels:
- pitch.{id} - per-pitch communication
- project.{id} - project-wide events
- presence channels for online status

**Files:**
- `routes/channels.php` (MODIFY)
- `config/reverb.php` (MODIFY if needed)

### 4.2 Broadcast Events

Create events for real-time updates:
- CommunicationMessageSent
- MessagesRead
- WorkSessionStarted/Paused/Ended
- PresenceChanged

**Files:**
- `app/Events/CommunicationMessageSent.php` (NEW)
- `app/Events/MessagesRead.php` (NEW)
- `app/Events/WorkSessionStarted.php` (NEW)
- `app/Events/WorkSessionPaused.php` (NEW)
- `app/Events/WorkSessionEnded.php` (NEW)
- `app/Events/PresenceChanged.php` (NEW)

### 4.3 Frontend WebSocket Integration

Update JavaScript to use Echo:
- Subscribe to channels
- Handle incoming events
- Update Livewire state

**Files:**
- `resources/js/communication-hub.js` (MODIFY)
- `resources/js/bootstrap.js` (MODIFY - ensure Echo configured)

### 4.4 Graceful Degradation

Ensure polling fallback works:
- Detect WebSocket failure
- Fall back to polling
- Reconnection logic

**Files:**
- `resources/js/communication-hub.js` (MODIFY)

### 4.5 Typing Indicators (Optional)

If desired, add typing indicators:
- Throttled typing events
- Display "X is typing..."
- Auto-clear after timeout

**Files:**
- `app/Events/UserTyping.php` (NEW)
- `resources/views/livewire/components/communication-panel.blade.php` (MODIFY)

### Phase 4 Success Criteria
- [ ] Messages appear instantly for both parties
- [ ] Read receipts update live
- [ ] Presence changes reflected immediately
- [ ] Falls back to polling if WebSocket fails
- [ ] No duplicate messages or events

---

## File Summary by Phase

### Phase 1 Files
| File | Type |
|------|------|
| `database/migrations/xxxx_add_communication_fields_to_pitch_events.php` | NEW |
| `app/Models/PitchEvent.php` | MODIFY |
| `app/Services/CommunicationService.php` | NEW |
| `app/Livewire/Components/CommunicationHub.php` | NEW |
| `app/Livewire/Components/CommunicationFab.php` | NEW |
| `app/Livewire/Components/CommunicationPanel.php` | NEW |
| `resources/views/livewire/components/communication-hub.blade.php` | NEW |
| `resources/views/livewire/components/communication-fab.blade.php` | NEW |
| `resources/views/livewire/components/communication-panel.blade.php` | NEW |
| `resources/js/communication-hub.js` | NEW |
| `app/Livewire/Project/ManageClientProject.php` | MODIFY |
| `resources/views/livewire/project/manage-client-project.blade.php` | MODIFY |
| `app/Http/Controllers/ClientPortalController.php` | MODIFY |
| `resources/views/client_portal/show.blade.php` | MODIFY |
| `tests/Unit/Services/CommunicationServiceTest.php` | NEW |
| `tests/Feature/Livewire/CommunicationHubTest.php` | NEW |

### Phase 2 Files
| File | Type |
|------|------|
| `app/Livewire/Components/QuickActions.php` | NEW |
| `resources/views/livewire/components/quick-actions.blade.php` | NEW |
| `app/Services/EmailReplyService.php` | NEW |
| `app/Http/Controllers/EmailWebhookController.php` | NEW |
| `app/Livewire/Components/CommunicationSearch.php` | NEW |
| `resources/views/livewire/components/communication-search.blade.php` | NEW |
| `app/Services/CommunicationExportService.php` | NEW |
| `app/Http/Controllers/CommunicationExportController.php` | NEW |

### Phase 3 Files
| File | Type |
|------|------|
| `database/migrations/xxxx_create_work_sessions_table.php` | NEW |
| `database/migrations/xxxx_add_presence_settings_to_users.php` | NEW |
| `app/Models/WorkSession.php` | NEW |
| `app/Services/WorkSessionService.php` | NEW |
| `app/Livewire/Components/WorkSessionControl.php` | NEW |
| `resources/views/livewire/components/work-session-control.blade.php` | NEW |
| `app/Livewire/Components/ActivityTimeline.php` | NEW |
| `resources/views/livewire/components/activity-timeline.blade.php` | NEW |

### Phase 4 Files
| File | Type |
|------|------|
| `routes/channels.php` | MODIFY |
| `app/Events/CommunicationMessageSent.php` | NEW |
| `app/Events/MessagesRead.php` | NEW |
| `app/Events/WorkSessionStarted.php` | NEW |
| `app/Events/WorkSessionPaused.php` | NEW |
| `app/Events/WorkSessionEnded.php` | NEW |
| `app/Events/PresenceChanged.php` | NEW |

---

## Dependencies & Order

```
Phase 1: Foundation
    ├── 1.1 Database Schema (no deps)
    ├── 1.2 Model Updates (depends on 1.1)
    ├── 1.3 CommunicationService (depends on 1.2)
    ├── 1.4 Livewire Components (depends on 1.3)
    ├── 1.5 ManageClientProject Integration (depends on 1.4)
    ├── 1.6 Client Portal Integration (depends on 1.4)
    ├── 1.7 Polling Infrastructure (depends on 1.4)
    └── 1.8 Tests (depends on all above)

Phase 2: Trust & Reliability (depends on Phase 1)
    ├── 2.1-2.2 can be parallel
    ├── 2.3 Email Reply (independent)
    ├── 2.4-2.5 can be parallel

Phase 3: Presence & Sessions (depends on Phase 1)
    ├── 3.1-3.2 Model & Service first
    ├── 3.3-3.7 can be somewhat parallel
    └── 3.8 Settings last

Phase 4: Real-Time (depends on Phase 1, benefits from 2 & 3)
    ├── 4.1-4.2 Event setup first
    ├── 4.3 Frontend integration
    ├── 4.4 Fallback logic
    └── 4.5 Optional typing indicators
```

---

## Estimated Effort

| Phase | Components | Estimated Effort |
|-------|------------|------------------|
| Phase 1 | 8 | High (core functionality) |
| Phase 2 | 5 | Medium |
| Phase 3 | 8 | Medium-High |
| Phase 4 | 5 | Medium |

**Recommendation**: Complete Phase 1 fully before starting others. Phases 2 and 3 can be worked on in parallel after Phase 1.

---

## Risks & Mitigations

| Risk | Mitigation |
|------|------------|
| Breaking existing communication | Extensive testing, feature flags |
| Performance with many messages | Pagination, lazy loading |
| Mobile responsiveness issues | Mobile-first design, thorough testing |
| WebSocket reliability | Polling fallback always available |
| Email parsing complexity | Start with simple parsing, iterate |
