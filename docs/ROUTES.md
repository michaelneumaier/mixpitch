# Routes (`routes/`)

## Index

*   [`web.php`](#webphp-analysis)
*   [`api.php`](#apiphp-analysis)
*   [`channels.php`](#channelsphp-analysis)
*   [`console.php`](#consolephp-analysis)

---

*   **`web.php`**: Defines the primary web routes for the application.
*   **`api.php`**: Defines API routes.
*   **`channels.php`**: Defines event broadcasting channels.
*   **`console.php`**: Defines Artisan console commands.

## `web.php` Analysis

**Status:** Complete.

This file defines the main user-facing routes. Key functionalities include:

*   **Authentication:** Standard Laravel Jetstream/Fortify routes (`login`, `register`, etc.), email verification (`verification.*` names), and socialite logins (`/auth/{provider}/...`). Most authenticated routes are grouped under `auth:sanctum` and `verified` middleware.
*   **Dashboard:** `/dashboard` handled by `DashboardController`.
*   **Core Resource Management:**
    *   **Projects (`/projects/...`):**
        *   Managed primarily by `ProjectController`.
        *   Livewire components `CreateProject` (`/create-project`, `/edit-project/{project}`) and `ManageProject` (`/manage-project/{project}`) handle creation, editing, and management views.
        *   Includes routes for listing (`projects.index`), showing (`projects.show`), storing (`projects.store`), updating (`projects.update`), deleting (`projects.destroy`), uploading files (`project.uploadFile`), deleting files (`projects.deleteFile`), and downloading (`projects.download`).
        *   Routes for creating associated Mixes (`mixes.create`, `mixes.store`) and Pitches (`projects.pitches.create`, `projects.pitches.store`).
    *   **Pitches (`/projects/{project}/pitches/{pitch}/...`):**
        *   Managed by `PitchController`, `PitchSnapshotController`, and related payment controllers (`PitchPaymentController`, `BillingController`).
        *   Structured nested under Projects.
        *   Includes routes for showing (`projects.pitches.show`), editing (`projects.pitches.edit`), updating (`projects.pitches.update`), changing status (`projects.pitches.change-status`), viewing snapshots (`projects.pitches.snapshots.show` using `ShowSnapshot` Livewire component), managing snapshot lifecycle (approve/deny/request changes via `PitchSnapshotController`), and handling payments (`projects.pitches.payment.*`).
        *   Includes a route to revert a completed pitch (`projects.pitches.return-to-approved`).
    *   **Mixes (`/mixes/...`):**
        *   Managed by `MixController`.
        *   Routes for rating mixes (`mixes.rate`).
    *   **Pitch Files (`/pitch-files/...`):**
        *   Managed by `PitchFileController`.
        *   Routes for showing (`pitch-files.show`) and downloading (`pitch-files.download`) with specific access middleware (`pitch.file.access`).
*   **Billing (`/billing/...`):**
    *   Managed by `BillingController` and `WebhookController`.
    *   Handles Stripe integration (Cashier).
    *   Includes routes for viewing billing overview (`billing.index`), managing payment methods (`billing.payment.update`, `billing.payment.remove`, `billing.payment-methods`), processing payments (`billing.payment.process`), viewing/downloading invoices (`billing.invoices`, `billing.invoice.show`, `billing.invoice.download`), accessing the customer portal (`billing.portal`), checkout (`billing.checkout`), and handling Stripe webhooks (`cashier.webhook`).
*   **User Profiles:**
    *   Public profile view by username (`/@username`) via `UserProfileController`.
    *   Profile editing (`/profile/edit`) likely uses Livewire (`user-profile.edit-livewire` view).
*   **Static Pages:** `/about` (`AboutController`), `/pricing` (`PricingController`).
*   **Testing & Debugging:**
    *   Routes for testing email sending (`/email/test`).
    *   Routes for testing an audio processor, possibly an AWS Lambda function (`/test-audio-processor`, `/test-lambda-direct`, `/test-lambda-with-file`).
    *   Fallback GET routes for POST-only snapshot actions to provide debug information.

**Controllers Used:** `DashboardController`, `MixController`, `ProjectController`, `PitchController`, `PitchFileController`, `AboutController`, `PricingController`, `UserProfileController`, `Admin\StatsController`, `Billing\BillingController`, `UserController`, `PitchSnapshotController`, `PitchStatusController`, `EmailController`, `PitchPaymentController`, `Auth\SocialiteController`, `TestAudioProcessorController`, `Billing\WebhookController`.

**Livewire Components Used:** `CreateProject`, `ManageProject`, `Pitch\Snapshot\ShowSnapshot`.

## `api.php` Analysis

**Status:** Complete.

This file defines routes intended for API access. Currently, it includes:

*   **/user:** A standard Laravel Sanctum route (`auth:sanctum` middleware) that returns the authenticated user's information.
*   **/webhooks/ses:** A POST endpoint (`webhooks.ses`) handled by `SesWebhookController` to process incoming webhooks from AWS Simple Email Service (SES). This is likely used for tracking email delivery events (bounces, complaints, etc.). It's rate-limited using the `throttle:60,1` middleware.

**Controllers Used:** `SesWebhookController`.

## `channels.php` Analysis

**Status:** Complete.

This file defines the authorization callbacks for broadcast channels used with Laravel Echo/Broadcasting.

*   **`App.Models.User.{id}`:** A private channel where authorization is granted if the authenticated user's ID matches the `{id}` in the channel name. Standard for user-specific real-time updates.
*   **`notifications.{id}`:** Another private channel, functionally similar to the above, authorizing based on the authenticated user's ID matching the channel's `{id}`. Likely used for pushing notifications to specific users.

The existence of these channels confirms the use of real-time broadcasting features in the application.

## `console.php` Analysis

**Status:** Complete.

This file allows for defining Artisan console commands using Closures.

*   **`inspire` command:** The default Laravel command is present, which displays an inspiring quote.

Currently, no custom application-specific commands are defined directly within this file using Closures. Custom commands are typically implemented as classes within the `app/Console/Commands` directory. 