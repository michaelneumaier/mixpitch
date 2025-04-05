# Models (`app/Models/`)

## Index

*   [`User`](#user-model-analysis)
*   [`Project`](#project-model-analysis)
*   [`Pitch`](#pitch-model-analysis)
*   [`PitchSnapshot`](#pitchsnapshot-model-analysis)
*   [`ProjectFile`](#projectfile-model-analysis)
*   [`PitchFile`](#pitchfile-model-analysis)
*   [`PitchFileComment`](#pitchfilecomment-model-analysis)
*   [`PitchEvent`](#pitchevent-model-analysis)
*   [`Mix`](#mix-model-analysis)
*   [`Notification`](#notification-model-analysis)
*   [`EmailAudit`](#emailaudit-model-analysis)
*   [`EmailEvent`](#emailevent-model-analysis)
*   [`EmailSuppression`](#emailsuppression-model-analysis)

*(Analysis pending...)*

### `User` Model Analysis

**Status:** Complete.

**File:** `app/Models/User.php`

Represents application users, integrating authentication, profiles, roles, and billing.

*   **Purpose:** Store user credentials, profile details, roles, relationships to projects/pitches, and billing info.
*   **Traits & Interfaces:** `Authenticatable`, `MustVerifyEmail`, `HasApiTokens` (Sanctum), `HasFactory`, `HasProfilePhoto` (Jetstream), `Notifiable`, `TwoFactorAuthenticatable` (Fortify), `HasRoles` (Spatie), `Billable` (Cashier), `FilamentUser`.
*   **Fillable:** Standard auth fields plus extensive profile fields (`username`, `bio`, `website`, `skills`, `equipment`, `social_links`, etc.), OAuth fields, `role`.
*   **Casts:** `email_verified_at` (datetime), list fields (`skills`, `equipment`, etc.) to array, flags to boolean.
*   **Appends:** `profile_photo_url`.
*   **Constants:** `ROLE_CLIENT`, `ROLE_PRODUCER`, `ROLE_ADMIN`.
*   **Relationships:**
    *   `projects()`: `hasMany(Project)` (Projects owned).
    *   `pitches()`: `hasMany(Pitch)` (Pitches submitted).
    *   `mixes()`: `hasMany(Mix)`.
    *   `notifications()`: `hasMany(Notification)` (via trait).
*   **Custom Methods & Overrides:**
    *   `updateProfilePhoto()`: Overrides Jetstream method for robust S3 upload/delete handling (incl. Livewire temps).
    *   `profilePhotoUrl()`: Overrides Jetstream accessor for signed S3 URLs.
    *   `hasRole()`: *Currently* checks simple `role` column. Potential conflict/override of Spatie `hasRole` behavior - needs clarification.
    *   `hasCompletedProfile()`: Checks flag.
    *   Filament Integration: `canAccessPanel()`, `getFilamentName()`, `getFilamentAvatarUrl()`.
    *   Scopes: `scopeClients()`, `scopeProducers()` (based on simple `role` column).

**Interaction:** Core model for authentication and user data. Interacts with multiple Laravel packages. Profile photo methods handle S3 integration. Role checking (`hasRole`) needs review based on intended primary role system (Spatie vs. simple column). Used throughout the application for user identification, authorization, and data association.

*(Further Model analysis pending...)*

### `Project` Model Analysis

**Status:** Complete.

**File:** `app/Models/Project.php`

Represents a project posted by a user seeking collaboration pitches.

*   **Purpose:** Store project details, requirements, status, files, and relationships.
*   **Traits:** `HasFactory`, `Sluggable` (generates slug from `name`).
*   **Fillable:** Standard project fields (name, description, genre, status, image_path, budget, deadline, etc.).
*   **Casts:** `collaboration_type` (array), `is_published` (boolean), `completed_at`, `deadline` (datetime).
*   **Defaults:** `status` ('unpublished'), `is_published` (false).
*   **Constants:** Defines statuses (`STATUS_UNPUBLISHED`, `STATUS_OPEN`, etc.) and storage/file size limits (`MAX_STORAGE_BYTES`, `MAX_FILE_SIZE_BYTES`).
*   **Route Key:** Uses `slug` for route model binding.
*   **Relationships:**
    *   `user()`: `belongsTo(User)` - Project owner.
    *   `files()`: `hasMany(ProjectFile)` - Files attached to the project.
    *   `pitches()`: `hasMany(Pitch)` - Pitches submitted for the project.
    *   `mixes()`: `hasMany(Mix)` - (Purpose unclear from context).
    *   `previewTrack()`: `hasOne(ProjectFile)` - Specific relationship for the designated preview track file.
*   **Accessors:** `getImageUrlAttribute()`: Generates temporary S3 URL for `image_path`.
*   **Custom Methods:**
    *   `isOwnedByUser()`: Checks ownership.
    *   `publish()` / `unpublish()`: Manages `is_published` flag and basic status changes.
    *   `hasPreviewTrack()` / `previewTrackPath()`: Check for and get signed URL for preview track.
    *   `userPitch()`: Get pitch submitted by a specific user.
    *   `isOpenForPitches()`: Check if status is 'open'.
    *   `hasStorageCapacity()` / `getRemainingStorageBytes()` / `getStorageUsedPercentage()`: Storage limit calculations.
    *   `isFileSizeAllowed()`: Static check against max file size constant.
    *   `incrementStorageUsed()` / `decrementStorageUsed()`: Atomic updates to `total_storage_used` field (used by `FileManagementService`).

**Interaction:** Central model for projects. Interacts with `User`, `ProjectFile`, `Pitch`, `Mix`. Provides state management (`publish`/`unpublish`), storage calculation helpers, and atomic storage usage updates crucial for `FileManagementService`.

*(Further Model analysis pending...)*

### `Pitch` Model Analysis

**Status:** Complete.

**File:** `app/Models/Pitch.php`

Represents a producer's submission/proposal for a specific project.

*   **Purpose:** Store pitch details, track its complex workflow status, manage files/snapshots/events, payment status, and relationships.
*   **Traits:** `HasFactory`, `Sluggable` (generates slug from `title`).
*   **Fillable:** `project_id`, `user_id`, `title`, `description`, `status`, `current_snapshot_id`, `completed_at`, `payment_status`, etc.
*   **Dates:** `created_at`, `updated_at`, `completed_at`, `payment_completed_at`.
*   **Constants:** Defines extensive `STATUS_*` constants (pending, in_progress, ready_for_review, approved, denied, etc.), `PAYMENT_STATUS_*` constants, and storage/file size limits.
*   **`$transitions` Array:** Static array defining allowed status transitions. **Note:** Core logic seems handled by `PitchWorkflowService`, potentially making this less critical.
*   **Route Key:** Uses `slug`.
*   **Relationships:**
    *   `user()`: `belongsTo(User)` - Pitch creator (producer).
    *   `project()`: `belongsTo(Project)` - Associated project.
    *   `files()`: `hasMany(PitchFile)` - Current files for the pitch.
    *   `events()`: `hasMany(PitchEvent)` - History log.
    *   `snapshots()`: `hasMany(PitchSnapshot)` - Version history.
    *   `currentSnapshot()`: `belongsTo(PitchSnapshot)` - The snapshot currently under review.
*   **Accessors:**
    *   `getReadableStatusAttribute()`: Human-friendly status name.
    *   `getStatusDescriptionAttribute()`: User-facing explanation of the status.
*   **Custom Methods:**
    *   `booted()`: Contains commented-out generic status change notification logic.
    *   `isOwner()`: Checks ownership.
    *   Storage Helpers: `isFileSizeAllowed()`, `getTotalStorageUsed()`, `hasStorageCapacity()`, `incrementStorageUsed()`, `decrementStorageUsed()` etc. (Used by `FileManagementService`).
    *   `isPaymentFinalized()`: Checks payment status.
    *   `isInactive()`: Checks if status is completed/closed/denied.
    *   `getStatuses()`: Static method returns list of statuses.

**Interaction:** Central model for pitch submissions. Tracks complex state managed by `PitchWorkflowService`. Interacts with `User`, `Project`, `PitchFile`, `PitchSnapshot`, `PitchEvent`. Provides storage helpers for `FileManagementService`.

*(Further Model analysis pending...)*

### `PitchSnapshot` Model Analysis

**Status:** Complete.

**File:** `app/Models/PitchSnapshot.php`

Represents a point-in-time version of a Pitch submitted for review.

*   **Purpose:** Capture the state of a pitch (files, data) at submission and track the review outcome for that version.
*   **Traits:** `HasFactory`.
*   **Fillable:** `pitch_id`, `project_id`, `user_id`, `snapshot_data`, `status`.
*   **Casts:** `snapshot_data` (array), `status` (string).
*   **Constants:** Defines snapshot-specific statuses (`STATUS_PENDING`, `STATUS_ACCEPTED`, `STATUS_DENIED`, `STATUS_REVISIONS_REQUESTED`, `STATUS_CANCELLED`, `STATUS_COMPLETED`).
*   **Relationships:**
    *   `pitch()`: `belongsTo(Pitch)`.
    *   `project()`: `belongsTo(Project)`.
    *   `user()`: `belongsTo(User)` (Pitch creator).
*   **Accessors:** `getStatusLabelAttribute()`: Human-readable status label.
*   **Custom Methods:**
    *   `getStatuses()` / `getStatusLabels()`: Static helpers for status info.
    *   `hasChangesRequested()`, `isAccepted()`, `isDenied()`, `isPending()`, `isCancelled()`: Boolean status check helpers.
    *   `changeStatus()`: Method to update status (primary updates likely done via `PitchWorkflowService`).
*   **`snapshot_data`:** Array field likely storing list of associated `PitchFile` IDs for this version, producer's `response_to_feedback`, and potentially other metadata.

**Interaction:** Created by `PitchWorkflowService` upon pitch submission. Its status is updated by `PitchWorkflowService` based on review actions (approve, deny, etc.). Provides a historical record of pitch versions and review outcomes. `snapshot_data` links it to the specific files and context of that submission.

### `ProjectFile` Model Analysis

**Status:** Complete.

**File:** `app/Models/ProjectFile.php`

Represents a file uploaded directly to a Project.

*   **Purpose:** Store file metadata (path, name, size, MIME) and provide secure S3 URL generation.
*   **Traits:** `HasFactory`, `SoftDeletes`.
*   **Fillable:** `project_id`, `file_path`, `storage_path`, `file_name`, `original_file_name`, `mime_type`, `user_id`, `size`, `is_preview_track`.
*   **Relationships:**
    *   `project()`: `belongsTo(Project)`.
    *   `user()`: `belongsTo(User)` (Uploader).
*   **Accessors:**
    *   `getFormattedSizeAttribute()`: Human-readable file size (KB, MB, etc.).
    *   `getFileNameAttribute()`: Extracts filename from path.
    *   `getFullFilePathAttribute()`: Generates temporary S3 URL (30 min expiry) for viewing/embedding.
    *   `getSignedUrlAttribute()`: Generates temporary S3 URL (60 min expiry) for downloading.
*   **Custom Methods:**
    *   `formatBytes()`: Helper for size formatting.
    *   `signedUrl()`: Method version of `getSignedUrlAttribute` allowing custom expiry.

**Interaction:** Created/deleted via `FileManagementService`. Used by `Project` model (e.g., for `previewTrackPath`). Provides necessary signed URLs for frontend display and download functionality.

### `PitchFile` Model Analysis

**Status:** Complete.

**File:** `app/Models/PitchFile.php`

Represents a file uploaded as part of a Pitch submission, with added features for audio review.

*   **Purpose:** Store file metadata, provide S3 URLs, manage waveform data, and link to timestamped comments.
*   **Traits:** `HasFactory`, `SoftDeletes`.
*   **Fillable:** Includes fields from `ProjectFile` plus `note`, `waveform_peaks` (JSON), `waveform_processed` (bool), `waveform_processed_at`, `duration` (float).
*   **Casts:** `waveform_processed` (boolean), `waveform_processed_at` (datetime), `duration` (float).
*   **Relationships:**
    *   `pitch()`: `belongsTo(Pitch)`.
    *   `user()`: `belongsTo(User)` (Uploader).
    *   `comments()`: `hasMany(PitchFileComment)` - Links to timestamped comments (Key difference from `ProjectFile`).
*   **Accessors:**
    *   `getFullFilePathAttribute()`: Signed S3 URL for viewing/playback.
    *   `getSignedUrlAttribute()`: Signed S3 URL for download.
    *   `getFormattedSizeAttribute()`: Human-readable size (checks DB then S3).
    *   `getWaveformPeaksArrayAttribute()`: Decodes `waveform_peaks` JSON to PHP array.
*   **Custom Methods:**
    *   `formatBytes()`: Size formatting helper.
    *   `name()` / `extension()`: Extract parts of filename.

**Interaction:** Created/deleted via `FileManagementService`. Central to the `PitchFilePlayer` component, providing waveform data (`waveform_peaks_array`) and the `comments` relationship. Also used by `PitchWorkflowService` when creating snapshots.

### `PitchFileComment` Model Analysis

**Status:** Complete.

**File:** `app/Models/PitchFileComment.php`

Represents a threaded, timestamped comment on a specific `PitchFile`.

*   **Purpose:** Store comment text, timestamp, threading information, and resolved status for feedback on pitch files.
*   **Traits:** `HasFactory`.
*   **Fillable:** `pitch_file_id`, `user_id`, `parent_id`, `comment`, `timestamp` (float), `resolved` (bool).
*   **Casts:** `timestamp` (float), `resolved` (boolean).
*   **Appends:** `formatted_timestamp`, `has_replies`.
*   **Relationships:**
    *   `pitchFile()`: `belongsTo(PitchFile)`.
    *   `user()`: `belongsTo(User)` (Author).
    *   `parent()`: `belongsTo(PitchFileComment)` (Parent comment).
    *   `replies()`: `hasMany(PitchFileComment)` (Direct replies).
*   **Accessors:**
    *   `getHasRepliesAttribute()`: Checks if comment has replies.
    *   `getFormattedTimestampAttribute()`: Formats timestamp to MM:SS string.
*   **Custom Methods:**
    *   `getAllReplies()`: Recursively fetches all nested replies.
    *   `isReply()`: Checks if it's a reply (has `parent_id`).

**Interaction:** Used by `PitchFilePlayer` to display and manage comments. Allows linking feedback to specific times in an audio file and supports nested discussions.

### `PitchEvent` Model Analysis

**Status:** Complete.

**File:** `app/Models/PitchEvent.php`

Logs significant actions and status changes related to a Pitch, forming its history.

*   **Purpose:** Record key events (status changes, feedback, etc.) in a pitch's lifecycle for auditing and display.
*   **Traits:** `HasFactory`.
*   **Fillable:** `pitch_id`, `event_type`, `status` (pitch status at event time), `comment` (description/feedback), `rating` (unused?), `created_by` (user ID), `snapshot_id`.
*   **Relationships:**
    *   `pitch()`: `belongsTo(Pitch)`.
    *   `user()`: `belongsTo(User)` (Event initiator).
    *   `snapshot()`: `belongsTo(PitchSnapshot)` (Associated snapshot, if applicable).
*   **Custom Methods:**
    *   `createStatusChangeEvent()`: Static factory, likely superseded by direct event creation in `PitchWorkflowService`.

**Interaction:** Records are created primarily by `PitchWorkflowService` during state transitions (approval, denial, submission, completion) and potentially other actions. Provides the data source for the `PitchHistory` and `FeedbackConversation` components.

*(Further Model analysis pending...)*

### `Mix` Model Analysis

**Status:** Complete.

**File:** `app/Models/Mix.php`

A relatively simple model representing a submitted mix for a project.

*   **Traits:** `HasFactory`.
*   **Attributes:** `project_id`, `

## `Notification` Model Analysis

**Status:** Complete.

**File:** `app/Models/Notification.php`

Represents a user notification stored in the database.

*   **Purpose:** Store notification details, link to user and source model, manage read status, provide display helpers.
*   **Traits:** `HasFactory`.
*   **Fillable:** `user_id`, `related_id`, `related_type`, `type`, `data` (JSON), `read_at`.
*   **Casts:** `data` (array), `read_at` (datetime).
*   **Constants:** Defines numerous `TYPE_*` constants for various notification events.
*   **Relationships:**
    *   `user()`: `belongsTo(User)` (Recipient).
    *   `related()`: `morphTo()` (Links to source model like `Pitch`, `PitchFile`).
*   **Custom Methods & Scopes:**
    *   `markAsRead()` / `isRead()` / `scopeUnread()`: Manage read status.
    *   `getUrl()`: Generates URL to the relevant resource (e.g., pitch page, file comment anchor) based on type/data. Includes robust fallback logic and on-the-fly slug generation if needed.
    *   `getReadableDescription()`: Generates user-friendly text description based on type/data.

**Interaction:** Created by `NotificationService`. Used by `NotificationList` component to display notifications, leveraging `getUrl()` and `getReadableDescription()` for rendering.

## Email-Related Models

### `EmailAudit` Model Analysis

**Status:** Complete.

**File:** `app/Models/EmailAudit.php`

Provides a detailed log record for every email attempt handled by the `EmailService`.

*   **Purpose:** Store comprehensive information about email sending attempts, including recipient, subject, status (sent, failed, queued, suppressed), message ID, content, headers, and metadata. Used for debugging and auditing email delivery.
*   **Traits:** `HasFactory`.
*   **Fillable:** `email`, `recipient_name`, `subject`, `message_id`, `status`, `metadata`, `headers`, `content`.
*   **Casts:** `metadata` (array), `headers` (array).
*   **Relationships:**
    *   `events()`: `hasMany(EmailEvent)` (Links to simpler event records for the same email address).
    *   `test()`: `belongsTo(EmailTest)` (Attempts to link to a test record via `message_id`, fallback is null).
*   **Custom Methods:**
    *   `log()`: Static factory method used by `EmailService` to create new audit records easily.

**Interaction:** Records are created exclusively by the `EmailService` during send/queue attempts. Provides a detailed history for troubleshooting email issues. Can be linked to high-level `EmailEvent` records and potentially `EmailTest` records.

### `EmailEvent` Model Analysis

**Status:** Complete.

**File:** `app/Models/EmailEvent.php`

Logs high-level events related to email addresses, such as delivery, bounces, or complaints.

*   **Purpose:** Provide a summarized event history for specific email addresses, often populated by webhook handlers (like SES) or the `EmailService` itself. Tracks key occurrences without the full detail of `EmailAudit`.
*   **Traits:** `HasFactory`.
*   **Fillable:** `email`, `message_id`, `event_type` (e.g., 'bounce', 'complaint', 'delivery', 'sent'), `email_type` (e.g., 'TestMail', 'PitchApproved'), `metadata`.
*   **Casts:** `metadata` (array).
*   **Relationships:** (None defined directly in the model, but `EmailAudit` links *to* it).
*   **Custom Methods:**
    *   `logEvent()`: Static factory method for easily creating new event logs. Likely used by `EmailService` and webhook controllers.

**Interaction:** Records are created by the `EmailService` and potentially webhook controllers (e.g., `SesWebhookController`) to track significant email lifecycle events. Provides a simpler, event-focused view compared to the detailed `EmailAudit` logs. Can be linked from `EmailAudit` records sharing the same email address.

### `EmailSuppression` Model Analysis

**Status:** Complete.

**File:** `app/Models/EmailSuppression.php`

Maintains a list of email addresses that are suppressed from receiving emails.

*   **Purpose:** Prevent sending emails to addresses that have bounced, complained, or been manually added to a suppression list.
*   **Traits:** `HasFactory`.
*   **Fillable:** `email`, `reason` (e.g., 'bounce', 'complaint', 'manual'), `type` (likely related to reason), `metadata`.
*   **Casts:** `metadata` (array).
*   **Relationships:** None.
*   **Custom Methods:**
    *   `isEmailSuppressed()`: Static method used by `EmailService` to quickly check if a given email address exists in the suppression list before attempting to send/queue an email.

**Interaction:** Records are likely created by webhook handlers (e.g., `SesWebhookController` processing bounces/complaints) or potentially through manual admin actions. The `EmailService` queries this model via `isEmailSuppressed()` to prevent sending to blocked addresses.

### `EmailTest` Model Analysis

**Status:** Complete.

**File:** `app/Models/EmailTest.php`

Records information about test emails sent through the application's email testing utility.

*   **Purpose:** Log the parameters (`recipient_email`, `subject`, `template`, `content_variables`) and outcome (`status`, `result`, `sent_at`) of test emails initiated via the testing interface.
*   **Traits:** `HasFactory`.
*   **Fillable:** `recipient_email`, `subject`, `template`, `content_variables`, `status`, `result`, `sent_at`.
*   **Casts:** `content_variables` (array), `result` (array), `sent_at` (datetime).
*   **Relationships:**
    *   `audits()`: `hasMany(EmailAudit)` (Links to detailed audit logs via `message_id`). **Note:** The relationship seems to use the `EmailTest` primary key (`id`) as the `message_id` for linking, which might be an assumption or convention used in the `EmailService`.
    *   `events()`: `hasMany(EmailEvent)` (Links to high-level events based on the recipient email address).

**Interaction:** Records are likely created by the `EmailController` or potentially the `EmailService` when handling requests from the test email form. It links to `EmailAudit` and `EmailEvent` for a complete picture of the test email's journey and delivery status.

## Other Models

### `Track` Model Analysis

**Status:** Complete (Usage Clarification Needed).

**File:** `app/Models/Track.php`

Represents a generic audio track, potentially uploaded by a user.

*   **Purpose:** Store basic information about an audio track (`title`, `genre`, `file_path`) and its owner. Its specific role compared to `ProjectFile` and `PitchFile` needs clarification based on how it's used in controllers/services.
*   **Traits:** `HasFactory`.
*   **Fillable:** `title`, `genre`, `file_path`, `user_id`.
*   **Casts:** None specified.
*   **Relationships:**
    *   `user()`: `belongsTo(User)`.
    *   `project()`: `belongsTo(Project)`. **Note:** `project_id` is not in the `fillable` array, suggesting this relationship might be set manually or is potentially unused/legacy.
*   **Custom Methods:** None.

**Interaction:** Used by `TrackController`. Its relationship to `Project` and its distinction from `ProjectFile`/`PitchFile` requires further investigation by examining its usage in `TrackController` and potentially other parts of the application. It might represent raw user uploads before association with a project/pitch, or serve a different purpose entirely.

*(Further Model analysis pending...)*