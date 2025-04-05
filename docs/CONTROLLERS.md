# Controller Layer (`app/Http/Controllers/`)

## Index

*   [`ProjectController`](#projectcontroller-analysis)
*   [`PitchController`](#pitchcontroller-analysis)
*   [`PitchFileController`](#pitchfilecontroller-analysis)
*   [`PitchSnapshotController`](#pitchsnapshotcontroller-analysis)
*   [`DashboardController`](#dashboardcontroller-analysis)
*   [`UserProfileController`](#userprofilecontroller-analysis)
*   [`BillingController`](#billingcontroller-analysis)
*   [`EmailController`](#emailcontroller-analysis)
*   [`AboutController`](#aboutcontroller-analysis)
*   [`PricingController`](#pricingcontroller-analysis)
*   [`PitchPaymentController`](#pitchpaymentcontroller-analysis)
*   [`FileDownloadController`](#filedownloadcontroller-analysis)
*   [`SesWebhookController`](#seswebhookcontroller-analysis)

This document outlines the purpose and functionality of the various controllers handling HTTP requests.

*(Analysis pending...)*

## `ProjectController` Analysis

**Status:** Complete (Refactoring Notes).

**File:** `app/Http/Controllers/ProjectController.php`

Handles web requests related to project listing, viewing, creation, updates, and file management.

*   **Purpose:** Manage HTTP requests for projects, delegate logic to services, return views/responses.
*   **Dependencies:** Services (`ProjectManagementService`, `FileManagementService`), Models (`Project`, `ProjectFile`), Requests (`StoreProjectRequest`, `UpdateProjectRequest`), Facades (`Auth`, `Log`).
*   **Core Methods:**
    *   `index()`: Displays public list of open projects.
    *   `show()`: Displays a single public project, checks if user can pitch.
    *   `store()`: Handles new project creation via `ProjectManagementService`, uses `StoreProjectRequest`.
    *   `update()`: Handles project updates via `ProjectManagementService` (including publish/unpublish), uses `UpdateProjectRequest`.
    *   `uploadSingle()`: Handles AJAX file uploads via `FileManagementService`, checks authorization.
    *   `edit()`: Likely loads Livewire edit component.
    *   `deleteFile()` / `destroy()`: (Implementations need review/refactor to use services).
*   **Deprecated/Redundant:** `projects()`, `storeProject()`, `createProject()`, `createStep2()`, helper methods (`formatBytes`, `cleanTempFolder`) appear outdated or misplaced, likely due to Livewire components taking over creation/editing.
*   **Authorization:** Uses Form Requests and `Auth::user()->can()` / `$this->authorize()`.

**Interaction:** Acts as HTTP entry point for projects. Delegates core logic to `ProjectManagementService` and `FileManagementService`. Uses Form Requests for validation/auth. Returns views or redirects. Some methods need refactoring or removal due to Livewire handling similar functionality.

## `PitchController` Analysis

**Status:** Complete (Refactoring Notes).

**File:** `app/Http/Controllers/PitchController.php`

Handles web requests for pitch creation, viewing, editing, status changes, and deletion within the project context.

*   **Purpose:** Manage HTTP requests for pitches, validate authorization, delegate workflow logic to `PitchWorkflowService`, and return views/redirects.
*   **Dependencies:** Services (`PitchWorkflowService`, `PitchService`), Models (`Project`, `Pitch`, `PitchSnapshot`), Requests (`StorePitchRequest`, `Request`), Facades (`Auth`, `Log`), Helpers (`RouteHelpers`), Exceptions (`AuthorizationException`, `PitchCreationException`, etc.), `Toaster`.
*   **Core Methods:**
    *   `create()`: Shows pitch creation form, authorizes, handles existing pitch redirect.
    *   `store()`: Handles new pitch storage via `PitchWorkflowService`, uses `StorePitchRequest`.
    *   `showProjectPitch()`: Displays a specific pitch, authorizes, redirects owner.
    *   `editProjectPitch()`: Shows pitch edit view (loads `ManagePitch` Livewire component), authorizes.
    *   `update()`: Handles basic pitch updates (title/desc) via `PitchService` (**Note:** Skips workflow service). Needs validation review.
    *   `changeStatus()`: Handles status change actions (submit, approve initial, cancel) by calling specific `PitchWorkflowService` methods.
    *   `returnToApproved()`: Handles reverting completed/failed pitch via `PitchWorkflowService`.
    *   `destroyConfirmed()`: Handles confirmed deletion via `PitchWorkflowService` (**Note:** Deletion logic might be better placed elsewhere).
    *   `showLatestSnapshot()`: Redirects to the latest snapshot view.
    *   Payment/Redirect routes also present.
*   **Authorization:** Uses `authorize()` method (Policies) extensively.
*   **Workflow Delegation:** Primarily uses `PitchWorkflowService` for state changes and related actions.
*   **URL Generation:** Uses custom `RouteHelpers`.

**Interaction:** Main web entry point for pitch actions. Heavily relies on `PitchWorkflowService` for core logic. Uses `PitchService` for basic updates. Needs review on `update` validation and `destroyConfirmed` service usage/placement. Interacts with Livewire components for editing (`ManagePitch`).

## `PitchFileController` Analysis

**Status:** Complete (Download Method Note).

**File:** `app/Http/Controllers/PitchFileController.php`

Manages requests related to individual pitch files (showing details/comments, uploads, deletion, downloads).

*   **Purpose:** Handle HTTP interactions specifically for `PitchFile` models.
*   **Dependencies:** Services (`FileManagementService`), Models (`Pitch`, `PitchFile`), Trait (`AuthorizesRequests`), Facades (`Auth`, `Log`), Exceptions (File*, Auth*), Helpers (`RouteHelpers`).
*   **Core Methods:**
    *   `show()`: Displays the pitch file detail page (likely embedding `PitchFilePlayer`), eager loads relations, authorizes.
    *   `delete()`: Handles file deletion via `FileManagementService`, authorizes, redirects. (Route definition needs confirmation).
    *   `uploadSingle()`: Handles AJAX file uploads via `FileManagementService`, validates, authorizes, returns JSON response with file/storage info.
    *   `download()`: Initiates file download, authorizes. Relies on `FileManagementService->downloadFile()` (**Note:** This service method was not seen in previous analysis; might generate a signed URL or stream response).
*   **Authorization:** Uses `$this->authorize()` based on `PitchFilePolicy` (implicitly).

**Interaction:** Provides specific endpoints for viewing (`PitchFilePlayer`), uploading (likely via `ManagePitch` component), deleting, and downloading pitch files, delegating file operations to `FileManagementService`.

## `PitchSnapshotController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/PitchSnapshotController.php`

Handles project owner actions (approve, deny, request revisions) on specific pitch snapshots.

*   **Purpose:** Process POST requests for snapshot review actions, delegate logic to `PitchWorkflowService`.
*   **Dependencies:** Services (`PitchWorkflowService`), Models (`Project`, `Pitch`, `PitchSnapshot`), Facades (`Auth`, `Log`), Exceptions (`AuthorizationException`, `ValidationException`).
*   **Core Methods (all handle POST requests):**
    *   `approve()`: Authorizes (`approveSubmission`), calls `pitchWorkflowService->approveSubmittedPitch()`, redirects.
    *   `deny()`: Authorizes (`denySubmission`), validates `reason`, calls `pitchWorkflowService->denySubmittedPitch()`, redirects.
    *   `requestChanges()`: Authorizes (`requestRevisions`), validates `reason`, calls `pitchWorkflowService->requestPitchRevisions()`, redirects.
*   **Authorization:** Uses `$this->authorize()` based on `PitchPolicy` (implicitly).
*   **Validation:** Uses `$request->validate()` for actions requiring input (deny, request changes).
*   **Workflow Delegation:** All core logic is handled by the injected `PitchWorkflowService`.

**Interaction:** Receives actions triggered by the project owner (likely from the `ShowSnapshot` Livewire component or `ManageProject` page). Validates input, authorizes, calls the appropriate `PitchWorkflowService` method, and handles redirects.

## `DashboardController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/DashboardController.php`

Prepares and displays the main user dashboard.

*   **Purpose:** Fetch user-specific projects and pitches and pass them to the dashboard view.
*   **Dependencies:** Models (`Project`, `Pitch` via `User`), Facades (`Auth`, `Log`, `Str`).
*   **Core Methods:**
    *   `index()`: Gets authenticated user. Fetches user's projects and pitches (eager loading project for pitches). **Includes logic to generate/save missing project slugs.** Returns `dashboard` view with data.

**Interaction:** Fetches data for the authenticated user's main landing page after login.

## `UserProfileController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/UserProfileController.php`

Handles displaying public user profiles and editing the authenticated user's own profile.

*   **Purpose:** Manage viewing and editing of user profile data.
*   **Dependencies:** Models (`User`, `Project`, `Pitch`), Facades (`Log`, `Auth`).
*   **Core Methods:**
    *   `show($username)`: Finds user by username. Fetches published projects and completed pitches. Checks edit permissions. Returns public profile view (`user-profile.show`).
    *   `edit()`: Returns the profile edit view (`user-profile.edit-livewire`), likely loading a Livewire component.
    *   `update()`: Handles POST request to update authenticated user's profile. Validates extensive profile fields. Performs data cleanup (website URL, social links via `extractUsername`). Handles username locking logic. Calculates `profile_completed` status. Saves user. Redirects back.
    *   `extractUsername()` (private): Helper to clean social media links.

**Interaction:** Provides endpoints for public profile viewing and self-service profile editing. The `update` method contains significant logic for validation, data normalization, and status flag calculation related to profiles.

## `BillingController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/Billing/BillingController.php`

Manages user billing settings, payment methods, invoice viewing/downloading, and Stripe Customer Portal access.

*   **Purpose:** Handle user interactions with their billing information and payment history.
*   **Dependencies:** Facades (`Auth`, `Log`), Laravel Cashier (`Billable` trait methods), Stripe PHP SDK (direct usage), Exceptions (`IncompletePayment`, `CardException`, etc.).
*   **Core Methods:**
    *   `index()`: Displays billing overview. Creates Stripe customer if needed. Fetches invoices (from Stripe API & Cashier). Creates Stripe Setup Intent for payment method forms. Returns `billing.index` view.
    *   `updatePaymentMethod()`: Updates user's default payment method using Cashier.
    *   `removePaymentMethod()`: Removes user's payment method using Cashier.
    *   `processPayment()`: Handles *one-time payments*. Creates Stripe Invoice/Item directly via Stripe SDK, finalizes, and pays.
    *   `invoices()`: Displays list of user invoices (syncs first).
    *   `showInvoice()`: Displays a single invoice using Cashier.
    *   `downloadInvoice()`: Downloads invoice PDF using Cashier.
    *   `customerPortal()`: Redirects user to Stripe Customer Portal using Cashier.
*   **Helper Methods:** `syncInvoicesFromStripe()` (protected) synchronizes local Cashier invoice data with Stripe.
*   **Stripe Interaction:** Uses both Laravel Cashier convenience methods and direct Stripe SDK calls (especially for fetching and one-time payments).

**Interaction:** Provides backend logic for the user billing section. Interacts heavily with Cashier and Stripe. Handles payment method management, invoice display/download, and one-time payments.

## `EmailController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/EmailController.php`

Handles requests for the email testing functionality.

*   **Purpose:** Display the email test form and handle test email sending requests.
*   **Dependencies:** Mailables (`TestMail`), Services (`EmailService`).
*   **Core Methods:**
    *   `sendTest()`: Handles POST request. Validates email. Calls `emailService->send()` with `TestMail` mailable. Redirects back with status message.
    *   `showTestForm()`: Handles GET request. Returns view (`emails.test-form`) that likely contains the `EmailTestForm` Livewire component.

**Interaction:** Provides backend routes for the email test page. Delegates actual sending logic to `EmailService` using a fixed `TestMail` mailable.

## `AboutController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/AboutController.php`

Displays the static "About" page.

*   **Purpose:** Return the static about view.
*   **Dependencies:** None.
*   **Core Methods:**
    *   `index()`: Returns the `about` view.

**Interaction:** Serves a static information page.

## `PricingController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/PricingController.php`

Displays the static "Pricing" page.

*   **Purpose:** Return the static pricing view.
*   **Dependencies:** None.
*   **Core Methods:**
    *   `index()`: Returns the `pricing` view.

**Interaction:** Serves a static information page.

## `PitchPaymentController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/PitchPaymentController.php`

Handles the web routes and logic associated with paying for a selected pitch.

*   **Purpose:** Provide the interface for project owners to initiate payment for a completed pitch, process the payment via Stripe, update the pitch status, and display payment receipts.
*   **Dependencies:** Services (`InvoiceService`, `PitchWorkflowService`), Models (`Project`, `Pitch`), Requests (`ProcessPitchPaymentRequest`), Helpers (`RouteHelpers`), Facades (`Auth`, `Log`), Exceptions (`IncompletePayment`, `CardException`).
*   **Core Methods:**
    *   `projectPitchOverview(Project $project, Pitch $pitch)`: Displays the payment initiation page to the project owner. Performs authorization and status checks (is project owner? is pitch completed? is payment pending?). Redirects if payment is not applicable or already handled.
    *   `projectPitchProcess(ProcessPitchPaymentRequest $request, Project $project, Pitch $pitch)`: Handles the payment submission. Uses `ProcessPitchPaymentRequest` for validation/authorization. Delegates invoice creation/retrieval to `InvoiceService->createPitchInvoice()`. Delegates payment execution to `InvoiceService->processInvoicePayment()`. Updates the pitch payment status (`PAID` or `FAILED`) via `PitchWorkflowService`. Handles Stripe exceptions (`CardException`, `IncompletePayment` for SCA) and redirects to the receipt page on success or back to the payment page on failure.
    *   `projectPitchReceipt(Project $project, Pitch $pitch)`: Displays the payment receipt view, accessible by both the project owner and the pitch creator. Retrieves the corresponding Stripe Invoice details via `InvoiceService->getInvoice()` using the ID stored on the `Pitch` model.
*   **Deprecated Methods:** Contains older methods (`overview`, `process`, `receipt`) that likely correspond to previous routing structures.
*   **Interaction:** Acts as the HTTP endpoint for the pitch payment user flow. It coordinates heavily with `InvoiceService` for all Stripe interactions (invoice creation, payment processing, retrieval) and `PitchWorkflowService` to update the pitch's internal payment status and potentially trigger related events/notifications. Utilizes `RouteHelpers` for URL generation.

## `FileDownloadController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/FileDownloadController.php`

Provides secure endpoints for initiating file downloads.

*   **Purpose:** Handle requests to download specific project or pitch files. It ensures the user is authorized, generates a temporary signed S3 URL, and redirects the user to that URL for the actual download.
*   **Dependencies:** Services (`FileManagementService`), Models (`PitchFile`, `ProjectFile`), Facades (`Auth`, `Log`), Traits (`AuthorizesRequests`).
*   **Core Methods:**
    *   `downloadPitchFile($id)`: Finds the `PitchFile` by ID. Authorizes the download action using the corresponding Policy (likely `PitchFilePolicy`). Calls `FileManagementService->getTemporaryDownloadUrl()` to generate a signed S3 URL. Logs the request and redirects the user to the signed URL. Handles authorization failures (403) and other exceptions.
    *   `downloadProjectFile($id)`: Finds the `ProjectFile` by ID. Authorizes the download action using the corresponding Policy (likely `ProjectFilePolicy`). Calls `FileManagementService->getTemporaryDownloadUrl()` to generate a signed S3 URL. Logs the request and redirects the user to the signed URL. Handles authorization failures (403) and other exceptions.
*   **Interaction:** Acts as a secure gateway for file downloads. It relies on Policies for authorization and the `FileManagementService` to interact with S3 and generate the necessary signed URLs, preventing direct exposure of S3 paths.

## `SesWebhookController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/SesWebhookController.php`

Handles incoming webhook notifications from AWS SES, typically delivered via SNS.

*   **Purpose:** Process notifications from SES regarding email events like bounces and complaints. It updates the application's internal state based on this feedback (e.g., adding emails to suppression lists).
*   **Dependencies:** Models (`EmailAudit`, `EmailEvent`, `EmailSuppression`, `User`), Facades (`Log`, `Schema`).
*   **Core Methods:**
    *   `handle(Request $request)`: The main entry point for the webhook.
        *   Verifies the request (currently allows all, but notes need for signature verification in production).
        *   Handles SNS subscription confirmation requests by visiting the `SubscribeURL`.
        *   Parses the incoming message payload (JSON).
        *   Identifies the notification type (`Bounce`, `Complaint`).
        *   Delegates processing to specific handler methods (`handleBounce`, `handleComplaint`).
    *   `handleBounce($bounceData)`: Processes bounce notifications.
        *   Iterates through bounced recipients.
        *   Adds the recipient's email to the `EmailSuppression` list with reason 'bounce'.
        *   Logs a 'bounced' record in `EmailEvent`.
        *   Logs a detailed 'bounced' record in `EmailAudit`.
        *   Optionally updates the associated `User` model (if found and `email_valid` column exists) to mark the email as invalid.
    *   `handleComplaint($complaintData)`: Processes complaint notifications.
        *   Iterates through complaining recipients.
        *   Adds the recipient's email to the `EmailSuppression` list with reason 'complaint'.
        *   Logs a 'complained' record in `EmailEvent`.
        *   Logs a detailed 'complained' record in `EmailAudit`.
        *   Optionally updates the associated `User` model (if found) to reflect the complaint (e.g., opt-out preferences).
*   **Interaction:** Receives asynchronous feedback from AWS SES about email delivery status. It updates the `EmailSuppression` list, which is then used by `EmailService` to prevent future sends. It also logs events in `EmailEvent` and `EmailAudit` and can potentially update `User` records based on deliverability.

## `TestAudioProcessorController` Analysis

**Status:** Complete (Development Tool).

**File:** `app/Http/Controllers/TestAudioProcessorController.php`

Provides a web interface and endpoints for testing the AWS Lambda audio processing function (used for waveform generation).

*   **Purpose:** Allow developers to manually trigger and debug the interaction between the Laravel application and the external AWS Lambda function that processes audio files. It's likely not intended for end-user access.
*   **Dependencies:** Models (`PitchFile`), Facades (`Http`, `Log`, `Storage`), `Request`.
*   **Core Methods:**
    *   `index()`: Displays a view (`test-audio-processor`) listing existing `PitchFile` audio files and providing controls to test the Lambda function. Passes the configured Lambda URL to the view.
    *   `testEndpoint(Request $request)`: Handles an AJAX request to test the Lambda function with a specific, existing `PitchFile`.
        *   Retrieves the `PitchFile` by ID.
        *   Gets a signed S3 URL for the file.
        *   Logs detailed information about the file and the request.
        *   Constructs the target Lambda URL (appending `/waveform` if needed).
        *   Makes a POST request to the Lambda URL via `Http` facade, sending the file URL and desired peak count.
        *   Includes extensive logging and debugging options (`debug`, `verify`=false).
        *   Parses the Lambda response (handling potential JSON nesting/escaping issues from AWS API Gateway/Lambda).
        *   Returns a JSON response indicating success or failure, including detailed error messages and the raw Lambda response.
    *   `uploadTest(Request $request)`: Handles an AJAX request to upload a *new* audio file directly to S3 (`test-uploads` folder) and then trigger the Lambda function with that new file's URL. Returns a similar JSON response as `testEndpoint`.
*   **Interaction:** Provides developer tools to test the integration with the `lambda_audio_processor_url` defined in `config/services.php`. It uses the `Http` facade to call the Lambda function and logs extensively to help diagnose issues with file access, URL encoding, Lambda execution, or response parsing. Relies on `PitchFile` for existing files and `Storage` (S3 driver) for test uploads.

## `TrackController` Analysis

**Status:** Complete (Potential Legacy/Refactor Candidate).

**File:** `app/Http/Controllers/TrackController.php`

Handles basic CRUD operations for `Track` models, storing files on the public disk.

*   **Purpose:** Provides endpoints to upload, list, view, and delete individual audio tracks or collections of tracks (referred to as "projects" within this controller). Seems to operate independently of the main `Project`/`Pitch` workflow and services.
*   **Dependencies:** Models (`Track`), Facades (`Storage`, `Auth`), `Request`.
*   **Core Methods:**
    *   `upload(Request $request)`: Uploads a single track file to `storage/app/public/tracks`, creates a `Track` record associated with the authenticated user.
    *   `index()`: Fetches all `Track` records and displays them in a `tracks` view.
    *   `createProject()`: Returns a view named `upload-project`, likely a form to upload multiple tracks as a conceptual "project" within this controller's scope.
    *   `projects()`: Fetches all `Track` records (or potentially distinct titles - commented out) and displays them in a `projects.index` view. **Note:** Method name and view path conflict with the main `ProjectController`.
    *   `show($id)`: Fetches a single `Track` by ID and displays it in a `tracks.show` view.
    *   `storeProject(Request $request)`: Handles the submission from `createProject()`. Uploads multiple track files to `storage/app/public/tracks`, creates multiple `Track` records via `Track::insert()`. **Note:** Associates tracks with a single title from the request.
    *   `destroy($id)`: Deletes a `Track` record and its associated file from the public disk after checking ownership. Redirects to `projects.index`.
*   **Storage:** Uses the `public` disk (likely local storage symlinked to `public/storage`) instead of S3 used by `FileManagementService`.
*   **Potential Issues:**
    *   Naming conflicts (`projects`, `storeProject`) with the main project functionality.
    *   Uses public disk, potentially bypassing security/access control mechanisms associated with S3 signed URLs.
    *   Seems disconnected from the core `Project`/`Pitch` models and services.
    *   Could be legacy code or a separate, simpler feature.
*   **Interaction:** Manages `Track` models and files stored locally. Does not appear to interact with the main services (`ProjectManagementService`, `FileManagementService`, etc.) or S3 storage. Its purpose relative to the main application features needs clarification; it might be deprecated or serve a niche function.

## `MixController` Analysis

**Status:** Complete (Potential Legacy/Refactor Candidate).

**File:** `app/Http/Controllers/MixController.php`

Handles submitting and rating mixes associated with a specific project.

*   **Purpose:** Allow users (presumably producers) to upload an audio file (`mix_file`) as a "mix" for a given project, and potentially allow project owners to rate these mixes.
*   **Dependencies:** Models (`Mix`, `Project`), Facades (`Auth`, `Storage`), `Request`.
*   **Core Methods:**
    *   `create($slug)`: Finds a `Project` by its slug. Returns the `mixes.create` view, passing the project.
    *   `store(Request $request, Project $project)`: Validates the uploaded `mix_file` and optional description. Stores the file in `storage/app/public/mixes`. Creates a new `Mix` record, associating it with the authenticated user and the provided `Project`. Redirects back to the project's show page.
    *   `rate(Mix $mix, int $rating)`: Updates the `rating` field on a specific `Mix` model. The validation is commented out. Redirects back.
*   **Storage:** Uses the `public` disk, similar to `TrackController`.
*   **Potential Issues:**
    *   Seems functionally similar to submitting a `PitchFile` but uses a separate `Mix` model and `public` storage.
    *   Its role compared to the main `Pitch` submission workflow is unclear. Could be legacy or a distinct, simpler feature.
    *   The `rate` method lacks validation.
*   **Interaction:** Manages `Mix` models and associated files stored locally. Takes a `Project` slug/model as input but doesn't seem to interact with core services. Might be part of an older feature set before the current pitch workflow was implemented.

## `Billing/WebhookController` Analysis

**Status:** Complete.

**File:** `app/Http/Controllers/Billing/WebhookController.php`

Extends Cashier's WebhookController to handle Stripe webhook events, especially those related to pitch payments and invoice status.

*   **Purpose:** Process asynchronous notifications from Stripe about payment events. Updates application state (e.g., pitch payment status) based on successful or failed payments. Also handles general invoice/customer events for syncing data.
*   **Dependencies:** Services (`PitchWorkflowService`), Models (`User`, `Pitch`), Base Controller (`Laravel\Cashier\Http\Controllers\WebhookController`), Facades (`Log`).
*   **Inheritance:** Extends `CashierWebhookController`.
*   **Key Handled Events:**
    *   `handleInvoicePaymentSucceeded($payload, PitchWorkflowService $pitchWorkflowService)`:
        *   **Primary logic for pitch payments.**
        *   Extracts `pitch_id` from invoice metadata.
        *   If found, finds the `Pitch` model.
        *   Calls `PitchWorkflowService->markPitchAsPaid()` with the pitch and Stripe invoice ID/charge ID.
        *   Logs success or warnings if the pitch isn't found.
        *   Also triggers general user invoice sync (`syncUserInvoicesFromPayload`).
    *   `handleInvoicePaymentFailed($payload, PitchWorkflowService $pitchWorkflowService)`:
        *   **Primary logic for failed pitch payments.**
        *   Extracts `pitch_id` from invoice metadata.
        *   If found, finds the `Pitch` model.
        *   Calls `PitchWorkflowService->markPitchPaymentFailed()` with the pitch, invoice ID, and failure reason (extracted from payload).
        *   Logs success or warnings.
        *   Also triggers general user invoice sync.
    *   `handleInvoiceCreated($payload)`: Logs the event and triggers user invoice sync.
    *   `handleChargeSucceeded($payload)`, `handleChargeFailed($payload)`: Logged but likely superseded by invoice events for pitch payments.
    *   Other customer/subscription events (`customer.subscription.*`, `customer.updated`): Logged, may be used by base Cashier functionality or future features.
*   **Helper Methods:**
    *   `syncUserInvoicesFromPayload()`: Finds user by Stripe customer ID in payload and calls `createOrGetStripeCustomer()` (which syncs invoices) or `downloadInvoices()`.
    *   `missingInvoiceId()`: Returns a 400 response if invoice ID is missing.
*   **Interaction:** Acts as the listener for critical Stripe events. It translates these events into actions within the application, primarily by calling `PitchWorkflowService` to update the payment status of pitches based on invoice outcomes. Ensures payment status is updated even if the user closes their browser before the payment fully processes. Also plays a role in keeping local user invoice data synchronized via Cashier.

## `Billing/AdminBillingController` Analysis

**Status:** Complete (Admin Feature).

**File:** `app/Http/Controllers/Billing/AdminBillingController.php`

Provides administrative actions related to user billing, likely intended for use within an admin panel like Filament.

*   **Purpose:** Allow an administrator to manually create a Stripe Customer object for a specific user within the application.
*   **Dependencies:** Models (`User`), Admin Panel Components (`Filament\Notifications\Notification`), `Request`.
*   **Core Methods:**
    *   `createStripeCustomer(Request $request, User $record)`:
        *   Takes a `User` model instance (`$record`).
        *   Checks if the user already has a `stripe_id`.
        *   If not, calls `$record->createAsStripeCustomer()` (from Cashier's `Billable` trait) with user details and admin-specific metadata.
        *   Sends a success notification via Filament's notification system.
        *   If a `stripe_id` already exists, sends an informational notification.
        *   Catches exceptions during creation and sends a danger notification.
        *   Redirects back to the previous page (presumably within the admin panel).
*   **Interaction:** Designed to be triggered from an admin interface (likely a button or action on a user resource page in Filament). It uses Cashier's functionality to interact with Stripe and provides feedback to the administrator via Filament's notification system. Allows manual setup of Stripe customers if needed, perhaps for users created manually or before automatic creation logic was triggered.

*(End of Controller analysis based on provided list)*