# Current Communication System Analysis

## Overview

The existing client management communication system is functional but fragmented. Communication happens through multiple channels and UI locations, which can lead to uncertainty about whether messages are being seen.

---

## Communication Channels

### 1. Producer to Client

**Location**: `ManageClientProject.php` (lines 1350-1392)

**Method**: `addProducerComment()`
- Creates `PitchEvent` with `event_type: 'producer_comment'`
- Metadata includes `visible_to_client: true`, `comment_type: 'producer_update'`
- Character limit: 2000
- Triggers email notification to client via `NotificationService->notifyClientProducerCommented()`

**Flow**:
```
Producer writes message in ManageClientProject
    ↓
PitchEvent created with metadata
    ↓
Email sent to client with signed portal URL (7-day expiry)
    ↓
Client clicks link → lands on portal → sees message in feed
```

### 2. Client to Producer

**Location**: `ClientPortalController.php` (lines 322-355)

**Method**: `storeComment()`
- Creates `PitchEvent` with `event_type: 'client_comment'`
- Metadata includes `client_email` (extracted from signed URL or auth)
- Character limit: 5000
- Triggers email notification to producer via `NotificationService->notifyProducerClientCommented()`

**Flow**:
```
Client submits form in portal
    ↓
PitchEvent created
    ↓
Email sent to producer
    ↓
Producer sees in-app notification + message in timeline
```

### 3. File-Specific Feedback

**Model**: `FileComment` (separate from PitchEvent)
- Polymorphic relationship with `PitchFile`
- Supports threading via `parent_id`
- Fields: `comment`, `timestamp`, `resolved`, `is_client_comment`
- Tracked in `ManageClientProject->fileCommentsData`

### 4. Revision Requests

**Location**: `ClientPortalController->requestRevisions()` (lines 675-699)
- Client provides feedback (max 5000 chars)
- Calls `PitchWorkflowService->clientRequestRevisions()`
- Creates `PitchEvent` with `event_type: 'client_revisions_requested'`
- Changes pitch status to `CLIENT_REVISIONS_REQUESTED`

### 5. Response to Feedback

**Component**: `ResponseToFeedback.php`
- Dedicated component for producer responses to revision requests
- Creates events with `comment_type: 'feedback_response'`
- Shows history of previous responses

---

## Data Model: PitchEvent

```php
// app/Models/PitchEvent.php
Schema:
- id
- pitch_id
- event_type          // Classification of event
- comment             // Text content
- status              // Pitch status at time of event
- created_by          // User ID (nullable for client events)
- metadata            // JSON field for additional context
- rating              // Optional (for completed projects)
- created_at
- updated_at

Event Types Used for Communication:
- 'client_comment'            // Client message from portal
- 'producer_comment'          // Producer message to client
- 'client_revisions_requested' // Client requests changes
- 'status_change'             // Workflow transitions
- 'file_uploaded'             // File activity
- 'client_approved'           // Client approval
```

---

## Notification System

### Producer Notifications

| Method | Type Constant | Email Method | Trigger |
|--------|---------------|--------------|---------|
| `notifyProducerClientCommented()` | `TYPE_CLIENT_COMMENT_ADDED` | `sendProducerClientCommented()` | Client sends message |
| `notifyProducerClientRevisionsRequested()` | `TYPE_CLIENT_REQUESTED_REVISIONS` | `sendProducerClientRevisionsRequested()` | Client requests revisions |
| `notifyProducerClientApproved()` | `TYPE_CLIENT_APPROVED_PITCH` | - | Client approves |
| `notifyProducerClientApprovedAndCompleted()` | - | `sendProducerClientApprovedAndCompletedEmail()` | Project completed |

### Client Notifications

| Method | Type | Notes |
|--------|------|-------|
| `notifyClientProducerCommented()` | Email only | No database record, includes signed portal URL |
| `notifyClientProjectInvite()` | Email only | Initial portal invite |
| `notifyClientProjectCompleted()` | Email only | Completion notification |

### Email Preferences

Stored in `Project->client_email_preferences` (JSON column):
- `revision_confirmation` - When client requests revisions
- `producer_resubmitted` - When producer uploads updates
- `payment_receipt` - After payments processed

---

## UI Components

### Producer Side

**ManageClientProject Component**:
- Communication timeline with colored event markers
- Producer comment form (textarea, max 2000 chars)
- Message deletion capability (own messages only)
- Event icon/color system:
  - Blue: client messages
  - Purple: producer messages
  - Green: approvals
  - Amber: revision requests
  - Gray: status updates
  - Indigo: file activities

**ResponseToFeedback Component**:
- Dedicated response form for revision requests
- Previous responses history

### Client Side

**communication-card.blade.php**:
- Simple comment form with textarea
- Activity history showing all event types
- Timeline ordered newest first

**client-communication-hub.blade.php**:
- Advanced communication interface
- Message form with collapsed/expanded states (Alpine.js)
- Producer info badge
- Color-coded event types
- "Send Message to Producer" button

---

## Real-Time Status

### Current: No Real-Time

- Reverb is configured but **not actively used** for communication
- No WebSocket channels broadcasting PitchEvent updates
- No real-time event listeners on client-side

### Existing Livewire Events

```php
// ManageClientProject.php
#[On('filesUploaded')]      // File upload handler
#[On('fileDeleted')]        // File deletion handler
#[On('requestCommentsRefresh')] // Comment refresh

// Refresh mechanisms
refreshCommentsForFileList()  // Refreshes file comments
refreshCommentsOnly()         // Preserves Alpine.js state
$refreshKey                   // Forces child component re-renders
```

---

## Identified Gaps

| Gap | Impact | Priority |
|-----|--------|----------|
| No unified communication view | Users check multiple places | High |
| No read receipts | Uncertainty if message was seen | High |
| No real-time updates | Feels like email back-and-forth | Medium |
| Split comment systems | PitchEvent vs FileComment confusion | Medium |
| No presence indicators | Can't tell if other party is active | Medium |
| Plain text only | Limited expressiveness | Low |
| No message search | Hard to find past conversations | Low |
| No threaded general comments | Complex discussions hard to follow | Low |

---

## Files Reference

### Backend
- `app/Livewire/Project/ManageClientProject.php`
- `app/Livewire/ResponseToFeedback.php`
- `app/Http/Controllers/ClientPortalController.php`
- `app/Services/NotificationService.php`
- `app/Services/EmailService.php`
- `app/Services/PitchWorkflowService.php`
- `app/Models/PitchEvent.php`
- `app/Models/FileComment.php`

### Frontend
- `resources/views/livewire/project/manage-client-project.blade.php`
- `resources/views/client_portal/show.blade.php`
- `resources/views/client_portal/partials/communication-card.blade.php`
- `resources/views/client_portal/partials/client-communication-hub.blade.php`

### Configuration
- `config/reverb.php` - WebSocket configuration (configured but unused)
