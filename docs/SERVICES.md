# Services (`app/Services/`)

## Index

*   **Core Workflow & Management:**
    *   [`PitchWorkflowService`](#pitchworkflowservice-analysis)
    *   [`ProjectManagementService`](#projectmanagementservice-analysis)
    *   [`FileManagementService`](#filemanagementservice-analysis)
    *   [`PitchCompletionService`](#pitchcompletionservice-analysis)
    *   [`PitchService`](#pitchservice-analysis) (Note: Issues Identified)
*   **External Integrations & Utilities:**
    *   [`InvoiceService`](#invoiceservice-analysis) (Stripe API)
    *   [`NotificationService`](#notificationservice-analysis)
    *   [`EmailService`](#emailservice-analysis)
*   **Payment Services (Root):**
    *   [`PaymentService`](#paymentservice-analysis)

This section documents the services within the `app/Services` directory, which encapsulate specific business logic domains.

*(Analysis pending...)*

### `PitchWorkflowService` Analysis

**Status:** Complete.

**File:** `app/Services/PitchWorkflowService.php`

Orchestrates the entire lifecycle and state transitions of `Pitch`es.

*   **Purpose:** Centralizes business logic for pitch status changes, snapshot reviews, and payment status updates.
*   **Dependencies:** Injects `NotificationService`. Uses Models (`Project`, `Pitch`, `User`, `PitchSnapshot`, `PitchEvent`), `DB`, `Log`, `Auth`. Calls `ProjectManagementService` directly.
*   **Key Workflow Methods:**
    *   `createPitch()`: Initial pitch creation (`PENDING`).
    *   `approveSubmittedPitch()`: Handles review approval (`Ready For Review` -> `Approved`). Updates Pitch and Snapshot.
    *   `denySubmittedPitch()`: Handles review denial (`Ready For Review` -> `Denied`). Updates Pitch and Snapshot.
    *   `requestPitchRevisions()`: Handles revision request (`Ready For Review` -> `Revisions Requested`). Updates Pitch and Snapshot.
    *   `cancelPitchSubmission()`: Allows creator to withdraw submission (`Ready For Review` -> `In Progress`). Updates Pitch and Snapshot.
    *   `submitPitchForReview()`: Handles creator submission (`In Progress`/`Revisions Requested` -> `Ready For Review`). Creates *new* `PitchSnapshot`, updates Pitch.
    *   `returnPitchToReview()`: Allows owner to revert `Approved`/`Denied`/`Revisions Requested` -> `Ready For Review`. Updates Pitch and Snapshot.
    *   `returnPitchToApproved()`: Allows owner to revert `Completed` (if payment pending/failed) -> `Approved`. Updates Pitch, calls `ProjectManagementService->reopenProject()`.
*   **Payment Status Methods:**
    *   `markPitchAsPaid()`: Sets status to `PAID`, records invoice/payment details. Called after successful payment.
    *   `markPitchPaymentFailed()`: Sets status to `FAILED`. Called after failed payment.
*   **Authorization & Validation:** Performs authorization checks (user roles, ownership) and status transition validation using custom Exceptions (`UnauthorizedActionException`, `InvalidStatusTransitionException`, `SnapshotException`).
*   **Database Transactions:** Wraps state changes in `DB::transaction()`.
*   **Side Effects:** Creates `PitchEvent` records for auditing and triggers notifications via `NotificationService`.

**Interaction:** Acts as the engine for the pitch process, called by various controllers (`PitchController`, `PitchSnapshotController`, `WebhookController`) to enforce workflow rules and manage state transitions atomically.

*(Further service analysis pending...)*

### `ProjectManagementService` Analysis

**Status:** Complete.

**File:** `app/Services/Project/ProjectManagementService.php`

Encapsulates the core business logic for managing the lifecycle of `Project` models.

*   **Purpose:** Centralize creation, update, and status management of projects, including image handling and transactions.
*   **Dependencies:** Models (`Project`, `User`), Facades (`DB`, `Storage`, `Log`), `UploadedFile`, Custom Exceptions (`ProjectCreationException`, `ProjectUpdateException`).
*   **Core Methods:**
    *   `createProject()`: Creates project, handles optional image upload (S3), sets status to unpublished. Uses DB transaction.
    *   `updateProject()`: Updates project, handles optional new image upload and old image deletion (S3). Uses DB transaction. Logs changes.
    *   `publishProject()` / `unpublishProject()`: Delegates status change to methods on the `Project` model.
    *   `completeProject()`: Sets status to completed and records timestamp. Called by other services.
    *   `reopenProject()`: Sets status back to open from completed, clears timestamp.
*   **Error Handling:** Uses DB transactions, try-catch blocks, logs errors, throws custom exceptions.

**Interaction:** Used by Livewire components (`CreateProject`, `ManageProject`) and other services (`PitchCompletionService`) to handle core project data operations, image storage, and lifecycle state changes, ensuring atomicity and consistent error handling.

*(Further Service analysis pending...)*

### `FileManagementService` Analysis

**Status:** Complete.

**File:** `app/Services/FileManagementService.php`

Centralizes file operations (upload, delete, download links) for both Project and Pitch models, interacting with S3 storage.

*   **Purpose:** Provide a consistent API for managing project/pitch files, storage usage, and S3 interaction.
*   **Dependencies:** Models (`Project`, `Pitch`, `ProjectFile`, `PitchFile`, `User`), Facades (`Storage`, `DB`, `Log`), `UploadedFile`, Jobs (`GenerateAudioWaveform`), Custom Exceptions (`FileUploadException`, `StorageLimitException`, etc.).
*   **Core Methods:**
    *   `uploadProjectFile()` / `uploadPitchFile()`: Validates size/storage limits, stores file on S3, creates DB record (`ProjectFile`/`PitchFile`), updates parent storage usage, dispatches waveform job for pitch audio. Uses DB transaction, handles S3 cleanup on failure.
    *   `deleteProjectFile()` / `deletePitchFile()`: Deletes DB record, decrements parent storage usage, deletes file from S3. Uses DB transaction.
    *   `getTemporaryDownloadUrl()`: Generates a temporary signed S3 URL for a `ProjectFile` or `PitchFile`.
    *   `setProjectPreviewTrack()`: Updates `preview_track_file_id` on `Project`.
    *   `clearProjectPreviewTrack()`: Nullifies `preview_track_file_id` on `Project`.
*   **Error Handling:** Uses DB transactions, custom file exceptions, logs errors, attempts S3 cleanup.
*   **Authorization:** Assumes authorization checks are performed by the calling code.

**Interaction:** Used by Livewire components (`ManageProject`, `ManagePitch`) to manage file uploads, deletions, downloads, and preview track settings. Interacts with S3, manages DB records, updates storage metrics, and dispatches jobs.

*(Further Service analysis pending...)*

### `InvoiceService` Analysis

**Status:** Complete.

**File:** `app/Services/InvoiceService.php`

Handles direct interaction with the Stripe API for creating, processing, and retrieving invoices, particularly for pitch payments.

*   **Purpose:** Manage the lifecycle of invoices for pitch payments, including creation, payment processing, and retrieval for display.
*   **Dependencies:** Models (`Pitch`, `User` via relations), Facades (`Auth`, `Log`), Stripe PHP SDK (direct usage), Carbon.
*   **Core Methods:**
    *   `createPitchInvoice(Pitch $pitch, float $amount, string $paymentMethod)`: Creates a Stripe invoice for a pitch payment.
        *   Gets the project owner (customer) from the pitch.
        *   Ensures the user has a Stripe customer record.
        *   Creates a Stripe invoice with metadata (pitch_id, project_id, etc.).
        *   Adds an invoice item with the payment amount and description.
        *   Returns the created invoice object, a generated invoice ID, and success status.
        *   Handles exceptions, logs errors, and returns appropriate error information.
    *   `processInvoicePayment($invoice, $paymentMethod)`: Processes payment for an existing invoice.
        *   Finalizes the invoice if not already finalized (handles potential already-finalized errors).
        *   Pays the invoice using the specified payment method with `off_session=true`.
        *   Returns success status and the payment result or error details.
        *   Includes detailed logging for invoice payment flow.
    *   `getInvoice($invoiceId)`: Retrieves detailed invoice information.
        *   Gets the raw Stripe invoice with expanded lines, customer, and payment_intent.
        *   Transforms it into a standardized format with consistent structure.
        *   Includes human-readable date formatting via Carbon.
    *   `getUserInvoices($limit = 10)`: Gets all invoices for the authenticated user.
        *   Retrieves invoices from Stripe with expanded line items and payment intent.
        *   Maps them to a standardized format matching `getInvoice()`.
        *   Returns an empty collection if the user has no Stripe ID or on error.
*   **Helper Methods:**
    *   `newStripeClient()` (protected): Creates a new instance of the Stripe client using the configured secret key.
*   **Error Handling:** All public methods include try-catch blocks, detailed error logging with context information, and return appropriate error details.

**Interaction:** Used by `PitchPaymentController` for handling the pitch payment flow and `Billing/WebhookController` for payment confirmation. Creates and processes Stripe invoices directly using the Stripe API rather than using Laravel Cashier's invoice methods. The invoice metadata (particularly `pitch_id`) is crucial for webhook-based payment status updates via `Billing/WebhookController`.

### `PitchService` Analysis

**Status:** Complete (Issues Noted).

**File:** `app/Services/PitchService.php`

Provides basic update, status change, and delete methods for Pitches. Seems partially deprecated or in conflict with other services.

*   **Purpose:** Offer general pitch modification and deletion functions.
*   **Dependencies:** Models (`User`, `Pitch`), Exceptions (`AuthorizationException`, `InvalidStatusTransitionException`), Facades (`Log`), Services (`PitchWorkflowService` - injected but unused).
*   **Core Methods:**
    *   `updatePitch()`: Authorizes (`update`), fills model, saves. Suitable for basic field updates.
    *   `changePitchStatus()`: Authorizes (`update`). Tries to validate against `Pitch::$transitions`. **Issue:** Calls non-existent `$pitch->changeStatus()`. Logic seems flawed/redundant given `PitchWorkflowService`.
    *   `deletePitch()`: Authorizes (`delete`). Calls Eloquent `delete()`. **Issue:** Lacks file cleanup logic (handled elsewhere).
*   **Helper Methods:** `determineStatusChangeDirection()` (checks `Pitch::$transitions`).
*   **Authorization:** Uses `$user->cannot()`.
*   **Potential Issues:** `changePitchStatus` implementation is incorrect/redundant. `deletePitch` is incomplete (no file cleanup). Role overlaps/conflicts with `PitchWorkflowService` and `FileManagementService`. **Needs further investigation/refactoring**.

**Interaction:** Potentially used for basic updates. Its status change and delete methods are problematic and should likely be avoided in favor of `PitchWorkflowService` and `FileManagementService`. The unused injection of `PitchWorkflowService` suggests potential deprecation.

*(Further Service analysis pending...)*

### `PitchCompletionService` Analysis

**Status:** Complete.

**File:** `app/Services/PitchCompletionService.php`

Orchestrates the process of marking a pitch as the final selection for a project.

*   **Purpose:** Marks selected pitch as `COMPLETED`, closes other active pitches, completes the project, sets initial payment status, and triggers notifications.
*   **Dependencies:** Models (`Pitch`, `Project`, `User`, `PitchSnapshot`), Facades (`DB`, `Log`), Services (`ProjectManagementService`, `NotificationService`), Custom Exceptions (`CompletionValidationException`, `UnauthorizedActionException`), `RuntimeException`.
*   **Core Method (`completePitch()`):**
    *   Authorizes (project owner).
    *   Validates pitch status (`APPROVED`) and payment status (not `PAID`/`PROCESSING`).
    *   Uses `DB::transaction()`.
    *   Updates selected `Pitch` status to `COMPLETED`, sets `completed_at`, stores feedback, sets initial `payment_status`.
    *   Updates final `PitchSnapshot` status to `COMPLETED`.
    *   Sets status of other active pitches on the project to `CLOSED` (and denies their pending snapshots).
    *   Calls `projectManagementService->completeProject()`.
    *   Creates `PitchEvent` for completion.
    *   Triggers notifications (`notifyPitchClosed`, `notifyPitchCompleted`) via `NotificationService`.
*   **Error Handling:** Uses DB transaction, specific validation exceptions, logs errors, re-throws `RuntimeException` on general failure.

**Interaction:** Called when a project owner finalizes their choice (likely via `CompletePitch` Livewire component). Coordinates updates across the selected pitch, competing pitches, the parent project, and triggers related notifications via `NotificationService` and `ProjectManagementService`.

### `NotificationService` Analysis

**Status:** Complete.

**File:** `app/Services/NotificationService.php`

Central service for creating database notifications (`App\Models\Notification`) for various application events.

*   **Purpose:** Generate user notifications, prevent duplicates, and trigger real-time updates.
*   **Dependencies:** Models (`Notification`, `Pitch`, `User`, etc.), Facades (`Log`, `DB`), Event (`NotificationCreated`).
*   **Core Functionality:**
    *   `createNotification()` (private): Core method. Checks for recent duplicates (5-min window). Saves `Notification` model to DB. Dispatches `NotificationCreated` event (for Echo). Includes detailed logging.
    *   Public `notify*` Methods (e.g., `notifyPitchSubmitted`, `notifySnapshotApproved`, `notifyPitchFileComment`): Wrappers for specific events. Determine recipient(s), gather context data, call `createNotification()`.
*   **Duplicate Prevention:** Implemented within `createNotification()`.
*   **Real-time:** Dispatches `NotificationCreated` event, likely used by Echo for frontend updates.
*   **Error Handling:** Logs errors during notification creation.

**Interaction:** Called by other services (e.g., `PitchWorkflowService`, `PitchCompletionService`) when notifiable events occur. Creates DB records and triggers the `NotificationCreated` event for real-time UI updates (like `NotificationList` / `NotificationCount`).

*(Further Service analysis pending...)*

## Email Services (`app/Services/` - Root)

### `EmailService` Analysis

**Status:** Complete.

**File:** `app/Services/EmailService.php`

Wrapper around Laravel Mail facade to handle sending/queuing with suppression checks and audit logging.

*   **Purpose:** Send/queue emails reliably, check suppression lists, log detailed audit trails.
*   **Dependencies:** Models (`EmailAudit`, `EmailEvent`, `EmailSuppression`, `EmailTest`), Facades (`Mail`, `Log`, `View`), `Mailable`.
*   **Core Methods:**
    *   `send()` / `sendToSingleRecipient()`: Checks suppression. Sends email via `Mail::send()`. Logs `suppressed`/`sent`/`failed` audit/event.
    *   `queue()` / `queueForSingleRecipient()`: Checks suppression. Queues email via `Mail::queue()`. Logs `suppressed`/`queued`/`failed` audit/event.
    *   `isEmailSuppressed()`: Checks `EmailSuppression` model.
    *   `sendTestEmail()`: Sends a test email via `Mail::send()` using a view/variables. Logs audit/event. **Note:** Does not appear to check suppression list.
*   **Key Features:** Suppression list checking, detailed `EmailAudit` logging, simpler `EmailEvent` logging, send vs. queue separation.
*   **Auditing:** Creates `EmailAudit` and `EmailEvent` records for all attempts.
*   **Error Handling:** Logs errors, updates audit status on failure.

**Interaction:** Should be used instead of direct `Mail::send/queue` calls application-wide to ensure suppression checks and detailed logging. Used by `EmailTestForm` and likely other features sending emails.

*(Further Service analysis pending...)*

## Payment Services (`app/Services/` - Root)