# Communication Hub Feature

## Overview

The Communication Hub is a unified communication system for MixPitch's Client Management workflow, designed to make MixPitch the central platform for all producer-client communication.

## Vision Statement

**Users should feel confident that if they choose to utilize MixPitch as their central communication platform for client projects, they won't miss any communications.**

This means:
- All communication in one place
- Clear delivery and read status
- Email as a backup channel, not the primary one
- Complete, searchable history
- Transparency without intrusion

## Documentation Structure

| Document | Purpose |
|----------|---------|
| [CURRENT_STATE.md](./CURRENT_STATE.md) | Analysis of existing communication features |
| [VISION.md](./VISION.md) | Goals, principles, and platform comparisons |
| [FEATURES.md](./FEATURES.md) | Detailed feature specifications |
| [UI_DESIGN.md](./UI_DESIGN.md) | Visual paradigms and UI patterns |
| [TECHNICAL.md](./TECHNICAL.md) | Data models, architecture, real-time considerations |
| [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md) | Phased implementation approach |
| [PROGRESS.md](./PROGRESS.md) | Implementation progress tracker |

## Key Design Decisions

1. **FAB-Based Access** - Floating Action Button as entry point to the hub
2. **Hub, Not Chat** - More than messaging; includes activity, notifications, presence
3. **Real-Time Ready** - Build for polling now, easy WebSocket upgrade later
4. **Focus Mode** - Producers can control their availability/visibility
5. **Email Integration** - Emails drive users back to platform, not replace it

## Priority Features

### Phase 1: Foundation (Unified Communication Hub)
- Hub Component with FAB access
- Unified event view
- Read receipts
- Enhanced PitchEvent model

### Phase 2: Trust & Reliability
- Smart email integration
- Message options (urgent, email copy)
- Search & history
- Export capability

### Phase 3: Presence & Sessions
- Work sessions with time tracking
- Focus mode
- Activity timeline
- Presence settings

### Phase 4: Real-Time
- WebSocket integration via Reverb
- Live updates
- Typing indicators (optional)

## For Agents Continuing This Work

1. Read [CURRENT_STATE.md](./CURRENT_STATE.md) to understand existing implementation
2. Review [VISION.md](./VISION.md) for design principles
3. Check [PROGRESS.md](./PROGRESS.md) for current status
4. Reference [TECHNICAL.md](./TECHNICAL.md) for code patterns
5. Follow phases in [IMPLEMENTATION_PLAN.md](./IMPLEMENTATION_PLAN.md)

## Related Files

### Current Communication Implementation
- `app/Livewire/Project/ManageClientProject.php` - Producer interface
- `app/Http/Controllers/ClientPortalController.php` - Client portal backend
- `app/Services/NotificationService.php` - Notification orchestration
- `app/Models/PitchEvent.php` - Communication data model
- `resources/views/client_portal/` - Client-facing views

### Key Blade Components
- `resources/views/client_portal/partials/communication-card.blade.php`
- `resources/views/client_portal/partials/client-communication-hub.blade.php`
