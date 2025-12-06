# Client Management Workflow - Current Implementation

> **Last Updated:** December 5, 2024 (Phase 2.1 Bulk Download Added)
> **Status:** Working Document - Update as implementation changes

This document describes the current implementation of the Client Management workflow in MixPitch. It serves as a living reference for understanding how the system works today.

---

## Table of Contents

1. [Overview](#overview)
2. [User Roles & Access](#user-roles--access)
3. [Project Creation Flow](#project-creation-flow)
4. [Producer Experience](#producer-experience)
5. [Client Portal Experience](#client-portal-experience)
6. [State Machine & Workflow](#state-machine--workflow)
7. [File Management](#file-management)
8. [Communication System](#communication-system)
9. [Payment & Milestones](#payment--milestones)
10. [Email Notifications](#email-notifications)
11. [Technical Architecture](#technical-architecture)

---

## Overview

Client Management is a workflow type (`WORKFLOW_TYPE_CLIENT_MANAGEMENT`) designed for producers to work with external clients on audio projects. Unlike the standard marketplace workflow, this is a private, one-to-one collaboration where:

- Projects are **never published** to the marketplace
- Clients access via **signed URLs** (no account required)
- Workflow focuses on **deliverable review and approval**
- Payment is **direct** (single payment or milestone-based)
- Payout hold period is **0 days** (immediate after payment)

### Key Differentiators from Standard Workflow

| Feature | Standard Workflow | Client Management |
|---------|------------------|-------------------|
| Visibility | Public marketplace | Private (signed URLs) |
| Initial approval | Project owner approves pitch | Skipped entirely |
| Primary reviewer | Project owner | External client |
| Revisions | Snapshot-based | Milestone-based with pricing |
| Included revisions | N/A | Configurable count |
| Payout hold | 1 day | 0 days (immediate) |

---

## User Roles & Access

### Producer (Authenticated User)
- Creates and manages client projects
- Uploads deliverables
- Responds to feedback
- Manages milestones and payments
- Access via: Standard authentication

### Client (External)
- Reviews producer submissions
- Approves or requests revisions
- Uploads reference files
- Makes payments
- Access via: **Signed URLs** (7-day expiry, configurable)

### Client Account Upgrade
Clients can optionally create an account from the portal:
- Route: `client.portal.upgrade`
- Email auto-verified
- Existing projects auto-linked
- Future access without signed URLs

---

## Project Creation Flow

### Entry Points

1. **Dashboard Quick Modal** (`QuickProjectModal.php`)
   - Fastest path: ~30 seconds to create
   - Minimal required fields

2. **Client Management Dashboard** (`/producer/clients`)
   - "Add Client" → "Create Project" flow
   - CRM-style client management

3. **Full Wizard** (`CreateProject.php`)
   - 4-step comprehensive wizard
   - More configuration options

### Required Fields
- Project name
- Client email

### Optional Fields
- Client name
- Payment amount (defaults to $0)
- Artist name
- Project type
- Genre
- Description

### Automatic Setup
On creation:
1. `workflow_type` → `WORKFLOW_TYPE_CLIENT_MANAGEMENT`
2. `status` → `UNPUBLISHED`
3. Pitch auto-created for producer (via `ProjectObserver`)
4. Client invite email sent (if email provided)

---

## Producer Experience

### Main Interface
**Component:** `ManageClientProject.php` (2,236 lines)
**View:** `manage-client-project.blade.php`
**Route:** `/projects/{slug}/manage-client`

### Interface Layout

```
┌─────────────────────────────────────────────────────────────┐
│ Project Header (title, status badge, actions)               │
├─────────────────────────┬───────────────────────────────────┤
│                         │                                   │
│  Main Content Area      │  Right Sidebar                    │
│  ─────────────────────  │  ─────────────────────            │
│  • File Upload (Uppy)   │  • Project Details Card           │
│  • Deliverables Tab     │  • Client Information             │
│  • Client Files Tab     │  • License Settings               │
│  • File List            │  • Deadline                       │
│  • Submit Section       │  • Email Preferences              │
│  • Communication Hub    │  • Billing Tracker                │
│  • Response to Feedback │  • Milestone Manager              │
│                         │                                   │
└─────────────────────────┴───────────────────────────────────┘
```

### Key Features

#### File Management
- **Uppy.js** multipart uploads to S3/R2
- Drag-and-drop interface
- Progress tracking with resume capability
- Two tabs: "Your Deliverables" and "Client Reference Files"

#### Version Control
- Snapshots created on each submission
- Version history dropdown
- Can view historical submissions
- Files marked with deletion indicators when viewing history

#### Watermarking
- Toggle for audio protection
- Affects: mp3, wav, m4a, aac, flac
- Applied at submission time
- Removed after approval/payment

#### Inline Editing
All project details editable without page refresh:
- Project title, description, artist name, genre, notes
- Client email, client name, payment amount
- License settings
- Deadline (with timezone conversion)

### Sub-Components

| Component | Purpose |
|-----------|---------|
| `ClientSubmitSection` | Submit/recall buttons, watermarking UI |
| `ResponseToFeedback` | Feedback response form, comment summary |
| `ProjectDetailsCard` | Project metadata, client info editing |
| `MilestoneManager` | Milestone CRUD, budget splitting |
| `ProjectBillingTracker` | Payment tracking, manual payment recording |
| `ClientProjectSetupChecklist` | Progress checklist badge |

---

## Client Portal Experience

### Access Mechanism

**Signed URLs:**
- Generated via `URL::temporarySignedRoute()`
- Default expiry: 7 days (configurable: `mixpitch.client_portal_link_expiry_days`)
- Middleware: `EnsureSignedOrClientAccess`

**URL Format:**
```
/projects/{id}/portal?signature=...&expires=...
```

### Portal Layout

```
┌─────────────────────────────────────────────────────────────┐
│ Header (Project Title, Producer Info)                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Project Progress Card                                      │
│  ─────────────────────                                      │
│  Visual status overview                                     │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Producer Deliverables Card (Tabbed)                        │
│  ─────────────────────────────────                          │
│  Tab 1: Your Reference Files (client uploads)               │
│  Tab 2: Producer Deliverables (submissions)                 │
│         - Version history cards                             │
│         - File list with comments                           │
│         - Audio player with waveforms                       │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Review & Approval Card                                     │
│  ─────────────────────                                      │
│  • Approve button / Request Revisions button                │
│  • Revision count display with pricing info                 │
│  • Producer's Response section (purple, with status)        │
│  • File Comments Summary (resolved/unresolved per file)     │
│  • "Awaiting response..." state with pulse animation        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Communication Hub                                          │
│  ─────────────────                                          │
│  • Message timeline with feedback response highlighting     │
│  • Send message form                                        │
│                                                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  Email Preferences Accordion                                │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### Client Actions

| Action | Route | Description |
|--------|-------|-------------|
| View submissions | `GET /projects/{id}/portal` | Main portal view |
| Approve file | `POST .../files/{id}/approve` | Individual file approval |
| Approve all | `POST .../files/approve-all` | Bulk file approval |
| Approve pitch | `POST .../approve` | Approve entire submission |
| Request revisions | `POST .../request-revisions` | Submit feedback modal |
| Add comment | `POST .../comments` | General message |
| Upload file | `POST .../upload` | Reference file upload |
| Pay milestone | `POST .../milestones/{id}/approve` | Stripe checkout |

### Livewire Components (Client Portal)

| Component | Purpose |
|-----------|---------|
| `ProducerDeliverables` | Snapshot navigation, file approval |
| `FileManager` | File viewing, commenting |
| `LicenseAgreementModal` | License acceptance workflow |
| `PostApprovalSuccessCard` | Completion messaging |

---

## State Machine & Workflow

### Pitch Status States

```
┌──────────────────┐
│   IN_PROGRESS    │  Producer uploads files
└────────┬─────────┘
         │ submitForReview()
         ▼
┌──────────────────┐
│ READY_FOR_REVIEW │  Client reviews submission
└────────┬─────────┘
         │
    ┌────┴────┐
    │         │
    ▼         ▼
┌────────┐  ┌─────────────────────────┐
│APPROVED│  │CLIENT_REVISIONS_REQUESTED│◄──┐
└───┬────┘  └────────────┬────────────┘   │
    │                    │                 │
    │                    │ submitForReview()
    │                    └─────────────────┘
    │
    │ (all milestones paid OR no payment required)
    ▼
┌──────────────────┐
│    COMPLETED     │  Project finished
└──────────────────┘
```

### Key Service Methods

**PitchWorkflowService:**

```php
// Producer submits work
submitPitchForReview($pitch, $user, $files, $responseToFeedback)

// Client approves
clientApprovePitch($pitch, $clientIdentifier)

// Client requests changes
clientRequestRevisions($pitch, $feedback, $clientIdentifier)

// Producer recalls submission
recallSubmission($pitch)
```

### Snapshot System

Each submission creates an immutable `PitchSnapshot`:

```php
$snapshotData = [
    'version' => 1,              // Increments per submission
    'file_ids' => [...],         // Files in this snapshot
    'response_to_feedback' => '', // Producer's response
    'previous_snapshot_id' => null,
];
```

**Snapshot Statuses:**
- `STATUS_PENDING` - Awaiting client review
- `STATUS_ACCEPTED` - Client approved
- `STATUS_REVISIONS_REQUESTED` - Client requested changes
- `STATUS_REVISION_ADDRESSED` - Producer resubmitted

---

## File Management

### Producer Files (PitchFile)

**Model:** `PitchFile`
**Storage:** S3/Cloudflare R2
**Upload:** Multipart via Uppy.js

**Key Fields:**
- `pitch_id` - Parent pitch
- `file_path` - S3 path
- `original_filename` - User-facing name
- `size` - Bytes
- `mime_type` - File type
- `waveform_data` - Audio visualization JSON
- `client_approval_status` - null | 'approved'
- `client_approved_at` - Timestamp
- `revision_round` - Which revision created this
- `superseded_by_revision` - True if replaced

### Client Files (ProjectFile)

**Model:** `ProjectFile`
**Context:** `CONTEXT_CLIENT_PORTALS`

**Key Fields:**
- `project_id` - Parent project
- `uploaded_by_client` - true for client uploads
- `client_email` - Uploader identifier
- `metadata` - JSON with upload context

### File Access Control

```php
// Files accessible to client based on payment status
$pitch->getAccessibleFilesForClient()

// If unpaid revision milestones exist:
//   → Only files from revisions ≤ last paid round
// Else:
//   → All non-superseded files
```

### Watermarking

- Audio files (mp3, wav, m4a, aac, flac)
- Toggle in producer UI before submission
- `PitchFile->shouldServeWatermarked()` determines serving
- Watermarked version stored at `processed_file_path`
- Clean version served after approval + payment

### Bulk Download

**Purpose:** Download multiple files as a single ZIP archive

**Architecture:** Cloudflare Workers + Queue for efficient streaming ZIP creation

**Components:**

1. **BulkDownloadService** (`app/Services/BulkDownloadService.php`)
   - Validates file IDs and authorization via Policy classes
   - Enforces 4GB total file size limit
   - Creates `BulkDownload` database record with UUID
   - Sends message to Cloudflare Queue for async processing

2. **Cloudflare Worker** (`cloudflare-workers/bulk-download-consumer/`)
   - Consumes queue messages
   - Streams files from R2 into ZIP using `fflate` library
   - Uses R2 multipart upload (5MB chunks)
   - Sends webhook callback to Laravel when complete

3. **BulkDownloadController** (`app/Http/Controllers/Api/BulkDownloadController.php`)
   - `POST /api/bulk-download/callback` - Webhook from Cloudflare (HMAC-SHA256 signed)
   - `GET /bulk-download/{id}/status` - Polling endpoint for UI
   - `GET /bulk-download/{id}/download` - Redirect to presigned URL

4. **Frontend Polling** (`resources/js/bulk-download.js`)
   - Polls status endpoint every 3 seconds
   - 5-minute timeout
   - Redirects to download URL when ready

**BulkDownload Model:**

```php
// Key fields
$table->uuid('id')->primary();
$table->foreignId('user_id');
$table->json('file_ids');
$table->string('archive_name');
$table->string('status'); // pending, processing, completed, failed
$table->string('storage_path')->nullable();
$table->string('download_url')->nullable();
$table->timestamp('download_url_expires_at')->nullable();
$table->string('error_message')->nullable();
$table->timestamp('completed_at')->nullable();
```

**Usage in ManageClientProject:**

```php
// Producer downloads pitch files
public function bulkDownloadFiles(array $fileIds) {
    $archiveId = app(BulkDownloadService::class)
        ->requestBulkDownload($fileIds, 'pitch');
    $this->dispatch('bulk-download-started', archiveId: $archiveId);
}

// Client downloads project files
public function bulkDownloadClientFiles(array $fileIds) {
    $archiveId = app(BulkDownloadService::class)
        ->requestBulkDownload($fileIds, 'project');
    $this->dispatch('bulk-download-started', archiveId: $archiveId);
}
```

**Limits & Expiry:**
- **Max archive size:** 4GB
- **Archive expiry:** 24 hours (cleaned by `CleanupOldBulkDownloads` job)
- **Download URL expiry:** 60 minutes (regenerated on demand)

**Supported Contexts:**
- `'pitch'` - PitchFile model (uses `PitchFilePolicy::downloadFile`)
- `'project'` - ProjectFile model (uses `ProjectFilePolicy::download`)

---

## Communication System

### Two Communication Channels

#### 1. General Messages (PitchEvent)

**Producer → Client:**
- Method: `addProducerComment()` in `ManageClientProject`
- Creates: `PitchEvent` with `event_type: 'producer_comment'`
- Metadata: `visible_to_client: true, comment_type: 'producer_update'`
- Notification: `notifyClientProducerCommented()`

**Client → Producer:**
- Route: `POST /client-portal/project/{id}/comments`
- Creates: `PitchEvent` with `event_type: 'client_comment'`
- Metadata: `client_email: '...'`
- Notification: `notifyProducerClientCommented()`

#### 2. File Comments (FileComment)

**Model:** `FileComment` (polymorphic)

**Key Fields:**
- `commentable_type/id` - Polymorphic (PitchFile, ProjectFile)
- `user_id` - Producer (null for clients)
- `parent_id` - For replies (threading)
- `comment` - Text content
- `timestamp` - Audio position (seconds)
- `resolved` - Boolean for tracking
- `client_email` - Client identifier
- `is_client_comment` - Boolean flag

**Features:**
- Threaded replies via `parent_id`
- Resolution tracking
- Auto-resolve on producer response
- Per-file aggregation in UI

### Communication Hub Components

**Producer View:** `client-communication-hub.blade.php` (components/client-project/)
- Shows all PitchEvents
- Send message form
- Delete own messages
- **Feedback responses highlighted** with purple gradient and "Your Response to Feedback" label

**Client View:** `client-communication-hub.blade.php` (client_portal/components/)
- Shows all PitchEvents
- Send message form (via signed route)
- **Feedback responses highlighted** with purple gradient and "Response to Your Feedback" label

### ResponseToFeedback Component

**Purpose:** Producer responds to revision feedback

**Displays:**
1. Client's revision feedback (from `client_revisions_requested` event)
2. File comments summary (unresolved count per file)
3. Previous responses history
4. Response form

**Creates:** `producer_comment` event with `comment_type: 'feedback_response'`

### Feedback Response Visibility (Client Portal)

**Location:** `review-approval-card.blade.php` (client_portal/components/)

When client is in `CLIENT_REVISIONS_REQUESTED` status, they see:

1. **Their submitted feedback** (amber/white styling)
2. **Producer's Response section:**
   - If producer has responded: Purple card with response text and "Received" badge
   - If awaiting: Gray card with "Awaiting producer's response..." and pulse animation
3. **File Comments Summary:**
   - Shows "X of Y addressed" badge
   - Per-file breakdown with resolved/unresolved counts
   - "All addressed" success badge or "X pending" warning badge

**Technical Implementation:**
```php
// Query for producer's feedback response
$producerResponse = $pitch->events()
    ->where('event_type', 'producer_comment')
    ->whereJsonContains('metadata->comment_type', 'feedback_response')
    ->where('created_at', '>', $latestFeedbackEvent->created_at)
    ->orderBy('created_at', 'desc')
    ->first();

// Query for file comments summary
$fileComments = FileComment::whereHasMorph(
    'commentable',
    [PitchFile::class],
    fn($query) => $query->where('pitch_id', $pitch->id)
)
->where('is_client_comment', true)
->whereNull('parent_id')
->get();
```

---

## Payment & Milestones

### Payment Models

#### Single Payment
- `pitch->payment_amount` set on project
- Client pays full amount on approval
- Stripe Checkout session created

#### Milestone-Based Payment
- Multiple `PitchMilestone` records
- Sequential payment (must pay in order)
- Each milestone paid separately

### PitchMilestone Model

**Key Fields:**
- `pitch_id` - Parent pitch
- `name` - Milestone identifier
- `description` - Details
- `amount` - Payment amount
- `sort_order` - Sequential order
- `status` - 'pending' | 'approved'
- `payment_status` - null | 'paid' | 'processing'
- `stripe_invoice_id` - Stripe reference
- `is_revision_milestone` - For additional revisions
- `revision_round_number` - Links to revision
- `revision_request_details` - Client's feedback

### MilestoneManager Component

**Features:**
- Create, edit, delete milestones
- Prevent editing/deleting paid milestones
- Budget splitting templates:
  - Equal split
  - Percentage split
  - Deposit structure (30-40-30)
- Revision policy configuration:
  - Included revisions count
  - Additional revision price
  - Scope guidelines

### ProjectBillingTracker Component

**Features:**
- Payment summary statistics
- Manual payment recording
- Invoice viewing
- Payment timeline (recent 10 events)

### Revision Pricing

```php
// Check if revision is free
isRevisionFree($pitch) {
    return ($pitch->revisions_used + 1) <= $pitch->included_revisions;
}

// If paid revision, creates milestone with:
// - name: "Revision Round {N}"
// - amount: additional_revision_price
// - is_revision_milestone: true
```

### Payout Flow

1. Client pays via Stripe Checkout
2. `PayoutProcessingService::schedulePayoutForMilestone()`
3. For manual payments: immediate completion, no commission
4. For Stripe: commission calculated, hold period applied
5. `ProcessScheduledPayouts` job transfers to producer's Stripe Connect

---

## Email Notifications

### Client Emails (TO Client)

| Email | Trigger | Description |
|-------|---------|-------------|
| `ClientProjectInvite` | Project creation | Portal link with signed URL |
| `ClientReviewReady` | Producer submits | Work ready for review |
| `ClientProducerComment` | Producer messages | New message notification |
| `RevisionRequestConfirmation` | Client requests revision | Confirms feedback received |
| `ProducerResubmitted` | Producer resubmits | Updated work ready |
| `ClientProjectCompleted` | Project completed | Final notification |
| `ClientPaymentReceipt` | Payment processed | Receipt and invoice |

### Producer Emails (TO Producer)

| Email | Trigger | Description |
|-------|---------|-------------|
| `ClientRevisionsRequested` | Client requests revision | Action required with feedback |
| `ClientCommented` | Client messages | New message notification |
| `ProducerClientApprovedAndCompleted` | Client approves | Celebration + payment info |
| `PaymentReceived` | Payment processed | Earnings breakdown |

### Email Preferences

**Per-Project Configuration:**
- Stored in `project->client_email_preferences` (JSON)
- Client can toggle: `revision_confirmation`, `producer_resubmitted`, `payment_receipt`
- Producer can toggle: `producer_revisions_requested`, `producer_client_commented`, `payment_received`

**Global Configuration:**
- `config('business.email_notifications.client_management.enabled')` - Master toggle

---

## Technical Architecture

### Key Files

**Livewire Components:**
```
app/Livewire/Project/
├── ManageClientProject.php          # Main producer interface
├── Component/
│   ├── ClientSubmitSection.php      # Submit/recall UI
│   ├── ResponseToFeedback.php       # Feedback response
│   ├── ProjectDetailsCard.php       # Project metadata
│   ├── MilestoneManager.php         # Milestone CRUD
│   └── ProjectBillingTracker.php    # Payment tracking

app/Livewire/ClientPortal/
├── ProducerDeliverables.php         # Submission viewing
├── FileManager.php                  # File interactions
├── LicenseAgreementModal.php        # License flow
└── PostApprovalSuccessCard.php      # Completion UI
```

**Services:**
```
app/Services/
├── PitchWorkflowService.php         # State transitions
├── NotificationService.php          # Email triggers
├── PayoutProcessingService.php      # Payment distribution
├── InvoiceService.php               # Invoice generation
├── FileManagementService.php        # File operations
└── BulkDownloadService.php          # ZIP archive generation via Cloudflare
```

**Controllers:**
```
app/Http/Controllers/
├── ClientPortalController.php       # Client portal routes
└── Api/
    └── BulkDownloadController.php   # Bulk download callback/status/download
```

**Models:**
```
app/Models/
├── Project.php                      # Core project
├── Pitch.php                        # Submission container
├── PitchFile.php                    # Producer files
├── PitchSnapshot.php                # Version snapshots
├── PitchEvent.php                   # Audit trail
├── PitchMilestone.php               # Payment milestones
├── FileComment.php                  # File comments
├── Invoice.php                      # Payment invoices
├── PayoutSchedule.php               # Payout tracking
└── BulkDownload.php                 # Bulk download archives
```

**Cloudflare Workers:**
```
cloudflare-workers/
└── bulk-download-consumer/
    └── src/consumer.ts              # Streaming ZIP creation worker
```

**Jobs:**
```
app/Jobs/
└── CleanupOldBulkDownloads.php      # Removes expired archives (24h)
```

### Authorization

**Policies Used:**
- `ProjectPolicy` - Project-level access
- `PitchPolicy` - Pitch operations
- `ProjectFilePolicy` - File access
- `PitchFilePolicy` - Pitch file access

**Middleware:**
- `EnsureSignedOrClientAccess` - Client portal access validation

### Event Listeners

```php
// ManageClientProject listeners
'filesUploaded' => 'handleFilesUploaded',
'fileDeleted' => 'handleFileDeleted',
'bulkFileAction' => 'handleBulkFileAction',
'milestonesUpdated' => '$refresh',
'budgetUpdated' => '$refresh',
'refreshClientFiles' => '$refresh',
'pitchStatusChanged' => 'refreshPitchStatus',
'requestCommentsRefresh' => 'refreshCommentsForFileList',
'swapToFileVersion' => 'handleSwapToFileVersion',
```

---

## Appendix: Database Schema

### Core Tables

```sql
-- projects (client management specific fields)
client_email VARCHAR(255)
client_name VARCHAR(255)
client_user_id BIGINT (FK to users)
client_email_preferences JSON
workflow_type ENUM('client_management', ...)

-- pitches
payment_amount DECIMAL
payment_status ENUM('pending', 'paid', ...)
included_revisions INT
additional_revision_price DECIMAL
revisions_used INT
current_snapshot_id BIGINT (FK)
watermarking_enabled BOOLEAN

-- pitch_files
client_approval_status VARCHAR
client_approved_at TIMESTAMP
revision_round INT
superseded_by_revision BOOLEAN
is_watermarked BOOLEAN
processed_file_path VARCHAR

-- pitch_snapshots
snapshot_data JSON
status ENUM('pending', 'accepted', 'revisions_requested', 'revision_addressed')

-- pitch_events
event_type VARCHAR
comment TEXT
status VARCHAR
metadata JSON
created_by BIGINT (FK, nullable)

-- pitch_milestones
name VARCHAR
description TEXT
amount DECIMAL
sort_order INT
status VARCHAR
payment_status VARCHAR
is_revision_milestone BOOLEAN
revision_round_number INT
revision_request_details TEXT

-- file_comments
commentable_type VARCHAR
commentable_id BIGINT
user_id BIGINT (nullable)
parent_id BIGINT (self-referencing)
comment TEXT
timestamp FLOAT
resolved BOOLEAN
client_email VARCHAR
is_client_comment BOOLEAN

-- bulk_downloads
id UUID (PK)
user_id BIGINT (FK to users)
file_ids JSON
archive_name VARCHAR
status ENUM('pending', 'processing', 'completed', 'failed')
storage_path VARCHAR (nullable)
download_url VARCHAR (nullable)
download_url_expires_at TIMESTAMP (nullable)
error_message VARCHAR (nullable)
completed_at TIMESTAMP (nullable)
```

---

## Change Log

| Date | Changes |
|------|---------|
| 2024-12-05 | Added Bulk Download feature documentation (Phase 2.1) |
| 2024-12-04 | Initial documentation created |
