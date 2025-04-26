# Mixpitch Notification System Documentation

This document outlines the current implementation of the notification system in Mixpitch.

## Overview

- **Core:** Custom system built around `App\Models\Notification` and `App\Services\NotificationService`.
- **Storage:** Notifications are stored in the `notifications` database table.
- **Creation:** Centralized through `NotificationService::createNotification()`. Specific public methods in `NotificationService` handle triggering notifications for different events.
- **Preferences:** The `NotificationCreatedListener` checks `App\Models\NotificationChannelPreference` to see if a user has disabled a specific notification type *for a specific channel (e.g., email)* before processing.
- **Real-time:** Uses `App\Events\NotificationCreated` event broadcast via Laravel Echo/Pusher for real-time UI updates (handled by `NotificationList` and `NotificationCount` Livewire components).
- **Delivery Channels:** 
    - Database (for in-app display via `NotificationService`).
    - Broadcast Event (`NotificationCreated` for real-time UI).
    - Email (queued via `NotificationCreatedListener` -> `SendNotificationEmailJob`, requires Mailable implementation).
- **Redundancy Handling:** `NotificationService::createNotification()` prevents creating duplicate notifications (same user, related object, type) within a 5-minute window.

## Notification Types & Triggers

The following notification types are defined in `App\Models\Notification`. They are triggered by the corresponding methods in `App\Services\NotificationService`.

| Type Constant                         | Triggering Method                      | Recipient(s)        | Related Model       | Key Data Payload                                                                                              | Notes                                                                              |
| :------------------------------------ | :------------------------------------- | :------------------ | :------------------ | :------------------------------------------------------------------------------------------------------------ | :--------------------------------------------------------------------------------- |
| `TYPE_PITCH_SUBMITTED`                | `notifyPitchSubmitted`                 | Project Owner       | `Pitch`             | `pitch_id`, `pitch_slug`, `project_id`, `project_name`, `producer_id`, `producer_name`                       | Triggered when pitch is first created by producer.                                 |
| `TYPE_PITCH_STATUS_CHANGE`            | `notifyPitchStatusChange`              | Pitch Creator       | `Pitch`             | `status`, `project_id`, `project_name`                                                                        | Used for general status updates.                                                   |
| `TYPE_PITCH_COMMENT`                  | `notifyPitchComment`                   | Pitch Creator       | `Pitch`             | `comment`, `commenter_id`, `project_id`, `project_name`                                                       | Not sent if commenter is the pitch creator.                                        |
| `TYPE_PITCH_FILE_COMMENT`             | `notifyPitchFileComment`               | File Uploader, Parent Commenter, Pitch Owner | `PitchFile` | `comment_id`, `comment_text`, `commenter_id`, `user_name`, `pitch_id`, `is_reply`, `parent_comment_id`, etc. | Notifies multiple relevant parties.                                                |
| `TYPE_SNAPSHOT_APPROVED`              | `notifySnapshotApproved`               | Pitch Creator       | `PitchSnapshot`     | `pitch_id`, `project_id`, `project_name`, `version`                                                           |                                                                                    |
| `TYPE_SNAPSHOT_DENIED`                | `notifySnapshotDenied`                 | Pitch Creator       | `PitchSnapshot`     | `reason`, `pitch_id`, `project_id`, `project_name`, `version`                                                 |                                                                                    |
| `TYPE_SNAPSHOT_REVISIONS_REQUESTED`   | `notifySnapshotRevisionsRequested`     | Pitch Creator       | `PitchSnapshot`     | `reason`, `pitch_id`, `project_id`, `project_name`, `version`                                                 | Triggered by `PitchWorkflowService::requestPitchRevisions`.                        |
| `TYPE_PITCH_COMPLETED`                | `notifyPitchCompleted`                 | Pitch Creator       | `Pitch`             | `feedback` (optional), `project_id`, `project_name`                                                           |                                                                                    |
| `TYPE_PITCH_EDITED`                   | `notifyPitchEdited`                    | Project Owner       | `Pitch`             | `pitch_id`, `project_id`, `project_name`, `editor_id`, `editor_name`                                          | Not sent if editor is the project owner.                                           |
| `TYPE_FILE_UPLOADED`                  | `notifyFileUploaded`                   | Project Owner       | `Pitch`             | `pitch_id`, `project_id`, `project_name`, `file_id`, `file_name`, `file_size`, `uploader_id`, `uploader_name` | Not sent if uploader is the project owner.                                         |
| `TYPE_PITCH_REVISION`                 | `notifyPitchRevisionSubmitted`         | Project Owner       | `Pitch`             | `pitch_id`, `project_id`, `project_name`, `submitter_id`, `submitter_name`, `snapshot_id`                    | Triggered when producer submits a revision.                                        |
| `TYPE_PITCH_CANCELLED`                | *None*                                 | *N/A*               | *N/A*               | *N/A*                                                                                                         | Constant exists, but no method currently creates this notification.                |
| `TYPE_PAYMENT_PROCESSED`              | `notifyPaymentProcessed`               | Pitch Creator       | `Pitch`             | `amount`, `invoice_id` (optional), `project_id`, `project_name`                                               |                                                                                    |
| `TYPE_PAYMENT_FAILED`                 | `notifyPaymentFailed`                  | Pitch Creator       | `Pitch`             | `reason`, `project_id`, `project_name`                                                                        |                                                                                    |
| `TYPE_PITCH_APPROVED`                 | `notifyPitchApproved`                  | Pitch Creator       | `Pitch`             | `project_id`, `project_name`, `status`                                                                        | For *initial* pitch approval (Pending -> In Progress).                            |
| `TYPE_PITCH_SUBMISSION_APPROVED`      | `notifyPitchSubmissionApproved`        | Pitch Creator       | `Pitch`             | `snapshot_id`, `project_id`, `project_name`                                                                   | For subsequent snapshot approvals (Ready for Review -> Approved).                  |
| `TYPE_PITCH_SUBMISSION_DENIED`        | `notifyPitchSubmissionDenied`          | Pitch Creator       | `Pitch`             | `snapshot_id`, `reason` (optional), `project_id`, `project_name`                                              |                                                                                    |
| `TYPE_PITCH_SUBMISSION_CANCELLED`     | `notifyPitchSubmissionCancelled`       | Project Owner       | `Pitch`             | `producer_name`, `producer_id`, `project_id`, `project_name`                                                  |                                                                                    |
| `TYPE_PITCH_READY_FOR_REVIEW`         | `notifyPitchReadyForReview`            | Project Owner       | `Pitch`             | `project_id`, `project_name`, `snapshot_id`, `snapshot_version`, `is_resubmission`                              |                                                                                    |
| `TYPE_PITCH_CLOSED`                   | `notifyPitchClosed`                    | Pitch Creator       | `Pitch`             | `project_id`, `project_name`, `reason`                                                                        |                                                                                    |

## Frontend Display

- **Dropdown:** `App\Livewire\NotificationList` component displays a list of recent notifications in a dropdown.
- **Count:** `App\Livewire\NotificationCount` component displays the count of unread notifications.
- **Real-time Updates:** Both components listen for the `NotificationCreated` broadcast event to refresh.
- **Marking Read:** `NotificationList` handles marking individual or all notifications as read.

## Potential Areas for Improvement (Refined)

- **User Preferences:** Implemented via `NotificationChannelPreference` model. Check occurs in `NotificationCreatedListener` before dispatching channel-specific jobs (e.g., email). UI component `NotificationPreferences` created (needs placement in settings).
- **Email Channel:** `NotificationCreatedListener` dispatches `SendNotificationEmailJob` based on preferences. Actual email sending logic (Mailables, templates) within the job needs implementation. (Next step in Phase 3).
- **Centralized Logic:** While `NotificationService` centralizes creation, the logic for *who* gets notified is spread across many specific methods. Could this be more configuration-driven? (Consider for future refactoring).
- **Cancellation Notification:** `TYPE_PITCH_CANCELLED` exists but is not triggered. Decide if/how cancellation should notify the project owner.
- **Pitch File Comment Recipients:** `notifyPitchFileComment` has complex logic for notifying multiple parties. Review if this covers all desired cases (e.g., should project owner always be notified?).
- **Notification Type Model:** Clarify that the system uses a string `type` on the `Notification` model, not a dedicated `NotificationType` model.

## Potential Areas for Improvement (Based on Initial Review)

- **Unused Types:** `TYPE_PITCH_CREATED` and `TYPE_NEW_PITCH` don't appear to be triggered by `NotificationService`. Verify if they are used elsewhere or can be removed.
- **Redundant Types:** `TYPE_PITCH_SUBMITTED` and `TYPE_NEW_SUBMISSION` seem very similar. Can they be consolidated?
- **User Preferences:** No mechanism currently exists for users to disable specific notification types.
- **Email Channel:** Email notifications are not sent via this service yet.
- **Centralized Logic:** While `NotificationService` centralizes creation, the logic for *who* gets notified is spread across many specific methods. Could this be more configuration-driven?
- **Clarity:** Some notification types are triggered by multiple methods (`TYPE_FILE_UPLOADED`, `TYPE_PITCH_REVISION`, `TYPE_PITCH_CANCELLED`). Ensure the distinction and purpose are clear. 