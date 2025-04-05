# Mixpitch Project: High-Level Overview

This document provides a high-level summary of the Mixpitch application's architecture, purpose, and key technologies, based on the available documentation.

## Core Purpose

Mixpitch serves as a platform connecting:

1.  **Clients:** Individuals or entities seeking music production, mixing, or mastering services.
2.  **Producers:** Music professionals offering these services.

Clients post **Projects** detailing their needs, and Producers submit **Pitches** (proposals often including audio demos) to win the work.

## Key Concepts & Entities

*   **Users:** Clients, Producers, Admins (role-based).
*   **Projects:** Client requests, including details, budget, deadline, and associated files (`ProjectFile`).
*   **Pitches:** Producer submissions for a specific Project, including description, status, and associated files (`PitchFile`). Pitches follow a complex workflow (Pending, In Progress, Ready For Review, Approved, Denied, etc.).
*   **Pitch Snapshots:** Point-in-time versions of a Pitch submitted for client review.
*   **Files (`ProjectFile`, `PitchFile`):** User-uploaded assets (documents, audio) stored primarily on AWS S3 with secure access via signed URLs. `PitchFile` includes audio-specific features like waveform data and duration.
*   **Comments (`PitchFileComment`):** Timestamped feedback and discussion threads attached to `PitchFile` audio tracks.
*   **Billing:** Handling payments for completed pitches, potentially subscriptions (via Cashier), invoice management (via Stripe).
*   **Notifications:** Real-time updates and alerts for users regarding project/pitch activity.

## Technology Stack

*   **Framework:** Laravel (latest stable version implied)
*   **Frontend UI/Interactivity:**
    *   Livewire v3 (Core interactive components)
    *   Alpine.js (UI behaviors, modals, state management alongside Livewire)
    *   Tailwind CSS + DaisyUI (Styling and component library)
    *   Blade (Templating engine)
    *   Wavesurfer.js (Audio waveform visualization and playback)
*   **Backend:**
    *   PHP
    *   Eloquent ORM (Database interaction)
    *   Laravel Queues (Background job processing)
    *   Laravel Policies (Authorization)
    *   Laravel Events & Listeners
    *   Laravel Notifications (Primarily for email, alongside a custom DB system)
*   **Database:** Relational (e.g., MySQL, PostgreSQL)
*   **Authentication:** Laravel Jetstream / Fortify
*   **File Storage:** AWS S3
*   **External Services:**
    *   Stripe (Payments, Invoicing, Subscriptions via Cashier & direct SDK)
    *   AWS Lambda (Audio waveform generation)
    *   AWS SES (Transactional Email sending & webhook handling)
*   **Admin Panel:** Filament PHP

## Architecture Overview

*   **MVC-like Structure:** Utilizes Controllers, Models, and Views (Blade).
*   **Service Layer:** Core business logic is encapsulated in Service classes (e.g., `PitchWorkflowService`, `FileManagementService`, `ProjectManagementService`, `InvoiceService`) promoting separation of concerns.
*   **Livewire Dominance:** Many user-facing features (forms, dashboards, file management, players) are built as Livewire components, handling state, validation (often via Form Objects), and interaction with the backend services.
*   **Authorization:** Primarily handled by Laravel Policies associated with Eloquent Models.
*   **Asynchronous Processing:** Laravel Jobs are used for tasks like audio waveform generation.
*   **Real-time Features:** Laravel Echo (likely via Pusher or similar) is used with a custom database notification system (`NotificationService`, `App\Models\Notification`) to provide real-time UI updates.
*   **External API Interaction:** Services like `InvoiceService` and the `GenerateAudioWaveform` job handle communication with Stripe and AWS Lambda respectively.

## Key Workflows

1.  **User Registration & Profile:** Standard authentication flows via Jetstream/Fortify. Profile completion is tracked.
2.  **Project Creation:** Clients create projects via a Livewire form (`CreateProject`), managed by `ProjectManagementService`.
3.  **Project Management:** Owners manage projects (details, files, publishing) via `ManageProject` (Livewire), using `FileManagementService` for file operations (uploads often involve JS -> S3 -> Livewire).
4.  **Pitch Submission:** Producers submit pitches for open projects via `ManagePitch` (Livewire), initiating the workflow managed by `PitchWorkflowService`.
5.  **Pitch Review & Feedback:** Clients review Pitch Snapshots (`ShowSnapshot` Livewire component), provide feedback via timestamped comments (`PitchFilePlayer` Livewire component), and approve/deny/request revisions (actions trigger `PitchWorkflowService` via controllers).
6.  **Pitch Completion & Payment:** Owners select a final pitch (`CompletePitch` Livewire component -> `PitchCompletionService`). Payment processing uses `PitchPaymentController` and `InvoiceService` (Stripe).
7.  **Billing & Invoices:** Users manage payment methods and view/download invoices via dedicated billing controllers interacting with Stripe/Cashier.
8.  **Notifications:** Events throughout the workflows trigger database notifications (`NotificationService`) and potentially emails (Laravel Notifications) to keep users informed.

## Areas for Attention (from `RESEARCH_AREAS.md`)

*   Clarify role management (Spatie vs. simple `role` column in `User`).
*   Review usage/status of `Track` model and `MixController` storage.
*   Ensure production readiness for SES webhook verification and Lambda SSL checks.
*   Investigate potential redundancy/issues in `PitchService`.
*   Understand the dual notification system (`NotificationService` vs. Laravel's built-in).
*   Review potentially overly permissive Policy rules (e.g., `ProjectPolicy::delete`). 