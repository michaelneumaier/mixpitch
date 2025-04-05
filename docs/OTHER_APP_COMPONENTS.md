# Other `app` Directory Components

*   **Actions (`app/Actions/`)**: Jetstream/Fortify actions.
*   **Console (`app/Console/`)**: Artisan commands.
*   **Events (`app/Events/`)**: Application events.
*   **Exceptions (`app/Exceptions/`)**: Custom exception handling.
*   **Filament (`app/Filament/`)**: Filament Admin Panel resources.
*   **Helpers (`app/Helpers/`)**: Custom helper functions.
*   **Http (`app/Http/`)**: Controllers, Middleware, Requests.
*   **Jobs (`app/Jobs/`)**: Queueable jobs.
*   **Mail (`app/Mail/`)**: Mailable classes.
*   **Notifications (`app/Notifications/`)**: Notification classes.
*   **Observers (`app/Observers/`)**: Model observers.
*   **Policies (`app/Policies/`)**: Authorization policies.
*   **Providers (`app/Providers/`)**: Service providers.
*   **View (`app/View/`)**: View composers/components.

## Actions (`app/Actions/`)

*   **Purpose:** Contains action classes primarily published by Laravel Jetstream and Fortify.
*   **Structure:** Subdirectories `Fortify/` and `Jetstream/`.
*   **Fortify Actions:** Includes `CreateNewUser`, `UpdateUserProfileInformation`, `UpdateUserPassword`, `PasswordValidationRules`, `ResetUserPassword`. These handle core authentication and basic profile tasks.
*   **Jetstream Actions:** Includes `DeleteUser`. Handles account deletion.
*   **Analysis:** These are mostly standard framework actions. Significant custom logic is unlikely unless specific actions (like `CreateNewUser`) have been modified (e.g., to assign default roles or perform other tasks on registration).

*(Further detailed analysis pending...)*

## Console (`app/Console/`)

*   **Purpose:** Defines Artisan console commands and scheduled tasks.
*   **Structure:** Contains `Kernel.php` (command registration, scheduling) and `Commands/` subdirectory.
*   **Custom Commands (`Commands/`):**
    *   Data Maintenance: `GeneratePitchSlugs`, `CalculatePitchStorageUsed`, `CalculateProjectStorageUsed`, `UpdatePitchFileSizes`.
    *   Synchronization: `SyncStripeInvoices`.
    *   Background Processing: `GenerateWaveforms`, `GenerateWaveformForFile`.
    *   Cleanup: `CleanupTemporaryUploads`, `CleanupCachedZips`.
    *   Testing/Utility: `SendTestEmail`.
*   **Analysis:** Provides various utility and maintenance commands. Some may be run manually, others scheduled in `Kernel.php`.

*(Further detailed analysis pending...)*

## Events (`app/Events/`)

*   **Purpose:** Defines application events.
*   **Structure:** Contains `NotificationCreated.php`.
*   **`NotificationCreated`:**
    *   Implements `ShouldBroadcast`.
    *   Dispatched when a new `App\Models\Notification` is created (likely by `NotificationService`).
    *   Broadcasts on a private channel (`notifications.{user_id}`).
    *   Sends notification ID, type, and timestamp.
    *   Used for real-time updates of notification UI components.

*(Further detailed analysis pending...)*

*(Detailed analysis pending...)*

## Exceptions (`app/Exceptions/`)

*   **Purpose:** Defines custom application exceptions and handles exception reporting/rendering.
*   **Structure:** Contains `Handler.php` and subdirectories for domain-specific exceptions (`Pitch/`, `File/`, `Project/`).
*   **`Handler.php`:** Main Laravel exception handler, may include custom reporting/rendering logic.
*   **Custom Exceptions:** Domain-specific exceptions used by services and controllers to signal errors related to Pitch workflows (`InvalidStatusTransitionException`, `SnapshotException`, etc.), File operations (`FileUploadException`, `StorageLimitException`, etc.), and Project operations (`ProjectCreationException`, etc.).

*(Further detailed analysis pending...)*

## Filament (`app/Filament/`)

*   **Purpose:** Contains the PHP classes defining the Filament Admin Panel resources, pages, widgets, and plugins.
*   **Structure:** Organized into standard Filament directories (`Resources/`, `Pages/`, `Widgets/`, `Plugins/`).
*   **Content:** Includes resources for managing core models (User, Project, Pitch, Files, Email models), custom dashboard pages, various statistical widgets, and a custom Billing plugin.
*   **Analysis:** The code defines the structure, forms, tables, actions, and logic for the admin panel using Filament's API. Detailed analysis would require specific investigation into Filament resource/page/widget definitions.

*(Further detailed analysis pending...)*

## Helpers (`app/Helpers/`)

*   **Purpose:** Contains custom helper functions/classes.
*   **Structure:** Contains `RouteHelpers.php`.
*   **`RouteHelpers`:**
    *   Provides static methods (`pitchUrl`, `pitchPaymentUrl`, `pitchReceiptUrl`, `pitchEditUrl`) specifically for generating URLs for nested Pitch routes (e.g., `projects.pitches.show`).
    *   Handles loading the `project` relationship for the given `Pitch` and includes error handling/logging if the project cannot be determined.
    *   Ensures correct parameters (`project` model, `pitch` model) are passed to the `route()` helper.
*   **Analysis:** Centralizes the logic for generating specific complex URLs, improving consistency and robustness.

*(Further detailed analysis pending...)*

## Jobs (`app/Jobs/`)

*   **Purpose:** Contains queueable jobs for background processing.
*   **Structure:** Contains `GenerateAudioWaveform.php`.
*   **`GenerateAudioWaveform`:**
    *   Handles asynchronous waveform generation for a `PitchFile`.
    *   Retrieves S3 URL for the file.
    *   Calls an external AWS Lambda function (URL from `config/services.php`) via HTTP POST, passing the file URL and desired peak count. Includes logic for encoding URL and ensuring `/waveform` suffix.
    *   Handles potentially nested/escaped JSON responses from Lambda/API Gateway.
    *   Includes fallback logic (`generateFallbackWaveformData`, `estimateDurationFromFileSize`, `generatePlaceholderWaveform`) if Lambda fails or is not configured.
    *   Updates the `PitchFile` model with `waveform_peaks`, `duration`, and `waveform_processed` status upon completion or failure (after retries).
    *   Configured with retries and timeout.
    *   **Note:** Currently disables SSL verification (`verify => false`) for HTTP calls, which needs to be removed for production.
*   **Analysis:** Centralizes the complex logic for interacting with the external audio processing service (Lambda) and includes fallback mechanisms.

*(Further detailed analysis pending...)*

## Mail (`app/Mail/`)

*   **Purpose:** Defines Mailable classes for sending emails.
*   **Structure:** Contains `PaymentReceipt.php` and `TestMail.php`.
*   **`PaymentReceipt`:**
    *   Handles sending payment confirmation/receipt emails (or free project completion emails).
    *   Implements `ShouldQueue`.
    *   Accepts Pitch, Project, amount, invoice ID, and recipient type.
    *   Uses `emails.payment.receipt` Blade view.
    *   Dynamically sets subject line and recipient name.
*   **`TestMail`:**
    *   Simple Mailable for the email testing utility (`EmailController`).
    *   Uses `emails.test` Blade view.
    *   Fixed subject line.

*(Further detailed analysis pending...)*

## Notifications (`app/Notifications/`)

*   **Purpose:** Defines standard Laravel Notification classes.
*   **Structure:** Contains `Pitch/PitchSubmittedNotification.php`.
*   **`PitchSubmittedNotification`:**
    *   Notifies project owner when a pitch is submitted.
    *   Implements `ShouldQueue`.
    *   Uses `database` and `mail` channels.
    *   `toMail`: Sends a formatted email using `MailMessage` with a link to the project manage page.
    *   `toArray`: Defines data stored in the **default** `notifications` table (likely not the one used by `NotificationService` / UI).
*   **Analysis:** This confirms the use of Laravel's built-in Notification system alongside the custom `NotificationService` + `App\Models\Notification` system. This specific notification handles email sending for pitch submissions. The reason for the dual system needs clarification (see `RESEARCH_AREAS.md`).

*(Further detailed analysis pending...)*

## Observers (`app/Observers/`)

*   **Purpose:** Contains Eloquent model observers.
*   **Structure:** Contains `PitchObserver.php`.
*   **`PitchObserver`:**
    *   Observes `created`, `updated`, `deleted`, `restored`, `forceDeleted` events for the `Pitch` model.
    *   On these events (specifically on status change for `updated`), it calls a private `syncProjectStatus` method.
    *   `syncProjectStatus` fetches the related `Project`, refreshes it, and calls `$project->syncStatusWithPitches()` (method assumed to be on the `Project` model) to potentially update the project's status based on the collective status of its pitches.
    *   Requires registration in a service provider (e.g., `EventServiceProvider`).
*   **Analysis:** Links changes in Pitch lifecycle/status to potential automatic updates of the parent Project's status.

*(Further detailed analysis pending...)*

## Policies (`app/Policies/`)

*   **Purpose:** Defines authorization rules controlling user actions on models.
*   **Structure:** Contains policies for `Project`, `Pitch`, `PitchFile`.
*   **Registration:** Assumed to be registered in `AuthServiceProvider`.

### ProjectPolicy
*   **Standard Actions:**
    *   `viewAny`, `view`, `create`: Allows any authenticated user.
    *   `update`: Allows project owner only.
    *   `delete`, `restore`, `forceDelete`: Allows any authenticated user (**Potential Issue:** `delete` seems too permissive).
*   **Custom Actions:**
    *   `createPitch`: Allows user if not owner and project is open.
    *   `publish`, `unpublish`: Allows project owner only.
    *   `uploadFile`: Allows project owner only.
    *   `deleteFile`: Allows project owner only (checks `$projectFile->project->user_id`).
    *   `download`: Allows project owner only (**Potential Issue:** May prevent pitch creators from downloading files).

### PitchPolicy
*   **Purpose:** Defines rules for `Pitch` actions, handling permissions for both pitch creator and project owner.
*   **Key Methods & Logic:**
    *   `view`: Allows pitch owner & project owner.
    *   `update`, `delete`, `submitForReview`, `cancelSubmission`, `uploadFile`: Allows **pitch owner** under specific pitch status conditions.
    *   `approveInitial`, `approveSubmission`, `denySubmission`, `requestRevisions`, `complete`, `returnToApproved`, `manageReview`: Allows **project owner** under specific pitch status and payment status conditions.
    *   `createPitch`: (Mirrors `ProjectPolicy`) Allows user if not project owner, project is open, and user hasn't pitched.
    *   `manageAccess`: Allows pitch owner & project owner.
*   **Overall:** Tightly controls workflow actions based on user role and pitch state.

### PitchFilePolicy
*   **Purpose:** Defines rules for `PitchFile` actions.
*   **Key Methods & Logic:**
    *   `view`, `downloadFile`: Allows pitch owner & project owner.
    *   `deleteFile`: Allows **pitch owner** if the associated pitch is `in_progress` or `revisions_requested`.
    *   `uploadFile`: **Redundant?** Takes a `Pitch`, not `PitchFile`. Allows pitch owner if pitch is `in_progress` or `revisions_requested`. This logic likely covered by `PitchPolicy::uploadFile`.

*(Marking Policy section as complete for now)*

## Providers (`app/Providers/`)

*   **Purpose:** Central location for bootstrapping application services, registering bindings, listeners, etc.
*   **Structure:** Contains standard Laravel providers, package-specific providers (Jetstream, Fortify, Filament), and custom providers.

### AppServiceProvider
*   **`register()`:**
    *   Explicitly binds `PitchWorkflowService`, manually resolving its `NotificationService` dependency (potentially for debugging DI issues).
*   **`boot()`:**
    *   Registers a custom Livewire component (`UpdateProfileInformationForm`) to override a Jetstream default.
    *   Registers several Livewire components for the Filament Billing plugin's widgets.
    *   Registers a `@recaptchav3` Blade directive for Google ReCaptcha V3 integration, using a service bound as `'recaptcha'`.

### AuthServiceProvider
*   **Purpose:** Registers Model Policies for authorization.
*   **`$policies` Array:**
    *   Maps `Pitch` -> `PitchPolicy`.
    *   Maps `Project` -> `ProjectPolicy`.
    *   Maps `PitchFile` -> `PitchFilePolicy`.
    *   Maps `ProjectFile` -> `ProjectPolicy` (confirming `ProjectFile` actions are authorized via `ProjectPolicy`).
*   **`boot()` Method:** Empty; relies solely on policy mapping.

### EventServiceProvider
*   **Purpose:** Registers event listeners and model observers.
*   **`$listen` Array:**
    *   Maps `Registered` event -> `SendEmailVerificationNotification` listener (standard email verification).
*   **`boot()` Method:**
    *   Registers `PitchObserver` for the `Pitch` model (`Pitch::observe(...)`).
*   **`shouldDiscoverEvents()`:** Returns `false` (automatic discovery disabled).

### RouteServiceProvider
*   **Purpose:** Configures route loading, rate limiting, and post-authentication redirect path.
*   **`HOME` Constant:** `/dashboard`.
*   **`boot()` Method:**
    *   Configures `api` rate limiter (60/min by user/IP).
    *   Configures `register` rate limiter (3/hour by IP) for registration routes.
    *   Loads `routes/api.php` (with `api` middleware, `/api` prefix).
    *   Loads `routes/web.php` (with `web` middleware).

### FortifyServiceProvider
*   **Purpose:** Configures Laravel Fortify (backend authentication).
*   **`boot()` Method:**
    *   Registers custom Action classes (`App\Actions\Fortify\*`) for user creation, profile updates, password updates, and password resets.
    *   Sets the registration view to `auth.register`.
    *   Configures `login` rate limiter (5/min by login ID/IP).
    *   Configures `two-factor` rate limiter (5/min by session user ID).

### JetstreamServiceProvider
*   **Purpose:** Configures Laravel Jetstream (UI scaffolding, profile management, API tokens, etc.).
*   **`boot()` Method:**
    *   Registers a custom Action class (`App\Actions\Jetstream\DeleteUser`) for user deletion.
    *   Calls `configurePermissions()`.
*   **`configurePermissions()` Method:**
    *   Sets default API token permissions to `['read']`.
    *   Defines application-wide permissions as `['create', 'read', 'update', 'delete']`.

### FilamentBillingServiceProvider
*   **Purpose:** Configures the custom Filament Billing plugin (`App\Filament\Plugins\Billing\*`).
*   **`boot()` Method:**
    *   Registers Livewire components (Widgets and Pages) for the billing plugin.
    *   Attempts to configure view publishing and loading for the plugin (`filament-billing::` namespace) but uses paths pointing to the main application's `resources/views` directory, which seems incorrect (**Potential Issue**).
    *   Registers Stripe.js (`https://js.stripe.com/v3/`) as a Filament asset (`stripe-js`).
    *   Registers custom Filament icons (`billing`, `invoice`, `payment`) mapped to Heroicons.

*(Further detailed analysis pending...)*

### TipjarServiceProvider
*   **Purpose:** Configures a "Tip Jar" feature, specifically URL validation.
*   **`$allowedDomains`:** Static array listing approved domains (PayPal, Ko-fi, etc.).
*   **`boot()` Method:**
    *   Registers a custom validation rule `allowed_tipjar_domain` using `Validator::extend()`.
    *   The rule checks if a given URL's domain (after parsing and stripping `www.`) is in the `$allowedDomains` list.
    *   Handles nullable values and provides a custom error message.

*(Further detailed analysis pending...)*

### EmailServiceProvider
*   **Purpose:** Registers the custom `EmailService`.
*   **`register()` Method:**
    *   Binds `App\Services\EmailService` as a singleton in the service container (`$this->app->singleton(...)`).
*   **`boot()` Method:** Empty.

*(Further detailed analysis pending...)*

### BroadcastServiceProvider
*   **Purpose:** Configures Laravel event broadcasting.
*   **`boot()` Method:**
    *   Registers broadcast authentication routes (`Broadcast::routes()`).
    *   Loads channel authorization logic from `routes/channels.php`.

*(Further detailed analysis pending...)*

### Filament\AdminPanelProvider
*   **Purpose:** Configures the main Filament admin panel (`/admin`).
*   **Key Configurations:**
    *   Sets ID, path, login, colors, font, favicon, Vite theme.
    *   Defines navigation groups ('Email Management', 'Content Management', etc.).
    *   Sets `homeUrl` to `/dashboard` (main app dashboard, **potential issue** as it directs away from admin panel post-login).
    *   Enables collapsible sidebar, full width, global search.
*   **Registered Components:**
    *   **Resources:** `Project`, `Pitch`, `ProjectFile`, `User`, `EmailAudit`, `EmailEvent`, `EmailSuppression`, `EmailTest`.
    *   **Pages:** `Dashboard`, `Settings`, `EmailAuditPage`, `EmailSuppressionPage`.
    *   **Widgets:** `StatsOverview`, `ProjectStats`, `UserVerificationStats`, `EmailStats`, `UserActivity`, `EmailActivityChart`, `LatestProjects`, `LatestPitches`, `FilesOverview`, `AccountWidget` (built-in).
*   **Middleware:** Standard web middleware + Filament Auth.
*   **Plugins:** Registers `BillingPlugin`.

*(Marking Providers section as complete for now)*

## Services (`app/Services/`)

*   **Purpose:** Contains classes encapsulating core business logic, coordinating tasks, and interacting with different application components (models, notifications, external APIs).
*   **Structure:** Includes services for major features (Pitch Workflow, Invoicing, Files, Notifications, Email) and potentially subdirectories for more specialized services.

### PitchWorkflowService
*   **Purpose:** Orchestrates the entire `Pitch` lifecycle, managing status transitions, validations, events, and notifications.
*   **Dependencies:** `NotificationService`, Models (`Project`, `Pitch`, `User`, `PitchSnapshot`), Facades (`DB`, `Log`, `Auth`), Custom Exceptions.
*   **Key Responsibilities & Methods:**
    *   **Pitch Creation:** `createPitch()` (handles initial checks, DB transaction, event, notification).
    *   **Initial Approval:** `approveInitialPitch()` (owner approves pending pitch -> in_progress).
    *   **Submission Review:**
        *   `approveSubmittedPitch()` (owner approves ready_for_review pitch -> approved; updates snapshot).
        *   `denySubmittedPitch()` (owner denies -> denied; updates snapshot).
        *   `requestPitchRevisions()` (owner requests changes -> revisions_requested; updates snapshot).
    *   **Pitch Creator Actions:**
        *   `submitPitchForReview()` (creator submits in_progress/revisions_requested -> ready_for_review; creates snapshot).
        *   `cancelPitchSubmission()` (creator cancels ready_for_review pitch).
    *   **Status Reversions:**
        *   `returnPitchToReview()` (admin/owner action to move back to review state).
        *   `returnPitchToApproved()` (reverts completed pitch to approved if payment failed/pending).
    *   **Payment Status:**
        *   `markPitchAsPaid()` (updates status, stores Stripe IDs).
        *   `markPitchPaymentFailed()` (updates status, logs reason).
*   **Patterns:** Heavy use of DB transactions, status validation, authorization checks (mirroring policies), `PitchEvent` creation for history, and `NotificationService` integration.

*(Further detailed analysis pending...)*

### InvoiceService
*   **Purpose:** Handles Stripe Invoice creation, processing, and retrieval, primarily for `Pitch` payments.
*   **Dependencies:** Models (`Pitch`, `Project`, `User`), Facades (`Auth`, `Log`), Stripe PHP SDK.
*   **Key Methods & Logic:**
    *   `newStripeClient()`: (Protected) Centralizes Stripe client instantiation.
    *   `createPitchInvoice()`: Creates a Stripe customer (if needed), creates a non-auto-advancing Stripe Invoice, adds an Invoice Item for the pitch amount, and stores metadata (`pitch_id`, `project_id`, `source`). Does *not* process payment.
    *   `processInvoicePayment()`: Takes an Invoice object and Payment Method ID, finalizes the invoice (handles already-finalized cases), and attempts payment using `off_session = true`.
    *   `getInvoice()`: Retrieves a specific Stripe Invoice by ID, expands related data, and formats the result.
    *   `getUserInvoices()`: Retrieves a list of Stripe Invoices for the authenticated user, expands related data, and formats the results.
*   **Patterns:** Direct Stripe API interaction, separation of invoice creation and payment, metadata storage for linking, formatting Stripe data for application use, logging.

*(Further detailed analysis pending...)*

### FileManagementService
*   **Purpose:** Centralizes file operations (upload, delete, download link generation) for Projects and Pitches, interacting with S3 storage.
*   **Dependencies:** Models (`Project`, `Pitch`, `ProjectFile`, `PitchFile`, `User`), Facades (`Storage`, `DB`, `Log`), Jobs (`GenerateAudioWaveform`), Config (`files.*`), Custom Exceptions.
*   **Key Methods & Logic:**
    *   `uploadProjectFile()` / `uploadPitchFile()`: Handles upload requests. Validates size/capacity (requires model methods `hasStorageCapacity`/`incrementStorageUsed`), stores file on S3 (`projects/` or `pitches/`), creates DB record, updates storage usage. Dispatches waveform job for pitch audio. Assumes caller handles authorization.
    *   `deleteProjectFile()` / `deletePitchFile()`: Handles deletion. Uses DB transaction, deletes DB record, updates storage usage (requires model method `decrementStorageUsed`), deletes S3 file. Assumes caller handles authorization/status checks.
    *   `getTemporaryDownloadUrl()`: Generates time-limited S3 download URLs (`Storage::temporaryUrl`). Assumes caller handles authorization.
    *   `setProjectPreviewTrack()` / `clearProjectPreviewTrack()`: Manages the association of a specific `ProjectFile` as the project's preview track.
*   **Storage:** Explicitly uses `Storage::disk('s3')`.
*   **Assumptions:** Relies on specific storage tracking methods in Project/Pitch models and expects authorization to be handled by the caller.

*(Further detailed analysis pending...)*

### PitchCompletionService
*   **Purpose:** Handles the business logic when a project owner marks an approved pitch as complete.
*   **Dependencies:** `ProjectManagementService`, `NotificationService`, Models (`Pitch`, `Project`, `User`, `PitchSnapshot`), Facades (`DB`, `Log`), Custom Exceptions.
*   **Key Method:** `completePitch()`
    *   **Authorization:** Checks if user is project owner (policy check noted).
    *   **Validation:** Checks if pitch is `APPROVED` and not already paid/processing.
    *   **DB Transaction:** Wraps all actions.
        1.  Updates selected `Pitch` status to `COMPLETED`, sets `completed_at`, stores feedback, sets initial `payment_status` (`PENDING` or `NOT_REQUIRED`).
        2.  Updates the current `PitchSnapshot` status to `COMPLETED`.
        3.  Updates other active pitches on the same project to `CLOSED` (and denies their pending snapshots), notifies their creators (`NotificationService::notifyPitchClosed`).
        4.  Calls `ProjectManagementService::completeProject()` to update the parent `Project`.
        5.  Creates a `PitchEvent` for the completed pitch.
        6.  Notifies the completed pitch creator (`NotificationService::notifyPitchCompleted`).
    *   Returns the completed `Pitch` model.
*   **Overall:** Orchestrates the multi-step finalization process involving the selected pitch, other pitches, the project, events, and notifications, ensuring atomicity.

*(Further detailed analysis pending...)*

### NotificationService
*   **Purpose:** Manages the custom, database-driven notification system (`App\Models\Notification`) and triggers real-time UI updates.
*   **Dependencies:** `App\Models\Notification` and related models (`Pitch`, `User`, etc.), `App\Events\NotificationCreated`, Facades (`Log`, `DB`).
*   **Core Method:** `createNotification()` (private/protected)
    *   Called by all public `notify*` methods.
    *   Prevents duplicate notifications within a short time window.
    *   Creates `App\Models\Notification` record (stores user, type, related model, JSON data).
    *   Dispatches `NotificationCreated` event (likely for broadcasting via Echo).
    *   Includes extensive logging and error handling.
*   **Public Methods (`notify*`):**
    *   Provide specific wrappers for various application events (pitch lifecycle, comments, files, payments, snapshots).
    *   Determine the recipient, notification `type`, `related` model, and contextual `data` array.
    *   Examples: `notifyPitchStatusChange`, `notifyPitchCompleted`, `notifyPitchComment`, `notifySnapshotApproved`, `notifyPaymentProcessed`, etc.
*   **Overall:** Central service for the custom notification system, distinct from `App\Notifications`. Standardizes creation, prevents spam, ensures data persistence, and enables real-time updates.

*(Further detailed analysis pending...)*

### PitchService
*   **Purpose:** Provides basic CRUD-like operations (update, delete) and generic status changes for `Pitch` models.
*   **Dependencies:** `PitchWorkflowService` (injected but **unused** in visible methods), Models (`User`, `Pitch`), Exceptions (`InvalidStatusTransitionException`, `AuthorizationException`).
*   **Key Methods & Logic:**
    *   `updatePitch()`: Authorizes (`can('update', $pitch)`) and updates pitch attributes.
    *   `deletePitch()`: Authorizes (`can('delete', $pitch)`) and deletes the pitch.
    *   `changePitchStatus()`: Authorizes (`can('update', $pitch)`), validates the transition using `determineStatusChangeDirection()`, and calls `$pitch->changeStatus()` (method assumed on Pitch model).
    *   `determineStatusChangeDirection()`: (Private) Checks a static `Pitch::$transitions` array to see if a status change is valid (forward/backward).
*   **Observations:**
    *   Provides simpler update/delete functionality compared to `PitchWorkflowService`.
    *   The generic `changePitchStatus` overlaps with more specific methods in `PitchWorkflowService`; its exact usage context is unclear.
    *   Injected `PitchWorkflowService` is unused in the analyzed methods.

*(Further detailed analysis pending...)*

### EmailService
*   **Purpose:** Centralized service for sending/queuing emails, wrapping `Mail` facade with suppression checks and detailed auditing.
*   **Dependencies:** Models (`EmailAudit`, `EmailEvent`, `EmailSuppression`), `Mailable`, Facades (`Mail`, `Log`).
*   **Key Features & Methods:**
    *   **Suppression Check:** `isEmailSuppressed()` checks `EmailSuppression` table. `send()`/`queue()` skip sending/queuing and log 'suppressed' audit if recipient is found.
    *   **Sending:** `send()` (public) -> `sendToSingleRecipient()` (protected) uses `Mail::send()`.
    *   **Queuing:** `queue()` (public) -> `queueForSingleRecipient()` (protected) uses `Mail::queue()`.
    *   **Auditing:** Both flows log extensively to `EmailAudit` (status: sent/queued/suppressed/failed, metadata, attempts content capture) and `EmailEvent`.
    *   **Content Capture:** Tries to render Mailable content before send/queue for audit log.
    *   **Test Emails:** `sendTestEmail()` method for sending test messages (used by admin/controllers), also includes suppression checks/logging.
*   **Overall:** Enhances standard mailing with suppression lists and robust auditing/event logging, acting as the primary email interface.

*(Further detailed analysis pending...)*

### Project\ProjectManagementService
*   **Purpose:** Handles core lifecycle actions (create, update, status changes) for `Project` models.
*   **Dependencies:** Models (`Project`, `User`), Facades (`DB`, `Storage`, `Log`), Custom Exceptions (`ProjectCreationException`, `ProjectUpdateException`).
*   **Key Methods & Logic:**
    *   `createProject()`: Creates project, sets owner/status, handles optional image upload (S3).
    *   `updateProject()`: Updates project data, handles optional new image upload (S3) and deletion of old image.
    *   `publishProject()` / `unpublishProject()`: Calls simple status change methods on the Project model (`publish`/`unpublish`). Assumes caller handles authorization.
    *   `completeProject()`: Sets status to `COMPLETED`, sets `completed_at`. Called by `PitchCompletionService`.
    *   `reopenProject()`: Reverts a `COMPLETED` project back to `OPEN`, clears `completed_at`. Likely called when a completed pitch is reverted.
*   **Patterns:** DB transactions for create/update, S3 interaction for images, relies on model for simple status changes, assumes external authorization.

*(Further detailed analysis pending...)*

## View (`app/View/`)

*   **Purpose:** Contains class-based Blade components.
*   **Structure:** Contains `Components/` subdirectory.
*   **Components (`app/View/Components/`):**
    *   `AppLayout.php`, `GuestLayout.php`: Standard layout classes.
    *   `ProjectStatusButton.php`: Passes props (`status`, `type`) to its view.
    *   `PitchStatus.php`: Contains logic (`setColors()` method) to map pitch status strings to Tailwind background/text color classes, which are then used by its view (`components.pitch-status`).
    *   `UpdatePitchStatus.php`, `PitchTermsModal.php`: Pass model props (`Pitch`, `Project`) to their respective views.
*   **Analysis:** Mostly simple components passing data to Blade views, except for `PitchStatus` which handles presentation logic (status->color mapping). Corresponds to views in `resources/views/components/`.

*(Marking View section as complete for now)*

## Models (`app/Models/`)

*   **Purpose:** Defines the Eloquent models representing the application's database entities and their relationships.

### User
*   **Purpose:** Represents application users, integrating authentication, profile, roles, billing, etc.
*   **Base Class/Interfaces:** Extends `Authenticatable`, implements `MustVerifyEmail`, `FilamentUser`.
*   **Traits:** `HasApiTokens`, `HasFactory`, `HasProfilePhoto` (methods overridden), `Notifiable`, `TwoFactorAuthenticatable`, `HasRoles` (Spatie), `Billable` (Cashier).
*   **Attributes:** Standard auth fields plus extensive profile (`username`, `bio`, `website`, `skills`, etc.), `role` column, flags (`profile_completed`).
*   **Profile Photo:** Overrides Jetstream's `updateProfilePhoto` (custom S3/Livewire temp file handling) and `profilePhotoUrl` (returns temporary signed S3 URL).
*   **Roles:** Uses Spatie `HasRoles` trait but also has a custom `hasRole` method checking the `role` column directly (**Potential Conflict/Ambiguity**). Defines role constants. Includes `scopeClients`, `scopeProducers`.
*   **Relationships:** `projects`, `pitches`, `mixes` (likely `hasMany`).
*   **Other:** `hasCompletedProfile()`, `canAccessPanel()`, `getFilamentName()`, `getFilamentAvatarUrl()`.
*   **Overall:** Feature-rich model integrating many packages. Custom S3 photo handling and ambiguous role checking are key points.

*(Further detailed analysis pending...)*

### Project
*   **Purpose:** Represents projects, acting as parent for pitches and files.
*   **Traits:** `HasFactory`, `Sluggable` (auto-generates `slug` from `name`).
*   **Attributes:** Defines status constants (`UNPUBLISHED`, `OPEN`, `IN_PROGRESS`, `COMPLETED`), storage limit constants. Fillable attributes cover project details, status, relationships, budget, deadline etc. Casts `collaboration_type` to array, relevant fields to boolean/datetime.
*   **Route Binding:** Uses `slug`.
*   **Relationships:** `user` (belongsTo), `files` (hasMany), `pitches` (hasMany), `mixes` (hasMany), `previewTrack` (hasOne ProjectFile).
*   **Status/Lifecycle:** `publish()`, `unpublish()` methods (called by service). `isOpenForPitches()` helper.
*   **Storage:** Includes methods `hasStorageCapacity()`, `getRemainingStorageBytes()`, `getStorageUsedPercentage()`, `incrementStorageUsed()`, `decrementStorageUsed()` relied upon by `FileManagementService`. Reads `total_storage_limit_bytes` column or uses constant.
*   **Files:** Accessor `getImageUrlAttribute()` and method `previewTrackPath()` return temporary signed S3 URLs.
*   **Helpers:** `isOwnedByUser()`, `userPitch()`.
*   **Overall:** Core model with status logic, storage management methods, Sluggable, and secure S3 URL generation.

*(Further detailed analysis pending...)*

### Pitch
*   **Purpose:** Represents a pitch submission by a user for a project.
*   **Traits:** `HasFactory`, `Sluggable` (auto-generates `slug` from `title`).
*   **Attributes:** Defines numerous status constants (e.g., `STATUS_PENDING`, `STATUS_READY_FOR_REVIEW`) and payment status constants (`PAYMENT_STATUS_PENDING`, `PAYMENT_STATUS_PAID`). Defines storage constants. Fillable fields cover associations, pitch data, status, payment details.
*   **Status Transitions:** Defines static `$transitions` array mapping allowed forward/backward status changes (used by `PitchService`).
*   **`booted()` Method:** Includes an `updated` listener that logs status changes but has commented-out notification logic (likely handled elsewhere now).
*   **Relationships:** `user` (belongsTo), `project` (belongsTo), `files` (hasMany), `events` (hasMany), `snapshots` (hasMany), `currentSnapshot` (belongsTo).
*   **Route Binding:** Uses `slug`.
*   **Accessors:** `getReadableStatusAttribute`, `getStatusDescriptionAttribute`.
*   **Storage:** Includes methods `hasStorageCapacity`, `getRemainingStorageBytes`, `getStorageUsedPercentage`, `incrementStorageUsed`, `decrementStorageUsed` (similar to Project).
*   **Helpers:** `isOwner()`, `isPaymentFinalized()`, `isInactive()`, `getStatuses()`.
*   **Overall:** Central model managing pitch data, complex status lifecycle (with defined transitions), payment status, associations (snapshots, events, files), and storage.

*(Further detailed analysis pending...)* 