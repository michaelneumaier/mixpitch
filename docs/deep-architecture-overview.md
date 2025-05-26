# MixPitch Deep Architecture Overview

_Last updated: 2025-04-28_

## 1. Technical Stack

- **Backend:** Laravel (PHP), Eloquent ORM, Events, Jobs, Notifications, Policies.
- **Frontend:** Blade templates, Livewire components, Tailwind CSS, Vite.
- **Real-time:** Laravel Echo, Pusher, Reverb WebSocket server.
- **Testing:** PHPUnit.
- **Node.js:** For asset compilation (Vite, Tailwind).

---

## 2. Core Models and Relationships

### Project
- Represents a music project.
- Statuses: `unpublished`, `open`, `in_progress`, `completed`.
- Workflow types: `standard`, `contest`, `direct_hire`, `client_management`.
- Relationships: `hasMany` pitches, mixes, files; `belongsTo` user (owner); preview track support.
- Handles storage limits, file size validation, publishing/unpublishing.

### Pitch
- User proposal/submission for a project.
- Statuses: `pending`, `in_progress`, `ready_for_review`, `pending_review`, `approved`, `denied`, `revisions_requested`, `completed`, `closed`, contest-specific statuses.
- Relationships: `belongsTo` project, user; `hasMany` files, snapshots, events; `belongsTo` currentSnapshot.
- Handles storage, file validation, status transitions, ownership checks.
- Business logic for contest winners, status descriptions, storage management.

### PitchSnapshot
- Versioned snapshot of a pitch (revision workflow).
- Statuses: `pending`, `accepted`, `denied`, `revisions_requested`, `revision_addressed`, `completed`, `cancelled`.
- Relationships: `belongsTo` pitch, project, user.
- Methods for status changes, revision/denial/cancellation checks.

### PitchFile
- File uploaded to a pitch (audio, stems, etc.).
- Relationships: `belongsTo` pitch, user; `hasMany` comments (PitchFileComment).
- Handles file metadata, waveform data, S3 URLs.

### PitchFileComment
- Comment on a pitch file (timestamped, threaded).
- Relationships: `belongsTo` pitch file, user; supports parent/child replies.
- Handles formatting, resolving comments.

### PitchEvent
- Event in the pitch lifecycle (status changes, comments, etc.).
- Relationships: `belongsTo` pitch, user, snapshot.
- Used for audit trail/history (PitchHistory component).

### User
- Standard Laravel user, roles: `client`, `producer`, `admin`.
- Relationships: `hasMany` projects, pitches, mixes, portfolio items; `morphToMany` tags.
- Handles profile photo (S3), ratings, role checks, Filament admin access.

### Notification
- Stores user notifications (pitch submitted, status change, comment, etc.).
- Relationships: `belongsTo` user; `morphTo` related (Pitch, PitchFile, etc.).
- Handles marking as read, generating URLs, readable descriptions.

### NotificationPreference / NotificationChannelPreference
- Store user preferences for notification types/channels.
- Relationships: `belongsTo` user.

### PortfolioItem, Mix, Tag, EmailAudit, EmailEvent, EmailSuppression, EmailTest
- PortfolioItem: User's portfolio (audio/YouTube).
- Mix: Mix submission for a project.
- Tag: Tagging for users/entities.
- EmailAudit/Event/Suppression/Test: Email tracking/suppression.

---

## 3. Business Logic Patterns

- **Status Management:** Well-defined status constants and helpers for transitions/validation (Project, Pitch).
- **Revision Workflow:** PitchSnapshot enables full revision/version history with clear status transitions.
- **Storage Management:** Project and Pitch handle file size/storage limits, atomic increment/decrement.
- **Audit Trail:** PitchEvent logs all significant actions for traceability.
- **Notifications:** Rich notification system with types, user/channel preferences, real-time delivery.
- **Role-based Access:** User roles and policies enforce access and workflow restrictions.

---

## 4. Notable Features (from recent improvements)

- **Snapshot Revision Workflow:**
  - 'revision_addressed' status for snapshots.
  - Automatic transition of old snapshots when new revisions submitted.
  - UI distinguishes revision statuses (amber/blue/red styling).
  - Improved snapshot navigation and file tracking.
- **Pitch Status System:**
  - `current_snapshot_id` on Pitch.
  - Standardized terminology (e.g., 'denied', 'changes_requested').
  - Validation methods for all state transitions.
  - Audit trail and improved logging.
  - UI with clear status indicators and feedback.
- **Pitch Completion:**
  - Completing a pitch closes all others for the project.
  - Pending snapshots for closed pitches are declined.
  - Events and UI updated for clarity.
- **Notifications:**
  - NotificationService, NotificationCreated event, Echo, Pusher, Reverb integration.
  - Real-time notifications for pitch/snapshot events, comments, and completions.

---

## 5. Next Steps for Further Exploration

- Map out controllers/services to understand request flow and orchestration.
- Explore Livewire components for frontend-backend reactivity.
- Analyze Blade views for UI structure.
- Dive into notification/event system, policies, and custom services.
- Review tests for validation and edge case handling.

---

## 6. Backend: Deep Dive into the `app` Directory

### 6.1 Controllers

#### AboutController
  - *Purpose*: Renders the static About page.
  - *Key Method*: `index()` – returns the `about` view.
  - *Design*: Minimal, no dependencies.

#### AudioFileController
  - *Purpose*: Handles secure access to audio files, generating pre-signed S3 URLs for playback/download.
  - *Key Methods*:
    - `getPreSignedUrl($filePath)`: Generates a pre-signed S3 URL for a given file path. Logs all actions and errors.
    - `getPortfolioAudioUrl($id)`: Looks up a portfolio item, validates it's audio, and returns a pre-signed URL.
  - *Design*: Uses Laravel Storage and logging. Returns JSON responses for API-style consumption.

#### ClientPortalController
  - *Purpose*: Manages the client-facing portal for project review, approval, revision requests, commenting, and secure file downloads.
  - *Key Methods*:
    - `show(Project $project, Request $request)`: Displays the portal, validates signed URLs and project type.
    - `storeComment(Project $project, Request $request)`: Allows clients to add comments, creates events, and notifies producers.
    - `approvePitch(...)`, `requestRevisions(...)`: Client actions for pitch approval/revisions, triggers workflow and notifications.
    - `resendInvite(...)`: Re-sends the client portal invite email with a new signed URL.
    - `downloadFile(...)`: Handles secure, signed downloads of pitch files.
  - *Design*: Integrates with `PitchWorkflowService` and `NotificationService`. Robust validation, error logging, and business rule enforcement.

#### DashboardController
  - *Purpose*: Renders the user dashboard, showing projects and pitches.
  - *Key Method*: `index()`: Loads projects and pitches for the authenticated user, ensures all projects have slugs, logs info.
  - *Design*: Ensures data integrity (slug generation), logs for debugging, loads related data for dashboard view.

#### EmailController
  - *Purpose*: Handles sending and testing of emails (admin/diagnostics).
  - *Key Methods*:
    - `sendTest(Request $request, EmailService $emailService)`: Sends a test email using the `EmailService`.
    - `showTestForm()`: Renders a form to trigger test emails.
  - *Design*: Integrates with `EmailService`, provides user feedback, handles errors gracefully.

#### FileDownloadController
  - *Purpose*: Handles secure downloads for pitch and project files.
  - *Key Methods*:
    - `downloadPitchFile($id)`: Authorizes and generates a temporary download URL for a pitch file using `FileManagementService`.
    - `downloadProjectFile($id)`: Same for project files.
  - *Design*: Uses policies for authorization, logs all download attempts, integrates with `FileManagementService` for S3 access.

#### PitchController
  - *Purpose*: Central controller for pitch lifecycle: creation, viewing, editing, status changes, and deletion.
  - *Key Methods*:
    - `index()`, `create(Project $project)`, `store(StorePitchRequest $request, Project $project)`: Pitch creation flow with validation and policy checks.
    - `show`, `showProjectPitch`, `showSnapshot`: Multi-pattern pitch/snapshot display with authorization and relationship checks.
    - `edit`, `editProjectPitch`, `update`: Editing and updating pitches.
    - `destroyConfirmed`, `destroy`: Pitch deletion with confirmation.
    - `changeStatus(...)`: Handles all status transitions (approve, deny, request revisions, complete, etc.) with validation and workflow service integration.
    - `returnToApproved(...)`: Allows returning a completed pitch to approved status (with policy and workflow checks).
  - *Design*: Integrates with `PitchWorkflowService`, uses policies for all sensitive actions, logs and handles errors robustly, supports multiple workflow types (contest, direct, client management).

#### PitchFileController
  - *Purpose*: Manages upload, deletion, viewing, and download of pitch files.
  - *Key Methods*:
    - `show(PitchFile $file)`: Displays file details and comments.
    - `delete(PitchFile $file)`: Deletes a file with authorization and service integration.
    - `uploadSingle(Request $request)`: Handles AJAX file upload with validation, quota checks, and error handling.
    - `download(PitchFile $file)`: Secure download via `FileManagementService`.
  - *Design*: Uses policies for all actions, integrates with `FileManagementService`, logs all operations, returns JSON for uploads.

#### PitchPaymentController
  - *Purpose*: Handles payment processing and receipts for pitches.
  - *Key Methods*:
    - `projectPitchOverview(Project $project, Pitch $pitch)`: Payment overview with status and authorization checks.
    - `projectPitchProcess(ProcessPitchPaymentRequest $request, ...)`: Processes Stripe payments, logs all actions, handles exceptions.
    - `projectPitchReceipt(Project $project, Pitch $pitch)`: Shows payment receipt, loads Stripe invoice.
    - Deprecated: `overview`, `process`, `receipt` (redirect to new methods).
  - *Design*: Integrates with `InvoiceService`, `PitchWorkflowService`, robust error handling, logs all payment activity, uses policies for access.

#### PitchSnapshotController
  - *Purpose*: Handles approval, denial, and revision requests for pitch snapshots.
  - *Key Methods*:
    - `approve(...)`: Approves a snapshot, updates status via workflow service.
    - `deny(...)`: Denies a snapshot with required reason, logs all actions.
    - `requestChanges(...)`: Requests revisions, logs and validates reason.
  - *Design*: Verifies all model relationships, uses policies for authorization, integrates with `PitchWorkflowService`, logs errors and validation failures.

#### ProjectController
  - *Purpose*: Manages project creation, editing, display, and file uploads.
  - *Key Methods*:
    - `index`, `show`: Project listing and display, includes user pitch and permissions.
    - `createProject`, `store`, `edit`, `update`, `destroy`: Full CRUD for projects, with service integration and policy checks.
    - `uploadSingle(Request $request)`: AJAX file upload for projects, integrates with `FileManagementService`.
    - Deprecated: `projects`, `createStep2`, `storeProject`, `formatBytes`, `cleanTempFolder` (marked for refactor/removal).
  - *Design*: Uses `ProjectManagementService` for business logic, policies for all sensitive actions, logs and handles errors, supports multi-step creation (legacy).

### 6.2 Services

#### PitchWorkflowService
- Central orchestrator for all pitch lifecycle actions and status transitions.
- Handles creation, approval, denial, revision requests, submissions, cancellations, and review cycles.
- Implements robust validation for status transitions and user permissions.
- Integrates with `NotificationService` for all relevant actions (submissions, approvals, denials, revisions, etc.).
- Ensures atomicity and consistency via database transactions.
- Supports contest, direct hire, and client management workflows with specialized logic.

#### PitchCompletionService
- Handles the pitch completion process:
  - Marks a pitch as completed and updates its status and feedback.
  - Automatically closes all other pitches for the project and declines any pending snapshots.
  - Triggers project completion via `ProjectManagementService`.
  - Sends notifications to all affected parties (pitch creators, project owner, client if applicable).
- Ensures all actions are performed atomically and logs all steps.

#### NotificationService
- Manages creation and delivery of notifications for all major events (pitch status changes, comments, file uploads, snapshot actions, payments, completions, etc.).
- Integrates with user preferences and notification channels (database, email, real-time push).
- Avoids duplicate notifications and logs all actions for traceability.
- Provides specialized methods for client/producer/project owner notifications.

### 6.3 Key Patterns and Architecture
- **Controller-Service Separation:** Controllers handle HTTP and authorization; services encapsulate business logic and workflow.
- **Policy-based Authorization:** Laravel policies enforce permissions at both controller and service levels.
- **Transactional Integrity:** All multi-step operations (pitch creation, completion, status changes) are wrapped in DB transactions to ensure consistency.
- **Event-Driven Workflow:** PitchEvents and notifications provide a full audit trail and real-time feedback.
- **Extensible Notification System:** Highly modular, with support for new notification types and channels.

### 6.4 Models

#### Pitch
- Represents a user's submission (proposal) for a project.
- **Status system:**
  - Rich set of constants for workflow states (pending, in_progress, ready_for_review, approved, denied, revisions_requested, completed, closed, contest-specific, direct hire, client management, etc.).
  - Payment status constants for tracking payment lifecycle.
- **Relationships:**
  - `belongsTo` project, user
  - `hasMany` files, snapshots, events
  - `belongsTo` currentSnapshot (active revision)
- **Business logic:**
  - Status transitions and validation (static `$transitions` array)
  - Storage and file size management (atomic increment/decrement, limits, human-readable formatting)
  - Ownership, contest entry/winner helpers
  - Route sluggable (uses `id` as slug source)

#### Project
- Represents a music project, the core container for all work.
- **Status system:**
  - Constants for `unpublished`, `open`, `in_progress`, `completed`.
  - Workflow types: `standard`, `contest`, `direct_hire`, `client_management`.
- **Relationships:**
  - `belongsTo` user (owner)
  - `hasMany` pitches, mixes, files
  - `hasOne` previewTrack
  - `belongsTo` targetProducer (for direct hire)
- **Business logic:**
  - Storage and file size management
  - Workflow type helpers (isStandard, isContest, etc.)
  - Filtering/sorting scopes for queries
  - Human-readable workflow type names
  - Route sluggable

#### PitchSnapshot
- Represents a versioned snapshot of a pitch (revision workflow).
- **Status system:**
  - Constants for `pending`, `accepted`, `denied`, `revisions_requested`, `revision_addressed`, `completed`, `cancelled`.
- **Relationships:**
  - `belongsTo` pitch, project, user
- **Business logic:**
  - Status helpers (isPending, isAccepted, isDenied, isCancelled, etc.)
  - Status label mapping and validation
  - Change status with validation

#### User
- Standard Laravel user, with roles: `client`, `producer`, `admin`.
- **Relationships:**
  - `hasMany` projects, pitches, mixes, portfolio items
  - `morphToMany` tags
- **Business logic:**
  - Profile photo management (S3-aware)
  - Role checks and Filament admin access
  - Average rating calculation from completed pitches
  - Query scopes for filtering by role

#### Notification
- Stores user notifications for all major events.
- **Notification types:**
  - Constants for pitch, snapshot, file, contest, direct hire, payment, and project events.
- **Relationships:**
  - `belongsTo` user
  - `morphTo` related (Pitch, PitchFile, etc.)
- **Business logic:**
  - Mark as read, generate URLs, readable descriptions
  - Helper for manageable types and labels

### 6.5 Policies, Observers, Jobs, and Events

#### Policies
- **PitchPolicy:**
  - Governs all permissions for pitch actions (view, create, update, delete, approve, deny, request revisions, submit for review, complete, file upload, manage access, select contest winners, etc.).
  - Fine-grained checks for user roles (project owner, pitch owner/producer) and pitch/project status.
  - Prevents invalid state transitions and enforces workflow rules for all pitch-related actions.
  - Example: Only project owners can approve/deny submissions, only pitch owners can update/delete their pitch in allowed statuses, etc.

#### Observers
- **PitchObserver:**
  - Listens for lifecycle events on Pitch (created, updated, deleted, restored, forceDeleted).
  - On status changes, automatically syncs the parent project's status with its pitches using `syncProjectStatus`.
  - Logs all status changes and errors, ensures project reflects the current state of all pitches.
- **ProjectObserver:**
  - Handles automatic pitch creation for special workflows:
    - **Direct Hire:** Creates a pitch for the target producer when a project is created, generates an initial event, and notifies the producer.
    - **Client Management:** Creates a pitch for the producer, generates an initial event, and sends an invite to the client with a signed portal link.
  - Uses `NotificationService` for all notifications.
  - Ensures slugs, payment details, and events are set up correctly for new projects.

#### Jobs
- **GenerateAudioWaveform:**
  - Asynchronous job to process uploaded audio files, generate waveform data, and estimate duration.
  - Uses external AWS Lambda service if available, with fallback to local estimation and placeholder waveform generation.
  - Updates PitchFile with waveform data, duration, and logs all steps/errors.
- **SendNotificationEmailJob:**
  - Asynchronous job to send notification emails to users based on notification type and data.
  - Uses a generic Mailable, logs all sends/errors, and supports retry/fail logic.

#### Events
- **NotificationCreated:**
  - Broadcasts new notifications to users in real time via private channels (Laravel Echo, Pusher, Reverb).
  - Contains notification type, ID, and timestamp for frontend consumption.

### 6.6 Core Services and Infrastructure
----

#### NotificationService
- Centralized service for all notification logic, supporting both database and email notifications.
- **Key features:**
  - Checks user notification preferences before sending.
  - Deduplication: avoids sending duplicate notifications within a short window.
  - Supports all major notification types: pitch status changes, comments, file uploads, approvals/denials, payments, contest and client management events.
  - Broadcasts real-time events using NotificationCreated for frontend updates.
  - Integrates with EmailService for email notifications and supports client portal flows.
  - Helper methods for each workflow event (e.g., notifyPitchStatusChange, notifyPitchCompleted, notifySnapshotApproved/Denied, notifyPitchComment, notifyPaymentProcessed/Failed, etc.).
  - Handles both producer and client notifications, including client management-specific flows.

#### PitchWorkflowService
- Orchestrates all pitch lifecycle actions, enforcing validation and transactional integrity.
- **Key responsibilities:**
  - Creating pitches with workflow-specific guards (standard, contest, direct hire, client management).
  - Approving, denying, requesting revisions, submitting for review, and returning to review.
  - Handles all status transitions, snapshot management, and event creation.
  - Integrates with NotificationService for all user-facing events.
  - Supports payment status updates and client portal actions.

#### PitchCompletionService
- Handles the process of marking pitches as completed and closing out projects.
- **Key responsibilities:**
  - Marks a pitch as completed, closes all other pitches, and updates all relevant statuses.
  - Marks final snapshots as completed and declines pending snapshots for closed pitches.
  - Notifies all affected users (pitch creators, project owners, clients).
  - Integrates with ProjectManagementService to mark the project as completed.
  - Supports project owner feedback and rating.

#### FileManagementService
- Manages all file operations for projects and pitches.
- **Key features:**
  - Upload, delete, and manage files for both projects and pitches.
  - Enforces storage limits and file size checks.
  - Handles S3 storage, generates temporary download/streaming URLs, and manages preview tracks.
  - Ensures atomic updates to storage usage and cleans up orphaned files on errors.

### 6.7 Project and Email Infrastructure
----

#### ProjectManagementService
- Central service for all project lifecycle management.
- **Key responsibilities:**
  - Project creation (with optional image upload, S3 storage, and transactional integrity).
  - Project update (handles new images, cleans up old images, and ensures atomic updates).
  - Publish/unpublish projects (status transitions, event dispatching).
  - Complete/reopen projects (integrates with pitch lifecycle, idempotent status changes, and event hooks).
  - Ensures all project state changes are logged and recoverable.

#### EmailService
- Handles all email delivery, queuing, suppression, and auditing.
- **Key features:**
  - Centralized logic for sending and queuing emails, with support for both single and multiple recipients.
  - Suppression list checks to avoid sending to unsubscribed or bounced addresses.
  - Detailed audit logging for all emails (sent, failed, suppressed).
  - Specialized methods for client management flows:
    - `sendClientInviteEmail`: Sends initial invite to client with signed portal link.
    - `sendClientReviewReadyEmail`: Notifies client when a pitch is ready for review.
    - `sendClientProjectCompletedEmail`: Notifies client when the project is completed, including feedback and rating.
  - Supports rendering and logging of email content, template variables, and metadata.
  - Integrates with Laravel's mail queue and event system for robust delivery.

### 6.8 Console Commands, Middleware, and Form Requests
----

#### Console Commands
- **Purpose:** Automate backend tasks, maintenance, and batch processing.
- **Key examples:**
  - `pitches:calculate-storage`: Calculates and updates total storage used for all pitches.
  - `waveform:generate`: Generates audio waveforms for pitch files (all or by file ID), dispatches jobs for async processing.
  - `stripe:sync-invoices`: Syncs Stripe invoices for a user or all users, ensuring local data matches Stripe.
  - `mail:test`: Sends a test email using the configured mail driver and suppression logic.
- **Design notes:**
  - Use progress bars and detailed logging for long-running tasks.
  - Integrate with jobs/queues for heavy or async work (e.g., waveform generation).
  - Support both batch and single-entity operations via command options.

#### Middleware
- **Purpose:** Enforce access control, authentication, and request manipulation at the HTTP layer.
- **Key examples:**
  - `Authenticate`: Redirects unauthenticated users to login or returns 401 for API requests.
  - `CheckPitchFileAccess`: Ensures only pitch owners or project owners can access pitch files, aborts with 403/404 if unauthorized.
  - Other standard Laravel middleware: CSRF, cookie encryption, request trimming, etc.
- **Design notes:**
  - Middleware is used for both security (access checks) and request lifecycle management.
  - Custom middleware leverages relationships and policies to enforce business rules.

#### Form Requests
- **Purpose:** Centralize validation and authorization logic for incoming HTTP requests.
- **Key examples:**
  - `StorePitchRequest`: Validates and authorizes pitch creation, supports flexible project lookup and policy checks.
  - `StoreProjectRequest`: Validates project creation with conditional rules based on workflow type (contest, direct hire, client management).
- **Design notes:**
  - Authorization leverages Laravel policies for fine-grained access control.
  - Validation rules adapt to environment (e.g., relaxed for testing) and workflow context.

### 6.9 Controllers, Jobs, and Event Broadcasting
----

#### Controllers
- **Purpose:** Orchestrate HTTP request handling, validation, authorization, and workflow delegation to services.
- **Key examples:**
  - `PitchController`: Handles pitch creation, editing, status changes, snapshot navigation, and workflow transitions. Delegates business logic to `PitchWorkflowService` and enforces policy checks. Integrates with notification and event systems for user feedback.
  - `ProjectController`: Manages project lifecycle (creation, editing, file upload, deletion), delegates to `ProjectManagementService` for transactional integrity and S3 file handling. Supports AJAX file uploads and multi-step creation flows.
  - `ClientPortalController`: Powers the client-facing portal for project review, feedback, approval, revision requests, and secure file downloads. Validates signed URLs, triggers notifications, and ensures proper workflow for client management projects.
- **Design notes:**
  - Controllers are thin, pushing business logic to services and jobs.
  - Policy checks and request validation are enforced at the controller level.
  - Error handling and logging are robust for user actions and workflow events.

#### Jobs
- **Purpose:** Offload heavy or asynchronous tasks to the queue for background processing.
- **Key examples:**
  - `GenerateAudioWaveform`: Processes audio files (via AWS Lambda or fallback), extracts duration, and generates waveform peaks for UI display. Handles error fallback and S3 access.
  - `SendNotificationEmailJob`: Sends notification emails to users, supports different notification types, and logs delivery status. Integrates with Laravel's mail system and supports retry/failure handling.
- **Design notes:**
  - Jobs are idempotent and log all outcomes for observability.
  - Use queue configuration for retries, timeouts, and error handling.

#### Event Broadcasting
- **Purpose:** Enable real-time updates in the frontend via WebSockets and Laravel Echo.
- **Key examples:**
  - `NotificationCreated`: Broadcasts new notifications to private user channels, delivering notification type, ID, and timestamp for instant UI updates.
- **Design notes:**
  - Events implement `ShouldBroadcast` and use private channels for user-specific delivery.
  - Event payloads are minimal and optimized for frontend consumption.

### 6.10 Models, Policies, and Observers
----

#### Models
- **Purpose:** Represent the core business entities and encapsulate business logic, relationships, and state transitions.
- **Key examples:**
  - `Pitch`: Central model for submissions. Defines status constants and allowed transitions, handles storage quotas, and provides helper methods for contest, direct hire, and client management workflows. Tracks current snapshot, completion, and payment status.
  - `PitchSnapshot`: Represents a versioned deliverable for a pitch. Tracks status (pending, accepted, denied, revisions requested, cancelled, revision addressed), links to files, and provides helpers for workflow and status labeling.
  - `Project`: Represents a client project. Tracks workflow type (standard, contest, direct hire, client management), status, storage, deadline, and relationships to pitches, files, and users. Includes methods for publishing, filtering, and workflow-specific logic.
- **Design notes:**
  - Models use constants for statuses and workflow types to ensure consistency.
  - Business logic for transitions and validation is encapsulated within models.
  - Relationships are defined for all major entities (user, project, files, snapshots).

#### Policies
- **Purpose:** Enforce authorization rules for actions on models, ensuring only permitted users can perform sensitive operations.
- **Key examples:**
  - `PitchPolicy`: Governs all pitch-related permissions (view, create, update, delete, approve, deny, request revisions, complete, upload files, manage access, select contest winners, etc.). Uses workflow type and pitch status to determine access.
  - `ProjectPolicy`: (Not detailed here, but typically governs project-level actions for owners and collaborators.)
- **Design notes:**
  - Policies leverage workflow context (standard, contest, direct hire, client management) for nuanced access control.
  - Methods are granular, supporting all workflow actions and transitions.
  - Policies are referenced in controllers and enforced at the route/middleware level.

#### Observers
- **Purpose:** React to model lifecycle events (created, updated, deleted, restored, force deleted) to maintain workflow integrity and trigger side effects.
- **Key examples:**
  - `PitchObserver`: Listens for pitch changes and synchronizes project status accordingly. Logs changes and errors for auditability. Ensures project status reflects the latest pitch state, supporting robust workflow management.
  - `ProjectObserver`: (Not detailed here, but typically handles project-level events.)
- **Design notes:**
  - Observers encapsulate side effects and decouple workflow logic from controllers/models.
  - Logging is used for traceability and debugging.

### 6.11 Services and Workflow Patterns
----

#### Services
- **Purpose:** Encapsulate complex business logic, transactional operations, and cross-cutting concerns. Services coordinate models, notifications, files, and workflow transitions.
- **Key examples:**
  - `PitchWorkflowService`: Orchestrates the full lifecycle of pitches, including creation, status transitions (approve, deny, request revisions, submit for review, cancel, complete), and validation. Coordinates with `NotificationService` for user feedback and maintains transactional integrity for all workflow steps. Handles contest, direct hire, and client management flows.
  - `PitchCompletionService`: Manages the completion process for pitches, including marking as completed, closing other pitches, updating snapshots, and triggering project completion. Ensures only eligible pitches can be completed, handles feedback/rating, and notifies all relevant parties (including clients for client management projects).
  - `NotificationService`: Centralizes notification creation, preference checks, and real-time broadcasting. Supports all major notification types (pitch status, comments, approvals, revisions, completion, client actions) and prevents duplicates. Integrates with email and WebSocket systems for multi-channel delivery.
  - `FileManagementService`: Handles secure file uploads, storage quota checks, S3 integration, and temporary download URL generation for both project and pitch files. Ensures atomic updates to storage usage and robust error handling for file operations.
- **Design notes:**
  - Services are injected into controllers and other services for maximum reusability.
  - All critical operations are wrapped in database transactions for consistency.
  - Logging and error handling are pervasive for observability and debugging.
  - Services decouple workflow/business logic from controllers and models, supporting scalability and testability.

#### Workflow Patterns
- **Pitch Lifecycle:**
  - Creation → Submission → Review (approve/deny/request revisions) → Completion → Payment
  - Each transition is validated for allowed status changes and authorized users, with events and notifications generated for every step.
  - Special handling for contests (entry, winner selection), direct hire (accept/reject offer), and client management (client approval/revisions).
- **Project Completion:**
  - When a pitch is completed, all other pitches are closed and pending snapshots are declined. The project is marked as completed, and all stakeholders are notified.
- **File Management:**
  - Files are uploaded to S3 with strict quota enforcement. Temporary download links are generated for secure access. File actions are logged and validated for permissions and workflow state.
- **Notification Delivery:**
  - Notifications are created only if user preferences allow, deduplicated within a time window, and broadcast in real-time. Email and in-app notifications are coordinated for all major workflow events.

---

_Next: Will continue with Testing, Error Handling, and Observability._

---

## Jobs

### Overview
Jobs in MixPitch are used for handling time-consuming or asynchronous tasks such as audio processing and sending notification emails. They leverage Laravel's queue system for reliability and scalability.

### List of Jobs

#### 1. `GenerateAudioWaveform`
- **Purpose:**
  - Processes uploaded audio files to extract waveform data and duration, using an external service (AWS Lambda) or fallback estimation methods.
- **Key Methods:**
  - `__construct(PitchFile $pitchFile)`: Initializes with the target pitch file.
  - `handle()`: Main job logic—fetches file, calls external audio processor, updates waveform data, handles errors and retries.
  - `processAudioWithExternalService($fileUrl)`: Calls AWS Lambda for waveform extraction; falls back to estimation on failure.
  - `generateFallbackWaveformData()`, `estimateDurationFromFileSize()`, `generatePlaceholderWaveform($numPeaks)`: Fallback and estimation helpers.
- **Design Patterns:**
  - Implements `ShouldQueue` for asynchronous execution.
  - Uses logging and error handling for robust operation.
  - Integrates with S3 for file access and updates PitchFile model.

#### 2. `SendNotificationEmailJob`
- **Purpose:**
  - Sends notification emails to users based on notification events and preferences.
- **Key Methods:**
  - `__construct(User $user, string $notificationType, array $data, ?int $originalNotificationId = null)`: Sets up email job.
  - `handle()`: Builds and sends the email using a generic Mailable; logs success or failure.
- **Design Patterns:**
  - Implements `ShouldQueue` for async email delivery.
  - Uses Laravel's Mail and Log facades.
  - Respects user notification preferences and handles error scenarios.


## Event Broadcasting & Listeners

### Overview
The notification system relies on Laravel's event broadcasting for real-time updates, using Laravel Echo, Pusher, and Reverb WebSocket server.

### Events

#### 1. `NotificationCreated`
- **Purpose:**
  - Broadcasts notification events to private user channels in real time.
- **Key Methods:**
  - `__construct(Notification $notification)`: Stores the notification instance.
  - `broadcastOn()`: Returns the private channel for the user.
  - `broadcastWith()`: Defines the payload to broadcast.
- **Design Patterns:**
  - Implements `ShouldBroadcast`.
  - Uses private channels for user-specific notifications.

### Listeners

#### 1. `NotificationCreatedListener`
- **Purpose:**
  - Handles `NotificationCreated` events, checks user preferences, and dispatches `SendNotificationEmailJob` if email is enabled.
- **Key Methods:**
  - `handle(NotificationCreated $event)`: Checks preferences, logs actions, dispatches email job if required.
- **Design Patterns:**
  - Implements `ShouldQueue` for async event handling.
  - Integrates with notification channel preferences.


## Models

### Key Models

#### 1. `Pitch`
- **Purpose:**
  - Represents a music pitch submission, including status, user, project, snapshots, files, and payment.
- **Key Methods:**
  - Status helpers: `canSubmitForReview()`, `canCancelSubmission()`, `canApprove()`, `canDeny()`, `canComplete()`, etc.
  - Relationship methods: `user()`, `project()`, `files()`, `snapshots()`, `currentSnapshot()`.
  - Storage helpers: `hasStorageCapacity()`, `getRemainingStorageBytes()`, `incrementStorageUsed()`, etc.
  - Status transitions and validation logic.
- **Design Patterns:**
  - Uses Eloquent relationships and attribute casting.
  - Centralizes status constants and validation.
  - Integrates with history/audit trail (`PitchEvent`).

#### 2. `PitchSnapshot`
- **Purpose:**
  - Tracks individual versions (snapshots) of a pitch, including status, feedback, and revision history.
- **Key Methods:**
  - Status helpers: `isPending()`, `isAccepted()`, `isDenied()`, `hasChangesRequested()`, `changeStatus()`.
  - Relationship methods: `pitch()`, `project()`, `user()`.
  - Status constants for full revision workflow: `pending`, `accepted`, `denied`, `revisions_requested`, `revision_addressed`, `cancelled`, `completed`.
- **Design Patterns:**
  - Eloquent model with status constants and helpers.
  - Supports complete revision and approval workflow.

#### 3. `Project`
- **Purpose:**
  - Represents a music project, including workflow type, status, owner, and related pitches/files.
- **Key Methods:**
  - Relationship methods: `user()`, `pitches()`, `files()`, `targetProducer()`.
  - Workflow helpers: `isStandard()`, `isContest()`, `isDirectHire()`, `isClientManagement()`.
  - Storage and file management helpers.
- **Design Patterns:**
  - Eloquent model with workflow constants.
  - Supports multiple project types and storage logic.

#### 4. `Notification`
- **Purpose:**
  - Stores notifications for users, including type, related model, data, and read status.
- **Key Methods:**
  - Type constants for all notification scenarios.
  - Relationship: `user()`.
  - Helpers: `markAsRead()`, `isRead()`, `getUrl()`, `getReadableDescription()`.
- **Design Patterns:**
  - Eloquent model with type constants and user-friendly labels.
  - Supports rich notification data and links.

#### 5. `PitchEvent`
- **Purpose:**
  - Represents events in the pitch lifecycle (status changes, comments, etc.) for audit/history.
- **Key Methods:**
  - Relationship: `pitch()`, `user()`, `snapshot()`.
  - Static creator: `createStatusChangeEvent()`.
- **Design Patterns:**
  - Eloquent model for timeline/history tracking.


## Policies

### Overview
Policies enforce authorization rules for actions on pitches, projects, and files. They centralize access logic and are called by controllers and form requests.

### Key Policies

#### 1. `PitchPolicy`
- **Purpose:**
  - Governs who can view, create, update, delete, approve, deny, complete, upload, and manage pitches.
- **Key Methods:**
  - `view`, `createPitch`, `update`, `delete`, `approveInitial`, `approveSubmission`, `denySubmission`, `requestRevisions`, `cancelSubmission`, `submitForReview`, `complete`, `returnToApproved`, `uploadFile`, `manageAccess`, `manageReview`, `selectWinner`, `selectRunnerUp`, `acceptDirectHire`, `rejectDirectHire`.
- **Design Patterns:**
  - Uses Laravel's HandlesAuthorization trait.
  - Centralizes workflow-specific rules and status checks.

#### 2. `ProjectPolicy`
- **Purpose:**
  - Governs who can view, create, update, delete, publish, unpublish, upload, and manage project files and pitches.
- **Key Methods:**
  - `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`, `createPitch`, `publish`, `unpublish`, `uploadFile`, `deleteFile`, `download`.
- **Design Patterns:**
  - Uses HandlesAuthorization trait.
  - Integrates with project type/workflow and file access logic.


## Observers

### Overview
Observers automate model-related side effects and synchronization, such as keeping project status in sync with pitches and triggering notifications.

### Key Observers

#### 1. `PitchObserver`
- **Purpose:**
  - Listens for pitch lifecycle events (created, updated, deleted, restored, force deleted) and synchronizes project status accordingly.
- **Key Methods:**
  - `created`, `updated`, `deleted`, `restored`, `forceDeleted`, `syncProjectStatus`.
- **Design Patterns:**
  - Encapsulates side effects and status sync logic.

#### 2. `ProjectObserver`
- **Purpose:**
  - Handles project creation (auto-creates pitches for Direct Hire/Client Management), and triggers client invitations and notifications.
- **Key Methods:**
  - `created`, `updated`, `deleted`, `restored`, `forceDeleted`.
- **Design Patterns:**
  - Integrates with NotificationService.
  - Automates project-pitch relationships and client onboarding.


## Form Requests

### Overview
Form Requests encapsulate validation and authorization logic for incoming HTTP requests, keeping controllers clean and ensuring robust validation.

### Key Form Requests

#### 1. `Pitch/StorePitchRequest`
- **Purpose:**
  - Validates and authorizes pitch creation requests, ensuring the user can create a pitch for the target project.
- **Key Methods:**
  - `authorize()`: Checks user permissions via `ProjectPolicy::createPitch`.
  - `rules()`: Validates project ID, terms agreement, and (optionally) title/description.

#### 2. `Pitch/ProcessPitchPaymentRequest`
- **Purpose:**
  - Validates and authorizes pitch payment processing requests.
- **Key Methods:**
  - `authorize()`: Ensures user is project owner and pitch is eligible for payment.
  - `rules()`: Validates payment method fields.
  - `messages()`, `prepareForValidation()` for custom error handling and normalization.

#### 3. `Project/StoreProjectRequest`
- **Purpose:**
  - Validates and authorizes new project creation requests.
- **Key Methods:**
  - `authorize()`: Requires authenticated user.
  - `rules()`: Validates project fields, workflow type, deadlines, and conditional requirements.

#### 4. `Project/UpdateProjectRequest`
- **Purpose:**
  - Validates and authorizes project update requests.
- **Key Methods:**
  - `authorize()`: (Typically handled by policy/controller.)
  - `rules()`: Validates updatable project fields with conditional logic.


## Middleware

### Overview
Middleware in MixPitch handle request filtering, authentication, and access control, including custom logic for pitch file access.

### Notable Middleware
- `Authenticate`: Standard Laravel authentication.
- `CheckPitchFileAccess`: Custom middleware for validating access to pitch files.
- `EncryptCookies`, `PreventRequestsDuringMaintenance`, `RedirectIfAuthenticated`, `TrimStrings`, `TrustHosts`, `TrustProxies`, `ValidateSignature`, `VerifyCsrfToken`: Standard Laravel middleware for security and request handling.


## Services

### Key Services

#### 1. `NotificationService`
- **Purpose:**
  - Centralizes notification creation, broadcasting, and email logic for all notification types.
- **Key Methods:**
  - `createNotification`, `notifyPitchStatusChange`, `notifyPitchCompleted`, `notifyPitchComment`, `notifySnapshotApproved`, `notifySnapshotDenied`, `notifySnapshotRevisionsRequested`, `notifyPitchEdited`, `notifyFileUploaded`, `notifyPitchRevisionSubmitted`, `notifyPitchFileComment`, `notifyPaymentProcessed`, `notifyPaymentFailed`, `notifyDirectHireAssignment`, `notifyClientProjectInvite`, etc.
- **Design Patterns:**
  - Service layer abstraction for notification logic.
  - Integrates with events, jobs, and user preferences.
  - Handles deduplication, logging, and fallback scenarios.


## Architectural Workflows & Patterns

### Pitch Lifecycle & Status Transitions
- **Pitch status transitions** are strictly validated via model methods and policies, ensuring only valid state changes (pending → in_progress → ready_for_review → approved/denied/revisions_requested → completed/closed).
- **Snapshot revision workflow** tracks every submission, request for revision, and acceptance/denial, keeping a complete revision history and status audit.
- **Pitch completion** process ensures only one pitch per project is marked as completed, automatically closing and declining pending snapshots for others.
- **Audit trail**: Every status change and significant action is logged via `PitchEvent` and surfaced in the UI via `PitchHistory`.

### Notification & Real-Time System
- **NotificationService** orchestrates all notification creation, broadcasting, and email dispatch, triggered by key user actions (submission, comment, status change, approval, denial, completion).
- **Real-time updates** are delivered via Laravel Echo, Pusher, and Reverb, with events and listeners ensuring users receive timely notifications both in-app and via email, respecting user preferences.

### Project & Pitch Observers
- **Observers** ensure project and pitch states remain synchronized, automate pitch creation for Direct Hire/Client Management, and trigger notifications/invitations as needed.

### Validation & Authorization
- **Form Requests** and **Policies** centralize validation and access control, ensuring business rules are enforced consistently across controllers and services.


## Summary

This document now provides a detailed, file-by-file architectural overview of the MixPitch backend. Every controller, job, event, listener, model, policy, observer, service, middleware, and form request is described with its purpose, key methods, design patterns, and how it integrates into the overall application workflow. Special attention is given to the pitch lifecycle, snapshot revision workflow, notification system, and audit trail, reflecting the latest enhancements and best practices in the codebase.

{{ ... }}
